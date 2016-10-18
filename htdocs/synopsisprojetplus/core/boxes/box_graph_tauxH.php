<?php

include_once DOL_DOCUMENT_ROOT.'/synopsisprojetplus/core/boxes/box_graph_imput.php';

class box_graph_tauxH extends box_graph_imput
{
	var $boxcode="graph_tauxH";
	var $boximg="object_bill";
	var $boxlabel="Taux heure vendue par mois";
	var $depends = array("synopsisprojetplus");
        var $typeStat = "tauxH";
        var $titre = "Taux heure vendue par mois (en %)";
}