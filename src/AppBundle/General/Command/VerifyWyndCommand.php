<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/07/2016
 * Time: 13:19
 */

namespace AppBundle\General\Command;

use Httpful\Request;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyWyndCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:wynd:verify')
            ->setDescription('Verify Connectivity With Wynd.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $url = $this->getContainer()->getParameter('wynd.url');
        $apiUser = $this->getContainer()->getParameter('wynd.api.user');
        $apiPassword = $this->getContainer()->getParameter('wynd.api.secretkey');

        $output->writeln('Vérification Wynd');
        $output->writeln('URL => '.$url);
        $output->writeln('USER => '.$apiUser);
        $output->writeln('USER PASS => '.$apiPassword);

        try {
            $data = Request::get($url)
                ->addHeaders(
                    array(
                        'Api-User' => $apiUser,
                        'Api-Hash' => $apiPassword,
                    )
                )
                ->expectsJson()
                ->send();

            $data = $data->body;

            if ($data->result == 'success') {
                $output->writeln('<info> Connexion Etablie</info>');
                $output->writeln('Data => '.substr(json_encode($data), 0, 200));
            } else {
                $output->writeln('<error> Connexion non etablie, error wynd  </error>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
        }
    }
}
