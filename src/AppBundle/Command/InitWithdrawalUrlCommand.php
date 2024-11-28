<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 28/01/2019
 * Time: 17:13
 */

namespace AppBundle\Command;


use AppBundle\Administration\Entity\Parameter;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitWithdrawalUrlCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    protected function configure()
    {
        $this->setName('init:withdrawal:url')
            ->addArgument('restaurantId', InputArgument::OPTIONAL)
            ->setDescription('init withdrawal urls');
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {


        $this->em = $this->getContainer()->get(
            'doctrine.orm.default_entity_manager'
        );

       parent::initialize($input,$output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('start initialization of withdrawal urls');

        if ($input->hasArgument('restaurantId')
            && !empty(
            $input->getArgument(
                'restaurantId'
            )
            )
        ) {
            $restaurantId = $input->getArgument('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)
                ->find($restaurantId);
            if ($currentRestaurant == null) {

                $output->writeln(
                    'restaurant with id '.$restaurantId.'not found'
                );

            } else {

                $orderurl = $this->em->getRepository(Parameter::class)->findOneBy(array(
                        "originRestaurant" => $currentRestaurant,
                        "type" => Parameter::ORDERS_URL_TYPE,
                    )
                );

                /**
                 * @var Parameter $orderurl
                 */
                if($orderurl){


                    $value=$orderurl->getValue();

                    if(!empty($value)){
                        $witdrawalUrl=str_replace('orders','pettycash',$value);
                        $parameter= new Parameter();
                        $parameter->setType(Parameter::WITHDRAWAL_URL_TYPE)
                            ->setLabel('withdrawal url')
                            ->setValue($witdrawalUrl)
                            ->setOriginRestaurant($currentRestaurant);
                        $this->em->persist($parameter);
                        $this->em->flush();
                    }
                }

            }
        } else {

            $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();

            foreach ($restaurants as $restaurant){

                $orderurl = $this->em->getRepository(Parameter::class)->findOneBy(array(
                        "originRestaurant" => $restaurant,
                        "type" => Parameter::ORDERS_URL_TYPE,
                    )
                );
                if($orderurl){
                    $value=$orderurl->getValue();

                    if(!empty($value)){

                        $output->writeln('initializing url of withdrawal for restaurant '.$restaurant->getCode());

                        $witdrawalUrl=str_replace('orders','pettycash',$value);

                        $output->writeln($witdrawalUrl);

                        $parameter= new Parameter();

                        $parameter->setType(Parameter::WITHDRAWAL_URL_TYPE)
                            ->setLabel('withdrawal url')
                            ->setValue($witdrawalUrl)
                            ->setOriginRestaurant($restaurant);


                        $this->em->persist($parameter);


                    }


                }


            }

            $this->em->flush();


        }

    }

}