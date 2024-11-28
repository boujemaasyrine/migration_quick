<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:52
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\ToolBox\Service\CommandLauncher;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Knp\Snappy\Pdf;
use Liuggio\ExcelBundle\Factory;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class TransferService
{

    private $em;
    private $quick;
    private $productService;
    private $mailer;
    private $twig;
    private $pdfGenerator;
    private $tmpDir;
    private $productPurchasedMvmtService;
    private $mailerUser;
    private $translator;
    private $phpExcel;
    private $commandLauncher;
    private $logger;


    public function __construct(
        EntityManager $entityManager,
        ProductService $productService,
        \Swift_Mailer $mailer,
        $quick,
        TwigEngine $twigEngine,
        Pdf $pdfGenerator,
        $tmpDir,
        ProductPurchasedMvmtService $productPurchasedMvmtService,
        $mailerUser,
        Translator $translator,
        Factory $factory,
        CommandLauncher $commandLauncher,
        Logger $logger
    )
    {
        $this->em = $entityManager;
        $this->quick = $quick;
        $this->productService = $productService;
        $this->mailer = $mailer;
        $this->pdfGenerator = $pdfGenerator;
        $this->twig = $twigEngine;
        $this->tmpDir = $tmpDir;
        $this->productPurchasedMvmtService = $productPurchasedMvmtService;
        $this->mailerUser = $mailerUser;
        $this->translator = $translator;
        $this->phpExcel = $factory;
        $this->commandLauncher = $commandLauncher;
        $this->logger = $logger;
    }

    public function createTransferIn(Transfer $transfer, $restaurant)
    {

        try {
            $this->em->beginTransaction();
            $this->em->persist($transfer);
            $this->productPurchasedMvmtService->createMvmtEntryForTransfer($transfer, $restaurant, false);
            foreach ($transfer->getLines() as $l) {
                if ($l->getProduct()->getPrimaryItem() != null) {
                    $this->productService->updateStock($l->getProduct()->getPrimaryItem(), $l->getTotal());
                }
                $this->productService->updateStock($l->getProduct(), $l->getTotal());
            }

            $this->em->flush();
            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->rollback();

            return false;
        }
    }

    public function createTransferOut(Transfer $transfer, $restaurant)
    {
        try {
            $this->em->beginTransaction();
            $this->em->persist($transfer);
            $this->productPurchasedMvmtService->createMvmtEntryForTransfer($transfer, $restaurant, false);
            $numQuick = $this->quick . date("ymd") . $transfer->getId();
            $transfer->setNumTransfer($numQuick);
            $this->em->persist($transfer);
            foreach ($transfer->getLines() as $l) {
                if ($l->getProduct()->getPrimaryItem() != null) {
                    $this->productService->updateStock($l->getProduct()->getPrimaryItem(), (-1) * $l->getTotal());
                }
                $this->productService->updateStock($l->getProduct(), (-1) * $l->getTotal());
            }

            $this->em->flush();
            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->rollback();

            return false;
        }
    }

    public function notifyRestaurant(Transfer $transfer, Restaurant $currentRestaurant)
    {
        try {
            $file = $this->generateBon($transfer);
            $local = !is_null($transfer->getRestaurant()->getLang()) ? strtolower($transfer->getRestaurant()->getLang()) : 'fr';

            if ($transfer->getType() == Transfer::TRANSFER_IN) {
                $objet = $currentRestaurant->getCode() . '-' . $currentRestaurant->getName() . '-' . $this->translator->trans("transfer.title") . ' IN';
                $mailBody = $this->twig->render(
                    "@Merchandise/Transfer/mails/transfer_in.html.twig",
                    array(
                        'transfer' => $transfer,
                        'local' => $local
                    )
                );
            } else {
                $objet = $currentRestaurant->getCode() . '-' . $currentRestaurant->getName() . '-' . $this->translator->trans("transfer.title") . ' OUT';
                $mailBody = $this->twig->render(
                    "@Merchandise/Transfer/mails/transfer_out.html.twig",
                    array(
                        'transfer' => $transfer,
                        'local' => $local
                    )
                );
            }
            $mail = \Swift_Message::newInstance()
                ->setSubject($objet)
                ->setFrom(array($this->mailerUser))
                ->setTo(array($transfer->getRestaurant()->getEmail()))
                ->addPart($mailBody, 'text/html')
                ->attach(\Swift_Attachment::fromPath($file, mime_content_type($file)));

            $this->mailer->send($mail);
        } catch (\Exception $e) {
            return false;
        }
        $transfer->setMailSent(true);
        $this->em->flush();

        return true;
    }

    /**
     * @param Transfer[] $list
     * @return array
     */
    public function serializeTransferList($list)
    {

        $data = [];

        foreach ($list as $l) {
            $data[] = array(
                'id' => $l->getId(),
                'restaurant' => $l->getRestaurant()->getName(),
                'date' => $l->getDateTransfer()->format('d/m/Y'),
                'responsible' => $l->getEmployee()->getFirstName() . " " . $l->getEmployee()->getLastName(),
                'type' => $l->getType(),
                'num' => $l->getNumTransfer(),
                'val' => $l->getValorization(),
            );
        }

        return $data;
    }

    public function getList($currentRestaurant, $criteria, $limit, $offset, $order)
    {

        $data = $this->em->getRepository(Transfer::class)->getList(
            $currentRestaurant,
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeTransferList($data['list']);
    }

    public function generateBon(Transfer $transfer)
    {
        $html = $this->twig->render(
            "@Merchandise/Transfer/transfer_print.html.twig",
            array('transfer' => $transfer)
        );

        $file_path = $this->tmpDir . "/transfer_" . hash('md5', date('Y/m/d H:i:s')) . ".pdf";

        $this->pdfGenerator->generateFromHtml($html, $file_path);

        return $file_path;
    }

    /**
     * @param Transfer $transfer
     * @return array  $result
     */
    public function deleteTransfer($transfer)
    {
        $sourceIds = [];
        foreach ($transfer->getLines() as $line) {
            $sourceIds[] = $line->getId();
        }
        $movements = $this->em->getRepository('Merchandise:ProductPurchasedMvmt')->findBy(
            [
                'type' => [
                    ProductPurchasedMvmt::TRANSFER_IN_TYPE,
                    ProductPurchasedMvmt::TRANSFER_OUT_TYPE,
                ],
                'sourceId' => $sourceIds,
            ]
        );

        try {
            $transfer->setDeleted(true);
            $transfer->setSynchronized(false);

            foreach ($movements as $movement) {
                $movement->setDeleted(true);
                $movement->setSynchronized(false);
            }
            $this->em->flush();
            $result['deleted'] = true;
        } catch (\Exception $e) {
            $result['deleted'] = false;
            $result['errors'] = $e->getMessage();
        }

        return $result;
    }

    public function notifyDeleteTransferRestaurant(Transfer $transfer)
    {
        try {
            $mailBody = $this->twig->render(
                "@Merchandise/Transfer/mails/delete_transfer.html.twig",
                array(
                    'transfer' => $transfer,
                )
            );

            $mail = \Swift_Message::newInstance()
                ->setSubject($this->translator->trans('transfers.delete.mail.subject'))
                ->setFrom(array($this->mailerUser))
                ->setTo(array($transfer->getRestaurant()->getEmail()))
                ->addPart($mailBody, 'text/html');

            $this->mailer->send($mail);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function genreateExcelFile(Restaurant $currentRestaurant, $criteria, $orderBy, $logoPath)
    {

        $data = $this->getList($currentRestaurant, $criteria, null, null, $orderBy);
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        //TODO translation
        $sheet->setTitle("Liste des transferts");

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $sheet->mergeCells("B5:K8");
        //TODO translation
        $content = 'Liste des transferts';
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

        $sheet->mergeCells("B10:K12");

        $startDate = "--";
        $endDate = "--";
        if (!is_null($criteria['date_transfer_inf']) && $criteria['date_transfer_inf'] != "") {
            $startDate = $criteria['date_transfer_inf'];
        }
        if (!is_null($criteria['date_transfer_sup']) && $criteria['date_transfer_sup'] != "") {
            $endDate = $criteria['date_transfer_sup'];
        }
        $sheet->setCellValue(
            'B10',
            $this->translator->trans('keyword.from') . ' : ' . $startDate . '  ' . $this->translator->trans('keyword.to') . ' : ' . $endDate
        );


        ExcelUtilities::setCellAlignment($sheet->getCell("B10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B10"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 10), 18, true);

        $sheet->mergeCells("A14:B14");
        ExcelUtilities::setFont($sheet->getCell('A14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A14"), "ECECEC");
        $sheet->setCellValue('A14', $this->translator->trans('transfer.restaurant'));


        $sheet->mergeCells("C14:D14");
        ExcelUtilities::setFont($sheet->getCell('C14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C14"), "ECECEC");
        if (!is_null($criteria['restaurant']) && $criteria['restaurant'] != "") {
            $restaurantId = $criteria['restaurant'];
            $restaurant = $this->em->getRepository('Merchandise:Restaurant')->find($restaurantId)->getName();
        } else {
            $restaurant = $this->translator->trans('label.all');
        }
        $sheet->setCellValue('C14', $restaurant);


        $sheet->mergeCells("A15:B15");
        ExcelUtilities::setFont($sheet->getCell('A15'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A15"), "ECECEC");
        $sheet->setCellValue('A15', $this->translator->trans('transfer_type'));

        $sheet->mergeCells("C15:D15");
        ExcelUtilities::setFont($sheet->getCell('C15'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C15"), "ECECEC");
        if (!is_null($criteria['type']) && $criteria['type'] != "") {
            if ($criteria['type'] == "transfer_in") {
                $transfer = $this->translator->trans('transfer_in');
            } else {
                $transfer = $this->translator->trans('transfer_out');
            }
        } else {
            $transfer = $this->translator->trans('label.all');
        }
        $sheet->setCellValue('C15', $transfer);

        //table
        $sheet->mergeCells("A17:B17");
        ExcelUtilities::setFont($sheet->getCell('A17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A17"), "ECECEC");
        $sheet->setCellValue('A17', $this->translator->trans('transfer.transfer_num'));
        ExcelUtilities::setBorder($sheet->getCell('A17'));
        ExcelUtilities::setBorder($sheet->getCell('B17'));


        $sheet->mergeCells("C17:D17");
        ExcelUtilities::setFont($sheet->getCell('C17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C17"), "ECECEC");
        $sheet->setCellValue('C17', $this->translator->trans('transfer_type'));
        ExcelUtilities::setBorder($sheet->getCell('C17'));
        ExcelUtilities::setBorder($sheet->getCell('D17'));

        $sheet->mergeCells("E17:F17");
        ExcelUtilities::setFont($sheet->getCell('E17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E17"), "ECECEC");
        $sheet->setCellValue('E17', $this->translator->trans('transfer.restaurant') . ":");
        ExcelUtilities::setBorder($sheet->getCell('E17'));
        ExcelUtilities::setBorder($sheet->getCell('F17'));


        $sheet->mergeCells("G17:H17");
        ExcelUtilities::setFont($sheet->getCell('G17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G17"), "ECECEC");
        $sheet->setCellValue('G17', $this->translator->trans('transfer.transfer_date'));
        ExcelUtilities::setBorder($sheet->getCell('G17'));
        ExcelUtilities::setBorder($sheet->getCell('H17'));


        $sheet->mergeCells("I17:J17");
        ExcelUtilities::setFont($sheet->getCell('I17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I17"), "ECECEC");
        $sheet->setCellValue('I17', $this->translator->trans('valorization'));
        ExcelUtilities::setBorder($sheet->getCell('I17'));
        ExcelUtilities::setBorder($sheet->getCell('J17'));


        $startLine = 18;
        foreach ($data as $key => $line) {
            $sheet->mergeCells("A" . $startLine . ":B" . $startLine);
            $sheet->setCellValue('A' . $startLine, $line['num']);
            ExcelUtilities::setBorder($sheet->getCell('A' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('B' . $startLine));
            ExcelUtilities::setFormat($sheet->getCell('A' . $startLine), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('A' . $startLine)->getNumberFormat()->applyFromArray(array('code' => \PHPExcel_Style_NumberFormat::FORMAT_NUMBER));

            $sheet->mergeCells("C" . $startLine . ":D" . $startLine);
            $sheet->setCellValue('C' . $startLine, $line['type']);
            ExcelUtilities::setBorder($sheet->getCell('C' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('D' . $startLine));
            $sheet->getStyle('C' . $startLine)->getAlignment()->setWrapText(true);

            $sheet->mergeCells("E" . $startLine . ":F" . $startLine);
            $sheet->setCellValue('E' . $startLine, $line['restaurant']);
            ExcelUtilities::setBorder($sheet->getCell('E' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('F' . $startLine));

            $sheet->mergeCells("G" . $startLine . ":H" . $startLine);
            $date = \DateTime::createFromFormat('d/m/Y',$line['date']);
            $sheet->setCellValue('G' . $startLine,floor(\PHPExcel_Shared_Date::PHPToExcel($date)) );
            $sheet->getStyle('G' . $startLine)->getNumberFormat()
                ->setFormatCode('dd-mm-yyyy');
            ExcelUtilities::setBorder($sheet->getCell('G' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('H' . $startLine));

            $sheet->mergeCells("I" . $startLine . ":J" . $startLine);
            $sheet->setCellValue('I' . $startLine, $line['val']);
            ExcelUtilities::setBorder($sheet->getCell('I' . $startLine));
            ExcelUtilities::setBorder($sheet->getCell('J' . $startLine));


            $startLine++;
        }
        $filename = "liste_des_transferts" . date('dmY_His') . ".xls";
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

    public function UpdateMFCforTransfer(Transfer $transfer)
    {
        $fiscalDate = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'date_fiscale',
            )
        )->getValue();
        if ($transfer->getDateTransfer()->format('d/m/Y') != $fiscalDate) {
            $command = 'report:marge:foodcost ' . $transfer->getOriginRestaurant()->getId() . ' ' . $transfer->getDateTransfer()->format(
                    'Y-m-d'
                ) . ' ' . $transfer->getDateTransfer()->format('Y-m-d');
            $this->commandLauncher->execute($command, true, false, true);
            $this->logger->info(
                'Updating Marge FC with success for date :' . $transfer->getDateTransfer()->format('Y-m-d'),
                ['TransferService']
            );
        }
    }
}
