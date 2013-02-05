<?php

include_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");

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

    static function getMenu() {
        global $conf;
        $return = '';
        $tabElem = getTypeAndId();
        $element_type = $tabElem[0];
        $element_id = $tabElem[1];
        if (isset($conf->global->MAIN_MODULE_SYNOPSISHISTO)) {
            histoNavigation::saveHisto($element_type, $element_id);
            $return .= histoNavigation::getBlocHisto();
        }
        if ($element_id > 0 && ($element_type == "commande" || $element_type == "DI" || $element_type == "FI")) {
            $return .= '<div class="blockvmenupair rouge">';
            $return .= '<div class="menu_titre">';
            $return .= '<a href="#" class="vmenu">Consigne Commande</a>';
            $return .= "</div>";
            $return .= '<div class="editable consigne">';
            global $db;
            $consigne = new consigneCommande($db);
            $consigne->fetch($element_type, $element_id);
            $return .= $consigne->note;
            $return .= "</div>";
            $return .= "</div>";
        }

        return $return;
    }

    static function getHeader() {
        $return = '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/Synopsis_Tools/global.css" />' . "\n";
        $return .= "<script type=\"text/javascript\">var DOL_URL_ROOT = '" . DOL_URL_ROOT . "';</script>\n";
        $return .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Tools/global.js"></script>';

        $nameFile = DOL_DATA_ROOT . "/special.css";
        if (is_file($nameFile)) {
            $css = file_get_contents($nameFile);
            $return .= "<style>" . $css . "</style>";
        }
        return $return;
    }

    static function footer() {
        global $conf;

        if (isset($conf->global->MAIN_MODULE_SYNOPSISDASHBOARD)) {
            if (stripos($_SERVER['REQUEST_URI'], "index.php") != false) {
                dashboard::getDashboard();
            }
        }
    }

    public static function getObj($type) {
        $tabResult = self::getObjAndMenu($type);
        return $tabResult[0];
    }

    public static function getObjAndMenu($type) {
        global $db, $conf;
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
                    if (isset($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE))
                        $obj = new Synopsis_Commande($db);
                    else {
                        require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                        $obj = new Commande($db);
                    }
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

}

class consigneCommande {

    var $note = '';
    var $rowid = 0;

    public function consigneCommande($db) {
        $this->db = $db;
    }

    public function fetch($element_type, $element_id) {
        global $conf;
        $db = $this->db;
        if ($element_id > 0) {
            $obj = synopsisHook::getObj($element_type);
            $obj->fetch($element_id);
            if ($element_type == "commande") {
                $id_comm = $element_id;
            } elseif ($element_type == "FI" || $element_type == "DI") {
                $id_comm = $obj->fk_commande;
            }

            if (isset($id_comm) && $id_comm > 0) {
                $comm = synopsisHook::getObj("commande");
                $comm->fetch($id_comm);
                if (isset($conf->global->MAIN_MODULE_SYNOPSISPREPACOMMANDE) && $comm->isGroupMember())
                    $this->fk_group = $comm->OrderGroup->id;
                else
                    $this->fk_comm = $id_comm;
                $this->init();
            }
        }
    }

    public function init() {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commande_consigne WHERE ";
        if ($this->fk_group)
            $sql .= " fk_group = " . $this->fk_group;
        else
            $sql .= " fk_comm = " . $this->fk_comm;
        $result = $this->db->query($sql);
        if ($this->db->num_rows($result) > 0) {
            $ligne = $this->db->fetch_object($result);
            $this->note = $ligne->note;
            $this->rowid = $ligne->rowid;
        }
        if ($this->note == "") {
            $this->note = "Cliquez pour éditer";
        }
    }

    public function setNote($note) {
        $this->note = $note;
        if ($this->rowid == 0) {
            if ($this->fk_group) {
                $champ = "fk_group";
                $val = $this->fk_group;
            } else {
                $champ = "fk_comm";
                $val = $this->fk_comm;
            }
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_commande_consigne (" . $champ . ") VALUES (" . $val . ")";
            $result = $this->db->query($sql);
            $this->rowid = $this->db->last_insert_id($result);
        }
        $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commande_consigne SET note ='" . $this->note . "' WHERE rowid = " . $this->rowid;
        $result = $this->db->query($sql);
    }

}

