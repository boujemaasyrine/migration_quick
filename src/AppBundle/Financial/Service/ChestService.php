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
use AppBundle\Administration\Service\WorkflowService;
use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxCheckQuick;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\ChestExchange;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Exception\ChestCannotBeValidatedException;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Interfaces\ListInterface;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\Translator;

class ChestService implements ListInterface
{

    const positiveThreshold = 0;
    const negativeThreshold = 0;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ParameterService
     */
    private $parameterService;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var WorkflowService
     */
    private $workflowService;

    /**
     * @var ExpenseService
     */
    private $expenseService;

    /**
     * @var AdministrativeClosingService
     */
    private $administrativeClosingService;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Factory
     */
    private $phpExcel;

    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var RestaurantService
     */
    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        ParameterService $parameterService,
        TokenStorage $tokenStorage,
        Router $router,
        Factory $factory,
        Translator $translator,
        WorkflowService $workflowService,
        ExpenseService $expenseService,
        AdministrativeClosingService $administrativeClosing,
        RestaurantService $restaurantService

    )
    {
        $this->em = $entityManager;
        $this->parameterService = $parameterService;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->phpExcel = $factory;
        $this->translator = $translator;

        $this->workflowService = $workflowService;
        $this->expenseService = $expenseService;
        $this->administrativeClosingService = $administrativeClosing;
        $this->restaurantService = $restaurantService;

    }

    // Load cashboxes
    public function loadCashboxCounts(ChestCount $chestCount)
    {
        $casboxes = $this->em->getRepository('Financial:CashboxCount')->findBy(
            [
                'smallChest' => null,
                'originRestaurant' => $this->restaurantService->getCurrentRestaurant(),
            ]
        );
        $chestCount->getSmallChest()->setCashboxCounts($casboxes);
    }

    // Load last chest count

    /**
     * @param ChestCount|null $chestCount
     * @return ChestCount
     */
    public function loadLastChestCount(ChestCount $chestCount = null)
    {
        $lastChestCount = $this->em->getRepository('Financial:ChestCount')->getLastChestCount(
            $this->restaurantService->getCurrentRestaurant()
        );//fixed
        if ($chestCount) {
            $chestCount->setLastChestCount($lastChestCount);
        }

        return $lastChestCount;
    }

    /**
     * @param ChestCount|null $chestCount
     * @param Restaurant $restaurant
     * @return ChestCount
     * @throws \Exception
     */
    public function loadLastChestCountByRestaurant(ChestCount $chestCount = null, Restaurant $restaurant)
    {
        $lastChestCount = $this->em->getRepository('Financial:ChestCount')->getLastChestCountByClosureDate(
            $restaurant
        );//fixed
        if ($chestCount) {
            $chestCount->setLastChestCount($lastChestCount);
        }

        return $lastChestCount;
    }

    // Load recipe tickets
    public function loadRecipeTickets(ChestCount $chestCount)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $queryBuilder = $this->em->getRepository('Financial:RecipeTicket')
            ->createQueryBuilder('recipeTicket')
            ->where('recipeTicket.chestCount is NULL')
            ->andWhere('recipeTicket.label != :chest_err')
            ->setParameter('chest_err', RecipeTicket::CHEST_ERROR);
        if (isset($restaurant)) {
            $queryBuilder->andWhere('recipeTicket.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }
        $recipeTickets = $queryBuilder->getQuery()->getResult();
        $chestCount->setRecipeTickets($recipeTickets);
    }

    // Load deposits
    public function loadDeposits(ChestCount $chestCount)
    {
        $deposits = $this->em->getRepository('Financial:Deposit')->findBy(
            ['chestCount' => null, 'originRestaurant' => $this->restaurantService->getCurrentRestaurant()]
        );

        $chestCount->setDeposits($deposits);
    }

    // Load expenses
    public function loadExpenses(ChestCount $chestCount)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();

        $queryBuilder = $this->em->getRepository('Financial:Expense')
            ->createQueryBuilder('expense')
            ->where('expense.chestCount is NULL')
            ->andWhere('expense.sousGroup != :chest_err')
            ->setParameter('chest_err', Expense::ERROR_CHEST);
        if (isset($restaurant)) {
            $queryBuilder->andWhere('expense.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $expenses = $queryBuilder->getQuery()->getResult();
        $chestCount->setExpenses($expenses);
    }

    // Load envelopes
    public function loadEnvelopes(ChestCount $chestCount)
    {
        $envelopes = $this->em->getRepository('Financial:Envelope')->findBy(
            ['chestCount' => null, 'originRestaurant' => $this->restaurantService->getCurrentRestaurant()]
        );
        $chestCount->setEnvelopes($envelopes);
    }

    public function loadChestCount(ChestCount $chestCount)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $this->loadCashboxCounts($chestCount);
        $this->loadRecipeTickets($chestCount);
        $this->loadDeposits($chestCount);
        $this->loadExpenses($chestCount);
        $this->loadEnvelopes($chestCount);
        $chestCount->calculateGap($restaurant);
    }
    //belsem 2020

    /**
     * @param ChestCount|null $chestCount
     * @return ChestCount
     */
    public function CreateChestCount(Restaurant $restaurant, \DateTime $date)

    {
        $lastChestCount = $this->em->getRepository('Financial:ChestCount')->getLastChestCountByClosureDate(
            $restaurant
        );
        echo 'last chest count id is : ' . $lastChestCount->getId() . "\n";
        $chest = new ChestCount();
        $chest->setClosureDate($this->administrativeClosingService->getLastNonClosedDate($restaurant));
        $chest->setOriginRestaurant($restaurant);
        $chest->setOwner($lastChestCount->getOwner());
        $chest->setEft($this->parameterService->isEftActivated($restaurant));
        $chest->setDate($date->add(new \DateInterval('P1D')));
        $chest->setClosure(true);
        $chest->getCashboxFund()->setTheoricalNbrOfCashboxes($this->parameterService->getNumberOfCashboxesByRestaurant($restaurant));
        $chest->getCashboxFund()->setTheoricalInitialCashboxFunds($this->parameterService->getStartDayCashboxFundsByRestaurant($restaurant));
        $this->loadLastChestCountByRestaurant($chest, $restaurant);
        $chest->getCashboxFund()->setInitialCashboxFunds($this->parameterService->getStartDayCashboxFundsByRestaurant($restaurant));
        $chest->getCashboxFund()->setNbrOfCashboxes($this->parameterService->getNumberOfCashboxesByRestaurant($restaurant));


        $exchangeFund = $chest->getExchangeFund();
        $lastChestExchange = $lastChestCount->getExchangeFund()->getChestExchanges();
        foreach ($lastChestExchange as $exchangeRate) {
            $exchange = clone $exchangeRate;
            $exchange->setId(null);
            $exchangeFund->addChestExchange($exchange);
        }


         $smallChest = $chest->getSmallChest();

         $ticketRestaurantCounts=$chest->getLastChestCount()->getSmallChest()->getTicketRestaurantCounts();

            foreach ($ticketRestaurantCounts as $tr) {
                $checkRestaurant = clone $tr;
                $checkRestaurant->setId(null);
                $smallChest->addTicketRestaurantCount($checkRestaurant);
            }
       
        $smallChest->setTotalCash($chest->getLastChestCount()->getSmallChest()->getTotalCash());
        
        $smallChest->setRealTrTotalDetail($chest->getLastChestCount()->getSmallChest()->getRealTrTotalDetail());
        $smallChest->setTheoricalTrTotalDetail($chest->getLastChestCount()->getSmallChest()->getTheoricalTrTotalDetail());
        $smallChest->setRealTrTotal($chest->getLastChestCount()->getSmallChest()->getRealTrTotal());
        $smallChest->setTheoricalTrTotal($chest->getLastChestCount()->getSmallChest()->getTheoricalTrTotal());
        
        $smallChest->setElectronicDeposed(false);
        $smallChest->setRealTotal($chest->getLastChestCount()->getRealTotal());
        $smallChest->setTheoricalTotal($chest->getLastChestCount()->getTheoricalTotal());
        $smallChest->setGap($chest->getLastChestCount()->getSmallChest()->getGap());
        $chest->setSmallChest($smallChest);

        return $chest;
    }


    /**
     * @param \DateTime $date
     * @return ChestCount
     */
    public function prepareChest(\DateTime $date)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $chest = new ChestCount();
        $chest->setDate($date);
        $chest->setOwner($this->tokenStorage->getToken()->getUser());
        $chest->setEft($this->parameterService->isEftActivated());
        $chest->setOriginRestaurant($currentRestaurant);
        $chest->getCashboxFund()->setTheoricalNbrOfCashboxes($this->parameterService->getNumberOfCashboxes());
        $chest->getCashboxFund()->setTheoricalInitialCashboxFunds($this->parameterService->getStartDayCashboxFunds());
        $this->loadLastChestCount($chest);
        $chest->getCashboxFund()->setInitialCashboxFunds($this->parameterService->getStartDayCashboxFunds());
        $chest->getCashboxFund()->setNbrOfCashboxes($this->parameterService->getNumberOfCashboxes());

        $smallChest = $chest->getSmallChest();
        // Check Restaurant
        $parameters = $this->parameterService->getTicketRestaurantValues($currentRestaurant, false);
        foreach ($this->parameterService->getTicketRestaurantValues($currentRestaurant, true) as $parameter) {
            $parameters[] = $parameter;
        }

        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            $values = $parameter->getValue();
//            $values['values'] = isset($values['values']) ? $values['values'] : array() ;

            foreach ($values['values'] as $value) {
                $checkRestaurant = new CashboxTicketRestaurant();
                $checkRestaurant->setTicketName($values['type'])
                    ->setIdPayment($values['id'])
                    ->setElectronic($values['electronic'])->setUnitValue($value);
                $smallChest->addTicketRestaurantCount($checkRestaurant);
            }
        }

        // Bank Card
        $bankCards = $this->parameterService->getBankCardValues();
        foreach ($bankCards as $bankCardParameter) {
            /**
             * @var Parameter $bankCardParameter
             */
            $bankCard = new CashboxBankCard();
            $bankCard->setCardName($bankCardParameter->getLabel())
                ->setIdPayment($bankCardParameter->getValue()['id']);
            $smallChest->addBankCardCount($bankCard);
        }

        // Check Quick
        $checkQuickValues = $this->parameterService->getCheckQuickValues($currentRestaurant);
        $parameters = [];
        foreach (
            $checkQuickValues
            as $parameter
        ) {
            $parameters[] = $parameter;
        }

        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            $values = $parameter->getValue();
            foreach ($values['values'] as $value) {

                $checkQuick = new CashboxCheckQuick();
                $checkQuick->setCheckName($values['type']);
                $checkQuick->setIdPayment($values['id']);
                $checkQuick->setUnitValue($value);
                $smallChest->addCheckQuickCount($checkQuick);
            }
        }

        // Foreign Currency
        if (count($smallChest->getForeignCurrencyCounts()) == 0) {
            $foreignCount = new CashboxForeignCurrency();
            $smallChest->addForeignCurrencyCount($foreignCount);
        }

        // Exchange fund
        $exchangeFund = $chest->getExchangeFund();
        $rawList = true;
        $exchangeRates = $this->parameterService->getExchangeList($rawList);
        foreach ($exchangeRates as $exchangeRate) {
            /**
             * @var Parameter $exchangeRate
             */
            $exchange = new ChestExchange();
            $exchange->setUnitParamsId($exchangeRate->getId())
                ->setUnitValue($this->parameterService->calculateParameterExchangeUnitValue($exchangeRate))->setType($exchangeRate->getValue()[Parameter::TYPE]);
            $exchangeFund->addChestExchange($exchange);
        }
        return $chest;
    }


    /**
     * @param ChestCount $chestCount
     * @throws \Exception
     */
    public function validateChestCount(ChestCount $chestCount)
    {
        try {
            $this->em->beginTransaction();
            $chestCount->setOwner($this->tokenStorage->getToken()->getUser());
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $lastChestCount = $this->em->getRepository('Financial:ChestCount')->getLastChestCount($currentRestaurant);
            if (!is_null($lastChestCount) && $lastChestCount !== $chestCount->getLastChestCount()) {
                throw new ChestCannotBeValidatedException();
            }
            $chestCount->getCashboxFund()->setNbrOfCashboxes($this->parameterService->getNumberOfCashboxes());
            // Set exchange type
            $exchangeList = $this->parameterService->getExchangeList(true);
            $exchangeFund = $chestCount->getExchangeFund();
            foreach ($exchangeFund->getChestExchanges() as $key => $exchange) {
                /**
                 * @var ChestExchange $exchange
                 */
                if (!is_null($exchange->getQty())) {
                    $exchange->setTypeFromParameters($exchangeList);
                } else {
                    $exchangeFund->getChestExchanges()->remove($key);
                }
            }

            // clearn empty ticket restaurant
            foreach ($chestCount->getSmallChest()->getTicketRestaurantCounts() as $key => $ticketRestaurantCount) {
                /**
                 * @var CashboxTicketRestaurant $ticketRestaurantCount
                 */
                if (is_null($ticketRestaurantCount->getUnitValue()) || is_null($ticketRestaurantCount->getQty())) {
                    $chestCount->getSmallChest()->getTicketRestaurantCounts()->remove($key);
                }
            }
            $parameters = $this->parameterService->getTicketNameIdPaymentMapForTicketRestaurant();
            foreach ($chestCount->getSmallChest()->getTicketRestaurantCounts() as $ticketRestaurantCount) {
                /**
                 * @var CashboxTicketRestaurant $ticketRestaurantCount
                 */
                if (!$ticketRestaurantCount->getIdPayment()) {
                    $param = isset(
                        $parameters[$ticketRestaurantCount->getTicketName()]
                    ) ? $parameters[$ticketRestaurantCount->getTicketName()] : null;
                    /**
                     * @var Parameter $param
                     */
                    if ($param) {
                        $ticketRestaurantCount->setIdPayment($param->getValue()['id'])
                            ->setElectronic($param->getValue()['electronic']);
                    }
                }
            }

            // clean foreign currencies rates
            foreach ($chestCount->getSmallChest()->getForeignCurrencyCounts() as $key => $fc) {
                /**
                 * @var CashboxForeignCurrency $fc
                 */
                if (is_null($fc->getAmount())) {
                    $chestCount->getSmallChest()->getForeignCurrencyCounts()->remove($key);
                }
            }

            // Set couting done is closure
            $inAdministrativeClosure = $this->workflowService->inAdministrativeClosing();
            $inChestCount = $this->administrativeClosingService->inChestCount();
            $chestCount->setClosure($inAdministrativeClosure && $inChestCount);
            if ($inAdministrativeClosure && $inChestCount) {
                $chestCount->setClosureDate($this->administrativeClosingService->getLastNonClosedDate());
            }
            $this->em->persist($chestCount);
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }


    /**
     * @param ChestCount $chestCount
     * @return string => url to download recipeticket or expense
     * @throws \Exception
     */
    public function generateAutomaticExpenceOrRecipeTicket(ChestCount $chestCount)
    {
        try {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
            $gap = $chestCount->getGap();
            $url = "";
            $inAdministrativeClosure = $this->workflowService->inAdministrativeClosing();
            $inChestCount = $this->administrativeClosingService->inChestCount();
            if ($gap > self::positiveThreshold) {
                $recipeTicket = new RecipeTicket();
                $recipeTicket->setAmount($gap)
                    ->setOwner($this->tokenStorage->getToken()->getUser())
                    ->setLabel(RecipeTicket::CHEST_ERROR)
                    ->setOriginRestaurant($restaurant);
                if ($inAdministrativeClosure && $inChestCount) {
                    $chestCount->setClosureDate($this->administrativeClosingService->getLastNonClosedDate());
                    $recipeTicket->setDate($this->administrativeClosingService->getLastNonClosedDate());
                } else {
                    $recipeTicket->setDate(new \DateTime());
                }
                $this->em->persist($recipeTicket);
                $this->em->flush();
                $url = $this->router->generate('print_recipe_tickets', ['recipeTicket' => $recipeTicket->getId()]);
            } elseif ($gap < self::negativeThreshold) {
                $expense = new Expense();
                $expense->setAmount($gap)
                    ->setResponsible($this->tokenStorage->getToken()->getUser())
                    ->setGroupExpense(Expense::GROUP_ERROR_COUNT)
                    ->setSousGroup(Expense::ERROR_CHEST)
                    ->setReference($this->expenseService->getLastRefExpense($restaurant) + 1)
                    ->setOriginRestaurant($restaurant);
                if ($inAdministrativeClosure && $inChestCount) {
                    $chestCount->setClosureDate($this->administrativeClosingService->getLastNonClosedDate());
                    $expense->setDateExpense($this->administrativeClosingService->getLastNonClosedDate());
                } else {
                    $expense->setDateExpense(new \DateTime());
                }
                $this->em->persist($expense);
                $this->em->flush();
                $url = $this->router->generate('expense_detail_print', ['expense' => $expense->getId()]);
            }

            return $url;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param array $chestCounts
     * @return array
     */
    public function serializeItems($chestCounts)
    {
        $result = [];
        foreach ($chestCounts as $c) {
            $result[] = $this->serializeItem($c);
        }

        return $result;
    }

    /**
     * @param ChestCount $c
     * @return array
     */
    public function serializeItem($c)
    {
        $result = array(
            'id' => $c['id'],
            'owner' => $c['firstName'] . ' ' . $c['lastName'],
            'date' => $c['date']->format('d/m/Y H:i:s'),
            'realCounted' => number_format($c['realTotal'], 2, ',', ''),
            'gap' => number_format($c['gap'], 2, ',', ''),
            'closured' => boolval($c['closure']),
            "closureDate" => $c['closureDate'] ? $c['closureDate']->format('d/m/Y') : "",
        );

        return $result;
    }

    public function listItems($criteria, $order, $limit, $offset, $search = null, $onlyList = false)
    {
        $chestCounts = $this->em->getRepository("Financial:ChestCount")->getChestCountsFilteredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $search,
            $onlyList
        );

        return $this->serializeItems($chestCounts);
    }

    public function createExcelFile(ChestCount $chestCount)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();

        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('chest.counting.title'));

        // Date
        ExcelUtilities::setLabel($sheet, "A2", "C2", $this->translator->trans('chest.counting.date'));
        ExcelUtilities::setValue($sheet, "D2", "E2", $chestCount->getDate('d/m/Y'));

        // Owner
        ExcelUtilities::setLabel($sheet, "A3", "C3", $this->translator->trans('chest.counting.counting_owner'));
        ExcelUtilities::setValue(
            $sheet,
            "D3",
            "E3",
            $chestCount->getOwner()->getFirstName() . " " . $chestCount->getOwner()->getLastName()
        );

        // Closure date
        ExcelUtilities::setLabel($sheet, "A4", "C4", $this->translator->trans('chest.listing.header.closured_day'));
        ExcelUtilities::setValue($sheet, "D4", "E4", $chestCount->getClosureDate('d/m/Y'));

        // Gap
        ExcelUtilities::setLabel($sheet, "N2", "P2", $this->translator->trans('chest.listing.header.diff') . " (euro)");
        ExcelUtilities::setValue($sheet, "N3", "P4", $chestCount->calculateGap($restaurant));

        $row = 6;
        // Preview title
        ExcelUtilities::setTitle(
            $sheet,
            "D" . $row,
            "O" . $row,
            $this->translator->trans('chest.counting.preview') . " (euro)"
        );
        $row++;
        // Preview header
        ExcelUtilities::setTableHeader($sheet, "D" . $row, "F" . $row, '');
        ExcelUtilities::setTableHeader(
            $sheet,
            "G" . $row,
            "I" . $row,
            $this->translator->trans('chest.preview.real_counted_title') . " (euro)"
        );
        ExcelUtilities::setTableHeader(
            $sheet,
            "J7",
            "L" . $row,
            $this->translator->trans('chest.preview.theorical') . " (euro)"
        );
        ExcelUtilities::setTableHeader(
            $sheet,
            "M" . $row,
            "O" . $row,
            $this->translator->trans('chest.preview.gap') . " (euro)"
        );
        $row++;
        // Preview body
        // Tirelire
        $tirelire = $chestCount->getTirelire();
        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.total_tirelire'),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $tirelire->calculateRealTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $tirelire->calculateTheoricalTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $tirelire->calculateGap($restaurant),
            true
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.envelope_not_versed'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $tirelire->calculateTotalCashNotVersedEnveloppes($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $tirelire->calculateTheoricalTotalCashNotVersedEnveloppes($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "M" . $row, "O" . $row, $tirelire->calculateGapCash());
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.tirelire_enveloppes_tr'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $tirelire->calculateTotalTrNotVersedEnveloppes($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $tirelire->calculateTotalTrNotVersedEnveloppes($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "M" . $row, "O" . $row, $tirelire->calculateGapTr());
        $row++;

        // Small chest
        $smallChest = $chestCount->getSmallChest();
        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.total_small_chest'),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $smallChest->calculateRealTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateGap($restaurant),
            true
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.small_chest_cash'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "G" . $row, "I" . $row, $smallChest->calculateRealCashTotal());
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalCashTotal($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateCashGap($restaurant)
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.small_chest_tr'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "G" . $row, "I" . $row, $smallChest->calculateRealTrTotal());
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalTrTotal($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateTrGap($restaurant)
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.small_chest_tre'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "G" . $row, "I" . $row, $smallChest->calculateRealTreTotal());
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalTreTotal($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateTreGap($restaurant)
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.small_chest_cb'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $smallChest->calculateRealCBTotal($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalCBTotal($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateCBGap($restaurant)
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.small_chest_cq'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $smallChest->calculateRealCheckQuickTotal()
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalCheckQuickTotal($restaurant)
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateCheckQuickGap($restaurant)
        );
        $row++;

        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.small_chest_foreign_currency'),
            false
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $smallChest->calculateRealForeignCurrencyTotal()
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $smallChest->calculateTheoricalForeignCurrencyTotal()
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "M" . $row,
            "O" . $row,
            $smallChest->calculateForeignCurrencyGap()
        );
        $row++;

        // Exchange fund
        $exchangeFund = $chestCount->getExchangeFund();
        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.total_exchange_fund'),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $exchangeFund->calculateRealTotal(),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $exchangeFund->calculateTheoricalTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "M" . $row, "O" . $row, $exchangeFund->calculateGap($restaurant), true);
        $row++;

        // Cashbox fund
        $cashboxFund = $chestCount->getCashboxFund();
        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.total_cashbox_fund'),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $cashboxFund->calculateRealTotal(),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $cashboxFund->calculateTheoricalTotal(),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "M" . $row, "O" . $row, $cashboxFund->calculateGap(), true);
        $row++;

        // Chest total
        ExcelUtilities::setTableColumnHeader(
            $sheet,
            "D" . $row,
            "F" . $row,
            $this->translator->trans('chest.counting.total_chest'),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "G" . $row,
            "I" . $row,
            $chestCount->calculateRealTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue(
            $sheet,
            "J" . $row,
            "L" . $row,
            $chestCount->calculateTheoricalTotal($restaurant),
            true
        );
        ExcelUtilities::setNumericCellTableBodyValue($sheet, "M" . $row, "O" . $row, $chestCount->calculateGap($restaurant), true);
        $row++;

        // Response creation
        $filename = "chest_count_details_" . $chestCount->getCreatedAt()->format('Y_m_d') . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @param $date
     * @return float|int
     * @deprecated Use getChestGap instead
     */
    public function getChestGapByDate($date)
    {

        $totalGap = 0;
        $chestCounts = $this->em->getRepository('Financial:ChestCount')->getChestByDate($date);

        foreach ($chestCounts as $count) {
            /**
             * @var ChestCount $count
             */
            $totalGap += $count->calculateGap();
        }

        return $totalGap;
    }

    public function getChestGap($starDate, $endDate = null, $currentRestaurant = null)
    {
        return $this->em->getRepository('Financial:ChestCount')->getChestGap($starDate, $endDate, $currentRestaurant);
    }

    public function generateExcelFile($criteria, $order, $limit, $offset, $logoPath, $search = null)
    {
        $data = $this->listItems($criteria, $order, $limit, $offset, $search, true);
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('chest.listing.title'));

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('chest.listing.title');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B5"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 5), 22, true);

        //logo
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        $objDrawing->setPath($logoPath);
        $objDrawing->setOffsetX(35);
        $objDrawing->setOffsetY(0);
        $objDrawing->setCoordinates('A2');
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 2), 12, true);
        $objDrawing->setWidth(28);                 //set width, height
        $objDrawing->setHeight(32);
        $objDrawing->setWorksheet($sheet);
        //restaurant name
        $sheet->mergeCells("B2:F2");
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $content = $currentRestaurant->getCode() . ' ' . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);


        $sheet->mergeCells("B10:K12");

        if (!is_null(
                $criteria['chest_counts_search[startDate']
            ) && $criteria['chest_counts_search[startDate'] != "" && !is_null(
                $criteria['chest_counts_search[endDate']
            ) && $criteria['chest_counts_search[endDate'] != ""
        ) {
            $sheet->setCellValue(
                'B10',
                $this->translator->trans('keyword.from') . ' : ' . $criteria['chest_counts_search[startDate'] . '  ' . $this->translator->trans('keyword.to') . ' : ' . $criteria['chest_counts_search[endDate']
            );
        } else {
            $sheet->setCellValue(
                'B10',
                $this->translator->trans('keyword.from') . ' : --  ' . $this->translator->trans('keyword.to') . ' : --'
            );
        }

        ExcelUtilities::setCellAlignment($sheet->getCell("B10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B10"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 10), 18, true);

        $sheet->mergeCells("B14:C14");
        $sheet->setCellValue('B14', $this->translator->trans('label.manager'));
        ExcelUtilities::setFont($sheet->getCell('B14'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("B14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B14"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B14"), "ECECEC");

        $sheet->mergeCells("D14:E14");
        if (!is_null($criteria['chest_counts_search[owner']) && $criteria['chest_counts_search[owner'] != '') {
            $responsinle = $this->em->getRepository('Staff:Employee')->find($criteria['chest_counts_search[owner']);
            $sheet->setCellValue('D14', $responsinle);
        } else {
            $sheet->setCellValue('D14', $this->translator->trans('label.all'));
        }
        ExcelUtilities::setFont($sheet->getCell('D14'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("D14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D14"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D14"), "ECECEC");

        $sheet->mergeCells("A16:B17");
        ExcelUtilities::setFont($sheet->getCell('A16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A16"), "ECECEC");
        $sheet->setCellValue('A16', $this->translator->trans('chest.listing.header.date'));
        ExcelUtilities::setBorder($sheet->getCell('A16'));
        ExcelUtilities::setBorder($sheet->getCell('B16'));
        ExcelUtilities::setBorder($sheet->getCell('A17'));
        ExcelUtilities::setBorder($sheet->getCell('B17'));

        $sheet->mergeCells("C16:D17");
        ExcelUtilities::setFont($sheet->getCell('C16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C16"), "ECECEC");
        $sheet->setCellValue('C16', $this->translator->trans('chest.listing.header.owner'));
        ExcelUtilities::setBorder($sheet->getCell('C16'));
        ExcelUtilities::setBorder($sheet->getCell('D16'));
        ExcelUtilities::setBorder($sheet->getCell('C17'));
        ExcelUtilities::setBorder($sheet->getCell('D17'));

        $sheet->mergeCells("E16:F17");
        ExcelUtilities::setFont($sheet->getCell('E16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E16"), "ECECEC");
        $sheet->setCellValue('E16', $this->translator->trans('chest.listing.header.real'));
        ExcelUtilities::setBorder($sheet->getCell('E16'));
        ExcelUtilities::setBorder($sheet->getCell('F16'));
        ExcelUtilities::setBorder($sheet->getCell('E17'));
        ExcelUtilities::setBorder($sheet->getCell('F17'));

        $sheet->mergeCells("G16:H17");
        ExcelUtilities::setFont($sheet->getCell('G16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G16"), "ECECEC");
        $sheet->setCellValue('G16', $this->translator->trans('chest.listing.header.diff'));
        ExcelUtilities::setBorder($sheet->getCell('G16'));
        ExcelUtilities::setBorder($sheet->getCell('H16'));
        ExcelUtilities::setBorder($sheet->getCell('G17'));
        ExcelUtilities::setBorder($sheet->getCell('H17'));

        $sheet->mergeCells("I16:J17");
        ExcelUtilities::setFont($sheet->getCell('I16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I16"), "ECECEC");
        $sheet->setCellValue('I16', $this->translator->trans('chest.listing.header.closured'));
        $sheet->getStyle('I16')->getAlignment()->setWrapText(true);
        ExcelUtilities::setBorder($sheet->getCell('I16'));
        ExcelUtilities::setBorder($sheet->getCell('J16'));
        ExcelUtilities::setBorder($sheet->getCell('I17'));
        ExcelUtilities::setBorder($sheet->getCell('J17'));

        $sheet->mergeCells("K16:L17");
        ExcelUtilities::setFont($sheet->getCell('K16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K16"), "ECECEC");
        $sheet->setCellValue('K16', $this->translator->trans('chest.listing.header.closured_day'));
        ExcelUtilities::setBorder($sheet->getCell('K16'));
        ExcelUtilities::setBorder($sheet->getCell('L16'));
        ExcelUtilities::setBorder($sheet->getCell('K17'));
        ExcelUtilities::setBorder($sheet->getCell('L17'));
        $startLine = 18;
        foreach ($data as $key => $line) {
            $sheet->mergeCells("A" . $startLine . ":B" . $startLine);
            $sheet->setCellValue('A' . $startLine, $line['date']);
            ExcelUtilities::setBorder($sheet->getCell('A' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('B' . $startLine));

            $sheet->mergeCells("C" . $startLine . ":D" . $startLine);
            $sheet->setCellValue('C' . $startLine, $line['owner']);
            ExcelUtilities::setBorder($sheet->getCell('C' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('D' . $startLine));

            $sheet->mergeCells("E" . $startLine . ":F" . $startLine);
            $sheet->setCellValue('E' . $startLine, number_format((float)str_replace(",", ".", $line['realCounted']), 2, '.', ''));
            ExcelUtilities::setBorder($sheet->getCell('E' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('F' . $startLine));


            $sheet->mergeCells("G" . $startLine . ":H" . $startLine);
            $sheet->setCellValue('G' . $startLine, number_format((float)str_replace(",", ".", $line['gap']), 2, '.', ''));
            ExcelUtilities::setBorder($sheet->getCell('G' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('H' . $startLine));


            $sheet->mergeCells("I" . $startLine . ":J" . $startLine);
            if ($line['closured']) {
                $sheet->setCellValue('I' . $startLine, $this->translator->trans('keyword.yes'));
            } else {
                $sheet->setCellValue('I' . $startLine, $this->translator->trans('keyword.no'));
            }
            ExcelUtilities::setBorder($sheet->getCell('I' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('J' . $startLine));

            $sheet->mergeCells("K" . $startLine . ":L" . $startLine);
            $sheet->setCellValue('K' . $startLine, $line['closureDate']);
            ExcelUtilities::setBorder($sheet->getCell('K' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('L' . $startLine));

            $startLine++;
        }
        //Creation de la response
        $filename = "liste_comptage_coffre" . date('dmY_His') . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
