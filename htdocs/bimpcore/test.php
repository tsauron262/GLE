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

BimpObject::loadClass('bimpcore', 'Bimp_Product');

$sql = BimpTools::getSqlFullSelectQuery('bimp_propal_line', array('id'), array(
            'or_abo'    => array(
                'or' => array(
                    'a.id_linked_contrat_line' => array('operator' => '>', 'value' => 0),
                    'a.abo_nb_units'           => 0,
                )
            ),
            'pef.type2' => Bimp_Product::$abonnements_sous_types
                ), array(
            'pdet' => array(
                'table' => 'propaldet',
                'on'    => 'pdet.rowid = a.id_line'
            ),
            'pef'  => array(
                'table' => 'product_extrafields',
                'on'    => 'pef.fk_object = pdet.fk_product'
            )
        ));

echo $sql;

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
