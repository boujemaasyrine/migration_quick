<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketInterventionSub;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;

class TicketsSyncService extends AbstractSyncService
{

    /**
     * @param $tickets
     * @param Restaurant $restaurant
     * @return array
     */
    public function deserialize($tickets, Restaurant $restaurant)
    {
        $result = [];
        foreach ($tickets as $ticket) {
            $ticket = json_decode($ticket, true);
            // Verify if the ticket is not already exist
            $existingTicket = $this->em->getRepository('AppBundle:Financial\Ticket')
                ->findOneBy(['originRestaurant' => $restaurant, 'originalID' => $ticket['id']]);

            if (is_null($existingTicket)) {
                $newTicket = new Ticket();
                $newTicket
                    ->setStatus($ticket['status'])
                    ->setOriginalID($ticket['id'])
                    ->setType($ticket['type'])
                    ->setCancelledFlag($ticket['cancelledFlag'])
                    ->setNum($ticket['num'])
                    ->setStartDate($ticket['startDate'], 'Y-m-d H:i:s')
                    ->setEndDate($ticket['endDate'], 'Y-m-d H:i:s')
                    ->setInvoiceNumber($ticket['invoiceNumber'])
                    ->setTotalHT($ticket['totalHT'])
                    ->setTotalTTC($ticket['totalTTC'])
                    ->setPaid($ticket['paid'])
                    ->setDeliveryTime($ticket['deliveryTime'], 'Y-m-d H:i:s')
                    ->setOperator($ticket['operator'])
                    ->setOperatorName($ticket['operatorName'])
                    ->setResponsible($ticket['responsible'])
                    ->setWorkstation($ticket['workstation'])
                    ->setWorkstationName($ticket['workstationName'])
                    ->setOriginId($ticket['originId'])
                    ->setOrigin($ticket['origin'])
                    ->setDestinationId($ticket['destinationId'])
                    ->setDestination($ticket['destination'])
                    ->setEntity($ticket['entity'])
                    ->setCustomer($ticket['customer'])
                    ->setDate($ticket['date'], 'Y-m-d')
                    ->setCounted($ticket['counted'])
                    ->setCreatedAt($ticket['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($ticket['updatedAt'], 'Y-m-d H:i:s')
                    ->setOriginRestaurant($restaurant);
                foreach ($ticket['lines'] as $line) {
                    $newTicketLine = new TicketLine();
                    $newTicketLine->setOriginalID($line['id'])
                        ->setLine($line['line'])
                        ->setQty($line['qty'])
                        ->setTotalHT($line['totalHT'])
                        ->setTotalTTC($line['totalTTC'])
                        ->setCategory($line['category'])
                        ->setDivision($line['division'])
                        ->setProduct($line['product'])
                        ->setDescription($line['description'])
                        ->setPlu($line['plu'])
                        ->setCombo($line['combo'])
                        ->setComposition($line['composition'])
                        ->setParentLine($line['parentLine'])
                        ->setTva($line['tva'])
                        ->setIsDiscount($line['isDiscount'])
                        ->setRevenuePrice($line['revenuePrice'])
                        ->setDiscountId(strval($line['discount_id']))
                        ->setDiscountCode(strval($line['discount_code']))
                        ->setDiscountLabel(strval($line['discount_label']))
                        ->setDiscountHt(floatval($line['discount_ht']))
                        ->setDiscountTva(floatval($line['discount_tva']))
                        ->setDiscountTtc(floatval($line['discount_ttc']));
                    $newTicket->addLine($newTicketLine);
                }
                foreach ($ticket['interventions'] as $intervention) {
                    $newIntervention = new TicketIntervention();
                    if (count($intervention) > 0) {
                        $newIntervention->setAction($intervention['action'])
                            ->setManagerID($intervention['managerID'])
                            ->setManagerName($intervention['managerName'])
                            ->setItemId($intervention['itemId'])
                            ->setItemLabel($intervention['itemLabel'])
                            ->setItemPrice($intervention['itemPrice'])
                            ->setItemPLU($intervention['itemPLU'])
                            ->setItemQty($intervention['itemQty'])
                            ->setItemAmount($intervention['itemAmount'])
                            ->setItemCode($intervention['itemCode'])
                            ->setDate($intervention['date'], 'Y-m-d H:i:s')
                            ->setPostTotal($intervention['posTotal']);
                        if (key_exists('subs', $intervention)) {
                            foreach ($intervention['subs'] as $sub) {
                                $newSubIntervention = new TicketInterventionSub();
                                $newSubIntervention->setSubId($sub['subId'])
                                    ->setSubLabel($sub['subLabel'])
                                    ->setSubPrice($sub['subPrice'])
                                    ->setSubPLU($sub['subPLU'])
                                    ->setSubQty($sub['subQty']);
                                $newIntervention->addSub($newSubIntervention);
                            }
                        }
                    }
                    $newTicket->addIntervention($newIntervention);
                }
                foreach ($ticket['payments'] as $payment) {
                    $newTicketPayment = new TicketPayment();
                    $newTicketPayment->setOriginalID($payment['id'])
                        ->setNum($payment['num'])
                        ->setLabel($payment['label'])
                        ->setIdPayment($payment['idPayment'])
                        ->setCode($payment['code'])
                        ->setAmount($payment['amount'])
                        ->setType($payment['type'])
                        ->setOperator($payment['operator'])
                        ->setFirstName($payment['firstName'])
                        ->setLastName($payment['lastName'])
                        ->setElectronic($payment['electronic']);
                    $newTicket->addPayment($newTicketPayment);
                }
                $result[] = $newTicket;
            } else {
                foreach ($existingTicket->getLines() as $line) {
                    /**
                     * @var TicketLine $line
                     */
                    foreach ($ticket['lines'] as $aline) {
                        if ($aline['id'] == $line->getOriginalID()) {
                            // TODO :  update all other fields
                            $line->setRevenuePrice($aline['revenuePrice']);
                            break;
                        }
                    }
                }
                $result[] = $existingTicket;
            }
        }

        return $result;
    }

    public function importTickets($ticketsData, $restaurant)
    {
        try {
            $this->em->beginTransaction();
            $tickets = $this->deserialize($ticketsData, $restaurant);
            foreach ($tickets as $ticket) {
                $this->em->persist($ticket);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::TICKETS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing inventories, import was rollback : '.$e->getMessage(),
                ['TicketService', 'ImportTickets']
            );
            throw new \Exception($e);
        }
    }
}
