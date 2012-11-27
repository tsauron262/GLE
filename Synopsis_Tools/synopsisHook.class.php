<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
error_reporting(E_ALL ^ E_NOTICE);
$conf->global->MAIN_APPLICATION_TITLE = "GLE";
$conf->global->MAIN_MENU_USE_JQUERY_ACCORDION = 0;
$conf->global->MAIN_MODULE_MULTICOMPANY = "1";
$conf->global->MAIN_MODULE_ORANGEHRM = "1";
//$conf->global->PROJET_ADDON = "mod_projet_tourmaline";


if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)) {
    $conf->projet->enabled = true;
    $conf->projet->dir_output = $conf->synopsisprojet->dir_output;
    $conf->imputations->dir_output = $conf->synopsisprojet->dir_output . "/imputation";
}
//if (isset($conf->global->MAIN_MODULE_SYNOPSISFICHINTER)) {
//    die;
    $conf->ficheinter->enabled = true;
//}

class synopsisHook {

    function synopsisHook() {
        global $conf, $db;
        if (isset($conf->global->MAIN_MODULE_SYNOPSISPROCESS)) {
            $tab = getTypeAndId();
            if ($tab[0] == "projet")
                $tab[0] = "project";
            launchRunningProcess($db, $tab[0], $tab[1]);
        }

        if (is_object($db) && isset($conf->global->MAIN_MODULE_SYNOPSISTOOLS)) {
            include_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/class/fileInfo.class.php");
            $fileInfo = new fileInfo($db);
            $fileInfo->showNewFile();
        }
    }

    static function menu() {
        global $conf;
        if (isset($conf->global->MAIN_MODULE_SYNOPSISHISTO)) {
            histoNavigation::saveHisto();
            histoNavigation::getBlocHisto();
        }
    }

    static function footer() {
        global $conf;
        echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/Synopsis_Tools/global.css" />' . "\n";
        echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Tools/global.js"></script>';

        $nameFile = DOL_DATA_ROOT . "/special.css";
        if (is_file($nameFile)) {
            $css = file_get_contents($nameFile);
            echo "<style>" . $css . "</style>";
        }

        if (isset($conf->global->MAIN_MODULE_SYNOPSISDASHBOARD)) {
            if (stripos($_SERVER['REQUEST_URI'], "index.php") != false) {
                dashboard::getDashboard();
            }
        }
    }

}

class histoNavigation {

