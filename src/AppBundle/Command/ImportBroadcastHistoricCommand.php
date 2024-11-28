<?php

namespace AppBundle\Command;

use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportBroadcastHistoricCommand extends ContainerAwareCommand
{
    private $em;
    private $dataDir;
    private $items;
    private $syncService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:broadcast:historic')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from file.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to import broadcast historic for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->syncService=$this->getContainer()->get('sync.create.entry.service');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->items = array();

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
                            $param=array();
                            parse_str($data[4],$param);
                            $this->items[] = array(
                                'cmd'=>trim($data[0]),
                                'status'=>trim($data[1]),
                                'syncDate'=>$data[2],
                                'createdAt'=>$data[3],
                                'params'=>$param,
                                'order'=>$data[5],
                                'direction'=>$data[6],
                                'globalProductID'=>$data[7],
                                'restaurantName'=>$data[8],
                                'restaurantCode'=>$data[9]
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

                            $this->items[] = array(
                                'cmd'=>trim($data['cmd']),
                                'status'=>trim($data['status']),
                                'syncDate'=>$data['syncDate'],
                                'createdAt'=>$data['createdAt'],
                                'params'=>$data['params'],
                                'order'=>$data['order'],
                                'direction'=>$data['direction'],
                                'globalProductID'=>$data['globalProductID'],
                                'restaurantName'=>$data['restaurantName'],
                                'restaurantCode'=>$data['restaurantCode']
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

        $output->writeln("Start importing broadcast historic...");
        $count = 0;

        $missingRestaurantCount=0;
        $missingPurchasedProduct=0;
        $missingSoldProduct=0;

        $progress = new ProgressBar($output, count($this->items));
        $progress->start();
        foreach ($this->items as $i) {
            try {
                $item = $this->em->getRepository(SyncCmdQueue::class)->createQueryBuilder('i')->join(
                    'i.product',
                    'p'
                )->join('i.originRestaurant', 'r')
                    ->where('i.status = :status')
                    ->andWhere('i.cmd = :cmd')
                    ->andWhere('p.globalProductID = :globalProductID')
                    ->andWhere('r.code = :code')
                    ->andWhere('i.order = :order')
                    ->andWhere('i.createdAt = :createdAt')
                    ->setParameter('status', $i['status'])
                    ->setParameter('cmd', $i['cmd'])
                    ->setParameter('globalProductID', $i['globalProductID'])
                    ->setParameter('order', $i['order'])
                    ->setParameter('createdAt', $i['createdAt'])
                    ->setParameter('code', $i['restaurantCode'])->getQuery()->getOneOrNullResult();
            }catch(\Exception $e ){
                continue;
            }

            //$item = $this->em->getRepository(SyncCmdQueue::class)->findOneBy();
            $progress->advance();
            if (is_null($item)) {
                $item = new SyncCmdQueue();

                $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($i['restaurantCode']);
                if ($restaurant) {
                    $item->setOriginRestaurant($restaurant);
                }else{
                    $missingRestaurantCount++;
                    continue;
                }

                $item->setStatus($i['status'])
                    ->setCmd($i['cmd'])
                    ->setOrder($i['order']);

                switch (strtolower($i['cmd']) ){
                    case SyncCmdQueue::DOWNLOAD_INV_ITEMS :
                        $product = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(array(
                            'globalProductID' => $i['globalProductID']
                        ));
                        if ($product) {
                          $item->setProduct($product);
                          $params=json_encode(['product_code' => $product->getGlobalProductID()]);
                          $item->setParams($params);
                        } else {
                            $missingPurchasedProduct++;
                            continue 2;
                        }
                        break;
                    case SyncCmdQueue::DOWNLOAD_SOLD_ITEMS :
                        $product = $this->em->getRepository(ProductSoldSupervision::class)->findOneBy(array(
                            'globalProductID' => $i['globalProductID']
                        ));
                        if ($product) {
                            $item->setProduct($product);
                            $params=json_encode(['globalProductID' => is_null($product->getGlobalProductID()) ? $product->getId() : $product->getGlobalProductID()]);
                            $item->setParams($params);
                        } else {
                            $missingSoldProduct++;
                            continue 2;
                        }
                        break;
                    default:
                        continue 2;

                }

                if($i['syncDate']){
                    $item->setSyncDate(new \DateTime($i['syncDate']));
                }
                if($i['createdAt']){
                    $item->setCreatedAt(new \DateTime($i['createdAt']));
                }

                $this->em->persist($item);
                $count++;

            }

            if (($count % 100) === 0) {
                $this->em->flush();
                $this->em->clear();
            }


        }

        $this->em->flush();
        $this->em->clear();
        $progress->finish();

        $output->writeln("");
        $output->writeln("-----> ".$count." broadcast historic entries imported.");

        if($missingRestaurantCount>0){
            $output->writeln("=> ".$missingRestaurantCount." entries can't be imported because of missing restanrants.");
        }
        if($missingPurchasedProduct>0){
            $output->writeln("=> ".$missingPurchasedProduct." entries can't be imported because of missing Purchased products.");
        }
        if($missingSoldProduct>0){
            $output->writeln("=>".$missingSoldProduct." entries can't be imported because of missing Sold products.");
        }
        $output->writeln("==> Broadcast historic import finished <==");


    }


}
