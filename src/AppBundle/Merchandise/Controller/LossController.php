<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Marchandise\Form\LossDateFormType;
use AppBundle\Marchandise\Form\LossDateType;
use AppBundle\Marchandise\Form\PreviousDateType;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\DateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Form\LossSheetType;
use AppBundle\Merchandise\Form\ConsultLoss\ConsultationLossType;
use AppBundle\Merchandise\Form\ConsultLoss\HourlyLossType;
use AppBundle\Merchandise\Form\ConsultLoss\DailyLossType;
use AppBundle\Merchandise\Form\ConsultLoss\WeeklyLossType;
use AppBundle\Merchandise\Form\ConsultLoss\MonthlyLossType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class LossController
 *
 * @package       AppBundle\Merchandise\Controller
 * @Route("loss")
 */
class LossController extends Controller
{

    /**
     * @RightAnnotation("loss_sheet")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/lossSheet",name="loss_sheet")
     */
    public function lossSheetAction(Request $request)
    {
        $response = null;
        if (!$request->isXmlHttpRequest()) {
            $type = $request->get('type');
            $response = $this->render(
                "@Merchandise/Loss/Sheets/sheets.html.twig",
                [
                    "type" => $type,
                ]
            );
        }

        return $response;
    }

    /**
     * @RightAnnotation("previous_day_loss")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/previousDayLoss/{type}/{date}",name="previous_day_loss", options={"expose"=true},defaults={"date" = null})
     */
    public function previousDayLossAction(Request $request, $type, $date)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $formPreviousDate = $this->createForm(\AppBundle\Merchandise\Form\ConsultLoss\PreviousDateType::class, ['date' => new \DateTime($date)]);

        list($mindate, $maxDate) = $this->dateInterval();

        if ($request->getMethod() === "POST") {
            $lossSheet = new LossSheet();
            $form = $this->createForm(
                LossSheetType::class,
                $lossSheet,
                [
                    "product_type" => $type,
                    "restaurant" => $restaurant,
                ]
            );
            $form->handleRequest($request);
            if ($form->isValid()) {
                $lossSheet->setType(SheetModel::$cibledByType[$type]);
                $this->get("loss.service")->saveLossSheet($restaurant, $lossSheet, true);
                $this->addFlash('success', 'loss.success_entry');

                return $this->get('workflow.service')->nextStep(
                    $this->redirect($this->generateUrl('index')),
                    'previous_day_loss'
                );
            }
        } else {
            $dateLoss = new \DateTime($date);
            if (!($dateLoss >= $mindate && $dateLoss <= $maxDate)) {
                $this->addFlash(
                    'warning',
                    $this->get('translator')->trans('loss.warning.loss_sheet_date_unavailable', ['%minDate%' => $mindate->format('Y-m-d'), '%maxDate%' => $maxDate->format('Y-m-d')])
                );
                return $this->redirectToRoute('previous_date', ['type' => $type]);
            }

            $yesterday = $dateLoss->setTime(6, 0, 0);
            $oldLossSheet = $this->getDoctrine()->getManager()->getRepository(
                "Merchandise:LossSheet"
            )
                ->findYesterdayLossSheet(
                    $restaurant,
                    SheetModel::$cibledByType[$type],
                    $yesterday
                );
            if (is_null($oldLossSheet)) {
                $this->addFlash(
                    'warning',
                    $this->get('translator')->trans('loss.warning.yesterday_loss_sheet_unavailable', ['%date%' => $date])
                );
                $yesterdayLossSheet = new LossSheet();
                $yesterdayLossSheet->setEntryDate($yesterday);
                $form = $this->createForm(
                    LossSheetType::class,
                    $yesterdayLossSheet,
                    ["product_type" => $type, "restaurant" => $restaurant]
                );
            } else {
                $form = $this->createForm(
                    LossSheetType::class,
                    $oldLossSheet,
                    [
                        "product_type" => $type,
                        "restaurant" => $restaurant
                    ]
                );
            }
        }

