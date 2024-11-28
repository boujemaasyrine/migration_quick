<?php

namespace AppBundle\Security\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;

/**
 * Rights
 *
 * @ORM\Table(name="`rights`")
 * @ORM\Entity()
 */
class Rights
{
    /**
     * Role constructor.
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
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
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="text_label", type="string", length=255, nullable=true)
     */
    private $textLabel;

    /**
     * @var ArrayCollection[Rights]
     * @ManyToMany(targetEntity="Role", inversedBy="rights")
     */
    private $roles;


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
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }


    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param Role[] $roles
     * @return Rights
     */
    public function setRoleRights($roles)
    {
        foreach ($roles as $role) {
            $this->roles->add($role);
        }

        return $this;
    }

    /**
     * Add role
     *
     * @param \AppBundle\Security\Entity\Role $role
     *
     * @return Role
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
     * Set textLabel
     *
     * @param string $textLabel
     *
     * @return Rights
     */
    public function setTextLabel($textLabel)
    {
        $this->textLabel = $textLabel;

        return $this;
    }

    /**
     * Get textLabel
     *
     * @return string
     */
    public function getTextLabel()
    {
        return $this->textLabel;
    }
}
