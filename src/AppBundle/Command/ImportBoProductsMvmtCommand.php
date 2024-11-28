<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportBoProductsMvmtCommand extends ContainerAwareCommand
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
            ->setName('saas:import:products:mvmt:data')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant products mvmt data form csv file exported by a BO instance.');
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
            $restaurantCode = trim($input->getArgument('restaurantCode'));
        } else {
            $helper = $this->getHelper('question');
            $question = new Question(
                'Please enter restaurant code (found at the end of json file name : stockData_restaurant_xxxx.json ) :'
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

        /************ Start the import process *****************/


        try {
            $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurantCode);

            if (!$restaurant) {
                $output->writeln("No restaurant with the '".$restaurantCode."' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");
                return;
            }
            $restaurantId=$restaurant->getId();


            ///////////////////////////////////////////////////////////////////////
            /// product Mvmt import
            $filename = "productPurchasedMvmt_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("Cannot import Products Purchased Movements for this restaurant :");
                $output->writeln("No Products Purchased Movements import file with the '" . $restaurantCode . "' restaurant code found !");
                return;
            }

            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);

            if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier
                $output->writeln("Start importing Products Purchased Movements...");
                $addedProductsPurchasedMovements = 0;
                $skippedProductsPurchasedMovements = 0;
                $i = 0;// number of iteration
                $j=0;// counter used for binding values in sql query
                $header = fgets($handle);//load the header
                $raw_query="INSERT INTO product_purchased_mvmt(
	id, product_id, origin_restaurant_id, date_time, variation, source_id, stock_qty, type, buying_cost, label_unit_exped, label_unit_inventory, label_unit_usage, inventory_qty, usage_qty, deleted, created_at, updated_at, synchronized, import_id)
	VALUES ";
                $query_values="";

                $progress->start();
                while (($data = fgetcsv($handle, 0, ";")) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire
                    $i++;
                    $j++;
                    $movementsData[] = array(
                        'id' => $data[0],
                        'date_time' => $data[1],
                        'variation' => $data[2],
                        'source_id' => $data[3],
                        'stock_qty' => $data[4],
                        'type' => $data[5],
                        'buying_cost' => $data[6],
                        'label_unit_exped' => $data[7],
                        'label_unit_inventory' => $data[8],
                        'label_unit_usage' => $data[9],
                        'inventory_qty' => $data[10],
                        'usage_qty' => $data[11],
                        'deleted' =>  boolval($data[12]) ? 1 : 0,
                        'created_at' => $data[13],
                        'updated_at' => $data[14],
                        'synchronized' => boolval($data[15]) ? 1 : 0,
                        'product_id' => $data[16],
                        'product_external_id' => $data[17],
                        'global_product_id' => $data[18],
                        'product_name' => trim($data[19])
                    );

                    //set the table to get the source id from by type
                    $source_table="";
                    switch ($data[5]) {
                        case ProductPurchasedMvmt::DELIVERY_TYPE:
                            $source_table="delivery_line";
                            break;
                        case ProductPurchasedMvmt::RETURNS_TYPE:
                            $source_table="return_line";
                            break;
                        case ProductPurchasedMvmt::SOLD_TYPE:
                            $source_table="ticket_line";
                            break;
                        case ProductPurchasedMvmt::INVENTORY_TYPE:
                            $source_table="inventory_line";
                            break;
                        case ProductPurchasedMvmt::PURCHASED_LOSS_TYPE:
                            $source_table="loss_line";
                            break;
                        case ProductPurchasedMvmt::SOLD_LOSS_TYPE:
                            $source_table="loss_line";
                            break;
                        case ProductPurchasedMvmt::TRANSFER_IN_TYPE:
                            $source_table="transfer_line";
                            break;
                        case ProductPurchasedMvmt::TRANSFER_OUT_TYPE:
                            $source_table="transfer_line";
                            break;
                    }

                    $query_values .= "(NEXTVAL('product_purchased_mvmt_id_seq') , ( SELECT pp.id FROM product p, product_purchased pp where p.origin_restaurant_id = :origin_restaurant_id AND p.id = pp.id AND pp.external_id = :external_id$j AND p.name = :name$j), :origin_restaurant_id, :date_time$j, :variation$j, (SELECT id FROM ".$source_table." where import_id = :source_id$j LIMIT 1), :stock_qty$j, :type$j, :buying_cost$j, :label_unit_exped$j, :label_unit_inventory$j, :label_unit_usage$j, :inventory_qty$j, :usage_qty$j, :deleted$j, :created_at$j, :updated_at$j, :synchronized$j, :import_id$j ),";

                    if (($i % 100) === 0 || $i >= $linesCount) {
                        $this->em->getConnection()->beginTransaction();
                        $query_values=rtrim($query_values,',');
                        $sql=$raw_query.$query_values." ON CONFLICT (import_id) DO NOTHING ;  ";
                        $statement = $this->em->getConnection()->prepare($sql);
                        // Set parameters
                        $c=1;
                        foreach ($movementsData as  $mvmt){
                            $statement->bindValue('origin_restaurant_id', $restaurantId);
                            $statement->bindValue('type'.$c, $mvmt['type']);
                            $statement->bindValue('date_time'.$c, $mvmt['date_time']);
                            $statement->bindValue('variation'.$c, floatval($mvmt['variation']));
                            $statement->bindValue('source_id'.$c, $mvmt['source_id']. "_" . $restaurantCode);
                            $statement->bindValue('stock_qty'.$c, floatval($mvmt['stock_qty']));
                            $statement->bindValue('type'.$c, $mvmt['type']);
                            $statement->bindValue('buying_cost'.$c, $mvmt['buying_cost']);
                            $statement->bindValue('label_unit_exped'.$c, $mvmt['label_unit_exped']);
                            $statement->bindValue('label_unit_inventory'.$c, $mvmt['label_unit_inventory']);
                            $statement->bindValue('label_unit_usage'.$c, $mvmt['label_unit_usage']);
                            $statement->bindValue('inventory_qty'.$c, floatval($mvmt['inventory_qty']));
                            $statement->bindValue('usage_qty'.$c, floatval($mvmt['usage_qty']));
                            $statement->bindValue('deleted'.$c, $mvmt['deleted']);
                            $statement->bindValue('created_at'.$c, $mvmt['created_at']);
                            $statement->bindValue('updated_at'.$c, $mvmt['updated_at']);
                            $statement->bindValue('synchronized'.$c, $mvmt['synchronized']);
                            $statement->bindValue('import_id'.$c, $mvmt['id']. "_" . $restaurantCode);
                            $statement->bindValue('external_id'.$c, $mvmt['product_external_id']);
                            $statement->bindValue('name'.$c, $mvmt['product_name']);

                            $c++;
                            $addedProductsPurchasedMovements++;
                        }

                        $statement->execute();
                        $this->em->getConnection()->commit();
                        $query_values="";
                        $movementsData=null;
                        unset($movementsData);
                        $this->em->clear();
                        gc_collect_cycles();
                        $j=0;

                    }

                    $progress->advance();

                }

                fclose($handle);
                $this->em->clear();
                $progress->finish();
                $output->writeln("");
                $output->writeln("=> Total Products Purchased Movements treated = " . $i);
                $output->writeln("--> " . $addedProductsPurchasedMovements . " Products Purchased Movements imported for restaurant " . $restaurant->getName());

            } else {
                $output->writeln("Cannot open the  Products Purchased Movements csv file! Exit command...");
                return;
            }


        }catch (\Exception $e){
            $output->writeln("Command failed ! Rollback...");
            $this->em->getConnection()->rollBack();
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("\n====> Restaurant [".$restaurant->getName()."] products purchased mvmt data imported successfully.");

    }



}
