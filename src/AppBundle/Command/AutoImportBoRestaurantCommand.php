<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AutoImportBoRestaurantCommand extends ContainerAwareCommand
{
    private $restaurantCode;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:auto:import:restaurant')
            ->addOption(
                'restaurant',
                null,
                InputOption::VALUE_REQUIRED,
                'Restaurant code',
                ""
            )
            ->addOption(
                'skip-restaurant-creation',
                null,
                InputOption::VALUE_OPTIONAL,
                'Flag to skip restaurant creation',
                false
            )
            ->setDescription('Command to auto import remote bo restaurant data.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->restaurantCode=null;
        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit','-1');

        $restaurantCode = trim($input->getOption("restaurant"));
        $skipRestaurantCreation = trim($input->getOption("skip-restaurant-creation"));

        if(empty($restaurantCode)){

            $output->writeln("Please provide a restaurant code !");
            return;

        }else {
            $this->restaurantCode=$restaurantCode;
        }


        $emptyInput =new ArrayInput(array());

        $arguments=array("restaurantCode"=>$this->restaurantCode);
        $commandInput = new ArrayInput($arguments);

        //start executing command in order
        if(!$skipRestaurantCreation) {
            //import the restaurant data and create sync command for products
            $command = $this->getApplication()->find('saas:import:restaurant');
            $command->run($commandInput, $output);

            //sync products
            $command = $this->getApplication()->find('quick:sync:execute');
            $command->run($emptyInput, $output);
        }

        //import products historic data
        $command = $this->getApplication()->find('saas:import:bo:historic:data');
        $command->run($commandInput, $output);

        //import bo stock data
        $command = $this->getApplication()->find('saas:import:bo:stock:data');
        $command->run($commandInput, $output);

        //import bo purchase data
        $command = $this->getApplication()->find('saas:import:bo:purchase:data');
        $command->run($commandInput, $output);

        //import bo tickets data
        $command = $this->getApplication()->find('saas:import:bo:tickets:data');
        $command->run($commandInput, $output);

        //import bo financial data
        $command = $this->getApplication()->find('saas:import:bo:financial:data');
        $command->run($commandInput, $output);

        //import bo products mvmt data
        $command = $this->getApplication()->find('saas:import:products:mvmt:data');
        $command->run($commandInput, $output);

        //import bo optikitchen parameters
        $command = $this->getApplication()->find('saas:import:optikitchen:parameters');
        $command->run($commandInput, $output);

        $output->writeln("************************************************************************");
        $output->writeln("************************** Auto Import ended ***************************");
        $output->writeln("************************************************************************");
    }


}
