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
 * ChestCashboxFund
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class ChestCashboxFund implements CompartmentCalculationInterface
{

    public function __construct()
    {
    }

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var ChestCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="cashboxFund")
     */
    private $chestCount;

    /**
     * @var integer
     * @ORM\Column(name="nbr_of_cashboxes", type="integer", nullable=TRUE)
     */
    private $nbrOfCashboxes;

    /**
     * @var float
     * @ORM\Column(name="initial_cashbox_funds", type="float", nullable=TRUE)
     */
    private $initialCashboxFunds;

    /**
     * @var integer
     * @ORM\Column(name="nbr_of_cashboxes_th", type="integer")
     */
    private $theoricalNbrOfCashboxes;

    /**
     * @var float
     * @ORM\Column(name="initial_cashbox_funds_th", type="float")
     */
    private $theoricalInitialCashboxFunds;

    /**
     * @return ChestCount
     */
    public function getChestCount()
    {
        return $this->chestCount;
    }

    /**
     * @param ChestCount $chestCount
     * @return ChestCashboxFund
     */
    public function setChestCount($chestCount)
    {
        $this->chestCount = $chestCount;

        return $this;
    }


    /**
     * Set nbrOfCashboxes
     *
     * @param integer $nbrOfCashboxes
     *
     * @return ChestCashboxFund
     */
    public function setNbrOfCashboxes($nbrOfCashboxes)
    {
        $this->nbrOfCashboxes = $nbrOfCashboxes;

        return $this;
    }

    /**
     * Get nbrOfCashboxes
     *
     * @return integer
     */
    public function getNbrOfCashboxes()
    {
        return $this->nbrOfCashboxes;
    }

    /**
     * @return int
     */
    public function getTheoricalNbrOfCashboxes()
    {
        return $this->theoricalNbrOfCashboxes;
    }

    /**
     * @param int $theoricalNbrOfCashboxes
     * @return ChestCashboxFund
     */
    public function setTheoricalNbrOfCashboxes($theoricalNbrOfCashboxes)
    {
        $this->theoricalNbrOfCashboxes = $theoricalNbrOfCashboxes;

        return $this;
    }

    /**
     * @return float
     */
    public function getTheoricalInitialCashboxFunds()
    {
        return $this->theoricalInitialCashboxFunds;
    }

    /**
     * @param float $theoricalInitialCashboxFunds
     * @return ChestCashboxFund
     */
    public function setTheoricalInitialCashboxFunds($theoricalInitialCashboxFunds)
    {
        $this->theoricalInitialCashboxFunds = $theoricalInitialCashboxFunds;

        return $this;
    }

    /**
     * Set initialCashboxFunds
     *
     * @param  float $initialCashboxFunds
     * @return ChestCashboxFund
     */
    public function setInitialCashboxFunds($initialCashboxFunds)
    {
        if (is_string($initialCashboxFunds)) {
            $initialCashboxFunds = str_replace(',', '.', $initialCashboxFunds);
        }
        $this->initialCashboxFunds = $initialCashboxFunds;

        return $this;
    }

    /**
     * Get initialCashboxFunds
     *
     * @return float
     */
    public function getInitialCashboxFunds()
    {
        return $this->initialCashboxFunds;
    }

    public function getInitialTheoricalAmount()
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $cashboxFund = $this->getChestCount()->getLastChestCount()->getCashboxFund();
            $total = $cashboxFund->calculateRealTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateRealTotal($restaurant = null)
    {
        return $this->getNbrOfCashboxes() * $this->getInitialCashboxFunds();
    }

    /**
     * @return float
     */
    public function calculateTheoricalTotal(Restaurant $restaurant = null)
    {
        $total = $this->getTheoricalNbrOfCashboxes() * $this->getTheoricalInitialCashboxFunds();

        return $total;
    }

    public function calculateGap($restaurant = null)
    {
        return $this->calculateRealTotal() - $this->calculateTheoricalTotal();
    }
}
