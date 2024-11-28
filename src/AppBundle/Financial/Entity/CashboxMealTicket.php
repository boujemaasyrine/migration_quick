<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 17:56
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\TypeInterface;
use AppBundle\ToolBox\Traits\IdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * MealTicket
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxMealTicket implements TypeInterface
{
    use IdTrait;

    /**
     * @var CashboxMealTicketContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxMealTicketContainer")
     */
    private $mealTicketContainer;

    /**
     * @var TicketPayment
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\TicketPayment")
     */
    private $ticketPayment;

    /**
     * @return TicketPayment
     */
    public function getTicketPayment()
    {
        return $this->ticketPayment;
    }

    /**
     * @param TicketPayment $ticketPayment
     * @return self
     */
    public function setTicketPayment($ticketPayment)
    {
        $this->ticketPayment = $ticketPayment;

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
     * @return self
     */
    public function setMealTicketContainer($mealTicketContainer)
    {
        $this->mealTicketContainer = $mealTicketContainer;

        return $this;
    }
}
