<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 26/04/2016
 * Time: 14:51
 */

namespace AppBundle\Administration\Form\DataTransformer;

use AppBundle\Security\Entity\Role;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IdToRoleTransformer implements DataTransformerInterface
{
    private $manager;

    /**
     * IdToRoleTransformer constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transforms an object (role) to a string (id).
     *
     * @param  Role|null $role
     *
     * @return string
     */
    public function transform($role)
    {
        if (null === $role) {
            return '';
        }

        return $role->getId();
    }

    /**
     * Transforms a string (number) to an object (role).
     *
     * @param  mixed $id
     *
     * @return Role|null|object|void
     */
    public function reverseTransform($id)
    {
        // no issue number? It's optional, so that's ok
        if (!$id) {
            return;
        }

        $role = $this->manager
            ->getRepository('Security:Role')
            // query for the issue with this id
            ->find($id);

        if (null === $role) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(
                sprintf(
                    'A role with id "%s" does not exist!',
                    $id
                )
            );
        }

        return $role;
    }
}
