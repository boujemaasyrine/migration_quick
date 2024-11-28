<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 17:07
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\ProductCategories;
use Doctrine\ORM\EntityManager;

class CategoryService
{
    private $em;

    //private $syncCmdCreateEntry;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
        //$this->syncCmdCreateEntry = $syncCmdCreateEntry;
    }

    public function saveCategory(ProductCategories $category)
    {
        $category->setName($category->getNameTranslation('fr'));
        $category->setActive(true);
        $new = is_null($category->getId());
        $this->em->persist($category);

        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $changes = $uow->getEntityChangeSet($category);
        $this->em->flush();
        if (count($changes) > 0 || $new) {
            $category->setGlobalId($category->getId());
            // $this->syncCmdCreateEntry->createCategoryEntry($category);
        }
    }

    public function deleteCategory(ProductCategories $category)
    {
        $category->setActive(false);
        $this->em->flush();

        //$this->syncCmdCreateEntry->createCategoryEntry($category);
        return true;
    }

    public function getCategories($criteria, $order, $limit, $offset)
    {
        $categories = $this->em->getRepository(ProductCategories::class)->getCategoriesOrdered(
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeCategories($categories);
    }

    public function serializeCategories($categories)
    {
        $result = [];
        foreach ($categories as $c) {
            /**
             * @var ProductCategories $c
             */
            $result[] = array(
                'name' => $c->getName(),
                'group' => $c->getCategoryGroup()->getName(),
                'tvaBel' => ($c->getTaxBe() != null) ? $c->getTaxBe()."%" : '',
                'tvaLux' => ($c->getTaxLux() != null) ? $c->getTaxLux()."%" : '',
            );
        }

        return $result;
    }
}
