<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 10/03/2016
 * Time: 17:24
 */

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * SoldingCanal
 *
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\SoldingCanalRepository")
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 */
class SoldingCanal
{

    const DESTINATION = "destination";
    const ORIGIN = "origin";

    const TAKE_AWAY = 'TakeOut';
    const TAKE_OUT = 'TAKE OUT';
    const POS = 'POS';
    const origin_e_ordering = 'MyQuick';
    const KIOSK = 'KIOSK';
    const DRIVE = 'DriveThru';
    const EATIN = 'EatIn';
    const TAKE_IN = 'TAKE IN';
    const DELIVERY= 'Delivery';
    const ALL_CANALS = 'allcanals';
    const e_ordering_in = 'MyQuickEatIn';
    const e_ordering_out = 'MyQuickTakeout';
    const E_ORDERING= 'MyQuickEatIn';
    //nouvelles destinations
    const MQDRIVE= 'MQDrive';
    const MQCURBSIDE= 'MQCurbside';

    const ATOUBEREATS= 'ATOUberEats';
    const ATODELIVEROO= 'ATODeliveroo';
    const ATOTAKEAWAY= 'ATOTakeAway';
    const ATOHELLOUGO= 'ATOHelloUgo';
    const ATOEASY2EAT= 'ATOEasy2Eat';
    const ATOGOOSTY= 'ATOGoosty';
    const ATOWOLT= 'ATOWolt';

    const CANAL_ALL_CANALS = 3;
    const ON_SITE_CANAL = 4;
    const E_ORDERING_IN_CANAL = 11;

    use TimestampableTrait;
    use GlobalIdTrait;

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
     * @ORM\Column(name="label", type="string")
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", nullable=TRUE)
     */
    private $type;

    /**
     * @ORM\Column(name="wynd_mapping_column", type="text", nullable=TRUE)
     */
    private $wyndMppingColumn;

    /**
     * @var boolean
     * @ORM\Column(name="default_canal", type="boolean", options={"default"=false}, nullable=TRUE)
     */
    private $default;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return SoldingCanal
     */
    public function setId($id)
    {
        $this->id = intval($id);

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
     * @return SoldingCanal
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
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
     * @return SoldingCanal
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $default
     * @return SoldingCanal
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get the value of wyndMppingColumn.
     */
    public function getWyndMppingColumn()
    {
        if ($this->wyndMppingColumn !== null) {
            return $this->wyndMppingColumn;
        } else {
            return '';
        }
    }

    /**
     * Set data
     * @return SoldingCanal
     */
    public function setWyndMppingColumn($wyndMppingColumn)
    {
        if ($wyndMppingColumn !== null) {
            $this->wyndMppingColumn = $wyndMppingColumn;
        } else {
            $this->wyndMppingColumn = '';
        }
        return $this;
    }

    /**
     * Get default
     *
     * @return boolean
     */
    public function getDefault()
    {
        return $this->default;
    }
}
