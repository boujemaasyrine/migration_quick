<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/03/2016
 * Time: 15:48
 */

namespace AppBundle\General\Controller;

use AppBundle\General\Entity\ImportProgression;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProgressController extends Controller
{

    /**
     * @param ImportProgression $progression
     * @return JsonResponse
     * @Route("/progress/{progression}",name="progress",options={"expose"=true})
     */
    public function getProgress(ImportProgression $progression = null)
    {

        if ($progression == null) {
            return new JsonResponse(
                array(
                    'result' => null,
                )
            );
        }

        $result = [];

        $result['status'] = $progression->getStatus();
        $result['progress'] = number_format($progression->getProgress(), '2', '.', '');
        $result['total'] = intval($progression->getTotalElements());
        $result['proceeded'] = intval($progression->getProceedElements());

        return new JsonResponse(
            array(
                'result' => $result,
            )
        );
    }
}
