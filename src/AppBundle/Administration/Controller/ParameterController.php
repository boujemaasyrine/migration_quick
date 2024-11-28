<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Form\Cashbox\CashboxParameterType;
use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Form\Cashbox\CashboxCountType;
use AppBundle\Financial\Form\Cashbox\DayIncomeType;
use AppBundle\Financial\Form\Envelope\EnvelopeType;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\General\Exception\OperationCannotBeDoneException;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\Security\Exception\NotAllowedException;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use AppBundle\Administration\Form\Restaurant\RestaurantParameterType;

/**
 * Class CashBoxController
 *
 * @Route("administration")
 */
class ParameterController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/foreign_currency",name="foreign_currency", options={"expose"=true})
     * @Method({"GET", "POST"})
     */
    public function addNewExchangeRateAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                if ($request->getMethod() === "POST") {
                    $label = $request->request->all()['label'];
                    $rate = $request->request->all()['rate'];
                    $this->get('paremeter.service')->saveNewExhcangeRate($label, $rate);
                    $data = [
                        "data" => [

                        ],
                    ];
                }
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }


    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/restaurantParameter",name="restaurant_parameter", options={"expose"=true})
     */
    public function restaurantParameterAction(Request $request)
    {
        $data = $this->get('paremeter.service')->loadCashboxParameters();

        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $form = $this->createForm(
            CashboxParameterType::Class,
            $data,
            array(
                'restaurant' => $currentRestaurant,
            )
        );

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('paremeter.service')->updateCashboxParameter($form->getData());
                $command = 'quick:upload:generic '.'closing_opening_hour';
                $this->get('toolbox.command.launcher')->execute($command, true, false, false);
                $message = $this->get('translator')->trans('success_parametres');
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('restaurant_parameter');
            }
            $message = $this->get('translator')->trans('cashbox.error_form');
            $this->get('session')->getFlashBag()->add('error', $message);

        }

        return $this->render(
            "@Administration/Cashbox/cashbox.html.twig",
            array(
                'form' => $form->createView(),
                'paymentMethodStatus' => $this->get('payment_method.status.service'),
            )
        );
    }
}
