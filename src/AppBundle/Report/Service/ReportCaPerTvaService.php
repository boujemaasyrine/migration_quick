<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 30/07/2018
 * Time: 11:47
 */

namespace AppBundle\Report\Service;


use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportCaPerTvaService
{
    private $em;
    private $restaurantService;
    private $phpExcel;
    private $translator;
    /**
     * ReportCaPerTvaService constructor.
     * @param $em
     * @param $restaurantService
     */
    public function __construct(EntityManager $em, RestaurantService $restaurantService, Factory $factory,Translator $translator)
    {
        $this->em = $em;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
        $this->translator = $translator;
    }

    public function getGlobalCa($data){
        $filter['startDate']= $data['startDate']->format('Y-m-d');
        $filter['endDate']=$data['endDate']->format('Y-m-d');
        $filter['beginDate']=$filter['startDate'];
        $filter['restaurant']= $this->restaurantService->getCurrentRestaurant()->getId();
        $result=$this->em->getRepository(Ticket::class)->getCaTicketPerTva($filter);
        $tickets=$this->em->getRepository(Ticket::class)->getTotalPerPeriod($filter);
        $VenteAnnexe=$this->em->getRepository(TicketLine::class)->getCaVenteAnnexe($filter,$filter['restaurant']);
        $VA=$VenteAnnexe['data'][0]['ca_va'];
        $nbTicket['EatIn']=isset($tickets['EatIn'])?$tickets['EatIn']: 0;
        $nbTicket['TakeOut']=isset($tickets['TakeOut'])?$tickets['TakeOut']: 0;
        $nbTicket['DriveThru']=isset($tickets['DriveThru'])?$tickets['DriveThru']: 0;
        $nbTicket['KioskIn']=isset($tickets['KioskIn'])?$tickets['KioskIn']: 0;
        $nbTicket['KioskOut']=isset($tickets['KioskOut'])?$tickets['KioskOut']: 0;
        $nbTicket['Delivery']=isset($tickets['Delivery'])?$tickets['Delivery']: 0;
        $nbTicket['e_ordering_in']=isset($tickets['e_ordering_in'])?$tickets['e_ordering_in']: 0;
        $nbTicket['e_ordering_out']=isset($tickets['e_ordering_out'])?$tickets['e_ordering_out']: 0;
        $nbTicket['total']=$nbTicket['EatIn']+$nbTicket['TakeOut']+$nbTicket['DriveThru']+$nbTicket['KioskIn']+$nbTicket['KioskOut']+$nbTicket['Delivery']+$nbTicket['e_ordering_in'] +$nbTicket['e_ordering_out'];

        $discountKiosk=$this->em->getRepository(Ticket::class)->getDiscountKiosk($filter);
        $CaNetTTc=0;
        $CaBrutTTc=0;
        $totalDisount=0;
        $CaNetTTcA=0;
        $CaNetTTcAA=0;
        $CaNetTTcB=0;
        $CaNetTTcC=0;
        $CaNetTTcD=0;
        $resultCanal=array('EatIn' =>0,'DriveThru'=>0,'TakeOut'=>0,'KioskIn'=>0,'KioskOut'=>0,'Delivery'=>0,'e_ordering_in'=>0,'e_ordering_out'=>0);
        $br=0;
        foreach ($result as $res){
            $CaNetTTc +=$res['CA_NET_TTC'] ;
            $CaBrutTTc +=$res['CA_BRUT_TTC'] ;
            $totalDisount +=$res['Disc_TTC'] ;
            $br +=$res['br'];
            if($res['canal_vente'] == 'EatIn'){
                $resultCanal['EatIn'] +=$res['CA_NET_TTC'] ;
            }elseif ($res['canal_vente'] == 'DriveThru'){
                $resultCanal['DriveThru'] +=$res['CA_NET_TTC'] ;
            }elseif ($res['canal_vente'] == 'TakeOut'){
                $resultCanal['TakeOut'] +=$res['CA_NET_TTC'] ;
            }elseif ($res['canal_vente'] == 'KioskIn'){
                $resultCanal['KioskIn'] +=$res['CA_NET_TTC'] ;
            }elseif ($res['canal_vente'] == 'KioskOut'){
                $resultCanal['KioskOut'] +=$res['CA_NET_TTC'] ;
            }elseif ($res['canal_vente'] == 'e_ordering_in'){
                $resultCanal['e_ordering_in'] +=$res['CA_NET_TTC'] ;
            }elseif ($res['canal_vente'] == 'e_ordering_out'){
                $resultCanal['e_ordering_out'] +=$res['CA_NET_TTC'] ;
            }
            else{
                $resultCanal['Delivery'] +=$res['CA_NET_TTC'] ;
            }
            if($this->restaurantService->getCurrentRestaurant()->getCountry() == "bel"){
            if($res['taxe'] == '0.21'){
                $CaNetTTcA +=$res['CA_NET_TTC'] ;
            }elseif ($res['taxe'] == '0.12'){
                $CaNetTTcB +=$res['CA_NET_TTC'] ;
            }elseif ($res['taxe'] == '0.06'){
                $CaNetTTcC +=$res['CA_NET_TTC'] ;
            }else{
                $CaNetTTcD +=$res['CA_NET_TTC'] ;
            }
            }elseif ($this->restaurantService->getCurrentRestaurant()->getCountry() == "lux"){
                if($res['taxe'] == '0.17'){
                    $CaNetTTcA +=$res['CA_NET_TTC'] ;
                }elseif($res['taxe'] == '0.16') {
                    $CaNetTTcAA += $res['CA_NET_TTC'];
                }elseif ($res['taxe'] == '0.06'){
                    $CaNetTTcB +=$res['CA_NET_TTC'] ;
                }elseif ($res['taxe'] == '0.03'){
                    $CaNetTTcC +=$res['CA_NET_TTC'] ;
                }else{
                    $CaNetTTcD +=$res['CA_NET_TTC'] ;
                }
            }
        }
        $allResult=array();
        $allResult['nbTicket']=$nbTicket;
        $allResult['discountKiosk']=$discountKiosk[0]['totaldiscount'];
        $allResult['CaNetTTC']=$CaNetTTc;
        $allResult['CaBrutTTc']=$CaBrutTTc;
        $allResult['VenteAnnexe']=$VA;
        $allResult['totalDiscount']= $totalDisount;
        $allResult['br']=$br;
        $allResult['CaNetTTcA']=$CaNetTTcA;$allResult['CaNetTTcAA']=$CaNetTTcAA;  $allResult['CaNetTTcB']=$CaNetTTcB; $allResult['CaNetTTcC']=$CaNetTTcC;$allResult['CaNetTTcD']=$CaNetTTcD;
        $allResult['resultCanal']=$resultCanal;
        $allResult['type']=$this->restaurantService->getCurrentRestaurant()->getCountry();
        return $allResult;
    }



    public function generateExcelFile($result, $filter, Restaurant $currentRestaurant, $logoPath)
    {

        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $period = date_diff($filter['startDate'], $filter['endDate'])->format('%a') + 1;
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('ca_per_tva.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('ca_per_tva.title');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B5"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 5), 22, true);

        //logo
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        $objDrawing->setPath($logoPath);
        $objDrawing->setOffsetX(35);
        $objDrawing->setOffsetY(0);
        $objDrawing->setCoordinates('A2');
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 2), 12, true);
        $objDrawing->setWidth(28);                 //set width, height
        $objDrawing->setHeight(32);
        $objDrawing->setWorksheet($sheet);
        //restaurant name
        $sheet->mergeCells("B2:F2");
        $content = $currentRestaurant->getCode() . ' ' . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE

        //Periode
        $sheet->mergeCells("A10:I10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorTwo);
        $sheet->setCellValue('A10', $this->translator->trans('report.period') . ":");
        ExcelUtilities::setCellAlignment($sheet->getCell("A10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A10"), $alignmentV);

        $sheet->mergeCells("A11:B11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('keyword.from') . ":");
        $sheet->mergeCells("C11:D11");
        ExcelUtilities::setFont($sheet->getCell('C11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C11"), $colorOne);
        $sheet->setCellValue('C11', $filter['startDate']->format('Y-m-d'));    // START DATE


        // END DATE
        $sheet->mergeCells("E11:F11");
        ExcelUtilities::setFont($sheet->getCell('E11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E11"), $colorOne);
        $sheet->setCellValue('E11', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("G11:I11");
        ExcelUtilities::setFont($sheet->getCell('G11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G11"), $colorOne);
        $sheet->setCellValue('G11', $filter['endDate']->format('Y-m-d'));

        //Content
        //CA gobal
        $sheet->mergeCells("A12:I12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('ca_per_tva.ca_global'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A12"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A12"), $alignmentV);

        // tiltle_row
        $sheet->mergeCells("A13:C13");
        ExcelUtilities::setFont($sheet->getCell('A13'), 11, true);
        $sheet->setCellValue('A13', null);
        ExcelUtilities::setCellAlignment($sheet->getCell("A13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A13"), $alignmentV);

        $sheet->mergeCells("D13:F13");
        ExcelUtilities::setFont($sheet->getCell('D13'), 11, true);
        $sheet->setCellValue('D13', $this->translator->trans('ca_per_tva.amount'));
        ExcelUtilities::setCellAlignment($sheet->getCell("D13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D13"), $alignmentV);

        $sheet->mergeCells("G13:I13");
        ExcelUtilities::setFont($sheet->getCell('G13'), 11, true);
        $sheet->setCellValue('G13', $this->translator->trans('ca_per_tva.ticket'));
        ExcelUtilities::setCellAlignment($sheet->getCell("G13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G13"), $alignmentV);

        //ttc_row
        $sheet->mergeCells("A14:C14");
        ExcelUtilities::setFont($sheet->getCell('A14'), 11, true);
        $sheet->setCellValue('A14', $this->translator->trans('ca_per_tva.all_ttc'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A14"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A14"), $alignmentV);

        $sheet->mergeCells("D14:F14");
        ExcelUtilities::setFont($sheet->getCell('D14'), 11, true);
        $sheet->setCellValue('D14', $result['CaNetTTC']);

        $sheet->mergeCells("G14:I14");
        ExcelUtilities::setFont($sheet->getCell('G14'), 11, true);
        $sheet->setCellValue('G14', $result['nbTicket']['total']);


        //brutttc_row
        $sheet->mergeCells("A15:C15");
        ExcelUtilities::setFont($sheet->getCell('A15'), 11, true);
        $sheet->setCellValue('A15', 'Brut TTC');
        ExcelUtilities::setCellAlignment($sheet->getCell("A15"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A15"), $alignmentV);

        $sheet->mergeCells("D15:F15");
        ExcelUtilities::setFont($sheet->getCell('D15'), 11, true);
        $sheet->setCellValue('D15', $result['CaBrutTTc']);

        $sheet->mergeCells("G15:I15");
        ExcelUtilities::setFont($sheet->getCell('G15'), 11, true);
        $sheet->setCellValue('G15', $result['nbTicket']['total']);

        //venteAnnexe_row
        $sheet->mergeCells("A16:C16");
        ExcelUtilities::setFont($sheet->getCell('A16'), 11, true);
        $sheet->setCellValue('A16', 'Ventes Annexes');
        ExcelUtilities::setCellAlignment($sheet->getCell("A16"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A16"), $alignmentV);

        $sheet->mergeCells("D16:F16");
        ExcelUtilities::setFont($sheet->getCell('D16'), 11, true);
        $sheet->setCellValue('D16', $result['VenteAnnexe']);

        $sheet->mergeCells("G16:I16");
        ExcelUtilities::setFont($sheet->getCell('G16'), 11, true);
        $sheet->setCellValue('G16', null);

        //discount_row
        $sheet->mergeCells("A17:C17");
        ExcelUtilities::setFont($sheet->getCell('A17'), 11, true);
        $sheet->setCellValue('A17', 'Discount');
        ExcelUtilities::setCellAlignment($sheet->getCell("A17"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A17"), $alignmentV);

        $sheet->mergeCells("D17:F17");
        ExcelUtilities::setFont($sheet->getCell('D17'), 11, true);
        $sheet->setCellValue('D17', $result['totalDiscount']);

        $sheet->mergeCells("G17:I17");
        ExcelUtilities::setFont($sheet->getCell('G17'), 11, true);
        $sheet->setCellValue('G17',null);

        //discountKiosk_row
        $sheet->mergeCells("A18:C18");
        ExcelUtilities::setFont($sheet->getCell('A18'), 11, true);
        $sheet->setCellValue('A18', 'Discount Kiosk');
        ExcelUtilities::setCellAlignment($sheet->getCell("A18"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A18"), $alignmentV);

        $sheet->mergeCells("D18:F18");
        ExcelUtilities::setFont($sheet->getCell('D18'), 11, true);
        $sheet->setCellValue('D18', $result['discountKiosk']);

        $sheet->mergeCells("G18:I18");
        ExcelUtilities::setFont($sheet->getCell('G18'), 11, true);
        $sheet->setCellValue('G18',null);

        //BR
        $sheet->mergeCells("A19:C19");
        ExcelUtilities::setFont($sheet->getCell('A19'), 11, true);
        $sheet->setCellValue('A19', 'BR');
        ExcelUtilities::setCellAlignment($sheet->getCell("A19"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A19"), $alignmentV);

        $sheet->mergeCells("D19:F19");
        ExcelUtilities::setFont($sheet->getCell('D19'), 11, true);
        $sheet->setCellValue('D19', $result['br']);

        $sheet->mergeCells("G19:I19");
        ExcelUtilities::setFont($sheet->getCell('G19'), 11, true);
        $sheet->setCellValue('G19',null);

        //net ttc
        $sheet->mergeCells("A20:C20");
        ExcelUtilities::setFont($sheet->getCell('A20'), 11, true);
        $sheet->setCellValue('A20', 'Net TTC');
        ExcelUtilities::setCellAlignment($sheet->getCell("A20"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A20"), $alignmentV);

        $sheet->mergeCells("D20:F20");
        ExcelUtilities::setFont($sheet->getCell('D20'), 11, true);
        $sheet->setCellValue('D20', $result['CaNetTTC']);

        $sheet->mergeCells("G20:I20");
        ExcelUtilities::setFont($sheet->getCell('G20'), 11, true);
        $sheet->setCellValue('G20',$result['nbTicket']['total']);

        //CA TVA
        $sheet->mergeCells("A21:I21");
        ExcelUtilities::setFont($sheet->getCell('A21'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A21"), $colorOne);
        $sheet->setCellValue('A21', $this->translator->trans('ca_per_tva.ca_tva'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A21"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A21"), $alignmentV);

        // tiltle_row
        $sheet->mergeCells("A22:C22");
        ExcelUtilities::setFont($sheet->getCell('A22'), 11, true);
        $sheet->setCellValue('A22', null);
        ExcelUtilities::setCellAlignment($sheet->getCell("A22"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A22"), $alignmentV);

        $sheet->mergeCells("D22:I22");
        ExcelUtilities::setFont($sheet->getCell('D22'), 11, true);
        $sheet->setCellValue('D22', $this->translator->trans('ca_per_tva.amount'));
        ExcelUtilities::setCellAlignment($sheet->getCell("D22"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D22"), $alignmentV);

        // ttc_row
        $sheet->mergeCells("A23:C23");
        ExcelUtilities::setFont($sheet->getCell('A23'), 11, true);
        $sheet->setCellValue('A23', $this->translator->trans('ca_per_tva.ca_per_tva'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A23"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A23"), $alignmentV);

        $sheet->mergeCells("D23:I23");
        ExcelUtilities::setFont($sheet->getCell('D23'), 11, true);
        $sheet->setCellValue('D23', $result['CaNetTTC']);
              // netttcA_row
//              $sheet->mergeCells("A24:C24");
//              ExcelUtilities::setFont($sheet->getCell('A24'), 11, true);
//              if($result['type']=='bel'){
//                  $sheet->setCellValue('A24','A '. $this->translator->trans('ca_per_tva.tva'). ' 21%');
//              }else{
//                   $sheet->setCellValue('A24','A '. $this->translator->trans('ca_per_tva.tva'). ' 17%');
//              }
//        ExcelUtilities::setCellAlignment($sheet->getCell("A24"), $alignmentH);
//        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A24"), $alignmentV);
//
//              $sheet->mergeCells("D24:I24");
//              ExcelUtilities::setFont($sheet->getCell('D24'), 11, true);
//              $sheet->setCellValue('D24', $result['CaNetTTcA']);

        // netttcAA_row
        $sheet->mergeCells("A24:C24");
        ExcelUtilities::setFont($sheet->getCell('A24'), 11, true);
        if($result['type']=='bel'){
            $sheet->setCellValue('A24','A '. $this->translator->trans('ca_per_tva.tva'). ' 21%');
        }else{
            $sheet->setCellValue('A24','A '. $this->translator->trans('ca_per_tva.tva'). ' 16%');
        }
        ExcelUtilities::setCellAlignment($sheet->getCell("A24"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A24"), $alignmentV);

        $sheet->mergeCells("D24:I24");
        ExcelUtilities::setFont($sheet->getCell('D24'), 11, true);
        $sheet->setCellValue('D24', $result['CaNetTTcAA']);
              // netttcB_row
              $sheet->mergeCells("A25:C25");
              ExcelUtilities::setFont($sheet->getCell('A25'), 11, true);
        if($result['type']=='bel') {
            $sheet->setCellValue('A25', 'B ' . $this->translator->trans('ca_per_tva.tva') . ' 12%');
        }else{
            $sheet->setCellValue('A25', 'B ' . $this->translator->trans('ca_per_tva.tva') . ' 6%');
        }
        ExcelUtilities::setCellAlignment($sheet->getCell("A25"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A25"), $alignmentV);
              $sheet->mergeCells("D25:I25");
              ExcelUtilities::setFont($sheet->getCell('D25'), 11, true);
              $sheet->setCellValue('D25', $result['CaNetTTcB']);
              // netttcC_row
              $sheet->mergeCells("A26:C26");
              ExcelUtilities::setFont($sheet->getCell('A26'), 11, true);
        if($result['type']=='bel') {
            $sheet->setCellValue('A26', 'C ' . $this->translator->trans('ca_per_tva.tva') . ' 6%');
        }else{
            $sheet->setCellValue('A26', 'C ' . $this->translator->trans('ca_per_tva.tva') . ' 3%');
        }
        ExcelUtilities::setCellAlignment($sheet->getCell("A26"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A26"), $alignmentV);

              $sheet->mergeCells("D26:I26");
              ExcelUtilities::setFont($sheet->getCell('D26'), 11, true);
              $sheet->setCellValue('D26', $result['CaNetTTcC']);

              // netttcD_row
              $sheet->mergeCells("A27:C27");
              ExcelUtilities::setFont($sheet->getCell('A27'), 11, true);
              $sheet->setCellValue('A27', 'D '.$this->translator->trans('ca_per_tva.tva'). ' 0%');
              ExcelUtilities::setCellAlignment($sheet->getCell("A27"), $alignmentH);
              ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A27"), $alignmentV);

              $sheet->mergeCells("D27:I27");
              ExcelUtilities::setFont($sheet->getCell('D27'), 11, true);
              $sheet->setCellValue('D27', $result['CaNetTTcD']);

        //net ttc
        $sheet->mergeCells("A28:C28");
        ExcelUtilities::setFont($sheet->getCell('A28'), 11, true);
        $sheet->setCellValue('A28', 'Net TTC');
        ExcelUtilities::setCellAlignment($sheet->getCell("A28"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A28"), $alignmentV);

        $sheet->mergeCells("D28:I28");
        ExcelUtilities::setFont($sheet->getCell('D28'), 11, true);
        $sheet->setCellValue('D28', $result['CaNetTTC']);


        //CA Per canal
        $sheet->mergeCells("A29:I29");
        ExcelUtilities::setFont($sheet->getCell('A29'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A29"), $colorOne);
        $sheet->setCellValue('A29', $this->translator->trans('ca_per_tva.ca_per_canal'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A29"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A29"), $alignmentV);


        // tiltle_row
        $sheet->mergeCells("A30:C30");
        ExcelUtilities::setFont($sheet->getCell('A30'), 11, true);
        $sheet->setCellValue('A30', null);
        ExcelUtilities::setCellAlignment($sheet->getCell("A30"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A30"), $alignmentV);

        $sheet->mergeCells("D30:F30");
        ExcelUtilities::setFont($sheet->getCell('D30'), 11, true);
        $sheet->setCellValue('D30', $this->translator->trans('ca_per_tva.amount'));
        ExcelUtilities::setCellAlignment($sheet->getCell("D30"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D30"), $alignmentV);

        $sheet->mergeCells("G30:I30");
        ExcelUtilities::setFont($sheet->getCell('G30'), 11, true);
        $sheet->setCellValue('G30', $this->translator->trans('ca_per_tva.ticket'));
        ExcelUtilities::setCellAlignment($sheet->getCell("G30"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G30"), $alignmentV);


        //Eat In
        $sheet->mergeCells("A31:C31");
        ExcelUtilities::setFont($sheet->getCell('A31'), 11, true);
        $sheet->setCellValue('A31', 'Eat IN');
        ExcelUtilities::setCellAlignment($sheet->getCell("A31"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A31"), $alignmentV);

        $sheet->mergeCells("D31:F31");
        ExcelUtilities::setFont($sheet->getCell('D31'), 11, true);
        $sheet->setCellValue('D31', $result['resultCanal']['EatIn']);

        $sheet->mergeCells("G31:I31");
        ExcelUtilities::setFont($sheet->getCell('G31'), 11, true);
        $sheet->setCellValue('G31', $result['nbTicket']['EatIn']);


        //Take out
        $sheet->mergeCells("A32:C32");
        ExcelUtilities::setFont($sheet->getCell('A32'), 11, true);
        $sheet->setCellValue('A32', 'Take Out');
        ExcelUtilities::setCellAlignment($sheet->getCell("A32"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A32"), $alignmentV);

        $sheet->mergeCells("D32:F32");
        ExcelUtilities::setFont($sheet->getCell('D32'), 11, true);
        $sheet->setCellValue('D32', $result['resultCanal']['TakeOut']);

        $sheet->mergeCells("G32:I32");
        ExcelUtilities::setFont($sheet->getCell('G32'), 11, true);
        $sheet->setCellValue('G32', $result['nbTicket']['TakeOut']);

        //Drive
        $sheet->mergeCells("A33:C33");
        ExcelUtilities::setFont($sheet->getCell('A33'), 11, true);
        $sheet->setCellValue('A33', 'Drive');
        ExcelUtilities::setCellAlignment($sheet->getCell("A33"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A33"), $alignmentV);

        $sheet->mergeCells("D33:F33");
        ExcelUtilities::setFont($sheet->getCell('D33'), 11, true);
        $sheet->setCellValue('D33', $result['resultCanal']['DriveThru']);

        $sheet->mergeCells("G33:I33");
        ExcelUtilities::setFont($sheet->getCell('G33'), 11, true);
        $sheet->setCellValue('G33', $result['nbTicket']['DriveThru']);

        //Kiosk Eat In
        $sheet->mergeCells("A34:C34");
        ExcelUtilities::setFont($sheet->getCell('A34'), 11, true);
        $sheet->setCellValue('A34', 'Kiosk Eat In');
        ExcelUtilities::setCellAlignment($sheet->getCell("A34"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A34"), $alignmentV);

        $sheet->mergeCells("D34:F34");
        ExcelUtilities::setFont($sheet->getCell('D34'), 11, true);
        $sheet->setCellValue('D34', $result['resultCanal']['KioskIn']);

        $sheet->mergeCells("G34:I34");
        ExcelUtilities::setFont($sheet->getCell('G34'), 11, true);
        $sheet->setCellValue('G34', $result['nbTicket']['KioskIn']);

        //Kiosk Take out
        $sheet->mergeCells("A35:C35");
        ExcelUtilities::setFont($sheet->getCell('A35'), 11, true);
        $sheet->setCellValue('A35', 'Kiosk Take out');
        ExcelUtilities::setCellAlignment($sheet->getCell("A35"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A35"), $alignmentV);

        $sheet->mergeCells("D35:F35");
        ExcelUtilities::setFont($sheet->getCell('D35'), 11, true);
        $sheet->setCellValue('D35', $result['resultCanal']['KioskOut']);

        $sheet->mergeCells("G35:I35");
        ExcelUtilities::setFont($sheet->getCell('G35'), 11, true);
        $sheet->setCellValue('G35', $result['nbTicket']['KioskOut']);

        //Delivery
        $sheet->mergeCells("A36:C36");
        ExcelUtilities::setFont($sheet->getCell('A36'), 11, true);
        $sheet->setCellValue('A36', 'Delivery');
        ExcelUtilities::setCellAlignment($sheet->getCell("A36"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A36"), $alignmentV);

        $sheet->mergeCells("D36:F36");
        ExcelUtilities::setFont($sheet->getCell('D36'), 11, true);
        $sheet->setCellValue('D36', $result['resultCanal']['Delivery']);

        $sheet->mergeCells("G36:I36");
        ExcelUtilities::setFont($sheet->getCell('G36'), 11, true);
        $sheet->setCellValue('G36', $result['nbTicket']['Delivery']);


        //E-ordering IN
        $sheet->mergeCells("A37:C37");
        ExcelUtilities::setFont($sheet->getCell('A37'), 11, true);
        $sheet->setCellValue('A37', 'E-ordering IN');
        ExcelUtilities::setCellAlignment($sheet->getCell("A37"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A37"), $alignmentV);

        $sheet->mergeCells("D37:F37");
        ExcelUtilities::setFont($sheet->getCell('D37'), 11, true);
        $sheet->setCellValue('D37', $result['resultCanal']['e_ordering_in']);

        $sheet->mergeCells("G37:I37");
        ExcelUtilities::setFont($sheet->getCell('G37'), 11, true);
        $sheet->setCellValue('G37', $result['nbTicket']['e_ordering_in']);

        //E-ordering OUT
        $sheet->mergeCells("A38:C38");
        ExcelUtilities::setFont($sheet->getCell('A38'), 11, true);
        $sheet->setCellValue('A38', 'E-ordering OUT');
        ExcelUtilities::setCellAlignment($sheet->getCell("A38"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A38"), $alignmentV);

        $sheet->mergeCells("D38:F38");
        ExcelUtilities::setFont($sheet->getCell('D38'), 11, true);
        $sheet->setCellValue('D38', $result['resultCanal']['e_ordering_out']);

        $sheet->mergeCells("G38:I38");
        ExcelUtilities::setFont($sheet->getCell('G38'), 11, true);
        $sheet->setCellValue('G38', $result['nbTicket']['e_ordering_out']);



        $filename = "Rapport_CA_Par_TVA_".date('dmY_His').".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}