    static function getBlocHisto() {
        global $db, $user;
//        if ($conf->global->MAIN_MODULE_BABELMINIHISTOUSER && $user->rights->MiniHisto->all->Afficher) {
        print '<div class="blockvmenupair">';
        print '<div class="menu_titre">';
        print '<a href="#" class="vmenu">Historique navigation</a>';
        print "</div>";
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                     WHERE user_refid = " . $user->id .
                " AND ref != '' AND element_type != '' ORDER BY tms DESC" .
                ($conf->global->BABEL_MINIHISTO_LENGTH > 0 ? " LIMIT " . $conf->global->BABEL_MINIHISTO_LENGTH : " LIMIT 5");

        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
            //print '<a href="#" class="vsmenu">'..'</a>';
            $ret = self::histoUser($res);
            if ($ret)
                print "<div class='menu_contenu'>  " . $ret . "</div>";
        }
        print "</div>";

//        }
    }

    private static function getObj($type) {
        $tabResult = self::getObjAndMenu($type);
        return $tabResult[0];
    }

    private static function getObjAndMenu($type) {
        global $db;
        $tabMenu = array(false, false);
        switch ($type) {
            case 'chrono': {
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/Chrono.class.php");
                    $obj = new Chrono($db);
                    $tabMenu[0] = "Chrono";
                }
                break;
            case 'propal': {
                    require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                    $obj = new Propal($db);
                    $tabMenu[0] = "commercial";
                    $tabMenu[1] = "propals";
                }
                break;
            case 'commande': {
                    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                    $obj = new Commande($db);
                    $tabMenu[0] = "commercial";
                    $tabMenu[1] = "orders";
                }
                break;
            case 'facture': {
                    require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                    $obj = new Facture($db);
                    $tabMenu[0] = "accountancy";
                }
                break;
            case 'prepaCom': {
//                    require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
//                    $obj = new Commande($db);
                }
                break;
            case 'FI': {
                    require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
                    $obj = new Fichinter($db);
                }
                break;
            case 'DI': {
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_DemandeInterv/demandeInterv.class.php");
                    $obj = new DemandeInterv($db);
                }
                break;
            case 'contrat': {
                    require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
                    $obj = new contrat($db);
                    $tabMenu[0] = "commercial";
                    $tabMenu[1] = "contracts";
                }
                break;
            case 'expedition': {
                    require_once(DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");
                    $obj = new Expedition($db);
                }
                break;
            case 'livraison': {
                    require_once(DOL_DOCUMENT_ROOT . "/livraison/class/livraison.class.php");
                    $obj = new Livraison($db);
                }
                break;
            case 'affaire': {
//                    require_once(DOL_DOCUMENT_ROOT . "/Babel_Affaire/Affaire.class.php");
//                    $obj = new Affaire($db);
                }
                break;
            case 'banque': {
                    require_once(DOL_DOCUMENT_ROOT . "/compta/bank/class/account.class.php");
                    $obj = new Account($db);
                }
                break;
            case 'contact': {
                    require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                    $obj = new Contact($db);
                    $tabMenu[0] = "companies";
                }
                break;
            case 'societe': {
                    require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
                    $obj = new Societe($db);
                    $tabMenu[0] = "companies";
                }
                break;
            case 'campagne': {
//                    require_once(DOL_DOCUMENT_ROOT . "/BabelProspect/Campagne.class.php");
//                    $obj = new Campagne($db);
                }
                break;
            case 'projet': {
                    require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                    $obj = new Project($db);
                    $tabMenu[0] = "synopsisprojet";
                }
                break;
            case 'tache': {
                    require_once(DOL_DOCUMENT_ROOT . "/projet/class/task.class.php");
                    $obj = new Task($db);
                    $tabMenu[0] = "synopsisprojet";
                }
                break;
            case 'groupCom': {
//                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
//                    $obj = new commandeGroup($db);
                }
                break;
            case 'process': {
                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                    $obj = new processDet($db);
                    $tabMenu[0] = "Process";
                }
                break;
            case 'product': {
                    require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
                    $obj = new product($db);
                    $tabMenu[0] = "products";
                }
                break;
            case 'user': {
                    require_once(DOL_DOCUMENT_ROOT . "/user/class/user.class.php");
                    $obj = new user($db);
                    $tabMenu[0] = "home";
                    $tabMenu[1] = "users";
                }
                break;
            case 'ndfp': {
                    require_once(DOL_DOCUMENT_ROOT . "/ndfp/class/ndfp.class.php");
                    $obj = new ndfp($db);
                    $tabMenu[0] = "accountancy";
                }
                break;
        }
        return array($obj, $tabMenu);
    }

    private static function histoUser($res) {
        $tabResult = self::getObjAndMenu($res->element_type);
        $obj = $tabResult[0];
        $tabMenu = $tabResult[1];
        if ($obj) {
            $result = $obj->fetch($res->element_id);
            if ($result > 0 && $obj->ref . "x" != "x") {
                $replace = ($tabMenu[0] ? '&mainmenu=' . $tabMenu[0] : '') . ($tabMenu[1] ? '&leftmenu=' . $tabMenu[1] : '') . '">';
                if ($res->element_type == "propal")
                    $nomUrl = str_replace('">', $replace, $obj->getNomUrl(1));
                else
                    $nomUrl = str_replace('">', $replace, $obj->getNomUrl(1, '', 20));
                return ("&nbsp;&nbsp;<span href='#' title='" . $res->element_type . " " . $res->ref . "' class='vsmenu' style='font-size: 8.5px;'>" . $nomUrl . "</span>");
            } else {
                return ("&nbsp;&nbsp;<span href='#' title='" . $res->element_type . " " . $res->ref . " (supprimer)' class='vsmenu ui-widget-error' style='font-size: 8.5px;'><del>" . dol_trunc($res->ref, 25) . "</del></span>");
            }
        } else {
            die("objet Incorect");
            return (false);
        }
    }

    static function saveHisto() {
        $tabElem = getTypeAndId();
        $element_type = $tabElem[0];
        $element_id = $tabElem[1];
//        //saveHistoUser($fichinter->id, "FI", $fichinter->ref);


        if (isset($element_id) && $element_type != '' && $element_id > 0) {
            $obj = self::getObj($element_type);
            if ($obj) {
                $obj->fetch($element_id);
                $ref = $obj->ref;
                global $user, $db;
                $requete = "SELECT *
                  FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                 WHERE user_refid = " . $user->id . "
                   AND element_type = '" . $element_type . "'
                   AND element_id = " . $element_id;
                $sql = $db->query($requete);
                if ($db->num_rows($sql) > 0) {
                    $res = $db->fetch_object($sql);
                    $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                       SET tms = now(),
                           ref = '" . addslashes($ref) . "'
                     WHERE id = " . $res->id;
                } else {
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                                (`user_refid`,`element_id`,`ref`,`element_type`)
                         VALUES (" . $user->id . "," . $element_id . ",'" . addslashes($ref) . "','" . $element_type . "')";
//        print $requete;
                }
//            die($requete);
                $sql = $db->query($requete);
                return($sql);
            }
        }
    }

}

