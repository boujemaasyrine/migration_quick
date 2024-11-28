<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/05/2016
 * Time: 11:49
 */

namespace AppBundle\Merchandise\Form\Coefficient;

use AppBundle\Merchandise\Entity\CoefBase;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CoefBaseType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                "startDate",
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
                "endDate",
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

                                    if (!$data instanceof CoefBase) {
                                        return;
                                    }

                                    if ($data->getStartDate() == null) {
                                        return;
                                    }

                                    if (Utilities::compareDates($value, $data->getStartDate()) <= 0) {
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
                'data_class' => CoefBase::class,
            )
        );
    }
}
