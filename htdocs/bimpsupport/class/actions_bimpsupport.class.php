<?php

class ActionsBimpsupport {

    var $menuOk = false;

    function doActions($parameters, &$object, &$action, $hookmanager) {
    }

//    function printSearchForm($parameters, &$object, &$action, $hookmanager) {
//        global $conf, $langs;
//        $return = '';
//
//        if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO)) {
////            $return .= '<div id="blockvmenusearch" class="blockvmenusearch">';
//            $return .= '<form method="get" action="' . DOL_URL_ROOT . '/synopsischrono/liste.php?mainmenu=Process">';
//            $return .= '<div class="menu_titre menu_titre_search"><a class="vsmenu" href="' . DOL_URL_ROOT . '/synopsischrono/listDetail.php?mainmenu=Process">
//                     ' . img_object("Chrono", "chrono@synopsischrono") . $langs->trans("Chrono") . '</a><br></div>';
//            $return .= '<input type="text" class="flat" name="filtre" size="10">';
//            $return .= '<input type="submit" value="' . $langs->trans("Go") . '" class="button">';
//            $return .= '</form>';
//        }
//        $this->resprints = $return;
//        return 0;
//    }
    
    

    function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
        global $langs;
	$hookmanager->resArray['searchintochrono']=array('text'=>img_object("Chrono", "chrono@synopsischrono") . $langs->trans("Chrono"), 'url'=>DOL_URL_ROOT.'/synopsischrono/liste.php?mainmenu=Process&filtre='.GETPOST('q'));
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
            $return .= '<div class="blockvmenufirst blockvmenupair rouge'.($context==1 ? ' vmenu':'').'">';
            $return .= '<div class="menu_titre">';
            $return .= '<a href="#" class="vmenu">Consigne Commande</a>';
            $return .= "</div>";
            $return .= '<div class="menu_contenu editable consigne">';
            global $db;
            $consigne = new consigneCommande($db);
            $consigne->fetch($element_type, $element_id);
            $return .= $consigne->note;
            $return .= '</div></div><div class="blockvmenuend">';
            $return .= "</div>";
        }


        
