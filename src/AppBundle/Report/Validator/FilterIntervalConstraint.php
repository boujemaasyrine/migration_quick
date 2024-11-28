<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 12/01/2016
 * Time: 12:08
 */

namespace AppBundle\Report\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @package AppBundle\Validator
 * @Annotation()
 */
class FilterIntervalConstraint extends Constraint
{
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
