<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

ignore_user_abort(0);

top_htmlhead('', 'QUICK SCRIPTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array(
        'correct_prod_cur_pa'                       => 'Corriger le champs "cur_pa_ht" des produits',
        'check_facs_paiement'                       => 'Vérifier les statuts paiements des factures',
        'check_facs_paiement_rap_inf_one_euro'      => 'Vérifier les statuts paiements des factures (Restes à payer < 1€)',
        'check_facs_remain_to_pay'                  => 'Recalculer tous les restes à payer',
        'check_clients_solvabilite'                 => 'Vérifier les statuts solvabilité des clients',
        'check_commandes_status'                    => 'Vérifier les statuts des commandes client',
        'check_commandes_fourn_status'              => 'Vérifier les statuts des commandes fournisseur',
        'change_prods_refs'                         => 'Corriger refs produits',
//        'check_vente_paiements'        => 'Vérifier les paiements des ventes en caisse',
        'check_factures_rg'                         => 'Vérification des Remmises globales factures',
        'traite_obsolete'                           => 'Traitement des produit obsoléte hors stock',
        'cancel_factures'                           => 'Annulation factures',
        'refresh_count_shipped'                     => 'Retraitement des lignes fact non livre et inversse',
        'convert_user_configs'                      => 'Convertir les configurations utilisateur vers la nouvelle version',
        'check_list_table_configs'                  => 'Vérifier les configurations de liste',
        'check_stocks_mouvements'                   => 'Vérifier les mouvements de stock (doublons)',
        'check_limit_client'                        => 'Vérifier les encours credit safe',
        'check_facs_margin'                         => 'Vérifier les marges + revals OK factures',
        'change_sn'                                 => 'Changement de SN',
        'secteur_facture_fourn_with_commande_fourn' => 'Secteur fact fourn with comm fourn',
        'correct_sav_dates_rdv'                     => 'Corriger dates RDV SAV',
        'correct_tickets_serials'                   => 'Récupérer serials tickets depuis sujet',
        'convert_fi'                                => 'Convertir FI',
        'check_margin_commande'                     => 'Marge commande',
        'check_margin_propal'                       => 'Marge propal',
        'convert_sql_field_for_items_braces'        => 'Convertir champ "Items_list" avec utilisation des crochets',
        'checkLinesEcheances'                       => 'Vérifier échéances produits limités',
        'maj_id_atradius'                           => 'Vérifier id atradius',
        'repare_id_contrat_note'                    => 'Reparé id contat dans note',
        'maj_marge'                                 => 'Mise a jour des marge liste id facutures'
    );

    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

BimpCore::setMaxExecutionTime(2400);

