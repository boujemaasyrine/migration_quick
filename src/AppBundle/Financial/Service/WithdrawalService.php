<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/03/2016
 * Time: 11:32
 */

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\Translator;

class WithdrawalService
{

    private $em;
    private $tokenStorage;
    private $translator;

    /**
     * @var EnvelopeService
     */
    private $envelopeService;
    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        Translator $translator,
        EnvelopeService $envelopeService,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->envelopeService = $envelopeService;
        $this->restaurantService = $restaurantService;
    }

    public function saveWithdrawal(Withdrawal $withdrawal, $today = null)
    {
        if (!$today) {
            $today = new \DateTime(date("Y-m-d"));
        }
        $withdrawal->setDate($today);

        $withdrawal->setStatusCount(Withdrawal::NOT_COUNTED);
        $withdrawal->setResponsible($this->tokenStorage->getToken()->getUser());
        $withdrawal->setAmountWithdrawal(str_replace(',', '.', $withdrawal->getAmountWithdrawal()));

        if ($withdrawal->getEnvelopeId()) {
            $envelope = $this->em->getRepository('Financial:Envelope')->find($withdrawal->getEnvelopeId());
            $envelope->setAmount($withdrawal->getAmountWithdrawal());
        }

        $this->em->persist($withdrawal);
        $this->em->flush();
    }

    public function saveEnvelope(Envelope $envelope, $withdrawal)
    {
        $withdrawalObject = $this->em->getRepository('Financial:Withdrawal')->find($withdrawal);
        if (is_null($withdrawalObject->getEnvelopeId())) {
            $envelope->setSourceId($withdrawal)
                ->setSource(Envelope::WITHDRAWAL)
                ->setOwner($this->tokenStorage->getToken()->getUser())
                ->setCashier($withdrawalObject->getMember())
                ->setNumEnvelope($this->envelopeService->getLastNumNotVersedEnvelope() + 1)
                ->setStatus(Envelope::NOT_VERSED);
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $envelope->setOriginRestaurant($currentRestaurant);
            $this->em->persist($envelope);

            $withdrawalObject->setEnvelopeId($envelope->getId());
            $this->em->persist($withdrawalObject);

            $this->em->flush();
        }
    }

    public function getWithdrawals($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $withdrawals = $this->em->getRepository("Financial:Withdrawal")->getWithdrawalsFiltredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeWithdrawals($withdrawals);
    }

    public function serializeWithdrawals($withdrawals)
    {
        $result = [];
        foreach ($withdrawals as $w) {
            if ($w->getStatusCount() == Withdrawal::COUNTED) {
                $edit = 'closing';
            } elseif ($w->getResponsible() != $this->tokenStorage->getToken()->getUser()) {
                $edit = 'notTheResponsible';
            } else {
                $edit = 'editable';
            }
            $result[] = array(
                'id' => $w->getId(),
                'responsible' => $w->getResponsible()->getFirstName().' '.$w->getResponsible()->getLastName(),
                'member' => $w->getMember()->getFirstName().' '.$w->getMember()->getLastName(),
                'date' => date_format($w->getDate(), 'd/m/Y'),
                'createdAt' => date_format($w->getCreatedAt(), 'd/m/Y H:i:s'),
                'amount' => $w->getAmountWithdrawal(),
                'status' => $this->translator->trans("status.".$w->getStatusCount()),
                'envelope' => ($w->getEnvelopeId()) ? 'true' : 'false',
                'edit' => $edit,

            );
        }

        return $result;
    }

    public function calculateTotalPendingWithdrawals(\DateTime $dateTime, Employee $employee = null)
    {
        $total = 0;
        // If dateTime is today set datetime to now, otherwise it will be all day date
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        $date = clone $dateTime;
        $date->setTime(0, 0, 0);

        $diff = $today->diff($date);
        $diffDays = (integer) $diff->format("%R%a");

        if (!is_null($employee)) {
            if ($diffDays === 0) {
                $total = $this->em->getRepository(
                    'Financial:Withdrawal'
                )->calculatePendingWithdrawalTotalByOperatorByDate(new \DateTime('now'), $employee);
            } else {
                $allDayWithdrawals = true;
                $total = $this->em->getRepository(
                    'Financial:Withdrawal'
                )->calculatePendingWithdrawalTotalByOperatorByDate($dateTime, $employee, $allDayWithdrawals);
            }
        }

        return (-$total);
    }

    public function findPendingWithdrawals(\DateTime $date, Employee $employee = null)
    {
        $withdrawals = [];

        if (!is_null($employee)) {
            $withdrawals = $this->em->getRepository(
                'Financial:Withdrawal'
            )->calculatePendingWithdrawalTotalByOperatorByDate(
                $date,
                $employee,
                true,
                $this->restaurantService->getCurrentRestaurant()
            );
        }

        return $withdrawals;
    }

    public function getCriteria($criteria)
    {
        if ($criteria['withdrawal_search[owner'] != '') {
            $owner = $this->em->getRepository('Staff:Employee')->find($criteria['withdrawal_search[owner']);
            $result['owner'] = $owner->getFirstName().' '.$owner->getLastName();
        } else {
            $result['owner'] = $this->translator->trans('label.all');
        }
        if ($criteria['withdrawal_search[member'] != '') {
            $member = $this->em->getRepository('Staff:Employee')->find($criteria['withdrawal_search[member']);
            $result['member'] = $member->getFirstName().' '.$member->getLastName();
        } else {
            $result['member'] = $this->translator->trans('label.all');
        }
        if ($criteria['withdrawal_search[startDate'] != '') {
            $result['startDate'] = $criteria['withdrawal_search[startDate'];
        } else {
            $result['startDate'] = $this->translator->trans('keyword.unspecified');
        }
        if ($criteria['withdrawal_search[endDate'] != '') {
            $result['endDate'] = $criteria['withdrawal_search[endDate'];
        } else {
            $result['endDate'] = $this->translator->trans('keyword.unspecified');
        }
        if ($criteria['withdrawal_search[statusCount'] != '') {
            $result['statusCount'] = $this->translator->trans('status.'.$criteria['withdrawal_search[statusCount']);
        } else {
            $result['statusCount'] = $this->translator->trans('label.all');
        }
        if (isset($criteria['withdrawal_search[envelope']) && count($criteria['withdrawal_search[envelope']) == 1) {
            if (isset($criteria['withdrawal_search[envelope']['0'])) {
                $result['envelope'] = $this->translator->trans('keyword.with');
            } else {
                $result['envelope'] = $this->translator->trans('keyword.without');
            }
        } else {
            $result['envelope'] = $this->translator->trans('keyword.with').' '.$this->translator->trans(
                    'keyword.and'
                ).' '.$this->translator->trans('keyword.without');
        }

        return $result;
    }
}
