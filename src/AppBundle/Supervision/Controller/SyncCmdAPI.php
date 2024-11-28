<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/05/2016
 * Time: 18:33
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\General\Entity\SyncCmdQueue;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SyncCmdAPI
 *
 * @package AppBundle\Controller
 * @Route("/ws_bo_api")
 */
class SyncCmdAPI extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/cmd_to_execute")
     * @ParamConverter("origin",class="AppBundle:Restaurant",converter="restaurant_converter")
     */
    public function getCmdToExecute(Request $request, $origin)
    {
        $allCmd = $this->getDoctrine()->getRepository("AppBundle:SyncCmdQueue")->getCmdForRestaurant($origin);
        $cmd = [];

        foreach ($allCmd as $c) {
            if (date('H:i') < $this->container->getParameter('product_sync_cmd_hour')
                && !is_null($c->getSyncDate())
                && in_array($c->getCmd(), [SyncCmdQueue::DOWNLOAD_INV_ITEMS, SyncCmdQueue::DOWNLOAD_SOLD_ITEMS])
            ) {
                //do noting
            } else {
                $cmd[] = $c;
            }
        }

        foreach ($cmd as $c) {
            $c->setStatus(SyncCmdQueue::PENDING);
        }
        $this->getDoctrine()->getManager()->flush();

        $data = [];
        foreach ($cmd as $c) {
            $data[] = [
                'id' => $c->getId(),
                'cmd' => $c->getCmd(),
                'params' => $c->getParams(),
                'direction' => $c->getDirection(),
                'order' => $c->getOrder(),
            ];
        }

        return new JsonResponse(
            array(
                'data' => $data,
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/ack")
     * @ParamConverter("origin",class="AppBundle:Restaurant",converter="restaurant_converter")
     */
    public function ackSync(Request $request, $origin)
    {

        //file_put_contents($this->container->getParameter('kernel.root_dir')."/xxx.log","hee");
        if ($request->request->has('syncCmd')) {
            $cmd = $this->getDoctrine()->getRepository("AppBundle:SyncCmdQueue")->find(
                $request->request->get('syncCmd')
            );
            if ($cmd && $cmd->getOriginRestaurant() == $origin) {
                if ($request->request->get('status') == 'success') {
                    $cmd->setStatus(SyncCmdQueue::EXECUTED_SUCCESS);
                } else {
                    $cmd->setStatus(SyncCmdQueue::EXECUTED_FAIL);
                }
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return new JsonResponse([]);
    }
}
