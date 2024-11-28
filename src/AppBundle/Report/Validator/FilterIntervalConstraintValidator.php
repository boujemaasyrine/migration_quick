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

class FilterIntervalConstraintValidator extends ConstraintValidator
{

    /**
     * Checks if the passed value is valid.
     *
     * @param  mixed $value The value that should be validated
     * @param  Constraint $constraint The constraint for the validation
     * @throws
     */
    public function validate($values, Constraint $constraint)
    {
        if (!($values["startDate"] <= $values['endDate'])) {
            $this->context->buildViolation(
                "cashbox_counts_anomalies.validation.start_date_must_be_before_end_dat"
            )->atPath(
                'startDate'
            )->addViolation();
        }

        foreach ($values as $key => $value) {
            if (is_array($values[$key])) {
                if (($values[$key]["firstInput"]
                        && $values[$key]['secondInput']
                        && $values[$key]["operator"] == "and"
                        && $values[$key]["firstInput"] <= $values[$key]['secondInput']) || ($values[$key]["firstInput"]
                        && $values[$key]['secondInput']
                        && $values[$key]["operator"] == "or"
                        && $values[$key]["firstInput"] >= $values[$key]['secondInput'])
                ) {
                    $message_key = "cashbox_counts_anomalies.validation.";
                    switch ($key) {
                        case 'diffCashbox':
                            $message_key .= 'diff_cashbox';
                            break;
                        case 'annulations':
                            $message_key .= 'annulations';
                            break;
                        case 'corrections':
                            $message_key .= 'corrections';
                            break;
                        case 'especes':
                            $message_key .= 'especes';
                            break;
                        case 'titreRestaurant':
                            $message_key .= 'titres_restaurant';
                            break;
                        case 'ecart':
                            $message_key .= 'ecart';
                            break;
                    }
                    $this->context->buildViolation('Le filtre est erroné')
                        ->atPath($key)
                        ->addViolation();
                }
            }
        }
    }
}
