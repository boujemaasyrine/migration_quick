<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 14/04/2016
 * Time: 09:40
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Form\Cashbox\CashboxCountType;
use AppBundle\Financial\Form\Cashbox\DayIncomeType;
use AppBundle\Financial\Form\Envelope\EnvelopeType;
use AppBundle\Financial\Form\RecipeTicket\RecipeTicketSearchType;
use AppBundle\Financial\Form\RecipeTicket\RecipeTicketType;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\General\Exception\OperationCannotBeDoneException;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\Security\Exception\NotAllowedException;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class RecipeTicketController
 *
 * @Route("recipeTicket")
 */
class RecipeTicketController extends Controller
{
    /**
     * @RightAnnotation("list_recipe_tickets")
     *
     * @param Request $request
     * @param $download
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list/{download}",name="list_recipe_tickets", options={"expose"=true})
     *
     * @Method({"GET", "POST"})
     */
    public function listRecipeTicketsAction(Request $request, $download = null)
    {
        $response = null;
        $download = intval($download);
        $orders = array('id', 'label', 'date', 'amount', 'owner');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        if ($request->isXmlHttpRequest()) {
            $items = $this->getDoctrine()->getRepository("Financial:RecipeTicket")->getRecipeTicketsFilteredOrdered(
                $dataTableHeaders['criteria'],
                $dataTableHeaders['orderBy'],
                $dataTableHeaders['offset'],
                $dataTableHeaders['limit'],
                $dataTableHeaders['search']
            );

            $return['draw'] = $dataTableHeaders['draw'];
            $return['recordsFiltered'] = $items['filtered'];
            $return['recordsTotal'] = $items['total'];
            $return['data'] = $this->get('recipe_ticket.service')->serializeRecipeTickets($items['list']);

            return new JsonResponse($return);
        }
            if ("POST" === $request->getMethod()) {
                if (1 === $download) {
                    $filename = 'Recipe_tickets_'.date('d-m-Y_H-i-s');
                    $response = $this->get('toolbox.document.generator')
                        ->generateXlsFile(
                            'recipe_ticket.service',
                            'getRecipeTickets',
                            [
                                'criteria' => $dataTableHeaders['criteria'],
                                'order' => $dataTableHeaders['orderBy'],
                                'search' => $dataTableHeaders['search'],
                                'onlyList' => true,
                            ],
                            $this->get('translator')->trans("recipe_ticket.index_title"),
                            [
                                $this->get('translator')->trans("recipe_ticket.reference"),
                                $this->get('translator')->trans("recipe_ticket.label"),
                                $this->get('translator')->trans("recipe_ticket.date"),
                                $this->get('translator')->trans("recipe_ticket.amount_"),
                                $this->get('translator')->trans("recipe_ticket.owner"),
                            ],
                            function ($line) {
                                return [
                                    $line['id'],
                                    $this->get('translator')->trans($line['label']),
                                    $line['date'],
                                    number_format((float)str_replace(",", ".", $line['amount']), 2, ".", ""),
                                    $line['owner'],
                                ];
                            },
                            $filename
                        );
                }

                if (2 === $download) {
                    $items = $this->getDoctrine()->getRepository(
                        "Financial:RecipeTicket"
                    )->getRecipeTicketsFilteredOrdered(
                        $dataTableHeaders['criteria'],
                        $dataTableHeaders['orderBy'],
                        $dataTableHeaders['offset'],
                        $dataTableHeaders['limit'],
                        $dataTableHeaders['search']
                    );

                    $return['draw'] = $dataTableHeaders['draw'];
                    $return['recordsFiltered'] = $items['filtered'];
                    $return['recordsTotal'] = $items['total'];
                    $return['data'] = $this->get('recipe_ticket.service')->serializeRecipeTickets($items['list']);

                    $title = $this->get('translator')->trans('recipe_ticket.index_title');
                    $criteria = $dataTableHeaders['criteria'];

                    $filter['startDate'] = $criteria['recipe_ticket_search[startDate'];
                    $filter['endDate'] = $criteria['recipe_ticket_search[endDate'];
                    $filter['owner'] = $criteria['recipe_ticket_search[owner']
                        ? $this->getDoctrine()->getManager()->getRepository('Staff:Employee')->find(
                            $criteria['recipe_ticket_search[owner']
                        )
                        : $this->get('translator')->trans('recipe_ticket.list.choose_member');
                    $filter['label'] = $criteria['recipe_ticket_search[label']
                        ? $this->get('translator')->trans($criteria['recipe_ticket_search[label'])
                        : $this->get('translator')->trans('recipe_ticket.list.choose_label');

                    $filename = 'list_recipe_tickets'.'_'.date('d_m_Y_H_i_s').".pdf";
                    $filePath = $this->get('toolbox.pdf.generator.service')
                        ->generatePdfFromTwig(
                            $filename,
                            '@Financial/RecipeTicket/parts/print_list.html.twig',
                            [
                                "recipeTickets" => $return['data'],
                                "title" => $title,
                                "filter" => $filter,
                                "download" => true,
                            ],
                            [
                                'orientation' => 'Portrait',
                            ],
                            true
                        );

                    return Utilities::createFileResponse($filePath, $title.' '.date('d_m_Y H_i_s').".pdf");
                }
            } else {
                $recipteTicketSearchForm = $this->createForm(
                    RecipeTicketSearchType::class,
                    [
                        "startDate" => new \DateTime(),
                        "endDate" => new \DateTime(),
                    ],
                    array("restaurant" => $dataTableHeaders['criteria']['restaurant'])
                );

                $response = $this->render(
                    '@Financial/RecipeTicket/list_recipe_ticket.html.twig',
                    [
                        'form' => $recipteTicketSearchForm->createView(),
                    ]
                );
            }

            return $response;

    }

