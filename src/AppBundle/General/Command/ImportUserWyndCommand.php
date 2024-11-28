<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/04/2016
 * Time: 10:47
 */

namespace AppBundle\General\Command;

use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Httpful\Request;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportUserWyndCommand extends ContainerAwareCommand
{

    private $url;
    private $apiUser;
    private $secretKey;
    private $restaurant;
    private $dataDir;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:user:wynd:import')
            ->addArgument('filename', InputArgument::OPTIONAL)
            ->setDescription('Import Wynd User from the REST API.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->url = $this->getContainer()->getParameter("wynd.api.rest.user");
        $this->apiUser = $this->getContainer()->getParameter("wynd.api.user");
        //$this->secretKey = $this->getContainer()->getParameter("wynd.api.secretkey");
        $this->restaurant = $this->getContainer()->getParameter("api_user_code");
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/";
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->addInfo('Processing import Users', ['quick:user:wynd:import']);
        $this->url .= $this->restaurant;

        $this->logger->addInfo('Processing import Users : '.$this->url, ['ImportUserWyndCommand']);

        if ($input->hasArgument('filename') && trim($input->getArgument('filename')) != '') {
            $filename = $input->getArgument('filename');
        } else {
            $filename = $this->dataDir.'wynd.new.json';
        }

        if (!file_exists($filename)) {
            $this->logger->addDebug($filename." is not existing !", ['ImportWyndCommand']);

            return;
        }

        $file = fopen($filename, 'r');

        if (!$file) {
            $this->logger->addDebug("Cannot open file $filename", ['ImportWyndCommand']);

            return;
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $data = file_get_contents($filename);

        $json = json_decode($data, true);

        if (!$json) {
            $this->logger->addDebug("Data in the ".$filename." is not JSON format !", ['ImportWyndCommand']);

            return;
        }

        if ($json['result'] == 'success') {
            $newUsersArray = array();
            foreach ($json['data'] as $user) {
                $employee = $this->em->getRepository('Staff:Employee')->findOneByWyndId($user['rowid']);
                if (!$employee) {
                    $employee = $this->em->getRepository('Staff:Employee')->findOneByUsername($user['login']);
                    if (!$employee) {
                        $employee = new Employee();
                        $employee
                            ->setFirstConnection(false)
                            ->setActive(false);
                    }
                }
                $employee
                    ->setWyndId($user['rowid'])
                    ->setUsername($user['login'])
                    ->setFirstName($user['firstname'])
                    ->setLastName($user['lastname'])
                    ->setMatricule($user['matricule'])
                    ->setTimeWork($user['time_work'])
                    ->setFromWynd(true)
                    ->setDeleted(false);
                $this->em->persist($employee);
                $this->em->flush();
                $newUsersArray[] = $employee->getId();
                echo "user with WyndId ".$user->rowid." updated with success \n";
            }
            $this->getContainer()->get('staff.service')->deleteUsers($newUsersArray, $this->restaurant);
        }
    }
}
