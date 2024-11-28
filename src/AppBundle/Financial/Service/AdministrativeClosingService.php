<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/05/2016
 * Time: 16:38
 */

namespace AppBundle\Financial\Service;

use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;

class AdministrativeClosingService
{
    private $em;
    private $container;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * AdministrativeClosingService constructor.
     *
     * @param EntityManager $em
     * @param Container $container
     * @param Container $container
     */
    public function __construct(EntityManager $em, Container $container, Logger $logger)
    {
        $this->em = $em;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * @return \DateTime
     */
    public function getLastWorkingEndDate(Restaurant $restaurant = null)
    {
        if($restaurant == null)
        {
            $restaurant = $this->container->get('restaurant.service')->getCurrentRestaurant();
        }

        //Wynd Api Todo
        $lastDateString = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'date_fiscale',
                'originRestaurant' => $restaurant,
            )
        );

        if ($lastDateString) {
            $lastDateString = $lastDateString->getValue();
        } else {
            $lastDateString = date('d/m/Y');
        }

        $lastDate = \DateTime::createFromFormat('d/m/Y', $lastDateString);

        return $lastDate;
    }

    /**
     * @return mixed|null
     */
    public function getLastClosingDate($restaurant=null)
    {
        if($restaurant==null){
            $restaurant = $this->container->get('restaurant.service')->getCurrentRestaurant();
        }
        $lastDate = $this->em->getRepository('Financial:AdministrativeClosing')->getLastClosingDate($restaurant);
        if (is_null($lastDate)) {
            $lastDate = new \DateTime('yesterday');
        }

        return $lastDate;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastNonClosedDate($restaurant=null)
    {
        $result = $this->getLastClosingDate($restaurant);
        if ($result != null) {
            $result = $result->add(new \DateInterval('P1D'));
        } else {
            $result = new \DateTime('now');
        }

        return $result;
    }

   public function getNoCloturedRestaurants($entredDate)
   {
       $restaurants = $this->container->get('restaurant.service')->getAllRestaurant();


       echo "The total number of restaurants is = ".count($restaurants)."\n";
       $ids=array();
       foreach ($restaurants as $restaurant) {
           $lastNonClosedDate = $this->getLastNonClosedDate($restaurant);

           if ($lastNonClosedDate != null && $entredDate >= $lastNonClosedDate) {

               $ids[] = (int)$restaurant->getId();

           }
       }
           echo "There is ".count($ids) . " restaurants not closed yet\n";
       return $ids;
}



    /**
     * @return \DateTime|null
     */
    public function getCurrentClosingDate($restaurant=null)
    {
        return $this->getLastNonClosedDate($restaurant);
    }

    public function inChestCount()
    {
        $session = $this->container->get('session');
        if ($session->has('chest_count_admin_closing')) {
            return true;
        }

        return false;
    }

    public function setInChestCount()
    {
        $session = $this->container->get('session');
        $session->set('chest_count_admin_closing', true);
    }

    public function resetInChestCount()
    {
        $session = $this->container->get('session');
        if ($session->has('chest_count_admin_closing')) {
            $session->remove('chest_count_admin_closing');
        }
    }

    public function verifyOpenedTable()
    {
        //Wynd Api Todo
        $opened = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'opened_table',
            )
        );

        if ($opened) {
            $opened = $opened->getValue();
            if (intval($opened) == 1) {
                return true;
            }
        }

        return false;
    }
}
