<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Returns
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\ReturnsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Returns
{
    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var Float
     * @ORM\Column(name="valorization",type="float",nullable=true)
     */
    private $valorization;

    /**
     * @var string
     * @ORM\Column(name="comment",type="text",nullable=true)
     */
    private $comment;

    /**
     * @var ReturnLine
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\ReturnLine",mappedBy="return",cascade={"persist"})
     */
    private $lines;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $employee;

    /**
     * @var Supplier
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Supplier")
     */
    private $supplier;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Returns
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set employee
     *
     * @param \AppBundle\Staff\Entity\Employee $employee
     *
     * @return Returns
     */
    public function setEmployee(\AppBundle\Staff\Entity\Employee $employee = null)
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
     * Set valorization
     *
     * @param float $valorization
     *
     * @return Returns
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
     * Add line
     *
     * @param \AppBundle\Merchandise\Entity\ReturnLine $line
     *
     * @return Returns
     */
    public function addLine(\AppBundle\Merchandise\Entity\ReturnLine $line)
    {
        $line->setReturn($this);
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Merchandise\Entity\ReturnLine $line
     */
    public function removeLine(\AppBundle\Merchandise\Entity\ReturnLine $line)
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
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     *
     * @return Returns
     */
    public function setSupplier(\AppBundle\Merchandise\Entity\Supplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return \AppBundle\Merchandise\Entity\Supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Returns
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
