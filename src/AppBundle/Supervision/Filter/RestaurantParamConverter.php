<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/05/2016
 * Time: 15:41
 */

namespace AppBundle\Supervision\Filter;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class RestaurantParamConverter implements ParamConverterInterface
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request $request The request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $restaurant = null;

        if ($request->request->has('restaurant')) {
            $restaurantID = $request->request->get('restaurant');
            $restaurant = $this->em->getRepository("AppBundle:Restaurant")
                ->findOneBy(array('code' => $request->request->get('restaurant')));
        }

        $request->attributes->set($configuration->getName(), $restaurant);
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration Should be an instance of ParamConverter
     *
     * @return bool True if the object is supported, else false
     */
    public function supports(ParamConverter $configuration)
    {
        if ($configuration->getConverter() == 'restaurant_converter') {
            return true;
        } else {
            return false;
        }
    }
}
