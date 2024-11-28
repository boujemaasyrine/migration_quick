<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/04/2016
 * Time: 14:54
 */

namespace AppBundle\General\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Administration\Entity\ProcedureStep;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class InitProceduresCommand extends ContainerAwareCommand
{


    /**
     * @var EntityManager
     */
    private $em;

    private $roles = [
         'ROLE_MANAGER',
         'ROLE_FIRST_ASSISTANT',
        'ROLE_ASSISTANT',
        'ROLE_SHIFT_LEADER',
        'ROLE_ADMIN',
        'ROLE_IT',
        'ROLE_COORDINATION',
        'ROLE_AUDIT',
    ];

    /**
     * @var Logger
     */
    private $logger;



    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:procedure:init')->setDefinition([])
            ->addArgument('restaurantId', InputArgument::REQUIRED,'the restaurant id for the restaurant to initialize')
            ->setDescription('Init procedures.');

    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /**
         * *** LOAD ROLES ***
         **/

        $roles = $this->em->getRepository(Role::class)
                            ->createQueryBuilder('r')
                            ->where('r.label IN (:roles)')
                            ->setParameter('roles', $this->roles)
                            ->getQuery()
                            ->getResult();

        if (count($roles) == 0)
        {
            echo 'Please import the roles first.';
            $this->logger->addAlert('Please import the roles first.', ['InitProceduresCommand']);
            return;
        }

        $restaurantId = intval($input->getArgument('restaurantId'));
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);

        if($restaurant == null){
            echo 'Restaurant not found with id: '.$restaurantId;
            $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['InitProceduresCommand']);
            return;
        }
        else {

            /**
             *
             ** *** LOAD PROCEDURE D'OUVERTURE ****
             **/


            $ouverture = $this->em->getRepository(Procedure::class)->findOneBy(array(
                    'name' => 'ouverture',
                    'originRestaurant' => $restaurant
            ));

            if ($ouverture) {
                foreach ($ouverture->getSteps() as $s) {
                    $this->em->remove($s);
                    $this->em->flush();
                }
            } else {
                $ouverture = new Procedure();
                $ouverture->setName('ouverture');
                $this->em->persist($ouverture);
            }
            $ouverture->setOriginRestaurant($restaurant)
                ->setAtSameTime(false)
                ->setOnlyOnceAtDay(false)
                ->setOnlyOnceForAll(false)
                ->setAutorizeAbandon(false)
                ->setCanBeDeleted(false);
            foreach ($roles as $role)
            {
                $ouverture->addEligibleRole($role);
            }
            $this->em->flush();

            $ouvertureActionNames = ['administrative_closing'];
            $ouvertureActionFixed = [1];
            foreach ($ouvertureActionNames as $key => $a) {
                $action = $this->em->getRepository(Action::class)->findOneBy(array(
                        'name' => $a,
                ));
                if ($action) {
                    $step = new ProcedureStep();
                    $step->setAction($action)
                        ->setOrder($key + 1)
                        ->setDeletable(($ouvertureActionFixed[$key] == 0) ? true : false)
                        ->setProcedure($ouverture);
                    $this->em->persist($step);
                    $this->em->flush();
                }
            }


            /**
             *
             ** *** LOAD PROCEDURE DE FERMETURE ****
             **/

            $fermeture = $this->em->getRepository(Procedure::class)->findOneBy(array(
                    'name' => 'fermeture',
                    'originRestaurant'=>$restaurant
            ));

            if ($fermeture) {
                foreach ($fermeture->getSteps() as $s) {
                    $this->em->remove($s);
                    $this->em->flush();
                }
            } else {
                $fermeture = new Procedure();
                $fermeture->setName('fermeture');
                $this->em->persist($fermeture);
            }

            $fermeture
                ->setOriginRestaurant($restaurant)
                ->setAtSameTime(false)
                ->setOnlyOnceAtDay(true)
                ->setOnlyOnceForAll(true)
                ->setAutorizeAbandon(false)
                ->setCanBeDeleted(false);
            foreach ($roles as $role)
            {
                $fermeture->addEligibleRole($role);
            }
            $this->em->flush();


            $fermetureActionNames = ['verify_opened_table'];
            $fermetureActionFixed = [1];
            foreach ($fermetureActionNames as $key => $a) {
                $action = $this->em->getRepository(Action::class)->findOneBy(array(
                        'name' => $a,
                ));
                if ($action) {
                    $step = new ProcedureStep();
                    $step->setAction($action)
                        ->setOrder($key + 1)
                        ->setDeletable(($fermetureActionFixed[$key] == 0) ? true : false)
                        ->setProcedure($fermeture);
                    $this->em->persist($step);
                    $this->em->flush();
                }
            }
        }
    }
}
