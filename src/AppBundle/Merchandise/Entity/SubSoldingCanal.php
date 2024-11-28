<?php

 namespace AppBundle\Merchandise\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubSoldingCanal
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class SubSoldingCanal
{


    const NONE_REUSABLE_CONFIGURATION = 'Configuration non reutilisable';
    const REUSABLE_CONFIGURATION = 'Configuration reutilisable';

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
     * @ORM\Column(name="label", type="string", length=255)
     */
    private $label;


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
     * Set label
     *
     * @param string $label
     *
     * @return SubSoldingCanal
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

    public function __toString()
    {
        return $this->getLabel();
    }
}

