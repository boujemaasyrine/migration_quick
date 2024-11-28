<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 03/12/2015
 * Time: 10:28
 */

namespace AppBundle\Report\Controller;

use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class CaVisualisationController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @RightAnnotation({"aaa","bbb"})
     * @Route("/displayCA",name="display_ca")
     */
    public function displayCaAction(Request $request)
    {

        return $this->render(
            "@Report/VisualisationCa/display.html.twig",
            [

            ]
        );
    }
}
