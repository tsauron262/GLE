<?php

class GSX_Const
{

    // Toutes les données en dur pour GSX V2 sont à mettre ici: 
    public static $mode = 'prod'; // test ou prod
    public static $debug_mode = false;
    public static $log_errors = true;
    public static $log_requests = false;
    public static $numbersNumChars = 10;
    public static $centres_array = null;
    public static $sav_files = array(
        'test' => 'TEST'
    );
    public static $urls = array(
        'login'            => array(
            'test' => 'https://login-partner-connect-uat.apple.com',
            'prod' => 'https://login-partner-connect.apple.com',
        ),
        'base'             => array(
            'test' => 'https://api-partner-connect-uat.apple.com/',
            'prod' => 'https://api-partner-connect.apple.com/',
        ),
        'base_file_upload' => array(
            'test' => 'https://partner-connect-uat.corp.apple.com/',
            'prod' => 'https://partner-connect.corp.apple.com/',
        ),
        'gsx'              => array(
            'test' => 'https://gsxapput.apple.com/',
            'prod' => ''
        ),
        'req'              => array(
            'authenticate'                   => 'api/authenticate/token',
            // Réparations: 
            'productDetails'                 => 'gsx/api/repair/product/details',
            'componentIssue'                 => 'gsx/api/repair/product/componentissue',
            'repairSummary'                  => 'gsx/api/repair/summary',
            'repairDetails'                  => 'gsx/api/repair/details',
            'repairEligibility'              => 'gsx/api/repair/eligibility',
            'repairCreate'                   => 'gsx/api/repair/create',
            'repairUpdate'                   => 'gsx/api/repair/update',
            'repairQuestions'                => 'gsx/api/repair/questions',
            // Diagnostiques: 
            'diagnosticSuites'               => 'gsx/api/diagnostics/suites',
            'diagnosticTest'                 => 'gsx/api/diagnostics/initiate-test',
            'diagnosticStatus'               => 'gsx/api/diagnostics/status',
            'diagnosticsLookup'              => 'gsx/api/diagnostics/lookup',
            // Retours: 
            'returnsManage'                  => 'gsx/api/returns/manage',
            'returnsLookup'                  => 'gsx/api/returns/lookup',
            'returnsConfirmshipment'         => 'gsx/api/returns/confirmshipment',
            // Réservations: 
            'fetchReservationsSummary'       => 'gsx/api/reservation/summary',
            'fetchReservation'               => 'gsx/api/reservation/details',
            'fetchAvailableSlots'            => 'gsx/api/reservation/fetch-available-slots',
            'createReservation'              => 'gsx/api/reservation/create',
            'updateReservation'              => 'gsx/api/reservation/update',
            // Stocks consignés
            'consignmentOrderLookup'         => 'gsx/api/consignment/order/lookup',
            'consignmentDeliveryLookup'      => 'gsx/api/consignment/delivery/lookup',
            'consignmentDeliveryAcknowledge' => 'gsx/api/consignment/delivery/acknowledge',
            'consignmentOrderSubmit'         => 'gsx/api/consignment/order/submit',
            'consignmentOrderShipment'       => 'gsx/api/consignment/order/shipment',
            // Autre: 
            'partsSummary'                   => 'gsx/api/parts/summary',
            'filesUpload'                    => 'gsx/api/attachment/upload-access',
            'articleLookup'                  => 'gsx/api/content/article/lookup',
            'getFile'                        => 'gsx/api/document-download',
            'attributeLookup'                => 'gsx/api/attribute/lookup'
        )
    );
    public static $getRequests = array(
        'repairDetails', 'diagnosticSuites', 'fetchReservation', 'fetchAvailableSlots'
    );
    public static $fileContentRequests = array(
        'getFile'
    );
    public static $certifs = array(
//        897316  => array(
//            'test' => array('testCertif.pem', '', 'test.key'),
//            'prod' => array('AppleCare-Partner-0000897316.Prod.apple.com.chain.pem', '', 'AppleCare-Partner-0000897316.Prod.apple.com.key')
//        ),
//        579256  => array(
//            'test' => array('privatekey.nopass.pem', ''),
//            'prod' => array('proditrb.pem', '')
//        ),
        1442050 => array(
            'test' => array('AppleCare-Partner-0001442050.Test.apple.com.chain.pem', '', 'AppleCare-Partner-0001442050.Test.apple.com.key'),
            'prod' => array('AppleCare-Partner-0001442050.Prod.apple.com.chain.pem', '', 'AppleCare-Partner-0001442050.Prod.apple.com.key')
        ),
        897316 => array(
            'test' => array('AppleCare-Partner-0001442050.Test.apple.com.chain.pem', '', 'AppleCare-Partner-0001442050.Test.apple.com.key'),
            'prod' => array('AppleCare-Partner-0001442050.Prod.apple.com.chain.pem', '', 'AppleCare-Partner-0001442050.Prod.apple.com.key')
        ),
        608111 => array(
             'test' => array('AppleCare-Partner-0000608111.Test.apple.com.fullchain.pem', 'tresor', 'AppleCare-Partner-0000608111.Test.apple.com.key'),
             'prod' => array('AppleCare-Partner-0000608111.Prod.apple.com.fullchain.pem', 'tresor', 'AppleCare-Partner-0000608111.Prod.apple.com.key')
        )
    );
    public static $test_ids = array(
//        'apple_id'    => 'olys_tech_aprvlreqrd@olys.com', // OLD
//        'apple_id'    => 'olys_tech_aprvlnotreqrd@olys.com', // OLD
        'apple_id'    => 'olys_tech_aprvlnotreqrd@bimp.fr', // New avec droits "Approve Repairs", "Repair Approval Not Required", "Order approval", "Order Approval Not Required" et "Extended Activation Details"
        'tech_id'     => 'WJYQUBA45A',
//        'apple_id'    => 'olys_tech_aprvlreqrd@bimp.fr', // New sans droits
//        'tech_id' => 'ZUGLPGA45B',
        'apple_pword' => 'Apple@214',
        'sold_to'     => '1442050',
        'ship_to'     => '1442050'
    );
    public static $default_ids = array(
        'apple_id'    => 'admin.gle@bimp.fr',
//        'apple_id'    => 't.sauron@ldlc.com',
        'apple_pword' => '@LdLc.com#69760',
        'sold_to'     => '1442050',
        'ship_to'     => '1442050'
    );
//    public static $default_reservations_tech_id = 'G1DFE7494B';
    public static $importIdentifierTypes = array(
        'serial'   => 'Numéro de série',
        'repairId' => 'ID réparation'
    );
    // Codes GSX: 
    public static $deviceIdentifiers = array(
        'meid'   => 'MEID',
        'imei2'  => 'IMEI2',
        'serial' => 'Numéro de série',
        'imei'   => 'IMEI'
    );
    public static $repair_types = array(
        'CIN'  => 'Carry-In',
        'CRBR' => 'Carry-In (Sans recommande de stock)',//Return Before Replace',
        'CINR' => 'Carry-In Non-Replenishment',
        'MINS' => 'Mail-In Return to Service Location',
//        'MINC' => 'Mail-In Return to Customer',
//        'DION' => 'Onsite Service Direct',
//        'INON' => 'Onsite Service Indirect',
//        'OSR'  => 'Onsite Service Facilitated',
//        'OSCR' => 'Onsite Service Pickup',
        'WUMS' => 'Whole Unit Mail-In Return to Service Location',
//        'WUMC' => 'Whole Unit Mail-In Return to Customer',
        'SVNR' => 'service Non-Repair'
    );
    public static $service_types = array(
        ''    => '',
        'NTF' => 'No Trouble Found',
        'SRC' => 'Screening',
        'LUA' => 'Loaner Unavailable',
        'CUA' => 'Customer Unavailable'
    );
//    public static $repair_classifications = array(
//        'SINGLE'                    => 'Simple - le client demande une seule réparation',
//        'BULK'                      => 'Multiple - le client demande plusieurs réparations',
//        'NEEDS_EXTRA_UNDERSTANDING' => 'Needs extra understanding - Customer requests repairs for a large number of devices that may not belong to them'
//    );
    public static $repair_classifications = array(
        'DIRECT'                    => 'Soumission directe',
        'NEEDS_EXTRA_UNDERSTANDING' => 'Nécessite une compréhension supplémentaire',
        'INDIRECT'                  => 'Soumission indirecte'
    );
    public static $repair_note_types = array(
        'TECHNICIAN'            => 'Notes du technicien',
        'CUSTOMER_INTAKE_NOTES' => 'Notes du client',
        'HOLD_REVIEW'           => 'Notes pour la révision',
//        'REP_STS'               => 'Statut réparation',
//        'RC_NOTE'               => 'Centre',
//        'REVIEW_RESULT'         => 'Notes indicating results for review hold'
    );
    public static $consumer_law = array(
        ''                  => '',
        'RFD'               => 'Refund',
        'RPL'               => 'Replace',
        'SVC'               => 'Service',
        'OPT_IN'            => 'Customer has opted in for Consumer Law coverage',
        'OPT_OUT'           => 'Customer has opted out of Consumer Law coverage',
        'OPT_IN_FOR_REVIEW' => 'Opt in for Review',
        'OPT_OUT_OF_REVIEW' => 'Opt out of Review'
    );
    public static $reproducibilities = array(
        'A' => 'Non Applicable',
        'B' => 'Continue',
        'C' => 'Intermittente',
        'D' => 'Panne à chaud',
        'E' => 'Environnementale',
        'F' => 'Configuration: périphérique',
        'G' => 'Endommagée',
        'H' => 'Demande d\'évaluation'
    );
    public static $return_statuses = array(
        ''     => '',
        'DOA'  => 'Dead on arrival',
        'CTS'  => 'Convert to stock',
        'GPR'  => 'Good part return',
        'TOW'  => 'Transfer to OOW',
        'SDOA' => 'Stock dead on arrival'
    );
    public static $coverage_options = array(
        ''               => '',
        'BATTERY'        => array('label' => 'Billable battery repair', 'classes' => array('success')),
        'DISPLAY'        => array('label' => 'Billable display repair', 'classes' => array('success')),
        'VMI_YELLOW'     => array('label' => 'VMI Yellow, service price', 'classes' => array('warning')),
        'APPLECARE_PLUS' => array('label' => 'AppleCare+ covered incident', 'classes' => array('success')),
        'VMI_RED'        => array('label' => 'VMI Red, full price', 'classes' => array('danger')),
        'VMI_GREEN'      => array('label' => 'VMI Green', 'classes' => array('success'))
    );
    public static $loaner_return_dispositions = array(
        ''     => '',
        'LRFR' => 'Recycle Loaner - Place back in stock',
        'LVFN' => 'Component Check Failed - Abandoned',
        'LEXP' => 'Loaner Expired',
        'LABN' => 'Repair Abandoned - No charge, iPhone return',
        'LRTN' => 'Return Loaner - No damage, no charge',
        'LABS' => 'Non-Repairable Damage - Return, no charge',
        'LRDD' => 'Return Loaner - Display damage',
        'DOA'  => 'Loaner Dead on Arrival - Return, no charge',
        'LOST' => 'Loaner Lost - Charge, no return',
        'LVFU' => 'Component Check Failed - Charge',
        'LABE' => 'Return Loaner - Repairable Damage'
    );
    public static $repairOutcomes = array(
        'HOLD'        => 'Denotes that repair is created and is put on hold',
        'STOP'        => 'Denotes that repair cannot be created',
        'MESSAGE'     => 'Denotes that repair is created',
        'REPAIR_TYPE' => 'Eligible repair types',
        'WARNING'     => 'Additional coverage details'
    );
    public static $allowedFilesFormats = array(
        'txt', 'doc', 'docx', 'csv', 'pdf', 'jpg', 'png', 'jpeg', 'zip'
    );

