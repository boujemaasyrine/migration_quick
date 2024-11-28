<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 12/02/2018
 * Time: 15:24
 */

namespace AppBundle\General\Command;


use AppBundle\Administration\Entity\MissingPlu;
use AppBundle\General\Service\NotificationService;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateMissingPluNotificationsCommand extends ContainerAwareCommand
{
    /**
     * @var NotificationService
     */
    private $notificationService;


    /**
     * @var Logger
     */
    private $logger;


    /**
     * @var EntityManager
     */
    private $em;

    protected function configure()
    {
        $this->setName('saas:generate:notifications')->setDefinition(
            []
        )->setDescription('Generate missing plus notifications.');
    }


    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize(
            $input,
            $output
        );

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->notificationService = $this->getContainer()->get(
            'notification.service'
        );
        $this->logger = $this->getContainer()->get(
            'monolog.logger.app_commands'
        );

    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurants = $this->em->getRepository(Restaurant::class)
            ->getOpenedRestaurants();

        if (isset($restaurants)) {

            foreach ($restaurants as $restaurant) {

                /** @var \DateTime $fiscalDate */
                $fiscalDate = $this->getContainer()->get(
                    'administrative.closing.service'
                )->getLastWorkingEndDate($restaurant);


                $output->writeln('looking for missing Plus on  '. $restaurant->getCode());



                $allPlus = $this->em->getRepository(ProductSold::class)
                    ->retrieveAllPlus($restaurant);

               $qb = $this->em->createQuery(
                    'SELECT tl.plu FROM AppBundle\Financial\Entity\TicketLine tl JOIN tl.ticket t WHERE t.num>=0 AND tl.originRestaurantId=(:restaurantID) and t.originRestaurant=(:restaurant) AND tl.plu <> (:empty) AND tl.plu NOT IN (:allPlus) AND tl.plu NOT IN (:ignoredPlus) AND (tl.product<90000 OR tl.product>95000) AND t.date>=(:date) AND tl.date>=(:date) AND t.date<=(:date) AND tl.date<=(:date) GROUP BY tl.plu '
                );

                $qb->setParameter('allPlus', $allPlus)
                    ->setParameter('restaurant', $restaurant)
                    ->setParameter('restaurantID', $restaurant->getId())
                    ->setParameter('date',$fiscalDate->format('Y-m-d'))
                   /*
                    ->setParameter('startdate',"2018-08-01")
                    ->setParameter('enddate',"2018-08-31")
                   */
                    ->setParameter('empty', "")
                    ->setParameter('ignoredPlus', ProductSold::IGNORED_PLUS);

                $arrayPlus = $qb->getScalarResult();
                $arrayPlus = array_map('current', $arrayPlus);
                echo 'missing plu '. print_r($arrayPlus,true);
                $this->notificationService->generatePluSNotification(
                    $arrayPlus,
                    $restaurant
                );

            }
            $missingPlus = $this->em->getRepository(MissingPlu::class)->findBy(
                array('notified' => false)
            );

            if (count($missingPlus) > 0) {

                echo 'start mail notification for Missing Plus '."\n";

                $this->logger->addDebug(
                    'start mail notification for Missing Plus ',
                    ['MissingPlu:mail:notify']
                );

                $result = $this->notificationService->notifyByMailMissingPlu(
                    $missingPlus
                );


                if ($result) {

                    echo $result['pluS']." Missing Plu'S in "
                        .$result['restaurants']
                        ." restaurants" ."\n";
                    $this->logger->addDebug(
                        $result['pluS']." missing PluS in "
                        .$result['restaurants']
                        ." restaurants",
                        ['MissingPlu:mail:notify']
                    );
                } else {

                    echo 'mail notification failed for missing PLU' ."\n";

                    $this->logger->addAlert(
                        'mail notification failed for missing PLU',
                        ['MissingPlu:mail:notify']
                    );

                }

                echo 'end of mail notification for Missing Plus '."\n";

                $this->logger->addDebug(
                    'end of mail notification for Missing Plus ',
                    ['MissingPlu:mail:notify']
                );
            } else {

                echo 'no missing Plus to notify about '."\n";

                $this->logger->addDebug(
                    'no missing Plus to notify about.',
                    ['MissingPlu:mail:notify']
                );

            }


        }


    }
}