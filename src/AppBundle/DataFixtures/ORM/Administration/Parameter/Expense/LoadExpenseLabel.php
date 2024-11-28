<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 08/04/2016
 * Time: 14:35
 */

namespace AppBundle\DataFixtures\ORM\Administration\Parameter\Expense;

use AppBundle\Administration\Entity\Parameter;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadExpenseLabel
 * @package AppBundle\DataFixtures\ORM\Administration\Parameter\Expense
 */
class LoadExpenseLabel extends AbstractFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $parameters = [
            ['label' => 'Achat FoodCost', 'value' => 'achat_foodcost'],
            ['label' => 'Achat petit matérial', 'value' => 'achat_petit_materiel'],
            ['label' => 'Acompte sur salaire', 'value' => 'acompte_sur_salaire'],
            ['label' => 'Animation Noel', 'value' => 'animation_noel'],
            ['label' => 'Enquete repas concurrence', 'value' => 'enquete_repas_concurrence'],
            ['label' => 'Divers', 'value' => 'divers'],
            ['label' => 'Etrennes ', 'value' => 'entrennes'],
            ['label' => 'Fourniture bureau', 'value' => 'fournitures_bureau'],
            ['label' => 'Frais annexes', 'value' => 'frais_annexes'],
            ['label' => 'Frais déplacement', 'value' => 'frais_depalcement'],
            ['label' => 'Frais postaux', 'value' => 'frais_postaux'],
            ['label' => 'Parking', 'value' => 'parking'],
            ['label' => 'Pharmacie', 'value' => 'pharmacie'],
            ['label' => 'Photocopie', 'value' => 'photocopie'],
            ['label' => 'Pressing', 'value' => 'pressing'],
            ['label' => 'Pub local', 'value' => 'pub_local'],
            ['label' => 'Taxes local', 'value' => 'taxes_local'],
            ['label' => 'Rembourssement borne', 'value' => 'rembousement_borne'],
            ['label' => 'Remise chèque quick', 'value' => 'remise_cheque_quick'],
            ['label' => 'Taxis', 'value' => 'taxis'],
            ['label' => 'Transport sur achat', 'value' => 'transport_sur_achat'],
        ];

        foreach ($parameters as $parameter) {
            $parameterExpense = new Parameter();

            $parameterExpense
                ->setLabel($parameter['label'])
                ->setValue($parameter['value'])
                ->setType(Parameter::EXPENSE_LABELS_TYPE);

            $manager->persist($parameterExpense);
            $manager->flush();
        }
    }
}
