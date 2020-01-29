<?php

//if (! defined('NOLOGIN'))        define('NOLOGIN','1');
require("../../main.inc.php");

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";

llxHeader();

set_time_limit(5000000);
ini_set('memory_limit', '1024M');

$loadEquip = false;
$loadSav = false;


$OK= 0;

$newErrors = array("pas d'erreur");

define('DONT_CHECK_SERIAL', true);

/*
 * TOTU
 * Ajouter index note a equipement
 * aj index id_equipment in place
 * Activé valid commande
 * support
 * des   chrono    prosses
 * 
 * 
 * 
 * maj bimp conf
 * Aj droit modif sav au xx sav
 * Aj droit toute les outiques 
 * Aj droit logistique
 */

if ($loadEquip == true) {
    $reqP1 = "UPDATE `llx_synopsischrono_chrono_101` SET `N__Serie` = REPLACE(`N__Serie`, ' ', '') WHERE 1";
    $db->query($reqP1);
    $sql = $db->query("SELECT * FROM `llx_synopsischrono_chrono_101` ce, llx_synopsischrono c WHERE c.id = ce.id AND concat('OLD', ce.id) NOT IN (SELECT note FROM `llx_be_equipment` WHERE 1) AND `N__Serie` NOT LIKE '% %' AND `N__Serie` NOT LIKE '' ORDER BY c.id LIMIT  0,1000000");

//        $equipement = BimpObject::getInstance('bimpequipment', 'Equipment');
//            $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');
//            $emplacement->config->params['positions'] = 0;
    while ($ligne = $db->fetch_object($sql)) {
        if ($ligne->description == "" && $ligne->Produit < 1)
            $ligne->description = "PROD N/C";


        if($ligne->fk_soc < 1)
            $ligne->fk_soc = 4674;


        $arrayEquipment = array(
            'type' => 1, // cf $types
            'serial' => addslashes($ligne->N__Serie), // num série
            'reserved' => 0, // réservé ou non
//            'date_purchase' => '2010-10-10', // date d'achat TODO remove
//            'date_warranty_end' => '2010-10-10', // TODO remove
            'warranty_type' => addslashes($ligne->Type_garantie), // type de garantie (liste non définie actuellement)
            'admin_login' => addslashes($ligne->Login_Admin),
            'admin_pword' => addslashes($ligne->Mdp_Admin),
            'date_purchase' => addslashes($ligne->Date_Achat),
            'date_warranty_end' => addslashes($ligne->Date_fin_SAV),
//            'date_vente' => '2999-01-01 00:00:00',
//            'date_update' => '2999-01-01 00:00:00',
            'product_label' => addslashes($ligne->description),
            'note' => "OLD" . $ligne->id,
            'id_product' => ($ligne->Produit > 0)? $ligne->Produit : "0"
        );
        
        $db->query('INSERT INTO llx_be_equipment(id_product,product_label,type,serial,date_purchase,warranty_type,prix_achat,prix_vente_except,prix_vente,origin_element,origin_id_element,id_facture,admin_login,admin_pword,note,id,date_create,user_create,user_update) '
                . 'VALUES ("'.$arrayEquipment['id_product'].'", "'.$arrayEquipment['product_label'].'", "1", "'.$arrayEquipment['serial'].'", "'.$arrayEquipment['date_purchase'].'", "'.$arrayEquipment['warranty_type'].'", "0", "0", "0", "", 0, 0, "'.$arrayEquipment['admin_login'].'", "'.$arrayEquipment['admin_pword'].'", "'.$arrayEquipment['note'].'", 0, "2018-06-08 14:33:41", 1, 1)');
        $idE = $db->last_insert_id('llx_be_equipment');
//        $newErrors = array_merge($newErrors, $equipement->validateArray($arrayEquipment));
//
//        $newErrors = array_merge($newErrors, $equipement->create());

        if ($idE > 0) {

            $arrayEmplacement = array(
                'id_equipment' => $idE,
                'type' => 1, // cf $types
                'id_client' => $ligne->fk_soc, // si type = 2
                'infos' => 'Import old Module',
//            'date_update' => '2999-01-01 00:00:00',
                'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') // date et heure d'arrivée
            );
            
            $db->query('INSERT INTO llx_be_equipment_place(id_equipment,type,id_client,id_contact,id_entrepot,code_centre,id_user,place_name,infos,date,code_mvt,id,date_create,user_create,user_update, position) '
                    . '     VALUES ('.$idE.', "1", '.$arrayEmplacement['id_client'].', 0, 0, "", 0, "", "Import old Module", "'.dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S').'", "", 0, "'.dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S').'", 1, 1,1)');
$OK++;
//            $newErrors = array_merge($newErrors, $emplacement->validateArray($arrayEmplacement));
//            $newErrors = array_merge($newErrors, $emplacement->create());
//            if ($emplacement->id > 0) {
//                //echo "<br/><br/>OK equipment " . $equipement->id;
//                $OK++;
//            } else {
//                echo "<br/><br/>ERREUR FATAL <pre>Impossible de validé " . print_r($arrayEmplacement, 1).print_r($newErrors,1);
//            }
        } else {
            echo "<br/><br/>ERREUR FATAL<pre>Impossible de validé " . print_r($arrayEquipment, 1).print_r($newErrors,1);
        }
