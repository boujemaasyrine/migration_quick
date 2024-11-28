<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/06/2016
 * Time: 10:41
 */

namespace AppBundle\Supervision\Service;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;

class RemoteHistoricService
{

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Restaurant $restaurant
     * @param $type
     * @param $data
     * @param string $status
     * @param array $error
     */
    public function createEntry(
        Restaurant $restaurant,
        $type,
        $data,
        $status = RemoteHistoric::SUCCESS,
        $error = []
    ) {

        $remoteEntry = new RemoteHistoric();
        $remoteEntry->setType($type)
            ->setErrors($error)
            ->setData($data)
            ->setStatus($status)
            ->setOriginRestaurant($restaurant);
        $this->em->persist($remoteEntry);
        $this->em->flush();
    }

    /**
     * @param Restaurant $restaurant
     * @param $type
     * @param $data
     */
    public function createSuccessEntry(Restaurant $restaurant, $type, $data)
    {
        $this->createEntry($restaurant, $type, $data, RemoteHistoric::SUCCESS);
    }

    /**
     * @param Restaurant $restaurant
     * @param $type
     * @param array $error
     */
    public function createFailEntry(Restaurant $restaurant, $type, $error = [])
    {
        $this->createEntry($restaurant, $type, [], RemoteHistoric::FAIL, $error);
    }
}
