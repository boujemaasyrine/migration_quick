<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:07
 */

namespace AppBundle\Administration\Entity;

use AppBundle\Administration\Entity\Translation\ParameterTranslation;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Parameter
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Administration\Repository\ParameterRepository")
 * @ORM\HasLifecycleCallbacks
 *
 * @Gedmo\TranslationEntity(class="AppBundle\Administration\Entity\Translation\ParameterTranslation")
 */
class Parameter
{
    use TimestampableTrait;
    use GlobalIdTrait;
    use OriginRestaurantTrait; //add originRestaurant attribut

    const TYPE = "type";

    const START_DAY_CASHBOX_FUNDS_TYPE = 'START_DAY_CASHBOX_FUNDS_TYPE';
    const START_DAY_CASHBOX_FUNDS_DEFAULT = "250";

    const EXPENSE_LABELS_TYPE = 'EXPENSE_LABELS_TYPE';
    const RECIPE_LABELS_TYPE = 'RECIPE_LABELS_TYPE';
    const EFT_ACTIVATED_TYPE = 'EFT_ACTIVATED_TYPE';

    const INITIAL_CREDIT_TYPE = 'INITIAL_CREDIT_TYPE';

    // Cashbox count
    const REAL_CASH_TYPE = 'REAL_CASH_VALUE';
    const TICKET_RESTAURANT_TYPE = 'TICKET_RESTAURANT_VALUES';
    const CHECK_QUICK_TYPE = 'CHECK_QUICK_VALUES';
    const FOREIGN_CURRENCY_TYPE = 'FOREIGN_CURRENCY_TYPE';
    const BANK_CARD_TYPE = 'BANK_CARD_VALUES';


    const ERROR_COUNT_TYPE = 'ERROR_COUNT_TYPE';
    const CASH_PAYMENT_TYPE = 'CASH_PAYMENT_TYPE';

    const LAST_CLOSURED_DAY = 'LAST_CLOSURED_DAY';

    const RESTAURANT_OPENING_HOUR = 'RESTAURANT_OPENING_HOUR';
    const RESTAURANT_OPENING_HOUR_DEFAULT = '7';
    const RESTAURANT_CLOSING_HOUR = 'RESTAURANT_CLOSING_HOUR';
    const RESTAURANT_CLOSING_HOUR_DEFAULT = '1';
    const RESTAURANT_EMAIL = 'RESTAURANT_EMAIL';
    const RESTAURANT_EMAIL_DEFAULT = 'quick@quick.fr';
    const RESTAURANT_ADDITIONAL_EMAILS = 'RESTAURANT_ADDITIONAL_EMAILS';

    const LAST_HOUR_OF_THE_DAY = 23;

    //Chest count
    const NUMBER_OF_CASHBOXES = "NUMBER_OF_CASHBOXES";
    const NUMBER_OF_CASHBOXES_DEFAULT = "2";

    //Exchange fund
    const ROLS = "ROLS";
    const BAG = "BAG";
    const BILL = "BILL";

    const EXCHANGE_TYPE = "EXCHANGE_TYPE";

    const BAG_CONTENT = "bag_content";
    const ROL_CONTENT = "rol_content";
    const PIECE_VALUE = "piece_value";
    const CASH="CASH";

    //Var in url
    const EXPENSE = 'expense';
    const RECIPE = 'recipe';

    // Crons
    const TICKET_UPLOAD = 'ticket_upload';
    const MOVEMENT_UPLOAD = 'mvmt_upload';
    const EXECUTE_SYNC = 'execute_sync';

    //Ping
    const SUPERVISION_ACCESSIBILITY = 'supervision_accessibility';
    const ACCESSIBLE = 'accessible';
    const INACCESSIBLE = 'inaccessible';

    const USERS_URL_TYPE = "USERS_URL_TYPE";
    const WITHDRAWAL_URL_TYPE = "withdrawal_url_type";
    const ORDERS_URL_TYPE = "ORDERS_URL_TYPE";
    const SECRET_KEY = "SECRET_KEY";
    const WYND_ACTIVE = 'WYND_ACTIVE';
    const WYND_USER = 'WYND_USER';
    const OPTIKITCHEN_PATH = 'OPTIKITCHEN_PATH';


    // If true, cash cashbox count will display global input. If false details entry will be initially filled in.
    const GLOBAL_CASH_CASHBOX_COUNT_PARAMETER = 'GLOBAL_CASH_CASHBOX_COUNT_PARAMETER';
    const PORTION_CONTROL_SELECTED_CATEGORIES = 'PORTION_CONTROL_SELECTED_CATEGORIES';
    const PORTION_CONTROL_THRESHOLD = 'PORTION_CONTROL_THRESHOLD';


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
     * @ORM\Column(name="parameter_type", type="string")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="value_label", type="string", nullable= TRUE)
     *
     * @Gedmo\Translatable
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable= TRUE)
     */
    private $value;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Administration\Entity\Translation\ParameterTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    /**
     * @var bool
     *
     * @ORM\Column(name="untouchable",type="boolean", nullable= TRUE, options={"default"=false})
     */
    private $untouchable;


    /**
     * Parameter constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        //        $trans = [];
        //        foreach ($this->translations as $translation) {
        //            /**
        //             * @var ParameterTranslation
        //             */
        //            $trans[$translation->getLocale()] = $translation;
        //        }
        //        ksort($trans);

        return $this->translations;
    }

    /**
     * @param ParameterTranslation $t
     */
    public function addTranslation(ParameterTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * @param $locale
     * @param $value
     *
     * @return $this
     */
    public function addLabelTranslation($locale, $value)
    {
        $exist = false;
        foreach ($this->translations as $t) {
            /**
             * @var ParameterTranslation $t
             */
            if ($t->getLocale() === $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new ParameterTranslation($locale, 'label', $value);
            $this->addTranslation($translation);
        }

        return $this;
    }

    /**
     * @param $locale
     *
     * @return null|string
     */
    public function getLabelTranslation($locale)
    {
        $label = null;
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                $label = $translation->getcontent();
            }
        }

        return $label;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Parameter
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     *
     * @return Parameter
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if (Utilities::is_serialized($this->value)) {
            return unserialize($this->value);
        }
        return $this->value;

    }

    /**
     * @param Mixed $value
     *
     * @return Parameter
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
    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Parameter
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }


    /**
     * Remove translation
     *
     * @param \AppBundle\Administration\Entity\Translation\ParameterTranslation $translation
     */
    public function removeTranslation(ParameterTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Set untouchable
     *
     * @param  boolean $untouchable
     *
     * @return Parameter
     */
    public function setUntouchable($untouchable)
    {
        $this->untouchable = $untouchable;

        return $this;
    }

    /**
     * Get untouchable
     *
     * @return boolean
     */
    public function getUntouchable()
    {
        return $this->untouchable;
    }
}
