<?php

namespace AppBundle\Financial\Form\DataTransformer;

use AppBundle\Financial\Entity\ChestCount;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 27/05/2016
 * Time: 15:44
 */
class ChestCountToIdTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transforms an object (chestCount) to a string (id).
     *
     * @param  ChestCount|null $chestCount
     * @return string
     */
    public function transform($chestCount)
    {
        if (null === $chestCount) {
            return '';
        }

        return $chestCount->getId();
    }

    /**
     * Transforms a string (number) to an object (chestCount).
     *
     * @param  mixed $id
     * @return ChestCount|null
     * @return ChestCount|null|object|void
     */
    public function reverseTransform($id)
    {
        // no issue number? It's optional, so that's ok
        if (!$id) {
            return;
        }

        $chestCount = $this->manager
            ->getRepository('Financial:ChestCount')
            // query for the issue with this id
            ->find($id);

        if (null === $chestCount) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(
                sprintf(
                    'A chestCount with id "%s" does not exist!',
                    $id
                )
            );
        }

        return $chestCount;
    }
}
