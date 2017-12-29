<?php

require_once __DIR__ . '/BimpModelPDF.php';

class PropalPDF extends BimpModelPDF {

    public $mode = "normal";

    function initData() {
        $this->typeObject = "propal";
        $this->prefName = "loyer_";


        $titre = "";



        $arrayHead = array("desc" => "Description", "prix" => array("Prix", "€"), "qty" => "Quantité", "remise" => array("Remise", " %"), "total_ht" => array("Total Ht", "€"), "tva" => array("Tva", " %"), "total_ttc" => array("Total Ttc", "€"));
        $arrayData = array();
        $arrayTot = array();

        $this->object->fetch_lines();
        foreach ($this->object->lines as $line) {
            $arrayData[] = array("desc" => $line->desc,
                "prix" => $line->subprice,
                "qty" => $line->qty,
                "remise" => $line->remise_percent,
                "total_ht" => $line->total_ht,
                "tva" => $line->tva_tx,
                "total_ttc" => $line->total_ttc);

            $arrayTot["prix"] += $line->subprice;
        }
        $tabHtml = $this->renderTable($arrayHead, $arrayData);



        if (isset($this->object) && is_object($this->object)) {
            $titre .= "<h2>" . get_class($this->object) . " " . $this->object->ref . "</h2>";
        }






        $this->text .= $this->pdf->renderTemplate($this->tpl_dir . '/table.html', array("titre" => $titre, "table" => $tabHtml));



        //ci apres juste pour les test
        if ($this->mode == "loyer")
            $this->text .= "<h3>En mode Loyer</h3>";

        $espace = "";
        $this->text .= "<br/><br/><br/>";
        for ($i = 0; $i < 100; $i++) {
            $espace .= " - ";
            $this->text .= "<br/>" . $espace . "Ligne n°" . $i;
        }
    }

}
