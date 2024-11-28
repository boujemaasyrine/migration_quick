<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 14:32
 */

namespace AppBundle\Financial\Service;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\ChestSmallChest;
use AppBundle\Financial\Entity\DeletedEnvelope;
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;
use AppBundle\Financial\Entity\CashboxCount;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\DateTime;

class EnvelopeService
{

    private $em;
    private $translator;
    private $parameter;
    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        Translator $translator,
        ParameterService $parameter,
        RestaurantService $restaurantService
    )
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->parameter = $parameter;
        $this->restaurantService = $restaurantService;
    }

    public function serializeEnvelopes($envelopes, $showTotal = true)
    {
        /* $result = [];
         foreach ($envelopes as $e) {
             $result[] = $this->serializeEnvelope($e);
         }

         return $result;*/

        $count = 0;
        foreach ($envelopes as $env) {
            $count++;
        }

        $result = [];

        $dateStart = new \DateTime('1970-01-01T15:03:01.012345Z');
        $dateStart = $dateStart->format('d/m/Y');
        $i = 0;
        $total = 0;

        foreach ($envelopes as $e) {

            $dateCurrent = $e->getCreatedAt()->format('d/m/Y');
            if ($dateStart == $dateCurrent || $dateStart == '01/01/1970') {
                $tmp = array();
                $tmp = $this->serializeEnvelope($e);
                $dateStart = $tmp['createdAt'];
                $total += floatval(str_replace(",", ".", $tmp['amount']));
                $result[] = $tmp;
                $i++;

            } else {

                if ($showTotal) {
                    $tmp = array();
                    $tmp['id'] = 'total';
                    $tmp['number'] = $this->translator->trans("envelope.total");
                    $tmp['reference'] = "";
                    $tmp['amount'] = $e->getType() == Envelope::TYPE_TICKET
                        ? number_format(
                            $total,
                            2,
                            ',',
                            ''
                        ) : $total;
                    $tmp['source'] = '';
                    $tmp['sousType'] = '';
                    $tmp['owner'] = '';
                    $tmp['cashier'] = '';
                    $tmp['date'] = $dateStart;
                    $tmp['createdAt'] = $dateStart;
                    $tmp['status'] = '';
                    $tmp['reference'] = '';
                    $result[] = $tmp;

                    $i++;
                }

                $tmp = array();
                $tmp = $this->serializeEnvelope($e);
                $dateStart = $tmp['createdAt'];
                $total = $tmp['amount'];
                $result[] = $tmp;
                $count++;
                $i++;

            }

            if ($i == $count) {

                if ($showTotal) {
                    $tmp = [];

                    $tmp['id'] = 'total';
                    $tmp['number'] = $this->translator->trans("envelope.total");
                    $tmp['reference'] = "";

                    $tmp['amount'] = $e->getType() == Envelope::TYPE_TICKET
                        ? number_format(
                            $total,
                            2,
                            ',',
                            ''
                        ) : $total;
                    $tmp['source'] = '';
                    $tmp['sousType'] = '';
                    $tmp['owner'] = '';
                    $tmp['cashier'] = '';
                    $tmp['date'] = $dateStart;
                    $tmp['createdAt'] = $dateStart;
                    $tmp['status'] = '';
                    $tmp['reference'] = '';
                    $result[] = $tmp;
                    $i++;
                }
            }
        }


        return $result;
    }

    /**
     * @param Envelope $e
     * @return array
     */
    public function serializeEnvelope(Envelope $e)
    {
        $result = array(
            'id' => $e->getId(),
            'number' => $e->getNumEnvelope(),
            'amount' => $e->getType() == Envelope::TYPE_TICKET ? number_format(
                $e->getAmount(),
                2,
                ',',
                ''
            ) : $e->getAmount(),
            'source' => $this->translator->trans("envelope.source." . strtolower($e->getSource())),
            'sousType' => $e->getType() == Envelope::TYPE_TICKET ?
                $this->parameter->getTicketRestaurantLabel($e->getSousType())
                : $this->translator->trans("envelope.source." . strtolower($e->getSource())),
            'owner' => $e->getOwner() ? $e->getOwner()->getFirstName() . ' ' . $e->getOwner()->getLastName() : '',
            'cashier' => $e->getCashier() ? $e->getCashier()->getFirstName() . ' ' . $e->getCashier()->getLastName() : '',
            'date' => $e->getCreatedAt() ? date_format($e->getCreatedAt(), 'd/m/Y') : '',
            'createdAt' => $e->getCreatedAt() ? date_format($e->getCreatedAt(), 'd/m/Y') : '',
            'status' => $e->getStatus() ? $this->translator->trans("envelope.status." . strtolower($e->getStatus())) : '',
            'reference' => $e->getReference(),
            'chestCount' => $e->getChestCount() ? $e->getChestCount()->getId() : null
        );

        return $result;
    }

    public function getEnvelopes(
        $criteria,
        $order,
        $limit,
        $offset,
        $search = null,
        $onlyList = false,
        $type = Envelope::TYPE_CASH
    )
    {
        $withdrawals = $this->em->getRepository("Financial:Envelope")->getEnvelopesFilteredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $search,
            $onlyList,
            $type
        );

        return $this->serializeEnvelopes($withdrawals);
    }

    public function getLastNumNotVersedEnvelope($sousType = null)
    {
        try {
            if ($sousType == null) {
                $sousType = Envelope::TYPE_CASH;
            }
            /**
             * @var Envelope $lastEnveloppe
             */
            $lastEnveloppe = $this->em->getRepository('Financial:Envelope')->createQueryBuilder('envelope')
                ->where('envelope.status = :notVersed')
                ->setParameter('notVersed', Envelope::NOT_VERSED)
                ->andWhere('envelope.sousType = :sousType')
                ->setParameter('sousType', $sousType)
                ->orderBy('envelope.numEnvelope', "DESC")
                ->setMaxResults(1)
                ->getQuery()->getSingleResult();

            return $lastEnveloppe->getNumEnvelope();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function saveCashboxCountEnveloppe(Envelope $envelope)
    {
        try {
            if (is_null($envelope->getId())) {
                $envelope->setNumEnvelope($this->getLastNumNotVersedEnvelope($envelope->getSousType()) + 1);
                $envelope->setStatus(Envelope::NOT_VERSED);
                $this->em->persist($envelope);
            }
            $this->em->flush();
        } catch (\Exception $e) {
        }
    }

    public function saveEnvelopeCash(Envelope $envelope)
    {
        try {
            if (is_null($envelope->getId())) {
                $envelope->setNumEnvelope($this->getLastNumNotVersedEnvelope($envelope->getSousType()) + 1);
                $envelope->setStatus(Envelope::NOT_VERSED);
                $this->em->persist($envelope);
            }
            $this->em->flush();
        } catch (\Exception $e) {
            throw new Exception($e);
        }
    }

    public function getTotalEnvelopeNotVersed($type, $sousType = null)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $return = $this->em->getRepository('Financial:Envelope')->getTotalNotVersed(
            $type,
            $sousType,
            $currentRestaurant
        );
        if ($return && isset($return[0]) && $return[0]['total']) {
            return $return[0]['total'];
        } else {
            return 0;
        }
    }

    public function getEnvelopesCriteria($type, $status, $source = null)
    {
        $envelopes = $this->em
            ->getRepository('Financial:Envelope')
            ->getEnvelopesCriteria($type, $status, $source, $this->restaurantService->getCurrentRestaurant());
        $envelopes = $this->serializeEnvelopes($envelopes, false);

        return $envelopes;
    }

    public function getEnvelopesDeposit(Deposit $deposit)
    {
        $envelopes = $this->em
            ->getRepository('Financial:Envelope')
            ->getEnvelopesDeposit($deposit);
        $envelopes = $this->serializeEnvelopes($envelopes, false);

        return $envelopes;
    }

    public function getTrMaxAmount($sousType = null)
    {
        $total = 0.0;

        // TR last chest count
        $chestCount = $this->em->getRepository("Financial:ChestCount")->getLastChestCount(
            $this->restaurantService->getCurrentRestaurant()
        );
        if ($chestCount) {
            /**
             * @var ChestCount $chestCount
             */
            $total += $chestCount->getSmallChest()->calculateRealTrTotal($sousType);
        }

        // TR cashbox count not counted
        $cashBoxCounts = $this->em->getRepository("Financial:CashboxCount")->getNotCounted(
            $this->restaurantService->getCurrentRestaurant()
        );

        foreach ($cashBoxCounts as $cashBoxCount) {
            /**
             * @var CashboxCount $cashBoxCount
             */
            $total += $cashBoxCount->getCheckRestaurantContainer()->calculateRealTotalAmount(null, $sousType);
        }

        // -TR envelope not counted
        $envelopes = $this->em->getRepository("Financial:Envelope")->getNotCounted(
            Envelope::TYPE_TICKET,
            $sousType,
            $this->restaurantService->getCurrentRestaurant()
        );
        foreach ($envelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $total -= $envelope->getAmount();
        }

        if ($total < 0) {
            $total = 0;
        }

        return number_format($total, 2, ',', '');
    }

    public function removeEnvelope(Envelope $envelope)
    {

        $entity = $this->em->getRepository(Envelope::class)->find($envelope);
        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            return true;
        }
        //return false if no operation is done
        return false;

    }

    /**
     * Supprimer tous les enveloppes de source cashbox_counts liés a cet caisse
     * @param $cbID id de caisse
     */
    public function removeCashboxEnvelope($cbID)
    {
        $envelopes = $this->em->getRepository(Envelope::class)->findBy(array('source' => Envelope::CASHBOX_COUNTS, 'sourceId' => $cbID));
        if (count($envelopes) > 0) {
            foreach ($envelopes as $e) {
                if (!$e->getChestCount()) {// delete only if not related to chest count
                    $this->removeEnvelope($e);
                }
            }
        }
    }

    /**
     * Calculer le montant totale des prélèvements
     * @param Restaurant $restaurant
     * @param $date
     * @return int
     */
    public function calculateTotalAmountOfWithdrwals(Restaurant $restaurant, $date, $onlyAmount = true)
    {
        $date = $date->format('j/m/Y');
        $criteria = [
            'restaurant' => $restaurant,
            'withdrawal_search[startDate' => $date,
            'withdrawal_search[endDate' => $date
        ];
        $ws = $this->em->getRepository(Withdrawal::class)->getWithdrawalsFiltredOrdered($criteria, null, null, null, true);
        $amount = 0;
        foreach ($ws as $w) {
            $amount += $w->getAmountWithdrawal();
        }

        if ($onlyAmount) {
            return $amount;
        } else {
            return [$ws, $amount];
        }
    }

    /**
     * Calculer le montant totale des enveloppes de source prélèvement qui ont été crées après la dernier date de cloture
     * @param Restaurant $restaurant
     * @return int
     */
    public function calculateTotalAmountOfEnvelopeSourceWithdrawalOfClosing(Restaurant $restaurant, $lastClosingDate)
    {
        $lcc=$this->em->getRepository(ChestCount::class)->getChestCountForClosedDate($lastClosingDate,$restaurant);
        $es = $this->em->getRepository(Envelope::class)->getEnvelopeWithdrawalOfClosing($restaurant,$lcc->getCreatedAt());
        $amount = 0;
        foreach ($es as $e) {
            $amount += $e->getAmount();
        }
        return $amount;
    }
    public function serializeDeletedEnvelopes($envelopes, $showTotal = true)
    {
        $count = 0;
        foreach ($envelopes as $env) {
            $count++;
        }

        $result = [];

        $dateStart = new \DateTime('1970-01-01T15:03:01.012345Z');
        $dateStart = $dateStart->format('d/m/Y');
        $i = 0;
        $total = 0;

        foreach ($envelopes as $e) {

            $dateCurrent = $e->getCreatedAt()->format('d/m/Y');
            if ($dateStart == $dateCurrent || $dateStart == '01/01/1970') {
                $tmp = array();
                $tmp = $this->serializeDeletedEnvelope($e);
                $tmp['deletedAt'] = $e->getDeletedAt() ? $e->getDeletedAt()->format('d/m/Y H:i:s') : null; // Ajout du champ deletedAt
                $tmp['deletedBy'] = $e->getDeletedBy() ? $e->getDeletedBy()->getFirstname().'  '.$e->getDeletedBy()->getLastName() : null; // Ajout du champ deletedBy
                $dateStart = $tmp['createdAt'];
                $total += floatval(str_replace(",", ".", $tmp['amount']));
                $result[] = $tmp;
                $i++;

            } else {

                if ($showTotal) {
                    $tmp = array();
                    $tmp['id'] = 'total';
                    $tmp['number'] = $this->translator->trans("deleted_envelope.total");
                    $tmp['reference'] = "";
                    $tmp['amount'] = $e->getType() == DeletedEnvelope::TYPE_TICKET
                        ? number_format(
                            $total,
                            2,
                            ',',
                            ''
                        ) : $total;
                    $tmp['source'] = '';
                    $tmp['sousType'] = '';
                    $tmp['owner'] = '';
                    $tmp['cashier'] = '';
                    $tmp['date'] = $dateStart;
                    $tmp['createdAt'] = $dateStart;
                    $tmp['status'] = '';
                    $tmp['reference'] = '';
                    $tmp['deletedAt'] = null;
                    $tmp['deletedBy'] = null;
                    $result[] = $tmp;

                    $i++;
                }

                $tmp = array();
                $tmp = $this->serializeDeletedEnvelope($e);
                $tmp['deletedAt'] = $e->getDeletedAt() ? $e->getDeletedAt()->format('d/m/Y H:i:s') : null; // Ajout du champ deletedAt
                $tmp['deletedBy'] = $e->getDeletedBy() ? $e->getDeletedBy()->getFirstname().' '.$e->getDeletedBy()->getLastName() : null; // Ajout du champ deletedBy
                $dateStart = $tmp['createdAt'];
                $total = $tmp['amount'];
                $result[] = $tmp;
                $count++;
                $i++;

            }

            if ($i == $count) {

                if ($showTotal) {
                    $tmp = [];

                    $tmp['id'] = 'total';
                    $tmp['number'] = $this->translator->trans("deleted_envelope.total");
                    $tmp['reference'] = "";

                    $tmp['amount'] = $e->getType() == DeletedEnvelope::TYPE_TICKET
                        ? number_format(
                            $total,
                            2,
                            ',',
                            ''
                        ) : $total;
                    $tmp['source'] = '';
                    $tmp['sousType'] = '';
                    $tmp['owner'] = '';
                    $tmp['cashier'] = '';
                    $tmp['date'] = $dateStart;
                    $tmp['createdAt'] = $dateStart;
                    $tmp['status'] = '';
                    $tmp['reference'] = '';
                    $tmp['deletedAt'] = null;
                    $tmp['deletedBy'] = null;
                    $result[] = $tmp;
                    $i++;
                }
            }
        }

        return $result;
    }



    /**
     * @param DeletedEnvelope $e
     * @return array
     */
    public function serializeDeletedEnvelope(DeletedEnvelope $e)
    {
        $result = array(
            'id' => $e->getId(),
            'number' => $e->getNumEnvelope(),
            'amount' => $e->getType() == DeletedEnvelope::TYPE_TICKET ? number_format(
                $e->getAmount(),
                2,
                ',',
                ''
            ) : $e->getAmount(),
            'source' => $this->translator->trans("envelope.source." . strtolower($e->getSource())),
            'sousType' => $e->getType() == DeletedEnvelope::TYPE_TICKET ?
                $this->parameter->getTicketRestaurantLabel($e->getSousType())
                : $this->translator->trans("envelope.source." . strtolower($e->getSource())),
            'owner' => $e->getOwner() ? $e->getOwner()->getFirstName() . ' ' . $e->getOwner()->getLastName() : '',
            'cashier' => $e->getCashier() ? $e->getCashier()->getFirstName() . ' ' . $e->getCashier()->getLastName() : '',
            'date' => $e->getCreatedAt() ? date_format($e->getCreatedAt(), 'd/m/Y') : '',
            'createdAt' => $e->getCreatedAt() ? date_format($e->getCreatedAt(), 'd/m/Y') : '',
            'status' => $e->getStatus() ? $this->translator->trans("envelope.status." . strtolower($e->getStatus())) : '',
            'reference' => $e->getReference(),
            'chestCount' => $e->getChestCount() ? $e->getChestCount()->getId() : null,
            'deletedAt' => $e->getDeletedAt() ? date_format($e->getDeletedAt(), 'd/m/Y H:i:s') : null,
            'deletedBy' => $e->getDeletedBy() ? $e->getDeletedBy()->getFirstName() . ' ' . $e->getDeletedBy()->getLastName() : ''
        );

        return $result;
    }


    public function getDeletedEnvelopes(
        $criteria,
        $order,
        $limit,
        $offset,
        $search = null,
        $onlyList = false,
        $type = DeletedEnvelope::TYPE_CASH
    )
    {
        $withdrawals = $this->em->getRepository("Financial:DeletedEnvelope")->getDeletedEnvelopesFilteredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $search,
            $onlyList,
            $type
        );

        return $this->serializeDeletedEnvelopes($withdrawals);
    }




}
