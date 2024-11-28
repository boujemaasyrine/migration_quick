<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 10/05/2016
 * Time: 12:05
 */

namespace AppBundle\Report\Service;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Financial\Service\CashboxService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Model\CashBookReport;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportCashBookService
{

    private $em;
    private $translator;
    private $cashboxService;
    private $parameterService;
    private $phpExcel;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        CashboxService $cashboxService,
        ParameterService $parameterService,
        Factory $phpExcel
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->cashboxService = $cashboxService;
        $this->parameterService = $parameterService;
        $this->phpExcel = $phpExcel;
    }

    public function generateCashbookReport($date,$restaurant)
    {
        $cashBookReport = new CashBookReport($this->cashboxService, $this->em);
        $cashBookReport->setDate($date);
        $cashBookReport->setOriginRestaurant($restaurant);
        return $cashBookReport->calculateBalancing($restaurant);
    }

    public function getAllCashBookResult($date)
    {
        $cashBookReport = new CashBookReport($this->cashboxService, $this->em);
        $cashBookReport->setDate($date);

        $dayIncome = $cashBookReport->getDayIncome();

        $caBrutByTva = $cashBookReport->getTotalCaBrutThisDateByTva();
        $totalMealTicket = $cashBookReport->getMealTicketTotalPayment();
        $totalCaBrut = $cashBookReport->getTotalCaBrutThisDate() ? $cashBookReport->getTotalCaBrutThisDate() : 0;

        if ($totalMealTicket > 0) {
            foreach ($caBrutByTva as &$caTva) {
                $proportion = $totalCaBrut > 0 ? $caTva['totalAmount'] / $totalCaBrut : 0;
                $amountToSubtract = $totalMealTicket * $proportion;
                $caTva['totalAmount'] -= $amountToSubtract;
            }
            $totalCaBrut = $totalCaBrut - $totalMealTicket;
        }

        $result = [
            'totalCaBrut' => $totalCaBrut,
            'caBrutByTva' => $caBrutByTva,
            'mealTicket' => $cashBookReport->getMealTicketTotalPayment(),
            'expensesBySubGroup' => $cashBookReport->getExpensesByDateFiltredBySubGroup(),
            'recipeByLabel' => $cashBookReport->getRecipeByDateFiltredByLabel(),
            'expensesAmount' => $cashBookReport->getTotalExpensesByDate(),
            'recipeTicketsAmount' => $cashBookReport->getTotalRecipeTicketsByDate(),
            'differenceRecipeExpense' => $cashBookReport->getDifferenceRecipeExpense(),
            'previousCredit' => $cashBookReport->getPreviousCredit(),
            'currentCredit' => $cashBookReport->getCurrentCredit(),
            'dailyRecipe' => $dayIncome->getCashboxTotal(),
            'cashGap' => $cashBookReport->getCashBoxError(),
            'chestGap' => $cashBookReport->getChestError() ? $cashBookReport->getChestError() : 0,
        ];

        $result['recipesAmount'] = $result['dailyRecipe'] + $result['recipeTicketsAmount'];

        return $result;
    }

    public function getAllCashBookResultBetweenTwoDates($criteria, Restaurant $currentRestaurant)
    {
        $this->em->getConfiguration()->setSQLLogger(null);
        $result = array();
        /**
         * @var \DateTime $date
         */
        $date = clone $criteria['startDate'];

        while ($date <= $criteria['endDate']) {
            $closure = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
                [
                    'date' => $date,
                    'originRestaurant' => $currentRestaurant,
                ]
            ) ? true : false;
            if ($closure) {
                $cashBookReport = new CashBookReport($this->cashboxService, $this->em);
                $cashBookReport->setDate($date)
                    ->setOriginRestaurant($currentRestaurant);

                $dayIncome = $cashBookReport->getDayIncome();

                $caBrutByTva = $cashBookReport->getTotalCaBrutThisDateByTva();
                $totalMealTicket = $cashBookReport->getMealTicketTotalPayment();
                $_totalCaBrut = $cashBookReport->getTotalCaBrutThisDate();
                $totalCaBrut = $_totalCaBrut ? $_totalCaBrut : 0;

                if ($totalMealTicket > 0) {
                    foreach ($caBrutByTva as &$caTva) {
                        $proportion = $totalCaBrut > 0 ? $caTva['totalAmount'] / $totalCaBrut : 0;
                        $amountToSubtract = $totalMealTicket * $proportion;
                        $caTva['totalAmount'] -= $amountToSubtract;
                    }
                }
                $chestGap = $cashBookReport->getChestError();
                $firstClosing=$this->em->getRepository(AdministrativeClosing::class)->getFirstClosingDate($currentRestaurant);
                $date->setTime(0,0,0);
                $diff=$date->diff($firstClosing);
                $diffDays = (integer)$diff->format("%R%a");
                if($diffDays==0){
                    $chestCount=$this->em->getRepository(ChestCount::class)->findOneBy(array('originRestaurant'=>$currentRestaurant));
                    if($chestCount){
                        $chestAmount=$chestCount->getCashboxFund()->calculateTheoricalTotal();
                    }
                    else {
                        $chestAmount=0;
                    }
                    $result[$date->format('Y-m-d')] = [
                        'expensesBySubGroup' => $cashBookReport->getExpensesByDateFiltredBySubGroup(),
                        'recipeByLabel' => $cashBookReport->getRecipeByDateFiltredByLabel(),
                        'chestGap' => $chestGap ? $chestGap : 0,
                        'expensesAmount' => $cashBookReport->getTotalExpensesByDate(),
                        'recipeTicketsAmount' => $cashBookReport->getTotalRecipeTicketsByDate(),
                        'differenceRecipeExpense' => $cashBookReport->getDifferenceRecipeExpense(),
                        'currentChestAmount' =>$chestAmount,
                        'currentBalancing' => $cashBookReport->getBalancingInThisDate(),
                        'dailyRecipe' => $dayIncome->calculateCashboxTheoricalTotal(),
                        'RealCashGap' => $dayIncome->calculateCashboxTotalGap(),
                    ];
                }
                else {
                    $result[$date->format('Y-m-d')] = [
                        'expensesBySubGroup'      => $cashBookReport->getExpensesByDateFiltredBySubGroup(
                        ),
                        'recipeByLabel'           => $cashBookReport->getRecipeByDateFiltredByLabel(
                        ),
                        'chestGap'                => $chestGap ? $chestGap : 0,
                        'expensesAmount'          => $cashBookReport->getTotalExpensesByDate(
                        ),
                        'recipeTicketsAmount'     => $cashBookReport->getTotalRecipeTicketsByDate(
                        ),
                        'differenceRecipeExpense' => $cashBookReport->getDifferenceRecipeExpense(
                        ),
                        'currentChestAmount'      => $this->getCurrentChestAmount(
                            $cashBookReport
                        ),
                        'currentBalancing'        => $cashBookReport->getBalancingInThisDate(
                        ),
                        'dailyRecipe'             => $dayIncome->calculateCashboxTheoricalTotal(
                        ),
                        'RealCashGap' => $dayIncome->calculateCashboxTotalGap(),
                    ];
                }


                $result[$date->format('Y-m-d')]['recipesAmount'] = $result[$date->format(
                        'Y-m-d'
                    )]['dailyRecipe'] + $result[$date->format('Y-m-d')]['recipeTicketsAmount'];
            }
            $result[$date->format('Y-m-d')]['closure'] = $closure;
            $date = Utilities::getDateFromDate($date, 1);
            $this->em->clear();
        }

        return $result;
    }

    /**
     * @param CashBookReport $cashBookReport
     * @return float|int
     */
    public function getCurrentChestAmount(CashBookReport $cashBookReport)
    {
        $amountChestCountForThisDate = 0;

        $date = $cashBookReport->getDate();
        $closure = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
            ['date' => $date, "originRestaurant" => $cashBookReport->getOriginRestaurant()]
        ) ? true : false;

        if ($closure) {
            $currentChestCount = $this->em->getRepository(ChestCount::class)->getChestCountForClosedDate(
                $date,
                $cashBookReport->getOriginRestaurant()
            );
            //$amountChestCountForThisDate = $currentChestCount ? $this->calculateRealTotal($currentChestCount) : 0;
            $amountChestCountForThisDate = $currentChestCount ? $currentChestCount->getRealTotal() : 0;

        } else {
            $lastClosingDate = $this->em->getRepository(AdministrativeClosing::class)->getLastClosingDateFromDate(
                $date
            );

            


            if ($lastClosingDate) {
                $chestCountLastClosingDate =
                    $this->em->getRepository('Financial:ChestCount')->getChestCountForClosedDate(
                        $lastClosingDate,
                        $cashBookReport->getOriginRestaurant()
                    );

               // $amountChestCountForThisDate = $chestCountLastClosingDate ? $this->calculateRealTotal($chestCountLastClosingDate) : 0;

                $amountChestCountForThisDate = $chestCountLastClosingDate ? $chestCountLastClosingDate->getRealTotal() : 0;
                $lastClosingDate = Utilities::getDateFromDate($lastClosingDate, 1);

                while ($lastClosingDate <= $date) {
                    $cashBook = new CashBookReport($this->cashboxService, $this->em);
                    $cashBook->setOriginRestaurant($cashBookReport->getOriginRestaurant());
                    $cashBook->setDate($lastClosingDate);
                    $amountChestCountForThisDate += $cashBook->getDifferenceRecipeExpense();
                    $lastClosingDate = Utilities::getDateFromDate($lastClosingDate, 1);
                }
            }
        }

        return $amountChestCountForThisDate;
    }

    public function calculateRealTotal(ChestCount $currentChestCount)
    {
        $qb = $this->em->getRepository('Financial:Envelope')->createQueryBuilder('e');
        $qb->select('sum(e.amount)')
            ->leftJoin('e.deposit', 'd')
            ->where('e.createdAt < :chestDate and (d.createdAt > :chestDate or e.deposit is null)')
            ->andWhere("e.originRestaurant = :restaurant")
            ->setParameter("restaurant", $currentChestCount->getOriginRestaurant())
            ->setParameter('chestDate', $currentChestCount->getDate());

        $totalEnveloppe = $qb->getQuery()->getSingleScalarResult();

        $total = 0.0;
        $total += $totalEnveloppe;
        $total += $currentChestCount->getSmallChest()->getRealTotal();
        $total += $currentChestCount->getExchangeFund()->getRealTotal();
        $total += $currentChestCount->getCashboxFund()->calculateRealTotal();


        return $total;
    }

    public function serializeCashBookReportResult($result)
    {
        $csvResult = array();
        $countRecipes = count($result['caBrutByTva']) + count($result['recipeByLabel']) + 2;
        $countExpenses = count($result['expensesBySubGroup']);
        if ($result['cashGap'] > 0) {
            $countRecipes++;
        } else {
            $countExpenses++;
        }
        if ($result['chestGap'] > 0) {
            $countRecipes++;
        } else {
            $countExpenses++;
        }

        $csvResult[] = [
            $this->translator->trans('cash_book.recipe'),
            '',
            number_format($result['totalCaBrut'], 2, ',', ''),
        ];

        foreach ($result['caBrutByTva'] as $caTva) {
            $csvResult[] = [
                'TVA',
                $caTva['tva'].' %',
                number_format($caTva['totalAmount'], 2, ',', ''),
            ];
        }
        $csvResult[] = [
            $this->translator->trans('cash_book.daily_recipe'),
            '',
            number_format($result['dailyRecipe'], 2, ',', ''),
        ];
        if ($result['cashGap'] > 0) {
            $csvResult[] = [
                $this->translator->trans('keyword.cash_gap'),
                '',
                abs(number_format($result['cashGap'], 2, ',', '')),
            ];
        }

        foreach ($result['recipeByLabel'] as $recipeTicket) {
            $csvResult[] = [
                $this->parameterService->getRecipeTicketLabel($recipeTicket['label']),
                '',
                abs(number_format($recipeTicket['totalAmount'], 2, ',', '')),
            ];
        }

        if ($result['chestGap'] > 0) {
            $csvResult[] = [
                $this->translator->trans('keyword.chest_gap'),
                '',
                abs(number_format($result['chestGap'], 2, ',', '')),
            ];
        }

        if ($countExpenses <= $countRecipes) {
            $i = 0;
            foreach ($result['expensesBySubGroup'] as $expense) {
                array_push($csvResult[$i], '', $expense['subGroup'], $expense['totalAmount']);
                $i++;
            }
            if ($result['cashGap'] < 0) {
                array_push(
                    $csvResult[$i],
                    '',
                    $this->translator->trans('keyword.cash_gap'),
                    number_format($result['cashGap'], 2, ',', '')
                );
                $i++;
            }
            if ($result['chestGap'] < 0) {
                array_push(
                    $csvResult[$i],
                    '',
                    $this->translator->trans('keyword.cash_gap'),
                    number_format($result['chestGap'], 2, ',', '')
                );
            }
        } else {
            for ($i = 0; $i < $countRecipes; $i++) {
                if (isset($result['expensesBySubGroup'][$i])) {
                    $label = $this->getLabelExpense(
                        $result['expensesBySubGroup'][$i]['groupExpense'],
                        $result['expensesBySubGroup'][$i]['subGroup']
                    );
                    array_push($csvResult[$i], '', $label, $result['expensesBySubGroup'][$i]['totalAmount']);
                } else {
                    break;
                }
            }
            if ($i = $countRecipes - 1) {
                $count = count($result['expensesBySubGroup']);
                for ($i = $i + 1; $i < $count; $i++) {
                    $label = $this->getLabelExpense(
                        $result['expensesBySubGroup'][$i]['groupExpense'],
                        $result['expensesBySubGroup'][$i]['subGroup']
                    );
                    $csvResult[] = [
                        '',
                        '',
                        '',
                        '',
                        $label,
                        $result['expensesBySubGroup'][$i]['totalAmount'],
                    ];
                }
            }
            if ($result['cashGap'] < 0) {
                $csvResult[] = [
                    '',
                    '',
                    '',
                    '',
                    $this->translator->trans('keyword.cash_gap'),
                    number_format(abs($result['cashGap']), 2, ',', ''),
                ];
                $i++;
            }
            if ($result['chestGap'] < 0) {
                $csvResult[] = [
                    '',
                    '',
                    '',
                    '',
                    $this->translator->trans('keyword.chest_gap'),
                    number_format(abs($result['chestGap']), 2, ',', ''),
                ];
            }
        }

        $csvResult[] = [
            $this->translator->trans('cash_book.total_recipe'),
            '',
            number_format($result['dailyRecipe'], 2, ',', ''),
            '',
            $this->translator->trans('cash_book.total_expense'),
            number_format($result['expensesAmount'], 2, ',', ''),
        ];


        return $csvResult;
    }

    public function getCsvHeader($previousCredit, $currentCredit)
    {
        $header = [
            [
                '',
                $this->translator->trans('cash_book.recipes'),
                '',
                '',
                $this->translator->trans('cash_book.expense'),
                '',
            ],
            [
                '',
                $this->translator->trans('cash_book.previous_credit').' : '.number_format($previousCredit, 2, ',', ''),
                '',
                '',
                $this->translator->trans('cash_book.current_credit').' : '.number_format($currentCredit, 2, ',', ''),
                '',
            ],
            [
                $this->translator->trans('keyword.label'),
                '',
                $this->translator->trans('keyword.amount'),
                '',
                $this->translator->trans('keyword.label'),
                $this->translator->trans('keyword.amount'),
            ],
        ];

        return $header;
    }

    public function getLabelExpense($group, $value)
    {
        $label = '';
        if ($group == Expense::GROUP_BANK_RESTAURANT_PAYMENT
            || $group == Expense::GROUP_BANK_E_RESTAURANT_PAYMENT
        ) {
            $label = $this->parameterService->getTicketRestaurantLabel($value);
        } elseif ($group == Expense::GROUP_BANK_CARD_PAYMENT) {
            $label = $this->parameterService->getBankCardLabel($value);
            $label = $this->translator->trans('expense.group.'.$group).' : '.$label;
        } elseif ($group == Expense::GROUP_BANK_CASH_PAYMENT) {
            $label = $this->translator->trans('keyword.cash');
        } elseif ($group == Expense::GROUP_OTHERS) {
            $label = $this->parameterService->getGroupOtherExpenseLabel($value);
        }

        return $label;
    }

    /**
     * @param $result
     * @param \DateTime $date
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PHPExcel_Exception
     */
    public function createExcelFile($result, $date)
    {
        $row = 17;
        $col = 9;
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('report.cash_book.title'));

        $sheet->mergeCells("B3:K6");
        $sheet->setCellValue('B3', $this->translator->trans('report.cash_book.title'));
        ExcelUtilities::setCellAlignment($sheet->getCell("B3"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B3"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 3), 22, true);

        $sheet->mergeCells("B8:K10");
        $sheet->setCellValue('B8', $this->translator->trans('keyword.date').' : '.$date->format('d-m-Y'));
        ExcelUtilities::setCellAlignment($sheet->getCell("B8"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B8"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 3), 22, true);

        $sheet->mergeCells("B12:F14");
        $sheet->setCellValue('B12', $this->translator->trans('cash_book.recipes'));
        ExcelUtilities::setFont($sheet->getCell('B12'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("B12"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B12"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B12"), "ECECEC");

        $sheet->mergeCells("G12:K14");
        $sheet->setCellValue('G12', $this->translator->trans('cash_book.expense'));
        ExcelUtilities::setFont($sheet->getCell('G12'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("G12"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G12"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G12"), "ECECEC");

        $sheet->mergeCells("B15:F17");
        $sheet->setCellValue(
            'B15',
            $this->translator->trans('cash_book.previous_credit').' : '.number_format(
                $result['previousCredit'],
                2,
                ',',
                ''
            )
        );
        ExcelUtilities::setFont($sheet->getCell('B15'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("B15"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B15"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B15"), "ECECEC");

        $sheet->mergeCells("G15:K17");
        $sheet->setCellValue(
            'G15',
            $this->translator->trans('cash_book.previous_credit').' : '.number_format(
                $result['currentCredit'],
                2,
                ',',
                ''
            )
        );
        ExcelUtilities::setFont($sheet->getCell('G15'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("G15"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G15"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G15"), "ECECEC");

        $countRecipes = count($result['caBrutByTva']) + count($result['recipeByLabel']) + 2;
        $countExpenses = count($result['expensesBySubGroup']);
        if ($result['cashGap'] > 0) {
            $countRecipes++;
        } elseif ($result['cashGap'] < 0) {
            $countExpenses++;
        }
        if ($result['chestGap'] > 0) {
            $countRecipes++;
        } elseif ($result['chestGap'] < 0) {
            $countExpenses++;
        }

        $sheet->mergeCells("B18:C19");
        $sheet->setCellValue('B18', $this->translator->trans('cash_book.recipe'));
        $sheet->mergeCells("D18:D19");
        $sheet->mergeCells("E18:F19");
        $sheet->setCellValue('E18', number_format($result['totalCaBrut'], 2, ',', ''));

        $recipeSheetIndex = 20;
        foreach ($result['caBrutByTva'] as $caTva) {
            $sheet->mergeCells("B".$recipeSheetIndex.":C".($recipeSheetIndex + 1));
            $sheet->setCellValue('B'.$recipeSheetIndex, 'TVA');
            $sheet->mergeCells("D".$recipeSheetIndex.":D".($recipeSheetIndex + 1));
            $sheet->setCellValue('D'.$recipeSheetIndex, $caTva['tva']." %");
            $sheet->mergeCells("E".$recipeSheetIndex.":F".($recipeSheetIndex + 1));
            $sheet->setCellValue('E'.$recipeSheetIndex, number_format($caTva['totalAmount'], 2, ',', ''));
            $recipeSheetIndex += 2;
        }

        $sheet->mergeCells("B".$recipeSheetIndex.":C".($recipeSheetIndex + 1));
        $sheet->setCellValue('B'.$recipeSheetIndex, $this->translator->trans('cash_book.daily_recipe'));
        $sheet->mergeCells("D".$recipeSheetIndex.":D".($recipeSheetIndex + 1));
        $sheet->mergeCells("E".$recipeSheetIndex.":F".($recipeSheetIndex + 1));
        $sheet->setCellValue('E'.$recipeSheetIndex, number_format($result['dailyRecipe'], 2, ',', ''));
        $recipeSheetIndex += 2;

        if ($result['cashGap'] > 0) {
            $sheet->mergeCells("B".$recipeSheetIndex.":C".($recipeSheetIndex + 1));
            $sheet->setCellValue('B'.$recipeSheetIndex, $this->translator->trans('keyword.cash_gap'));
            $sheet->mergeCells("D".$recipeSheetIndex.":D".($recipeSheetIndex + 1));
            $sheet->mergeCells("E".$recipeSheetIndex.":F".($recipeSheetIndex + 1));
            $sheet->setCellValue('E'.$recipeSheetIndex, number_format($result['cashGap'], 2, ',', ''));
            $recipeSheetIndex += 2;
        }

        if (!is_null($result['recipeByLabel'])) {
            foreach ($result['recipeByLabel'] as $recipeTicket) {
                $sheet->mergeCells("B".$recipeSheetIndex.":C".($recipeSheetIndex + 1));
                $sheet->setCellValue(
                    'B'.$recipeSheetIndex,
                    $this->parameterService->getRecipeTicketLabel($recipeTicket['label'])
                );
                $sheet->mergeCells("D".$recipeSheetIndex.":D".($recipeSheetIndex + 1));
                $sheet->setCellValue('D'.$recipeSheetIndex, "");
                $sheet->mergeCells("E".$recipeSheetIndex.":F".($recipeSheetIndex + 1));
                $sheet->setCellValue('E'.$recipeSheetIndex, number_format($recipeTicket['totalAmount'], 2, ',', ''));
                $recipeSheetIndex += 2;
            }
        }

        if ($result['chestGap'] > 0) {
            $sheet->mergeCells("B".$recipeSheetIndex.":C".($recipeSheetIndex + 1));
            $sheet->setCellValue('B'.$recipeSheetIndex, $this->translator->trans('keyword.chest_gap'));
            $sheet->mergeCells("D".$recipeSheetIndex.":D".($recipeSheetIndex + 1));
            $sheet->mergeCells("E".$recipeSheetIndex.":F".($recipeSheetIndex + 1));
            $sheet->setCellValue('E'.$recipeSheetIndex, number_format($result['chestGap'], 2, ',', ''));
        }

        $expenseSheetIndex = 18;
        foreach ($result['expensesBySubGroup'] as $expense) {
            $label = $this->getLabelExpense($expense['groupExpense'], $expense['subGroup']);
            $sheet->mergeCells("G".$expenseSheetIndex.":I".($expenseSheetIndex + 1));
            $sheet->setCellValue('G'.$expenseSheetIndex, $label);
            $sheet->getStyle('G'.$expenseSheetIndex)->getAlignment()->setWrapText(true);
            $sheet->mergeCells("J".$expenseSheetIndex.":K".($expenseSheetIndex + 1));
            $sheet->setCellValue('J'.$expenseSheetIndex, number_format($expense['totalAmount'], 2, ',', ''));
            $expenseSheetIndex += 2;
        }
        if ($result['cashGap'] < 0) {
            $sheet->mergeCells("G".$expenseSheetIndex.":I".($expenseSheetIndex + 1));
            $sheet->setCellValue('G'.$expenseSheetIndex, $this->translator->trans('keyword.cash_gap'));
            $sheet->mergeCells("J".$expenseSheetIndex.":K".($expenseSheetIndex + 1));
            $sheet->setCellValue('J'.$expenseSheetIndex, abs(number_format($result['cashGap'], 2, ',', '')));
            $expenseSheetIndex += 2;
        }
        if ($result['chestGap'] < 0) {
            $sheet->mergeCells("G".$expenseSheetIndex.":I".($expenseSheetIndex + 1));
            $sheet->setCellValue('G'.$expenseSheetIndex, $this->translator->trans('keyword.cash_gap'));
            $sheet->mergeCells("J".$expenseSheetIndex.":K".($expenseSheetIndex + 1));
            $sheet->setCellValue('J'.$expenseSheetIndex, abs(number_format($result['chestGap'], 2, ',', '')));
        }

        $maxCount = max([$countExpenses, $countRecipes]);
        $resultIndex = $maxCount * 2 + 18;
        $sheet->mergeCells("B".$resultIndex.":C".($resultIndex + 1));
        $sheet->setCellValue('B'.$resultIndex, $this->translator->trans('cash_book.total_recipe'));
        $sheet->mergeCells("D".$resultIndex.":D".($resultIndex + 1));
        $sheet->mergeCells("E".$resultIndex.":F".($resultIndex + 1));
        $sheet->setCellValue('E'.$resultIndex, number_format($result['recipesAmount'], 2, ',', ''));

        $sheet->mergeCells("G".$resultIndex.":I".($resultIndex + 1));
        $sheet->setCellValue('G'.$resultIndex, $this->translator->trans('cash_book.total_expense'));
        $sheet->mergeCells("J".$resultIndex.":K".($resultIndex + 1));
        $sheet->setCellValue('J'.$resultIndex, number_format($result['expensesAmount'], 2, ',', ''));
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$resultIndex), "CDDAB4");
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$resultIndex), "CDDAB4");
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$resultIndex), "CDDAB4");
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$resultIndex), "CDDAB4");
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$resultIndex), "CDDAB4");


        //Creation de la response
        $filename = "livre_caisse_CSV".date('dmY_His').".xls";
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

    public function createExcelFileSecondVersion($result, $criteria, Restaurant $currentRestaurant, $logoPath)
    {
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('report.cash_book.title'));

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $sheet->mergeCells("B5:E7");
        $content = $this->translator->trans('report.cash_book.title');

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
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        $sheet->mergeCells("B10:E12");
        $sheet->setCellValue(
            'B10',
            $this->translator->trans('keyword.from').' : '.$criteria['startDate']->format('d-m-Y').'  '.$this->translator->trans('keyword.to').' : '.$criteria['endDate']->format('d-m-Y')
        );
        ExcelUtilities::setCellAlignment($sheet->getCell("B10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B10"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 10), 18, true);

        $sheet->setCellValue('B14', $this->translator->trans('keyword.date'));
        ExcelUtilities::setFont($sheet->getCell('B14'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("B14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B14"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B14"), "ECECEC");

        $sheet->setCellValue('C14', $this->translator->trans('keyword_descriptions'));
        ExcelUtilities::setFont($sheet->getCell('C14'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("C14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("C14"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C14"), "ECECEC");

        $sheet->setCellValue('D14', $this->translator->trans('keyword_recipes'));
        ExcelUtilities::setFont($sheet->getCell('D14'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("D14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D14"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D14"), "ECECEC");

        $sheet->setCellValue('E14', $this->translator->trans('keyword_expenses'));
        ExcelUtilities::setFont($sheet->getCell('E14'), 11, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("E14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("E14"), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E14"), "ECECEC");
        $indexCell = 15;
        foreach ($result as $key => $line) {
            if ($line['closure']) {
                $index = 1;

                foreach ($line['expensesBySubGroup'] as $expense) {
                    $label = $this->getLabelExpense($expense['groupExpense'], $expense['subGroup']);
                    $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                    if ($index == 1) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell('B'.$indexCell), "FCD29F");
                    }
                    $sheet->setCellValue('C'.$indexCell, $label);
                    $sheet->setCellValue('E'.$indexCell, number_format($expense['totalAmount'], 2, ',', ''));
                    $index++;
                    $indexCell++;
                }

                if ($line['chestGap'] < 0) {
                    $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                    $sheet->setCellValue('C'.$indexCell, $this->translator->trans('keyword.chest_gap'));
                    $sheet->setCellValue('E'.$indexCell, number_format(abs($line['chestGap']), 2, ',', ''));
                    $index++;
                    $indexCell++;
                }

                foreach ($line['recipeByLabel'] as $recipeTicket) {
                    $label = $this->parameterService->getRecipeTicketLabel($recipeTicket['label']);
                    $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                    if ($index == 1) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell('B'.$indexCell), "FCD29F");
                    }
                    $sheet->setCellValue('C'.$indexCell, $label);
                    $sheet->setCellValue('D'.$indexCell, number_format($recipeTicket['totalAmount'], 2, ',', ''));
                    $index++;
                    $indexCell++;
                }
                if ($line['chestGap'] > 0) {
                    $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                    $sheet->setCellValue('C'.$indexCell, $this->translator->trans('keyword.chest_gap'));
                    $sheet->setCellValue('D'.$indexCell, number_format($line['chestGap'], 2, ',', ''));
                    $index++;
                    $indexCell++;
                }

                $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                if ($index == 1) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell('B'.$indexCell), "FCD29F");
                }
                $sheet->setCellValue('C'.$indexCell, $this->translator->trans('cash_book.daily_recipe_theo'));
                $sheet->setCellValue('D'.$indexCell, number_format($line['dailyRecipe'], 2, ',', ''));
                $index++;
                $indexCell++;

                $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                if ($index == 1) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell('B'.$indexCell), "FCD29F");
                }
                $sheet->setCellValue('C'.$indexCell, $this->translator->trans('cash_book.real_cashGap'));
                if($line['RealCashGap']>0) {
                    $sheet->setCellValue(
                        'D'.$indexCell,
                        number_format($line['RealCashGap'], 2, ',', '')
                    );
                }
                else {
                    $sheet->setCellValue(
                        'E'.$indexCell,
                        number_format(abs($line['RealCashGap']), 2, ',', '')
                    );
                }
                $index++;
                $indexCell++;

                $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                if ($index == 1) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell('B'.$indexCell), "FCD29F");
                }
                $sheet->setCellValue('C'.$indexCell, $this->translator->trans('keyword_balancing'));
                $sheet->setCellValue('D'.$indexCell, number_format($line['currentBalancing'], 2, ',', ''));
                $index++;
                $indexCell++;

                $sheet->setCellValue('B'.$indexCell, $key.' - '.$index);
                if ($index == 1) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell('B'.$indexCell), "FCD29F");
                }

                $sheet->setCellValue('C'.$indexCell, $this->translator->trans('keyword_in_chest'));
                $sheet->setCellValue('D'.$indexCell, number_format($line['currentChestAmount'], 2, ',', ''));
                $indexCell++;
            }
        }
        //Border
        for ($cp = 14; $cp < $indexCell; $cp++) {
            ExcelUtilities::setBorder($sheet->getCell('B'.$cp));
            ExcelUtilities::setBorder($sheet->getCell('C'.$cp));
            ExcelUtilities::setBorder($sheet->getCell('D'.$cp));
            ExcelUtilities::setBorder($sheet->getCell('E'.$cp));
        }
        foreach(range('B','E') as $columnID) {
            $phpExcelObject->getActiveSheet()->getColumnDimension($columnID)
                ->setAutoSize(true);
        }
        //Creation de la response
        $filename = "livre_caisse_".date('dmY_His').".xls";
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
