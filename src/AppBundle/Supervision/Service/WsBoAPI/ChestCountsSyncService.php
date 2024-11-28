<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use Doctrine\ORM\NoResultException;

// TODO : Marwen for restoration purpose
class ChestCountsSyncService extends AbstractSyncService
{

    /**
     * @param $tickets
     * @param $quickCode
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($tickets, $quickCode)
    {
        try {
            $restaurant = $this->em->getRepository('AppBundle:Restaurant')
                ->createQueryBuilder('restaurant')
                ->select('restaurant')
                ->where('restaurant.code = :code')
                ->setParameter('code', $quickCode)
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleResult();
        } catch (NoResultException $e) {
            $this->logger->addAlert(
                'Uknown restaurant code ('.$quickCode.')',
                ['TicketService', 'deserialize', 'UknownProduct']
            );
            throw new \Exception("restaurant : ".$quickCode." not found.");
        }

        $result = [];
        foreach ($tickets as $ticket) {
            $newTicket = new Ticket();
            try {
                $employee = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->createQueryBuilder('staff\employee')
                    ->select('staff\employee')
                    ->where('staff\employee.globalEmployeeID = :id')
                    ->setParameter('id', $ticket['employee'])
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown employee code ('.$ticket['employee'].') '.$e->getMessage(),
                    ['TicketService', 'deserialize', 'UknownProduct']
                );
                throw new \Exception("Employee : ".$ticket['employee']." not found.");
            }

            $newTicket
                ->setFiscalDate($ticket['fiscalDate'], 'Y-m-d')
                ->setOriginalID($ticket['id'])
                ->setStatus($ticket['status'])
                ->setSheetModelLabel($ticket['sheetModelLabel'])
                ->setEmployee($employee)
                ->setCreatedAt($ticket['createdAt'], 'Y-m-d H:i:s')
                ->setUpdatedAt($ticket['updatedAt'], 'Y-m-d H:i:s')
                ->setOrigin($restaurant);

            foreach ($ticket['lines'] as $line) {
                try {
                    $product = $this->em->getRepository('AppBundle:Product')
                        ->createQueryBuilder('product')
                        ->select('product')
                        ->where('product.globalProductID = :productId')
                        ->setParameter('productId', $line['product'])
                        ->getQuery()
                        ->setMaxResults(1)
                        ->getSingleResult();
                    $newLine = new TicketLine();
                    $newLine
                        ->setTotalTicketCnt($line['totalTicketCnt'])
                        ->setTicketCnt($line['ticketCnt'])
                        ->setUsageCnt($line['usageCnt'])
                        ->setExpedCnt($line['expedCnt'])
                        ->setCreatedAt($line['createdAt'], 'Y-m-d H:i:s')
                        ->setUpdatedAt($line['updatedAt'], 'Y-m-d H:i:s')
                        ->setProduct($product);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown product imported from Quick bo ('.$quickCode.') with id : '.$line['product'],
                        ['TicketService', 'deserialize', 'UknownProduct']
                    );
                    throw new \Exception("Product : ".$line['product']." not found.");
                }
            }
            $result[] = $newTicket;
        }

        return $result;
    }

    public function importTickets($ticketsData, $originalCode)
    {
        $this->em->beginTransaction();
        try {
            $tickets = $this->deserialize($ticketsData, $originalCode);
            foreach ($tickets as $ticket) {
                $this->em->persist($ticket);
                $this->em->flush();
            }
            $this->em->commit();
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
