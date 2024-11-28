<?php

namespace AppBundle\General\Controller;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Model\DayIncome;
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
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="index",options={"expose"=true})
     */
    public function indexAction()
    {
        $day = $lasDay = $this->get('administrative.closing.service')->getLastWorkingEndDate(
        );//fixed //new \DateTime('now');
        $dateP = clone $day;
        $dateP->sub(new \DateInterval('P1Y'));
        $tickets = $this->get('dashboard.service')->getTicketsAvg($lasDay);//fixed

        $ca_prev = $this->get('ca_prev.service')->createIfNotExsit(new \DateTime('now'));//fixed

        $cancels = $this->get('cashbox.service')->getCancelsAbandonsCorrectionsCashbox($day);//fixed

        $nonCounted = $this->get('cashbox.service')->getNotCountedCashBox($day);//fixed

        $chestCount = $this->get('chest.service')->loadLastChestCount();//fixed

        $dayIncome = new DayIncome();
        $dayIncome->setDate($day);
        $dayIncome->setCashboxCounts($this->get('cashbox.service')->findCashboxCountsByDate($dayIncome->getDate()));

        $takeout = $this->get('ticket.service')->takeout();//fixed
        $drive= $this->get('ticket.service')->drive();

        //$caBrut = $this->get('report.sales.service')->getCaBrutPerDay($day);//fixed

        $caHTva = $this->get('report.sales.service')->getCaHTva($day);

        return $this->render(
            '@General/home.html.twig',
            array(
                'home_page' => true,
                'tickets' => $tickets,//["br"=>0,"brHT"=>0,"caBrutHt"=>0,"caNetHt"=>0,"nbrTickets"=>0,"nbrTicketsPerCentNOne"=>0,"avgTicket"=>0,"avgTicketPerCentNOne"=>0],
                'lastChestCount' => $chestCount,
                'nonCounted' => $nonCounted,
                'cancels' => $cancels,
                'dayIncome' => $dayIncome,
                'takeout' => $takeout,
                'drive' => $drive,
                'ca_prev' => $ca_prev,
                "caBrut" => 0,//$caBrut,
                'caHTva' => $caHTva
            )
        );
    }
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dashboard_hour_by_hour", name="dashboard_hour_by_hour",options={"expose"=true})
     */
    public function hourByHourAction()
    {
        $result=array();
        for ($i=0;$i<25;$i++){
            $result['ticket'][$i]=array('nbrTicket'=>0);
            $result['ca'][$i]="0";
            $result['origin']['pos'][$i]=array('ca'=>0,'tickets'=>0);
            $result['origin']['drivethru'][$i]=array('ca'=>0,'tickets'=>0);
            $result['ca_prev'][$i]=0;
        }
        return new JsonResponse(
            array(
                "hourByHour" => $result,
                'opening_hour' => "10",
                'closing_hour' => "3",
            )
        );
        /*
        $day = $this->get('administrative.closing.service')->getLastWorkingEndDate();//new \DateTime('now');
        $criteria['from'] = $day->format('d-m-Y');
        $criteria['to'] = $day->format('d-m-Y');

        $allResult = $this->get('report.sales.service')->generateHourByHourReport($criteria, 0);
        $result = $allResult['result'];
        $openingHour = $allResult['openingHour'];
        $closingHour = $allResult['closingHour'];
        dump($allResult);
        return new JsonResponse(
            array(
                "hourByHour" => $result,
                'opening_hour' => $openingHour,
                'closing_hour' => $closingHour,
            )
        );*/
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/ca_budget", name="dashboard_ca_budget",options={"expose"=true})
     */
    public function caBudgetAction()
    {
        return new JsonResponse(array());
        /*
        $day = $lasDay = $this->get('administrative.closing.service')->getLastWorkingEndDate();//new \DateTime('now');

        $dateP = clone $day;
        $dateP->sub(new \DateInterval('P1Y'));

        $period = DateUtilities::getDays(Utilities::getDateFromDate($day, -30), Utilities::getDateFromDate($day, -1));

        $dayResult = [];
        foreach ($period as $date) {
            $dayResult[] = $this->get('dashboard.service')->getCaVsCaOne($date);
        }

        return new JsonResponse($dayResult);*/
    }

    /**
     * @param Request $request
     * @param string $locale
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
        $synchronizedTicket = $this->getDoctrine()->getManager()->getRepository('Financial:Ticket')
            ->findOneBy(['synchronized' => true]);
        $response = $this->container->get('sync.remove_ticket.service')
            ->removeTicket(null, $synchronizedTicket);

        return $this->render("@General/home.html.twig", array('raw_html' => $response));
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
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/profile", name="user_profile")
     */
    public function profileAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('Staff:Employee')->find($this->getUser()->getId());

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

            $this->addFlash('success', $this->get('translator')->trans('language.change_confirm'));
        }
        if ($form_password->isValid()) {
            $newPwEncoded = $this->get('security.password_encoder')->encodePassword(
                $user,
                $form_password->get('password')->getData()
            );
            $user->setPassword($newPwEncoded);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('profile.change_password.confirm'));

            /*if ($user->hasCentralRole()) {
                $data = $this->get('send.pw.service')->sendPassword($user);
                if (isset($data['status']) && $data['status'] === true) {
                    $this->addFlash('success', 'your_pw_in_super_synchronized_success');
                } else {
                    $this->addFlash('error', 'your_pw_in_super_synchronized_fail');
                }
            }*/
        }

        if($form_email->isSubmitted() and $form_email->isValid())
        {
            $this->addFlash('success', $this->get('translator')->trans('staff.list.email.success'));
            $em->flush();
        }
        else
        {
            $user->setEmail($previousEmail);
            $em->flush();
        }

        return $this->render(
            '@General/profile.html.twig',
            [
                'form_language' => $form_language->createView(),
                'form_password' => $form_password->createView(),
                'user' => $user,
                'form_email' => $form_email->createView()
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
                $currentRestaurant = $this->getDoctrine()->getManager()->getRepository("Merchandise:Restaurant")->find(
                    $restaurantId
                );
                if (isset($currentRestaurant)) {
                    $this->get('session')->set('currentRestaurant', $currentRestaurant);
                    $this->addFlash(
                        'success',
                        $this->get('translator')->trans('restaurant_switch_success')." ".$currentRestaurant->getName()
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
}
