<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

class PropalPDF extends BimpModelPDF
{

    public $propal = null;
    public $mode = "normal";

    public function __construct($id_propal)
    {
        parent::__construct();

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        global $db;

        $this->propal = new Propal($db);
        if ($this->propal->fetch($id_propal) <= 0) {
            $this->errors[] = 'ID propal invalide: ' . $id_propal;
        } else {
            if ($this->propal->fetch_thirdparty($this->propal->socid) <= 0) {
                $this->errors[] = 'Tiers invalide pour cette proposition commerciale';
            }

            if ($this->propal->fetch_contact($this->propal->contactid)) {
                $this->errors[] = 'Contact invalide pour cette proposition commerciale';
            }
        }
    }

    public function initData()
    {
//        $this->typeObject = "propal";
//        $this->prefName = "loyer_";
//
//        $titre = "";
//
//        $arrayHead = array("desc" => "Description", "prix" => array("Prix", "€"), "qty" => "Quantité", "remise" => array("Remise", " %"), "total_ht" => array("Total Ht", "€"), "tva" => array("Tva", " %"), "total_ttc" => array("Total Ttc", "€"));
//        $arrayData = array();
//        $arrayTot = array();
//
//        $this->object->fetch_lines();
//        foreach ($this->object->lines as $line) {
//            $arrayData[] = array("desc"      => $line->desc,
//                "prix"      => $line->subprice,
//                "qty"       => $line->qty,
//                "remise"    => $line->remise_percent,
//                "total_ht"  => $line->total_ht,
//                "tva"       => $line->tva_tx,
//                "total_ttc" => $line->total_ttc);
//
//            $arrayTot["prix"] += $line->subprice;
//        }
//        $tabHtml = $this->renderTable($arrayHead, $arrayData);
//
//        if (isset($this->object) && is_object($this->object)) {
//            $titre .= "<h2>" . get_class($this->object) . " " . $this->object->ref . "</h2>";
//        }
//
//        $this->text .= $this->pdf->renderTemplate($this->tpl_dir . '/table.html', array("titre" => $titre, "table" => $tabHtml));
//
//        //ci apres juste pour les test
//        if ($this->mode == "loyer")
//            $this->text .= "<h3>En mode Loyer</h3>";
//
//        $espace = "";
//        $this->text .= "<br/><br/><br/>";
//        for ($i = 0; $i < 100; $i++) {
//            $espace .= " - ";
//            $this->text .= "<br/>" . $espace . "Ligne n°" . $i;
//        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        $rows = '';
        $nRows = 0;

        $this->pdf->topMargin = 40;

        if ($nRows > 2) {
            $this->pdf->topMargin += 4 * ($nRows - 2);
        }

        $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . '/' . static::$type . '/header_right.html', array(
            'doc_name' => $docName,
            'doc_ref'  => $docRef,
            'rows'     => $rows
        ));
    }

    protected function renderContent()
    {
        $this->writeContent($this->renderAddresses($this->propal->thirdparty, $this->propal->contact));
    }
}
