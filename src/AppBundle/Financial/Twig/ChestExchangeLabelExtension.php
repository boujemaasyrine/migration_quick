<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 13/07/2016
 * Time: 11:45
 */

namespace AppBundle\Financial\Twig;

use AppBundle\Administration\Entity\Parameter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChestExchangeLabelExtension extends \Twig_Extension
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('chest_exchange_label', array($this, 'chestExchangeLabel')),
        );
    }

    public function chestExchangeLabel($value)
    {
        $parameter = $this->container->get('doctrine.orm.entity_manager')->getRepository('Administration:Parameter')
            ->find($value);
        if(!$parameter)
            return "Not defined";
        switch ($parameter->getValue()[Parameter::TYPE]) {
            case Parameter::BAG:
                $nbOfPiece = $parameter->getValue()[Parameter::BAG_CONTENT] * $parameter->getValue(
                )[Parameter::ROL_CONTENT];
                $value = $nbOfPiece * $parameter->getValue()[Parameter::PIECE_VALUE];
                $result = $this->container->get('translator')->trans(
                    $parameter->getValue()[Parameter::TYPE]
                )." x ".number_format($parameter->getValue()[Parameter::PIECE_VALUE], 2, ',', '')."€ ( ".number_format($value, 2, ',', '')."€ )";
                break;
            case Parameter::ROLS:
                $nbOfPiece = $parameter->getValue()[Parameter::ROL_CONTENT];
                $value = $parameter->getValue()[Parameter::ROL_CONTENT] * $parameter->getValue(
                )[Parameter::PIECE_VALUE];
                $result = $this->container->get('translator')->trans(
                    $parameter->getValue()[Parameter::TYPE]
                )." x ".number_format($parameter->getValue()[Parameter::PIECE_VALUE], 2, ',', '')."€ ( ".number_format($value, 2, ',', '')."€ )";
                break;
            case Parameter::BILL:
                $value = $parameter->getValue()[Parameter::PIECE_VALUE];
                $result = $this->container->get('translator')->trans(
                    $parameter->getValue()[Parameter::TYPE]
                )." ( ".number_format($value, 2, ',', '')."€ )";
                break;
            case Parameter::CASH:
                $value=$parameter->getValue()[Parameter::PIECE_VALUE];
                $result=$this->container->get('translator')->trans($parameter->getValue()[Parameter::TYPE]) . " ( " . number_format($value, 2, ',', '') . "€ )";
                break;
            default:
                $result='Not defined';
        }

        return $result;
    }

    public function getName()
    {
        return 'chest_exchange_label';
    }
}
