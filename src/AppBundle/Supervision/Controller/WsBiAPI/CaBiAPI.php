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
class CaBiAPI extends Controller
{
    /**
     * @Route("/ca_per_taxe_solding_canal")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function CaPerTaxeAndSoldingCanalAction(Request $request)
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
                ->generateCsvFile('bi_api.ca.service', 'getCaPerTaxeAndSoldingCanal',
                    ['criteria' => [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'restaurant' => $restaurants[0]->getCode()
                    ]],
                    ["Restaurant", "CommercialDate", "canal de vente", "taxe", "CA_BRUT_TTC", "CA_BRUT_TVA", "CA_BRUT_HT","Disc_BPub_TTC",
                        "Disc_BPub_TVA", "Disc_BPub_HT", "Disc_BRep_TTC", "Disc_BRep_TVA", "Disc_BRep_HT", "VA_TTC", "VA_TVA", "VA_HT", "CA_NET_TTC", "CA_NET_TVA", "CA_NET_HT"],
                    function ($line) {
                        return $line;
                    }
                );

            return $response = Utilities::createCsvFileResponse($file, date('Ymd') . "D." . $restaurantId);
        } catch (Exception $e) {
            return $response = new JsonResponse([
                'error' => 'Internal error!'
            ], 400);
        }
    }
}