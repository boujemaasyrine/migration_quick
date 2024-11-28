<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 08:59
 */

namespace AppBundle\Administration\Command;

use AppBundle\Administration\Entity\Parameter;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpdateLastClosuredDayForDevPurposeCommand
 * @package AppBundle\Administration\Command
 */
class UpdateLastClosuredDayForDevPurposeCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @param $type
     * @param $value
     * @param null $labelValue
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertNewParameter($type, $value, $labelValue = null)
    {
        $parameter = new Parameter();
        $parameter->setType($type)
            ->setValue($value)
            ->setLabel($labelValue);
        $this->em->persist($parameter);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:update:last:closuredDate')->setDefinition(
            []
        )->setDescription('Update last closured date.');
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

        $parameter = $this->em->getRepository('Administration:Parameter')->findOneBy(
            [
                "type" => Parameter::LAST_CLOSURED_DAY,
            ]
        );
        $yesterday = new \DateTime('yesterday');
        if (is_null($parameter)) {
            $this->insertNewParameter(Parameter::LAST_CLOSURED_DAY, $yesterday->format('Y/m/d'));
        } else {
            $parameter->setValue($yesterday->format('Y/m/d'));
            $this->em->flush();
        }
    }

}
