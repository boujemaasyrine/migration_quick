<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 05/07/2018
 * Time: 10:49
 */

namespace AppBundle\Command;


use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Entity\Restaurant;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadParamsPaymentCommand extends ContainerAwareCommand
{

    private $em;

    /**
     * @var Logger
     */
    private $logger;


    protected function configure()
    {
        $this->setName('quick:parameters:payment:create')->setDefinition(
            []
        )->setDescription('create parameters for the payment methods')
            ->addArgument(
                'restaurantId',
                InputArgument::OPTIONAL,
                'the restaurant to initialize with params'
            );
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize(
            $input,
            $output
        );

        $this->em = $this->getContainer()->get(
            'doctrine.orm.default_entity_manager'
        );

        $this->logger = $this->getContainer()->get(
            'monolog.logger.app_commands'
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $restaurant = null;
        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {
            $restaurant = $this->em->getRepository(Restaurant::class)->find(
                intval($input->getArgument('restaurantId'))
            );
            if ($restaurant == null) {
                $output->writeln(
                    'no restaurant found with the given Id '.$restaurant->getId(
                    )
                );

                return;
            }
        }

        if($restaurant==null){

            $restaurants=$this->em->getRepository(Restaurant::class)->getOpenedRestaurants();

            foreach ($restaurants as $restaurant) {

                $this->CreatePaymentsParams($restaurant);

            }
        }

        else {

            $this->CreatePaymentsParams($restaurant);
        }




}

    public function createPaymentsParams($restaurant)
    {

        /**
         * @var array PaymentMethod $paymentMethods
         */
        $paymentMethods = $this->em->getRepository(PaymentMethod::class)
            ->findAll();

        try {

            /**
             * @var PaymentMethod $paymentMethod
             */
            foreach ($paymentMethods as $paymentMethod) {

               echo 'payment method '.$paymentMethod->getLabel()."\n";

                $this->logger->addDebug(
                    'payment method '.$paymentMethod->getLabel(),
                    ['create:payment:params']
                );
                if ($paymentMethod->getType()
                    != PaymentMethod::FOREIGN_CURRENCY_TYPE
                    && $paymentMethod->getType()
                    != PaymentMethod::REAL_CASH_TYPE
                ) {

                    $parameter = $this->em->getRepository(Parameter::class)
                        ->createQueryBuilder('p')
                        ->where('p.type = :type')
                        ->andWhere('p.originRestaurant = :restaurant')
                        ->andWhere('p.label = :label')
                        ->setParameter('type', $paymentMethod->getType())
                        ->setParameter('label', $paymentMethod->getLabel())
                        ->setParameter('restaurant', $restaurant)
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
                    if (is_null($parameter)) {
                        echo 'inexistant parameter  for '.$paymentMethod->getLabel()."\n";
                        $parameter = new Parameter();
                        $parameter
                            ->setValue($paymentMethod->getValue())
                            ->setType($paymentMethod->getType())
                            ->setLabel($paymentMethod->getLabel())
                            ->setGlobalId($paymentMethod->getGlobalId())
                            ->setOriginRestaurant($restaurant);
                        $this->em->persist($parameter);
                        $this->em->flush();

                        echo 'parameter '.$paymentMethod->getLabel()." created \n";

                    }
                }

                /** associate every payment method to the new restaurant **/
                if (!$restaurant->getPaymentMethods()->contains(
                    $paymentMethod
                )
                ) {
                    $restaurant->addPaymentMethod($paymentMethod);
                    $paymentMethod->addRestaurant($restaurant);
                }

                $this->em->persist($paymentMethod);

                $this->em->flush();

            }


        }

        catch (\Exception $exception){
            $this->logger->addDebug($exception->getMessage(),['restaurant:payment:param']);
        }

}

}