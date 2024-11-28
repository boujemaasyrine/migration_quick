<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Transfer
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\TransferRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Transfer
{
    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    const TRANSFER_IN = 'transfer_in';
    const TRANSFER_OUT = 'transfer_out';

    const PENDING = 'pending';
    const DRAFT = 'draft';
    const DELIVERED = 'delivered';
    const CANCELED = 'canceled';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_transfer", type="date")
     */
    private $dateTransfer;

    /**
     * @var Restaurant
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Restaurant",inversedBy="transfers")
     */
    private $restaurant;

    /**
     * @var float
     * @ORM\Column(name="valorization",type="float",nullable=true)
     */
    private $valorization;

    /**
     * @var TransferLine
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\TransferLine",mappedBy="transfer",cascade={"persist"})
     */
    private $lines;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $employee;

    /**
     * @var string
     * @ORM\Column(name="num_transfer",type="string",length=50,nullable=true)
     */
    private $numTransfer;

    /**
     * @var boolean
     * @ORM\Column(name="mail_sent", type="boolean", options={"default"=true}, nullable=true)
     */
    protected $mailSent = false;

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
     * Set type
     *
     * @param string $type
     *
     * @return Transfer
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
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set restaurant
     *
     * @param \AppBundle\Merchandise\Entity\Restaurant $restaurant
     *
     * @return Transfer
     */
    public function setRestaurant(\AppBundle\Merchandise\Entity\Restaurant $restaurant = null)
    {
        $this->restaurant = $restaurant;

        return $this;
    }

    /**
     * Get restaurant
     *
     * @return \AppBundle\Merchandise\Entity\Restaurant
     */
    public function getRestaurant()
    {
        return $this->restaurant;
    }

    /**
     * Add line
     *
     * @param \AppBundle\Merchandise\Entity\TransferLine $line
     *
     * @return Transfer
     */
    public function addLine(\AppBundle\Merchandise\Entity\TransferLine $line)
    {
        $line->setTransfer($this);
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Merchandise\Entity\TransferLine $line
     */
    public function removeLine(\AppBundle\Merchandise\Entity\TransferLine $line)
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
     * Set employee
     *
     * @param \AppBundle\Staff\Entity\Employee $employee
     *
     * @return Transfer
     */
    public function setEmployee(Employee $employee = null)
    {
        $this->employee = $employee;

        return $this;
    }

    /**
     * Get employee
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getEmployee()
    {
        return $this->employee;
    }

    /**
     * Set dateTransfer
     *
     * @param \DateTime $dateTransfer
     *
     * @return Transfer
     */
    public function setDateTransfer($dateTransfer)
    {
        $this->dateTransfer = $dateTransfer;

        return $this;
    }

    /**
     * Get dateTransfer
     *
     * @return \DateTime
     */
    public function getDateTransfer()
    {
        return $this->dateTransfer;
    }

    /**
     * Set numTransfer
     *
     * @param string $numTransfer
     *
     * @return Transfer
     */
    public function setNumTransfer($numTransfer)
    {
        $this->numTransfer = $numTransfer;

        return $this;
    }

    /**
     * Get numTransfer
     *
     * @return string
     */
    public function getNumTransfer()
    {
        return $this->numTransfer;
    }

    /**
     * Set valorization
     *
     * @param float $valorization
     *
     * @return Transfer
     */
    public function setValorization($valorization)
    {
        $valorization = str_replace(',', '.', $valorization);
        $this->valorization = $valorization;

        return $this;
    }

    /**
     * Get valorization
     *
     * @return float
     */
    public function getValorization()
    {
        return $this->valorization;
    }

    /**
     * Set mailSent
     *
     * @param boolean $mailSent
     *
     * @return Transfer
     */
    public function setMailSent($mailSent)
    {
        $this->mailSent = $mailSent;

        return $this;
    }

    /**
     * Get mailSent
     *
     * @return boolean
     */
    public function getMailSent()
    {
        return $this->mailSent;
    }
}
