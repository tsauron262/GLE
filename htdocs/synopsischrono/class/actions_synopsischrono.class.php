<?php

class ActionsSynopsischrono {

    var $menuOk = false;

    function doActions($parameters, &$object, &$action, $hookmanager) {
        
    }

    function printSearchForm($parameters, &$object, &$action, $hookmanager) {
        global $conf, $langs;
        $return = '';

        if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO)) {
//            $return .= '<div id="blockvmenusearch" class="blockvmenusearch">';
            $return .= '<form method="get" action="' . DOL_URL_ROOT . '/synopsischrono/liste.php?mainmenu=Process">';
            $return .= '<div class="menu_titre menu_titre_search"><a class="vsmenu" href="' . DOL_URL_ROOT . '/synopsischrono/listDetail.php?mainmenu=Process">
                     ' . img_object("Chrono", "chrono@synopsischrono") . $langs->trans("Chrono") . '</a><br></div>';
            $return .= '<input type="text" class="flat" name="filtre" size="10">';
            $return .= '<input type="submit" value="' . $langs->trans("Go") . '" class="button">';
            $return .= '</form>';
        }
        $this->resprints = $return;
        return 0;
    }

    function printMenuAfter($parameters, &$object, &$action, $hookmanager) {
        if (!$this->menuOk) {
            $this->afficherMenu(0);
            $this->menuOk = true;
        }
        return 0;
    }

    function printLeftBlock($parameters, &$object, &$action, $hookmanager) {
        if (!$this->menuOk) {
            $this->afficherMenu(1);
            $this->menuOk = true;
        }
        return 0;
    }

    function afficherMenu($context) {
        global $conf, $user, $db;
       

        //consigne commande
        if ($element_id > 0 && ($element_type == "contrat" || $element_type == "commande" || $element_type == "DI" || $element_type == "FI" || $element_type == "expedition")) {
            $return .= '<div class="blockvmenupair rouge'.($context==1 ? ' vmenu':'').'">';
            $return .= '<div class="menu_titre">';
            $return .= '<a href="#" class="vmenu">Consigne Commande</a>';
            $return .= "</div>";
            $return .= '<div class="menu_contenu editable consigne">';
            global $db;
            $consigne = new consigneCommande($db);
            $consigne->fetch($element_type, $element_id);
            $return .= $consigne->note;
            $return .= '</div><div class="menu_end"></div>';
            $return .= "</div>";
        }


        
//        require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
//        $groupSav = new UserGroup($db);
//        $groupSav->fetch('', "XX SAV");
        if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO) && userInGroupe("XX Sav", $user->id)) {
            $hrefFin = "#pangridChronoDet105";
            $return .= '<div class="blockvmenupair'.($context==1 ? ' vmenu':'').'">';
            $return .= '<div class="menu_titre">' . img_object("SAV", "drap0@synopsistools") . ' Fiche SAV</div>';
            $return .= '<div class="menu_contenu">';
            $return .= '<a class="vsmenu" title="Fiche rapide SAV" href="' . DOL_URL_ROOT . '/synopsisapple/FicheRapide.php"> <img src="' . DOL_URL_ROOT . '/theme/eldy/img/filenew.png" border="0" alt="" title=""> Fiche rapide SAV</a>';
            $return .= '<br/><a class="vsmenu" title="Fiche rapide SAV" href="' . DOL_URL_ROOT . '/synopsisapple/test.php"> <img src="' . DOL_URL_ROOT . '/theme/eldy/img/on.png" border="0" alt="" title=""> Garantie Apple</a>';
            $return .= '</div>';
            $centre = str_replace(" ", "','", $user->array_options['options_apple_centre']);



            $tabGroupe = array(array('label' => "Tous", 'valeur' => $centre, 'forUrl' => 'Tous'));
            $result3 = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 " . ($centre ? " AND valeur IN ('" . $centre . "')" : ""));
            while ($ligne3 = $db->fetch_object($result3)) {
                $tabGroupe[] = array("label" => $ligne3->label, "valeur" => $ligne3->valeur, "forUrl" => $ligne3->valeur);
            }
            $tabResult = array();
            $result2 = $db->query("SELECT COUNT(chr.id) as nb, Centre as CentreVal, Etat as EtatVal FROM `" . MAIN_DB_PREFIX . "synopsischrono_chrono_105` chrP, `" . MAIN_DB_PREFIX . "synopsischrono` chr WHERE chr.id = chrP.id AND " . ($centre ? "Centre IN ('" . $centre . "') AND" : "") . " revisionNext <= 0 GROUP BY Centre, Etat");
            while ($ligne2 = $db->fetch_object($result2)) {
                $tabResult[$ligne2->CentreVal][$ligne2->EtatVal] = $ligne2->nb;
                if (!isset($tabResult[$centre][$ligne2->EtatVal]))
                    $tabResult[$centre][$ligne2->EtatVal] = 0;
                $tabResult[$centre][$ligne2->EtatVal] += $ligne2->nb;
            }
            
            
            $tabStatut = array();
            $result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 7" . " ORDER BY id ASC");
            while ($ligne = $db->fetch_object($result)) {
                $tabStatut[] = $ligne;
            }
            
            

            foreach ($tabGroupe as $ligne3) {
                $centre = $ligne3['valeur']; //((isset($user->array_options['options_apple_centre']) && $user->array_options['options_apple_centre'] != "") ? $user->array_options['options_apple_centre'] : null);
                $href = DOL_URL_ROOT . '/'
                        . 'synopsischrono/index.php?idmenu=845&chronoDet=105&mainmenu=Process' . ($ligne3['valeur'] ? '&FiltreCentre=' . $ligne3['forUrl'] : "");
                $return .= '<div class="menu_contenu ' . ($ligne3['forUrl'] != "Tous" ? 'menu_contenueCache' : '') . '"><span><a class="vsmenu" href="' . $href . $hrefFin . '">
                    ' . img_object("SAV", "drap0@synopsistools") . ' ' . $ligne3['label'] . '</a></span><br/>';

                foreach($tabStatut as $ligne){
                    $nb = (isset($tabResult[$centre]) && isset($tabResult[$centre][$ligne->valeur]) ? $tabResult[$centre][$ligne->valeur] : 0);
                    $return .= '<span href="#" title="" class="vsmenu" style="font-size: 10px; margin-left:12px">';
                    if ($nb == "")
                        $nb = "0";
                    $nbStr = ($nb < 10 ? "&nbsp;&nbsp;" . $nb : ($nb < 100 ? "&nbsp;" . $nb : $nb));
                    $return .= "<a href='" . $href . "&Etat=" . urlencode($ligne->valeur) . $hrefFin . "'>" . $nbStr . " : " . $ligne->label . "</a>";
                    $return .= "</span><br/>";
                }
                $return .= '</div>';
            }
            if(count($tabGroupe) > 3){
            $return .= "<div style='width:100%;text-align:center;'><a id='showDetailChrono'>(...)</a></div>";

            $return .= "<script type='text/javascript'>$(document).ready(function(){"
                    . "$('.menu_contenueCache').hide();"
                    . "$('#showDetailChrono').click(function(){"
                    . "$('.menu_contenueCache').show();"
                    . "$(this).hide();"
                    . "});"
                    . "});</script>";
            }

            $return .= '<div class="menu_end"></div></div>';
        }




