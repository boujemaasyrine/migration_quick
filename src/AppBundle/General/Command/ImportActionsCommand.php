<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/04/2016
 * Time: 11:48
 */

namespace AppBundle\General\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\Merchandise\Entity\SheetModel;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportActionsCommand extends ContainerAwareCommand
{
    // Template for an action
    //['name' => '', 'route' => '', 'params' => [] ,'hasExitBtn' => false ]


    private $actions = [
        ['name' => 'create_order', 'route' => 'add_command', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'order_list', 'route' => 'list_pendings_commands', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'help_order', 'route' => 'init', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'create_delivery', 'route' => 'delivery_entry', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'delivery_list', 'route' => 'delivered_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'transfer_in', 'route' => 'new_transfer_in', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'transfer_out', 'route' => 'new_transfer_out', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'transfer_list', 'route' => 'list_transfer', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'create_return', 'route' => 'create_return', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'return_list', 'route' => 'returns_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'ca_prev_list', 'route' => 'show_ca_prv_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'create_return', 'route' => 'create_return', 'params' => [], 'hasExitBtn' => true],
        [
            'name' => 'index_food_cost_synthetic',
            'route' => 'index_food_cost_synthetic',
            'params' => [],
            'hasExitBtn' => false,
        ],
        ['name' => 'control_stock_report', 'route' => 'control_stock_report', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'index_workflows', 'route' => 'index_workflows', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'withdrawal_entry', 'route' => 'withdrawal_entry', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'withdrawal_list', 'route' => 'withdrawal_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'expense_entry', 'route' => 'expense_entry', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'expenses_list', 'route' => 'expenses_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'staff_list', 'route' => 'staff_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'index_config_role', 'route' => 'index_config_role', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'suppliers_list', 'route' => 'suppliers_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'restaurant_list', 'route' => 'restaurant_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'inventory_item_list', 'route' => 'inventory_item_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'report_inventory_loss', 'route' => 'report_inventory_loss', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'report_sold_loss', 'route' => 'report_sold_loss', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'report_in_out', 'route' => 'report_in_out', 'params' => [], 'hasExitBtn' => false],
        [
            'name' => 'margin_food_cost_report',
            'route' => 'margin_food_cost_report',
            'params' => [],
            'hasExitBtn' => false,
        ],
        ['name' => 'hour_by_hour', 'route' => 'hour_by_hour', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'administrative_closing', 'route' => 'kiosk_counting', 'params' => [] ,'hasExitBtn' => true],
        //['name' => 'administrative_closing', 'route' => 'verify_last_date', 'params' => [], 'hasExitBtn' => true],
        //['name' => 'administrative_closing', 'route' => 'validation_income_show', 'params' => [] ,'hasExitBtn' => true],

        // Financial
        ['name' => 'chest_count', 'route' => 'chest_count', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'chest_list', 'route' => 'chest_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'list_recipe_tickets', 'route' => 'list_recipe_tickets', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'create_recipe_tickets', 'route' => 'create_recipe_tickets', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'cashbox_counting', 'route' => 'cashbox_counting', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'cashbox_list', 'route' => 'cashbox_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'day_income', 'route' => 'day_income', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'deposit_ticket', 'route' => 'deposit_ticket', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'deposit_cash', 'route' => 'deposit_cash', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'envelope_list', 'route' => 'envelope_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'envelope_ticket_list', 'route' => 'envelope_ticket_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'create_envelope_cash', 'route' => 'create_envelope_cash', 'params' => [], 'hasExitBtn' => false],
        [
            'name' => 'create_envelope_restau',
            'route' => 'create_envelope_restau',
            'params' => [],
            'hasExitBtn' => false,
        ],
        ['name' => 'expense_entry', 'route' => 'expense_entry', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'expenses_list', 'route' => 'expenses_list', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'withdrawal_entry', 'route' => 'withdrawal_entry', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'withdrawal_list', 'route' => 'withdrawal_list', 'params' => [], 'hasExitBtn' => false],

        // Merchandise
        [
            'name' => 'add_inventory_sheet_model',
            'route' => 'add_inventory_sheet_model',
            'params' => [],
            'hasExitBtn' => true,
            'isPage' => false,
        ],
        [
            'name' => 'edit_inventory_sheet_model',
            'route' => 'edit_inventory_sheet_model',
            'params' => [],
            'hasExitBtn' => true,
            'isPage' => false,
        ],
        [
            'name' => 'delete_inventory_sheet_model',
            'route' => 'delete_inventory_sheet_model',
            'params' => [],
            'hasExitBtn' => true,
            'isPage' => false,
        ],

        [
            'name' => 'api_save_inventory_sheet',
            'route' => 'api_save_inventory_sheet_model',
            'params' => [],
            'hasExitBtn' => true,
        ],
        ['name' => 'inventory_list', 'route' => 'inventory_list', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'saisie_inventory', 'route' => 'inventory_entry', 'params' => [], 'hasExitBtn' => true],
        ['name' => 'manage_inventory_sheet', 'route' => 'inventory_sheet', 'params' => [], 'hasExitBtn' => true],

        [
            'name' => 'add_loss_sheet_article',
            'route' => 'add_loss_sheet_article',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => true,
            'isPage' => false,
        ],
        [
            'name' => 'edit_loss_sheet_article',
            'route' => 'edit_loss_sheet_article',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => true,
            'isPage' => false,
        ],
        [
            'name' => 'delete_loss_sheet_article',
            'route' => 'delete_loss_sheet_article',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => true,
            'isPage' => false,
        ],

        [
            'name' => 'add_loss_sheet_pf',
            'route' => 'add_loss_sheet_pf',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => true,
            'isPage' => false,
        ],
        [
            'name' => 'edit_loss_sheet_pf',
            'route' => 'edit_loss_sheet_pf',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => true,
            'isPage' => false,
        ],
        [
            'name' => 'delete_loss_sheet_pf',
            'route' => 'delete_loss_sheet_pf',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => true,
            'isPage' => false,
        ],

        [
            'name' => 'loss_sheet_article',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
        ],
        [
            'name' => 'loss_sheet_pf',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
        ],

        [
            'name' => 'loss_entry_article',
            'route' => 'loss_entry',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => true,
        ],
        [
            'name' => 'loss_entry_pf',
            'route' => 'loss_entry',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => true,
        ],

        [
            'name' => 'previous_day_loss_inv',
            'route' => 'previous_day_loss',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
        ],
        [
            'name' => 'previous_day_loss_vtes',
            'route' => 'previous_day_loss',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
        ],

        // Report
        [
            'name' => 'report_cashbox_counts_owner',
            'route' => 'report_cashbox_counts_owner',
            'params' => [],
            'hasExitBtn' => false,
        ],
        [
            'name' => 'report_cashbox_counts_cashier',
            'route' => 'report_cashbox_counts_cashier',
            'params' => [],
            'hasExitBtn' => false,
        ],
        [
            'name' => 'report_cashbox_counts_anomalies',
            'route' => 'report_cashbox_counts_anomalies',
            'params' => [],
            'hasExitBtn' => false,
        ],
        ['name' => 'daily_results_report', 'route' => 'daily_results_report', 'params' => [], 'hasExitBtn' => false],
        [
            'name' => 'report_portion_control',
            'route' => 'report_portion_control',
            'params' => [],
            'hasExitBtn' => false,
        ],

        ['name' => 'cahsbox_parameter', 'route' => 'cahsbox_parameter', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'planning', 'route' => 'planning', 'params' => [], 'hasExitBtn' => false],
        [
            'name' => 'planning_suppliers',
            'route' => 'planning_suppliers',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],

        ['name' => 'coef_calculate_base', 'route' => 'coef_calculate_base', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'show_coeff_pp', 'route' => 'show_coeff_pp', 'params' => [], 'hasExitBtn' => false],

        ['name' => 'add_role', 'route' => 'add_role', 'params' => [], 'hasExitBtn' => false],
        ['name' => 'product_sold_list', 'route' => 'product_sold_list', 'params' => [], 'hasExitBtn' => false],

        // Administration
        [
            'name' => 'labels_config_expense',
            'route' => 'labels_config',
            'params' => ["type" => Parameter::EXPENSE],
            'hasExitBtn' => false,
        ],
        [
            'name' => 'labels_config_recipe',
            'route' => 'labels_config',
            'params' => ["type" => Parameter::RECIPE],
            'hasExitBtn' => false,
        ],

        ['name' => 'verify_opened_table', 'route' => 'verify_opened_table', 'params' => [], 'hasExitBtn' => false],

        //Optikitchen
        ['name' => 'optikitchen_calcul', 'route' => 'optikitchen_calcul', 'params' => [], 'hasExitBtn' => false],
        [
            'name' => 'optikitchen_consultation_item_inv',
            'route' => 'optikitchen_pp',
            'params' => [],
            'hasExitBtn' => false,
        ],
        [
            'name' => 'optikitchen_consultation_item_vtes',
            'route' => 'optikitchen_ps',
            'params' => [],
            'hasExitBtn' => false,
        ],
        ['name' => 'optikitchen_parameters', 'route' => 'optikitchen_param', 'params' => [], 'hasExitBtn' => false],

        //Save actions
        [
            'name' => 'create_workflow',
            'route' => 'create_workflow',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],
        [
            'name' => 'delete_procedure',
            'route' => 'delete_procedure',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],
        [
            'name' => 'bud_prev_edit',
            'route' => 'bud_prev_edit',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],

        //Users Management
        [
            'name' => 'attribute_role',
            'route' => 'attribute_role',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],
        [
            'name' => 'default_password',
            'route' => 'default_password',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],
        [
            'name' => 'synchronize_users',
            'route' => 'synchronize_users',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
        ],

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
        $this->setName('quick:actions:import')->setDefinition(
            []
        )->setDescription('Import All Actions.');
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
        echo "Import Actions => \n";

        foreach ($this->actions as $c) {
            echo "Import Action => ".$c['name']."\n";
            $action = $this->em->getRepository("Administration:Action")->findOneBy(
                array(
                    'name' => $c['name'],
                )
            );

            if (!$action) {
                $action = new Action();
                $action->setName($c['name']);
            }

            $action->setRoute($c['route'])
                ->setParams($c['params'])
                ->setHasExit($c['hasExitBtn']);

            if (!isset($c['isPage']) || ($c['isPage'] && isset($c['isPage']))) {
                $action->setIsPage(true);
            } else {
                $action->setIsPage(false);
            }

            $this->em->persist($action);
            $this->em->flush();
        }
        echo " => Finish Importing Actions <= \n";
    }
}
