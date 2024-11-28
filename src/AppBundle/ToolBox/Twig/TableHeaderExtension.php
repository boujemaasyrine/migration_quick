<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 18/04/2016
 * Time: 11:51
 */

namespace AppBundle\ToolBox\Twig;

class TableHeaderExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('t_head', array($this, 'tHead')),
        );
    }

    public function tHead(array $heads)
    {
        $css = '@media only screen and (max-width: 760px),
                (min-device-width: 768px) and (max-device-width: 1024px) {
                ';
        foreach ($heads as $key => $head) {
            $css .= '
                    .table-responsive td:nth-of-type('.($key + 1).'):before {
                        content: "'.$head.'";
                    }
            ';
        }
        $css .= '}';

        return $css;
    }

    public function getName()
    {
        return 't_head_extension';
    }
}
