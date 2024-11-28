<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 14/05/2016
 * Time: 08:48
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryLine;
use AppBundle\Merchandise\Entity\DeliveryLineTmp;
use AppBundle\Merchandise\Entity\DeliveryTmp;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\ToolBox\Utils\Utilities;
use AppBundle\ToolBox\Utils\XML2Array;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;

class DeliveryIntegrationService
{

    /**
     * @var EntityManager
     */
    private $em;

    private $tmp;

    /**
     * @var Logger
     */
    private $logger;

    private $ftpHost;
    private $ftpPort;
    private $ftpUser;
    private $ftpPw;

    public function __construct(EntityManager $em, Logger $logger, $tmp, $ftpHost, $ftpPort, $ftpUser, $ftpPw)
    {
        $this->em = $em;
        $this->tmp = $tmp;
        $this->ftpHost = $ftpHost;
        $this->ftpPort = $ftpPort;
        $this->ftpUser = $ftpUser;
        $this->ftpPw = $ftpPw;
        $this->logger = $logger;
    }

    /**
     * @param Order $order
     * @return string|bool
     */
    public function checkDeliveryTicketAvailabiltiyForAnOrder(Order $order)
    {
        $this->logger->info("checkDeliveryTicketAvailabiltiyForAnOrder ".$order->getNumOrder());
        $found = false;

        //create filename to search for from the order
        $file = $this->constructDeliveryFilenameFroOrder($order);

        //Connect to the ftp
        $t0 = time();
        $conId = ftp_connect($this->ftpHost, $this->ftpPort);
        $this->logger->info("Result off connection ".$conId." time to connect => ".(time() - $t0));
        if (!$conId) {
        } else {//Connexion établie
            $login = ftp_login($conId, $this->ftpUser, $this->ftpPw);
            if (!$login) {
            } else {//Connexion reussite
                //Turning passive mode
                ftp_pasv($conId, true);

                //test if a file exist
                $existingFiles = ftp_nlist($conId, '.');
                if (in_array($file, $existingFiles)) {
                    $found = true;
                } else {
                    $found = false;
                }
                // Fermeture de la connexion
                ftp_close($conId);
            }//End Cnx reusssite
        }

        //return false if don't exist, filename if exist
        if ($found) {
            return $file;
        } else {
            return false;
        }
    }

    /**
     * @param DeliveryTmp $deliveryTmp
     * @return Delivery
     */
    public function convertDeliveryTmpToDelivery(DeliveryTmp $deliveryTmp)
    {
        $delivery = new Delivery();
        $delivery->setValorization($deliveryTmp->getValorization())
            ->setDeliveryBordereau($deliveryTmp->getDeliveryBordereau())
            ->setDate($deliveryTmp->getDate())
            ->setEmployee($deliveryTmp->getEmployee())
            ->setOrder($deliveryTmp->getOrder());

        foreach ($deliveryTmp->getLines() as $l) {
            $deliveryLine = new DeliveryLine();
            $deliveryLine->setValorization($l->getValorization())
                ->setProduct($l->getProduct())
                ->setQty($l->getQty());

            $delivery->addLine(clone $deliveryLine);
        }

        return $delivery;
    }

