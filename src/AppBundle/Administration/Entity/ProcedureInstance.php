<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:07
 */

namespace AppBundle\Administration\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProcedureInstance
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Administration\Repository\ProcedureInstanceRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProcedureInstance
{

    use TimestampableTrait;
    use ImportIdTrait;

    const PENDING = 'pending';
    const FINISH = 'finish';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="currentStep", type="smallint")
     */
    private $currentStep;

    /**
     * @var int
     *
     * @ORM\Column(name="sub_step",type="smallint",nullable=true)
     */
    private $subStep;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $user;

    /**
     * @var Procedure
     *
     * @ORM\ManyToOne(targetEntity="Procedure",inversedBy="instances")
     */
    private $procedure;


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
     * Set currentStep
     *
     * @param integer $currentStep
     *
     * @return ProcedureInstance
     */
    public function setCurrentStep($currentStep)
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    /**
     * Get currentStep
     *
     * @return int
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return ProcedureInstance
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
     * Set user
     *
     * @param \AppBundle\Staff\Entity\Employee $user
     *
     * @return ProcedureInstance
     */
    public function setUser(\AppBundle\Staff\Entity\Employee $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set procedure
     *
     * @param \AppBundle\Administration\Entity\Procedure $procedure
     *
     * @return ProcedureInstance
     */
    public function setProcedure(\AppBundle\Administration\Entity\Procedure $procedure = null)
    {
        $this->procedure = $procedure;

        return $this;
    }

    /**
     * Get procedure
     *
     * @return \AppBundle\Administration\Entity\Procedure
     */
    public function getProcedure()
    {
        return $this->procedure;
    }

    /**
     * @return bool
     */
    public function isFinalStep()
    {

        if ($this->currentStep === count($this->procedure->getSteps())) {
            return true;
        }

        return false;
    }

    /**
     * increment currentStep by 1
     */
    public function next()
    {
        $this->currentStep++;
    }

    /**
     * Set subStep
     *
     * @param int $subStep
     *
     * @return ProcedureInstance
     */
    public function setSubStep($subStep)
    {
        $this->subStep = $subStep;

        return $this;
    }

    /**
     * Get subStep
     *
     * @return int
     */
    public function getSubStep()
    {
        return $this->subStep;
    }
}
