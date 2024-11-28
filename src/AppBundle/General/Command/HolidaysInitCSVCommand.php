<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/05/2016
 * Time: 18:08
 */

namespace AppBundle\General\Command;

use AppBundle\General\Entity\Holiday;
use Httpful\Request;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HolidaysInitCSVCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:holidays:init:csv')->setDefinition(
            []
        )->setDescription('Initialize holidays from csv');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        echo "Initialize  Holidays.. \n";

        $t1 = microtime();

        $dataPath = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/';
        $file = fopen($dataPath.'holidays.csv', 'r');
        $header = fgets($file);

        echo "Removing old data.. \n";
        $holidays = $this->em->getRepository('General:Holiday')->findAll();
        foreach ($holidays as $holiday) {
            $this->em->remove($holiday);
        }
        $this->em->flush();

        echo "Add new data.. \n";
        while ($item = fgets($file)) {
            $item = explode(';', $item);

            $date = $item[1];
            $name = $item[2];
            $holiday = new Holiday();
            $holiday->setName($name)
                ->setDate(\DateTime::createFromFormat('Y-m-d', $date));

            $this->em->persist(clone $holiday);
        }

        $this->em->flush();
        $t2 = microtime();

        echo "Time for adding is ".($t2 - $t1)." ms \n";
    }
}
