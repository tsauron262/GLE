<?php

require_once __DIR__ . '/BimpModelPDF.php';

class PropalPDF extends BimpModelPDF {

    public $mode = "normal";

    function renderTable($arrayHead, $arrayData) {
        $arrayUtil = array();
        foreach ($arrayHead as $clef => $label) {
            foreach ($arrayData as $ligne) {
                $val = $ligne[$clef];
                if (is_array($val))
                    $val = $val[0];

                if ($val !== null && $val !== "" && $val !== "0")
                    $arrayUtil[$clef] = $label;
            }
        }

        $html = "<table><tr style='background-color: green;'>";
        foreach ($arrayUtil as $valT) {
            if (is_array($valT))
                $label = $valT[0];
            else
                $label = $valT;
            $html .= "<th>" . $label . "</th>";
        }
        $html .= "</tr>";

        foreach ($arrayData as $ligne) {
            $html .= "<tr>";
            foreach ($arrayUtil as $clef => $valT) {
                $html .= "<td>";
                if (isset($ligne[$clef])) {
                    $unit = "";
                    $val = $ligne[$clef];
                    if (is_array($valT) && isset($valT[1]))
                        $unit = " " . $valT[1];
                    if ($unit == " €" || $unit == " %")
                        $val = price($val);
                    $html .= $val . $unit;
                }
                $html .= "</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</table>";

        return $html;
    }

    function initData() {
        $this->typeObject = "propal";
        $this->prefName = "loyer_";


        $titre = "";



        $arrayHead = array("desc" => "Description", "prix" => array("Prix", "€"), "qty" => "Quantité", "remise" => array("Remise", " %"), "total_ht" => array("Total Ht", "€"), "tva" => array("Tva", " %"), "total_ttc" => array("Total Ttc", "€"));
        $arrayData = array();
        $arrayTot = array();

        $this->object->fetch_lines();
//            $line = new PropaleLigne;
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
