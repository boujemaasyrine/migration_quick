<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 08:59
 */

namespace AppBundle\Administration\Command;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoadParametersCommand
 */
class LoadParametersCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @param      $type
     * @param      $value
     * @param      $restaurant
     * @param null $labelValue
     * @param null $labelTranslation
     */
    public function insertNewParameter(
        $type,
        $value,
        $restaurant = null,
        $labelValue = null,
        $labelTranslation = null
    ) {
        $parameter = new Parameter();
        $parameter->setType($type)
            ->setValue($value)
            ->setLabel($labelValue);
        if (isset($restaurant)) {
            $parameter->setOriginRestaurant($restaurant);
        }
        echo($labelTranslation);
        if (null !== $labelTranslation) {
            $parameter->addLabelTranslation('nl', $labelTranslation);
        }
        $this->em->persist($parameter);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:parameters:import')->setDefinition(
            []
        )->setDescription('Import initial cashbox parameters.')
            ->addArgument(
                'restaurantId',
                InputArgument::OPTIONAL,
                'the restaurant to initialize with params'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get(
            'doctrine.orm.default_entity_manager'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progress = new ProgressBar($output, 13);
        $restaurant = null;
        if (intval($input->getArgument('restaurantId'))) {
            $restaurant = $this->em->getRepository(Restaurant::class)->find(
                intval($input->getArgument('restaurantId'))
            );
            if ($restaurant == null) {
                return;
            }
        }

        //initialize the restaurant with default parameters

        $dateFiscal = new Parameter();
        $dateFiscal->setType('date_fiscale')
            ->setValue(date('d/m/Y'))
            ->setOriginRestaurant($restaurant);
        $this->em->persist($dateFiscal);

        $progress->advance();

        $eft = new Parameter();
        $eft->setType(Parameter::EFT_ACTIVATED_TYPE)
            ->setValue("true")
            ->setOriginRestaurant($restaurant);
        $this->em->persist($eft);

        $progress->advance();

        // Number of cashboxes
        $cashboxes = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type"             => Parameter::NUMBER_OF_CASHBOXES,
                    "originRestaurant" => $restaurant,
                ]
            );
        if (is_null($cashboxes)) {
            $this->insertNewParameter(
                Parameter::NUMBER_OF_CASHBOXES,
                0,
                $restaurant
            );
        }
        $progress->advance();

        // Start Day funds
        $startDayFundsParam = $this->em->getRepository(
            'Administration:Parameter'
        )->findOneBy(
            [
                "type"             => Parameter::START_DAY_CASHBOX_FUNDS_TYPE,
                "originRestaurant" => $restaurant,
            ]
        );
        if (is_null($startDayFundsParam)) {
            $this->insertNewParameter(
                Parameter::START_DAY_CASHBOX_FUNDS_TYPE,
                250,
                $restaurant
            );
        }
        $progress->advance();

        // Restaurant Opening Hour
        $restaurantOpeningHourParam = $this->em->getRepository(
            'Administration:Parameter'
        )->findOneBy(
            [
                "type"             => Parameter::RESTAURANT_OPENING_HOUR,
                "originRestaurant" => $restaurant,
            ]
        );
        if (is_null($restaurantOpeningHourParam)) {
            $this->insertNewParameter(
                Parameter::RESTAURANT_OPENING_HOUR,
                7,
                $restaurant
            );
        }
        $progress->advance();

        // Restaurant Closing Hour
        $restaurantClosingHourParam = $this->em->getRepository(
            'Administration:Parameter'
        )->findOneBy(
            [
                "type"             => Parameter::RESTAURANT_CLOSING_HOUR,
                "originRestaurant" => $restaurant,
            ]
        );
        if (is_null($restaurantClosingHourParam)) {
            $this->insertNewParameter(
                Parameter::RESTAURANT_CLOSING_HOUR,
                1,
                $restaurant
            );
        }
        $progress->advance();

        //Error Counting Label Type
        $errorCountParam = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type" => Parameter::ERROR_COUNT_TYPE,
                ]
            );
        if (is_null($errorCountParam)) {
            $parameters = [
                [
                    'label'            => 'Erreur Caisse',
                    'value'            => 'error_cashbox',
                    'labelTranslation' => 'Kasverschil',
                ],
                [
                    'label'            => 'Erreur coffre',
                    'value'            => 'error_chest',
                    'labelTranslation' => 'Koffer Verschil',
                ],
            ];

            foreach ($parameters as $parameter) {
                $this->insertNewParameter(
                    Parameter::ERROR_COUNT_TYPE,
                    $parameter['value'],
                    null,
                    $parameter['label'],
                    $parameter['labelTranslation']
                );
            }

        }
        $progress->advance();

        /*// Bank Cash Payment  Type
        $cashPaymentParam = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type" => Parameter::CASH_PAYMENT_TYPE,
                ]
            );
        if (is_null($cashPaymentParam)) {
            $this->insertNewParameter(
                Parameter::CASH_PAYMENT_TYPE,
                'cash_payment',
                null,
                'Espèces',
                'Contanten'
            );
        }
        $progress->advance();*/


        // Foreign Currency
        //        $parameter = $this->em->getRepository('Administration:Parameter')->findOneBy([
        //            "type" => Parameter::FOREIGN_CURRENCY_TYPE
        //        ]);
        //        if (is_null($parameter)) {
        //            $this->insertNewParameter(Parameter::FOREIGN_CURRENCY_TYPE, 1.22, 'USD');
        //            $this->insertNewParameter(Parameter::FOREIGN_CURRENCY_TYPE, 1.05, 'CAD');
        //            $this->insertNewParameter(Parameter::FOREIGN_CURRENCY_TYPE, 1.5, 'TND');
        //            $this->insertNewParameter(Parameter::FOREIGN_CURRENCY_TYPE, 1.6, 'GBP');
        //        }
        //        $progress->advance();


        // Exchange type needed for Exhange fund on chest count

        // BAGS
        $bags = [
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 25,
                Parameter::PIECE_VALUE => 2,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 25,
                Parameter::PIECE_VALUE => 1,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 40,
                Parameter::PIECE_VALUE => 0.50,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 40,
                Parameter::PIECE_VALUE => 0.20,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 40,
                Parameter::PIECE_VALUE => 0.10,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 50,
                Parameter::PIECE_VALUE => 0.05,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 50,
                Parameter::PIECE_VALUE => 0.02,
                Parameter::TYPE        => Parameter::BAG,
            ],
            [
                Parameter::BAG_CONTENT => 10,
                Parameter::ROL_CONTENT => 50,
                Parameter::PIECE_VALUE => 0.01,
                Parameter::TYPE        => Parameter::BAG,
            ],
        ];
        foreach ($bags as $bag) {
            $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                array(
                    "type" => Parameter::EXCHANGE_TYPE,
                    "value" => serialize($bag)
                )
            );
            if(!$parameter){
                $this->insertNewParameter(Parameter::EXCHANGE_TYPE, $bag);
            }

        }

        // ROLLS
        $rolls = [
            [
                Parameter::ROL_CONTENT => 25,
                Parameter::PIECE_VALUE => 2,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 25,
                Parameter::PIECE_VALUE => 1,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 40,
                Parameter::PIECE_VALUE => 0.50,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 40,
                Parameter::PIECE_VALUE => 0.20,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 40,
                Parameter::PIECE_VALUE => 0.10,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 50,
                Parameter::PIECE_VALUE => 0.05,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 50,
                Parameter::PIECE_VALUE => 0.02,
                Parameter::TYPE        => Parameter::ROLS,
            ],
            [
                Parameter::ROL_CONTENT => 50,
                Parameter::PIECE_VALUE => 0.01,
                Parameter::TYPE        => Parameter::ROLS,
            ],
        ];
        foreach ($rolls as $roll) {
            $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                array(
                    "type" => Parameter::EXCHANGE_TYPE,
                    "value" => serialize($roll)
                )
            );
            if(!$parameter) {
                $this->insertNewParameter(Parameter::EXCHANGE_TYPE, $roll);
            }
        }

        // Bills
        $bills = [
            [
                Parameter::PIECE_VALUE => 100,
                Parameter::TYPE        => Parameter::BILL,
            ],
            [
                Parameter::PIECE_VALUE => 50,
                Parameter::TYPE        => Parameter::BILL,
            ],
            [
                Parameter::PIECE_VALUE => 20,
                Parameter::TYPE        => Parameter::BILL,
            ],
            [
                Parameter::PIECE_VALUE => 10,
                Parameter::TYPE        => Parameter::BILL,
            ],
            [
                Parameter::PIECE_VALUE => 5,
                Parameter::TYPE        => Parameter::BILL,
            ],
        ];
        foreach ($bills as $bill) {
            $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                array(
                    "type" => Parameter::EXCHANGE_TYPE,
                    "value" => serialize($bill)
                )
            );
            if(!$parameter) {
                $this->insertNewParameter(Parameter::EXCHANGE_TYPE, $bill);
            }
        }

        $progress->advance();

        $cash = [
            Parameter::PIECE_VALUE => 1,
            Parameter::TYPE        => Parameter::CASH,
        ];
        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                "type" => Parameter::EXCHANGE_TYPE,
                "value" => serialize($cash)
            )
        );
        if(!$parameter) {
            $this->insertNewParameter(Parameter::EXCHANGE_TYPE, $cash);
        }

        $progress->advance();

        $paymentMethods = $this->em->getRepository(PaymentMethod::class)->findAll();
        foreach ($paymentMethods as $paymentMethod) {
            /** create restaurant parameters for every payment method except for the foreign currency payment method **/
            if($paymentMethod->getType() != PaymentMethod::FOREIGN_CURRENCY_TYPE && $paymentMethod->getType() != PaymentMethod::REAL_CASH_TYPE)
            {
                $parameter = new Parameter();
                $parameter
                    ->setValue($paymentMethod->getValue())
                    ->setType($paymentMethod->getType())
                    ->setLabel($paymentMethod->getLabel())
                    ->setGlobalId($paymentMethod->getGlobalId())
                    ->setOriginRestaurant($restaurant);
                $this->em->persist($parameter);
            }
            /** associate every payment method to the new restaurant **/
            $restaurant->addPaymentMethod($paymentMethod);
        }

        $progress->advance();

        // add the new restaurant to the list of the eligible restaurants for the superadmin and admin users
        $centralUsers = $this->em->getRepository(Employee::class)->getShownUsers();
        foreach ($centralUsers as $user)
        {
            /**
             * @var User $user
             */
            if($user->isSuperAdmin() and !$user->getEligibleRestaurants()->contains($restaurant))
            {
                $user->addEligibleRestaurant($restaurant);
            }
        }

        $adminUsers = $this->em->getRepository(Employee::class)->getUsersByRole(Role::ROLE_ADMIN);
        if(count($adminUsers) and !$adminUsers[0]->getEligibleRestaurants()->contains($restaurant))
        {
            $adminUsers[0]->addEligibleRestaurant($restaurant);
        }

        $progress->advance();

        $progress->finish();
    }
}
