<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/02/2016
 * Time: 09:40
 */

namespace AppBundle\DataFixtures\ORM\Merchandise;

use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadSupplier
 * @package AppBundle\DataFixtures\ORM\Merchandise
 */
class LoadSupplier extends AbstractFixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $suppliers = [
            "BIDVEST",
            "COCA COLA ENTREPRISES BELGIUM-",
            "VIDANGES",
            "SOUTIRAGE LUXEMBOURGEOIS",
            "MUNHOWEN",
            "BRASSERIE DE LUX DIEKIRCH",
            "AIR LIQUIDE RODANGE",
            "440-GENK STADSPLEIN",
            "COCA COLA ENTREPRISES BELGIUM",
            "INTERBREW BELGIUM-",
            "ACP BELGIUM",
            "EUROCARBO BENELUX",
            "INTERBREW BELGIUM",
            "EUROCARBO BENELUX-",
            "ACP BELGIUM-",
            "ACHAT LOCAL",
            "AANKOOP LOCAAL",
            "MESSER BELGIUM",
            "604-GB GARE NAMUR",
            "MESSER BELGIUM-",
            "INTERBREW BELGIUM-",
            "426-HASSELT KINEPOLIS",
            "434-BILZEN",
            "744-GENK",
        ];

        //        foreach ($suppliers as $s){
        //            $supplier = new Supplier();
        //            $supplier->setName($s)->setCode(random_int(1000,9999));
        //            $manager->persist(clone $supplier);
        //        }
        //
        //        $manager->flush();
    }
}
