<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:33
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\General\Service\WsSecurityService;
use AppBundle\Merchandise\Service\HistoricEntitiesService;
use AppBundle\Supervision\Entity\ProductSupervision;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use RestClient\CurlRestClient;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Monolog\Logger;

abstract class AbstractDownloaderService extends WsSecurityService
{
    const SUCCESS = 'success';
    const FAIL = 'fail';

    protected $em;

    protected $quickCode;



    protected $supervisionParams;

    protected $syncCmd;

    protected $logger;
    private $managerRegistry;

    /**
     * @var ParameterService
     */
    protected $parameterService;

    /**
     * @var HistoricEntitiesService
     */
    protected $historicEntityService;

    public function __construct(
        ManagerRegistry $managerRegistry,
        Logger $logger,
        $quickCode,
        $supervisionParams,
        HistoricEntitiesService $historicEntityService,
        ParameterService $parameterService
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->em = $this->managerRegistry->getManager();
        $this->quickCode = $quickCode;
        $this->supervisionParams = $supervisionParams;
        $this->historicEntityService = $historicEntityService;
        $this->logger = $logger;
        $this->parameterService = $parameterService;
    }

    /**
     * @param $url
     * @param SyncCmdQueue $synCmd
     * @return ProductSupervision|null
     */
    protected function startDownload($url, SyncCmdQueue $synCmd)
    {
        if ($synCmd) {
            // change the syncCommandQueue's state
            if (in_array($synCmd->getStatus(), [SyncCmdQueue::EXECUTED, SyncCmdQueue::EXECUTING])) {
                $this->logger->addAlert(
                    "TRYING TO EXECUTE AN ".$synCmd->getStatus()." CMD TYPE => ".$synCmd->getCmd(
                    )." ID => ".$synCmd->getId()
                );
                echo "TRYING TO EXECUTE AN ".$synCmd->getStatus()." CMD TYPE => ".$synCmd->getCmd(
                )." ID => ".$synCmd->getId()."\n";

                return null;
            } else {
                $synCmd->setStatus(SyncCmdQueue::EXECUTING);
                $this->em->flush();
            }

            $synCmd->setStatus(SyncCmdQueue::EXECUTED);
            $this->em->flush();

            //return the supervision product
            return $synCmd->getProduct();
        }

        return null;
    }

    abstract public function download($idSynCmd = null);

     public function notifyCentralSuccess(SyncCmdQueue $syncCmd){
         echo "Notifying success Central ".$syncCmd->getId()." \n";
         $this->logger->addInfo("Notifying success Central ".$syncCmd->getId()." \n");

         if ($syncCmd){
             $syncCmd->setStatus(SyncCmdQueue::EXECUTED_SUCCESS);
             $this->em->flush();
         }
     }

     public function notifyCentralFail(SyncCmdQueue $syncCmd){
         echo "Notifying fail Central ".$syncCmd->getId()." \n";
         $this->logger->addAlert("Notifying fail Central ".$syncCmd->getId()." \n");
         $this->em = $this->managerRegistry->resetManager();
         if ($syncCmd){
             $syncCmd = $this->em->getRepository(SyncCmdQueue::class)->find($syncCmd->getId());
             $syncCmd->setStatus(SyncCmdQueue::EXECUTED_FAIL);
             $this->em->flush($syncCmd);
         }
     }
}
