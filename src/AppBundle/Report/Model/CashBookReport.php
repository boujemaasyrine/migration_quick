<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/05/2016
 * Time: 12:28
 */

namespace AppBundle\Report\Model;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\Financial\Service\CashboxService;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;

class CashBookReport
{
    use OriginRestaurantTrait;

    /**
     * @var \DateTime $date
     */
    private $date;

    /**
     * @var CashboxService $cashBoxService
     */
    private $cashBoxService;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * CashBookReport constructor.
     *
     * @param CashboxService $cashBoxService
     */

    public function __construct(CashboxService $cashBoxService, EntityManager $em)
    {
        $this->cashBoxService = $cashBoxService;
        $this->em = $em;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return CashBookReport
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return DayIncome $dayIncome
     */
    public function getDayIncome($restaurant=null)
    {
        $dayIncome = new DayIncome();
        $dayIncome->setDate($this->date);
        $dayIncome->setCashboxCounts($this->cashBoxService->findCashboxCountsByDate($dayIncome->getDate(),$restaurant));

        return $dayIncome;
    }

    /**
     * @return float
     */
    public function getTotalCaBrutThisDate()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $result = $this->em->getRepository(TicketLine::class)->getAmountLinesTicketByDate(
            $this->date,
            false,
            $currentRestaurant
        );

        return $result;
    }

    public function getTotalCaBrutThisDateByTva()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $result = $this->em->getRepository(TicketLine::class)->getAmountLinesTicketByDate(
            $this->date,
            true,
            $currentRestaurant
        );

