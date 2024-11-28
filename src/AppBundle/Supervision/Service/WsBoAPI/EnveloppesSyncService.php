<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class EnveloppesSyncService extends AbstractSyncService
{
    /**
     * @param $enveloppes
     * @param Restaurant $restaurant
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($enveloppes, Restaurant $restaurant)
    {
        $result = [];
        foreach ($enveloppes as $enveloppe) {
            $enveloppe = json_decode($enveloppe, true);
            $newEnveloppe = new Envelope();
            try {
                $owner = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->createQueryBuilder('staff\employee')
                    ->select('staff\employee')
                    ->where('staff\employee.globalEmployeeID = :id')
                    ->setParameter('id', $enveloppe['owner'])
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown owner code ('.$enveloppe['employee'].') '.$e->getMessage(),
                    ['EnveloppeService', 'deserialize', 'UknownEmployee']
                );
                throw $e;
            }
            $cashier = null;
            if (!is_null($enveloppe['cashier'])) {
                try {
                    $cashier = $this->em->getRepository('AppBundle:Staff\Employee')
                        ->createQueryBuilder('staff\employee')
                        ->select('staff\employee')
                        ->where('staff\employee.globalEmployeeID = :id')
                        ->setParameter('id', $enveloppe['cashier'])
                        ->getQuery()
                        ->setMaxResults(1)
                        ->getSingleResult();
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown cashier code ('.$enveloppe['cashier'].') '.$e->getMessage(),
                        ['EnveloppeService', 'deserialize', 'UknownEmployee']
                    );
                    throw $e;
                }
            }
            $existantEnvelope = $this->em->getRepository('AppBundle:Financial\Envelope')->findOneBy(
                array(
                    'originalID' => $enveloppe['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (is_null($existantEnvelope)) {
                $newEnveloppe
                    ->setOriginalID($enveloppe['id'])
                    ->setNumEnvelope($enveloppe['numEnvelope'])
                    ->setAmount($enveloppe['amount'])
                    ->setSource($enveloppe['source'])
                    ->setSourceId($enveloppe['sourceId'])
                    ->setOwner($owner)
                    ->setCashier($cashier)
                    ->setReference($enveloppe['reference'])
                    ->setStatus($enveloppe['status'])
                    ->setType($enveloppe['type'])
                    ->setSousType($enveloppe['sousType'])
                    ->setCreatedAt($enveloppe['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($enveloppe['updatedAt'], 'Y-m-d H:i:s')
                    ->setOriginRestaurant($restaurant);
                $result[] = $newEnveloppe;
            }
        }

        return $result;
    }

    public function importEnveloppes($enveloppesData, Restaurant $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $enveloppes = $this->deserialize($enveloppesData, $restaurant);
            foreach ($enveloppes as $enveloppe) {
                $this->em->persist($enveloppe);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::ENVELOPPES, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing enveloppes, import was rollback : '.$e->getMessage(),
                ['EnveloppeService', 'ImportEnveloppes']
            );
            throw new \Exception($e);
        }
    }
}
