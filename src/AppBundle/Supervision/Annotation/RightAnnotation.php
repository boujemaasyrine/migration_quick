<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/06/2016
 * Time: 16:01
 */

namespace AppBundle\Supervision\Annotation;

/**
 * Class RightAnnotation
 *
 * @package    AppBundle
 * @Annotation
 */
class RightAnnotation
{

    private $rights = [];

    public function __construct($value)
    {

        if (isset($value['value'])) {
            $this->rights = $value['value'];
        } else {
            $this->rights = array();
        }
    }

    public function getRights()
    {
        return $this->rights;
    }
}
