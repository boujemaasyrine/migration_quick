<?php

namespace AppBundle\Staff\Form\DataTransformer;

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
class EmployeeToIdTransformer implements DataTransformerInterface
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Transforms an object (product) to a string (id).
     *
     * @param  Product|null $employee
     * @return string
     */
    public function transform($employee)
    {
        if (null === $employee) {
            return '';
        }

        return $employee->getId();
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

        $employee = $this->manager
            ->getRepository('Staff:Employee')
            // query for the issue with this id
            ->find($id);

        if (null === $employee) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(
                sprintf(
                    'An employee with id "%s" does not exist!',
                    $id
                )
            );
        }

        return $employee;
    }
}