    /**
     * @param string $xml
     * @param Order $order
     * @return DeliveryTmp
     */
    public function createDeliveryTmpFromXml(Order $order, $xml)
    {

        $data = XML2Array::createArray($xml);

        $deliveryTmp = new DeliveryTmp();
        $deliveryTmp->setOrder($order)
            ->setEmployee($order->getEmployee())
            ->setDeliveryBordereau($data['Interchange']['Header']['RFF']['DeliveryNoteNumber'])
            ->setDate($order->getDateDelivery());
        $val = 0;
        if (isset($data['Interchange']) && is_array($data['Interchange'])) {
            if (isset($data['Interchange']['SupportDetails']) && is_array($data['Interchange']['SupportDetails'])) {
                foreach ($data['Interchange']['SupportDetails'] as $supportDetail) {
                    if (isset($supportDetail['Items']) && is_array($supportDetail['Items'])) {
                        foreach ($supportDetail['Items'] as $key => $value) {
                            if ($key == 'Item') {
                                foreach ($value as $item) {
                                    if (isset($item['BidvestItemNumber'])) {
                                        $x = $item;
                                        $break = false;
                                    } elseif (isset($value['BidvestItemNumber'])) {
                                        $x = $value;
                                        $break = true;
                                    }


                                    //Get Product
                                    $product = $this->em->getRepository("Merchandise:ProductPurchased")->findOneBy(
                                        array(
                                            'externalId' => $x["BidvestItemNumber"],
                                        )
                                    );

                                    if ($product) {
                                        // Test If product exist in the original order
                                        $deliveryLine = new DeliveryLineTmp();
                                        $deliveryLine->setProduct($product)
                                            ->setQty(intval($x['DeliveredQuantity']['Qty']))
                                            ->setValorization(
                                                intval($x['DeliveredQuantity']['Qty']) * $product->getBuyingCost()
                                            );
                                        $val += (intval($x['DeliveredQuantity']['Qty']) * $product->getBuyingCost());
                                        $deliveryTmp->addLine(clone $deliveryLine);
                                    } else {//Product Not found
                                        $break = false;
                                    }

                                    if ($break) {
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $deliveryTmp->setValorization($val);

        return $deliveryTmp;
    }

    public function createTmpDeliveries($restaurant = null)
    {

        $orders = [];

        //Get sended Orders
        $qb = $this->em->getRepository("Merchandise:Order")->createQueryBuilder('o')
            ->where('o.status = :sended')
            ->orWhere('o.status = :modified')
            ->setParameter('sended', Order::SENDED)
            ->setParameter('modified', Order::MODIFIED);

        if (isset($restaurant)) {
            $qb->andWhere("o.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }
        $sendedOrders = $qb->getQuery()->getResult();

        //Get Modifi

        //get deliveries tmp
        if (isset($restaurant)) {
            $tmps = $this->em->getRepository("Merchandise:DeliveryTmp")->findBy(
                array("originRestaurant" => $restaurant)
            );
        } else {
            $tmps = $this->em->getRepository("Merchandise:DeliveryTmp")->findAll();
        }


        foreach ($sendedOrders as $so) {
            $exist = false;
            foreach ($tmps as $t) {
                if ($t->getOrder() == $so) {
                    $exist = true;
                }
            }
            if (!$exist) {
                $orders[] = $so;
            }
        }

        //Fetch for BL for sended orders and create tmp for
        $this->logger->addInfo("Searching for desadv in ".$this->ftpHost);
        if ($this->ftpHost != null) {
            $this->logger->addInfo("FETCH IN FTP FOR DESADV");
            foreach ($orders as $o) {
                $found = $this->checkDeliveryTicketAvailabiltiyForAnOrder($o);
                if ($found) {
                    $tmpDeliveryOld = $this->em->getRepository("Merchandise:DeliveryTmp")->findOneBy(
                        array(
                            'order' => $o,
                        )
                    );
                    if ($tmpDeliveryOld) {
                        $this->em->remove($tmpDeliveryOld);
                        $this->em->flush();
                    }

                    $path = $this->tmp."/".basename($found);
                    Utilities::moveFileFromFtpToPath(
                        $found,
                        $path,
                        $this->ftpHost,
                        $this->ftpPort,
                        $this->ftpUser,
                        $this->ftpPw
                    );
                    $deliveryTmp = $this->createDeliveryTmpFromXml($o, file_get_contents($path));
                    $this->em->persist($deliveryTmp);
                    $this->em->flush($deliveryTmp);
                }
            }
        } else {
            $this->logger->addInfo("FTP HOST IS DISABLED");
        }
    }

    public function checkDeliveryTmpExistenceForOrder(Order $order)
    {

        $deliveryTmp = $this->em->getRepository("Merchandise:DeliveryTmp")->findOneBy(
            array(
                'order' => $order,
            )
        );

        return $deliveryTmp;
    }

    public function constructDeliveryFilenameFroOrder(Order $order)
    {
        $file = './D_'.$order->getNumOrder().'.xml';

        return $file;
    }
}
