<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 30/07/2018
 * Time: 09:30
 */

namespace AppBundle\Supervision\Service\Talan2Easi;

use AppBundle\ToolBox\Utils\ExcelUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use PHPExcel_Cell;
use PHPExcel_Style_Fill;

class ExportBiExcelService
{

    private $em;

    private $phpExcel;

    private $restaurantType;
    private $country;

    const TYPE_CA = '3001';
    const TYPE_BON = '3002';

    const CA_RUB_ID = [
        "CA_NET_TTC" => "2",
        "CA_NET_TVA" => "3",
        "Disc_BonRepas_TTC" => "4",
        "Disc_BonPub_TTC" => "5",
        "VA_TTC" => "6",
        "VA_TVA" => "7",
    ];

    const CODE_SOLDING_CANAL
        = [
            "eatin" => "1",
            "eatout" => "2",
            "drivethru" => "3",
            "kioskin" => "4",
            "kioskout" => "4",
        ];

    const CODE_TVA_BE = [
        "0" => ["taxe_grp_id" => "3", "code" => 0],
        "0.06" => ["taxe_grp_id" => "2", "code" => 1],
        "0.12" => ["taxe_grp_id" => "4", "code" => 2],
        "0.21" => ["taxe_grp_id" => "1", "code" => 3],
        "0.03" => ["taxe_grp_id" => "6", "code" => 4],
        "0.17" => ["taxe_grp_id" => "5", "code" => 5],
    ];
    const CODE_TVA_LUX = [
        "0" => ["taxe_grp_id" => "2", "code" => 0],
        "0.03" => ["taxe_grp_id" => "1", "code" => 1],
        "0.17" => ["taxe_grp_id" => "4", "code" => 2],
    ];

    const CODE_RESTAURANT_MAPPER = [
        'B747' => 11001405,
        'B722' => 11001447,
        'B441' => 11001444,
        'B764' => 11001406,
        'B292' => 11001471,
        'B294' => 11001331,
        'B299' => 11001443,
        'B015' => 11001473,
        'B291' => 11001495,
        'B751' => 11001395,
        'B739' => 11001500,
        'Q418' => 11001302,
        'Q710' => 11001386,
        'Q723' => 11001348,
        'Q735' => 11001397,
        'Q750' => 11001382,
        'Q771' => 11001342,
        'Q772' => 11001311,
        'Q773' => 11001309,
        'Q777' => 11001323,
        'Q780' => 11001390,
        'Q292' => 11001471,
        'Q293' => 11001352,
        'Q294' => 11001331,
        'Q295' => 11001358,
        'Q296' => 11001345,
        'Q297' => 11001393,
        'Q298' => 11001375,
        'Q299' => 11001443,

    ];

