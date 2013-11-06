<?php

function sanitize_string($str, $newstr = '_') {
    $forbidden_chars_to_underscore = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ",", ";", "=");
    //$forbidden_chars_to_remove=array("(",")");
    $forbidden_chars_to_remove = array();

    return str_replace($forbidden_chars_to_underscore, $newstr, str_replace($forbidden_chars_to_remove, "", $str));
}

function boxToWidget($file, $title) {
    // A widget object as per jquery.dashboard.js.
    global $db, $user, $langs;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load('commercial');
    $langs->load("boxes");


    require_once(DOL_DOCUMENT_ROOT . '/core/boxes/' . $file);
    $nameShort = preg_replace('/.php$/', '', $file);
    $box = new $nameShort($db);

    $table = "<p>";
    ob_start();
    $box->loadBox(10);
    $table .= $box->showBox(false);
    $table .= ob_get_contents();
    ob_clean();
    $table .= "</p>";

    $table2 = "<p>";
    ob_start();
    $box->loadBox(50);
    $table2 .= $box->showBox(false);
    $table2 .= ob_get_contents();
    ob_clean();
    $table2 .= "</p>";


    return array(
        'title' => $title,
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
    );
}

/**
  \brief      Fonction servant a afficher une duree dans une liste deroulante
  \param        prefix       prefix
  \param      iSecond      Nombre de secondes
 */
function select_duration($prefix, $iSecond = '') {
    if ($iSecond) {
        require_once(DOL_DOCUMENT_ROOT . "/lib/date.lib.php");

        $hourSelected = ConvertSecondToTime($iSecond, 'hour');
        $minSelected = ConvertSecondToTime($iSecond, 'min');
    }

    print '<table><tr><td><select class="flat durrCorrectWidth" name="' . $prefix . 'hour">';
    for ($hour = 0; $hour < 24; $hour++) {
        print '<option value="' . $hour . '"';
        if ($hourSelected == $hour || ($iSecond == '' && $hour == 1)) {
            print " selected=\"true\"";
        }
        print ">" . $hour . "</option>";
    }
    print "</select><td>";
    print "H &nbsp;<td>";
    print '<select class="flat durrCorrectWidth" name="' . $prefix . 'min">';
    for ($min = 0; $min <= 55; $min = $min + 5) {
        print '<option value="' . $min . '"';
        if ($minSelected == $min)
            print ' selected="true"';
        print '>' . $min . '</option>';
    }
    print "</select><td>";
    print "M&nbsp;</table>";
    print "<style>.durrCorrectWidth { min-width: 40px !important; }</style>";
}

function getContratObj($id) {
    global $db, $conf;
    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contrat.class.php");
    if (1 || isset($conf->global->MAIN_MODULE_BABELGA) || isset($conf->global->MAIN_MODULE_BABELGMAO)) {
        $contrat = new Synopsis_Contrat($db);
        $contratTmp = new Synopsis_Contrat($db);
        $type = $contratTmp->getTypeContrat_noLoad($id);
        //if ($contrat->isGA($id))
        switch ($type) {
            case 6: {
                    require_once(DOL_DOCUMENT_ROOT . "/Babel_GA/ContratGA.class.php");
                    $isGA = true;
                    $contrat = new ContratGA($db);
                }
                break;
            case 5: {
                    require_once(DOL_DOCUMENT_ROOT . "/Babel_GA/ContratLocProdGA.class.php");
                    $isLocGA = true;
                    $contrat = new ContratLocProdGA($db);
                }
                break;
            case 4: {
                    require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/contratSAV.class.php");
                    $isSAV = true;
                    $contrat = new ContratSAV($db);
                }
                break;
            case 3: {
                    require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/contratMaintenance.class.php");
                    $isMaintenance = true;
                    $contrat = new ContratMaintenance($db);
                }
                break;
            case 2: {
                    require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/contratTicket.class.php");
                    $isTicket = true;
                    $contrat = new ContratTicket($db);
                }
                break;
            case 7: {
                    require_once(DOL_DOCUMENT_ROOT . "/Babel_GMAO/contratMixte.class.php");
                    $isSAV = true;
                    $isMaintenance = true;
                    $isTicket = true;
                    $contrat = new ContratMixte($db);
                }
                break;

            default:
                $contrat = new Synopsis_Contrat($db);
                break;
        }
    } else {
        $contrat = new Synopsis_Contrat($db);
    }
    return ($contrat);
}