class dashboard {

    static function getDashboard() {
        echo <<<EOF
        <script type="text/javascript" src="/Synopsis_Tools/dashboard2/js/lib/jquery.dashboard.min.js"></script>
    <script type="text/javascript" src="/Synopsis_Tools/dashboard2/js/lib/themeroller.js"></script>

    <script type="text/javascript">
      // This is the code for definining the dashboard
      $(document).ready(function() {

        // load the templates
        $('body').append('<div id="templates"></div>');
        $("#templates").hide();
        $("#templates").load("/Synopsis_Tools/dashboard2/demo/templates.html", initDashboard);

        // call for the themeswitcher
        $('#switcher').themeswitcher();

        function initDashboard() {

          // to make it possible to add widgets more than once, we create clientside unique id's
          // this is for demo purposes: normally this would be an id generated serverside
          var startId = 100;

          var dashboard = $('#dashboard').dashboard({
            // layout class is used to make it possible to switch layouts
            layoutClass:'layout',
            // feed for the widgets which are on the dashboard when opened
            json_data : {
              url: "/Synopsis_Tools/dashboard2/ajaxData.php?op=get_widgets_by_column"
            },
            // json feed; the widgets whcih you can add to your dashboard
            addWidgetSettings: {
              widgetDirectoryUrl:"/Synopsis_Tools/dashboard2/ajax/listWidget-xml_response"
            },

            // Definition of the layout
            // When using the layoutClass, it is possible to change layout using only another class. In this case
            // you don't need the html property in the layout

            layouts :
              [
                { title: "Layout1",
                  id: "layout1",
                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout1.png",
                  html: '<div class="layout layout-a"><div class="column first column-first"></div></div>',
                  classname: 'layout-a'
                },
                { title: "Layout2",
                  id: "layout2",
                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout2.png",
                  html: '<div class="layout layout-aa"><div class="column first column-first"></div><div class="column second column-second"></div></div>',
                  classname: 'layout-aa'
                },
                { title: "Layout3",
                  id: "layout3",
                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout3.png",
                  html: '<div class="layout layout-ba"><div class="column first column-first"></div><div class="column second column-second"></div></div>',
                  classname: 'layout-ba'
                },
                { title: "Layout4",
                  id: "layout4",
                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout4.png",
                  html: '<div class="layout layout-ab"><div class="column first column-first"></div><div class="column second column-second"></div></div>',
                  classname: 'layout-ab'
                },
                { title: "Layout5",
                  id: "layout5",
                  image: "/synopsys_dashboard/dashboard2/demo/layouts/layout5.png",
                  html: '<div class="layout layout-aaa"><div class="column first column-first"></div><div class="column second column-second"></div><div class="column third column-third"></div></div>',
                  classname: 'layout-aaa'
                }
              ]

          }); // end dashboard call

          // binding for a widgets is added to the dashboard
          dashboard.element.live('dashboardAddWidget',function(e, obj){
            var widget = obj.widget;

            dashboard.addWidget({
              "id":startId++,
              "title":widget.title,
              "url":widget.url,
              "metadata":widget.metadata
              }, dashboard.element.find('.column:first'));
          });

          // the init builds the dashboard. This makes it possible to first unbind events before the dashboars is built.
          dashboard.init();
        }
      });
      

    </script>

    <link rel="stylesheet" type="text/css" href="/Synopsis_Tools/dashboard2/themes/default/dashboardui.css" />
    <link rel="stylesheet" type="text/css" href="/Synopsis_Tools/dashboard2/themes/default/jquery-ui-1.8.2.custom.css" />

  </head>

  <body>

<!--  <div class="header_tile_image">
    <div class="headerbox">
      <div id="switcher"></div>
    </div>
    <div class="headerlinks">
      <a class="openaddwidgetdialog headerlink" href="#">Add Widget</a>&nbsp;<span class="headerlink">|</span>&nbsp;
      <a class="editlayout headerlink" href="#">Edit layout</a>
    </div>
  </div>-->
      
      <div class="butAction ui-widget-header ui-corner-all ui-state-default" style="padding: 5px 10px; width: 290px;"><em><span class="ui-icon ui-icon-info" style="float: left; margin: -1px 3px 0px 0px"></span><a class="openaddwidgetdialog" href="#">Ajouter des widgets à votre tableau de bord.</a></em></div>


  <div id="dashboard" class="dashboard">
    <!-- this HTML covers all layouts. The 5 different layouts are handled by setting another layout classname -->
    <div class="layout">
      <div class="column first column-first"></div>
      <div class="column second column-second"></div>
      <div class="column third column-third"></div>
    </div>
  </div>
EOF;
    }

}

