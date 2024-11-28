<?php

namespace AppBundle\Security\Entity;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 *
 * @ORM\Table(name="`quick_user`")
 * @ORM\Entity(repositoryClass="AppBundle\Security\Repository\UserRepository")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"employee" = "AppBundle\Staff\Entity\Employee"})
 *
 * @UniqueEntity("username", message="unique.username")
 * @ORM\HasLifecycleCallbacks()
 */
abstract class User implements UserInterface, \Serializable
{
    static $ROLE_USER = "ROLE_USER";
    static $ROLE_CASHIER = "ROLE_CASHIER";
    static $ROLE_MANAGER = "ROLE_MANAGER";

    use TimestampableTrait;

    public function __construct()
    {
        $this->roles = [$this::$ROLE_USER];
        $this->eligibleRestaurants = new \Doctrine\Common\Collections\ArrayCollection();
        $this->employeeRoles = new ArrayCollection();
    }

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
     * @ORM\Column(name="username", type="string", length=255, unique=true)
     * @Assert\Valid
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;

    /**
     * @var ArrayCollection[Role]
     * @ManyToMany(targetEntity="AppBundle\Security\Entity\Role", mappedBy="users")
     */
    private $employeeRoles;


    /**
     * @var boolean
     * @ORM\Column(name="first_connexion", type="boolean", options={"default"=false})
     */
    protected $firstConnection = false;

    /**
     * @var string
     *
     * @ORM\Column(name="matricule", type="string", length=255, nullable=true)
     */
    private $matricule;

    /**
     * @var float
     *
     * @ORM\Column(name="time_work", type="float", nullable=true)
     */
    private $timeWork;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", options={"default"=false})
     */
    protected $active = false;

    /**
     * @var string
     * @ORM\Column(name="default_locale",type="string",nullable=false, options={"default"="fr"})
     */
    protected $defaultLocale = "fr";

    /**
     * @var Restaurant
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Restaurant", inversedBy="eligibleUsers")
     * @OrderBy({"code" = "ASC"})
     */
    private $eligibleRestaurants;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return Employee
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Employee
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }


    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return Employee
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @param $role
     * @return $this|bool
     */
    public function addRole($role)
    {
        if (!in_array($role, $this->getRoles())) {
            $this->roles[] = $role;
            return $this;
        } else {
            return false;
        }
    }

    /**
     * @param $role
     * @return $this|bool
     */
    public function removeRole($role)
    {
        if (in_array($role, $this->getRoles())) {
            unset($this->roles[array_search($role, $this->getRoles())]);
            return $this;
        } else {
            return false;
        }
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        $roles = array();
        foreach ($this->employeeRoles as $role) {
            /**
             * @var Role $role
             */
            $roles[] = $role->getLabel();
        }
        return $roles;
    }

    /**
     * @return array
     */
    public function getRolesAsObject()
    {
        return $this->roles;
    }


    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     *
     * @link   http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize([$this->id, $this->username, $this->password,]);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     *
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        list ($this->id, $this->username, $this->password) = unserialize($serialized);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return '';
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }


    /**
     * Add employeeRole
     *
     * @param \AppBundle\Security\Entity\Role $employeeRole
     *
     * @return User
     */
    public function addEmployeeRole(Role $employeeRole)
    {
        $this->employeeRoles[] = $employeeRole;
        return $this;
    }

    /**
     * Has employeeRole
     *
     * @param \AppBundle\Security\Entity\Role $employeeRole
     *
     * @return boolean
     */
    public function hasEmployeeRole(\AppBundle\Security\Entity\Role $employeeRole)
    {
        $roles = $this->getEmployeeRoles();
        if ($roles->contains($employeeRole)) {
            $exist = true;
        } else {
            $exist = false;
        }
        return $exist;
    }

    /**
     * Has employeeRole
     *
     * @param \AppBundle\Security\Entity\Role $role
     *
     * @return boolean
     */
    public function hasRole(\AppBundle\Security\Entity\Role $role)
    {
        $roles = $this->getEmployeeRoles();
        if ($roles->contains($role)) {
            $exist = true;
        } else {
            $exist = false;
        }
        return $exist;
    }

    /**
     * Remove employeeRole
     *
     * @param \AppBundle\Security\Entity\Role $employeeRole
     */
    public function removeEmployeeRole(\AppBundle\Security\Entity\Role $employeeRole)
    {
        $this->employeeRoles->removeElement($employeeRole);
    }

    /**
     * Get employeeRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmployeeRoles()
    {
        return $this->employeeRoles;
    }

    /**
     * Set firstConnection
     *
     * @param boolean $firstConnection
     *
     * @return User
     */
    public function setFirstConnection($firstConnection)
    {
        $this->firstConnection = $firstConnection;
        return $this;
    }

    /**
     * Get firstConnection
     *
     * @return boolean
     */
    public function getFirstConnection()
    {
        return $this->firstConnection;
    }

    /**
     * Set matricule
     *
     * @param string $matricule
     *
     * @return User
     */
    public function setMatricule($matricule)
    {
        $this->matricule = $matricule;
        return $this;
    }

    /**
     * Get matricule
     *
     * @return string
     */
    public function getMatricule()
    {
        return $this->matricule;
    }

    /**
     * Set timeWork
     *
     * @param float $timeWork
     *
     * @return $this
     */
    public function setTimeWork($timeWork)
    {
        $this->timeWork = $timeWork;
        return $this;
    }

    /**
     * Get timeWork
     *
     * @return float
     */
    public function getTimeWork()
    {
        return $this->timeWork;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
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
     * Set defaultLocale
     *
     * @param string $defaultLocale
     *
     * @return User
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
        return $this;
    }

    /**
     * Get defaultLocale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Add eligibleRestaurants
     *
     * @param  \AppBundle\Merchandise\Entity\Restaurant $eligibleRestaurants
     * @return User
     */
    public function addEligibleRestaurant(Restaurant $eligibleRestaurants)
    {
        $this->eligibleRestaurants[] = $eligibleRestaurants;
        return $this;
    }

    /**
     * Remove eligibleRestaurants
     *
     * @param \AppBundle\Merchandise\Entity\Restaurant $eligibleRestaurants
     */
    public function removeEligibleRestaurant(Restaurant $eligibleRestaurants)
    {
        $this->eligibleRestaurants->removeElement($eligibleRestaurants);
    }

    /**
     * Get eligibleRestaurants
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEligibleRestaurants()
    {
        return $this->eligibleRestaurants;
    }


    public function isSuperAdmin()
    {
        $superAdminRoles = $this->employeeRoles->filter(function ($role) {
            return in_array($role->getLabel(), Role::$SUPER_ADMINS_ROLES);
        });
        return $superAdminRoles->count() > 0;
    }


}
