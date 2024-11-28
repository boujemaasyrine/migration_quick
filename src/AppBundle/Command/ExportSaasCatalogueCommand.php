<?php

namespace AppBundle\Command;

use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ExportSaasCatalogueCommand extends ContainerAwareCommand
{
    private $exportFormat;
    private $em;
    private $dataDir;
    private $data;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:export:catalogue')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output file format (csv/json)',
                ""
            )
            ->setDescription('Command to export saas catalogue to csv/json file.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/export/saas/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->data=array();
        $this->exportFormat = null;

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $option = $input->getOption("format");
        if (trim($option) !== "") {
            if (strtolower($option) === "json") {
                $this->exportFormat = "JSON";
            } elseif (strtolower($option) === "csv") {
                $this->exportFormat = "CSV";
            } else {
                $output->writeln("Invalid option passed ! Please provide a valid option : csv or json.");
                return;
            }
        } else {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select the export format :',
                array('CSV', 'JSON'),
                0
            );
            $question->setErrorMessage('Invalid choice !');
            $this->exportFormat = $helper->ask($input, $output, $question);
        }

        $items = $this->em->getRepository(ProductPurchasedSupervision::class)->findAll();

        if(empty($items))
        {
            $output->writeln("No data to export! Ending command...");
            return;
        }

        foreach ($items as $item){

            $suppliers=$item->getSuppliers();
            $suppliersCode=array();
            foreach ($suppliers as $supplier){
                $suppliersCode[]=array("name"=>$supplier->getName(),"code"=>$supplier->getCode());// the code is not unique so we use the name
            }

            $restaurants=$item->getRestaurants();
            $restaurantsCode=array();

            foreach ($restaurants as $restaurant){
                $restaurantsCode[]=$restaurant->getCode();
            }

            $this->data[]=array(
                "id"=> $item->getId(),
                "name"=> $item->getName(),
                "name_translation" => $item->getNameTranslation("nl"),
                "global_product_id"=> $item->getGlobalProductID(),
                "reference"=> $item->getReference(),
                "active" => $item->getActive(),
                "external_id" => $item->getExternalId(),// the couple external_id / supplier can identify a productPurchased
                "status" => $item->getStatus(),
                "type" => $item->getType(),
                "storage_condition" => $item->getStorageCondition(),
                "buying_cost" =>$item->getBuyingCost(),
                "label_unit_exped"=> $item->getLabelUnitExped(),
                "label_unit_inventory"=> $item->getLabelUnitInventory(),
                "label_unit_usage"=> $item->getLabelUnitUsage(),
                "inventory_qty"=> $item->getInventoryQty(),
                "usage_qty"=> $item->getUsageQty(),
                "id_item_inv"=>$item->getIdItemInv(),
                "dlc" => $item->getDlc(),
                "category_name"=> $item->getProductCategory()->getName(),
                "suppliers"=>$suppliersCode,
                "restaurants"=>$restaurantsCode
            );

        }

        //prepare the json file and save data into it
        try {

            switch(strtoupper($this->exportFormat))
            {
                case 'JSON';
                    $output->writeln('====> Start exporting restaurant inventory items to json file...');
                    $fileName = 'inventoryItems.json';
                    $filePath=$this->prepareJson($fileName);
                    break;
                case 'CSV';
                    $output->writeln('====> Start exporting restaurant inventory items to csv file...');
                    $fileName = 'inventoryItems.csv';
                    $filePath=$this->prepareCsv($fileName);
                    break;
                default;
                    $output->writeln("invalid output format ! Ending command...");
                    return;
            }

            $output->writeln("Data successfully exported to : ".$filePath);

        } catch (\Exception $e) {
            $output->writeln("Data Export failed !");
            $output->writeln("Exception while creating the output file:");
            $output->writeln($e->getMessage());
        }


        /////////////////////////////////////////////////////
        /// export sold items
        $this->data=array();
        unset($items);

        $query = $this->em
            ->getRepository(ProductSoldSupervision::class)
            ->createQueryBuilder('p')
            ->getQuery();

        $items = $query->getArrayResult();


        if(empty($items))
        {
            $output->writeln("No data to export! Ending command...");
            return;
        }

        foreach ($items as &$item){

            $entity = $this->em->getRepository(ProductSoldSupervision::class)->find($item['id']);

            $item["name_translation"] = $entity->getNameTranslation("nl");
            $item["productPurchasedName"] = $entity->getProductPurchasedName();
            $item["productPurchasedExternalId"]=NULL;
            if($entity->getProductPurchased()){
                $item["productPurchasedExternalId"] = $entity->getProductPurchased()->getExternalId();
            }


            $recipesArray=array();
            foreach ($entity->getRecipes() as $recipe){

                //prepare item solding Canal
                $soldingCanal=$recipe->getSoldingCanal();
                $soldingCanalArray=array(
                    'label'=>$soldingCanal->getLabel(),
                    'type' =>$soldingCanal->getType(),
                    'wyndMppingColumn' =>$soldingCanal->getWyndMppingColumn(),
                    'default'=> $soldingCanal->getDefault()
                );

                //prepare item recipes
                $recipeLinesArray=array();
                foreach ($recipe->getRecipeLines() as $recipeLine){
                    $recipeLinesArray[]=array(
                        'id' => $recipeLine->getId(),
                        'qty' =>$recipeLine->getQty(),
                        'supplierCode' =>$recipeLine->getSupplierCode(),
                        'productPurchasedName'=>$recipeLine->getProductPurchased()->getName(),
                        'productPurchasedExternalId'=>$recipeLine->getProductPurchased()->getExternalId()
                    );
                }
                $recipesArray[]=array(
                    'id' => $recipe->getId(),
                    'externalId'=> $recipe->getExternalId(),
                    'active' => $recipe->getActive(),
                    'revenuePrice'=> $recipe->getRevenuePrice(),
                    'soldingCanal'=>$soldingCanalArray,
                    'recipeLines'=>$recipeLinesArray,
                    'globalId'=>$recipe->getGlobalId()
                );
            }

            $item["recipes"]=$recipesArray;

            //prepare item eligible restaurants
            $restaurants=$entity->getRestaurants();
            $restaurantsCode=array();
            foreach ($restaurants as $restaurant){
                $restaurantsCode[]=$restaurant->getCode();
            }
            $item['restaurants']=$restaurantsCode;

            $this->data[]=$item;
        }

        //prepare the json file and save data into it
        try {

            switch(strtoupper($this->exportFormat))
            {
                case 'JSON';
                    $output->writeln('====> Start exporting restaurant sold products to json file...');
                    $fileName = 'soldItems.json';
                    $filePath=$this->prepareJson($fileName);
                    break;
                case 'CSV';
                    $output->writeln('====> Start exporting restaurant sold products to csv file...');
                    $fileName = 'soldItems.csv';
                    $filePath=$this->prepareCsv($fileName);
                    break;
                default;
                    $output->writeln("invalid output format ! Ending command...");
                    return;
            }

            $output->writeln("Data successfully exported to : ".$filePath);

        } catch (\Exception $e) {
            $output->writeln("Data Export failed !");
            $output->writeln("Exception while creating the output file:");
            $output->writeln($e->getMessage());
        }

    }

    /**
     * @param $fileName
     * @return string
     */
    private function prepareCsv($fileName){
        $filePath = $this->dataDir.$fileName;
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->dumpFile($filePath, "");
        $handle = fopen($filePath, 'r+');
        foreach ($this->data as $row){
            $row['suppliers']=implode(",", $row['suppliers']);
            $row['restaurants']=implode(",", $row['restaurants']);
            fputcsv($handle, $row,";");
        }
        fclose($handle);
        return $filePath;
    }

    /**
     * @param $fileName
     * @return string
     */
    private function prepareJson($fileName){
        $filePath = $this->dataDir.$fileName;
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->dumpFile($filePath, json_encode($this->data, JSON_PRETTY_PRINT));
        return $filePath;
    }

}