switch ($action) {
    case 'maj_id_atradius':
        global $db;
        $warnings = array();
        $sql = $db->query("SELECT rowid FROM `llx_societe` WHERE (`outstanding_limit_atradius` > '0' || `outstanding_limit_credit_check` > 0) && id_atradius < 1 LIMIT 0,10000;");
        while ($ln = $db->fetch_object($sql)) {
            $cli = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', $ln->rowid);
            $cli->syncroAtradius($warnings);
        }
        print_r($warnings);
        break;
    case 'maj_marge':
        $ids = array(358309, 780375, 780444, 780546, 780549, 780960, 781017, 782178, 782352, 783201, 785091, 785097, 787026, 793197, 794430, 794481, 794505, 799401, 799413, 799629, 799662, 799668, 799683, 799689, 800010, 800013, 801471, 802485, 802542, 802698, 803490, 803511, 805230, 805410, 805428, 805434, 807492, 807762, 809001, 811089, 812805, 816819, 816828, 816849, 816858, 817455, 817521, 818193, 818382, 818487, 818673, 819174, 819183, 819273, 819507, 819837, 820908, 820959, 821040, 822000, 822234, 822237, 822240, 822249, 822255, 823209, 824346, 824373, 824451, 824532, 825600, 825663, 825717, 826659, 826869, 826872, 826878, 827421, 827637, 828126, 828951, 828957, 828987, 829176, 829344, 829533, 829824, 830364, 831555, 833061, 833354, 833356, 833410, 834630, 835622, 836495, 836955, 837191, 837194, 837219, 837259, 839218, 840155, 840279, 840451, 842119, 842123, 842127, 842574, 842818, 842830, 842866, 842894, 843090, 843131, 843202, 844751, 844959, 845003, 846695, 846711, 846723, 851104, 851112, 851572, 851576, 851584, 851688, 851704, 851840, 851858, 851870, 852408, 853531, 853636, 853644, 853679, 853723, 853799, 854460, 854508, 854644, 854704, 855740, 855744, 856112, 856160, 856172, 856933, 857132, 860588, 861668, 862176, 862611, 864599, 864615, 864619, 864927, 864935, 864939, 865107, 865299, 867127, 867491, 868737, 868742, 869263, 869433, 870243, 870413, 870856, 873883, 874253, 874278, 874453, 874523, 874533, 874538, 874558, 874598, 875423, 875428, 875498, 876833, 877087, 877492, 877532, 877537, 878082, 878117, 878928, 879517, 880257, 880321, 881272, 881287, 881297, 882111, 882561, 883466, 883526, 884536, 885534, 885538, 885542, 885562, 886106, 886205, 890023, 890133, 890527, 891118, 891149, 891161, 891585, 891671, 891675, 892331, 892593, 893423, 893427, 894319, 895335, 895339, 895357, 895421, 895433, 896559, 896609, 896613, 896617, 896633, 897438, 897442, 899429, 899494, 900119, 900123, 900147, 900679, 900803, 900847, 901295, 901351, 901903, 902059, 902063, 902067, 902070, 902563, 904155, 904931, 905667, 905775, 905863, 905874, 905881, 906185, 906335, 906571, 908289, 909355, 911281, 911547, 912687, 913330, 914144, 914191, 914223, 914251, 914327, 915224, 915256, 915432, 915508, 915540, 915556, 916703, 916707, 916716, 916752, 917079, 917478, 917751, 917883, 917912, 918038, 918172, 918184, 918216, 918640, 918664, 918952, 918956, 920240, 920808, 920864, 921528, 922152, 922468, 922472, 922618, 922638, 922927, 923964, 924370, 924623, 924880, 926824, 926848, 926856, 926888, 926903, 926911, 926944, 927352, 929314, 930924, 932083, 932103, 932172, 932184, 932204, 932583, 932607, 932652, 932672, 932680, 932688, 932768, 932776, 933310, 933494, 934744, 934818, 935903, 936038, 936979, 937238, 937392, 938060, 938075, 938119, 938175, 938183, 938280, 938375, 938792, 938971, 939399, 939479, 939483, 939492, 939500, 940228, 940342, 940354, 941448, 941509, 941948, 942728, 943123, 943226, 943251, 943298, 944118, 944846, 945107, 945112, 945136, 945357, 946182, 948556, 948571, 948621, 949377, 949381, 949534, 949702, 951758, 951762, 951766, 952006, 954854, 955097, 956318, 957658, 958537, 958605, 958613, 958673, 958817, 958927, 959131, 959251, 959551, 959999, 960205, 960581, 960635, 961195, 961535, 961573, 961733, 962077, 962519, 962525, 962750, 962810, 962816, 963023, 963032, 963761, 963791, 963797, 964016, 964097, 964235, 964238, 964319, 964706, 964889, 964907, 965189, 965225, 965279, 965744, 967172, 967184, 967265, 967268, 968363, 968387, 968918, 969620, 969902, 970679, 970937, 971687, 971936, 972161, 973298, 973301, 973427, 973430, 973592, 973601, 973622, 973628, 974147, 974159, 974606, 975257, 977261, 977264, 977537, 978113, 978122, 978290, 978509, 978665, 979602, 979650, 979662, 980800, 980977, 981796, 981826, 982231, 982285, 982351, 982906, 982969, 983053, 983062, 983068, 983485, 983494, 983731, 984307, 984310, 984910, 985333, 985723, 986209, 986284, 986308, 986818, 987709, 989417, 989447, 989454, 989600, 989813, 991322, 991674, 991686, 991702, 992908, 992913, 992963, 994403, 994543, 995793, 995973, 995993, 996708, 996818, 996828, 996853, 996873, 996883, 998098, 999148, 1000068, 1001523, 1001733, 1002098, 1003053, 1003093, 1004447, 1004740, 1004957, 1005187, 1007163, 1007183, 1007345, 1009628, 1009688, 1009875, 1010255, 1010938, 1012478, 1013148, 1013153, 1014058, 1015095, 1015382, 1016752, 1017745, 1018527, 1018532, 1019004, 1019014, 1019029, 1019044, 1020139, 1020225, 1020344, 1020380, 1020400, 1021164, 1021892, 1021909, 1022174, 1024374, 1024398, 1024554, 1024924, 1024929, 1024934, 1025509, 1025584, 1025604, 1026127, 1026207, 1026217, 1026272, 1026369, 1027157, 1027890, 1029192, 1029869, 1030914, 1030974, 1030999, 1031039, 1031080, 1031219, 1031255, 1031270, 1031765, 1031805, 1032450, 1032512, 1034750, 1035565, 1035782, 1035850, 1037032, 1037652, 1037662, 1037667, 1037900, 1038200, 1038205, 1038260, 1039052, 1041233, 1041458, 1042243, 1042473, 1045352, 1046793, 1047222, 1047577, 1048218, 1049453, 1050118, 1050582, 1050728, 1052030, 1053033, 1053112, 1053123, 1054783, 1054933, 1054953, 1054958, 1054963, 1054973, 1054978, 1055113, 1056620, 1057293, 1057303, 1057423, 1057458, 1058435, 1059755, 1060118, 1060392, 1061327, 1061427, 1061625, 1062238, 1062630, 1063360, 1064178, 1064595, 1064605, 1064610, 1064810, 1065305, 1066097, 1067152, 1069519, 1070504, 1072394, 1073172, 1073202, 1073777, 1073779, 1074879, 1075418, 1075422, 1076243, 1076267, 1076277, 1076307, 1076452, 1077137, 1077147, 1077167, 1078083, 1078123, 1078187, 1078206, 1078416, 1080334, 1081337, 1081342, 1081347, 1081352, 1081566, 1081721, 1081726, 1081731, 1081736, 1081751, 1081756, 1081761, 1081766, 1082104, 1082114, 1082119, 1083032, 1083561, 1085276, 1085679, 1087679, 1088099, 1088219, 1088261, 1088331, 1088336, 1088341, 1088346, 1088622, 1088639, 1088664, 1088679, 1088972, 1089112, 1089116, 1089692, 1089724, 1090389, 1092232, 1093747, 1093756, 1094311, 1094336, 1094366, 1094386, 1095914, 1095974, 1099157, 1099251, 1099266, 1099281, 1099331, 1099336, 1099341, 1099439, 1100989, 1101027, 1101042, 1101072, 1101487, 1101492, 1101497, 1101502, 1101726, 1101736, 1102134, 1104279, 1105002, 1105387, 1106767, 1107049, 1107156, 1107161, 1107171, 1107812, 1107822, 1109172, 1111089, 1111828, 1111857, 1111865, 1113571, 1113596, 1114027, 1115017, 1116717, 1116722, 1119381, 1119386, 1119391, 1119396, 1121222, 1121232, 1121237, 1121263, 1121273, 1121371, 1121380, 1121701, 1121725, 1121735, 1122650, 1124031, 1124056, 1124220, 1125396, 1125912, 1125992, 1128727, 1129615, 1130377, 1130389, 1130778, 1130793, 1130953, 1131475, 1132599, 1133803, 1134929, 1134935, 1135888, 1137365, 1137566, 1137722, 1138516, 1138582, 1138588, 1138606, 1138612, 1139132, 1139138, 1139144, 1139150, 1139440, 1140800, 1140806, 1141664, 1141668, 1141676, 1141682, 1141692, 1141694, 1141700, 1141704, 1141718, 1141742, 1141748, 1141752, 1141754, 1141766, 1141772, 1141796, 1141802, 1141806, 1143294, 1144564, 1144570, 1147864, 1147980, 1148260, 1148489, 1149275, 1149280, 1149286, 1149946, 1150925, 1152260, 1152348, 1152356, 1152657, 1152773, 1152860, 1152881, 1152908, 1152912, 1152959, 1153023, 1155583, 1155587, 1155999, 1156715, 1156926, 1156930, 1156934, 1156938, 1157143, 1157425, 1158449, 1159344, 1159387, 1159395, 1159540, 1159572, 1159604, 1159628, 1159673, 1159733, 1159783, 1159803, 1160409, 1161345, 1162747, 1163139, 1163264, 1163281, 1163289, 1163553, 1164631, 1164681, 1164944, 1165392, 1165405, 1166292, 1166403, 1166915, 1166971, 1167225, 1167232, 1167465, 1167592, 1168024, 1168535, 1168859);
        foreach ($ids as $id) {
            $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id);
            $fact->checkMargin(true, true);
        }
        break;
    case 'repare_id_contrat_note':
        global $db;
        $warnings = array();
        $sql = $db->query("SELECT * FROM `llx_bimpcore_note` WHERE `id_obj` = '7345' AND `content` LIKE 'Une facture a été créée automatiqueme%';");
        while ($ln = $db->fetch_object($sql)) {
            echo $ln->content;
            $patern = '/^.*' . preg_quote('bimp8/contrat/card.php?id=', "/") . '(\d+)".*$/';
            if (preg_match($patern, $ln->content, $result))
                echo $result[1] . '|';
            else
                echo 'probléme' . $patern;

            echo '<br/>';
            $db->query('UPDATE llx_bimpcore_note SET id_obj ="' . $result[1] . '" WHERE id = ' . $ln->id);
            echo '<br/><br/><br/>';
        }
        print_r($warnings);
        break;

    case 'secteur_facture_fourn_with_commande_fourn':
        global $db;
        $sql = $db->query("SELECT c.rowid, ref, ce.type, cfe.type as newType FROM `llx_facture_fourn` c, llx_facture_fourn_extrafields ce LEFT JOIN llx_element_element ee ON ee.sourcetype = 'order_supplier' AND targettype = 'invoice_supplier' AND ee.fk_target = ce.fk_object LEFT JOIN llx_commande_fournisseur_extrafields cfe ON ee.fk_source = cfe.fk_object WHERE c.rowid = ce.fk_object AND ce.type IS null AND c.`datec` > '2019-07-01' AND cfe.type != '';");
        while ($ln = $db->fetch_object($sql)) {
            $db->query("UPDATE llx_facture_fourn_extrafields SET type = '" . $ln->newType . "' WHERE type is NULL AND fk_object =" . $ln->rowid);
        }
        break;

    case 'check_margin_commande':
        global $db;
        $sql = $db->query('SELECT c.rowid, c.marge, SUM(cd.total_ht - (cd.buy_price_ht * cd.qty)) as margeCalc FROM llx_commande c LEFT JOIN llx_commandedet cd ON cd.`fk_commande` = c.`rowid` WHERE 1 GROUP BY c.rowid HAVING SUM(cd.total_ht - (cd.buy_price_ht * cd.qty)) - c.marge > 1 || SUM(cd.total_ht - (cd.buy_price_ht * cd.qty)) - c.marge < -1');

        while ($ln = $db->fetch_object($sql)) {
            $db->query("UPDATE llx_commandedet SET test = 1 WHERE fk_commande = " . $ln->rowid);
        }
        break;
    case 'check_margin_propal':
        global $db;
        $sql = $db->query('SELECT c.rowid, c.marge, SUM(cd.total_ht - (cd.buy_price_ht * cd.qty)) as margeCalc FROM llx_propal c LEFT JOIN llx_propaldet cd ON cd.`fk_propal` = c.`rowid` WHERE 1 GROUP BY c.rowid HAVING SUM(cd.total_ht - (cd.buy_price_ht * cd.qty)) - c.marge > 1 || SUM(cd.total_ht - (cd.buy_price_ht * cd.qty)) - c.marge < -1');

        while ($ln = $db->fetch_object($sql)) {
            $db->query("UPDATE llx_propaldet SET test = 1 WHERE fk_propal = " . $ln->rowid);
        }
        break;

    case 'check_limit_client':
        $errors = array();
        $socs = BimpObject::getBimpObjectList('bimpcore', 'Bimp_Societe', array('rowid' => array('custom' => 'a.rowid IN (SELECT DISTINCT(`fk_soc`)  FROM `llx_societe_commerciaux` WHERE `fk_user` = 7)')));
        foreach ($socs as $idSoc) {
            $soc = BimpObject::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $idSoc);
            $data = array();
            $errors = BimpTools::merge_array($errors, $soc->checkSiren('siret', $soc->getData('siret'), $data));
            if (count($data) > 0) {
                $soc->set('notecreditsafe', $data['notecreditsafe']);
                //$soc->set('outstanding_limit', $data['outstanding_limit']);
                $soc->set('capital', $data['capital']);
                $soc->set('tva_intra', $data['tva_intra']);
                $soc->set('capital', $data['capital']);
                $errors = BimpTools::merge_array($errors, $soc->update());
            }
            print_r($idSoc . '<br/>');
            print_r($data);
            echo '<br/><br/>';
        }
        print_r($erros);
        break;
    case 'refresh_count_shipped':
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeLine');
        Bimp_CommandeLine::checkAllQties();
        break;
    case 'traite_obsolete':
        global $db;
        $sql = $db->query("SELECT DISTINCT (a.rowid) FROM llx_product a LEFT JOIN llx_product_extrafields ef ON a.rowid = ef.fk_object WHERE (a.stock = '0' || a.stock is null) AND a.tosell IN ('1') AND (ef.famille = 3097) ORDER BY a.ref DESC");
        while ($ln = $db->fetch_object($sql))
            $db->query("UPDATE `llx_product` SET `tosell` = 0, `tobuy` = 0 WHERE rowid = " . $ln->rowid);
        break;

    case 'correct_prod_cur_pa':
        BimpObject::loadClass('bimpcore', 'Bimp_Product');
        Bimp_Product::correctAllProductCurPa(true, true);
        break;

    case 'check_facs_paiement':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkIsPaidAll();
        break;

    case 'check_facs_paiement_rap_inf_one_euro':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkIsPaidAll(array(
            'remain_to_pay' => array(
                'and' => array(
                    array(
                        'operator' => '>',
                        'value'    => 0
                    ),
                    array(
                        'operator' => '<',
                        'value'    => 1
                    )
                )
            )
        ));
        break;

    case 'check_facs_remain_to_pay':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkRemainToPayAll(true);
        break;

    case 'check_commandes_status':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        Bimp_Commande::checkStatusAll();
        break;

    case 'check_commandes_fourn_status':
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeFourn');
        Bimp_CommandeFourn::checkStatusAll();
        break;

    case 'check_clients_solvabilite':
        BimpObject::loadClass('bimpcore', 'Bimp_Societe');
        Bimp_Societe::checkSolvabiliteStatusAll();
        break;

    case 'change_prods_refs':
        $bdb = new BimpDb($db);
        $lines = file(DOL_DOCUMENT_ROOT . '/bimpcore/convert_file.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        foreach ($lines as $line) {
            $data = explode(':', $line);

            if ($data[0] === $data[1]) {
                continue;
            }

            if ($bdb->update('product', array(
                        'ref' => $data[1]
                            ), 'ref = \'' . $data[0] . '\'') < 0) {
                echo 'ECHEC ' . $data[0];
            } else {
                echo 'OK ' . $data[1];
            }

            echo '<br/>';
        }
        break;

    case 'check_factures_rg':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkRemisesGlobalesAll(true, true);
        break;

    case 'cancel_factures':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::cancelFacturesFromRefsFile(DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/factures_to_cancel.txt', true);
        break;

    case 'convert_user_configs':
        if (!(int) BimpCore::getConf('old_user_configs_converted', 0)) {
            echo 'CONVERSION DES FILTRES ENREGISTRES: <br/><br/>';
            $new_filters = convertFiltersConfigs();

            echo '<br/><br/>CONVERSION DES CONFIGS DE LISTE: <br/><br/>';
            convertListsConfigs($new_filters);

            BimpCore::setConf('old_user_configs_converted', 1);
        }
        break;

    case 'check_list_table_configs':
        BimpObject::loadClass('bimpuserconfig', 'ListTableConfig');

        $exec = (int) BimpTools::getValue('exec', 0);

        if (!$exec) {
            $path = pathinfo(__FILE__);
            echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=check_list_table_configs&exec=1" class="btn btn-default">';
            echo 'effectuer les corrections';
            echo '</a>';
            echo '<br/><br/>';
        }

        ListTableConfig::checkAll(true, $exec);
        break;

    case 'check_stocks_mouvements':
        $date_min = BimpTools::getValue('date_min', '');
        $date_max = BimpTools::getValue('date_max', date('Y-m-d H:i:s'));
        $id_product = BimpTools::getValue('id_product', 0);

        if (!$date_min) {
            echo BimpRender::renderAlerts('Indiquer date_min et date_max dans l\'url', 'info');
        } else {
            BimpObject::loadClass('bimpcore', 'BimpProductMouvement');
            BimpProductMouvement::checkMouvements($date_min, $date_max, true, $id_product);
        }
        break;

    case 'check_facs_margin':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        $errors = Bimp_Facture::checkMarginAll();
        if (count($errors)) {
            echo BimpRender::renderAlerts($errors);
        } else {
            echo '<span class="success">Aucune erreur</span>';
        }
        break;

    case 'change_sn':
        $sql = $db->query("SELECT a.serial, a.id FROM " . MAIN_DB_PREFIX . "be_equipment a LEFT JOIN llx_be_equipment_place a___places ON a___places.id_equipment = a.id WHERE (a___places.infos LIKE '%FV202000952549%' ESCAPE '$')");
        $i = 0;
        $tabNew = array("DMPDKFVCQ1GC", "DMPDKYZYQ1GC", "DMQDK05PQ1GC", "DMQDKDAPQ1GC", "DMQDKDS9Q1GC", "DMQDKERUQ1GC", "DMPDKKZPQ1GC", "DMPDKPX4Q1GC", "DMPDKRLWQ1GC", "DMPDKTDBQ1GC", "DMPDKTNWQ1GC", "DMPDKW79Q1GC", "DMPDKWM1Q1GC", "DMPDKY3NQ1GC", "DMQDK0CVQ1GC", "DMQDK0NAQ1GC", "DMQDK2FVQ1GC", "DMQDK6JYQ1GC", "DMQDK6VVQ1GC", "DMQDK72BQ1GC", "DMQDK9EBQ1GC", "DMQDK9Z3Q1GC", "DMQDKA3VQ1GC", "DMQDKBQ1Q1GC", "DMQDKBS2Q1GC", "DMQDKBSCQ1GC", "DMQDKCF3Q1GC", "DMQDKCVBQ1GC", "DMQDKCVMQ1GC", "DMQDKD5QQ1GC", "DMQDKDPDQ1GC", "DMQDKDSPQ1GC", "DMQDKENUQ1GC", "DMQDKEQEQ1GC", "DMQDKHC0Q1GC", "DMQDKHX8Q1GC", "DMPDKSUMQ1GC", "DMPDKWSGQ1GC", "DMPDKX2KQ1GC", "DMPDKY3EQ1GC", "DMPDKYQ3Q1GC", "DMPDKZY7Q1GC", "DMQDK09WQ1GC", "DMQDK0BHQ1GC", "DMQDK2HHQ1GC", "DMQDK3G6Q1GC", "DMQDK3QLQ1GC", "DMQDK5ZXQ1GC", "DMQDK5ZZQ1GC", "DMQDK681Q1GC", "DMQDK6KDQ1GC", "DMQDK7YBQ1GC", "DMQDK7YNQ1GC", "DMQDK87DQ1GC", "DMQDK87JQ1GC", "DMQDK8SYQ1GC", "DMQDK8YTQ1GC", "DMQDKAD2Q1GC", "DMQDKALCQ1GC", "DMQDKC4YQ1GC", "DMQDKC67Q1GC", "DMQDKCXNQ1GC", "DMQDKEUDQ1GC", "DMQDKJDVQ1GC", "DMQDKJQEQ1GC", "DMQDKLPWQ1GC", "DMQDK47RQ1GC", "DMQDK2UZQ1GC", "DMPDKWQJQ1GC", "DMPDKQUBQ1GC");
        while ($ln = $db->fetch_object($sql)) {
            global $dolibarr_main_url_root;
            die($dolibarr_main_url_root);
//           $db->query("UPDATE ".MAIN_DB_PREFIX."be_equipment SET serial = '".$tabNew[$i]."' WHERE serial = '".$ln->serial."' AND id = ".$ln->id.";");
            $i++;
        }
        break;

    case 'correct_sav_dates_rdv':
        BimpObject::loadClass('bimpsupport', 'BS_SAV');
        BS_SAV::correctDateRdvAll(true);
        break;

    case 'correct_tickets_serials':
        BimpObject::loadClass('bimpsupport', 'BS_Ticket');
        BS_Ticket::correctSerialsAll(true);
        break;

    case 'convert_fi':
        BimpObject::loadClass('bimptechnique', 'BT_ficheInter');
        BT_ficheInter::convertAllNewFi();
        break;

    case 'convert_sql_field_for_items_braces':
        $table = 'societe_extrafields';
        $primary = 'rowid';
        $field = 'marche';
        $delimiter = ',';

        $where = $field . ' IS NOT NULL AND ' . $field . ' != \'\'';
        $bdb = new BimpDb($db);
        $rows = $bdb->getRows($table, $where, null, 'array', array($primary, $field));

        echo 'NB Lignes: ' . count($rows) . '<br/>';
        if (is_array($rows)) {
            foreach ($rows as $r) {
                if (strpos($r[$field], $delimiter) !== false) {
                    continue;
                }
                $values = explode($delimiter, $r[$field]);

                $new_val = '';

                foreach ($values as $val) {
                    $new_val .= '[' . $val . ']';
                }

                if ($new_val != $r[$field]) {
                    $bdb->update($table, array(
                        $field => $new_val
                            ), $primary . ' = ' . $r[$primary]);
                }
            }
        }

        break;

    case 'checkLinesEcheances':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        Bimp_Commande::checkLinesEcheances();
        break;

    default:
        echo 'Action invalide';
        break;
}

