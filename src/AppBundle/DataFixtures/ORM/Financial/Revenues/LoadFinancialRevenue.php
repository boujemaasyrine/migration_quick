<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 10/03/2016
 * Time: 15:29
 */

namespace AppBundle\DataFixtures\ORM\Financial\Revenues;

use AppBundle\Financial\Entity\FinancialRevenue;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadFinancialRevenue
 * @package AppBundle\DataFixtures\ORM\Financial\Revenues
 */
class LoadFinancialRevenue extends AbstractFixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $date1 = \DateTime::createFromFormat('d/m/Y', '01/01/2012');
        $date2 = \DateTime::createFromFormat('d/m/Y', '01/01/2017');

        $t1 = $date1->getTimestamp();
        $t2 = $date2->getTimestamp();

        for ($i = $t1; $i <= $t2; $i += 86400) {
            $date = new \DateTime();
            $date->setTimestamp($i);
            $fr = new FinancialRevenue();
            $amount = random_int(1000, 9999) + random_int(100, 999) + random_int(0, 99) + (rand(0, 1) / 10) + rand(
                0,
                1
            );
            $fr->setAmount($amount)
                ->setDate(clone $date);
            $manager->persist(clone $fr);
        }
        $manager->flush();
    }
}