    const GENERAL_ACCOUNT_LUX =
        [
            "D" => [
                5 => [
                    'compte' => '47160000',
                    'affectation' => '484900',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                6 => [
                    'compte' => '65810200',
                    'affectation' => '613383',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '405020201',
                ],
                8 => [
                    'compte' => '51296800',
                    'affectation' => '517050',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                38 => [
                    'compte' => '48860200',
                    'affectation' => '471800',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                77 => [
                    'compte' => '60100300',
                    'affectation' => '601100',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '402070001',
                ],
                78 => [
                    'compte' => '60680000',
                    'affectation' => '608121',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405010101',
                ],
                79 => [
                    'compte' => '42110000',
                    'affectation' => '471410',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                80 => [
                    'compte' => '62315000',
                    'affectation' => '603501',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405040105',
                ],
                81 => [
                    'compte' => '62510500',
                    'affectation' => '615241',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405040106',
                ],
                82 => [
                    'compte' => '64840000',
                    'affectation' => '618831',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405080103',
                ],
                83 => [
                    'compte' => '62380000',
                    'affectation' => '612201',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405070101',
                ],
                84 => [
                    'compte' => '60640000',
                    'affectation' => '603501',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405010101',
                ],
                85 => [
                    'compte' => '64840000',
                    'affectation' => '618831',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405080103',
                ],
                86 => [
                    'compte' => '62510000',
                    'affectation' => '616807',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '405040102',
                ],
                87 => [
                    'compte' => '62610000',
                    'affectation' => '615381',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '405010102',
                ],
                88 => [
                    'compte' => '62510500',
                    'affectation' => '616803',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405040104',
                ],
                89 => [
                    'compte' => '64840000',
                    'affectation' => '618831',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405010105',
                ],
                90 => [
                    'compte' => '60640000',
                    'affectation' => '603501',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405010101',
                ],
                91 => [
                    'compte' => '64840000',
                    'affectation' => '618831',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405080103',
                ],
                92 => [
                    'compte' => '62315000',
                    'affectation' => '615185',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405040105',
                ],
                93 => [
                    'compte' => '65160200',
                    'affectation' => '688601',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '404050101',
                ],
                94 => [
                    'compte' => '62510500',
                    'affectation' => '616803',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405040107',
                ],
                95 => [
                    'compte' => '62481000',
                    'affectation' => '618831',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '405040107',
                ],
                102 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                103 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                104 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                105 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                106 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                107 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                108 => [
                    'compte' => '41140000',
                    'affectation' => '421886',
                    'affectation_tva' => '',
                    'contre_partie' => '517174',
                    'dossier' => '',
                ],
                120 => [
                    'compte' => '41130000',
                    'affectation' => '421885',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                123 => [
                    'compte' => '51265000',
                    'affectation' => '517050',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                124 => [
                    'compte' => '65837000',
                    'affectation' => '613382',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '405020201',
                ],
                125 => [
                    'compte' => '65810200',
                    'affectation' => '613383',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '405020201',
                ],
                126 => [
                    'compte' => '75810000',
                    'affectation' => '748000',
                    'affectation_tva' => '421612',
                    'contre_partie' => '516000',
                    'dossier' => '401020101',
                ],
                130 => [
                    'compte' => '41140000',
                    'affectation' => '421886',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                132 => [
                    'compte' => '41135000',
                    'affectation' => '421884',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                300 => [
                    'compte' => '41130000',
                    'affectation' => '421885',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                400 => [
                    'compte' => '41140000',
                    'affectation' => '421886',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                500 => [
                    'compte' => '41135000',
                    'affectation' => '421884',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                600 => [
                    'compte' => '51297000',
                    'affectation' => '517020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                601 => [
                    'compte' => '51297000',
                    'affectation' => '517020',//'518020',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                115 => [ // Foostix
                    'compte' => '',
                    'affectation' => '421883',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ]
                ,
                    116 => [
                    'compte' => '42188200',
                    'affectation' => '421882',
                    'affectation_tva' => '0',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                702 => [
                    'compte' => '51703100',
                    'affectation' => '517031',
                    'affectation_tva' => '0',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
            ],

            "R" =>
                [
                    1 => [
                        'compte' => '51296800',
                        'affectation' => '517050',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '',
                    ],
                    3 => [
                        'compte' => '75810000',
                        'affectation' => '748000',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '401020101',
                    ],
                    5 => [
                        'compte' => '47160000',
                        'affectation' => '484900',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '',
                    ],
                    6 => [
                        'compte' => '65810200',
                        'affectation' => '613383',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '405020201',
                    ],
                    7 => [
                        'compte' => '75810000',
                        'affectation' => '748000',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '401020101',
                    ],
                    9 => [
                        'compte' => '47160000',
                        'affectation' => '484900',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '',
                    ],
                    101 => [
                        'compte' => '60100300',
                        'affectation' => '601100',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '402070001',
                    ],
                    102 => [
                        'compte' => '60680000',
                        'affectation' => '608121',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405010101',
                    ],
                    103 => [
                        'compte' => '42110000',
                        'affectation' => '471410',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '',
                    ],
                    104 => [
                        'compte' => '62315000',
                        'affectation' => '603501',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405040105',
                    ],
                    106 => [
                        'compte' => '62510500',
                        'affectation' => '615241',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405040106',
                    ],
                    105 => [
                        'compte' => '64840000',
                        'affectation' => '618831',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405080103',
                    ],
                    107 => [
                        'compte' => '62380000',
                        'affectation' => '612201',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '405070101',
                    ],
                    108 => [
                        'compte' => '60640000',
                        'affectation' => '603501',
                        'affectation_tva' => '421612',
                        'contre_partie' => '517174',
                        'dossier' => '405010101',
                    ],
                    109 => [
                        'compte' => '64840000',
                        'affectation' => '618831',
                        'affectation_tva' => '421612',
                        'contre_partie' => '517173',
                        'dossier' => '405080103',
                    ],
                    110 => [
                        'compte' => '62510000',
                        'affectation' => '616807',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '405040102',
                    ],
                    111 => [
                        'compte' => '62610000',
                        'affectation' => '615381',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '405010102',
                    ],
                    112 => [
                        'compte' => '62510500',
                        'affectation' => '616803',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405040106',
                    ],
                    113 => [
                        'compte' => '64840000',
                        'affectation' => '618831',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405080103',
                    ],
                    114 => [
                        'compte' => '60640000',
                        'affectation' => '603501',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405010101',
                    ],
                    115 => [
                        'compte' => '64840000',
                        'affectation' => '618831',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405080103',
                    ],
//                    116 => [
//                        'compte' => '62315000',
//                        'affectation' => '615185',
//                        'affectation_tva' => '421612',
//                        'contre_partie' => '516000',
//                        'dossier' => '405040105',
//                    ],
                    118 => [
                        'compte' => '65160200',
                        'affectation' => '688601',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '404050101',
                    ],
                    117 => [
                        'compte' => '62510500',
                        'affectation' => '616803',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405040106',
                    ],
                    119 => [
                        'compte' => '62481000',
                        'affectation' => '618831',
                        'affectation_tva' => '421612',
                        'contre_partie' => '516000',
                        'dossier' => '405040107',
                    ],
                    120 => [
                        'compte' => '51296800',
                        'affectation' => '517050',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '',
                    ],
                    122 => [
                        'compte' => '65837000',
                        'affectation' => '613382',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '405020201',
                    ],
                    121 => [
                        'compte' => '65810200',
                        'affectation' => '613383',
                        'affectation_tva' => '',
                        'contre_partie' => '516000',
                        'dossier' => '405020201',
                    ],
                    116 => [
                    'compte' => '42188200',
                    'affectation' => '421882',
                    'affectation_tva' => '0',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                702 => [
                    'compte' => '51703100',
                    'affectation' => '517031',
                    'affectation_tva' => '0',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ],
                ],
        ];

    const GENERAL_ACCOUNT_BE = [
        "D" =>
            [
                5 => [
                    'compte' => '47160000',
                    'affectation' => '498000',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                6 => [
                    'compte' => '65810200',
                    'affectation' => '618121',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                8 => [
                    'compte' => '58005000',
                    'affectation' => '580050',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                38 => [
                    'compte' => '48860200',
                    'affectation' => '489000',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                77 => [
                    'compte' => '60100300',
                    'affectation' => '604100',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '402070001',
                ],
                78 => [
                    'compte' => '60680000',
                    'affectation' => '611001',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405010101',
                ],
                79 => [
                    'compte' => '42110000',
                    'affectation' => '455000',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                80 => [
                    'compte' => '62315000',
                    'affectation' => '616421',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040105',
                ],
                81 => [
                    'compte' => '62510500',
                    'affectation' => '616510',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040106',
                ],
                82 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                83 => [
                    'compte' => '62380000',
                    'affectation' => '610901',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405070101',
                ],
                84 => [
                    'compte' => '60640000',
                    'affectation' => '611101',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405010101',
                ],
                85 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                86 => [
                    'compte' => '62510000',
                    'affectation' => '614101',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040102',
                ],
                87 => [
                    'compte' => '62610000',
                    'affectation' => '616151',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '405010102',
                ],
                88 => [
                    'compte' => '62510500',
                    'affectation' => '614310',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040106',
                ],
                89 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                90 => [
                    'compte' => '60640000',
                    'affectation' => '611101',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405010101',
                ],
                91 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                92 => [
                    'compte' => '62315000',
                    'affectation' => '616421',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040105',
                ],
                93 => [
                    'compte' => '63585000',
                    'affectation' => '640601',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                94 => [
                    'compte' => '62510500',
                    'affectation' => '614310',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040106',
                ],
                95 => [
                    'compte' => '62481000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040107',
                ],
                102 => [
                    'compte' => '58001000',
                    'affectation' => '581011',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                103 => [
                    'compte' => '51290510',
                    'affectation' => '581051',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                104 => [
                    'compte' => '51290510',
                    'affectation' => '581051',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                105 => [
                    'compte' => '51290510',
                    'affectation' => '581051',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                106 => [
                    'compte' => '51290510',
                    'affectation' => '581051',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                107 => [
                    'compte' => '51290510',
                    'affectation' => '581051',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                108 => [
                    'compte' => '41146100',
                    'affectation' => '581041',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                109 => [
                    'compte' => '41137100',
                    'affectation' => '581021',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                110 => [
                    'compte' => '41158100',
                    'affectation' => '581031',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                123 => [
                    'compte' => '58005000',
                    'affectation' => '580050',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                124 => [
                    'compte' => '65837000',
                    'affectation' => '618111',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                125 => [
                    'compte' => '65810200',
                    'affectation' => '618121',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                126 => [
                    'compte' => '75810000',
                    'affectation' => '748001',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '401020101',
                ],
                201 => [
                    'compte' => '65810200',
                    'affectation' => '618121',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                600 => [
                    'compte' => '51290510',
                    'affectation' => '581051',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                151 => [ // Takeaway
                    'compte' => '',
                    'affectation' => '416130',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                150 => [ // Delivro
                    'compte' => '',
                    'affectation' => '416140',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                113 => [ // Uber Eats
                    'compte' => '',
                    'affectation' => '416150',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                115 => [ // Foostix
                    'compte' => '',
                    'affectation' => '421883',
                    'affectation_tva' => '',
                    'contre_partie' => '516000',
                    'dossier' => '',
                ]
                ,
                450 => [
                    'compte' => '41611000',
                    'affectation' => '416110',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                451 => [
                    'compte' => '41610000',
                    'affectation' => '416100',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                 116 => [
                    'compte' => '41616000',
                    'affectation' => '416160',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                 702 => [
                    'compte' => '58106100',
                    'affectation' => '581061',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
            ],
        "R" =>
            [
                1 => [
                    'compte' => '58005000',
                    'affectation' => '580050',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                3 => [
                    'compte' => '75810000',
                    'affectation' => '748001',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '401020101',
                ],
                5 => [
                    'compte' => '47160000',
                    'affectation' => '498000',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                6 => [
                    'compte' => '65810200',
                    'affectation' => '618121',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                7 => [
                    'compte' => '75810000',
                    'affectation' => '748001',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '401020101',
                ],
                9 => [
                    'compte' => '47160000',
                    'affectation' => '498000',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                101 => [
                    'compte' => '60100300',
                    'affectation' => '604100',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '402070001',
                ],
                103 => [
                    'compte' => '42110000',
                    'affectation' => '455000',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                104 => [
                    'compte' => '62315000',
                    'affectation' => '616421',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040105',
                ],
                106 => [
                    'compte' => '62510500',
                    'affectation' => '616510',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040106',
                ],
                105 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                107 => [
                    'compte' => '62380000',
                    'affectation' => '610901',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405070101',
                ],
                108 => [
                    'compte' => '60640000',
                    'affectation' => '611101',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405010101',
                ],
                109 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                110 => [
                    'compte' => '62510000',
                    'affectation' => '614101',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040102',
                ],
                111 => [
                    'compte' => '62610000',
                    'affectation' => '616151',
                    'affectation_tva' => '',
                    'contre_partie' => '570000',
                    'dossier' => '405010102',
                ],
                112 => [
                    'compte' => '62510500',
                    'affectation' => '614310',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040106',
                ],
                113 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
                114 => [
                    'compte' => '60640000',
                    'affectation' => '611101',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405010101',
                ],
                115 => [
                    'compte' => '64840000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405080103',
                ],
//                116 => [
//                    'compte' => '62315000',
//                    'affectation' => '616421',
//                    'affectation_tva' => '411000',
//                    'contre_partie' => '570000',
//                    'dossier' => '405040105',
//                ],
                118 => [
                    'compte' => '63585000',
                    'affectation' => '640601',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                117 => [
                    'compte' => '62510500',
                    'affectation' => '614310',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040106',
                ],
                119 => [
                    'compte' => '62481000',
                    'affectation' => '623121',
                    'affectation_tva' => '411000',
                    'contre_partie' => '570000',
                    'dossier' => '405040107',
                ],
                120 => [
                    'compte' => '58005000',
                    'affectation' => '580050',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                205 => [
                    'compte' => '65837000',
                    'affectation' => '618111',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                121 => [
                    'compte' => '65810200',
                    'affectation' => '618121',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                201 => [
                    'compte' => '65810200',
                    'affectation' => '618121',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '405020201',
                ],
                450 => [
                    'compte' => '41611000',
                    'affectation' => '416110',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                451 => [
                    'compte' => '41610000',
                    'affectation' => '416100',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                 116 => [
                    'compte' => '41616000',
                    'affectation' => '416160',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                 702 => [
                    'compte' => '58106100',
                    'affectation' => '581061',
                    'affectation_tva' => '0',
                    'contre_partie' => '570000',
                    'dossier' => '',
                ],
                
                
                
            ],
    ];

    // key =rub_id+canal_vente+taxe_code
    const CA_BE_MAPPER = array(
        213 => ['compte' => '618121', 'dossier' => '405020201', 'intitule' => 'CA Salle 0%'],
        211 => ['compte' => 700400, 'dossier' => 401010101, 'intitule' => 'CA Salle 21%'],
        //70600000
        212 => ['compte' => 700100, 'dossier' => 401010101, 'intitule' => 'CA Salle 6%'],
        //70100000
        214 => ['compte' => 700200, 'dossier' => 401010101, 'intitule' => 'CA Salle 12%'],
        //70600200
        313 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Salle 0%'],
        311 => ['compte' => 700410, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 21%'],
        //70612100
        312 => ['compte' => 700110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 6%'],
        //70100100
        314 => ['compte' => 700210, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 12%'],
        //70611200
        223 => ['compte' => '', 'dossier' => '', 'intitule' => 'CA Emporter 0%'],
        323 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Emporter 0%'],
        222 => ['compte' => 700100, 'dossier' => 401010101, 'intitule' => 'CA Emporter 6%'],
        322 => ['compte' => 700110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Emporter 6%'],
        //70100100
        221 => ['compte' => 700300, 'dossier' => 401010101, 'intitule' => 'CA Emporter 21%'],
        //70110000
        321 => ['compte' => 700310, 'dossier' => 401010101, 'intitule' => 'TVA/CA Emporter 21%'],
        //70112100
        233 => ['compte' => '', 'dossier' => '', 'intitule' => 'CA Drive 0%'],
        234 => ['compte' => 700200, 'dossier' => 401010101, 'intitule' => 'CA Salle 12%'],
        //not defined , use 700200 to resolve this
        333 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Drive 0%'],
        334 => ['compte' => 700210, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 12%'],
        //not defined , use 700210 to resolve this
        232 => ['compte' => 700100, 'dossier' => 401010101, 'intitule' => 'CA Drive 6%'],
        332 => ['compte' => 700110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 6%'],
        //70100100
        231 => ['compte' => 700300, 'dossier' => 401010101, 'intitule' => 'CA Drive 21%'],
        //70110000
        331 => ['compte' => 700310, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 21%'],
        //70112100
        242 => ['compte' => 700100, 'dossier' => 401010101, 'intitule' => 'CA Emporter 6%'],
        //['compte' => 700100, 'dossier' => 401010101, 'intitule' => 'CA Drive 6%'],
        244 => ['compte' => 700200, 'dossier' => 401010101, 'intitule' => 'CA Salle 12%'],
        //not defined , use 700200 to resolve this
        342 => ['compte' => 700110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Emporter 6%'],
        //['compte' => 700110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 6%'], //70100100
        344 => ['compte' => 700210, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 12%'],
        // not defined
        241 => ['compte' => 700400, 'dossier' => 401010101, 'intitule' => 'CA Salle 21%'],
        //['compte' => 700300, 'dossier' => 401010101, 'intitule' => 'CA Drive 21%'], //70110000
        341 => ['compte' => 700410, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 21%'],
        //['compte' => 700310, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 21%'],//70112100
        613 => ['compte' => '', 'dossier' => '', 'intitule' => 'VA 0%'],
        623 => ['compte' => '', 'dossier' => '', 'intitule' => 'VA 0%'],
        633 => ['compte' => '', 'dossier' => '', 'intitule' => 'VA 0%'],
        643 => ['compte' => '', 'dossier' => '', 'intitule' => 'VA 0%'],
        713 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/VA 0%'],
        723 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/VA 0%'],
        733 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/VA 0%'],
        743 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/VA 0%'],
        612 => ['compte' => 700500, 'dossier' => 406020201, 'intitule' => 'VA 6%'],
        //70705500
        622 => ['compte' => 700500, 'dossier' => 406020201, 'intitule' => 'VA 6%'],
        //70705500
        632 => ['compte' => 700500, 'dossier' => 406020201, 'intitule' => 'VA 6%'],
        //70705500
        642 => ['compte' => 700500, 'dossier' => 406020201, 'intitule' => 'VA 6%'],
        //70705500
        712 => ['compte' => 700510, 'dossier' => 406020201, 'intitule' => 'TVA/VA 6%'],
        //70725500
        722 => ['compte' => 700510, 'dossier' => 406020201, 'intitule' => 'TVA/VA 6%'],
        //70725500
        732 => ['compte' => 700510, 'dossier' => 406020201, 'intitule' => 'TVA/VA 6%'],
        //70725500
        742 => ['compte' => 700510, 'dossier' => 406020201, 'intitule' => 'TVA/VA 6%'],
        //70725500
        611 => ['compte' => 700600, 'dossier' => 406020201, 'intitule' => 'VA 21%'],
        //70719600
        621 => ['compte' => 700600, 'dossier' => 406020201, 'intitule' => 'VA 21%'],
        //70719600
        631 => ['compte' => 700600, 'dossier' => 406020201, 'intitule' => 'VA 21%'],
        //70719600
        641 => ['compte' => 700600, 'dossier' => 406020201, 'intitule' => 'VA 21%'],
        //70719600
        711 => ['compte' => 700620, 'dossier' => 406020201, 'intitule' => 'TVA/VA 21%'],
        //70729600
        721 => ['compte' => 700620, 'dossier' => 406020201, 'intitule' => 'TVA/VA 21%'],
        //70729600
        731 => ['compte' => 700620, 'dossier' => 406020201, 'intitule' => 'TVA/VA 21%'],
        //70729600
        741 => ['compte' => 700620, 'dossier' => 406020201, 'intitule' => 'TVA/VA 21%'],
        //70729600
        614 => ['compte' => 498000, 'dossier' => '', 'intitule' => 'Centralisation CA'],
        //47160000
        511 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        512 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        514 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        521 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        522 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        531 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        532 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        541 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        542 => ['compte' => 616701, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        411 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        412 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        414 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        421 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        422 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        431 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        432 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        441 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        442 => ['compte' => 626000, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
    );


    const CA_LUX_MAPPER = array(
        212 => ['compte' => '618121', 'dossier' => '405020201', 'intitule' => 'CA Salle 0%'],
        211 => ['compte' => 702100, 'dossier' => 401010101, 'intitule' => 'CA Salle 3%'],
        //70600300
        214 => ['compte' => 702200, 'dossier' => 401010101, 'intitule' => 'CA Salle 17%'],
        //70600700
        311 => ['compte' => 702110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 3%'],
        //70601600
        312 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Salle  0%'],
        314 => ['compte' => 702210, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 17%'],
        //70600800
        222 => ['compte' => 702140, 'dossier' => 401010101, 'intitule' => 'CA Emporter 0%'],
        //70100000
        221 => ['compte' => 702140, 'dossier' => 401010101, 'intitule' => 'CA Emporter 3%'],
        //70100000
        224 => ['compte' => 702300, 'dossier' => 401010101, 'intitule' => 'CA Emporter 17%'],
        //70100200
        322 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Emporter 0%'],
        321 => ['compte' => 702150, 'dossier' => 401010101, 'intitule' => 'TVA/CA Emporter 3%'],
        //70100100
        324 => ['compte' => 702310, 'dossier' => 401010101, 'intitule' => 'TVA/CA Emporter 17%'],
        //70100300
        232 => ['compte' => 702140, 'dossier' => 401010101, 'intitule' => 'CA Drive 0%'],
        //70100000
        231 => ['compte' => 702140, 'dossier' => 401010101, 'intitule' => 'CA Drive 3%'],
        //70100000
        234 => ['compte' => 702300, 'dossier' => 401010101, 'intitule' => 'CA Drive 17%'],
        //70100200
        332 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Drive 0%'],
        331 => ['compte' => 702150, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 3%'],
        //70100100
        334 => ['compte' => 702310, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 17%'],
        //70100300
        242 => ['compte' => '', 'dossier' => '', 'intitule' => 'CA Drive 0%'],
        //['compte' => '', 'dossier' => '', 'intitule' => 'CA Drive 0%'],
        241 => ['compte' => 702100, 'dossier' => 401010101, 'intitule' => 'CA Salle 3%'],
        //['compte' => 702140, 'dossier' => 401010101, 'intitule' => 'CA Drive 3%'],//70100000
        244 => ['compte' => 702200, 'dossier' => 401010101, 'intitule' => 'CA Salle 17%'],
        //['compte' => 702300, 'dossier' => 401010101, 'intitule' => 'CA Drive 17%'], //70100200
        342 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/CA Drive 0%'],
        341 => ['compte' => 702110, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 3%'],
        //['compte' => 702150, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 3%'],//70100100
        344 => ['compte' => 702210, 'dossier' => 401010101, 'intitule' => 'TVA/CA Salle 17%'],
        //['compte' => 702310, 'dossier' => 401010101, 'intitule' => 'TVA/CA Drive 17%'],//70100300
        612 => ['compte' => '', 'dossier' => '', 'intitule' => 'VA 0%'],
        611 => ['compte' => 70705500, 'dossier' => '', 'intitule' => 'VA 3%'],
        621 => ['compte' => 70705500, 'dossier' => '', 'intitule' => 'VA 3%'],
        631 => ['compte' => 70705500, 'dossier' => '', 'intitule' => 'VA 3%'],
        641 => ['compte' => 70705500, 'dossier' => '', 'intitule' => 'VA 3%'],
        712 => ['compte' => '', 'dossier' => '', 'intitule' => 'TVA/VA 0%'],
        711 => ['compte' => 70725500, 'dossier' => '', 'intitule' => 'TVA/VA 3%'],
        721 => ['compte' => 70725500, 'dossier' => '', 'intitule' => 'TVA/VA 3%'],
        731 => ['compte' => 70725500, 'dossier' => '', 'intitule' => 'TVA/VA 3%'],
        741 => ['compte' => 70725500, 'dossier' => '', 'intitule' => 'TVA/VA 3%'],
        714 => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 17%'],
        //44570500
        724 => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 17%'],
        //44570500
        734 => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 17%'],
        //44570500
        744 => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 17%'],
        //44570500
        614 => ['compte' => 484900, 'dossier' => '', 'intitule' => 'Centralisation CA'],
        //47160000
        624 => ['compte' => 484900, 'dossier' => '', 'intitule' => 'Centralisation CA'],
        //47160000
        634 => ['compte' => 484900, 'dossier' => '', 'intitule' => 'Centralisation CA'],
        //47160000
        644 => ['compte' => 484900, 'dossier' => '', 'intitule' => 'Centralisation CA'],
        //47160000
        511 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        514 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        521 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        524 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        531 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        534 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        541 => ['compte' => 618891, 'dossier' => 402070001, 'intitule' => 'Ext Bon pub PR'],
        //62317000
        411 => ['compte' => 618814, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        421 => ['compte' => 618814, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        431 => ['compte' => 618814, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
        441 => ['compte' => 618814, 'dossier' => 403030102, 'intitule' => 'Ext Bon repas PR'],
        //64141000
    );

    const LUX_DUPLICATE_MAPPER = array(
        '0' => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 0%'],//44570500
        '0.21' => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 21%'],//44570500
        '0.12' => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 12%'],//44570500
        '0.06' => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 6%'],//44570500
        '0.03' => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 3%'],//44570500
        '0.17' => ['compte' => 461413, 'dossier' => '', 'intitule' => 'TVA 17%'],//44570500
        'C_CA' => ['compte' => 484900, 'dossier' => '', 'intitule' => 'Centralisation CA'],//47160000
        'BON_P' => ['compte' => 618893, 'dossier' => 402070001, 'intitule' => 'Bon pub.'],//62317100
        'BON_R' => ['compte' => 618815, 'dossier' => 402120001, 'intitule' => 'Bon repas'],//60100000
    );

    const BE_DUPLICATE_MAPPER = array(
        '0' => ['compte' => 451000, 'dossier' => '', 'intitule' => 'TVA 0%'],//44570500
        '0.21' => ['compte' => 451000, 'dossier' => '', 'intitule' => 'TVA 21%'],//44570500
        '0.12' => ['compte' => 451000, 'dossier' => '', 'intitule' => 'TVA 12%'],//44570500
        '0.06' => ['compte' => 451000, 'dossier' => '', 'intitule' => 'TVA 6%'],//44570500
        '0.03' => ['compte' => 451000, 'dossier' => '', 'intitule' => 'TVA 3%'],//44570500
        '0.17' => ['compte' => 451000, 'dossier' => '', 'intitule' => 'TVA 17%'],//44570500
        'C_CA' => ['compte' => 498000, 'dossier' => '', 'intitule' => 'Centralisation CA'],
        'BON_P' => ['compte' => 616711, 'dossier' => 402070001, 'intitule' => 'Bon pub.'],//62317100
        'BON_R' => ['compte' => 604400, 'dossier' => 402120001, 'intitule' => 'Bon repas'],//60100000
    );

    public function __construct(\Doctrine\ORM\EntityManager $em, \Liuggio\ExcelBundle\Factory $factory, $restaurantType)
    {
        $this->em = $em;
        $this->phpExcel = $factory;
        $this->restaurantType = $restaurantType;
    }


    public function generateExcel($filename, $country, $docType, $results, $path, $fileExist)
    {

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $this->country = $country;

        if (!$fileExist) {
            $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        } else {
            $phpExcelObject = $this->phpExcel->createPHPExcelObject($path);
        }


        $phpExcelObject->setActiveSheetIndex(0);

        $sheet = $phpExcelObject->getActiveSheet();

        $sheet->setTitle($filename);

        $sheet->getColumnDimension('Y')->setAutoSize(true);
        $sheet->getColumnDimension('AP')->setAutoSize(true);
        $sheet->getColumnDimension('AF')->setAutoSize(true);
        $sheet->getColumnDimension('AG')->setAutoSize(true);
        $sheet->getColumnDimension('AH')->setAutoSize(true);
        $sheet->getColumnDimension('AI')->setAutoSize(true);
        $sheet->getColumnDimension('AJ')->setAutoSize(true);
        $sheet->getColumnDimension('AK')->setAutoSize(true);
        $sheet->getColumnDimension('AN')->setAutoSize(true);
        $sheet->getColumnDimension('AT')->setAutoSize(true);
        $sheet->getColumnDimension('AV')->setAutoSize(true);


        $lineIndex = ($fileExist) ? ($sheet->getHighestRow() + 1) : 2;

        switch (strtoupper($docType)) {
            case "BON":
                $docType = self::TYPE_BON;
                $results = $this->serializeBonResults($results);
                break;
            case "CA":
                $docType = self::TYPE_CA;
                $results = $this->serializeCAResults($results);
                break;
            default:
                $docType = self::TYPE_CA;
        }

        $ca_mapping_key = '';
        foreach ($results as $result) {

            if (0 == ($lineIndex % 2)) {
                $color = 'ffbb99';
            } else {
                $color = 'ffffff';
            }

            $phpExcelObject->getActiveSheet()->getStyle($lineIndex.":".$lineIndex)->getFill()->applyFromArray(
                array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array(
                        'rgb' => $color,
                    ),
                )
            );

            $vat = null;
            $vatCode = "";
            $vatType = "";
            $tax_grp_id = null;
            if (array_key_exists("taxe", $result)) {
                $vat = $result["taxe"];
            }
            $environment='';
            if ($country == "bel") {
                $environment = "310001";
                if (!is_null($vat)) {
                    $vatCode = array_key_exists($vat, self::CODE_TVA_BE) ? self::CODE_TVA_BE[$vat]['code'] : '';
                    $tax_grp_id = array_key_exists(
                        $vat,
                        self::CODE_TVA_BE
                    ) ? self::CODE_TVA_BE[$vat]['taxe_grp_id'] : '0';
                    $vatType = "B1";
                }
            } elseif ($country == "lux") {
                $environment = "311001";
                if (!is_null($vat)) {
                    $vatCode = array_key_exists($vat, self::CODE_TVA_LUX) ? self::CODE_TVA_LUX[$vat]['code'] : '';
                    $tax_grp_id = array_key_exists(
                        $vat,
                        self::CODE_TVA_LUX
                    ) ? self::CODE_TVA_LUX[$vat]['taxe_grp_id'] : '0';
                    $vatType = "L10";
                }
            }

            // dlc ,crc , debit and credit
            $debit = 0;
            $credit = 0;
            $generalAccount = "";
            $analyticaldim1 = '350000001';
            $analyticaldim2 = '';
            $analyticaldim3 = '';
            $codeBon = "";
            switch (strtolower($this->restaurantType)) {
                case "bk":
                    $analyticaldim5 = 1;
                    break;
                case "quick":
                    $analyticaldim5 = 2;
                    break;
                default:
                    $analyticaldim5 = 2;
            }

            if ($docType == self::TYPE_BON) {
                $vatType = '';
                $restaurantCode = $result['RestCode'];// $analyticaldim3
                $codeBon = $result['codeFonction'];
                $date = \DateTime::createFromFormat('d/m/Y', $result['DateBon']);
                if (strpos(strtolower($result['Groupe']), 'versement') !== false) {
                    $label = 'Versement '.$result['Libelle'];
                } else {
                    $label = $result['Libelle'] == "" ? $result['Groupe'] : $result['Libelle'];
                }

                if (strtoupper($result['Type']) == 'D') {

                    if ($result['line_type'] == 'contre_partie') {
                        $debit = Utilities::digitMask(24, 6, 0);
                        $credit = Utilities::digitMask(24, 6, abs($result['Montant']));
                    } else {
                        $debit = Utilities::digitMask(24, 6, abs($result['Montant']));
                        $credit = Utilities::digitMask(24, 6, 0);
                    }
                    if ($country == "bel") {
                        $generalAccount = $result['compte'];
                        $analyticaldim2 = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['D']
                        ) ? self::GENERAL_ACCOUNT_BE['D'][$result['codeFonction']]['dossier'] : "";
                        if ($result['line_type'] == 'affectation_tva' || ($result['line_type'] == 'affectation' && substr(
                                    $generalAccount,
                                    0,
                                    1
                                ) == '6' && $generalAccount != '618121' && $generalAccount != '618111')) {
                            $vatType = 'B1';
                            $vatCode = 1;
                        }

                    } else {
                        $generalAccount = $result['compte'];
                        $analyticaldim2 = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['D']
                        ) ? self::GENERAL_ACCOUNT_LUX['D'][$result['codeFonction']]['dossier'] : "";
                        if ($result['line_type'] == 'affectation_tva' || ($result['line_type'] == 'affectation' && substr(
                                    $generalAccount,
                                    0,
                                    1
                                ) == '6' && $generalAccount != '618121' && $generalAccount != '618111')) {
                            $vatType = 'L10';
                            $vatCode = 1;
                        }
                    }

                } elseif (strtoupper($result['Type']) == 'R') {

                    if ($result['line_type'] == 'contre_partie') {
                        $credit = Utilities::digitMask(24, 6, 0);
                        $debit = Utilities::digitMask(24, 6, abs($result['Montant']));
                    } else {
                        $credit = Utilities::digitMask(24, 6, abs($result['Montant']));
                        $debit = Utilities::digitMask(24, 6, 0);
                    }
                    if ($country == "bel") {
                        $generalAccount = $result['compte'];
                        $analyticaldim2 = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['R']
                        ) ? self::GENERAL_ACCOUNT_BE['R'][$result['codeFonction']]['dossier'] : "";
                        if ($result['line_type'] == 'affectation_tva' || ($result['line_type'] == 'affectation' && substr(
                                    $generalAccount,
                                    0,
                                    1
                                ) == '6' && $generalAccount != '618121' && $generalAccount != '618111')) {
                            $vatType = 'B1';
                            $vatCode = 1;
                        }
                    } else {
                        $generalAccount = $result['compte'];
                        $analyticaldim2 = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['R']
                        ) ? self::GENERAL_ACCOUNT_LUX['R'][$result['codeFonction']]['dossier'] : "";
                        if ($result['line_type'] == 'affectation_tva' || ($result['line_type'] == 'affectation' && substr(
                                    $generalAccount,
                                    0,
                                    1
                                ) == '6' && $generalAccount != '618121' && $generalAccount != '618111')) {
                            $vatType = 'L10';
                            $vatCode = 1;
                        }
                    }
                }

                if ($country == "bel") {
                    if ($generalAccount == 416130 || $generalAccount == 498000 || $generalAccount == 570000 || $generalAccount == 580050 || $generalAccount == 581011 || $generalAccount == 581021 || $generalAccount == 581031 || $generalAccount == 581041 || $generalAccount==581051 || $generalAccount ==416110 || $generalAccount ==416100 || $generalAccount==416160 || $generalAccount==581061) {
                        $vat = '';
                        $vatCode = '';
                        $vatType = '';

                    }
                } else {
                    if ($generalAccount == 421884 || $generalAccount == 421885 || $generalAccount == 421886 || $generalAccount == 471800 || $generalAccount == 484900 || $generalAccount == 516000 || $generalAccount == 517020 || $generalAccount == 517050 || $generalAccount == 421882 || $generalAccount == 517031 || $generalAccount == 517174 || $generalAccount == 517173) {
                        $vat = '';
                        $vatCode = '';
                        $vatType = '';

                    }
                }

            } else {

                $restaurantCode = $result['Restaurant'];
                $date = \DateTime::createFromFormat('Y-m-d', $result['CommercialDate']);
                $ca_mapping_key = $result['rub_id'].$result['canal_vente'].$tax_grp_id;

                if ($country == "bel") {
                    $ca_row = array_key_exists(
                        $ca_mapping_key,
                        self::CA_BE_MAPPER
                    ) ? self::CA_BE_MAPPER[$ca_mapping_key] : null;
                } else {
                    $ca_row = array_key_exists(
                        $ca_mapping_key,
                        self::CA_LUX_MAPPER
                    ) ? self::CA_LUX_MAPPER[$ca_mapping_key] : null;
                }

                if (is_null($ca_row)) {
                    if ($country == "bel") {
                        $generalAccount = 618121;//'not defined';
                        $label = 'not defined';
                        $analyticaldim2 = '405020201';
                    } else {
                        $generalAccount = 618831;//'not defined';
                        $label = 'not defined';
                        $analyticaldim2 = '405020201';
                    }
                } else {
                    $generalAccount = $ca_row['compte'];
                    $label = $ca_row['intitule'];
                    $analyticaldim2 = $ca_row['dossier'];
                }

                switch ($result['rub_id']) {
                    case "2":
                        if (!$result['duplicate']) {
                            $credit = Utilities::digitMask(24, 6, $result['CA_NET_TTC']);
                            $debit = Utilities::digitMask(24, 6, 0);
                        } else {
                            $debit = Utilities::digitMask(24, 6, $result['CA_NET_TTC']);
                            $credit = Utilities::digitMask(24, 6, 0);
                            if ($country == "bel") {
                                $generalAccount = self::BE_DUPLICATE_MAPPER['C_CA']['compte'];
                                $label = self::BE_DUPLICATE_MAPPER['C_CA']['intitule'];
                                $analyticaldim2 = self::BE_DUPLICATE_MAPPER['C_CA']['dossier'];
                            } else {
                                $generalAccount = self::LUX_DUPLICATE_MAPPER['C_CA']['compte'];
                                $label = self::LUX_DUPLICATE_MAPPER['C_CA']['intitule'];
                                $analyticaldim2 = self::LUX_DUPLICATE_MAPPER['C_CA']['dossier'];
                            }
                            $vatType = "";
                            $vatCode = "";
                        }
                        break;
                    case "3":
                        if (!$result['duplicate']) {
                            $debit = Utilities::digitMask(24, 6, $result['CA_NET_TVA']);
                            $credit = Utilities::digitMask(24, 6, 0);
                        } else {
                            $credit = Utilities::digitMask(24, 6, $result['CA_NET_TVA']);
                            $debit = Utilities::digitMask(24, 6, 0);
                            if ($country == "bel") {
                                $generalAccount = self::BE_DUPLICATE_MAPPER[$result["taxe"]]['compte'];
                                $label = self::BE_DUPLICATE_MAPPER[$result["taxe"]]['intitule'];
                                $analyticaldim2 = self::BE_DUPLICATE_MAPPER[$result["taxe"]]['dossier'];
                            } else {
                                $generalAccount = self::LUX_DUPLICATE_MAPPER[$result["taxe"]]['compte'];
                                $label = self::LUX_DUPLICATE_MAPPER[$result["taxe"]]['intitule'];
                                $analyticaldim2 = self::LUX_DUPLICATE_MAPPER[$result["taxe"]]['dossier'];
                            }

                        }
                        break;
                    case "4":
                        $result['Disc_BRep_TTC'] = $result['Disc_BRep_TTC'] * 0.2093;
                        if (!$result['duplicate']) {
                            $debit = Utilities::digitMask(24, 6, $result['Disc_BRep_TTC']);
                            $credit = Utilities::digitMask(24, 6, 0);
                        } else {
                            $credit = Utilities::digitMask(24, 6, $result['Disc_BRep_TTC']);
                            $debit = Utilities::digitMask(24, 6, 0);
                            if ($country == "bel") {
                                $generalAccount = self::BE_DUPLICATE_MAPPER['BON_R']['compte'];
                                $label = self::BE_DUPLICATE_MAPPER['BON_R']['intitule'];
                                $analyticaldim2 = self::BE_DUPLICATE_MAPPER['BON_R']['dossier'];
                            } else {
                                $generalAccount = self::LUX_DUPLICATE_MAPPER['BON_R']['compte'];
                                $label = self::LUX_DUPLICATE_MAPPER['BON_R']['intitule'];
                                $analyticaldim2 = self::LUX_DUPLICATE_MAPPER['BON_R']['dossier'];
                            }

                        }
                        $vatType = "";
                        $vatCode = "";
                        break;
                    case "5":
                        $result['Disc_BPub_TTC'] = $result['Disc_BPub_TTC'] * 0.2196;
                        if (!$result['duplicate']) {
                            $debit = Utilities::digitMask(24, 6, $result['Disc_BPub_TTC']);
                            $credit = Utilities::digitMask(24, 6, 0);
                        } else {
                            $credit = Utilities::digitMask(24, 6, $result['Disc_BPub_TTC']);
                            $debit = Utilities::digitMask(24, 6, 0);
                            if ($country == "bel") {
                                $generalAccount = self::BE_DUPLICATE_MAPPER['BON_P']['compte'];
                                $label = self::BE_DUPLICATE_MAPPER['BON_P']['intitule'];
                                $analyticaldim2 = self::BE_DUPLICATE_MAPPER['BON_P']['dossier'];
                            } else {
                                $generalAccount = self::LUX_DUPLICATE_MAPPER['BON_P']['compte'];
                                $label = self::LUX_DUPLICATE_MAPPER['BON_P']['intitule'];
                                $analyticaldim2 = self::LUX_DUPLICATE_MAPPER['BON_P']['dossier'];
                            }
                        }
                        $vatType = "";
                        $vatCode = "";
                        break;
                    case "6":
                        $debit = Utilities::digitMask(24, 6, $result['VA_TTC']);
                        $credit = Utilities::digitMask(24, 6, 0);
                        break;
                    case "7":
                        $debit = Utilities::digitMask(24, 6, $result['VA_TVA']);
                        $credit = Utilities::digitMask(24, 6, 0);
                        break;
                    default:
                        $debit = 'error';
                        $credit = 'error';
                }

                if ($country == "bel") {
                    if ($generalAccount == 498000 || $generalAccount == 616701 || $generalAccount == 616711 || $generalAccount == 604400 || $generalAccount == 626000) {
                        $vat = '';
                        $vatCode = '';
                        $vatType = '';

                    }
                } else {
                    if ($generalAccount == 484900 || $generalAccount == 618891 || $generalAccount == 618893 || $generalAccount == 618814 || $generalAccount == 618815) {
                        $vat = '';
                        $vatCode = '';
                        $vatType = '';

                    }
                }
            }

            switch (strtolower($this->restaurantType)) {
                case "bk":
                    $restaurantCodeMapper = 'B'.substr($restaurantCode, 1);
                    break;
                case "quick":
                    $restaurantCodeMapper = 'Q'.substr($restaurantCode, 1);
                    break;
                default:
                    $restaurantCodeMapper = 'Q'.substr($restaurantCode, 1);
            }
            $analyticaldim3 = array_key_exists(
                $restaurantCodeMapper,
                self::CODE_RESTAURANT_MAPPER
            ) ? self::CODE_RESTAURANT_MAPPER[$restaurantCodeMapper] : $restaurantCode;

            //Code Journal : 3001 pour CA 3002 pour Bon de dépense/Bon de recette
            $sheet->setCellValueByColumnAndRow(0, $lineIndex, $environment);
            //Journal code :3001 pour CA 3002 pour Bon de dépense/Bon de recette
            $sheet->setCellValueByColumnAndRow(1, $lineIndex, $docType);
            //Year of the invoice 4 digits
            $sheet->setCellValueByColumnAndRow(2, $lineIndex, $date->format('Y'));
            //Month of the invoice 2 digits
            $sheet->setCellValueByColumnAndRow(3, $lineIndex, $date->format('m'));

            //Piece number : always 0
            $sheet->setCellValueByColumnAndRow(4, $lineIndex, "0");
            //Line number : always 0
            $sheet->setCellValueByColumnAndRow(5, $lineIndex, "0");
            //split : empty
            $sheet->setCellValueByColumnAndRow(6, $lineIndex, '');
            //Document type : empty
            $sheet->setCellValueByColumnAndRow(7, $lineIndex, '');
            //Mouvement code : always CHA
            $sheet->setCellValueByColumnAndRow(8, $lineIndex, 'CHA');
            //Date of the document
            $sheet->setCellValueByColumnAndRow(9, $lineIndex, $date->format('Ymd'));
            // Date : empty
            $sheet->setCellValueByColumnAndRow(10, $lineIndex, '');
            // date : empty
            $sheet->setCellValueByColumnAndRow(11, $lineIndex, '');
            //Code journal Cut Off  : empty
            $sheet->setCellValueByColumnAndRow(12, $lineIndex, '');
            //Date Start Cut Off : empty
            $sheet->setCellValueByColumnAndRow(13, $lineIndex, '');
            //Date End Cut Off
            $sheet->setCellValueByColumnAndRow(14, $lineIndex, '');
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(15, $lineIndex, '');
            //Main supplier code : empty
            $sheet->setCellValueByColumnAndRow(16, $lineIndex, '');
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(17, $lineIndex, '');
            //Main supplier code for paiement : empty
            $sheet->setCellValueByColumnAndRow(18, $lineIndex, '');
            //Paiement mode : empty
            $sheet->setCellValueByColumnAndRow(19, $lineIndex, '');
            //External reference : empty
            $sheet->setCellValueByColumnAndRow(20, $lineIndex, '');
            //Internal reference : empty
            $sheet->setCellValueByColumnAndRow(21, $lineIndex, $ca_mapping_key);
            //Description structure : empty
            $sheet->setCellValueByColumnAndRow(22, $lineIndex, '');
            //Description header : empty
            $sheet->setCellValueByColumnAndRow(23, $lineIndex, '');

            //Description détail
            $sheet->setCellValueByColumnAndRow(24, $lineIndex, $label);

            //VAT type
            $sheet->setCellValueByColumnAndRow(25, $lineIndex, $vatType);

            //VAT code
            $sheet->setCellValueByColumnAndRow(26, $lineIndex, $vatCode);

            //VAT rate
            if(empty($vatType) && empty($vatCode)){
                $sheet->setCellValueByColumnAndRow(27, $lineIndex, '');
            }else{
                $sheet->setCellValueByColumnAndRow(27, $lineIndex, sprintf("%05d", $vat * 10000));
            }



            //Currency : Always EUR
            $sheet->setCellValueByColumnAndRow(28, $lineIndex, "EUR");
            //Local Currency : Always EUR
            $sheet->setCellValueByColumnAndRow(29, $lineIndex, "EUR");
            //Change rate : empty
            $sheet->setCellValueByColumnAndRow(30, $lineIndex, '');
            //Discount : always 0
            $sheet->setCellValueByColumnAndRow(31, $lineIndex, sprintf("%024d", 0));
            //Discount local Currency : always 0
            $sheet->setCellValueByColumnAndRow(32, $lineIndex, sprintf("%024d", 0));

            //Debit local Currency
            $sheet->setCellValueByColumnAndRow(33, $lineIndex, $debit);
            //Credit local currency
            $sheet->setCellValueByColumnAndRow(34, $lineIndex, $credit);
            //Debit
            $sheet->setCellValueByColumnAndRow(35, $lineIndex, $debit);
            //Credit
            $sheet->setCellValueByColumnAndRow(36, $lineIndex, $credit);

            //Flag lettrage : empty
            $sheet->setCellValueByColumnAndRow(37, $lineIndex, '');
            //Référence Lettrage : empty
            $sheet->setCellValueByColumnAndRow(38, $lineIndex, '');

            //Quantity : masque 99999999,99 (no space)
            $sheet->setCellValueByColumnAndRow(39, $lineIndex, sprintf("%010d", 0));

            //Flag abr/num : Number of chars in “General accompt” field
            $sheet->setCellValueByColumnAndRow(40, $lineIndex, strlen($generalAccount));
            //General account
            $sheet->setCellValueByColumnAndRow(41, $lineIndex, $generalAccount);
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(42, $lineIndex, '');
            //Abrégé compte cut off Y-1 : empty
            $sheet->setCellValueByColumnAndRow(43, $lineIndex, '');

            //Flag abr/num : Number of chars in “Analytical dimention 1” field.
            $sheet->setCellValueByColumnAndRow(
                44,
                $lineIndex,
                (strlen($analyticaldim1) == 0) ? 'N' : strlen($analyticaldim1)
            );
            //Analytical dimention 1 : "390000001"
            $sheet->setCellValueByColumnAndRow(45, $lineIndex, $analyticaldim1);
            $colString = PHPExcel_Cell::stringFromColumnIndex(46);
            //$colNumber = PHPExcel_Cell::columnIndexFromString($colString);
            $sheet->getStyle($colString.$lineIndex)->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER
            );

            //Flag abr/num : Number of chars in “Analytical dimention 2” field.

            $sheet->setCellValueByColumnAndRow(
                46,
                $lineIndex,
                (strlen($analyticaldim2) == 0) ? 'N' : strlen($analyticaldim2)
            );
            //Analytical dimention 2
            $sheet->setCellValueByColumnAndRow(47, $lineIndex, $analyticaldim2);
            //Flag abr/num : Number of chars in “Analytical dimention 3” field.
            $sheet->setCellValueByColumnAndRow(
                48,
                $lineIndex,
                (strlen($analyticaldim3) == 0) ? 'N' : strlen($analyticaldim3)
            );
            //Analytical dimention 3 : restaurant reference
            $sheet->setCellValueByColumnAndRow(49, $lineIndex, $analyticaldim3);
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(50, $lineIndex, '');
            //Analytical dimention 4 : empty
            $sheet->setCellValueByColumnAndRow(51, $lineIndex, '');
            //Flag abr/num
            $sheet->setCellValueByColumnAndRow(52, $lineIndex, '');
            //Approval by : empty
            $sheet->setCellValueByColumnAndRow(53, $lineIndex, '');
            //Approval state : empty
            $sheet->setCellValueByColumnAndRow(54, $lineIndex, '');
            //Code Litige : empty
            $sheet->setCellValueByColumnAndRow(55, $lineIndex, '');
            //Numéro de litige : empty
            $sheet->setCellValueByColumnAndRow(56, $lineIndex, '');
            //Niveau de rappel : empty
            $sheet->setCellValueByColumnAndRow(57, $lineIndex, '');
            //Condition de paiement : empty
            $sheet->setCellValueByColumnAndRow(58, $lineIndex, '');
            //Traitement particulier : empty
            $sheet->setCellValueByColumnAndRow(59, $lineIndex, '');
            //Commentaire(s) en-tête : empty
            $sheet->setCellValueByColumnAndRow(60, $lineIndex, '');
            //Date paiement : empty
            $sheet->setCellValueByColumnAndRow(61, $lineIndex, '');
            //Date Enregistrement : empty
            $sheet->setCellValueByColumnAndRow(62, $lineIndex, '');

            //Zone libre : code bon
            $sheet->setCellValueByColumnAndRow(63, $lineIndex, $codeBon);

            //Chemin du pdf attaché : empty
            $sheet->setCellValueByColumnAndRow(64, $lineIndex, '');
            //Nature du Document : empty
            $sheet->setCellValueByColumnAndRow(65, $lineIndex, '');
            //N° Domiciliation : empty
            $sheet->setCellValueByColumnAndRow(66, $lineIndex, '');
            //Compte Bancaire : empty
            $sheet->setCellValueByColumnAndRow(67, $lineIndex, '');
            //N°Adresse de facturation : empty
            $sheet->setCellValueByColumnAndRow(68, $lineIndex, '');
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(69, $lineIndex, '');
            //ASSET : empty
            $sheet->setCellValueByColumnAndRow(70, $lineIndex, '');
            //Date de prochain rappel : empty
            $sheet->setCellValueByColumnAndRow(71, $lineIndex, '');
            //Destination Environnement : empty
            $sheet->setCellValueByColumnAndRow(72, $lineIndex, '');
            //Code Imputation : empty
            $sheet->setCellValueByColumnAndRow(73, $lineIndex, '');
            //Période TVA : empty
            $sheet->setCellValueByColumnAndRow(74, $lineIndex, '');
            //Date TVA : empty
            $sheet->setCellValueByColumnAndRow(75, $lineIndex, '');
            //Date valeur : empty
            $sheet->setCellValueByColumnAndRow(76, $lineIndex, '');
            //Date escompte : empty
            $sheet->setCellValueByColumnAndRow(77, $lineIndex, '');

            //Flag abr/num : Number of chars in “Analytical dimention 5” field.
            $sheet->setCellValueByColumnAndRow(
                78,
                $lineIndex,
                (strlen($analyticaldim5) == 0) ? 'N' : strlen($analyticaldim5)
            );
            //Analytical dimention 5
            $sheet->setCellValueByColumnAndRow(79, $lineIndex, $analyticaldim5);

            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(80, $lineIndex, '');
            //Abrégé zone additionnelle 2 : empty
            $sheet->setCellValueByColumnAndRow(81, $lineIndex, '');
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(82, $lineIndex, '');
            //Abrégé zone additionnelle 3 : empty
            $sheet->setCellValueByColumnAndRow(83, $lineIndex, '');
            //Flag abr/num : empty
            $sheet->setCellValueByColumnAndRow(84, $lineIndex, '');
            //Abrégé zone additionnelle 4 : empty
            $sheet->setCellValueByColumnAndRow(85, $lineIndex, '');
            //Période d’extourne : empty
            $sheet->setCellValueByColumnAndRow(86, $lineIndex, '');
            //Date de début : empty
            $sheet->setCellValueByColumnAndRow(87, $lineIndex, '');
            //Date de fin : empty
            $sheet->setCellValueByColumnAndRow(88, $lineIndex, '');

            // end of ligne : Dans Excel, vous devez  indiquer  pour X pour la fin de ligne
            $sheet->setCellValueByColumnAndRow(89, $lineIndex, 'X');

            $lineIndex++;


        }


        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel2007');


        $writer->save($path);

        return $writer;

    }

    /**
     * @param $data
     * @return array
     */
    public function serializeCAResults($data)
    {
        $serializedResults = array();
        foreach ($data as $result) {
            if ($result['ca_net_ttc'] >= 0.01) {
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'CA_NET_TTC' => $result['ca_net_ttc'],
                    'rub_id' => self::CA_RUB_ID['CA_NET_TTC'],
                    'duplicate' => false,
                );
            }
            if ($result['ca_net_tva'] >= 0.01) {
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'CA_NET_TVA' => $result['ca_net_tva'],
                    'rub_id' => self::CA_RUB_ID['CA_NET_TVA'],
                    'duplicate' => false,
                );
            }
            if ($result['ca_net_ttc'] >= 0.01) {
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'CA_NET_TTC' => $result['ca_net_ttc'],
                    'rub_id' => self::CA_RUB_ID['CA_NET_TTC'],
                    'duplicate' => true,
                );
            }
            if ($result['ca_net_tva'] >= 0.01) {
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'CA_NET_TVA' => $result['ca_net_tva'],
                    'rub_id' => self::CA_RUB_ID['CA_NET_TVA'],
                    'duplicate' => true,
                );
            }
            if ($result['disc_brep_ttc'] >= 0.01) {
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'Disc_BRep_TTC' => $result['disc_brep_ttc'],
                    'rub_id' => self::CA_RUB_ID['Disc_BonRepas_TTC'],
                    'duplicate' => false,
                );
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'Disc_BRep_TTC' => $result['disc_brep_ttc'],
                    'rub_id' => self::CA_RUB_ID['Disc_BonRepas_TTC'],
                    'duplicate' => true,
                );
            }
            if ($result['disc_bpub_ttc'] >= 0.01) {
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'Disc_BPub_TTC' => $result['disc_bpub_ttc'],
                    'rub_id' => self::CA_RUB_ID['Disc_BonPub_TTC'],
                    'duplicate' => false,
                );
                $serializedResults[] = array(
                    'Restaurant' => $result['restaurant'],
                    'CommercialDate' => $result['commercial_date'],
                    'canal_vente' => $this->getCodeSoldingCanal($result['canal_vente']),
                    'taxe' => $result['taxe'],
                    'Disc_BPub_TTC' => $result['disc_bpub_ttc'],
                    'rub_id' => self::CA_RUB_ID['Disc_BonPub_TTC'],
                    'duplicate' => true,
                );
            }

        }

        return $serializedResults;
    }

    /**
     * @param $data
     * @return array
     */
    public function serializeBonResults($data)
    {
        $serializedResults = array();
        foreach ($data as $result) {
            if ($result['Montant'] >= 0.01) {
                $tva = 0;
                $net = 0;
                $brut = $result['Montant'];
                if (strtoupper($result['Type']) == 'D') {
                    if ($this->country == "bel") {
                        $affectation = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['D']
                        ) ? self::GENERAL_ACCOUNT_BE['D'][$result['codeFonction']]['affectation'] : "";
                        $affectation_tva = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['D']
                        ) ? self::GENERAL_ACCOUNT_BE['D'][$result['codeFonction']]['affectation_tva'] : "";
                        $contre_partie = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['D']
                        ) ? self::GENERAL_ACCOUNT_BE['D'][$result['codeFonction']]['contre_partie'] : "";
                        $net = $brut / 1.06;
                        $tva = $brut - $net;
                    } else {
                        $affectation = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['D']
                        ) ? self::GENERAL_ACCOUNT_LUX['D'][$result['codeFonction']]['affectation'] : "";
                        $affectation_tva = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['D']
                        ) ? self::GENERAL_ACCOUNT_LUX['D'][$result['codeFonction']]['affectation_tva'] : "";
                        $contre_partie = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['D']
                        ) ? self::GENERAL_ACCOUNT_LUX['D'][$result['codeFonction']]['contre_partie'] : "";
                        $net = $brut / 1.03;
                        $tva = $brut - $net;
                    }

                } elseif (strtoupper($result['Type']) == 'R') {
                    if ($this->country == "bel") {
                        $affectation = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['R']
                        ) ? self::GENERAL_ACCOUNT_BE['R'][$result['codeFonction']]['affectation'] : "";
                        $affectation_tva = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['R']
                        ) ? self::GENERAL_ACCOUNT_BE['R'][$result['codeFonction']]['affectation_tva'] : "";
                        $contre_partie = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_BE['R']
                        ) ? self::GENERAL_ACCOUNT_BE['R'][$result['codeFonction']]['contre_partie'] : "";
                        $net = $brut / 1.06;
                        $tva = $brut - $net;
                    } else {
                        $affectation = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['R']
                        ) ? self::GENERAL_ACCOUNT_LUX['R'][$result['codeFonction']]['affectation'] : "";
                        $affectation_tva = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['R']
                        ) ? self::GENERAL_ACCOUNT_LUX['R'][$result['codeFonction']]['affectation_tva'] : "";
                        $contre_partie = array_key_exists(
                            $result['codeFonction'],
                            self::GENERAL_ACCOUNT_LUX['R']
                        ) ? self::GENERAL_ACCOUNT_LUX['R'][$result['codeFonction']]['contre_partie'] : "";
                        $net = $brut / 1.03;
                        $tva = $brut - $net;
                    }

                }
                if (!empty($affectation_tva)) {//411000
                    $result['line_type'] = 'affectation_tva';
                    $result['compte'] = $affectation_tva;
                    $result['Montant'] = $tva;
                    $serializedResults[] = $result;
                } else {
                    $net = $brut;
                }
                if (!empty($affectation)) {
                    $result['line_type'] = 'affectation';
                    $result['compte'] = $affectation;
                    $result['Montant'] = $net;
                    $serializedResults[] = $result;
                }
                $result['Montant'] = $brut;
                if (!empty($contre_partie)) {//570000
                    $result['line_type'] = 'contre_partie';
                    $result['compte'] = $contre_partie;
                    $serializedResults[] = $result;
                }
            }
        }

        return $serializedResults;
    }

    /**
     * @param $soldingCanal
     * @return mixed
     */
    private function getCodeSoldingCanal($soldingCanal)
    {
        if (array_key_exists(strtolower($soldingCanal), self::CODE_SOLDING_CANAL)) {
            return self::CODE_SOLDING_CANAL[strtolower($soldingCanal)];
        } else {
            return self::CODE_SOLDING_CANAL["eatin"];
        }
    }


    /**
     * generate a not serialised excel
     * @param $filename
     * @param $docType
     * @param $results
     * @param $path
     * @return \PHPExcel_Writer_IWriter
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function generateNormalExcel($filename, $docType, $results, $path, $fileExist = false)
    {
        if (empty($results)) {
            return false;
        }
        if (!$fileExist) {
            $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        } else {
            $phpExcelObject = $this->phpExcel->createPHPExcelObject($path);
        }

        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $lineIndex = ($fileExist) ? ($sheet->getHighestRow() + 1) : 1;
        $columnIndex = 0;
        $sheet->setTitle($filename);

        if (!$fileExist) {
            // set file headers title
            $firstLine = $results[0];
            $sheet->getStyle($lineIndex.":".$lineIndex)->getFill()->applyFromArray(
                array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array(
                        'rgb' => 'c0c0c0',
                    ),
                )
            );
            foreach ($firstLine as $key => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $lineIndex, strtoupper($key));
                $columnIndex++;
            }
            $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            /** @var PHPExcel_Cell $cell */
            foreach ($cellIterator as $cell) {
                $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
            }
            $lineIndex++;
        }

        if (strtoupper($docType) == 'BON') {

        } else {


            foreach ($results as $result) {
                $columnIndex = 0;
                foreach ($result as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $lineIndex, $value);
                    $columnIndex++;
                }
                $lineIndex++;
            }
        }

        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel2007');
        $writer->save($path);

        return $writer;

    }


}