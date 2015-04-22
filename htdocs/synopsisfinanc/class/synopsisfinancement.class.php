<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Synopsisfinancement extends CommonObject {

    var $db;
    var $montantAF;
    var $VR;
    var $pret;
    var $periode;
    var $duree;
    var $commC;
    var $commF;
    var $taux;
    var $banque;
    var $propal_id;
    var $id;
    var $contrat_id;
    var $facture_id;
    var $user_cre;
    var $user_mdf;
    //resultat des differents calculs
    var $emprunt;
    var $mensualite;
    var $mensualite0;
    var $prix_final;
    var $location;
    var $loyer;
    var $nb_periode;
    static $tabM = array(1 => "Mois", 3 => "Trimestres", 4 => "Quadrimestres", 6 => "Semestres");
    static $rad = array("financier" => "Location financière", "operationnel" => "Location operationnel", "evol+" => "Location à taux 0");

    function __construct($db) {
        $this->db = $db;
    }

    function calcul() {
        $this->emprunt = $this->montantAF * ((100 + $this->commC) / 100 * (100 + $this->commF) / 100);

        $this->interet = $this->taux / 100 / 12;

        $this->mensualite = $this->emprunt * ($this->interet / (1 - pow((1 + $this->interet), -($this->duree))));

        $this->mensualite0 = $this->pret / $this->duree;

        $this->loyer = ($this->mensualite + $this->mensualite0) * $this->periode;

        $this->nb_periode = $this->duree / $this->periode;

        $this->prix_final = ($this->duree * ($this->mensualite + $this->mensualite0));
    }

    function verif_integer() {
        $erreurs = array();
        if (is_numeric($this->montantAF) == false || $this->montantAF < 0) {
            $erreurs[] = 'Le premier champs a besoin d\'un nombre';
        }

        if (is_numeric($this->commC) == false || $this->commC < 0) {
            $erreurs[] = 'Le champs "commission commerciale" a besoin d\'un nombre';
        }

        if (is_numeric($this->commF) == false || $this->commF < 0) {
            $erreurs[] = 'Le champs "commission financière" a besoin d\'un nombre';
        }

        if (is_numeric($this->taux) == false || $this->taux < 0) {
            $erreurs[] = 'Le champs "Taux" a besoin d\'un nombre';
        }

        if ($this->pret == "" || is_numeric($this->pret) == false || $this->pret < 0) {
            $erreurs[] = 'Le champs "argent préter" a besoin d\'un nombre';
        }

        if ($this->VR == "" || is_numeric($this->VR) == false || $this->VR < 0) {
            $erreurs[] = 'Le champs "VR" a besoin d\'un nombre';
        }

        if (isset($erreurs[0])) {
            dol_htmloutput_mesg("", $erreurs, "error");
            return false;
        } else {
            return true;
        }
    }

    function insert($user) {
        $req = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsisfinancement`(`user_create`, `fk_propal`, `montantAF`, `periode`, `duree`, `commC`, `commF`, `taux`, `banque`, preter, VR, type_location) VALUES (' . $user->id . ',' . $this->propal_id . ',' . $this->montantAF . ',' . $this->periode . ',' . $this->duree . ',' . $this->commC . ',' . $this->commF . ',' . $this->taux . ',"' . $this->banque . '",' . $this->pret . ',' . $this->VR . ',"' . $this->location . '");';
        if ($this->verif_integer() == true) {
            $this->db->query($req);
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsisfinancement');
        }
    }

    function update($user) {
        $req = 'UPDATE ' . MAIN_DB_PREFIX . 'synopsisfinancement SET user_modify=' . $user->id . ',montantAF=' . $this->montantAF . ',periode=' . $this->periode . ',duree=' . $this->duree . ',commC=' . $this->commC . ',commF=' . $this->commF . ',taux=' . $this->taux . ',banque="' . $this->banque . '",preter=' . $this->pret . ', VR=' . $this->VR . ', type_location="' . $this->location . '" WHERE rowid=' . $this->id . ';';
        //echo $req;
        if ($this->verif_integer() == true) {

            $this->db->query($req);
        }
    }

    function fetch($id, $propal_id = null, $contrat_id = null, $facture_id = null) {
        $req = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsisfinancement WHERE ";

        if ($id) {
            $req.="rowid=" . $id;
        }
        if ($propal_id) {
            $req.="fk_propal=" . $propal_id;
        }
        if ($contrat_id) {
            $req.="fk_contrat=" . $contrat_id;
        }
        if ($facture_id) {
            $req.="fk_facture=" . $facture_id;
        }

        $result = $this->db->query($req);

        if ($this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_object($result);

            $this->taux = $row->taux;
            $this->montantAF = $row->montantAF;
            $this->periode = $row->periode;
            $this->duree = $row->duree;
            $this->commC = $row->commC;
            $this->commF = $row->commF;
            $this->banque = $row->banque;
            $this->propal_id = $row->fk_propal;
            $this->contrat_id = $row->fk_contrat;
            $this->id = $row->rowid;
            $this->facture_id = $row->fk_facture;
            $this->user_cre = $row->user_create;
            $this->user_mdf = $row->user_modify;
            $this->VR = $row->VR;
            $this->pret = $row->preter;
            $this->location = $row->type_location;

            $this->calcul();
        } else {
            return 0;
        }
    }

}
