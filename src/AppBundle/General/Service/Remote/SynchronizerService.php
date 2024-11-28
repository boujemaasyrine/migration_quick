<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 11:27
 */

namespace AppBundle\General\Service\Remote;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\General\Service\Download\AbstractDownloaderService;
use AppBundle\General\Service\WsSecurityService;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use RestClient\CurlRestClient;

/**
 * Class SynchronizerService
 *
 * @package AppBundle\General\Service\Remote
 */
abstract class SynchronizerService extends WsSecurityService
{
    // Dependency

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $supervisionUrl;

    protected $supervisionAlias;

    /**
     * @var string
     */
    protected $remoteHistoricType = null;

    /**
     * @var
     */
    protected $params;

    /**
     * @var string
     */
    protected $quickCode;

    // Userfull fields

    /**
     * @var RemoteHistoric
     */
    protected $mySynchro = null;

    /**
     * @var RemoteHistoric
     */
    protected $lastSynchro = null;

    protected $syncCmd;

    /**
     * @var ParameterService
     */
    protected $parameterService;

    public function setSupervisionAlias($alias)
    {
        $this->supervisionAlias = $alias;
    }

    public function setParameterService(ParameterService $parameterService)
    {
        $this->parameterService = $parameterService;
    }

    public function setEm(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function setSupervisionUrl($supervisionUrl)
    {
        $this->supervisionUrl = $supervisionUrl;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function setQuickCode($quickCode)
    {
        $this->quickCode = $quickCode;
    }

    // Usefull methods

    protected function getLastSynchro()
    {
        //Get last remote date
        return $this->em->getRepository("General:RemoteHistoric")->findOneBy(
            [
                'type' => $this->remoteHistoricType,
            ],
            [
                'startedAt' => 'Desc',
            ]
        );
    }

    protected function preUpload()
    {
        if ($this->supervisionUrl == "" or $this->parameterService->getSupervisionAccessibility() == false) {
            exit();
        }
        $this->lastSynchro = $this->getLastSynchro();
        // Starting new synchronisation
        $synchronisation = new RemoteHistoric();
        $synchronisation->setType($this->remoteHistoricType)
            ->setStatus(RemoteHistoric::CREATED)
            ->setStartedAt(new \DateTime('now'));
        $this->em->persist($synchronisation);
        $this->em->flush();

        $this->mySynchro = $synchronisation;
    }

    protected function startUpload($url, $data, $idSynCmd = null)
    {
        $data['restaurant'] = $this->quickCode;
        $data['token'] = $this->hashKey();
        $data['syncCmd'] = $idSynCmd;
        $this->syncCmd = $idSynCmd;
        if ($idSynCmd) {
            $synCmd = $this->em->getRepository("General:SyncCmdQueue")->findOneBy(
                array(
                    'globalId' => $idSynCmd,
                )
            );
            if ($synCmd) {
                $synCmd->setStatus(SyncCmdQueue::EXECUTING);
                $this->em->flush();
            }
        }
        // Starting new synchronisation
        $this->mySynchro->setStatus(RemoteHistoric::PENDING);
        $this->em->flush();
        //Send to the central
        $this->logger->addDebug(
            'Sending to: '.$this->supervisionUrl.'/'.$url.' '.$data['restaurant'].' '.$data['token'].' data count '.count(
                $data['data']
            ),
            ['Syncrhonizer:startUpload']
        );
        $curl = new CurlRestClient($this->supervisionUrl);
        $response = $curl->post($this->supervisionAlias.$url, $data);
        $responseJson = json_decode($response, true);
        $this->logger->addDebug("Response : ".$response, ['Syncrhonizer:startUpload']);
        $this->processUpdateSync($idSynCmd, $responseJson, $response);

        return $responseJson;
    }

    public function processUpdateSync($idSynCmd, $responseJson = null, $rawRespons = null)
    {
        $synCmd = $this->em->getRepository("General:SyncCmdQueue")->findOneBy(
            array(
                'globalId' => $idSynCmd,
            )
        );
        if ($synCmd) {
            $synCmd->setStatus(SyncCmdQueue::EXECUTED);
            $this->em->flush();
        }
    }

    protected function uploadFinishWithSuccess()
    {
        $this->mySynchro->setStatus(RemoteHistoric::SUCCESS);
        $this->em->flush();
        $this->notifyCentralSuccess();
    }

    protected function uploadFinishWithFail()
    {
        $this->mySynchro->setStatus(RemoteHistoric::FAIL);
        $this->em->flush();
        $this->notifyCentralSuccess(AbstractDownloaderService::FAIL);
    }

    public function notifyCentralSuccess($status = AbstractDownloaderService::SUCCESS)
    {
        echo "Notifying Central ".$this->syncCmd." \n";
        if ($this->syncCmd) {
            $data['restaurant'] = $this->quickCode;
            $data['token'] = $this->hashKey();
            $data['syncCmd'] = $this->syncCmd;
            $data['status'] = $status;
            $curl = new CurlRestClient($this->supervisionUrl);
            $response = $curl->post($this->supervisionAlias.$this->params['central_notify'], $data);

            return json_decode($response, true);
        }
    }

    abstract public function start($idSynCmd = null);
}