function getTypeContrat_noLoad($id) {
    global $db;
    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "contrat WHERE rowid = " . $id;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    return($res->extraparams);
}

function launchRunningProcess($db, $type_str, $element_id) {
    global $conf;
    if ($element_id != '') {
        if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
            $arrProcess = getRunningProcess($db, $type_str, $element_id);
            $blocking = false;
            foreach ($arrProcess as $processId => $arrTmp) {
                if ($arrTmp['bloquant'] == 1) {
                    $extra = "";
                    if ($arrTmp['processdet'] > 0) {
                        $extra = "&processDetId=" . $arrTmp['processdet'];
                        $tmp = new processDet($db);
                        $tmp->fetch($arrTmp['processdet']);
                        if ((!$tmp->statut > 0) || $tmp->statut == 999)
                            $blocking = true;
                        //var_dump($blocking);
                    } else {
                        $extra = '&type=' . $arrTmp['type'];
                        $blocking = true;
                    }
                    if ($blocking) {
                        header('location:' . DOL_URL_ROOT . "/Synopsis_Process/form.php?process_id=" . $processId . "&id=" . $_GET['id'] . $extra);
                        exit();
                    }
                }
            }
        }
    }
}

function getRunningProcess($db, $type_str, $element_id) {
    global $conf, $user;
    if ($conf->global->MAIN_MODULE_SYNOPSISPROCESS) {

        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet_active as a,
                           " . MAIN_DB_PREFIX . "Synopsis_Process_type_element as e,
                           " . MAIN_DB_PREFIX . "Synopsis_Process as p
                     WHERE upper(e.type)=upper('" . $type_str . "')
                       AND e.id = a.type_refid
                       AND p.id = a.process_refid
                       AND a.element_refid = " . $element_id;
//print $requete;
        $sql = $db->query($requete);
        $arr = array();
        while ($res = $db->fetch_object($sql)) {
            $arr[$res->process_refid] = array('bloquant' => $res->bloquant, "label" => $res->label, "processdet" => $res->processdet_refid, "process" => $res->process_refid, "type" => $res->type_refid);
        }
        return($arr);
    } else {
        return array();
    }
}

function getIdleProcess($db, $type_str, $element_id) {
    return(getRunningProcess($db, $type_str, $element_id));
}

function DoesElementhasProcess($db, $element_type) {
    global $conf, $user;
    if (isset($conf->global->MAIN_MODULE_PROCESS)) {
        $requete = "SELECT p.id
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Process as p,
                           " . MAIN_DB_PREFIX . "Synopsis_Process_type_element as e
                     WHERE e.type = '" . $element_type . "'
                       AND p.typeElement_refid = e.id";
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0) {
            return (true);
        } else {
            return (false);
        }
    } else {
        return false;
    }
}

function SynSanitize($str) {
    $str = sanitize_string($str);
    $str = remove_accents($str);
    return($str);
}

function seems_utf8($str) {
    $length = strlen($str);
    for ($i = 0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80)
            $n = 0;# 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0)
            $n = 1;# 110bbbbb
        elseif (($c & 0xF0) == 0xE0)
            $n = 2;# 1110bbbb
        elseif (($c & 0xF8) == 0xF0)
            $n = 3;# 11110bbb
        elseif (($c & 0xFC) == 0xF8)
            $n = 4;# 111110bb
        elseif (($c & 0xFE) == 0xFC)
            $n = 5;# 1111110b
        else
            return false;# Does not match any model
        for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
            if (( ++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                return false;
        }
    }
    return true;
}

