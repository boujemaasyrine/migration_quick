<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCentralUsersCommand extends ContainerAwareCommand
{
    private $em;
    private $dataDir;
    private $data;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:central:users')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from file.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to import central users for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->data = array();

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('file');
        $option = strtolower(trim($input->getOption('format')));
        if ($option !== "csv" && $option !== "json") {
            $output->writeln("Invalid format option ! Only json or csv values are accepted. Command exit...");

            return;
        }

        if (isset($argument)) {
            $filename = $argument.".".$option;
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No ".$option." import file with the '".$argument."' name !");
                return;
            }

            try {
                if ($option === "csv") {
                    // Import du fichier CSV
                    if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter

                        $output->writeln("---->Import mode: CSV file.");
                        while (($data = fgetcsv(
                                $handle,
                                1000,
                                ";"
                            )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                            $this->data[] = array(
                                'lastName' => $data[0],
                                'firstName'=> $data[1],
                                'login' => $data[2],
                                'password' => $data[3],
                                'email'=> $data[4],
                                'eligibleRestaurants'=> explode(",",$data[5]),
                                'roles'=>explode(",",$data[6])
                            );

                        }
                        fclose($handle);
                    } else {
                        $output->writeln("Cannot open the csv file! Exit command...");

                        return;
                    }

                } else {// import du fichier json

                    if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter

                        $output->writeln("---->Import mode: JSON file.");
                        try {
                            $fileData = file_get_contents($filePath);
                            $itemsData = json_decode($fileData, true);
                        } catch (\Exception $e) {
                            $output->writeln($e->getMessage());
                            return;
                        }

                        foreach ($itemsData as $data) {

                            $this->data[] = array(
                                'lastName' => $data['lastName'],
                                'firstName'=> $data['firstName'],
                                'login' => $data['login'],
                                'password' => $data['password'],
                                'email'=> $data['email'],
                                'eligibleRestaurants'=> $data['eligibleRestaurants'],
                                'roles'=>$data['roles']
                            );
                        }

                        fclose($handle);

                    } else {
                        $output->writeln("Cannot open the json file! Exit command...");
                        return;
                    }

                }

            } catch(\Exception $e) {
                $output->writeln($e->getMessage());
                return;
            }

        } else {
            $output->writeln("Please provide a valid import file name. ");
            return;
        }

        $output->writeln("Start importing central users...");
        $count = 0;
        $updatedCount=0;

        foreach ($this->data as $u) {
            $isUpdate=false;
            $user= $this->em->getRepository(Employee::class)->findOneBy(
                array(
                    "username"=>$u['login'],
                    "email"=>$u['email']
                )
            );
            if (!$user) {
                $user = new Employee();
            }else{
                $isUpdate=true;
                $updatedCount++;
            }
                $user
                    ->setUsername($u['login'])
                    ->setPassword($u['password'])
                    ->setActive(true)
                    ->setDeleted(false)
                    ->setEmail($u['email'])
                    ->setFirstName($u['firstName'])
                    ->setLastName($u['lastName']);

                // add eligible restaurant to users
                foreach ($u['eligibleRestaurants'] as $code){
                    $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($code);
                    if ($restaurant) {
                        if(!$user->getEligibleRestaurants()->contains($restaurant)){
                            $user->addEligibleRestaurant($restaurant);
                        }
                    }
                }

                foreach ($u['roles'] as $r){

                    $role = $this->em->getRepository(Role::class)->findOneBy(
                        array(
                            'label' => $r,
                        )
                    );
                    if($role){
                        if (!$user->hasEmployeeRole($role)) {
                            $user->addEmployeeRole($role);
                            $role->addUser($user);
                        }
                    }
                }
                $adminRole = $this->em->getRepository(Role::class)->findOneBy(
                    array(
                        'label' => Role::ROLE_SUPERVISION,
                    )
                );
                $user->setRoles([Role::ROLE_SUPERVISION]);
                if (!$user->hasEmployeeRole($adminRole)) {
                    $user->addEmployeeRole($adminRole);
                }
                $adminRole->addUser($user);

                $this->em->persist($user);

                if($isUpdate){
                    $output->writeln("Central user updated => ".$u['firstName']. " ".$u['lastName']);
                    $updatedCount++;
                }else{
                    $output->writeln("Central user imported => ".$u['firstName']. " ".$u['lastName']);
                    $count++;
                }

        }

        $this->em->flush();


        $output->writeln("----> ".$count." central users imported.");
        $output->writeln("----> ".$updatedCount." central users updated.");
        $output->writeln("==> Central users import finished <==");

    }
}
