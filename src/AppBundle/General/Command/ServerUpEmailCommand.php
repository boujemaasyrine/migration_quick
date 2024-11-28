<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/07/2016
 * Time: 13:40
 */

namespace AppBundle\General\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerUpEmailCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:email:server:up')
            ->addArgument('mail', InputArgument::REQUIRED)
            ->addArgument('address', InputArgument::REQUIRED)
            ->setDescription('Notify starting backup server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fromMail = $this->getContainer()->getParameter('sender_adress');
        /**
         * @var \Swift_Mailer
         */
        $mailer = $this->getContainer()->get('mailer');

        $mail = $input->getArgument('mail');
        $address = $input->getArgument('address');

        try {
            $mail = \Swift_Message::newInstance()
                ->setSubject("[QUICK] BACKUP SERVER STARTED")
                ->setFrom(array($fromMail))
                ->setTo(array($mail))
                ->setBody("The backup server is started at this address: $address", 'text/html');
            $send = $mailer->send($mail);

            if ($send) {
                $output->writeln('<info> Email Envoyé avec succès</info>');
            } else {
                $output->writeln("<error> Echec lors de l'envoi de mail</error>");
            }
        } catch (\Swift_RfcComplianceException $e) {
            $output->writeln("<error> Echec lors de l'envoi de mail ".$e->getMessage()."</error>");
        } catch (\Exception $ee) {
            $output->writeln("<error> Echec lors de l'envoi de mail ".$ee->getMessage()."</error>");
        }
    }
}
