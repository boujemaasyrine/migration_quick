<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 04/04/2016
 * Time: 09:06
 */

namespace AppBundle\DataFixtures\ORM\Merchandise;

use AppBundle\Merchandise\Entity\CategoryGroup;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadGroup
 */
class LoadGroup extends AbstractFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $groups = [
            [
                'name' => 'FOODCOST',
                'active' => true,
            ],
            [
                'name' => 'PAPERCOST DIRECT',
                'active' => true,
            ],
            [
                'name' => 'PAPERCOST INDIRECT',
                'active' => true,
            ],
        ];

        foreach ($groups as $group) {
            $categoryGroup = new CategoryGroup();
            $categoryGroup
                ->setName($group['name'])
                ->setActive($group['active']);

            $manager->persist($categoryGroup);
            $manager->flush();
        }
    }
}
