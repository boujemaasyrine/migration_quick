<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 28/09/2017
 * Time: 11:50
 */

namespace AppBundle\General\Command;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePaymentMethodsCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    protected function configure()
    {
        $this->setName('quick:payment:method:update')->setDefinition(
            []
        )->setDescription('Update payment methods.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getEntityManager();
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $oldMethods = $this->em->getRepository(PaymentMethod::class)->findAll();
        foreach ($oldMethods as $method) {
            $this->em->remove($method);
        }

        $this->em->flush();

        $progress = new ProgressBar($output, 17);
        $this->insertNewPaymentMethod(PaymentMethod::REAL_CASH_TYPE, [10, 20, 50], 'Cash');
        $progress->advance();
        $this->insertNewPaymentMethod(PaymentMethod::CHECK_QUICK_TYPE, [5], 'Check Quick');
        $progress->advance();
        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "BANCONTACT",
                "id" => "102",
            ],
            'Bancontact'
        );
        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Bancontact',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "code" => "BANCONTACT",
                    "id" => "102",
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();

        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "CB",
                "id" => "2",
            ],
            'Carte Bancaire'
        );
        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "MASTERCARD",
                "id" => "103",
            ],
            'MasterCard'
        );
        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'MasterCard',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "code" => "MASTERCARD",
                    "id" => "103",
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $progress->advance();
        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "MAESTRO",
                "id" => "104",
            ],
            'Maestro'
        );
        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Maestro',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "code" => "MAESTRO",
                    "id" => "104",
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();

        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "VISA",
                "id" => "105",
            ],
            'Visa'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Visa',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "code" => "VISA",
                    "id" => "105",
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "V-PAY",
                "id" => "106",
            ],
            'Vpay'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Vpay',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "code" => "V-PAY",
                    "id" => "106",
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "AMEX",
                "id" => "107",
            ],
            'American Express'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'American Express',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "code" => "AMEX",
                    "id" => "107",
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();

        $this->insertNewPaymentMethod(
            PaymentMethod::TICKET_RESTAURANT_TYPE,
            [
                "type" => "Edenred",
                "electronic" => true,
                "values" => [1],
                "id" => "108",
                "code" => 'EDENRED',
            ],
            'Edenred'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Edenred',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "type" => "Edenred",
                    "electronic" => true,
                    "values" => [1],
                    "id" => "108",
                    "code" => 'EDENRED',
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::TICKET_RESTAURANT_TYPE,
            [
                "type" => "Epasssodexo",
                "electronic" => true,
                "values" => [1],
                "id" => "109",
                "code" => 'E SODEXO',
            ],
            'Epasssodexo'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Epasssodexo',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "type" => "Epasssodexo",
                    "electronic" => true,
                    "values" => [1],
                    "id" => "109",
                    "code" => 'E SODEXO',
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::TICKET_RESTAURANT_TYPE,
            [
                "type" => "Payfair",
                "electronic" => true,
                "values" => [1],
                "id" => "110",
                "code" => 'PAYFAIR',
            ],
            'Payfair'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Payfair',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "type" => "Payfair",
                    "electronic" => true,
                    "values" => [1],
                    "id" => "110",
                    "code" => 'PAYFAIR',
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::TICKET_RESTAURANT_TYPE,
            [
                "type" => "Sodexo",
                "electronic" => false,
                "values" => [4.5, 7, 10],
                "id" => "120",
                "code" => 'SODEXO',
                "affiliate_code" => "256615"
            ],
            'Sodexo'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Sodexo',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "type" => "Sodexo",
                    "electronic" => false,
                    "values" => [4.5, 7, 10],
                    "id" => "120",
                    "code" => 'SODEXO',
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::TICKET_RESTAURANT_TYPE,
            [
                "type" => "Ticket restaurant",
                "electronic" => false,
                "values" => [5, 10],
                "id" => "130",
                "code" => 'TICKETREST',
                "affiliate_code" => "1038919"
            ],
            'Ticket restaurant'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Ticket restaurant',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "type" => "Ticket restaurant",
                    "electronic" => false,
                    "values" => [5, 10],
                    "id" => "130",
                    "code" => 'TICKETREST',
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();


        $this->insertNewPaymentMethod(
            PaymentMethod::BANK_CARD_TYPE,
            [
                "code" => "OTHER CARD",
                "id" => "600",
            ],
            'Autre carte'
        );

        /*

        $param=new Parameter();
        $param
            ->setValue( [
                    "code" => "OTHER CARD",
                    "id" => "601",
            ])
            ->setType(PaymentMethod::BANK_CARD_TYPE)
            ->setLabel('Autre carte');
         $this->em->persist($param);
         $param->setGlobalId($param->getId());
         $this->em->flush($param);

        */

        $progress->advance();


        $this->insertNewPaymentMethod(PaymentMethod::FOREIGN_CURRENCY_TYPE, null, 'Argent étranger');

        $progress->advance();

        $this->insertNewPaymentMethod(
            PaymentMethod::TICKET_RESTAURANT_TYPE,
            [
                "type" => "Cheque resto Lux",
                "electronic" => false,
                "values" => [5, 10],
                "id" => "132",
                "code" => 'CHQLUX',
                "affiliate_code" => "R10026"
            ],
            'Cheque resto Lux'
        );

        $params = $this->em->getRepository(Parameter::class)->findBy(
            array(
                "label" => 'Chèque resto Lux',
            )
        );
        foreach ($params as $param) {
            $param->setValue(
                [
                    "type" => "Cheque resto Lux",
                    "electronic" => false,
                    "values" => [5, 10],
                    "id" => "132",
                    "code" => 'CHQLUX',
                ]
            );
            $this->em->persist($param);
        }
        $this->em->flush($params);
        $progress->advance();

        $progress->finish();
    }


    public function insertNewPaymentMethod($type, $value, $labelValue = null)
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setType($type)
            ->setValue($value)
            ->setLabel($labelValue)
            ->setActive(true);
        $this->em->persist($paymentMethod);
        $paymentMethod->setGlobalId($paymentMethod->getId());
        $this->em->flush();
    }
}
