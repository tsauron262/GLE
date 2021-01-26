<?php

class controlleController extends BimpController {

    private $dir = DIR_SYNCH . 'exportCegid/BY_DATE/';

    public function renderHtml() {
        
        if(isset($_REQUEST['user'])) {
            global $db;
            $bimp = new BimpDb($db);
            $list_of_user_actif = $bimp->getRows('user', 'statut > 0', 300, 'object', null, "lastname", "ASC");
            echo '"NOM", "PRENOM", "EMAIL", "RESPONSABLE";' . "<br />";
            foreach($list_of_user_actif as $user) {
                $responsable = $bimp->getRow('user', 'rowid = ' . $user->fk_user);
                echo "\"$user->lastname \"; \"$user->firstname\";\"$user->email\"; \"$responsable->lastname $responsable->firstname\"; " . "</br >";
            }
        }
        
        if (isset($_REQUEST['tra']) && $_REQUEST['tra']) {
            set_time_limit(100);
            $tra = $this->dir . $_REQUEST['tra'];
            if (file_exists($tra)) {
                $extention = substr($tra, strlen($tra) - 3);
                if ($extention == 'tra') {
                    if (filesize($tra) > 148) {
                        $for_version = explode('_', $_REQUEST['tra']);
                        if($for_version[4] == BimpCore::getConf('BIMPTOCEGID_version_tra') . '.tra') {
                            $this->controlle(file($tra));
                        } else {
                            echo BimpRender::renderAlerts("Le fichier <b>" . $_REQUEST['tra'] . "</b> n'est pas en version " . BimpCore::getConf('BIMPTOCEGID_version_tra'), 'danger', false);
                        }
                    } else {
                        echo BimpRender::renderAlerts("Le fichier <b>" . $_REQUEST['tra'] . "</b> ne comporte aucune écriture", 'danger', false);
                    }
                } else {
                    echo BimpRender::renderAlerts("L'extention <b>" . $extention . "</b> n'est pas pris en charge par le module", 'danger', false);
                }
            } else {
                echo BimpRender::renderAlerts("Le fichier <b>" . $_REQUEST['tra'] . "</b> n'existe pas", 'danger', false);
            }
        } else {
            echo BimpRender::renderAlerts("Il n'y à pas de fichier TRA à controller", 'warning', false);
        }
    }

