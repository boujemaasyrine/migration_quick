<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/05/2016
 * Time: 16:48
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\Translation\PaymentMethodTranslation;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PaymentMethod
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\PaymentMethodRepository")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\TranslationEntity(class="AppBundle\Financial\Entity\Translation\PaymentMethodTranslation")
 */
class PaymentMethod implements \Serializable
{

    use GlobalIdTrait;

    // Payment Method
    const REAL_CASH_TYPE = 'REAL_CASH_VALUE';
    const TICKET_RESTAURANT_TYPE = 'TICKET_RESTAURANT_VALUES';
    const CHECK_QUICK_TYPE = 'CHECK_QUICK_VALUES';
    const FOREIGN_CURRENCY_TYPE = 'FOREIGN_CURRENCY_TYPE';
    const BANK_CARD_TYPE = 'BANK_CARD_VALUES';

    // Electronic Card Type
    const TICKET_RESTAURANT_PAPER = 'TICKET_RESTAURANT_PAPER';
    const TICKET_RESTAURANT_ELECTRONIC = 'TICKET_RESTAURANT_ELECTRONIC';

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
     * @ORM\Column(name="parameter_type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="value_label", type="string", nullable= TRUE)
     * @Gedmo\Translatable
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(name="value", type="text", nullable= TRUE)
     */
    private $value;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", nullable= TRUE)
     */
    private $active = false;


    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Financial\Entity\Translation\PaymentMethodTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;


    /**
     * @var Restaurant
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Restaurant",mappedBy="paymentMethods")
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
     * Set type
     *
     * @param  string $type
     * @return PaymentMethod
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
     * Set label
     *
     * @param  string $label
     * @return PaymentMethod
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
     * @param Mixed $value
     * @return PaymentMethod
     */
    public function setValue($value)
    {
        if (is_array($value)) {
            $value = serialize($value);
        }
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if (Utilities::is_serialized($this->value)) {
            return unserialize($this->value);
        } else {
            return $this->value;
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->restaurants = new ArrayCollection();
    }


    public function addTranslation(PaymentMethodTranslation $translations)
    {
        if (!$this->translations->contains($translations)) {
            $this->translations[] = $translations;
            $translations->setObject($this);
        }
    }


    /**
     * @param $locale
     * @param $value
     * @return $this
     */
    public function addLabelTranslation($locale, $value)
    {
        $exist = false;
        foreach ($this->translations as $t) {
            /**
             * @var PaymentMethodTranslation $t
             */
            if ($t->getLocale() == $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new PaymentMethodTranslation($locale, 'label', $value);
            $this->addTranslation($translation);
        }

        return $this;
    }


    /**
     * @param $locale
     * @return null|string
     */
    public function getLabelTranslation($locale)
    {
        $label = null;
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() == $locale) {
                $label = $translation->getcontent();
            }
        }

        return $label;
    }


    /**
     * @param PaymentMethodTranslation $translations
     */
    public function removeTranslation(PaymentMethodTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }


    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add restaurants
     *
     * @param  \AppBundle\Merchandise\Entity\Restaurant $restaurants
     * @return PaymentMethod
     */
    public function addRestaurant(Restaurant $restaurants)
    {
        $this->restaurants[] = $restaurants;

        return $this;
    }


    /**
     * Remove a restaurant
     *
     * @param \AppBundle\Merchandise\Entity\Restaurant $restaurants
     */
    public function removeRestaurant(Restaurant $restaurants)
    {
        $this->restaurants->removeElement($restaurants);
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


    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return PaymentMethod
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }


    public function serialize()
    {
        return [
            'parameterType' => $this->getType(),
            'valueLabel' => $this->getLabel(),
            'value' => $this->getValue(),
            'globalID' => $this->getGlobalId(),
        ];
    }

    public function unserialize($serialized)
    {
    }
}
