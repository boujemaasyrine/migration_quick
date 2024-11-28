<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/04/2016
 * Time: 11:48
 */

namespace AppBundle\Supervision\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Merchandise\Entity\SheetModel;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportActionsCommand extends ContainerAwareCommand
{
    // Template for an action
    //['name' => '', 'route' => '', 'params' => [] ,'hasExitBtn' => false, 'type' => Action::RESTAURANT_ACTION_TYPE ]


    private $actions = [
        ////////////////////////////////////////////// Restaurant Actions//////////////////////////////////////////////////////////////////
        [
            'name' => 'create_order',
            'route' => 'add_command',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'order_list',
            'route' => 'list_pendings_commands',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'help_order',
            'route' => 'init',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'create_delivery',
            'route' => 'delivery_entry',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'delivery_list',
            'route' => 'delivered_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'transfer_in',
            'route' => 'new_transfer_in',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'transfer_out',
            'route' => 'new_transfer_out',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'transfer_list',
            'route' => 'list_transfer',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'create_return',
            'route' => 'create_return',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'return_list',
            'route' => 'returns_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'ca_prev_list',
            'route' => 'show_ca_prv_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'index_food_cost_synthetic',
            'route' => 'index_food_cost_synthetic',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'control_stock_report',
            'route' => 'control_stock_report',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'index_workflows',
            'route' => 'index_workflows',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'withdrawal_entry',
            'route' => 'withdrawal_entry',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'withdrawal_list',
            'route' => 'withdrawal_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'expense_entry',
            'route' => 'expense_entry',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'expenses_list',
            'route' => 'expenses_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'staff_list',
            'route' => 'staff_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'index_config_role',
            'route' => 'index_config_role',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'suppliers_list',
            'route' => 'suppliers_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'restaurant_list',
            'route' => 'restaurant_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'inventory_item_list',
            'route' => 'inventory_item_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'report_inventory_loss',
            'route' => 'report_inventory_loss',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'report_sold_loss',
            'route' => 'report_sold_loss',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'report_in_out',
            'route' => 'report_in_out',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'margin_food_cost_report',
            'route' => 'margin_food_cost_report',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'hour_by_hour',
            'route' => 'hour_by_hour',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'administrative_closing',
            'route' => 'verify_last_date',
            'params' => [],
            'hasExitBtn' => true,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        // Financial
        [
            'name' => 'chest_count',
            'route' => 'chest_count',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'chest_list',
            'route' => 'chest_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'list_recipe_tickets',
            'route' => 'list_recipe_tickets',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'create_recipe_tickets',
            'route' => 'create_recipe_tickets',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'cashbox_counting',
            'route' => 'cashbox_counting',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'cashbox_list',
            'route' => 'cashbox_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'day_income',
            'route' => 'day_income',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'deposit_ticket',
            'route' => 'deposit_ticket',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'deposit_cash',
            'route' => 'deposit_cash',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'envelope_list',
            'route' => 'envelope_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'deleted_envelope_list',
            'route' => 'deleted_envelope_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'envelope_ticket_list',
            'route' => 'envelope_ticket_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'create_envelope_cash',
            'route' => 'create_envelope_cash',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'create_envelope_restau',
            'route' => 'create_envelope_restau',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'expense_entry',
            'route' => 'expense_entry',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'expenses_list',
            'route' => 'expenses_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'withdrawal_entry',
            'route' => 'withdrawal_entry',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'withdrawal_list',
            'route' => 'withdrawal_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        // Merchandise
        [
            'name' => 'add_inventory_sheet_model',
            'route' => 'api_save_inventory_sheet_model',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'edit_inventory_sheet_model',
            'route' => 'api_save_inventory_sheet_model',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'delete_inventory_sheet_model',
            'route' => 'api_delete_sheet_model',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        // ['name' => 'api_save_inventory_sheet', 'route' => 'api_save_inventory_sheet_model', 'params' => [] ,'hasExitBtn' => true],
        [
            'name' => 'inventory_list',
            'route' => 'inventory_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'saisie_inventory',
            'route' => 'inventory_entry',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'manage_inventory_sheet',
            'route' => 'inventory_sheet',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        [
            'name' => 'add_loss_sheet_article',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'edit_loss_sheet_article',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'delete_loss_sheet_article',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],


        [
            'name' => 'add_loss_sheet_pf',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'edit_loss_sheet_pf',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'delete_loss_sheet_pf',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        [
            'name' => 'loss_sheet_article',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'loss_sheet_pf',
            'route' => 'loss_sheet',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        [
            'name' => 'loss_entry_article',
            'route' => 'loss_entry',
            'params' => ['type' => SheetModel::ARTICLES_LOSS_MODEL],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'loss_entry_pf',
            'route' => 'loss_entry',
            'params' => ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
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
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'report_cashbox_counts_cashier',
            'route' => 'report_cashbox_counts_cashier',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'report_cashbox_counts_anomalies',
            'route' => 'report_cashbox_counts_anomalies',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'daily_results_report',
            'route' => 'daily_results_report',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'report_portion_control',
            'route' => 'report_portion_control',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        [
            'name' => 'cahsbox_parameter',
            'route' => 'cahsbox_parameter',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'planning',
            'route' => 'planning',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'planning_suppliers',
            'route' => 'planning_suppliers',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        [
            'name' => 'coef_calculate_base',
            'route' => 'coef_calculate_base',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'show_coeff_pp',
            'route' => 'show_coeff_pp',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        //        ['name' => 'add_role', 'route' => 'add_role', 'params' => [], 'hasExitBtn' => false, 'type' => Action::RESTAURANT_ACTION_TYPE],
        [
            'name' => 'product_sold_list',
            'route' => 'product_sold_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],


        [
            'name' => 'verify_opened_table',
            'route' => 'verify_opened_table',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        ////////////////////////////////////////////////////////////Central Actions///////////////////////////////////////////////////////////

        [
            'name' => 'categories_list',
            'route' => 'categories_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'groups_list',
            'route' => 'groups_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'inventory_item_list',
            'route' => 'inventory_item_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'report_final_stock',
            'route' => 'report_final_stock',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'product_sold_list',
            'route' => 'product_sold_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'product_sold_save',
            'route' => 'product_sold_save',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'restaurants_list',
            'route' => 'restaurants_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        //['name' => 'restaurant_parameters', 'route' => 'restaurant_parameters', 'params' => [] ,'hasExitBtn' => false, 'type' => Action::CENTRAL_ACTION_TYPE],
        [
            'name' => 'restaurant_list_super',
            'route' => 'restaurant_list_super',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'suppliers_list',
            'route' => 'suppliers_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'users_list',
            'route' => 'users_list',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'add_role',
            'route' => 'add_role',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'control_stock_report',
            'route' => 'control_stock_report',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'daily_results_report',
            'route' => 'daily_results_report',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'hour_by_hour',
            'route' => 'hour_by_hour',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'margin_food_cost_report',
            'route' => 'margin_food_cost_report',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'index_food_cost_synthetic',
            'route' => 'index_food_cost_synthetic',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'config_right_restaurant',
            'route' => 'config_right_restaurant',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'config_right_central',
            'route' => 'config_right_central',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'labels_config_expense',
            'route' => 'expense_labels_config',
            'params' => ["type" => 'expense'],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'labels_config_recipe',
            'route' => 'recipe_labels_config',
            'params' => ["type" => 'recipe'],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'historic_broadcast_all_restaurants',
            'route' => 'historic_broadcast_all_restaurants',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],
        [
            'name' => 'supervision_details',
            'route' => 'supervision_details',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::CENTRAL_ACTION_TYPE,
        ],

        //Optikitchen
        [
            'name' => 'optikitchen_calcul',
            'route' => 'optikitchen_calcul',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'opti_consultation',
            'route' => 'opti_consultation',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'optikitchen_parameters',
            'route' => 'optikitchen_param',
            'params' => [],
            'hasExitBtn' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        //        ['name' => 'edit_optikitchen', 'route' => 'edit_optikitchen', 'params' => [], 'hasExitBtn' => false,'isPage' => false, 'type' => Action::RESTAURANT_ACTION_TYPE],
        [
            'name' => 'export_optikitchen',
            'route' => 'export_optikitchen',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        //Save actions
        [
            'name' => 'create_workflow',
            'route' => 'create_workflow',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'delete_procedure',
            'route' => 'delete_procedure',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'bud_prev_edit',
            'route' => 'bud_prev_edit',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        //Users Management
        [
            'name' => 'attribute_role',
            'route' => 'attribute_role',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'default_password',
            'route' => 'default_password',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'synchronize_users',
            'route' => 'synchronize_users',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],

        //Jours comparable
        [
            'name' => 'comparable_days_list',
            'route' => 'comparable_days_list',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => true,
            'type' => Action::RESTAURANT_ACTION_TYPE,
        ],
        [
            'name' => 'comparable_days_modify',
            'route' => 'comparable_days_modify',
            'params' => [],
            'hasExitBtn' => false,
            'isPage' => false,
            'type' => Action::RESTAURANT_ACTION_TYPE,
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
        $this->setName('quick:supervision-actions:import')->setDefinition(
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

        echo count($this->actions)."\n";

        foreach ($this->actions as $c) {
            echo "Import Action => ".$c['name']."\n";
            $action = $this->em->getRepository(Action::class)->findOneBy(
                array(
                    'name' => $c['name'],
                    'type' => isset($c['type']) ? $c['type'] : '',
                )
            );

            if (!$action) {
                $action = new Action();
                $action->setName($c['name']);
                if (isset($c['type'])) {
                    $action->setType($c['type']);
                }
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

            if ($action->getGlobalId() == null) {
                $action->setGlobalId($action->getId());
            }
        }
        $this->em->flush();

        echo " => Finish Importing Actions <= \n";
    }
}
