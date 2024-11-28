<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 14/04/2016
 * Time: 10:04
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * RecipeTicket
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\RecipeTicketRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RecipeTicket
{
    use IdTrait;
    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    //use SynchronizedFlagTrait;

    // Constant Block
    // labels
    const CHANGE_RECIPE = 'change_recipe';
    const WC_MONEY = 'wc_money';
    const VARIOUS = 'various';

    // automatic labels
    const CASHBOX_ERROR = 'cashbox_error';
    const CHEST_ERROR = 'chest_error';
    const CACHBOX_RECIPE = 'cachbox_recipe';

    static $labels = [
        self::CHANGE_RECIPE => self::CHANGE_RECIPE,
        self::WC_MONEY => self::WC_MONEY,
        self::VARIOUS => self::VARIOUS,
    ];

    // Fields Block

    /**
     * @var string
     * @ORM\Column(name="label",type="string",length=100)
     */
    private $label;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=true)
     */
    private $date;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $owner;

    /**
     * @var ChestCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="recipeTickets")
     */
    private $chestCount;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false}, nullable=true)
     */
    protected $deleted = false;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return RecipeTicket
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return RecipeTicket
     */
    public function setAmount($amount)
    {
        if (is_string($amount)) {
            $amount = str_replace(',', '.', $amount);
        }
        $this->amount = $amount;

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
     * @return RecipeTicket
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }


    /**
     * Set chestCount
     *
     * @param \AppBundle\Financial\Entity\ChestCount $chestCount
     *
     * @return RecipeTicket
     */
    public function setChestCount(\AppBundle\Financial\Entity\ChestCount $chestCount = null)
    {
        $this->chestCount = $chestCount;

        return $this;
    }

    /**
     * Get chestCount
     *
     * @return \AppBundle\Financial\Entity\ChestCount
     */
    public function getChestCount()
    {
        return $this->chestCount;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return RecipeTicket
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
    public function getDate($format = null)
    {
        if (!is_null($format) && !is_null($this->date)) {
            return $this->date->format($format);
        }

        return $this->date;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return RecipeTicket
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
}
