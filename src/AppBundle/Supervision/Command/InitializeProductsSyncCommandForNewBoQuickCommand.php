<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 12:18
 */

namespace AppBundle\Supervision\Command;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InitializeProductsSyncCommandForNewBoQuickCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
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
        $this->setName('quick:sync:cmd:products')->setDefinition(
            []
        )
            ->addArgument('quickCode', InputArgument::REQUIRED)
            ->setDescription('');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $quickCode = $input->getArgument('quickCode');
        $restaurant = $this->em->getRepository('AppBundle:Restaurant')->findOneBy(['code' => $quickCode]);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select product that you want to import in your bo',
            array('all', 'sold', 'purchased'),
            'all'
        );
        $question->setErrorMessage('Choice %s is invalid.');
        $choice = $helper->ask($input, $output, $question);
        $output->writeln('You have just selected: '.$choice);

        $ps = $this->em->getRepository('AppBundle:Product')->createQueryBuilder('product')
            ->leftJoin('product.restaurants', 'restaurants')
            ->where('restaurants = :rest')
            ->setParameter('rest', $restaurant)
            ->getQuery()
            ->getResult();
        $this->logger->info(
            'Restaurant '.$quickCode.' has '.count($ps).' eligible products',
            ['InitializeSyncCommandForNewBoQuickCommand']
        );

        $this->getContainer()->get('sync.create.entry.service')
            ->createQuickBoInitProductsSyncCommands($restaurant, $ps, $choice);
    }
}
