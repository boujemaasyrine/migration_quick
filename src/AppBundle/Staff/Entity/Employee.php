<?php

namespace AppBundle\Staff\Entity;

use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Financial\Entity\Withdrawal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Employee
 *
 * @ORM\Entity(repositoryClass="AppBundle\Staff\Repository\EmployeeRepository")
 * @UniqueEntity("socialId",message="unique.socialId")
 */
class Employee extends User
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @var LossSheet
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\LossSheet", mappedBy="employee")
     */
    private $lossSheet;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true, unique=false)
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(name="social_id", type="string", length=255, nullable=true)
     */
    private $socialId;

    /**
     * @var string
     * @ORM\Column(name="first_name",type="string",length=100,nullable=true)
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(name="last_name",type="string",length=100,nullable=true)
     */
    private $lastName;

    /**
     * @var Withdrawal
     * @ORM\OneToMany(targetEntity="AppBundle\Financial\Entity\Withdrawal",mappedBy="member")
     */
    private $withdrawals;


    /**
     * @var integer
     * @ORM\Column(name="wynd_id",type="integer",nullable=true)
     */
    private $wyndId;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted;

    /**
     * @var boolean
     * @ORM\Column(name="from_wynd", type="boolean", options={"default"=true})
     */
    protected $fromWynd;

    /**
     * @var boolean
     * @ORM\Column(name="from_central", type="boolean", options={"default"=false}, nullable=true)
     */
    protected $fromCentral = false;


    /**
     * @var
     * @ORM\Column(name="global_employee_id",type="integer",nullable=true)
     */
    private $globalEmployeeID;

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Employee
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
     * @return string
     */
    public function getSocialId()
    {
        return $this->socialId;
    }

    /**
     * @param $socialId
     * @return $this
     */
    public function setSocialId($socialId)
    {
        $this->socialId = $socialId;

        return $this;
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
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Employee
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Employee
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }


    /**
     * Add lossSheet
     *
     * @param \AppBundle\Merchandise\Entity\LossSheet $lossSheet
     *
     * @return Employee
     */
    public function addLossSheet(\AppBundle\Merchandise\Entity\LossSheet $lossSheet)
    {
        $this->lossSheet[] = $lossSheet;

        return $this;
    }

    /**
     * Remove lossSheet
     *
     * @param \AppBundle\Merchandise\Entity\LossSheet $lossSheet
     */
    public function removeLossSheet(\AppBundle\Merchandise\Entity\LossSheet $lossSheet)
    {
        $this->lossSheet->removeElement($lossSheet);
    }

    /**
     * Get lossSheet
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLossSheet()
    {
        return $this->lossSheet;
    }

    /**
     * Get employee full name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * Add withdrawal
     *
     * @param \AppBundle\Financial\Entity\Withdrawal $withdrawal
     *
     * @return Employee
     */
    public function addWithdrawal(\AppBundle\Financial\Entity\Withdrawal $withdrawal)
    {
        $this->withdrawals[] = $withdrawal;

        return $this;
    }

    /**
     * Remove withdrawal
     *
     * @param \AppBundle\Financial\Entity\Withdrawal $withdrawal
     */
    public function removeWithdrawal(\AppBundle\Financial\Entity\Withdrawal $withdrawal)
    {
        $this->withdrawals->removeElement($withdrawal);
    }

    /**
     * Get withdrawals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWithdrawals()
    {
        return $this->withdrawals;
    }

    /**
     * @return string
     */
    public function getWyndId()
    {
        return $this->wyndId;
    }

    /**
     * @param string $wyndId
     * @return Employee
     */
    public function setWyndId($wyndId)
    {
        $this->wyndId = $wyndId;

        return $this;
    }

    public function __toString()
    {
        return $this->getWyndId()." - ".$this->getFirstName()." ".$this->getLastName();
    }

    /**
     * Set globalEmployeeID
     *
     * @param integer $globalEmployeeID
     *
     * @return Employee
     */
    public function setGlobalEmployeeID($globalEmployeeID)
    {
        $this->globalEmployeeID = $globalEmployeeID;

        return $this;
    }

    /**
     * Get globalEmployeeID
     *
     * @return integer
     */
    public function getGlobalEmployeeID()
    {
        return $this->globalEmployeeID;
    }

    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return Employee
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set fromWynd
     *
     * @param boolean $fromWynd
     *
     * @return Employee
     */
    public function setFromWynd($fromWynd)
    {
        $this->fromWynd = $fromWynd;

        return $this;
    }

    /**
     * Get fromWynd
     *
     * @return boolean
     */
    public function getFromWynd()
    {
        return $this->fromWynd;
    }

    public function hasCentralRole()
    {
        foreach ($this->getEmployeeRoles() as $r) {
            if ($r->getType() == Role::CENTRAL_ROLE_TYPE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set fromCentral
     *
     * @param boolean $fromCentral
     *
     * @return Employee
     */
    public function setFromCentral($fromCentral)
    {
        $this->fromCentral = $fromCentral;

        return $this;
    }

    /**
     * Get fromCentral
     *
     * @return boolean
     */
    public function getFromCentral()
    {
        return $this->fromCentral;
    }

}
