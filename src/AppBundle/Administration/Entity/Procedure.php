<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/04/2016
 * Time: 09:26
 */

namespace AppBundle\Administration\Entity;

use AppBundle\Administration\Entity\Translation\ProcedureTranslation;
use AppBundle\Security\Entity\Role;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Procedure
 *
 * @ORM\Table()
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="AppBundle\Administration\Entity\Translation\ProcedureTranslation")
 */
class Procedure
{
    use OriginRestaurantTrait;

    CONST OPENING = 'ouverture';

    /**
     * @var int
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
     *
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="canBeDeleted", type="boolean",nullable=true)
     */
    private $canBeDeleted = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="only_once_at_day",type="boolean",nullable=true)
     */
    private $onlyOnceAtDay = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="only_once_for_all",type="boolean",nullable=true)
     */
    private $onlyOnceForAll = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="at_same_time",type="boolean",nullable=true)
     */
    private $atSameTime = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="autorize_abandon",type="boolean",nullable=true)
     */
    private $autorizeAbandon = true;

    /**
     * @var ProcedureStep
     *
     * @ORM\OneToMany(targetEntity="ProcedureStep",mappedBy="procedure",cascade={"remove"})
     */
    private $steps;

    /**
     * @var ProcedureInstance
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Administration\Entity\ProcedureInstance",mappedBy="procedure",cascade={"remove"})
     */
    private $instances;

    /**
     * @var Role
     * @ORM\ManyToMany(targetEntity="AppBundle\Security\Entity\Role")
     */
    private $eligibleRoles;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Administration\Entity\Translation\ProcedureTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    public function getTranslations()
    {
        return $this->translations;
    }
    public function addTranslation(ProcedureTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
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
     * Set name
     *
     * @param string $name
     *
     * @return Procedure
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
     * Set canBeDeleted
     *
     * @param boolean $canBeDeleted
     *
     * @return Procedure
     */
    public function setCanBeDeleted($canBeDeleted)
    {
        $this->canBeDeleted = $canBeDeleted;

        return $this;
    }

    /**
     * Get canBeDeleted
     *
     * @return boolean
     */
    public function getCanBeDeleted()
    {
        return $this->canBeDeleted;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->steps = new ArrayCollection();
        $this->eligibleRoles = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    /**
     * Add step
     *
     * @param \AppBundle\Administration\Entity\ProcedureStep $step
     *
     * @return Procedure
     */
    public function addStep(\AppBundle\Administration\Entity\ProcedureStep $step
    ) {
        $step->setProcedure($this);
        $this->steps[] = $step;

        return $this;
    }

    /**
     * Remove step
     *
     * @param \AppBundle\Administration\Entity\ProcedureStep $step
     */
    public function removeStep(
        \AppBundle\Administration\Entity\ProcedureStep $step
    ) {
        $this->steps->removeElement($step);
    }

    /**
     * Get steps
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param $order
     *
     * @return ProcedureStep
     */
    public function getStepByOrder($order)
    {
        foreach ($this->getSteps() as $s) {
            if ($s->getOrder() == $order) {
                return $s;
            }
        }

        return null;
    }

    /**
     * Add instance
     *
     * @param \AppBundle\Administration\Entity\ProcedureInstance $instance
     *
     * @return Procedure
     */
    public function addInstance(
        \AppBundle\Administration\Entity\ProcedureInstance $instance
    ) {
        $this->instances[] = $instance;

        return $this;
    }

    /**
     * Remove instance
     *
     * @param \AppBundle\Administration\Entity\ProcedureInstance $instance
     */
    public function removeInstance(
        \AppBundle\Administration\Entity\ProcedureInstance $instance
    ) {
        $this->instances->removeElement($instance);
    }

    /**
     * Get instances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * Set onlyOnceAtDay
     *
     * @param boolean $onlyOnceAtDay
     *
     * @return Procedure
     */
    public function setOnlyOnceAtDay($onlyOnceAtDay)
    {
        $this->onlyOnceAtDay = $onlyOnceAtDay;

        return $this;
    }

    /**
     * Get onlyOnceAtDay
     *
     * @return boolean
     */
    public function getOnlyOnceAtDay()
    {
        return $this->onlyOnceAtDay;
    }

    /**
     * Set onlyOnceForAll
     *
     * @param boolean $onlyOnceForAll
     *
     * @return Procedure
     */
    public function setOnlyOnceForAll($onlyOnceForAll)
    {
        $this->onlyOnceForAll = $onlyOnceForAll;

        return $this;
    }

    /**
     * Get onlyOnceForAll
     *
     * @return boolean
     */
    public function getOnlyOnceForAll()
    {
        return $this->onlyOnceForAll;
    }

    /**
     * Set atSameTime
     *
     * @param boolean $atSameTime
     *
     * @return Procedure
     */
    public function setAtSameTime($atSameTime)
    {
        $this->atSameTime = $atSameTime;

        return $this;
    }

    /**
     * Get atSameTime
     *
     * @return boolean
     */
    public function getAtSameTime()
    {
        return $this->atSameTime;
    }

    /**
     * Set autorizeAbandon
     *
     * @param boolean $autorizeAbandon
     *
     * @return Procedure
     */
    public function setAutorizeAbandon($autorizeAbandon)
    {
        $this->autorizeAbandon = $autorizeAbandon;

        return $this;
    }

    /**
     * Get autorizeAbandon
     *
     * @return boolean
     */
    public function getAutorizeAbandon()
    {
        return $this->autorizeAbandon;
    }

    /**
     * Add eligibleRole
     *
     * @param \AppBundle\Security\Entity\Role $eligibleRole
     *
     * @return Procedure
     */
    public function addEligibleRole(
        \AppBundle\Security\Entity\Role $eligibleRole
    ) {
        $this->eligibleRoles[] = $eligibleRole;

        return $this;
    }

    /**
     * Remove eligibleRole
     *
     * @param \AppBundle\Security\Entity\Role $eligibleRole
     */
    public function removeEligibleRole(
        \AppBundle\Security\Entity\Role $eligibleRole
    ) {
        $this->eligibleRoles->removeElement($eligibleRole);
    }

    /**
     * Get eligibleRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEligibleRoles()
    {
        return $this->eligibleRoles;
    }

    /**
     * @param $locale
     * @param $value
     *
     * @return $this
     */
    public function addNameTranslation($locale, $value)
    {
        $exist = false;
        foreach ($this->translations as $t) {
            /**
             * @var ProcedureTranslation $t
             */
            if ($t->getLocale() === $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new ProcedureTranslation($locale, 'name', $value);
            $this->addTranslation($translation);
        }

        return $this;
    }

    /**
     * @param $locale
     *
     * @return null|string
     */
    public function getNameTranslation($locale)
    {
        $name = null;
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                $name = $translation->getcontent();
            }
        }
        return $name;
    }



}
