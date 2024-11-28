<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 16/06/2016
 * Time: 10:17
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

class RestaurantService
{

    private $em;
    private $session;

    public function __construct(EntityManager $em, Session $session)
    {
        $this->em = $em;
        $this->session = $session;
    }

    /**
     * @return \AppBundle\Merchandise\Entity\Restaurant
     */
    public function getCurrentRestaurant()
    {
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find(
            $this->session->get("currentRestaurant")
        );
        if (!$currentRestaurant) {
            throw new \Exception();
        }

        return $currentRestaurant;
    }
public function getAllRestaurant()
{
    $allRestaurant = $this->em->getRepository(Restaurant::class)->findAll();
    return $allRestaurant;
}
    public function getCurrentRestaurantCode()
    {

        if ($this->session->get("currentRestaurant")) {
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find(
                $this->session->get("currentRestaurant")
            );
            if (!$currentRestaurant) {
                throw new \Exception();
            }

            return $currentRestaurant->getCode();
        } else {
            return false;
        }
    }

    /**
     * @param Restaurant $currentRestaurant
     *
     * @return string
     */
    public function getRestaurantOpeningHour()
    {
        $currentRestaurant = $this->getCurrentRestaurant();
        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
            [
                "type" => Parameter::RESTAURANT_OPENING_HOUR,
                "originRestaurant" => $currentRestaurant,
            ]
        );
        if (!is_null($parameter)) {
            return $parameter->getValue();
        }

        return Parameter::RESTAURANT_OPENING_HOUR_DEFAULT;

    }

    /**
     * @param Restaurant $currentRestaurant
     *
     * @return string
     */
    public function getRestaurantClosingHour()
    {
        $currentRestaurant = $this->getCurrentRestaurant();
        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
            [
                "type" => Parameter::RESTAURANT_CLOSING_HOUR,
                "originRestaurant" => $currentRestaurant,
            ]
        );
        if (!is_null($parameter)) {
            return $parameter->getValue();
        }

        return Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT;
    }

    /*
     *
     */
    public function getWorkingHours()
    {
        $currentRestaurant = $this->getCurrentRestaurant();
        $openingHour = ($this->getRestaurantOpeningHour(
                $currentRestaurant
            ) == null)
            ? 0
            : $this->getRestaurantOpeningHour(
                $currentRestaurant
            );
        $closingHour = ($this->getRestaurantClosingHour(
                $currentRestaurant
            ) == null)
            ? 23
            : $this->getRestaurantClosingHour(
                $currentRestaurant
            );
        $hoursArray = array();
        if ($closingHour <= $openingHour) {
            ;
        }
        $closingHour += 24;
        for ($i = intval($openingHour); $i <= intval($closingHour); $i++) {
            $hoursArray[$i] = (($i >= 24) ? ($i - 24) : $i);
        }

        return $hoursArray;
    }

    /**
     * @param Restaurant $restaurant
     * @param $date
     * @return bool
     */
    public function isHistoricDate(Restaurant $restaurant, $date)
    {
        $firstOpening = $restaurant->getFirstOpenning();
        if (is_null($firstOpening)) {
            return false;
        }

        return $firstOpening > $date;
    }


}