        return $result;
    }

    public function getTotalExpensesByDate()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $result = $this->em->getRepository(Expense::class)->getAllExpenseByDate($this->date, false, $currentRestaurant);

        if ($this->getChestError() < 0) {
            $result += abs($this->getChestError());
        }

        return $result;
    }

    public function getTotalRecipeTicketsByDate()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $result = $this->em->getRepository(RecipeTicket::class)->getAllRecipeByDate(
            $this->date,
            false,
            $currentRestaurant
        );

        if ($this->getChestError() > 0) {
            $result += abs($this->getChestError());
        }

        return $result;
    }

    public function getExpensesByDateFiltredBySubGroup()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $result = $this->em->getRepository(Expense::class)->getAllExpenseByDate($this->date, true, $currentRestaurant);

        return $result;
    }

    public function getRecipeByDateFiltredByLabel()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $result = $this->em->getRepository(RecipeTicket::class)->getAllRecipeByDate(
            $this->date,
            true,
            $currentRestaurant
        );

        return $result;
    }

    public function getDifferenceRecipeExpense($restaurant=null)
    {
        if($restaurant){
            return $this->getDayIncome($restaurant)->getCashboxTotal()+ $this->getTotalRecipeTicketsByDate(
                ) - $this->getTotalExpensesByDate();
        }
        return $this->getDayIncome()->getCashboxTotal() + $this->getTotalRecipeTicketsByDate(
        ) - $this->getTotalExpensesByDate();
    }

    public function getPreviousCredit()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $initialCreditDate = $this->em->getRepository(AdministrativeClosing::class)->getFirstClosingDate(
            $currentRestaurant
        );
        if ($initialCreditDate >= $this->date) {
            return 0;
        }

        $previousDate = clone $this->date;
        $previousDate->sub(new \DateInterval('P1D'));

        /**
         * @var AdministrativeClosing $previousClosing
         */
        $previousClosing = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
            array(
                "date" => $previousDate,
                "originRestaurant" => $this->getOriginRestaurant(),
            )
        );

        while (!$previousClosing) {
            $previousDate->sub(new \DateInterval('P1D'));
            $previousClosing = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
                array(
                    "date" => $previousDate,
                    "originRestaurant" => $this->getOriginRestaurant(),
                )
            );
        }

        return $previousClosing->getCreditAmount();
    }

    public function getCurrentCredit()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $initialDate = $this->em->getRepository(AdministrativeClosing::class)->getFirstClosingDate($currentRestaurant);
        if ($initialDate > $this->date) {
            return 0;
        }

        return $this->getPreviousCredit() + $this->getDifferenceRecipeExpense();
    }

    public function getChestError()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $positiveChestError = $this->em->getRepository(RecipeTicket::class)->getTotalChestErrorRecipeTicketByDate(
            $this->date,
            $currentRestaurant
        );
        $negativeChestError = $this->em->getRepository(Expense::class)->getTotalChestErrorExpenseByDate(
            $this->date,
            $currentRestaurant
        );
        $positiveChestError = $positiveChestError ? $positiveChestError : 0;
        $negativeChestError = $negativeChestError ? $negativeChestError : 0;
        return abs($positiveChestError) - abs($negativeChestError);
    }

    public function getCashBoxError()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $positiveCashBoxError = $this->em->getRepository(RecipeTicket::class)->getTotalCashBoxErrorRecipeTicketByDate(
            $this->date,
            $currentRestaurant
        );
        $negativeCashBoxError = $this->em->getRepository('Financial:Expense')->getTotalCashBoxErrorExpenseByDate(
            $this->date,
            $currentRestaurant
        );
        $positiveCashBoxError = $positiveCashBoxError ? $positiveCashBoxError : 0;
        $negativeCashBoxError = $negativeCashBoxError ? $negativeCashBoxError : 0;

        return abs($positiveCashBoxError) - abs($negativeCashBoxError);
    }

    public function getMealTicketTotalPayment()
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $mealTicket = $this->em->getRepository(TicketPayment::class)->getTotalMealTicket(
            $this->date,
            $currentRestaurant
        );

        return $mealTicket ? $mealTicket : 0;
    }

    public function getPreviousChestAmount()
    {
        $previousDate = Utilities::getDateFromDate($this->date, -1);
        /**
         * @var ChestCount $previousChestCount ;
         */
        $amountLastChestCount = $this->getChestCountInDate($previousDate);

        return $amountLastChestCount;
    }

    public function getCurrentChestAmount()
    {

        $amountCurrentChestCount = $this->getChestCountInDate($this->date);

        return $amountCurrentChestCount;
    }

    /**
     * @return float|int
     * @deprecated
     */
    public function getCurrentBalancing()
    {
        return
            $this->getPreviousChestAmount() + $this->getDifferenceRecipeExpense();
    }

    public function getChestCountInDate($date)
    {
        $currentRestaurant = $this->getOriginRestaurant();
        $closure = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
            ['date' => $date, "originRestaurant" => $currentRestaurant]
        ) ? true : false;

        if ($closure) {
            $currentChestCount = $this->em->getRepository(ChestCount::class)->getChestCountForClosedDate(
                $date,
                $currentRestaurant
            );
            $amountChestCountForThisDate = $currentChestCount ? $currentChestCount->calculateRealTotal() : 0;
        } else {
            $lastClosingDate = $this->em->getRepository(AdministrativeClosing::class)->getLastClosingDateFromDate(
                $date
            );
            if ($lastClosingDate) {
                $chestCountLastClosingDate =
                    $this->em->getRepository(ChestCount::class)->getChestCountForClosedDate(
                        $lastClosingDate,
                        $currentRestaurant
                    );
                $amountChestCountForThisDate = $chestCountLastClosingDate ? $chestCountLastClosingDate->calculateRealTotal(
                ) : 0;
                $lastClosingDate = Utilities::getDateFromDate($lastClosingDate, 1);
                while ($lastClosingDate <= $date) {
                    $cashBook = new CashBookReport($this->cashBoxService, $this->em);
                    $cashBook->setOriginRestaurant($currentRestaurant);
                    $cashBook->setDate($lastClosingDate);
                    $amountChestCountForThisDate += $cashBook->getDifferenceRecipeExpense();
                    $lastClosingDate = Utilities::getDateFromDate($lastClosingDate, 1);
                }
            } else {
                $amountChestCountForThisDate = 0;
            }
        }

        return $amountChestCountForThisDate;
    }

    public function getPreviousBalancing()
    {



        $previousDate = clone $this->date;
        $previousDate->sub(new \DateInterval('P1D'));
        $currentRestaurant = $this->getOriginRestaurant();

        $lastChestCount = $this->em->getRepository(ChestCount::class)->getChestCountForClosedDate(
            $previousDate,
            $currentRestaurant
        );
        if ($lastChestCount) {
           /* $chestCredit = $this->em->getRepository(ChestCount::class)->calculateRealTotal(
                $lastChestCount,
                $currentRestaurant
            );*/

            /**
             * @var $lastChestCount ChestCount
             */
           $chestCredit=$lastChestCount->getRealTotal();


        }



        /**
         * @var AdministrativeClosing $previousClosing
         */
        $previousClosing = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
            array(
                "date" => $previousDate,
                "originRestaurant" => $currentRestaurant,
            )
        );
        $previousClosingAmount = $previousClosing ? $previousClosing->getCreditAmount() : 0;


        if (isset($chestCredit) && round($chestCredit, 2) != round($previousClosingAmount, 2)) {
            return $chestCredit;
        } else {
            return $previousClosingAmount;
        }
    }

    public function calculateBalancing($restaurant)
    {

        return $this->getPreviousBalancing() + $this->getDifferenceRecipeExpense($restaurant);
    }

    public function getBalancingInThisDate()
    {
        /**
         * @var AdministrativeClosing $thisClosing
         */
        $currentRestaurant = $this->getOriginRestaurant();
        $thisClosing = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
            array(
                "date" => $this->date,
                "originRestaurant" => $currentRestaurant,
            )
        );
        $thisClosingAmount = $thisClosing ? $thisClosing->getCreditAmount() : 0;

        return $thisClosingAmount;
    }
}
