<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 14/04/2016
 * Time: 09:20
 */

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Staff\Service\StaffService;
use AppBundle\ToolBox\Service\CommandLauncher;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

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

    private $restaurantService;

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
    public function importTickets(\DateTime $date)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        try {
            // Process tickets import
            $ticketCount = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('ticket')
                ->select('COUNT(ticket)')
                ->where('ticket.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant)
                ->getQuery()
                ->getSingleScalarResult();

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
            ).' '.$restaurant->getId();
            $this->commandLauncher->execute($command, true, false, false);
            $this->logger->info('Importing tickets is successfully completed.', ['TicketService:ImportTickets']);

            $newTicketsCount = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('ticket')
                ->select('COUNT(ticket)')
                ->where('ticket.originRestaurant=:restaurant')
                ->setParameter('restaurant', $restaurant)
                ->getQuery()
                ->getSingleScalarResult();

            return $newTicketsCount > $ticketCount;
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage(), ['TicketService:ImportTickets']);
            throw new \Exception($e);
        }
    }

    public function isAllUsersAreSynced(Restaurant $restaurant)
    {
        $query = $this->em->createQuery(
            'SELECT count(t) from AppBundle\Financial\Entity\Ticket t LEFT JOIN AppBundle\Staff\Entity\Employee e WITH e.wyndId = t.operator where e.id is null AND t.originRestaurant = :restaurant'
        );
        $query->setParameter("restaurant", $restaurant);
        $ticketCount = $query->getSingleScalarResult();

        return $ticketCount === 0;
    }

    public function takeout()
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $caPOSImport = $this->em->getRepository('Financial:Ticket')->getDayTicketsForCanal(
            null,
            SoldingCanal::POS,
            SoldingCanal::TAKE_AWAY,
            $restaurant
        );

        $caDriveImport=$this->em->getRepository(Ticket::class)->getDayTicketsForCanal(null,SoldingCanal::DRIVE,SoldingCanal::DRIVE,$restaurant);
        $caKioskImport=$this->em->getRepository(Ticket::class)->getDayTicketsForCanal(null,SoldingCanal::KIOSK,SoldingCanal::TAKE_AWAY,$restaurant);
        $caDrive=$this->em->getRepository(Ticket::class)->getDayTicketsForCanal(null,SoldingCanal::DRIVE,null,$restaurant);
        $caKiosk=$this->em->getRepository(Ticket::class)->getDayTicketsForCanal(null,SoldingCanal::KIOSK,null,$restaurant);
        $caPOS = $this->em->getRepository('Financial:Ticket')->getDayTicketsForCanal(null, SoldingCanal::POS,null,$restaurant);
        if ($caPOS != 0) {
            return ($caPOSImport+$caDriveImport+$caKioskImport) * 100 / ($caPOS+$caDrive+$caKiosk);
        } else {
            return 0;
        }
    }

 public function drive(){
        $restaurant = $this->restaurantService->getCurrentRestaurant()->getId();
        $caNetDriveThr=$this->em->getRepository(TicketLine::class)->getCaNetDrive(null,SoldingCanal::DRIVE,SoldingCanal::DRIVE,$restaurant);
        $caNetDrive=$this->em->getRepository(TicketLine::class)->getCaNetDrive(null,null,'DRIVE',$restaurant);

        if($caNetDrive != 0){
            return ($caNetDriveThr) * 100 /($caNetDrive+$caNetDriveThr) ;
        }else{
            return 0;
        }
    }
}
