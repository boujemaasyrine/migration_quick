<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/04/2016
 * Time: 09:26
 */

namespace AppBundle\Administration\Entity\Optikitchen;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Optikitchen
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Optikitchen
{
    use TimestampableTrait;
    use OriginRestaurantTrait;

    /**
     * @var int
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
     * @var \DateTime
     *
     * @ORM\Column(name="date1", type="date")
     */
    private $date1;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date2", type="date")
     */
    private $date2;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date3", type="date")
     */
    private $date3;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date4", type="date")
     */
    private $date4;

    /**
     * @var bool
     *
     * @ORM\Column(name="synchronized", type="boolean")
     */
    private $synchronized;

    /**
     * @var bool
     *
     * @ORM\Column(name="locked", type="boolean")
     */
    private $locked;

    /**
     * @var OptikitchenProduct
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct",mappedBy="optikitchen",cascade={"remove","persist"})
     */
    private $products;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_syncho_date",type="datetime",nullable=true)
     */
    private $lastSynchoDate;

    /**
     * @var string
     *
     * @ORM\Column(name="meta",type="text",nullable=true)
     */
    private $meta;

    /**
     * @var float
     *
     * @ORM\Column(name="bud_prev",type="float",nullable=true)
     */
    private $budPrev;


    /**
     * @var array
     *
     * @ORM\Column(name="budgets",type="array",nullable=true)
     */
    private $budgets;

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
     * @return Optikitchen
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
     * Set synchronized
     *
     * @param boolean $synchronized
     *
     * @return Optikitchen
     */
    public function setSynchronized($synchronized)
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    /**
     * Get synchronized
     *
     * @return boolean
     */
    public function getSynchronized()
    {
        return $this->synchronized;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     *
     * @return Optikitchen
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->budgets = new ArrayCollection();
    }

    /**
     * Add product
     *
     * @param \AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct $product
     *
     * @return Optikitchen
     */
    public function addProduct(OptikitchenProduct $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct $product
     */
    public function removeProduct(OptikitchenProduct $product)
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
        $products = $this->products->toArray();

        usort(
            $products,
            function (OptikitchenProduct $p, OptikitchenProduct $p2) {
                if ($p->getProduct()->getName() < $p2->getProduct()->getName()) {
                    return -1;
                }

                return 1;

            }
        );

        return $products;
    }

    /**
     * Set date1
     *
     * @param \DateTime $date1
     *
     * @return Optikitchen
     */
    public function setDate1($date1)
    {
        $this->date1 = $date1;

        return $this;
    }

    /**
     * Get date1
     *
     * @return \DateTime
     */
    public function getDate1()
    {
        return $this->date1;
    }

    /**
     * Set date2
     *
     * @param \DateTime $date2
     *
     * @return Optikitchen
     */
    public function setDate2($date2)
    {
        $this->date2 = $date2;

        return $this;
    }

    /**
     * Get date2
     *
     * @return \DateTime
     */
    public function getDate2()
    {
        return $this->date2;
    }

    /**
     * Set date3
     *
     * @param \DateTime $date3
     *
     * @return Optikitchen
     */
    public function setDate3($date3)
    {
        $this->date3 = $date3;

        return $this;
    }

    /**
     * Get date3
     *
     * @return \DateTime
     */
    public function getDate3()
    {
        return $this->date3;
    }

    /**
     * Set date4
     *
     * @param \DateTime $date4
     *
     * @return Optikitchen
     */
    public function setDate4($date4)
    {
        $this->date4 = $date4;

        return $this;
    }

    /**
     * Get date4
     *
     * @return \DateTime
     */
    public function getDate4()
    {
        return $this->date4;
    }

    /**
     * Set lastSynchoDate
     *
     * @param \DateTime $lastSynchoDate
     *
     * @return Optikitchen
     */
    public function setLastSynchoDate($lastSynchoDate)
    {
        $this->lastSynchoDate = $lastSynchoDate;

        return $this;
    }

    /**
     * Get lastSynchoDate
     *
     * @return \DateTime
     */
    public function getLastSynchoDate()
    {
        return $this->lastSynchoDate;
    }


    /**
     * @return mixed|string
     */
    public function getMeta()
    {

        $data = json_decode($this->meta, true);
        if ($data) {
            return $data;
        }

        return $this->meta;
    }

    /**
     * @param $data
     */
    public function setMeta($data)
    {

        if (is_array($data)) {
            $this->meta = json_encode($data);
        } else {
            $this->meta = $data;
        }
    }

    /**
     * @return array
     */
    public function getDayParts()
    {

        $meta = $this->getMeta();

        $openHour = $meta['open'];
        $closeHour = $meta['close'];
        $id = 4 * $openHour;

        $diff = ($openHour * 60);
        if ($openHour <= $closeHour) {
            $diff = ($closeHour * 60) - $diff;
        } else {
            $diff = (23 * 60) + 45 - $diff + ($closeHour * 60);
        }

        $partDays = [];
        for ($i = 0; $i <= $diff; $i = $i + 15) {
            $sh = intval(floor((($openHour * 60) + $i) / 60));
            if ($sh >= 24) {
                $sh = $sh - 24;
            }
            $sh = str_pad($sh, 2, '0', STR_PAD_LEFT);

            $sm = (($openHour * 60) + $i) % 60;
            $sm = str_pad($sm, 2, '0', STR_PAD_LEFT);

            $eh = intval(floor((($openHour * 60) + 15 + $i) / 60));
            if ($eh >= 24) {
                $eh = $eh - 24;
            }
            $eh = str_pad($eh, 2, '0', STR_PAD_LEFT);

            $em = (($openHour * 60) + $i + 15) % 60;
            $em = str_pad($em, 2, '0', STR_PAD_LEFT);

            $partDays[] = [
                'id' => $id,
                'startH' => $sh,
                'startM' => $sm,
                'endH' => $eh,
                'endM' => $em,
            ];
            $id++;
        }

        if (count($this->budgets) > 0) {
            foreach ($partDays as $key => &$value) {
                $value['budget'] = $this->budgets[$key];
                if ($this->budPrev != 0) {
                    $value['percent_budget'] = $this->budgets[$key] / $this->budPrev;
                } else {
                    $value['percent_budget'] = 0;
                }
            }
        }

        return $partDays;
    }

    /**
     * @param OptikitchenMatrix[] $matrix
     * @param array $d
     *
     * @return array
     */
    public function getBinLevel($matrix, $d)
    {

        $data = 0;

        foreach ($matrix as $m) {
            if (($m->getMin() <= $d['budget']) && ($m->getMax() > $d['budget'])) {
                $data = $m->getLevel();
            }
        }

        return $data;
    }


    /**
     * Set budPrev
     *
     * @param float $budPrev
     *
     * @return Optikitchen
     */
    public function setBudPrev($budPrev)
    {
        $this->budPrev = $budPrev;

        return $this;
    }

    /**
     * Get budPrev
     *
     * @return float
     */
    public function getBudPrev()
    {
        return $this->budPrev;
    }


    /**
     * Set budgets
     *
     * @param array $budgets
     *
     * @return OptikitchenProduct
     */
    public function setBudgets($budgets)
    {
        $this->budgets = $budgets;

        return $this;
    }

    /**
     * Get budgets
     *
     * @return array
     */
    public function getBudgets()
    {
        return $this->budgets;
    }
}
