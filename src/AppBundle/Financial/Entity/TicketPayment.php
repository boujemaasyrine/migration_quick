<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TicketPayment
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\TicketPaymentRepository")
 */
class TicketPayment
{
    use ImportIdTrait;

    const PAYMENT_TYPE = 'payment';
    const DISCOUNT_TYPE = 'discount';
    //Ticket restaurants Type
    const CHECK_RESTO_LUX_QUICK = 132;
    const CHECK_RESTO_LUX_BK = 500;
    const SOD_PASS_QUICK= 120;
    const SOD_PASS_BK=300;
    const CHECK_QUICK_5=131;
    const VOUCHER_QUICK=133;
    const CHECK_BK_5=550;
    const CHECK_KING_50=551;
    const LOC_CHEQUE=552;
    const RADIO_C50=553;
    const CHECK_BK=[self::CHECK_BK_5,self::CHECK_KING_50,self::LOC_CHEQUE,self::RADIO_C50];
    const CHECK_QUICK=[self::CHECK_QUICK_5,self::VOUCHER_QUICK];

    // Payment Methods type
    const REAL_CASH = 1;
    const CHECK_RESTAURANT_QUICK = 130;
    const CHECK_RESTAURANT_BK= 400;
    const MEAL_TICKET = 5;
    const BANK_CARD = 2;
    const EDENRED = 108;
    const E_SODEXO = 109;
    const PAYFAIR = 110;
    const CONSODEX = 451;
    const CONSEDENR = 450;

    public static $ticketRestaurantsQuick = [
        self::CHECK_RESTAURANT_QUICK,
        self::CHECK_RESTO_LUX_QUICK,
        self::SOD_PASS_QUICK,
        self::EDENRED,
        self::E_SODEXO,
        self::PAYFAIR,
        self::CONSEDENR,
        self::CONSODEX
    ];

    public static $ticketRestaurantsBK = [
        self::CHECK_RESTAURANT_BK,
        self::CHECK_RESTO_LUX_BK,
        self::SOD_PASS_BK,
        self::EDENRED,
        self::E_SODEXO,
        self::PAYFAIR,
        self::CONSEDENR,
        self::CONSODEX
    ];

    const VPAY = 106;

    const LOCAL = 64;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer",nullable=true)
     */
    private $num;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=50,nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="id_payment", type="string", length=50,nullable=true)
     */
    private $idPayment;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50,nullable=true)
     */
    private $code;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float",nullable=true)
     */
    private $amount;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=30,nullable=true)
     */
    private $type;

    // Meal ticket fields
    /**
     * @var string
     * @ORM\Column(name="operator",type="string",length=30,nullable=true)
     */
    private $operator;

    /**
     * @var string
     * @ORM\Column(name="first_name",type="string",length=50,nullable=true)
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(name="last_name",type="string",length=30,nullable=true)
     */
    private $lastName;

    /**
     * @var boolean
     * @ORM\Column(name="electronic",type="boolean",nullable=true, options={"default" : false})
     */
    private $electronic = false;

    /**
     * @var Ticket
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\Ticket",inversedBy="payments")
     */
    private $ticket;

    /**
     * @var CashboxRealCashContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxRealCashContainer",inversedBy="ticketPayments")
     */
    private $realCashContainer;

    /**
     * @var CashboxCheckRestaurantContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxCheckRestaurantContainer",inversedBy="ticketPayments")
     */
    private $checkRestaurantContainer;

    /**
     * @var CashboxBankCardContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxBankCardContainer", inversedBy="ticketPayments")
     */
    private $bankCardContainer;

    /**
     * @var CashboxCheckQuickContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxCheckQuickContainer", inversedBy="ticketPayments")
     */
    private $checkQuickContainer;

    /**
     * @var CashboxMealTicketContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxMealTicketContainer", inversedBy="ticketPayments")
     */
    private $mealTicketContainer;

    /**
     * @var CashboxForeignCurrencyContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxForeignCurrencyContainer", inversedBy="ticketPayments")
     */
    private $foreignCurrencyContainer;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return TicketPayment
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return TicketPayment
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return TicketPayment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set ticket
     *
     * @param \AppBundle\Financial\Entity\Ticket $ticket
     *
     * @return TicketPayment
     */
    public function setTicket(\AppBundle\Financial\Entity\Ticket $ticket = null)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return \AppBundle\Financial\Entity\Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return TicketPayment
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return CashboxRealCashContainer
     */
    public function getRealCashContainer()
    {
        return $this->realCashContainer;
    }

    /**
     * @param CashboxRealCashContainer $realCashContainer
     * @return TicketPayment
     */
    public function setRealCashContainer($realCashContainer)
    {
        $this->getTicket()->setCounted(true);
        $this->realCashContainer = $realCashContainer;

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
     * @return TicketPayment
     */
    public function setCheckRestaurantContainer(CashboxCheckRestaurantContainer $checkRestaurantContainer)
    {
        $this->getTicket()->setCounted(true);
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
     * @return TicketPayment
     */
    public function setBankCardContainer($bankCardContainer)
    {
        $this->getTicket()->setCounted(true);
        $this->bankCardContainer = $bankCardContainer;

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
     * @return TicketPayment
     */
    public function setMealTicketContainer($mealTicketContainer)
    {
        $this->getTicket()->setCounted(true);
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
     * @return TicketPayment
     */
    public function setForeignCurrencyContainer($foreignCurrencyContainer)
    {
        $this->getTicket()->setCounted(true);
        $this->foreignCurrencyContainer = $foreignCurrencyContainer;

        return $this;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     * @return TicketPayment
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return TicketPayment
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return TicketPayment
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

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
     * @return TicketPayment
     */
    public function setCheckQuickContainer($checkQuickContainer)
    {
        $this->getTicket()->setCounted(true);
        $this->checkQuickContainer = $checkQuickContainer;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdPayment()
    {
        return $this->idPayment;
    }

    /**
     * @param string $idPayment
     * @return TicketPayment
     */
    public function setIdPayment($idPayment)
    {
        $this->idPayment = $idPayment;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isElectronic()
    {
        return $this->electronic;
    }

    /**
     * @param boolean $electronic
     * @return TicketPayment
     */
    public function setElectronic($electronic)
    {
        $this->electronic = $electronic;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return TicketPayment
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }
}
