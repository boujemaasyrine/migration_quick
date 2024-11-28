<?php

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Staff\Service\StaffService;
use AppBundle\ToolBox\Service\CommandLauncher;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

/**
 * Class TicketService
 * @package AppBundle\Financial\Service
 */
class TicketService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CommandLauncher
     */
    private $commandLauncher;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StaffService
     */
    private $staffService;

    /**
     * @var RestaurantService
     */
    private $restaurantService;

    /**
     * TicketService constructor.
     * @param EntityManager $entityManager
     * @param CommandLauncher $commandLauncher
     * @param Logger $logger
     * @param StaffService $staffService
     * @param RestaurantService $restaurantService
     */
    public function __construct(
        EntityManager $entityManager,
        CommandLauncher $commandLauncher,
        Logger $logger,
        StaffService $staffService,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->commandLauncher = $commandLauncher;
        $this->logger = $logger;
        $this->staffService = $staffService;
        $this->restaurantService = $restaurantService;
    }

    /**
     * If date is today it will calculate data from the start of the day
     * If date is not today it will calculate data from the whole day
     *
     * @param  \DateTime $date
     * @return bool
     * @throws
     */
    public function importTickets(\DateTime $date,$asynch=false)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        try {
            // Process tickets import
//            $ticketCount = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('ticket')
//                ->select('COUNT(ticket)')
//                ->where('ticket.originRestaurant = :restaurant')
//                ->setParameter('restaurant', $restaurant)
//                ->getQuery()
//                ->getSingleScalarResult();

            $today = new \DateTime(); // This object represents current date/time
            $today->setTime(0, 0, 0); // reset time part, to prevent partial comparison

            $date->setTime(0, 0, 0); // reset time part, to prevent partial comparison

            $diff = $today->diff($date);
            $diffDays = (integer) $diff->format("%R%a"); // Extract days count in interval

            $startDate = null;
            $endDate = null;
            if ($diffDays === 0) {
                $startDate = $today;
                $endDate = new \DateTime();
            } else {
                $this->logger->addAlert($date->format('Y/m/d'), ['TicketService:ImportTickets']);
                $startDate = $date;
                $endDate = clone $startDate;
                $endDate = $endDate->add(new \DateInterval('P1D'));
            }
            // import the latest tickets
            $command = 'quick:wynd:rest:import '.$startDate->format('Y-m-d').' '.$endDate->format(
                    'Y-m-d'
                ).' '.$restaurant->getId().' '.$asynch;
            $this->commandLauncher->execute($command, true, false, false);
            $this->logger->info('Importing tickets is successfully completed.', ['TicketService:ImportTickets']);

//            $newTicketsCount = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('ticket')
//                ->select('COUNT(ticket)')
//                ->where('ticket.originRestaurant=:restaurant')
//                ->setParameter('restaurant', $restaurant)
//                ->getQuery()
//                ->getSingleScalarResult();

//            return $newTicketsCount > $ticketCount;
            return true;
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage(), ['TicketService:ImportTickets']);
            throw new \Exception($e);
        }
    }

    /**
     * @param Restaurant $restaurant
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isAllUsersAreSynced(Restaurant $restaurant)
    {
        $query = $this->em->createQuery(
            'SELECT count(t) from AppBundle\Financial\Entity\Ticket t LEFT JOIN AppBundle\Staff\Entity\Employee e WITH e.wyndId = t.operator where e.id is null AND t.originRestaurant = :restaurant'
        );
        $query->setParameter("restaurant", $restaurant);
        $ticketCount = $query->getSingleScalarResult();

        return $ticketCount === 0;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param Restaurant|null $restaurant
     * @return float|int
     * @throws \Exception
     */
    public function takeout($startDate,$endDate,Restaurant $restaurant = null)
    {
        if(is_null($restaurant)){
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $result=$this->em->getRepository(Ticket::class)->getTakeOutSalePercentage( $startDate,  $endDate, $restaurant);
        return $result;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param Restaurant|null $restaurant
     * @return float|int
     * @throws \Exception
     */
    public function drive($startDate,$endDate,Restaurant $restaurant = null){

        if(is_null($restaurant)){
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $result=$this->em->getRepository(Ticket::class)->getDriveSalePercentage( $startDate,  $endDate, $restaurant);
        return $result;
    }

    /**
     * retourne le pourcentage de Chiffre d'affaires du canal kiosk
     * @return float|int
     * @throws \Exception
     */
    public function kiosk($startDate,$endDate,Restaurant $restaurant = null)
    {
        if(is_null($restaurant)){
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $result=$this->em->getRepository(Ticket::class)->getKioskSalePercentage($startDate,  $endDate, $restaurant);
        return $result;
    }


    /**
     * retourne le pourcentage de Chiffre d'affaires du canal Delivery
     * @return float|int
     * @throws \Exception
     */
    public function delivery($startDate,$endDate,Restaurant $restaurant = null)
    {
        if(is_null($restaurant)){
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $result=$this->em->getRepository(Ticket::class)->getDeliverySalePercentage($startDate,  $endDate, $restaurant);
        return $result;
    }
    /**
     * retourne le pourcentage de Chiffre d'affaires du canal E-ordering
     * @return float|int
     * @throws \Exception
     */
    public function eOrdering($startDate,$endDate,Restaurant $restaurant = null)
    {
        if(is_null($restaurant)){
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $result=$this->em->getRepository(Ticket::class)->getEorderingSalePercentage($startDate,  $endDate, $restaurant);
        return $result;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function getCancellation(\DateTime $from,\DateTime $to){
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $results=$this->em->getRepository(Ticket::class)->getCancellation($from,$to,$restaurant);
        return $results;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return mixed
     * @throws \Exception
     */
    public function getAbandons(\DateTime $from,\DateTime $to){
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $results=$this->em->getRepository(Ticket::class)->getAbandons($from,$to,$restaurant);
        return $results;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return mixed
     * @throws \Exception
     */
    public function getCorrections(\DateTime $from,\DateTime $to){
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $results=$this->em->getRepository(Ticket::class)->getCorrections($from,$to,$restaurant);
        return $results;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return float|int|mixed
     * @throws \Exception
     */
    public function getTicketsCount(\DateTime $from,\DateTime $to){
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $result=$this->em->getRepository(Ticket::class)->getTicketsCount($from,$to,$restaurant);
        return $result;
    }
    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return float|int|mixed
     *
     */
    public function getTicketsCountByRestaurant(\DateTime $date,Restaurant $restaurant){
        $result=$this->em->getRepository(Ticket::class)->getTicketsCount($date,$date,$restaurant);
        return $result;
    }
}