echo '<br/>FIN';
echo '</body></html>';

// FONCTIONS: 


function convertListsConfigs($new_filters = array())
{
    global $db;

    $bdb = new BimpDb($db);
    $rows = $bdb->getRows('bimpcore_list_config', 1, null, 'array');

    foreach ($rows as $r) {
        $data = array(
            'name'               => $r['name'],
            'owner_type'         => $r['owner_type'],
            'id_owner'           => $r['id_owner'],
            'id_user_create'     => ($r['owner_type'] === 2 ? (int) $r['id_owner'] : 0),
            'is_default'         => $r['is_default'],
            'obj_module'         => $r['obj_module'],
            'obj_name'           => $r['obj_name'],
            'component_name'     => $r['list_name'],
            'sort_field'         => $r['sort_field'],
            'sort_option'        => $r['sort_option'],
            'sort_way'           => $r['sort_way'],
            'nb_items'           => $r['nb_items'],
            'total_row'          => $r['total_row'],
            'active_filters'     => $r['active_filters'],
            'id_default_filters' => (isset($new_filters[(int) $r['id_default_filters']]) ? $new_filters[(int) $r['id_default_filters']] : 0)
        );

        echo '#' . $r['id'] . ': ';

        switch ($r['list_type']) {
            case 'list_table':
                $data['search_open'] = $r['search_open'];
                $data['filters_open'] = $r['filters_open'];
                $data['sheet_name'] = $r['sheet_name'];

                $instance = BimpObject::getInstance($r['obj_module'], $r['obj_name']);

                if (is_a($instance, 'BimpObject')) {
                    $list_name = $r['list_name'];
                    $new_cols = array();
                    $cols = explode(',', $r['cols']);
                    $cols_options = json_decode($r['cols_options'], 1);

                    foreach ($cols as $col_name) {
                        $list_path = 'lists/' . $list_name . '/cols/' . $col_name . '/';
                        $col_path = 'lists_cols/' . $col_name . '/';
                        $field = $instance->getConf($list_path . 'field', $instance->getConf($col_path . 'field', ''));
                        $child = $instance->getConf($list_path . 'child', $instance->getConf($col_path . 'child', ''));
                        $label = BimpTools::getArrayValueFromPath($cols_options, $col_name . '/label', $instance->getConf($list_path . 'label', $instance->getConf($col_path . 'label', '')));

                        if (!$label && $field) {
                            if ($child) {
                                $child_obj = $instance->getChildObject($child);
                                if (is_a($child_obj, 'BimpObject')) {
                                    if ($child_obj->field_exists($field)) {
                                        $label = $child_obj->getConf('fields/' . $field . '/label', $col_name);
                                    }
                                }
                            } else {
                                if ($instance->field_exists($field)) {
                                    $label = $instance->getConf('fields/' . $field . '/label', $col_name);
                                }
                            }
                        }
                        $new_col_name = '';
                        if ($field) {
                            if ($child) {
                                $new_col_name = $child . ':';
                            }
                            $new_col_name .= $field;
                        } else {
                            $new_col_name = $col_name;
                        }

                        $new_cols[$new_col_name] = array(
                            'label'      => $label,
                            'csv_option' => BimpTools::getArrayValueFromPath($cols_options, $col_name . '/csv_display', '')
                        );
                    }

                    $data['cols'] = json_encode($new_cols);
                } else {
                    $data['cols'] = '';
                }

                if ($bdb->insert('buc_list_table_config', $data) <= 0) {
                    echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
                } else {
                    echo '<span class="success">[OK]</span>';
                }
                break;

            case 'stats_list':
                $data['cols'] = $r['cols'];

                if ($bdb->insert('buc_stats_list_config', $data) <= 0) {
                    echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
                } else {
                    echo '<span class="success">[OK]</span>';
                }
                break;

            default:
                echo '<span class="danger">TYPE INCONNU: ' . $r['list_type'] . '</span>';
        }

        echo '<br/>';
    }
}

