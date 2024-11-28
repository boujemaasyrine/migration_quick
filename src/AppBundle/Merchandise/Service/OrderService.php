<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:41
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use AppBundle\ToolBox\Service\CommandLauncher;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Knp\Snappy\Pdf;
use Liuggio\ExcelBundle\Factory;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class OrderService
{

    private $em;

    private $twig;

    private $poDirectory;

    private $commandLuncher;

    private $pdfGenerator;

    private $mailer;

    private $mailerUser;

    private $restaurantService;

    private $phpExcel;

    private $translator;

    private $productService;

    public function __construct(
        EntityManager $entityManager,
        TwigEngine $twigEngine,
        $poDirectory,
        CommandLauncher $commandLauncher,
        Pdf $pdfGenerator,
        \Swift_Mailer $mail,
        $mailerUser,
        RestaurantService $restaurantService,
        Factory $factory,
        Translator $translator,
        ProductService $productService
    ) {
        $this->em = $entityManager;
        $this->twig = $twigEngine;
        $this->poDirectory = $poDirectory;
        $this->commandLuncher = $commandLauncher;
        $this->pdfGenerator = $pdfGenerator;
        $this->mailer = $mail;
        $this->mailerUser = $mailerUser;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
        $this->translator = $translator;
        $this->productService = $productService;
    }

    /**
     * Cette methode crée un order avec les lignes passées en deuxième argument
     * Si l'order est un brouillon, on supprime les anciennes lignes
     * Si le passage d'ordre se fait dans la plage autorisée, son statut devient "sending"
     * sinon il sera rejeté
     *
     * @param  Order $order
     * @throws \Exception
     */
    public function createOrder(Order $order)
    {
        if ($order->getNumOrder() == null) {
            $order->setNumOrder($this->getLastOrderNum());
        }
        //OKAY YOU CAN REGISTER THE ORDER
        $order->setStatus(Order::SENDING);
        //Generating the file
        $this->generatePoXml($order);
        $this->em->persist($order);
        $this->em->flush();
    }

    /**
     * @param Order $order
     * @return string|bool true if can be cancelled otherwise the msg
     */
    public function canBeCancelled(Order $order)
    {

        if ($order->getStatus() == Order::SENDED) {
            return 'order_sended';
        }

        if ($order->getStatus() == Order::DELIVERED) {
            return 'order_delivered';
        }

        if ($order->getStatus() == Order::CANCELED) {
            return 'order_cancelled';
        }

        return true;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function cancelOrder(Order $order)
    {
        $order->setStatus(Order::CANCELED);
        $this->em->flush();

        return true;
    }

    /**
     * Cette methode enregistre un order avec les lignes passées en deuxième argument en tant que brouillon
     * Si l'order est un brouillon (existe), on supprime les anciennes lignes
     *
     * @param Order $order
     * @param Order $oldOrder
     */
    public function saveOrderAsDraft(Order $order, Order $oldOrder)
    {
        try {
            $this->em->beginTransaction();
            if ($oldOrder != null) {
                foreach ($oldOrder->getLines() as $l) {
                    $this->em->remove($l);
                    $this->em->flush();
                }

                foreach ($order->getLines() as $line) {
                    $oldOrder->addLine($line);
                }

                $oldOrder->setDateDelivery($order->getDateDelivery())
                    ->setDateOrder($order->getDateOrder())
                    ->setSupplier($order->getSupplier())
                    ->setEmployee($order->getEmployee())
                    ->setNumOrder($order->getNumOrder());


                $oldOrder->setStatus(Order::DRAFT);
                $this->em->persist($oldOrder);
            } else {
                $order->setStatus(Order::DRAFT);
                $order->setNumOrder($this->getLastOrderNum());

                $this->em->persist($order);
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
        }
    }

    public function getLastOrderNum()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $codeRes = $currentRestaurant->getCode();
        try {
            $lastOrder = $this->em
                ->getRepository("Merchandise:Order")
                ->createQueryBuilder("o")
                ->orderBy("o.id", "DESC")
                ->setMaxResults(1)->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            $lastOrder = null;
        }

        if ($lastOrder === null) {
            return intval($codeRes.'1');
        }

        return intval($codeRes.strval($lastOrder->getId() + 1));
    }

    public function generatePoXml(Order $order)
    {
        $restaurant=$order->getOriginRestaurant();
        $xml = $this->twig->render(
            "@Merchandise/Order/po_xml/po_container.xml.twig",
            array('order' => $order, 'restaurant' => $restaurant)
        );

        return file_put_contents($this->poDirectory."/PO".$order->getNumOrder().".xml", $xml);
    }

    public function cloneOrderWithoutLines(Order $oldOrder)
    {
        $order = new Order;
        $order->setDateDelivery($oldOrder->getDateDelivery());
        $order->setDateOrder($oldOrder->getDateOrder());
        $order->setEmployee($oldOrder->getEmployee());
        $order->setSupplier($oldOrder->getSupplier());
        $order->setNumOrder($oldOrder->getNumOrder());

        return $order;
    }

    /**
     * @param Order $order
     * Envoie le fichier d'un order au portail fournisseur
     * si l'envoie est effectué avec succés, on modifie le statut de la commande
     */
    public function sendOrder(Order $order)
    {
        $cmd = " order:send ".$order->getId();

        $this->commandLuncher->execute($cmd);
    }

    /**
     * Supprime le fichier déposé pour un ordre
     * retourne true si c'est effacée
     * false sinon
     *
     * @param  Order $order
     * @return bool
     */
    public function deleteOrderFile(Order $order)
    {
        //Todo to complete with how we delete the file

        return true;
    }

    public function canBeEditable(Order $order)
    {

        if ($order->getStatus() == Order::SENDED) {
            return 'order_sended';
        }

        if ($order->getStatus() == Order::DELIVERED) {
            return 'order_delivered';
        }

        if ($order->getStatus() == Order::CANCELED) {
            return 'order_canceled';
        }

        if ($order->getStatus() == Order::REJECTED) {
            return 'order_rejected';
        }

        if ($order->getStatus() == Order::MODIFIED) {
            return 'order_modified';
        }

        return true;
    }

    /**
     * @param Order $order
     * @return bool|string
     */
    public function canBeForced(Order $order)
    {
        if ($order->getStatus() != 'sended') {
            return 'only_sended_order';
        }

        $today = new \DateTime("NOW");
        $dateDiff = $today->diff($order->getCreatedAt());

        if (!(($dateDiff->days == 1 && intval(date('H')) < 11) || $dateDiff->days == 0)) {
            return 'delay_passed';
        }

        return true;
    }

    /**
     * Modifie un order en testant sur la possibilité de modification
     * retourn un tableau associatif avec une colonne 'status' = true|false
     * si c'est false on trouve la raison  dans la colonne reason
     *
     * @param  Order $oldOrder
     * @param  Order $newOrder
     * @return array
     */
    public function editOrder(Order $oldOrder, Order $newOrder, $status = null)
    {
        try {
            $this->em->beginTransaction();
            if ($oldOrder->getNumOrder() === null) {
                $newOrder->setNumOrder($this->getLastOrderNum());
            } else {
                $newOrder->setNumOrder($oldOrder->getNumOrder());
            }
            if ($status === null) {
                $newOrder->setStatus($oldOrder->getStatus());
            } else {
                $newOrder->setStatus($status);
            }

            $this->em->remove($oldOrder);
            $this->em->persist($newOrder);
            $this->em->flush();
            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->rollback();

            return false;
        }
    }

    public function getList($criteria, $order, $limit, $offset)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $data = $this->em->getRepository("Merchandise:Order")->getList(
            $restaurant,
            false,
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeList($data['data']);
    }

    public function getDeliveries($criteria, $order, $limit, $offset, $onlyList = false, $serialize = false)
    {
        $deliveries = $this->em->getRepository("Merchandise:Delivery")->getList(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        if ($onlyList) {
            if ($serialize) {
                return $this->serializeDeliveries($deliveries);
            } else {
                return $deliveries;
            }
        } else {
            return $deliveries;
        }
    }

    public function notifySupplierByModification(Order $order)
    {

        $file = $this->generateBonOrder($order);

        $mailBody = $this->twig->render(
            "@Merchandise/Order/mails/modification_mail.html.twig",
            array(
                'order' => $order,
            )
        );
        try {
            $mail = \Swift_Message::newInstance()
                ->setSubject("[QUICK] MODIFICATION D'UNE COMMANDE")
                ->setFrom(array($this->mailerUser))
                ->setTo(array($order->getSupplier()->getEmail()))
                ->addPart($mailBody, 'text/html')
                ->attach(\Swift_Attachment::fromPath($file, mime_content_type($file)));
            $this->mailer->send($mail);
        } catch (\Swift_RfcComplianceException $e) {
            return false;
        } catch (\Exception $ee) {
            return false;
        }

        return true;
    }

    public function generateBonOrder(Order $order)
    {

        $html = $this->twig->render(
            "@Merchandise/Order/bon_order.html.twig",
            array('order' => $order)
        );

        $file_path = $this->poDirectory."/order_".hash('md5', date('Y/m/d H:i:s')).".pdf";

        $this->pdfGenerator->generateFromHtml($html, $file_path);

        return $file_path;
    }

    /**
     * @param Supplier    $supplier
     * @param \DateTime   $date
     * @param \DateTime[] $excludeDates
     * @return \DateTime
     */
    public function getNextOrderDate(Supplier $supplier, \DateTime $date, $excludeDates)
    {

        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $plannings = $supplier->getPlannings()->filter(function (SupplierPlanning $item) use($currentRestaurant){
            return $item->getOriginRestaurant() === $currentRestaurant ? true: false;
        })->map(
            function (SupplierPlanning $item) {
                return $item->getOrderDay();
            }
        );
        $numDay = intval($date->format('w'));

        if ($plannings->contains($numDay)) {
            if (!in_array($date, $excludeDates)) {
                return $date;
            }
        }

        //Next Date
        $newDateTimestamp = mktime(
            0,
            0,
            0,
            intval($date->format('m')),
            intval($date->format('d')) + 1,
            intval($date->format('Y'))
        );
        $newDate = new \DateTime();
        $newDate->setTimestamp($newDateTimestamp);

        return $this->getNextOrderDate($supplier, $newDate, $excludeDates);
    }

    public function notifyByMailRejectedOrder(Order $order)
    {

        try {
            $body = $this->twig->render(
                "@Merchandise/Order/mails/rejected_order.html.twig",
                array(
                    'order' => $order,
                )
            );
            $file = $this->generateBonOrder($order);
            $mail = \Swift_Message::newInstance()
                ->setSubject("Commande rejetée")
                ->setFrom(array($this->mailerUser))
                ->setTo(
                    array(
                        $this->restaurantService->getCurrentRestaurant()->getEmail(),
                        $this->restaurantService->getCurrentRestaurant()->getManagerEmail(),
                    )
                )
                ->addPart($body, 'text/html')
                ->attach(\Swift_Attachment::fromPath($file, mime_content_type($file)));
            $this->mailer->send($mail);
        } catch (\Swift_RfcComplianceException $e) {
            return false;
        }
    }

    public function notifyManagerByUnsendedPreparedOrder(Order $order)
    {
        try {
            $body = $this->twig->render(
                "@Merchandise/Order/mails/mail_prepared_not_sended.html.twig",
                array(
                    'order' => $order,
                )
            );

            $mail = \Swift_Message::newInstance()
                ->setSubject("Commande préparée non envoyée")
                ->setFrom(array($this->mailerUser))
                ->setTo(
                    array(
                        $this->restaurantService->getCurrentRestaurant()->getEmail(),
                        $this->restaurantService->getCurrentRestaurant()->getManagerEmail(),
                    )
                )
                ->setBody($body, 'text/html');
            $this->mailer->send($mail);
        } catch (\Swift_RfcComplianceException $e) {
            return false;
        }
    }

    /**
     *
     * * *** Serializers ****
     */

    public function serializeOrder(Order $o)
    {
        $data = [];
        $data['id'] = $o->getId();
        $data['numOrder'] = $o->getNumOrder();
        $data['supplier'] = $o->getSupplier()->getName();
        $data['dateOrder'] = $o->getDateOrder()->format('d/m/Y');
        $data['dateDelivery'] = $o->getDateDelivery()->format('d/m/Y');
        $data['responsible'] = $o->getEmployee()->getFirstName()." ".$o->getEmployee()->getLastName();
        $data['status'] = $o->getStatus();
        $data['supplier_id'] = $o->getSupplier()->getId();
        foreach ($o->getLines() as $line){
            $data['lines'][]=$line->getProduct()->getExternalId();
        }

        return $data;
    }

    /**
     * @param Order[] $list
     * @return array
     */
    public function serializeList($list)
    {
        $result = [];
        foreach ($list as $o) {
            $result[] = $this->serializeOrder($o);
        }

        return $result;
    }

    /**
     * @param OrderLine[] $lines
     * @return array
     */
    public function serializeOrderLines($lines)
    {

        $data = [];

        foreach ($lines as $l) {
            $data[] = array(
                'id' => $l->getId(),
                'article' => $l->getProduct()->getName(),
                'code' => $l->getProduct()->getExternalId(),
                'unit_exped' => $l->getProduct()->getLabelUnitExped(),
                'unit_inv' => $l->getProduct()->getLabelUnitInventory(),
                'qty' => $l->getQty(),
                'price' => $l->getProduct()->getBuyingCost(),
            );
        }

        return $data;
    }

    /**
     * @param Delivery[] $list
     * @return array
     */
    public function serializeDeliveries($list)
    {
        $result = [];
        foreach ($list as $d) {
            $result[] = array(
                'id' => $d->getId(),
                'date' => $d->getDate()->format('d/m/Y'),
                'responsible' => $d->getEmployee()->getFirstName()." ".$d->getEmployee()->getLastName(),
                'order' => $this->serializeOrder($d->getOrder()),
                'valorization' => $d->getValorization(),
                'num_delivery' => $d->getDeliveryBordereau(),
            );
        }

        return $result;
    }

    public function generateExcelFile($criteria, $order, $logoPath)
    {
        $data = $this->getList($criteria, $order, null, null);
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('command.list.pending.title'));

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('command.list.pending.title');
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
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        $sheet->mergeCells("A11:B11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), "ECECEC");
        $sheet->setCellValue('A11', $this->translator->trans('command.list.table.header.num_order'));
        ExcelUtilities::setBorder($sheet->getCell('A11'));
        ExcelUtilities::setBorder($sheet->getCell('B11'));


        $sheet->mergeCells("C11:D11");
        ExcelUtilities::setFont($sheet->getCell('C11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C11"), "ECECEC");
        $sheet->setCellValue('C11', $this->translator->trans('command.list.table.header.supplier'));
        ExcelUtilities::setBorder($sheet->getCell('C11'));
        ExcelUtilities::setBorder($sheet->getCell('D11'));

        $sheet->mergeCells("E11:F11");
        ExcelUtilities::setFont($sheet->getCell('E11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E11"), "ECECEC");
        $sheet->setCellValue('E11', $this->translator->trans('command.list.table.header.date_order'));
        ExcelUtilities::setBorder($sheet->getCell('E11'));
        ExcelUtilities::setBorder($sheet->getCell('F11'));


        $sheet->mergeCells("G11:H11");
        ExcelUtilities::setFont($sheet->getCell('G11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G11"), "ECECEC");
        $sheet->setCellValue('G11', $this->translator->trans('command.list.table.header.date_delivery'));
        ExcelUtilities::setBorder($sheet->getCell('G11'));
        ExcelUtilities::setBorder($sheet->getCell('H11'));


        $sheet->mergeCells("I11:J11");
        ExcelUtilities::setFont($sheet->getCell('I11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I11"), "ECECEC");
        $sheet->setCellValue('I11', $this->translator->trans('command.list.table.header.responsible'));
        ExcelUtilities::setBorder($sheet->getCell('I11'));
        ExcelUtilities::setBorder($sheet->getCell('J11'));

        $sheet->mergeCells("K11:L11");
        ExcelUtilities::setFont($sheet->getCell('K11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K11"), "ECECEC");
        $sheet->setCellValue('K11', $this->translator->trans('command.list.table.header.status'));
        ExcelUtilities::setBorder($sheet->getCell('K11'));
        ExcelUtilities::setBorder($sheet->getCell('L11'));

        $startLine = 12;
        foreach ($data as $key => $line) {
            $sheet->mergeCells("A".$startLine.":B".$startLine);
            $sheet->setCellValue('A'.$startLine, $line['numOrder']);
            ExcelUtilities::setBorder($sheet->getCell('A'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('B'.$startLine));

            $sheet->mergeCells("C".$startLine.":D".$startLine);
            $sheet->setCellValue('C'.$startLine, $line['supplier']);
            ExcelUtilities::setBorder($sheet->getCell('C'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('D'.$startLine));

            $sheet->mergeCells("E".$startLine.":F".$startLine);
            $sheet->setCellValue('E'.$startLine, $line['dateOrder']);
            ExcelUtilities::setBorder($sheet->getCell('E'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('F'.$startLine));

            $sheet->mergeCells("G".$startLine.":H".$startLine);
            $sheet->setCellValue('G'.$startLine, $line['dateDelivery']);
            ExcelUtilities::setBorder($sheet->getCell('G'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('H'.$startLine));

            $sheet->mergeCells("I".$startLine.":J".$startLine);
            $sheet->setCellValue('I'.$startLine, $line['responsible']);
            ExcelUtilities::setBorder($sheet->getCell('I'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('J'.$startLine));

            $sheet->mergeCells("K".$startLine.":L".$startLine);
            $sheet->setCellValue('K'.$startLine, $this->translator->trans($line['status'], [], "order_status"));
            ExcelUtilities::setBorder($sheet->getCell('K'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('L'.$startLine));
            $startLine++;
        }
        $filename = "liste_des_commandes_encours".date('dmY_His').".xls";
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

    public function generateDeliveriesExcelFile($criteria, $order, $logoPath)
    {
        $data = $this->getDeliveries($criteria, $order, null, null, true, true);
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('delivery.delivery_order_title'));

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('delivery.delivery_order_title');
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
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        $sheet->mergeCells("B10:K12");

        $startDate = "--";
        $endDate = "--";
        if (!is_null($criteria['delivery_date_min']) && $criteria['delivery_date_min'] != "") {
            $startDate = $criteria['delivery_date_min'];
        }
        if (!is_null($criteria['delivery_date_max']) && $criteria['delivery_date_max'] != "") {
            $endDate = $criteria['delivery_date_max'];
        }
        $sheet->setCellValue(
            'B8',
            $this->translator->trans('keyword.from').' : '.$startDate.'  '.$this->translator->trans('keyword.to').' : '.$endDate
        );


        ExcelUtilities::setCellAlignment($sheet->getCell("B10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B10"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 10), 18, true);

        $sheet->mergeCells("A14:B14");
        ExcelUtilities::setFont($sheet->getCell('A14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A14"), "ECECEC");
        $sheet->setCellValue('A14', $this->translator->trans('filter.supplier'));


        $sheet->mergeCells("C14:D14");
        ExcelUtilities::setFont($sheet->getCell('C14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C14"), "ECECEC");
        if (!is_null($criteria['supplier']) && $criteria['supplier'] != "") {
            $sheet->setCellValue('C14', $criteria['supplier']);
        } else {
            $sheet->setCellValue('C14', $this->translator->trans('label.all'));
        }

        //table
        $sheet->mergeCells("A16:B16");
        ExcelUtilities::setFont($sheet->getCell('A16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A16"), "ECECEC");
        $sheet->setCellValue('A16', $this->translator->trans('delivery.num'));
        ExcelUtilities::setBorder($sheet->getCell('A16'));
        ExcelUtilities::setBorder($sheet->getCell('B16'));


        $sheet->mergeCells("C16:D16");
        ExcelUtilities::setFont($sheet->getCell('C16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C16"), "ECECEC");
        $sheet->setCellValue('C16', $this->translator->trans('command.table.supplier'));
        ExcelUtilities::setBorder($sheet->getCell('C16'));
        ExcelUtilities::setBorder($sheet->getCell('D16'));

        $sheet->mergeCells("E16:F16");
        ExcelUtilities::setFont($sheet->getCell('E16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E16"), "ECECEC");
        $sheet->setCellValue('E16', $this->translator->trans('command.date.order').":");
        ExcelUtilities::setBorder($sheet->getCell('E16'));
        ExcelUtilities::setBorder($sheet->getCell('F16'));


        $sheet->mergeCells("G16:H16");
        ExcelUtilities::setFont($sheet->getCell('G16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G16"), "ECECEC");
        $sheet->setCellValue('G16', $this->translator->trans('command.date.delivery'));
        ExcelUtilities::setBorder($sheet->getCell('G16'));
        ExcelUtilities::setBorder($sheet->getCell('H16'));


        $sheet->mergeCells("I16:J16");
        ExcelUtilities::setFont($sheet->getCell('I16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I16"), "ECECEC");
        $sheet->setCellValue('I16', $this->translator->trans('delivery_valorisation'));
        ExcelUtilities::setBorder($sheet->getCell('I16'));
        ExcelUtilities::setBorder($sheet->getCell('J16'));
        $sheet->getStyle('I16')->getAlignment()->setWrapText(true);

        $sheet->mergeCells("K16:L16");
        ExcelUtilities::setFont($sheet->getCell('K16'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K16"), "ECECEC");
        $sheet->setCellValue('K16', $this->translator->trans('delivery.entry.responsible'));
        ExcelUtilities::setBorder($sheet->getCell('K16'));
        ExcelUtilities::setBorder($sheet->getCell('L16'));
        $sheet->getStyle('K16')->getAlignment()->setWrapText(true);

        $startLine = 17;
        foreach ($data as $key => $line) {
            $sheet->mergeCells("A".$startLine.":B".$startLine);
            $sheet->setCellValue('A'.$startLine, $line['num_delivery']);
            ExcelUtilities::setBorder($sheet->getCell('A'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('B'.$startLine));

            $sheet->mergeCells("C".$startLine.":D".$startLine);
            $sheet->setCellValue('C'.$startLine, $line['order']['supplier']);
            ExcelUtilities::setBorder($sheet->getCell('C'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('D'.$startLine));
            $sheet->getStyle('C'.$startLine)->getAlignment()->setWrapText(true);

            $sheet->mergeCells("E".$startLine.":F".$startLine);
            $sheet->setCellValue('E'.$startLine, $line['order']['dateOrder']);
            ExcelUtilities::setBorder($sheet->getCell('E'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('F'.$startLine));

            $sheet->mergeCells("G".$startLine.":H".$startLine);
            $sheet->setCellValue('G'.$startLine, $line['order']['dateDelivery']);
            ExcelUtilities::setBorder($sheet->getCell('G'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('H'.$startLine));

            $sheet->mergeCells("I".$startLine.":J".$startLine);
            $sheet->setCellValue('I'.$startLine, $line['valorization']);
            ExcelUtilities::setBorder($sheet->getCell('I'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('J'.$startLine));

            $sheet->mergeCells("K".$startLine.":L".$startLine);
            $sheet->setCellValue('K'.$startLine, $line['responsible']);
            ExcelUtilities::setBorder($sheet->getCell('K'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('L'.$startLine));
            $startLine++;
        }
        $filename = "liste_des_livraisons".date('dmY_His').".xls";
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

    public function generateBonOrderExcelFile(Order $order, $logoPath)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $leftAlignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_LEFT;
        $rightAlignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('command_reciepe'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('command_reciepe');
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
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //HEADER ZONE

        //Supplier
        $sheet->mergeCells("A10:C10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue('A10', $this->translator->trans('command.details.supplier').":");
        $sheet->mergeCells("D10:F10");
        ExcelUtilities::setFont($sheet->getCell('D10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D10"), $colorOne);
        $sheet->setCellValue('D10', strtoupper($order->getSupplier()->getName()));


        //Num order
        $sheet->mergeCells("A11:C11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('command.details.num_order').":");
        $sheet->mergeCells("D11:F11");
        ExcelUtilities::setFont($sheet->getCell('D11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D11"), $colorOne);
        $sheet->setCellValue('D11', strval($order->getNumOrder()));
        ExcelUtilities::setCellAlignment($sheet->getCell("D11"), $leftAlignmentH);



        //Date order
        $sheet->mergeCells("A12:C12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('command.details.date_order').":");
        $sheet->mergeCells("D12:F12");
        ExcelUtilities::setFont($sheet->getCell('D12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D12"), $colorOne);
        $sheet->setCellValue('D12', $order->getDateOrder()->format('d/m/Y'));


        //Date delivery
        $sheet->mergeCells("H12:I12");
        ExcelUtilities::setFont($sheet->getCell('H12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H12"), $colorOne);
        $sheet->setCellValue('H12', $this->translator->trans('command.details.date_delivery').":");
        $sheet->mergeCells("J12:K12");
        ExcelUtilities::setFont($sheet->getCell('J12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J12"), $colorOne);
        $sheet->setCellValue('J12', $order->getDateDelivery()->format('d/m/Y'));


        //responsible
        $sheet->mergeCells("A13:C13");
        ExcelUtilities::setFont($sheet->getCell('A13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A13"), $colorOne);
        $sheet->setCellValue('A13', $this->translator->trans('command.details.responsible').":");
        $sheet->mergeCells("D13:F13");
        ExcelUtilities::setFont($sheet->getCell('D13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D13"), $colorOne);
        $sheet->setCellValue('D13', $order->getEmployee()->getFirstName());


        //Date delivery
        $sheet->mergeCells("H13:I13");
        ExcelUtilities::setFont($sheet->getCell('H13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H13"), $colorOne);
        $sheet->setCellValue('H13', $this->translator->trans('command.list.table.header.status').":");
        $sheet->mergeCells("J13:K13");
        ExcelUtilities::setFont($sheet->getCell('J13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J13"), $colorOne);
        $sheet->setCellValue('J13',$this->translator->trans($order->getStatus(), [], 'order_status'));

        //Table header
        $i = 16;
        //Code
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('command.details.lines.code'));
        //Item
        $sheet->mergeCells('B'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
        $sheet->setCellValue('B'.$i, $this->translator->trans('command.details.lines.article'));
        //Stock qty
        $sheet->mergeCells('E'.$i.':F'.$i);
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('command.new.lines.stock_qty'));
        //Unit report
        $sheet->mergeCells('G'.$i.':I'.$i);
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('units_rapport'));

        //Ordered qty
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, $this->translator->trans('command.details.lines.ordred_qty'));

        //Unit price
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, $this->translator->trans('unit_price')." (€)");

        //Valorization
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, $this->translator->trans('valorization'));

        $total=0;
        //Border
        $cell = 'A';
        while ($cell != 'M') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        //Content
        $i++;
        foreach ($order->getLines() as $line)
        {

            if($line->getQty() != '0') {
                $total++;
                //Code
                ExcelUtilities::setFont($sheet->getCell('A' . $i), 10, true);
                $sheet->setCellValue('A' . $i, $line->getProduct()->getExternalId());
                //Item
                $sheet->mergeCells('B' . $i . ':D' . $i);
                ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
                $sheet->setCellValue('B' . $i, $line->getProduct()->getName());
                //Stock qty
                $qty = null;
                $qtyData = $this->productService->getRTStockQty($line->getProduct());
                $division = $qtyData['qty'] / $line->getProduct()->getInventoryQty();
                $qty = number_format($division, 2, ',', '') . " " . $this->translator->trans($line->getProduct()->getLabelUnitExped());
                if ($qtyData['type'] == 'real') {
                    $qty .= " (R)";
                } else {
                    $qty .= " (T)";
                }
                $sheet->mergeCells('E' . $i . ':F' . $i);
                ExcelUtilities::setFont($sheet->getCell('E' . $i), 10, true);
                $sheet->setCellValue('E' . $i, $qty);

                //Unit report
                $unitReport = "1 " . $this->translator->trans($line->getProduct()->getLabelUnitExped()) . " = " . $line->getProduct()->getInventoryQty() . " " . $this->translator->trans($line->getProduct()->getLabelUnitInventory()) . "\n" .
                    "1 " . $this->translator->trans($line->getProduct()->getLabelUnitExped()) . " = " . $line->getProduct()->getUsageQty() . " " . $this->translator->trans($line->getProduct()->getLabelUnitUsage());

                $sheet->mergeCells('G' . $i . ':I' . $i);
                ExcelUtilities::setFont($sheet->getCell('G' . $i), 10, true);
                $sheet->setCellValue('G' . $i, $unitReport);
                $sheet->getStyle('G' . $i)->getAlignment()->setWrapText(true);
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G" . $i), $alignmentV);
                $sheet->getRowDimension($i)->setRowHeight(30);

                //Ordered qty
                $orderedQty = $line->getQty();
                ExcelUtilities::setFont($sheet->getCell('J' . $i), 10, true);
                $sheet->setCellValue('J' . $i, $orderedQty);
                $sheet->getStyle('J' . $i)->getNumberFormat()->applyFromArray(array('code' => \PHPExcel_Style_NumberFormat::FORMAT_NUMBER));

                //Unit price
                ExcelUtilities::setFont($sheet->getCell('K' . $i), 10, true);
                $sheet->setCellValue('K' . $i, number_format($line->getProduct()->getBuyingCost(), 2));
                ExcelUtilities::setCellAlignment($sheet->getCell("K" . $i), $rightAlignmentH);


                //Valorization
                ExcelUtilities::setFont($sheet->getCell('L' . $i), 10, true);
                $sheet->setCellValue('L' . $i, str_replace(',', '.', $line->getValorization()));
                //line border
                $cell = 'A';
                while ($cell != 'M') {
                    ExcelUtilities::setBorder($sheet->getCell($cell . $i));
                    $cell++;
                }
                $i++;
            }
        }
        // total valorization
        $i++;
        $sheet->mergeCells('I'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        $sheet->setCellValue('I'.$i, $this->translator->trans("order_valorization")." (€)");

        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        $sheet->setCellValue('L'.$i, number_format($order->getTotal(), 2, '.', ''));

        // total items commandé
        $i++;
        $sheet->mergeCells('I'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        $sheet->setCellValue('I'.$i, $this->translator->trans("total_item"));

        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        $sheet->setCellValue('L'.$i, $total);

        $filename = "order_".date('Y_m_d_H_i_s').".xls";
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
