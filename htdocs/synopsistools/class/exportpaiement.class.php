<?php

require_once (DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once (DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php");

class exportpaiement {

//    var $sep = "    -   ";
//    var $saut = "<br/>";        
    var $sep = "\t";
    var $saut = "\n";
    public $info = array();
    public $type = "";
    public $id8sens = 0;
    public $nbE = 0;
    public $debug = false;
    public $output = "Rien";
    public $error = "";
    public $tabIgnore = array();
    private $where = " AND fk_export_compta = 0 AND f.extraparams = 2 AND pf.exported < 1 LIMIT 0,10";

    public function __construct($db, $sortie = 'html') {
        $this->db = $db;
//        $this->path = (defined('DIR_SYNCH') ? DIR_SYNCH : DOL_DATA_ROOT . "/synopsischrono/export/" ) . "/extractPaiGle/";
        $this->path = "/data/synchro/export/paiement/";
    }

    public function exportTout() {
        $this->exportPaiementNormal();
        if ($this->error == "") {
            $this->output = trim($this->nbE . " facture(s) exportée(s)");
            return 0;
        } else {
            $this->output = trim($this->error);
            return 1;
        }
    }

    /* private function getId8sensByCentreSav($centre) {
      require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/centre.inc.php");
      global $tabCentre;
      if (isset($tabCentre[$centre][3]) && $tabCentre[$centre][3] > 0)
      return $tabCentre[$centre][3];
      mailSyn2("Impossible de trouvé un id8sens", "admin@bimp.fr, jc.cannet@bimp.fr", "BIMP-ERP<admin@bimp.fr>", "Bonjour impossible de trouver d'id 8sens Centre : " . $centre);
      return 0;
      } */

    public function exportPaiementNormal() {
        $result = $this->db->query("SELECT p.*, facnumber, codeCli8Sens, Collab8sens, pf.rowid as pfid FROM `llx_paiement` p, llx_facture f, llx_paiement_facture pf WHERE pf.`fk_paiement` = p.rowid AND pf.`fk_facture` = f.rowid " . $this->where);
        $tabPaiement = $tabPfId = array();
        //$tabPaiement[] = array("JorCode" => "Code journal", "EcrRef" => "Référence", "EcrDate" => "Date", "EcrCpt" => "Compte écriture", "EcrLib" => "Libellé Ecriture", "EcrDebit" => "Débit", "EcrCredit" => "Crédit", "EcrSolde" => "Solde", "EcrLettrage" => "Lettrage/Pointage", "EcrIsMark" => "Lettrée");
        while ($ligne = $this->db->fetch_object($result)) {
            $credit = str_replace(".", ",", ($ligne->amount < 0) ? 0 : $ligne->amount);
            $debit = str_replace(".", ",", ($ligne->amount < 0) ? -$ligne->amount : 0);
            $solde = -$ligne->amount;
            $tabPaiement[] = array("EcrPiece" => "", "JorCode" => "LCL", "EcrRef" => "", "EcrDate" => dol_print_date($ligne->datec, "%d/%m/%Y"), "EcrGCptCode" => "5128000000", /* "EcrCpt" => "remise de paiement", */ "EcrLib" => "Paiement " . $ligne->facnumber, "EcrDebit" => $credit, "EcrCredit" => $debit, /* "EcrSolde" => -$solde, */ "EcrLettrage" => "", "PcvGpriID" => "");
            $tabPaiement[] = array("EcrPiece" => $ligne->facnumber, "JorCode" => "LCL", "EcrRef" => "Paiement GLE", "EcrDate" => dol_print_date($ligne->datec, "%d/%m/%Y"), "EcrGCptCode" => "411" . $ligne->codeCli8Sens, /* "EcrCpt" => "411071719-GD", */ "EcrLib" => "Paiement " . $ligne->facnumber, "EcrDebit" => $debit, "EcrCredit" => $credit, /* "EcrSolde" => $solde, */ "EcrLettrage" => $ligne->facnumber, "PcvGpriID" => $ligne->Collab8sens);
            $tabPfId[] = $ligne->pfid;
        }
        if (count($tabPaiement) > 0) {
            $txt = $this->getTxt($tabPaiement, array());
            if (file_put_contents($this->path . "Pai" . dol_print_date(dol_now(), "%d-%m-%Y--%H:%M:%S") . ".txt", $txt)) {
                echo "ok";
                if (count($tabPfId) > 0) {
                    $this->db->query("UPDATE llx_paiement_facture SET exported = 1 WHERE rowid IN ('" . implode("','", $tabPfId) . "')");
                }
            } else
                echo "Impossible d'exporté " . $this->path . "Pai" . dol_print_date(dol_now(), "%d-%m-%Y--%H:%M:%S") . ".txt avec " . $txt;
        } else
            echo "Rien a exportée";
    }

    function getTxt($tab1, $tab2) {
        $sortie = "";
        if (!isset($tab1[0]) || !isset($tab1[0]))
            return 0;

        if (isset($tab1[0])) {
            foreach ($tab1[0] as $clef => $inut)
                $sortie .= $clef . $this->sep;
            $sortie .= $this->saut;
        }
        if (isset($tab2[0])) {
            foreach ($tab2[0] as $clef => $inut)
                $sortie .= $clef . $this->sep;
            $sortie .= $this->saut;
        }


        foreach ($tab1 as $tabT) {
            foreach ($tabT as $val)
                $sortie .= str_replace(array($this->saut, $this->sep, "\n", "\r"), "  ", $val) . $this->sep;
            $sortie .= $this->saut;
        }
        foreach ($tab2 as $tabT) {
            foreach ($tabT as $val)
                $sortie .= str_replace(array($this->saut, $this->sep, "\n", "\r"), "  ", $val) . $this->sep;
            $sortie .= $this->saut;
        }

        return $sortie;
    }

    /*   function error($msg, $idProd = 0, $idCat = 0) {
      $this->error = $msg;
      dol_syslog($msg, 3, 0, "_extract");
      $to = "";

      if ($idProd > 0) {
      $prod = new Product($this->db);
      $prod->fetch($idProd);
      $msg .= "<br/>" . $prod->getNomUrl(1);
      $to = "a.delauzun@bimp.fr, tommy@bimp.fr";
      }
      if ($idCat > 0) {
      $cat = new Categorie($this->db);
      $cat->fetch($idCat);
      $msg .= $cat->getNomUrl(1);
      $to = "tommy@bimp.fr";
      }
      if ($to != "")
      mailSyn2("Produit non catégorisé", $to, "admin@bimp.fr", "Bonjour ceci est un message automatique des export vers 8sens <br/>" . $msg);
      if ($this->debug)
      echo "<span class='red'>" . $msg . "</span><br/>";
      } */
}
