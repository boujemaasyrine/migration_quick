<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRestaurantsListCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    private $dataDir;

    private $logger;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:restaurants:list')
            ->addArgument('file', InputArgument::OPTIONAL, 'Json file name to import data from.')
            ->setDescription('Import restaurants list form json file exported by a supervision instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.tickets_import');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('file');

        if (isset($argument)) {
            $filename = $argument.".json";
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No JSON import file with the '".$argument."' name !");

                return;
            }
            try {
                $fileData = file_get_contents($filePath);
                $restaurantsData = json_decode($fileData, true);
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                return;
            }



            /************ Start the import process *****************/
            $restaurantsCount = count($restaurantsData);
            $progress = new ProgressBar($output, $restaurantsCount);
            $updatedRestaurants = array();
            $addedRestaurants = array();
            $output->writeln("====> Import restaurant started...");
            $progress->start();
            foreach ($restaurantsData as $restaurant) {
                $this->em->getConnection()->beginTransaction(); // suspend auto-commit
                $operation = null;
                try {
                    $restaurantEntity = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurant['code']);
                    if ($restaurantEntity) {
                        $operation = "update";
                    } else {
                        $restaurantEntity = new Restaurant();
                        $operation="add";
                    }
                    //Set the restaurant data
                    $restaurantEntity
                        ->setName($restaurant['name'])
                        ->setCode($restaurant['code'])
                        ->setLang($restaurant['lang'])
                        ->setCustomerLang($restaurant['customerLang'])
                        ->setManager($restaurant['manager'])
                        ->setManagerEmail($restaurant['managerEmail'])
                        ->setEmail($restaurant['email'])
                        ->setManagerPhone($restaurant['managerPhone'])
                        ->setDmCf($restaurant['dmCf'])
                        ->setPhoneDmCf($restaurant['phoneDmCf'])
                        ->setAddress($restaurant['address'])
                        ->setZipCode($restaurant['zipCode'])
                        ->setCity($restaurant['city'])
                        ->setPhone($restaurant['phone'])
                        ->setBtwTva($restaurant['btwTva'])
                        ->setCompanyName($restaurant['companyName'])
                        ->setAddressCompany($restaurant['addressCompany'])
                        ->setZipCodeCompany($restaurant['zipCodeCompany'])
                        ->setCityCorrespondance($restaurant['cityCorrespondance'])
                        ->setCyFtFpLg($restaurant['cyFtFpLg'])
                        ->setCluster($restaurant['cluster'])
                        ->setType($restaurant['type'])
                        ->setFirstOpenning(new \DateTime($restaurant['firstOpenning']['date']))
                        ->setTypeCharte($restaurant['typeCharte'])
                        ->setEft(boolval($restaurant['eft']));
                    if($operation === "add")
                    {
                        $restaurantEntity->setActive(false);
                    }

                    $this->em->persist($restaurantEntity);

                    //create the restaurant suppliers and affect them to the restaurant if not exist
                    // or if the suplier exist , affect it to the restaurant
                   /* foreach ($restaurant['suppliers'] as $s) {
                        $supplier = $this->em->getRepository(Supplier::class)->findOneBy(
                            array('name' => trim($s['name']))
                        );
                        if (!$supplier) {//create the supplier and affect it
                            $supplier = new Supplier();
                            $supplier
                                ->setActive(boolval($s['active']))
                                ->setName($s['name'])
                                ->setDesignation($s['designation'])
                                ->setEmail($s['email'])
                                ->setCode($s['code'])
                                ->setAddress($s['address'])
                                ->setPhone($s['phone'])
                                ->setZone($s['zone']);
                        }
                        if (!$supplier->getRestaurants()->contains($restaurantEntity)) {
                            $supplier->addRestaurant($restaurantEntity);
                        }
                        if (!$restaurantEntity->getSuppliers()->contains($supplier)) {
                            $restaurantEntity->addSupplier($supplier);
                        }
                        $this->em->persist($supplier);
                    }*/

                    $progress->advance();
                    $this->em->flush();
                    $this->em->getConnection()->commit();


                    if($operation === "update"){
                        $updatedRestaurants[] = $restaurant['name'];
                    }elseif ($operation === "add"){
                        $addedRestaurants[] = $restaurant['name'];
                    }

                } catch (\Exception $e) {
                    $output->writeln("Command failed ! Rollback...");
                    $this->em->getConnection()->rollBack();
                    $output->writeln($e->getMessage());
                    return;
                }

            }
            $progress->finish();

            foreach ($updatedRestaurants as $r){
                $output->writeln("--> ".$r." is updated.");
            }
            $output->writeln("-------------------------------");
            foreach ($addedRestaurants as $r){
                $output->writeln("--> ".$r." is added.");
            }

            $output->writeln("===> ".count($updatedRestaurants)." restaurants updated.");
            $output->writeln("===> ".count($addedRestaurants)." restaurants added.");

            $output->writeln("Please dont forget to import the missing restaurant data and parameters from the BO instance!");

        } else {
            $output->writeln("Please provide a valid import file name. ");

            return;
        }


    }


}
