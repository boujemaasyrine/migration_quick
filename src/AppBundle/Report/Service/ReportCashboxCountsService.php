<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 05/04/2016
 * Time: 18:14
 */

namespace AppBundle\Report\Service;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class ReportCashboxCountsService
{

    private $em;
    private $container;
    private $sqlQueriesDir;

    public function __construct(EntityManager $em, Container $container, $sqlQueriesDir)
    {
        $this->em = $em;
        $this->container = $container;
        $this->sqlQueriesDir = $sqlQueriesDir;
    }

    public function getCashboxCountsOwner($filter, $limit = null, $offset = null)
    {
        return $this->em->getRepository(CashboxCount::class)->getCashboxCountsOwner($filter, $this->sqlQueriesDir);
    }

    public function getCashboxCountsCashier($filter, $limit = null, $offset = null)
    {
        return $this->em->getRepository(CashboxCount::class)->getCashboxCountsCashier($filter, $this->sqlQueriesDir);
    }

    public function getCashboxCountsAnomalies($filter, $limit = null, $offset = null)
    {
        return $this->em->getRepository(CashboxCount::class)->getCashboxCountsAnomalies($filter, $this->sqlQueriesDir);
    }

    public function getCashboxCountsOwnerCSV($filter, $limit = null, $offset = null)
    {
        if ($offset > 0) {
            return null;
        }
        $result = $this->em->getRepository('Financial:CashboxCount')->getCashboxCountsOwner(
            $filter,
            $this->sqlQueriesDir
        );

        $result['total'][0]['owner_name'] = 'Total';
        $return[] = array_merge($result['lines'], $result['total']);

        return $return[0];
    }

    public function getCashboxCountsCashierCSV($filter, $limit = null, $offset = null)
    {
        if ($offset > 0) {
            return null;
        }
        $result = $this->em->getRepository('Financial:CashboxCount')->getCashboxCountsCashier(
            $filter,
            $this->sqlQueriesDir
        );

        $result['total'][0]['cashier_name'] = 'Total';
        $return[] = array_merge($result['lines'], $result['total']);

        return $return[0];
    }

    public function getCashboxCountsAnomaliesCSV($filter, $limit = null, $offset = null)
    {
        if ($offset > 0) {
            return null;
        }
        $result = $this->em->getRepository('Financial:CashboxCount')->getCashboxCountsAnomalies(
            $filter,
            $this->sqlQueriesDir
        );

        $result['total'][0]['cashier_name'] = 'Total';

        $size_result = sizeof($result['lines']);
        $result['avj'][0]['cashier_name'] = 'Moyenne';
        $result['avj'][0]['diff_caisse_percent'] = $size_result ? $result['total'][0]['diff_caisse'] / $size_result : 0;
        $result['avj'][0]['total_cancels_percent'] = $size_result ? $result['total'][0]['total_cancels'] / $size_result : 0;
        $result['avj'][0]['total_corrections_percent'] = $size_result ? $result['total'][0]['total_corrections'] / $size_result : 0;
        $result['avj'][0]['total_abondons_percent'] = $size_result ? $result['total'][0]['total_abondons'] / $size_result : 0;
        $result['avj'][0]['rc_real_percent'] = $size_result ? $result['total'][0]['rc_real'] / $size_result : 0;
        $result['avj'][0]['cr_real_percent'] = $size_result ? $result['total'][0]['cr_real'] / $size_result : 0;

        $return[] = array_merge($result['lines'], $result['total'], $result['avj']);

        return $return[0];
    }

