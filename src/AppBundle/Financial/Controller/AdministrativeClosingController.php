<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/05/2016
 * Time: 15:55
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\AdminClosingTmp;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\RightAnnotation;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class AdministrativeClosingController
 *
 * @Route("/financial")
 */
class AdministrativeClosingController extends Controller
{

    /**
     * @Route("/kiosk_counting/{list}",name="kiosk_counting",options={"expose"=true},defaults={"list"="false"})
     * @RightAnnotation("kiosk_counting")
     * @param $list
     *
     * @return Response
     */

    public function kioskCashboxCountingAction($list)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $closureDate = $this->get('administrative.closing.service')
            ->getCurrentClosingDate($restaurant);


        if ($list != "true") {
            $progression = new ImportProgression();

            $progression->setNature('kiosk_counting')
                ->setStatus('pending')
                ->setStartDateTime(new \DateTime());

            $this->getDoctrine()->getManager()->persist($progression);

            $this->getDoctrine()->getManager()->flush();

            $this->get('toolbox.command.launcher')->execute(
                'saas:kiosk:counting' . ' ' . $restaurant->getId() . ' '
                . $this->getUser()->getId() . ' ' . $progression->getId()
            );

            return $this->render(
                "@Financial/AdministrativeClosing/kiosk_counting.html.twig",
                array(
                    'list' => false,
                    'progressID' => $progression->getId(),
                    'closureDate' => $closureDate,
                )
            );
        } else {

            $cashboxs = $this->get('cashbox.service')->kioskCashboxList(
                $restaurant,
                $closureDate
            );

            return $this->render(
                "@Financial/AdministrativeClosing/kiosk_counting.html.twig",
                array(
                    'list' => true,
                    'cashboxs' => $cashboxs,
                    'closureDate' => $closureDate,
                )
            );
        }

    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/verify_last_date",name="verify_last_date",options={"expose"=true})
     * @RightAnnotation("kiosk_counting")
     */
    public
    function verifyYesterdaysWorks(
        Request $request
    )
    {

        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        if ($this->get('workflow.service')->inAdministrativeClosing()
            == false
        ) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'you_must_be_in_cloture'
            );

            return $this->redirectToRoute('index_workflows');
        }

        $dcac = $this->get('administrative.closing.service')
            ->getCurrentClosingDate($restaurant);

        $lastDate = $this->get('administrative.closing.service')
            ->getLastWorkingEndDate();

        $caHTva = $this->get('report.sales.service')->getCaHTva($dcac, $dcac);

        $this->get('workflow.service')->setSubStep(1);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                array(
                    'data' => array(
                        'lastDate' => $lastDate->format('d/m/Y'),
                        'continue' => ($dcac->format('Ymd') < $lastDate->format(
                                'Ymd'
                            )),
                    ),
                )
            );
        }