function remove_accents($string) {
    if (!preg_match('/[\x80-\xff]/', $string))
        return $string;

    if (seems_utf8($string)) {
        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
            chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
            chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
            chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E',
            chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E',
            chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I',
            chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I',
            chr(195) . chr(143) => 'I', chr(195) . chr(145) => 'N',
            chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O',
            chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
            chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U',
            chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
            chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
            chr(195) . chr(159) => 's', chr(195) . chr(160) => 'a',
            chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a',
            chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a',
            chr(195) . chr(165) => 'a', chr(195) . chr(167) => 'c',
            chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
            chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
            chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
            chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
            chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o',
            chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o',
            chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o',
            chr(195) . chr(182) => 'o', chr(195) . chr(185) => 'u',
            chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u',
            chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y',
            chr(195) . chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
            chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
            chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
            chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
            chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
            chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
            chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
            chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
            chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
            chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
            chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
            chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
            chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
            chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
            chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
            chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
            chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
            chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
            chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
            chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
            chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
            chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
            chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
            chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
            chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
            chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
            chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
            chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
            chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L',
            chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
            chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
            chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
            chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
            chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
            chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
            chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
            chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N',
            chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
            chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
            chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
            chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
            chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
            chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r',
            chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
            chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r',
            chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
            chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's',
            chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
            chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
            chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
            chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
            chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
            chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
            chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
            chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
            chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
            chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
            chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
            chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
            chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
            chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
            chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
            chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
            chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',
            // Euro Sign
            chr(226) . chr(130) . chr(172) => 'E',
            // GBP (Pound) Sign
            chr(194) . chr(163) => '');

        $string = strtr($string, $chars);
    } else {
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
                . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
                . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
                . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
                . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
                . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
                . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
                . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
                . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
                . chr(252) . chr(253) . chr(255);

        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }
    return $string;
}

function debug($arr) {
//    echo "<div style='float:left; border:4px; text-align:left;'><pre>";
//    print_r($arr);
//    echo "</pre></div>"; 
    foreach ($arr as $p)
        $i++;
    echo $i;
}

function getIdInUrl($url, $nomId = "id") {
    $tabUrl = explode("?", $url);
    $tabUrl = explode("#", $tabUrl[1]);
    $tabUrl = explode("&", $tabUrl[0]);
    foreach ($tabUrl as $val) {
        if (stripos($val, $nomId) !== false)
            return str_replace($nomId . "=", "", $val);
    }
    return false;
}

function getTypeAndId($url = null, $request = null) {
    if ($url == NULL)
        $url = $_SERVER['REQUEST_URI'];
    if ($request == NULL)
        $request = $_REQUEST;
    if (stripos($url, "ajax") != false) {
        return null;
    }
    if (stripos($url, "compta/facture") != false) {
        $element_type = 'facture';
        @$element_id = $request['facid'];
    } elseif (stripos($url, "societe/soc.php") || stripos($url, "comm/fiche.php?socid=") || stripos($url, "comm/prospect/fiche.php?socid=")) {
        $element_type = 'societe';
        @$element_id = $request['socid'];
    } elseif (stripos($url, "product/fiche.php") != false) {
        $element_type = 'product';
        @$element_id = $request['id'];
    } elseif (stripos($url, "projet/tasks/task.php") != false) {
        $element_type = 'tache';
        @$element_id = $request['id'];
    } elseif (stripos($url, "projet/") != false) {
        $element_type = 'projet';
        @$element_id = $request['id'];
    } elseif (stripos($url, "commande/") != false) {
        $element_type = 'commande';
        @$element_id = $request['id'];
    } elseif (stripos($url, "compta/bank/") != false) {
        $element_type = 'banque';
        @$element_id = $request['id'];
    } elseif (stripos($url, "fichinter/") != false) {
        $element_type = 'FI';
        @$element_id = $request['id'];
    } elseif (stripos($url, "Synopsis_DemandeInterv/") != false) {
        $element_type = 'DI';
        @$element_id = $request['id'];
    } elseif (stripos($url, "contrat/") != false) {
        $element_type = 'contrat';
        @$element_id = $request['id'];
    } elseif (stripos($url, "user/fiche.php") != false) {
        $element_type = 'user';
        @$element_id = $request['id'];
    } elseif (stripos($url, "comm/propal.php") != false) {
        $element_type = 'propal';
        @$element_id = $request['id'];
    } elseif (stripos($url, "admin/Synopsis_Chrono") != false) {
        $element_type = 'configChrono';
        @$element_id = $request['id'];
    } elseif (stripos($url, "Synopsis_Chrono") != false) {
        $element_type = 'chrono';
        @$element_id = $request['id'];
    } elseif (stripos($url, "Synopsis_Process") != false) {
        $element_type = 'process';
        @$element_id = $request['process_id'];
    } elseif (stripos($url, "ndfp") != false) {
        $element_type = 'ndfp';
        @$element_id = $request['id'];
    } elseif (stripos($url, "expedition") != false) {
        $element_type = 'expedition';
        @$element_id = $request['id'];
    } else {
        return null;
    }
    return array($element_type, $element_id);
}

