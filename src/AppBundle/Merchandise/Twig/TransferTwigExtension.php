<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 30/03/2016
 * Time: 14:02
 */

namespace AppBundle\Merchandise\Twig;

use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Entity\TransferLine;

class TransferTwigExtension extends \Twig_Extension
{

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'val_per_tva',
                array(
                    $this,
                    'valTvaFunction',
                )
            ),
        );
    }

    public function valTvaFunction(Transfer $transfer, $zone)
    {

        if ($zone == 'L') {
            $zone = 'getTaxLux';
        } else {
            $zone = 'getTaxBe';
        }

        $tva = [];
        $cat = [];
        $catList = [];

        foreach ($transfer->getLines() as $l) {
            $s = $l->getValorization();

            $key = strval($l->getProduct()->getProductCategory()->$zone());
            if (!array_key_exists($key, $tva)) {
                $tva[$key] = 0;
            }
            $tva[$key] = $tva[$key] + $s;

            $key = $l->getProduct()->getProductCategory()->getId();
            if (!array_key_exists($key, $cat)) {
                $cat[$key] = 0;
                $catList[$key] = $l->getProduct()->getProductCategory();
            }
            $cat[$key] = $cat[$key] + $s;
        }

        //die;

        return array(
            'tva' => $tva,
            'cat' => $cat,
            'catList' => $catList,
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'transfer_extension';
    }
}
