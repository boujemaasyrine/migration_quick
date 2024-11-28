<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:53
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * CashboxCount
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\CashboxCountRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class CashboxCount
{

    use SynchronizedFlagTrait;
    use TimestampableTrait;
    use IdTrait;
    use OriginRestaurantTrait; // this Trait add $originRestaurant attribut ManyToOne

    use ImportIdTrait;

    /**
     * CashboxCount constructor.
     */
    public function __construct()
    {
        $this->cashContainer = new CashboxRealCashContainer();
        $this->cashContainer->setCashbox($this);

        $this->checkRestaurantContainer = new CashboxCheckRestaurantContainer();
        $this->checkRestaurantContainer->setCashbox($this);

        $this->bankCardContainer = new CashboxBankCardContainer();
        $this->bankCardContainer->setCashbox($this);

        $this->checkQuickContainer = new CashboxCheckQuickContainer();
        $this->checkQuickContainer->setCashbox($this);

        $this->discountContainer = new CashboxDiscountContainer();
        $this->discountContainer->setCashbox($this);

        $this->mealTicketContainer = new CashboxMealTicketContainer();
        $this->mealTicketContainer->setCashbox($this);

        $this->foreignCurrencyContainer = new CashboxForeignCurrencyContainer();
        $this->foreignCurrencyContainer->setCashbox($this);

        $this->abondonedTickets = new ArrayCollection();

        $this->withdrawals = new ArrayCollection();
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $owner;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $cashier;

    /**
     * @var float
     *
     * @ORM\Column(name="real_ca_counted", type="float", nullable= TRUE)
     */
    private $realCaCounted;

    /**
     * @var float
     *
     * @ORM\Column(name="theorical_ca", type="float", nullable= TRUE)
     */
    private $theoricalCa;

    /**
     * @var int
     *
     * @ORM\Column(name="number_cancels", type="integer", nullable= TRUE)
     */
    private $numberCancels;

    /**
     * @var float
     *
     * @ORM\Column(name="total_cancels", type="float", nullable= TRUE)
     */
    private $totalCancels;

    /**
     * @var int
     *
     * @ORM\Column(name="number_corrections", type="integer", nullable= TRUE)
     */
    private $numberCorrections;

    /**
     * @var float
     *
     * @ORM\Column(name="total_corrections", type="float", nullable= TRUE)
     */
    private $totalCorrections;

    /**
     * @var int
     *
     * @ORM\Column(name="number_abondons", type="integer", nullable= TRUE)
     */
    private $numberAbondons;

    /**
     * @var float
     *
     * @ORM\Column(name="total_abondons", type="float", nullable= TRUE)
     */
    private $totalAbondons;

    /**
     * @var bool
     *
     * @ORM\Column(name="eft", type="boolean", nullable= TRUE)
     */
    private $eft;

    /**
     * @var bool
     *
     * @ORM\Column(name="counted", type="boolean", nullable= TRUE)
     */
    private $counted = false;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Financial\Entity\Ticket", mappedBy="cashboxCount")
     */
    private $abondonedTickets;

    /**
     * @var ChestSmallChest
     *
     * @ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestSmallChest", inversedBy="cashboxCounts")
     */
    private $smallChest;

    /**
     * @var CashboxRealCashContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxRealCashContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $cashContainer;

    /**
     * @var CashboxCheckQuickContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCheckQuickContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $checkQuickContainer;

    /**
     * @var CashboxCheckRestaurantContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCheckRestaurantContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $checkRestaurantContainer;

    /**
     * @var CashboxBankCardContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxBankCardContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $bankCardContainer;

    /**
     * @var CashboxDiscountContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxDiscountContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $discountContainer;

    /**
     * @var CashboxMealTicketContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxMealTicketContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $mealTicketContainer;

    /**
     * @var CashboxForeignCurrencyContainer
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxForeignCurrencyContainer", mappedBy="cashbox", cascade={"persist","remove"})
     */
    private $foreignCurrencyContainer;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\Withdrawal", mappedBy="cashboxCount")
     */
    private $withdrawals;

    /**
     * @param null $format
     *
     * @return \DateTime
     */
    public function getDate($format = null)
    {
        if (!is_null($format)) {
            return $this->date->format($format);
        }

        return $this->date;
    }

    /**
     * @param \DateTime $date
     *
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Employee
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Employee $owner
     *
     * @return self
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Employee
     */
    public function getCashier()
    {
        return $this->cashier;
    }

    /**
     * @param Employee $cashier
     *
     * @return self
     */
    public function setCashier($cashier)
    {
        $this->cashier = $cashier;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCounted()
    {
        return $this->counted;
    }

    /**
     * @param bool $counted
     *
     * @return CashboxCount
     */
    public function setCounted($counted)
    {
        $this->counted = $counted;

        return $this;
    }

    /**
     * @return CashboxRealCashContainer
     */
    public function getCashContainer()
    {
        return $this->cashContainer;
    }

    /**
     * @param CashboxRealCashContainer $cashContainer
     *
     * @return CashboxCount
     */
    public function setCashContainer($cashContainer)
    {
        $cashContainer->setCashbox($this);
        $this->cashContainer = $cashContainer;

        return $this;
    }

    /**
     * @return CashboxCheckRestaurantContainer
     */
    public function getCheckRestaurantContainer()
    {
        return $this->checkRestaurantContainer;
    }

    /**
     * @param CashboxCheckRestaurantContainer $checkRestaurantContainer
     *
     * @return CashboxCount
     */
    public function setCheckRestaurantContainer(
        CashboxCheckRestaurantContainer $checkRestaurantContainer
    )
    {
        $this->checkRestaurantContainer = $checkRestaurantContainer;

        return $this;
    }

    /**
     * @return CashboxBankCardContainer
     */
    public function getBankCardContainer()
    {
        return $this->bankCardContainer;
    }

    /**
     * @param CashboxBankCardContainer $bankCardContainer
     *
     * @return CashboxCount
     */
    public function setBankCardContainer(
        CashboxBankCardContainer $bankCardContainer
    )
    {
        $this->bankCardContainer = $bankCardContainer;

        return $this;
    }

    /**
     * @return CashboxCheckQuickContainer
     */
    public function getCheckQuickContainer()
    {
        return $this->checkQuickContainer;
    }

    /**
     * @param CashboxCheckQuickContainer $checkQuickContainer
     *
     * @return CashboxCount
     */
    public function setCheckQuickContainer($checkQuickContainer)
    {
        $this->checkQuickContainer = $checkQuickContainer;

        return $this;
    }

    /**
     * @return CashboxDiscountContainer
     */
    public function getDiscountContainer()
    {
        return $this->discountContainer;
    }

    /**
     * @param CashboxDiscountContainer $discountContainer
     *
     * @return CashboxCount
     */
    public function setDiscountContainer($discountContainer)
    {
        $this->discountContainer = $discountContainer;

        return $this;
    }

    /**
     * @return CashboxMealTicketContainer
     */
    public function getMealTicketContainer()
    {
        return $this->mealTicketContainer;
    }

    /**
     * @param CashboxMealTicketContainer $mealTicketContainer
     *
     * @return CashboxCount
     */
    public function setMealTicketContainer($mealTicketContainer)
    {
        $this->mealTicketContainer = $mealTicketContainer;

        return $this;
    }

    /**
     * @return CashboxForeignCurrencyContainer
     */
    public function getForeignCurrencyContainer()
    {
        return $this->foreignCurrencyContainer;
    }

    /**
     * @param CashboxForeignCurrencyContainer $foreignCurrencyContainer
     *
     * @return CashboxCount
     */
    public function setForeignCurrencyContainer($foreignCurrencyContainer)
    {
        $this->foreignCurrencyContainer = $foreignCurrencyContainer;

        return $this;
    }

    /**
     * @return float
     */
    public function getRealCaCounted()
    {
        return $this->realCaCounted;
    }

    /**
     * @param float $realCaCounted
     *
     * @return CashboxCount
     */
    public function setRealCaCounted($realCaCounted)
    {
        $this->realCaCounted = $realCaCounted;

        return $this;
    }

    /**
     * ‘real_ca_counted’ = total éspece réel +
     *                    total carte bancaire +
     *                    total monnaie étrangere(convertit en euro) +
     *                    total ticket restaurant (electronique et papier) +
     *                    total check quick +
     *                    total bons repas +
     *                    total discounts
     *
     * @return float|int
     */
    public function calculateRealCaCounted()
    {
        $total = 0;
        $total += $this->getCashContainer()->getTotal();
        $total += $this->calculateTotalWithdrawals();
        $total += $this->getBankCardContainer()->calculateBankCardTotal();
        $total += $this->getForeignCurrencyContainer()
            ->calculateTotalForeignCurrencyAmount();
        $total += $this->getCheckRestaurantContainer()
            ->calculateRealTotalAmount();
        $total += $this->getCheckQuickContainer()->calculateCheckQuickTotal();

        $total += abs($this->getDiscountContainer()->calculateTheoricalTotal());
        $total += abs(
            $this->getMealTicketContainer()->calculateTheoricalTotal()
        );

        $this->realCaCounted = $total;

        return $total;
    }

    /**
     * @return ArrayCollection
     */
    public function getAbondonedTickets()
    {
        return $this->abondonedTickets;
    }

    /**
     * @param ArrayCollection $abondonedTickets
     *
     * @return CashboxCount
     */
    public function setAbondonedTickets($abondonedTickets)
    {
        foreach ($abondonedTickets as $abondonedTicket) {
            $abondonedTicket->setCashboxCount($this);
        }
        $this->abondonedTickets = $abondonedTickets;

        return $this;
    }

    /**
     * @param Ticket $abondonedTicket
     *
     * @return self
     */
    public function addAbondonedTicket(Ticket $abondonedTicket)
    {
        $abondonedTicket->setCashboxCount($this);
        $this->abondonedTickets->add($abondonedTicket);

        return $this;
    }

    /**
     * @param Ticket $abondonedTicket
     */
    public function removeAbondonedTicket(Ticket $abondonedTicket)
    {
        $this->abondonedTickets->removeElement($abondonedTicket);
    }

    /**
     * @return float
     */
    public function getTheoricalCa()
    {
        $this->setTheoricalCa($this->calculateTheoricalTotalCashbox());

        return $this->theoricalCa;
    }

    /**
     * @param float $theoricalCa
     *
     * @return CashboxCount
     */
    public function setTheoricalCa($theoricalCa)
    {
        $this->theoricalCa = $theoricalCa;

        return $this;
    }

    /**
     * ‘theorical_ca’ = total paiement éspece +
     *                 total paiement chèques quick +
     *                 total paiement carte bancaire +
     *                 total d'argent étranger (saisie et converti en euro) +
     *                 total paiement titres restaurant (papier et electronique) +
     *                 total discount +
     *                 total bon repas
     *
     * @return float|int
     */
    public function calculateTheoricalCa()
    {
        $total = 0;
        $total += $this->getCashContainer()->calculateTheoricalTotal();
        $total += $this->getCheckQuickContainer()->calculateTheoricalTotal();
        $total += $this->getBankCardContainer()->calculateTheoricalTotal();
        $total += $this->getForeignCurrencyContainer()->calculateTheoricalTotal();
        $total += $this->getCheckRestaurantContainer()->calculateTheoricalTotal();
        $total += abs($this->getDiscountContainer()->getTotalAmount());
        $total += abs($this->getMealTicketContainer()->getTotalAmount());

        return $total;
    }

    /**
     * @return ArrayCollection
     */
    public function getWithdrawals()
    {
        return $this->withdrawals;
    }

    /**
     * @param ArrayCollection $withdrawals
     *
     * @return CashboxCount
     */
    public function setWithdrawals($withdrawals)
    {
        foreach ($withdrawals as $withdrawal) {
            /**
             * @var Withdrawal $withdrawal
             */
            $withdrawal->setCashboxCount($this);
        }
        $this->withdrawals = $withdrawals;

        return $this;
    }

    /**
     * @param Withdrawal $withdrawal
     *
     * @return self
     */
    public function addWithdrawal(Withdrawal $withdrawal)
    {
        $withdrawal->setCashboxCount($this);
        $this->withdrawals->add($withdrawal);

        return $this;
    }

    /**
     * @param Withdrawal $withdrawal
     */
    public function removeWithdrawal(Withdrawal $withdrawal)
    {
        $this->withdrawals->removeElement($withdrawal);
    }

    /**
     * @return int
     */
    public function getNumberCancels()
    {
        return $this->numberCancels;
    }

    /**
     * @param int $numberCancels
     *
     * @return CashboxCount
     */
    public function setNumberCancels($numberCancels)
    {
        $this->numberCancels = $numberCancels;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalCancels()
    {
        return $this->totalCancels;
    }

    /**
     * @param float $totalCancels
     *
     * @return CashboxCount
     */
    public function setTotalCancels($totalCancels)
    {
        $this->totalCancels = $totalCancels;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberCorrections()
    {
        return $this->numberCorrections;
    }

    /**
     * @param int $numberCorrections
     *
     * @return CashboxCount
     */
    public function setNumberCorrections($numberCorrections)
    {
        $this->numberCorrections = $numberCorrections;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalCorrections()
    {
        return $this->totalCorrections;
    }

    /**
     * @param float $totalCorrections
     *
     * @return CashboxCount
     */
    public function setTotalCorrections($totalCorrections)
    {
        $this->totalCorrections = $totalCorrections;

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberAbondons()
    {
        return $this->numberAbondons;
    }

    /**
     * @param int $numberAbondons
     *
     * @return CashboxCount
     */
    public function setNumberAbondons($numberAbondons)
    {
        $this->numberAbondons = $numberAbondons;

        return $this;
    }

    /**
     * @return float
     */
    public function getTotalAbondons()
    {
        return $this->totalAbondons;
    }

    /**
     * @param float $totalAbondons
     *
     * @return CashboxCount
     */
    public function setTotalAbondons($totalAbondons)
    {
        $this->totalAbondons = $totalAbondons;

        return $this;
    }

    /**
     * @return ChestCount
     */
    public function getSmallChest()
    {
        return $this->smallChest;
    }

    /**
     * @param ChestSmallChest $smallChest
     *
     * @return CashboxCount
     */
    public function setSmallChest($smallChest)
    {
        $this->setCounted(true);
        $this->smallChest = $smallChest;

        return $this;
    }

    //
    // Calculation methods
    //


    /**
     * @return float|int
     */
    public function calculateTotalWithdrawals()
    {
        $total = 0;
        foreach ($this->getWithdrawals() as $withdrawal) {
            /**
             * @var Withdrawal $withdrawal
             */
            $total += abs($withdrawal->getAmountWithdrawal());
        }

        return $total;
    }

    /**
     * @return float|int
     */
    public function calculateWithdrawalsGap()
    {
        return $this->calculateTotalWithdrawals()
            - $this->calculateTotalWithdrawals();
    }

    /**
     * @return float|int
     */
    public function calculateCashGap()
    {
        return $this->getCashContainer()->getTotal()
            - $this->getTheoricalCashTotal();
    }

    /**
     * @return float|int
     */
    public function getTheoricalCashTotal()
    {
        return $this->getCashContainer()->calculateTheoricalTotal()
            - $this->calculateTotalWithdrawals();
    }

    /**
     * Réel compté = total éspece réel +
     *               total prélèvement +
     *               total carte bancaire +
     *               total monnaie étrangere(convertit en euro) +
     *               total ticket restaurant (electronique et papier) +
     *               total check quick
     *
     * @return float|int
     */
    public function calculateTotalCashbox()
    {
        $total = 0;
        $total += $this->getCashContainer()->getTotal();
        $total += $this->calculateTotalWithdrawals();
        $total += $this->getBankCardContainer()->calculateBankCardTotal();
        $total += $this->getForeignCurrencyContainer()
            ->calculateTotalForeignCurrencyAmount();
        $total += $this->getCheckRestaurantContainer()
            ->calculateRealTotalAmount(false);
        $total += $this->getCheckRestaurantContainer()
            ->calculateRealTotalAmount(true);
        $total += $this->getCheckQuickContainer()->calculateCheckQuickTotal();

        return $total;
    }

    /**
     * Montant theorique = total paiement éspece  +
     *                     total paiement chèques quick +
     *                     total paiement carte bancaire +
     *                     total d'argent étranger (saisie et converti en euro) +
     *                     total paiement titres restaurant (papier et electronique)
     *
     * @return float|int|number
     */
    public function calculateTheoricalTotalCashbox()
    {
        $total = 0;
        $total += $this->getCashContainer()->calculateTheoricalTotal();
        $total += $this->getCheckQuickContainer()->calculateTheoricalTotal();
        $total += $this->getBankCardContainer()->calculateTheoricalTotal();
        $total += $this->getForeignCurrencyContainer()->calculateTheoricalTotal();
        $total += $this->getCheckRestaurantContainer()->calculateTheoricalTotal();

        return $total;
    }

    /**
     * @return float|int|number
     */
    public function calculateCashboxGap()
    {
        return $this->calculateTotalCashbox()
            - $this->calculateTheoricalTotalCashbox();
    }

    /**
     * @deprecated
     *
     * @return float|int|number
     */
    public function calculateRealTotalCashbox()
    {
        $realTotalCashbox = $this->calculateTotalCashbox();
        $realTotalCashbox += $this->calculateTotalWithdrawals();
        $realTotalCashbox += -($this->getDiscountContainer()->getTotalAmount());
        $realTotalCashbox += -($this->getMealTicketContainer()->getTotalAmount());

        return $realTotalCashbox;
    }

    /**
     * @deprecated
     *
     * @return float|int|number
     */
    public function calculateTheoricalRealTotalCashbox()
    {
        $total = $this->calculateTheoricalTotalCashbox();
        $total += -($this->getDiscountContainer()->calculateTheoricalTotal());
        $total += -($this->getMealTicketContainer()->calculateTheoricalTotal());
        $total += $this->calculateTotalWithdrawals();

        return $total;
    }

    /**
     * @deprecated
     *
     * @return float|int|number
     */
    public function calculateRealTotalCashboxGap()
    {
        return $this->calculateRealTotalCashbox()
            - $this->calculateTheoricalRealTotalCashbox();
    }

    /**
     * @return boolean
     */
    public function isEft()
    {
        return $this->eft;
    }

    /**
     * @param boolean $eft
     *
     * @return CashboxCount
     */
    public function setEft($eft)
    {
        $this->eft = $eft;

        return $this;
    }

    /**
     * @return float|int|string
     */
    public function calculateTo()
    {
        $nbrTo = 0;
        $tickets = [];
        $tps = $this->getCashContainer()->getTicketPayments();
        foreach ($tps as $tp) {
            /**
             * @var TicketPayment $tp
             */
            $tickets[$tp->getTicket()->getId()] = $tp->getTicket();
        }
        $tps = $this->getCheckQuickContainer()->getTicketPayments();
        foreach ($tps as $tp) {
            /**
             * @var TicketPayment $tp
             */
            $tickets[$tp->getTicket()->getId()] = $tp->getTicket();
        }
        $tps = $this->getCheckRestaurantContainer()->getTicketPayments();
        foreach ($tps as $tp) {
            /**
             * @var TicketPayment $tp
             */
            $tickets[$tp->getTicket()->getId()] = $tp->getTicket();
        }
        $tps = $this->getBankCardContainer()->getTicketPayments();
        foreach ($tps as $tp) {
            /**
             * @var TicketPayment $tp
             */
            $tickets[$tp->getTicket()->getId()] = $tp->getTicket();
        }
        $lines = $this->getDiscountContainer()->getTicketLines();
        foreach ($lines as $line) {
            /**
             * @var TicketLine $line
             */
            $tickets[$line->getTicket()->getId()] = $line->getTicket();
        }
        $tps = $this->getForeignCurrencyContainer()->getTicketPayments();
        foreach ($tps as $tp) {
            /**
             * @var TicketPayment $tp
             */
            $tickets[$tp->getTicket()->getId()] = $tp->getTicket();
        }

        $tps = $this->getMealTicketContainer()->getTicketPayments();
        foreach ($tps as $tp) {
            /**
             * @var TicketPayment $tp
             */
            $tickets[$tp->getTicket()->getId()] = $tp->getTicket();
        }

        foreach ($tickets as $ticket) {
            /**
             * @var Ticket $ticket
             */
            if (strtoupper($ticket->getDestination()) === strtoupper(
                    'A emporter'
                )
            ) {
                $nbrTo++;
            }
        }
        if (count($tickets) === 0) {
            $result = '-';
        } else {
            $result = ($nbrTo / count($tickets)) * 100;
        }

        return $result;
    }

    /**
     * @return float
     */
    public function getGap()
    {
        return $this->realCaCounted - $this->theoricalCa;
    }

    /**
     * Calculer l'espèce des prélèvements qui n'ont pas été placés dans des enveloppes.
     * @return float|int
     */
    public function CalculateCashTotalOfWithdrawalsThatWereNotEnveloped()
    {
        $withdrawals = $this->getWithdrawals();
        $total = 0.0;
        if (is_object($withdrawals)) {
            foreach ($withdrawals as $w) {
                if (empty($w->getEnvelopeId())) {
                    $total += abs($w->getAmountWithdrawal());
                }
            }
        }
        return $total;
    }

}
