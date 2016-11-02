<?php

include_once DOL_DOCUMENT_ROOT.'/synopsisprojetplus/core/boxes/box_graph_imput.php';

class box_graph_derive extends box_graph_imput
{
	var $boxcode="graph_derive";
	var $boximg="object_bill";
	var $boxlabel="Taux de derive par mois";
	var $depends = array("synopsisprojetplus");
        var $typeStat = "derive";
        var $titre = "Derive (en %)";
}