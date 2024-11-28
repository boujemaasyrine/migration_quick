<?php

namespace AppBundle\General\Controller;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\Financial\Service\TicketService;
use AppBundle\General\Form\ChangeLanguageType;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Staff\Form\Management\ChangeEmailType;
use AppBundle\Staff\Form\Management\ChangePasswordType;
use AppBundle\ToolBox\Utils\DateUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 *
 * @package AppBundle\Default\Controller
 */
class DefaultController extends Controller
{
    const DATE_RANGE_TODAY = 1;
    const DATE_RANGE_WEEK=2;
    const DATE_RANGE_MONTH=3;
    const DATE_RANGE_DAY_BEFORE=0;

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="index",options={"expose"=true})
     */
    public function indexAction()
    {


        $chestCount = $this->get('chest.service')->loadLastChestCount();

        return $this->render(
            '@General/home.html.twig',
            array(
                'home_page'      => true,
                'lastChestCount' => $chestCount
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_hour_by_hour", name="dashboard_hour_by_hour",options={"expose"=true})
     */
    public function hourByHourAction(Request $request)
    {
        $dateRange=$request->query->get('date_range');
        if(is_null($dateRange)){
            $dateRange=self::DATE_RANGE_TODAY;
        }
        $dateRange=$this->getDatesRange($dateRange);
        $criteria['from'] = $dateRange['from']->format('d-m-Y');
        $criteria['to'] = $dateRange['to']->format('d-m-Y');
        $allResult = $this->get('report.sales.service')
            ->generateHourByHourReport(
                $criteria,
                0
            );
        $result = $allResult['result'];
        $openingHour = $allResult['openingHour'];
        $closingHour = $allResult['closingHour'];

        return new JsonResponse(
            array(
                "hourByHour"   => $result,
                'opening_hour' => $openingHour,
                'closing_hour' => $closingHour,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/ca_budget", name="dashboard_ca_budget",options={"expose"=true})
     */
    public function caBudgetAction(Request $request)
    {
        $dateRange=$request->query->get('date_range');
        if(is_null($dateRange)){
            $dateRange=self::DATE_RANGE_TODAY;
        }
        $dateRange=$this->getDatesRange($dateRange);
        if($dateRange['from']==$dateRange['to']){
            $dateRange['from'] =clone $dateRange['to'];
            $dateRange['from']->modify('-1 month');
        }
        $period = DateUtilities::getDays(
            $dateRange['from'],
            $dateRange['to']
        );

        $dayResult = [];
        foreach ($period as $date) {
            $dayResult[] = $this->get('dashboard.service')->getCaVsCaOne($date);
        }

        return new JsonResponse($dayResult);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_day_income_api", name="dashboard_day_income_api",options={"expose"=true})
     */
    public function dayIncomeAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $totalGap=$this->get('cashbox.service')->calculateCashBoxTotalGap($dateRange['from'],$dateRange['to'],$restaurant);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'diff_cashbox' => empty($totalGap) ? 0 : $totalGap,

                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_ca_prev_api", name="dashboard_ca_prev_api",options={"expose"=true})
     */
    public function caPrevApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $ca_prev = $this->get('ca_prev.service')->getCumulCaPrevBetweenDate($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'ca_prev' => $ca_prev,

                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
                array(
                    'status' => 0,
                    'message'=> 'Not Ajax request!'
                )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_ca_brut_api", name="dashboard_ca_brut_api",options={"expose"=true})
     */
    public function caBrutApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $caBrut = $this->get('report.sales.service')->getCaBrutInDates($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'caBrut' => $caBrut,
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_ca_net_htva_api", name="dashboard_ca_net_htva_api",options={"expose"=true})
     */
    public function caNetHtvaApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $caNetHTva = $this->get('report.sales.service')->getCaHTva($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'caNetHTva' => $caNetHTva,
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_cancellation_api", name="dashboard_cancellation_api",options={"expose"=true})
     */
    public function cancellationApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $cancels = $this->get('ticket.service')->getCancellation($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'cancellations_count' => $cancels['nbr_cancels'],
                        'cancellations_value' => $cancels['total_ttc'],
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_abandons_api", name="dashboard_abandons_api",options={"expose"=true})
     */
    public function abandonsApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $abandons = $this->get('ticket.service')->getAbandons($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'abandons_count' => $abandons['nbr_abandons'],
                        'abandons_value' => $abandons['total_ttc'],
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_corrections_api", name="dashboard_corrections_api",options={"expose"=true})
     */
    public function correctionsApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $corrections = $this->get('ticket.service')->getCorrections($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'corrections_count' => $corrections['nbr_corrections'],
                        'corrections_value' => $corrections['total_ttc'],
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_tickets_count_api", name="dashboard_tickets_count_api",options={"expose"=true})
     */
    public function ticketsCountApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $tickets = $this->get('ticket.service')->getTicketsCount($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'tickets_count'=>$tickets
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_not_counted_cashbox_api", name="dashboard_not_counted_cashbox_api",options={"expose"=true})
     */
    public function notCountedCashboxApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $notCounted = $this->get('cashbox.service')->getNotCountedCashBoxCount($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'not_counted_cashbox_count'=>$notCounted
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_takeout_percentage_api", name="dashboard_takeout_percentage_api",options={"expose"=true})
     */
    public function takeOutApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $takeOut = $this->get('ticket.service')->takeout($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'takeout_percentage' => $takeOut,
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_drive_percentage_api", name="dashboard_drive_percentage_api",options={"expose"=true})
     */
    public function driveApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $drive = $this->get('ticket.service')->drive($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'drive_percentage' => $drive,
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_kiosk_percentage_api", name="dashboard_kiosk_percentage_api",options={"expose"=true})
     */
    public function kioskApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $kiosk = $this->get('ticket.service')->kiosk($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'kiosk_percentage' => $kiosk,
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_delivery__percentage_api", name="dashboard_delivery_percentage_api",options={"expose"=true})
     */
    public function deliveryApiAction(Request $request)
    {
        if($request->isXmlHttpRequest()) {
            try {
                $dateRange=$request->query->get('date_range');
                if(is_null($dateRange)){
                    $dateRange=self::DATE_RANGE_TODAY;
                }
                $dateRange=$this->getDatesRange($dateRange);
                $delivery = $this->get('ticket.service')->delivery($dateRange['from'],$dateRange['to']);
                return new JsonResponse(
                    array(
                        'status' => 1,
                        'delivery_percentage' => $delivery,
                    )
                );
            }catch(\Exception $e) {
                return new JsonResponse(
                    array(
                        'status' => -1,
                        'message' => $e->getMessage(),
                    )
                );
            }

        }
        return new JsonResponse(
            array(
                'status' => 0,
                'message'=> 'Not Ajax request!'
            )
        );

    }
	
 //added by belsem 2020	
    /**	
     * @return \Symfony\Component\HttpFoundation\Response	
     * @Route("/dashboard_e_ordering_percentage_api", name="dashboard_e_ordering_percentage_api",options={"expose"=true})	
     */	
    public function eOrderingApiAction(Request $request)	
    {	
        if($request->isXmlHttpRequest()) {	
            try {	
                $dateRange=$request->query->get('date_range');	
                if(is_null($dateRange)){	
                    $dateRange=self::DATE_RANGE_TODAY;	
                }	
                $dateRange=$this->getDatesRange($dateRange);	
                $e_ordering = $this->get('ticket.service')->eOrdering($dateRange['from'],$dateRange['to']);	
                return new JsonResponse(	
                    array(	
                        'status' => 1,	
                        'e_ordering_percentage' => $e_ordering,	
                    )	
                );	
            }catch(\Exception $e) {	
                return new JsonResponse(	
                    array(	
                        'status' => -1,	
                        'message' => $e->getMessage(),	
                    )	
                );	
            }	
        }	
        return new JsonResponse(	
            array(	
                'status' => 0,	
                'message'=> 'Not Ajax request!'	
            )	
        );	
    }
    /**
     * @param Request $request
     * @param string  $locale
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/locale/{locale}", name="locale_switch",options={"expose"=true})
     */
    public function localeAction(Request $request, $locale)
    {
        $request->getSession()->set('_locale', $locale);

        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            throw $this->createNotFoundException('Page not found.');
        }

        return $this->redirect($referer);
    }

    /**
     * @Route("/test_remote")
     */
    public function testRemote()
    {
        $synchronizedTicket = $this->getDoctrine()->getManager()->getRepository(
            'Financial:Ticket'
        )
            ->findOneBy(['synchronized' => true]);
        $response = $this->container->get('sync.remove_ticket.service')
            ->removeTicket(null, $synchronizedTicket);

        return $this->render(
            "@General/home.html.twig",
            array('raw_html' => $response)
        );
    }

    /**
     * @Route("/connection_status", name="connection_status",options={"expose"=true})
     */
    public function connectionStatus()
    {
        /* $connected = $this->get('bo_status.service')->connectionStatus();
        $lastSynchronizationDate = $this->get('bo_status.service')->lastSynchronizationDate();

        return $this->render(
            "@General/parts/status.html.twig",
            array(
                'connected' => $connected,
                'lastSynchronizationDate' => $lastSynchronizationDate,
            )
        );*/
        return new Response();
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/profile", name="user_profile")
     */
    public function profileAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('Staff:Employee')->find(
            $this->getUser()->getId()
        );

        $form_password = $this->createForm(ChangePasswordType::class);

        $form_language = $this->createForm(
            ChangeLanguageType::class,
            [
                'language' => $user->getDefaultLocale(),
            ]
        );
        $form_email = $this->createForm(ChangeEmailType::class, $user);
        $previousEmail = $user->getEmail();

        $form_language->handleRequest($request);
        $form_password->handleRequest($request);
        $form_email->handleRequest($request);

        if ($form_language->isValid()) {
            $user->setDefaultLocale($form_language->get('language')->getData());
            $em->flush();

            $this->addFlash(
                'success',
                $this->get('translator')->trans('language.change_confirm')
            );
        }
        if ($form_password->isValid()) {
            $newPwEncoded = $this->get('security.password_encoder')
                ->encodePassword(
                    $user,
                    $form_password->get('password')->getData()
                );
            $user->setPassword($newPwEncoded);
            $em->persist($user);
            $em->flush();
            $this->addFlash(
                'success',
                $this->get('translator')->trans(
                    'profile.change_password.confirm'
                )
            );

            /*if ($user->hasCentralRole()) {
                $data = $this->get('send.pw.service')->sendPassword($user);
                if (isset($data['status']) && $data['status'] === true) {
                    $this->addFlash('success', 'your_pw_in_super_synchronized_success');
                } else {
                    $this->addFlash('error', 'your_pw_in_super_synchronized_fail');
                }
            }*/
        }

        if ($form_email->isSubmitted() and $form_email->isValid()) {
            $this->addFlash(
                'success',
                $this->get('translator')->trans('staff.list.email.success')
            );
            $em->flush();
        } else {
            $user->setEmail($previousEmail);
            $em->flush();
        }

        return $this->render(
            '@General/profile.html.twig',
            [
                'form_language' => $form_language->createView(),
                'form_password' => $form_password->createView(),
                'user'          => $user,
                'form_email'    => $form_email->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @Route("switch_restaurant", name="switch_restaurant")
     */
    public function switchRestaurantAction(Request $request)
    {
        $referer = $request->headers->get('referer');

        if ($request->isMethod('POST')) {
            $restaurantId = $request->request->get('restaurant-switch');
            if (isset($restaurantId)) {
                $currentRestaurant = $this->getDoctrine()->getManager()
                    ->getRepository("Merchandise:Restaurant")->find(
                    $restaurantId
                );
                if (isset($currentRestaurant)) {
                    $this->get('session')->set(
                        'currentRestaurant',
                        $currentRestaurant
                    );
                    $this->addFlash(
                        'success',
                        $this->get('translator')->trans(
                            'restaurant_switch_success'
                        )." ".$currentRestaurant->getName()
                    );

                    return $this->redirect($referer);
                }
            }
            $this->addFlash('error', 'restaurant_not_found');

            return $this->redirect($referer);
        }
        $this->addFlash('error', 'restaurant_switch_failure');

        return $this->redirect($referer);
    }

    /**
     * @param $rangeType
     * @return array
     */
    private function getDatesRange($rangeType){
        $result=array();
        $today=$this->get('administrative.closing.service')->getLastWorkingEndDate();
        switch ($rangeType) {
            case 0://day before
                $result['from']=$today->modify('yesterday');
                $result['to']=$today;
                break;
            case 1://today
                $result['from']=$today;
                $result['to']=$today;
                break;
            case 2://this week
                $from=clone $today;
                $to=clone $today;
                $result['from']=$from->modify('Monday this week');
                $result['to']=$to->modify('Sunday this week');
                break;
            case 3://this month
                $from=clone $today;
                $to=clone $today;
                $result['from']=$from->modify('first day of this month');
                $result['to']=$to->modify('last day of this month');
                break;
            default:
                $result['from']=$today;
                $result['to']=$today;
        }
        return $result;
    }
}
