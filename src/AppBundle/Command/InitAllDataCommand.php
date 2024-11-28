<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InitAllDataCommand extends ContainerAwareCommand
{
    private $franchiseType;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:all')
            ->addOption(
                'franchise',
                'f',
                InputOption::VALUE_REQUIRED,
                'Restaurant franchise type (Quick or BurgerKing)',
                ""
            )
            ->setDescription('Command to initialise all default data for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->franchiseType=null;
        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $option = $input->getOption("franchise");
        if(trim($option)!==""){
            if(strtolower($option)==="q" || strtolower($option)==="quick"){
                $this->franchiseType="QUICK";
            }elseif (strtolower($option)==="b" || strtolower($option)==="burgerking"){
                $this->franchiseType="BURGER KING";
            }else{
                $output->writeln("Invalid option passed ! Please provide a valid option : q or quick for Quick / b or burgerking for Burger King.");
                return;
            }
        }else {

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select the restaurant franchise type:',
                array('BURGER KING', 'QUICK'),
                0
            );
            $question->setErrorMessage('Franchise type %s is invalid.');
            $this->franchiseType = $helper->ask($input, $output, $question);
        }

        switch(strtoupper($this->franchiseType)){
            case "QUICK" :
                $restaurantType="q";
                break;
            case "BURGER KING" :
                $restaurantType="b";
                break;
            default:
                $restaurantType="q";
                break;
        }

        $emptyInput =new ArrayInput(array());

        //start executing command in order
        $rolesCommand = $this->getApplication()->find('saas:init:roles');
        $rolesCommand->run($emptyInput, $output);

        $actionsCommand = $this->getApplication()->find('saas:init:actions');
        $actionsCommand->run($emptyInput, $output);

        $arguments=array("file"=>"rolesActions","-f"=>"json");
        $rolesActionsCommandInput = new ArrayInput($arguments);
        $rolesActionsCommand = $this->getApplication()->find('saas:import:roles:actions');
        $rolesActionsCommand->run($rolesActionsCommandInput, $output);

        $categoriesGroupCommand = $this->getApplication()->find('saas:init:categories:groups');
        $categoriesGroupCommand->run($emptyInput, $output);

        $categoriesCommand = $this->getApplication()->find('saas:init:categories');
        $categoriesCommand->run($emptyInput, $output);

        $arguments=array("file"=>"parametersLabels","-f"=>"json");
        $parametersLabelsCommandInput = new ArrayInput($arguments);
        $parametersLabelsCommand = $this->getApplication()->find('saas:import:parameters:labels');
        $parametersLabelsCommand->run($parametersLabelsCommandInput, $output);

        $arguments=array("--franchise"=>$restaurantType,);
        $paymentMethodsCommandInput = new ArrayInput($arguments);
        $paymentMethodsCommand = $this->getApplication()->find('saas:init:payment_methods');
        $paymentMethodsCommand->run($paymentMethodsCommandInput, $output);

        $suppliersCommand = $this->getApplication()->find('saas:import:suppliers');
        $suppliersCommand->run($emptyInput, $output);

        $soldingCanalsCommand = $this->getApplication()->find('saas:init:solding:canal');
        $soldingCanalsCommand->run($emptyInput, $output);

        $arguments=array("file"=>"supervision_restaurants");
        $restaurantsListCommandInput = new ArrayInput($arguments);
        $restaurantsListCommand = $this->getApplication()->find('saas:import:restaurants:list');
        $restaurantsListCommand->run($restaurantsListCommandInput, $output);

        $arguments=array("file"=>"inventoryItems","-f"=>"json");
        $inventoryItemsCommandInput = new ArrayInput($arguments);
        $inventoryItemsCommand = $this->getApplication()->find('saas:import:inventory:items');
        $inventoryItemsCommand->run($inventoryItemsCommandInput, $output);

        $arguments=array("file"=>"soldItems","-f"=>"json");
        $soldItemsCommandInput = new ArrayInput($arguments);
        $soldItemsCommand = $this->getApplication()->find('saas:import:sold:products');
        $soldItemsCommand->run($soldItemsCommandInput, $output);

        $output->writeln("************************************************************************");
        $output->writeln("******************** Platforme initialization ended ********************");
        $output->writeln("************************************************************************");
    }

}
