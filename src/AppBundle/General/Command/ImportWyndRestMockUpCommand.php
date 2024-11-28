<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/03/2016
 * Time: 11:10
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

class ImportWyndRestMockUpCommand extends ContainerAwareCommand
{

    private $url;
    private $apiUser;
    private $secretKey;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:wynd:rest:import:mock')
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->addArgument('startDateMock', InputArgument::REQUIRED)
            ->addArgument('endDateMock', InputArgument::REQUIRED)
            ->setDescription('Import Wynd Tickets from the REST API.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->url = $this->getContainer()->getParameter("wynd.url");
        $this->apiUser = $this->getContainer()->getParameter("wynd.api.user");
        $this->secretKey = $this->getContainer()->getParameter("wynd.api.secretkey");
        $this->logger = $this->getContainer()->get('logger');
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";
        $startDate = $input->getArgument('startDate');
        $endDate = $input->getArgument('endDate');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $startDateMock = $input->getArgument('startDateMock');
        $endDateMock = $input->getArgument('endDateMock');
        $startDateMock = date_create_from_format($supportedFormat, $startDateMock);
        $endDateMock = date_create_from_format($supportedFormat, $endDateMock);

        if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat(
            $startDate,
            $supportedFormat
        ) && Utilities::isValidDateFormat($endDate, $supportedFormat)) {
            $this->url .= "?date_start=".$startDate."&"."date_end=".$endDate;
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
            $startDate = new \DateTime('today');
            $endDate = new \DateTime('today');
            $this->url .= "?date_start=".$startDate->format($supportedFormat)."&"."date_end=".$endDate->format(
                $supportedFormat
            );
        }
        $this->logger->addInfo('Processing import tickets : '.$this->url, ['ImportWyndRestCommand']);
        $data = Request::get($this->url)
            ->addHeaders(
                array(
                    'Api-User' => $this->apiUser,
                    'Api-Hash' => $this->secretKey,
                )
            )
            ->expectsJson()
            ->send();

        $data = $data->body;

        if ($data->result == 'success') {
            $n = $em->getRepository("Financial:Ticket")->createQueryBuilder('t')->select("min(t.num)")->getQuery(
            )->getSingleScalarResult();
            $tickNum = intval($n) - 1;
            $initialData = clone $data;

            for ($i = 0; $i <= $endDateMock->diff($startDateMock)->days; $i++) {
                $date = Utilities::getDateFromDate($startDateMock, $i);

                echo "Mocking ".$date->format($supportedFormat)."===== \n";

                $filename = $this->getContainer()->getParameter('tmp_directory')."/wynd_".str_replace(
                    '/',
                    '_',
                    $date->format($supportedFormat)
                )."_".str_replace('/', '_', $date->format($supportedFormat)).".json";

                $newData = [];
                foreach ($data->data as $ticket) {
                    if ($ticket->invoice) {
                        $tic = $ticket->invoice;
                        $type = 'invoice';
                    } elseif ($ticket->order) {
                        $tic = $ticket->order;
                        $type = 'order';
                    } else {
                        continue;
                    }

                    //Modify dates
                    $tic->date = $date->format($supportedFormat);
                    $tic->date_ticket_start = $date->format($supportedFormat).' '.explode(
                        ' ',
                        $tic->date_ticket_start
                    )[1];
                    $tic->date_ticket_end = $date->format($supportedFormat).' '.explode(' ', $tic->date_ticket_end)[1];

                    $ticket2 = $ticket;
                    $ticket2->$type = $tic;

                    $newData[$tickNum] = $ticket2;

                    $tickNum--;
                }

                $initialData->data = $newData;
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
