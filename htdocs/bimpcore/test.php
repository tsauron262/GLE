<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$refs = array(
    'ACC-ACCOMPROJET_CASSBECOL',
    'ACC-PROJET',
    'Assistance Globale',
    'BIM-CONTRAT-ASSIST-ECOLE-POSTE',
    'BIMP-VIDEO_MARKETING',
    'SERV-ACCES-HOTLINE',
    'SERV-BIMP-5ANS-TS-853BU-RP-4G',
    'SERV-BIMPEDUC',
    'SERV-BTOYCONT',
    'SERV-BTOYCONT1',
    'SERV-BTOYFD26',
    'SERV-BTOYFD42',
    'SERV-BTOYFD69',
    'SERV-CA-GLOBALE',
    'SERV-CA-LOGICIEL',
    'SERV-CD1',
    'SERV-CD11',
    'SERV-CWX2',
    'SERV-CX2',
    'SERV-CX3',
    'SERV-DOMGANDI',
    'SERV-FD0',
    'SERV-FD01',
    'SERV-FDBOUT25',
    'SERV-FDBOUT251',
    'SERV-FDBOUT26',
    'SERV-FDBOUT38',
    'SERV-FDBOUT42',
    'SERV-FDBOUT69',
    'SERV-FDBOUT690',
    'SERV-FDBOUT691',
    'SERV-FDBOUT693',
    'SERV-FDBOUT74',
    'SERV-FO-OM4',
    'SERV-FORI-BUR1',
    'SERV-FORI-BUR2',
    'SERV-FORI-BURTH',
    'SERV-FORI-ILIFE',
    'SERV-FORI-IT',
    'SERV-FORI-ITC',
    'SERV-FORI-MED1/2',
    'SERV-FORI-OSX',
    'SERV-FORI-PAODF',
    'SERV-FORI-PAOGF',
    'SERV-FORX-ARCHINO',
    'SERV-FORX-BURBEYSSAC',
    'SERV-FORX-BURJFM',
    'SERV-FORX-BURMB',
    'SERV-FORX-BURMF',
    'SERV-FORX-DAOCMA',
    'SERV-FORX-DAOEM',
    'SERV-FORX-DAOID',
    'SERV-FORX-DF',
    'SERV-FORX-DOAACAST',
    'SERV-FORX-FRAISDEP',
    'SERV-FORX-GESTPT',
    'SERV-FORX-PAODIV',
    'SERV-FORX-PREST',
    'SERV-GESTION-CONTRAT',
    'SERV-GESTION-LICENCE',
    'SERV-GPRS',
    'SERV-GPRS-1M',
    'SERV-GPRS-2M',
    'SERV-GPRS-3M',
    'SERV-GRAVAGEIPAD',
    'SERV-HEBEROVH1',
    'SERV19-ASS',
    'SERV19-CA1',
    'SERV19-CA2',
    'SERV19-CA3',
    'SERV19-CAPP',
    'SERV19-CHARGE',
    'SERV19-CMI1',
    'SERV19-CMI2',
    'SERV19-CMI3',
    'SERV19-CMI3-STANLEY',
    'SERV19-CONS',
    'SERV19-CPM1',
    'SERV19-CPM2',
    'SERV19-CSM0',
    'SERV19-DP1',
    'SERV19-DP2',
    'SERV19-DP3',
    'SERV19-FD01',
    'SERV19-FD02',
    'SERV19-FD03',
    'SERV19-FD04',
    'SERV19-FD05',
    'SERV19-FORI-ARCHI',
    'SERV19-FORI-GEN',
    'SERV19-FORI-MED-05J',
    'SERV19-FORX-ARCHI-05J',
    'SERV19-FORX-ARCHI-PERF-1J',
    'SERV19-FORX-BUREAU-05J',
    'SERV19-FORX-BUREAU-INI-1J',
    'SERV19-FORX-BUREAU-PERF-1J',
    'SERV19-FORX-CAODIVINTER',
    'SERV19-FORX-CC-INTER',
    'SERV19-FORX-CPF-365',
    'SERV19-FORX-GEN-05J',
    'SERV19-FORX-GEN-INI-1J',
    'SERV19-FORX-GEN-INI-H',
    'SERV19-FORX-GEN-PERF-1J',
    'SERV19-FORX-GEN-PERF-H',
    'SERV19-FORX-GESTION-05J',
    'SERV19-FORX-GESTION-INI-1J',
    'SERV19-FORX-GESTION-PERF-1J',
    'SERV19-FORX-PAO-05J',
    'SERV19-FORX-PAO-INI-1J',
    'SERV19-FORX-PAO-PERF-1J',
    'SERV19-FORX-VIDEO-MEDIA-05J',
    'SERV19-FORX-VIDEO-MEDIA-INI-1J',
    'SERV19-FORX-VIDEO-MEDIA-PERF-1J',
    'SERV19-FPR-2',
    'SERV19-FPR-3',
    'SERV19-FPR-ATE',
    'SERV19-FPR-ATE/TELE1',
    'SERV19-FPR-ATE/TELE2',
    'SERV19-FPR-ATE/TELE3',
    'SERV19-FPR-TELE',
    'SERV19-MAJ-WEEK',
    'SERV19-PREP-MDM',
    'SERV19-SPARE',
    'SERV19-SPARE-J1',
    'SERV19-SPARE2',
    'SERV21-ASS',
    'SERV21-CONS',
    'SERV22-DPI-AAPEI'
);

$w = array();
foreach ($refs as $ref) {
    echo '<br/>' . $ref . ' : ';

    $p = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                'ref' => $ref
    ));

    if (!BimpObject::objectLoaded($p)) {
        echo '<span class="danger">';
        echo 'NON TROUVE';
        echo '</span>';
        continue;
    }
    
    echo $p->getLink() .' - ';


    $p->set('tosell', 0);
    $p->set('tobuy', 0);

    $err = $p->update($w, true);

    if (!empty($err)) {
        echo BimpRender::renderAlerts($err);
    } else {
        echo 'OK';
    }
    
//    break;
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