    // Méthodes statiques: 

    public static function getCertifInfo($soldTo, &$errors = array())
    {
        $soldTo = intval($soldTo);

        $pass = $certif = $pathKey = '';
        if (isset(self::$certifs[$soldTo][self::$mode])) {
            $data = self::$certifs[$soldTo][self::$mode];

            if (isset($data[0])) {
                $certif = $data[0];
            }
            if (isset($data[1])) {
                $pass = $data[1];
            }
            if (isset($data[2])) {
                $pathKey = $data[2];
            }
        }

        $folder = DOL_DOCUMENT_ROOT . '/bimpapple/certif/api2/';

        if (empty($certif)) {
            $errors[] = 'Aucun certificat trouvé pour le soldTo "' . $soldTo . '"';
            BimpCore::addlog('Aucun certificat trouvé pour le soldTo "' . $soldTo . '"', Bimp_Log::BIMP_LOG_URGENT, 'gsx');
        } else {
            if (!file_exists($folder . $pathKey)) {
                $errors[] = 'Fichier "Path Key" absent';
                BimpCore::addlog('Fichier "' . $folder . $pathKey . '" absent', Bimp_Log::BIMP_LOG_URGENT, 'gsx');
            }

            if (!file_exists($folder . $certif)) {
                $errors[] = 'Fichier du certificat absent';
                BimpCore::addlog('Fichier "' . $folder . $certif . '" absent', Bimp_Log::BIMP_LOG_URGENT, 'gsx');
            }
        }

        return array("pass" => $pass, "pathKey" => $folder . $pathKey, 'path' => $folder . $certif);
    }

    public static function renderJsVars()
    {
        $use_gsx = 1;

        $html = '<script type="text/javascript">';
        $html .= 'use_gsx = ' . $use_gsx . ';';
        $html .= 'use_gsx_v2 = ' . ((int) BimpCore::getConf('use_gsx_v2', 1, 'bimpapple') ? 'true' : 'false') . ';';
        $html .= 'gsx_login_url = \'' . self::$urls['login'][self::$mode] . '\';';
        $html .= '</script>';

        return $html;
    }

    public function getCountriesCodesArray()
    {
        return BimpCache::getCountriesArray(0, 'code_iso');
    }

    public function getCentresArray()
    {
        if (is_null(self::$centres_array)) {
            self::$centres_array = array();

            BimpCore::requireFileForEntity('bimpsupport', 'centre.inc.php');

            global $tabCentre;
            foreach ($tabCentre as $code => $centre) {
                self::$centres_array[$code] = $centre[2] . ' (' . $centre[4] . ')';
            }
        }

        return self::$centres_array;
    }
}
