<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Entity\TransferLine;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class TransferService
{

    private $em;
    private $translator;

    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
    }


    /**
     * @param Transfer[] $list
     * @return array
     */
    public function serializeTransfers($list)
    {
        $data = [];
        foreach ($list as $t) {
            $data[] = $this->serializeTransfer($t);
        }

        return $data;
    }

    /**
     * @param Transfer $t
     * @return array
     */
    public function serializeTransfer($t)
    {
        $data = array(
            'restCode' => $t->getOriginRestaurant() ? $t->getOriginRestaurant()->getCode() : '',
            'idTrans' => $t->getId(),
            'numTrans' => $t->getNumTransfer(),
            'restDest' => $t->getRestaurant()->getCode(),
            'date' => $t->getDateTransfer()->format('d/m/Y'),
            'type' => $t->getType() == Transfer::TRANSFER_IN ? 'IN' : 'OUT',
            'valTrans' => number_format($t->getValorization(), 2, ',', ''),
        );

        return $data;
    }

    /**
     * @param $criteria
     * @param $limit
     * @param $offset
     * @return array
     */
    public function getTransfers($criteria, $limit, $offset)
    {
        $data = $this->em->getRepository(Transfer::class)->getTransferBi($criteria, $offset, $limit);
        $return = $this->serializeTransfers($data);

        return $return;
    }


    /**
     * @param Transfer[] $list
     * @return array
     */
    public function serializeTransferLines($list)
    {
        $data = [];
        foreach ($list as $t) {
            $transfer = $this->serializeTransfer($t);
            foreach ($t->getLines() as $line) {
                $line = $this->serializeTransferLine($line);
                $data[] = array_merge($transfer, $line);
            }
        }

        return $data;
    }

    /**
     * @param TransferLine $l
     * @return array
     */
    public function serializeTransferLine($l)
    {
        $p = $l->getProduct();

        $data = array(
            'codeArticle' => $p->getExternalId(),
            'qteLiv' => $l->getTotal(),
            'prixAchat' => number_format($p->getBuyingCost(), 2, ',', ''),
            'montant' => number_format($l->getValorization(), 2, ',', ''),
            'categorieId' => $p->getProductCategory()->getId(),
            'categorie' => $p->getProductCategory()->getName(),
            'groupeId' => $p->getProductCategory()->getCategoryGroup()->getId(),
            'groupe' => $p->getProductCategory()->getCategoryGroup()->getName(),
        );

        return $data;
    }

    /**
     * @param $criteria
     * @param $limit
     * @param $offset
     * @return array
     */
    public function getTransferLines($criteria, $limit, $offset)
    {
        $data = $this->em->getRepository(Transfer::class)->getTransferBi($criteria, $offset, $limit);
        $return = $this->serializeTransferLines($data);

        return $return;
    }
}
