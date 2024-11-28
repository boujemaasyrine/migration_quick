<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 10:38
 */

namespace AppBundle\ToolBox\Traits;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PreUpdate;

/**
 * Class SynchronizedFlagTrait
 *
 * @package                 AppBundle\ToolBox\Traits
 * @HasLifecycleCallbacks()
 */
trait SynchronizedFlagTrait
{

    /**
     * @var bool
     * @Column(name="synchronized",type="boolean",nullable=true)
     */
    private $synchronized = false;

    public function setSynchronized($synchronized)
    {
        $this->synchronized = $synchronized;

        return $this;
    }

    public function getSynchronized()
    {
        return $this->synchronized;
    }

    //    /**
    //     * @PreUpdate()
    //     */
    //    public function setNonSynchronized(){
    //        $this->synchronized = false;
    //    }


    public function isSynchronized()
    {
        return $this->synchronized;
    }
}
