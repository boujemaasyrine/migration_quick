<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/02/2016
 * Time: 11:27
 */

namespace AppBundle\DataFixtures\ORM\SupplierPlanning;

use AppBundle\Merchandise\Entity\SupplierPlanning;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadSupplierPlanning
 * @package AppBundle\DataFixtures\ORM\SupplierPlanning
 */
class LoadSupplierPlanning extends AbstractFixture
{

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        //       $suppliers = $manager->getRepository("Merchandise:Supplier")->findAll();
        //        foreach ($suppliers as $s){
        //            $n = random_int(1,4);
        //            $i = 0 ;
        //            while($i++<$n){
        //                $planning = new SupplierPlanning();
        //                $planning
        //                    ->setSupplier($s)
        //                    ->setDeliveryDay(random_int(0,6))
        //                    ->setDelay(random_int(0,4))
        //                    ->setOrderDay(random_int(0,6))
        //                    ->setStartTime('10:00')
        //                    ->setEndTime('17:00')
        //                    ->setNbWeek(2);
        //                $manager->persist(clone $planning);
        //            }
        //        }
        //        $manager->flush();
    }
}
