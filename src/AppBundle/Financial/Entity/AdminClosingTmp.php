<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 06/06/2016
 * Time: 09:13
 */

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AdminClosingTmp
 *
 *
 * @Table()
 *
 * @Entity()
 */
class AdminClosingTmp
{
    use OriginRestaurantTrait;
    use ImportIdTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="ca_brut_ttcrapport_z",type="float",nullable=true)
     */
    private $caBrutTTCRapportZ;


    /**
     * @ORM\Column(name="deposed",type="boolean",nullable=true)
     */
    private $deposed;


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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return AdminClosingTmp
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set caBrutTTCRapportZ
     *
     * @param float $caBrutTTCRapportZ
     *
     * @return AdminClosingTmp
     */
    public function setCaBrutTTCRapportZ($caBrutTTCRapportZ)
    {
        $this->caBrutTTCRapportZ = str_replace(',', '.', $caBrutTTCRapportZ);

        return $this;
    }

    /**
     * Get caBrutTTCRapportZ
     *
     * @return float
     */
    public function getCaBrutTTCRapportZ()
    {
        return $this->caBrutTTCRapportZ;
    }

    /**
     * @return mixed
     */
    public function getDeposed()
    {
        return $this->deposed;
    }

    /**
     * @param mixed $deposed
     */
    public function setDeposed($deposed)
    {
        $this->deposed = $deposed;
    }


}