//        $equipement->reset();
//        $emplacement->reset();
    }
    $db->query("UPDATE `llx_be_equipment_place` SET `position` = '1' WHERE position = 0;");
    
    
    
}



if ($loadSav) {
    $sql = $db->query("SELECT s.*, c.*, e.id as idMat, s.id as idS  FROM `llx_synopsischrono` c, `llx_synopsischrono_chrono_105` s LEFT JOIN llx_be_equipment e ON e.note = CONCAT('OLD', Materiel) WHERE s.`Centre` NOT LIKE 'AB' AND c.id = s.id AND (revisionNext < 1 OR revisionNext IS NULL)");

$savok = 0;

        $sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
    while ($ligne = $db->fetch_object($sql)) {


        $code_centre = "S";
        $idP = 74729; //Prod par default
        $idClient = 144582;
        
        if (isset($ligne->Centre) && $ligne->Centre != "")
            $code_centre = $ligne->Centre;
        else
            echo("ERREUR FATAL Pas de correspondance pour le centre " . $ligne->Centre);

        if ($ligne->idMat < 1) {//on cherche dans element_element
            //echo "<br/><br/>ERR Pas de prod dans old SAV num ".$ligne->idS;
            $tabT = getElementElement("sav", "productCli", $ligne->idS);
            if(isset($tabT[0])){
                $sql2 = $db->query('SELECT id FROM `llx_be_equipment` WHERE note = CONCAT("OLD", "' . $tabT[0]['d']  . '")');
                if ($db->num_rows($sql2) < 1)
                    echo "<br/><br/>Pas de prod avec old id " . $tabT[0]['d'];
                else {
                    if ($db->num_rows($sql2) > 1)
                        echo "<br/><br/>Plusieurs résultat pour prod old id " . $tabT[0]['d'];
                    $ln = $db->fetch_object($sql2);
                    $idP = $ln->id;
                }
            }
            else
                echo "<br/><br/>ERREUR 2 Pas de prod dans old SAV element element SAV num ".$ligne->idS;
        } else {
            $idP = $ligne->idMat;
        }
        if($ligne->fk_soc < 1)
            echo "<br/><br/>ERREUR Pas de client old sav";
        else
            $idClient = $ligne->fk_soc;

        $arraySav = array(
            'ref' => $ligne->ref,
            'id_equipment' => $idP, //TODO
            'code_centre' => $code_centre,
            'id_user_tech' => $ligne->Technicien,
            'id_client' => $idClient,
            'id_contrat' => $ligne->Contract,
            'id_propal' => $ligne->propalid,
            'sav_pro' => $ligne->SAV_PRO,
            'prestataire_number' => $ligne->N__de_dossier_prestataire,
            'date_problem' => $ligne->Date_Probleme,
            'date_close' => $ligne->Date___Heure_Fin,
            'accident' => $ligne->Accident,
            'save_option' => $ligne->Sauvegarde,
            'contact_pref' => $ligne->Pref_de_contact_retour,
            'etat_materiel' => $ligne->Etat_Materiel,
            'etat_materiel_desc' => $ligne->description,
            'accessoires' => $ligne->Accessoires_joints,
            'symptomes' => ($ligne->Symptomes != "")? $ligne->Symptomes : "Symptomes inc",
            'diagnostic' => $ligne->Diagnostic,
            'resolution' => $ligne->Resolution,
            'date_create' => $ligne->date_create,
            'user_create' => $ligne->fk_user_author,
            'id_contact' => $ligne->fk_socpeople,
            'date_update' => $ligne->tms,
            'user_update' => $ligne->fk_user_modif,
            'status' => $ligne->Etat,
            'pword_admin' => "x"
        ); //pas de system  login pass
        
        
        
        $newErrors = array_merge($newErrors, $sav->validateArray($arraySav));
        $newErrors = array_merge($newErrors, $sav->create());
        if ($sav->id > 0) {
            //echo "<br/><br/>OK sav " . $sav->id;
            $OK++;
            
            $req11 = "SELECT * FROM llx_synopsis_apple_repair WHERE chronoId = ".$ligne->idS;
            $sql11 = $db->query($req11);
            while($ligne11 = $db->fetch_object($sql11)){
                $req12 = 'INSERT INTO  `llx_bimp_gsx_repair` (id_sav, serial, repair_number, repair_confirm_number, repair_type, total_from_order, ready_for_pick_up, closed, reimbursed) VALUES ("'.$sav->id.'", "'.$ligne11->serial_number.'", "'.$ligne11->repairNumber.'", "'.$ligne11->repairConfirmNumber.'", "'.$ligne11->repairType.'", "'.$ligne11->totalFromOrder.'", "'.$ligne11->ready_for_pick_up.'", "'.$ligne11->closed.'", "'.$ligne11->is_reimbursed.'")';
                $db->query($req12);
            }
            $savok++;
        } else {
            echo "<br/><br/>ERREUR FATAL <pre>Impossible de validé " . print_r($arraySav, 1).print_r($newErrors,1) ;//. print_r($sav, 1);
        }
        $sav->reset();
    }
    
    
    //req en vrac
    $req = "UPDATE `llx_bs_sav` SET "
            . "id_facture_acompte = (SELECT MAX(f.rowid) "
            . "FROM `llx_facture` f, llx_element_element "
            . "WHERE sourcetype = 'propal' AND targettype = 'facture' AND fk_source = id_propal AND fk_target = f.rowid AND f.`ref` LIKE 'AC%') "
            . "WHERE `id_propal` > 0 AND `id_facture_acompte` < 1";

    $req2 = "UPDATE `llx_bs_sav` SET id_facture = (SELECT MAX(f.rowid) FROM `llx_facture` f, llx_element_element WHERE sourcetype = 'propal' AND targettype = 'facture' AND fk_source = id_propal AND fk_target = f.rowid AND f.`ref` LIKE 'FA%') WHERE `id_propal` > 0 AND `id_facture` < 1";
    $req3 = "UPDATE `llx_bs_sav` SET `id_discount` = (SELECT rowid FROM `llx_societe_remise_except` WHERE `fk_facture_source` = id_facture_acompte) WHERE `id_facture_acompte` > 0;";
    $req4 = 'UPDATE `llx_bs_sav` SET `pword_admin`="x" WHERE `pword_admin` = ""';
    $db->query($req);
    $db->query($req2);
    $db->query($req3);
    $db->query($req4);
    echo "OK SAV : ".$savok."<br/>";
}





echo "OK ".$OK;

if (count($newErrors)) {
    BimpRender::renderAlerts($newErrors);
}


llxFooter();
