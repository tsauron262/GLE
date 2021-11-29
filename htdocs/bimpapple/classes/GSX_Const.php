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
//            'test' => 'https://dgfdgsx2-uat.apple.com/gsx/api/login', // OLD
            'test' => 'https://login-partner-connect-uat.apple.com',
//            'prod' => 'https://gsx2.apple.com/gsx/api/login', // OLD
            'prod' => 'https://login-partner-connect.apple.com',
        ),
        'base'             => array(
//            'test' => 'https://partner-connect-uat.apple.com/gsx/api/', // OLD
            'test' => 'https://api-partner-connect-uat.apple.com/',
//            'prod' => 'https://partner-connect.apple.com/gsx/api/' // OLD
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
//            'authenticate'           => 'authenticate/token',
            // Réparations: 
//            'productDetails'         => 'repair/product/details',
//            'componentIssue'         => 'repair/product/componentissue',
//            'repairSummary'          => 'repair/summary',
//            'repairDetails'          => 'repair/details',
//            'repairEligibility'      => 'repair/eligibility',
//            'repairCreate'           => 'repair/create',
//            'repairUpdate'           => 'repair/update',
//            'repairQuestions'        => 'repair/questions',
//            // Diagnostiques: 
//            'diagnosticSuites'       => 'diagnostics/suites',
//            'diagnosticTest'         => 'diagnostics/initiate-test',
//            'diagnosticStatus'       => 'diagnostics/status',
//            'diagnosticsLookup'      => 'diagnostics/lookup',
//            // Retours: 
//            'returnsManage'          => 'returns/manage',
//            'returnsLookup'          => 'returns/lookup',
//            'returnsConfirmshipment' => 'returns/confirmshipment',
//            // Autre: 
//            'partsSummary'           => 'parts/summary',
//            'filesUpload'            => 'attachment/upload-access',
//            'articleLookup'          => 'content/article/lookup',

            'authenticate'           => 'api/authenticate/token',
            // Réparations: 
            'productDetails'         => 'gsx/api/repair/product/details',
            'componentIssue'         => 'gsx/api/repair/product/componentissue',
            'repairSummary'          => 'gsx/api/repair/summary',
            'repairDetails'          => 'gsx/api/repair/details',
            'repairEligibility'      => 'gsx/api/repair/eligibility',
            'repairCreate'           => 'gsx/api/repair/create',
            'repairUpdate'           => 'gsx/api/repair/update',
            'repairQuestions'        => 'gsx/api/repair/questions',
            // Diagnostiques: 
            'diagnosticSuites'       => 'gsx/api/diagnostics/suites',
            'diagnosticTest'         => 'gsx/api/diagnostics/initiate-test',
            'diagnosticStatus'       => 'gsx/api/diagnostics/status',
            'diagnosticsLookup'      => 'gsx/api/diagnostics/lookup',
            // Retours: 
            'returnsManage'          => 'gsx/api/returns/manage',
            'returnsLookup'          => 'gsx/api/returns/lookup',
            'returnsConfirmshipment' => 'gsx/api/returns/confirmshipment',
            // Autre: 
            'partsSummary'           => 'gsx/api/parts/summary',
            'filesUpload'            => 'gsx/api/attachment/upload-access',
            'articleLookup'          => 'gsx/api/content/article/lookup',
            'getFile'                => 'gsx/api/document-download',
        )
    );
    public static $getRequests = array(
        'repairDetails', 'diagnosticSuites'
    );
    public static $fileContentRequests = array(
        'getFile'
    );
    public static $certifs = array(
        897316 => array(
            'test' => array('testCertif.pem', '', 'test.key'),
            'prod' => array('AppleCare-Partner-0000897316.Prod.apple.com.chain.pem', '', 'prod.key')
        ),
        579256 => array(
            'test' => array('privatekey.nopass.pem', ''),
            'prod' => array('proditrb.pem', '')
        ),
        1442050 => array(
            'test' => array('AppleCare-Partner-0001442050.Test.apple.com.chain.pem', '', 'AppleCare-Partner-0001442050.Test.apple.com.key'),
            'prod' => array('AppleCare-Partner-0001442050.Prod.apple.com.chain.pem', '', 'AppleCare-Partner-0001442050.Prod.apple.com.key')
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
        'sold_to'     => '897316',
        'ship_to'     => '897316'
    );
    public static $default_ids = array(
        'apple_id'    => 'admin.gle@bimp.fr',
        'apple_pword' => '@LdLc.com#69760',
        'sold_to'     => '897316',
        'ship_to'     => '897316'
    );
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
        'CRBR' => 'Carry-In Return Before Replace',
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
        'LUA' => 'Loaner Unavailable'
    );
    public static $repair_classifications = array(
        'SINGLE'                    => 'Simple - le client demande une seule réparation',
        'BULK'                      => 'Multiple - le client demande plusieurs réparations',
        'NEEDS_EXTRA_UNDERSTANDING' => 'Needs extra understanding - Customer requests repairs for a large number of devices that may not belong to them'
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
        ''        => '',
        'RFD'     => 'Refund',
        'RPL'     => 'Replace',
        'SVC'     => 'Service',
        'OPT_IN'  => 'Customer has opted in for Consumer Law coverage',
        'OPT_OUT' => 'Customer has opted out of Consumer Law coverage'
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

    public static function getCertifInfo($soldTo)
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

        if (empty($certif)) {
            if (self::$log_errors) {
                dol_syslog('Aucun certificat trouvé pour le soldTo "' . $soldTo . '"', LOG_ERR);
            }
        }
        $folder = DOL_DOCUMENT_ROOT . '/bimpapple/certif/api2/';
        return array("pass" => $pass, "pathKey" => $folder . $pathKey, 'path' => $folder . $certif);
    }

    public static function renderJsVars()
    {
        $use_gsx = 1;

        $html = '<script type="text/javascript">';
        $html .= 'use_gsx = ' . $use_gsx . ';';
        $html .= 'use_gsx_v2 = ' . ((int) BimpCore::getConf('use_gsx_v2') ? 'true' : 'false') . ';';
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

            require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

            global $tabCentre;
            foreach ($tabCentre as $code => $centre) {
                self::$centres_array[$code] = $centre[2] . ' (' . $centre[4] . ')';
            }
        }

        return self::$centres_array;
    }
}
