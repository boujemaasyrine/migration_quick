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
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Expense;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\Translator;

class ExpenseService
{

    private $em;
    private $tokenStorage;
    private $translator;
    private $paramService;
    private $container;

    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        Translator $translator,
        ParameterService $paramService,
        Container $container
    ) {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->container = $container;
    }

    public function saveExpense(Expense $expense)
    {
        $restaurant = $this->container->get('restaurant.service')->getCurrentRestaurant();
        $expense->setGroupExpense(Expense::GROUP_OTHERS)
            ->SetResponsible($this->tokenStorage->getToken()->getUser())
            ->setTva(str_replace(',', '.', $expense->getTva()))
            ->setAmount(str_replace(',', '.', $expense->getAmount()))
            ->setReference($this->getLastRefExpense($restaurant) + 1)
            ->setOriginRestaurant($restaurant);

        $this->em->persist($expense);

        $this->em->flush();
    }

    public function saveExpenseDeposit(Deposit $deposit,\DateTime $date=null)
    {
        $restaurant = $this->container->get('restaurant.service')->getCurrentRestaurant();
        $expense = new Expense();
        if (is_null($date)) {
            $date = new \DateTime(date('Y-m-d'));
        }
        $sousType = $deposit->getSousType();

        if ($deposit->getType() == Deposit::TYPE_CASH) {
            $sousType = 'cash_payment';
            $expense->setGroupExpense(Expense::GROUP_BANK_CASH_PAYMENT);

        } elseif ($deposit->getType() == Deposit::TYPE_TICKET) {
            $expense->setGroupExpense(Expense::GROUP_BANK_RESTAURANT_PAYMENT);

        } elseif ($deposit->getType() == Deposit::TYPE_BANK_CARD) {
            $expense->setGroupExpense(Expense::GROUP_BANK_CARD_PAYMENT);

        } elseif ($deposit->getType() == Deposit::TYPE_E_TICKET) {
            $expense->setGroupExpense(Expense::GROUP_BANK_E_RESTAURANT_PAYMENT);
        }

        $expense->setDateExpense($date)
            ->setSousGroup($sousType)
            ->setReference($this->getLastRefExpense($restaurant) + 1)
            ->setAmount($deposit->getTotalAmount())
            ->setResponsible($deposit->getOwner())
            ->setOriginRestaurant($restaurant);

        $deposit->setExpense($expense);

        $this->em->persist($expense);
        $this->em->flush();
    }

    public function getExpenses($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $expenses = $this->em->getRepository("Financial:Expense")->getExpensesFiltredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeExpenses($expenses);
    }

    /**
     * @param Expense[] $expenses
     * @return array
     */
    public function serializeExpenses($expenses)
    {
        $result = [];
        foreach ($expenses as $e) {
            $result[] = $this->serializeExpense($e);
        }

        return $result;
    }

    /**
     * @param Expense $e
     * @return array
     */
    public function serializeExpense(Expense $e)
    {
        /**
         * @var Expense $e
         */
        switch ($e->getGroupExpense()) {
            case Expense::GROUP_BANK_E_RESTAURANT_PAYMENT:
                $label = $this->paramService->getTicketRestaurantLabel($e->getSousGroup());
                break;
            case Expense::GROUP_BANK_RESTAURANT_PAYMENT:
                $label = $this->paramService->getTicketRestaurantLabel($e->getSousGroup());
                break;
            case Expense::GROUP_BANK_CARD_PAYMENT:
                $label = $this->paramService->getBankCardLabel($e->getSousGroup());
                break;
            case Expense::GROUP_BANK_CASH_PAYMENT:
                $label = $this->paramService->getCashLabel($e->getSousGroup());
                break;
            case Expense::GROUP_OTHERS:
                $label = $this->paramService->getExpenseLabel($e->getSousGroup());
                break;
            case Expense::GROUP_ERROR_COUNT:
                $label = $this->paramService->getErrorCountLabel($e->getSousGroup());
                break;
            default:
                $label = $e->getSousGroup();
                break;
        }
        $result = array(
            'id' => $e->getId(),
            'reference' => $e->getReference(),
            'label' => $label,
            'group' => $this->translator->trans('expense.group.'.$e->getGroupExpense()),
            'owner' => $e->getResponsible()->getFirstName().' '.$e->getResponsible()->getLastName(),
            'amount' => $this->container->get('general.format')->floatFormat($e->getAmount()),
            'comment' => $e->getComment(),
            'tva' => $this->container->get('general.format')->floatFormat($e->getTva()),
            'date' => $e->getDateExpense(),
            'deposit' => $e->getDeposit(),
            'dataClass' => $this->translator->trans('label.group').' '.$this->translator->trans(
                    'expense.group.'.$e->getGroupExpense()
                ).
                ' '.$e->getDateExpense()->format('d/m/Y'),
            'dataValue' => $e->getGroupExpense().'/'.$e->getDateExpense()->format('d/m/Y'),
        );

        return $result;
    }

    public function getLabelsOfGroup($group)
    {
        $labels = array();

        switch ($group) {
            case Expense::GROUP_BANK_RESTAURANT_PAYMENT:
                $tickets = $this->paramService->getTicketRestaurantValues();
                foreach ($tickets as $ticket) {
                    /**
                     * @var Parameter $ticket
                     */
                    if (!$ticket->getValue()['electronic']) {
                        $labels[$ticket->getValue()['id']] = $ticket->getLabel();
                    }
                }
                break;
            case Expense::GROUP_BANK_E_RESTAURANT_PAYMENT:
                $tickets = $this->paramService->getTicketRestaurantValues();
                foreach ($tickets as $ticket) {
                    /**
                     * @var Parameter $ticket
                     */
                    if ($ticket->getValue()['electronic']) {
                        $labels[$ticket->getValue()['id']] = $ticket->getLabel();
                    }
                }
                break;
            case Expense::GROUP_BANK_CARD_PAYMENT:
                $cards = $this->paramService->getBankCardValues();
                foreach ($cards as $card) {
                    /**
                     * @var Parameter $card
                     */
                    $labels[$card->getValue()['id']] = $card->getLabel();
                }
                break;
            case Expense::GROUP_ERROR_COUNT:
                $errors = $this->paramService->getErrorCountLabels();
                foreach ($errors as $error) {
                    /**
                     * @var Parameter $error
                     */
                    $labels[$error->getValue()] = $error->getLabel();
                }
                break;
            case Expense::GROUP_OTHERS: {
                $labels = $this->paramService->getExpenseLabels();
            }
                break;
            case Expense::GROUP_BANK_CASH_PAYMENT: {
                $cashLabels = $this->paramService->getCashLabels();
                foreach ($cashLabels as $cash) {
                    /**
                     * @var Parameter $cash
                     */
                    $labels[$cash->getValue()] = $cash->getLabel();
                }
            }
        }

        return $labels;
    }

    public function getAllLabelsOfGroup()
    {
        $labels = $this->paramService->getExpenseLabels();

        $ticketRestaurant = $this->paramService->getTicketRestaurantValues();
        foreach ($ticketRestaurant as $ticket) {
            /**
             * @var Parameter $ticket
             */
            $labels[$ticket->getValue()['id']] = $ticket->getLabel();
        }

        $bankCard = $this->paramService->getBankCardValues();
        foreach ($bankCard as $card) {
            /**
             * @var Parameter $card
             */
            $labels[$card->getValue()['id']] = $card->getLabel();
        }

        $errors = $this->paramService->getErrorCountLabels();
        foreach ($errors as $error) {
            /**
             * @var Parameter $error
             */
            $labels[$error->getValue()] = $error->getLabel();
        }
        $cashLabels = $this->paramService->getCashLabels();
        foreach ($cashLabels as $cash) {
            /**
             * @var Parameter $cash
             */
            $labels[$cash->getValue()] = $cash->getLabel();
        }
        asort($labels);
        foreach ($labels as $key => $label){
            if (substr(trim($label), 0, 1) === '#') {
                $value=$labels[$key];
                unset($labels[$key]);
                $labels[$key]=$label;
            }
        }

        return $labels;
    }

    public function getLastRefExpense($restaurant)
    {
        /**
         * @var Expense[] $lastExpense
         */
        $lastExpense = $this->em->getRepository('Financial:Expense')->createQueryBuilder('e')
            ->where('e.originRestaurant=:restaurant')
            ->setParameter('restaurant', $restaurant)
            ->orderBy('e.reference', "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
        if (!isset($lastExpense['0'])) {
            return -1;
        } else {
            return $lastExpense['0']->getReference();
        }
    }


}
