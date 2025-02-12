<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/03/2016
 * Time: 11:40PostgreSQL - quick@localhost
 */

namespace AppBundle\Merchandise\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class WyndMockUpController
 *
 * @package           AppBundle\Merchandise\Controller
 * @Route("/mockups")
 */
class WyndMockUpController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/tickets")
     */
    public function getTickets(Request $request)
    {

        if (!$request->headers->has('Api-User')) {
            return new JsonResponse(
                array(
                    'result' => 'error',
                    'code' => 3362,
                    'message' => 'Api-User header not found',
                )
            );
        }

        if (!$request->headers->has('Api-Hash')) {
            return new JsonResponse(
                array(
                    'result' => 'error',
                    'code' => 3362,
                    'message' => 'Api-hash header not found',
                )
            );
        }

        $user = 'quick';

        $hash = sha1('quick'.json_encode(array('box' => 1)));

        if ($request->headers->get('Api-User') != $user) {
            return new JsonResponse(
                array(
                    'result' => 'error',
                    'code' => 3362,
                    'message' => 'Api-User wrong',
                )
            );
        }

        if ($request->headers->get('Api-hash') != $hash) {
            return new JsonResponse(
                array(
                    'result' => 'error',
                    'code' => 32362,
                    'message' => 'Api-hash wrong , expected '.$hash." got ".$request->headers->get('Api-hash'),
                )
            );
        }


        $fileJson = $this->getParameter('kernel.root_dir')."/../data/import/wynd.json";

        if (!file_exists($fileJson)) {
            return new JsonResponse(
                array(
                    'result' => 'error',
                    'code' => 126542,
                    'message' => 'mockup file not found',
                )
            );
        }

        $ts = $this->getDoctrine()->getRepository("Financial:Ticket")->findBy(
            array(),
            array(
                'num' => 'DESC',
            )
        );
        if ($ts == null || count($ts) == 0) {
            $firstId = 1;
        } else {
            $ts = $ts[0];
            $firstId = $ts->getNum();
        }

        $n = rand(10, 20);

        $data = json_decode(file_get_contents($fileJson), true);
        $tickets = [];
        $i = 0;
        foreach ($data['data'] as $t) {
            if ($i++ >= $n) {
                break;
            }

            $type = 'order';
            if (isset($t['invoice'])) {
                $type = 'invoice';
            }

            $t[$type]['date'] = date('Y-m-d');
            $t[$type]['date_ticket_start'] = date('Y-m-d').substr($t[$type]['date_ticket_start'], 10);
            $t[$type]['date_ticket_end'] = date('Y-m-d').substr($t[$type]['date_ticket_end'], 10);
            $tickets[$firstId++] = $t;
        }

        unset($data['data']);
        $data['data'] = $tickets;


        return new JsonResponse($data);
    }
}
