<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Supplier
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\SupplierRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Supplier
{

    use TimestampableTrait;
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
     * @ORM\Column(name="name", type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="designation", type="string", length=100, nullable=true)
     */
    private $designation;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var boolean
     * @ORM\Column(name="active",type="boolean",nullable=true)
     */
    private $active;

    /**
     * @var  ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased",mappedBy="supplier")
     */
    private $products;

    /**
     * @var SupplierPlanning
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\SupplierPlanning",mappedBy="supplier",  cascade={"persist"})
     */
    private $plannings;

    /**
     * @var Order
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\Order",mappedBy="supplier")
     */
    private $orders;

    /**
     * @var string
     * @ORM\Column(name="email",type="string",length=50,nullable=true)
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(name="zone",type="string",length=50,nullable=true)
     */
    private $zone;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Restaurant", mappedBy="suppliers")
     */
    private $restaurants;

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
     * Set name
     *
     * @param string $name
     *
     * @return Supplier
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Supplier
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add planning
     *
     * @param \AppBundle\Merchandise\Entity\SupplierPlanning $planning
     *
     * @return Supplier
     */
    public function addPlanning(\AppBundle\Merchandise\Entity\SupplierPlanning $planning)
    {
        $this->plannings[] = $planning;

        return $this;
    }

    /**
     * Remove planning
     *
     * @param \AppBundle\Merchandise\Entity\SupplierPlanning $planning
     */
    public function removePlanning(\AppBundle\Merchandise\Entity\SupplierPlanning $planning)
    {
        $this->plannings->removeElement($planning);
    }

    /**
     * Get plannings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlannings()
    {
        return $this->plannings;
    }

    /**
     * Get plannings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlanningsByRestaurant(Restaurant $restaurant)
    {
        if(!$restaurant)
            return false;

        $result=array();
        foreach ($this->plannings as $p){
            if($p->getOriginRestaurant()==$restaurant)
            {
                $result[]=$p;
            }
        }
        return $result;
    }


    /**
     * Add order
     *
     * @param \AppBundle\Merchandise\Entity\Order $order
     *
     * @return Supplier
     */
    public function addOrder(\AppBundle\Merchandise\Entity\Order $order)
    {
        $this->orders[] = $order;

        return $this;
    }

    /**
     * Remove order
     *
     * @param \AppBundle\Merchandise\Entity\Order $order
     */
    public function removeOrder(\AppBundle\Merchandise\Entity\Order $order)
    {
        $this->orders->removeElement($order);
    }

    /**
     * Get orders
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @param $day
     * @return SupplierPlanning[] array
     */
    public function getPlanningsByOrderDay($day)
    {
        $result = [];
        foreach ($this->plannings as $p) {
            if ($p->getOrderDay() == $day) {
                $result[] = $p;
            }
        }

        return $result;
    }

    /**
     * @param $day
     * @return SupplierPlanning[] array
     */
    public function getPlanningsByDeliveryDay($day)
    {
        $result = [];
        foreach ($this->plannings as $p) {
            if ($p->getDeliveryDay() == $day) {
                $result[] = $p;
            }
        }

        return $result;
    }

    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return Supplier
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Supplier
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set designation
     *
     * @param string $designation
     *
     * @return Supplier
     */
    public function setDesignation($designation)
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * Get designation
     *
     * @return string
     */
    public function getDesignation()
    {
        return $this->designation;
    }


    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Supplier
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return boolean
     * @return $this
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Supplier
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->plannings = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->restaurants = new ArrayCollection();
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return Supplier
     */
    public function addProduct(\AppBundle\Merchandise\Entity\ProductPurchased $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     */
    public function removeProduct(\AppBundle\Merchandise\Entity\ProductPurchased $product)
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
     * Set zone
     *
     * @param string $zone
     *
     * @return Supplier
     */
    public function setZone($zone)
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * Get zone
     *
     * @return string
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * @return ArrayCollection
     */
    public function getRestaurants()
    {
        return $this->restaurants;
    }

    /**
     * @param ArrayCollection $restaurants
     */
    public function setRestaurants($restaurants)
    {
        $this->restaurants = $restaurants;
    }

    /**
     * Add product
     *
     * @param $restaurant
     *
     * @return Supplier
     */
    public function addRestaurant(Restaurant $restaurant)
    {

        $this->restaurants->add($restaurant);

        return $this;
    }

    /**
     * Remove product
     *
     * @param $restaurant
     */
    public function removeRestaurant(Restaurant $restaurant)
    {
        $this->restaurants->removeElement($restaurant);
    }
}
