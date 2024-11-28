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
trait IdTrait
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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \Datetime $id
     * @return TimestampableTrait
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
