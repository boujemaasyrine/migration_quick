<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 08:59
 */

namespace AppBundle\Supervision\Command;

use AppBundle\Financial\Entity\PaymentMethod;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadPaymentMethodCommand extends ContainerAwareCommand
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
        $this->setName('quick:payment:method:import')->setDefinition(
            []
        )->setDescription('Import initial payment methods.');
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
        $progress = new ProgressBar($output, 5);

        // Real Cash
        $paymentMethod = $this->em->getRepository('AppBundle:Administration\PaymentMethod')->findOneBy(
            [
                "type" => PaymentMethod::REAL_CASH_TYPE,
            ]
        );
        if (is_null($paymentMethod)) {
            $this->insertNewPaymentMethod(PaymentMethod::REAL_CASH_TYPE, [10, 20, 50], 'Espèces');
        }
        $progress->advance();

        // Ticket Restaurant
        $paymentMethod = $this->em->getRepository('AppBundle:Administration\PaymentMethod')->findOneBy(
            [
                "type" => PaymentMethod::TICKET_RESTAURANT_TYPE,
            ]
        );
        if (is_null($paymentMethod)) {
            $this->insertNewPaymentMethod(
                PaymentMethod::TICKET_RESTAURANT_TYPE,
                [
                    "type" => "Sodexo",
                    "electronic" => false,
                    "values" => [4.5, 7, 10],
                    "id" => "51",
                    "code" => 'SODPAS',
                ],
                'Sodexo'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::TICKET_RESTAURANT_TYPE,
                [
                    "type" => "Chèque resto Lux",
                    "electronic" => false,
                    "values" => [5, 10],
                    "id" => "50",
                    "code" => 'CHQLUX',
                ],
                'Chèque resto Lux'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::TICKET_RESTAURANT_TYPE,
                [
                    "type" => "Edenred",
                    "electronic" => true,
                    "values" => [1],
                    "id" => "52",
                    "code" => 'EDR',
                ],
                'Edenred'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::TICKET_RESTAURANT_TYPE,
                [
                    "type" => "Epasssodexo",
                    "electronic" => true,
                    "values" => [1],
                    "id" => "53",
                    "code" => 'EPSOD',
                ],
                'Epasssodexo'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::TICKET_RESTAURANT_TYPE,
                [
                    "type" => "Payfair",
                    "electronic" => true,
                    "values" => [1],
                    "id" => "54",
                    "code" => 'PAYFAIR',
                ],
                'Payfair'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::TICKET_RESTAURANT_TYPE,
                [
                    "type" => "Ticket restaurant",
                    "electronic" => false,
                    "values" => [5, 10],
                    "id" => "4",
                    "code" => 'TR',
                ],
                'Ticket restaurant'
            );
        }
        $progress->advance();

        // Check Quick
        $paymentMethod = $this->em->getRepository('AppBundle:Administration\PaymentMethod')->findOneBy(
            [
                "type" => PaymentMethod::CHECK_QUICK_TYPE,
            ]
        );
        if (is_null($paymentMethod)) {
            $this->insertNewPaymentMethod(PaymentMethod::CHECK_QUICK_TYPE, [5, 6, 20], 'Check Quick');
        }
        $progress->advance();

        // Foreign Currency
        $paymentMethod = $this->em->getRepository('AppBundle:Administration\PaymentMethod')->findOneBy(
            [
                "type" => PaymentMethod::FOREIGN_CURRENCY_TYPE,
            ]
        );
        if (is_null($paymentMethod)) {
            $this->insertNewPaymentMethod(PaymentMethod::FOREIGN_CURRENCY_TYPE, null, 'Argent étranger');
        }
        $progress->advance();

        // Bank Card
        $paymentMethod = $this->em->getRepository('AppBundle:Administration\PaymentMethod')->findOneBy(
            [
                "type" => PaymentMethod::BANK_CARD_TYPE,
            ]
        );
        if (is_null($paymentMethod)) {
            $this->insertNewPaymentMethod(
                PaymentMethod::BANK_CARD_TYPE,
                [
                    "code" => "VPAY",
                    "id" => "61",
                ],
                'Vpay'
            );
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
                    "id" => "58",
                ],
                'MasterCard'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::BANK_CARD_TYPE,
                [
                    "code" => "AMEX",
                    "id" => "56",
                ],
                'American Express'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::BANK_CARD_TYPE,
                [
                    "code" => "BANCT",
                    "id" => "57",
                ],
                'Bancontact'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::BANK_CARD_TYPE,
                [
                    "code" => "MAESTRO",
                    "id" => "59",
                ],
                'Maestro'
            );
            $this->insertNewPaymentMethod(
                PaymentMethod::BANK_CARD_TYPE,
                [
                    "code" => "VISA",
                    "id" => "60",
                ],
                'Visa'
            );
        }
        $progress->advance();

        $progress->finish();
    }

    public function insertNewPaymentMethod($type, $value, $labelValue = null, $labelTranslation = null)
    {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setType($type)
            ->setValue($value)
            ->setLabel($labelValue);
        echo($labelTranslation);
        if ($labelTranslation != null) {
            $paymentMethod->addLabelTranslation('nl', $labelTranslation);
        }
        $this->em->persist($paymentMethod);
        $paymentMethod->setGlobalId($paymentMethod->getId());
        $this->em->flush();
    }
}
