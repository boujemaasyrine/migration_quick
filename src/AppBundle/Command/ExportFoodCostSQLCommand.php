<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 29/08/2018
 * Time: 15:38
 */

namespace AppBundle\Command;


use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ExportFoodCostSQLCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var Factory $phpExcel
     */
    private $phpExcel;


    protected function configure()
    {
        $this->setName("saas:foodcost:sql");
        $this->addArgument('startDate', InputArgument::OPTIONAL);
        $this->addArgument('endDate', InputArgument::OPTIONAL);
        $this->addArgument('progressionId', InputArgument::OPTIONAL);
        $this->addArgument('filename', InputArgument::OPTIONAL);
        $this->addArgument(
            'restaurants',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            "the id of restaurants to launch calculation on"
        );
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize(
            $input,
            $output
        );

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->logger = $this->getContainer()->get('logger');

        $this->phpExcel = $this->getContainer()->get('phpexcel');


    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Saas FoodCost sql started');

        $this->logger->addInfo(
            'Sass FoodCost sql started',
            ['saas:foodcost:sql']
        );

        if ($input->hasArgument('startDate')
            && $input->hasArgument(
                'endDate'
            )
        ) {
            $startDate = $input->getArgument("startDate");

            $endDate = $input->getArgument("endDate");
        }

        if ($input->hasArgument('progressionId')) {
            $progressBarId = $input->getArgument('progressionId');

            $progression = $this->em->getRepository(ImportProgression::class)
                ->find($progressBarId);

            if ($progression === null) {

                $output->writeln(
                    'alert no progession found with the id '.$progressBarId
                );

                $this->logger->addAlert(
                    'no progression found with the id '.$progressBarId
                );

                return;

            }

        }


        if ($input->hasArgument('restaurants')) {

            $restaurants = $input->getArgument('restaurants');
        } else {
            $openedRestaurants = $this->em->getRepository(Restaurant::class)
                ->getOpenedRestaurants();

            $restaurants = array_map("current", $openedRestaurants);

            $this->em->clear($openedRestaurants);
        }


        $sql
            = " SELECT 
  EXTRACT(YEAR FROM t.date) AS Year,
  EXTRACT(MONTH FROM t.date) AS Month,
  r.code AS restaurantID,
  PS.code_plu AS ProductID,
  P.name AS ProductName,
  COALESCE(SUM(tl.qty),0) AS Quantity,
 COALESCE(sum(tl.totalttc),0) AS SalesTTC,
  COALESCE(sum(tl.totalht),0) AS SalesHT,
  COALESCE(SUM(tl.revenue_price),0) AS totalRevenuePrice,
  FinancialRevenue.CaNetHT AS CaNetHT,
  100*((COALESCE(SUM(tl.revenue_price),0)+ COALESCE(Loss.Pertes_connues,0))/FinancialRevenue.CaNetHT) AS FoodCost,
  COALESCE(Loss.Pertes_connues,0) AS PertesConnues
  FROM 
  restaurant r
  LEFT JOIN product P ON P.origin_restaurant_id=r.id
  INNER JOIN product_sold PS ON PS.id=P.id  
  LEFT JOIN ticket t ON r.id =t.origin_restaurant_id
  LEFT JOIN ticket_line tl ON t.id=tl.ticket_id AND tl.plu=PS.code_plu and tl.origin_restaurant_id=t.origin_restaurant_id and t.date=tl.date
  LEFT JOIN ( 
  SELECT   EXTRACT(YEAR FROM fr.date) AS FYear,
           EXTRACT(MONTH FROM fr.date) AS FMonth,
           fr.origin_restaurant_id AS Frestaurant,
           SUM(net_ht) AS CaNetHT
           FROM financial_revenue fr
           GROUP BY FYear,FMonth,Frestaurant) AS FinancialRevenue     
  ON FinancialRevenue.Frestaurant=t.origin_restaurant_id  AND FinancialRevenue.FYear= EXTRACT(YEAR FROM t.date) AND FinancialRevenue.FMonth= EXTRACT(MONTH FROM t.date) 
  LEFT JOIN (
			   SELECT 
               COALESCE(SUM(LL.total_revenue_price),0) AS Pertes_connues, 
			   EXTRACT(MONTH FROM LS.entry) AS LMonth,
	           EXTRACT(YEAR FROM LS.entry) AS LYear,
			   LS.origin_restaurant_id AS Lrestaurant,
			   PS.code_plu AS LProduct
			   FROM loss_sheet LS 
			   LEFT JOIN loss_line LL ON LS.id = LL.loss_sheet_id AND LS.type = 'finalProduct'
			   LEFT JOIN public.product_sold PS ON PS.id = LL.product_id
               INNER JOIN public.product_purchased PPS ON PPS.id = PS.product_purchased_id
               INNER JOIN public.product P ON P.id = PS.id			   
	      GROUP BY LMonth,LYear,Lrestaurant,LProduct) AS Loss	
   ON Loss.Lrestaurant=t.origin_restaurant_id AND Loss.LYear=FinancialRevenue.FYear AND Loss.LMonth=FinancialRevenue.FMonth AND PS.code_plu=Loss.LProduct	


  WHERE t.status NOT IN (-1, 5) AND t.num>=0 AND t.origin_restaurant_id=:restaurant_id AND r.id=:restaurant_id";


        if (isset($startDate) && isset($endDate) && !is_null($startDate)
            && !is_null($endDate)
        ) {

            $sql .= " and t.date>=:startDate and t.date<=:endDate";
        }

        $sql .= " group by restaurantID, Month,Year,ProductID,ProductName,FinancialRevenue.CaNetHT,Loss.Pertes_connues order by restaurantID, Year,Month,ProductID,ProductName;";


        $tmpDir = $this->getContainer()->getParameter('kernel.root_dir')
            ."/../data/tmp/foodCostSupervision/";

        if ($input->hasArgument('filename')) {
            $filename = $tmpDir.$input->getArgument('filename');
        } else {
            $filename = $tmpDir.'reportFoodCost'.date('Y_m_d_H_i_s').'.xls';
        }

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();

        $phpExcelObject->setActiveSheetIndex(0);

        $sheet = $phpExcelObject->getActiveSheet();

        $sheet->setTitle('rapport FoodCost');
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $startCell = 0;

        $startLine = 1;

        $topHeaderColor = "CA9E67";

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;

        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;


        //headers

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "year"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "month"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "restaurantID"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "productID"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "productname"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );


        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "quantity"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "salesTTC"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "salesht"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );


        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "Totalrevenueprice"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );


        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "CanetHT"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "foodcost"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell).$startLine,
            "pertesconnues"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentH
        );

        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell).$startLine),
            $alignmentV
        );


        //Content

        $startCell = 0;

        $startLine++;

        /**
         * the id of the restaurant
         *
         * @var int $restaurant
         */
        foreach ($restaurants as $restaurant) {

            $this->logger->addDebug(
                'start of generation for restaurant ID '.$restaurant,
                ["supervision:report:foodCost"]
            );

            $output->writeln(
                'start of generation for restaurant ID '.$restaurant
            );

            $stm = $this->em->getConnection()->prepare($sql);

            if (isset($startDate) && isset($endDate) && !is_null($startDate)
                && !is_null($endDate)
            ) {

                $stm->bindParam('startDate', $startDate);
                $stm->bindParam('endDate', $endDate);
            }
            $stm->bindParam('restaurant_id', $restaurant);

            $stm->execute();

            $results = $stm->fetchAll();


            foreach ($results as $result) {

                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    $result["year"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    $result["month"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    $result["restaurantid"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    $result["productid"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    $result["productname"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    $result["quantity"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    number_format($result["salesttc"], 2, '.', '')
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                $sheet->getStyle($this->getNameFromNumber($startCell).$startLine)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    number_format($result["salesht"],2, '.', '')
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                $sheet->getStyle($this->getNameFromNumber($startCell).$startLine)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    number_format($result["totalrevenueprice"],2, '.', '')
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                $sheet->getStyle($this->getNameFromNumber($startCell).$startLine)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    number_format($result["canetht"],2, '.', '')
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                $sheet->getStyle($this->getNameFromNumber($startCell).$startLine)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    number_format($result["foodcost"],2, '.', '')
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                $sheet->getStyle($this->getNameFromNumber($startCell).$startLine)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );
                $startCell++;
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell).$startLine,
                    number_format($result["pertesconnues"],2, '.', '')
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell).$startLine
                    )
                );

                $startCell = 0;
                $startLine++;

            }


            if (isset($progression) && $progression !== null) {
                $progression->incrementProgression();
                $this->em->flush();
            }

            $output->writeln(
                'finish of generation for restaurant ID '.$restaurant
            );

            $this->logger->addDebug(
                'finish of generation for restaurant ID '.$restaurant,
                ["supervision:report:foodCost"]
            );


        }

        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');

        $writer->save($filename);


        if (isset($progression) && $progression !== null) {
            $progression->setStatus("finish");
            $progression->setEndDateTime(new \DateTime());
            $this->em->flush();

        }


    }

    public function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2 - 1).$letter;
        } else {
            return $letter;
        }
    }

}