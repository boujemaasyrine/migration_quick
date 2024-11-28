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

class HolidaysInitCommand extends ContainerAwareCommand
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
        $this->setName('quick:holidays:init')->setDefinition(
            []
        );
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
        echo "Initialize  Holidays \n";

        $url = "https://holidayapi.com/v1/holidays";

        $t1 = microtime();
        for ($i = 2010; $i < 2100; $i++) {
            $params = http_build_query(
                array(
                    'key' => "8b440262-bc6c-491f-b16f-b364dff9e0fe",
                    'country' => 'BE',
                    'year' => $i,
                )
            );
            $clientRest = Request::get($url."?".$params)
                ->expectsJson()
                ->send();

            $data = $clientRest->body;

            if ($data->status === 200) {
                echo 'Retrieving Holidays for  '.$i."\n";
                foreach ($data->holidays as $key => $value) {
                    $holiday = new Holiday();
                    $holiday->setName($value[0]->name)
                        ->setDate(\DateTime::createFromFormat('Y-m-d', $key));
                    $this->em->persist(clone $holiday);
                }
            } else {
                echo $data->error.' WARNING Retrieving Holidays for  '.$i."\n";
            }
            $this->em->flush();
        }
        $t2 = microtime();

        echo "Time for adding is ".($t2 - $t1)." ms \n";
    }
}