    /**
     * @RightAnnotation("create_recipe_tickets")
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/createRecipeTicket",name="create_recipe_tickets", options={"expose"=true})
     *
     * @Method({"GET","POST"})
     */
    public function createRecipeTicketAction(Request $request)
    {
        $recipeTicket = new RecipeTicket();
        $recipeTicket->setDate($this->get('administrative.closing.service')->getLastNonClosedDate());
        $recipeTicketForm = $this->createForm(
            RecipeTicketType::class,
            $recipeTicket
        );
        if ($request->getMethod() === "POST") {
            $recipeTicketForm->handleRequest($request);
            if ($recipeTicketForm->isValid()) {
                $this->get('recipe_ticket.service')->saveRecipeTicket($recipeTicket);
                $this->get('session')->getFlashBag()->add('success', 'recipe_ticket.created_with_success');

                return $this->get('workflow.service')->nextStep($this->redirectToRoute('list_recipe_tickets'));
            }
        }
        $response = $this->render(
            '@Financial/RecipeTicket/create_recipe_ticket.html.twig',
            [
                'form' => $recipeTicketForm->createView(),
            ]
        );

        return $response;
    }

    /**
     * @RightAnnotation("list_recipe_tickets")
     *
     * @param Request      $request
     * @param RecipeTicket $recipeTicket
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/printRecipeTicket/{recipeTicket}",name="print_recipe_tickets", options={"expose"=true})
     */
    public function printRecipeTicketAction(Request $request, RecipeTicket $recipeTicket)
    {
        $title = $this->get('translator')->trans('recipe_ticket.print_title');
        $filename = preg_replace('([ :])', '_', $title.'_'.date('d_m_Y_H_i_s').".pdf");

        $filePath = $this->get('toolbox.pdf.generator.service')
            ->generatePdfFromTwig(
                $filename,
                '@Financial/RecipeTicket/parts/print.html.twig',
                [
                    "recipeTicket" => $recipeTicket,
                    "title" => $title,
                    "download" => true,
                ],
                [
                    'orientation' => 'Portrait',
                ],
                true
            );

        return Utilities::createFileResponse($filePath, $filename);
    }
}
