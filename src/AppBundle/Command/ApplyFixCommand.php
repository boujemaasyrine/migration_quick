<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyFixCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    private $dataDir;

    private $logger;

    private $restaurant;

    private $restaurantCode;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('apply:fix')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Command only for fix purpuses.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.import_commands');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getArgument('restaurantCode')) {
            $this->restaurantCode = trim($input->getArgument('restaurantCode'));
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($this->restaurantCode);

            if (!$this->restaurant) {
                $output->writeln("No restaurant with the '".$this->restaurantCode."' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");

                return;
            }
        }
        /////////////////////////////////////////////////////////////////////////////////////
        /// Start command logic
        try {
// fix paramter global id and set it as value id to be used as mapper for BI
            $maping=array(
                1 => 77,
                2 => 78,
                3 => 79,
                4 => 80,
                5 => 81,
                6 => 82,
                7 => 83,
                8 => 84,
                9 => 85,
                10 => 86,
                11 => 87,
                12 => 88,
                13 => 89,
                14 => 90,
                15 => 91,
                16 => 92,
                17 => 93,
                18 => 94,
                19 => 95,
                21 => 7,
                20 => 3,
                22 => 101,
                23 => 102,
                24 => 103,
                25 => 104,
                26 => 105,
                27 => 106,
                28 => 107,
                29 => 108,
                30 => 109,
                31 => 110,
                32 => 111,
                33 => 112,
                34 => 113,
                35 => 114,
                36 => 115,
                37 => 116,
                38 => 117,
                39 => 118,
                40 => 119,
                41 => 120,
                42 => 121,
                43 => 122,
                44 => 123,
                45 => 124,
                46 => 125,
                47 => 126,
                48 => 38,
                49 => 1,
                50 => 5,
                51 => 6,
                52 => 9
            );
            $allParam = $this->em->getRepository(Parameter::class)->createQueryBuilder('p')
                ->where('p.type = :type1 or p.type = :type2')
                ->setParameter('type1', 'RECIPE_LABELS_TYPE')
                ->setParameter('type2', 'EXPENSE_LABELS_TYPE')
                ->getQuery()->getResult();
            $i=0;

            foreach ($allParam as $param){
                $l=$param->getLabel();
                $value=$param->getValue();
                if (isset($value['id']) && is_numeric($value['id'])){
                    $id=$maping[$param->getId()];
                    $param->setGlobalId($id);
                    $output->writeln($param->getId().' | new Global Id = '.$id.'---> '.$l);
                }
                $i++;
            }
            $this->em->flush();

            $expenses= $this->em->getRepository(Expense::class)->createQueryBuilder('e')
                ->where('e.groupExpense = :groupe')
                ->setParameter('groupe', 'GROUP_OTHERS')
                ->getQuery()->getResult();
            foreach ($expenses as $expense){
                $id=$expense->getSousGroup();
                foreach ($allParam as $parameter) {

                    if (isset($parameter->getValue()['id'])
                        && $id == $parameter->getValue()['id'] && is_numeric($parameter->getValue()['id'])
                    ) {
                        $expense->setSousGroup($parameter->getGlobalId());
                    }
                }
            }
            $this->em->flush();

            $recipes= $this->em->getRepository(RecipeTicket::class)->createQueryBuilder('r')
                ->getQuery()->getResult();
            foreach ($recipes as $recipe){
                $id=$recipe->getLabel();
                if(is_numeric($id)){
                    foreach ($allParam as $parameter) {

                        if (isset($parameter->getValue()['id'])
                            && $id == $parameter->getValue()['id'] && is_numeric($parameter->getValue()['id'])
                        ) {
                           $recipe->setLabel($parameter->getGlobalId());
                        }
                    }
                }

            }
            $this->em->flush();

            foreach ($allParam as $param){
                $id=$param->getGlobalId();
                $value=$param->getValue();
                if (isset($param->getValue()['id']) && is_numeric($param->getValue()['id']) ){
                    $value['id']=$id;
                    $param->setValue($value);
                }

            }
            $this->em->flush();

            return;
            /*$allProcedure = $this->em->getRepository(Procedure::class)->findAll();
            foreach ($allProcedure as $p){
                if($p->getName()==='ouverture'){
                    $p->addNameTranslation("nl", "Opening");
                }elseif ($p->getName()==='fermeture'){
                    $p->addNameTranslation("nl", "Sluiting");
                }

            }
            $this->em->flush();

            return;


            $filename = "financialData_restaurant_".$this->restaurantCode.".json";
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '".$this->restaurantCode."' restaurant code found !");
                return;
            }
            try {
                $fileData = file_get_contents($filePath);
                $financialData = json_decode($fileData, true);
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                return;
            }


            $output->writeln("Importing Recipe tickets");
            $recipeTickets=$financialData['recipeTickets'];

            $progress = new ProgressBar($output, count($recipeTickets));
            $progress->start();
            $addedRecipeTickets=0;
            $updatedRecipeTickets=0;
            $skippedRecipeTickets=0;
            foreach ($recipeTickets as $recipeTicket){
                $progress->advance();
                $isUpdate = false;
                if(empty($recipeTicket) || !array_key_exists('id',$recipeTicket)){
                    continue;
                }

                $recipeTicketEntity = new RecipeTicket();

                $recipeTicketEntity
                    ->setLabel($recipeTicket['label'])
                    ->setAmount(floatval($recipeTicket['amount']))
                    ->setDeleted(boolval($recipeTicket['deleted']));
                $recipeTicketEntity->setOriginRestaurant($this->restaurant);
                $recipeTicketEntity->setImportId($recipeTicket['id'] . "_" . $this->restaurantCode);
                $date = new \DateTime($recipeTicket['date']['date']);
                $recipeTicketEntity->setDate($date);

                $userName=null;
                if ($recipeTicket['owner']) {

                    if(empty($recipeTicket['owner']['wyndId'])){
                        $userName=$recipeTicket['owner']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$recipeTicket['owner']['username'];
                    }
                    $owner = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($owner) {
                        $recipeTicketEntity->setOwner($owner);
                    } else {
                        $this->logger->info('Recipe Ticket Skipped because owner doesn\'t exist : ', array("userName" => $recipeTicket['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                        $skippedRecipeTickets++;
                        continue;
                    }
                }

                $this->em->persist($recipeTicketEntity);


                $isUpdate ? $updatedRecipeTickets++ : $addedRecipeTickets++;

            }

            $this->em->flush();

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedRecipeTickets." Recipe Tickets were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedRecipeTickets." Recipe Tickets were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedRecipeTickets." Recipe Tickets were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import expenses
            $filename = "financialData_restaurant_".$this->restaurantCode.".json";
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '".$this->restaurantCode."' restaurant code found !");
                return;
            }
            try {
                $fileData = file_get_contents($filePath);
                $financialData = json_decode($fileData, true);
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                return;
            }
            $output->writeln("Importing Expenses...");
            $expenses=$financialData['expenses'];

            $progress = new ProgressBar($output, count($expenses));
            $progress->start();
            $addedExpenses=0;
            $updatedExpenses=0;
            $skippedExpenses=0;
            foreach ($expenses as $expense) {
                $progress->advance();
                $isUpdate = false;
                if (empty($expense) || !array_key_exists('id', $expense)) {
                    continue;
                }

                $expenseEntity = $this->em->getRepository(Expense::class)->findOneBy(
                    array(
                        "importId" => $expense['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$expenseEntity) {
                    //$expenseEntity = new Expense();
                    continue;
                } else {
                    $isUpdate = true;
                }


                if($expense['groupExpense'] === Expense::GROUP_OTHERS){
                    $query = $this->em->getRepository(Parameter::class)->createQueryBuilder('p')
                        ->where('p.label = :label')
                        ->setParameter('label', $expense['sousGroup'])->getQuery();
                    $query->setHint(
                        Query::HINT_CUSTOM_OUTPUT_WALKER,
                        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
                    );
                    $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, "nl");
                    //$p=$this->em->getRepository(Parameter::class)->findOneByLabel($expense['sousGroup']);
                    $p=$query->getOneOrNullResult();
                    if($p){
                        //$output->writeln("-> ".$p->getLabel());
                        $expenseEntity->setSousGroup($p->getGlobalId());
                        $updatedExpenses++;
                    }
                }


                $this->em->persist($expenseEntity);
                $isUpdate ? $updatedExpenses++ : $addedExpenses++;
            }
            $this->em->flush();
            $expenses=null;
            unset($expenses);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedExpenses." Expenses were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedExpenses." Expenses were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedExpenses." Expenses were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            $action = new Action();

            $action->setName('api_delete_envelope');

            $action->setType(Action::RESTAURANT_ACTION_TYPE);

            $action
                ->setRoute('api_delete_envelope')
                ->setParams([])
                ->setHasExit(false);




            $action->setIsPage(false);
            $action->setGlobalId($action->getId());

            $this->em->persist($action);



            $this->em->flush();
*/
        } catch (\Exception $e) {
            $output->writeln("");
            $output->writeln("Command failed ! ");
            $output->writeln($e->getMessage());

            return;
        }

    }


}
