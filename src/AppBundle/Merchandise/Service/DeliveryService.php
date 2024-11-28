<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:42
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\Product;
use Doctrine\ORM\EntityManager;
use Knp\Snappy\Pdf;
use Monolog\Logger;
use Symfony\Bundle\TwigBundle\TwigEngine;
use AppBundle\ToolBox\Service\CommandLauncher;

class DeliveryService
{

    private $em;
    private $productService;
    private $pdfGenerator;
    private $twig;
    private $tmpDir;
    private $productPurchasedMvmtService;
    private $logger;
    private $commandLauncher;
    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        ProductService $productService,
        TwigEngine $twigEngine,
        Pdf $pdfGenerator,
        $tmpDir,
        ProductPurchasedMvmtService $productPurchasedMvmtService,
        Logger $logger,
        CommandLauncher $commandLauncher,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->productService = $productService;
        $this->pdfGenerator = $pdfGenerator;
        $this->twig = $twigEngine;
        $this->tmpDir = $tmpDir;
        $this->productPurchasedMvmtService = $productPurchasedMvmtService;
        $this->logger = $logger;
        $this->commandLauncher = $commandLauncher;
        $this->restaurantService = $restaurantService;
    }

    public function createDelivery(Delivery $delivery,$restaurant)
    {
        try {
            $this->em->beginTransaction();
            $this->em->persist($delivery);
            $this->productPurchasedMvmtService->createMvmtEntryForDelivery($delivery,$restaurant, false);
            $delivery->getOrder()->setStatus(Order::DELIVERED);

            //increase stock qty
            foreach ($delivery->getLines() as $l) {
                if ($l->getProduct()->getPrimaryItem() != null) {
                    //secondary item qty in Usage Unit
                    $secondaryItem=$l->getProduct();
                    $secondaryUsage= $l->getQty()*$secondaryItem->getInventoryQty()*$secondaryItem->getUsageQty();
                    $this->productService->updateStock($l->getProduct()->getPrimaryItem(), $secondaryUsage, Product::EXPED_UNIT,null,true);
                } else {
                    $this->productService->updateStock($l->getProduct(), $l->getQty(), Product::EXPED_UNIT);
                }
            }
            $this->em->flush();
            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->logger->addCritical("[DELIVERY SERVICE] [createDelivery] : ".$e->getMessage());
            $this->em->rollback();

            return false;
        }
    }

    public function generateBonOrder(Delivery $delivery)
    {
        $html = $this->twig->render(
            "@Merchandise/Delivery/bt_to_print.html.twig",
            array('delivery' => $delivery)
        );


        $file_path = $this->tmpDir."/delivery_".hash('md5', date('Y/m/d H:i:s')).".pdf";

        $this->pdfGenerator->generateFromHtml($html, $file_path);

        return $file_path;
    }

    public function UpdateMFCforDelivery(Delivery $delivery)
    {
        $fiscalDate = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'date_fiscale',
                'originRestaurant' => $this->restaurantService->getCurrentRestaurant(),
            )
        )->getValue();
        if ($delivery->getDate()->format('d/m/Y') != $fiscalDate) {
            $command = 'report:marge:foodcost '.$delivery->getOriginRestaurant()->getId().' '.$delivery->getDate()->format('Y-m-d').' '.$delivery->getDate()->format(
                'Y-m-d'
            );
            $this->commandLauncher->execute($command, true, false, true);
            $this->logger->info(
                'Updating Marge FC with success for date :'.$delivery->getDate()->format('Y-m-d'),
                ['DeliveryService']
            );
        }
    }
}
