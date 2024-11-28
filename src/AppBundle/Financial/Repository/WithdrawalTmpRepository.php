<?php

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Financial\Entity\WithdrawalTmp;
use AppBundle\Financial\Service\WithdrawalSynchronizationService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityRepository;

class WithdrawalTmpRepository extends EntityRepository
{

    /**
     * Création d'un nouveau prélèvement à partir des données importées(dans la table temporaire)
     * @param Restaurant $restaurant
     * @param $data
     * @return WithdrawalTmp
     */
    public function createWithdrawalTmp(Restaurant $restaurant, $data)
    {
        extract($data, EXTR_OVERWRITE);
        $newWTmp = new WithdrawalTmp();
        $newWTmp->setAmountWithdrawal(abs(${WithdrawalSynchronizationService::JSON_AMOUNT}));
        $member = $this->_em->getRepository(Employee::class)->getRestaurantEmployeeByWyndID($restaurant, ${WithdrawalSynchronizationService::JSON_EMPLOYEE_ID});
        if (is_object($member)) {
            $newWTmp->setMember($member);
        } else {
            return WithdrawalSynchronizationService::MEMBER_NOT_EXIST;
        }
        $responsible = $this->_em->getRepository(Employee::class)->getRestaurantEmployeeByWyndID($restaurant, ${WithdrawalSynchronizationService::JSON_MANEGER_ID});
        if (is_object($responsible)) {
            $newWTmp->setResponsible($responsible);
        } else {
            return WithdrawalSynchronizationService::RESPONSIBLE_NOT_EXIST;
        }
        $newWTmp->setOriginRestaurant($restaurant);
        $newWTmp->setTime(new \DateTime(${WithdrawalSynchronizationService::JSON_TIME}));
        $this->_em->persist($newWTmp);
        return WithdrawalSynchronizationService::WITHDRAWAL_CREATED;
    }

    /**
     * Vérifier si le prélèvement existe ou non
     * @param $restaurant
     * @param $wTmp
     * @return bool
     */
    public function isExist($restaurant, $wTmp)
    {
        return is_object($this->findOneBy(array('time' => new \DateTime($wTmp[WithdrawalSynchronizationService::JSON_TIME]),
            'responsible' => $this->_em->getRepository(Employee::class)->getRestaurantEmployeeByWyndID($restaurant, $wTmp[WithdrawalSynchronizationService::JSON_MANEGER_ID]),
            'originRestaurant' => $restaurant
        )));
    }

    /**
     * Retourne les prélèvements, suivant la filtre [endDate, startDate, status de validation].
     * @param Restaurant $restaurant
     * @param $responsable
     * @param $startDate
     * @param $endDate
     * @param null $validated
     * @return array
     */
    public function getWithdrawalsTmp(Restaurant $restaurant, $responsable=null , $startDate = null, $endDate = null, $validated = null)
    {
        $queryBuilder = $this->createQueryBuilder('wt');

        $queryBuilder
            ->where('wt.originRestaurant = :restaurant')
            ->setParameter('restaurant', $restaurant);

        if (!is_null($responsable)) {
            $queryBuilder->andWhere('wt.responsible = :responsable')
                ->setParameter('responsable', $responsable);
        }
        if (!is_null($startDate)) {
            $queryBuilder->andWhere('wt.time >= :from ')
                ->setParameter('from', $startDate);
        }
        if (!is_null($endDate)) {
            $queryBuilder->andWhere('wt.time >= :to ')
                ->setParameter('to', $endDate);
        }

        if (is_bool($validated)) {
            $queryBuilder->andWhere('wt.validated = :validated')
                ->setParameter('validated', $validated);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Cette fonction permet de valider un prélèvement.
     * Mettre l'attribue validate (true) et créer un nouveau prélèvement dans la table withdrawalTmp
     * Créer un nouveau prélèvement dans la table withdrawal
     * @param $id
     * @param Employee $user
     * @return Withdrawal|bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function validateWithdrawal($id, Employee $user)
    {
        /**
         * @var WithdrawalTmp $wTmp
         */
        $wTmp = $this->find($id);
        if (is_object($wTmp)) {
            if (!$wTmp->isValidated()) {
                /**
                 * @var Withdrawal $w
                 */
                $w = new Withdrawal();
                $w->setMember($wTmp->getMember());
                $w->setAmountWithdrawal($wTmp->getAmountWithdrawal());
                $w->setDate($wTmp->getTime());
                $w->setResponsible($user);
                $w->setValidatedBy($user);
                $w->setStatusCount(Withdrawal::NOT_COUNTED);
                $w->setOriginRestaurant($wTmp->getOriginRestaurant());
                $this->_em->persist($w);

                $wTmp->setValidated(true);
                $wTmp->setWithdrawal($w);
                $this->_em->persist($wTmp);

                $this->_em->flush();
                return $w;
            } else {
                return $wTmp->getWithdrawal();
            }
        }
        return false;
    }

    /**
     * Retourne la date de dernier prélèvement créé.
     * @param Restaurant $restaurant
     * @return bool
     */
    public function getLatestUpdateDateFromApi(Restaurant $restaurant)
    {
        $lwTmp = $this->findOneBy(array(
            'originRestaurant' => $restaurant),
            array('createdAt' => 'DESC'));

        return is_object($lwTmp) ? $lwTmp->getCreatedAt() : false;
    }

    /**
     * Vérifier si l'équipier avoir encore des prélèvement temporaire non-valide ou non
     * @param Restaurant $restaurant
     * @param $userID
     * @param $date
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function hasInvalidWithdrawalsTmp(Restaurant $restaurant, $userID, $date)
    {
        $date = $date->format('Y-m-d');
        $from = new \DateTime($date . " 00:00:00");
        $to = new \DateTime($date . " 23:59:59");
        $cashier = $this->_em->getRepository(Employee::class)->find($userID);
        $qb = $this->createQueryBuilder('wt');
        $qb->where('wt.originRestaurant = :restaurant')
            ->andWhere('wt.member = :cashier')
            ->andWhere('wt.time >= :from ')
            ->andWhere('wt.time <= :to ')
            ->andWhere('wt.validated = :validated ')
            ->setParameters(array('restaurant' => $restaurant, 'cashier' => $cashier, 'from' => $from, 'to' => $to, 'validated' => false));
        return count($qb->getQuery()->getResult()) > 0;
    }
}