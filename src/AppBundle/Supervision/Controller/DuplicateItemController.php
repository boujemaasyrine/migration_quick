<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 15/02/2019
 * Time: 17:42
 */
namespace AppBundle\Supervision\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Supervision\Service\Reports\DuplicateItemReportService;

class DuplicateItemController extends Controller{


    /**
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PHPExcel_Exception
     * @Route("/download-duplicate-inventory-item", name="download_duplicate_inventory_item", options={"expose"=true})
     */
    public function duplicateInventoryItemReportAction()
    {
        $version= $this->container->getParameter("version");
        /**
         * @var DuplicateItemReportService $dir
         */
        $dir=$this->get('duplicate.item.report.service');
        $responce=$dir->generateDuplicateInventoryItemExcelFile($version);

        return $responce;
    }



    /**
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PHPExcel_Exception
     * @Route("/download-duplicate-product-sold", name="download_duplicate_product_sold", options={"expose"=true})
     */
    public function duplicateProductSoldReportAction()
    {
        $version= $this->container->getParameter("version");
        /**
         * @var DuplicateItemReportService $dir
         */
        $dir=$this->get('duplicate.item.report.service');
        $responce= $dir->generateDuplicateProductSoldExcelFile($version);

        return $responce;
    }


}