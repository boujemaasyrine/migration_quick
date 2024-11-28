<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 22/06/2016
 * Time: 15:44
 */

namespace AppBundle\Administration\Entity;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * ParameterLabel
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @UniqueEntity("plu",message="unique.plu")
 * @ORM\HasLifecycleCallbacks
 */
class MissingPlu {

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
     * @ORM\Column(name="plu", type="string", unique=true)
     */
    private $plu;

    /**
     * @var boolean
     * @ORM\Column(name="notified",type="boolean")
     */
    private $notified;

    /**
     * @var ArrayCollection[Restaurant]
     * @ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Restaurant")
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
     * Set plu
     *
     * @param string $plu
     * @return MissingPlu
     */
    public function setPlu($plu)
    {
        $this->plu = $plu;

        return $this;
    }

    /**
     * Get plu
     *
     * @return string 
     */
    public function getPlu()
    {
        return $this->plu;
    }

    /**
     * Set notified
     *
     * @param boolean $notified
     * @return MissingPlu
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;

        return $this;
    }

    /**
     * Get notified
     *
     * @return boolean 
     */
    public function getNotified()
    {
        return $this->notified;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->restaurants = new ArrayCollection();
    }

    /**
     * Add restaurants
     *
     * @param Restaurant $restaurant
     * @return MissingPlu
     */
    public function addRestaurant(Restaurant $restaurant)
    {
        $this->restaurants[] = $restaurant;

        return $this;
    }

    /**
     * Remove restaurants
     *
     * @param Restaurant $restaurant
     */
    public function removeRestaurant(Restaurant $restaurant)
    {
        $this->restaurants->removeElement($restaurant);
    }

    /**
     * Get restaurants
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRestaurants()
    {
        return $this->restaurants;
    }

    public function hasRestaurant(Restaurant $restaurant){
        if ($this->getRestaurants()->contains(($restaurant))){
            return true;
        }
        else {
            return false;
        }
    }
}
