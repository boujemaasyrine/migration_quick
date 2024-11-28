<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Supervision\Service\ParameterService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class RecipeService
{

    private $em;
    private $translator;
    private $parameterService;

    public function __construct(
        EntityManager $entityManager,
        Translator $translator,
        ParameterService $parameterService
    ) {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->parameterService = $parameterService;
    }

    public function getRecipes($criteria, $limit, $offset)
    {
        $recipes = $this->em->getRepository(RecipeTicket::class)->getRecipeBi(
            $criteria,
            $offset,
            $limit
        );
        return $this->serializeRecipes($recipes);
    }

    /**
     * @param RecipeTicket[] $recipes
     * @return array
     */
    public function serializeRecipes($recipes)
    {
        $result = [];
        foreach ($recipes as $e) {
            if(abs($e->getAmount()) >= 0.000001) {
                $result[] = $this->serializeRecipe($e);
            }
        }

        return $result;
    }


    /**
     * @param RecipeTicket $e
     * @return array
     */
    public function serializeRecipe(RecipeTicket $e)
    {

        $labelId = array(
            'change_recipe' => 1,
            'various' => 3,
            'cashbox_error' => 5,
            'chest_error' => 6,
            'wc_money' => 7,
            'cachbox_recipe' => 9,
        );
        if($e->getLabel()=='cachbox_recipe'){
            $thoeAmount= $e->getAmount();
            $error1= $this->em->getRepository(RecipeTicket::class)->findOneBy(['date'=>$e->getDate(),'originRestaurant'=>$e->getOriginRestaurant(),'label'=>RecipeTicket::CASHBOX_ERROR]);
            $error2= $this->em->getRepository(Expense::class)->findOneBy(['dateExpense'=>$e->getDate(),'originRestaurant'=>$e->getOriginRestaurant(),'sousGroup'=>Expense::ERROR_CASHBOX]);
            if($error1){
                $thoeAmount = $thoeAmount - $error1->getAmount();
            }
            if($error2){
                $thoeAmount= $thoeAmount + $error2->getAmount();
            }
            $e->setAmount($thoeAmount);
        }

        $result = array(
            "RestCode" => $e->getOriginRestaurant()->getCode(),
            "DateBon" => date_format($e->getDate(), 'd/m/Y'),
            "Type" => 'R',
            "Ref" => $e->getId(),
            "idGroupe" => '',
            "Groupe" => '',
            "codeFonction" => isset($labelId[$e->getLabel()]) ? $labelId[$e->getLabel()] : 0,
            "Libelle" => $this->translator->trans('recipe_ticket.'.$e->getLabel(), [], 'supervision'),
            "Montant" => number_format($e->getAmount(), 6, '.', ''),
            "HeureCreation" => date_format($e->getCreatedAt(), 'H:m:s'),
            "DatvalCreation" => date_format($e->getCreatedAt(), 'd/m/Y'),
            "Commentaire" => '',
            "TVA" => '',
        );

        return $result;
    }
}
