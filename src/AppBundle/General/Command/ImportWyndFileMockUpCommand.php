<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 19/04/2016
 * Time: 12:20
 */

namespace AppBundle\General\Command;

use AppBundle\ToolBox\Service\RestJsonClient;
use AppBundle\ToolBox\Utils\Utilities;
use Httpful\Request;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportWyndFileMockUpCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:wynd:file:import:mock')
            ->addArgument('startDateMock', InputArgument::REQUIRED)
            ->addArgument('endDateMock', InputArgument::REQUIRED)
            ->addArgument('fileName', InputArgument::REQUIRED)
            ->addArgument('shuffle', InputArgument::OPTIONAL)
            ->setDescription('Import Wynd Tickets from json file.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger = $this->getContainer()->get('logger');
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $startDateMock = $input->getArgument('startDateMock');
        $endDateMock = $input->getArgument('endDateMock');
        $shuffle = boolval($input->getArgument('shuffle'));
        $startDateMock = date_create_from_format($supportedFormat, $startDateMock);
        $endDateMock = date_create_from_format($supportedFormat, $endDateMock);

        $originFile = $this->getContainer()->getParameter('tmp_directory')."/".$input->getArgument('fileName');

        if (!file_exists($originFile)) {
            echo $originFile." is not existing ! \n";

            return;
        }

        $str = file_get_contents($originFile);
        $data = json_decode($str, true);

        if ($data['result'] == 'success') {
            $n = $em->getRepository("Financial:Ticket")->createQueryBuilder('t')->select("min(t.num)")->getQuery(
            )->getSingleScalarResult();
            $tickNum = intval($n) - 1;
            $initialData = $data;

            for ($i = 0; $i <= $endDateMock->diff($startDateMock)->days; $i++) {
                $date = Utilities::getDateFromDate($startDateMock, $i);

                echo "Mocking ".$date->format($supportedFormat)."===== \n";

                $filename = $this->getContainer()->getParameter('tmp_directory')."/wynd_".str_replace(
                    '/',
                    '_',
                    $date->format($supportedFormat)
                )."_".str_replace('/', '_', $date->format($supportedFormat)).".json";

                if ($shuffle) {
                    shuffle($data['data']['invoices']);
                    shuffle($data['data']['orders']);
                }

                $newData = [];
                $tmp = 0;
                foreach ($data['data']['invoices'] as $ticket) {
                    if (!$shuffle || ($shuffle && $tmp++ < 10)) {
                        if ($ticket['invoice']) {
                            $tic = $ticket['invoice'];
                            $type = 'invoice';
                        } elseif ($ticket['order']) {
                            $tic = $ticket['order'];
                            $type = 'order';
                        } else {
                            continue;
                        }

                        //Modify dates
                        $tic['date'] = $date->format($supportedFormat);
                        $tic['date_ticket_start'] = $date->format($supportedFormat).' '.explode(
                            ' ',
                            $tic['date_ticket_start']
                        )[1];
                        $tic['date_ticket_end'] = $date->format($supportedFormat).' '.explode(
                            ' ',
                            $tic['date_ticket_end']
                        )[1];

                        $ticket2 = $ticket;
                        $ticket2[$type] = $tic;

                        $newData[$tickNum] = $ticket2;

                        $tickNum--;
                    }
                }
                $initialData['data']['invoices'] = $newData;

                $newData = [];
                $tmp = 0;
                foreach ($data['data']['orders'] as $ticket) {
                    if (!$shuffle || ($shuffle && $tmp++ < 10)) {
                        if ($ticket['invoice']) {
                            $tic = $ticket['invoice'];
                            $type = 'invoice';
                        } elseif ($ticket['order']) {
                            $tic = $ticket['order'];
                            $type = 'order';
                        } else {
                            continue;
                        }

                        //Modify dates
                        $tic['date'] = $date->format($supportedFormat);
                        $tic['date_ticket_start'] = $date->format($supportedFormat).' '.explode(
                            ' ',
                            $tic['date_ticket_start']
                        )[1];
                        $tic['date_ticket_end'] = $date->format($supportedFormat).' '.explode(
                            ' ',
                            $tic['date_ticket_end']
                        )[1];

                        $ticket2 = $ticket;
                        $ticket2[$type] = $tic;

                        $newData[$tickNum] = $ticket2;

                        $tickNum--;
                    }
                }
                $initialData['data']['orders'] = $newData;

                file_put_contents($filename, json_encode($initialData));
                echo $this->getContainer()->get('toolbox.command.launcher')->execute(
                    'quick:wynd:import '.$filename,
                    true,
                    true,
                    false
                );
            }

            $this->logger->addInfo('Processing import tickets terminated with success. ', ['ImportWyndRestCommand']);
        } else {
            echo "ERROR \n";
            $this->logger->addError('Processing import tickets failed. ', ['ImportWyndRestCommand']);
        }
    }
}
