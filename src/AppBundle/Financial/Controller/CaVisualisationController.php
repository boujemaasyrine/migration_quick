<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 03/12/2015
 * Time: 10:28
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class CaVisualisationController
 *
 */
class CaVisualisationController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @RightAnnotation("display_ca")
     *
     * @Route("/displayCA",name="display_ca")
     */
    public function displayCaAction(Request $request)
    {

        return $this->render(
            "@Financial/VisualisationCa/display.html.twig",
            [

            ]
        );
    }
}