function getAdresseLivraisonComm($commId) {
    global $db, $langs;
    $return = '';
    $sql = "SELECT a.* FROM `" . MAIN_DB_PREFIX . "element_contact` c, " . MAIN_DB_PREFIX . "c_type_contact t, " . MAIN_DB_PREFIX . "socpeople a WHERE `fk_c_type_contact` = t.rowid AND t.code = 'SHIPPING' AND a.rowid = c.fk_socpeople AND c.`element_id` = " . $commId;

    $resql = $db->query($sql);
//        if ($resql) {
    $nb = $db->num_rows($resql);
    print $nb ? ($nb) . " adresse(s): " : $langs->trans("NoOtherDeliveryAddress");
    while ($res = $db->fetch_object($resql)) {
        $return .= "<table width=100%><tr><td>" . $res->name . " " . $res->firstname . "</td></tr>
                                      <tr><td>" . $res->address . "</td></tr>
                                      <tr><td>" . $res->cp . " " . $res->ville . "</td></tr>
                                      <tr><td>Tel: " . $res->phone . " - Fax: " . $res->fax . "</td></tr>
                               </table><br/>";
    }
    return $return;
}

function getParaChaine($chaine, $delimite, $delimite2 = " ") {
    $result = false;
    $chaine = explode($delimite2, $chaine);
    foreach ($chaine as $bouChaine)
        if (stripos($bouChaine, $delimite) !== false)
            $result = str_replace($delimite, "", $bouChaine);
    return $result;
}

function addElementElement($typeS, $typeD, $idS, $idD, $ordre = 1) {
    global $db;
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    $req = "INSERT INTO " . MAIN_DB_PREFIX . "element_element (sourcetype, targettype, fk_source, fk_target) VALUES ('" . $typeS . "', '" . $typeD . "', " . $idS . ", " . $idD . ")";
    return $db->query($req);
}

function delElementElement($typeS, $typeD, $idS = null, $idD = null, $ordre = true) {
    global $db;
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    if (!isset($typeS) && !isset($typeD))
        die("Suppr tout probleme pas de type");
    $req = "DELETE FROM " . MAIN_DB_PREFIX . "element_element WHERE 1";
    if (isset($typeS))
    $req .= " AND sourcetype = '" . $typeS . "'";        
    if (isset($typeD))
    $req .= " AND targettype = '" . $typeD . "'";
    
    if (isset($idS))
        $req .= " AND fk_source = " . $idS;
    if (isset($idD))
        $req .= " AND fk_target = " . $idD;
    $db->query($req);
}

function getElementElement($typeS = null, $typeD = null, $idS = null, $idD = null, $ordre = true) {
    global $db;
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    $req = "SELECT * FROM " . MAIN_DB_PREFIX . "element_element WHERE ";
    $tabWhere = array("1");
    if ($typeS)
        $tabWhere[] = "sourcetype = '" . $typeS . "'";
    if ($typeD)
        $tabWhere[] = "targettype = '" . $typeD . "'";
    $req .= implode(" AND ", $tabWhere);

    if (isset($idS))
        $req .= " AND fk_source = " . $idS;
    if (isset($idD))
        $req .= " AND fk_target = " . $idD;
    $sql = $db->query($req);
    $tab = array();
    while ($result = $db->fetch_object($sql)) {
        if ($ordre)
            $tab[] = array("s" => $result->fk_source, "d" => $result->fk_target, "ts" => $result->sourcetype, "td" => $result->targettype);
        else
            $tab[] = array("d" => $result->fk_source, "s" => $result->fk_target, "td" => $result->sourcetype, "ts" => $result->targettype);
    }
    return $tab;
}

function setElementElement($typeS, $typeD, $idS, $idD, $ordre = true) {
    if (!$ordre) {
        $typeST = $typeD;
        $idST = $idD;
        $typeD = $typeS;
        $idD = $idS;
        $typeS = $typeST;
        $idS = $idST;
    }
    delElementElement($typeS, $typeD, $idS);
    return addElementElement($typeS, $typeD, $idS, $idD);
}

