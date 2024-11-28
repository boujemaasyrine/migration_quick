<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 22/02/2016
 * Time: 13:51
 */

namespace AppBundle\Merchandise\Validator;

use AppBundle\Merchandise\Entity\Order;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PlanningDateOrderConstraintValidator extends ConstraintValidator
{

    private $em;

    public function __construct(EntityManager $manager)
    {
        $this->em = $manager;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param  mixed      $value      The value that should be validated
     * @param  Constraint $constraint The constraint for the validation
     * @throws \Exception
     */
    public function validate($value, Constraint $constraint)
    {
        //        if (!$value instanceof Order) {
        //            throw new \Exception("Order Object is expected got ".get_class($value));
        //        }
        //
        //        $plannings =$this->em->getRepository("Merchandise:SupplierPlanning")->findBy(array(
        //            "supplier" => $value->getSupplier()
        //        ));
        //
        //        $orderDay = intval($value->getDateOrder()->format('w'));
        //
        //        foreach($plannings as $p){
        //            if (intval($orderDay) === $p->getOrderDay())
        //            {
        //                return ;
        //            }
        //        }
        //
        //        $this->context
        //            ->buildViolation("order.date_not_programmed")
        //            ->atPath('dateOrder')
        //            ->addViolation();
    }
}
