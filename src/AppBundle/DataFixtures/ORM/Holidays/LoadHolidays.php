<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 15/03/2016
 * Time: 17:21
 */

namespace AppBundle\DataFixtures\ORM\Holidays;

use AppBundle\General\Entity\Holiday;
use AppBundle\ToolBox\Service\RestClient;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Httpful\Request;

/**
 * Class LoadHolidays
 */
class LoadHolidays extends AbstractFixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $url = "http://holidayapi.com/v1/holidays";

        $t1 = microtime();
        for ($i = 2010; $i < 2100; $i++) {
            $params = http_build_query(
                array(
                    'country' => 'BE',
                    'year'    => $i,
                )
            );
            $clientRest = Request::get($url."?".$params)
                ->expectsJson()
                ->send();

            $data = $clientRest->body;

            if ($data->status === 200) {
                echo 'Retrieving Holidays for  '.$i."\n";
                foreach ($data->holidays as $key => $value) {
                    $holiday = new Holiday();
                    $holiday->setName($value[0]->name)
                        ->setDate(\DateTime::createFromFormat('Y-m-d', $key));
                    $manager->persist(clone $holiday);
                }
            } else {
                echo 'WARNING Retrieving Holidays for  '.$i."\n";
            }
            $manager->flush();
        }
        $t2 = microtime();

        echo "Time for adding is ".($t2 - $t1)." ms \n";
    }
}
