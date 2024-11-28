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

class VerifySendEmailCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:email:sending:verify')
            ->addArgument('mail', InputArgument::REQUIRED)
            ->setDescription('Verify Sending emails.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fromMail = $this->getContainer()->getParameter('sender_adress');
        /**
         * @var \Swift_Mailer
         */
        $mailer = $this->getContainer()->get('mailer');

        $mail = $input->getArgument('mail');

        try {
            $mail = \Swift_Message::newInstance()
                ->setSubject("[QUICK] TESTING MAIL")
                ->setFrom(array($fromMail))
                ->setTo(array($mail))
                ->setBody('This is a testing email', 'text/html');

            $mailLogger=new \Swift_Plugins_Loggers_ArrayLogger();

            $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($mailLogger));

            $send = $mailer->send($mail);






            if ($send) {
                $output->writeln('<info> Email Envoyé avec succès</info>');
            } else {
                $output->writeln("<error> Echec lors de l'envoi de mail</error>");
                $output->writeln($mailLogger->dump());
            }
        } catch (\Swift_RfcComplianceException $e) {
            $output->writeln("<error> Echec lors de l'envoi de mail ".$e->getMessage()."</error>");
        } catch (\Exception $ee) {
            $output->writeln("<error> Echec lors de l'envoi de mail ".$ee->getMessage()."</error>");
        }
    }
}
