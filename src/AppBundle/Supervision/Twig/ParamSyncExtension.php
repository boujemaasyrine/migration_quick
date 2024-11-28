<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 10/06/2016
 * Time: 10:59
 */

namespace AppBundle\Supervision\Twig;

use AppBundle\General\Entity\SyncCmdQueue;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class ParamSyncExtension extends \Twig_Extension
{
    /**
     * @var Translator
     */
    private $translator;

    private $em;

    public function __construct(Translator $translator, EntityManager $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }



    //    public function paramRemoteFilter($value,RemoteHistoric $remoteHistoric){
    //
    //        switch ($remoteHistoric->getType()){
    //            case RemoteHistoric::ORDERS:
    //                break;
    //            case RemoteHistoric::DELIVERIES:
    //                break;
    //            case RemoteHistoric::TRANSFERS:
    //                break;
    //            case RemoteHistoric::RETURNS:
    //                break;
    //            case RemoteHistoric::SHEET_MODELS:
    //                break;
    //            case RemoteHistoric::INVENTORIES:
    //                break;
    //            case RemoteHistoric::LOSS_PURCHASED:
    //                break;
    //            case RemoteHistoric::LOSS_SOLD:
    //                break;
    //            case RemoteHistoric::FINANCIAL_REVENUES:
    //                break;
    //            case RemoteHistoric::BUDGET_PREVISIONNELS:
    //                break;
    //            case RemoteHistoric::ADMIN_CLOSING:
    //                break;
    //            case RemoteHistoric::CASHBOX_COUNTS:
    //                break;
    //            case RemoteHistoric::CHEST_COUNTS:
    //                break;
    //            case RemoteHistoric::ENVELOPPES:
    //                break;
    //            case RemoteHistoric::WITHDRAWALS:
    //                break;
    //            case RemoteHistoric::DEPOSITS:
    //                break;
    //            case RemoteHistoric::EXPENSES:
    //                break;
    //            case RemoteHistoric::RECIPE_TICKETS:
    //                break;
    //            case RemoteHistoric::TICKETS:
    //                break;
    //            case RemoteHistoric::EMPLOYEE:
    //                break;
    //            default:
    //                return '';
    //        }
    //
    //    }


    public function getName()
    {
        return 'param_sync_extension';
    }
}
