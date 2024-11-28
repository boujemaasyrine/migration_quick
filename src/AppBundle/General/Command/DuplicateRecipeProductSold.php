<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\SubSoldingCanal;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\RecipeLineSupervision;
use AppBundle\Supervision\Entity\RecipeSupervision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DuplicateRecipeProductSold extends ContainerAwareCommand
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
        $this->setName('quick:duplicate:recipe:productsold')
            ->setDefinition([])
            ->setDescription('Duplicate recipe form product sold supervision.');
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
        $productSoldSupervisions = $this->em->getRepository('Supervision:ProductSoldSupervision')->findBy(array('type'=>ProductSoldSupervision::TRANSFORMED_PRODUCT));
        $allCanals = $this->em->getRepository(SoldingCanal::class)->findOneByLabel(SoldingCanal::ALL_CANALS);
        $noneReusableSubsoldingCanal = $this->em->getRepository(SubSoldingCanal::class)->findOneBy(array('id' => 1));
        $reusableSubSoldingCanal= $this->em->getRepository(SubSoldingCanal::class)->findOneBy(array('id' => 2));
        $recipes = $this->em->getRepository(RecipeSupervision::class)->findBy(array('productSold'=>$productSoldSupervisions, 'soldingCanal'=> $allCanals, 'subSoldingCanal' => $noneReusableSubsoldingCanal));
        foreach ($recipes as $recipe) {
            $recipe->setRecipeLines(array_reverse($recipe->getRecipeLines()->toArray()));
            $newRecipe = clone($recipe);
            $newRecipe->setSubSoldingCanal($reusableSubSoldingCanal);
            $this->em->persist($newRecipe);
            $newRecipe->setGlobalId($newRecipe->getId());
            $output->writeln("Recipe duplicated with its recipeLines");
            $this->em->flush();
        }
    }
}