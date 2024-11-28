<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Report\Entity\ControlStockTmp;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * SheetModel
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\SheetModel\SheetModelRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SheetModel implements \Serializable
{

    const UNIT_NEED = 'unitNeed';
    const ARTICLE = 'article';
    const FINALPRODUCT = 'finalProduct';

    const INVENTORY_MODEL = 'inventory_model';
    const ARTICLES_LOSS_MODEL = 'articles_loss_model';
    const PRODUCT_SOLD_LOSS_MODEL = 'sold_products_loss_model';

    static $modelTypes = [
        self::INVENTORY_MODEL,
        self::ARTICLES_LOSS_MODEL,
        self::PRODUCT_SOLD_LOSS_MODEL,
    ];

    static $cibledByType = [
        self::INVENTORY_MODEL => self::ARTICLE,
        self::ARTICLES_LOSS_MODEL => self::ARTICLE,
        self::PRODUCT_SOLD_LOSS_MODEL => self::FINALPRODUCT,
    ];

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    //use SynchronizedFlagTrait;

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
     * @var SheetModelLine[]
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\SheetModelLine", mappedBy="sheet", cascade={"persist", "remove"})
     * @ORM\OrderBy({"orderInSheet" = "ASC"})
     */
    private $lines;

    /**
     * @var string
     * @ORM\Column(name="label", type="string", length=155, nullable=true)
     */
    private $label;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $employee;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted = false;

    /**
     * @var string
     *
     * @ORM\Column(name="sheet_type", type="string", length=255, nullable=true)
     */
    private $linesType;

    /**
     * @var ControlStockTmp[]
     * @ORM\OneToMany(targetEntity="AppBundle\Report\Entity\ControlStockTmp",mappedBy="sheet",cascade={"remove"})
     */
    private $controlStocks;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return SheetModel
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
     * Set type
     *
     * @param string $type
     *
     * @return SheetModel
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
     * @param SheetModelLine[] $lines
     * @return $this
     */
    public function setLines($lines)
    {
        foreach ($lines as $line) {
            $line->setSheet($this);
            $this->addLine($line);
        }

        return $this;
    }

    /**
     * @param SheetModelLine $line
     * @return $this
     */
    public function addLine(SheetModelLine $line)
    {
        $lines = $this->getLines();
        $line->setSheet($this);
        //        $line->setOrderInSheet(count($lines));
        $lines->add($line);

        return $this;
    }

    /**
     * @param SheetModelLine $line
     */
    public function removeLine(SheetModelLine $line)
    {
        $this->getLines()->removeElement($line);
    }

    /**
     *
     * @return ArrayCollection
     */
    public function getLines()
    {
        $lines = $this->lines;
        $iterator = $lines->getIterator();

        $iterator->uasort(
            function ($first, $second) {
                if ($first->getOrderInSheet() === $second->getOrderInSheet()) {
                    return 0;
                }

                return (int) $first->getOrderInSheet() < (int) $second->getOrderInSheet() ? -1 : 1;
            }
        );

        return $lines;
    }

    /**
     * @param Employee|null $employee
     * @return $this
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
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param boolean $deleted
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return SheetModel
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLinesType()
    {
        return $this->linesType;
    }

    /**
     * @param string $linesType
     * @return SheetModel
     */
    public function setLinesType($linesType)
    {
        $this->linesType = $linesType;

        return $this;
    }

    public function doesProductAlreadyExistInThisSheet(Product $product)
    {
        $result = $this->getLines()->filter(
            function ($line) use ($product) {
                if (!is_null($line->getProduct()))
                {
                    if ($line->getProduct()->getId() === $product->getId()) {
                        return true;
                    }
                }
                return false;
            }
        );

        return count($result) > 0;
    }

    public function __toString()
    {
        return sprintf("%s - %s", $this->getId(), $this->getLabel());
    }

    public function serialize()
    {
        $sheet = [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'employee' => $this->getEmployee()->getGlobalEmployeeID(),
            'type' => $this->getType(),
            'deleted' => $this->isDeleted(),
            'createdAt' => $this->getCreatedAt('Y-m-d H:i:s'),
            'updatedAt' => $this->getUpdatedAt('Y-m-d H:i:s'),
        ];

        return $sheet;
    }

    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
    }

    /**
     * @return \AppBundle\Report\Entity\ControlStockTmp[]
     */
    public function getControlStocks()
    {
        return $this->controlStocks;
    }

    /**
     * @param \AppBundle\Report\Entity\ControlStockTmp[] $controlStocks
     * @return SheetModel
     */
    public function setControlStocks($controlStocks)
    {
        $this->controlStocks = $controlStocks;

        return $this;
    }
}
