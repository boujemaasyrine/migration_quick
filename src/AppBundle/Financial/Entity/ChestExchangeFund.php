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
use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * ChestExchangeFund
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class ChestExchangeFund implements CompartmentCalculationInterface
{

    public function __construct()
    {
        $this->chestExchanges = new ArrayCollection();
    }

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var ChestCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="exchangeFund")
     */
    private $chestCount;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\ChestExchange", mappedBy="chestExchangeFund", cascade={"persist"})
     */
    private $chestExchanges;

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
     * @return ChestCount
     */
    public function getChestCount()
    {
        return $this->chestCount;
    }

    /**
     * @param ChestCount $chestCount
     * @return ChestExchangeFund
     */
    public function setChestCount($chestCount)
    {
        $this->chestCount = $chestCount;

        return $this;
    }

    /**
     * Add chestExchange
     *
     * @param \AppBundle\Financial\Entity\ChestExchange $chestExchange
     *
     * @return ChestExchangeFund
     */
    public function addChestExchange(\AppBundle\Financial\Entity\ChestExchange $chestExchange)
    {
        $chestExchange->setChestExchangeFund($this);
        $this->chestExchanges[] = $chestExchange;

        return $this;
    }

    /**
     * Remove chestExchange
     *
     * @param \AppBundle\Financial\Entity\ChestExchange $chestExchange
     */
    public function removeChestExchange(\AppBundle\Financial\Entity\ChestExchange $chestExchange)
    {
        $this->chestExchanges->removeElement($chestExchange);
    }

    /**
     * Get chestExchanges
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChestExchanges()
    {
        return $this->chestExchanges;
    }

    public function getInitialTheoricalAmount()
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            if (!is_null($this->getChestCount()->getLastChestCount()->getExchangeFund())){
                $exchangeFund = $this->getChestCount()->getLastChestCount()->getExchangeFund();
                $total += $exchangeFund->calculateRealTotal();
            } 
        }
        return $total;
    }

    /**
     * @return float
     */
    public function calculateRealTotal($restaurant = null)
    {
        $total = 0;
        foreach ($this->getChestExchanges() as $chestExchange) {
            /**
             * @var ChestExchange $chestExchange
             */
            $total += $chestExchange->calculateTotal();
        }
        $this->setRealTotal($total);

        return $total;
    }

    /**
     * @return float
     */
    public function calculateTheoricalTotal(Restaurant $restaurant)
    {
        $total = $this->getInitialTheoricalAmount();
        // Recipe Tickets = reception maonaie
        $recipeTickets = $this->getChestCount()->getRecipeTickets(
            ['label' => [RecipeTicket::CHANGE_RECIPE], 'restaurant' => $restaurant]
        );
        $totalRecipeTickets = 0;
        foreach ($recipeTickets as $recipeTicket) {
            /**
             * @var RecipeTicket $recipeTicket
             */
            $totalRecipeTickets += $recipeTicket->getAmount();
        }
        $total += $totalRecipeTickets;

        // Envelopes cash; source = fond de change
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [Envelope::EXCHANGE_FUNDS],
                "restaurant" => $restaurant,
            ]
        );
        $totalCashEnvelopes = 0;
        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $totalCashEnvelopes += $envelope->getAmount();
        }
        $total -= $totalCashEnvelopes;

        $this->setTheoricalTotal($total);

        return $total;
    }

    public function calculateGap($restaurant = null)
    {
        return $this->calculateRealTotal() - $this->calculateTheoricalTotal($restaurant);
    }

    public function getGap()
    {
        return $this->getRealTotal() - $this->getTheoricalTotal();
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
}
