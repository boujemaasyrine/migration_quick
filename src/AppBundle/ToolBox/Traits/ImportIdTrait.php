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
 * Trait ImportIdTrait
 * @package AppBundle\ToolBox\Traits
 */
trait ImportIdTrait
{

    /**
     * @var string
     * @ORM\Column(name="import_id", type="string",nullable=true, unique=true)
     */
    private $importId;

    /**
     * @return string
     */
    public function getImportId()
    {
        return $this->importId;
    }

    /**
     * @param $importId
     * @return $this
     */
    public function setImportId($importId)
    {
        $this->importId = $importId;

        return $this;
    }

}
