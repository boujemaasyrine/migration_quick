<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 18/02/2016
 * Time: 13:51
 */

namespace AppBundle\Supervision\Validator;

use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCodeRestaurantConstraintValidator extends ConstraintValidator
{

    private $em;
    private $translator;

    public function __construct(EntityManager $manager, Translator $translator)
    {
        $this->em = $manager;
        $this->translator = $translator;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param $value : The value that should be validated
     * @param Constraint                                 $constraint The constraint for the validation
     */
    public function validate($restaurant, Constraint $constraint)
    {

        if (!empty($this->em->getRepository(Restaurant::class)->getActiveRestaurantCode($restaurant))) {
            $this->context->buildViolation(
                $this->translator->trans("validation.unique_code_constraint")
            )
                ->atPath('code')
                ->addViolation();
        }
    }
}
