<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxMealTicketContainer;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Form\Cashbox\CashboxCountsSearchType;
use AppBundle\Financial\Form\Cashbox\CashboxCountType;
use AppBundle\Financial\Form\Cashbox\DayIncomeType;
use AppBundle\Financial\Form\Envelope\EnvelopeType;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\Financial\Service\CashboxCheckQuickContainerService;
use AppBundle\Financial\Service\CashboxCheckRestaurantContainerService;
use AppBundle\Financial\Service\CashboxDiscountContainerService;
use AppBundle\Financial\Service\CashboxMealTicketContainerService;
use AppBundle\Financial\Service\EnvelopeService;
use AppBundle\Financial\Service\WithdrawalSynchronizationService;
use AppBundle\ToolBox\Utils\DateUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Exception\Exception;
use AppBundle\Security\RightAnnotation;
use AppBundle\Administration\Service\ParameterService;

/**
 * Class CashBoxController
 *
 * @Route("cashbox")
 */
class CashBoxController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @RightAnnotation("cashbox_counting")
     *
     * @Route("/counting",name="cashbox_counting", options={"expose"=true})
     *
     * @Method({"GET", "POST"})
     */
    public function cashboxCountingAction(Request $request)
    {
        $unique=uniqid('cashboxcounting_');
        /**
         * @var Logger $loggerFinancial
         */
        $loggerFinancial = $this->get('monolog.logger.financial');
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        $tf1 = time();
        $loggerFinancial->addDebug('Start Cashbox counting for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
        $response = null;
        $isGlobalCashCashboxCount = $this->get('paremeter.service')
            ->getGlobalCashCashboxCountParameter()->getValue();



     if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                if ($request->getMethod() === "POST") {

                    $rAll = $request->request->all();
                    $cashboxDate = date_create_from_format(
                        'd/m/Y',
                        $rAll['cashbox_count']['date']
                    );
                    $cachierId = $rAll['cashbox_count']['cashier'];

                    $t1 = time();
                    $loggerFinancial->addDebug('Start synch of withdrawal for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
                    $this->synchApiWithdrawalTmp($currentRestaurant,$cashboxDate);
                    $t2 = time();
                    $executionTime = $t2 - $t1;
                    $loggerFinancial->addDebug('finish synch of withdrawal for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);

                    /**
                     * @var WithdrawalSynchronizationService $wss
                     */
                    $wss = $this->get('withdrawal.synchronization.service');
                    $hasIWTmp = !empty($cachierId) ? $wss->hasInvalidWithdrawalsTmp($currentRestaurant, $cachierId, $cashboxDate) : false;


                        $t1 = time();
                        $loggerFinancial->addDebug('Start import Tickets for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
                        $this->get('ticket.service')->importTickets($cashboxDate);
                        $t2 = time();
                        $executionTime = $t2 - $t1;
                        $loggerFinancial->addDebug('finish import Tickets for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);
                    $t1 = time();
                    $loggerFinancial->addDebug('Start prepare cashbox for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
                    $cashbox = $this->get('cashbox.service')->prepareCashbox(
                        $cashboxDate
                    );
                    $t2 = time();
                    $executionTime = $t2 - $t1;
                    $loggerFinancial->addDebug('finish prepare cashbox for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);

                    $cashbox->getCashContainer()->setAllAmount(
                        $isGlobalCashCashboxCount == true
                    );
                    $cashboxForm = $this->createForm(
                        CashboxCountType::class,
                        $cashbox,
                        array("restaurant" => $currentRestaurant)
                    );
                    $cashboxForm->handleRequest($request);
                    if ($cashboxForm->isValid()) {

                        $t1 = time();
                        $loggerFinancial->addDebug('Start load Payment Tickets for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
                        $this->get('cashbox.service')->loadPaymentTickets(
                            $cashbox
                        );
                        $t2 = time();
                        $executionTime = $t2 - $t1;
                        $loggerFinancial->addDebug('finish load Payment Tickets for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);



                        $t1 = time();
                        $loggerFinancial->addDebug('Start load Discounts Lines for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
                        $this->get('cashbox.service')->loadDiscountsLines(
                            $cashbox
                        );
                        $t2 = time();
                        $executionTime = $t2 - $t1;
                        $loggerFinancial->addDebug('finish load Discounts Lines for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);



                        $t1 = time();
                        $loggerFinancial->addDebug('Start load Withdrawals for the restaurant ' . $currentRestaurant->getCode());
                        $this->get('cashbox.service')->loadWithdrawals(
                            $cashbox
                        );
                        $t2 = time();
                        $executionTime = $t2 - $t1;
                        $loggerFinancial->addDebug('finish load Withdrawals for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);


                        $t1 = time();
                        $loggerFinancial->addDebug('Start calculateCancelsAbondonsCorrectionsCashbox for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
                        $this->get('cashbox.service')
                            ->calculateCancelsAbondonsCorrectionsCashbox(
                                $cashbox
                            );
                        $t2 = time();
                        $executionTime = $t2 - $t1;
                        $loggerFinancial->addDebug('finish calculateCancelsAbondonsCorrectionsCashbox for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);

							
                    }
                    $data = [
                        "data" => [
                            $this->renderView(
                                "@Financial/CashBox/Counting/parts/cashbox_count_block.html.twig",
                                [
                                    "form" => $cashboxForm->createView(),
                                    'paymentMethodStatus' => $this->get(
                                        'payment_method.status.service'
                                    ),
                                ]
                            ),
                            'hasIwTmp' => $hasIWTmp],
                    ];
                }
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans(
                            'Error.general.internal'
                        ),
                        $e->getLine() . " : " . $e->getMessage(),
                    ],
                ];
            }
            $response->setData($data);
        } else {

       /*  $t1 = time();
         $loggerFinancial->addDebug('Start synch of withdrawal for the restaurant Asychro' . $currentRestaurant->getCode().' : '.$unique);
         $this->synchApiWithdrawalTmp($currentRestaurant,null,true);
         $t2 = time();
         $executionTime = $t2 - $t1;
         $loggerFinancial->addDebug('finish synch of withdrawal for the restaurant Asynch' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);
*/
       $date = $this->get('administrative.closing.service')
                ->getLastWorkingEndDate();

     /*    $loggerFinancial->addDebug('Start import Tickets asychro for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
         $t1 = time();
        // $cashboxDate = new \DateTime($date->format('d/m/Y'));
         $this->get('ticket.service')->importTickets($date,true);
         $t2 = time();
         $executionTime = $t2 - $t1;
         $loggerFinancial->addDebug('finish import Tickets asychro for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);
*/

         $lastClosedDay = $this->get('administrative.closing.service')
                ->getLastClosingDate();
            if (DateUtilities::isToday($lastClosedDay)) {
                $date = $this->get('administrative.closing.service')
                    ->getLastWorkingEndDate();
            }

            $t1 = time();
            $loggerFinancial->addDebug('Start prepare Cashbox for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
            $cashbox = $this->get('cashbox.service')->prepareCashbox($date);
            $t2 = time();
            $executionTime = $t2 - $t1;
            $loggerFinancial->addDebug('finish prepare Cashbox for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);



            $cashbox->getCashContainer()->setAllAmount(
                $isGlobalCashCashboxCount == true
            );
            $cashboxForm = $this->createForm(
                CashboxCountType::class,
                $cashbox,
                array("restaurant" => $currentRestaurant)
            );

            $response = $this->render(
                "@Financial/CashBox/Counting/cashbox_counting.html.twig",
                [
                    'form' => $cashboxForm->createView(),
                    'paymentMethodStatus' => $this->get(
                        'payment_method.status.service'
                    ),
                ]
            );
        }
        $tf2 = time();
        $executionTime = $tf2 - $tf1;
        $loggerFinancial->addDebug('finish cashbox counting for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);

        return $response;
    }

    /**
     * Cette fonction permet la synchronisation automatique des prélèvements avec API pour la date fiscale.
     * @param $currentRestaurant
     */
    private function synchApiWithdrawalTmp($currentRestaurant,$date=null,$asynch=false)
    {
        /**
         * @var WithdrawalSynchronizationService $wss
         */
        $wss = $this->get('withdrawal.synchronization.service');
        if($date===null){
            $df = $this->get('administrative.closing.service')->getLastWorkingEndDate();
            $startDate = new \DateTime($df->format('j-m-Y'));
            $endDate = new \DateTime($df->format('j-m-Y'));
        }else{
            $startDate = new \DateTime($date->format('j-m-Y'));
            $endDate = new \DateTime($date->format('j-m-Y'));
        }

        $wss->synchApiWithdrawalTmp($currentRestaurant, $startDate, $endDate,false,$asynch);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @RightAnnotation("cashbox_counting")
     *
     * @Route("/gap",name="gap_cashbox_count", options={"expose"=true})
     *
     * @Method({"POST"})
     */
    public function cashboxCountingGapAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();

        if ($request->getMethod() === "POST") {
            $response = new JsonResponse();
            try {
                $data = [];
                $cashboxDate = date_create_from_format(
                    'd/m/Y H:i:s',
                    $request->request->all()['cashbox_count']['date']
                );
                $cashbox = $this->get('cashbox.service')->prepareCashbox(
                    $cashboxDate
                );
                $cashboxForm = $this->createForm(
                    CashboxCountType::class,
                    $cashbox,
                    array("restaurant" => $currentRestaurant)
                );
                $cashboxForm->handleRequest($request);
                if ($cashboxForm->isValid()) {
                    $this->get('cashbox.service')->loadPaymentTickets($cashbox);
                    $this->get('cashbox.service')->loadDiscountsLines($cashbox);
                    $this->get('cashbox.service')->loadWithdrawals($cashbox);
                    $this->get('cashbox.service')
                        ->calculateCancelsAbondonsCorrectionsCashbox($cashbox);
                    $data = [
                        "data" => [
                            $this->renderView(
                                '@Financial/CashBox/Counting/modal/cashbox_gap.html.twig',
                                [
                                    "cashbox" => $cashbox,
                                ]
                            ),
                            "footer" => $this->renderView(
                                '@Financial/CashBox/Counting/modal/footer_cashbox_gap.html.twig'
                            ),
                        ],
                    ];
                } else {
                    $data = [
                        "data" => [
                            $this->renderView(
                                "@Financial/CashBox/Counting/parts/cashbox_count_block.html.twig",
                                [
                                    "form" => $cashboxForm->createView(),
                                    'paymentMethodStatus' => $this->get(
                                        'payment_method.status.service'
                                    ),
                                ]
                            ),
                        ],
                        "errors" => [],
                    ];
                }
                $response->setData($data);
            } catch (\Exception $e) {
                $this->get('logger')->addAlert(
                    $e->getMessage(),
                    ['CashboxController:cashboxCountingGapAction']
                );
            }

            return $response;
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws Exception
     *
     * @RightAnnotation("cashbox_counting")
     *
     * @Route("/validating_count",name="validate_cashbox_count", options={"expose"=true})
     *
     * @Method({"POST"})
     */
    public function cashboxCountingValidateAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        if ($request->getMethod() === "POST") {
            $response = new JsonResponse();
            $data = [];
            $cashboxDate = date_create_from_format(
                'd/m/Y H:i:s',
                $request->request->all()['cashbox_count']['date']
            );
            $cashbox = $this->get('cashbox.service')->prepareCashbox(
                $cashboxDate
            );
            $cashboxForm = $this->createForm(
                CashboxCountType::class,
                $cashbox,
                array("restaurant" => $currentRestaurant)
            );
            $cashboxForm->handleRequest($request);
            if ($cashboxForm->isValid()) {
                try {
                    $this->get('cashbox.service')->loadPaymentTickets($cashbox);
                    $this->get('cashbox.service')->loadDiscountsLines($cashbox);
                    $this->get('cashbox.service')->loadWithdrawals($cashbox);
                    $this->get('cashbox.service')
                        ->calculateCancelsAbondonsCorrectionsCashbox($cashbox);
                    $this->get('cashbox.service')->validateCashboxCount(
                        $cashbox
                    );

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans(
                            'cashbox.cashbox_count_validated'
                        )
                    );

                    // Envelope
                    $total = $cashbox->getCashContainer()->getOnlyBillsTotal();
                    $total = floor($total - $total % 5);
                    $enveloppe = new Envelope();
                    $enveloppe->setSource(Envelope::CASHBOX_COUNTS)
                        ->setSourceId($cashbox->getId())
                        ->setOwner($cashbox->getOwner())
                        ->setCashier($cashbox->getCashier())
                        ->setAmount($total)
                        ->setOriginRestaurant(
                            $this->get('restaurant.service')
                                ->getCurrentRestaurant()
                        );

                    $enveloppeForm = $this->createForm(
                        EnvelopeType::class,
                        $enveloppe
                    );
                    $cashierWyndId = $cashbox->getCashier()->getWyndId();
                    $cashierName = $cashbox->getCashier()->getFirstName() . ' '
                        . $cashbox->getCashier()->getLastName();
                    $cashbox = $this->get('cashbox.service')->prepareCashbox(
                        new \DateTime('now')
                    );
                    $cashboxForm = $this->createForm(
                        CashboxCountType::class,
                        $cashbox,
                        array("restaurant" => $currentRestaurant)
                    );

                    $data = [
                        "data" => [
                            $this->renderView(
                                "@Financial/CashBox/Counting/parts/cashbox_count_block.html.twig",
                                [
                                    "form" => $cashboxForm->createView(),
                                    'paymentMethodStatus' => $this->get(
                                        'payment_method.status.service'
                                    ),
                                ]
                            ),
                            "enveloppe" => $this->renderView(
                                '@Financial/CashBox/Counting/modal/enveloppe_creation.html.twig',
                                [
                                    "form" => $enveloppeForm->createView(),
                                ]
                            ),
                            "operator" => $cashierWyndId,
                            "operator_name" => $cashierName,
                        ],
                    ];
                } catch (\Exception $e) {
                    $this->get('logger')->addAlert(
                        $e->getMessage(),
                        ['ValidatingCashboxGap']
                    );
                    throw new \Exception($e);
                }
            } else {
                $data = [
                    "data" => [
                        $this->renderView(
                            '@Financial/CashBox/Counting/modal/cashbox_gap.html.twig',
                            [
                                "cashbox" => $cashbox,
                                'paymentMethodStatus' => $this->get(
                                    'payment_method.status.service'
                                ),
                            ]
                        ),
                        "footer" => $this->renderView(
                            '@Financial/CashBox/Counting/modal/footer_cashbox_gap.html.twig'
                        ),
                    ],
                ];
            }
            $response->setData($data);

            return $response;
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @RightAnnotation("day_income")
     *
     * @Route("/day_income",name="day_income", options={"expose"=true})
     *
     * @Method({"GET", "POST"})
     */
    public function consultDayIncomeAction(Request $request)
    {
        $response = null;
        $dayIncome = new DayIncome();
        if ($request->getMethod() === "GET") {
            $dayIncome->setDate(new \DateTime('today'));
            $dayIncome->setCashboxCounts(
                $this->get('cashbox.service')->findCashboxCountsByDate(
                    $dayIncome->getDate()
                )
            );
            $dayIncomeForm = $this->createForm(
                DayIncomeType::class,
                $dayIncome
            );
        } else {
            $dayIncomeForm = $this->createForm(
                DayIncomeType::class,
                $dayIncome
            );
            $dayIncomeForm->handleRequest($request);
            if ($dayIncomeForm->isValid()) {
                $dayIncome->setCashboxCounts(
                    $this->get('cashbox.service')->findCashboxCountsByDate(
                        $dayIncome->getDate()
                    )
                );
            }
        }
        $this->get('day_income.service')->getDiscountsTotal($dayIncome);

        $bankCardPaymentParams = $this->get('paremeter.service')->getBankCardValues();
        $ticketRestaurantParams = $this->get('paremeter.service')->getTicketRestaurantValues();
        $electronicTicketRestaurantParams = $this->get('paremeter.service')->getTicketRestaurantValues(null, true);
        $response = $this->render(
            "@Financial/CashBox/DayIncome/day_income.html.twig",
            [
                'form' => $dayIncomeForm->createView(),
                'bankCardPaymentParams' => $bankCardPaymentParams,
                'ticketRestaurantParams' => $ticketRestaurantParams,
                'electronicTicketRestaurantParams' => $electronicTicketRestaurantParams,
                'dayIncome' =>$dayIncome
            ]
        );

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @RightAnnotation("cashbox_list")
     *
     * @Route("/list",name="cashbox_list", options={"expose"=true})
     *
     * @RightAnnotation("cashbox_list")
     */
    public function listAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        $form = $this->createForm(
            new CashboxCountsSearchType(),
            [
                'startDate' => new \DateTime('now'),
                'endDate' => new \DateTime('now'),
            ],
            array('restaurant' => $currentRestaurant)
        );

        return $this->render(
            '@Financial/CashBox/Listing/list_index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     *
     * @RightAnnotation("cashbox_list")
     *
     * @Route("/list_json/{download}",name="cashbox_list_json", options={"expose"=true})
     */
    public function cashboxListJsonAction(Request $request, $download = 0)
    {

        $download = intval($download);
        $orders = array(
            'date',
            'owner',
            'cashier',
            'realCaCounted',
            'theoricalCa',
            'difference',
            'createdAt',
        );
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get(
            'restaurant.service'
        )->getCurrentRestaurant();

        if (1 === $download) {
            $name = 'Liste_des_comptages_caisse';
            $response = $this->get('toolbox.document.generator')
                ->generateXlsFile(
                    'cashbox.service',
                    'listCashboxCounts',
                    [
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'search' => $dataTableHeaders['search'],
                        'onlyList' => true,
                    ],
                    $this->get('translator')->trans("cashbox.listing.title"),
                    [
                        $this->get('translator')->trans(
                            "cashbox.listing.header.date"
                        ),
                        $this->get('translator')->trans(
                            "cashbox.listing.header.owner"
                        ),
                        $this->get('translator')->trans(
                            "cashbox.listing.header.cashier"
                        ),
                        $this->get('translator')->trans(
                            "cashbox.listing.header.real"
                        ),
                        $this->get('translator')->trans(
                            "cashbox.listing.header.theorical"
                        ),
                        $this->get('translator')->trans(
                            "cashbox.listing.header.diff"
                        ),
                        $this->get('translator')->trans(
                            "cashbox.listing.header.createdAt"
                        ),
                    ],
                    function ($line) {
                        return [
                            $line['date'],
                            $line['owner'],
                            $line['cashier'],
                            number_format((float)str_replace(",", ".", $line['realCaCounted']), 2, ".", ""),
                            number_format((float)str_replace(",", ".", $line['theoricalCa']), 2, ".", ""),
                            number_format((float)str_replace(",", ".", $line['difference']), 2, ".", ""),
                            $line['createdAt'],
                        ];
                    },
                    $name
                );

            return $response;
        }

        $cashboxCounts = $this->getDoctrine()->getRepository(
            "Financial:CashboxCount"
        )->getCashboxCountsFilteredOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit'],
            $dataTableHeaders['search']
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $cashboxCounts['filtered'];
        $return['recordsTotal'] = $cashboxCounts['total'];
        $return['data'] = $this->get('cashbox.service')->serializeCashboxCounts(
            $cashboxCounts['list']
        );

        return new JsonResponse($return);
    }

    /**
     * @param CashboxCount $cashboxCount
     *
     * @return JsonResponse
     *
     * @RightAnnotation("cashbox_list")
     *
     * @Route("/json/details/{cashboxCount}",name="cashbox_count_detail",options={"expose"=true})
     */
    public function detailsExpenseJsonAction(CashboxCount $cashboxCount)
    {
		 $unique=uniqid('detailsCashbox_');
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
			
		/**
         * @var Logger $loggerFinancial
         */
        $loggerFinancial = $this->get('monolog.logger.financial');
        $tf1 = time();
        $loggerFinancial->addDebug('Start details Cashbox for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);

			
        $t1 = time();
        $loggerFinancial->addDebug('Start calculate value of CheckRestaurantContainer for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);

        //Calculer totale théorique
        /**
         * @var CashboxCheckRestaurantContainerService $ccrcs
         */
        $ccrcs= $this->get("cashbox.check.restaurant.container.service");
        $cashboxCount->getCheckRestaurantContainer()->setTheoricalTotal(
            $ccrcs->calculateTheoricalTotal($cashboxCount->getCheckRestaurantContainer(),false)
        ) ;
        $cashboxCount->getCheckRestaurantContainer()->setTheoricalTotalElectronic(
            $ccrcs->calculateTheoricalTotal($cashboxCount->getCheckRestaurantContainer(),true)
        ) ;
		 $t2 = time();
        $executionTime = $t2 - $t1;
        $loggerFinancial->addDebug('finish calculate value of CheckRestaurantContainer for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);
        $t1 = time();
        $loggerFinancial->addDebug('Start calculate value of CheckQuickContainer for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
       
        /**
         * @var CashboxCheckQuickContainerService $ccqcs
         */
        $ccqcs= $this->get("cashbox.check.quick.container.service");
        $cashboxCount->getCheckQuickContainer()->setTheoricalTotal(
            $ccqcs->calculateTheoricalTotal($cashboxCount->getCheckQuickContainer())
        ) ;
		
		    $t2 = time();
        $executionTime = $t2 - $t1;
        $loggerFinancial->addDebug('finish calculate value of CheckQuickContainer for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);

		  $t1 = time();
        $loggerFinancial->addDebug('Start calculate value of MealTicketContainer for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
       
        /**
         * @var CashboxMealTicketContainerService $cmtcs
         */
        $cmtcs= $this->get("cashbox.meal.ticket.container.service");
        $cashboxCount->getMealTicketContainer()->setTheoricalTotal(
            $cmtcs->calculateTheoricalTotal($cashboxCount->getMealTicketContainer())
        ) ;
		   $t2 = time();
        $executionTime = $t2 - $t1;
        $loggerFinancial->addDebug('finish calculate value of MealTicketContainer for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);

        $t1 = time();
        $loggerFinancial->addDebug('Start calculate value of DiscountContainer for the restaurant ' . $currentRestaurant->getCode().' : '.$unique);
       
        /**
         * Pour calculer totale théorique et totale amount de discount container
         * @var CashboxDiscountContainerService $cdcs
         */
        $cdcs = $this->get("cashbox.discount.container.service");
        $cashboxDiscount = $cashboxCount->getDiscountContainer();
		 $cashboxDiscount->setOriginRestaurantId($currentRestaurant->getId());
        $cashboxDiscount->setDiscountLabels(
            $cdcs->listDiscountLabes($cashboxDiscount)
        );
        $cashboxDiscount->setAmountByLabelsArray(
            $cdcs->generateAmountByLabels($cashboxDiscount)
        );
        $cashboxDiscount->setQuantityByLabelsArray(
            $cdcs->generateQuantityByLabels($cashboxDiscount)
        );
        $cashboxDiscount->setTotalQuantity(
            $cdcs->generateTotalQuantity($cashboxDiscount)
        );
        $cashboxDiscount->setTheoricalTotal(
            $cdcs->calculateTheoricalTotal( $cashboxDiscount)
        );
        $cashboxDiscount->setTotalAmount(
            $cdcs->calculateTotalAmount( $cashboxDiscount)
        );
		
		 $t2 = time();
        $executionTime = $t2 - $t1;
        $loggerFinancial->addDebug('finish calculate value of DiscountContainer for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);



        $cashboxForm = $this->createForm(
            CashboxCountType::class,
            $cashboxCount,
            array("restaurant" => $currentRestaurant)
        );
		
		$tf2 = time();
        $executionTime = $tf2 - $tf1;
        $loggerFinancial->addDebug('finish details cashbox for the restaurant ' . $currentRestaurant->getCode() . ' in time= ' . $executionTime.' : '.$unique);


        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Financial/CashBox/Counting/parts/cashbox_count_block.html.twig",
                    [
                        'form' => $cashboxForm->createView(),
                        'list' => true,
                    ]
                ),
                'dataFooter' => $this->renderView(
                    '@Financial/CashBox/Listing/parts/detail_footer.html.twig',
                    [
                        'cashboxCount' => $cashboxCount,
                        'paymentMethodStatus' => $this->get(
                            'payment_method.status.service'
                        ),
                    ]
                ),
            )
        );
    }

    /**
     * @param CashboxCount $cashboxCount
     *
     * @return JsonResponse
     *
     * @RightAnnotation("cashbox_list")
     *
     * @Route("/print/{cashboxCount}",name="cashbox_count_detail_print",options={"expose"=true})
     */
    public function printCashboxCountDetailAction(CashboxCount $cashboxCount)
    {
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();


        //Calculer totale théorique
        /**
         * @var CashboxCheckRestaurantContainerService $ccrcs
         */
        $ccrcs= $this->get("cashbox.check.restaurant.container.service");
        $cashboxCount->getCheckRestaurantContainer()->setTheoricalTotal(
            $ccrcs->calculateTheoricalTotal($cashboxCount->getCheckRestaurantContainer(),false)
        ) ;
        $cashboxCount->getCheckRestaurantContainer()->setTheoricalTotalElectronic(
            $ccrcs->calculateTheoricalTotal($cashboxCount->getCheckRestaurantContainer(),true)
        ) ;
        /**
         * @var CashboxCheckQuickContainerService $ccqcs
         */
        $ccqcs= $this->get("cashbox.check.quick.container.service");
        $cashboxCount->getCheckQuickContainer()->setTheoricalTotal(
            $ccqcs->calculateTheoricalTotal($cashboxCount->getCheckQuickContainer())
        ) ;
        /**
         * @var CashboxMealTicketContainerService $cmtcs
         */
        $cmtcs= $this->get("cashbox.meal.ticket.container.service");
        $cashboxCount->getMealTicketContainer()->setTheoricalTotal(
            $cmtcs->calculateTheoricalTotal($cashboxCount->getMealTicketContainer())
        ) ;

        /**
         * Pour calculer totale théorique et totale amount de discount container
         * @var CashboxDiscountContainerService $cdcs
         */
        $cdcs = $this->get("cashbox.discount.container.service");
        $cashboxDiscount=$cashboxCount->getDiscountContainer();
		 $cashboxDiscount->setOriginRestaurantId($currentRestaurant->getId());
        $cashboxDiscount->setTheoricalTotal(
            $cdcs->calculateTheoricalTotal( $cashboxDiscount)
        );
        $cashboxDiscount->setTotalAmount(
            $cdcs->calculateTotalAmount( $cashboxDiscount)
        );


        $cashboxForm = $this->createForm(
            CashboxCountType::class,
            $cashboxCount,
            array("restaurant" => $currentRestaurant)
        );

        $title = $this->get('translator')->trans(
            'cashbox.listing.title_download'
        );
        $filename = preg_replace(
            '([ :])',
            '_',
            'cashbox_count_detail_' . date('d_m_Y_H_i_s') . ".pdf"
        );

        $filePath = $this->get('toolbox.pdf.generator.service')
            ->generatePdfFromTwig(
                $filename,
                "@Financial/CashBox/Listing/print/print_detail.html.twig",
                [
                    'form' => $cashboxForm->createView(),
                    "title" => $title,
                    "download" => true,
                    'list' => true,
                    'paymentMethodStatus' => $this->get(
                        'payment_method.status.service'
                    ),
                ],
                [
                    'orientation' => 'Portrait',
                ],
                true
            );

        return Utilities::createFileResponse(
            $filePath,
            preg_replace('([ :])', '_', $title) . '_' . date('d_m_Y_H_i_s') . ".pdf"
        );
    }

    /**
     * @Route("/printDayIncome/{dayIncomedate}",name="day_income_print",options={"expose"=true})
     */
    public function dayIncomePrintAction($dayIncomedate)
    {
        $dayIncome = new DayIncome();
        $dayIncomedate = str_replace('_', '-', $dayIncomedate);
        $dayIncomedate = new \DateTime($dayIncomedate);
        $dayIncome->setDate($dayIncomedate);
        $dayIncome->setCashboxCounts(
            $this->get('cashbox.service')->findCashboxCountsByDate(
                $dayIncome->getDate()
            )
        );
        $bankCardPaymentParams = $this->get('paremeter.service')->getBankCardValues();
        $ticketRestaurantParams = $this->get('paremeter.service')->getTicketRestaurantValues();
        $electronicTicketRestaurantParams = $this->get('paremeter.service')->getTicketRestaurantValues(null, true);
        $title = $this->get('translator')->trans('cashbox.day_income_download');
        $filename = preg_replace(
            '([ :])',
            '_',
            'day_income' . date('d_m_Y_H_i_s') . ".pdf"
        );
        $filePath = $this->get('toolbox.pdf.generator.service')
            ->generatePdfFromTwig(
                $filename,
                "@Financial/CashBox/DayIncome/parts/day_income_print.html.twig",
                array('dayIncome' => $dayIncome, 'bankCardPaymentParams' => $bankCardPaymentParams,
                    'ticketRestaurantParams' => $ticketRestaurantParams,
                    'electronicTicketRestaurantParams' => $electronicTicketRestaurantParams)

            );

        return Utilities::createFileResponse(
            $filePath,
            preg_replace('([ :])', '_', $title) . '_' . date('d_m_Y_H_i_s') . ".pdf"
        );
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/delete_cashbox_count/{cashboxCount}",name="delete_cashbox_count", options={"expose"=true})
     */
    public function deleteCashboxCountAction(Request $request, CashboxCount $cashboxCount)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            try {
                $password = $request->request->get('password');
                $user = $this->getUser();
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $salt = $user->getSalt();
                if ($encoder->isPasswordValid($user->getPassword(), trim($password), $salt)) {
                    if ($cashboxCount->getSmallChest() == null) {
                        $em = $this->getDoctrine()->getManager();
                        $params['cashbox_count_id'] = $cashboxCount->getId();
                        $params1['restaurant_id'] = $cashboxCount->getOriginRestaurant()->getId();
                        $params1['cashbox_count_id'] = $cashboxCount->getId();
                        $sql11 = "update ticket set counted =false where id in (select ticket_id from ticket_payment where real_cash_container_id in ( select id from cashbox_real_cash_container where cashbox_id= :cashbox_count_id)) and origin_restaurant_id = :restaurant_id";
                        $stm11 = $em->getConnection()->prepare($sql11);
                        $stm11->execute($params1);

                        $sql1 = "update ticket_payment set real_cash_container_id=null where real_cash_container_id in ( select id from cashbox_real_cash_container where cashbox_id= :cashbox_count_id)";
                        $stm1 = $em->getConnection()->prepare($sql1);
                        $stm1->execute($params);

                        $sql12 = "update ticket set counted =false where id in (select ticket_id from ticket_payment where check_restaurant_container_id in (select id from cashbox_check_restaurant_container where cashbox_id= :cashbox_count_id)) and origin_restaurant_id = :restaurant_id";
                        $stm12 = $em->getConnection()->prepare($sql12);
                        $stm12->execute($params1);

                        $sql2 = "update ticket_payment set check_restaurant_container_id=null where check_restaurant_container_id in (select id from cashbox_check_restaurant_container where cashbox_id= :cashbox_count_id)";
                        $stm2 = $em->getConnection()->prepare($sql2);
                        $stm2->execute($params);

                        $sql13 = "update ticket set counted =false where id in (select ticket_id from ticket_payment where bank_card_container_id in (select id from cashbox_bank_card_container where cashbox_id= :cashbox_count_id)) and origin_restaurant_id = :restaurant_id";
                        $stm13 = $em->getConnection()->prepare($sql13);
                        $stm13->execute($params1);

                        $sql3 = "update ticket_payment set bank_card_container_id=null where bank_card_container_id in (select id from cashbox_bank_card_container where cashbox_id= :cashbox_count_id)";
                        $stm3 = $em->getConnection()->prepare($sql3);
                        $stm3->execute($params);

                        $sql14 = "update ticket set counted =false where id in (select ticket_id from ticket_payment where check_quick_container_id in (select id from cashbox_check_quick_container where cashbox_id= :cashbox_count_id)) and origin_restaurant_id = :restaurant_id";
                        $stm14 = $em->getConnection()->prepare($sql14);
                        $stm14->execute($params1);

                        $sql4 = "update ticket_payment set check_quick_container_id=null where check_quick_container_id in (select id from cashbox_check_quick_container where cashbox_id= :cashbox_count_id)";
                        $stm4 = $em->getConnection()->prepare($sql4);
                        $stm4->execute($params);

                        $sql15 = "update ticket set counted =false where id in (select ticket_id from ticket_payment where meal_ticket_container_id in (select id from cashbox_meal_ticket_container where cashbox_id= :cashbox_count_id)) and origin_restaurant_id = :restaurant_id";
                        $stm15 = $em->getConnection()->prepare($sql15);
                        $stm15->execute($params1);

                        $sql5 = "update ticket_payment set meal_ticket_container_id=null where meal_ticket_container_id in (select id from cashbox_meal_ticket_container where cashbox_id= :cashbox_count_id)";
                        $stm5 = $em->getConnection()->prepare($sql5);
                        $stm5->execute($params);

                        $sql16 = "update ticket set counted =false where id in (select ticket_id from ticket_payment where foreign_currency_container_id in (select id from cashbox_foreign_currency_container where cashbox_id= :cashbox_count_id)) and origin_restaurant_id = :restaurant_id";
                        $stm16 = $em->getConnection()->prepare($sql16);
                        $stm16->execute($params1);

                        $sql6 = "update ticket_payment set foreign_currency_container_id=null where foreign_currency_container_id in (select id from cashbox_foreign_currency_container where cashbox_id= :cashbox_count_id)";
                        $stm6 = $em->getConnection()->prepare($sql6);
                        $stm6->execute($params);

                        $sql7 = "update ticket_line set discount_container_id=null where discount_container_id in (select id from cashbox_discount_container where cashbox_id= :cashbox_count_id) and origin_restaurant_id = :restaurant_id";
                        $stm7 = $em->getConnection()->prepare($sql7);
                        $stm7->execute($params1);
                        $sql8 = "update ticket set cashbox_count_id=null where cashbox_count_id = :cashbox_count_id and origin_restaurant_id = :restaurant_id";
                        $stm8 = $em->getConnection()->prepare($sql8);
                        $stm8->execute($params1);
                        $sql9 = "update withdrawal set cashbox_count_id=null, status_count='not_counted' where cashbox_count_id= :cashbox_count_id";
                        $stm9 = $em->getConnection()->prepare($sql9);
                        $stm9->execute($params);
                        //Supprimer les enveloppes
                        /**
                         * @var EnvelopeService $es
                         */
                        $es = $this->get('envelope.service');
                        $es->removeCashboxEnvelope($cashboxCount->getId());

                        foreach ($cashboxCount->getBankCardContainer()->getBankCardCounts() as $bankCardCount) {
                            $em->remove($bankCardCount);
                        }
                        $em->flush();
                        foreach ($cashboxCount->getCheckQuickContainer()->getCheckQuickCounts() as $checkQuickCount) {
                            $em->remove($checkQuickCount);
                        }
                        $em->flush();
                        foreach ($cashboxCount->getCheckRestaurantContainer()->getTicketRestaurantCounts() as $ticketRestaurantCount) {
                            $em->remove($ticketRestaurantCount);
                        }
                        $em->flush();
                        foreach ($cashboxCount->getForeignCurrencyContainer()->getForeignCurrencyCounts() as $foreignCurrencyCount) {
                            $em->remove($foreignCurrencyCount);
                        }
                        $em->flush();
                        foreach ($cashboxCount->getMealTicketContainer()->getMealTicketCounts() as $mealTicketCount) {
                            $em->remove($mealTicketCount);
                        }
                        $em->flush();
                        $em->remove($cashboxCount);
                        $em->flush();
                        $message = $this->get('translator')->trans('cashbox_success_deleted');
                        $this->get('session')->getFlashBag()->add('success', $message);
                        $response->setData(
                            array(
                                "deleted" => 1,
                            )
                        );
                    } else {

                        //cashbox counting lié à un comptage coffre
                        $response->setData(
                            array(
                                "deleted" => 2,
                            )
                        );
                    }
                } else {
                    //invalid password
                    $response->setData(
                        array(
                            "deleted" => -1,
                        )
                    );
                }

            } catch (\Exception $e) {
                $response->setData(
                    array(
                        "deleted" => 0,
                        "errors" => array(
                            $this->get('translator')->trans('Error.general.internal'),
                            $e->getLine() . " : " . $e->getTraceAsString(),
                        ),
                    )
                );
            }

            return $response;
        } else {
            throw new AccessDeniedHttpException("This method accept only ajax calls.");
        }
    }
}
