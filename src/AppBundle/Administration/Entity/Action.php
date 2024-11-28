<?php

namespace AppBundle\Administration\Entity;

use AppBundle\ToolBox\Traits\GlobalIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;

/**
 * Action
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Administration\Repository\ActionRepository")
 */
class Action
{

    use GlobalIdTrait;

    const CENTRAL_ACTION_TYPE = 'CENTRAL_ACTION_TYPE';
    const RESTAURANT_ACTION_TYPE = 'RESTAURANT_ACTION_TYPE';

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
     * @ORM\Column(name="route", type="string", length=100)
     */
    private $route;

    /**
     * @var array
     *
     * @ORM\Column(name="params", type="array")
     */
    private $params;

    /**
     * @var bool
     * @ORM\Column(name="has_exit",type="boolean",nullable=true)
     */
    private $hasExit = false;

    /**
     * @var ArrayCollection[Roles]
     * @ManyToMany(targetEntity="AppBundle\Security\Entity\Role", inversedBy="actions")
     */
    private $roles;

    /**
     * @var bool
     * @ORM\Column(name="is_page",type="boolean",nullable=true,options={"default"=true})
     */
    private $isPage = true;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=TRUE, options={"default"="RESTAURANT_ACTION_TYPE"})
     */
    private $type = self::RESTAURANT_ACTION_TYPE;

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
     * @return Action
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
     * Set route
     *
     * @param string $route
     *
     * @return Action
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set params
     *
     * @param array $params
     *
     * @return Action
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        if ($this->params === null) {
            return [];
        } elseif (!is_array($this->params)) {
            return [];
        }

        return $this->params;
    }

    /**
     * Set hasExit
     *
     * @param boolean $hasExit
     *
     * @return Action
     */
    public function setHasExit($hasExit)
    {
        $this->hasExit = $hasExit;

        return $this;
    }

    /**
     * Get hasExist
     *
     * @return boolean
     */
    public function getHasExit()
    {
        return $this->hasExit;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add role
     *
     * @param \AppBundle\Security\Entity\Role $role
     *
     * @return Action
     */
    public function addRole(\AppBundle\Security\Entity\Role $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \AppBundle\Security\Entity\Role $role
     */
    public function removeRole(\AppBundle\Security\Entity\Role $role)
    {
        $this->roles->removeElement($role);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set isPage
     *
     * @param boolean $isPage
     *
     * @return Action
     */
    public function setIsPage($isPage)
    {
        $this->isPage = $isPage;

        return $this;
    }

    /**
     * Get isPage
     *
     * @return boolean
     */
    public function getIsPage()
    {
        return $this->isPage;
    }

    /**
     * Set type
     *
     * @param  string $type
     * @return Action
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
}
