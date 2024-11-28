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
class UniqueCodeSupplierConstraint extends Constraint
{

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return "supplier_unique_code_validator";
    }
}