/* Pour la compatibilté avec les module version 1  */

function sanitize_string($str, $newstr = '_') {
    $forbidden_chars_to_underscore = array(" ", "'", "/", "\\", ":", "*", "?", "\"", "<", ">", "|", "[", "]", ",", ";", "=");
    //$forbidden_chars_to_remove=array("(",")");
    $forbidden_chars_to_remove = array();

    return str_replace($forbidden_chars_to_underscore, $newstr, str_replace($forbidden_chars_to_remove, "", $str));
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
    if ($conf->global->MAIN_MODULE_BABELGA == 1 || $conf->global->MAIN_MODULE_BABELGMAO) {
        $contrat = new Contrat($db);
        $contratTmp = new Contrat($db);
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
                $contrat = new Contrat($db);
                break;
        }
    } else {
        $contrat = new Contrat($db);
    }
    return ($contrat);
}

function getTypeContrat_noLoad($id) {
    global $db;
    $requete = "SELECT * FROM llx_contrat WHERE rowid = " . $id;
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
    if ($conf->global->MAIN_MODULE_PROCESS) {
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

function getTypeAndId() {
    if (stripos($_SERVER['REQUEST_URI'], "compta/facture") != false) {
        $element_type = 'facture';
        $element_id = $_REQUEST['facid'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "societe/soc.php")
            || stripos($_SERVER['REQUEST_URI'], "comm/fiche.php?socid=")
            || stripos($_SERVER['REQUEST_URI'], "comm/prospect/fiche.php?socid=")) {
        $element_type = 'societe';
        $element_id = $_REQUEST['socid'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "product/fiche.php") != false) {
        $element_type = 'product';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "projet/tasks/task.php") != false) {
        $element_type = 'tache';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "projet/") != false) {
        $element_type = 'projet';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "commande/") != false) {
        $element_type = 'commande';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "compta/bank/") != false) {
        $element_type = 'banque';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "fichinter/") != false) {
        $element_type = 'FI';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "Synopsis_DemandeInterv/") != false) {
        $element_type = 'DI';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "contrat/") != false) {
        $element_type = 'contrat';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "user/fiche.php") != false) {
        $element_type = 'user';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "comm/propal.php") != false) {
        $element_type = 'propal';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "admin/Synopsis_Chrono") != false) {
        $element_type = 'configChrono';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "Synopsis_Chrono") != false) {
        $element_type = 'chrono';
        $element_id = $_REQUEST['id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "Synopsis_Process") != false) {
        $element_type = 'process';
        $element_id = $_REQUEST['process_id'];
    } elseif (stripos($_SERVER['REQUEST_URI'], "ndfp") != false) {
        $element_type = 'ndfp';
        $element_id = $_REQUEST['id'];
    }
    return array($element_type, $element_id);
}


