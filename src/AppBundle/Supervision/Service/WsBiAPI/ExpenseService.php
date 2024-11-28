<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Service\WsBiAPI\RecipeService;
use AppBundle\Supervision\Model\DayIncome;
use AppBundle\Supervision\Service\ParameterService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class ExpenseService
{

    private $em;
    private $translator;
    private $recipeService;
    private $parameterService;

    public function __construct(
        EntityManager $entityManager,
        Translator $translator,
        RecipeService $recipeService,
        ParameterService $parameterService
    ) {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->recipeService = $recipeService;
        $this->parameterService = $parameterService;
    }

    public function getExpenses($criteria, $limit, $offset)
    {
        $expenses = $this->em->getRepository(Expense::class)->getExpensesBi(
            $criteria,
            $offset,
            $limit
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
            if(abs($e->getAmount()) >= 0.000001){
                $result[] = $this->serializeExpense($e);
            }
        }

        return $result;
    }

    /**
     * @param Expense $e
     * @return array
     */
    public function serializeExpense(Expense $e)
    {

        $idGroupe = array(
            Expense::GROUP_BANK_E_RESTAURANT_PAYMENT => 1,
            Expense::GROUP_BANK_RESTAURANT_PAYMENT => 2,
            Expense::GROUP_BANK_CARD_PAYMENT => 3,
            Expense::GROUP_BANK_CASH_PAYMENT => 4,
            Expense::GROUP_OTHERS => 5,
            Expense::GROUP_ERROR_COUNT => 6,
        );

        $staticLabel = array(
            "cashbox_error" => 5,
            "chest_error" => 6,
            "cash_payment" => 8,
        );

        /**
         * @var Expense $e
         */
        switch ($e->getGroupExpense()) {
            case Expense::GROUP_BANK_E_RESTAURANT_PAYMENT:
                $label = $this->parameterService->getPaymentMethodLabel(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    $e->getSousGroup()
                );
                break;
            case Expense::GROUP_BANK_RESTAURANT_PAYMENT:
                $label = $this->parameterService->getPaymentMethodLabel(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    $e->getSousGroup()
                );
                break;
            case Expense::GROUP_BANK_CARD_PAYMENT:
                $label = $this->parameterService->getPaymentMethodLabel(
                    PaymentMethod::BANK_CARD_TYPE,
                    $e->getSousGroup()
                );
                break;
            case Expense::GROUP_BANK_CASH_PAYMENT:
                $label = $this->parameterService->getPaymentMethodLabel(PaymentMethod::REAL_CASH_TYPE, null);
                break;
            case Expense::GROUP_OTHERS:
                $label = $this->parameterService->getParameterLabel($e->getSousGroup());
                break;
            case Expense::GROUP_ERROR_COUNT:
                $label = $this->parameterService->getParameterLabel($e->getSousGroup());
                break;
            default:
                $label = $e->getSousGroup();
                break;
        }
        $result = array(
            "RestCode" => $e->getOriginRestaurant()->getCode(),
            "DateBon" => date_format($e->getDateExpense(), 'd/m/Y'),
            "Type" => 'D',
            "Ref" => $e->getReference(),
            "idGroupe" => isset($idGroupe[$e->getGroupExpense()]) ? $idGroupe[$e->getGroupExpense()] : 0,
            "Groupe" => $this->translator->trans('expense.group.'.$e->getGroupExpense()),
            "codeFonction" => isset($staticLabel[$e->getSousGroup()]) ? $staticLabel[$e->getSousGroup(
            )] : $e->getSousGroup(),
            "Libelle" => $label,
            "Montant" => number_format($e->getAmount(), 6, '.', ''),
            "HeureCreation" => date_format($e->getCreatedAt(), 'H:m:s'),
            "DateCreation" => date_format($e->getCreatedAt(), 'd/m/Y'),
            "Commentaire" => $this->decodeComment($e->getComment()),
            "TVA" => number_format($e->getTva(), 2, '.', ''),
        );

        return $result;
    }

    /**
     * @param DayIncome $d
     * @return array
     */
    public function serializeDayIncome(DayIncome $d, Restaurant $restaurant)
    {

        $result = array(
            "RestCode" => $restaurant->getCode(),
            "DateBon" => date_format($d->getDate(), 'd/m/Y'),
            "Type" => 'R',
            "Ref" => date_format($d->getDate(), 'dmY'),
            "idGroupe" => '',
            "Groupe" => '',
            "codeFonction" => 9,
            "Libelle" => $this->translator->trans('expense.label.cashbox_count', [], 'supervision'),
            "Montant" => $d->calculateCashboxTotal(),
            "HeureCreation" => date_format($d->getDate(), 'H:m:s'),
            "DateCreation" => date_format($d->getDate(), 'd/m/Y'),
            "Commentaire" => '',
            "TVA" => '',
        );

        return $result;
    }

    /**
     * @param $criteria
     * @param $limit
     * @param $offset
     * @return array
     */
    public function getExpensesRecipe($criteria, $limit, $offset)
    {
        $result = $dayIncomes = [];

        $expenses = $this->em->getRepository(Expense::class)->getExpensesBi(
            $criteria,
            $offset,
            $limit
        );
        $result = $this->serializeExpenses($expenses);

        $recipes = $this->em->getRepository(RecipeTicket::class)->getRecipeBi(
            $criteria,
            $offset,
            $limit
        );

        $recipes = $this->recipeService->serializeRecipes($recipes);

        $result = array_merge($result, $recipes);

        return $result;
    }

    private function decodeComment($comment)
    {
       $result = str_replace("\r\n"," ", $comment);
       $result = str_replace(";",",", $result);

       return $result;
    }
}
