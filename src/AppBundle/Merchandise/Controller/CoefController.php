<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/05/2016
 * Time: 09:30
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\CoefBase;
use AppBundle\Merchandise\Entity\Coefficient;
use AppBundle\Merchandise\Entity\OrderHelpFixedCoef;
use AppBundle\Merchandise\Form\Coefficient\CoefBaseType;
use AppBundle\Security\RightAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CoefController
 *
 * @package               AppBundle\Merchandise\Controller
 * @Route("/coefficient")
 */
class CoefController extends Controller
{


    private function calculateCA(\DateTime $startDate, \DateTime $endDate)
    {
        $cas = $this->getDoctrine()
            ->getRepository("Financial:FinancialRevenue")
            ->getFinancialRevenueBetweenDates(
                $startDate,
                $endDate,
                $this->get('restaurant.service')->getCurrentRestaurant()
            );

        $ca = 0;
        foreach ($cas as $c) {
            $ca += $c->getBrutTTC();
        }

        return $ca;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/calculate_base",name="coef_calculate_base", options={"expose"=true})
     * @RightAnnotation("coef_calculate_base")
     */
    public function calculateAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
         $base = $this->getDoctrine()->getRepository("Merchandise:CoefBase")->findCoefBaseOfCurrentWeek($restaurant);
        if ($base && $base->getLocked()) {
            return $this->render(
                "@Merchandise/Coef/base_calcul.html.twig",
                array(
                    'base' => $base,
                    'locked' => true,
                )
            );
        }

        if ($base == null) {
            $base = new CoefBase();

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

            $ca = $this->calculateCA($lastMonday, $lastSunday);

            $base
                ->setStartDate($lastMonday)
                ->setEndDate($lastSunday)
                ->setCa($ca)
                ->setWeek(intval(date('W')))
                ->setOriginRestaurant($restaurant);
            $this->getDoctrine()->getManager()->persist($base);
        }

        $form = $this->createForm(CoefBaseType::class, $base);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $loss=$request->get('loss');
                $ca = $this->calculateCA($base->getStartDate(), $base->getEndDate());
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(
                        array(
                            'ca' => $ca,
                        )
                    );
                } else {//GO TO STEP TWO
                    $base->setCa($ca);

                    $progress = new ImportProgression();
                    $progress->setStartDateTime(new \DateTime())
                        ->setNature('coeff')
                        ->setStatus('pending');
                    $this->getDoctrine()->getManager()->persist($progress);
                    $this->getDoctrine()->getManager()->flush();
                    //Launch cof calcul
                    $this->get('toolbox.command.launcher')->execute(
                        "coef:calculate ".$base->getId()." ".$progress->getId()." ".$restaurant->getId()." ".$loss
                    );

                    //return the coef page with base id and the progress id

                    return $this->render(
                        "@Merchandise/Coef/coeff_pp.html.twig",
                        array(
                            'progressID' => $progress->getId(),
                            'baseID' => $base->getId(),
                        )
                    );
                }
            } else { // Form INVALID
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(
                        array(
                            'error' => true,
                            'html' => $this->renderView(
                                "@Merchandise/Coef/base_calcul_form.html.twig",
                                array(
                                    'form' => $form->createView(),
                                )
                            ),
                        )
                    );
                }
            }
        }

        return $this->render(
            "@Merchandise/Coef/base_calcul.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param CoefBase $base
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/show_coeff_pp/{base}",name="show_coeff_pp",options={"expose"=true})
     * @RightAnnotation("show_coeff_pp")
     */
    public function showCoeffPPAction(CoefBase $base = null)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($base == null) {
            $base = $this->getDoctrine()->getRepository("Merchandise:CoefBase")->findOneBy(
                array("originRestaurant" => $currentRestaurant),
                array(
                    'id' => 'DESC',
                )
            );
            if ($base == null) {
                return $this->redirectToRoute("coef_calculate_base");
            }
        }
        if ($base->getLocked()) {
            return $this->render(
                "@Merchandise/Coef/coeff_pp.html.twig",
                array(
                    'locked' => true,
                )
            );
        }


        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->getRestaurantSuppliers(
            $currentRestaurant
        );

        $categories = $this->getDoctrine()->getRepository("Merchandise:ProductCategories")->findBy(
            [],
            ['name' => 'ASC']
        );


        return $this->render(
            "@Merchandise/Coef/coeff_pp.html.twig",
            array(
                'base' => $base,
                'suppliers' => $suppliers,
                'categories' => $categories,
            )
        );
    }

    /**
     * @param Request $request
     * @param CoefBase $base
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/save_coef/{base}",name="save_coef")
     * @Method("POST")
     */
    public function saveCoefAction(Request $request, CoefBase $base)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
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
        foreach ($base->getCoefs() as $c) {
            $p = $products[$c->getId()];
            if (!preg_match('/^\-?[0-9]+([,\.][0-9]+)?$/', $p)) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('all_coef_must_be_numeric').$c->getProduct()->getExternalId()
                );
                $error = true;
                $c->setCoef(null);
            } else {
                $c->setCoef(intval($p));
            }

            if ($natures[$c->getId()] == 'real') {
                $c->setType(Coefficient::TYPE_REAL);
            } else {
                $c->setType(Coefficient::TYPE_THEO);
            }

            //Test if fixed
            $coefFixed = new OrderHelpFixedCoef();
            $coefFixed
                ->setReal($c->getType() == Coefficient::TYPE_REAL)
                ->setProduct($c->getProduct())
                ->setOriginRestaurant($currentRestaurant);

            if (array_key_exists($c->getId(), $fixed)) {//is fixed
                $coefFixed->setCoef($c->getCoef());
                $c->setFixed(true);
            } else {//else
                $coefFixed->setCoef(null);
                $c->setFixed(false);
            }

            $this->getDoctrine()->getManager()->persist(clone $coefFixed);
        }

        $base->setUpdatedAt(new \DateTime('NOW'));
        $this->getDoctrine()->getManager()->flush();

        $this->get('session')->getFlashBag()->add('success', 'coef_saved_with_success');

        return $this->redirectToRoute("show_coeff_pp", array('base' => $base->getId()));
    }

    /**
     * @param CoefBase $base
     * @Route("/download_coef/{base}",name="coef_download")
     */
    public function downloadCoefAction(CoefBase $base){
        $currentRestaurant=$this->get("restaurant.service")->getCurrentRestaurant();
        $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
        $response = $this->get('coef.service')->generateExcelFile($base,$currentRestaurant  , $logoPath);

        return $response;
    }
}
