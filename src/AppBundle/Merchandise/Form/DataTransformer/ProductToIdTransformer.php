<?php

namespace AppBundle\Merchandise\Form\DataTransformer;

use AppBundle\Merchandise\Entity\Product;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 01/03/2016
 * Time: 08:44
 */
class ProductToIdTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transforms an object (product) to a string (id).
     *
     * @param  Product|null $product
     * @return string
     */
    public function transform($product)
    {
        if (null === $product) {
            return '';
        }

        return $product->getId();
    }

    /**
     * Transforms a string (number) to an object (product).
     *
     * @param  mixed $id
     * @return Product|null
     * @return Product|null|object|void
     */
    public function reverseTransform($id)
    {
        // no issue number? It's optional, so that's ok
        if (!$id) {
            return;
        }

        $product = $this->manager
            ->getRepository('Merchandise:Product')
            // query for the issue with this id
            ->find($id);

        if (null === $product) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(
                sprintf(
                    'A product with id "%s" does not exist!',
                    $id
                )
            );
        }

        return $product;
    }
}
