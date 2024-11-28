<?php

namespace AppBundle\Security\Entity;

use AppBundle\Security\Entity\Translation\RoleTranslation;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Administration\Entity\Action;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinTable;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Role
 *
 * @ORM\Table(name="`role`")
 * @ORM\Entity(repositoryClass="AppBundle\Security\Repository\RoleRepository")
 * @Gedmo\TranslationEntity(class="AppBundle\Security\Entity\Translation\RoleTranslation")
 */
class Role
{

    use GlobalIdTrait;

    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_EMPLOYEE = 'ROLE_EMPLOYEE';
    const ROLE_MANAGER = 'ROLE_MANAGER';
    const ROLE_SUPERVISION = 'ROLE_SUPERVISION';
    const ROLE_COORDINATION = "ROLE_COORDINATION";

    const CENTRAL_ROLE_TYPE = 'CENTRAL_ROLE_TYPE';
    const RESTAURANT_ROLE_TYPE = 'RESTAURANT_ROLE_TYPE';


    //Super Admins Roles
    static $SUPER_ADMINS_ROLES = [
        'ROLE_IT',
        'ROLE_COORDINATION',
        'ROLE_AUDIT',
        'ROLE_ADMIN'
    ];

    /**
     * Role constructor.
     */
    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->translations = new ArrayCollection();
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
     * @Gedmo\Translatable
     */
    private $textLabel;

    /**
     * @var ArrayCollection[Rights]
     * @ManyToMany(targetEntity="Rights", mappedBy="roles")
     */
    private $rights;

    /**
     * @var ArrayCollection[Action]
     * @ManyToMany(targetEntity="AppBundle\Administration\Entity\Action", mappedBy="roles")
     */
    private $actions;

    /**
     * @var ArrayCollection[User]
     * @ManyToMany(targetEntity="User", inversedBy="employeeRoles")
     */
    private $users;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Security\Entity\Translation\RoleTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true, options={"default"="CENTRAL_ROLE_TYPE"})
     */
    private $type;

    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param RoleTranslation $t
     */
    public function addTranslation(RoleTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getTextLabelTranslation($locale)
    {
        $label = null;
        foreach ($this->translations as $translation) {
            /**
             * @var RoleTranslation $translation
             */
            if ($translation->getLocale() == $locale) {
                $label = $translation->getcontent();
            }
        }

        return $label;
    }

    public function addTextLabelTranslation($locale, $value)
    {
        $exist = false;
        foreach ($this->translations as $t) {
            /**
             * @var RoleTranslation $t
             */
            if ($t->getLocale() == $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new RoleTranslation($locale, 'textLabel', $value);
            $this->addTranslation($translation);
        }

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
     * Set label
     *
     * @param string $label
     *
     * @return Role
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * @param Rights[] $rights
     * @return Role
     */
    public function setRights($rights)
    {
        foreach ($rights as $t) {
            $this->rights->add($t);
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRightsKey()
    {
        $this->rights->map(
            function ($e) {
                return $e->getKey();
            }
        );
    }

    /**
     * Add right
     *
     * @param \AppBundle\Security\Entity\Rights $right
     *
     * @return Role
     */
    public function addRight(\AppBundle\Security\Entity\Rights $right)
    {
        $this->rights[] = $right;

        return $this;
    }

    /**
     * Remove right
     *
     * @param \AppBundle\Security\Entity\Rights $right
     */
    public function removeRight(\AppBundle\Security\Entity\Rights $right)
    {
        $this->rights->removeElement($right);
    }

    /**
     * Has right
     *
     * @param \AppBundle\Security\Entity\Rights $right
     *
     * @return boolean
     */
    public function hasRight(\AppBundle\Security\Entity\Rights $right)
    {
        $rights = $this->getRights();
        if ($rights->contains($right)) {
            $exist = true;
        } else {
            $exist = false;
        }

        return $exist;
    }

    /**
     * Add user
     *
     * @param \AppBundle\Security\Entity\User $user
     *
     * @return Role
     */
    public function addUser(\AppBundle\Security\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param \AppBundle\Security\Entity\User $user
     */
    public function removeUser(\AppBundle\Security\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set textLabel
     *
     * @param string $textLabel
     *
     * @return Role
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

    /**
     * Add action
     *
     * @param \AppBundle\Administration\Entity\Action $action
     *
     * @return Role
     */
    public function addAction(\AppBundle\Administration\Entity\Action $action)
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * Remove action
     *
     * @param \AppBundle\Administration\Entity\Action $action
     */
    public function removeAction(\AppBundle\Administration\Entity\Action $action)
    {
        $this->actions->removeElement($action);
    }

    /**
     * Get actions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActions()
    {
        return $this->actions;
    }


    /**
     * Set type
     *
     * @param string $type
     *
     * @return Role
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

    /**
     * Remove translation
     *
     * @param \AppBundle\Security\Entity\Translation\RoleTranslation $translation
     */
    public function removeTranslation(\AppBundle\Security\Entity\Translation\RoleTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * @return array: the list of super admin
     */
    public function getStaticSuperAdminRoles()
    {
        return self::$SUPER_ADMINS_ROLES;
    }
}
