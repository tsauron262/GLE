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
    var $duree_degr;
    var $pourcent_degr;
    var $location;
    //resultat des differents calculs
    var $emprunt;
    var $emprunt_degr;
    var $mensualite;
    var $mensualite0;
    var $prix_final;
    var $emprunt_total;
    var $loyer1;
    var $loyer2;
    var $nb_periode;
    //tableau static pour l'affichage de donnée variable
    static $TPeriode = array(1 => "Mensuel", 3 => "Trimestriel", 4 => "Quadrimestriel", 6 => "Semestriel");
    static $tabM = array(1 => "Mois", 3 => "Trimestres", 4 => "Quadrimestres", 6 => "Semestres");
    static $rad = array("financier" => "Location financière", "operationnel" => "Location operationnel", "evol+" => "Location à taux 0");
    static $tabD = array(12 => "12 mois", 24 => "24 mois", 36 => "36 mois", 48 => "48 mois", 240 => "240 mois");

    function __construct($db) {
        $this->db = $db;
    }

    function calculLoyer($montant, $montantPre, $duree) {
        if ($this->taux > 0) {//si il y a un taux
            $mensualite = ($montant) * ($this->interet / (1 - pow((1 + $this->interet), -($duree)))); //calcul de la mensualité de remboursement
        } else {
            $mensualite = ($montant) / $duree; //calcul de la mensualité de remboursement sans taux
        }

        $mensualite0 = $montantPre / $duree; //calcul des mensualité de remboursement à taux 0 (evol+) sur le materiel

        return ($mensualite + $mensualite0) * $this->periode; //calcul du monant des mensualités en fonction du nombre de periode
    }

    function calcul() {

        $mode = true;//default true
        $mode_comm = false;//default false

        $this->nb_periode = $this->duree / $this->periode;
        $this->nb_periode2 = $this->duree_degr / $this->periode; //nombre de période de remboursement durant toute la durée du financement
        $this->interet = $this->taux / 100 / 12; //calcul des interets par mois en fonction du taux



        $this->p_degr = $this->pourcent_degr / 100;

        $this->montantAF1 = $this->montantAF * (1 - $this->p_degr);
        $this->montantAF2 = $this->montantAF * ($this->p_degr);

        $this->commCM1 = $this->montantAF1 * (($this->commC) / 100);
        if ($mode) {
            $this->commFM1 = ($this->montantAF1 + $this->commCM1) * (($this->commF) / 100);
        } else {
            $this->commFM1 = $this->montantAF1 * (($this->commF) / 100);
        }

        $this->commCM2 = $this->montantAF2 * (($this->commC) / 100);
        if ($mode) {
            $this->commFM2 = ($this->montantAF2 + $this->commCM2) * (($this->commF) / 100);
        } else {
            $this->commFM2 = $this->montantAF2 * (($this->commF) / 100);
        }
//        echo 'commF='.$this->commFM1."\n";

        $this->emprunt1 = $this->montantAF1 + $this->commCM1 + $this->commFM1;
        $this->emprunt2 = $this->montantAF2 + $this->commCM2 + $this->commFM2;

        $this->emprunt_total = $this->emprunt1 + $this->emprunt2;



        $this->pret1 = $this->pret * (1 - $this->p_degr);
        $this->pret2 = $this->pret * ($this->p_degr);

        if ($mode_comm) {
            $this->commCP1 = $this->pret1 * (($this->commC) / 100);
            if ($mode) {
                $this->commFP1 = ($this->pret1 + $this->commCP1) * (($this->commF) / 100);
            } else {
                $this->commFP1 = $this->pret1 * (($this->commF) / 100);
            }
            $this->commCP2 = $this->pret2 * (($this->commC) / 100);
            if ($mode) {
                $this->commFP2 = ($this->pret2 + $this->commCP2) * (($this->commF) / 100);
            } else {
                $this->commFP2 = $this->pret2 * (($this->commF) / 100);
            }
            $this->pret1 = $this->pret1 + $this->commCP1 + $this->commFP1;
            $this->pret2 = $this->pret2 + $this->commCP2 + $this->commFP2;
//            echo $this->commFP1." , ".$this->commFP2;
        }


        $this->loyer1 = $this->calculLoyer($this->emprunt1, $this->pret1, $this->duree);
        if ($this->duree_degr != 0 && $this->pourcent_degr != 0) {
            $this->loyer2 = $this->calculLoyer($this->emprunt2, $this->pret2, $this->duree_degr);
        } else {
            $this->loyer2 = 0;
        }


        //
        //
        //puis
        //
        //
        $this->prix_final1 = ($this->nb_periode * $this->loyer1);
        $this->prix_final2 = ($this->nb_periode2 * $this->loyer2);

        $this->prix_final = $this->prix_final1 + $this->prix_final2; //prix final que le financé aura payer au total
    }

    function verif_integer($user) {
        $erreurs = array();
        if (is_numeric($this->montantAF) == false || $this->montantAF < 0) {
            $erreurs[] = 'Le premier champs a besoin d\'un nombre';
        }

        if (is_numeric($this->commC) == false || $this->commC < 0) {
            $erreurs[] = 'Le champs "commission commerciale" a besoin d\'un nombre';
        }

        if (!$user->rights->synopsisFinanc->super_write) {
            if ($this->commC > 4) {
                $erreurs[] = 'La commission commerciale ne peu pas excéder 4 %';
            }
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

        if ($this->duree_degr != 0 || $this->pourcent_degr != 0) {
            if ($this->duree_degr == 0 xor $this->pourcent_degr == "") {
                $erreurs[] = 'Les 2 champs du tarif dégréssif doivent tout deux etre remplit';
            }

            if ($this->duree_degr <= 0) {
                $erreurs[] = 'La durée dégressive a besoin d\'une valeur';
            }

            if (is_numeric($this->pourcent_degr) == false || $this->pourcent_degr <= 0) {
                $erreurs[] = 'Le pourcentage dégressif a besoin d\'un nombre supérieur à 0';
            }
        }

        if (isset($erreurs[0])) {
            dol_htmloutput_mesg("", $erreurs, "error");
            return false;
        } else {
            return true;
        }
    }

    function insert($user) {
        $req = 'INSERT INTO `' . MAIN_DB_PREFIX . 'synopsisfinancement`(`user_create`, `fk_propal`, `montantAF`, `periode`, `duree`, `commC`, `commF`, `taux`, `banque`, preter, VR, type_location,duree_degr,pourcent_degr) VALUES (' . $user->id . ',' . $this->propal_id . ',' . $this->montantAF . ',' . $this->periode . ',' . $this->duree . ',' . $this->commC . ',' . $this->commF . ',' . $this->taux . ',"' . $this->banque . '",' . $this->pret . ',' . $this->VR . ',"' . $this->location . '",' . $this->duree_degr . ',' . $this->pourcent_degr . ');';
        if ($this->verif_integer($user) == true) {
            $this->db->query($req);
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'synopsisfinancement');
        }
    }

    function update($user) {
        $req = 'UPDATE ' . MAIN_DB_PREFIX . 'synopsisfinancement SET user_modify=' . $user->id . ',montantAF=' . $this->montantAF . ',periode=' . $this->periode . ',duree=' . $this->duree . ',commC=' . $this->commC . ',commF=' . $this->commF . ',taux=' . $this->taux . ',banque="' . $this->banque . '",preter=' . $this->pret . ', VR=' . $this->VR . ', type_location="' . $this->location . '", fk_contrat="' . $this->contrat_id . '", duree_degr=' . $this->duree_degr . ', pourcent_degr=' . $this->pourcent_degr . ', fk_facture="' . $this->facture_id . '" WHERE rowid=' . $this->id . ';';
        //echo $req;
        if ($this->verif_integer($user) == true) {

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
            $this->facture_id = $row->fk_facture;
            $this->id = $row->rowid;
            $this->facture_id = $row->fk_facture;
            $this->user_cre = $row->user_create;
            $this->user_mdf = $row->user_modify;
            $this->VR = $row->VR;
            $this->pret = $row->preter;
            $this->location = $row->type_location;
            $this->duree_degr = $row->duree_degr;
            $this->pourcent_degr = $row->pourcent_degr;

//            $this->calcul();
        } else {
            return 0;
        }
    }

    function calc_no_commF() {
        $this->emprunt2 = $this->montantAF * (100 + $this->commC);

        if ($this->taux > 0) {
            //$this->interet = $this->taux / 100 / 12;
            $this->mensualite2 = $this->emprunt2 * ($this->interet / (1 - pow((1 + $this->interet), -($this->duree))));
        } else {
            $this->mensualite2 = $this->emprunt2 / $this->duree;
        }

        $this->mensualite02 = $this->pret / $this->duree;



        return $this->prix_final2 = ($this->duree * ($this->mensualite2 + $this->mensualite02));
    }

}
