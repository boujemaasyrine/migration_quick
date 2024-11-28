<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\Deposit;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class DepositsSyncService extends AbstractSyncService
{

    /**
     * @param $deposits
     * @param Restaurant $restaurant
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($deposits, Restaurant $restaurant)
    {
        $result = [];
        foreach ($deposits as $deposit) {
            $deposit = json_decode($deposit, true);
            $newDeposit = new Deposit();
            try {
                $owner = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->getEmployeeByGlobalId($deposit['owner']);
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown employee code ('.$deposit['owner'].') '.$e->getMessage(),
                    ['DepositService', 'deserialize', 'UknownEmployee']
                );
                throw $e;
            }
            // expense
            $expense = null;
            if (!is_null($deposit['expense'])) {
                try {
                    $expense = $this->em->getRepository('AppBundle:Financial\Expense')
                        ->createQueryBuilder('exp')
                        ->leftJoin('exp.originRestaurant', 'originRestaurant')
                        ->where('originRestaurant = :restaurant')
                        ->setParameter('restaurant', $restaurant)
                        ->andWhere('exp.originalID = :id')
                        ->setParameter('id', $deposit['expense'])
                        ->getQuery()
                        ->setMaxResults(1)
                        ->getSingleResult();
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown expense code ('.$deposit['expense'].') '.$e->getMessage(),
                        ['DepositService', 'deserialize', 'UknownExpense']
                    );
                    throw $e;
                }
            }
            $existantDeposit = $this->em->getRepository('AppBundle:Financial\Deposit')->findOneBy(
                array(
                    'originalID' => $deposit['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (is_null($existantDeposit)) {
                $newDeposit
                    ->setOriginalID($deposit['id'])
                    ->setOwner($owner)
                    ->setExpense($expense)
                    ->setReference($deposit['reference'])
                    ->setSource($deposit['source'])
                    ->setDestination($deposit['destination'])
                    ->setAffiliateCode($deposit['affiliateCode'])
                    ->setType($deposit['type'])
                    ->setSousType($deposit['sousType'])
                    ->setTotalAmount($deposit['totalAmount'])
                    ->setCreatedAt($deposit['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($deposit['updatedAt'], 'Y-m-d H:i:s')
                    ->setOriginRestaurant($restaurant);
                $result[] = $newDeposit;
            }
        }

        return $result;
    }

    public function importDeposits($depositsData, Restaurant $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $deposits = $this->deserialize($depositsData, $restaurant);
            foreach ($deposits as $deposit) {
                $this->em->persist($deposit);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::DEPOSITS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing deposits, import was rollback : '.$e->getMessage(),
                ['DepositService', 'ImportDeposits']
            );
            throw new \Exception($e);
        }
    }
}
