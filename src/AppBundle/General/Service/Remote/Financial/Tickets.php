<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketInterventionSub;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class Tickets extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::TICKETS;
    }

    /**
     * @param Ticket[] $tickets
     */
    public function serialize($tickets)
    {
        //Create the data
        foreach ($tickets as $ticket) {
            /**
             * @var Ticket $ticket
             */
            $oData = array(
                'id' => $ticket->getId(),
                'type' => $ticket->getType(),
                'cancelledFlag' => $ticket->getCancelledFlag(),
                'num' => $ticket->getNum(),
                'startDate' => $ticket->getStartDate('Y-m-d H:i:s'),
                'endDate' => $ticket->getEndDate('Y-m-d H:i:s'),
                'invoiceNumber' => $ticket->getInvoiceNumber(),
                'totalHT' => $ticket->getTotalHT(),
                'totalTTC' => $ticket->getTotalTTC(),
                'paid' => $ticket->getPaid(),
                'deliveryTime' => $ticket->getDeliveryTime('Y-m-d H:i:s'),
                'operator' => $ticket->getOperator(),
                'operatorName' => $ticket->getOperatorName(),
                'responsible' => $ticket->getResponsible(),
                'workstation' => $ticket->getWorkstation(),
                'workstationName' => $ticket->getWorkstationName(),
                'originId' => $ticket->getOriginId(),
                'origin' => $ticket->getOrigin(),
                'destinationId' => $ticket->getDestinationId(),
                'destination' => $ticket->getDestination(),
                'entity' => $ticket->getEntity(),
                'customer' => $ticket->getCustomer(),
                'date' => $ticket->getDate('Y-m-d'),
                'counted' => $ticket->isCounted(),
                'createdAt' => $ticket->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $ticket->getUpdatedAt('Y-m-d H:i:s'),
                'status' => $ticket->getStatus(),
                'canceled' => $ticket->isCanceled(),
            );

            // lines
            $lines = [];
            foreach ($ticket->getLines() as $tl) {
                /**
                 * @var TicketLine $tl
                 */
                $lines[] = [
                    'id' => $tl->getId(),
                    'line' => $tl->getLine(),
                    'qty' => $tl->getQty(),
                    'price' => $tl->getPrice(),
                    'totalHT' => $tl->getTotalHT(),
                    'totalTTC' => $tl->getTotalTTC(),
                    'category' => $tl->getCategory(),
                    'division' => $tl->getDivision(),
                    'product' => $tl->getProduct(),
                    'description' => $tl->getDescription(),
                    'plu' => $tl->getPlu(),
                    'combo' => $tl->getCombo(),
                    'composition' => $tl->getComposition(),
                    'parentLine' => $tl->getParentLine(),
                    'tva' => $tl->getTva(),
                    'isDiscount' => $tl->getIsDiscount(),
                    'revenuePrice' => $tl->getRevenuePrice(),
                    'discount_id' => $tl->getDiscountId(),
                    'discount_code' => $tl->getDiscountCode(),
                    'discount_label' => $tl->getDiscountLabel(),
                    'discount_ht' => $tl->getDiscountHt(),
                    'discount_tva' => $tl->getDiscountTva(),
                    'discount_ttc' => $tl->getDiscountTtc(),
                ];
            }
            $oData['lines'] = $lines;

            // interventions
            $interventions = [];
            foreach ($ticket->getInterventions() as $int) {
                /**
                 * @var TicketIntervention $int
                 */
                $interventions[] = [
                    'action' => $int->getAction(),
                    'managerID' => $int->getManagerID(),
                    'managerName' => $int->getManagerName(),
                    'itemId' => $int->getItemId(),
                    'itemLabel' => $int->getItemLabel(),
                    'itemPrice' => $int->getItemPrice(),
                    'itemPLU' => $int->getItemPLU(),
                    'itemQty' => $int->getItemQty(),
                    'itemAmount' => $int->getItemAmount(),
                    'itemCode' => $int->getItemCode(),
                    'date' => $int->getDate('Y-m-d H:i:s'),
                    'posTotal' => $int->getPostTotal(),
                ];
                $subs = [];
                foreach ($int->getSubs() as $sub) {
                    /**
                     * @var TicketInterventionSub $sub
                     */
                    $subs[] = [
                        'subId' => $sub->getSubId(),
                        'subLabel' => $sub->getSubLabel(),
                        'subPrice' => $sub->getSubPrice(),
                        'subPLU' => $sub->getSubPLU(),
                        'subQty' => $sub->getSubQty(),
                    ];
                }
                $interventions['subs'] = $subs;
            }
            $oData['interventions'] = $interventions;

            // ticket payments
            $ticketPayments = [];
            foreach ($ticket->getPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                $ticketPayments[] = [
                    'id' => $payment->getId(),
                    'num' => $payment->getNum(),
                    'label' => $payment->getLabel(),
                    'idPayment' => $payment->getIdPayment(),
                    'code' => $payment->getCode(),
                    'amount' => $payment->getAmount(),
                    'type' => $payment->getType(),
                    'operator' => $payment->getOperator(),
                    'firstName' => $payment->getFirstName(),
                    'lastName' => $payment->getLastName(),
                    'electronic' => $payment->isElectronic(),
                ];
            }
            $oData['payments'] = $ticketPayments;
            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    public function uploadTickets($idCmd = null, $rawResponse = null)
    {
        $key = Utilities::generateRandomString(5);
        parent::preUpload();
        $this->logger->addInfo('Uploading Tickets to Central.', ['Tickets:uploadTickets']);
        // check if lock is here
        $param = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(['type' => Parameter::TICKET_UPLOAD]);
        if ($param) {
            $this->logger->addInfo('Existing lock, checking timeout.', ['Tickets:uploadTickets']);
            // Check timeout
            $now = new \DateTime('now');
            $diffInSeconds = $now->getTimestamp() - $param->getUpdatedAt()->getTimestamp();
            // If ticket lock wasn't updated since 1h delete it
            $this->logger->addInfo('Lock isn\'t updated since '.$diffInSeconds.'second', ['Tickets:uploadTickets']);
            if ($diffInSeconds > 7200) {
                $this->logger->addInfo('Lock expired for uploading Tickets to Central.', ['Tickets:uploadTickets']);
                $this->em->remove($param);
                $this->em->flush();

                $param = new Parameter();
                $param->setType(Parameter::TICKET_UPLOAD)
                    ->setValue($key);
                $this->em->persist($param);
                $this->em->flush();
                $this->logger->addInfo(
                    'Launching upload with renew existing lock due to a timeout.',
                    ['Tickets:uploadTickets']
                );
                $this->launchUpload($idCmd, $key);
            } else {
                $this->logger->addInfo('Process exit because another process has lock.', ['Tickets:uploadTickets']);

                return;
            }
        } else {
            $param = new Parameter();
            $param->setType(Parameter::TICKET_UPLOAD)
                ->setValue($key);
            $this->em->persist($param);
            $this->em->flush();
            $this->logger->addInfo('Launching upload with new lock.', ['Tickets:uploadTickets']);
            $this->launchUpload($idCmd, $key);
        }
    }

    public function launchUpload($idCmd, $key)
    {
        try {
            //Get Ticket paginated not uploaded
            $totalOfTickets = $this->em->getRepository("Financial:Ticket")->createQueryBuilder('ticket')
                ->select('count(ticket)')
                ->where("ticket.synchronized = false")
                ->orWhere("ticket.synchronized is NULL")
                ->getQuery()
                ->getSingleScalarResult();
            $this->logger->info('Try to upload '.$totalOfTickets.' tickets to Central.', ['UploadTickets']);
            $max_per_page = 100;
            $pages = ceil($totalOfTickets / $max_per_page);
            $this->logger->info('Pages : '.$pages.' , max per page : '.$max_per_page, ['UploadTickets']);
            for ($i = 1; $i <= $pages; $i++) {
                $tickets = $this->em->getRepository("Financial:Ticket")->createQueryBuilder('ticket')
                    ->where("ticket.synchronized = false")
                    ->orWhere("ticket.synchronized is NULL")
                    ->orderBy('ticket.startDate', 'desc')
                    ->setMaxResults(intval($max_per_page))
                    ->getQuery()
                    ->getResult();
                $this->logger->info('Page '.$i.' / '.$pages.' , Number of items : '.count($tickets), ['UploadTickets']);
                if (count($tickets)) {
                    $data = $this->serialize($tickets);
                    $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);
                    $this->logger->info(
                        'Number of error in reponse from central : '.count($response['error']),
                        ['UploadTickets']
                    );
                    if (count($response['error']) === 0) {
                        $events = Utilities::removeEvents(Ticket::class, $this->em);
                        foreach ($tickets as $ticket) {
                            /**
                             * @var Ticket $ticket
                             */
                            $ticket->setSynchronized(true);
                        }
                        $this->em->flush();
                        Utilities::returnEvents(Ticket::class, $this->em, $events);
                        $this->uploadFinishWithSuccess();
                    } else {
                        $this->uploadFinishWithFail();
                    }
                } else {
                    $this->logger->info('No tickets to upload in this page.', ['UploadTickets']);
                }

                $param = $this->em->getRepository('Administration:Parameter')
                    ->findOneBy(['type' => Parameter::TICKET_UPLOAD]);
                if ($param->getValue() == $key) {
                    $this->logger->addInfo('Updating current lock key : '.$key.'.', ['Tickets:uploadTickets']);
                    $param->setUpdatedAt(new \DateTime('now'));
                } else {
                    $this->logger->addInfo(
                        'Uploading this page took too much time and exceeded the timeout, process will be exited.',
                        ['Tickets:uploadTickets']
                    );

                    return;
                }
                $this->em->flush();
                $this->em->clear();
            }
            $this->logger->info('Upload tickets finished, deleting lock.', ['UploadTickets']);
            $param = $this->em->getRepository('Administration:Parameter')
                ->findOneBy(['type' => Parameter::TICKET_UPLOAD]);
            $this->em->remove($param);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage(), ['UploadTickets']);
            $param = $this->em->getRepository('Administration:Parameter')
                ->findOneBy(['type' => Parameter::TICKET_UPLOAD]);
            $this->em->remove($param);
            $this->em->flush();
            $this->em->clear();
            throw $e;
        }
    }

    /**
     *
     * +
     *
     * @return array
     */
    public function start($idCmd = null)
    {
        return $this->uploadTickets($idCmd);
    }
}
