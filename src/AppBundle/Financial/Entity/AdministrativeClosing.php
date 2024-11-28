<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/05/2016
 * Time: 15:00
 */

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AdministrativeClosing
 *
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\AdministrativeClosingRepository")
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks()
 */
class AdministrativeClosing
{
    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    //use SynchronizedFlagTrait;

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
     * @var
     *
     * @ORM\Column(name="comparable",type="boolean")
     */
    private $comparable = true;

    /**
     * @var $comment
     *
     * @ORM\Column(name="comment",type="text",nullable=true)
     */
    private $comment;

    /**
     * @var float
     *
     * @ORM\Column(name="credit_amount", type="float", nullable=true)
     */
    private $creditAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="ca_brut_ttcrapport_z", type="float", nullable=true)
     */
    private $caBrutTTCRapportZ;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return AdministrativeClosing
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
     * Set comparable
     *
     * @param boolean $comparable
     *
     * @return AdministrativeClosing
     */
    public function setComparable($comparable)
    {
        $this->comparable = $comparable;

        return $this;
    }

    /**
     * Get comparable
     *
     * @return boolean
     */
    public function getComparable()
    {
        return $this->comparable;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return AdministrativeClosing
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set creditAmount
     *
     * @param float $creditAmount
     *
     * @return AdministrativeClosing
     */
    public function setCreditAmount($creditAmount)
    {
        $this->creditAmount = $creditAmount;

        return $this;
    }

    /**
     * Get creditAmount
     *
     * @return float
     */
    public function getCreditAmount()
    {
        return $this->creditAmount;
    }

    /**
     * Set caBrutTTCRapportZ
     *
     * @param float $caBrutTTCRapportZ
     *
     * @return AdministrativeClosing
     */
    public function setCaBrutTTCRapportZ($caBrutTTCRapportZ)
    {
        $this->caBrutTTCRapportZ = $caBrutTTCRapportZ;

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
}
