<?php

require("../../main.inc.php");

require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";

llxHeader();

set_time_limit(5000000);

$loadEquip = false;
$loadSav = true;

if ($loadEquip == true) {
    $sql = $db->query("SELECT * FROM `llx_synopsischrono_chrono_101` ce, llx_synopsischrono c WHERE c.id = ce.id AND concat('OLD', ce.id) NOT IN (SELECT note FROM `llx_be_equipment` WHERE 1) AND `N__Serie` NOT LIKE '% %' AND `N__Serie` NOT LIKE '' ORDER BY c.id LIMIT  0,1000000");

    while ($ligne = $db->fetch_object($sql)) {
        if ($ligne->description == "")
            $ligne->description = "PROD N/C";



        $equipement = BimpObject::getInstance('bimpequipment', 'Equipment');

        $arrayEquipment = array(
            'id_product' => '', // ID du produit. 
            'type' => 1, // cf $types
            'serial' => $ligne->N__Serie, // num série
            'reserved' => 0, // réservé ou non
//            'date_purchase' => '2010-10-10', // date d'achat TODO remove
//            'date_warranty_end' => '2010-10-10', // TODO remove
            'warranty_type' => $ligne->Type_garantie, // type de garantie (liste non définie actuellement)
            'admin_login' => $ligne->Login_Admin,
            'admin_pword' => $ligne->Mdp_Admin,
            'date_purchase' => $ligne->Date_Achat,
            'date_warranty_end' => $ligne->Date_fin_SAV,
//            'date_vente' => '2999-01-01 00:00:00',
//            'date_update' => '2999-01-01 00:00:00',
            'product_label' => $ligne->description,
            'note' => "OLD" . $ligne->id
        );
        if ($ligne->Produit > 0)
            $arrayEquipment['id_product'] = $ligne->Produit;

        $equipement->validateArray($arrayEquipment);

        $newErrors = array_merge($newErrors, $equipement->create());

        if ($equipement->id > 0) {
            $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

            $arrayEmplacement = array(
                'id_equipment' => $equipement->id,
                'type' => 1, // cf $types
                'id_client' => $ligne->fk_soc, // si type = 2
                'infos' => 'Import old Module',
//            'date_update' => '2999-01-01 00:00:00',
                'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S') // date et heure d'arrivée
            );

            $emplacement->validateArray($arrayEmplacement);
            $newErrors = array_merge($newErrors, $emplacement->create());
            if ($emplacement->id > 0) {
                echo "<br/><br/>OK equipment " . $equipement->id;
            } else {
                echo "<br/><br/><pre>Impossible de validé " . print_r($arrayEmplacement, 1);
            }
        } else {
            echo "<br/><br/><pre>Impossible de validé " . print_r($arrayEquipment, 1);
        }
    }
}



if ($loadSav) {
    $sql = $db->query("SELECT *  FROM `llx_element_element`, `llx_synopsischrono_chrono_105` s, `llx_synopsischrono` c WHERE c.id = s.id AND `sourcetype` LIKE 'SAV' AND `targettype` LIKE 'productCli' AND `fk_source` = s.id LIMIT 0,100");

    require_once DOL_DOCUMENT_ROOT . "/synopsisapple/centre.inc.php";


    while ($ligne = $db->fetch_object($sql)) {

        $sav = BimpObject::getInstance('bimpsupport', 'BS_Sav');

        $idEntrepot = 0;
        if (isset($tabCentre[$ligne->Centre]) && isset($tabCentre[$ligne->Centre][7]))
            $idEntrepot = $tabCentre[$ligne->Centre][8];

        if ($idEntrepot == 0)
            die("Pas de correspondance pour le centre " . $ligne->Centre);

        $idP = 17; //Prod par default
        if ($ligne->Materiel == "") {
            echo "<br/><br/>Pas de prod dans old SAV ";
        } else {
            $sql2 = $db->query('SELECT id FROM `llx_be_equipment` WHERE note = CONCAT("OLD", "' . $ligne->Materiel . '")');
            if ($db->num_rows($sql2) < 1)
                echo "<br/><br/>Pas de prod avec old id " . $ligne->Materiel;
            else {
                if ($db->num_rows($sql2) > 1)
                    echo "<br/><br/>Plusieurs résultat pour prod old id " . $ligne->Materiel;
                $ln = $db->fetch_object($sql2);
                $idP = $ln->id;
            }
        }

        $arraySav = array(
            'ref' => $ligne->ref,
            'id_equipment' => $idP, //TODO
            'id_entrepot' => $idEntrepot,
            'id_user_tech' => $ligne->Technicien,
            'id_client' => $ligne->fk_soc,
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
            'symptomes' => $ligne->Symptomes,
            'diagnostic' => $ligne->Diagnostic,
            'resolution' => $ligne->Resolution,
            'date_create' => $ligne->date_create,
            'user_create' => $ligne->fk_user_author,
            'id_contact' => $ligne->fk_socpeople,
            'date_update' => $ligne->tms,
            'user_update' => $ligne->fk_user_modif,
            'status' => $ligne->Etat
        ); //pas de system  login pass

        $sav->validateArray($arraySav);
        $newErrors = array_merge($newErrors, $sav->create());
        if ($sav->id > 0) {
            echo "<br/><br/>OK sav " . $sav->id;

            //req en vrac
            $req = "UPDATE `llx_bs_sav` SET "
                    . "id_facture_acompte = (SELECT MAX(f.rowid) "
                    . "FROM `llx_facture` f, llx_element_element "
                    . "WHERE sourcetype = 'propal' AND targettype = 'facture' AND fk_source = id_propal AND fk_target = f.rowid AND f.`facnumber` LIKE 'AC%') "
                    . "WHERE `id_propal` > 0 AND `id_facture_acompte` < 1";

            $req2 = "UPDATE `llx_bs_sav` SET id_facture = (SELECT MAX(f.rowid) FROM `llx_facture` f, llx_element_element WHERE sourcetype = 'propal' AND targettype = 'facture' AND fk_source = id_propal AND fk_target = f.rowid AND f.`facnumber` LIKE 'FA%') WHERE `id_propal` > 0 AND `id_facture` < 1";
            $db->query($req);
            $db->query($req2);
        } else {
            echo "<br/><br/><pre>Impossible de validé " . print_r($arraySav, 1);
        }
    }
}





print_r($newErrors);


llxFooter();
