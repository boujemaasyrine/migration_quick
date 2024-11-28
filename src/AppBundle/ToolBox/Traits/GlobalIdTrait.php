<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/11/2015
 * Time: 10:38
 */

namespace AppBundle\ToolBox\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class IdTrait
 *
 * @package AppBundle\Traits
 */
trait GlobalIdTrait
{

    /**
     * @var integer
     * @ORM\Column(name="global_id", type="integer",nullable=true)
     */
    private $globalId;

    /**
     * @return int
     */
    public function getGlobalId()
    {
        return $this->globalId;
    }

    /**
     * @param int $globalId
     * @return $this
     */
    public function setGlobalId($globalId)
    {
        $this->globalId = $globalId;

        return $this;
    }
}
