<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 08/03/2016
 * Time: 15:17
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\CategoryGroup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Parameter;

class GroupService
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function saveGroup(CategoryGroup $group)
    {
        $group->setName($group->getNameTranslation('fr'));
        $group->setActive(true);
        $this->em->persist($group);
        $this->em->flush();
    }

    public function deleteGroup(CategoryGroup $group)
    {
        $group->setActive(false);
        $this->em->flush();

        return true;
    }

    public function getGroups($criteria, $order, $limit, $offset)
    {
        $groups = $this->em->getRepository(CategoryGroup::class)->getGroupsOrdered(
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeGroups($groups);
    }

    public function serializeGroups($groups)
    {
        $result = [];
        foreach ($groups as $g) {
            /**
             * @var CategoryGroup $g
             */
            $result[] = array(
                'Ref' => $g->getId(),
                'name' => $g->getName(),
                'nameFr' => $g->getNameTranslation('fr'),
                'nameNl' => $g->getNameTranslation('nl'),
            );
        }

        return $result;
    }
}
