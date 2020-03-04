<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);

global $db, $langs;

//require_once '../pdf/classes/LoyerPDF.php';
//$obj = new Propal($db);
//$obj->fetch(92608);
//$pdf = new PropalPDF($db);
//$pdf = new LoyerPDF($db);
//$pdf->init($obj);
//$pdf->render(DOL_DATA_ROOT . '/propal.pdf', true);
//require_once '../pdf/classes/InvoicePDF.php';
//$obj = new Facture($db);
//$obj->fetch(86570);
//$pdf = new InvoicePDF($db);
//$pdf->init($obj);
//$pdf->render(__DIR__. '/testFacture.pdf', true);
//require_once '../pdf/classes/OrderFournPDF.php';
//
//$pdf = new OrderFournPDF($db);
//
//$obj = new CommandeFournisseur($db);
//$obj->fetch(486);
//$pdf = new OrderFournPDF($db);
//$pdf->init($obj);
//$pdf->render(DOL_DATA_ROOT . '/testCommandeFourn.pdf', true);


require_once '../pdf/classes/RelancePaiementPDF.php';

$pdf = new RelancePaiementPDF($db);
$client = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', 335849);

$data = array(
    'relance_idx' => 4,
    'solde_ttc'   => 1500, 72,
    'factures'    => array(
        225386 => BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', 225386)
    ),
    'rows'        => array(
        array(
            'date'     => '31 / 01 / 2020',
            'fac'      => 'FAR2001-00004',
            'comm'     => 'FAR2001-00004',
            'lib'      => 'TEST',
            'debit'    => '100 €',
            'credit'   => '250 €',
            'echeance' => '02 / 02 / 2020'
        )
    )
);

$pdf->client = $client;
$pdf->data = $data;

$pdf->render(DOL_DATA_ROOT . '/testRelancePaiement.pdf', true);

