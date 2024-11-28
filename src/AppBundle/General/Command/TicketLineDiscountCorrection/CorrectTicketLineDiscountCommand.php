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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CorrectTicketLineDiscountCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    private $file;

    private $dir;

    protected function configure()
    {
        $this->setName('correct_ticket_line_discount')
            ->setDescription('Import Wynd Tickets in the file.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->dir = $this->getContainer()->getParameter(
            'kernel.root_dir'
        )."/../data/import/ticket_line_discount_correction/";
        $this->file = $this->dir."ticket_discount_corrections_11_12_bo_295.csv";

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = fopen($this->file, 'r');
        $header = fgets($file);
        $i = 1;

        while ($line = fgetcsv($file, null, ';')) {
            try {
                echo "Processing Line $i \n";

                $num = $line[0];
                $type = $line[2];
                $dateS = $line[1];
                $date = \DateTime::createFromFormat('Y-m-d', $dateS);
                $discountTTC = $line[3];
                $discountHT = $line[4];
                $discountTVA = $line[5];

                $ticket = $this->em->getRepository("Financial:Ticket")
                    ->findOneBy(
                        array(
                            'num' => $num,
                            'type' => $type,
                            'date' => $date,
                        )
                    );

                if (!$ticket) {
                    throw new \Exception("Ticket Not found");
                }

                $discountContainerId = null;
                foreach ($ticket->getLines() as $tl) {
                    $tl->setIsDiscount(false)
                        ->setDiscountTtc(null)
                        ->setDiscountHt(null)
                        ->setDiscountTva(null);
                    if ($tl->getDiscountContainer()) {
                        $discountContainerId = $tl->getDiscountContainer();
                    }
                }
                /**
                 * @var TicketLine $ticketLine
                 */
                $ticketLine = $ticket->getLines()[0];
                $ticketLine->setIsDiscount(true)
                    ->setTotalTTC($ticketLine->getTotalTTC() - $discountTTC)
                    ->setDiscountTtc($discountTTC)
                    ->setDiscountHt($discountHT)
                    ->setDiscountTva($discountTVA)
                    ->setDiscountContainer($discountContainerId);

                $ticket->setSynchronized(false);

                $this->em->flush();
                $this->em->clear();

                $i++;
            } catch (\Exception $e) {
            }
        }
    }
}
