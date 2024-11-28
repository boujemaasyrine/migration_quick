<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxCheckQuick;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class CashboxCounts extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::CASHBOX_COUNTS;
    }

    /**
     * @param CashboxCount[] $cashboxCounts
     * @return array
     */
    public function serialize($cashboxCounts)
    {
        $data = [];
        //Create the data
        foreach ($cashboxCounts as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $oData = array(
                'id' => $cashboxCount->getId(),
                'date' => $cashboxCount->getDate('Y-m-d H:i:s'),
                'owner' => $cashboxCount->getOwner()->getGlobalEmployeeID(),
                'cashier' => $cashboxCount->getCashier()->getGlobalEmployeeID(),
                'realCaCounted' => $cashboxCount->getRealCaCounted(),
                'theoricalCa' => $cashboxCount->getTheoricalCa(),
                'numberCancels' => $cashboxCount->getNumberCancels(),
                'totalCancels' => $cashboxCount->getTotalCancels(),
                'numberCorrections' => $cashboxCount->getNumberCorrections(),
                'totalCorrections' => $cashboxCount->getTotalCorrections(),
                'numberAbondons' => $cashboxCount->getNumberAbondons(),
                'totalAbondons' => $cashboxCount->getTotalAbondons(),
                'eft' => $cashboxCount->isEft(),
                'counted' => $cashboxCount->isCounted(),
                'createdAt' => $cashboxCount->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $cashboxCount->getUpdatedAt('Y-m-d H:i:s'),
            );

            // abondoned tickets
            $abondonedTickets = [];
            foreach ($cashboxCount->getAbondonedTickets() as $tl) {
                /**
                 * @var Ticket $tl
                 */
                $abondonedTickets[] = [
                    'id' => $tl->getId(),
                ];
            }
            $oData['abondonedTickets'] = $abondonedTickets;

            // withdrawals
            $withdrawals = [];
            foreach ($cashboxCount->getWithdrawals() as $withdrawal) {
                /**
                 * @var Withdrawal $withdrawal
                 */
                $withdrawals[] = [
                    'id' => $withdrawal->getId(),
                ];
            }
            $oData['withdrawals'] = $withdrawals;

            // cashContainer
            $cashContainer = [
                'totalAmount' => $cashboxCount->getCashContainer()->getTotalAmount(),
                'billOf100' => $cashboxCount->getCashContainer()->getBillOf100(),
                'billOf50' => $cashboxCount->getCashContainer()->getBillOf50(),
                'billOf20' => $cashboxCount->getCashContainer()->getBillOf20(),
                'billOf10' => $cashboxCount->getCashContainer()->getBillOf10(),
                'billOf5' => $cashboxCount->getCashContainer()->getBillOf5(),
                'change' => $cashboxCount->getCashContainer()->getChange(),
                'allAmount' => $cashboxCount->getCashContainer()->isAllAmount(),
            ];
            foreach ($cashboxCount->getCashContainer()->getTicketPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                $cashContainer['ticketPayments'][] = $payment->getId();
            }
            $oData['cashContainer'] = $cashContainer;

            // checkQuickContainer
            $checkQuickContainer = [];
            foreach ($cashboxCount->getCheckQuickContainer()->getCheckQuickCounts() as $checkQuickCount) {
                /**
                 * @var CashboxCheckQuick $checkQuickCount
                 */
                $checkQuickContainer['checkQuickCounts'][] = [
                    'qty' => $checkQuickCount->getQty(),
                    'unitValue' => $checkQuickCount->getUnitValue(),
                ];
            }
            foreach ($cashboxCount->getCheckQuickContainer()->getTicketPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                $checkQuickContainer['ticketPayments'][] = $payment->getId();
            }
            $oData['checkQuickContainer'] = $checkQuickContainer;

            // checkRestaurantContainer
            $checkRestaurantContainer = [];
            foreach ($cashboxCount->getCheckRestaurantContainer()->getTicketRestaurantCounts(
            ) as $ticketRestaurantCount) {
                /**
                 * @var CashboxTicketRestaurant $ticketRestaurantCount
                 */
                $checkRestaurantContainer['ticketRestaurantCounts'][] = [
                    'qty' => $ticketRestaurantCount->getQty(),
                    'unitValue' => $ticketRestaurantCount->getUnitValue(),
                    'ticketName' => $ticketRestaurantCount->getTicketName(),
                    'idPayment' => $ticketRestaurantCount->getIdPayment(),
                    'electronic' => $ticketRestaurantCount->isElectronic(),
                ];
            }
            foreach ($cashboxCount->getCheckRestaurantContainer()->getTicketPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                $checkRestaurantContainer['ticketPayments'][] = $payment->getId();
            }
            $oData['checkRestaurantContainer'] = $checkRestaurantContainer;

            // bankCardContainer
            $bankCardContainer = [];
            foreach ($cashboxCount->getBankCardContainer()->getBankCardCounts() as $bankCardCount) {
                /**
                 * @var CashboxBankCard $bankCardCount
                 */
                $bankCardContainer['bankCardCounts'][] = [
                    'amount' => $bankCardCount->getAmount(),
                    'cardName' => $bankCardCount->getCardName(),
                    'idPayment' => $bankCardCount->getIdPayment(),
                ];
            }
            foreach ($cashboxCount->getBankCardContainer()->getTicketPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                $bankCardContainer['ticketPayments'][] = $payment->getId();
            }
            $oData['bankCardContainer'] = $bankCardContainer;

            // discountContainer
            $discountContainer = [];
            foreach ($cashboxCount->getDiscountContainer()->getTicketLines() as $ticketLine) {
                /**
                 * @var TicketLine $ticketLine
                 */
                $discountContainer['ticketLines'][] = $ticketLine->getId();
            }
            $oData['discountContainer'] = $discountContainer;

            // mealTicketContainer
            $mealTicketContainer = [];
            foreach ($cashboxCount->getMealTicketContainer()->getTicketPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                $mealTicketContainer['ticketPayments'][] = $payment->getId();
            }
            $oData['mealTicketContainer'] = $mealTicketContainer;

            // foreignCurrencyContainer
            $foreignCurrencyContainer = [];
            foreach ($cashboxCount->getForeignCurrencyContainer()->getForeignCurrencyCounts(
            ) as $foreignCurrencyCount) {
                /**
                 * @var CashboxForeignCurrency $foreignCurrencyCount
                 */
                $foreignCurrencyContainer['foreignCurrencyCounts'][] = [
                    'amount' => $foreignCurrencyCount->getAmount(),
                    'exchangeRate' => $foreignCurrencyCount->getExchangeRate(),
                    'foreignCurrencyLabel' => $foreignCurrencyCount->getForeignCurrencyLabel(),
                ];
            }
            $oData['foreignCurrencyContainer'] = $foreignCurrencyContainer;

            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    /**
     * before upload cashbox ensure that :
     * - related withdrawals are synchronized (withdrawals)
     * - related tickets are synchronized (tickets)
     * - related ticketPayment are synchronized (tickets)
     *
     * @param  null $idCmd
     * @param  bool $rawResponse
     * @return bool|mixed|null
     */
    public function uploadCashboxCounts($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $cashboxCounts = $this->em->getRepository("Financial:CashboxCount")->createQueryBuilder('cashboxCount')
            ->where("cashboxCount.synchronized = false")
            ->orWhere("cashboxCount.synchronized is NULL")
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
        $success = null;
        $response = null;
        if (count($cashboxCounts)) {
            $data = $this->serialize($cashboxCounts);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);
            if (!is_null($response) && count($response['error']) === 0) {
                $events = Utilities::removeEvents(CashboxCount::class, $this->em);
                foreach ($cashboxCounts as $cashboxCount) {
                    /**
                     * @var CashboxCount $cashboxCount
                     */
                    $cashboxCount->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(CashboxCount::class, $this->em, $events);
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
        return $this->uploadCashboxCounts($idCmd);
    }
}
