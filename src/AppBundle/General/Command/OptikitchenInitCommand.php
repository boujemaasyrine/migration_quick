<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/05/2016
 * Time: 18:08
 */

namespace AppBundle\General\Command;

use AppBundle\Administration\Entity\Optikitchen\OptikitchenMatrix;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OptikitchenInitCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:optikitchen:init')->setDefinition(
            []
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Initialize  Optikitchen Params \n";

        $oldData = $this->em->getRepository("Administration:Optikitchen\\OptikitchenMatrix")->findAll();
        foreach ($oldData as $d) {
            $this->em->remove($d);
            $this->em->flush();
        }

        $data = [
            [
                'level' => 1,
                'min' => 0,
                'max' => 62.5,
                'avg' => 31.25,
                'value' => 0.1,
            ],
            [
                'level' => 2,
                'min' => 62.5,
                'max' => 125,
                'avg' => 93.75,
                'value' => 0.15,
            ],
            [
                'level' => 3,
                'min' => 125,
                'max' => 187.5,
                'avg' => 156.25,
                'value' => 0.25,
            ],
            [
                'level' => 4,
                'min' => 187.5,
                'max' => 250,
                'avg' => 218.25,
                'value' => 0.28,
            ],
            [
                'level' => 5,
                'min' => 250,
                'max' => 312.5,
                'avg' => 281.25,
                'value' => 0.32,
            ],
            [
                'level' => 6,
                'min' => 312.5,
                'max' => 375,
                'avg' => 343.75,
                'value' => 0.35,
            ],
            [
                'level' => 7,
                'min' => 375,
                'max' => 437.5,
                'avg' => 406.25,
                'value' => 0.39,
            ],
            [
                'level' => 8,
                'min' => 437.5,
                'max' => 500,
                'avg' => 468.75,
                'value' => 0.42,
            ],
            [
                'level' => 9,
                'min' => 500,
                'max' => 562.5,
                'avg' => 531.25,
                'value' => 0.42,
            ],
            [
                'level' => 10,
                'min' => 562.5,
                'max' => 625,
                'avg' => 593.75,
                'value' => 0.42,
            ],
            [
                'level' => 11,
                'min' => 625,
                'max' => 687.5,
                'avg' => 656.25,
                'value' => 0.45,
            ],
            [
                'level' => 12,
                'min' => 687.5,
                'max' => 750,
                'avg' => 718.75,
                'value' => 0.45,
            ],
            [
                'level' => 13,
                'min' => 750,
                'max' => 812.5,
                'avg' => 781.25,
                'value' => 0.47,
            ],
            [
                'level' => 14,
                'min' => 812.5,
                'max' => 1500,
                'avg' => 843.75,
                'value' => 0.5,
            ],
        ];

        foreach ($data as $d) {
            $obj = new OptikitchenMatrix();
            $obj->setValue($d['value'])
                ->setAvg($d['avg'])
                ->setLevel($d['level'])
                ->setMax($d['max'])
                ->setMin($d['min']);

            $this->em->persist($obj);
            $this->em->flush();
        }
    }
}
