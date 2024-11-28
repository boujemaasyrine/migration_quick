<?php

namespace AppBundle\General\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * ImportProgression
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ImportProgression
{
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
     * @ORM\Column(name="filename", type="string", length=255,nullable=true)
     */
    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=50,nullable=true)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDateTime", type="datetime",nullable=true)
     */
    private $startDateTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDateTime", type="datetime",nullable=true)
     */
    private $endDateTime;

    /**
     * @var float
     *
     * @ORM\Column(name="progress", type="float",nullable=true)
     */
    private $progress;

    /**
     * @var string
     *
     * @ORM\Column(name="nature", type="string", length=50,nullable=true)
     */
    private $nature;

    /**
     * @var $totalElements
     * @ORM\Column(name="total_elements",type="integer",nullable=true)
     */
    private $totalElements;

    /**
     * @var $totalElements
     * @ORM\Column(name="proceed_elements",type="integer",nullable=true)
     */
    private $proceedElements;

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
     * Set filename
     *
     * @param string $filename
     *
     * @return ImportProgression
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return ImportProgression
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set startDateTime
     *
     * @param \DateTime $startDateTime
     *
     * @return ImportProgression
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    /**
     * Get startDateTime
     *
     * @return \DateTime
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Set endDateTime
     *
     * @param \DateTime $endDateTime
     *
     * @return ImportProgression
     */
    public function setEndDateTime($endDateTime)
    {
        $this->endDateTime = $endDateTime;

        return $this;
    }

    /**
     * Get endDateTime
     *
     * @return \DateTime
     */
    public function getEndDateTime()
    {
        return $this->endDateTime;
    }

    /**
     * Set progress
     *
     * @param float $progress
     *
     * @return ImportProgression
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return float
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set nature
     *
     * @param string $nature
     *
     * @return ImportProgression
     */
    public function setNature($nature)
    {
        $this->nature = $nature;

        return $this;
    }

    /**
     * Get nature
     *
     * @return string
     */
    public function getNature()
    {
        return $this->nature;
    }

    /**
     * Set totalElements
     *
     * @param integer $totalElements
     *
     * @return ImportProgression
     */
    public function setTotalElements($totalElements)
    {
        $this->totalElements = $totalElements;

        return $this;
    }

    /**
     * Get totalElements
     *
     * @return integer
     */
    public function getTotalElements()
    {
        return $this->totalElements;
    }

    /**
     * Set proceedElements
     *
     * @param integer $proceedElements
     *
     * @return ImportProgression
     */
    public function setProceedElements($proceedElements)
    {
        $this->proceedElements = $proceedElements;

        return $this;
    }

    /**
     * Get proceedElements
     *
     * @return integer
     */
    public function getProceedElements()
    {
        return $this->proceedElements;
    }

    public function incrementProgression($i = 1)
    {
        $this->proceedElements += $i;
        $this->progress = ($this->proceedElements / $this->totalElements) * 100;
    }

    public function incrementPercentProgression($prog)
    {
        $newProgress = $this->getProgress() + $prog;
        if ($newProgress >= 100) {
            $this->setProgress(100);
        } else {
            $this->setProgress($newProgress);
        }
    }
}
