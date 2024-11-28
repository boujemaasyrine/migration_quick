<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 01/03/2016
 * Time: 13:24
 */

namespace AppBundle\Merchandise\Form\HelpOrder;

use AppBundle\Merchandise\Entity\OrderHelpTmp;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HelpOrderType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                "startDateLastWeek",
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                "endDateLastWeek",
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value == null || $value == '') {
                                        return;
                                    }

                                    $data = $context->getRoot()->getData();

                                    if (!$data instanceof OrderHelpTmp) {
                                        return;
                                    }

                                    if ($data->getStartDateLastWeek() == null) {
                                        return;
                                    }

                                    if (Utilities::compareDates($value, $data->getStartDateLastWeek()) <= 0) {
                                        $context->buildViolation('La date du fin doit être supérieur à celle de début')
                                            ->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => OrderHelpTmp::class,
            )
        );
    }
}
