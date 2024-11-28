<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class WithdrawalsSyncService extends AbstractSyncService
{
    /**
     * @param $withdrawals
     * @param $restaurant
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($withdrawals, Restaurant $restaurant)
    {
        $result = [];
        foreach ($withdrawals as $withdrawal) {
            $withdrawal = json_decode($withdrawal, true);
            $existantSheet = $this->em->getRepository('AppBundle:Financial\Withdrawal')->findOneBy(
                array(
                    'originalID' => $withdrawal['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (!is_null($existantSheet)) {
                $this->em->remove($existantSheet);
                $this->em->flush();
            }
            $newWithdrawal = new Withdrawal();
            try {
                $member = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->createQueryBuilder('staff\employee')
                    ->select('staff\employee')
                    ->where('staff\employee.globalEmployeeID = :id')
                    ->setParameter('id', $withdrawal['member'])
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown member code ('.$withdrawal['employee'].') '.$e->getMessage(),
                    ['WithdrawalService', 'deserialize', 'UknownProduct']
                );
                throw $e;
            }
            try {
                $responsible = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->createQueryBuilder('staff\employee')
                    ->select('staff\employee')
                    ->where('staff\employee.globalEmployeeID = :id')
                    ->setParameter('id', $withdrawal['responsable'])
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown responsible code ('.$withdrawal['employee'].') '.$e->getMessage(),
                    ['WithdrawalService', 'deserialize', 'UknownProduct']
                );
                throw $e;
            }
            $enveloppe = null;
            if (!is_null($withdrawal['envelopeId'])) {
                try {
                    $enveloppe = $this->em->getRepository('AppBundle:Financial\Envelope')
                        ->createQueryBuilder('e')
                        ->select('e')
                        ->leftJoin('e.originRestaurant', 'originRestaurant')
                        ->where('originRestaurant = :restaurant')
                        ->setParameter('restaurant', $restaurant)
                        ->andWhere('e.originalID = :id')
                        ->setParameter('id', $withdrawal['envelopeId'])
                        ->getQuery()
                        ->setMaxResults(1)
                        ->getSingleResult();
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown enveloppe code ('.$withdrawal['envelopeId'].') '.$e->getMessage(),
                        ['WithdrawalService', 'deserialize', 'UknownProduct']
                    );
                    throw $e;
                }
            }
            $newWithdrawal
                ->setOriginalID($withdrawal['id'])
                ->setDate($withdrawal['date'], 'Y-m-d H:i:s')
                ->setAmountWithdrawal($withdrawal['amountWithdrawal'])
                ->setStatusCount($withdrawal['statusCount'])
                ->setMember($member)
                ->setResponsible($responsible)
                ->setEnvelopeId(is_null($enveloppe) ? null : $enveloppe->getId())
                ->setCreatedAt($withdrawal['createdAt'], 'Y-m-d H:i:s')
                ->setUpdatedAt($withdrawal['updatedAt'], 'Y-m-d H:i:s')
                ->setOriginRestaurant($restaurant);

            $result[] = $newWithdrawal;
        }

        return $result;
    }

    public function importWithdrawals($withdrawalsData, Restaurant $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $withdrawals = $this->deserialize($withdrawalsData, $restaurant);
            foreach ($withdrawals as $withdrawal) {
                $this->em->persist($withdrawal);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::WITHDRAWALS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing withdrawals, import was rollback : '.$e->getMessage(),
                ['WithdrawalService', 'ImportWithdrawals']
            );
            throw $e;
        }
    }
}
