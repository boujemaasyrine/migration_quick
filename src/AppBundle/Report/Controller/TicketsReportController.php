<?php

namespace AppBundle\Report\Controller;

use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Report\Form\TicketsFormType;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TicketsReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/tickets",name="tickets_report")
     */
    public function indexAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $data['currentRestaurant'] = $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(
            TicketsFormType::class,
            $data,
            array(
                'restaurant' => $data['currentRestaurant'],
            )
        );

        $limit = 100;
        $page = $request->get('page', 0);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {
                $i=rand();
                $filter = $form->getData();
                $logger->addInfo('Generate report tickets by '.$currentRestaurant->getCode().' from '.$filter['startDate']->format('Y-m-d').' to '.$filter['endDate']->format('Y-m-d').' '.$i);
                $t1 = time();
                $result = $this->get('report.tickets.service')->getTicketListV2($filter, 1, $limit);
                $t2 = time();
                $logger->addInfo('Generate report tickets finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);

                if (is_null($request->get('export', null))) {
                    return $this->render(
                        '@Report/Tickets/index_tickets_report.html.twig',
                        array(
                            'form' => $form->createView(),
                            'result' => $result,
                            'generated' => true,
                            'filter' => $filter,
                        )
                    );
                } else {// generate pdf
                    $filename = "tickets_report_" . date('Y_m_d_H_i_s') . ".pdf";
                    $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                        $filename,
                        '@Report/Tickets/export/export_report_tickets.html.twig',
                        [
                            "form",
                            $form->createView(),
                            "result" => $result,
                            "generated" => true,
                            "download" => true
                        ]
                        ,
                        [
                            'orientation' => 'Portrait',
                            'page-size' => "A4",
                            'footer-center' => '[page]',
                            'footer-font-size' => 6,
                            'margin-right' => 0.5,
                            'margin-left' => 0.5,
                            'no-stop-slow-scripts' => true,
                            'enable-javascript' => true,
                            'javascript-delay' => 7000
                        ]
                    );

                    return Utilities::createFileResponse($filepath, $filename);
                }

            }

        } elseif ($request->getMethod() == "GET" && $page >= 1) {
            $filter = $request->get('filter');
            $filter['startDate'] = new \DateTime($filter['startDate']['date']);
            $filter['endDate'] = new \DateTime($filter['endDate']['date']);
            if (array_key_exists('cashierId', $filter)) {
                $filter['cashier'] = $this->getDoctrine()->getRepository(Employee::class)->find($filter['cashierId']);
            }
            if (array_key_exists('paymentMethodIds', $filter)) {
                $filter['paymentMethod'] = $this->getDoctrine()->getRepository(PaymentMethod::class)->createQueryBuilder('p')->where("p.id in (:ids)")
                    ->setParameter("ids", $filter['paymentMethodIds'])->getQuery()->getResult();
                $filter['paymentMethod'] = new ArrayCollection($filter['paymentMethod']);
            }


            $form = $this->createForm(
                TicketsFormType::class,
                $filter,
                array(
                    'restaurant' => $data['currentRestaurant'],
                )
            );
            $result = $this->get('report.tickets.service')->getTicketListV2($filter, $page, $limit);

            if (is_null($request->get('export', null))) {
                return $this->render(
                    '@Report/Tickets/index_tickets_report.html.twig',
                    array('form' => $form->createView(), 'result' => $result, 'generated' => true, 'filter' => $filter)
                );
            } else {
                $filename = "tickets_report_" . date('Y_m_d_H_i_s') . ".pdf";
                $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                    $filename,
                    '@Report/Tickets/export/export_report_tickets.html.twig',
                    [
                        "form",
                        $form->createView(),
                        "data" => $result,
                        "generated" => true,
                    ]
                    ,
                    [
                        'orientation' => 'Portrait',
                        'page-size' => "A4",
                        'footer-center' => '[page]',
                        'footer-font-size' => 6,
                        'margin-right' => 0.5,
                        'margin-left' => 0.5,
                        'no-stop-slow-scripts' => true,
                        'enable-javascript' => true,
                        'javascript-delay' => 7000
                    ]
                );

                return Utilities::createFileResponse($filepath, $filename);
            }

        }

        return $this->render(
            '@Report/Tickets/index_tickets_report.html.twig',
            array('form' => $form->createView())
        );
    }
}
