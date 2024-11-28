<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Form\Chest\ChestCountsSearchType;
use AppBundle\Financial\Form\Chest\ChestCountType;
use AppBundle\Financial\Exception\ChestCannotBeValidatedException;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ChestController
 *
 * @Route("chest")
 */
class ChestController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @RightAnnotation("chest_count")
     *
     * @Route("/count",name="chest_count", options={"expose"=true})
     *
     * @Method({"GET", "POST"})
     */
    public function chestCountAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                if ($request->getMethod() === "POST") {
                    $chestDate = date_create_from_format(
                        'd/m/Y H:i:s',
                        $request->request->all()['chest_count']['date']
                    );
                    $chest = $this->get('chest.service')->prepareChest(
                        $chestDate
                    );
                    $chestForm = $this->createForm(
                        ChestCountType::class,
                        $chest
                    );
                    $chestForm->handleRequest($request);
                    $data = [
                        "data" => [
                            $this->renderView(
                                "@Financial/Chest/Counting/parts/chest_count_block.html.twig",
                                [
                                    "form"       => $chestForm->createView(),
                                    "restaurant" => $restaurant,
                                ]
                            ),
                        ],
                    ];
                }
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans(
                            'Error.general.internal'
                        ),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        } else {
            $chest = $this->get('chest.service')->prepareChest(
                new \DateTime('now')
            );
            $this->get('chest.service')->loadChestCount($chest);
            $chestForm = $this->createForm(
                ChestCountType::class,
                $chest
            );
            $response = $this->render(
                "@Financial/Chest/Counting/chest_count.html.twig",
                [
                    'form'       => $chestForm->createView(),
                    'restaurant' => $restaurant,
                ]
            );
        }

        return $response;
    }

    /**
     * @RightAnnotation("chest_count")
     *
     * @Route("/validate_chest",name="validate_chest", options={"expose"=true})
     *
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function chestValidateAction(Request $request)
    {
        if ($request->getMethod() === "POST") {
            $response = new JsonResponse();
            $data = [];
            $chestDate = date_create_from_format(
                'd/m/Y H:i:s',
                $request->request->all()['chest_count']['date']
            );
            $chest = $this->get('chest.service')->prepareChest($chestDate);
            $chestForm = $this->createForm(
                ChestCountType::class,
                $chest
            );
            $chestForm->handleRequest($request);
            if ($chestForm->isValid()) {
                try {
                    $this->get('chest.service')->loadChestCount($chest);
                    $this->get('chest.service')->validateChestCount($chest);
                    // Check for gap and create expense or recipe ticket
                    $ticketUrl = $this->get('chest.service')
                        ->generateAutomaticExpenceOrRecipeTicket($chest);
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans(
                            'chest.chest_count_validated'
                        )
                    );
                    $data = [
                        "data" => [
                            "smallChestId" => $chest->getSmallChest()->getId(),
                            "gap"          => $chest->getGap(),
                            "download_url" => $ticketUrl,
                        ],
                    ];
                } catch (ChestCannotBeValidatedException $e) {
                    $this->get('logger')->addAlert($e->getMessage(), []);
                    $this->get('chest.service')->loadLastChestCount($chest);
                    $chestForm = $this->createForm(
                        ChestCountType::class,
                        $chest
                    );
                    $chestForm->addError(
                        new FormError('error.last_chest_count_is_modified')
                    );
                    $data = [
                        "errors" => [
                            '',
                        ],
                        "data"   => [
                            $this->renderView(
                                "@Financial/Chest/Counting/parts/chest_count_block.html.twig",
                                ["form" => $chestForm->createView()]
                            ),
                        ],
                    ];
                } catch (\Exception $e) {
                    $this->get('logger')->addAlert($e->getMessage(), []);
                    throw new \Exception($e);
                }
            } else {
                $chestForm->addError(new FormError('error.there_is_a_problem'));
                $data = [
                    "errors" => [],
                    "data"   => [
                        $this->renderView(
                            "@Financial/Chest/Counting/parts/chest_count_block.html.twig",
                            ["form" => $chestForm->createView()]
                        ),
                    ],
                ];
            }
            $response->setData($data);

            return $response;
        }
    }

    /**
     * @RightAnnotation("chest_list")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/list",name="chest_list", options={"expose"=true})
     */
    public function listAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        $form = $this->createForm(
            new ChestCountsSearchType(),
            [
                'startDate' => new \DateTime('now'),
                'endDate'   => new \DateTime('now'),
            ],
            array('restaurant' => $currentRestaurant)
        );

        return $this->render(
            '@Financial/Chest/Listing/list_index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @RightAnnotation("chest_list")
     *
     * @param Request $request
     * @param int $download
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     *
     * @Route("/list_json/{download}",name="chest_list_json", options={"expose"=true})
     */
    public function chestListJsonAction(Request $request, $download = 0)
    {
        $download = intval($download);
        $orders = array(
            'date',
            'owner',
            'realCounted',
            'gap',
            'closured',
            'closureDate',
        );
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get(
            'restaurant.service'
        )->getCurrentRestaurant();

        if (1 === $download) {
            $filepath = $this->get('toolbox.document.generator')
                ->generateCsvFile(
                    'chest.service',
                    'listItems',
                    [
                        'criteria' => $dataTableHeaders['criteria'],
                        'order'    => $dataTableHeaders['orderBy'],
                        'search'   => $dataTableHeaders['search'],
                        'onlyList' => true,
                    ],
                    [
                        $this->get('translator')->trans(
                            "chest.listing.header.date"
                        ),
                        $this->get('translator')->trans(
                            "chest.listing.header.owner"
                        ),
                        $this->get('translator')->trans(
                            "chest.listing.header.real"
                        ),
                        $this->get('translator')->trans(
                            "chest.listing.header.diff"
                        ),
                        $this->get('translator')->trans(
                            "chest.listing.header.closured"
                        ),
                        $this->get('translator')->trans(
                            "chest.listing.header.closured_day"
                        ),
                    ],
                    function ($line) {
                        return [
                            $line['date'],
                            $line['owner'],
                            $line['realCounted'],
                            $line['gap'],
                            $line['closured'] ? 'x' : '',
                            $line['closureDate'],
                        ];
                    }
                );
            $name = 'Liste_des_comptages_coffres';

            $response = Utilities::createFileResponse(
                $filepath,
                $name.date('dmY_His').".csv"
            );

            return $response;
        }
        if ($download === 2) {
            $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
            $response = $this->get('chest.service')->generateExcelFile(
                $dataTableHeaders['criteria'],
                $dataTableHeaders['orderBy'],
                $dataTableHeaders['offset'],
                $dataTableHeaders['limit'],
                $logoPath,
                $dataTableHeaders['search']
            );

            return $response;
        }


        $chestCounts = $this->getDoctrine()->getRepository(
            "Financial:ChestCount"
        )->getChestCountsFilteredOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit'],
            $dataTableHeaders['search']
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $chestCounts['filtered'];
        $return['recordsTotal'] = $chestCounts['total'];
        $return['data'] = $this->get('chest.service')->serializeItems(
            $chestCounts['list']
        );

        return new JsonResponse($return);
    }

    /**
     * @RightAnnotation("chest_list")
     *
     * @param ChestCount $chestCount
     *
     * @return JsonResponse
     *
     * @Route("/json/details/{chestCount}",name="chest_count_detail",options={"expose"=true})
     */
    public function detailsAction(ChestCount $chestCount)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $chestForm = $this->createForm(
            ChestCountType::class,
            $chestCount
        );

        return new JsonResponse(
            array(
                'data'       => $this->renderView(
                    "@Financial/Chest/Counting/parts/chest_count_block.html.twig",
                    [
                        'form'       => $chestForm->createView(),
                        'restaurant' => $restaurant,
                        'list'       => true,
                    ]
                ),
                'dataFooter' => $this->renderView(
                    '@Financial/Chest/Listing/parts/detail_footer.html.twig',
                    [
                        'chestCount' => $chestCount,
                    ]
                ),
            )
        );
    }

    /**
     * @RightAnnotation("chest_list")
     *
     * @param Request    $request
     * @param ChestCount $chestCount
     *
     * @return JsonResponse
     *
     * @Route("/print/{chestCount}",name="chest_count_detail_print",options={"expose"=true})
     */
    public function printChestCountDetailAction(Request $request, ChestCount $chestCount) {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $chestForm = $this->createForm(ChestCountType::class, $chestCount);

        $title = $this->get('translator')->trans(
            'chest.listing.title_download'
        );

        if ($request->get('xls', false)) {
            $response = $this->get('chest.service')->createExcelFile(
                $chestCount
            );

            return $response;
        }

            $filename = preg_replace(
                '([ :])',
                '_',
                'chest_count_detail_'.date('d_m_Y_H_i_s').".pdf"
            );
            $filePath = $this->get('toolbox.pdf.generator.service')
                ->generatePdfFromTwig(
                    $filename,
                    "@Financial/Chest/Listing/print/print_detail.html.twig",
                    [
                        'form'       => $chestForm->createView(),
                        'restaurant' => $restaurant,
                        "title"      => $title,
                        "download"   => true,
                        'list'       => true,
                    ],
                    [
                        'orientation' => 'Portrait',
                    ],
                    true
                );

            return Utilities::createFileResponse(
                $filePath,
                preg_replace('([ :])', '_', $title).'_'.date('d_m_Y_H_i_s')
                .".pdf"
            );
        }

}