//        require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
//        $groupSav = new UserGroup($db);
//        $groupSav->fetch('', "XX SAV");
        if (isset($conf->global->MAIN_MODULE_BIMPSUPPORT) && (userInGroupe("XX Sav", $user->id)) || userInGroupe("XX Sav MyMu", $user->id)) {
            $hrefFin = "#sav";
            $return .= '<div class="blockvmenufirst blockvmenupair'.($context==1 ? ' vmenu':'').'">';
            $return .= '<div class="menu_titre">' . img_object("SAV", "drap0@synopsistools") . ' Fiche SAV</div>';
            $return .= '<div class="menu_contenu">';
            $return .= '<a class="vsmenu" title="Fiche rapide SAV" href="' . DOL_URL_ROOT . '/bimpsupport/?tab=sav"> <img src="' . DOL_URL_ROOT . '/theme/eldy/img/filenew.png" border="0" alt="" title=""> Acces SAV</a>';
            $return .= '<br/><a class="vsmenu" title="Fiche rapide SAV" href="' . DOL_URL_ROOT . '/synopsisapple/test.php"> <img src="' . DOL_URL_ROOT . '/theme/eldy/img/on.png" border="0" alt="" title=""> Garantie Apple</a>';
            $return .= '</div>';
            

            require_once DOL_DOCUMENT_ROOT.'/bimpsupport/centre.inc.php';
            if($user->array_options['options_apple_centre'] == ""){//Ajout de tous les centre
                $centreUser = array();
                foreach($tabCentre as $idT2 => $tabCT)
                    if(is_int($idT2))
                        $centreUser[] = $idT2;
            }
            else{
                $centreUser = explode(" ", $user->array_options['options_apple_centre']);//Transforme lettre centre en id centre
                foreach($centreUser as $idT=> $CT){
                    foreach($tabCentre as $idT2 => $tabCT)
                        if($tabCT[8] == $CT)
                            $centreUser[$idT] = $idT2;
                }
            }
            


            if(count($centreUser) > 1)
                $tabGroupe = array(array('label' => "Tous", 'valeur' => 'Tous', 'forUrl' => implode($centreUser,"-")));

            foreach($tabCentre as $idGr => $tabOneCentr){
                if(count($centreUser) == 0 || in_array($idGr, $centreUser))
                    $tabGroupe[] = array("label" => $tabOneCentr[2], "valeur" => $idGr, "forUrl" => $idGr);
            }
            $tabResult = array();
            $result2 = $db->query("SELECT COUNT(id) as nb, id_entrepot as CentreVal, status as EtatVal FROM `".MAIN_DB_PREFIX."bs_sav` WHERE 1 ".(count($centreUser)>0 ? "AND id_entrepot IN ('".implode($centreUser, "','")."')" : "")." GROUP BY id_entrepot, status");
            while ($ligne2 = $db->fetch_object($result2)) {
                $tabResult[$ligne2->CentreVal][$ligne2->EtatVal] = $ligne2->nb;
                if (!isset($tabResult['Tous'][$ligne2->EtatVal]))
                    $tabResult['Tous'][$ligne2->EtatVal] = 0;
                $tabResult['Tous'][$ligne2->EtatVal] += $ligne2->nb;
            }

            foreach ($tabGroupe as $ligne3) {
                $centre = $ligne3['valeur'];
                $href = DOL_URL_ROOT . '/bimpsupport/?fc=index&tab=sav' . ($ligne3['valeur'] ? '&id_entrepot=' . $ligne3['forUrl'] : "");
                $return .= '<div class="menu_contenu ' . ($ligne3['valeur'] != "Tous" ? 'menu_contenueCache2' : '') . '"><span><a class="vsmenu" href="' . $href . $hrefFin . '">
                    ' . img_object("SAV", "drap0@synopsistools") . ' ' . $ligne3['label'] . '</a></span><br/>';

                foreach($tabStatutSav as $idStat => $labelStat){
                    $nb = (isset($tabResult[$centre]) && isset($tabResult[$centre][$idStat]) ? $tabResult[$centre][$idStat] : 0);
                    $return .= '<span href="#" title="" class="vsmenu" style="font-size: 10px; margin-left:12px">';
                    if ($nb == "")
                        $nb = "0";
                    $nbStr = ($nb < 10 ? "&nbsp;&nbsp;" . $nb : ($nb < 100 ? "&nbsp;" . $nb : $nb));
                    $return .= "<a href='" . $href . "&status=" . urlencode($idStat) . $hrefFin . "'>" . $nbStr . " : " . $labelStat . "</a>";
                    $return .= "</span><br/>";
                }
                $return .= '</div>';
            }
            if(count($tabGroupe) > 3){
            $return .= "<div style='width:100%;text-align:center;'><a id='showDetailChrono2'>(...)</a></div>";

            $return .= "<script type='text/javascript'>$(document).ready(function(){"
                    . "$('.menu_contenueCache2').hide();"
                    . "$('#showDetailChrono2').click(function(){"
                    . "$('.menu_contenueCache2').show();"
                    . "$(this).hide();"
                    . "});"
                    . "});</script>";
            }

            $return .= '</div><div class="blockvmenuend"></div>';
        }




//        require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
//        $groupHotline = new UserGroup($db);
//        $groupHotline->fetch('', "XX Hotline");
        if (isset($conf->global->MAIN_MODULE_SYNOPSISCHRONO) && userInGroupe("XX Hotline", $user->id)) {
            $hrefFin = "#pangridChronoDet100";
            $return .= '<div class="blockvmenufirst blockvmenupair'.($context==1 ? ' vmenu':'').'">';
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
            $return .= '</div></div><div class="blockvmenuend">';
//            }
            $return .= '</div>';
        }




        $this->resprints = $return;
        return 0;
    }

}
