<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Parameter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportParametersLabelsCommand extends ContainerAwareCommand
{

    private $em;
    private $dataDir;
    private $data;
    private $bags = array(
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 25,
            Parameter::PIECE_VALUE => 2,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 25,
            Parameter::PIECE_VALUE => 1,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 40,
            Parameter::PIECE_VALUE => 0.50,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 40,
            Parameter::PIECE_VALUE => 0.20,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 40,
            Parameter::PIECE_VALUE => 0.10,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 50,
            Parameter::PIECE_VALUE => 0.05,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 50,
            Parameter::PIECE_VALUE => 0.02,
            Parameter::TYPE => Parameter::BAG,
        ],
        [
            Parameter::BAG_CONTENT => 10,
            Parameter::ROL_CONTENT => 50,
            Parameter::PIECE_VALUE => 0.01,
            Parameter::TYPE => Parameter::BAG,
        ],
    );
    private $rolls = array(
        [
            Parameter::ROL_CONTENT => 25,
            Parameter::PIECE_VALUE => 2,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 25,
            Parameter::PIECE_VALUE => 1,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 40,
            Parameter::PIECE_VALUE => 0.50,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 40,
            Parameter::PIECE_VALUE => 0.20,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 40,
            Parameter::PIECE_VALUE => 0.10,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 50,
            Parameter::PIECE_VALUE => 0.05,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 50,
            Parameter::PIECE_VALUE => 0.02,
            Parameter::TYPE => Parameter::ROLS,
        ],
        [
            Parameter::ROL_CONTENT => 50,
            Parameter::PIECE_VALUE => 0.01,
            Parameter::TYPE => Parameter::ROLS,
        ],
    );
    private $bills = array(
        [
            Parameter::PIECE_VALUE => 100,
            Parameter::TYPE => Parameter::BILL,
        ],
        [
            Parameter::PIECE_VALUE => 50,
            Parameter::TYPE => Parameter::BILL,
        ],
        [
            Parameter::PIECE_VALUE => 20,
            Parameter::TYPE => Parameter::BILL,
        ],
        [
            Parameter::PIECE_VALUE => 10,
            Parameter::TYPE => Parameter::BILL,
        ],
        [
            Parameter::PIECE_VALUE => 5,
            Parameter::TYPE => Parameter::BILL,
        ],
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:parameters:labels')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name to import data from.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to import parameters labels for the platform.');
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

                            $translations = explode(",", $data[4]);
                            $translationsArray = array();
                            foreach ($translations as $translation) {
                                $tmp = explode("::", $translation);
                                $translationsArray[] = array(
                                    'local' => $tmp[0],
                                    'content' => $tmp[1],
                                );
                            }

                            $parsedValue = array();
                            parse_str($data[3], $parsedValue);

                            $this->data[] = array(
                                'type' => $data[0],
                                'label' => $data[1],
                                'untouchable' => boolval($data[2]),
                                'value' => $parsedValue,
                                'translations' => $translationsArray,
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
                                'type' => $data['type'],
                                'label' => $data['label'],
                                'untouchable' => $data['untouchable'],
                                'value' => $data['value'],
                                'translations' => $data['translations'],
                            );
                        }

                        fclose($handle);

                    } else {
                        $output->writeln("Cannot open the json file! Exit command...");

                        return;
                    }

                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return;
            }

        } else {
            $output->writeln("Please provide a valid import file name. ");

            return;
        }

        $output->writeln("Start importing parameters labels ( expense/recipe )...");
        $count = 0;

        foreach ($this->data as $l) {
            if (empty($l) || is_null($l['type'])) {
                continue;
            }

            $label = $this->em->getRepository(Parameter::class)->findOneBy(
                [
                    'type' => $l['type'],
                    'label' => $l['label'],
                ]
            );

            $isUpdate = true;
            if (is_null($label)) {
                $label = new Parameter();
                $isUpdate = false;
            }

            $label->setLabel($l['label'])
                ->setType($l['type'])
                ->setUntouchable(boolval($l['untouchable']));


            $this->em->persist($label);
            $label->setGlobalId($label->getId());
            if (is_array($l['value'])) {
                $value = array();
                if (is_int($l['value']['id'])) {
                    $value['id'] = $label->getId();
                } else {
                    $value['id'] = $l['value']['id'];
                }
                $value['deleted'] = boolval($l['value']['deleted']);

                if (array_key_exists('shown', $l['value'])) {
                    $value['shown'] = boolval($l['value']['shown']);
                }
            } else {
                $value = $l['value'];
            }

            $label->setValue($value);
            foreach ($l['translations'] as $translation) {
                $label->addLabelTranslation($translation['local'], $translation['content']);
            }

            $output->writeln("Parameter Label imported => ".$l['label']);
            $count++;

            if ($isUpdate) {
                $output->writeln("Parameter Label '".$l['label']."' already exist! Updating it...");
            }

        }

        /////////////////////////////////////////////////////
        //create the all exchange type paramters
        foreach ($this->bags as $bag) {
            $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                array(
                    "type" => Parameter::EXCHANGE_TYPE,
                    "value" => serialize($bag)
                )
            );
            if (!$parameter) {
                $parameter = new Parameter();
                $parameter
                    ->setType(Parameter::EXCHANGE_TYPE)
                    ->setValue(
                        $bag
                    );
                $this->em->persist($parameter);
                $output->writeln("Parameter Created => ".Parameter::EXCHANGE_TYPE." : ".Parameter::BAG);
                $this->em->flush();
                $count++;
            }else{
                $output->writeln("Default Parameter already exist, skipping it => ".Parameter::EXCHANGE_TYPE." : ".Parameter::BAG);
            }
        }
        foreach ($this->rolls  as $roll) {
            $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                array(
                    "type" => Parameter::EXCHANGE_TYPE,
                    "value" => serialize($roll)
                )
            );
            if (!$parameter) {
                $parameter = new Parameter();
                $parameter
                    ->setType(Parameter::EXCHANGE_TYPE)
                    ->setValue(
                        $roll
                    );
                $this->em->persist($parameter);
                $output->writeln("Parameter Created => ".Parameter::EXCHANGE_TYPE." : ".Parameter::ROLS);
                $this->em->flush();
                $count++;
            }else{
                $output->writeln("Default Parameter already exist, skipping it => ".Parameter::EXCHANGE_TYPE." : ".Parameter::ROLS);
            }
        }
        foreach ($this->bills  as $bill) {
            $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                array(
                    "type" => Parameter::EXCHANGE_TYPE,
                    "value" => serialize($bill)
                )
            );
            if (!$parameter) {
                $parameter = new Parameter();
                $parameter
                    ->setType(Parameter::EXCHANGE_TYPE)
                    ->setValue(
                        $bill
                    );
                $this->em->persist($parameter);
                $output->writeln("Parameter Created => ".Parameter::EXCHANGE_TYPE." : ".Parameter::BILL);
                $this->em->flush();
                $count++;
            }else{
                $output->writeln("Default Parameter already exist, skipping it => ".Parameter::EXCHANGE_TYPE." : ".Parameter::BILL);
            }
        }

        $cash = array(
            Parameter::PIECE_VALUE => 1,
            Parameter::TYPE        => Parameter::CASH
        );
        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                "type" => Parameter::EXCHANGE_TYPE,
                "value"=>serialize($cash)
            )
        );
        if (!$parameter) {
            $parameter = new Parameter();
            $parameter
                ->setType(Parameter::EXCHANGE_TYPE)
                ->setValue(
                   $cash
                );
            $this->em->persist($parameter);
            $output->writeln("Parameter Created => ".Parameter::EXCHANGE_TYPE." : ".Parameter::CASH);
            $this->em->flush();
            $count++;
        }else{
            $output->writeln("Default Parameter already exist, skipping it => ".Parameter::EXCHANGE_TYPE." : ".Parameter::CASH);
        }


        ////////////////////////////////////////////////////
        //Create parameter Error Counting Label Type
        $errorCashboxParam = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type" => Parameter::ERROR_COUNT_TYPE,
                    "value" => "cashbox_error",
                ]
            );
        $parameter = array(
            'label' => 'Erreur Caisse',
            'value' => 'cashbox_error',
            'labelTranslation' => 'Kasverschil',
        );
        if (!$errorCashboxParam) {
            $errorCashboxParam = new Parameter();
        }
        $errorCashboxParam->setType(Parameter::ERROR_COUNT_TYPE)
            ->setValue($parameter['value'])
            ->setLabel($parameter['label']);

        if (null !== $parameter['labelTranslation']) {
            $errorCashboxParam->addLabelTranslation('nl', $parameter['labelTranslation']);
        }
        $this->em->persist($errorCashboxParam);
        $this->em->flush();
        $count++;

        $errorChestParam = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type" => Parameter::ERROR_COUNT_TYPE,
                    "value" => "chest_error",
                ]
            );
        $parameter = array(
            'label' => 'Erreur coffre',
            'value' => 'chest_error',
            'labelTranslation' => 'Koffer Verschil',
        );
        if (!$errorChestParam) {
            $errorChestParam = new Parameter();
        }
        $errorChestParam->setType(Parameter::ERROR_COUNT_TYPE)
            ->setValue($parameter['value'])
            ->setLabel($parameter['label']);

        if (null !== $parameter['labelTranslation']) {
            $errorChestParam->addLabelTranslation('nl', $parameter['labelTranslation']);
        }
        $this->em->persist($errorChestParam);
        $this->em->flush();
        $count++;

        /////////////
        $cashPaymentParam = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type" => Parameter::CASH_PAYMENT_TYPE,
                    "value" => "cash_payment",
                ]
            );
        $parameter = array(
            'label' => 'Espèces',
            'value' => 'cash_payment',
            'labelTranslation' => 'Contanten',
        );
        if (!$cashPaymentParam) {
            $cashPaymentParam = new Parameter();
        }
        $cashPaymentParam->setType(Parameter::CASH_PAYMENT_TYPE)
            ->setValue($parameter['value'])
            ->setLabel($parameter['label']);

        if (null !== $parameter['labelTranslation']) {
            $cashPaymentParam->addLabelTranslation('nl', $parameter['labelTranslation']);
        }
        $this->em->persist($cashPaymentParam);
        $this->em->flush();
        $count++;

        $output->writeln("----> ".$count." parameters labels imported.");
        $output->writeln("==> Parameters labels import finished <==");

    }


}
