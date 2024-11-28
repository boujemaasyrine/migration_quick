<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 14/04/2016
 * Time: 08:02
 */

namespace AppBundle\ToolBox\Twig;

class FloatExtension extends \Twig_Extension
{


    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'float_format',
                array(
                    $this,
                    'floatFormat',
                )
            ),
        );
    }

    public function floatFormat($x, $keepInt = true)
    {

        if ($x === null) {
            return 'X';
        }

        if (is_float($x) || (is_string($x) && preg_match('/^[-]?[0-9]+([,\.]{1}[0-9]+)?$/', $x) > 0)) {
            return number_format($x, 2, ',', '');
        }

        if (is_int($x) || (is_string($x) && preg_match('/^[-]?[0-9]+$/', $x) > 0)) {
            if ($keepInt) {
                return $x;
            } else {
                return number_format($x, 2, ',', '');
            }
        }

        if (is_numeric($x)) {
            return number_format($x, 2, ',', '');
        }

        return $x;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'float_extension';
    }
}