function convertFiltersConfigs()
{
    global $db;
    $new_filters = array();

    $bdb = new BimpDb($db);
    $rows = $bdb->getRows('bimpcore_list_filters', 1, null, 'array');

    foreach ($rows as $r) {
        echo '#' . $r['id'] . ': ';
        $data = array(
            'name'           => $r['name'],
            'owner_type'     => $r['owner_type'],
            'id_owner'       => $r['id_owner'],
            'is_default'     => 0,
            'id_user_create' => ((int) $r['id_user_create'] ? (int) $r['id_user_create'] : ((int) $r['owner_type'] == 2 ? (int) $r['id_owner'] : 0)),
            'obj_module'     => $r['obj_module'],
            'obj_name'       => $r['obj_name']
        );

        $obj = BimpObject::getInstance($r['obj_module'], $r['obj_name']);

        if (is_a($obj, 'BimpObject')) {
            $filters = array();
//            $new_filters = array();

            $incl = json_decode($r['filters'], 1);
            $excl = json_decode($r['excluded'], 1);

            if (isset($incl['fields']) && !empty($incl['fields'])) {
                foreach ($incl['fields'] as $filter_name => $values) {
                    if (!isset($filters[$filter_name])) {
                        $filters[$filter_name] = array();
                    }

                    $filters[$filter_name]['values'] = $values;
                }
            }

            if (isset($excl['fields']) && !empty($excl['fields'])) {
                foreach ($excl['fields'] as $filter_name => $values) {
                    if (!isset($filters[$filter_name])) {
                        $filters[$filter_name] = array();
                    }

                    $filters[$filter_name]['excluded_values'] = $values;
                }
            }

            if (isset($incl['children']) && !empty($incl['children'])) {
                foreach ($incl['children'] as $child_name => $child_filters) {
                    foreach ($child_filters as $name => $values) {
                        $filter_name = $child_name . ':' . $name;
                        if (!isset($filters[$filter_name])) {
                            $filters[$filter_name] = array();
                        }
                        $filters[$filter_name]['values'] = $values;
                    }
                }
            }

            if (isset($excl['children']) && !empty($excl['children'])) {
                foreach ($excl['children'] as $child_name => $child_filters) {
                    foreach ($child_filters as $name => $values) {
                        $filter_name = $child_name . ':' . $name;

                        if (!isset($filters[$filter_name])) {
                            $filters[$filter_name] = array();
                        }

                        $filters[$filter_name]['excluded_values'] = $values;
                    }
                }
            }
//
//            $filter_path_base = 'filters_panel/' . $r['panel_name'] . '/filters/';
//
//            foreach ($filters as $filter_name => $values) {
//                $new_filter_name = '';
//                $filter_path = $filter_path_base . $filter_name / '/';
//
//                $field = $obj->getConf($filter_path . 'field');
//                $child = $obj->getConf($filter_path . 'child');
//
//                if ($field) {
//                    if ($child) {
//                        $new_filter_name .= $child . ':';
//                    }
//                    $new_filter_name .= $field;
//                } else {
//                    $new_filter_name = $filter_name;
//                }
//
//                $new_filters[$new_filter_name] = $values;
//            }

            $data['filters'] = json_encode($filters);

            $id_new_filter = $bdb->insert('buc_list_filters', $data, true);

            if ($id_new_filter <= 0) {
                echo '<span class="danger">[ECHEC] - ' . $bdb->err() . '</span>';
            } else {
                echo '<span class="success">[OK]</span>';

                $new_filters[(int) $r['id']] = $id_new_filter;
            }
        } else {
            echo '<span class="danger">INSTANCE INVALIDE</span>';
        }

        echo '<br/>';
    }

    return $new_filters;
}
