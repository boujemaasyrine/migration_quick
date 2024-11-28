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
use AppBundle\Financial\Entity\TicketInterventionSub;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Service\CommandLauncher;
use AppBundle\ToolBox\Utils\DateUtilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class CashboxService
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
     * @var WithdrawalService
     */
    private $withdrawalService;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var Container
     */
    private $container;

    private $restaurantService;

    /**
     * CashboxService constructor.
     *
     * @param EntityManager     $entityManager
     * @param Logger            $logger
     * @param ParameterService  $parameterService
     * @param WithdrawalService $withdrawalService
     * @param TokenStorage      $tokenStorage
     * @param Container         $container
     */
    public function __construct(
        EntityManager $entityManager,
        Logger $logger,
        ParameterService $parameterService,
        WithdrawalService $withdrawalService,
        TokenStorage $tokenStorage,
        Container $container,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->parameterService = $parameterService;
        $this->withdrawalService = $withdrawalService;
        $this->tokenStorage = $tokenStorage;
        $this->container = $container;
        $this->restaurantService = $restaurantService;
    }

    public function loadDiscountsLines(
        CashboxCount $cashboxCount,
        $restaurant = null
    ) {
        if ($restaurant == null) {

            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }


        if (!is_null($cashboxCount->getCashier())) {
            $allTheDayPaymentTicket = false;
            $today = new \DateTime();
            $today->setTime(
                0,
                0,
                0
            ); // reset time part, to prevent partial comparison

            $date = clone $cashboxCount->getDate();
            $date->setTime(
                0,
                0,
                0
            ); // reset time part, to prevent partial comparison

            $diff = $today->diff($date);
            $diffDays = (integer)$diff->format(
                "%R%a"
            ); // Extract days count in interval

            if ($diffDays !== 0) {
                $allTheDayPaymentTicket = true;
                $selectedDate = $cashboxCount->getDate();
            } else {
                $selectedDate = new \DateTime('now');
            }

            $discounts = $this->em->getRepository(
                'Financial:TicketLine'
            )->getNotCountedDiscountTicketLinesByDateByCashier(
                $selectedDate,
                $cashboxCount->getCashier(),
                $allTheDayPaymentTicket,
                $restaurant
            );
            $cashboxCount->getDiscountContainer()->setTicketLines(
                new ArrayCollection($discounts)
            );
        }
    }

    public function loadPaymentTickets(
        CashboxCount $cashboxCount,
        $restaurant = null
    ) {
        $version = $this->container->getParameter('version');

        if ($restaurant == null) {

            $restaurant = $this->restaurantService->getCurrentRestaurant();

        }

        if (!is_null($cashboxCount->getCashier())) {
            $allTheDayPaymentTicket = false;
            $today = new \DateTime(
            ); // This object represents current date/time
            $today->setTime(
                0,
                0,
                0
            ); // reset time part, to prevent partial comparison

            $date = clone $cashboxCount->getDate();
            $date->setTime(
                0,
                0,
                0
            ); // reset time part, to prevent partial comparison

            $diff = $today->diff($date);
            $diffDays = (integer)$diff->format(
                "%R%a"
            ); // Extract days count in interval

            if ($diffDays !== 0) {
                $allTheDayPaymentTicket = true;
                $selectedDate = $cashboxCount->getDate();
            } else {
                $selectedDate = new \DateTime('now');
            }

            $realCashes = $this->em->getRepository(
                'Financial:TicketPayment'
            )->getNotCountedPaymentTicketsByDateByCashier(
                $selectedDate,
                TicketPayment::REAL_CASH,
                $cashboxCount->getCashier(),
                $allTheDayPaymentTicket,
                $restaurant
            );
            $cashboxCount->getCashContainer()->setTicketPayments(
                new ArrayCollection($realCashes)
            );
            if ($version == "quick") {
                $checkRestaurants = $this->em->getRepository(
                    'Financial:TicketPayment'
                )->getNotCountedPaymentTicketsByDateByCashier(
                    $selectedDate,
                    TicketPayment::$ticketRestaurantsQuick,
                    $cashboxCount->getCashier(),
                    $allTheDayPaymentTicket,
                    $restaurant
                );
            } else {
                $checkRestaurants = $this->em->getRepository(
                    'Financial:TicketPayment'
                )->getNotCountedPaymentTicketsByDateByCashier(
                    $selectedDate,
                    TicketPayment::$ticketRestaurantsBK,
                    $cashboxCount->getCashier(),
                    $allTheDayPaymentTicket,
                    $restaurant
                );
            }

            $cashboxCount->getCheckRestaurantContainer()->setTicketPayments(
                new ArrayCollection($checkRestaurants)
            );
            if ($version == "quick") {
                $checkQuick = $this->em->getRepository(
                    'Financial:TicketPayment'
                )->getNotCountedPaymentTicketsByDateByCashier(
                    $selectedDate,
                    TicketPayment::CHECK_QUICK,
                    $cashboxCount->getCashier(),
                    $allTheDayPaymentTicket,
                    $restaurant
                );
            } else {
                $checkQuick = $this->em->getRepository(
                    'Financial:TicketPayment'
                )->getNotCountedPaymentTicketsByDateByCashier(
                    $selectedDate,
                    TicketPayment::CHECK_BK,
                    $cashboxCount->getCashier(),
                    $allTheDayPaymentTicket,
                    $restaurant
                );
            }

            $cashboxCount->getCheckQuickContainer()->setTicketPayments(
                new ArrayCollection($checkQuick)
            );

            $bankCardIds = $this->parameterService->getBankCardPaymentIds($restaurant);
            $bankCards = $this->em->getRepository(
                'Financial:TicketPayment'
            )->getNotCountedPaymentTicketsByDateByCashier(
                $selectedDate,
                $bankCardIds,
                $cashboxCount->getCashier(),
                $allTheDayPaymentTicket,
                $restaurant
            );
            $cashboxCount->getBankCardContainer()->setTicketPayments(
                new ArrayCollection($bankCards)
            );

            $mealTickets = $this->em->getRepository(
                'Financial:TicketPayment'
            )->getNotCountedPaymentTicketsByDateByCashier(
                $selectedDate,
                TicketPayment::MEAL_TICKET,
                $cashboxCount->getCashier(),
                $allTheDayPaymentTicket,
                $restaurant
            );
            $cashboxCount->getMealTicketContainer()->setTicketPayments(
                new ArrayCollection($mealTickets)
            );
        }
    }

    public function loadWithdrawals(CashboxCount $cashboxCount)
    {
        $withdrawals = $this->withdrawalService->findPendingWithdrawals(
            $cashboxCount->getDate(),
            $cashboxCount->getCashier()
        );
        $cashboxCount->setWithdrawals(new ArrayCollection($withdrawals));
    }

    public function calculateCancelsAbondonsCorrectionsCashbox(
        CashboxCount $cashboxCount,
        $restaurant = null
    ) {

        if ($restaurant == null) {

            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }


        if (!is_null($cashboxCount->getCashier())) {
            // get tickets
            $allTheDayPaymentTicket = false;
            $date = clone $cashboxCount->getDate();
            $date->setTime(
                0,
                0,
                0
            ); // reset time part, to prevent partial comparison
            if (DateUtilities::isToday($date)) {
                $selectedDate = new \DateTime('now');
            } else {
                $allTheDayPaymentTicket = true;
                $selectedDate = $cashboxCount->getDate();
            }

            $tickets = $this->em->getRepository('Financial:Ticket')
                ->getDayTicketsForCashier(
                    $selectedDate,
                    $cashboxCount->getCashier(),
                    $allTheDayPaymentTicket,
                    $restaurant
                );

            $numberCancels = 0;
            $totalCancels = 0;
            $numberAbonodns = 0;
            $totalAbondons = 0;
            $numberCorrections = 0;
            $totalCorrections = 0;

            foreach ($tickets as $ticket) {
                /**
                 * @var Ticket $ticket
                 */
                // tickets cancellation
                if ($ticket->getInvoiceCancelled()) {
                    $numberCancels++;
                    $totalCancels += abs($ticket->getTotalTTC());
                } //tickets abandon
                elseif ($ticket->getStatus() == Ticket::ABONDON_STATUS_VALUE) {
                    $cashboxCount->addAbondonedTicket($ticket);
                    $numberAbonodns++;
                    $totalAbondons += $ticket->getTotalTTC();
                }
                // Look for corrections
                foreach ($ticket->getInterventions() as $intervention) {
                    if ($intervention->getAction()
                        === TicketIntervention::DELETE_ACTION
                    ) {
                        $numberCorrections += $intervention->getItemQty();
                        $totalCorrections += $intervention->getItemQty()
                            * $intervention->getItemPrice();
                    }
                }
            }
            $cashboxCount->setNumberCancels($numberCancels);
            $cashboxCount->setTotalCancels($totalCancels);
            $cashboxCount->setNumberAbondons($numberAbonodns);
            $cashboxCount->setTotalAbondons($totalAbondons);
            $cashboxCount->setNumberCorrections($numberCorrections);
            $cashboxCount->setTotalCorrections($totalCorrections);
        }
    }

    public function getCancelsAbandonsCorrectionsCashbox(\DateTime $date)
    {
        // get tickets
        $allTheDayPaymentTicket = false;
        $date->setTime(
            0,
            0,
            0
        ); // reset time part, to prevent partial comparison
        if (DateUtilities::isToday($date)) {
            $selectedDate = new \DateTime('now');
        } else {
            $allTheDayPaymentTicket = true;
            $selectedDate = $date;
        }

        $tickets = $this->em->getRepository('Financial:Ticket')
            ->getDayTicketsForCashier(
                $selectedDate,
                null,
                $allTheDayPaymentTicket,
                $this->restaurantService->getCurrentRestaurant()
            );//fixed

        $numberCancels = 0;
        $totalCancels = 0;
        $numberAbandons = 0;
        $totalAbandons = 0;
        $numberCorrections = 0;
        $totalCorrections = 0;

        foreach ($tickets as $ticket) {
            /**
             * @var Ticket $ticket
             */
            if ($ticket->getInvoiceCancelled()) {
                $numberCancels++;
                $totalCancels += abs($ticket->getTotalTTC());
            } //tickets abandon
            elseif ($ticket->getStatus() == Ticket::ABONDON_STATUS_VALUE) {
                $numberAbandons++;
                $totalAbandons += $ticket->getTotalTTC();
            }

            // Look for corrections
            foreach ($ticket->getInterventions() as $intervention) {
                if ($intervention->getAction()
                    === TicketIntervention::DELETE_ACTION
                ) {
                    $numberCorrections += $intervention->getItemQty();
                    $totalCorrections += $intervention->getItemQty()
                        * $intervention->getItemPrice();
                }
            }
        }

        $result['numCancels'] = $numberCancels;
        $result['totalCancels'] = $totalCancels;
        $result['numAbandons'] = $numberAbandons;
        $result['totalAbandons'] = $totalAbandons;
        $result['numCorrections'] = $numberCorrections;
        $result['totalCorrections'] = $totalCorrections;

        return $result;
    }

    /**
     * @param \DateTime $date
     *
     * @return CashboxCount
     */
    public function prepareCashbox(
        \DateTime $date,
        $restaurant = null,
        $user = null
    ) {
        if ($restaurant == null) {
            $restaurant = $this->container->get('restaurant.service')
                ->getCurrentRestaurant();
        }

        if ($user == null) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        $cashbox = new CashboxCount();
        $cashbox->setDate($date);
        $cashbox->setOwner($user);
        $cashbox->setEft($this->parameterService->isEftActivated($restaurant));
        $cashbox->setOriginRestaurant($restaurant);

        // Check Restaurant
        $parameters = $this->parameterService->getTicketRestaurantValues(
            $restaurant,
            false
        );
        foreach (
            $this->parameterService->getTicketRestaurantValues(
                $restaurant,
                true
            ) as
            $parameter
        ) {
            $parameters[] = $parameter;
        }

        $checkRestaurantContainer = $cashbox->getCheckRestaurantContainer();
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            $values = $parameter->getValue();
            foreach ($values['values'] as $value) {
                $checkRestaurant = new CashboxTicketRestaurant();
                $checkRestaurant->setTicketName($values['type'])
                    ->setIdPayment($values['id'])
                    ->setElectronic($values['electronic'])->setUnitValue(
                        $value
                    );
                $checkRestaurantContainer->addTicketRestaurantCount(
                    $checkRestaurant
                );
            }
        }

        // Bank Card
        $bankCards = $this->parameterService->getBankCardValues($restaurant);
        $bankCardContainer = $cashbox->getBankCardContainer();
        foreach ($bankCards as $bankCardParameter) {
            /**
             * @var Parameter $bankCardParameter
             */
            $bankCard = new CashboxBankCard();
            $bankCard->setCardName($bankCardParameter->getLabel())
                ->setIdPayment($bankCardParameter->getValue()['id']);
            $bankCardContainer->addBankCardCount($bankCard);
        }

        // Check Quick
        $checkQuickValues = $this->parameterService->getCheckQuickValues(
            $restaurant
        );
        $checkQuickContainer = $cashbox->getCheckQuickContainer();
        foreach ($checkQuickValues as $checkQuickValue) {
            $checkQuick = new CashboxCheckQuick();
            $checkQuick->setUnitValue($checkQuickValue);
            $checkQuickContainer->addCheckQuickCount($checkQuick);
        }

        // Foreign Currency
        $rawList = true;
        $foreignContainer = $cashbox->getForeignCurrencyContainer();
        $foreignCount = new CashboxForeignCurrency();
        $foreignContainer->addForeignCurrencyCount($foreignCount);

        return $cashbox;
    }

    public function validateCashboxCount(
        CashboxCount $cashboxCount,
        $restaurant = null,
        $user = null
    ) {
        try {

            if ($restaurant == null) {
                $restaurant = $this->restaurantService->getCurrentRestaurant();
            }

            if ($user == null) {
                $user = $this->tokenStorage->getToken()->getUser();
            }
            $this->em->beginTransaction();
            $cashboxCount->setOwner($user);
            $cashboxCount->setRealCaCounted(
                $cashboxCount->calculateTotalCashbox()
            );
            $cashboxCount->setTheoricalCa(
                $cashboxCount->calculateTheoricalTotalCashbox()
            );
            // clean foreign currencies rates
            foreach (
                $cashboxCount->getForeignCurrencyContainer()
                    ->getForeignCurrencyCounts() as $key => $fc
            ) {
                /**
                 * @var CashboxForeignCurrency $fc
                 */
                if (is_null($fc->getAmount())) {
                    $cashboxCount->getForeignCurrencyContainer()
                        ->getForeignCurrencyCounts()->remove($key);
                }
            }
            // clear empty ticket restaurant
            foreach (
                $cashboxCount->getCheckRestaurantContainer()
                    ->getTicketRestaurantCounts() as $key =>
                $ticketRestaurantCount
            ) {
                /**
                 * @var CashboxTicketRestaurant $ticketRestaurantCount
                 */
                if (is_null($ticketRestaurantCount->getUnitValue())
                    || is_null(
                        $ticketRestaurantCount->getQty()
                    )
                ) {
                    $cashboxCount->getCheckRestaurantContainer()
                        ->getTicketRestaurantCounts()->remove($key);
                }
            }
            $parameters
                = $this->parameterService->getTicketNameIdPaymentMapForTicketRestaurant(
                $restaurant
            );
            $checkRestaurantContainer
                = $cashboxCount->getCheckRestaurantContainer();
            foreach (
                $checkRestaurantContainer->getTicketRestaurantCounts() as
                $ticketRestaurantCount
            ) {
                /**
                 * @var CashboxTicketRestaurant $ticketRestaurantCount
                 */
                if (!$ticketRestaurantCount->getIdPayment()) {
                    $param = isset(
                        $parameters[$ticketRestaurantCount->getTicketName()]
                    ) ? $parameters[$ticketRestaurantCount->getTicketName()]
                        : null;
                    /**
                     * @var Parameter $param
                     */
                    if ($param) {
                        $ticketRestaurantCount->setIdPayment(
                            $param->getValue()['id']
                        )
                            ->setElectronic($param->getValue()['electronic']);
                    }
                }
            }

            $cashboxCount->setOriginRestaurant(
                $restaurant
            );
            $globalCashCashboxCount = $this->parameterService->getGlobalCashCashboxCountParameter();
            $globalCashCashboxCount->setValue($cashboxCount->getCashContainer()->isAllAmount());

            if (is_null($cashboxCount->getId())) {
                $this->em->persist($cashboxCount);
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage(), ['validateCashboxCount']);
            $this->em->rollback();
            throw new \Exception($e);
        }
    }

    public function findCashboxCountsByDate(\DateTime $dateTime,$restaurant=null)
    {
        if(!$restaurant){
            $restaurant=$this->restaurantService->getCurrentRestaurant();
        }


        return $this->em->getRepository('Financial:CashboxCount')
            ->findCashboxCountsBydate(
                $dateTime,
                $restaurant

            );//fixed
    }

    public function getRealCashAmount($id)
    {
        return $this->em->getRepository('Financial:CashboxCount')
            ->getRealCashAmount($id);
    }

    /**
     * @param array $cashboxCounts
     *
     * @return array
     */
    public function serializeCashboxCounts(
        $cashboxCounts
    ) {
        $result = [];
        foreach ($cashboxCounts as $c) {
            $result[] = $this->serializeCashboxCount($c);
        }

        return $result;
    }

    /**
     * @param array $c
     *
     * @return array
     */
    public function serializeCashboxCount(
        $c
    ) {
        $result = array(
            'id'            => $c['id'],
            'owner'         => $c['oFirstName'].' '.$c['oLastName'],
            'cashier'       => $c['cFirstName'].' '.$c['cLastName'],
            'date'          => $c['date']->format('d/m/Y'),
            'createdAt'     => $c['createdAt']->format('d/m/Y H:i:s'),
            'realCaCounted' => number_format($c['realCaCounted'], 2, ',', ''),
            'theoricalCa'   => number_format($c['theoricalCa'], 2, ',', ''),
            'difference'    => number_format(
                ($c['realCaCounted'] - $c['theoricalCa']),
                2,
                ',',
                ''
            ),
            'eft'           => $c['eft'],
            'counted'       => $c['counted'],

        );

        return $result;
    }

    public function listCashboxCounts(
        $criteria,
        $order,
        $limit,
        $offset,
        $search = null,
        $onlyList = false
    ) {
        $cashboxCounts = $this->em->getRepository("Financial:CashboxCount")
            ->getCashboxCountsFilteredOrdered(
                $criteria,
                $order,
                $offset,
                $limit,
                $search,
                $onlyList
            );

        return $this->serializeCashboxCounts($cashboxCounts);
    }

    public function getNotCountedCashBox(\DateTime $date)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $qb = $this->em->getRepository("Financial:Ticket")->createQueryBuilder(
            't'
        );
        $qb->select('t.operator')
            ->where('t.counted = :counted')
            ->setParameter('counted', false)
            ->andWhere('t.date = :date')
            ->setParameter('date', $date)
            ->andWhere('t.originRestaurant = :restaurant')
            ->setParameter('restaurant', $currentRestaurant);

        $operatorsId = $qb->getQuery()->getResult();

        $qb = $this->em->getRepository("Staff:Employee")->createQueryBuilder(
            'o'
        );
        $qb->where('o.wyndId in (:operators)')
            ->setParameter('operators', $operatorsId)
            ->andWhere(":restaurant Member of o.eligibleRestaurants")
            ->setParameter("restaurant", $currentRestaurant);
        $operators = $qb->getQuery()->getResult();

        return $operators;
    }


    public function calculateCashBoxTotalGap(
        $startDate,
        $endDate = null,
        $currentRestaurant = null
    ) {
        if (is_null($endDate)) {
            $endDate = clone $startDate;
        }

        $qb = $this->em->getRepository("Financial:CashboxCount")
            ->createQueryBuilder('cc');
        $qb->select('SUM(cc.realCaCounted - cc.theoricalCa)')
            ->where('cc.date between :start and :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);
        if ($currentRestaurant != null) {
            $qb->andWhere("cc.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }


    public function kioskCashboxCalculation(
        Restaurant $restaurant,
        \DateTime $date,
        $user,
        ImportProgression $progression = null
    ) {
        if (isset($progression)) {
            $kiosks = $this->em->getRepository(Employee::class)
                ->getKioskCashiers($date, $restaurant);

            if (isset($kiosks) && isset($user)) {

                $progression->setTotalElements(count($kiosks))
                    ->setProceedElements(0)
                    ->setStatus('pending');

                try {

                    foreach ($kiosks as $kiosk) {

                        //prepare cashbox with date = administrative closing date

                        $cashbox = $this->prepareCashbox(
                            $date,
                            $restaurant,
                            $user
                        );
                        $cashbox->setCashier($kiosk);

                        //load tickets

                        $this->loadPaymentTickets($cashbox, $restaurant);
                        $this->loadDiscountsLines($cashbox, $restaurant);
                        $this->calculateCancelsAbondonsCorrectionsCashbox(
                            $cashbox,
                            $restaurant
                        );

                        // set real amount of cash to = theorical value

                        $realCashContainer = $cashbox->getCashContainer();

                        $realCashContainer->setTotalAmount(
                            $realCashContainer->calculateTheoricalTotal()
                        );

                        $realCashContainer->setAllAmount(true);

                        //save cashbox

                        $this->validateCashboxCount(
                            $cashbox,
                            $restaurant,
                            $user
                        );

                        $progression->incrementProgression();
                        $this->em->flush();

                    }

                } catch (Exception $e) {

                    echo($e->getMessage());

                }


            } else {
                $progression->setTotalElements(0)
                    ->setProceedElements(0)
                    ->setProgress(100)
                    ->setStatus('pending');
                return;
            }
        }


    }


    public function kioskCashboxList(Restaurant $restaurant, \DateTime $date)
    {
        // get Counted kiosk cashboxs

        $kiosks = $this->em->getRepository(Employee::class)->getKioskCashiers(
            $date,
            $restaurant,
            true
        );

        if (isset($kiosks)) {

            $cashboxs = $this->em->getRepository(CashboxCount::class)
                ->findCashboxCountsByDateAndCashier(
                    $date,
                    $restaurant,
                    $kiosks
                );

            return $cashboxs;
        }

        return null;

    }


}
