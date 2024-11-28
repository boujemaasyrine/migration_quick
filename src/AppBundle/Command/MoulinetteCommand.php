<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 31/05/2019
 * Time: 09:48
 */

namespace AppBundle\Command;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\Ticket;
use Doctrine\ORM\EntityManager;
use AppBundle\General\Entity\ImportProgression;
use PHPStanVendor\Nette\Utils\DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class MoulinetteCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    private $expenseService;

    protected function configure()
    {

        parent::configure();

        $this->setName('saas:moulinette:calcul')
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('progressBarId', InputArgument::REQUIRED)
            ->addArgument('restaurants', InputArgument::REQUIRED)
            ->setDescription('A command that export moulinette to BI on excel format');
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->expenseService = $this->getContainer()->get('bi_api.expense.service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startDate = $input->getArgument('startDate');
        $endDate = $input->getArgument('endDate');
        $type = $input->getArgument('type');
        $progressId = $input->getArgument('progressBarId');

        $restaurant= $input->getArgument('restaurants');

        $list=explode('-',$restaurant);

        $today = new \DateTime();
        $restaurants = $this->em->getRepository(Restaurant::class)->createQueryBuilder("r")
            ->join(AdministrativeClosing::class, 'ad', Join::WITH, 'ad.originRestaurant = r')
            ->where('ad.date < :today')
            ->andWhere('r.active = true')
            ->andWhere('r.code IN (:list) ')
            ->setParameter('list', $list)
            ->setParameter('today', $today)->getQuery()
            ->getResult();
        $progression = $this->em->getRepository(ImportProgression::class)
            ->findOneBy(array('id' =>$progressId));
        if ($progression) {
            $progression->setStatus('pending');
            $progression->setTotalElements(count($restaurants))->setProceedElements(0);
            $this->em->flush($progression);
        }

        if ($type == '0') {
            $criteria['startDate'] = $startDate;
            $criteria['endDate'] = $endDate;
            foreach ($restaurants as $restaurant) {
                $country = $restaurant->getCountry();
                $fileName = 'Moulinette CA ' . strtoupper($country) . '-' . $restaurant->getCode();
                mkdir( $this->getContainer()->getParameter('kernel.root_dir')
                    . "/../data/export/".$progression->getId()."/", 0777, true);
                $path = $this->getContainer()->getParameter('kernel.root_dir')
                    . "/../data/export/".$progression->getId()."/" . $fileName . '.xlsx';
                $criteria['restaurant'] = $restaurant->getCode();
                if (file_exists($path)) {
                    $fileExist = true;
                } else {
                    $fileExist = false;
                }
                $results = $this->em->getRepository(Ticket::class)
                    ->getCaTicketPerTaxeAndSoldingCanal($criteria);

                $docType = "CA";
                $this->getContainer()->get('export.bi.excel')->generateExcel(
                    $fileName,
                    $country,
                    $docType,
                    $results,
                    $path,
                    $fileExist
                );
                $documents[] = $path;
                if ($progression) {
                    $progression->incrementProgression();
                    $this->em->flush();
                }

            }
        } else {
//            $startDate->format('d/m/Y'), $endDate->format('d/m/Y')
            $startDate= \DateTime::createFromFormat("Y-m-d", $startDate);
            $endDate = \DateTime::createFromFormat("Y-m-d", $endDate);
            $criteria['startDate'] =$startDate->format('d/m/Y');
            $criteria['endDate'] = $endDate->format('d/m/Y');
//            var_dump( $criteria['startDate']);die;
            foreach ($restaurants as $restaurant) {
                $country = $restaurant->getCountry();
                $fileName = 'Moulinette Bon ' . strtoupper($country) . '-' . $restaurant->getCode();
                mkdir( $this->getContainer()->getParameter('kernel.root_dir')
                    . "/../data/export/".$progression->getId()."/", 0777, true);
                $path = $this->getContainer()->getParameter('kernel.root_dir')
                    . "/../data/export/".$progression->getId()."/" . $fileName . '.xlsx';

                if (file_exists($path)) {
                    $fileExist = true;
                } else {
                    $fileExist = false;
                }

                $criteria = [
                    'startDate' => $startDate->format('d/m/Y'),
                    'endDate' => $endDate->format('d/m/Y'),
                    'restaurants' => [$restaurant],
                ];

                $results = $this->expenseService->getExpensesRecipe($criteria,null,null);

                $docType = "BON";
                $this->getContainer()->get('export.bi.excel')->generateExcel(
                    $fileName,
                    $country,
                    $docType,
                    $results,
                    $path,
                    $fileExist
                );
                $documents[] = $path;
                if ($progression) {
                    $progression->incrementProgression();
                    $this->em->flush();
                }
            }
        }

    }
}