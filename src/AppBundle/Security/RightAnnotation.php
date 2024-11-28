<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/02/2016
 * Time: 10:25
 */

namespace AppBundle\Security;

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
