<?php

namespace AppBundle\Command;


use AppBundle\Merchandise\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ExportProductsTranslationsCommand extends ContainerAwareCommand
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
            ->setName('saas:product:translations:export')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output file format (csv/json)',
                ""
            )
            ->setDescription('Command to export product translations to csv/json file.');
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

        $products = $this->em->getRepository(Product::class)->findAll();




        if(empty($products))
        {
            $output->writeln("No data to export! Ending command...");
            return;
        }



        foreach ($products as $product){

            $result=array();
            $result['id']=$product->getId();
            $result['origin_restaurant_code']=$product->getOriginRestaurant()->getCode();
            $result['name']=$product->getName();
            $result['name_fr']=$product->getName('fr');
            $result['name_nl']=$product->getName('nl');

            $this->data[]=$result;
        }

        //prepare the json file and save data into it
        try {

            switch(strtoupper($this->exportFormat))
            {
                case 'JSON';
                    $output->writeln('====> Start exporting products translations to json file...');
                    $fileName = 'productsTranslations.json';
                    $filePath=$this->prepareJson($fileName);
                    break;
                case 'CSV';
                    $output->writeln('====> Start exporting restaurant translations to csv file...');
                    $fileName = 'productsTranslations.csv';
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
