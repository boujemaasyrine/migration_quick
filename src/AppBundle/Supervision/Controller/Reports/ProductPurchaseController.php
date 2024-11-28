<?php

namespace AppBundle\Supervision\Controller\Reports;

use AppBundle\Supervision\Service\Reports\ProductPurchasedMvmtService;
use AppBundle\Supervision\Form\Reports\FilterByDateAndRestaurantType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class ProductPurchaseController
 *
 * @package                     AppBundle\Report\Controller
 * @Route("report/product_purchase")
 */
class ProductPurchaseController extends Controller
{

    /**
     * @param Request $request
     * @param int $download
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/{download}",name="supervision_product_purchase_report",defaults={"download"=0})
     */
    public function indexAction(Request $request, $download = 0)
    {
        $form = $this->createForm(new FilterByDateAndRestaurantType($this->getUser()));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $beginDate = $form['beginDate']->getData();
            $endDate = $form['endDate']->getData();
            $restaurants = $form['restaurant']->getData()->isEmpty() ? $this->getUser()->getEligibleRestaurants() : $form['restaurant']->getData();
            if ((int)$download == 1) {
                $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                $response = $this->get('supervision.product.purchased.mvmt.service')->getProductPurchasedReportExcelFile($beginDate, $endDate, $restaurants, $logoPath);
                return $response;
            }
        }
        return $this->render(
            "@Supervision/Reports/ProductPurchase/index.html.twig",
            array('form' => $form->createView())
        );
    }
}