class Synopsis_Commande extends Commande {

    function fetch($id, $ref = '', $ref_ext = '', $ref_int = '') {
        $return = parent::fetch($id, $ref, $ref_ext, $ref_int);
        $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commande WHERE rowid = " . $id);
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
    public function isGroupMember() {
//        return false;
        $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_commande_grp.id as gid
                      FROM " . MAIN_DB_PREFIX . "Synopsis_commande_grpdet,
                           " . MAIN_DB_PREFIX . "Synopsis_commande_grp
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_commande_grp.id=" . MAIN_DB_PREFIX . "Synopsis_commande_grpdet.commande_group_refid
                       AND command_refid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0) {
            $res = $this->db->fetch_object($sql);
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
            $comGrp = new CommandeGroup($this->db);
            $comGrp->fetch($res->gid);
            $this->OrderGroup = $comGrp;
            return ($comGrp);
        }
        return false;
    }

    public function listGroupMember($excludeMyself = true) {
        $ret = $this->isGroupMember();
        if ($ret) {
            $this->OrderGroup = $ret;
            $arr = array();
            foreach ($ret->commandes as $key => $commande) {
                if ($excludeMyself && $commande->id == $this->id) {
                    continue;
                }
                $arr[$key] = $commande;
            }
            return($arr);
        }
        return array();
    }

    function fetch_group_lines($only_product = 0, $only_service = 0, $only_contrat = 0, $only_dep = 0, $srv_dep = 0) {
        $lines = array();
        $comms = $this->listGroupMember(false);
        if (isset($comms[0])) {
            foreach ($comms as $commande) {
                $commande->fetch_lines();
                foreach ($commande->lines as $ligne) {
                    $lines[$i] = $ligne;
                    $i++;
                }
//            $this->lines = array_merge($this->lines, $commande->lines);
            }
            $this->lines = $lines;
        }
        else
            $this->fetch_lines($only_product);
        return true;
//        return $this->fetch_lines($only_product);
    }

}

class Synopsis_OrderLine extends OrderLine {

    function fetch($id) {
        $return = parent::fetch($id);
        $sql = $this->db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_commandedet WHERE rowid = " . $id);
        $result = $this->db->fetch_object($sql);
        $this->logistique_ok = $result->logistique_ok;
        $this->finance_ok = $result->finance_ok;
        $this->coef = $result->coef;
        $this->logistique_date_dispo = $result->logistique_date_dispo;
        return $return;
    }

}

class histoNavigation {

    static function getBlocHisto() {
        global $db, $user, $conf;
//        if ($conf->global->MAIN_MODULE_BABELMINIHISTOUSER && $user->rights->MiniHisto->all->Afficher) {
        $return = '<div class="blockvmenupair">';
        $return .= '<div class="menu_titre">';
        $return .= '<a href="#" class="vmenu">Historique navigation</a>';
        $return .= "</div>";
        $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Histo_User
                     WHERE user_refid = " . $user->id .
                " AND ref != '' AND element_type != '' ORDER BY tms DESC" .
                (isset($conf->global->BABEL_MINIHISTO_LENGTH) && $conf->global->BABEL_MINIHISTO_LENGTH > 0 ? " LIMIT " . $conf->global->BABEL_MINIHISTO_LENGTH : " LIMIT 5");

        $sql = $db->query($requete);
        while ($res = $db->fetch_object($sql)) {
            //print '<a href="#" class="vsmenu">'..'</a>';
            $ret = self::histoUser($res);
            if ($ret)
                $return .= "<div class='menu_contenu'>  " . $ret . "</div>";
        }
        $return .= "</div>";
        return $return;
    }

    private static function histoUser($res) {
        $tabResult = synopsisHook::getObjAndMenu($res->element_type);
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

    static function saveHisto($element_type, $element_id) {
//        //saveHistoUser($fichinter->id, "FI", $fichinter->ref);


        if (isset($element_id) && isset($element_type) && $element_type != '' && $element_id > 0) {
            $obj = synopsisHook::getObj($element_type);
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

?>
