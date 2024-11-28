<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 09:15
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\DeletedEnvelope;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Financial\Form\Envelope\DeletedEnvelopeSearchType;
use AppBundle\Financial\Form\Envelope\EnvelopeCreateType;
use AppBundle\Financial\Form\Envelope\EnvelopeSearchType;
use AppBundle\Financial\Form\Envelope\EnvelopeTicketCreateType;
use AppBundle\Financial\Form\Envelope\EnvelopeTicketSearchType;
use AppBundle\Financial\Form\Envelope\EnvelopeType;
use AppBundle\Financial\Service\EnvelopeService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Security\RightAnnotation;

/**
 * Class ConsultationsController
 *
 * @Route("envelope")
 */
class EnvelopeController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list", name="envelope_list")
     *
     * @RightAnnotation("envelope_list")
     */
    public function envelopeListAction(Request $request)
    {
        $response = null;
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        if (!$request->isXmlHttpRequest()) {
            $form = $this->createForm(
                EnvelopeSearchType::class,
                [
                    'status' => Envelope::NOT_VERSED,
                ],
                array("restaurant" => $currentRestaurant)
            );

            $response = $this->render(
                "@Financial/Envelope/list.html.twig",
                [
                    'form' => $form->createView(),
                ]
            );
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list_ticket", name="envelope_ticket_list")
     *
     * @RightAnnotation("envelope_ticket_list")
     */
    public function envelopeTicketListAction(Request $request)
    {
        $response = null;
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        if (!$request->isXmlHttpRequest()) {
            $form = $this->createForm(
                new EnvelopeTicketSearchType($this->container),
                [
                    'status' => Envelope::NOT_VERSED,
                ],
                array("restaurant" => $currentRestaurant)
            );

            $response = $this->render(
                "@Financial/Envelope/list_ticket.html.twig",
                [
                    'form' => $form->createView(),
                ]
            );
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param int $download
     *
     * @return JsonResponse
     *
     * @Route("list/download/{download}", name="envelope_json_list", options={"expose"=true})
     */
    public function envelopeListJson(Request $request, $download = 0)
    {
        return $this->envelopeList($request, $download, Envelope::TYPE_CASH);
    }

    /**
     * @param Request $request
     * @param int $download
     *
     * @return JsonResponse
     *
     * @Route("list/ticket/download/{download}", name="envelope_ticket_json_list", options={"expose"=true})
     */
    public function envelopeTicketListJson(Request $request, $download = 0)
    {
        $type = Envelope::TYPE_TICKET;

        return $this->envelopeList($request, $download, $type);
    }

    /**
     * @param Request $request
     * @param int $download
     * @param null $type
     *
     * @return null|string|JsonResponse
     *
     * @throws \Exception
     */
    public function envelopeList(Request $request, $download = 0, $type = null)
    {
        $download = intval($download);
        $orders = array(
            'number',
            'date',
            'amount',
            'source',
            'ref',
            'owner',
            'cashier',
            'status',
            'sousType',
        );
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get(
            'restaurant.service'
        )->getCurrentRestaurant();

        if (1 === $download) {
            if (Envelope::TYPE_CASH === $type) {
                $name = 'Enveloppes_espece_';
            } else {
                $name = 'Enveloppes_titres_restaurant_';
            }
            $response = $this->get('toolbox.document.generator')
                ->generateXlsFile(
                    'envelope.service',
                    'getEnvelopes',
                    [
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'search' => $dataTableHeaders['search'],
                        'onlyList' => true,
                        'type' => $type,
                    ],
                    $type == Envelope::TYPE_TICKET ? $this->get('translator')->trans("envelope.index_ticket_title")
                        : $this->get('translator')->trans("envelope.index_title"),
                    $type === Envelope::TYPE_TICKET
                        ?
                        [
                            $this->get('translator')->trans(
                                "envelope.header.number"
                            ),
                            $this->get('translator')->trans(
                                "envelope.header.day"
                            ),
                            $this->get('translator')->trans(
                                "envelope.header.amount"
                            ),
                            $this->get('translator')->trans(
                                "envelope.header.type"
                            ),
                            $this->get('translator')->trans("label.reference"),
                            $this->get('translator')->trans("label.manager"),
                            $this->get('translator')->trans(
                                "envelope.header.status"
                            ),
                        ]
                        :
                        [
                            $this->get('translator')->trans(
                                "envelope.header.number"
                            ),
                            $this->get('translator')->trans(
                                "envelope.header.day"
                            ),
                            $this->get('translator')->trans(
                                "envelope.header.amount"
                            ),
                            $this->get('translator')->trans(
                                "envelope.header.type"
                            ),
                            $this->get('translator')->trans("label.reference"),
                            $this->get('translator')->trans("label.manager"),
                            $this->get('translator')->trans("label.member"),
                            $this->get('translator')->trans(
                                "envelope.header.status"
                            ),
                        ],
                    $type === Envelope::TYPE_TICKET
                        ?
                        function ($line) {
                            return [
                                $line['number'],
                                $line['date'],
                                number_format((float)str_replace(",", ".", $line['amount']), 2, '.', ''),
                                $line['sousType'],
                                $line['reference'],
                                $line['owner'],
                                $line['status'],
                            ];
                        }
                        :
                        function ($line) {
                            return [
                                $line['number'],
                                $line['date'],
                                number_format((float)str_replace(",", ".", $line['amount']), 2, '.', ''),
                                $line['source'],
                                $line['reference'],
                                $line['owner'],
                                $line['cashier'],
                                $line['status'],
                            ];
                        },
                    $name . date('dmY_His')
                );

            return $response;
        }

        $itemsEnvelope = $this->getDoctrine()->getRepository(
            "Financial:Envelope"
        )->getEnvelopesFilteredOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit'],
            $dataTableHeaders['search'],
            null,
            $type
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $itemsEnvelope['filtered'];
        $return['recordsTotal'] = $itemsEnvelope['total'];

        $return['data'] = $this->get('envelope.service')->serializeEnvelopes(
            $itemsEnvelope['list']
        );

        return new JsonResponse($return);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/create_enveloppe",name="create_enveloppe", options={"expose"=true})
     *
     * @Method({"POST"})
     */
    public function createCashboxCountEveloppeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = null;
            if ($request->getMethod() === "POST") {
                $response = new JsonResponse();
                $data = [];
                $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                $envelope = new Envelope();
                $envelope->setOriginRestaurant($currentRestaurant);

                $envelopeForm = $this->createForm(
                    EnvelopeType::class,
                    $envelope
                );
                $envelopeForm->handleRequest($request);
                if ($envelopeForm->isValid()) {
                    $this->get('envelope.service')->saveCashboxCountEnveloppe(
                        $envelope
                    );
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'envelope.enveloppe_created_with_success'
                    );
                    $data = [
                        "data" => [
                            "redirect" => $this->get('router')->generate(
                                'envelope_list'
                            ),
                        ],
                    ];
                    $response->setData($data);

                    return $this->get('workflow.service')->nextStep(
                        $response,
                        'cashbox_counting'
                    );
                }

                $data = [
                    "data" => [
                        $this->renderView(
                            '@Financial/CashBox/Counting/modal/enveloppe_creation.html.twig',
                            ["form" => $envelopeForm->createView()]
                        ),
                    ],
                    "errors" => [],
                ];
                $response->setData($data);

            }

            return $response;
        }
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/saisie_enveloppe",name="create_envelope_cash")
     *
     * @RightAnnotation("create_envelope_cash")
     */
    public function createEnvelopeCashAction(Request $request)
    {
        $envelope = new Envelope();
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        $envelopeForm = $this->createForm(
            EnvelopeCreateType::class,
            $envelope,
            array('restaurant' => $currentRestaurant,
                'envelopeService'=> $this->get('envelope.service'),
                'closingDate'=>$this->get('administrative.closing.service')->getCurrentClosingDate(),
                'lastClosingDate'=>$this->get('administrative.closing.service')->getLastClosingDate() )
        );
        $envelopeForm->handleRequest($request);

        if ($envelopeForm->isValid()) {
            $envelope->setOwner($this->getUser());
            $envelope->setType(Envelope::TYPE_CASH);
            $envelope->setOriginRestaurant($currentRestaurant);
            $this->get('envelope.service')->saveEnvelopeCash($envelope);
            $this->get('session')->getFlashBag()->add(
                'success',
                'envelope.enveloppe_created_with_success'
            );

            return $this->get('workflow.service')->nextStep(
                $this->redirect($this->get('router')->generate('envelope_list'))
            );


        }

        return $this->render(
            '@Financial/Envelope/envelope_creation.html.twig',
            array(
                'form' => $envelopeForm->createView(),
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/saisie_enveloppe_titre_restaurant",name="create_envelope_restau")
     *
     * @RightAnnotation("create_envelope_restau")
     */
    public function createEnvelopeTicketAction(Request $request)
    {
        $envelope = new Envelope();
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();
        $envelopeForm = $this->createForm(
            EnvelopeTicketCreateType::class,
            $envelope
        );
        $envelopeForm->handleRequest($request);
        if ($request->isXmlHttpRequest()) {
            if ($envelope->getSousType()) {
                $return['data'] = $this->get('envelope.service')
                    ->getTrMaxAmount($envelope->getSousType());
            } else {
                $return['data'] = 0;
            }

            return new JsonResponse($return);
        } else {
            if ($envelopeForm->isValid()) {
                $envelope->setOwner($this->getUser());
                $envelope->setType(Envelope::TYPE_TICKET);
                $envelope->setSource(Envelope::SMALL_CHEST);
                $envelope->setOriginRestaurant($currentRestaurant);

                $this->get('envelope.service')->saveEnvelopeCash($envelope);
                $this->get('session')->getFlashBag()->add(
                    'success',
                    'envelope.enveloppe_created_with_success'
                );

                return $this->get('workflow.service')->nextStep(
                    $this->redirect(
                        $this->get('router')->generate('envelope_ticket_list')
                    )
                );
            }
        }

        return $this->render(
            '@Financial/Envelope/envelope_creation_resto.html.twig',
            array(
                'form' => $envelopeForm->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @param Envelope $envelope
     * @Route("/delete_envelope/{envelope}", name="api_delete_envelope", options={"expose"=true})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @RightAnnotation("api_delete_envelope")
     */
    public function deleteEnvelopeAction(Request $request, Envelope $envelope)
    {
        if ($request->isXmlHttpRequest()) {
            try {
                $password = $request->request->get('password');
                $data = ["status" => 0];
                $user = $this->getUser();
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $salt = $user->getSalt();

                if ($encoder->isPasswordValid($user->getPassword(), trim($password), $salt)) {
                    if (!$envelope->getChestCount()) {// delete only if not related to chest count
                        if ($this->isWithdrawalEnvelope($envelope)) {
                            $weID = $envelope->getId();
                        }
                        //tracer l'enveloppe à supprimer
                        $deletedEnvelope = new DeletedEnvelope();
                        $deletedEnvelope->setOriginalId($envelope->getId());
                        $deletedEnvelope->setNumEnvelope($envelope->getNumber());
                        $deletedEnvelope->setOriginRestaurant($envelope->getOriginRestaurant());
                        $deletedEnvelope->setReference($envelope->getReference());
                        $deletedEnvelope->setAmount($envelope->getAmount());
                        $deletedEnvelope->setSourceId($envelope->getSourceId());
                        $deletedEnvelope->setSource($envelope->getSource());
                        $deletedEnvelope->setStatus($envelope->getStatus());
                        $deletedEnvelope->setType($envelope->getType());
                        $deletedEnvelope->setSousType($envelope->getSousType());
                        $deletedEnvelope->setOwner($envelope->getOwner());
                        $deletedEnvelope->setCashier($envelope->getCashier());
                        $deletedEnvelope->setDeposit($envelope->getDeposit());
                        $deletedEnvelope->setChestCount($envelope->getChestCount());
                        $deletedEnvelope->setCreatedAt($envelope->getCreatedAt());
                        $deletedEnvelope->setUpdatedAt($envelope->getUpdatedAt());
                        $deletedEnvelope->setDeletedBy($user);
                        $deletedEnvelope->setDeletedAt(new \DateTime());

                        $this->getDoctrine()->getManager()->persist($deletedEnvelope);
                        $this->getDoctrine()->getManager()->flush();

                        if ($this->get('envelope.service')->removeEnvelope($envelope)) {
                            $data = ["status" => 1];
                            if (!empty($weID)) {
                                $this->updateWithdrawalEnvelopeId($weID);
                            }
                        } else {
                            $data = ["status" => 0];
                        }
                    }
                } else {
                    $data = ["status" => -1];
                }


            } catch (\Exception $e) {
                $this->get('logger')->addError('Deleting Envelope', $e->getTrace());
                $data =
                    [
                        "status" => 0,
                        "errors" => [
                            $this->get('translator')->trans('Error.general.internal'),
                            $e->getLine() . " : " . $e->getMessage(),
                        ],
                    ];
            }

            return new JsonResponse($data);
        } else {
            throw new AccessDeniedHttpException("This method accept only ajax calls.");
        }
    }

    /**
     * Vérifier si l'enveloppe de source prélèvement ou non
     * @param Envelope $e
     * @return bool
     */
    private function isWithdrawalEnvelope(Envelope $e)
    {
        if ($e->getSource() == Envelope::WITHDRAWAL) {
            return true;
        }
        return false;
    }

    /**
     * supprimer la relation entre l'enveloppe ( supprimé) et le prélèvement
     * @param $weID
     */
    private function updateWithdrawalEnvelopeId($weID)
    {
        $this->getDoctrine()->getRepository(Withdrawal::class)->updateWithdrawalEnvelopeID($weID);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/deleted/list", name="deleted_envelope_list")
     *
     * @RightAnnotation("deleted_envelope_list")
     */
    public function deletedEnvelopeListAction(Request $request)
    {
        $response = null;
        $currentRestaurant = $this->get('restaurant.service')
            ->getCurrentRestaurant();

        if (!$request->isXmlHttpRequest()) {
            $form = $this->createForm(
                DeletedEnvelopeSearchType::class,
                [
                    'status' => DeletedEnvelope::NOT_VERSED,
                ],
                array("restaurant" => $currentRestaurant)
            );

            $response = $this->render(
                "@Financial/DeletedEnvelope/list.html.twig",
                [
                    'form' => $form->createView(),
                ]
            );
        }

        return $response;
    }


    /**
     * @param Request $request
     * @param int $download
     *
     * @return JsonResponse
     *
     * @Route("deleted/list/download/{download}", name="deleted_envelope_json_list", options={"expose"=true})
     */
    public function deletedEnvelopeListJson(Request $request, $download = 0)
    {
        return $this->deletedEnvelopeList($request, $download, DeletedEnvelope::TYPE_CASH);
    }

    /**
     * @param Request $request
     * @param int $download
     * @param null $type
     *
     * @return null|string|JsonResponse
     *
     * @throws \Exception
     */
    /**
     * @param Request $request
     * @param int $download
     * @param null $type
     *
     * @return null|string|JsonResponse
     *
     * @throws \Exception
     */
    public function deletedEnvelopeList(Request $request, $download = 0, $type = null)
    {
        $download = intval($download);
        $orders = array(
            'number',
            'date',
            'amount',
            'source',
            'ref',
            'owner',
            'cashier',
            'status',
            'deletedAt',
            'deletedBy'
        );

        // Récupérer les headers pour le DataTable
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        if (1 === $download) {
            // Générer le fichier Excel si nécessaire
            $response = $this->get('toolbox.document.generator')->generateXlsFile(
                'envelope.service',
                'getDeletedEnvelopes',
                [
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                    'search' => $dataTableHeaders['search'],
                    'onlyList' => true,
                    'type' => $type,
                ],
                // Adaptation des titres et des colonnes selon le type
                $type == DeletedEnvelope::TYPE_TICKET
                    ? $this->get('translator')->trans("deleted_envelope.index_ticket_title")
                    : $this->get('translator')->trans("deleted_envelope.index_title"),
                $type === DeletedEnvelope::TYPE_TICKET
                    ? [
                    $this->get('translator')->trans("deleted_envelope.header.number"),
                    $this->get('translator')->trans("deleted_envelope.header.day"),
                    $this->get('translator')->trans("deleted_envelope.header.amount"),
                    $this->get('translator')->trans("deleted_envelope.header.type"),
                    $this->get('translator')->trans("label.reference"),
                    $this->get('translator')->trans("label.manager"),
                    $this->get('translator')->trans("deleted_envelope.header.status"),
                    $this->get('translator')->trans("deleted_envelope.header.deletedAt"),
                    $this->get('translator')->trans("deleted_envelope.header.deletedBy")
                    ]
                    : [
                    $this->get('translator')->trans("deleted_envelope.header.number"),
                    $this->get('translator')->trans("deleted_envelope.header.day"),
                    $this->get('translator')->trans("deleted_envelope.header.amount"),
                    $this->get('translator')->trans("deleted_envelope.header.type"),
                    $this->get('translator')->trans("label.reference"),
                    $this->get('translator')->trans("label.manager"),
                    $this->get('translator')->trans("label.member"),
                    $this->get('translator')->trans("deleted_envelope.header.status"),
                    $this->get('translator')->trans("deleted_envelope.header.deletedAt"),
                    $this->get('translator')->trans("deleted_envelope.header.deletedBy")
                ],
                // Fonction de formatage pour les lignes du fichier Excel
                $type === DeletedEnvelope::TYPE_TICKET
                    ? function ($line) {
                    return [
                        $line['number'],
                        $line['date'],
                        number_format((float)str_replace(",", ".", $line['amount']), 2, '.', ''),
                        $line['sousType'],
                        $line['reference'],
                        $line['owner'],
                        $line['status'],
                        $line['deletedAt'],
                        $line['deletedBy']
                    ];
                }
                    : function ($line) {
                    return [
                        $line['number'],
                        $line['date'],
                        number_format((float)str_replace(",", ".", $line['amount']), 2, '.', ''),
                        $line['source'],
                        $line['reference'],
                        $line['owner'],
                        $line['cashier'],
                        $line['status'],
                        $line['deletedAt'],
                        $line['deletedBy']
                    ];
                },
                'DeletedEnvelopes_' . date('dmY_His')
            );

            return $response;
        }

        // Récupérer les données pour le DataTable
        $itemsDeletedEnvelope = $this->getDoctrine()->getRepository("Financial:DeletedEnvelope")
            ->getDeletedEnvelopesFilteredOrdered(
                $dataTableHeaders['criteria'],
                $dataTableHeaders['orderBy'],
                $dataTableHeaders['offset'],
                $dataTableHeaders['limit'],
                $dataTableHeaders['search'],
                null,
                $type
            );

        // Préparer la réponse JSON pour le DataTable
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $itemsDeletedEnvelope['filtered'];
        $return['recordsTotal'] = $itemsDeletedEnvelope['total'];

        // Sérialiser les données pour le DataTable
        $return['data'] = $this->get('envelope.service')->serializeDeletedEnvelopes(
            $itemsDeletedEnvelope['list']
        );

        return new JsonResponse($return);
    }



}
