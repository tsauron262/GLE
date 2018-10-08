<?php

include_once DOL_DOCUMENT_ROOT.'/synopsisprojetplus/core/boxes/box_graph_imput.php';

class box_graph_caimput extends box_graph_imput
{
	var $boxcode="graph_caimput";
	var $boximg="object_bill";
	var $boxlabel="CA Imputations par mois";
	var $depends = array("synopsisprojetplus");
        var $typeStat = "ca";
        var $titre = "CA Imputations (en K€)";
        var $coefValeur = 1000;
}