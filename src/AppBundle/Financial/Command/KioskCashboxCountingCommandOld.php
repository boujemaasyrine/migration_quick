<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 13/02/2018
 * Time: 18:00
 */

namespace AppBundle\Financial\Command;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KioskCashboxCountingCommand extends ContainerAwareCommand
{

    /**
     * @var \AppBundle\General\Entity\ImportProgression
     */
    private $progression;

    /**
     * @var EntityManager
     */
    private $em;

    private $logger;

    protected function configure()
    {
        $this->setName('saas:kiosk:counting')
            ->setDescription('kiosk cashbox counting')
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('userId', InputArgument::REQUIRED)
            ->addArgument('progressBarId', InputArgument::REQUIRED);

    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.financial');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurantId = $input->getArgument('restaurantId');
        $userId = $input->getArgument('userId');
        $progressBarId = $input->getArgument('progressBarId');

        $restaurant = $this->em->getRepository(Restaurant::class)->find(intval($restaurantId));



        if ($restaurant === null) {
            $this->logger->error(
                'no restaurant found with the given Id '.$restaurantId
            );

            return;
        }

        $user = $this->em->getRepository(Employee::class)->find($userId);

        if ($user === null) {
            $this->logger->error('no user found with the given Id '.$userId);

            return;
        }

        $this->progression = $this->em->getRepository(ImportProgression::class)->find($progressBarId);

        if ($this->progression === null) {
            $this->logger->error(
                'no progession bar found with the given Id '.$progressBarId
            );

            return;
        }


        $closureDate = $this->getContainer()->get(
            'administrative.closing.service'
        )->getCurrentClosingDate($restaurant);

        $this->logger->debug('the restaurant given is '.$restaurant->getName());
        $this->getContainer()->get('cashbox.service')->kioskCashboxCalculation(
            $restaurant,
            $closureDate,
            $user,
            $this->progression
        );
        $this->progression->setStatus('finish');
        $this->em->flush();


    }


}