        return $this->render(
            "@Merchandise/Loss/entry.html.twig",
            array(
                'lossForm' => $form->createView(),
                'type' => $type,
                'yesterdayLoss' => true,
                'onlyDate' => false,
                'lossDateForm' => $formPreviousDate->createView(),
                'maxDate' => $maxDate,
                'minDate' => $mindate,
                'previousDate' => $dateLoss,
            )
        );
    }

    /**
     * @RightAnnotation("loss_entry")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/lossEntry/{type}",name="loss_entry", options={"expose"=true})
     */
    public function lossEntryAction(Request $request, $type)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $fiscalDate = $this->get('administrative.closing.service')
            ->getLastWorkingEndDate();

        $entryDate = $fiscalDate->setTime(6, 0, 0);

        if ($request->getMethod() === "POST") {
            $lossSheet = new LossSheet();
            $lossSheet->setEntryDate($entryDate);
            $form = $this->createForm(
                LossSheetType::class,
                $lossSheet,
                [
                    "product_type" => $type,
                    "restaurant" => $restaurant,
                ]
            );
            $form->handleRequest($request);

            $lossSheet->setType(SheetModel::$cibledByType[$type]);
            if ($form->isValid()) {
                $this->get("loss.service")->saveLossSheet($restaurant, $lossSheet);
                $this->addFlash('success', 'loss.success_entry');

                return $this->get('workflow.service')->nextStep(
                    $this->redirect($this->generateUrl('index')),
                    'loss_entry'
                );
            } else {
                $this->addFlash('error', 'loss.errors.message');

                return $this->redirect($this->generateUrl('loss_entry', array('type' => 'articles_loss_model')));
            }
        } else {
            $oldLossSheet = $this->getDoctrine()->getManager()->getRepository("Merchandise:LossSheet")
                ->findTodayLossSheet(SheetModel::$cibledByType[$type], $restaurant, $entryDate);

            if (is_null($oldLossSheet)) {
                $oldLossSheet = new LossSheet();
                $oldLossSheet->setEntryDate($entryDate);
            }
            $form = $this->createForm(
                LossSheetType::class,
                $oldLossSheet,
                [
                    "product_type" => $type,
                    'restaurant' => $restaurant,
                ]
            );
        }

        return $this->render(
            "@Merchandise/Loss/entry.html.twig",
            array(
                'lossForm' => $form->createView(),
                'type' => $type,
                'yesterdayLoss' => false,
                'onlyDate' => false
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/previousDate/{type}",name="previous_date", options={"expose"=true})
     *
     */
    public function previousDateAction(Request $request, $type)
    {
        $form = $this->createForm(\AppBundle\Merchandise\Form\ConsultLoss\PreviousDateType::class);
        $form->handleRequest($request);
        list($mindate, $maxDate) = $this->dateInterval();

        //  $valid=$form->isValid();
        if ($form->isValid()) {
            $data = $form->getData();
            return $this->redirectToRoute('previous_day_loss', ['type' => $type, 'date' => $data['date']->format('Y-m-d')]);
        }
        return $this->render(
            "@Merchandise/Loss/entry.html.twig",
            array(
                'lossDateForm' => $form->createView(),
                'type' => $type,
                'yesterdayLoss' => true,
                'onlyDate' => true,
                'maxDate' => $maxDate,
                'minDate' => $mindate,
            )
        );
    }

    private function dateInterval()
    {
        $fiscalDate = $this->get('administrative.closing.service')
            ->getLastWorkingEndDate()->format('Y/m/d');
        $maxDate = new \DateTime($fiscalDate);
        $maxDate->modify('yesterday');
        $monday = new \DateTime($fiscalDate);
        $monday->modify('monday this week');
        $mindate = $monday;
        if ($fiscalDate == $monday->format('Y/m/d')) {
            $mindate->modify('yesterday');
        }
        return [$mindate, $maxDate];
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/lossList",name="loss_list")
     */
    public function lossListAction(Request $request)
    {
        $form = $this->createForm(ConsultationLossType::class);

        return $this->render(
            "@Merchandise/Loss/loss_list.html.twig",
            array('lossTypeForm' => $form->createView())
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/lossConsult/{type}",name="loss_consult",options={"expose"=true})
     */
    public function ConsultLossAction(Request $request, $type)
    {

        $response = new JsonResponse();

        try {
            switch ($type) {
                case 'hourly':
                    $form = $this->createForm(HourlyLossType::class);
                    break;
                case 'daily':
                    $form = $this->createForm(DailyLossType::class);
                    break;
                case 'weekly':
                    $form = $this->createForm(WeeklyLossType::class);
                    break;
                case 'monthly':
                    $form = $this->createForm(MonthlyLossType::class);
                    break;
            }
            $form->submit($request);
            if ($request->isXmlHttpRequest()) {
                if ($form->isValid()) {
                    $lines = $this->get("loss.service")->getListLoss($form, $type);
                    $response->setData(
                        [
                            "data" => [
                                $this->renderView(
                                    '@Merchandise/Loss/parts/loss_list_product.html.twig',
                                    array(
                                        'lines' => $lines,
                                    )
                                ),
                            ],
                        ]
                    );
                } else {
                    $response->setData(
                        [
                            "data" => [
                                $this->renderView(
                                    '@Merchandise/Loss/consult_loss.html.twig',
                                    array(
                                        'form' => $form->createView(),
                                        'type' => $type,
                                    )
                                ),
                            ],
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $response->setData(
                [
                    "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                ]
            );
        }


        return $response;
    }

    /**
     * @param LossLine $lossLine
     * @return JsonResponse
     * @Route("/get_loss_by_id/{modelId}",name="get_loss_by_id",options={"expose"=true})
     */
    public function getLossByIdAction(Request $request, $modelId)
    {
        $model = $this->getDoctrine()->getRepository("Merchandise:SheetModel")->find($modelId);
        if ($model) {
            $currentTime = new \DateTime((string)date("Y-m-d H:i:s"));
            $date = $currentTime;
            $date = $date->format('d/m/Y');
            $return = [
                "date" => $date,
                "type" => $model->getLinesType(),
            ];
        }

        return new JsonResponse(
            array(
                'data' => $return,
            )
        );
    }

    /**
     * @param Request $request
     * @param $modelId
     * @return JsonResponse
     * @throws \Exception
     * @Route("/get_line_by_loss/{modelId}",name="get_line_by_loss",options={"expose"=true})
     */
    public function getLineByLossAction(Request $request, $modelId)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $model = $this->getDoctrine()->getRepository("Merchandise:SheetModel")->find($modelId);
        $loss = $this->get("loss.service")->initLoss($model);
        $response = new JsonResponse();

        try {
            // serialization stuff
            $encoder = new JsonEncoder();
            $normalizer = new ObjectNormalizer();
            $normalizer->setCircularReferenceHandler(
                function ($object) {
                    return $object->getId();
                }
            );
            $serializer = new Serializer(array($normalizer), array($encoder));

            $lines = $loss->getLossLines();
            $loss->setLossLines(new ArrayCollection());

            if ($loss->getType() === LossSheet::ARTICLE) {
                $form = $this->createForm(
                    LossSheetType::class,
                    $loss,
                    [
                        "product_type" => SheetModel::ARTICLES_LOSS_MODEL,
                        "restaurant" => $restaurant,
                    ]
                );
                $response->setData(
                    [
                        "data" => [
                            $this->renderView(
                                '@Merchandise/Loss/parts/loss_type_article.html.twig',
                                array(
                                    'loss' => $form->createView(),
                                )
                            ),
                            'lines' => json_decode($serializer->serialize($lines, 'json')),
                        ],
                    ]
                );
            } else {
                if ($loss->getType() === LossSheet::FINALPRODUCT) {
                    $form = $this->createForm(
                        LossSheetType::class,
                        $loss,
                        [
                            "product_type" => SheetModel::PRODUCT_SOLD_LOSS_MODEL,
                            "restaurant" => $restaurant,
                        ]
                    );
                    $response->setData(
                        [
                            "data" => [
                                $this->renderView(
                                    '@Merchandise/Loss/parts/loss_type_article.html.twig',
                                    array(
                                        'loss' => $form->createView(),
                                    )
                                ),
                                'lines' => json_decode($serializer->serialize($lines, 'json')),
                            ],
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $response->setData(
                [
                    "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                ]
            );
        }

        return $response;
    }

    /**
     * @Route("/export/{lossSheet}", name="export_loss_sheet", options={"expose"=true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportLossModelAction(
        Request $request,
        LossSheet $lossSheet
    )
    {
        $lossSheetForm = $this->createForm(
            LossSheetType::class,
            $lossSheet
        );
        $filename = "loss_sheet_" . $lossSheet->getType() . "_" . date('Y_m_d_H_i_s') . ".pdf";
        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
            $filename,
            '@Merchandise/Loss/exports/loss_sheet.html.twig',
            array(
                "lossForm" => $lossSheetForm->createView(),
            )
        );

        return Utilities::createFileResponse($filepath, $filename);
    }
}
