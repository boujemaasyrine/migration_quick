<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * SheetModelLine
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class SheetModelLine implements \Serializable
{

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableTrait;
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
     * @var float
     *
     * @ORM\Column(name="cnt", type="float", nullable=true)
     */
    private $cnt;

    /**
     * @var SheetModel
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SheetModel", inversedBy="lines")
     */
    private $sheet;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Product")
     */
    private $product;

    /**
     * @var integer
     * @ORM\Column(name="order_in_sheet", type="integer", nullable=TRUE)
     */
    private $orderInSheet;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return SheetModelLine
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
     * Set cnt
     *
     * @param float $cnt
     *
     * @return SheetModelLine
     */
    public function setCnt($cnt)
    {
        $cnt = str_replace(',', '.', $cnt);
        $this->cnt = $cnt;

        return $this;
    }

    /**
     * Get cnt
     *
     * @return float
     */
    public function getCnt()
    {
        return $this->cnt;
    }

    /**
     * @param SheetModel $sheet
     * @return $this
     */
    public function setSheet(SheetModel $sheet)
    {
        $this->sheet = $sheet;

        return $this;
    }

    /**
     * Get inventorySheet
     *
     * @return SheetModelLine
     */
    /**
     * @return mixed
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Merchandise\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return int
     */
    public function getOrderInSheet()
    {
        return $this->orderInSheet;
    }

    /**
     * @param int $orderInSheet
     * @return SheetModelLine
     */
    public function setOrderInSheet($orderInSheet)
    {
        $this->orderInSheet = $orderInSheet;

        return $this;
    }

    public function serialize()
    {
        $sheetLine = [
            'id' => $this->getId(),
            'cnt' => $this->getCnt(),
            'product' => $this->getProduct()->getGlobalProductID(),
            'orderInSheet' => $this->getOrderInSheet(),
            'createdAt' => $this->getCreatedAt('Y-m-d H:i:s'),
            'updatedAt' => $this->getUpdatedAt('Y-m-d H:i:s'),
        ];

        return $sheetLine;
    }

    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
    }
}
