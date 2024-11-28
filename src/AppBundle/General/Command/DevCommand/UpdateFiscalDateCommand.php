<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command\DevCommand;

use Doctrine\ORM\EntityManager;
use AppBundle\Administration\Entity\Parameter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Httpful\Request;
use Monolog\Logger;

class UpdateFiscalDateCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    //    /**
    //     * @var String
    //     */
    //    private $url;
    /**
     * {@inheritDoc}
     */
    /**
     * @var Logger
     */
    private $logger;

    protected function configure()
    {
        $this->setName('quick:fiscal:date:refresh')->setDefinition(
            []
        )->setDescription('Update fiscal date.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        //        $this->url = $this->getContainer()->getParameter("wynd.api.fiscal_date");
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
    }

    private function setFiscalDate($fiscalDate)
    {
        $params = $this->em->getRepository("Administration:Parameter")->findBy(
            array(
                'type' => 'date_fiscale',
            )
        );
        if (isset($params) && !empty($params)) {
            /**
             * @var Parameter $param
             */
            foreach ($params as $param) {
                $param->setValue($fiscalDate->format('d/m/Y'));
                $this->em->flush();
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            //            $data = Request::get($this->url)
            //                ->expectsJson()
            //                ->send();
            //            $data = $data->body;
            //            if (isset($data->result) && $data->result == "success" && isset($data->data->dateFiscaleNow)) {
            //                $fiscalDate = date_create_from_format('Y-m-d', $data->data->dateFiscaleNow);
            //                $this->logger->addInfo('API Success, Updating fiscal date to : ' . $fiscalDate->format('d/m/Y'), ['UpdateFiscalDateCommand']);
            //            } else {
            $fiscalDate = new \DateTime();
            //                $this->logger->addInfo('API Error, Updating fiscal date to TODAY Value : ' . $fiscalDate->format('d/m/Y'), ['UpdateFiscalDateCommand']);
            //            }
            $this->setFiscalDate($fiscalDate);
            $this->logger->addInfo(
                'Updating fiscal date to : '.$fiscalDate->format('d/m/Y').' done with success',
                ['UpdateFiscalDateCommand']
            );
        } catch (\Exception $e) {
            $this->logger->addInfo(
                'Error while Updating fiscal date with exception: '.$e->getMessage(),
                ['UpdateFiscalDateCommand']
            );
            $fiscalDate = new \DateTime();
            $this->logger->addInfo(
                'Exception detected, Updating fiscal date to TODAY Value : '.$fiscalDate->format('d/m/Y'),
                ['UpdateFiscalDateCommand']
            );
            $this->setFiscalDate($fiscalDate);
            $this->logger->addInfo(
                'Updating fiscal date to : '.$fiscalDate->format('d/m/Y').' done with success',
                ['UpdateFiscalDateCommand']
            );
        }
    }
}
