<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 25/05/2016
 * Time: 17:26
 */

namespace AppBundle\Supervision\Controller\WsBiAPI;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Supervision\Utils\Utilities;

/**
 * Class EmployeeAPI
 * @package AppBundle\Controller\WsBoAPI
 * @Route("/ws_bi_api")
 */
class LossBiAPI extends Controller
{

    /**
     * @Route("/loss")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lossAction(Request $request)
    {
        try {
            $data = isset($request->request->getIterator()['data']) ? $request->request->getIterator()['data'] : null;
            if (is_null($data)) {
                return $response = new JsonResponse([
                    'error' => 'Parameter data is not set.'
                ], 400);
            }
            $data = json_decode($data, true);

            $startDate = isset($data['startDate']) ? $data['startDate'] : null;
            $endDate = isset($data['endDate']) ? $data['endDate'] : null;
            $restaurantId = isset($data['restaurantId']) ? $data['restaurantId'] : null;

            $restaurants = $this->get('bi_api.parameter.service')->verifyRestaurant($restaurantId, $startDate, $endDate);
            if (!is_array($restaurants)) {
                return $response = new JsonResponse([
                    'error' => $restaurants
                ], 400);
            }

            $file = $this->get('bi_api.response.service')
                ->generateCsvFile('bi_api.loss.service', 'getLoss',
                    ['criteria' => [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'restaurants' => $restaurants
                    ]],
                    ["CodeRest", "Date", "TotalArticle", "TotalProduit"],

                    function ($line) {
                        return $line;
                    }
                );


            return $response = Utilities::createCsvFileResponse($file, date('Ymd') . "P." . $restaurantId.'.csv');
        } catch (Exception $e) {
            return $response = new JsonResponse([
                'error' => 'Internal error!'
            ], 400);
        }
    }

}