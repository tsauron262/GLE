<?php

$tabSql = array("UPDATE `llx_product_extrafields` pe SET `type2` = (SELECT fk_product_type FROM llx_product p WHERE pe.`fk_object` = p.rowid)",
    "UPDATE `llx_product` SET fk_product_type = 1 WHERE fk_product_type IN (2,3,4)");


$text = "Maj vers 6 : <br/><br/>  Ajouter extrafields product_type2"
        . "0,Materiel
1,Service inter
2,Service Contrat
3,Déplacement inter
4,Déplacement Contrat
5,Logiciel"
        . "Reinstaller synopsistools, synopsisapple, synopsischrono, raccourci agenda";