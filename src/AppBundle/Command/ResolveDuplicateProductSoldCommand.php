<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 07/03/2019
 * Time: 14:59
 */

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Cette commande permet de
 * 1)Coté restaurant, le produit de vente passé en argument sera désactivé
 * 2)Coté supervision, Le restaurant devient non-éligible ce produit de vente.
 * Class ResolveDuplicateProductSoldCommand
 * @package AppBundle\Command
 */
class ResolveDuplicateProductSoldCommand extends ContainerAwareCommand
{

    /**
     * @var Logger $loggerCommand
     */
    private $loggerCommand;

    /**
     * @var EntityManager $em
     */
    private $em;

    protected function configure()
    {
        $this
            ->setName("resolve:duplicate:product:sold")
            ->addArgument('productSoldId', InputArgument::REQUIRED)
            ->setDescription("Disable product sold");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->loggerCommand = $this->getContainer()->get('monolog.logger.app_commands');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var ProductSold $productSold
         */

        $unique = uniqid('duplicate_product_sold');

        $productSoldId = $input->getArgument('productSoldId');
        $productSold = $this->em->getRepository(ProductSold::class)->find($productSoldId);
        if (!is_object($productSold)) {
            $this->loggerCommand->addAlert('Product sold is not found with id: ' . $productSoldId . ': ' . $unique, ['resolve:duplicate:product:sold']);
            echo 'Product sold is not found with id: ' . $productSoldId;
            return;
        }
        $restaurant = $productSold->getOriginRestaurant();

        $this->loggerCommand->addDebug('start commande' . ': ' . $unique, ['resolve:duplicate:product:sold']);
        $output->writeln('start commande');

        $this->loggerCommand->addDebug('Restaurant: ' . $restaurant->getName . '(' . $restaurant->getCode() . ')' . ': ' . $unique, ['resolve:duplicate:product:sold']);
        $output->writeln('Restaurant: ' . $restaurant->getName() . '(' . $restaurant->getCode() . ')');

        $this->loggerCommand->addDebug('Product sold to disable: ' . $productSold->getName() . '(' . $productSold->getCodePlu() . ')' . ': ' . $unique, ['resolve:duplicate:product:sold']);
        $output->writeln('Product sold to disable: ' . $productSold->getName() . '(' .$productSold->getCodePlu()  . ')');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action?', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('The order has been canceled --------');
            $this->loggerCommand->addDebug('The order has been canceled --------' . ': ' . $unique, ['resolve:duplicate:product:sold']);
            return;
        }
        $output->writeln('Start execution order');
        $this->loggerCommand->addDebug('Start execution order' . ': ' . $unique, ['resolve:duplicate:product:sold']);

        try {
            /**
             * @var ProductSoldSupervision $productSoldSupervision
             */
            $productSoldSupervision= $productSold->getSupervisionProduct();
            $productSoldSupervision->removeRestaurant($restaurant);
            $productSold->setActive(false);
            $this->em->persist($productSold);
            $this->em->persist($productSoldSupervision);
            $this->em->flush();
            $output->writeln('Product Sold '.$productSold->getName().'('.$productSold->getCodePlu().') successfully disabled');
            $this->loggerCommand->addDebug('Product Sold '.$productSold->getName().'('.$productSold->getCodePlu().')  successfully disabled' . ': ' . $unique, ['resolve:duplicate:product:sold']);
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('problem during this order' . ': ' . $unique, ['resolve:duplicate:product:sold']);
            throw $e;
        }
    }

}