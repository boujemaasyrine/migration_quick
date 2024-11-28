<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ticket
 *
 * @ORM\Table(indexes={@ORM\Index(name="ticket_date_index",columns={"date"})},uniqueConstraints={@ORM\UniqueConstraint(name="ticket_unique", columns={"origin_restaurant_id","date","type","invoicenumber"})})
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\TicketRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Ticket
{

    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    const INVOICE = 'invoice';
    const ORDER = 'order';

    const CANCEL_STATUS_VALUE = -1;
    const ABONDON_STATUS_VALUE = 5;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="decimal")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=10,nullable=true)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cancelled_flag", type="boolean",nullable=true)
     */
    private $cancelledFlag;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="bigint",nullable=true)
     */
    private $num;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="datetime",nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="datetime",nullable=true)
     */
    private $endDate;

    /**
     * @var string
     *
     * @ORM\Column(name="invoiceNumber", type="string", length=50,nullable=true)
     */
    private $invoiceNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer",nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="invoiceCancelled", type="string",length=100,nullable=true)
     */
    private $invoiceCancelled;

    /**
     * @var float
     *
     * @ORM\Column(name="totalHT", type="float",nullable=true)
     */
    private $totalHT;

    /**
     * @var float
     *
     * @ORM\Column(name="totalTTC", type="float",nullable=true)
     */
    private $totalTTC;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid", type="boolean",nullable=true)
     */
    private $paid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deliveryTime", type="datetime", nullable=true)
     */
    private $deliveryTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator", type="integer", nullable=true)
     */
    private $operator;

    /**
     * @var string
     *
     * @ORM\Column(name="operatorName", type="string", length=50, nullable=true)
     */
    private $operatorName;

    /**
     * @var string
     *
     * @ORM\Column(name="responsible", type="string", length=50, nullable=true)
     */
    private $responsible;

    /**
     * @var integer
     *
     * @ORM\Column(name="workstation", type="integer", nullable=true)
     */
    private $workstation;

    /**
     * @var string
     *
     * @ORM\Column(name="workstationName", type="string", length=100, nullable=true)
     */
    private $workstationName;

    /**
     * @var integer
     *
     * @ORM\Column(name="originId", type="integer",nullable=true)
     */
    private $originId;

    /**
     * @var string
     *
     * @ORM\Column(name="origin", type="string", length=100, nullable=true)
     */
    private $origin;

    /**
     * @var integer
     *
     * @ORM\Column(name="destinationId", type="integer",nullable=true)
     */
    private $destinationId;

    /**
     * @var string
     *
     * @ORM\Column(name="destination", type="string", length=50,nullable=true)
     */
    private $destination;

    /**
     * @var integer
     *
     * @ORM\Column(name="entity", type="integer",nullable=true)
     */
    private $entity;

    /**
     * @var integer
     *
     * @ORM\Column(name="customer", type="integer",nullable=true)
     */
    private $customer;

    /**
     * @var \DateTime
     * @ORM\Column(name="date",type="date",nullable=true)
     */
    private $date;

    /**
     * @var TicketLine
     * @ORM\OneToMany(targetEntity="AppBundle\Financial\Entity\TicketLine",mappedBy="ticket",cascade={"persist", "remove"})
     */
    private $lines;

    /**
     * @var TicketPayment
     * @ORM\OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment",mappedBy="ticket",cascade={"persist", "remove"})
     */
    private $payments;

    /**
     * @var TicketIntervention
     * @ORM\OneToMany(targetEntity="TicketIntervention",mappedBy="ticket",cascade={"persist", "remove"})
     */
    private $interventions;

    /**
     * @var boolean
     * @ORM\Column(name="counted", type="boolean", options={"default" = false})
     */
    private $counted = false;


    /**
     * @var CashboxCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount",inversedBy="abondonedTickets")
     */
    private $cashboxCount;

    /**
     * @var string
     * @ORM\Column(name="external_id", type="string", nullable=TRUE)
     */
    private $externalId;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $countedCanceled = false;

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
     * @return Ticket
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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Ticket
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate($format = null)
    {
        if (!is_null($format) && !is_null($this->startDate)) {
            return $this->startDate->format($format);
        }

        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Ticket
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate($format = null)
    {
        if (!is_null($format) && !is_null($this->endDate)) {
            return $this->endDate->format($format);
        }

        return $this->endDate;
    }

    /**
     * Set invoiceNumber
     *
     * @param string $invoiceNumber
     *
     * @return Ticket
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    /**
     * Get invoiceNumber
     *
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Ticket
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set invoiceCancelled
     *
     * @param string $invoiceCancelled
     *
     * @return Ticket
     */
    public function setInvoiceCancelled($invoiceCancelled)
    {
        $this->invoiceCancelled = $invoiceCancelled;

        return $this;
    }

    /**
     * Get invoiceCancelled
     *
     * @return string
     */
    public function getInvoiceCancelled()
    {
        return $this->invoiceCancelled;
    }

    /**
     * Set totalHT
     *
     * @param float $totalHT
     *
     * @return Ticket
     */
    public function setTotalHT($totalHT)
    {
        $this->totalHT = $totalHT;

        return $this;
    }

    /**
     * Get totalHT
     *
     * @return float
     */
    public function getTotalHT()
    {
        return $this->totalHT;
    }


    /**
     * Set paid
     *
     * @param boolean $paid
     *
     * @return Ticket
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;

        return $this;
    }

    /**
     * Get paid
     *
     * @return boolean
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set deliveryTime
     *
     * @param \DateTime $deliveryTime
     *
     * @return Ticket
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return \DateTime
     */
    public function getDeliveryTime($format = null)
    {
        if (!is_null($format) && !is_null($this->deliveryTime)) {
            return $this->deliveryTime->format($format);
        }

        return $this->deliveryTime;
    }

    /**
     * Set operator
     *
     * @param integer $operator
     *
     * @return Ticket
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return integer
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set responsible
     *
     * @param string $responsible
     *
     * @return Ticket
     */
    public function setResponsible($responsible)
    {
        $this->responsible = $responsible;

        return $this;
    }

    /**
     * Get responsible
     *
     * @return string
     */
    public function getResponsible()
    {
        return $this->responsible;
    }

    /**
     * Set workstation
     *
     * @param integer $workstation
     *
     * @return Ticket
     */
    public function setWorkstation($workstation)
    {
        $this->workstation = $workstation;

        return $this;
    }

    /**
     * Get workstation
     *
     * @return integer
     */
    public function getWorkstation()
    {
        return $this->workstation;
    }

    /**
     * Set origin
     *
     * @param string $origin
     *
     * @return Ticket
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * Get origin
     *
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * Set destination
     *
     * @param string $destination
     *
     * @return Ticket
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Get destination
     *
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Set entity
     *
     * @param integer $entity
     *
     * @return Ticket
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return integer
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set customer
     *
     * @param integer $customer
     *
     * @return Ticket
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer
     *
     * @return integer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add line
     *
     * @param \AppBundle\Financial\Entity\TicketLine $line
     *
     * @return Ticket
     */
    public function addLine(\AppBundle\Financial\Entity\TicketLine $line)
    {
        $line->setTicket($this);
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Financial\Entity\TicketLine $line
     */
    public function removeLine(\AppBundle\Financial\Entity\TicketLine $line)
    {
        $this->lines->removeElement($line);
    }

    /**
     * Get lines
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Add payment
     *
     * @param \AppBundle\Financial\Entity\TicketPayment $payment
     *
     * @return Ticket
     */
    public function addPayment(\AppBundle\Financial\Entity\TicketPayment $payment)
    {
        $payment->setTicket($this);
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Remove payment
     *
     * @param \AppBundle\Financial\Entity\TicketPayment $payment
     */
    public function removePayment(\AppBundle\Financial\Entity\TicketPayment $payment)
    {
        $this->payments->removeElement($payment);
    }

    /**
     * Get payments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * Add intervention
     *
     * @param \AppBundle\Financial\Entity\TicketIntervention $intervention
     *
     * @return Ticket
     */
    public function addIntervention(\AppBundle\Financial\Entity\TicketIntervention $intervention)
    {
        $intervention->setTicket($this);
        $this->interventions[] = $intervention;

        return $this;
    }

    /**
     * Remove intervention
     *
     * @param \AppBundle\Financial\Entity\TicketIntervention $intervention
     */
    public function removeIntervention(\AppBundle\Financial\Entity\TicketIntervention $intervention)
    {
        $this->interventions->removeElement($intervention);
    }

    /**
     * Get interventions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInterventions()
    {
        return $this->interventions;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Ticket
     */
    public function setDate($date, $format = null)
    {
        if (is_string($date) && $format) {
            $this->date = \DateTime::createFromFormat($format, $date);
        } else {
            $this->date = $date;
        }

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate($format = null)
    {
        if (!is_null($format) && !is_null($this->date)) {
            return $this->date->format($format);
        }

        return $this->date;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Ticket
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
     * Set cancelledFlag
     *
     * @param boolean $cancelledFlag
     *
     * @return Ticket
     */
    public function setCancelledFlag($cancelledFlag)
    {
        $this->cancelledFlag = $cancelledFlag;

        return $this;
    }

    /**
     * Get cancelledFlag
     *
     * @return boolean
     */
    public function getCancelledFlag()
    {
        return $this->cancelledFlag;
    }

    /**
     * Set totalTTC
     *
     * @param float $totalTTC
     *
     * @return Ticket
     */
    public function setTotalTTC($totalTTC)
    {
        $this->totalTTC = $totalTTC;

        return $this;
    }

    /**
     * Get totalTTC
     *
     * @return float
     */
    public function getTotalTTC()
    {
        return $this->totalTTC;
    }

    /**
     * @return boolean
     */
    public function isCounted()
    {
        return $this->counted;
    }

    /**
     * @param boolean $counted
     * @return Ticket
     */
    public function setCounted($counted)
    {
        $this->counted = $counted;

        return $this;
    }


    /**
     * Set operatorName
     *
     * @param string $operatorName
     *
     * @return ticket
     */
    public function setOperatorName($operatorName)
    {
        $this->operatorName = $operatorName;

        return $this;
    }

    /**
     * Get operatorName
     *
     * @return string
     */
    public function getOperatorName()
    {
        return $this->operatorName;
    }

    /**
     * Set workstationName
     *
     * @param string $workstationName
     *
     * @return ticket
     */
    public function setWorkstationName($workstationName)
    {
        $this->workstationName = $workstationName;

        return $this;
    }

    /**
     * Get workstationName
     *
     * @return string
     */
    public function getWorkstationName()
    {
        return $this->workstationName;
    }

    /**
     * Set originId
     *
     * @param integer $originId
     *
     * @return ticket
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;

        return $this;
    }

    /**
     * Get originId
     *
     * @return integer
     */
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * Set destinationId
     *
     * @param integer $destinationId
     *
     * @return ticket
     */
    public function setDestinationId($destinationId)
    {
        $this->destinationId = $destinationId;

        return $this;
    }

    /**
     * Get destinationId
     *
     * @return integer
     */
    public function getDestinationId()
    {
        return $this->destinationId;
    }

    /**
     * @return CashboxCount
     */
    public function getCashboxCount()
    {
        return $this->cashboxCount;
    }

    /**
     * @param CashboxCount $cashboxCount
     * @return Ticket
     */
    public function setCashboxCount($cashboxCount)
    {
        $this->setCounted(true);
        $this->cashboxCount = $cashboxCount;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     * @return Ticket
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCountedCanceled()
    {
        return $this->countedCanceled;
    }

    /**
     * @param boolean $countedCanceled
     * @return Ticket
     */
    public function setCountedCanceled($countedCanceled)
    {
        $this->countedCanceled = $countedCanceled;

        return $this;
    }

    /**
     * @return string
     */
    public function getSoldingCanalLabel()
    {

        if (
            (strtolower($this->getOrigin()) == strtolower("POS") && strtolower($this->getDestination()) == strtolower(
                    "TakeOut"
                )) ||
            (strtolower($this->getOrigin()) == strtolower("NULL") && strtolower($this->getDestination()) == strtolower(
                    "TAKE OUT"
                )) ||
            (strtolower($this->getOrigin()) == strtolower("") && strtolower($this->getDestination()) == strtolower(
                    "TAKE OUT"
                ))
        ) {
            return "TakeOut";
        } elseif (
            strtolower($this->getOrigin()) == strtolower("KIOSK") && strtolower($this->getDestination()) == strtolower(
                "TakeOut"
            )
        ) {
            return "KIOSK OUT";
        } elseif (strtolower($this->getOrigin()) == strtolower("KIOSK") && strtolower(
                $this->getDestination()
            ) == strtolower("EatIn")) {
            return "KIOSK IN";
        } elseif (
            (strtolower($this->getOrigin()) == strtolower("DriveThru") && strtolower(
                    $this->getDestination()
                ) == strtolower("DriveThru")) ||
            (strtolower($this->getOrigin()) == strtolower("NULL") && strtolower($this->getDestination()) == strtolower(
                    "DRIVE"
                )) ||
            (strtolower($this->getOrigin()) == strtolower("") && strtolower($this->getDestination()) == strtolower(
                    "DRIVE"
                )) ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("MQDrive" ))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("MQCurbside" ))
        ) {
            return "DriveThru";
        } elseif (
            strtolower($this->getOrigin()) == strtolower("POS") && strtolower($this->getDestination()) == strtolower(
                "Delivery"
            )||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATOUberEats"))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATODeliveroo"))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATOTakeAway"))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATOHelloUgo"))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATOEasy2Eat"))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATOGoosty"))
            ||
            (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower("ATOWolt"))
        ) {
            return "Delivery";
        }
        elseif (
            strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower($this->getDestination()) == strtolower(
                "MyQuickEatIn"
            )
        ) {
            return "E-ordering IN";
        } elseif (strtolower($this->getOrigin()) == strtolower("MyQuick") && strtolower(
                $this->getDestination()
            ) == strtolower("MyQuickTakeout")) {
            return "E-ordering OUT";
        }


        else {
            return "EatIn";
        }
    }

    /*
     *
     *
     */
    public function getGroupedDiscount()
    {

        $result = array();
        $keys = array();
        foreach ($this->getLines() as $line) {
            if (!empty($line->getDiscountCode()) && strtoupper($line->getDiscountCode()) != "NULL") {
                $key = array_search($line->getDiscountCode(), $keys);
                if (false !== $key) {
                    $result[$key]['total'] += $line->getDiscountTtc();
                } else {
                    $keys[] = $line->getDiscountCode();
                    $result[] = ['total' => $line->getDiscountTtc(), 'label' => $line->getDiscountLabel(),'discount_id' => $line->getDiscountId()];
                }
            }
        }

        return $result;
    }
}