function getAdresseLivraisonComm($commId) {
    global $db, $langs;
    $return = '';
    $sql = "SELECT a.* FROM `llx_element_contact` c, llx_c_type_contact t, llx_socpeople a WHERE `fk_c_type_contact` = t.rowid AND t.code = 'SHIPPING' AND a.rowid = c.fk_socpeople AND c.`element_id` = " . $commId;

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


function addElementElement($typeS, $typeD, $idS, $idD){
    global $db;
    $req = "INSERT INTO llx_element_element (sourcetype, targettype, fk_source, fk_target) VALUES ('".$typeS."', '".$typeD."', ".$idS.", ".$idD.")";
    return $db->query($req);
    
}
function delElementElement($typeS, $typeD, $idS = null, $idD = null){
    global $db;
    $req = "DELETE FROM llx_element_element WHERE sourcetype = '".$typeS."' AND targettype = '".$typeD."'";
    if(isset($idS))
        $req .= " AND fk_source = ".$idS;
    if(isset($idD))
        $req .= " AND fk_target = ".$idD;
    $db->query($req);  
}
function getElementElement($typeS, $typeD, $idS = null, $idD = null){
    global $db;
    $req = "SELECT * FROM llx_element_element WHERE sourcetype = '".$typeS."' AND targettype = '".$typeD."'";
    if(isset($idS))
        $req .= " AND fk_source = ".$idS;
    if(isset($idD))
        $req .= " AND fk_target = ".$idD;
    $sql = $db->query($req);
    $tab = array();
    while($result = $db->fetch_object($sql)){
        $tab[] = array("s"=>$result->fk_source, "d"=>$result->fk_target);
    }
    return $tab;
}
function setElementElement($typeS, $typeD, $idS, $idD){
    delElementElement($typeS, $typeD, $idS);
    return addElementElement($typeS, $typeD, $idS, $idD);
}




include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
class Synopsis_Commande extends Commande {

    function fetch($id) {
        $return = parent::fetch($id);
        $sql = $this->db->query("SELECT * FROM llx_Synopsis_commande WHERE rowid = " . $id);
        $result = $this->db->fetch_object($sql);
        $this->logistique_ok = $result->logistique_ok;
        $this->logistique_statut = $result->logistique_statut;
        $this->finance_ok = $result->finance_ok;
        $this->finance_statut = $result->finance_statut;
        $this->logistique_date_dispo = $result->logistique_date_dispo;
        return $return;
    }

    function fetch_lines($only_product = 0) {
        parent::fetch_lines($only_product);
//        $this->lines=array();

        foreach ($this->lines as $id => $line) {
            $newLine = new Synopsis_OrderLine($this->db);
            $newLine->fetch($this->lines[$id]->id);
            $this->lines[$id] = $newLine;
        }
        return;
        $sql = 'SELECT l.rowid FROM ' . MAIN_DB_PREFIX . 'commandedet as l';
        $sql.= ' WHERE l.fk_commande = ' . $this->id;
        if ($only_product)
            $sql .= ' AND p.fk_product_type = 0';
        $sql .= ' ORDER BY l.rang';

        dol_syslog("Commande::fetch_lines sql=" . $sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);

                $line = new OrderLine($this->db);

                $line->fetch($objp->rowid);

                $this->lines[$i] = $line;

                $i++;
            }
            $this->db->free($result);
            parent::fetch_lines($only_product);
            return 1;
        } else {
            $this->error = $this->db->error();
            dol_syslog('Commande::fetch_lines: Error ' . $this->error, LOG_ERR);
            return -3;
        }
    }
    
    
    
    
    //La commande est elle membre d'un groupe
    public function isGroupMember()
    {
        return false;
        $requete = "SELECT Babel_commande_grp.id as gid
                      FROM Babel_commande_grpdet,
                           Babel_commande_grp
                     WHERE Babel_commande_grp.id=Babel_commande_grpdet.commande_group_refid
                       AND command_refid = ".$this->id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0)
        {
            $res = $this->db->fetch_object($sql);
            require_once(DOL_DOCUMENT_ROOT."/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
            $comGrp = new CommandeGroup($this->db);
            $comGrp->fetch($res->gid);
            $this->OrderGroup = $comGrp;
            return ($comGrp);
        }
        return false;
    }
    public function listGroupMember($excludeMyself = true)
    {
        $ret = $this->isGroupMember();
        if ($ret)
        {
            $this->OrderGroup = $ret;
            $arr = array();
            foreach($ret->commandes as $key=>$commande)
            {
                if ($excludeMyself && $commande->id == $this->id) {
                    continue;
                 }
                 $arr[$key]=$commande;
            }
            return($arr);
        }
        return array();
    }
    
    
    function fetch_group_lines($only_product=0,$only_service=0,$only_contrat=0,$only_dep=0,$srv_dep=0){
        return $this->fetch_lines($only_product);
    }

}

class Synopsis_OrderLine extends OrderLine {

    function fetch($id) {
        $return = parent::fetch($id);
        $sql = $this->db->query("SELECT * FROM llx_Synopsis_commandedet WHERE rowid = " . $id);
        $result = $this->db->fetch_object($sql);
        $this->logistique_ok = $result->logistique_ok;
        $this->finance_ok = $result->finance_ok;
        $this->coef = $result->coef;
        $this->logistique_date_dispo = $result->logistique_date_dispo;
        return $return;
    }

}



    function asPosition($str){
        $tab = explode(" ", $str);
        $tab[0] = intval($tab[0]);
        if(is_int($tab[0]) && $tab[0] > 0)
            return array(0=>$tab[0],1=>  str_replace($tab[0]." ", "", $str));
        return false;
    }
?>
