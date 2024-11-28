<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/05/2016
 * Time: 17:00
 */

namespace AppBundle\Administration\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class OptikitchenCalculCommand
 */
class OptikitchenCalculCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:optikitchen:calcul')->setDefinition(
            []
        )
            ->addArgument('date', InputArgument::REQUIRED)
            ->addArgument('date1', InputArgument::REQUIRED)
            ->addArgument('date2', InputArgument::REQUIRED)
            ->addArgument('date3', InputArgument::REQUIRED)
            ->addArgument('date4', InputArgument::REQUIRED)
            ->addArgument('progress', InputArgument::REQUIRED)
            ->addArgument('restaurant_id', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dateS = $input->getArgument('date');
        $date = \DateTime::createFromFormat('Y-m-d', $dateS);

        $dateS1 = $input->getArgument('date1');
        $date1 = \DateTime::createFromFormat('Y-m-d', $dateS1);

        $dateS2 = $input->getArgument('date2');
        $date2 = \DateTime::createFromFormat('Y-m-d', $dateS2);

        $dateS3 = $input->getArgument('date3');
        $date3 = \DateTime::createFromFormat('Y-m-d', $dateS3);

        $dateS4 = $input->getArgument('date4');
        $date4 = \DateTime::createFromFormat('Y-m-d', $dateS4);

        $restaurantId= $input->getArgument('restaurant_id');
        $output->writeln('restaurant ID= '. $restaurantId);
        if ($restaurantId) {
            $this->getContainer()->get('session')->set('currentRestaurant', $restaurantId);
            $restaurant=$this->em->getRepository(Restaurant::class)->find($restaurantId);
        }

        $idProg = $input->getArgument('progress');
        $progress = $this->em->getRepository("General:ImportProgression")->find($idProg);
        $this->getContainer()->get('optikitchen.service')->calculate(
            $date,
            [$date1, $date2, $date3, $date4],
            $restaurant,
            $progress
        );//fixed
        $progress->setStatus('finish');
        $this->em->flush();
    }
}
