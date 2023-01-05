<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK AVOIRS FACTURES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo 'DEBUT <br/><br/>';

global $db;
$bdb = new BimpDb($db);

$sql = 'SELECT a.`rowid` as ida, f.rowid as idf FROM ' . MAIN_DB_PREFIX . 'facture a';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture f on a.fk_facture_source = f.rowid';
$sql .= ' WHERE a.fk_facture_source > 0';
$sql .= ' AND f.fk_statut > 0 && a.fk_statut > 0';
$sql .= ' AND a.type = 2 AND f.type = 0';
$sql .= ' ORDER BY a.rowid desc';

echo $sql . '<br/><br/>';

$rows = $bdb->executeS($sql, 'array');

echo count($rows) . ' avoirs <br/><br/>';

$n = 0;
foreach ($rows as $r) {
    $av = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['ida']);

    if (BimpObject::objectLoaded($av)) {
        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['idf']);

        if (BimpObject::objectLoaded($fac)) {
            $rtp_av = (float) $av->getRemainToPay();
            $rtp_fac = (float) $fac->getRemainToPay();

            if ($rtp_fac > 0) {
                echo 'Avoir #' . $r['ida'] . ' - Facture #' . $r['idf'] . ' <br/>';
                echo 'RAP AV: ' . $rtp_av . ' - fac: ' . $rtp_fac . '<br/>';
                $n++;
                // Une remise a-t-elle était créée? 
                $remise = new DiscountAbsolute($db);
                $remise->fetch(0, $av->id);
                if (!empty($remise->id)) {
                    echo 'Remise déjà créée: ' . $remise->amount_ttc . '<br/>';

                    if ($remise->fk_facture || $remise->fk_facture_line) {
                        echo '<span class="warning">REMISE DEJA CONSOMMEE</span><br/><br/>';
                        continue;
                    } else {
                        if (round($remise->amount_ttc, 2) != round($rtp_fac, 2)) {
                            echo '<span class="warning">La remise ne correspond pas au rap de la facture</span><br/><br/>';
                            continue;
                        }
                    }
                } else {
                    unset($remise);
                    if (round($rtp_av, 2) != -(round($rtp_fac, 2))) {
                        echo '<span class="warning">La rap de l\'avoir ne correspond pas au rap de la facture</span><br/><br/>';
                        continue;
                    } else {
                        echo 'Conversion du RAP de l\'avoir en remise: ';
                        $err = $av->convertToRemise();

                        if (count($err)) {
                            echo BimpRender::renderAlerts($err);
                        } else {
                            $remise = new DiscountAbsolute($db);
                            $remise->fetch(0, $av->id);

                            if (!BimpObject::objectLoaded($remise)) {
                                echo '<span class="danger">REMISE CREEE NON TROUVEE</span>';
                                unset($remise);
                            } else {
                                echo '<span class="success">OK</span><br/>';
                            }
                        }
                    }
                }

                if (BimpObject::objectLoaded($remise)) {
                    echo 'APPLICATION DE LA REMISE: ';
                    if ($remise->fk_facture || $remise->fk_facture_line) {
                        echo '<span class="warning">REMISE DEJA CONSOMMEE</span><br/>';
                    } elseif ($remise->link_to_invoice(0, $fac->id) <= 0) {
                        BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($remise), 'Echec'));
                    } else {
                        echo '<span class="success">OK</span>';
                    }
                }

                $av->checkIsPaid();
                $fac->checkIsPaid();

                echo '<br/><br/>';
            }
        }
    }
}

echo $n . ' facs traitées';
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