function asPosition($str) {
    $tab = explode(" ", $str);
    $tab[0] = intval($tab[0]);
    if (is_int($tab[0]) && $tab[0] > 0)
        return array(0 => $tab[0], 1 => str_replace($tab[0] . " ", "", $str));
    return false;
}

function mailSyn($to, $sujet, $text, $headers = null, $cc = '') {
    $toReplay = "Tommy SAURON <tommy@drsi.fr>";
    $ccAdmin = $toReplay . ", Christian CONSTANTIN-BERTIN <cconstantin@finapro.fr>";
    if (defined('MOD_DEV_SYN_MAIL')) {
        $text = "OrigineTo = " . $to . "\n\n" . $text;
        $to = MOD_DEV_SYN_MAIL;
    } elseif ($cc != '')
        $ccAdmin .= ", " . $cc;
    if (!isset($to) || $to == '') {
        $text = "Pas de mail expediteur definit." . "\n\n" . $text;
        $to = $toReplay;
    }
//    if (!$headers) {
//        $headers = 'MIME-Version: 1.0' . "\r\n";
//        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
//        $headers .= 'From: Application GLE ' . MAIN_INFO_SOCIETE_NOM . ' <no-replay-' . str_replace(" ", "", MAIN_INFO_SOCIETE_NOM) . '@synopsis-erp.com>' . "\r\n";
//        $headers .= 'Cc: ' . $ccAdmin . "\r\n";
//        $headers .= 'Reply-To: ' . $toReplay . "\r\n";
//        $text = str_replace("\n", "<br/>", $text);
//    }
    if (isset($to) && $to != ''){
//        mail($to, $sujet, $text, $headers);
                require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
    $mailfile = new CMailFile($sujet,$to,
            'Application GLE ' . MAIN_INFO_SOCIETE_NOM . ' <no-replay-' . str_replace(" ", "", MAIN_INFO_SOCIETE_NOM) . '@synopsis-erp.com>',
            $text, array(), array(), array(),
            $ccAdmin, "", 0, 1);
    $mailfile->sendfile();
    }
}

function utf8_encodeRien($str) {
    return $str;
}

function php2js($var) {
    if (is_array($var)) {
        $res = "[";
        $array = array();
        foreach ($var as $a_var) {
            $array[] = php2js($a_var);
        }
        return "[" . join(",", $array) . "]";
    } elseif (is_bool($var)) {
        return $var ? "true" : "false";
    } elseif (is_int($var) || is_integer($var) || is_double($var) || is_float($var)) {
        return $var;
    } elseif (is_string($var)) {
        return "\"" . addslashes(stripslashes($var)) . "\"";
    }
    // autres cas: objets, on ne les gère pas
    return FALSE;
}

function convDur($duration) {

    // Initialisation
    $duration = abs($duration);
    $converted_duration = array();

    // Conversion en semaines
    $converted_duration['weeks']['abs'] = floor($duration / (60 * 60 * 24 * 7));
    $modulus = $duration % (60 * 60 * 24 * 7);

    // Conversion en jours
    $converted_duration['days']['abs'] = floor($duration / (60 * 60 * 24));
    $converted_duration['days']['rel'] = floor($modulus / (60 * 60 * 24));
    $modulus = $modulus % (60 * 60 * 24);

    // Conversion en heures
    $converted_duration['hours']['abs'] = floor($duration / (60 * 60));
    $converted_duration['hours']['rel'] = floor($modulus / (60 * 60));
    $modulus = $modulus % (60 * 60);

    // Conversion en minutes
    $converted_duration['minutes']['abs'] = floor($duration / 60);
    $converted_duration['minutes']['rel'] = floor($modulus / 60);
    if ($converted_duration['minutes']['rel'] < 10) {
        $converted_duration['minutes']['rel'] = "0" . $converted_duration['minutes']['rel'];
    };
    $modulus = $modulus % 60;

    // Conversion en secondes
    $converted_duration['seconds']['abs'] = $duration;
    $converted_duration['seconds']['rel'] = $modulus;

    // Affichage
    return( $converted_duration);
}

