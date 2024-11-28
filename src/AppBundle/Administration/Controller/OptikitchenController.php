<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/05/2016
 * Time: 16:35
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Entity\Optikitchen\Optikitchen;
use AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\RightAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class OptikitchenController
 *
 * @package               AppBundle\Administration\Controller
 * @Route("/optikitchen")
 */
class OptikitchenController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/calcul/{success}",name="optikitchen_calcul",options={"expose"=true})
     * @RightAnnotation("optikitchen_calcul")
     */
    public function calcul(Request $request, $success = 0)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $today = new \DateTime('today');
        $o = $this->getDoctrine()->getRepository("Administration:Optikitchen\\Optikitchen")
            ->findOneBy(
                array(
                    'date' => $today,
                    'originRestaurant' => $currentRestaurant,
                )
            );

        if ($o && $o->getLocked()) {
            return $this->redirectToRoute("opti_consultation");
        }

        $this->get('ca_prev.service')->createIfNotExsit($today);
        $caPrev = $this->getDoctrine()->getRepository("Merchandise:CaPrev")->findOneBy(
            array(
                'date' => $today,
                'originRestaurant' => $currentRestaurant,
            )
        );

        $cas['budget'] = $this->_getCas(
            $caPrev->getDate1(),
            $caPrev->getDate2(),
            $caPrev->getDate3(),
            $caPrev->getDate4()
        );//fixed

        if ($o) {
            $date1 = $o->getDate1();
            $date2 = $o->getDate2();
            $date3 = $o->getDate3();
            $date4 = $o->getDate4();
        } else {
            $dates = $this->get('optikitchen.service')->getDefaultDates($today,$currentRestaurant);//fixed
            $date1 = $dates[0];
            $date2 = $dates[1];
            $date3 = $dates[2];
            $date4 = $dates[3];
        }

        $cas['opti'] = $this->_getCas($date1, $date2, $date3, $date4); //fixed

        $form = $this->createFormBuilder(
            array(
                'date1' => $date1,
                'date2' => $date2,
                'date3' => $date3,
                'date4' => $date4,
            )
        )
            ->add(
                'date1',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'date2',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'date3',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'date4',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->getForm();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $progress = new ImportProgression();
                $progress->setStatus('pending')
                    ->setStartDateTime(new \DateTime())
                    ->setNature('optikitchen_calcul');
                $this->getDoctrine()->getManager()->persist($progress);
                $this->getDoctrine()->getManager()->flush();

                $date1 = $form->getData()['date1'];
                $date2 = $form->getData()['date2'];
                $date3 = $form->getData()['date3'];
                $date4 = $form->getData()['date4'];

                //$this->get('optikitchen.service')->calculate($today,[$date1,$date2,$date3,$date4],$progress);

                $this->get('toolbox.command.launcher')->execute(
                    "quick:optikitchen:calcul ".$today->format('Y-m-d')." ".$date1->format('Y-m-d')." ".$date2->format('Y-m-d')." ".$date3->format('Y-m-d')." ".$date4->format('Y-m-d')." ".$progress->getId()." ".$currentRestaurant->getId());


                return $this->render(
                    "@Administration/Optikitchen/calcul.html.twig",
                    array(
                        'progressID' => $progress->getId(),
                        'day' => $today,
                        'caPrev' => $caPrev,
                        'cas' => $cas,
                    )
                );
            }
        }

        return $this->render(
            "@Administration/Optikitchen/calcul.html.twig",
            array(
                'form' => $form->createView(),
                'day' => $today,
                'caPrev' => $caPrev,
                'cas' => $cas,
                'success' => intval($success),
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("/consultation",name="opti_consultation")
     * @RightAnnotation("opti_consultation")
     * @Method("GET")
     */
    public function consultationAction()
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $today = new \DateTime('today');
        $o = $this->getDoctrine()->getRepository("Administration:Optikitchen\\Optikitchen")
            ->findOneBy(
                array(
                    'date' => $today,
                    'originRestaurant' => $currentRestaurant,
                )
            );
        if (!$o) {
            $this->addFlash(
                'warning',
                $this->get('translator')->trans('optikitchen.msg_abs', ['%1%' => $today->format('d/m/Y')])
            );

            return $this->redirectToRoute("optikitchen_calcul");
        } elseif ($o->getLocked()) {
            $this->addFlash('success', $this->get('translator')->trans('optikitchen.please_wait'));
        }

        $productSold = $this->getDoctrine()->getRepository("Administration:Optikitchen\\OptikitchenProduct")
            ->findBy(
                array(
                    'type' => 'sold',
                    'optikitchen' => $o,
                )
            );

        $productPurchased = $this->getDoctrine()->getRepository("Administration:Optikitchen\\OptikitchenProduct")
            ->findBy(
                array(
                    'type' => 'purchased',
                    'optikitchen' => $o,
                )
            );

        return $this->render(
            "@Administration/Optikitchen/products.html.twig",
            array(
                'sProducts' => $productSold,
                'pProducts' => $productPurchased,
                'opti' => $o,
                'c1' => $this->_getCa($o->getDate1()),
                'c2' => $this->_getCa($o->getDate2()),
                'c3' => $this->_getCa($o->getDate3()),
                'c4' => $this->_getCa($o->getDate4()),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/ca_optikitchen",name="ca_optikitchen",options={"expose"=true})
     */
    public function getCaJsonAction(Request $request)
    {

        if (!$request->request->has('date')) {
            return new JsonResponse(
                array(
                    'ca' => null,
                )
            );
        }

        $dateS = $request->request->get('date');
        $date = \DateTime::createFromFormat('d/m/Y', $dateS);
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();

        $financialRevenu = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")->findOneBy(
            array('date' => $date, 'originRestaurant' => $currentRestaurant)
        );

        if (!$financialRevenu) {
            return new JsonResponse(
                array(
                    'ca' => null,
                )
            );
        }

        return new JsonResponse(['ca' => $financialRevenu->getAmount()]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @param $request
     * @Route("/send",name="send_optikitchen")
     * @Method("POST")
     */
    public function sendToOptikitchen(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $this->get('app.security.checker')->checkOrThrowAccedDenied('export_optikitchen');
        $today = new \DateTime('today');
        $o = $this->getDoctrine()->getRepository("Administration:Optikitchen\\Optikitchen")
            ->findOneBy(
                array(
                    'date' => $today,
                    'originRestaurant' => $currentRestaurant,
                )
            );

        $this->_save($request, $o);

        //Send To Optikitchen
        $ps = $this->getDoctrine()->getRepository("Administration:Optikitchen\\OptikitchenProduct")
            ->findBy(
                array(
                    'optikitchen' => $o,
                    'type' => 'sold',
                )
            );

        $pp = $this->getDoctrine()->getRepository("Administration:Optikitchen\\OptikitchenProduct")
            ->findBy(
                array(
                    'optikitchen' => $o,
                    'type' => 'purchased',
                )
            );

        $matrix = $this->getDoctrine()->getRepository("Administration:Optikitchen\\OptikitchenMatrix")->findBy(
            [],
            ['level' => 'asc']
        );
        $xml = $this->renderView(
            "@Administration/Optikitchen/XML/optikitchen_main.xml.twig",
            [
                'optikitchen' => $o,
                'sold_products' => $ps,
                'purchased_products' => $pp,
                'matrix' => $matrix,
            ]
        );

        $filename = $this->get('optikitchen.service')->generateFilename($o);

        //Generating XML for download
        $filePath = $this->container->getParameter('kernel.root_dir')."/../web/uploads/".$filename;
        file_put_contents($filePath, $xml);
        $link = $this->generateUrl('download_xml')."?filename=$filename";
        $this->addFlash('xml', "Fonctionnalité de Test, <a href='$link'>Télécharger le XML Généré</a>");

        $optikitchenDir = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::OPTIKITCHEN_PATH, 'originRestaurant' => $currentRestaurant)
        );
        //Generating XML in the shared dir
        if ($optikitchenDir) {

            $optikitchenDir=$optikitchenDir->getValue();

            if (is_dir($optikitchenDir)) {
                if (is_writable($optikitchenDir)) {
                    $filePath2 = $optikitchenDir."/".$filename;
                    file_put_contents($filePath2, $xml);
                    $this->addFlash('success', $this->get('translator')->trans('optikitchen.coef_sended_with_success'));
                } else {
                    $this->addFlash(
                        'error',
                        $this->get('translator')->trans(
                            'optikitchen_path_not_writable',
                            [
                                '%1%' => $optikitchenDir,
                            ]
                        )
                    );
                }
            } else {
                $this->addFlash(
                    'error',
                    $this->get('translator')->trans(
                        'optikitchen_path_not_exist',
                        [
                            '%1%' => $optikitchenDir,
                        ]
                    )
                );
            }
        } else {
            $this->addFlash('error', 'optikitchen_path_is_not_set');
        }


        $o->setLastSynchoDate(new \DateTime('now'));
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('opti_consultation');
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/param",name="optikitchen_param")
     * @RightAnnotation("optikitchen_param")
     */
    public function parameterOptikitchenAction(Request $request) //fixed
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($request->getMethod() == 'POST') {
            $this->get('optikitchen.service')->resetEligibiliteForAllProducts();
            if ($request->request->has('eligible') && is_array($request->request->get('eligible'))) {
                $data = $request->request->get('eligible');
                foreach ($data as $key => $value) {
                    $p = $this->getDoctrine()->getRepository("Merchandise:Product")->find($key);
                    if ($p) {
                        $p->setEligibleForOptikitchen(true);
                    }
                }
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'config_saved_with_success');
            }
        }

        $productsPurchased = $this->getDoctrine()->getRepository("Merchandise:ProductPurchased")
            ->getActivatedProductsInDay(new \DateTime('now'), false, $currentRestaurant);

        $productsSold = $this->getDoctrine()->getRepository("Merchandise:ProductSold")
            ->findBy(
                array(
                    'active' => true,
                    'originRestaurant' => $currentRestaurant,
                )
            );

        return $this->render(
            "@Administration/Optikitchen/parameter_optikitchen.html.twig",
            array(
                'productsPurchased' => $productsPurchased,
                'productsSold' => $productsSold,
            )
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/donwload_xml",name="download_xml")
     */
    public function downloadAction(Request $request)
    {
        $filename = $request->query->get('filename');
        $path = $this->get('kernel')->getRootDir()."/../web/uploads/";

        $filepath = $path.$filename;

        if (!file_exists($filepath)) {
            throw $this->createNotFoundException();
        }

        $content = file_get_contents($filepath);

        $response = new Response();

        //set headers
        $response->headers->set('Content-Type', mime_content_type($filepath));
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);

        $response->setContent($content);

        return $response;
    }

    /**
     *
     * * *** PRIVATES *****
     */

    private function _save(Request $request, Optikitchen $o)
    {
        //$this->get('app.security.checker')->checkOrThrowAccedDenied('edit_optikitchen');
        $data = $request->request->getIterator();
        if (isset($data['coef']) && is_array($data['coef'])) {
            foreach ($data['coef'] as $key => $value) {
                //Test on validity
                $oPro = $productPurchased = $this->getDoctrine()->getRepository(
                    "Administration:Optikitchen\\OptikitchenProduct"
                )
                    ->findOneBy(
                        array(
                            'id' => $key,
                            'optikitchen' => $o,
                        )
                    );

                if ($oPro && trim($value) != '') {
                    $oPro->setCoef(floatval($value));
                } else {
                    $oPro->setCoef(null);
                }
            }
        }
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', $this->get('translator')->trans('optikitchen.coef_saved_with_succes'));
    }

    private function _getCa(\DateTime $d)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $c = $this->getDoctrine()->getRepository('Financial:FinancialRevenue')->findOneBy(['date' => $d,'originRestaurant'=>$currentRestaurant]);
        if ($c) {
            $c = $c->getAmount();
        } else {
            $c = 0;
        }

        return $c;
    }

    private function _getCas($date1, $date2, $date3, $date4)//fixed
    {
        // Ca for bud previsionnel
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $ca1 = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")->findOneBy(
            array(
                'date' => $date1,
                'originRestaurant' => $currentRestaurant,
            )
        );
        if ($ca1) {
            $ca1 = $ca1->getAmount();
        } else {
            $ca1 = 0;
        }

        $ca2 = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")->findOneBy(
            array(
                'date' => $date2,
                'originRestaurant' => $currentRestaurant,
            )
        );
        if ($ca2) {
            $ca2 = $ca2->getAmount();
        } else {
            $ca2 = 0;
        }

        $ca3 = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")->findOneBy(
            array(
                'date' => $date3,
                'originRestaurant' => $currentRestaurant,
            )
        );
        if ($ca3) {
            $ca3 = $ca3->getAmount();
        } else {
            $ca3 = 0;
        }

        $ca4 = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")->findOneBy(
            array(
                'date' => $date4,
                'originRestaurant' => $currentRestaurant,
            )
        );
        if ($ca4) {
            $ca4 = $ca4->getAmount();
        } else {
            $ca4 = 0;
        }

        // end Ca for bud previsionnel
        return [$ca1, $ca2, $ca3, $ca4];
    }
}
