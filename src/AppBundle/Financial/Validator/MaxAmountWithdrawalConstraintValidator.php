<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/04/2016
 * Time: 15:10
 */

namespace AppBundle\Financial\Validator;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxAmountWithdrawalConstraintValidator extends ConstraintValidator
{

    private $em;
    private $administrativeClosingService;
    private $restaurantService;
    private $translator;

    public function __construct(
        EntityManager $manager,
        Translator $translator,
        AdministrativeClosingService $administrativeClosingService,
        RestaurantService $restaurantService
    ) {
        $this->em = $manager;
        $this->translator = $translator;
        $this->administrativeClosingService = $administrativeClosingService;
        $this->restaurantService=$restaurantService;
    }

    /**
     * Checks if the passed value is valid.
     * @param Withdrawal $withdrawal
     *
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($withdrawal, Constraint $constraint)
    {
        //$date = date('Y-m-d');
        $currentRestaurant=$this->restaurantService->getCurrentRestaurant();
        $date = $this->administrativeClosingService->getLastWorkingEndDate();
        $val = str_replace(',', '.', $withdrawal->getAmountWithdrawal());

        $cashier = $withdrawal->getMember() ? $withdrawal->getMember()->getWyndId() : null;
        $quickCashier = $withdrawal->getMember() ? $withdrawal->getMember() : null;

        $total = $this->em->getRepository(TicketPayment::class)->getTotalPaymentPerDay($date, $currentRestaurant, $cashier);
        $total = ($total) ? $total : 0;

        $id = ($withdrawal->getId()) ? $withdrawal->getId() : null;
        $totalAmountPendingWithdrawal =
            $this->em->getRepository(Withdrawal::class)->getTotalPendingAmount($currentRestaurant,$id, $quickCashier);
        $totalAmountPendingWithdrawal = ($totalAmountPendingWithdrawal) ? $totalAmountPendingWithdrawal : 0;
        if (($val + $totalAmountPendingWithdrawal) > $total) {
            $this->context->buildViolation(
                $this->translator->trans("fund_management.withdrawal.entry.validation_amount_failed")
            )
                ->atPath('amountWithdrawal')
                ->addViolation();
        }
    }
}
