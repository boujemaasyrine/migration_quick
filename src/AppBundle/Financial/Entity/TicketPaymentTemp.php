<?php

namespace AppBundle\Financial\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * $this
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class TicketPaymentTemp
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
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
     * @ORM\Column(name="first_name",type="string",length=30,nullable=true)
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
     * @var string
     * @ORM\Column(name="ticket_id", type="string", nullable=TRUE)
     */
    private $ticket;

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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * Set type
     *
     * @param string $type
     *
     * @return $this
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
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param string $ticket
     * @return TicketPaymentTemp
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }
}
