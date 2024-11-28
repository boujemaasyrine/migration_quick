<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 13/07/2016
 * Time: 16:17
 */

namespace AppBundle\Administration\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RecipeLabelExtension
 */
class RecipeLabelExtension extends \Twig_Extension
{

    private $container;

    /**
     * RecipeLabelExtension constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('recipe_ticket_label', array($this, 'recipeTicketLabel')),
        );
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function recipeTicketLabel($value)
    {
        $code = $this->container->get('paremeter.service')->getRecipeTicketLabel($value);

        return $code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'recipe_ticket_label';
    }
}
