<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:24
 */

namespace AppBundle\Supervision\Service\WsBiAPI;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;


class CaPerTaxeAndSoldingCanalService
{

    const CODE_TVA_LUX
        = [
            "0.03" => "1",
            "0.06" => "2",
            "0"    => "3",
            "0.17" => "4",
        ];
    const CODE_TVA_BE
        = [
            "0.21" => "1",
            "0.06" => "2",
            "0"    => "3",
            "0.12" => "4",
        ];
    const CODE_SOLDING_CANAL
        = [
            "eatin"     => "1",
            "eatout"    => "2",
            "drivethru" => "3",
            "kioskin"     => "4",
            "kioskout"     => "4",
        ];

    private $em;


    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getCaPerTaxeAndSoldingCanal($criteria, $limit, $offset)
    {

        if ($offset == 0) {
            $results = $this->em->getRepository(Ticket::class)
                ->getCaTicketPerTaxeAndSoldingCanal($criteria);

            return $this->serializeResult($results);
        } else {
            return null;
        }
    }

    /**
     * @param [] $results
     *
     * @return array
     */
    private function serializeResult($results)
    {
        $result = [];
        foreach ($results as $r) {
            $result[] = $this->serializeCa($r);
        }

        return $result;
    }

    /**
     * @param  $r
     *
     * @return array
     */
    private function serializeCa($r)
    {

        $commercialDate = \DateTime::createFromFormat(
            'Y-m-d',
            $r['commercial_date']
        );
        $commercialDate->setTime(0, 0);
        $result = array(
            "Restaurant"     => $r['restaurant'],
            "CommercialDate" => $commercialDate->format('Y-m-d H:i:s'),
            "canal de vente" => $this->getCodeSoldingCanal($r['canal_vente']),
            "taxe"           => $this->getCodeTva($r['restaurant'], $r["taxe"]),
            "CA_BRUT_TTC"    => number_format($r["ca_brut_ttc"], 2, ',', ''),
            "CA_BRUT_TVA"    => number_format($r["ca_brut_tva"], 2, ',', ''),
            "CA_BRUT_HT"     => number_format($r["ca_brut_ht"], 2, ',', ''),
            "Disc_BPub_TTC"  => number_format($r["disc_bpub_ttc"], 2, ',', ''),
            "Disc_BPub_TVA"  => number_format($r["disc_bpub_tva"], 2, ',', ''),
            "Disc_BPub_HT"   => number_format($r["disc_bpub_ht"], 2, ',', ''),
            "Disc_BRep_TTC"  => number_format($r["disc_brep_ttc"], 2, ',', ''),
            "Disc_BRep_TVA"  => number_format($r["disc_brep_tva"], 2, ',', ''),
            "Disc_BRep_HT"   => number_format($r["disc_brep_ht"], 2, ',', ''),
            "VA_TTC"         => 0,
            "VA_TVA"         => 0,
            "VA_HT"          => 0,
            "CA_NET_TTC"     => number_format($r["ca_net_ttc"], 2, ',', ''),
            "CA_NET_TVA"     => number_format($r["ca_net_tva"], 2, ',', ''),
            "CA_NET_HT"      => number_format($r["ca_net_ht"], 2, ',', ''),
        );

        return $result;
    }

    private function getCodeTva($code, $tva)
    {
        /**
         * @var Restaurant $restaurant
         */
        $restaurant = $this->em->getRepository(Restaurant::class)->findOneBy(
            array(
                'code' => $code,
            )
        );
        if (!$restaurant) {
            throw new \Exception('restaurant not found.');
        }
        if ($restaurant->getCountry() == Restaurant::COUNTRIES[0]) {
            return $this->getCodeTvaPerCountry('BE', $tva);
        } else {
            return $this->getCodeTvaPerCountry('LUX', $tva);
        }
    }

    private function getCodeTvaPerCountry($country, $tva)
    {
        if ($country == 'LUX') {
            if (key_exists($tva, self::CODE_TVA_LUX)) {
                return self::CODE_TVA_LUX[$tva];
            } else {
                throw new \Exception('Code TVA LUX not found.');
            }
        } else {
            if ($country == 'BE') {
                if ($tva == '0.03' or $tva == '0.17') {
                    return $tva;
                }
                if (key_exists($tva, self::CODE_TVA_BE)) {
                    return self::CODE_TVA_BE[$tva];
                } else {
                    throw new \Exception('Code TVA BE not found.');
                }
            } else {
                throw new \Exception();
            }
        }
    }

    private function getCodeSoldingCanal($soldingCanal)
    {
        if (array_key_exists(strtolower($soldingCanal), self::CODE_SOLDING_CANAL)) {
            return self::CODE_SOLDING_CANAL[strtolower($soldingCanal)];
        } else {
            return self::CODE_SOLDING_CANAL["eatin"];
        }
    }




}