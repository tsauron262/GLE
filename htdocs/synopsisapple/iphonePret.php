<?php

require_once('../main.inc.php');

llxHeader("", "Signaler un bug");


print load_fiche_titre("Suivie des prêt SAV en cours");



        $sql = $db->query('SELECT ch106.id as idP, ch107.id as idI, ch106.Fin_Pret, Centre FROM `' . MAIN_DB_PREFIX . 'synopsischrono_chrono_107` ch107, `' . MAIN_DB_PREFIX . 'synopsischrono_chrono_106` ch106, ' . MAIN_DB_PREFIX . 'element_element WHERE `sourcetype` = "iphone" AND `targettype` = "pret" AND `fk_target` = ch106.id AND fk_source = ch107.id AND ch106.Restitue = 0');
        while($ligne = $db->fetch_object($sql)){
            $red = (new DateTime($ligne->Fin_Pret) < new DateTime('today'));
                echo "<div class='".($red ? 'redT' : '')."'>";
            $chrono = new Chrono($db);
            $chrono->fetch($ligne->idI);
            echo $chrono->getNomUrl(1);
            $chrono = new Chrono($db);
            $chrono->fetch($ligne->idP);
            echo $chrono->getNomUrl(1);
            echo " Centre : ".$ligne->Centre;
            echo " Date fin : ".($red?"Retard ":"").dol_print_date($ligne->Fin_Pret);
            echo "</div>";
        }