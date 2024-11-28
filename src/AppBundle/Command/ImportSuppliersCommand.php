<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\Supplier;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportSuppliersCommand
 * @package AppBundle\Command
 */
class ImportSuppliersCommand extends ContainerAwareCommand
{
    //row format : name  ;  designation  ;  code  ;  phone  ;  address  ;  active  ;  email  ;  zone  ;

    private $em;
    private $dataDir;
    private $suppliers = array(
            array(
                'name' => "VIDANGES",
                'designation' => "",
                'code' => "790",
                'phone' => "",
                'address' => "",
                'active' => 1,
                'email' => "vidanges@quick.fr",
                'zone' => "",
            ),
            array(
                'name' => "COCA COLA ENTREPRISES BELGIUM",
                'designation' => "Boissons",
                'code' => "587001",
                'phone' => "0032 800 99 179",
                'address' => "Bergense Steenweg 1424 - B-1070 Brussel",
                'active' => 1,
                'email' => "tyanakieva@cokecce.com",
                'zone' => "",
            ),
            array(
                'name' => "AIR LIQUIDE RODANGE",
                'designation' => "C02 LUX",
                'code' => "8273002",
                'phone' => "00352 20 88 12 49",
                'address' => "Zoning PED BP 20 - L-4801 Rodange",
                'active' => 1,
                'email' => "servicecleintscne.benelux@airliquide.com",
                'zone' => "",
            ),
            array(
                'name' => "MUNHOWEN",
                'designation' => "Boissons",
                'code' => "8272002",
                'phone' => "00352 48 33 33 1",
                'address' => "ZARE Est 14 -  L-4385 Ehlerange",
                'active' => 1,
                'email' => "info@munhowen.lu",
                'zone' => "",
            ),
            array(
                'name' => "BRASSERIE DE LUX DIEKIRCH",
                'designation' => "Boissons",
                'code' => "8274002",
                'phone' => "00352 80 21 31 999",
                'address' => "Rue de la Brasserie 1 - L-9214 Diekirch",
                'active' => 1,
                'email' => "CSC.FO.LUX@mouseldiekirch.lu",
                'zone' => "",
            ),
            array(
                'name' => "INTERBREW BELGIUM",
                'designation' => "",
                'code' => "589001",
                'phone' => "3216247906",
                'address' => "Vaartkom 31",
                'active' => 1,
                'email' => "",
                'zone' => "",
            ),
            array(
                'name' => "SOUTIRAGE LUXEMBOURGEOIS",
                'designation' => "",
                'code' => "5098002",
                'phone' => "003524851511",
                'address' => "2 rue de Joncs",
                'active' => 1,
                'email' => "",
                'zone' => "",
            ),
            array(
                'name' => "ACP BELGIUM",
                'designation' => "C02",
                'code' => "9457001",
                'phone' => "003213530316",
                'address' => "Boulevard du Souverain 142, 1170 Bruxelles",
                'active' => 1,
                'email' => "acp.belgium@test.fr",
                'zone' => "",
            ),
            array(
                'name' => "COCA COLA ENTERPRISES LUXEMBOURG",
                'designation' => "Boissons",
                'code' => "5098002",
                'phone' => "00352 48 51 51",
                'address' => "Rue des Joncs 2A - L-1818 Howald",
                'active' => 1,
                'email' => "kkubow@cokecce.com",
                'zone' => "",
            ),
            array(
                'name' => "HAPPY QUICK S.A.",
                'designation' => "siège adm. lux",
                'code' => "BFI1014",
                'phone' => "",
                'address' => "3-5 rue des Frênes - L-1549 Luxembourg-Cessange",
                'active' => 1,
                'email' => "",
                'zone' => "",
            ),
            array(
                'name' => "BIDFOOD",
                'designation' => "Distributeur resto",
                'code' => "2000003120",
                'phone' => "0032 71 59 98 00",
                'address' => "Avenue Deli XL 1 - B-6530 Thuin",
                'active' => 1,
                'email' => "csagrc1@bidfood.be",
                'zone' => "",
            ),
            array(
                'name' => "MESSER BELGIUM",
                'designation' => "Gaz",
                'code' => "588001",
                'phone' => "3222670811",
                'address' => "Wolwelaan 3 1830, Machelen",
                'active' => 1,
                'email' => "",
                'zone' => "",
            ),
            array(
                'name' => "Heintz",
                'designation' => "Bière Diekirch Lux",
                'code' => "0003",
                'phone' => "00352 99 80 81 1",
                'address' => "Z.I. Op der Hei - L-9809 Hosingen",
                'active' => 1,
                'email' => "",
                'zone' => "",
            ),
            array(
                'name' => "Eurocarbo",
                'designation' => "C02 BE",
                'code' => "594001",
                'phone' => "0032 89 71 10 00",
                'address' => "Dr. Philipsstraat 6 - NL-6136 XZ Sittard",
                'active' => 1,
                'email' => "",
                'zone' => "",
            ),
            array(
                'name' => "Inbev",
                'designation' => "Bière Belgique",
                'code' => "589001",
                'phone' => "0032 2 200 60 50",
                'address' => "Brouwerijplein 1 - B-3000 Leuven",
                'active' => 1,
                'email' => "",
                'zone' => "",
            )
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:suppliers')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of import from csv file.')
            ->setDescription('Command to import supplier for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('file');
        if (isset($argument)) {
            $filename = $argument.".csv";
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No csv import file with the '".$argument."' name !");

                return;
            }
            try {
                // Import du fichier CSV
                $this->suppliers = array();
                if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter
                    $output->writeln("---->Import mode: CSV file.");
                    while (($data = fgetcsv(
                            $handle,
                            0,
                            ";"
                        )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                        $this->suppliers[] = array(
                            "name" => $data[0],
                            "designation" => $data[1],
                            "code" => $data[2],
                            "phone" => $data[3],
                            "address" => $data[4],
                            "active" => boolval($data[5]),
                            "email" => $data[6],
                            "zone" => $data[7],
                        );

                    }
                    fclose($handle);
                } else {
                    $output->writeln("Cannot open the csv file! Exit command...");

                    return;
                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return;
            }

        } else {
            $output->writeln("---->Import mode: Default.");
        }

        $output->writeln("Start importing Suppliers...");
        $count = 0;

        foreach ($this->suppliers as $s) {
            $supplier = $this->em->getRepository(Supplier::class)->findOneBy(
                array(
                    'name' => $s['name'],
                )
            );
            if (!$supplier) {
                $supplier = new Supplier();
                $supplier
                    ->setEmail($s['email'])
                    ->setActive($s['active'])
                    ->setName($s['name'])
                    ->setDesignation($s['designation'])
                    ->setCode($s['code'] ? $s['code'] : strval(rand(0, 1000) + rand(0, 1000)))
                    ->setZone($s['zone'])
                    ->setAddress($s['address'])
                    ->setPhone($s['phone']);

                $this->em->persist($supplier);
                $output->writeln("Import Supplier => ".$s['name']);
                $count++;
            }else{
                $output->writeln("-> Supplier [".$s['name']."] already exist! Skipping it...");
            }


        }

        $this->em->flush();

        $output->writeln("----> ".$count." suppliers imported.");
        $output->writeln("==> Suppliers import finish <==");

    }
}
