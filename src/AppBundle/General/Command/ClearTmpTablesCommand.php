<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/06/2016
 * Time: 08:53
 */

namespace AppBundle\General\Command;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearTmpTablesCommand extends ContainerAwareCommand
{
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
        $this->setName('quick:clear:tmp:tables')->setDefinition(
            []
        )->setDescription('Clearing Tmp Tables');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lastWeek = Utilities::getDateFromDate(new \DateTime('today'), -8);
        $lastWeekStr = $lastWeek->format('d/m/Y H:i:s');

        $lastMonth= Utilities::getDateFromDate(new \DateTime('today'), -31);
        $lastMonthStr= $lastMonth->format('d/m/Y H:i:s');


        $this->logger->addInfo("[CLEARING TMP TABLES] : Starting");

        //Controle de stock tmp
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing ControlStockTmp Tables to date => $lastWeekStr");
        $tmp = $this->em->getRepository("Report:ControlStockTmp")->createQueryBuilder('t')
            ->where('t.createdAt < :lastWeek')
            ->setParameter('lastWeek', $lastWeek)
            ->getQuery()
            ->getResult();
        $n = count($tmp);
        foreach ($tmp as $t) {
            $this->em->remove($t);
        }
        $this->em->flush();
        $this->em->clear();
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing ControlStockTmp FINISH, $n lines was deleted");

        //Coefficients

        /*
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing CoefBase");
        $tmp = $this->em->getRepository("Merchandise:CoefBase")->findBy([], ['id' => 'desc'], null, 1);
        $n = count($tmp);
        foreach ($tmp as $t) {
            $this->em->remove($t);
        }
        $this->em->flush();
        $this->em->clear();
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing CoefBase FINISH, $n lines was deleted");
        */

        //Aide à la commande
        $currentWeek = intval(date('W'));
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing HelpOrdersTmp");
        $tmp = $this->em->getRepository("Merchandise:OrderHelpTmp")->createQueryBuilder('t')
            ->where('t.week < :currentWeek')
            ->setParameter('currentWeek', $currentWeek)
            ->getQuery()
            ->getResult();
        $n = count($tmp);
        foreach ($tmp as $t) {
            $this->em->remove($t);
        }
        $this->em->flush();
        $this->em->clear();
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing HelpOrdersTmp FINISH, $n lines was deleted");

        //Rapport Tmp
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing RapportTmp Tables to date => $lastMonthStr");
        $tmp = $this->em->getRepository("Report:RapportTmp")->createQueryBuilder('t')
            ->where('t.createdAt < :lastMonth')
            ->setParameter('lastMonth', $lastMonth)
            ->getQuery()
            ->getResult();
        $n = count($tmp);
        foreach ($tmp as $t) {
            $this->em->remove($t);
        }
        $this->em->flush();
        $this->em->clear();
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing RapportTmp FINISH, $n lines was deleted");

        //Optikitchen
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing Optikitchen Tables to date => $lastWeekStr");
        $tmp = $this->em->getRepository('Administration:Optikitchen\Optikitchen')->createQueryBuilder('t')
            ->where('t.date < :lastWeek')
            ->setParameter('lastWeek', $lastWeek)
            ->getQuery()
            ->getResult();
        $n = count($tmp);
        foreach ($tmp as $t) {
            $this->em->remove($t);
        }
        $this->em->flush();
        $this->em->clear();
        $this->logger->addInfo("[CLEARING TMP TABLES] : Clearing Optikitchen FINISH, $n lines was deleted");
    }
}
