<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\Expense;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class ExpensesSyncService extends AbstractSyncService
{

    /**
     * @param $expenses
     * @param $quickCode
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($expenses, Restaurant $restaurant)
    {
        $result = [];
        foreach ($expenses as $expense) {
            $expense = json_decode($expense, true);
            $newExpense = new Expense();
            try {
                $responsible = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->getEmployeeByGlobalId($expense['responsible']);
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown employee code ('.$expense['responsible'].') '.$e->getMessage(),
                    ['ExpenseService', 'deserialize', 'UknownEmployee']
                );
                throw $e;
            }
            $existantExpense = $this->em->getRepository('AppBundle:Financial\Expense')->findOneBy(
                array(
                    'originalID' => $expense['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (is_null($existantExpense)) {
                $newExpense
                    ->setOriginalID($expense['id'])
                    ->setResponsible($responsible)
                    ->setDateExpense($expense['dateExpense'], 'Y-m-d')
                    ->setComment($expense['comment'])
                    ->setTva($expense['tva'])
                    ->setAmount($expense['amount'])
                    ->setGroupExpense($expense['groupExpense'])
                    ->setSousGroup($expense['sousGroup'])
                    ->setReference($expense['reference'])
                    ->setCreatedAt($expense['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($expense['updatedAt'], 'Y-m-d H:i:s')
                    ->setOriginRestaurant($restaurant);
                $result[] = $newExpense;
            }
        }

        return $result;
    }

    public function importExpenses($expensesData, Restaurant $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $expenses = $this->deserialize($expensesData, $restaurant);
            foreach ($expenses as $expense) {
                $this->em->persist($expense);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::DEPOSITS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing expenses, import was rollback : '.$e->getMessage(),
                ['ExpenseService', 'ImportExpenses']
            );
            throw new \Exception($e);
        }
    }
}
