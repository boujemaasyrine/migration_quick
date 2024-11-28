<?php

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\WithdrawalTmp;
use AppBundle\Financial\Service\WithdrawalSynchronizationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class AjaxFundManagementController
 * @package AppBundle\Financial\Controller
 * @Route("/ajax-financial")
 */
class AjaxFundManagementController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/ajax-withdrawal-validation",name="ajax_withdrawal_validation",options={"expose"=true})
     */
    public function validationWithdrawalAction(Request $request)
    {
        $wTmpID = $request->get('id');
        $user = $this->getUser();
        $wRep = $this->getDoctrine()
            ->getRepository(WithdrawalTmp::class);
        $withdrawal = $wRep->validateWithdrawal($wTmpID, $user);
        $success = is_object($withdrawal) ? true : false;
        $status = $success ? 200 : 500;
        return new Response(
            json_encode(['success' => $success, 'id' => $wTmpID, "data" => [
                'footer_btn' => $this->renderView(
                    '@Financial/FundManagement/Withdrawal/btn.html.twig',
                    array(
                        'type' => 'validate',
                        'withdrawalCreated' => $withdrawal,
                    )
                ),
                'withdrawalCreated' => $withdrawal->getId(),
                'envelope' => ($withdrawal->getEnvelopeId()) ? 'true' : 'false'
            ]]), $status, array(
                'Content-Type' => "application/json")
        );
    }
}