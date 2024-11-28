<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/12/2015
 * Time: 17:19
 */

namespace AppBundle\Report\Validator;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DatesReportConstraintValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param  mixed $value The value that should be validated
     * @param  Constraint $constraint The constraint for the validation
     * @throws
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($value["startDate"] <= $value['endDate'])) {
            $this->context->buildViolation(
                "portion_control.validation.start_date_must_be_before_end_dat"
            )->atPath(
                'startDate'
            )->addViolation();
            $this->context->buildViolation(
                "portion_control.validation.start_date_must_be_before_end_dat"
            )->atPath(
                'startDate'
            )->addViolation();
        }
    }
}
