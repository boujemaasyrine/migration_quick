<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/05/2016
 * Time: 11:11
 */

namespace AppBundle\General\Entity;

use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * RemoteHistoric
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class RemoteHistoric
{

    use TimestampableTrait;

    // General
    const CLOSING_OPENING_HOUR = 'closing_opening_hour';
    const MISSING_PLU_NOTIFICATION = 'missing_plu_notification';

    // Status
    const CREATED = 'created';
    const PENDING = 'pending';
    const SUCCESS = 'success';
    const FAIL = 'fail';

    //Merchandise
    const ORDERS = 'orders';
    const DELIVERIES = 'deliveries';
    const TRANSFERS = 'transfers';
    const RETURNS = 'returns';
    const SHEET_MODELS = 'sheet_models';
    const PRODUCT_PURCHASED_MOVEMENTS = 'product_purchased_movements';
    const REMOVE_MOVEMENT = 'remove_movement';

    //Stock
    const INVENTORIES = 'inventories';
    const LOSS_PURCHASED = 'loss_purchased';
    const LOSS_SOLD = 'loss_sold';

    //Financial
    const FINANCIAL_REVENUES = 'financial_revenues';
    const BUDGET_PREVISIONNELS = 'bud_prev';
    const ADMIN_CLOSING = 'admin_closing';
    const CASHBOX_COUNTS = 'cashbox_counts';
    const CHEST_COUNTS = 'chest_counts';
    const ENVELOPPES = 'enveloppes';
    const WITHDRAWALS = 'withdrawals';
    const DEPOSITS = 'deposits';
    const EXPENSES = 'expenses';
    const RECIPE_TICKETS = 'recipe_tickets';
    const TICKETS = 'tickets';
    const REMOVE_TICKETS = 'remove_ticket';

    //Staff
    const EMPLOYEE = 'employee';

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startedAt", type="datetime")
     */
    private $startedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=255, nullable=TRUE)
     */
    private $error;


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
     * @return RemoteHistoric
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
     * Set startedAt
     *
     * @param \DateTime $startedAt
     *
     * @return RemoteHistoric
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * Get startedAt
     *
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return RemoteHistoric
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set error
     *
     * @param string $error
     *
     * @return RemoteHistoric
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
