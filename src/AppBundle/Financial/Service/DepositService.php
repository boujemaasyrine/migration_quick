<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Financial\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\ChestSmallChest;
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\Translator;

class DepositService
{

    private $em;
    private $translator;
    private $container;
    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        Translator $translator,
        Container $container,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->container = $container;
        $this->restaurantService = $restaurantService;
    }


    /**
     * @param Deposit    $deposit
     * @param Envelope[] $envelopes
     * @throws \Exception
     */
    public function saveDepositEnvelope(Deposit $deposit, $envelopes)
    {
        try {
            $this->em->beginTransaction();

            foreach ($envelopes as $envelope) {
                $deposit->addEnvelope($envelope);
                $envelope->setDeposit($deposit);
                $envelope->setStatus(Envelope::VERSED);
            }
            $deposit->setDestination(Deposit::DESTINATION_BANK);
            $deposit->setSource(Deposit::SOURCE_TIRELIRE);
            $deposit->setReference($this->getLastRefDeposit() + 1);
            $deposit->setOriginRestaurant($this->restaurantService->getCurrentRestaurant());

            $this->em->persist($deposit);
            $this->em->flush();

            $this->container->get('expense.service')->saveExpenseDeposit($deposit);

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception($e);
        }
    }

    /**
     * @param Deposit $deposit
     * @throws \Exception
     */
    public function saveDepositElectronic(Deposit $deposit,\DateTime $date=null)
    {
        try {
            $this->em->beginTransaction();

            if ($deposit->getType() == Deposit::TYPE_BANK_CARD) {
                $deposit->setType(Deposit::TYPE_BANK_CARD);
            } elseif ($deposit->getType() == Deposit::TYPE_E_TICKET) {
                $deposit->setType(Deposit::TYPE_E_TICKET);
            }

            $deposit->setReference($this->getLastRefDeposit() + 1)
                ->setDestination(Deposit::DESTINATION_BANK)
                ->setSource(Deposit::SOURCE_SMALL_CHEST);

            $this->em->persist($deposit);
            $this->em->flush();

            $this->container->get('expense.service')->saveExpenseDeposit($deposit,$date);

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw new \Exception($e);
        }
    }

    /**
     * Type: Deposit::TYPE_BANK_CARD|Deposit::TYPE_E_TICKET
     *
     * @param  ChestSmallChest $smallChest
     * @param  $type
     * @return array
     * @throws \Exception
     */
    public function depositElectronic(ChestSmallChest $smallChest, $type)
    {
        $values = [];
        $success = [];

        if ($type == Deposit::TYPE_BANK_CARD) {
            $values = $this->container->get('paremeter.service')->getBankCardValues();
        } elseif ($type == Deposit::TYPE_E_TICKET) {
            $values = $this->container->get('paremeter.service')->getTicketRestaurantValues(null,true);
        }
        foreach ($values as $value) {
            /**
             * @var Parameter $value
             */
            $idPayment = $value->getValue()['id'];

            $total = 0;
            if ($type == Deposit::TYPE_BANK_CARD) {
                $total = $smallChest->calculateBankCardRealTotal($idPayment);
            } elseif ($type == Deposit::TYPE_E_TICKET) {
                $total = $smallChest->calculateCheckRestaurantRealTotal($idPayment);
            }

            if ($total > 0) {
                $deposit = new Deposit();
                $deposit->setType($type)
                    ->setTotalAmount($total)
                    ->setSousType($value->getValue()['id'])
                    ->setOwner($smallChest->getChestCount()->getOwner());

                $this->container->get('deposit.service')->saveDepositElectronic($deposit);

                $success[$value->getValue()['id']] = $total;
            }
        }

        return $success;
    }

    /**
     * Type: Deposit::TYPE_BANK_CARD|Deposit::TYPE_E_TICKET
     *
     * @param  ChestSmallChest $smallChest
     * @param  $type
     * @return array
     * @throws \Exception
     */
    public function getDepositElectronic(ChestSmallChest $smallChest, $type)
    {
        $values = [];
        $success = [];

        if ($type == Deposit::TYPE_BANK_CARD) {
            $values = $this->container->get('paremeter.service')->getBankCardValues();
        } elseif ($type == Deposit::TYPE_E_TICKET) {
            $values = $this->container->get('paremeter.service')->getTicketRestaurantValues(null,true);
        }
        foreach ($values as $value) {
            /**
             * @var Parameter $value
             */
            $idPayment = $value->getValue()['id'];

            $total = 0;
            if ($type == Deposit::TYPE_BANK_CARD) {
                $total = $smallChest->calculateBankCardRealTotal($idPayment);
            } elseif ($type == Deposit::TYPE_E_TICKET) {
                $total = $smallChest->calculateCheckRestaurantRealTotal($idPayment);
            }

            if ($total > 0) {
                $success[$value->getValue()['id']] = $total;
            }
        }

        return $success;
    }

    /**
     * @return int
     */
    public function getLastRefDeposit()
    {
        $lastDeposit = $this->em->getRepository('Financial:Deposit')->createQueryBuilder('d')
            ->orderBy('d.reference', "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        if (!isset($lastDeposit['0'])) {
            return 0;
        } else {
            return $lastDeposit['0']->getReference();
        }
    }

    public function getPeriod($id)
    {
        $period = $this->em->getRepository('Financial:Deposit')->getPeriod($id);
        if (isset($period[0])) {
            return $period[0];
        } else {
            return null;
        }
    }

    public function getNotVersedTicketTypes()
    {
        $qb = $this->em->getRepository('Financial:Envelope')->createQueryBuilder('e');
        $qb->select('e.sousType, COUNT(e.sousType) as countEnvelope')
            ->where('e.type = :type')
            ->setParameter('type', Envelope::TYPE_TICKET)
            ->andWhere('e.status = :status')
            ->andWhere('e.originRestaurant = :restaurant')
            ->setParameter('restaurant', $this->restaurantService->getCurrentRestaurant())
            ->setParameter('status', Envelope::NOT_VERSED)
            ->groupBy('e.sousType');
        $results = $qb->getQuery()->getResult();
        $return = [];
        foreach ($results as $result) {
            $return[$result['sousType']] = $this->container->get('paremeter.service')->getTicketRestaurantLabel(
                $result['sousType']
            ).' ('.$result['countEnvelope'].')';
        }

        return $return;
    }






    public function getDepositElectronicV2($chestCount,$type)
    {
        $values = [];

        $success = [];

        $cashboxes = $this->em->getRepository('Financial:CashboxCount')->findBy([
            'smallChest' => null,'originRestaurant'=>$this->restaurantService->getCurrentRestaurant()
        ]);


        if ($type == Deposit::TYPE_BANK_CARD) {
            $values = $this->container->get('paremeter.service')->getBankCardValues();
        } elseif ($type == Deposit::TYPE_E_TICKET) {
            $values = $this->container->get('paremeter.service')->getTicketRestaurantValues(null,true);
        }
        foreach ($values as $value) {
            /**
             * @var Parameter $value
             */
            $idPayment = $value->getValue()['id'];

            $total = 0;

            if ($type == Deposit::TYPE_BANK_CARD) {
                $total = $this->calculateBankCardTheoricalTotal($chestCount,$cashboxes,$idPayment);
            } elseif ($type == Deposit::TYPE_E_TICKET) {
                $total = $this->calculateCheckRestaurantTheoricalTotal($chestCount,$cashboxes,$idPayment);
            }

            if ($total > 0) {
                $success[$value->getValue()['id']] = $total;
            }


        }

        return $success;



    }

    public function depositElectronicV2($chestCount,$type,$closingDate)
    {

        $values = [];
        $success = [];

        $cashboxes = $this->em->getRepository('Financial:CashboxCount')->findBy([
            'smallChest' => null,'originRestaurant'=>$this->restaurantService->getCurrentRestaurant()
        ]);

        if ($type == Deposit::TYPE_BANK_CARD) {
            $values = $this->container->get('paremeter.service')->getBankCardValues();
        } elseif ($type == Deposit::TYPE_E_TICKET) {
            $values = $this->container->get('paremeter.service')->getTicketRestaurantValues(null,true);
        }

        foreach ($values as $value) {
            /**
             * @var Parameter $value
             */
            $idPayment = $value->getValue()['id'];

            $total = 0;

            if ($type == Deposit::TYPE_BANK_CARD) {
                $total = $this->calculateBankCardTheoricalTotal($chestCount,$cashboxes,$idPayment);
            } elseif ($type == Deposit::TYPE_E_TICKET) {
                $total = $this->calculateCheckRestaurantTheoricalTotal($chestCount,$cashboxes,$idPayment);
            }

            if ($total > 0) {

                $deposit=new Deposit();
                $deposit->setType($type)
                    ->setTotalAmount($total)
                    ->setSousType($value->getValue()['id'])
                    //set the user performing the closing as owner for the deposit
                    ->setOwner($this->container->get('security.token_storage')->getToken()->getUser())
                    ->setOriginRestaurant($this->restaurantService->getCurrentRestaurant());

                $this->saveDepositElectronic($deposit,$closingDate);

                $success[$value->getValue()['id']] = $total;

            }

        }



        return $success;


    }


    public function calculateBankCardTheoricalTotal($chestCount,$cashboxes,$paymentId)
    {
        $total = 0.0;

        if(isset($chestCount)&& !$chestCount->isClosure()){

            /**
             * @var ChestCount $chestCount
             */
            $smallChest=$chestCount->getSmallChest();

            $total=$smallChest->calculateBankCardRealTotal($paymentId);
        }




        foreach ($cashboxes as $cashboxCount) {
            /** @var CashboxCount $cashboxCount */
            $total += $cashboxCount->getBankCardContainer() ?
                $cashboxCount->getBankCardContainer()->calculateBankCardTotal($paymentId) : 0;
        }
        return $total;

    }

    public function calculateCheckRestaurantTheoricalTotal($chestCount,$cashboxes,$paymentId)
    {
        $total = 0.0;


        if(isset($chestCount)&& !$chestCount->isClosure()){

            /**
             * @var ChestCount $chestCount
             */
            $smallChest=$chestCount->getSmallChest();

            $total=$smallChest->calculateCheckRestaurantRealTotal($paymentId);

        }



        foreach ($cashboxes as $cashboxCount) {
            /** @var CashboxCount $cashboxCount */

            $total += $cashboxCount->getCheckRestaurantContainer() ?
                $cashboxCount->getCheckRestaurantContainer()->calculateRealTotalAmountId(true, $paymentId) : 0;

        }

        return $total;

    }



}
