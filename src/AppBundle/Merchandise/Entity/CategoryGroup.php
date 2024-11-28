<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Merchandise\Entity\Translation\CategoryGroupTranslation;
use AppBundle\Merchandise\Entity\Translation\ProductCategoriesTranslation;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CategoryGroup
 *
 * @ORM\Table(name="category_group")
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\CategoryGroupRepository")
 * @Gedmo\TranslationEntity(class="AppBundle\Merchandise\Entity\Translation\CategoryGroupTranslation")
 */
class CategoryGroup
{
    use GlobalIdTrait;

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
     * @ORM\Column(name="name", type="string", length=20)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var boolean
     * @ORM\Column(name="active",type="boolean")
     */
    private $active;

    /**
     * @var boolean
     * @ORM\Column(type="boolean",options={"default" = true})
     */
    private $isFoodCost = true;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Merchandise\Entity\Translation\CategoryGroupTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();

        $this->addNameTranslation('fr', '');
        $this->addNameTranslation('nl', '');
    }


    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param CategoryGroupTranslation $t
     */
    public function addTranslation(CategoryGroupTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getNameTranslation($locale)
    {
        $label = null;
        foreach ($this->translations as $translation) {
            /**
             * @var CategoryGroupTranslation $translation
             */
            if ($translation->getLocale() == $locale) {
                $label = $translation->getcontent();
            }
        }

        return $label;
    }

    public function addNameTranslation($locale, $value)
    {
        $exist = false;
        foreach ($this->translations as $t) {
            if ($t->getLocale() == $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new CategoryGroupTranslation($locale, 'name', $value);
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
     * Set name
     *
     * @param  string $name
     * @return CategoryGroup
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
     * Set active
     *
     * @param  boolean $active
     * @return CategoryGroup
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
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return bool
     */
    public function isFoodCost()
    {
        return $this->isFoodCost;
    }

    /**
     * @param bool $isFoodCost
     */
    public function setIsFoodCost($isFoodCost)
    {
        $this->isFoodCost = $isFoodCost;
    }
}
