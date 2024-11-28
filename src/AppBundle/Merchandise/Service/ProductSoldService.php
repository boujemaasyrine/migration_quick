<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 11/03/2016
 * Time: 08:53
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ProductSoldService
{
    private $em;
    private $tokenStorage;
    private $twig;

    public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage, TwigEngine $twigEngine)
    {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twigEngine;
    }

    /**
     * @param $criteria
     * @param $order
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getProductsSold($criteria, $order, $offset, $limit)
    {
        $results = [
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
        ];
        $products = $this->em->getRepository('Merchandise:ProductSold')->getProductsSold(
            $criteria,
            $order,
            $offset,
            $limit
        );
        $results['recordsTotal'] = $this->em->getRepository('Merchandise:ProductSold')->getProductsSoldCount();
        $results['recordsFiltered'] = $this->em->getRepository('Merchandise:ProductSold')->getFiltredProductsSoldCount(
            $criteria
        );
        $results['data'] = $products;

        return $results;
    }

    public function saveRecipe(Recipe $recipe, Recipe $oldRecipe = null)
    {
        if ($oldRecipe) {
            $this->em->merge($recipe);

            foreach ($recipe->getRecipeLines() as $recipeLine) {
                if ($oldRecipe->getRecipeLines()->contains($recipeLine)) {
                    $key = $oldRecipe->getRecipeLines()->indexOf($recipeLine);
                    $recipe->getRecipeLines()->set($key, $recipeLine);
                } else {
                    $oldRecipe->addRecipeLine($recipeLine);
                }
            }

            foreach ($oldRecipe->getRecipeLines() as $recipeLine) {
                if (!$recipe->getRecipeLines()->contains($recipeLine)) {
                    $oldRecipe->removeRecipeLine($recipeLine);
                    $this->em->remove($recipeLine);
                }
            }
        }
    }

    public function saveProductSold(ProductSold $productSold)
    {
        if (is_null($productSold->getId())) {
            $this->em->persist($productSold);
        } else {
            $oldProductSold = $this->em->merge($productSold);
            $recipes = $oldProductSold->getRecipes();

            if ($productSold->getType() === ProductSold::TRANSFORMED_PRODUCT) {
                foreach ($recipes as $recipe) {
                    $elems = $productSold->getRecipes()->filter(
                        function ($elem) use ($recipe) {
                            return intval($elem->getId()) === intval($recipe->getId());
                        }
                    );
                    if (count($elems) == 0) {
                        $this->em->remove($recipe);
                    }
                }

                foreach ($productSold->getRecipes() as $recipe) {
                    $elems = $recipes->filter(
                        function ($elem) use ($recipe) {
                            return intval($elem->getId()) === intval($recipe->getId());
                        }
                    );
                    if (count($elems) > 0) {
                        $oldRecipe = $elems[0];
                        $this->saveRecipe($recipe, $oldRecipe);
                    } else {
                        $oldProductSold->addRecipe($recipe);
                    }
                }
                $oldProductSold->setProductPurchased(null);
            } elseif ($productSold->getType() === ProductSold::NON_TRANSFORMED_PRODUCT) {
                foreach ($productSold->getRecipes() as $recipe) {
                    $recipe = $this->em->merge($recipe);
                    $this->em->remove($recipe);
                }
            }
        }
        $this->em->flush();

        return $productSold;
    }

    public function getProductsSoldOrdered($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $soldItems = $this->em->getRepository("Merchandise:ProductSold")->getProductsSoldOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeProduct($soldItems);
    }

    public function getRecipeLinesOrdered($criteria, $order, $limit, $offset, $onlyList = false)
    {

        $recipeLines = $this->em->getRepository('Merchandise:RecipeLine')->getRecipeLinesOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeRecipeLines($recipeLines);
    }

    public function serializeProduct($soldItems)
    {
        $result = [];
        foreach ($soldItems as $i) {
            /**
             * @var ProductSold $i
             */
            $result[] = array(
                'id' => $i->getId(),
                'codePlu' => $i->getCodePlu(),
                'name' => $i->getName(),
                'type' => $i->getType(),
                'active' => $i->getActive(),
                'inventoryItem' => $i->getProductPurchased() != null ? $i->getProductPurchased()->getId() : '',
            );
        }

        return $result;
    }

    public function serializeRecipeLines($recipeLines)
    {
        $result = [];

        foreach ($recipeLines as $line) {
            /**
             * @var RecipeLine $line
             */
            if (!is_null($line->getRecipe()) && !is_null($line->getRecipe()->getProductSold())) {
                $priceLine = $line->getProductPurchased()->getBuyingCost() / $line->getProductPurchased(
                )->getInventoryQty()
                    / $line->getProductPurchased()->getUsageQty() * $line->getQty();
                $result[] = array(
                    'id' => $line->getId(),
                    'recipeId' => $line->getRecipe()->getId(),
                    'soldItemId' => $line->getRecipe()->getProductSold()->getId(),
                    'canal' => $line->getRecipe()->getSoldingCanal(),
                    'codeInventoryItem' => $line->getProductPurchased()->getExternalId(),
                    'codeInventoryName' => $line->getProductPurchased()->getName(),
                    'qty' => $line->getQty(),
                    'usageUnit' => $line->getProductPurchased()->getLabelUnitUsage(),
                    'linePrice' => $priceLine,
                    'recipePrice' => $line->getRecipe()->getRevenuePrice(),
                );
            }
        }

        return $result;
    }

    public function generateProductSoldXML()
    {
        $productsSold = $this->em->getRepository(ProductSold::class)->findAll();
        $xml = $this->twig->render("@Merchandise/ps_xml/ps_container.xml.twig", array('productsSold' => $productsSold));

        return file_put_contents('%kernel.root_dir%/../data'."/Products_sold".date('Y-m-D-His').".xml", $xml);
    }
}
