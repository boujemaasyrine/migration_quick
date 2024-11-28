<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * InventoriesSheets
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\InventorySheetRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class InventorySheet
{

    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;
    //use SynchronizedFlagTrait;

    const INVENTORY_CREATED = "inventory.status.created";
    const INVENTORY_DRAFT = "inventory.status.draft";
    const INVENTORY_VALIDATED = "inventory.status.validated";

    static $inventoryStatus = [
        self::INVENTORY_CREATED,
        self::INVENTORY_DRAFT,
        self::INVENTORY_VALIDATED,
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var InventoryLine[]
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\InventoryLine", mappedBy="inventorySheet", cascade="persist")
     */
    private $lines;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $employee;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fiscal_date", type="datetime")
     */
    private $fiscalDate;

    /**
     * @deprecated
     * @var string
     * @ORM\Column(name="status", type="string", nullable=TRUE)
     */
    private $status = '';

    /**
     * @var SheetModel
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SheetModel")
     */
    private $sheetModel;

    /**
     * @var string
     * @ORM\Column(name="sheet_model_label", type="string", nullable= TRUE)
     */
    private $sheetModelLabel;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return InventoryLine
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * @param string|null $format
     * @return \DateTime
     */
    public function getFiscalDate($format = null)
    {
        if ($format) {
            return $this->fiscalDate->format($format);
        }

        return $this->fiscalDate;
    }

    /**
     * @param \DateTime $fiscalDate
     * @return $this
     */
    public function setFiscalDate($fiscalDate)
    {
        $this->fiscalDate = $fiscalDate;

        return $this;
    }

    /**
     * @param ArrayCollection $lines
     * @return $this
     */
    public function setLines($lines)
    {
        $this->lines = $lines;

        return $this;
    }

    /**
     * Add inventoryLine
     *
     * @param  Mixed $line
     * @return $this
     */
    public function addLine($line)
    {
        if ($line instanceof InventoryLine) {
            $line->setInventorySheet($this);
        }
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove inventoryLine
     *
     * @param \AppBundle\Merchandise\Entity\InventoryLine $line
     */
    public function removeLine(InventoryLine $line)
    {
        $this->lines->removeElement($line);
    }

    /**
     * Get inventoryLine
     *
     * @return ArrayCollection
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
     * @return InventorySheet
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
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return SheetModel
     */
    public function getSheetModel()
    {
        return $this->sheetModel;
    }

    /**
     * @param SheetModel $sheetModel
     * @return InventorySheet
     */
    public function setSheetModel($sheetModel)
    {
        $this->sheetModel = $sheetModel;
        if (!is_null($sheetModel)) {
            $this->setSheetModelLabel($sheetModel->getLabel());
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSheetModelLabel()
    {
        return $this->sheetModelLabel;
    }

    /**
     * @param string $sheetModelLabel
     * @return InventorySheet
     */
    public function setSheetModelLabel($sheetModelLabel)
    {
        $this->sheetModelLabel = $sheetModelLabel;

        return $this;
    }
}
