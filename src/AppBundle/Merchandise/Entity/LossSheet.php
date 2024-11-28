<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * LossSheet
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\LossSheetRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class LossSheet
{

    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    //use SynchronizedFlagTrait;


    /**
     * LossSheet Type
     */
    const ARTICLE = 'article';
    const FINALPRODUCT = 'finalProduct';

    // Depracated
    const SET = 'set';
    const DRAFT = 'draft';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\LossLine", mappedBy="lossSheet", cascade={"persist", "remove"})
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $lossLines;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee", inversedBy="lossSheet")
     */
    private $employee;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;


    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=10, nullable=true)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="entry", type="datetime", nullable=true)
     */
    private $entryDate;

    /**
     * @var SheetModel
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SheetModel")
     */
    private $model;

    /**
     * @var string
     * @ORM\Column(name="sheet_model_label", type="string", nullable= TRUE)
     */
    private $sheetModelLabel;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lossLines = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @return integer
     */
    public function setId($id)
    {
        return $this->id = $id;
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
     * Set type
     *
     * @param string $type
     *
     * @return LossSheet
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
     * Add lossLine
     *
     * @param \AppBundle\Merchandise\Entity\LossLine $lossLine
     *
     * @return LossSheet
     */
    public function addLossLines(\AppBundle\Merchandise\Entity\LossLine $lossLine)
    {
        $this->lossLines->add($lossLine);

        return $this;
    }

    /**
     * Remove lossLine
     *
     * @param \AppBundle\Merchandise\Entity\LossLine $lossLine
     */
    public function removeLossLines(\AppBundle\Merchandise\Entity\LossLine $lossLine)
    {
        $this->lossLines->removeElement($lossLine);
    }

    /**
     * Add lossLine
     *
     * @param Mixed $lossLine
     *
     * @return LossSheet
     */
    public function addLossLine($lossLine)
    {
        if ($lossLine instanceof LossLine) {
            $lossLine->setLossSheet($this);
        }
        $this->lossLines->add($lossLine);

        return $this;
    }

    /**
     * Remove lossLine
     *
     * @param \AppBundle\Merchandise\Entity\LossLine $lossLine
     */
    public function removeLossLine(\AppBundle\Merchandise\Entity\LossLine $lossLine)
    {
        $this->lossLines->removeElement($lossLine);
    }

    /**
     * Get lossLine
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLossLines()
    {
        return $this->lossLines;
    }

    /**
     * @param ArrayCollection $lossLine
     * @return $this
     */
    public function setLossLines(ArrayCollection $lossLines)
    {

        foreach ($lossLines as $l) {
            $l->setLossSheet($this);
        }

        $this->lossLines = $lossLines;

        return $this;
    }

    /**
     * Set employee
     *
     * @param \AppBundle\Staff\Entity\Employee $employee
     *
     * @return LossSheet
     */
    public function setEmployee(\AppBundle\Staff\Entity\Employee $employee = null)
    {
        $this->employee = $employee;

        return $this;
    }

    /**
     * Get employee
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getEmployee()
    {
        return $this->employee;
    }


    /**
     * Set entryDate
     *
     * @param \DateTime $entryDate
     *
     * @return LossSheet
     */
    public function setEntryDate($entryDate)
    {
        $this->entryDate = $entryDate;

        return $this;
    }

    /**
     * @param null $format
     * @return \DateTime
     */
    public function getEntryDate($format = null)
    {
        if (!is_null($format)) {
            return $this->entryDate->format($format);
        }

        return $this->entryDate;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return LossSheet
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
     * @return SheetModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param SheetModel $model
     * @return LossSheet
     */
    public function setModel($model)
    {
        $this->model = $model;
        if (!is_null($model)) {
            $this->setSheetModelLabel($model->getLabel());
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getSheetModelLabel()
    {
        return $this->sheetModelLabel;
    }

    /**
     * @param string $sheetModelLabel
     * @return LossSheet
     */
    public function setSheetModelLabel($sheetModelLabel)
    {
        $this->sheetModelLabel = $sheetModelLabel;

        return $this;
    }
}
