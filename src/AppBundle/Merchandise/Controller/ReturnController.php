<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/03/2016
 * Time: 09:05
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Returns;
use AppBundle\Merchandise\Form\ReturnType;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ReturnController
 *
 * @package          AppBundle\Merchandise\Controller
 * @Route("/return")
 */
class ReturnController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     * @Route("/create",name="create_return",options={"expose"=true})
     * @RightAnnotation("create_return")
     */
    public function createReturnAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($request->getMethod() == 'POST') {
            $return = new Returns();
            $return
                ->setDate(new \DateTime('NOW'))
                ->setEmployee($this->getUser());
            $return->setOriginRestaurant($currentRestaurant);
            $form = $this->createForm(
                ReturnType::class,
                $return,
                array(
                    "current_restaurant" => $currentRestaurant,
                )
            );
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($request->query->has('download')) {
                    $this->get('return.service')->createReturnForDownload($return);
                    $filepath = $this->get('return.service')->generateBon($return);
                    $filename = 'return_'.date('Y_m_d').".pdf";

                    return Utilities::createFileResponse($filepath, $filename);
                }

                $r = $this->get('return.service')->createReturn($return,$currentRestaurant);

                if ($r) {
                    $this->get('session')->getFlashBag()->add('success', "return_created_success");
                } else {
                    $this->get('session')->getFlashBag()->add('error', "return_created_fail");
                }

                return $this->get('workflow.service')->nextStep($this->redirectToRoute("returns_list"));
            }
        } else {
            $return = new Returns();
            $form = $this->createForm(
                ReturnType::class,
                $return,
                array(
                    "current_restaurant" => $currentRestaurant,
                )
            );
        }

        return $this->render(
            "@Merchandise/Returns/create_return.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return Response
     * @Route("/list",name="returns_list")
     * @RightAnnotation("returns_list")
     */
    public function showListAction()
    {
        return $this->render("@Merchandise/Returns/list.html.twig");
    }

    /**
     * @param Request $request
     * @param int $download
     * @return JsonResponse
     * @Route("/json/list/{download}",name="json_return_list",options={"expose"=true})
     */
    public function returnsListJsonAction(Request $request, $download = 0)
    {
        $dataTable = Utilities::getDataTableHeader($request, ['supplier', 'date', 'responsible', 'valorization']);
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $download = intval($download);
        if ($download === 1) {
            $filepath = $this->get('toolbox.document.generator')
                ->generateCsvFile(
                    'return.service',
                    'getList',
                    array(
                        'currentRestaurant' => $currentRestaurant,
                        'criteria' => $dataTable['criteria'],
                        'order' => $dataTable['orderBy'],
                        'onlyList' => true,
                    ),
                    ['Fournisseur', 'Date', 'Responsable', 'Valorisation'],
                    function ($row) {
                        return array(
                            $row['supplier'],
                            $row['date'],
                            $row['responsible'],
                            number_format($row['valorization'], 2, ',', ''),
                        );
                    }
                );

            $response = Utilities::createFileResponse($filepath, 'retours_'.date('dmY_His').".csv");

            return $response;
        } else {
            if ($download === 2) {
                $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                $response = $this->get('return.service')->generateExcelFile(
                    $currentRestaurant,
                    $dataTable['criteria'],
                    $dataTable['orderBy'],
                    $logoPath
                );

                return $response;
            }
        }

        $list = $this->getDoctrine()->getRepository(Returns::class)
            ->getList(
                $currentRestaurant,
                $dataTable['criteria'],
                $dataTable['orderBy'],
                $dataTable['offset'],
                $dataTable['limit']
            );

        return new JsonResponse(
            array(
                'data' => $this->get('return.service')->serializeReturnsList($list['list']),
                'draw' => $dataTable['draw'],
                'recordsTotal' => $list['total'],
                'recordsFiltered' => $list['filtred'],
            )
        );
    }

    /**
     * @param Returns $return
     * @return JsonResponse
     * @Route("/details/{return}",name="details_return",options={"expose"=true})
     */
    public function detailsAction(Returns $return)
    {

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Merchandise/Returns/modals/details.html.twig",
                    array(
                        'return' => $return,
                    )
                ),
            )
        );
    }

    /**
     *
     * @Route("/print/{return}",name="print_return",options={"expose"=true})
     */

    public function printReturnAction(Returns $return)
    {

        $this->get('return.service')->createReturnForDownload($return);
        $filepath = $this->get('return.service')->generateBon($return);
        $filename = 'return_'.date('Y_m_d').".pdf";

        return Utilities::createFileResponse($filepath, $filename);

    }
}
