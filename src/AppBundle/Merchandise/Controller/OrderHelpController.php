<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/03/2016
 * Time: 08:11
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderHelpFixedCoef;
use AppBundle\Merchandise\Entity\OrderHelpProducts;
use AppBundle\Merchandise\Entity\OrderHelpTmp;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Form\HelpOrder\HelpOrderType;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OrderHelpController
 *
 * @package              AppBundle\Merchandise\Controller
 * @Route("/order_help")
 */
class OrderHelpController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("/init/{init}",name="init",options={"expose"=true})
     * @RightAnnotation("init")
     */
    public function initAction($init = 0)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();

        //Test if there's coef calculated
        $base = $this->getDoctrine()->getRepository("Merchandise:CoefBase")->findOneBy(
            array("originRestaurant" => $currentRestaurant),
            array('id' => 'DESC')
        );

        if ($base == null) {
            $this->get('session')->getFlashBag()->add('warning', "Il n'y a pas de Coefficients calculés");

            return $this->redirectToRoute("coef_calculate_base");
        }
        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );

        //Récupération des founissuers actifs
        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->getRestaurantSuppliers($currentRestaurant);
        $productList=array();
        foreach ($suppliers as $s){
            $suppliersProducts = $this->getDoctrine()->getRepository("Merchandise:ProductPurchased")
                ->getActiveProductForOrderHelp($s, $currentRestaurant);
        foreach ($suppliersProducts as $p){
            $coef = $this->getDoctrine()->getRepository("Merchandise:Coefficient")->findOneBy(
                array(
                    'base' => $base,
                    'product' => $p,
                )
            );
            if(!$coef){
                $productList[]=$p;
            }
        }
        }
         if(!empty($productList)){
            $message = $this->get('translator')->trans('coeff_product_warning');
            foreach($productList as $product){
                $message.= $product->getName(). ';';
            }
            $this->get('session')->getFlashBag()->add('warning', $message);
            return $this->render(
                "@Merchandise/OrderHelp/steps/init.html.twig",
                array(
                    'helpOrder' => $helpOrder,
                    'base' => $base,
                )
            );
        }


        if ($helpOrder == null) {
            $init = 1;
            $helpOrder = new OrderHelpTmp();
        }
        $helpOrder
            ->setStartDateLastWeek($base->getStartDate())
            ->setEndDateLastWeek($base->getEndDate())
            ->setCa($base->getCa())
            ->setWeek(intval(date('W')))
            ->setOriginRestaurant($currentRestaurant);

         if ($init == 1) {
            $progress = new ImportProgression();
            $progress->setNature('init_help_order')
                ->setStatus('pending')
                ->setStartDateTime(new \DateTime());

            $this->getDoctrine()->getManager()->persist($helpOrder);
            $this->getDoctrine()->getManager()->persist($progress);
            $this->getDoctrine()->getManager()->flush();

            //$this->get("logger")->info('order:help:init:v2  ' . $helpOrder->getId() . ' ' . $progress->getId(). ' ' .$currentRestaurant->getId());

            $this->get('toolbox.command.launcher')->execute(
                'order:help:init:v2  '.$helpOrder->getId().' '.$progress->getId().' '.$currentRestaurant->getId()
            );

            return $this->render(
                "@Merchandise/OrderHelp/steps/init.html.twig",
                array(
                    'initializing' => true,
                    'progressID' => $progress->getId(),
                )
            );
        }

        return $this->render(
            "@Merchandise/OrderHelp/steps/init.html.twig",
            array(
                'helpOrder' => $helpOrder,
                'base' => $base,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("/third_step_v2",name="third_step_v2")
     */
    public function stepThreeV2Action()
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );

        if ($helpOrder == null) {
            $this->get('session')->getFlashBag()->add('warning', 'start_with_this_step');

            return $this->redirectToRoute("init");
        }

        if ($helpOrder->getLocked()) {
            return $this->render(
                "@Merchandise/OrderHelp/steps/mask_v2.html.twig",
                array(
                    'tmp' => $helpOrder,
                    'locked' => true,
                )
            );
        }

        $supplierss = $helpOrder->getSuppliers();
        $suppliers = [];
        foreach ($supplierss as $s) {
            $suppliers[] = $s->getSupplier();
        }
        $j = $helpOrder->getMasks()[0]->getStartDate();

        $days = [
            $this->get('translator')->trans('days.sunday'),
            $this->get('translator')->trans('days.monday'),
            $this->get('translator')->trans('days.tuesday'),
            $this->get('translator')->trans('days.wednesday'),
            $this->get('translator')->trans('days.thursday'),
            $this->get('translator')->trans('days.friday'),
            $this->get('translator')->trans('days.saturday'),
        ];

        $shortDays = [
            'Dim',
            'Lun',
            'Mar',
            'Mer',
            'Jeu',
            'Ven',
            'Sam',
        ];

        $dates = [];
        for ($i = 0; $i < 20; $i++) {
            $newDateTs = mktime(
                0,
                0,
                0,
                intval($j->format('m')),
                intval($j->format('d')) + $i,
                intval($j->format('Y'))
            );
            $newDate = new \DateTime();
            $newDate->setTimestamp($newDateTs);

            $dates[$newDate->format('d/m/Y')] = array(
                'bud' => $this->get('ca_prev.service')->createIfNotExsit($newDate),
                'day' => $days[intval($newDate->format('w'))],
                'short_day' => $shortDays[intval($newDate->format('w'))],
                'w' => intval($newDate->format('w')),
            );
        }
        $categories = $this->getDoctrine()->getRepository("Merchandise:ProductCategories")->findBy(
            array(
                'eligible' => true,
            )
        );
        return $this->render(
            "@Merchandise/OrderHelp/steps/mask_v2.html.twig",
            array(
                'suppliers' => $suppliers,
                'dates' => $dates,
                'categories' => $categories,
                'days' => $days,
                'tmp' => $helpOrder,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/fourth_step_v2",name="fourth_step_v2")
     */
    public function fourthStepV2Action(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );
        if ($helpOrder == null) {
            $this->get('session')->getFlashBag()->add('warning', 'start_with_this_step');

            return $this->redirectToRoute("first_step_order_help");
        }

        if ($helpOrder->getLocked()) {
            return $this->render(
                "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
                array(
                    'tmp' => $helpOrder,
                    'locked' => true,
                )
            );
        }

        if ($request->getMethod() == 'POST') {
            $masks = $request->request->getIterator()['mask'];

            if(isset($request->request->getIterator()['display_all']) &&!empty($request->request->getIterator()['display_all'])){
                $displayAll="true";
            }

            else {
                $displayAll="false";
            }
            foreach ($masks as $key => $m) {
                $mask = $this->getDoctrine()->getRepository("Merchandise:OrderHelpMask")->find($key);
                $mask
                    ->setRange(floatval($m['range']))
                    ->setAbsoluteDeliveryDay($m['absolute_delivery_day'])
                    ->setAbsoluteOrderDay($m['absolute_order_day'])
                    ->setBudget(floatval($m['budget']));
                $this->getDoctrine()->getManager()->flush();
            }

            $progress = new ImportProgression();
            $progress->setStartDateTime(new \DateTime('NOW'))
                ->setNature('result_help_order_table')
                ->setProgress(0);

            $this->getDoctrine()->getManager()->persist($progress);
            $this->getDoctrine()->getManager()->flush();
            //$this->get("logger")->info('order:help:result ' . $helpOrder->getId() . ' ' . $progress->getId() .' '. $currentRestaurant->getId());
            $this->get('toolbox.command.launcher')->execute(
                'order:help:result '.$helpOrder->getId().' '.$progress->getId().' '.$currentRestaurant->getId()
            );

            return $this->render(
                "@Merchandise/OrderHelp/steps/results.html.twig",
                array(
                    'progressId' => $progress->getId(),
                    'orderTmpId' => $helpOrder->getId(),
                    'displayAll' =>$displayAll

                )
            );
        }

        return $this->render("@Merchandise/OrderHelp/steps/results.html.twig");
    }

    /**
     *
     * * *** OLD VERSION ****
     */


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="order_help")
     */
    public function indexAction()
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->getRestaurantSuppliers(
            $currentRestaurant
        );

        return $this->render(
            "@Merchandise/OrderHelp/index.html.twig",
            array(
                'suppliers' => $suppliers,
            )
        );
    }

    /**
     * @return JsonResponse
     * @param Request $request
     * @throws
     * @Route("/calculate_ca",name="calculate_ca",options={"expose"=true})
     */
    public function calculateCaAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if (!$request->request->has('dd')) {
            throw new \Exception("Expect a dd parameter in the post");
        }

        if (!$request->request->has('df')) {
            throw new \Exception("Expect a df parameter in the post");
        }


        $dd = $request->request->get('dd');
        $df = $request->request->get('df');

        $dd = \DateTime::createFromFormat('d/m/Y', $dd);
        $df = \DateTime::createFromFormat('d/m/Y', $df);


        if (!$dd || !$df) {
            return new JsonResponse(
                array(
                    'data' => null,
                )
            );
        }

        $ca = 0;
        //        $diff = $df->diff($dd)->days;
        //        for ($i = 0; $i <= $diff; $i++) {
        //            $ca += $this->getDoctrine()
        //                ->getRepository("Financial:Ticket")
        //                ->getTotalPerDay(Utilities::getDateFromDate($dd, $i));
        //        }

        $cas = $this->getDoctrine()
            ->getRepository("Financial:FinancialRevenue")
            ->getFinancialRevenueBetweenDates($dd, $df, $currentRestaurant);
        foreach ($cas as $c) {
            $ca += $c->getAmount();
        }

        return new JsonResponse(
            array(
                'data' => $ca,
            )
        );
    }

    /**
     * @param OrderHelpTmp $tmp
     * @return JsonResponse
     * @Route("/launch_coef_calculation/{tmp}",name="launch_coef_calculation",options={"expose"=true})
     */
    public function calculateCoef(OrderHelpTmp $tmp)
    {

        $progress = new ImportProgression();
        $progress
            ->setStartDateTime(new \DateTime('NOW'))
            ->setNature('calcul_coef_help_order')
            ->setProgress(0);

        $this->getDoctrine()->getManager()->persist($progress);
        $this->getDoctrine()->getManager()->flush();

        $this->get('toolbox.command.launcher')->execute(
            'order:help:calculate:coef '.$tmp->getId().' '.$progress->getId()
        );

        return new JsonResponse(
            array(
                'data' => array(
                    'ProgressId' => $progress->getId(),
                ),
            )
        );
    }

    /**
     * @param Request $request
     * @param OrderHelpTmp $tmp
     * @return JsonResponse
     * @Route("/get_coef_table/{tmp}",name="get_coef_table",options={"expose"=true})
     */
    public function getCoefTable(Request $request, OrderHelpTmp $tmp)
    {

        if ($request->isXmlHttpRequest()) {
            $categories = $this->getDoctrine()->getRepository("Merchandise:ProductCategories")->findBy(
                array(
                    'eligible' => true,
                )
            );

            return new JsonResponse(
                array(
                    'data' => $this->renderView(
                        "@Merchandise/OrderHelp/steps/coef_table.html.twig",
                        array(
                            'orderHelp' => $tmp,
                            'categories' => $categories,
                        )
                    ),
                )
            );
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/first_step",name="first_step_order_help")
     */
    public function firstStep()
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $new = false;
        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );

        if ($helpOrder == null) {
            $new = true;
            $helpOrder = new OrderHelpTmp();

            $today = new \DateTime("NOW");
            $d = 7 + (intval($today->format('w')) - 1);

            $lastMondayTS = mktime(
                0,
                0,
                0,
                intval($today->format('m')),
                intval($today->format('d')) - $d,
                intval($today->format('Y'))
            );
            $lastMonday = new \DateTime();
            $lastMonday->setTimestamp($lastMondayTS);

            $lastSundayTS = mktime(
                0,
                0,
                0,
                intval($lastMonday->format('m')),
                intval($lastMonday->format('d')) + 6,
                intval($lastMonday->format('Y'))
            );
            $lastSunday = new \DateTime();
            $lastSunday->setTimestamp($lastSundayTS);

            $cas = $this->getDoctrine()
                ->getRepository("Financial:FinancialRevenue")
                ->getFinancialRevenueBetweenDates($lastMonday, $lastSunday, $currentRestaurant);

            $ca = 0;
            foreach ($cas as $c) {
                $ca += $c->getAmount();
            }

            $helpOrder
                ->setStartDateLastWeek($lastMonday)
                ->setEndDateLastWeek($lastSunday)
                ->setCa($ca)
                ->setWeek(intval(date('W')))
                ->setOriginRestaurant($currentRestaurant);
        } else {
            if ($helpOrder->getLocked()) {
                return $this->render(
                    "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
                    array(
                        'tmp' => $helpOrder,
                        'locked' => true,
                    )
                );
            }
        }

        $helpOrderForm = $this->createForm(HelpOrderType::class, $helpOrder);

        return $this->render(
            "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
            array(
                'form' => $helpOrderForm->createView(),
                'new' => $new,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @param Request $request
     * @Route("/second_step/{tmp}",name="second_step_order_help")
     */
    public function secondStep(Request $request, OrderHelpTmp $tmp = null)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $categories = $this->getDoctrine()->getRepository("Merchandise:ProductCategories")->findBy(
            array(
                'eligible' => true,
            )
        );

        if ($tmp != null) {
            return $this->render(
                "@Merchandise/OrderHelp/steps/coeff.html.twig",
                array(
                    'categories' => $categories,
                    'orderHelp' => $tmp,
                    'doNotCalculate' => true,
                )
            );
        }

        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );


        if ($request->getMethod() == 'POST') {
            if ($helpOrder == null) {
                $helpOrder = new OrderHelpTmp();
                $helpOrder
                    ->setWeek(intval(date('W')))
                    ->setCreatedBy($this->getUser());
            }
            $helpOrderForm = $this->createForm(HelpOrderType::class, $helpOrder);
            $helpOrderForm->handleRequest($request);
            if ($helpOrderForm->isValid()) {
                $progress = new ImportProgression();
                $progress->setStartDateTime(new \DateTime('NOW'))
                    ->setNature('init_help_order_table')
                    ->setProgress(0);

                $this->getDoctrine()->getManager()->persist($progress);
                $this->getDoctrine()->getManager()->persist($helpOrder);

                $this->getDoctrine()->getManager()->flush();

                $this->get('toolbox.command.launcher')->execute(
                    'order:help:init '.$helpOrder->getId().' '.$progress->getId()
                );

                return $this->render(
                    "@Merchandise/OrderHelp/steps/coeff.html.twig",
                    array(
                        'progressId' => $progress->getId(),
                        'orderTmpId' => $helpOrder->getId(),
                    )
                );
            } else {
                return $this->render(
                    "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
                    array(
                        'form' => $helpOrderForm->createView(),
                        'new' => false,
                    )
                );
            }
        } else {
            if ($helpOrder == null) {
                $this->get('session')->getFlashBag()->add('warning', 'start_with_this_step');

                return $this->redirectToRoute("first_step_order_help");
            } else {
                if ($helpOrder->getLocked()) {
                    return $this->render(
                        "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
                        array(
                            'tmp' => $helpOrder,
                            'locked' => true,
                        )
                    );
                }
            }
        }


        return $this->render(
            "@Merchandise/OrderHelp/steps/coeff.html.twig",
            array(
                'orderHelp' => $helpOrder,
                'doNotCalculate' => true,
                'categories' => $categories,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/third_step",name="third_step_order_help")
     */
    public function thirdStep(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );

        if ($helpOrder == null) {
            $this->get('session')->getFlashBag()->add('warning', 'start_with_this_step');

            return $this->redirectToRoute("first_step_order_help");
        }

        if ($helpOrder->getLocked()) {
            return $this->render(
                "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
                array(
                    'tmp' => $helpOrder,
                    'locked' => true,
                )
            );
        }

        if ($request->getMethod() == 'POST') {
            $products = $request->request->getIterator()['product'];
            $natures = $request->request->getIterator()['nature'];
            $fixed = $request->request->has('fixed') ? $request->request->getIterator()['fixed'] : [];

            //Delete all fixed coef
            $this->getDoctrine()->getRepository("Merchandise:OrderHelpFixedCoef")->deleteAllFromRestaurant(
                $currentRestaurant->getId()
            );

            $request->request->remove('product');
            $request->request->remove('nature');
            $error = false;
            foreach ($helpOrder->getProducts() as $hp) {
                $p = $products[$hp->getId()];
                if (!preg_match('/^\-?[0-9]+([,\.][0-9]+)?$/', $p)) {
                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('all_coef_must_be_numeric').$hp->getProduct()->getExternalId()
                    );
                    $error = true;
                    $hp->setCoeff(null);
                } else {
                    $p = str_replace(',', '.', $p);
                    $p = floatval($p);
                    $hp->setCoeff($p);
                }

                if ($natures[$hp->getId()] == 'real') {
                    $hp->setType(OrderHelpProducts::TYPE_REAL);
                } else {
                    $hp->setType(OrderHelpProducts::TYPE_THEO);
                }

                //Test if fixed
                $coefFixed = new OrderHelpFixedCoef();
                $coefFixed
                    ->setReal($hp->getType() == OrderHelpProducts::TYPE_REAL)
                    ->setProduct($hp->getProduct())
                    ->setOriginRestaurant($currentRestaurant);
                if (array_key_exists($hp->getId(), $fixed)) {//is fixed
                    $p = $p / $hp->getProduct()->getInventoryQty();
                    $coefFixed->setCoef($p);
                    $hp->setFixed(true);
                } else {//else
                    $coefFixed->setCoef(null);
                    $hp->setFixed(false);
                }

                $this->getDoctrine()->getManager()->persist($coefFixed);
                $this->getDoctrine()->getManager()->flush();
            }


            if ($error) {
                return $this->redirectToRoute('second_step_order_help');
            }
        }

        $supplierss = $helpOrder->getSuppliers();
        $suppliers = [];
        foreach ($supplierss as $s) {
            $suppliers[] = $s->getSupplier();
        }
        $j = $helpOrder->getMasks()[0]->getStartDate();

        $days = [
            $this->get('translator')->trans('days.sunday'),
            $this->get('translator')->trans('days.monday'),
            $this->get('translator')->trans('days.tuesday'),
            $this->get('translator')->trans('days.wednesday'),
            $this->get('translator')->trans('days.thursday'),
            $this->get('translator')->trans('days.friday'),
            $this->get('translator')->trans('days.saturday'),
        ];

        $shortDays = [
            'Dim',
            'Lun',
            'Mar',
            'Mer',
            'Jeu',
            'Ven',
            'Sam',
        ];

        $dates = [];
        for ($i = 0; $i < 20; $i++) {
            $newDateTs = mktime(
                0,
                0,
                0,
                intval($j->format('m')),
                intval($j->format('d')) + $i,
                intval($j->format('Y'))
            );
            $newDate = new \DateTime();
            $newDate->setTimestamp($newDateTs);

            $dates[$newDate->format('d/m/Y')] = array(
                'bud' => $this->getDoctrine()
                    ->getRepository("Merchandise:CaPrev")
                    ->findOneBy(
                        array(
                            'date' => $newDate,
                        )
                    )->getCa(),
                'day' => $days[intval($newDate->format('w'))],
                'short_day' => $shortDays[intval($newDate->format('w'))],
                'w' => intval($newDate->format('w')),
            );
        }
        $categories = $this->getDoctrine()->getRepository("Merchandise:ProductCategories")->findBy(
            array(
                'eligible' => true,
            )
        );

        return $this->render(
            "@Merchandise/OrderHelp/steps/mask.html.twig",
            array(
                'suppliers' => $suppliers,
                'dates' => $dates,
                'categories' => $categories,
                'days' => $days,
                'tmp' => $helpOrder,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/fourth_step",name="fourth_step_order_help")
     */
    public function fourthStep(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );
        if ($helpOrder == null) {
            $this->get('session')->getFlashBag()->add('warning', 'start_with_this_step');

            return $this->redirectToRoute("first_step_order_help");
        }

        if ($helpOrder->getLocked()) {
            return $this->render(
                "@Merchandise/OrderHelp/steps/calcul_ca.html.twig",
                array(
                    'tmp' => $helpOrder,
                    'locked' => true,
                )
            );
        }

        if ($request->getMethod() == 'POST') {
            $masks = $request->request->getIterator()['mask'];
            $displayAll=$request->request->getIterator()['display_all'];

            if(isset($displayAll) && $displayAll=="display_all"){
                $displayAll=true;
            }

            else {
                $displayAll=false;
            }


            foreach ($masks as $key => $m) {
                $mask = $this->getDoctrine()->getRepository("Merchandise:OrderHelpMask")->find($key);
                $mask
                    ->setRange(floatval($m['range']))
                    ->setAbsoluteDeliveryDay($m['absolute_delivery_day'])
                    ->setAbsoluteOrderDay($m['absolute_order_day'])
                    ->setBudget(floatval($m['budget']));
                $this->getDoctrine()->getManager()->flush();
            }

            $progress = new ImportProgression();
            $progress->setStartDateTime(new \DateTime('NOW'))
                ->setNature('result_help_order_table')
                ->setProgress(0);

            $this->getDoctrine()->getManager()->persist($progress);
            $this->getDoctrine()->getManager()->flush();

            $this->get('toolbox.command.launcher')->execute(
                'order:help:result '.$helpOrder->getId().' '.$progress->getId().' '.$currentRestaurant->getId()
            );

            return $this->render(
                "@Merchandise/OrderHelp/steps/results.html.twig",
                array(
                    'progressId' => $progress->getId(),
                    'orderTmpId' => $helpOrder->getId(),
                    'displayAll' =>$displayAll
                )
            );
        }

        return $this->render("@Merchandise/OrderHelp/steps/results.html.twig");
    }

    /**
     * @return JsonResponse
     * @Route("/load_results_help_order/{displayAll}",name="load_results_help_order",options={"expose"=true})
     */
    public function loadResult($displayAll=false)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if($displayAll=="true"){
            $displayAll=true;
        }

        else {
            $displayAll=false;
        }

        $helpOrder = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );

        $days = [
            $this->get('translator')->trans('days.sunday'),
            $this->get('translator')->trans('days.monday'),
            $this->get('translator')->trans('days.tuesday'),
            $this->get('translator')->trans('days.wednesday'),
            $this->get('translator')->trans('days.thursday'),
            $this->get('translator')->trans('days.friday'),
            $this->get('translator')->trans('days.saturday'),
        ];

        $orderDates = [];
        foreach ($days as $key => $d) {
            $orderDates[] = $this->get('help_order.service')->getOrderDate(
                $helpOrder->getMasks()[0]->getStartDate(),
                $key
            );
        }

        $html = $this->renderView(
            "@Merchandise/OrderHelp/steps/result_table.html.twig",
            array(
                'tmp' => $helpOrder,
                'days' => $days,
                'orderDates' => $orderDates,
                'displayAll'=> $displayAll
            )
        );

        return new JsonResponse(
            array(
                'data' => array(
                    'html' => $html,
                ),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/verify_availability",name="verify_availability",options={"expose"=true})
     */
    public function verifyDayAvailabilityAction(Request $request)
    {

        $day = intval($request->query->get('day'));
        $supplierId = $request->query->get('supplier');

        $result = $this->get('help_order.service')->verifyAvailability($day, $supplierId);

        return new JsonResponse(
            array(
                'data' => $result,
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/create_order_from_help",name="create_order_from_help", options={"expose"=true})
     */
    public function prepareOrderAction(Request $request)
    {
        $day = intval($request->query->get('day'));
        $supplierId = $request->query->get('supplier');
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();


        $helpOrderTmp = $this->getDoctrine()->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );

        $result = $this->get('help_order.service')->verifyAvailability($day, $supplierId);
        if ($result['code'] == 'not_free_no_modification') {
            return new JsonResponse(
                array(
                    'data' => null,
                )
            );
        }

        if (!$request->request->has('product')) {
            return new JsonResponse(
                array(
                    'data' => null,
                )
            );
        }


        $helpOrderTmp->addGeneratedCouples($supplierId."/".$day);

        $postData = $request->request->getIterator();
        $products = $postData['product'];
        $lines = new ArrayCollection();
        foreach ($products as $key => $qty) {
            if (preg_match('/^[0-9]+$/', $qty) > 0) {
                $product = $this->getDoctrine()->getRepository("Merchandise:ProductPurchased")->find($key);
                if ($product) {
                    $orderLine = new OrderLine();
                    $orderLine
                        ->setProduct($product)
                        ->setQty(intval($qty));
                    $lines->add(clone $orderLine);
                }
            } else {
                //Qty not integer, show erro if you want
            }
        }
        foreach ($lines as $l) {
            $this->getDoctrine()->getManager()->persist($l);
        }

        if ($result['code'] == 'free') {// New Order
            $supplier = $this->getDoctrine()->getRepository("Merchandise:Supplier")->find($supplierId);
            $planning = $this->getDoctrine()->getRepository("Merchandise:SupplierPlanning")->findOneBy(
                array(
                    'supplier' => $supplier,
                    'orderDay' => $day,
                )
            );

            $diff=$planning->getDeliveryDay() - $planning->getOrderDay();
            if ($diff < 0) {
                $diff = 7 + $diff;
            }
            $deliveryDay = Utilities::getDateFromDate(
                $result['orderDay'],$diff

            );
            $order = new Order();
            $order
                ->setDateOrder($result['orderDay'])
                ->setDateDelivery($deliveryDay)
                ->setEmployee($this->getUser())
                ->setStatus(Order::DRAFT)
                ->setSupplier($supplier)
                ->setNumOrder($this->get('order.service')->getLastOrderNum())
                ->setLines($lines);
            $order->setOriginRestaurant($currentRestaurant);
        } elseif ($result['code'] == 'not_free_with_modification') {
            $idOrder = $request->query->get('orderId');
            $order = $this->getDoctrine()->getRepository("Merchandise:Order")->find($idOrder);
            foreach ($order->getLines() as $l) {
                $this->getDoctrine()->getManager()->remove($l);
            }
            $this->getDoctrine()->getManager()->flush();
            $order->setLines($lines);

            if ($order->getStatus() == Order::SENDED) {
                $order->setStatus(Order::MODIFIED);
            }
        }

        $this->getDoctrine()->getManager()->persist($order);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(
            array(
                'data' => $order->getId(),
            )
        );
    }
}
