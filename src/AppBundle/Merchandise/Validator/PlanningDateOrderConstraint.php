<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 22/02/2016
 * Time: 13:51
 */

namespace AppBundle\Merchandise\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Class PlanningDateOrderConstraint
 *
 * @package    AppBundle\Merchandise\Validator
 * @Annotation
 */
class PlanningDateOrderConstraint extends Constraint
{

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return "planning_order_date_validator";
    }
}
