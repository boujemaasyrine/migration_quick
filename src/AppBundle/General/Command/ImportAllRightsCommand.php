<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 14:03
 */

namespace AppBundle\General\Command;

use AppBundle\Security\Entity\Rights;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportAllRightsCommand extends ContainerAwareCommand
{

    private $rigths = [
        ['label' => 'add_order', 'textLabel' => 'Création d\'une commande'],
        ['label' => 'create_inventory_sheet', 'textLabel' => 'Edition feuille d\'inventaire '],
        ['label' => 'inventory_entry', 'textLabel' => 'Saisie d\'inventaire'],
        ['label' => 'create_loss_sheet', 'textLabel' => 'Edition feuille de perte'],
        ['label' => 'loss_entry', 'textLabel' => 'Saisir une perte'],
        ['label' => 'staff_list', 'textLabel' => 'Liste du personnel'],
        ['label' => 'withdrawal_entry', 'textLabel' => 'Saisir un prélèvement'],
        ['label' => 'planning', 'textLabel' => 'Planning des commandes'],
        ['label' => 'withdrawal_list', 'textLabel' => 'Liste des prélèvements'],
        ['label' => 'expense_entry', 'textLabel' => 'Saisir une dépense'],
        ['label' => 'expenses_list', 'textLabel' => 'Liste des dépenses'],
        ['label' => 'index_config_role', 'textLabel' => 'Gestion des droits'],
        ['label' => 'suppliers_list', 'textLabel' => 'Liste des fournisseurs'],
        ['label' => 'restaurant_list', 'textLabel' => 'Liste des restaurants Quick'],
        ['label' => 'report_inventory_loss', 'textLabel' => 'Rapport Pertes Items Inventaire'],
        ['label' => 'report_sold_loss', 'textLabel' => 'Rapport Pertes Items de Vente'],
        ['label' => 'report_in_out', 'textLabel' => 'Rapport des entrées & sorties'],
        ['label' => 'margin_food_cost_report', 'textLabel' => 'Rapport Marge FoodCost'],
        ['label' => 'hour_by_hour', 'textLabel' => 'Rapport Heure par Heure'],
        ['label' => 'restaurant_list', 'textLabel' => 'Liste des restaurants Quick'],
        ['label' => 'add_role', 'textLabel' => 'Ajouter un role'],
    ];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:rights:import')->setDefinition(
            []
        )->setDescription('Import All Roles.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Import Rights => \n";

        foreach ($this->rigths as $r) {
            echo "Import Role => ".$r['textLabel']."\n";
            $right = $this->em->getRepository("Security:Rights")->findOneBy(
                array(
                    'label' => $r['label'],
                )
            );

            if (!$right) {
                $right = new Rights();
                $right->setLabel($r['label']);
            }

            $right->setTextLabel($r['textLabel']);
            $this->em->persist($right);
            $this->em->flush();
        }
        echo " => Finish Importing Rights \n";
    }
}
