<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 11/10/2016
 * Time: 09:55
 */

namespace AppBundle\Report\Service;


use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportCancellationService
{

    private $em;
    private $translator;
    private $paramService;
    private $phpExcel;

    /**
     * ReportDiscountService constructor.
     * @param $em
     * @param $translator
     * @param $paramService
     */
    public function __construct(EntityManager $em, Translator $translator, ParameterService $paramService, Factory $factory)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService= $paramService;
        $this->phpExcel = $factory;
    }

    public function getHoursList(Restaurant $currentRestaurant){
        $openingHour = ($this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            ) == null)
            ? 0
            : $this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            );
        $closingHour = ($this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            ) == null)
            ? 23
            : $this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            );
        $hoursArray = array();
        if ($closingHour <= $openingHour) {
            ;
        }
        $closingHour += 24;
        for ($i = intval($openingHour); $i <= intval($closingHour); $i++) {
            $hoursArray[$i] = (($i >= 24) ? ($i - 24) : $i).":00";
        }

        return $hoursArray;
    }

    public function getCAReel($filter)
    {
        $startHour = (is_null($filter['startHour'])) ? 0 : $filter['startHour'];
        $endHour = (is_null($filter['endHour'])) ? 23 : $filter['endHour'];
        $dateStart = $filter['startDate'];
        $dateEnd = $filter['endDate'];
        $cashier = $filter['cashier'];
        $invoice = $filter['InvoiceNumber'];
        $currentRestaurant = $filter['currentRestaurant'];
        $tmpDate = $dateStart->format('Y-m-d');
        $queryOutput=array();
       // while (strtotime($tmpDate) <= strtotime($dateEnd->format("Y-m-d"))) {
            $tmpDateStart_1 = clone new \DateTime($tmpDate);
            $tmpDateStart_1->setTime($startHour, 0, 0);
            $tmpDateStart_2 = clone new \DateTime($dateEnd->format('Y-m-d'));
            $tmpDateStart_2->setTime($endHour, 0, 0);
            $query = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('t')
                ->select('t')
                ->andWhere('t.date >= :startDate')
                ->andWhere('t.date <= :endDate');
            if(!is_null($filter['startHour'])){
                $query ->andWhere('t.startDate >= :startDate1')
                    ->setParameter('startDate1',$tmpDateStart_1);
            }
            if(!is_null($filter['endHour'])){
                $query ->andWhere('t.startDate <= :startDate2')
                    ->setParameter('startDate2',$tmpDateStart_2);
            }
            $query->andWhere('t.originRestaurant = :restaurant')
                ->setParameter('startDate',$tmpDate)
                ->setParameter('endDate',$dateEnd->format('Y-m-d'))
                ->setParameter('restaurant', $currentRestaurant);
            $result = $query->getQuery()
                ->getResult();
            $queryOutput= array_merge($queryOutput,$result);
           // $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));

       // }
        $CA=0;
        foreach ($queryOutput as $output) {
            $CA = $CA + $output->getTotalTTC();
        }
        return $CA;
    }

    public function getCancellationList($filter){
        $startHour=(is_null($filter['startHour']))?0:$filter['startHour'];
        $endHour= (is_null($filter['endHour']))?23:$filter['endHour'];
        $dateStart= $filter['startDate'];
        $dateEnd = $filter['endDate'];
        $cashier=$filter['cashier'];
        $invoice = $filter['InvoiceNumber'];
        $currentRestaurant=$filter['currentRestaurant'];
        //$queryOutput=array();
        $tmpDate=$dateStart->format('Y-m-d');
//        while (strtotime($tmpDate) <= strtotime($dateEnd->format("Y-m-d"))) {
            $tmpDateStart_1=clone new \DateTime($tmpDate);
            $tmpDateStart_1->setTime($startHour,0,0);
            $tmpDateStart_2=clone new \DateTime($dateEnd->format('Y-m-d'));
            $tmpDateStart_2->setTime($endHour,0,0);


            $query= $this->em->getRepository('Financial:Ticket')->createQueryBuilder('t')
                //->select('t')
                //->where('t.type =:type')
                ->andWhere('t.date >= :startDate')
                ->andWhere('t.date <= :endDate');
                if(!is_null($filter['startHour'])){
                    $query ->andWhere('t.startDate >= :startDate1')
                        ->setParameter('startDate1',$tmpDateStart_1);
                }
              if(!is_null($filter['endHour'])){
                  $query ->andWhere('t.startDate <= :startDate2')
                      ->setParameter('startDate2',$tmpDateStart_2);
              }

                $query->andWhere("t.invoiceCancelled = '1' ")
                ->andWhere('t.originRestaurant = :restaurant')
                //->setParameter('type','invoice')

                ->setParameter('startDate',$tmpDate)
                ->setParameter('endDate',$dateEnd->format('Y-m-d'))
                ->setParameter('restaurant',$currentRestaurant);

            if(!is_null($cashier)){
                $query->andWhere('t.operator = :cashier')
                    ->setParameter('cashier',$cashier->getWyndId());
                $cashierName= $cashier->getFirstName()." ".$cashier->getLastName();
            }else{
                $cashierName="Tous";
            }

            if(!is_null($invoice)){
                $query->andWhere('t.invoiceNumber = :invoice')
                    ->setParameter('invoice',$invoice);
            }

            $query->orderBy('t.startDate');
            //$query->groupBy('t.startDate','t.operator', 't.id','t.type');
            $result=$query->getQuery()
                    ->getResult();
           // $queryOutput= array_merge($queryOutput,$result);
//            $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));
//        }
        $output['report']= $this->serializeList($result,$currentRestaurant);
        $output['startDate']= $filter['startDate']->format('d-m-Y');
        $output['endDate']  = $filter['endDate']->format('d-m-Y');
        $output['startHour']  = $startHour;
        $output['endHour']  = $endHour;
        $output['cashier']  = $cashierName;
        return $output;

    }


    public function serializeList($data,$currentRestaurant){
        $list=array();
        foreach($data as $element){
            $tmpLine=array();
            $tmpLine['invoiceNumber']= $element->getInvoiceNumber();
            $tmpLine['date']= $element->getStartDate()->format('Y-m-d');
            $tmpLine['hour']= $element->getStartDate()->format('H:i:s');
            $cashier = $this->findCashier($element->getOperator(),$currentRestaurant,'name');
            $id = $this->findCashier($element->getOperator(),$currentRestaurant,'id');
            $tmpLine['cashier']= $cashier;
            $tmpLine['id']= $id;
            $tmpLine['amount']=$element->getTotalTTC();
            $cashbox=$this->em->getRepository('Financial:CashboxCount')->findOneBy(array('date' =>new \DateTime($tmpLine['date']),'cashier' =>$id));
            if($cashbox != null) {
                $tmpLine['TotalTheoric']=$cashbox->getTheoricalCa();
                $tmpLine['responsible']=$cashbox->getOwner()->getName();
                $list[] = $tmpLine;
            }
        }
        $list1= array();
        $info=array();
        $i=1;
        $j=1;
        $k=1;
        $date_anc=0;
        $cashier_anc='';
        foreach ($list as $li){
            $info['invoiceNumber']=$li['invoiceNumber'];
            $info['hour']=$li['hour'];
            $info['amount']=$li['amount'];
            $info['responsible']=$li['responsible'];
            $TotalTheoric=$li['TotalTheoric'];
            if($li['date'] != $date_anc){
                $list1[$i]['row']=1;
                $list1[$i]['date']=$li['date'];
                $list1[$i]['infos'][$i]['infos'][]=$info;
                $list1[$i]['infos'][$i]['cashier']=$li['cashier'];
                $list1[$i]['infos'][$i]['totalamount']=abs($info['amount']);
                $list1[$i]['infos'][$i]['cashbox']= abs($info['amount']*100/$TotalTheoric);
                $list1[$i]['infos'][$i]['row']=1;
                $j=1;
                $k=1;
            }  else{
                $j=$j+1;
                if($li['cashier'] == $cashier_anc){
                    $k=$k+1;
                    $list1[$i-($j-1)]['infos'][$i-($k-1)]['infos'][]=$info;
                    $list1[$i-($j-1)]['infos'][$i-($k-1)]['row']=$k;
                    $list1[$i-($j-1)]['infos'][$i-($k-1)]['totalamount']=abs($info['amount'])+abs($list1[$i-($j-1)]['infos'][$i-($k-1)]['totalamount']);
                    $list1[$i-($j-1)]['infos'][$i-($k-1)]['cashbox']=$list1[$i-($j-1)]['infos'][$i-($k-1)]['totalamount'] *100/abs($TotalTheoric);
                }else{
                    $list1[$i-($j-1)]['infos'][$i]['infos'][]=$info;
                    $list1[$i-($j-1)]['infos'][$i]['cashier']=$li['cashier'];
                    $list1[$i-($j-1)]['infos'][$i]['row']=1;
                    $list1[$i-($j-1)]['infos'][$i]['totalamount']=abs($info['amount']);
                    $list1[$i-($j-1)]['infos'][$i]['cashbox']= abs($info['amount']*100/$TotalTheoric);
                    $k=1;

                }
                $list1[$i-($j-1)]['row']=$j;
                $cashier_anc=$li['cashier'];
            }

            $date_anc=$li['date'];
            $i++;
        }
        return $list1;

    }



    public function findCashier($id,$currentRestaurant,$type){
//        $result= $this->em->getRepository('Staff:Employee')->findBy(array('wyndId'=>$id));
        $result=$this->em->getRepository("Staff:Employee")
            ->createQueryBuilder('e')->where(
                ':restaurant MEMBER OF e.eligibleRestaurants'
            )->andWhere('e.wyndId= :wyndId')
            ->setParameter('restaurant', $currentRestaurant)
            ->setParameter('wyndId', $id)
            ->getQuery()->getOneOrNullResult();
        if(is_null($result)){
           return "";
        } else{
            if($type =='id'){
                return $result->getId();
            }
            return $result->getName();
        }
    }

    public function generateExcelFile($result, $filter, Restaurant $currentRestaurant, $logoPath)
    {

        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $period=date_diff($filter['startDate'],$filter['endDate'])->format('%a')+1;
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('cancellation_report.cancellation_report'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('cancellation_report.cancellation_report');
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
        $sheet->mergeCells("A10:H10");
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
        $sheet->mergeCells("G11:H11");
        ExcelUtilities::setFont($sheet->getCell('G11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G11"), $colorOne);
        $sheet->setCellValue('G11', $filter['endDate']->format('Y-m-d'));

        // START DATE
        $sheet->mergeCells("A12:B12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('keyword.from') . ":");
        $sheet->mergeCells("C12:D12");
        ExcelUtilities::setFont($sheet->getCell('C12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C12"), $colorOne);
        $sheet->setCellValue('C12', $filter['startHour']);


        // END DATE
        $sheet->mergeCells("E12:F12");
        ExcelUtilities::setFont($sheet->getCell('E12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E12"), $colorOne);
        $sheet->setCellValue('E12', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("G12:H12");
        ExcelUtilities::setFont($sheet->getCell('G12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G12"), $colorOne);
        $sheet->setCellValue('G12', $filter['endHour']);

        //Numero facture
        $sheet->mergeCells("A13:B13");
        ExcelUtilities::setFont($sheet->getCell('A13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A13"), $colorOne);
        $sheet->setCellValue('A13', $this->translator->trans('cancellation_report.invoice_number') . ":");
        $sheet->mergeCells("C13:D13");
        ExcelUtilities::setFont($sheet->getCell('C13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C13"), $colorOne);
        $sheet->setCellValue('C13', $filter['InvoiceNumber']);

        //Equipier
        $sheet->mergeCells("E13:F13");
        ExcelUtilities::setFont($sheet->getCell('E13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E13"), $colorOne);
        $sheet->setCellValue('E13', $this->translator->trans('label.member') . ":");
        $sheet->mergeCells("G13:H13");
        ExcelUtilities::setFont($sheet->getCell('G13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G13"), $colorOne);
        $sheet->setCellValue('G13', $filter['cashier']);

       //content
        $i=15;

        //Date
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('cancellation_report.date'));
        //Cashier
        $sheet->mergeCells('C'.$i.':E'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('cancellation_report.cashier'));
         //responsible
        $sheet->mergeCells('F'.$i.':H'.$i);
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('cancellation_report.responsible'));

        //invoice number
        $sheet->mergeCells('I'.$i.':J'.$i);
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, $this->translator->trans('cancellation_report.invoice_number'));

        //amount canceled
        $sheet->mergeCells('K'.$i.':L'.$i);
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, $this->translator->trans('cancellation_report.amount_canceled'));

        //Hour
        $sheet->mergeCells('M'.$i.':N'.$i);
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, $this->translator->trans('cancellation_report.hour'));
        //perc
        $sheet->mergeCells('O'.$i.':P'.$i);
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('cancellation_report.perc'));

        //Border
        $cell = 'A';
        while ($cell != 'Q') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i=16;
        $total=0;
        foreach ($result['report'] as $line){
            if($line['row'] != 1){
                $k=$i+$line['row']-1;
            }else{
                $k=$i;
            }
            //Date
            $sheet->mergeCells('A'.$i.':B'.$k);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $line['date']);
            $n=$i;
            foreach ($line['infos'] as $li){
                if($li['row'] != 1){
                    $j=$n+$li['row']-1;
                }else{
                    $j=$n;
                }
                //Cashier
                $sheet->mergeCells('C'.$n.':E'.$j);
                ExcelUtilities::setFont($sheet->getCell('C'.$n), 10, true);
                $sheet->setCellValue('C'.$n, $li['cashier']);
                 $t=$n;
                foreach ($li['infos'] as $l){
                    //Border
                    $cell = 'A';
                    while ($cell != 'Q') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$t));
                        $cell++;
                    }

                    //responsible
                    $sheet->mergeCells('F'.$t.':H'.$t);
                    ExcelUtilities::setFont($sheet->getCell('F'.$t), 10, true);
                    $sheet->setCellValue('F'.$t,$l['responsible'] );

                    //invoice number
                    $sheet->mergeCells('I'.$t.':J'.$t);
                    ExcelUtilities::setFont($sheet->getCell('I'.$t), 10, true);
                    $sheet->setCellValue('I'.$t,$l['invoiceNumber'] );
                    ExcelUtilities::setFormat($sheet->getCell('I'.$t),\PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sheet->getStyle('I'.$t)->getNumberFormat()->applyFromArray(array('code' => \PHPExcel_Style_NumberFormat::FORMAT_NUMBER));

                    //amount canceled
                    $sheet->mergeCells('K'.$t.':L'.$t);
                    ExcelUtilities::setFont($sheet->getCell('K'.$t), 10, true);
                    $sheet->setCellValue('K'.$t,$l['amount'] );
                    $total=$total+$l['amount'];

                    //Hour
                    $sheet->mergeCells('M'.$t.':N'.$t);
                    ExcelUtilities::setFont($sheet->getCell('M'.$t), 10, true);
                    $sheet->setCellValue('M'.$t,$l['hour'] );
                    $t++;

                }

                //perc
                $sheet->mergeCells('O'.$n.':P'.$j);
                ExcelUtilities::setFont($sheet->getCell('O'.$n), 10, true);
                $sheet->setCellValue('O'.$n, number_format($li['cashbox'],'2'));

                if($li['row'] != 1){
                    $n=$n+$li['row'];
                }else{
                    $n++;
                }
            }

            if($line['row'] != 1){
                $i=$i+$line['row'];
            }else{
                $i++;
            }

        }
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('cancellation_report.total'));
        $sheet->mergeCells('C'.$i.':P'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $total);
        //Border
        $cell = 'A';
        while ($cell != 'Q') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;
        //moyenne
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('cancellation_report.moyen'));
        $sheet->mergeCells('C'.$i.':P'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $ca=$this->getCAReel($filter);
        $sheet->setCellValue('C'.$i, abs(number_format($total*100/$ca, 2)));
        //Border
        $cell = 'A';
        while ($cell != 'Q') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }



        $filename = "Rapport_annulation_".date('dmY_His').".xls";
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