//        require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
//        $groupHotline = new UserGroup($db);
//        $groupHotline->fetch('', "XX Hotline");
        if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO) && userInGroupe("XX Hotline", $user->id)) {
            $hrefFin = "#pangridChronoDet100";
            $return .= '<div class="blockvmenupair'.($context==1 ? ' vmenu':'').'">';
//            $centre = ((isset($user->array_options['options_apple_centre']) && $user->array_options['options_apple_centre'] != "") ? $user->array_options['options_apple_centre'] : null);
//            $tabGroupe = array(array('label'=>"Tous", 'valeur'=>0));
//            $result3 = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 11 " . ($centre ? " AND valeur='" . $centre . "'" : ""));
//            while ($ligne3 = $db->fetch_object($result3)) {
//                $tabGroupe[] = array("label" => $ligne3->label, "valeur" => $ligne3->valeur);
//            }
//            foreach ($tabGroupe as $ligne3) {
//                $centre = $ligne3['valeur']; //((isset($user->array_options['options_apple_centre']) && $user->array_options['options_apple_centre'] != "") ? $user->array_options['options_apple_centre'] : null);
            $href = DOL_URL_ROOT . '/synopsischrono/index.php?idmenu=845&chronoDet=100&mainmenu=Process';
            $return .= '<div class="menu_titre"><a class="vmenu" href="' . $href . $hrefFin . '">
                    ' . img_object("Hotline", "phoning") . ' Appel </a><br></div>';
            $return .= '<div class="menu_contenu">';
            $result = $db->query("SELECT * FROM `" . MAIN_DB_PREFIX . "Synopsis_Process_form_list_members` WHERE `list_refid` = 5");


            $result2 = $db->query("SELECT COUNT(id) as nb, Etat as EtatVal FROM `" . MAIN_DB_PREFIX . "synopsischrono_chrono_100` group by Etat");
            $tabResult = array();
            while ($ligne2 = $db->fetch_object($result2)) {
                $tabResult[$ligne2->EtatVal] = $ligne2->nb;
            }

            while ($ligne = $db->fetch_object($result)) {
//                $result2 = $db->query("SELECT COUNT(*) as nb FROM `" . MAIN_DB_PREFIX . "synopsischrono` WHERE  `id` IN (SELECT `chrono_refid` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `key_id` = 1034 AND `value` = '" . $ligne->valeur . "')");

                $nb = $tabResult[$ligne->valeur];

                $return .= '<span href="#" title="" class="vsmenu" style="font-size: 10px; margin-left:12px">';
                $return .= "<a href='" . $href . "&Etat=" . urlencode($ligne->valeur) . $hrefFin . "'>" . $nb . " : " . $ligne->label . "</a>";
                $return .= "</span><br/>";
            }


//            while ($ligne = $db->fetch_object($result)) {
////                $result2 = $db->query("SELECT COUNT(*) as nb FROM `" . MAIN_DB_PREFIX . "synopsischrono` WHERE  `id` IN (SELECT `chrono_refid` FROM `" . MAIN_DB_PREFIX . "synopsischrono_value` WHERE `key_id` = 1034 AND `value` = '" . $ligne->valeur . "')");
//                $result2 = $db->query("SELECT COUNT(id) as nb FROM `" . MAIN_DB_PREFIX . "synopsischrono_view_100` WHERE  EtatVal = '" . $ligne->valeur . "'");
//                $ligne2 = $db->fetch_object($result2);
//                $return .= '<span href="#" title="" class="vsmenu" style="font-size: 10px; margin-left:12px">';
//                $return .= "<a href='" . $href . "&Etat=" . urlencode($ligne->label) . $hrefFin . "'>" . $ligne2->nb . " : " . $ligne->label . "</a>";
//                $return .= "</span><br/>";
//            }
            $return .= '</div><div class="menu_end"></div>';
//            }
            $return .= '</div>';
        }




        $this->resprints = $return;
        return 0;
    }

}