    public function generateCSVReportOwner($filter)
    {
        $trans = $this->container->get('translator');
        $trans_prefix = "cashbox_counts_owner.report_labels";

        $filename = 'cashbox_counts_owner_'.date('Y_m_d_H_i_s');
        $response = $this->container->get('toolbox.document.generator')
            ->generateXlsFile(
                'report.cashbox.service',
                'getCashboxCountsOwnerCSV',
                [
                    'filter' => $filter,
                ],
                $trans->trans("cashbox_counts_owner.title"),
                [
                    [
                        $trans->trans("cashbox_counts_owner.title"),
                        $trans->trans("period_label"),
                        $trans->trans("from_label"),
                        $filter['startDate']->format('Y-m-d'),
                        $trans->trans("to_label"),
                        $filter['endDate']->format('Y-m-d'),

                    ],
                    [
                        $trans->trans($trans_prefix.".responsable"),
                        $trans->trans($trans_prefix.".nombre_comptage"),
                        $trans->trans($trans_prefix.".ca_reel"),
                        $trans->trans($trans_prefix.".ca_theorique"),
                        $trans->trans($trans_prefix.".especes"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".titres_restaurant"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".cartes_bancaires"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".bon_repas"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".discounts"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".annulations"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".corrections"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".abandons"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".diff_caisse"),

                    ],
                    [
                        '',
                        '',
                        '',
                        '',

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".nombre"),
                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".nombre"),
                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".nombre"),
                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        '',
                    ],
                ],
                function ($line) {
                    return [
                        $line['owner_name'],
                        $line['nbre'] ? $line['nbre'] : 0,

                        $line['ca_real'] ? number_format($line['ca_real'], 2) : 0,

                        $line['ca_theoretical'] ? number_format($line['ca_theoretical'], 2,'.', '') : 0,

                        number_format($line['rc_real'] +$line['withdrawals'], 2, '.', '')  ,
                        number_format($line['rc_real']+$line['withdrawals'] - $line['rc_theoretical']-$line['cb_canceled'], 2, '.', ''),
                        $line['ca_real'] != 0 ? number_format($line['rc_real'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['cr_real'] ? number_format($line['cr_real'], 2,'.', '') : 0,
                        number_format($line['cr_real'] - $line['cr_theoretical'], 2,'.', ''),
                        $line['ca_real'] != 0 ? number_format($line['cr_real'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['bc_real'] ? number_format($line['bc_real'], 2,'.', '') : 0,
                        number_format($line['bc_real'] - $line['bc_theoretical'], 2,'.', ''),
                        $line['ca_real'] != 0 ? number_format($line['bc_real'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['mt_theoretical'] ? number_format($line['mt_theoretical'], 2,'.', ' ') : 0,
                        number_format($line['mt_theoretical'] - $line['mt_theoretical'], 2,'.', ''),
                        $line['ca_real'] != 0 ? number_format($line['mt_theoretical'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['d_theoretical'] ? number_format($line['d_theoretical'], 2,'.', '') : 0,
                        number_format($line['d_theoretical'] - $line['d_theoretical'], 2,'.', ' '),
                        $line['ca_real'] != 0 ? number_format($line['d_theoretical'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['nbr_cancels'] ? number_format($line['nbr_cancels'], 2,'.', '') : 0,
                        $line['total_cancels'] ? number_format($line['total_cancels'], 2,'.', '') : 0,
                        $line['ca_real'] != 0 ? number_format($line['total_cancels'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['nbr_corrections'] ? number_format($line['nbr_corrections'], 2,'.', '') : 0,
                        $line['total_corrections'] ? number_format($line['total_corrections'], 2,'.', '') : 0,
                        $line['ca_real'] != 0 ? number_format($line['total_corrections'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                        $line['nbr_abondons'] ? number_format($line['nbr_abondons'] , 2,'.', ''): 0,
                        $line['total_abondons'] ? number_format($line['total_abondons'], 2,'.', ' ') : 0,
                        $line['ca_real'] != 0 ? number_format($line['total_abondons'] * 100 / $line['ca_real'], 2,'.', '') : 0,
                         number_format($line['ca_real'] - $line['ca_theoretical'] + $line['d_theoretical'] + $line['mt_theoretical'], 2,'.', ''),
                    ];
                },
                $filename
            );

        return $response;
    }

    public function generateCSVReportCashier($filter)
    {
        $trans = $this->container->get('translator');
        $trans_prefix = "cashbox_counts_cashier.report_labels";
        $filename = 'cashbox_counts_cashier_'.date('Y_m_d_H_i_s');
        $response = $this->container->get('toolbox.document.generator')
            ->generateXlsFile(
                'report.cashbox.service',
                'getCashboxCountsCashierCSV',
                [
                    'filter' => $filter,
                ],
                $trans->trans("cashbox_counts_cashier.title"),
                [
                    [
                        $trans->trans("cashbox_counts_cashier.title"),
                        $trans->trans("period_label"),
                        $trans->trans("from_label"),
                        $filter['startDate']->format('Y-m-d'),
                        $trans->trans("to_label"),
                        $filter['endDate']->format('Y-m-d'),

                    ],
                    [
                        $trans->trans($trans_prefix.".responsable"),
                        $trans->trans($trans_prefix.".nombre_comptage"),
                        $trans->trans($trans_prefix.".ca_reel"),
                        $trans->trans($trans_prefix.".ca_theorique"),
                        '',
                        '',
                        '',
                        $trans->trans($trans_prefix.".especes"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".titres_restaurant"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".cartes_bancaires"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".bon_repas"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".autre_quick"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".annulations"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".corrections"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".abandons"),
                        '',
                        '',
                        $trans->trans($trans_prefix.".diff_caisse"),
                    ]
                    ,
                    [
                        '',
                        '',
                        '',

                        $trans->trans($trans_prefix.".brut"),
                        $trans->trans($trans_prefix.".discounts"),
                        $trans->trans($trans_prefix.".pourcentage"),
                        $trans->trans($trans_prefix.".net"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".ecart"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".nombre"),
                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".nombre"),
                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".nombre"),
                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        '',
                    ],
                ],
                function ($line) {
                    return [
                        $line['cashier_name'],
                        $line['nbre'] ? $line['nbre'] : 0,

                        $line['ca_real'] ? number_format($line['ca_real'], 2,'.', '') : 0,

                        $line['ca_theoretical'] ? number_format($line['ca_theoretical'], 2, '.', '') : 0,
                        $line['d_theoretical'] ? number_format($line['d_theoretical'], 2, '.', '') : 0,
                        $line['ca_theoretical'] != 0 ? number_format($line['d_theoretical'] * 100 / $line['ca_theoretical'], 2, '.', '') : 0,
                        number_format($line['ca_theoretical'] - abs($line['d_theoretical']) - $line['mt_theoretical'], 2, '.', ''),
                        number_format($line['rc_real'] +$line['withdrawals'], 2, '.', '')  ,
                        number_format($line['rc_real']+$line['withdrawals'] - $line['rc_theoretical']-$line['cb_canceled'], 2, '.', ''),
                       $line['ca_real'] != 0 ? number_format($line['rc_real']+$line['withdrawals'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        $line['cr_real'] ? number_format($line['cr_real'], 2, '.', '') : 0,
                        number_format($line['cr_real'] - $line['cr_theoretical'], 2, '.', ''),
                        $line['ca_real'] != 0 ? number_format($line['cr_real'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        $line['bc_real'] ? number_format($line['bc_real'], 2, '.', '') : 0,
                        number_format($line['bc_real'] - $line['bc_theoretical'], 2, '.', ''),
                        $line['ca_real'] != 0 ? number_format($line['bc_real'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        $line['mt_theoretical'] ? number_format($line['mt_theoretical'], 2, '.', '') : 0,
                        number_format($line['mt_theoretical'] - $line['mt_theoretical'], 2, '.', ' '),
                        $line['ca_real'] != 0 ? number_format($line['mt_theoretical'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        $line['cq_real'] ? number_format($line['cq_real'], 2, '.', '') : 0,
                        number_format($line['cq_real'] - $line['cq_theoretical'], 2, '.', ''),
                        $line['ca_real'] != 0 ? number_format($line['cq_real'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        $line['nbr_cancels'] ? number_format($line['nbr_cancels'],2, '.', '') : 0,
                        $line['total_cancels'] ? number_format($line['total_cancels'],2, '.', '') : 0,
                        $line['ca_real'] != 0 ? number_format($line['total_cancels'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        $line['nbr_corrections'] ? number_format($line['nbr_corrections'],2, '.', '') : 0,
                        $line['total_corrections'] ? number_format($line['total_corrections'],2, '.', '') : 0,
                        $line['ca_real'] != 0 ? number_format($line['total_corrections'] * 100 / $line['ca_real'],2, '.', '') : 0,

                        $line['nbr_abondons'] ? number_format($line['nbr_abondons'],2, '.', '') : 0,
                        $line['total_abondons'] ? number_format($line['total_abondons'], 2, '.', '') : 0,
                        $line['ca_real'] != 0 ? number_format($line['total_abondons'] * 100 / $line['ca_real'], 2, '.', '') : 0,

                        number_format($line['ca_real'] - $line['ca_theoretical'] + $line['d_theoretical'] + $line['mt_theoretical'], 2, '.', ''),
                    ];
                },
                $filename
            );

        return $response;
    }

    public function generateCSVReportAnomalies($filter)
    {
        $trans = $this->container->get('translator');
        $trans_prefix = "cashbox_counts_anomalies.report_labels";
        $filename = 'cashbox_counts_anomalies_'.date('Y_m_d_H_i_s');
        $response = $this->container->get('toolbox.document.generator')
            ->generateXlsFile(
                'report.cashbox.service',
                'getCashboxCountsAnomaliesCSV',
                [
                    'filter' => $filter,
                ],
                $trans->trans("cashbox_counts_anomalies.title"),
                [
                    [
                        $trans->trans("cashbox_counts_anomalies.title"),
                        $trans->trans("period_label"),
                        $trans->trans("from_label"),
                        $filter['startDate']->format('Y-m-d'),
                        $trans->trans("to_label"),
                        $filter['endDate']->format('Y-m-d'),

                    ],
                    [
                        $trans->trans("cashbox_counts_anomalies.title"),
                        $trans->trans("period_label"),
                        $trans->trans("from_label"),
                        $filter['startDate']->format('Y-m-d'),
                        $trans->trans("to_label"),
                        $filter['endDate']->format('Y-m-d'),

                    ],
                    [
                        $trans->trans($trans_prefix.".responsable"),
                        $trans->trans($trans_prefix.".nombre_caisse"),
                        $trans->trans($trans_prefix.".ca_reel"),
                        $trans->trans($trans_prefix.".diff_caisse"),
                        '',
                        $trans->trans($trans_prefix.".annulations"),
                        '',
                        $trans->trans($trans_prefix.".corrections"),
                        '',
                        $trans->trans($trans_prefix.".abandons"),
                        '',
                        $trans->trans($trans_prefix.".especes"),
                        '',
                        $trans->trans($trans_prefix.".titres_restaurant"),
                        '',
                    ]
                    ,
                    [
                        '',
                        '',
                        '',

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),

                        $trans->trans($trans_prefix.".montant"),
                        $trans->trans($trans_prefix.".pourcentage"),
                    ],
                ],
                function ($line) {
                    if ($line['cashier_name'] == "Moyenne") {
                        $return = [
                            $line['cashier_name'],
                            '',
                            '',
                            $line['diff_caisse_percent'],
                            '',
                            $line['total_cancels_percent'],
                            '',
                            $line['total_corrections_percent'],
                            '',
                            $line['total_abondons_percent'],
                            '',
                            $line['rc_real_percent'],
                            '',
                            $line['cr_real_percent'],
                            '',
                        ];
                    } else {
                        $return = [
                            $line['cashier_name'],
                            $line['nbre'],

                            $line['ca_real'],

                            $line['diff_caisse'],
                            $line['diff_caisse_percent'],

                            $line['total_cancels'],
                            $line['total_cancels_percent'],

                            $line['total_corrections'],
                            $line['total_corrections_percent'],

                            $line['total_abondons'],
                            $line['total_abondons_percent'],

                            $line['rc_real'],
                            $line['rc_real_percent'],

                            $line['cr_real'],
                            $line['cr_real_percent'],
                        ];
                    }

                    return $return;
                },
                $filename
            );

        return $response;
    }

    public function getCashboxCountsAnomaliesFilters($filter, $limit = null, $offset = null)
    {
        $result = $this->em->getRepository('Financial:CashboxCount')->getCashboxCountsAnomaliesTotal(
            $filter,
            $this->sqlQueriesDir
        );
        $max_percents = $this->em->getRepository('Financial:CashboxCount')->getCashboxCountsAnomaliesMaxPercent(
            $filter,
            $this->sqlQueriesDir
        );

        $return['diffCashbox']['first'] = 0.5;
        $return['diffCashbox']['second'] = (round($max_percents[0]['max_diff_caisse_percent']) + 1) > 100 ? (round(
            $max_percents[0]['max_diff_caisse_percent'] + 0.49
        )) : 100;
        $return['diffCashbox']['max'] = round($max_percents[0]['max_diff_caisse_percent'] + 0.49);

        $return['annulations']['first'] = $result['total'][0]['total_cancels_percent'] ? round(
            ($result['total'][0]['total_cancels_percent'] + 0.2),
            2
        ) : 0;
        $return['annulations']['second'] = (round($max_percents[0]['max_cancels_percent']) + 1) > 100 ? (round(
            $max_percents[0]['max_cancels_percent'] + 0.49
        )) : 100;
        $return['annulations']['max'] = round($max_percents[0]['max_cancels_percent'] + 0.49);

        $return['corrections']['first'] = $result['total'][0]['total_corrections_percent'] ? round(
            ($result['total'][0]['total_corrections_percent'] + 1),
            2
        ) : 0;
        $return['corrections']['second'] = (round($max_percents[0]['max_corrections_percent']) + 1) > 100 ? (round(
            $max_percents[0]['max_corrections_percent'] + 0.49
        )) : 100;
        $return['corrections']['max'] = round($max_percents[0]['max_corrections_percent'] + 0.49);

        $return['abandons']['first'] = $result['total'][0]['total_abondons_percent'] ? round(
            $result['total'][0]['total_abondons_percent'],
            2
        ) : 0;
        $return['abandons']['second'] = (round($max_percents[0]['max_abondons_percent']) + 1) > 100 ? round(
            $max_percents[0]['max_abondons_percent'] + 0.49
        ) : 100;
        $return['abandons']['max'] = round($max_percents[0]['max_abondons_percent'] + 0.49);

        $return['especes']['first'] = 0;
        $return['especes']['second'] = $result['total'][0]['rc_real_percent'] > 2.5 ? round(
            $result['total'][0]['rc_real_percent'],
            2
        ) - 2.5 : 0.1;
        $return['especes']['max'] = round($max_percents[0]['max_rc_real_percent'] + 0.49);

        $return['titreRestaurant']['first'] = 0;
        $return['titreRestaurant']['second'] = $result['total'][0]['cr_real_percent'] ? (round(
            $result['total'][0]['cr_real_percent'],
            2
        ) + 2.5) : 100;
        $return['titreRestaurant']['max'] = round($max_percents[0]['max_cr_real_percent'] + 0.49);

        return $return;
    }

    public function getCashboxCountsAnomaliesMax($filter, $limit = null, $offset = null)
    {
        $max_percents = $this->em->getRepository(CashboxCount::class)->getCashboxCountsAnomaliesMaxPercent(
            $filter,
            $this->sqlQueriesDir
        );

        $return['diffCashbox'] = round($max_percents[0]['max_diff_caisse_percent'] + 0.49);

        $return['annulations'] = round($max_percents[0]['max_cancels_percent'] + 0.49);

        $return['corrections'] = round($max_percents[0]['max_corrections_percent'] + 0.49);

        $return['abandons'] = round($max_percents[0]['max_abondons_percent'] + 0.49);

        $return['especes'] = round($max_percents[0]['max_rc_real_percent'] + 0.49);

        $return['titreRestaurant'] = round($max_percents[0]['max_cr_real_percent'] + 0.49);

        return $return;
    }
}
