<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:11
 */

namespace AppBundle\Administration\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Service\CommandLauncher;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\Translation\Translator;

/**
 * Class ParameterService
 *
 * @package AppBundle\Administration\Service
 */
class ParameterService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var bool
     */
    private $restaurantCode;

    /**
     * @var CommandLauncher
     */
    private $commandLauncher;

    /**
     * @var RestaurantService
     */
    private $restaurantService;

    /**
     * ParameterService constructor.
     *
     * @param EntityManager     $em
     * @param Translator        $translator
     * @param CommandLauncher   $commandLauncher
     * @param RestaurantService $restaurantService
     *
     * @throws \Exception
     */
    public function __construct(
        EntityManager $em,
        Translator $translator,
        CommandLauncher $commandLauncher,
        RestaurantService $restaurantService
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->commandLauncher = $commandLauncher;
        $this->restaurantService = $restaurantService;
        $this->restaurantCode = $this->restaurantService->getCurrentRestaurantCode();
    }

    /**
     * @return float
     *
     * @throws InternalErrorException
     */
    public function getStartDayCashboxFunds()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                array(
                    'type'             => Parameter::START_DAY_CASHBOX_FUNDS_TYPE,
                    'originRestaurant' => $currentRestaurant,
                )
            );

        if (is_null($parameter)) {
            throw new InternalErrorException(
                'There is no value start day cashbox funds in parameter table.'
            );
        }

        return floatval($parameter->getValue());
    }

    /**
     * @param Restaurant $restaurant
     * @return float
     *
     * @throws InternalErrorException
     */
    public function getStartDayCashboxFundsByRestaurant(Restaurant $restaurant)
    {

        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                array(
                    'type'             => Parameter::START_DAY_CASHBOX_FUNDS_TYPE,
                    'originRestaurant' => $restaurant,
                )
            );

        if (is_null($parameter)) {
            throw new InternalErrorException(
                'There is no value start day cashbox funds in parameter table.'
            );
        }

        return floatval($parameter->getValue());
    }
    /**
     * @return bool
     *
     * @throws InternalErrorException
     * @throws \Exception
     */
    public function isEftActivated($restaurant = null)
    {
        if ($restaurant == null) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    'type'             => Parameter::EFT_ACTIVATED_TYPE,
                    'originRestaurant' => $restaurant,
                ]
            );

        if (is_null($parameter)) {
            throw new InternalErrorException(
                'There is no value for EFT status in parameter table.'
            );
        }

        return $parameter->getValue() === ''
        || $parameter->getValue() === 'false' ? false : true;
    }

    /**
     * @param bool $rawList
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getExchangeRateList($rawList = false)
    {
        $result = [];
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $prameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::FOREIGN_CURRENCY_TYPE)
            ->andWhere("parameter.originRestaurant = :restaurant")
            ->setParameter("restaurant", $currentRestaurant)
            ->getQuery()->getResult();

        if (!$rawList) {
            foreach ($prameters as $prameter) {
                $result[$prameter->getValue()] = $prameter->getLabel()
                    ." => EUR ".' ('.$prameter->getValue().')';
            }
        } else {
            $result = $prameters;
        }

        return $result;
    }

    /**
     * @param bool $rawList
     *
     * @return array
     */
    public function getExchangeList($rawList = false)
    {
        $result = [];
        $prameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')->where(
                'parameter.type = :type_param'
            )->setParameter('type_param', Parameter::EXCHANGE_TYPE)->getQuery()
            ->getResult();

        if (!$rawList) {
            foreach ($prameters as $parameter) {
                /**
                 * @var Parameter $parameter
                 */
                switch ($parameter->getValue()[Parameter::TYPE]) {
                    case Parameter::BAG:
                        $nbOfPiece = $parameter->getValue(
                            )[Parameter::BAG_CONTENT] * $parameter->getValue(
                            )[Parameter::ROL_CONTENT];
                        $value = $nbOfPiece * $parameter->getValue(
                            )[Parameter::PIECE_VALUE];
                        $result[$parameter->getId()] = $this->translator->trans(
                                $parameter->getValue()[Parameter::TYPE]
                            )." x ".number_format(
                                $parameter->getValue()[Parameter::PIECE_VALUE],
                                2,
                                ',',
                                ''
                            )."€ ( ".number_format($value, 2, ',', '')."€ )";
                        break;
                    case Parameter::ROLS:
                        $nbOfPiece = $parameter->getValue(
                        )[Parameter::ROL_CONTENT];
                        $value = $parameter->getValue()[Parameter::ROL_CONTENT]
                            * $parameter->getValue()[Parameter::PIECE_VALUE];
                        $result[$parameter->getId()] = $this->translator->trans(
                                $parameter->getValue()[Parameter::TYPE]
                            )." x ".number_format(
                                $parameter->getValue()[Parameter::PIECE_VALUE],
                                2,
                                ',',
                                ''
                            )."€ ( ".number_format($value, 2, ',', '')."€ )";
                        break;
                    case Parameter::BILL:
                        $value = $parameter->getValue()[Parameter::PIECE_VALUE];
                        $result[$parameter->getId()] = $this->translator->trans(
                                $parameter->getValue()[Parameter::TYPE]
                            )." ( ".number_format($value, 2, ',', '')."€ )";
                        break;
                    case Parameter::CASH:
                        $value = $parameter->getValue()[Parameter::PIECE_VALUE];
                        $result[$parameter->getId()] = $this->translator->trans(
                                $parameter->getValue()[Parameter::TYPE]
                            )." ( ".number_format($value, 2, ',', '')."€ )";
                        break;
                }
            }
        } else {
            $result = $prameters;
        }

        return $result;
    }

    /**
     * @param Parameter $parameter
     *
     * @return float|int
     */
    public function calculateParameterExchangeUnitValue(Parameter $parameter)
    {
        $value = 0;
        switch ($parameter->getValue()[Parameter::TYPE]) {
            case Parameter::BAG:
                $nbOfPiece = $parameter->getValue()[Parameter::BAG_CONTENT]
                    * $parameter->getValue()[Parameter::ROL_CONTENT];
                $value = $nbOfPiece * $parameter->getValue(
                    )[Parameter::PIECE_VALUE];
                break;
            case Parameter::ROLS:
                $nbOfPiece = $parameter->getValue()[Parameter::ROL_CONTENT];
                $value = $parameter->getValue()[Parameter::ROL_CONTENT]
                    * $parameter->getValue()[Parameter::PIECE_VALUE];
                break;
            case Parameter::BILL:
                $value = $parameter->getValue()[Parameter::PIECE_VALUE];
                break;
            case Parameter::CASH:
                $value = $parameter->getValue()[Parameter::PIECE_VALUE];
        }

        return $value;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getCheckQuickValues($restaurant = null)
    {
        if ($restaurant == null) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        $result = [];
        $prameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('type_param', Parameter::CHECK_QUICK_TYPE)
            ->getQuery()
            ->getResult();


        foreach ($prameters as $prameter) {
            $result[] = $prameter;
        }


        return $result;
    }

    /**
     * @param bool $electronic
     *
     * @return array|null
     *
     * @throws \Exception
     */
    public function getTicketRestaurantValues(
        Restaurant $restaurant = null,
        $electronic = false
    ) {
        if ($restaurant == null) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }


        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('type_param', Parameter::TICKET_RESTAURANT_TYPE)
            ->setParameter('restaurant', $restaurant)
            ->orderBy('parameter.label')
            ->getQuery()
            ->getResult();
        $result = null;
        foreach ($parameters as $parameter) {
            if ($parameter->getValue()['electronic'] == $electronic) {
                $result[] = $parameter;
            }
        }

        return $result;
    }

    /**
     * @return null
     *
     * @throws \Exception
     */
    public function getTicketNameIdPaymentMapForTicketRestaurant(
        $restaurant = null
    ) {
        if ($restaurant == null) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::TICKET_RESTAURANT_TYPE)
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->orderBy('parameter.label')
            ->getQuery()
            ->getResult();
        $result = null;
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            $result[$parameter->getValue()['type']] = $parameter;
        }

        return $result;
    }

    /**
     * @param $electronic
     *
     * @return null
     *
     * @throws \Exception
     */
    public function getTicketRestaurantTypes($electronic)
    {
        $parameters = $this->getTicketRestaurantValues();

        $result = null;
        foreach ($parameters as $parameter) {
            $value = $parameter->getValue();
            if (isset($value['electronic']) && isset($value['id'])
                && $value['electronic'] == $electronic
            ) {
                $result[$value['id']] = $parameter->getLabel();
            }
        }

        return $result;
    }

    /**
     * @param $code
     *
     * @return null
     *
     * @throws \Exception
     */
    public function getTicketAffiliateCode($code)
    {
        $parameters = $this->getTicketRestaurantValues();

        $result = null;
        foreach ($parameters as $parameter) {
            $value = $parameter->getValue();
            if (isset($value['id']) && $value['id'] == $code) {
                $result = $value['affiliate_code'];
            }
        }

        return $result;
    }

    /**
     * @param $id
     *
     * @return null
     *
     * @throws \Exception
     */
    public function getTicketRestaurantLabel($id)
    {
        // Check Restaurant
        $parameters = $this->getTicketRestaurantValues(null, false);
        foreach ($this->getTicketRestaurantValues(null, true) as $parameter) {
            $parameters[] = $parameter;
        }

        $result = null;
        foreach ($parameters as $parameter) {
            $value = $parameter->getValue();
            if (isset($value['id']) && $value['id'] == $id) {
                $result = $parameter->getLabel();
            }
        }

        return $result;
    }

    /**
     * @param $id
     *
     * @return null
     *
     * @throws \Exception
     */
    public function getTicketRestaurantCode($id)
    {
        $parameters = $this->getTicketRestaurantValues();

        $result = null;
        foreach ($parameters as $parameter) {
            $value = $parameter->getValue();
            if (isset($value['id']) && $value['id'] == $id) {
                $result = $value['code'];
            }
        }

        return $result;
    }

    /**
     * @param $id
     *
     * @return null
     *
     * @throws \Exception
     */
    public function getBankCardCode($id)
    {
        $parameters = $this->getBankCardValues();

        $result = null;
        foreach ($parameters as $parameter) {
            $value = $parameter->getValue();
            if (isset($value['id']) && $value['id'] == $id) {
                $result = $value['code'];
            }
        }

        return $result;
    }

    /**
     * @param $id
     *
     * @return array|null|string
     *
     * @throws \Exception
     */
    public function getTranslatedPaymentMethodLabelById($id)
    {
        $label = null;
        $label = $this->getTicketRestaurantLabel($id);
        if ($label) {
            return $label;
        }
        $label = $this->getBankCardLabel($id);
        if ($label) {
            return $label;
        }
        if (TicketPayment::REAL_CASH === $id) {
            return $this->translator->trans('payment.method.cash');
        }
        if (TicketPayment::CHECK_QUICK === $id) {
            return $this->translator->trans('payment.method.chqqui');
        }
        if (TicketPayment::MEAL_TICKET === $id) {
            return $this->translator->trans('payment.method.br');
        }
        throw new \Exception(
            'This id payment is not found, check your parameter configuration.'
        );
    }

    /**
     * @param $value
     *
     * @return null|string
     */
    public function getGroupOtherExpenseLabel($value)
    {
        /**
         * @var Parameter $parameter
         */
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    'globalId' => $value,
                ]
            );
        if ($parameter) {
            return $parameter->getLabel();
        }

        return null;
    }

    /**
     * @return array
     *
     * @param $restaurant
     *
     * @throws \Exception
     */
    public function getBankCardValues($restaurant = null)
    {
        if ($restaurant == null) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->select('parameter')
            ->where('parameter.type = :type_param')
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('type_param', Parameter::BANK_CARD_TYPE)
            ->getQuery()
            ->getResult();

        return $parameters;
    }

    /**
     * @return array
     */
    public function getBankCardPaymentIds($restaurant)
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->select('parameter')
            ->where('parameter.type = :type_param')
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('type_param', Parameter::BANK_CARD_TYPE)
            ->getQuery()
            ->getResult();
        $ids = [];
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            $ids[] = $parameter->getValue()['id'];
        }

        return $ids;
    }

    /**
     * @param $idPayment
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getBankCardLabel($idPayment)
    {
        $parameters = $this->getBankCardValues();
        $result = '';
        foreach ($parameters as $parameter) {
            $value = $parameter->getValue();
            if (isset($value['id']) && $value['id'] == $idPayment) {
                $result = $parameter->getLabel();
            }
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getLastClosuredDate()
    {
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    "type" => Parameter::LAST_CLOSURED_DAY,
                ]
            );
        if (!is_null($parameter)) {
            return $parameter->getValue();
        }
    }

    /**
     * @param Restaurant $currentRestaurant
     *
     * @return string
     */
    public function getRestaurantOpeningHour(Restaurant $currentRestaurant)
    {
        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
            [
                "type"             => Parameter::RESTAURANT_OPENING_HOUR,
                "originRestaurant" => $currentRestaurant,
            ]
        );
        if (!is_null($parameter)) {
            return $parameter->getValue();
        }

        return Parameter::RESTAURANT_OPENING_HOUR_DEFAULT;

    }

    /**
     * @param Restaurant $currentRestaurant
     *
     * @return string
     */
    public function getRestaurantClosingHour(Restaurant $currentRestaurant)
    {
        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
            [
                "type"             => Parameter::RESTAURANT_CLOSING_HOUR,
                "originRestaurant" => $currentRestaurant,
            ]
        );
        if (!is_null($parameter)) {
            return $parameter->getValue();
        }

        return Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT;
    }

    /**
     * @param null $id
     *
     * @return string
     */
    public function getExpenseLabel($id = null)
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::EXPENSE_LABELS_TYPE)
            ->getQuery()
            ->getResult();

        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if ($id == $parameter->getValue()['id']) {
                return $parameter->getLabel();
            }
        }

        return '';
    }

    /**
     * @param bool $all
     *
     * @return array
     */
    public function getExpenseLabels($all = true)
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::EXPENSE_LABELS_TYPE)
            ->orderBy('parameter.label', 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if ((!$all && !$parameter->getValue()['deleted']) || $all) {
                $result[$parameter->getValue()['id']] = $parameter->getLabel();
            }
        }
        arsort($result);

        return $result;
    }

    /**
     * @param bool $all
     *
     * @return array
     */
    public function getRecipeTicketLabels($all = true)
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::RECIPE_LABELS_TYPE)
            ->orderBy('parameter.label', 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if ($all) {
                $result[$parameter->getValue()['id']] = $parameter->getLabel();
            } else {
                if (isset($parameter->getValue()['deleted'])
                    && isset($parameter->getValue()['shown'])
                ) {
                    if ((!$all && !$parameter->getValue()['deleted']
                        && $parameter->getValue()['shown'])
                    ) {
                        $result[$parameter->getValue()['id']]
                            = $parameter->getLabel();
                    }
                } elseif (isset($parameter->getValue()['shown'])
                    && $parameter->getValue()['shown']
                ) {
                    $result[$parameter->getValue()['id']]
                        = $parameter->getLabel();
                } elseif (isset($parameter->getValue()['deleted'])
                    && $parameter->getValue()['deleted']
                ) {
                    $result[$parameter->getValue()['id']]
                        = $parameter->getLabel();
                }
            }
            //            foreach (RecipeTicket::$labels as $key => $label) {
            //                $result[$key] = $label;
            //            }
        }

        arsort($result);

        return $result;
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function getRecipeTicketLabel($id)
    {
        $result = '';
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::RECIPE_LABELS_TYPE)
            ->getQuery()
            ->getResult();
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if (isset($parameter->getValue()['id'])
                && $id == $parameter->getValue()['id']
            ) {
                $result = $parameter->getLabel();
                return $result;
            }
        }
        //        foreach (RecipeTicket::$labels as $key => $label) {
        //            if ($label == $id) {
        //                $result = $this->translator->trans('recipe_ticket.' . $label);
        //            }
        //        }
        //        if ($id == RecipeTicket::CHEST_ERROR) {
        //            $result = $this->translator->trans('recipe_ticket.' . $id);
        //        }
        //        if ($id == RecipeTicket::CASHBOX_ERROR) {
        //            $result = $this->translator->trans('recipe_ticket.' . $id);
        //        }


    }

    /**
     * @param null $id
     *
     * @return array|string
     */
    public function getErrorCountLabels($id = null)
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::ERROR_COUNT_TYPE)
            ->getQuery()
            ->getResult();
        if (is_null($id)) {
            return $parameters;
        }

        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if ($id == $parameter->getValue()) {
                return $parameter->getLabel();
            }
        }

        return '';
    }

    /**
     * @param null $id
     *
     * @return string
     */
    public function getErrorCountLabel($id = null)
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::ERROR_COUNT_TYPE)
            ->getQuery()
            ->getResult();

        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if ($id == $parameter->getValue()) {
                return $parameter->getLabel();
            }
        }

        return '';
    }

    /**
     * @param $type
     *
     * @return string
     */
    public function getCashLabel($type)
    {
        $label = '';
        $parameters = $this->getCashLabels();
        foreach ($parameters as $cash) {
            /**
             * @var Parameter $cash
             */
            if ($cash->getValue() == $type) {
                $label = $cash->getLabel();
            }
        }

        return $label;
    }

    /**
     * @return array
     */
    public function getCashLabels()
    {
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->setParameter('type_param', Parameter::CASH_PAYMENT_TYPE)
            ->getQuery()
            ->getResult();

        return $parameters;
    }

    /**
     * @return mixed
     */
    public function getNumberOfCashboxes()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                array(
                    'type'             => Parameter::NUMBER_OF_CASHBOXES,
                    'originRestaurant' => $currentRestaurant,
                )
            );

        return $parameter->getValue();
    }
    /**
     * @return mixed
     */
    public function getNumberOfCashboxesByRestaurant(Restaurant $restaurant)
    {

        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                array(
                    'type'             => Parameter::NUMBER_OF_CASHBOXES,
                    'originRestaurant' => $restaurant,
                )
            );

        return $parameter->getValue();
    }
    /**
     * @return mixed
     */
    public function getStartDayFunds()
    {
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                ['type' => Parameter::START_DAY_CASHBOX_FUNDS_TYPE]
            );

        return $parameter->getValue();
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function loadCashboxParameters()
    {
        $data = [];
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        //EFT
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findParameterByTypeAndRestaurant(
                Parameter::EFT_ACTIVATED_TYPE,
                $currentRestaurant
            );

        if (!$parameter) {
            $data['eft'] = false;
        } else {
            if ($parameter->getValue()) {
                $data['eft'] = true;//($parameter->getValue() === "true");
            } else {
                $data['eft'] = false;
            }
        }

        // Nbr cashboxes
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findParameterByTypeAndRestaurant(
                Parameter::NUMBER_OF_CASHBOXES,
                $currentRestaurant
            );
        if (is_null($parameter)) {
            $data['nbrCashboxes'] = null;
        } else {
            $data['nbrCashboxes'] = $parameter->getValue();
        }

        // cashbox starting day funds
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findParameterByTypeAndRestaurant(
                Parameter::START_DAY_CASHBOX_FUNDS_TYPE,
                $currentRestaurant
            );
        if (is_null($parameter)) {
            $data['cashboxStartingDayFunds'] = false;
        } else {
            $data['cashboxStartingDayFunds'] = $parameter->getValue();
        }

        //Opening Hour
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findParameterByTypeAndRestaurant(
                Parameter::RESTAURANT_OPENING_HOUR,
                $currentRestaurant
            );
        if (is_null($parameter)) {
            $data['openingHour'] = false;
        } else {
            $data['openingHour'] = $parameter->getValue();
        }

        // Closing Hour
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findParameterByTypeAndRestaurant(
                Parameter::RESTAURANT_CLOSING_HOUR,
                $currentRestaurant
            );
        if (is_null($parameter)) {
            $data['closingHour'] = false;
        } else {
            $data['closingHour'] = $parameter->getValue();
        }

        // Email
        if (is_null($currentRestaurant)) {
            $data['mail'] = false;
        } else {
            $data['mail'] = $currentRestaurant->getEmail();
        }
        // Tickets Restaurant Values
        $parameters = $this->em->getRepository(Parameter::class)
            ->findParametersByTypeAndRestaurant(
                Parameter::TICKET_RESTAURANT_TYPE,
                $currentRestaurant
            );
        $results = null;
        foreach ($parameters as $parameter) {
            if ($parameter->getValue()['electronic'] == false) {
                $results[] = $parameter;
            }
        }
        if (is_null($results)) {
            $data['checkRestaurantContainer'] = array();
        } else {
            foreach ($results as $parameter) {
                /**
                 * @var Parameter $parameter
                 */
                $tmp = array();
                foreach ($parameter->getValue()['values'] as $value) {
                    $tmp[]['unitValue'] = $value;
                }

                $data['checkRestaurantContainer']['ticketRestaurantCounts'][]
                    = [
                    'id'         => $parameter->getId(),
                    'ticketName' => $parameter->getLabel(),
                    'value'      => [
                        'type'           => $parameter->getValue()['type'],
                        'electronic'     => false,
                        'values'         => $tmp,
                        'id'             => $parameter->getValue()['id'],
                        'code'           => $parameter->getValue()['code'],
                        'affiliate_code' => (isset(
                            $parameter->getValue()['affiliate_code']
                        )) ? $parameter->getValue()['affiliate_code'] : '',
                    ],
                ];
            }
        }


        // Check Quick Values
        $parameters = $this->em->getRepository('Administration:Parameter')
            ->findParametersByTypeAndRestaurant(
                Parameter::CHECK_QUICK_TYPE,
                $currentRestaurant
            );

        $results = null;
        foreach ($parameters as $parameter) {

            $results[] = $parameter;

        }
        if (is_null($results)) {
            $data['checkQuickContainer'] = array();
        } else {

            foreach ($results as $parameter) {
                /**
                 * @var Parameter $parameter
                 */
                $tmp = array();
                foreach ($parameter->getValue()['values'] as $value) {
                    $tmp[]['unitValue'] = $value;
                }

                $data['checkQuickContainer'] ['checkQuickCounts'] [] = [

                    'id'        => $parameter->getId(),
                    'checkName' => $parameter->getLabel(),
                    'value'     => [
                        'type'   => $parameter->getValue()['type'],
                        'values' => $tmp,
                        'id'     => $parameter->getValue()['id'],
                        'code'   => $parameter->getValue()['code'],

                    ],

                ];


            }

        }

        // Foreign Currency Values

        $parameters = $this->em->getRepository('Administration:Parameter')
            ->findParametersByTypeAndRestaurant(
                Parameter::FOREIGN_CURRENCY_TYPE,
                $currentRestaurant
            );
        if (count($parameters) == 0) {
            $data['foreignCurrencyContainer'] = array();
        } else {
            foreach ($parameters as $parameter) {

                /**
                 * @var Parameter $parameter
                 */
                $data['foreignCurrencyContainer']['foreignCurrencyCounts'][] = [
                    'id'                   => $parameter->getId(),
                    'foreignCurrencyLabel' => $parameter->getLabel(),
                    'exchangeRate'         => $parameter->getValue(),
                ];
            }
        }

        // Additional Mails Values

        $parameters = $this->em->getRepository('Administration:Parameter')
            ->findParametersByTypeAndRestaurant(
                Parameter::RESTAURANT_ADDITIONAL_EMAILS,
                $currentRestaurant
            );
        if (count($parameters) === 0) {
            $data['additionalMailsContainer'] = array();
        } else {
            foreach ($parameters as $parameter) {

                /**
                 * @var Parameter $parameter
                 */
                $data['additionalMailsContainer']['mails'][] = [
                    'id'   => $parameter->getId(),
                    'mail' => $parameter->getValue(),
                ];
            }
        }

        return $data;
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public function updateCashboxParameter($data)
    {
        try {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant(
            );
            // Nbr cashboxes
            if (key_exists('nbrCashboxes', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findParameterByTypeAndRestaurant(
                    Parameter::NUMBER_OF_CASHBOXES,
                    $currentRestaurant
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::NUMBER_OF_CASHBOXES)
                        ->setValue(Parameter::NUMBER_OF_CASHBOXES_DEFAULT)
                        ->setLabel(null)
                        ->setOriginRestaurant($currentRestaurant);
                    $this->em->persist($parameter);
                } else {
                    $parameter->setValue($data['nbrCashboxes']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::NUMBER_OF_CASHBOXES)
                    ->setValue(Parameter::NUMBER_OF_CASHBOXES_DEFAULT)
                    ->setLabel(null)
                    ->setOriginRestaurant($currentRestaurant);
                $this->em->persist($parameter);
            }

            // cashbox starting day funds
            if (key_exists('cashboxStartingDayFunds', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findParameterByTypeAndRestaurant(
                    Parameter::START_DAY_CASHBOX_FUNDS_TYPE,
                    $currentRestaurant
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::START_DAY_CASHBOX_FUNDS_TYPE)
                        ->setValue(Parameter::START_DAY_CASHBOX_FUNDS_DEFAULT)
                        ->setLabel(null)
                        ->setOriginRestaurant($currentRestaurant);
                    $this->em->persist($parameter);
                } else {
                    if (is_string($data['cashboxStartingDayFunds'])) {
                        $data['cashboxStartingDayFunds'] = str_replace(
                            ',',
                            '.',
                            $data['cashboxStartingDayFunds']
                        );
                    }
                    $parameter->setValue($data['cashboxStartingDayFunds']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::START_DAY_CASHBOX_FUNDS_TYPE)
                    ->setValue(Parameter::START_DAY_CASHBOX_FUNDS_DEFAULT)
                    ->setLabel(null)
                    ->setOriginRestaurant($currentRestaurant);
                $this->em->persist($parameter);
            }

            // Opening Hour
            if (key_exists('openingHour', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findParameterByTypeAndRestaurant(
                    Parameter::RESTAURANT_OPENING_HOUR,
                    $currentRestaurant
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::RESTAURANT_OPENING_HOUR)
                        ->setValue(Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
                        ->setLabel(null)
                        ->setOriginRestaurant($currentRestaurant);
                    $this->em->persist($parameter);
                } else {
                    $parameter->setValue($data['openingHour']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::RESTAURANT_OPENING_HOUR)
                    ->setValue(Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
                    ->setLabel(null)
                    ->setOriginRestaurant($currentRestaurant);
                $this->em->persist($parameter);
            }

            // Closing Hour
            if (key_exists('closingHour', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findParameterByTypeAndRestaurant(
                    Parameter::RESTAURANT_CLOSING_HOUR,
                    $currentRestaurant
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::RESTAURANT_CLOSING_HOUR)
                        ->setValue(Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT)
                        ->setLabel(null)
                        ->setOriginRestaurant($currentRestaurant);
                    $this->em->persist($parameter);
                } else {
                    $parameter->setValue($data['closingHour']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::RESTAURANT_CLOSING_HOUR)
                    ->setValue(Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT)
                    ->setLabel(null)
                    ->setOriginRestaurant($currentRestaurant);
                $this->em->persist($parameter);
            }

            // Ticket Restaurant Values
            if (key_exists('checkRestaurantContainer', $data)) {
                $tmp = array();
                foreach (
                    $data['checkRestaurantContainer']['ticketRestaurantCounts']
                    as $ticket
                ) {
                    $tmp = $ticket['value'];
                    unset($tmp['values']);
                    $i = 0;
                    $parameter = $this->em->getRepository(
                        'Administration:Parameter'
                    )->find($ticket['id']);
                    foreach ($ticket['value']['values'] as $value) {
                        $tmp['values'][$i] = $value['unitValue'];
                        $i++;
                    }
                    $tmp['electronic'] = false;
                    $parameter->setValue($tmp);
                    $this->em->persist($parameter);
                }
            }

            //Check Quick Values
            if (key_exists('checkQuickContainer', $data)) {
                $tmp = array();
                foreach (
                    $data['checkQuickContainer']['checkQuickCounts'] as
                    $checkQuick
                ) {
                    $tmp = $checkQuick['value'];
                    unset($tmp['values']);
                    $i = 0;
                    $parameter = $this->em->getRepository(
                        'Administration:Parameter'
                    )->find($checkQuick['id']);
                    foreach ($checkQuick['value']['values'] as $value) {
                        $tmp['values'][$i] = $value['unitValue'];
                        $i++;
                    }
                    $parameter->setValue($tmp);
                    $this->em->persist($parameter);
                }


            }

            //Foreign Currency Values
            if (key_exists('foreignCurrencyContainer', $data)) {
                $ids = [];
                foreach (
                    $data['foreignCurrencyContainer']['foreignCurrencyCounts']
                    as $currency
                ) {
                    if (!$currency['id']) {
                        $parameter = new Parameter();
                        $parameter->setType(Parameter::FOREIGN_CURRENCY_TYPE)
                            ->setOriginRestaurant($currentRestaurant);
                    } else {
                        $parameter = $this->em->getRepository(
                            'Administration:Parameter'
                        )->find($currency['id']);
                    }
                    $parameter->setLabel($currency['foreignCurrencyLabel']);
                    $parameter->setValue(
                        str_replace(',', '.', $currency['exchangeRate'])
                    );

                    $this->em->persist($parameter);
                    $ids[] = $parameter->getId();
                }
                $parameters = $this->em->getRepository(
                    'Administration:Parameter'
                )->findParametersByTypeAndRestaurant(
                    Parameter::FOREIGN_CURRENCY_TYPE,
                    $currentRestaurant
                );
                foreach ($parameters as $parameter) {
                    if (!in_array($parameter->getId(), $ids)) {
                        $this->em->remove($parameter);
                    }
                }
            }

            if (key_exists('additionalMailsContainer', $data)) {
                $ids = [];
                foreach ($data['additionalMailsContainer']['mails'] as $mail) {
                    if (!$mail['id']) {
                        $parameter = new Parameter();
                        $parameter->setType(
                            Parameter::RESTAURANT_ADDITIONAL_EMAILS
                        )->setOriginRestaurant(
                            $currentRestaurant
                        );
                    } else {
                        $parameter = $this->em->getRepository(
                            'Administration:Parameter'
                        )->find($mail['id']);
                    }
                    $parameter->setValue($mail['mail']);
                    $this->em->persist($parameter);
                    $ids[] = $parameter->getId();
                }
                $parameters = $this->em->getRepository(
                    'Administration:Parameter'
                )->findParametersByTypeAndRestaurant(
                    Parameter::RESTAURANT_ADDITIONAL_EMAILS,
                    $currentRestaurant
                );
                foreach ($parameters as $parameter) {
                    if (!in_array($parameter->getId(), $ids)) {
                        $this->em->remove($parameter);
                    }
                }
            }

            $this->em->flush();
        } catch (\Exception $e) {
            // Logg error
            throw new \Exception($e);
        }
    }

    /**
     * @param $label
     * @param $rate
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveNewExhcangeRate($label, $rate)
    {
        $parameter = new Parameter();
        $parameter->setType(Parameter::FOREIGN_CURRENCY_TYPE)
            ->setValue($rate)
            ->setLabel($label);
        $this->em->persist($parameter);
        $this->em->flush();
    }

    /**
     * @return array
     */
    public function loadRestaurantParameters()
    {
        $data = [];

        //Opening Hour
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                ['type' => Parameter::RESTAURANT_OPENING_HOUR]
            );
        if (is_null($parameter)) {
            $data['openingHour'] = false;
        } else {
            $data['openingHour'] = $parameter->getValue();
        }

        // Closing Hour
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                ['type' => Parameter::RESTAURANT_CLOSING_HOUR]
            );
        if (is_null($parameter)) {
            $data['closingHour'] = false;
        } else {
            $data['closingHour'] = $parameter->getValue();
        }

        // Email
        $parameter = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                ['type' => Parameter::RESTAURANT_EMAIL]
            );
        if (is_null($parameter)) {
            $data['mail'] = false;
        } else {
            $data['mail'] = $parameter->getValue();
        }

        return $data;
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    public function updateRestaurantParameter($data)
    {
        try {
            // Opening Hour
            if (key_exists('openingHour', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findOneBy(
                    ['type' => Parameter::RESTAURANT_OPENING_HOUR]
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::RESTAURANT_OPENING_HOUR)
                        ->setValue(Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
                        ->setLabel(null);
                    $this->em->persist($parameter);
                } else {
                    $parameter->setValue($data['openingHour']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::RESTAURANT_OPENING_HOUR)
                    ->setValue(Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
                    ->setLabel(null);
                $this->em->persist($parameter);
            }

            // Closing Hour
            if (key_exists('closingHour', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findOneBy(
                    ['type' => Parameter::RESTAURANT_CLOSING_HOUR]
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::RESTAURANT_CLOSING_HOUR)
                        ->setValue(Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT)
                        ->setLabel(null);
                    $this->em->persist($parameter);
                } else {
                    $parameter->setValue($data['closingHour']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::RESTAURANT_CLOSING_HOUR)
                    ->setValue(Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT)
                    ->setLabel(null);
                $this->em->persist($parameter);
            }

            // Email
            if (key_exists('mail', $data)) {
                $parameter = $this->em->getRepository(
                    'Administration:Parameter'
                )->findOneBy(
                    ['type' => Parameter::RESTAURANT_EMAIL]
                );
                if (is_null($parameter)) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::RESTAURANT_EMAIL)
                        ->setValue(Parameter::RESTAURANT_EMAIL_DEFAULT)
                        ->setLabel(null);
                    $this->em->persist($parameter);
                } else {
                    $parameter->setValue($data['mail']);
                }
                $this->em->flush();
            } else {
                $parameter = new Parameter();
                $parameter->setType(Parameter::RESTAURANT_EMAIL)
                    ->setValue(Parameter::RESTAURANT_EMAIL_DEFAULT)
                    ->setLabel(null);
                $this->em->persist($parameter);
            }
        } catch (\Exception $e) {
            // Logg error
            throw new \Exception($e);
        }
    }

    /**
     * @param      $label
     * @param      $parameter
     * @param      $count
     * @param null $locale
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addLabelParameter(
        $label,
        $parameter,
        $count,
        $locale = null
    ) {
        if (!$label->getId()) {
            $label->setType($parameter);
            $this->em->persist($label);
            $label->setValue(strtolower($parameter).'_'.$label->getId());
        }
        $this->em->persist($label);

        $this->em->flush();
    }

    /**
     * @param $label
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteLabel($label)
    {
        try {
            $this->em->remove($label);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @return bool
     */
    public function getSupervisionAccessibility()
    {
        $accessibility = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    'type' => Parameter::SUPERVISION_ACCESSIBILITY,
                ]
            );

        if (!$accessibility) {
            $command = 'quick:ping:supervision';
            $this->commandLauncher->execute($command, true, false, false);
            $accessibility = $this->em->getRepository(
                'Administration:Parameter'
            )->findOneBy(
                [
                    'type' => Parameter::SUPERVISION_ACCESSIBILITY,
                ]
            );
        }
        /**
         * @var Parameter $accessibility
         */
        $result = $accessibility->getValue() === Parameter::ACCESSIBLE ? true
            : false;

        return $result;
    }

    /**
     * @param $key
     *
     * @return Parameter|null|object
     */
    public function getOrCreateTicketUploadLock($key)
    {
        $param = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(['type' => Parameter::TICKET_UPLOAD]);
        if (!$param) {
            $param = new Parameter();
            $param->setType(Parameter::TICKET_UPLOAD)
                ->setValue($key);
            $this->em->persist($param);
        }

        return $param;
    }

    /**
     * @param $key
     *
     * @return Parameter|null|object
     */
    public function getOrCreateMovementUploadLock($key)
    {
        $param = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(['type' => Parameter::MOVEMENT_UPLOAD]);
        if (!$param) {
            $param = new Parameter();
            $param->setType(Parameter::MOVEMENT_UPLOAD)
                ->setValue($key);
            $this->em->persist($param);
        }

        return $param;
    }

    /**
     * If there is no parameter in the table it will create a new one, by default false.
     *
     * @return Parameter|null|object
     */
    public function getGlobalCashCashboxCountParameter()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $param = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(
                [
                    'type'             => Parameter::GLOBAL_CASH_CASHBOX_COUNT_PARAMETER,
                    'originRestaurant' => $currentRestaurant,
                ]
            );
        if (!$param) {
            $param = new Parameter();
            $param->setType(Parameter::GLOBAL_CASH_CASHBOX_COUNT_PARAMETER);
            $param->setValue(false)
                ->setOriginRestaurant($currentRestaurant);
            $this->em->persist($param);
            $this->em->flush();
        }

        return $param;
    }
}
