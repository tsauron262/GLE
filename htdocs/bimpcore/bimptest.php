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


$sql = BimpTools::getSqlFullSelectQuery('object_line_equipment', array('DISTINCT a.id_equipment', 'e.serial', 'p.ref as ref_prod', 'p.label', 'f.datef', 'f.ref as ref_fac'), array(
            'a.object_type' => 'facture',
            'or_soc'        => array(
                'or' => array(
                    'f.id_client_final' => 142,
                    'f.fk_soc'          => 142
                )
            ),
            'f.datef'       => array(
                'min' => '2021-09-01'
            ),
            'or_prod'       => array(
                'or' => array(
                    'p.label'         => array(
                        'part_type' => 'middle',
                        'part'      => 'mac'
                    ),
                    'e.product_label' => array(
                        'part_type' => 'middle',
                        'part'      => 'mac'
                    )
                )
            )
                ), array(
            'e'  => array(
                'table' => 'be_equipment',
                'on'    => 'a.id_equipment = e.id'
            ),
            'fl' => array(
                'table' => 'bimp_facture_line',
                'on'    => 'fl.id = a.id_object_line'
            ),
            'f'  => array(
                'table' => 'facture',
                'on'    => 'f.rowid = fl.id_obj'
            ),
            'p'  => array(
                'table' => 'product',
                'on'    => 'p.rowid = e.id_product'
            )
        ));

$rows = $bdb->executeS($sql, 'array');

$str = '"N°de série";"Réf. produit";"Libellé produit";"Réf. facture";"Date facture"';
foreach ($rows as $r) {
    $str .= "<br/>" . '"' . $r['serial'] . '";"' . $r['ref_prod'] . '";"' . $r['label'] . '";"' . $r['ref_fac'] . '";"' . date('d / m / Y', strtotime($r['datef'])) . '"';
}

echo $str;

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
