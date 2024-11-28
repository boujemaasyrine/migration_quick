<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 22/05/2019
 * Time: 13:36
 */

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Procedure;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Cette Commande permet de completer la traduction des procedures
 * V1 traduction des procedures d'ouverture
 * Class CompletProcedureTranslationsCommand
 * @package AppBundle\Command
 */
class CompletProcedureTranslationsCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    protected function configure()
    {
        $this
            ->setName("complet:procedure:translation")
            ->addArgument('local', InputArgument::REQUIRED)
            ->addArgument('content', InputArgument::REQUIRED)
            ->setDescription("Complete the translation of the procedures");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $local = strtolower(trim($input->getArgument('local')));
        $content = strtolower(trim($input->getArgument('content')));
        $procedures = $this->getAllProcedures(Procedure::OPENING);
        foreach ($procedures as $p) {
            $p->addNameTranslation($local, $content);
            $this->em->persist($p);
        }
        $this->em->flush();
    }

    private function getAllProcedures($name)
    {
        return $this->em->getRepository(Procedure::class)->findBy(array('name' => $name)
        );
    }
}