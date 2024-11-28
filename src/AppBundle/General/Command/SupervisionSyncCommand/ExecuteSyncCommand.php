<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/05/2016
 * Time: 09:52
 */

namespace AppBundle\General\Command\SupervisionSyncCommand;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\General\Entity\SyncCmdQueue;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteSyncCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:sync:execute')->setDefinition(
            []
        )->setDescription('Getting Cmd');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('monolog.logger.synchro');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->logger->addDebug('ExecuteSyncCommand launched.', ['ExecuteSyncCommand']);
        echo "ExecuteSyncCommand launched. \n";
        $key = $this->generateRandomString(5);
        // check if lock is here
        $param = $this->em->getRepository(Parameter::class)
            ->findOneBy(['type' => Parameter::EXECUTE_SYNC]);
        if ($param) {
            $this->logger->addInfo('Existing lock checking timeout.', ['ExecuteSyncCommand']);
            echo "Existing lock checking timeout. \n";
            // Check timeout
            $now = new \DateTime('now');
            $diffInSeconds = $now->getTimestamp() - $param->getUpdatedAt()->getTimestamp();
            // If ticket lock wasn't updated since 1h delete it
            $this->logger->addInfo('Lock isn\'t updated since '.$diffInSeconds.'second', ['ExecuteSyncCommand']);
            echo "Lock isn\'t updated since ".$diffInSeconds."second. \n";
            if ($diffInSeconds > 7200) {
                $this->logger->addInfo('Lock expired for execute sync.', ['ExecuteSyncCommand']);
                echo "Lock expired for execute sync.\n";
                $this->em->remove($param);
                $this->em->flush();

                $param = new Parameter();
                $param->setType(Parameter::EXECUTE_SYNC)
                    ->setValue($key);
                $this->em->persist($param);
                $this->em->flush();
                $this->launchExecuteSync($key);
            } else {
                return;
            }
        } else {
            $this->logger->addInfo('No lock found.', ['ExecuteSyncCommand']);
            echo "'No lock found.'\n";
            $param = new Parameter();
            $param->setType(Parameter::EXECUTE_SYNC)
                ->setValue($key);
            $this->em->persist($param);
            $this->em->flush();
            $this->launchExecuteSync($key);
        }
    }

    public function launchExecuteSync($key)
    {
        $this->logger->addInfo('Processing execute sync.', ['ExecuteSyncCommand']);
        echo "Processing execute sync.\n";
        // Get new command for this BO QUICK
        $this->logger->addDebug('Launching get command from supervision.', ['ExecuteSyncCommand']);
        echo "Launching get command from supervision.\n";
        $em = $this->getContainer()
            ->get('doctrine.orm.entity_manager');
        $executingCmd = $em->getRepository(SyncCmdQueue::class)
            ->findBy(
                array(
                    'status' => SyncCmdQueue::EXECUTING,
                )
            );
        if ($executingCmd) {
            $this->logger->addDebug('ExecuteSyncCommand exiting.', ['ExecuteSyncCommand']);
            echo "ExecuteSyncCommand exiting.\n";

            return;
        }
        $today = new \DateTime();
        $today = $today->format('Y-m-d');

        $cmdLauncher = $this->getContainer()->get('toolbox.command.launcher');
        while ($em->getRepository(SyncCmdQueue::class)
                ->findOneBy(
                    [
                        'status' => SyncCmdQueue::WAITING,
                        'syncDate' => array($today, null)
                    ],
                    [
                        'order' => 'ASC',
                    ]
                ) && !$em->getRepository(SyncCmdQueue::class)
                ->findBy(
                    array(
                        'status' => SyncCmdQueue::EXECUTING,
                    )
                )) {//End condition while
            $cmd = $em->getRepository(SyncCmdQueue::class)
                ->findOneBy(
                    [
                        'status' => SyncCmdQueue::WAITING,
                        'syncDate' => array($today, null)
                    ],
                    [
                        'order' => 'ASC',
                    ]
                );

            $cmdStr = "quick:download:generic ";
            $cmdStr = $cmdStr." ".$cmd->getCmd()." ".$cmd->getId();
            $this->logger->debug("Invoking $cmdStr ", ['ExecuteSyncCommand']);
            echo "Invoking $cmdStr \n";
            echo $cmdLauncher->execute($cmdStr, true, true, false);
            echo "\n";

            $param = $this->em->getRepository(Parameter::class)
                ->findOneBy(['type' => Parameter::EXECUTE_SYNC]);
            if ($param->getValue() == $key) {
                $this->logger->addInfo('Updating current lock key : '.$key.'.', ['ExecuteSyncCommand']);
                echo "Updating current lock key : ".$key.".\n";
                $param->setUpdatedAt(new \DateTime('now'));
            } else {
                $this->logger->addInfo(
                    'Sync took too much time and exceeded the timeout, process will be exited.',
                    ['ExecuteSyncCommand']
                );
                echo "Sync took too much time and exceeded the timeout, process will be exited.\n";

                return;
            }
            $this->em->flush();
            $this->em->clear();
        }
        $param = $this->em->getRepository(Parameter::class)
            ->findOneBy(['type' => Parameter::EXECUTE_SYNC]);
        $this->em->remove($param);
        $this->em->flush();
        $this->logger->addDebug('ExecuteSyncCommand exiting.', ['ExecuteSyncCommand']);
        echo "ExecuteSyncCommand exiting.\n";
    }

    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
