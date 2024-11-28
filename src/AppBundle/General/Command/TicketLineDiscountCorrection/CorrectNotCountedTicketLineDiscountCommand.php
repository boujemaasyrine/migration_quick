<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 30/07/2016
 * Time: 07:51
 */

namespace AppBundle\General\Command\TicketLineDiscountCorrection;

use AppBundle\Financial\Entity\TicketLine;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\AppBundle\Financial\Entity\TicketPayment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CorrectNotCountedTicketLineDiscountCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    protected function configure()
    {
        $this->setName('correct_not_counted_ticket_line_discount')
            ->setDescription('Import Wynd Tickets in the file.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $ticketIds = [2491590, 2765083, 2488595, 2765086, 2765554, 2765572, 2765076, 2491579, 2488599, 2765077];
        foreach ($ticketIds as $ticketId) {
            try {
                echo "Processing Ticket $ticketId \n";

                $ticket = $this->em->getRepository("Financial:Ticket")
                    ->findOneBy(
                        array(
                            'id' => $ticketId,
                        )
                    );

                if (!$ticket) {
                    throw new \Exception("Ticket Not found");
                }

                $discountContainerId = null;
                foreach ($ticket->getLines() as $tl) {
                    /**
                     * @var TicketLine $tl
                     */
                    if ($tl->getDiscountContainer()) {
                        $discountContainerId = $tl->getDiscountContainer();
                    }
                }

                if (is_null($discountContainerId)) {
                    foreach ($ticket->getPayments() as $tp) {
                        /**
                         * @var TicketPayment $tp
                         */
                        if ($tp->getBankCardContainer()) {
                            $discountContainerId = $tp->getBankCardContainer();
                        } else {
                            if ($tp->getCheckQuickContainer()) {
                                $discountContainerId = $tp->getCheckQuickContainer();
                            } else {
                                if ($tp->getCheckRestaurantContainer()) {
                                    $discountContainerId = $tp->getCheckRestaurantContainer();
                                } else {
                                    if ($tp->getRealCashContainer()) {
                                        $discountContainerId = $tp->getRealCashContainer();
                                    } else {
                                        if ($tp->getForeignCurrencyContainer()) {
                                            $discountContainerId = $tp->getForeignCurrencyContainer();
                                        } else {
                                            if ($tp->getMealTicketContainer()) {
                                                $discountContainerId = $tp->getMealTicketContainer();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (is_null($discountContainerId)) {
                    $ticket = $this->em->getRepository("Financial:Ticket")
                        ->findOneBy(
                            array(
                                'id' => $ticketId - 1,
                                'date' => $ticket->getDate(),
                            )
                        );

                    if (!$ticket) {
                        throw new \Exception("Ticket Not found");
                    }

                    $discountContainerId = null;
                    foreach ($ticket->getLines() as $tl) {
                        /**
                         * @var TicketLine $tl
                         */
                        if ($tl->getDiscountContainer()) {
                            $discountContainerId = $tl->getDiscountContainer();
                        }
                    }

                    if (is_null($discountContainerId)) {
                        foreach ($ticket->getPayments() as $tp) {
                            /**
                             * @var TicketPayment $tp
                             */
                            if ($tp->getBankCardContainer()) {
                                $discountContainerId = $tp->getBankCardContainer();
                            } else {
                                if ($tp->getCheckQuickContainer()) {
                                    $discountContainerId = $tp->getCheckQuickContainer();
                                } else {
                                    if ($tp->getCheckRestaurantContainer()) {
                                        $discountContainerId = $tp->getCheckRestaurantContainer();
                                    } else {
                                        if ($tp->getRealCashContainer()) {
                                            $discountContainerId = $tp->getRealCashContainer();
                                        } else {
                                            if ($tp->getForeignCurrencyContainer()) {
                                                $discountContainerId = $tp->getForeignCurrencyContainer();
                                            } else {
                                                if ($tp->getMealTicketContainer()) {
                                                    $discountContainerId = $tp->getMealTicketContainer();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ($ticket->getLines() as $tl) {
                    /**
                     * @var TicketLine $tl
                     */
                    if ($tl->getIsDiscount() && $discountContainerId) {
                        $tl->setDiscountContainer($discountContainerId);
                    }
                }

                $ticket->setSynchronized(false);

                $this->em->flush();
                $this->em->clear();
            } catch (\Exception $e) {
            }
        }
    }
}
