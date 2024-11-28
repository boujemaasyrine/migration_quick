<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 26/05/2016
 * Time: 09:02
 */

namespace AppBundle\Supervision\Service;

//use AppBundle\Entity\Administration\ParameterLabel;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class ParameterService
{
    private $em;
    private $translator;
    /**
     * @var SyncCmdCreateEntryService
     */
    private $syncCmdCreateEntryService;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        SyncCmdCreateEntryService $syncCmdCreateEntryService
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->syncCmdCreateEntryService = $syncCmdCreateEntryService;
    }

    public function getTicketRestaurantMethod(
        $electronic = false,
        $paymentMethods = null
    ) {
        if ($paymentMethods === null) {
            $paymentMethods = $this->em->getRepository(PaymentMethod::class)
                ->createQueryBuilder('pm')
                ->where('pm.type = :type_param')
                ->setParameter(
                    'type_param',
                    PaymentMethod::TICKET_RESTAURANT_TYPE
                )
                ->orderBy('pm.label')
                ->getQuery()
                ->getResult();
        }

        $result = null;
        foreach ($paymentMethods as $parameter) {
            if (isset($parameter->getValue()['electronic'])
                && $parameter->getValue()['electronic'] == $electronic
            ) {
                $result[] = $parameter;
            }
        }

        return $result;
    }

    public function getCheckQuickMethod($paymentMethods = null)
    {
        if ($paymentMethods === null) {
            $paymentMethods = $this->em->getRepository(PaymentMethod::class)
                ->createQueryBuilder('pm')
                ->where('pm.type = :type_param')
                ->setParameter('type_param', PaymentMethod::CHECK_QUICK_TYPE)
                ->orderBy('pm.label')
                ->getQuery()
                ->getResult();
        }

        $result = null;
        foreach ($paymentMethods as $parameter) {
            $result[] = $parameter;

        }

        return $result;
    }

    public function getPaymentMethodLabel(
        $paymentMethod = null,
        $paymentId = null
    ) {
        $paymentMethods = $this->em->getRepository(PaymentMethod::class)
            ->createQueryBuilder('pm')
            ->where('pm.type = :type_param')
            ->setParameter('type_param', $paymentMethod)
            ->orderBy('pm.label')
            ->getQuery()
            ->getResult();
        $result = null;
        foreach ($paymentMethods as $parameter) {
            /**
             * @var PaymentMethod $parameter
             */
            if (($paymentId == null and $parameter->getType() == $paymentMethod) or ($paymentId != null and isset($parameter->getValue()['id']) and $parameter->getValue()['id'] == $paymentId) ) {
                return $parameter->getLabel();
            }
        }

        return $result;
    }

    public function getParameterLabel($id = null)
    {
        $parameterLabels = $this->em->getRepository(Parameter::class)
            ->createQueryBuilder('pl')
            ->getQuery()
            ->getResult();
        $result = null;
        foreach ($parameterLabels as $parameter) {

            if (isset($parameter->getValue()['id'])
                && $id == $parameter->getValue()['id']
            ) {
                $result = $parameter->getLabel();
            }
        }

        return $result;
    }

    public function getBankCardMethod()
    {
        $parameters = $this->em->getRepository(PaymentMethod::class)
            ->createQueryBuilder('pm')
            ->where('pm.type = :type_param')
            ->setParameter('type_param', PaymentMethod::BANK_CARD_TYPE)
            ->getQuery()
            ->getResult();

        return $parameters;
    }

    /**
     * @param Restaurant $restaurant
     * @param            $data
     *
     * @return bool
     */
    public function saveParameters($restaurant, $data)
    {

        try {
            $cashMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneByType(PaymentMethod::REAL_CASH_TYPE);
            /*$checkQuickMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneByType(PaymentMethod::CHECK_QUICK_TYPE);

            */
            $foreignCurrency = $this->em->getRepository(PaymentMethod::class)
                ->findOneByType(PaymentMethod::FOREIGN_CURRENCY_TYPE);

            $restaurant->setEft($data['eft']);
            $eftParameters = $restaurant->getParameters()->filter(
                function (Parameter $parameter) {
                    return $parameter->getType()
                        == Parameter::EFT_ACTIVATED_TYPE;
                }
            );
            foreach ($eftParameters as $parameter) {
                $this->em->remove($parameter);
            }
            $this->em->flush();
            $eftParameter = new Parameter();
            $eftParameter->setOriginRestaurant($restaurant);
            $eftParameter->setType(Parameter::EFT_ACTIVATED_TYPE);
            $eftParameter->setValue($data["eft"]);
            $this->em->persist($eftParameter);

            foreach ($restaurant->getPaymentMethods() as $method) {
                /**
                 * @var PaymentMethod $method
                 */
                $restaurant->removePaymentMethod($method);
                $method->removeRestaurant($restaurant);
            }
            foreach ($data['ticketRestaurant'] as $ticket) {
                /**
                 * @var PaymentMethod $ticket
                 */
                $restaurant->addPaymentMethod($ticket);
                $ticket->addRestaurant($restaurant);
            }
            foreach ($data['electronicTicketRestaurant'] as $electronicTicket) {
                /**
                 * @var PaymentMethod $electronicTicket
                 */
                $restaurant->addPaymentMethod($electronicTicket);
                $electronicTicket->addRestaurant($restaurant);
            }
            foreach ($data['bankCard'] as $bankCard) {
                /**
                 * @var PaymentMethod $bankCard
                 */
                $restaurant->addPaymentMethod($bankCard);
                $bankCard->addRestaurant($restaurant);
            }
            if (in_array(
                PaymentMethod::REAL_CASH_TYPE,
                $data['paymentMethod']
            )
            ) {
                $restaurant->addPaymentMethod($cashMethod);
                $cashMethod->addRestaurant($restaurant);
            }

            foreach ($data['checkQuick'] as $check) {
                /**
                 * @var PaymentMethod $check
                 */
                $restaurant->addPaymentMethod($check);
                $check->addRestaurant($restaurant);
            }

            /*if (in_array(
                PaymentMethod::CHECK_QUICK_TYPE,
                $data['paymentMethod']
            )
            ) {
                $restaurant->addPaymentMethod($checkQuickMethod);
                $checkQuickMethod->addRestaurant($restaurant);
            }*/
            if (in_array(
                PaymentMethod::FOREIGN_CURRENCY_TYPE,
                $data['paymentMethod']
            )
            ) {
                $restaurant->addPaymentMethod($foreignCurrency);
                $foreignCurrency->addRestaurant($restaurant);
            }
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            echo $e->getMessage();
            throw $e;
            //            return false;
        }
    }

    /**
     * @param Restaurant $restaurant
     *
     * @return mixed $data
     */
    public function loadParameters($restaurant)
    {
        $data = array();

        $data['eft'] = $restaurant->getEft() ? 'true' : false;
        if ($restaurant->hasPaymentMethodByType(
            PaymentMethod::REAL_CASH_TYPE
        )
        ) {
            $data['paymentMethod'][] = PaymentMethod::REAL_CASH_TYPE;
        }

        $data['checkQuick'] = $restaurant->getPaymentMethodByType(
            PaymentMethod::CHECK_QUICK_TYPE
        );

        if (count($data['checkQuick']) > 0) {
            $data['paymentMethod'][] = PaymentMethod::CHECK_QUICK_TYPE;
        }

        if ($restaurant->hasPaymentMethodByType(
            PaymentMethod::FOREIGN_CURRENCY_TYPE
        )
        ) {
            $data['paymentMethod'][] = PaymentMethod::FOREIGN_CURRENCY_TYPE;
        }

        $data['bankCard'] = $restaurant->getPaymentMethodByType(
            PaymentMethod::BANK_CARD_TYPE
        );
        if (count($data['bankCard']) > 0) {
            $data['paymentMethod'][] = PaymentMethod::BANK_CARD_TYPE;
        }

        $ticketRestaurant = $restaurant->getPaymentMethodByType(
            PaymentMethod::TICKET_RESTAURANT_TYPE
        );

        $data['ticketRestaurant'] = $this->getTicketRestaurantMethod(
            false,
            $ticketRestaurant
        );
        $data['electronicTicketRestaurant'] = $this->getTicketRestaurantMethod(
            true,
            $ticketRestaurant
        );
        if (count($data['ticketRestaurant']) > 0) {
            $data['paymentMethod'][] = PaymentMethod::TICKET_RESTAURANT_PAPER;
        }
        if (count($data['electronicTicketRestaurant']) > 0) {
            $data['paymentMethod'][]
                = PaymentMethod::TICKET_RESTAURANT_ELECTRONIC;
        }

        return $data;
    }

    /**
     * @param Parameter $label
     * @param           $parameter
     * @param           $count
     * @param null      $locale
     */


    public function addLabelParameter(
        Parameter $label,
        $parameter,
        $count,
        $locale = null
    ) {
        if (!$label->getId()) {
            $label->setType($parameter);
            $this->em->persist($label);
            $value = [
                'id'      => $label->getId(),
                'deleted' => false,
            ];
            if ($parameter == Parameter::RECIPE_LABELS_TYPE) {
                $value['shown'] = true;
            }
            $label->setLabel($label->getLabelTranslation('fr'));
            $label->setValue($value);
        }
        $this->em->persist($label);
        $label->setGlobalId($label->getId());
        /* This section to be verified as the synchro*/

        /*if($label->getType() === Parameter::RECIPE_LABELS_TYPE) {
            $this->syncCmdCreateEntryService->createRecipeTicketLabelEntry();
        } elseif($label->getType() === Parameter::EXPENSE_LABELS_TYPE) {
            $this->syncCmdCreateEntryService->createExpenseLabelEntry();
        }*/

        $this->em->flush();
    }

    /**
     * @param Parameter $label
     *
     * @return bool
     * @throws \Exception
     * */

    public function deleteLabel($label)
    {
        try {
            $value = $label->getValue();
            $value['deleted'] = true;
            $label->setValue($value);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function getActiveLabelsByType($parameter)
    {
        $result = array();
        $labels = $this->em->getRepository(Parameter::class)
            ->findParameterByType($parameter);
        foreach ($labels as $label) {
            if (!$label->getValue()['deleted']) {
                $result[] = $label;
            }
        }

        return $result;
    }

    public function getLabels($criteria, $order, $limit, $offset, $type)
    {
        $result = array();
        $labels = $this->em->getRepository(Parameter::class)->getLabelsOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $type
        );
        foreach ($labels as $label) {
            if (!$label->getValue()['deleted']) {
                $result[] = $label;
            }
        }

        return $this->serializeLabels($result);
    }

    public function serializeLabels($labels)
    {
        $result = [];
        foreach ($labels as $l) {
            $result[] = array(
                'Ref'    => $l->getId(),
                'nameFr' => $l->getLabelTranslation('fr'),
                'nameNl' => $l->getLabelTranslation('nl'),
            );
        }

        return $result;
    }
}