    private function controlle($tra) {

        $array = [];
        $is_paiement = false;
        $last_ref = "";
        foreach ($tra as $line_num => $line) {
            
            $id_piece = '';
            $montant_ligne = '';
            $char_id = [151, 8];
            $char_tt = [131, 20];
            $sens = $line[129];
            $inverse = false;
            if ($line_num > 0) {
            if($line[0] != "V" && $line[0] != "A") {
                
                $is_paiement = true;
                $ref_paiement = $line[48] . $line[49] . $line[50] . $line[51] . $line[52] . $line[53] . $line[54] . $line[55] . $line[56] . $line[57] . $line[58] . $line[59] . $line[60] . $line[61];
                $ref_paiement = str_replace(" ", "", $ref_paiement);
                
                if($ref_paiement != $last_ref) {
                    echo '"' . $ref_paiement . '",<br />';
                }

                $last_ref = $ref_paiement;
                $paiements[$ref_paiement]["id"] = $pay->id;
               
            }
if(!$is_paiement){
// Test du 30èm caractère
//    echo '<pre>';
//    echo $line[30] . '<br />';
//    echo '</pre>';
    
                $line = trim($line);
                for ($i = 0; $i < $char_id[1]; $i++) {
                    $id_piece .= $line[$char_id[0] + $i];
                }
                for ($i = 0; $i < $char_tt[1]; $i++) {
                    $montant_ligne .= $line[$char_tt[0] + $i];
                }
                if ($line[30] == "X") {
                    $sens_piece = $sens;
                    $array[intval($id_piece)]['CONTROLLE_AUXILIAIRE'] = $line[31];
                    $array[intval($id_piece)]['TYPE_EXPORT'] = $line[0];
                    $array[intval($id_piece)]['LIGNE_FACTURE'] = $line_num + 1;
                    $array[intval($id_piece)]['TTC'] = doubleval($montant_ligne);
                } elseif ($line[30] == " ") {
                    $array[intval($id_piece)]['DETAILS_LIGNE']['NOMBRE'] ++;
                    $array[intval($id_piece)]['DETAILS_LIGNE'][intval(substr($line, 13))] = doubleval($montant_ligne);
                    if ($line[0] == 'A') {
                        if (intval(substr($line, 13)) == intval("44571100") || intval(substr($line, 13)) == intval("44566600")) {
                            $array[intval($id_piece)]['AUTO_LIQUIDE'] += doubleval($montant_ligne);
                        } else {
                            $array[intval($id_piece)]['HT'] += doubleval($montant_ligne);
                        }
                    } else {
                        $array[intval($id_piece)]['HT'] += doubleval($montant_ligne);
                    }
                }
            }
        }
        $color = 'style="color:#EF7D00"';
        $nombre_ecart = 0;
        $array_ecart = [];
        $array_auxil = [];
        $message = '<h2><b>BIMP<span ' . $color . ' >to</span>CEGID</b><sup style="font-size:10px"><i>Rapport de controlle de fichier</i></sup></h2>';
        $message .= '<b style="color:lightgrey">Ce mail est expedié automatiquement du module BIMPtoCEGID</b><br />------------------------------';
        $message .= '<br /><b>Nom du fichier traité : </b><i ' . $color . '>' . $_REQUEST['tra'] . '</i>';
        $message .= '<br /><b>Nombre de facture dans le fichier : </b><i ' . $color . '>' . count($array) . '</i>';
        foreach ($array as $id_piece => $infos) {
            if (abs(round($infos['TTC'] - $infos['HT'], 2))) {
                $nombre_ecart++;
                $array_ecart[$id_piece]['LIGNE'] = $infos['LIGNE_FACTURE'];
                $array_ecart[$id_piece]['TYPE'] = $infos['TYPE_EXPORT'];
                $array_ecart[$id_piece]['MONTANT'] = round($infos['TTC'] - $infos['HT'], 2);
                $array_ecart[$id_piece]['DETAILS'] = $infos['DETAILS_LIGNE'];
            }
            
            if(empty($infos['CONTROLLE_AUXILIAIRE']) && $infos['CONTROLLE_AUXILIAIRE'] != "0") {
                $array_auxil[$id_piece]['LIGNE'] = $infos['LIGNE_FACTURE'];
                $array_auxil[$id_piece]['TYPE'] = $infos['TYPE_EXPORT'];
            }
            
        }

        $message .= '<br /><b>Nombre de facture en ecart : </b><i ' . $color . '>' . $nombre_ecart . '</i>';
        $message .= '<br />------------------------------<br />';
        $message .= "<h3>Liste des factures en écart</h3>";
//        echo '<pre>';
//        print_r($array_ecart);
        if (count($array_ecart)) {
            foreach ($array_ecart as $id_piece => $infos) {
                if ($infos['TYPE'] == 'V') {
                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', $id_piece);
                } elseif ($infos['TYPE'] == 'A') {
                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn', $id_piece);
                }


                if ($facture->isLoaded()) {
                    $affichage_facture = $facture->getNomUrl();
                } else {
                    $affichage_facture = '<a style="text-decoration:none" href="' . DOL_URL_ROOT . '/bimpcommercial/?fc=facture&id=' . $id_piece . '">' . $id_piece . '</a>';
                }


                $message .= '<b>Facture : <i ' . $color . ' >' . $affichage_facture . '</i></b><br />';
                $message .= '<b>Ligne fichier TRA : <i ' . $color . ' >#' . $infos['LIGNE'] . '</i></b><br />';
                $message .= '<b>Montant de l\'écart : <i ' . $color . ' >' . $infos['MONTANT'] . '€</i></b><br />';
            }
        } else {
            $message .= "<b " . $color . ">Fichier comforme à l'export</b>";
        }
        $message .= "<h3>Liste des factures sans code auxiliaire</h3>";
        if (count($array_auxil)) {
            foreach ($array_auxil as $id_piece => $infos) {
                if ($infos['TYPE'] == 'V') {
                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', $id_piece);
                } elseif ($infos['TYPE'] == 'A') {
                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureFourn', $id_piece);
                }


                if ($facture->isLoaded()) {
                    $affichage_facture = $facture->getNomUrl();
                } else {
                    $affichage_facture = '<a style="text-decoration:none" href="' . DOL_URL_ROOT . '/bimpcommercial/?fc=facture&id=' . $id_piece . '">' . $id_piece . '</a>';
                }


                $message .= '<b>Facture : <i ' . $color . ' >' . $affichage_facture . '</i></b><br />';
                $message .= '<b>Ligne fichier TRA : <i ' . $color . ' >#' . $infos['LIGNE'] . '</i></b><br />';
            }
        } else {
            $message .= "<b " . $color . ">Fichier comforme à l'export</b>";
        }
        }
        echo "<pre>";
        print_r($paiements);
        //mailSyn2('Rapport de controller de fichier', 'al.bernard@bimp.fr', 'al.bernard@bimp.fr', $message);
        echo $message;
    }

}
