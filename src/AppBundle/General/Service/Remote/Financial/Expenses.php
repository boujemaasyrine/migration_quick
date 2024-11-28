<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\Expense;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class Expenses extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::EXPENSES;
    }

    /**
     * @param Expense[] $expenses
     */
    public function serialize($expenses)
    {
        $data = [];
        //Create the data
        foreach ($expenses as $expense) {
            /**
             * @var Expense $expense
             */
            $oData = array(
                'id' => $expense->getId(),
                'responsible' => $expense->getResponsible()->getGlobalEmployeeID(),
                'dateExpense' => $expense->getDateExpense('Y-m-d'),
                'comment' => $expense->getComment(),
                'tva' => $expense->getTva(),
                'amount' => $expense->getAmount(),
                'groupExpense' => $expense->getGroupExpense(),
                'sousGroup' => $expense->getSousGroup(),
                'reference' => $expense->getReference(),
                'createdAt' => $expense->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $expense->getUpdatedAt('Y-m-d H:i:s'),
            );

            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    public function uploadExpenses($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $expenses = $this->em->getRepository("Financial:Expense")->createQueryBuilder('expense')
            ->where("expense.synchronized = false")
            ->orWhere("expense.synchronized is NULL")
            ->getQuery()
            ->getResult();
        $success = null;
        $response = null;
        if (count($expenses)) {
            $data = $this->serialize($expenses);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);
            if (!is_null($response) && count($response['error']) === 0) {
                $events = Utilities::removeEvents(Expense::class, $this->em);
                foreach ($expenses as $expense) {
                    /**
                     * @var Expense $expense
                     */
                    $expense->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(Expense::class, $this->em, $events);
                $this->uploadFinishWithSuccess();
                $success = true;
            } else {
                $this->uploadFinishWithFail();
                $success = false;
            }
        } else {
            $success = true;
        }

        if ($rawResponse) {
            return $response;
        } else {
            return $success;
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
        return $this->uploadExpenses($idCmd);
    }
}
