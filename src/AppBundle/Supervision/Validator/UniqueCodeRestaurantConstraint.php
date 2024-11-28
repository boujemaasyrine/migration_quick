<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 18/03/2016
 * Time: 13:51
 */

namespace AppBundle\Supervision\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueCodeSupplierConstraint
 *
 * @package    AppBundle\Validator
 * @Annotation
 */
class UniqueCodeRestaurantConstraint extends Constraint
{

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return "restaurant_unique_code_validator";
    }
}
