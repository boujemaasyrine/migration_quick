<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 16:29
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\CompartmentCalculationInterface;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Traits\IdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * ChestTirelire
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class ChestTirelire implements CompartmentCalculationInterface
{

    public function __construct()
    {
    }

    use IdTrait;

    /**
     * @var ChestCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="tirelire")
     */
    private $chestCount;

    /**
     * @var float
     * @ORM\Column(name="real_total", type="float", nullable= TRUE)
     */
    private $realTotal;

    /**
     * @var float
     * @ORM\Column(name="theorical_total", type="float", nullable= TRUE)
     */
    private $theoricalTotal;

    /**
     * @var float
     * @ORM\Column(name="gap", type="float", nullable= TRUE)
     */
    private $gap;

    /**
     * @var float
     * @ORM\Column(name="total_cash_envelopes", type="float", nullable= TRUE)
     */
    private $totalCashEnvelopes;

    /**
     * @var float
     * @ORM\Column(name="total_tr_envelopes", type="float", nullable= TRUE)
     */
    private $totalTrEnvelopes;

    /**
     * @return ChestCount
     */
    public function getChestCount()
    {
        return $this->chestCount;
    }

    /**
     * @param ChestCount $chestCount
     * @return ChestTirelire
     */
    public function setChestCount($chestCount)
    {
        $this->chestCount = $chestCount;

        return $this;
    }

    public function getInitialTheoricalAmount()
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $lastTirelire = $this->getChestCount()->getLastChestCount()->getTirelire();
            $total = $lastTirelire->calculateRealTotal();
        }

        return $total;
    }

    public function calculateTotalCashNotVersedEnveloppes($restaurant)
    {
        $totalCashEnvelopes = 0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $totalCashEnvelopes += $this->getChestCount()->getLastChestCount()->getTirelire()->getTotalCashEnvelopes();

/*if($restaurant->getId()==116){
               echo 'valeur ini '.  $totalCashEnvelopes;         
   }*/

        }




        // Envelopes cash; comptage caisse, withdrawals, change fund, small chest pour le restaurant courant
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [
                    Envelope::CASHBOX_COUNTS,
                    Envelope::WITHDRAWAL,
                    Envelope::EXCHANGE_FUNDS,
                    Envelope::SMALL_CHEST,
                    Envelope::CASHBOX_FUNDS,
                ],
                "restaurant" => $restaurant,
            ],
            null
        );

        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $totalCashEnvelopes += $envelope->getAmount();
 /*if($restaurant->getId()==116){
               echo $envelope->getId().' env '.$envelope->getAmount() .' ';
            }*/
        }



        $envelopeCashDeposits = $this->getChestCount()->getDeposits(['type' => Deposit::TYPE_CASH,"restaurant" => $restaurant]);
        foreach ($envelopeCashDeposits as $deposit) {
            /**
             * @var Deposit $deposit
             */
            $totalCashEnvelopes -= abs($deposit->getTotalAmount());
 /*if($restaurant->getId()==116){
                echo $deposit->getId().' dep '.$deposit->getTotalAmount() .' ';
            }*/
        }



        $this->totalCashEnvelopes = $totalCashEnvelopes;

        return $totalCashEnvelopes;
    }

    public function calculateTheoricalTotalCashNotVersedEnveloppes($restaurant)
    {
        return $this->calculateTotalCashNotVersedEnveloppes($restaurant);
    }

    public function calculateTotalTrNotVersedEnveloppes($restaurant, $idPayment = null)
    {
        $totalTrEnvelopes = 0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $totalTrEnvelopes += $this->getChestCount()->getLastChestCount()->getTirelire()->getTotalTrEnvelopes();
        }


        // Envelopes TR
        $tRenvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_TICKET,
                "source" => [Envelope::SMALL_CHEST],
                "restaurant" => $restaurant,
            ],
            null
        );


        foreach ($tRenvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            if (is_null($idPayment) || ($idPayment === $envelope->getSousType())) {
                $totalTrEnvelopes += $envelope->getAmount();
            }
        }



        $envelopeTrDeposits = $this->getChestCount()->getDeposits(['type' => Deposit::TYPE_TICKET,"restaurant" => $restaurant]);


        foreach ($envelopeTrDeposits as $deposit) {
            /**
             * @var Deposit $deposit
             */
            $totalTrEnvelopes -= abs($deposit->getTotalAmount());
        }



        $this->totalTrEnvelopes = $totalTrEnvelopes;

        return $totalTrEnvelopes;
    }

    public function calculateTotalCashboxCashEnveloppes()
    {
        $totalCashEnvelopes = 0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $totalCashEnvelopes += $this->getChestCount()->getLastChestCount()->getTirelire(
            )->calculateTotalCashboxCashEnveloppes();
        }
        // Envelopes cash; comptage caise, withdrawals, change fund, small chest
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [Envelope::CASHBOX_COUNTS],
            ],
            Envelope::NOT_VERSED
        );
        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $totalCashEnvelopes += $envelope->getAmount();
        }

        return $totalCashEnvelopes;
    }

    public function calculateTotalWithdrawalCashEnveloppes()
    {
        $totalCashEnvelopes = 0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $totalCashEnvelopes += $this->getChestCount()->getLastChestCount()->getTirelire(
            )->calculateTotalWithdrawalCashEnveloppes();
        }
        // Envelopes cash; comptage caise, withdrawals, change fund, small chest
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [Envelope::WITHDRAWAL],
            ],
            Envelope::NOT_VERSED
        );
        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $totalCashEnvelopes += $envelope->getAmount();
        }

        return $totalCashEnvelopes;
    }

    public function calculateTotalExchangeFundCashEnveloppes()
    {
        $totalCashEnvelopes = 0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $totalCashEnvelopes += $this->getChestCount()->getLastChestCount()->getTirelire(
            )->calculateTotalExchangeFundCashEnveloppes();
        }
        // Envelopes cash; comptage caise, withdrawals, change fund, small chest
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [Envelope::EXCHANGE_FUNDS],
            ],
            Envelope::NOT_VERSED
        );
        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $totalCashEnvelopes += $envelope->getAmount();
        }

        return $totalCashEnvelopes;
    }

    public function calculateTotalSmallChestCashEnveloppes()
    {
        $totalCashEnvelopes = 0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $totalCashEnvelopes += $this->getChestCount()->getLastChestCount()->getTirelire(
            )->calculateTotalSmallChestCashEnveloppes();
        }
        // Envelopes cash; comptage caise, withdrawals, change fund, small chest
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [Envelope::SMALL_CHEST],
            ],
            Envelope::NOT_VERSED
        );
        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $totalCashEnvelopes += $envelope->getAmount();
        }

        return $totalCashEnvelopes;
    }

    /**
     * @return float
     */
    public function calculateRealTotal($restaurant)
    {
        $this->realTotal = $this->calculateTheoricalTotal($restaurant);

        return $this->realTotal;
    }

    /**
     * @return float
     */
    public function calculateTheoricalTotal(Restaurant $restaurant)
    {
        $total = 0.0;
        $total += $this->calculateTotalCashNotVersedEnveloppes($restaurant);
        $total += $this->calculateTotalTrNotVersedEnveloppes($restaurant);

        $this->theoricalTotal = $total;

        return $total;
    }

    // Gap calculation
    public function calculateGapCash()
    {
        return 0;
    }

    public function calculateGapTr()
    {
        return 0;
    }

    public function calculateGap($restaurant)
    {
        $this->gap = $this->calculateRealTotal($restaurant) - $this->calculateTheoricalTotal($restaurant);

        return $this->gap;
    }

    public function getNotVersedTrIdpayments()
    {
        $result = [];
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $result = array_merge(
                $result,
                $this->getChestCount()->getLastChestCount()->getTirelire()->getNotVersedTrIdpayments()
            );
        }
        $tRenvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_TICKET,
                "source" => [Envelope::SMALL_CHEST],
            ],
            Envelope::NOT_VERSED
        );

        foreach ($tRenvelopes as $envelopes) {
            /**
             * @var Envelope $envelopes
             */
            $result[] = $envelopes->getSousType();
        }
        $result = array_unique($result);

        return $result;
    }

    /**
     * @return float
     */
    public function getRealTotal()
    {
        return $this->realTotal;
    }

    /**
     * @param float $realTotal
     */
    public function setRealTotal($realTotal)
    {
        $this->realTotal = $realTotal;
    }

    /**
     * @return float
     */
    public function getTheoricalTotal()
    {
        return $this->theoricalTotal;
    }

    /**
     * @param float $theoricalTotal
     */
    public function setTheoricalTotal($theoricalTotal)
    {
        $this->theoricalTotal = $theoricalTotal;
    }

    /**
     * @return float
     */
    public function getGap()
    {
        return $this->gap;
    }

    /**
     * @param float $gap
     */
    public function setGap($gap)
    {
        $this->gap = $gap;
    }

    /**
     * @return float
     */
    public function getTotalCashEnvelopes()
    {
        return $this->totalCashEnvelopes;
    }

    /**
     * @param float $totalCashEnvelopes
     */
    public function setTotalCashEnvelopes($totalCashEnvelopes)
    {
        $this->totalCashEnvelopes = $totalCashEnvelopes;
    }

    /**
     * @return float
     */
    public function getTotalTrEnvelopes()
    {
        return $this->totalTrEnvelopes;
    }

    /**
     * @param float $totalTrEnvelopes
     */
    public function setTotalTrEnvelopes($totalTrEnvelopes)
    {
        $this->totalTrEnvelopes = $totalTrEnvelopes;
    }
}
