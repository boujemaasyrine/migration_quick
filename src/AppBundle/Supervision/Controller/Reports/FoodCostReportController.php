<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 18/09/2018
 * Time: 08:41
 */

namespace AppBundle\Supervision\Controller\Reports;


use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Form\Reports\FoodCostType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


/**
 * Class FoodCostReportController
 *
 * @package AppBundle\Supervision\Controller\Reports
 * @Route("report")
 */
class FoodCostReportController extends Controller
{

    /**
     *
     * @param Request $request
     *
     * @return Response
     * @Route("/foodcost_report",name="supervision_foodcost_report")
     */
    public function generateFoodCostReportAction(Request $request)
    {


        // throwing an access denied exception
        
        // throw new AccessDeniedException();
        
        
        $form = $this->createForm(FoodCostType::class, null);


        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $filter = $form->getData();

                $progression = new ImportProgression();

                $progression->setNature('supervision_foodcost_report')
                    ->setStatus('pending')->setStartDateTime(new \DateTime());
                $restaurants = count($filter["restaurants"]) > 0
                    ? $filter["restaurants"]->toArray()
                    : $this->getDoctrine()->getManager()->getRepository(
                        Restaurant::class
                    )->getOpenedRestaurants();
                $total = count($restaurants);
                $restaurants = array_map("current", $restaurants);
                $progression->setProceedElements(0)->setTotalElements($total);
                $this->getDoctrine()->getManager()->persist($progression);
                $this->getDoctrine()->getManager()->flush();
                $filename = 'reportFoodCost'.date('Y_m_d_H_i_s').'.xls';
                $request->getSession()->set(
                    'foodCost_report_filename',
                    $filename
                );
                $cmd = 'saas:foodcost:sql '.$filter["startDate"]->format(
                        'Y-m-d'
                    )
                    .' '.$filter["endDate"]->format('Y-m-d').' '
                    .$progression->getId().' '.$filename.' '.implode(
                        " ",
                        $restaurants
                    );

                $this->get('toolbox.command.launcher')->execute(
                    $cmd
                );


                return $this->render(
                    "@Supervision/Reports/Merchandise/FoodCostReport/index.html.twig",
                    array(
                        'progressID' => $progression->getId(),
                        'form'       => $form->createView(),
                    )
                );


            }


        }

        return $this->render(
            "@Supervision/Reports/Merchandise/FoodCostReport/index.html.twig",
            array(
                'form' => $form->createView(),
            )
        );

    }

    /**
     * @param Request $request
     *
     * @return BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/download_excel_file",name="download_foodcost_report",options={"expose"=true})
     */
    public function downloadExcelFile(Request $request)
    {

        $tmpDir = $this->getParameter('kernel.root_dir')
            ."/../data/tmp/foodCostSupervision/";

        $this->get('logger')->addDebug('the temp Directory '.$tmpDir);

        $filename = $request->getSession()->get('foodCost_report_filename');

        if (isset($filename) && !empty($filename)) {

            try {

                $file = $tmpDir.$filename;

                $this->get('logger')->addDebug('the file to download '.$file);

                $response = new BinaryFileResponse($file);

                $response->headers->set('Content-Type', 'text/csv');


                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $filename
                );

                return $response;

            } catch (\Exception $e) {

                $this->get('logger')->addAlert(
                    'download of foodCost report failed '.$e->getMessage(),
                    ["download:report_foodcost:supervision"]
                );

                return $this->redirectToRoute('restaurant_list_super');

            }

        }

    }


}