function select_dolusersInGroup($form, $group, $selected = '', $htmlname = 'userid', $show_empty = 0, $exclude = '', $disabled = 0, $include = '', $enableonly = '', $force_entity = false) {
    global $conf, $user, $langs;

    // If no preselected user defined, we take current user
    if ($selected < -1 && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE))
        $selected = $user->id;

    // Permettre l'exclusion d'utilisateurs
    if (is_array($exclude))
        $excludeUsers = implode("','", $exclude);
    // Permettre l'inclusion d'utilisateurs
    if (is_array($include))
        $includeUsers = implode("','", $include);

    $out = '';

    // On recherche les utilisateurs
    $sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.login, u.admin, u.entity";
    if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
        $sql.= ", e.label";
    }
    $sql.= " FROM " . MAIN_DB_PREFIX . "user as u";
    $sql.= ", " . MAIN_DB_PREFIX . "usergroup_user as ug2";
    if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "entity as e ON e.rowid=u.entity";
        if ($force_entity)
            $sql.= " WHERE u.entity IN (0," . $force_entity . ")";
        else
            $sql.= " WHERE u.entity IS NOT NULL";
    }
    else {
        if (!empty($conf->multicompany->transverse_mode)) {
            $sql.= ", " . MAIN_DB_PREFIX . "usergroup_user as ug";
            $sql.= " WHERE ug.fk_user = u.rowid";
            $sql.= " AND ug.entity = " . $conf->entity;
        } else {
            $sql.= " WHERE u.entity IN (0," . $conf->entity . ")";
        }
    }
    $sql.= " AND ug2.fk_user = u.rowid";
    $sql.= " AND ug2.fk_usergroup =" . $group;
    if (!empty($user->societe_id))
        $sql.= " AND u.fk_societe = " . $user->societe_id;
    if (is_array($exclude) && $excludeUsers)
        $sql.= " AND u.rowid NOT IN ('" . $excludeUsers . "')";
    if (is_array($include) && $includeUsers)
        $sql.= " AND u.rowid IN ('" . $includeUsers . "')";
    $sql .= " AND statut = 1";
    $sql.= " ORDER BY u.firstname ASC";

    dol_syslog(get_class($form) . "::select_dolusers sql=" . $sql);
    $resql = $form->db->query($sql);
    if ($resql) {
        $num = $form->db->num_rows($resql);
        $i = 0;
        if ($num) {
            $out.= '<select class="flat" id="' . $htmlname . '" name="' . $htmlname . '"' . ($disabled ? ' disabled="disabled"' : '') . '>';
            if ($show_empty)
                $out.= '<option value="-1"' . ($selected == -1 ? ' selected="selected"' : '') . '>&nbsp;</option>' . "\n";

            $userstatic = new User($form->db);

            while ($i < $num) {
                $obj = $form->db->fetch_object($resql);

                $userstatic->id = $obj->rowid;
                $userstatic->lastname = $obj->lastname;
                $userstatic->firstname = $obj->firstname;

                $disableline = 0;
                if (is_array($enableonly) && count($enableonly) && !in_array($obj->rowid, $enableonly))
                    $disableline = 1;

                if ((is_object($selected) && $selected->id == $obj->rowid) || (!is_object($selected) && $selected == $obj->rowid)) {
                    $out.= '<option value="' . $obj->rowid . '"';
                    if ($disableline)
                        $out.= ' disabled="disabled"';
                    $out.= ' selected="selected">';
                }
                else {
                    $out.= '<option value="' . $obj->rowid . '"';
                    if ($disableline)
                        $out.= ' disabled="disabled"';
                    $out.= '>';
                }
                $out.= $userstatic->getFullName($langs);

                if (!empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && !$user->entity) {
                    if ($obj->admin && !$obj->entity)
                        $out.=" (" . $langs->trans("AllEntities") . ")";
                    else
                        $out.=" (" . $obj->label . ")";
                }

                //if ($obj->admin) $out.= ' *';
                if (!empty($conf->global->MAIN_SHOW_LOGIN))
                    $out.= ' (' . $obj->login . ')';
                $out.= '</option>';
                $i++;
            }
        }
        else {
            $out.= '<select class="flat" name="' . $htmlname . '" disabled="disabled">';
            $out.= '<option value="">' . $langs->trans("None") . '</option>';
        }
        $out.= '</select>';
    } else {
        dol_print_error($form->db);
    }

    return $out;
}

?>
