<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxCheckQuick;
use AppBundle\Financial\Entity\CashboxCheckQuickContainer;
use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxDiscountContainer;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use AppBundle\Financial\Entity\CashboxMealTicketContainer;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class CashboxCountsSyncService extends AbstractSyncService
{

    /**
     * @param $cashboxCounts
     * @param Restaurant    $restaurant
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($cashboxCounts, $restaurant)
    {
        $this->restaurant = $restaurant;
        $result = [];
        foreach ($cashboxCounts as $cashboxCount) {
            $cashboxCount = json_decode($cashboxCount, true);
            $newCashboxCount = new CashboxCount();
            try {
                $owner = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->findOneBy(
                        array(
                            'globalEmployeeID' => $cashboxCount['owner'],
                        )
                    );

                if (!$owner) {
                    throw new NoResultException();
                }
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown owner code ('.$cashboxCount['owner'].') '.$e->getMessage(),
                    ['CashboxCountservice', 'deserialize', 'UknownProduct']
                );
                throw new \Exception("Employee : ".$cashboxCount['owner']." not found.");
            }

            try {
                $cashier = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->findOneBy(
                        array(
                            'globalEmployeeID' => $cashboxCount['cashier'],
                        )
                    );

                if (!$cashier) {
                    throw new NoResultException();
                }
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Unknown cashier code ('.$cashboxCount['owner'].') '.$e->getMessage(),
                    ['CashboxCountservice', 'deserialize', 'UknownProduct']
                );
                throw new \Exception("Employee : ".$cashboxCount['owner']." not found.");
            }
            $existantCashboxCount = $this->em->getRepository('AppBundle:Financial\CashboxCount')->findOneBy(
                array(
                    'originalID' => $cashboxCount['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (!is_null($existantCashboxCount)) {
                /**
                 * @var CashboxCount $existantCashboxCount
                 */
                $this->em->remove($existantCashboxCount);
            }
            $newCashboxCount
                ->setOriginalID($cashboxCount['id'])
                ->setDate($cashboxCount['date'], 'Y-m-d H:i:s')
                ->setOwner($owner)
                ->setCashier($cashier)
                ->setRealCaCounted($cashboxCount['realCaCounted'])
                ->setTheoricalCa($cashboxCount['theoricalCa'])
                ->setNumberCancels($cashboxCount['numberCancels'])
                ->setTotalCancels($cashboxCount['totalCancels'])
                ->setNumberCorrections($cashboxCount['numberCorrections'])
                ->setTotalCorrections($cashboxCount['totalCorrections'])
                ->setNumberAbondons($cashboxCount['numberAbondons'])
                ->setTotalAbondons($cashboxCount['totalAbondons'])
                ->setEft($cashboxCount['eft'])
                ->setCounted($cashboxCount['counted'])
                ->setCreatedAt($cashboxCount['createdAt'], 'Y-m-d H:i:s')
                ->setUpdatedAt($cashboxCount['updatedAt'], 'Y-m-d H:i:s')
                ->setOriginRestaurant($restaurant);
            if (isset($cashboxCount['abondonedTickets'])) {
                foreach ($cashboxCount['abondonedTickets'] as $abondonedTicket) {
                    try {
                        $ticket = $this->em->getRepository('AppBundle:Financial\Ticket')
                            ->findOneBy(
                                array(
                                    'originalID' => $abondonedTicket['id'],
                                    'originRestaurant' => $restaurant,
                                )
                            );

                        if (!$ticket) {
                            throw new NoResultException();
                        }

                        $newCashboxCount->addAbondonedTicket($ticket);
                    } catch (NoResultException $e) {
                        $this->logger->addAlert(
                            'Unknown ticket imported from Quick bo ('.$restaurant->getCode(
                            ).') with id : '.$abondonedTicket['id'],
                            ['CashboxCountservice', 'deserialize', 'UknownTicket']
                        );
                        throw new \Exception("Product : ".$abondonedTicket['id']." not found.");
                    }
                }
            }

            if (isset($cashboxCount['withdrawals'])) {
                foreach ($cashboxCount['withdrawals'] as $withdrawal) {
                    try {
                        $withdrawal = $this->em->getRepository('AppBundle:Financial\Withdrawal')
                            ->findOneBy(
                                array(
                                    'originalID' => $withdrawal['id'],
                                    'originRestaurant' => $restaurant,
                                )
                            );

                        if (!$withdrawal) {
                            throw new NoResultException();
                        }

                        $newCashboxCount->addWithdrawal($withdrawal);
                    } catch (NoResultException $e) {
                        $this->logger->addAlert(
                            'Unknown withdrawal imported from Quick bo ('.$restaurant->getCode(
                            ).') with id : '.$withdrawal['id'],
                            ['CashboxCountservice', 'deserialize', 'UknownWithdrawal']
                        );
                        throw new \Exception("Product : ".$withdrawal['id']." not found.");
                    }
                }
            }
            $cashReal = $this->deserializeCashContainer($cashboxCount);
            $newCashboxCount->setCashContainer($cashReal);

            $cashBoxCheckQuick = $this->deserializeCheckQuickContainer($cashboxCount);
            $newCashboxCount->setCheckQuickContainer($cashBoxCheckQuick);

            $cashCheckRestaurant = $this->deserializeCheckRestaurantContainer($cashboxCount);
            $newCashboxCount->setCheckRestaurantContainer($cashCheckRestaurant);

            $cashboxBank = $this->deserializeBankCardContainer($cashboxCount);
            $newCashboxCount->setBankCardContainer($cashboxBank);

            $xx = $this->deserializeDiscountContainer($cashboxCount);
            $newCashboxCount->setDiscountContainer($xx);

            $yy = $this->deserializeMealTicketContainer($cashboxCount);
            $newCashboxCount->setMealTicketContainer($yy);

            $zz = $this->deserializeForeignCurrencyContainer($cashboxCount);
            $newCashboxCount->setForeignCurrencyContainer($zz);

            $result[] = $newCashboxCount;
        }

        return $result;
    }

    public function importCashboxCounts($cashboxCountsData, $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $cashboxCounts = $this->deserialize($cashboxCountsData, $restaurant);
            foreach ($cashboxCounts as $cashboxCount) {
                $this->em->persist($cashboxCount);
            }
            $this->em->flush();
            $this->em->commit();
            $this->logger->addInfo("SUCCESSSSSS !! ");
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::CASHBOX_COUNTS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occurred when importing cashbox counts, import was rollback : '.$e->getMessage(),
                ['CashBoxCountService', 'ImportCashBoxCounts']
            );
            throw new \Exception($e);
        }
    }

    public function deserializeCashContainer($cashboxCount)
    {
        $newCashContainer = new CashboxRealCashContainer();
        $cashContainer = $cashboxCount['cashContainer'];
        if (isset($cashContainer['ticketPayments']) && count($cashContainer['ticketPayments']) > 0) {
            foreach ($cashContainer['ticketPayments'] as $ticketPaymentId) {
                try {
                    $ticketPayment = $this->em->getRepository('AppBundle:Financial\TicketPayment')
                        ->createQueryBuilder('ticketPayment')
                        ->leftJoin('ticketPayment.ticket', 'ticket')
                        ->select('ticketPayment')
                        ->where('ticket.originRestaurant = :restaurant')
                        ->setParameter('restaurant', $this->restaurant)
                        ->andWhere('ticketPayment.originalID = :id')
                        ->setParameter('id', $ticketPaymentId)
                        ->getQuery()
                        ->setMaxResults(1)
                        ->getSingleResult();
                    $ticketPayment->setRealCashContainer($newCashContainer);
                    $newCashContainer->addTicketPayment($ticketPayment);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Unknown ticket payment id ('.$ticketPaymentId.') '.$e->getMessage(),
                        ['CashboxCountservice', 'deserialize', 'UknownTicketPayment']
                    );
                    throw new \Exception(
                        "Ticket : ".$ticketPaymentId.' for restaurant code : '.$this->restaurant->getCode(
                        )." not found."
                    );
                }
            }
        }
        $newCashContainer->setAllAmount($cashContainer['allAmount'])
            ->setTotalAmount($cashContainer['totalAmount'])
            ->setBillOf100($cashContainer['billOf100'])
            ->setBillOf50($cashContainer['billOf50'])
            ->setBillOf20($cashContainer['billOf20'])
            ->setBillOf10($cashContainer['billOf10'])
            ->setBillOf5($cashContainer['billOf5'])
            ->setChange($cashContainer['change']);

        return $newCashContainer;
    }

    public function deserializeCheckQuickContainer($cashboxCount)
    {
        $newCheckQuickContainer = new CashboxCheckQuickContainer();
        $checkQuickContainer = $cashboxCount['checkQuickContainer'];
        // checkQuickContainer
        if (isset($checkQuickContainer['checkQuickCounts'])) {
            foreach ($checkQuickContainer['checkQuickCounts'] as $checkQuickCount) {
                $newCheckQuickCount = new CashboxCheckQuick();
                $newCheckQuickCount->setQty($checkQuickCount['qty'])
                    ->setUnitValue($checkQuickCount['unitValue']);
                $newCheckQuickContainer->addCheckQuickCount($newCheckQuickCount);
            }
        }

        if (isset($checkQuickContainer['ticketPayments']) && count($checkQuickContainer['ticketPayments']) > 0) {
            foreach ($checkQuickContainer['ticketPayments'] as $ticketPaymentId) {
                try {
                    $ticketPayment = $this->em->getRepository('AppBundle:Financial\TicketPayment')
                        ->getTicketPaymentByRestaurantByOriginalId($this->restaurant, $ticketPaymentId);
                    $newCheckQuickContainer->addTicketPayment($ticketPayment);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown ticket payment id ('.$ticketPaymentId.') '.$e->getMessage(),
                        ['CashboxCountservice', 'deserialize', 'UknownTicketPayment']
                    );
                    throw new \Exception(
                        "Ticket : ".$ticketPaymentId.' for restaurant code : '.$this->restaurant->getCode(
                        )." not found."
                    );
                }
            }
        }

        return $newCheckQuickContainer;
    }

    public function deserializeCheckRestaurantContainer($cashboxCount)
    {
        $newCheckRestaurantContainer = new CashboxCheckRestaurantContainer();
        $checkRestaurantContainer = $cashboxCount['checkRestaurantContainer'];
        // checkRestaurantContainer
        if (isset($checkRestaurantContainer['ticketRestaurantCounts'])) {
            foreach ($checkRestaurantContainer['ticketRestaurantCounts'] as $ticketRestaurantCount) {
                $newTicketRestaurantCount = new CashboxTicketRestaurant();
                $newTicketRestaurantCount->setQty($ticketRestaurantCount['qty'])
                    ->setUnitValue($ticketRestaurantCount['unitValue'])
                    ->setTicketName($ticketRestaurantCount['ticketName'])
                    ->setIdPayment($ticketRestaurantCount['idPayment'])
                    ->setElectronic($ticketRestaurantCount['electronic']);
                $newCheckRestaurantContainer->addTicketRestaurantCount($newTicketRestaurantCount);
            }
        }

        if (isset($checkRestaurantContainer['ticketPayments']) && count(
            $checkRestaurantContainer['ticketPayments']
        ) > 0) {
            foreach ($checkRestaurantContainer['ticketPayments'] as $ticketPaymentId) {
                try {
                    $ticketPayment = $this->em->getRepository('AppBundle:Financial\TicketPayment')
                        ->getTicketPaymentByRestaurantByOriginalId($this->restaurant, $ticketPaymentId);
                    $newCheckRestaurantContainer->addTicketPayment($ticketPayment);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown ticket payment id ('.$ticketPaymentId.') '.$e->getMessage(),
                        ['CashboxCountservice', 'deserialize', 'UknownTicketPayment']
                    );
                    throw new \Exception(
                        "Ticket : ".$ticketPaymentId.' for restaurant code : '.$this->restaurant->getCode(
                        )." not found."
                    );
                }
            }
        }

        return $newCheckRestaurantContainer;
    }

    public function deserializeBankCardContainer($cashboxCount)
    {
        $newBankCardContainer = new CashboxBankCardContainer();
        $bankCardContainer = $cashboxCount['bankCardContainer'];
        // bankCardContainer
        if (isset($bankCardContainer['bankCardCounts'])) {
            foreach ($bankCardContainer['bankCardCounts'] as $bankCardCount) {
                $newBankCardCount = new CashboxBankCard();
                $newBankCardCount->setAmount($bankCardCount['amount'])
                    ->setCardName($bankCardCount['cardName'])
                    ->setIdPayment($bankCardCount['idPayment']);
                $newBankCardContainer->addBankCardCount($newBankCardCount);
            }
        }

        if (isset($bankCardContainer['ticketPayments']) && count($bankCardContainer['ticketPayments']) > 0) {
            foreach ($bankCardContainer['ticketPayments'] as $ticketPaymentId) {
                try {
                    $ticketPayment = $this->em->getRepository('AppBundle:Financial\TicketPayment')
                        ->getTicketPaymentByRestaurantByOriginalId($this->restaurant, $ticketPaymentId);
                    $newBankCardContainer->addTicketPayment($ticketPayment);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Unknown ticket payment id ('.$ticketPaymentId.') '.$e->getMessage(),
                        ['CashboxCountservice', 'deserialize', 'UknownTicketPayment']
                    );
                    throw new \Exception(
                        "Ticket : ".$ticketPaymentId.' for restaurant code : '.$this->restaurant->getCode(
                        )." not found."
                    );
                }
            }
        }

        return $newBankCardContainer;
    }

    public function deserializeDiscountContainer($cashboxCount)
    {
        $newDiscountContainer = new CashboxDiscountContainer();
        $discountContainer = $cashboxCount['discountContainer'];
        // discountContainer
        if (isset($discountContainer['ticketLines']) && count($discountContainer['ticketLines']) > 0) {
            foreach ($discountContainer['ticketLines'] as $ticketLineId) {
                try {
                    $ticketLine = $this->em->getRepository('AppBundle:Financial\TicketLine')
                        ->getTicketLineByRestaurantByOriginalId($this->restaurant, $ticketLineId);
                    $newDiscountContainer->addTicketLine($ticketLine);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown ticket line id ('.$ticketLineId.') '.$e->getMessage(),
                        ['CashboxCountservice', 'deserialize', 'UknownTicketLine']
                    );
                    throw new \Exception(
                        "Ticket line : ".$ticketLineId.' for restaurant code : '.$this->restaurant->getCode(
                        )." not found."
                    );
                }
            }
        }

        return $newDiscountContainer;
    }

    public function deserializeMealTicketContainer($cashboxCount)
    {
        $newMealTicketContainer = new CashboxMealTicketContainer();
        $mealTicketContainer = $cashboxCount['mealTicketContainer'];
        // mealTicketContainer
        if (isset($mealTicketContainer['ticketPayments']) && count($mealTicketContainer['ticketPayments']) > 0) {
            foreach ($mealTicketContainer['ticketPayments'] as $ticketPaymentId) {
                try {
                    $ticketPayment = $this->em->getRepository('AppBundle:Financial\TicketPayment')
                        ->getTicketPaymentByRestaurantByOriginalId($this->restaurant, $ticketPaymentId);
                    $newMealTicketContainer->addTicketPayment($ticketPayment);
                } catch (NoResultException $e) {
                    $this->logger->addAlert(
                        'Uknown ticket payment id ('.$ticketPaymentId.') '.$e->getMessage(),
                        ['CashboxCountservice', 'deserialize', 'UknownTicketPayment']
                    );
                    throw new \Exception(
                        "Ticket : ".$ticketPaymentId.' for restaurant code : '.$this->restaurant->getCode(
                        )." not found."
                    );
                }
            }
        }

        return $newMealTicketContainer;
    }

    public function deserializeForeignCurrencyContainer($cashboxCount)
    {
        $newForeignCurrencyContainer = new CashboxForeignCurrencyContainer();
        $foreignCurrencyContainer = $cashboxCount['foreignCurrencyContainer'];
        // foreignCurrencyContainer
        if (isset($foreignCurrencyContainer['foreignCurrencyCounts'])) {
            foreach ($foreignCurrencyContainer['foreignCurrencyCounts'] as $foreignCurrencyCount) {
                $newForeignCurrencyCount = new CashboxForeignCurrency();
                $newForeignCurrencyCount->setAmount($foreignCurrencyCount['amount'])
                    ->setExchangeRate($foreignCurrencyCount['exchangeRate'])
                    ->setForeignCurrencyLabel($foreignCurrencyCount['foreignCurrencyLabel']);
                $newForeignCurrencyContainer->addForeignCurrencyCount($newForeignCurrencyCount);
            }
        }

        return $newForeignCurrencyContainer;
    }
}
