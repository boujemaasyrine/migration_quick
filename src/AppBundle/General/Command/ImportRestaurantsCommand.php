<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/03/2016
 * Time: 11:42
 */

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Question\Question;

class ImportRestaurantsCommand extends ContainerAwareCommand
{

    private $dataDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:restaurants:import')->setDefinition(
            []
        )
            ->addArgument('filename', InputArgument::OPTIONAL)
            ->setDescription('Import all restaurants');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('filename') && trim($input->getArgument('filename')) != '') {
            $filename = $input->getArgument('filename');
        } else {
            $filename = $this->dataDir.'quicks.csv';
        }

        if (!file_exists($filename)) {
            echo $filename." is not existing ! \n";

            return;
        }

        $file = fopen($filename, 'r');

        if (!$file) {
            echo "Cannot open file $filename \n";

            return;
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        while ($line = fgetcsv($file, null, ';')) {
            // TODO amjed : assure toi qu'il y'a pas de doublon au niveau des restaurant lors de l'import
            $quick = new Restaurant();
            $code = $line[0];
            if (preg_match('/^[0-9]+$/', $code) === 0) {
                continue;
            }
            if (strpos('Luxembourg', $line[21]) !== false) {
                $code = '6'.$code;
            } else {
                $code = '2'.$code;
            }

            $quick
                ->setName($line[1])
                ->setCode(intval($code))
                ->setLang($line[2])
                ->setCustomerLang($line[3])
                ->setManager($line[4])
                ->setManagerEmail($line[5])
                ->setEmail($quick->getName()."@quick.fr.rec")
                ->setManagerPhone($line[6])
                ->setDmCf($line[7])
                ->setPhoneDmCf($line[8])
                ->setAddress($line[9])
                ->setZipCode($line[10])
                ->setCity($line[11])
                ->setPhone($line[12])
                ->setBtwTva($line[13])
                ->setCompanyName($line[14])
                ->setAddressCompany($line[15])
                ->setZipCodeCompany($line[16])
                ->setCityCorrespondance($line[17])
                ->setCyFtFpLg($line[18])
                ->setTypeCharte($line[19]);
            if (!empty($line[20])) {
                $quick->setFirstOpenning(\DateTime::createFromFormat('d/m/Y', $line[20]));
            }
            $quick
                ->setCluster($line[21])
                ->setType(Restaurant::COMPANY);

            if (!empty($line[22])) {
                switch (strtolower($line[22])) {
                    case "ouvert":
                        $quick->setActive(true);
                        break;
                    case "fermé":
                        $quick->setActive(false);
                        break;
                    default:
                        $quick->setActive(true);
                        break;
                }
            }

            echo "Importing Quick ".$quick->getName()."\n";
            $this->em->persist(clone $quick);
        }

        $this->em->flush();
    }
}
