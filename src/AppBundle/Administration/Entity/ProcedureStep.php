<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:07
 */


namespace AppBundle\Administration\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProcedureStep
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ProcedureStep
{
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
     * @ORM\Column(name="orderr", type="integer")
     */
    private $order;

    /**
     * @var bool
     *
     * @ORM\Column(name="deletable",type="boolean",nullable=true)
     */
    private $deletable = true;

    /**
     * @var Action
     *
     * @ORM\ManyToOne(targetEntity="Action")
     */
    private $action;

    /**
     * @var Procedure
     *
     * @ORM\ManyToOne(targetEntity="Procedure",inversedBy="steps")
     */
    private $procedure;

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
     * Set order
     *
     * @param int $order
     *
     * @return ProcedureStep
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set deletable
     *
     * @param bool $deletable
     *
     * @return ProcedureStep
     */
    public function setDeletable($deletable)
    {
        $this->deletable = $deletable;

        return $this;
    }

    /**
     * Get deletable
     *
     * @return bool
     */
    public function getDeletable()
    {
        return $this->deletable;
    }

    /**
     * Set action
     *
     * @param \AppBundle\Administration\Entity\Action $action
     *
     * @return ProcedureStep
     */
    public function setAction(\AppBundle\Administration\Entity\Action $action = null)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return \AppBundle\Administration\Entity\Action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set procedure
     *
     * @param \AppBundle\Administration\Entity\Procedure $procedure
     *
     * @return ProcedureStep
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
}