// if (!$continue){
        return $this->render(
            "@Financial/AdministrativeClosing/verify_yesterday_work.html.twig",
            array(
                'lastDate' => $lastDate->format('d/m/Y'),
                'caHTva' => $caHTva,
                'rapport_z_url' => $this->container->hasParameter(
                    'rapport_z_url'
                ) ? $this->container->getParameter('rapport_z_url') : '#'
                // 'continue' => $continue
            )
        );
        // }else{
        // return $this->redirectToRoute('validation_income_show');
        // }
    }


    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     *
     * @Route("/validation_income_show",name="validation_income_show",options={"expose"=true})
     *
     * @RightAnnotation("kiosk_counting")
     */
    public
    function validationIncomeShowAction(
        Request $request
    )
    {
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        if ($this->get('workflow.service')->inAdministrativeClosing()
            == false
        ) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'you_must_be_in_cloture'
            );

            return $this->redirectToRoute('index_workflows');
        }
        $date = $this->get('administrative.closing.service')
            ->getCurrentClosingDate($currentRestaurant);

        if ($request->getMethod() === 'POST') {
            if ($request->request->has('ca_brut_ttc')) {
                $caBrut = $request->request->get('ca_brut_ttc');
                $adminClosingTmp = $this->getDoctrine()->getRepository(
                    "Financial:AdminClosingTmp"
                )->findOneBy(
                    ['date' => $date, 'originRestaurant' => $currentRestaurant]
                );
                if (!$adminClosingTmp) {
                    $adminClosingTmp = new AdminClosingTmp();
                    $adminClosingTmp->setDate($date);
                    $adminClosingTmp->setOriginRestaurant($currentRestaurant);
                    $this->getDoctrine()->getManager()->persist(
                        $adminClosingTmp
                    );
                }
                $adminClosingTmp->setCaBrutTTCRapportZ($caBrut);
                $this->getDoctrine()->getManager()->flush();
            }
        }


        $this->get('workflow.service')->setSubStep(2);
        $incomeDay = new DayIncome();

        $incomeDay->setDate($date);
        $incomeDay->setCashboxCounts(
            $this->get('cashbox.service')->findCashboxCountsByDate(
                $incomeDay->getDate()
            )
        );
        $this->get('day_income.service')->getDiscountsTotal($incomeDay);
        $bankCardPaymentParams = $this->get('paremeter.service')->getBankCardValues();
        $ticketRestaurantParams = $this->get('paremeter.service')->getTicketRestaurantValues();
        $electronicTicketRestaurantParams = $this->get('paremeter.service')->getTicketRestaurantValues(null, true);

        $ticketNotCount = $this->getDoctrine()->getRepository(
            "Financial:Ticket"
        )->findBy(
            array(
                'counted' => false,
                'date' => $date,
                'originRestaurant' => $currentRestaurant,
            )
        );

        $operators = [];
        foreach ($ticketNotCount as $t) {
            $o = $this->getDoctrine()->getRepository(Employee::class)
                ->createQueryBuilder('emp')
                ->where('emp.wyndId = :wyndId')
                ->andWhere(':restaurant MEMBER OF emp.eligibleRestaurants')
                ->setParameters(
                    array(
                        'wyndId' => $t->getOperator(),
                        'restaurant' => $currentRestaurant,
                    )
                )
                ->getQuery()
                ->getSingleResult();

            if (!in_array($o, $operators) && $o) {
                $operators[] = $o;
            }
        }

        if (count($operators)) {
            $countComplete = false;
        } else {
            $countComplete = true;
        }

        return $this->render(
            "@Financial/AdministrativeClosing/validation_income.html.twig",
            array(
                'dayIncome' => $incomeDay,
                'count_complete' => $countComplete,
                'ticketsNotCount' => $ticketNotCount,
                'operators' => $operators,
                'bankCardPaymentParams' => $bankCardPaymentParams,
                'ticketRestaurantParams' => $ticketRestaurantParams,
                'electronicTicketRestaurantParams' => $electronicTicketRestaurantParams,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/validation_income_validate",name="validation_income_validate",options={"expose"=true})
     *
     * @RightAnnotation("kiosk_counting")
     */
    public
    function validateIncomeValidateAction()
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $em = $this->getDoctrine()->getManager();

        if ($this->get('workflow.service')->inAdministrativeClosing()
            === false
        ) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'you_must_be_in_cloture'
            );

            return $this->redirectToRoute('index_workflows');
        }

        if ($this->get('workflow.service')->getSubStep() == 2) {
            $incomeDay = new DayIncome();
            $date = $this->get('administrative.closing.service')
                ->getCurrentClosingDate($restaurant);
            $incomeDay->setDate($date);
            $incomeDay->setCashboxCounts(
                $this->get('cashbox.service')->findCashboxCountsByDate(
                    $incomeDay->getDate()
                )
            );

            $recipeExist = $em->getRepository(RecipeTicket::class)->findOneBy(['date' => $date, 'originRestaurant' => $restaurant, 'label' => RecipeTicket::CACHBOX_RECIPE]);

            if (!$recipeExist) {
                $diff = $incomeDay->calculateCashboxTotalGap();


                if ($diff < 0) {//Create depense
                    $expense = new Expense();
                    $expense->setAmount(abs($diff))
                        ->setDateExpense($date)
                        ->setResponsible($this->getUser())
                        ->setGroupExpense(Expense::GROUP_ERROR_COUNT)
                        ->setSousGroup(Expense::ERROR_CASHBOX)
                        ->setReference(
                            $this->get('expense.service')->getLastRefExpense(
                                $restaurant
                            ) + 1
                        )
                        ->setOriginRestaurant($restaurant);
                    $em->persist($expense);
                } elseif ($diff > 0) {//Create Recette
                    $recipe = new RecipeTicket();
                    $recipe->setAmount($diff)
                        ->setOwner($this->getUser())
                        ->setLabel(RecipeTicket::CASHBOX_ERROR)
                        ->setDate($date);

                    $this->get('recipe_ticket.service')->saveRecipeTicket(
                        $recipe
                    );
                    $em->persist($recipe);
                }

                $recipeTicket = new RecipeTicket();
                $recipeTicket->setDate($date)
                    ->setAmount($incomeDay->calculateCashboxTotal())
                    ->setOwner($this->getUser())
                    ->setLabel(RecipeTicket::CACHBOX_RECIPE)
                    ->setOriginRestaurant($restaurant);
                $em->persist($recipeTicket);

                $em->flush();
            }
            $this->get('workflow.service')->setSubStep(3);
        }

        return $this->redirectToRoute("deposit_V2");
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     *
     * @Route("/comparable_day",name="comparable_day", options={"expose"=true} )
     *
     * @RightAnnotation("kiosk_counting")
     */
    public
    function closeAction(
        Request $request
    )
    {
        if ($this->get('workflow.service')->inAdministrativeClosing()
            === false
        ) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'you_must_be_in_cloture'
            );

            return $this->redirectToRoute('index_workflows');
        }

        $this->get('administrative.closing.service')->resetInChestCount();


        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        $date = $this->get('administrative.closing.service')
            ->getCurrentClosingDate($currentRestaurant);
        if ($request->getMethod() === 'POST') {

            $adminClosing = $this->getDoctrine()->getRepository(
                "Financial:AdministrativeClosing"
            )
                ->findOneBy(
                    array(
                        'date' => $date,
                        'originRestaurant' => $currentRestaurant,
                    )
                );

            if (!$adminClosing) {
                $adminClosing = new AdministrativeClosing();
                $adminClosing->setDate($date);
                $adminClosing->setOriginRestaurant($currentRestaurant);
                $this->getDoctrine()->getManager()->persist($adminClosing);
            }

            $data = $request->request->getIterator();
            $decision = $data['radio'];

            if ('no' == $decision) {
                $adminClosing->setComparable(false);
            } else {
                $adminClosing->setComparable(true);
            }
            $comment = isset($data['comment']) ? $data['comment'] : null;
            $adminClosing->setComment($comment);
            $cred = $this->get('cash.book.report')->generateCashbookReport(
                $date, $currentRestaurant
            );
            $adminClosing->setCreditAmount($cred);

            $adminClosingTmp = $this->getDoctrine()->getRepository(
                "Financial:AdminClosingTmp"
            )->findOneBy(
                ['date' => $date, 'originRestaurant' => $currentRestaurant]
            );
            if ($adminClosingTmp) {
                $adminClosing->setCaBrutTTCRapportZ(
                    $adminClosingTmp->getCaBrutTTCRapportZ()
                );
            }

            $this->getDoctrine()->getManager()->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans(
                    'close_day_success',
                    array(
                        '%1%' => $date->format('d/m/Y'),
                    )
                )
            );
            $this->get('workflow.service')->setSubStep(null);

            $this->get('toolbox.command.launcher')->execute(
                "quick:optikitchen:automatic " . $currentRestaurant->getId()
            );
            $this->get('toolbox.command.launcher')->execute(
                "quick:financial:revenue:import " . $date->format('Y-m-d') . " "
                . $date->format('Y-m-d')
            );

            return $this->get('workflow.service')->nextStep();
        } else {
            $this->get('workflow.service')->setSubStep(6);
        }
        return $this->render(
            "@Financial/AdministrativeClosing/comparable_day.html.twig",
            array(
                'today' => $date->format('d/m/Y'),
            )
        );
    }

    /**
     * @return JsonResponse
     *
     * @Route("/in_chest_count",name="in_chest_count",options={"expose"=true})
     */
    public
    function inChestCountAction()
    {
        $x = $this->get('administrative.closing.service')->inChestCount();

        return new JsonResponse(
            array(
                'in' => $x,
            )
        );
    }

    /**
     * @Route("/deposit_v2",name="deposit_V2",options={"expose"=true})
     */
    public function depositV2Action()
    {


        if ($this->get('workflow.service')->inAdministrativeClosing()) {

            $this->get('administrative.closing.service')->setInChestCount();

            $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();

            $date = $this->get('administrative.closing.service')
                ->getCurrentClosingDate($currentRestaurant);

            $adminClosingTmp = $this->getDoctrine()->getRepository(
                "Financial:AdminClosingTmp"
            )->findOneBy(
                ['date' => $date, 'originRestaurant' => $currentRestaurant]
            );


            $chestCount = $this->get('chest.service')->loadLastChestCount();

            if ($adminClosingTmp && !$adminClosingTmp->getDeposed()) {


                $em = $this->getDoctrine()->getManager();


                try {
                    $em->beginTransaction();

                    $closingDate = $this->get('administrative.closing.service')
                        ->getCurrentClosingDate();

                    $result['card'] = $this->get('deposit.service')
                        ->depositElectronicV2(
                            $chestCount,
                            Deposit::TYPE_BANK_CARD,
                            $closingDate
                        );
                    $result['ticket'] = $this->get('deposit.service')
                        ->depositElectronicV2(
                            $chestCount,
                            Deposit::TYPE_E_TICKET,
                            $closingDate
                        );

                    $em->flush();
                    $em->commit();

                    $adminClosingTmp->setDeposed(true);
                    $em->persist($adminClosingTmp);
                    $em->flush();
                    $this->get('administrative.closing.service')
                        ->setInChestCount();
                    $this->get('workflow.service')->setSubStep(4);

                } catch (Exception $e) {

                    $em->rollback();
                    throw new Exception($e);
                }


            } else {

                $result['card'] = $this->get('deposit.service')
                    ->getDepositElectronicV2(
                        $chestCount,
                        Deposit::TYPE_BANK_CARD
                    );
                $result['ticket'] = $this->get('deposit.service')
                    ->getDepositElectronicV2(
                        $chestCount,
                        Deposit::TYPE_E_TICKET
                    );
                $this->get('workflow.service')->setSubStep(4);

            }

            return $this->render(
                '@Financial/Deposit/Electronic/indexV2.html.twig',
                array(
                    'result' => $result,
                )
            );

        } else {
            throw  new NotFoundHttpException('Page not found');
        }
    }

    /**
     * @Route("/notify_ca", name="notify_ca", options={"expose"=true})
     */
    public function caNotifyByEmail(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $responsible = $this->getDoctrine()->getRepository(Employee::class)->find($this->getUser()->getId());
            $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
            $type = $currentRestaurant->getType();
            if ($type == Restaurant::COMPANY || $type == Restaurant::FRANCHISE) {
                $to = [];
                // Ajouter l'email du manager du restaurant si défini
                $managerEmail = $currentRestaurant->getManagerEmail();
                if ($managerEmail) {
                    $to[] = $managerEmail;
                }
                // Si aucun email n'est trouvé, ne pas envoyer l'email
                if (!empty($to)) {
                    $caAloha = $request->request->get('caAloha');
                    $caTalan = $request->request->get('caBO');
                    $body = $this->get('translator')->trans(
                            'ca_notify.email',
                            array(
                                '%1%' => $caAloha,
                                '%2%' => $responsible->getFirstName(),
                                '%3%' => $responsible->getLastName(),
                                '%4%' => $caTalan
                            )
                        ) . "\n " . $currentRestaurant->getName() . " " . $currentRestaurant->getCode();

                    $fromMail = $this->getParameter('sender_adress');
                    $mail = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('ca_notify.subject'))
                        ->setFrom([$fromMail])
                        ->setTo($to)
                        ->setCc("mohamedali.zouai@talan.com")
                        ->setBody($body);

                    $this->get('mailer')->send($mail);
                }
            }

            return new Response();
        }
    }


    /**
     * @Route("/verifying_mdp",name="verifying_mdp",options={"expose"=true})
     */
    public function verifyingMdp(Request $request)
    {
        $response = new JsonResponse();
        try {
            $password = $request->request->get('password');
            $user = $this->getUser();
            $factory = $this->get('security.encoder_factory');
            $encoder = $factory->getEncoder($user);
            $salt = $user->getSalt();
            if ($encoder->isPasswordValid($user->getPassword(), trim($password), $salt)) {
                return $response->setData(array('res' => 1));
            } else {
                return $response->setData(array('res' => 0));
            }
        } catch (\Exception $e) {
            $response->setData(array('res' => -1)
            );
        }
    }


    /**
     * @Route("/check_withdrawal_envelope/{validate}",name="check_withdrawal_envelope", defaults={"validate"=null},options={"expose"=true})
     * @return Response
     */
    public function checkWithdrawalEnvelopeAction($validate)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $ClosingDate = $this->get('administrative.closing.service')->getCurrentClosingDate($restaurant);
        $lastClosingDate = $this->get('administrative.closing.service')->getLastClosingDate($restaurant);
        if ($this->get('workflow.service')->inAdministrativeClosing()
            === false
        ) {
            $this->get('session')->getFlashBag()->add(
                'warning',
                'you_must_be_in_cloture'
            );

            return $this->redirectToRoute('index_workflows');
        }
        if ($this->get('workflow.service')->getSubStep() == 4) {
            list($withdrawals, $wsAmount) = $this->get('envelope.service')->calculateTotalAmountOfWithdrwals($restaurant, $ClosingDate, false);
            list($envelopes, $esAmount) = $this->getEnvelopesTotalAmount($restaurant,$lastClosingDate);
            $validateAmount = $wsAmount - $esAmount;
            $isValidateStep = $validateAmount > 0 ? false : true;
            if ($validate === null) {
                $errorMessage = '';
                if (!$isValidateStep) {
                    $errorMessage = $this->get('translator')->trans('admin_closing.error_during_step_check_withdrawal_envelope', ['%validateAmount%' => $validateAmount,
                        '%link%' => $this->generateUrl('create_envelope_cash')]);
                }

                return $this->render(
                    '@Financial/Envelope/check_withdrawal_envelope.html.twig',
                    array(
                        'withdrawals' => $withdrawals,
                        'envelopes' => $envelopes,
                        'isValidateStep' => $isValidateStep,
                        'errorMessage' => $errorMessage,
                        'wsAmount' => $wsAmount,
                        'esAmount' => $esAmount,
                    )
                );
            } else if ($validate && $isValidateStep) {
                $this->get('workflow.service')->setSubStep(5);
                return $this->get('workflow.service')->nextStep(
                    $this->redirect($this->get('router')->generate('chest_count'))
                );
            }
        } else {
            return $this->redirectToRoute('index_workflows');
        }
    }

    /**
     *
     * @param $restaurant
     * @param $lastClosingDate
     * @return array
     */
    private function getEnvelopesTotalAmount($restaurant, $lastClosingDate)
    {
        $lcc=$this->getDoctrine()->getRepository(ChestCount::class)->getChestCountForClosedDate($lastClosingDate,$restaurant);
        if ($lcc !== null) {
            $es = $this->getDoctrine()->getRepository(Envelope::class)->getEnvelopeWithdrawalOfClosing($restaurant, $lcc->getCreatedAt());
            $amount = 0;
            foreach ($es as $e) {
                $amount += $e->getAmount();
            }
            return [$es, $amount];
        } else {
            return [[], 0];
        }
    }


}
