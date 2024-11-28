<?php

namespace AppBundle\Merchandise\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderHelpSupplier
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OrderHelpSupplier
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
     * @var Supplier
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Supplier")
     */
    private $supplier;

    /**
     * @var OrderHelpTmp
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpTmp",inversedBy="suppliers")
     */
    private $orderHelp;

    /**
     * @var $products
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpProducts", mappedBy="supplier" , cascade={"persist","remove"})
     */
    private $products;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMask",mappedBy="supplier",cascade={"remove"})
     */
    private $days;

    /**
     * @var $products
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMaskProduct",mappedBy="supplier",cascade={"remove"})
     */
    private $helpMaskProducts;

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
     * Constructor
     */
    public function __construct()
    {
        $this->days = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     *
     * @return OrderHelpSupplier
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
     * Set orderHelp
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpTmp $orderHelp
     *
     * @return OrderHelpSupplier
     */
    public function setOrderHelp(\AppBundle\Merchandise\Entity\OrderHelpTmp $orderHelp = null)
    {
        $this->orderHelp = $orderHelp;

        return $this;
    }

    /**
     * Get orderHelp
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpTmp
     */
    public function getOrderHelp()
    {
        return $this->orderHelp;
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpProducts $product
     *
     * @return OrderHelpSupplier
     */
    public function addProduct(\AppBundle\Merchandise\Entity\OrderHelpProducts $product)
    {
        $product->setSupplier($this);
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpProducts $product
     */
    public function removeProduct(\AppBundle\Merchandise\Entity\OrderHelpProducts $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add day
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMask $day
     *
     * @return OrderHelpSupplier
     */
    public function addDay(\AppBundle\Merchandise\Entity\OrderHelpMask $day)
    {
        $this->days[] = $day;

        return $this;
    }

    /**
     * Remove day
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMask $day
     */
    public function removeDay(\AppBundle\Merchandise\Entity\OrderHelpMask $day)
    {
        $this->days->removeElement($day);
    }

    /**
     * Get days
     *
     * @return OrderHelpMask[]
     */
    public function getDays()
    {
        $days = $this->days->toArray();
        usort(
            $days,
            function (OrderHelpMask $e1, OrderHelpMask $e2) {
                if ($e1->getOrderDate()->format('Ymd') < $e2->getOrderDate()->format('Ymd')) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $days;
    }

    /**
     * Get days
     *
     * @return OrderHelpMask[]
     */
    public function getDaysOrdredByCategories()
    {
        $days = $this->days->toArray();
        usort(
            $days,
            function (OrderHelpMask $e1, OrderHelpMask $e2) {

                if ($e1->getCategory()->getOrder() === null) {
                    return 1;
                }

                if ($e2->getCategory()->getOrder() === null) {
                    return -1;
                }

                if ($e1->getCategory()->getOrder() < $e2->getCategory()->getOrder()) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $days;
    }

    public function getDaysWithOccurence()
    {
        //var_dump($this->getSupplier()->getName()." ".$this->getId());
        $data = [];
        foreach ($this->getDays() as $d) {
            if (array_key_exists($d->getOrderDay(), $data)) {
                $data[$d->getOrderDay()]++;
            } else {
                $data[$d->getOrderDay()] = 1;
            }
        }

        return $data;
    }


    /**
     * Add helpMaskProduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct
     *
     * @return OrderHelpSupplier
     */
    public function addHelpMaskProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct)
    {
        $this->helpMaskProducts[] = $helpMaskProduct;

        return $this;
    }

    /**
     * Remove helpMaskProduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct
     */
    public function removeHelpMaskProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct)
    {
        $this->helpMaskProducts->removeElement($helpMaskProduct);
    }

    /**
     * Get helpMaskProducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHelpMaskProducts()
    {
        $helpProducts = $this->helpMaskProducts->toArray();
        usort(
            $helpProducts,
            function (OrderHelpMaskProduct $p1, OrderHelpMaskProduct $p2) {

                if ($p1->getMask()->getOrderDate()->format('Ymd') < $p2->getMask()->getOrderDate()->format('Ymd')) {
                    return -1;
                }

                return 1;
            }
        );

        return $helpProducts;
    }
}
