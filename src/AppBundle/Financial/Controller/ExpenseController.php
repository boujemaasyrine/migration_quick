<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/03/2016
 * Time: 17:56
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Form\Expense\ExpenseType;
use AppBundle\Financial\Form\Expense\ExpenseSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\ToolBox\Utils\Utilities;

/**
 * Class ExpenseController
 *
 * @Route("expense")
 */
class ExpenseController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/expenseEntry",name="expense_entry",options={"expose"=true})
     *
     * @RightAnnotation("expense_entry")
     */
    public function entryExpenseAction(Request $request, Expense $expense = null)
    {

        if (null == $expense) {
            $expense = new Expense();
        }
        $date = $this->get('administrative.closing.service')->getLastNonClosedDate();
        $today = new \DateTime('now');
        if ($date > $today) {
            $expense->setDateExpense($date);
        } else {
            $expense->setDateExpense($today);
        }
        $form = $this->createForm(ExpenseType::class, $expense);

        if ($request->getMethod() === "POST") {
            try {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $this->get('expense.service')->saveExpense($expense);
                    $message = $this->get('translator')->trans('expense.entry.add_success');
                    $this->get('session')->getFlashBag()->add('success', $message);

                    return $this->get('workflow.service')->nextStep($this->redirectToRoute('expenses_list'));
                }

                return $this->render(
                        "@Financial/Expense/entry.html.twig",
                        [
                            'form' => $form->createView(),
                            'startDate' => $date,
                        ]
                );

            } catch (\Exception $e) {
            }
        }

        return $this->render(
            "@Financial/Expense/entry.html.twig",
            [
                'form' => $form->createView(),
                'startDate' => $date,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/expensesList",name="expenses_list",options={"expose"=true})
     *
     * @RightAnnotation("expenses_list")
     *
     * @throws \Exception
     */
    public function expenseListAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $form = $this->createForm(
            ExpenseSearchType::class,
            [
                "startDate" => new \DateTime(),
                "endDate" => new \DateTime(),
            ],
            array("restaurant" => $currentRestaurant)
        );

        return $this->render(
            "@Financial/Expense/list.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/expensesJsonList/{download}",name="expenses_json_list", options={"expose"=true})
     *
     * @throws \Exception
     */
    public function expenseJsonListAction(Request $request, $download = 0)
    {
        $orders = array('ref', 'label', 'owner', 'amount');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        $download = intval($download);
        if (1 === $download) {
            $filename = 'Depenses'.date('d-m-Y_H-i-s');
            $response = $this->get('toolbox.document.generator')
                ->generateXlsFile(
                    'expense.service',
                    'getExpenses',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,

                    ),
                    $this->get('translator')->trans('expense.list.title'),
                    ['Référence', 'Date', 'Libellé', 'Groupe', 'Responsable', 'Montant', 'TVA', 'Commentaires'],
                    function ($line) {
                        return [
                            $line['reference'],
                            $line['date']->format('d/m/Y'),
                            $line['label'],
                            $line['group'],
                            $line['owner'],
                            number_format((float)str_replace(",", ".", $line['amount']),2,'.',''),
                            number_format((float)str_replace(",", ".", $line['tva']),2,'.',''),
                            $line['comment'],
                        ];
                    },
                    $filename
                );

            return $response;
        }

        $expenses = $this->getDoctrine()->getRepository("Financial:Expense")->getExpensesFiltredOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $expensesList = $expenses['list'];
        if (!empty($expensesList)) {
            $return['data'] = $this->get('expense.service')->serializeExpenses($expensesList);
        } else {
            $return['data'] = [
                [
                    'dataClass' => '',
                    'reference' => '',
                    'label' => '',
                    'owner' => '',
                    'amount' => '',
                ],
            ];
        }
        if (2 === $download) {
            $title = $this->get('translator')->trans('expense.list.title');
            $criteria = $dataTableHeaders['criteria'];

            $filter['startDate'] = $criteria['expense_search[startDate'];
            $filter['endDate'] = $criteria['expense_search[endDate'];
            $filter['responsible'] = $criteria['expense_search[responsible']
                ? $this->getDoctrine()->getManager()->getRepository('Staff:Employee')->find(
                    $criteria['expense_search[responsible']
                )
                : $this->get('translator')->trans('expense.list.choose_member');
            $filter['group'] = $criteria['expense_search[group']
                ? $this->get('translator')->trans('expense.group.'.$criteria['expense_search[group'])
                : $this->get('translator')->trans('expense.list.choose_group');

            $filename = 'expense_list'.'_'.date('d_m_Y_H_i_s').".pdf";
            $filePath = $this->get('toolbox.pdf.generator.service')
                ->generatePdfFromTwig(
                    $filename,
                    '@Financial/Expense/print/print_list.html.twig',
                    [
                        "expenses" => $return['data'],
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

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $expenses['filtred'];
        $return['recordsTotal'] = $expenses['total'];

        return new JsonResponse($return);
    }

    /**
     * @param Expense $expense
     *
     * @return JsonResponse
     *
     * @Route("/json/detailsExpense/{expense}",name="expense_detail",options={"expose"=true})
     */
    public function detailsExpenseJsonAction(Expense $expense)
    {
        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Financial/Expense/modals/details.html.twig",
                    array(
                        'expense' => $this->get('expense.service')->serializeExpense($expense),
                    )
                ),
                'dataClass' => $this->get('translator')->trans('label.group').' '.$this->get('translator')->trans(
                    'expense.group.'.$expense->getGroupExpense()
                ).' '.$expense->getDateExpense()->format('d/m/Y'),
                'dataValue' => $expense->getGroupExpense().'/'.$expense->getDateExpense()->format('d/m/Y'),
                'dataFooter' => $this->renderView(
                    '@Financial/Expense/parts/detail_footer.html.twig',
                    [
                        'expense' => $expense,
                    ]
                ),
                'table' => $expense->getDeposit() && $expense->getDeposit()->getEnvelopes()->count() > 0,
            )
        );
    }

    /**
     * @param Expense $expense
     *
     * @return JsonResponse
     *
     * @Route("/print/{expense}",name="expense_detail_print",options={"expose"=true})
     *
     * @throws \Exception
     */
    public function printExpenseDetailAction(Expense $expense)
    {
        $title = $this->get('translator')->trans('expense.list.expense_export');
        $filename = preg_replace('([ :])', '_', 'expense_detail_print'.date('d_m_Y_H_i_s').".pdf");

        $filePath = $this->get('toolbox.pdf.generator.service')
            ->generatePdfFromTwig(
                $filename,
                '@Financial/Expense/print/print_detail.html.twig',
                [
                    "expense" => $this->get('expense.service')->serializeExpense($expense),
                    "title" => $title,
                    "download" => true,
                ],
                [
                    'orientation' => 'Portrait',
                ],
                true
            );

        return Utilities::createFileResponse($filePath, $title.' '.date('d_m_Y_H_i_s').".pdf");
    }

    /**
     * @return JsonResponse
     *
     * @Route("/json/ExpenseGroupLabels/{group}",name="expense_group_labels",options={"expose"=true})
     */
    public function getLabelsOfGroupJsonAction($group = null)
    {
        if (is_null($group)) {
            $labels = $this->get('expense.service')->getAllLabelsOfGroup();
        } else {
            $labels = $this->get('expense.service')->getLabelsOfGroup($group);
        }

        return new JsonResponse(
            array(

                'labels' => $labels,
            )
        );
    }
}
