<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 14:17
 */

namespace AppBundle\Financial\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxCheckQuick;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\DateUtilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Acl\Exception\Exception;

class DayIncomeService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ParameterService
     */
    private $parameterService;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var RestaurantService
     */
    private $restaurantService;

    /**
     * DayIncomeService constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param ParameterService $parameterService
     * @param Container $container
     * @param RestaurantService $restaurantService
     */
    public function __construct(
        EntityManager $entityManager,
        Logger $logger,
        ParameterService $parameterService,
        Container $container,
        RestaurantService $restaurantService
    )
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->parameterService = $parameterService;
        $this->container = $container;
        $this->restaurantService = $restaurantService;
    }


    /**
     * @param DayIncome $dayIncome
     * @return float
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function getDiscountsTotal(DayIncome $dayIncome){
        if(empty($dayIncome->getCashboxCounts())){
            return array(
                "discount_total"=>0,
                "discounts_theorical_total"=>0,
                "discounts_gap"=> 0
            );
        }
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $restaurantId=$this->restaurantService->getCurrentRestaurant()->getId();
        $discountContainerIds=[];
        foreach ($dayIncome->getCashboxCounts() as $cashboxCount) {
            $discountContainerIds []= $cashboxCount->getDiscountContainer()->getId();
        }
        $discountTotal=$this->em->getRepository(TicketLine::class)->createQueryBuilder('tl')
            ->select('SUM(tl.discountTtc) as discount')
            ->where('tl.status != :abondon')
            ->andWhere('tl.status != :cancelled')
            ->andWhere('tl.originRestaurantId = :restaurant_id')
            ->andWhere('tl.discountContainer IN (:ids)')
            ->andWhere('tl.countedCanceled = FALSE')
            ->andWhere('tl.date = :date')
            ->setParameter('abondon', $abandonment)
            ->setParameter('cancelled', $canceled)
            ->setParameter('restaurant_id', $restaurantId)
            ->setParameter('ids', $discountContainerIds)
            ->setParameter('date', $dayIncome->getDate())
            ->getQuery()
            ->getSingleScalarResult();

        $discountsTheoricalTotal=$this->em->getRepository(TicketLine::class)->createQueryBuilder('tl')
            ->select('SUM(tl.discountTtc) as discount')
            ->where('tl.status != :abondon')
            ->andWhere('tl.status != :cancelled')
            ->andWhere('tl.originRestaurantId = :restaurant_id')
            ->andWhere('tl.discountContainer IN (:ids)')
            ->andWhere('tl.date = :date')
            ->setParameter('abondon', $abandonment)
            ->setParameter('cancelled', $canceled)
            ->setParameter('restaurant_id', $restaurantId)
            ->setParameter('ids', $discountContainerIds)
            ->setParameter('date', $dayIncome->getDate())
            ->getQuery()
            ->getSingleScalarResult();

        $discountTotal=round(abs($discountTotal), 2);
        $discountsTheoricalTotal=round(abs($discountsTheoricalTotal), 2);
        $discountGap=abs($discountTotal-$discountsTheoricalTotal);

        $dayIncome->setDiscountsTotal($discountTotal);
        $dayIncome->setDiscountsTheoricalTotal($discountsTheoricalTotal);
        $dayIncome->setDiscountsGap($discountGap);

        return array(
            "discount_total"=>$discountTotal,
            "discounts_theorical_total"=>$discountsTheoricalTotal,
            "discounts_gap"=> $discountGap
        );

    }

    public function getCashboxTotalGap(DayIncome $dayIncome){
        $cashboxTotal=0;
        $cashboxIds=[];
        $restaurantId=$this->restaurantService->getCurrentRestaurant()->getId();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        foreach ($dayIncome->getCashboxCounts() as $cashboxCount) {
            $cashboxTotal += $cashboxCount->calculateTotalCashbox();
            $cashboxIds[]=$cashboxCount->getId();
        }
        $cashContainerTheoricalTotal=$this->em->getRepository(CashboxCount::class)->createQueryBuilder('cashbox')
            ->select('SUM(ticketPayments.amount)')
            ->join('cashbox.cashContainer','cashContainer')
            ->join('cashContainer.ticketPayments','ticketPayments')
            ->join('ticketPayments.ticket','ticket')
            ->where('cashbox.originRestaurantId = :restaurant_id')
            ->andWhere('cashbox.id IN (:ids)')
            ->andWhere('ticket.countedCanceled != TRUE')
            ->andWhere('ticket.status != :cancelled')
            ->andWhere('ticket.status != :abondon')
            ->andWhere('ticket.originRestaurant != :restaurant_id')
            ->andWhere('ticket.date = :date')
            ->setParameter('restaurant_id', $restaurantId)
            ->setParameter('ids', $cashboxIds)
            ->setParameter('abondon', $abandonment)
            ->setParameter('cancelled', $canceled)
            ->setParameter('date', $dayIncome->getDate())
            ->getQuery()
            ->getSingleScalarResult();
        $checkQuickContainerTheoricalTotal=$this->em->getRepository(CashboxCount::class)->createQueryBuilder('cashbox')
            ->select('SUM(ticketPayments.amount)')
            ->join('cashbox.checkQuickContainer','checkQuickContainer')
            ->join('checkQuickContainer.ticketPayments','ticketPayments')
            ->join('ticketPayments.ticket','ticket')
            ->where('cashbox.originRestaurantId = :restaurant_id')
            ->andWhere('cashbox.id IN (:ids)')
            ->andWhere('ticket.countedCanceled != TRUE')
            ->andWhere('ticket.status != :cancelled')
            ->andWhere('ticket.status != :abondon')
            ->andWhere('ticket.originRestaurant != :restaurant_id')
            ->andWhere('ticket.date = :date')
            ->setParameter('restaurant_id', $restaurantId)
            ->setParameter('ids', $cashboxIds)
            ->setParameter('abondon', $abandonment)
            ->setParameter('cancelled', $canceled)
            ->setParameter('date', $dayIncome->getDate())
            ->getQuery()
            ->getSingleScalarResult();
        $bankCardContainerTheoricalTotal= $this->em->getRepository(CashboxCount::class)->createQueryBuilder('cashbox')
                ->select('SUM(ticketPayments.amount)')
                ->join('cashbox.bankCardContainer','bankCardContainer')
                ->join('bankCardContainer.ticketPayments','ticketPayments')
                ->join('ticketPayments.ticket','ticket')
                ->where('cashbox.originRestaurantId = :restaurant_id')
                ->andWhere('cashbox.id IN (:ids)')
                ->andWhere('ticket.countedCanceled != TRUE')
                ->andWhere('ticket.status != :cancelled')
                ->andWhere('ticket.status != :abondon')
                ->andWhere('ticket.originRestaurant != :restaurant_id')
                ->andWhere('ticket.date = :date')
                ->setParameter('restaurant_id', $restaurantId)
                ->setParameter('ids', $cashboxIds)
                ->setParameter('abondon', $abandonment)
                ->setParameter('cancelled', $canceled)
                ->setParameter('date', $dayIncome->getDate())
                ->getQuery()
                ->getSingleScalarResult();

    }


}
