<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/04/2016
 * Time: 15:10
 */

namespace AppBundle\Financial\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class MaxAmountWithdrawalConstraint
 *
 * @package    AppBundle\Financial\Validator
 * @Annotation
 */
class MaxAmountWithdrawalConstraint extends Constraint
{

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return "max_amount_withdrawal_validator";
    }
}
