<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/05/2016
 * Time: 16:04
 */

namespace AppBundle\Supervision\Controller\Reports;

use AppBundle\Supervision\Form\Reports\HourByHourType;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use AppBundle\Security\RightAnnotation;

/**
 * Class HourByHourController
 *
 * @package         AppBundle\Controller\Reports
 * @Route("report")
 */
class HourByHourController extends Controller
{

    /**
     * @RightAnnotation("hour_by_hour")
     * @param Request $request
     *
     * @return Response
     * @Route("/hour_by_hour",name="supervision_hour_by_hour")
     */
    public function generateHourByHourReportAction(Request $request)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['date'] = new \DateTime();
        $form = $this->createForm(HourByHourType::class, $data, ['user' => $user]);

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $startDate = $form->getData()['date'];
                $restaurant = $form->getData()['restaurant'];
                $allResult = $this->get('report.hour.hour.service')->generateHourByHourReport($form->getData());
                $result = $allResult['result'];
                $openingHour = $allResult['openingHour'];
                $closingHour = $allResult['closingHour'];
                if (is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                    $request->get('xls', null)
                )) {
                    return $this->render(
                        '@Supervision/Reports/Revenue/HourByHour/index.html.twig',
                        [
                            "result" => $result,
                            "generated" => true,
                            'opening_hour' => $openingHour,
                            'closing_hour' => $closingHour,
                            'form' => $form->createView(),
                        ]
                    );
                } else {
                    if (!is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                        $request->get('xls', null)
                    )) {
                        $filename = "heure_par_heure".date('Y_m_d_H_i_s').".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Supervision/Reports/Revenue/HourByHour/report.html.twig',
                            [
                                'result' => $result,
                                'data' => $form->getData(),
                                "download" => true,
                                'opening_hour' => $openingHour,
                                'closing_hour' => $closingHour,
                            ],
                            [
                                'orientation' => 'Landscape',
                            ]
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        if (!is_null($request->get('xls', null)) && is_null($request->get('export', null)) && is_null(
                            $request->get('download', null)
                        )) {
                            $response = $this->get('report.hour.hour.service')->generateExcelFile(
                                $result,
                                $restaurant,
                                $startDate,
                                $openingHour,
                                $closingHour
                            );

                            return $response;
                        } else {
                            $result = $this->get('report.hour.hour.service')->serializeHourByHourReportResult(
                                $result,
                                $openingHour,
                                $closingHour
                            );
                            $header = $this->get('report.hour.hour.service')->getCsvHeader($openingHour, $closingHour);

                            $filepath = $this->get('toolbox.document.generator')->getFilePathFromSerializedResult(
                                $header,
                                $result
                            );
                            $response = Utilities::createFileResponse(
                                $filepath,
                                'heure_par_heureCSV'.date('dmY_His').".csv"
                            );

                            return $response;
                        }
                    }
                }
            }
        }

        return $this->render(
            "@Supervision/Reports/Revenue/HourByHour/index.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }
}
