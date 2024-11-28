<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportBoOptikitchenParametersCommand extends ContainerAwareCommand
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
            ->setName('saas:import:optikitchen:parameters')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant optikitchen paramters form json file exported by a BO instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir') . "/../data/import/saas/";
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
            $restaurantCode = trim($input->getArgument('restaurantCode'));
        } else {
            $helper = $this->getHelper('question');
            $question = new Question(
                'Please enter restaurant code (found at the end of the json file name : restaurant_xxxx.json ) :'
            );
            $question->setValidator(
                function ($answer) {
                    if (!is_string($answer) || strlen($answer) < 1) {
                        throw new \RuntimeException(
                            'Please enter the restaurnat code!'
                        );
                    }
                    return trim($answer);
                }
            );
            $restaurantCode = $helper->ask($input, $output, $question);
        }
        $filename = "restaurant_" . $restaurantCode . ".json";
        $filePath = $this->dataDir . $filename;

        if (!file_exists($filePath)) {
            $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");

            return;
        }
        try {
            $fileData = file_get_contents($filePath);
            $restaurantData = json_decode($fileData, true);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }


        /************ Start the import process *****************/


        try {
            $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurantData['code']);

            if (!$restaurant) {
                $output->writeln("No restaurant with the '" . $restaurantData['code'] . "'code exist ! Command exit... ");
                return;
            }



            //set optikitchen parameters
            $sql = "UPDATE product set eligible_for_optikitchen = FALSE WHERE origin_restaurant_id= :restaurantId ; ";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindValue('restaurantId', $restaurant->getId());
            $stm->execute();

            if($restaurantData['optikitchen'] && array_key_exists('purchased_items',$restaurantData['optikitchen'])){
                $output->writeln("Importing products purchased parameters for Optikitchen...");
                $progress = new ProgressBar($output, count($restaurantData['optikitchen']['purchased_items']));
                $progress->start();

                foreach ($restaurantData['optikitchen']['purchased_items'] as $purchasedItem) {
                    $p = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "name"=>trim($purchasedItem['item']),
                            //"globalProductID"=>trim($purchasedItem['global_id'])
                        )
                    );
                    if ($p) {
                        $p->setEligibleForOptikitchen(true);
                        $this->em->persist($p);
                    }
                    $progress->advance();
                }
                $progress->finish();
            }

            $output->writeln("");
            $output->writeln("Importing products sold parameters for Optikitchen...");
            if($restaurantData['optikitchen'] &&  array_key_exists('sold_items',$restaurantData['optikitchen'])) {
                $progress = new ProgressBar($output, count($restaurantData['optikitchen']['sold_items']));
                $progress->start();

                foreach ($restaurantData['optikitchen']['sold_items'] as $soldItem) {
                    $p = $this->em->getRepository(ProductSold::class)->findOneBy(
                        array(
                            "name"=>trim($soldItem['item']),
                            "codePlu"=>trim($soldItem['plu'])
                        )
                    );
                    if ($p) {
                        $p->setEligibleForOptikitchen(true);
                        $this->em->persist($p);
                    }
                    $progress->advance();
                }
                $progress->finish();
            }


            /////////////////////////////////////////////////////////////
            //update products status (active/inactive)
            unset($filePath);
            unset($filename);
            unset($fileData);
            $filename = "restaurant_" . $restaurantCode . ".json";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No restaurant import file with the '" . $restaurantCode . "' restaurant code found ! ");
                $output->writeln("Cannot set products status. ");
                return;
            }
            try {
                $fileData = file_get_contents($filePath);
                $restaurantData = json_decode($fileData, true);
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                return;
            }

            $output->writeln("");
            $output->writeln("Start updating products status (active/inactive)...");
            $progress = new ProgressBar($output, count($restaurantData['ProductPurchased']) + count($restaurantData['ProductSold']));
            //create sync cmd for product purchased
            foreach ($restaurantData['ProductPurchased'] as $product) {
                $progress->advance();
                $p = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                    array('externalId' => $product['external_id'], 'originRestaurant' => $restaurant)
                );
                if ($p) {
                    $p->setStatus($product['status']);
                    $p->setActive(boolval($product['active']));
                    $this->em->persist($p);

                }else{
                    $this->logger->info('ProductPurchased not found in this restaurant :', array("productExternalId" => $product['external_id'], "Restaurant" => $restaurant->getName()));
                }
            }

            //create sync cmd for product sold
            foreach ($restaurantData['ProductSold'] as $product) {
                $progress->advance();
                $p = $this->em->getRepository(ProductSold::class)->findOneBy(
                    array('codePlu' => $product['codePlu'], 'originRestaurant' => $restaurant)//globalId will be the id of the supervision product
                );
                if ($p) {
                    $p->setActive(boolval($product['active']));
                    $this->em->persist($p);

                }else{
                    $this->logger->info('ProductSold not found in this restaurant :', array("productPlu" => $product['codePlu'], "Restaurant" => $restaurant->getName()));
                }
            }


            $this->em->flush();

            $progress->finish();

        } catch (\Exception $e) {
            $output->writeln("");
            $output->writeln("Command failed ! ");
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("");
        $output->writeln("\nRestaurant " . $restaurant->getName() . " optikitchen parameters imported successfully.");

    }




}
