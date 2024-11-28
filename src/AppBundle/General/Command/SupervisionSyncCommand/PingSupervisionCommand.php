<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 14/07/2016
 * Time: 11:23
 */

namespace AppBundle\General\Command\SupervisionSyncCommand;

use AppBundle\Administration\Entity\Parameter;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use RestClient\CurlRestClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PingSupervisionCommand extends ContainerAwareCommand
{

    private $supervisionUrl;
    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:ping:supervision')->setDefinition(
            []
        )->setDescription('Get Response Status Code Ping Supervision');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->supervisionUrl = $this->getContainer()->getParameter('supervision.url');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Take the part after http:// of the supervision url
        $doubleSlash = strpos($this->supervisionUrl, '//');
        if ($doubleSlash) {
            $loginUrl = substr($this->supervisionUrl, $doubleSlash + 2);
        } else {
            $loginUrl = $this->supervisionUrl;
        }

        // Get the login url from supervision url
        $loginUrl = (substr($loginUrl, -1) == '/') ? $loginUrl.'login'
            : $loginUrl.'/login';
        echo $loginUrl."\n";
        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_HEADER, true);  // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, true);  // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo $statusCode;
        curl_close($ch);
        $accessibility = $this->em->getRepository('Administration:Parameter')->findOneBy(
            [
                'type' => Parameter::SUPERVISION_ACCESSIBILITY,
            ]
        );
        if (!$accessibility) {
            $accessibility = new Parameter();
            $accessibility
                ->setType(Parameter::SUPERVISION_ACCESSIBILITY);
            $this->em->persist($accessibility);
        }
        if ($statusCode == 200) {
            $accessibility->setValue(Parameter::ACCESSIBLE);
        } else {
            $accessibility->setValue(Parameter::INACCESSIBLE);
        }
        $this->em->flush();

        return $statusCode;
    }
}
