<?php

namespace AppBundle\Supervision\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class RestaurantSecretKeyCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('restaurant:secret_key:change')
            ->setDescription('Hello PhpStorm');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var EntityManager
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $helper = $this->getHelper('question');
        $question = new Question('Veuillez saisie le code du restaurant: ');
        $bundle = $helper->ask($input, $output, $question);
        if ($bundle == null) {
            echo "Code ne doit pas �tre null. Abandon!\n";

            return;
        }

        $restaurant = $em->getRepository("AppBundle:Restaurant")->findOneBy(
            array(
                'code' => $bundle,
            )
        );

        if ($restaurant == null) {
            echo "Aucun restaurant pour le code $bundle. Abandon!\n";

            return;
        }

        $question = new ConfirmationQuestion(
            "Voulez vous modifier le code secret du restaurant ".$restaurant->getName()."-".$restaurant->getCode()." [Y/n]?",
            true
        );
        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $question = new Question('Veuillez saisie le nouveau code secret: ');
        $m1 = $helper->ask($input, $output, $question);
        if ($m1 == null) {
            echo "le Code ne doit pas �tre null. Abandon!\n";

            return;
        }

        $question = new Question('Veuillez confirmer le code: ');
        $m2 = $helper->ask($input, $output, $question);
        if ($m2 == null) {
            echo "le Code ne doit pas �tre null. Abandon!\n";

            return;
        }
        if ($m1 != $m2) {
            echo "le Codes ne sont pas identiques. Abandon!\n";

            return;
        }

        $restaurant->setSecretKey(md5($m1));
        $em->flush();

        echo "Code modifi� avec succ�s\n";
    }
}
