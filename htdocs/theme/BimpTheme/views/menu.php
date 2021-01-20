
<?php
global $user, $conf;

//die(print_r($_GET, 1));

//dol_hide_leftmenu

echo '<div class="wrapper">';

//echo '<pre>';
//die(print_r($_SESSION, 1));

    if(!GETPOST("dol_hide_topmenu", "int") and !GETPOST("dol_hide_leftmenu", "int")) {
        
        //echo '<pre>';print_r($user); echo '</pre>';
        displayNoReadMessages();
        
        echo '    
            <header class="header-top" header-theme="light">
                <div class="container-fluid" >
                    <div class="d-flex justify-content-between">
                        <div class="top-menu d-flex align-items-center pull-left">
                        
                            <!-- Bouton menu responsive -->
                            <button type="button" id="responsiveButton" class="btn-icon mobile-nav-toggle"><span></span></button>
                            

                            <!-- Barre de recherche -->
                            ';

                        global $hookmanager, $langs;

                        if (!is_object($form))
                            $form = new Form($db);

                        $selected = -1;
                        $usedbyinclude = 1;
                        include_once DOL_DOCUMENT_ROOT . '/core/ajax/selectsearchbox.php'; // This set $arrayresult
                        echo $form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT . '/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'vmenusearchselectcombo', 1, $langs->trans("Search"), 1);

                        echo '
                            <button type="button" id="navbar-fullscreen" class="nav-link"><i class="ik ik-maximize"></i></button>
                            <a type="button" href="' . DOL_URL_ROOT . '/" class="nav-link"><i class="ik ik-home"></i></a>

                        </div>
                        <div class="modifMenuTopRight">
                        
                            <div class="top-menu d-flex align-items-center pull-right">';
                        
                                    print displayMessageIcone();
                                    
                                    print displayAcountIcone();
                        
            echo '
                        </div> 
                    </div>
                </div>
            </header>

            <div class="page-wrap">';
                        
          
            echo '<div class="app-sidebar colored">
                    <div class="sidebar-header">
                        <a class="header-brand" href="' . DOL_URL_ROOT . '/">
                            <div id="logo-img" class="logo-img">
                               <img src="' . DOL_URL_ROOT . '/theme/BimpTheme/views/imgs/bimp-logo.png" class="header-brand-img" alt="Bimp logo" width="125">
                            </div>
                            <!--<span class="text">BIMP - ERP</span>-->
                        </a>
                        <button id="sidebarOpen" onclick="hideBimpLogo();" type="button" class="nav-toggle"><i data-toggle="expanded" class="ik ik-toggle-right toggle-icon"></i></button>
                        <button id="sidebarClose" class="nav-close"><i class="ik ik-x"></i></button>
                    </div>

                    <div class="sidebar-content">
                        <div class="nav-container">
                            <nav id="main-menu-navigation" class="navigation-main">
                                <div class="nav-lavel">Navigation</div>';
 

            


        global $db, $langs;
        $tableLangs = ["commercial"];

        foreach ($tableLangs as $nomLang){
            $langs->load($nomLang);
        }

        $sql = $db->query('SELECT * FROM `' . MAIN_DB_PREFIX . 'menu` WHERE `type` = "top" ORDER BY `position`');

        while ($ln = $db->fetch_object($sql)) {
            //$html .= getAllSubMenu($ln->rowid, $ln->mainmenu)
            //$html .=  synopsisHolidayMenu();
            $html .= displayMenuAndSubMenu($ln->rowid, 1);

        }


        echo $html;
        echo ' </nav>
            </div>
        </div>
    </div>';
}


?>
<!-- style forcé pour supprimé le filtre bleu (pas très beau) voir pour intégrer dans le css -->
<div class="main-content" style="background-color: white !important;">
    
<?php

    global $hookmanager;

    // Instantiate hooks of thirdparty module
    $hookmanager->initHooks(array('toprightmenu'));

    // Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
    $parameters = array();
    $result = $hookmanager->executeHooks('printTopRightMenu', $parameters);    // Note that $action and $object may have been modified by some hooks
    if (is_numeric($result)) {
        if ($result == 0)
            $toprightmenu .= $hookmanager->resPrint;  // add
        else
            $toprightmenu = $hookmanager->resPrint;   // replace
    }
    else {
        $toprightmenu .= $result; // For backward compatibility
    }

    echo $toprightmenu;

    //Début/Fin inclusion fichier custom.css
    echo '<link rel="stylesheet" type="text/css" title="default" href="'.DOL_URL_ROOT.'/theme/BimpTheme/views/css/custom.css">'."\n";
    
    
?>

<!-- Début inclusion fichier custom.js -->    
<script>
    
    let sessionHideMenu = <?php echo BimpController::getSessionConf('hideMenu') == "true" ? 1 : 0 ?>;

</script>

<?php

    echo '<script src="'.DOL_URL_ROOT.'/theme/BimpTheme/views/js/custom.js"></script>';


//Fin inclusion fichier custom.js 
    
    
  
    
/***************
 * FONCTIONS
 ***************/
    
function synopsisHolidayMenu() {
    global $db, $langs, $user;

    $sqlHoliday = $db->query('SELECT * FROM `' . MAIN_DB_PREFIX . 'menu` WHERE `module` = "synopsisholiday" AND `fk_menu` = -1');
    
    while($res = $db->fetch_object($sqlHoliday)) {   
        
        //$res = $db->fetch_object($sqlHoliday);
        //icon bars par défaut
        //$menu_icon = (!is_null($res->icon)) ? $res->icon : "bars";
        
        $menu_icon = (!is_null($res->icon)) ? BimpRender::renderIcon($res->icon) : "";
        
        $html .= '<div class="nav-item">';
        $html .= '<a class="menu-item" href="' . DOL_URL_ROOT . '/' . $res->url . '">'.$menu_icon.'<span> ' . $langs->trans($res->titre) . '</span></a>';
        $html .= '</div>';

    } 
    
    return $html;
    
} 




//                                while($ln = $db->fetch_object($sql)){
//                                    $souMenu = getSousMenu($ln->rowid);
//
//                                    if($souMenu != '')
//                                        $html .=  '<div class="nav-item has-sub">
//                                            <a href="javascript:void(0)"><i class="ik ik-layers"></i><span> '. $langs->trans($ln->titre).'</span></a>
//                                            <div class="submenu-content">'.$souMenu.'</div>
//                                                                </div>';
//                                    else
//                                        $html .= '<div class="nav-item has-sub"><a href="'.DOL_URL_ROOT.$ln->url.'" class="menu-item"><i class="ik ik-layers"></i><span>'. $langs->trans($ln->titre).'</span></a></div>';
//
//
//                                }
//      


function getAllSubMenu($id, $mainmenu) {
    global $db, $langs, $user;
    $sql3 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "menu WHERE rowid != " . $id ." AND mainmenu = '" . $mainmenu . "' ");
    $sub = [];
    if ($res = $db->fetch_object($sql3)) {
        $under_menu = 0;
        $html .= '<div class="submenu-content">';
        while ($res = $db->fetch_object($sql3)) {
            if ($res->level == 0) {
                $html .= '<a href="' . DOL_URL_ROOT . '/' . $res->url . '"><i class="ik ik-layers"></i><span> ' . $langs->trans($res->titre) . '</span></a>';
                $under_menu = $res->rowid;
            }
            if ($res->fk_menu == $under_menu) {

                $html .= '<a class="menu-item" href="' . DOL_URL_ROOT . '/' . $res->url . '"><i class="ik ik-layers"></i><span> ' . $res->rowid . ' ' . $langs->trans($res->titre) . '</span></a>';
            }
          
        }
        $html .= '</div>';
    }

    return $html;
}


function getTotalNoReadMessage() {
    global $user;
    $messages = BimpObject::getInstance('bimpcore', "BimpNote");
    
    return count($messages->getList(["fk_user_dest" => $user->id, "viewed" => 0]));
}

function displayNoReadMessages() {
    global $user;
    $messages = BimpObject::getInstance('bimpcore', "BimpNote");
    $list = $messages->getList(["fk_user_dest" => $user->id, "viewed" => 0], 5);
  
    
    $html = "";
    
    foreach($list as $num => $infos) {
        $messages->fetch($infos['id']);
        $obj = BimpObject::getInstance($infos['obj_module'], $infos['obj_name'], $infos['id_obj']);
        
        $field_client = "fk_soc";
        switch($infos['obj_name']) {
            case 'BS_SAV':
                $field_client = 'id_client';
                break;
        }
        
        $client = BimpObject::getInstance('bimpcore', 'Bimp_Societe', $obj->getData($field_client));
        
        $html .= '  <div class="lnMsg">
            
                        <div onclick="loadModalObjectNotes($(this), \''.$infos['obj_module'].'\', \''.$infos['obj_name'].'\', \''.$infos['id_obj'].'\', \'chat\', true);" class="media">                  

                            <span class="d-flex">
                                <i class="' . BimpRender::renderIconClass($obj->params['icon']) . ' iconLeft"></i>
                            </span>

                            <span class="media-body">
                                
                                <span class="heading-font-family media-heading titreFilMsg"><span style="font-size: 15px !important;">'. $obj->getData('ref') .'</span><br/><span style="font-size: 11px !important;">'. $client->getNomUrl() .'</span></span><br/>
                                <div class="media-content" style="font-size: 12px !important; padding: 4px !important; max-width: 100% !important;"><div class="" style="color: #343a40; padding-left: 15px;border-left: 3px solid #343a40; font-size: 12px !important; max-width: 100% !important;">'.$infos['content'].' - <span style="font-size: 10px !important;"><i class="far fa-clock"></i> '.$infos['date_create'].'</span></div><span style="margin-left: 2%; font-size: 10px !important;">'.$messages->displayAuthor().'</span></div>
                                    
                            </span>
                        
                        </div>
                        
                    </div>';
    }
    
    return $html;

    //    $html = print_r($list, 1);
    //    
    //    return $html;
}


//fonction pour menu et sous menu
function displayMenuAndSubMenu($id, $niveau = 1) {
    //on charge db, langs et user
    global $db, $langs, $user;
    $html = $htmlSub = "";
    //récupération id du menu
    $sql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "menu WHERE rowid = " . $id);
    if ($db->num_rows($sql) > 0) {
        $res = $db->fetch_object($sql);
        
        if($res->perms != '') {
            $test = 'if('. $res->perms .') {  }else{ return ""; }';

            eval($test);
        }
        
      
        if ($res->langs != '')
            $langs->load($res->langs);
        
        $sub = [];
        $sql2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "menu WHERE fk_menu = " . $id . " ORDER BY position ASC");
        if ($db->num_rows($sql2) > 0) {
            
            //icon bars par défaut
            $menu_icon = (!is_null($res->icon)) ? $res->icon : "bars";
            
            $htmlSub .= '<div class="submenu-content">';
            $htmlSub .= '<a class="menu-item" href="' . DOL_URL_ROOT . '/' . $res->url . '"><span> ' . $langs->trans($res->titre) . '</span></a>';
            
            while ($res2 = $db->fetch_object($sql2)) {
                $htmlSub .= displayMenuAndSubMenu($res2->rowid, $niveau + 1);
            }
            
            
            $htmlSub .= '</div>';

            
            $html .= '<div class="nav-item has-sub">';
            $html .= '<a class="menu-item" href="javascript:void(0)">'.BimpRender::renderIcon($menu_icon).'<span> ' . $langs->trans($res->titre) . '</span></a>';
            
 //         $html .= '<div class="submenu-content">';
                            
            $html .= $htmlSub;               
            $html .= '</div>';
            //print_r($res->module["synopsisholiday"]);
            
        } else {
            
            //sinon pas d'icon
            $menu_icon = (!is_null($res->icon)) ? BimpRender::renderIcon($res->icon) : "";
            
            

            $html .= '<div class="nav-item">';
            $html .= '<a class="menu-item" href="' . DOL_URL_ROOT . '/' . $res->url . '">'.$menu_icon.'<span>' . $langs->trans($res->titre) . '</span></a>';
            
            /* POURQUOI ? 
            if($res->module["synopsisholiday"] && $res->leftmenu["holiday"])
                $html .= synopsisHolidayMenu();*/
            $html .= '</div>';
            
        }
                
    }

    return $html;
}

//                               function getSousMenu($idMenu){
//                                   global $db, $langs;
//                                   $html = '';
//                                    $sql2 = $db->query('SELECT * FROM `'.MAIN_DB_PREFIX.'menu` WHERE fk_menu = '.$idMenu.' GROUP BY `position`');
//                                    while($ln = $db->fetch_object($sql2)){
//                                        $souMenu = getSousMenu($ln->rowid);
//                                        
//                                        
//                                        if($souMenu != '')
//                                            $html .=  '<div class="nav-item has-sub">
//                                                <a href="javascript:void(0)"><i class="ik ik-layers"></i><span> '. $langs->trans($ln->titre).'</span></a>
//                                                <div class="submenu-content">'.$souMenu.'</div>
//                                                                    </div>';
//                                        else
//                                            $html .= '<div class="nav-item has-sub"><a href="'.DOL_URL_ROOT.$ln->url.'" class="menu-item"><i class="ik ik-layers"></i><span>'. $langs->trans($ln->titre).'</span></a></div>';
//                                    }
//                                    return $html;
//                                                          }

?>

<!--                                <div class="nav-item active">
                                    <a href="index.html"><i class="ik ik-bar-chart-2"></i><span>Dashboard</span></a>
                                </div>
                                <div class="nav-item">
                                    <a href="pages/navbar.html"><i class="ik ik-menu"></i><span>Navigation</span> <span class="badge badge-success">New</span></a>
                                </div>-->
<!--                                <div class="nav-item has-sub">
                                    <a href="javascript:void(0)"><i class="ik ik-layers"></i><span>Widgets</span> <span class="badge badge-danger">150+</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/widgets.html" class="menu-item">Basic</a>
                                        <a href="pages/widget-statistic.html" class="menu-item">Statistic</a>
                                        <a href="pages/widget-data.html" class="menu-item">Data</a>
                                        <a href="pages/widget-chart.html" class="menu-item">Chart Widget</a>
                                    </div>
                                </div>-->
<!--                                <div class="nav-lavel">UI Element</div>
                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-box"></i><span>Basic</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/ui/alerts.html" class="menu-item">Alerts</a>
                                        <a href="pages/ui/badges.html" class="menu-item">Badges</a>
                                        <a href="pages/ui/buttons.html" class="menu-item">Buttons</a>
                                        <a href="pages/ui/navigation.html" class="menu-item">Navigation</a>
                                    </div>
                                </div>-->
<!--                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-gitlab"></i><span>Advance</span> <span class="badge badge-success">New</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/ui/modals.html" class="menu-item">Modals</a>
                                        <a href="pages/ui/notifications.html" class="menu-item">Notifications</a>
                                        <a href="pages/ui/carousel.html" class="menu-item">Slider</a>
                                        <a href="pages/ui/range-slider.html" class="menu-item">Range Slider</a>
                                        <a href="pages/ui/rating.html" class="menu-item">Rating</a>
                                    </div>
                                </div>
                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-package"></i><span>Extra</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/ui/session-timeout.html" class="menu-item">Session Timeout</a>
                                    </div>
                                </div>
                                <div class="nav-item">
                                    <a href="pages/ui/icons.html"><i class="ik ik-command"></i><span>Icons</span></a>
                                </div>-->
<!--                                <div class="nav-lavel">Forms</div>
                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-edit"></i><span>Forms</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/form-components.html" class="menu-item">Components</a>
                                        <a href="pages/form-addon.html" class="menu-item">Add-On</a>
                                        <a href="pages/form-advance.html" class="menu-item">Advance</a>
                                    </div>
                                </div>
                                <div class="nav-item">
                                    <a href="pages/form-picker.html"><i class="ik ik-terminal"></i><span>Form Picker</span> <span class="badge badge-success">New</span></a>
                                </div>-->

<!--                                <div class="nav-lavel">Tables</div>
                                <div class="nav-item">
                                    <a href="pages/table-bootstrap.html"><i class="ik ik-credit-card"></i><span>Bootstrap Table</span></a>
                                </div>
                                <div class="nav-item">
                                    <a href="pages/table-datatable.html"><i class="ik ik-inbox"></i><span>Data Table</span></a>
                                </div>

                                <div class="nav-lavel">Charts</div>
                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-pie-chart"></i><span>Charts</span> <span class="badge badge-success">New</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/charts-chartist.html" class="menu-item active">Chartist</a>
                                        <a href="pages/charts-flot.html" class="menu-item">Flot</a>
                                        <a href="pages/charts-knob.html" class="menu-item">Knob</a>
                                        <a href="pages/charts-amcharts.html" class="menu-item">Amcharts</a>
                                    </div>
                                </div>-->

<!--                                <div class="nav-lavel">Apps</div>
                                <div class="nav-item">
                                    <a href="pages/calendar.html"><i class="ik ik-calendar"></i><span>Calendar</span></a>
                                </div>
                                <div class="nav-item">
                                    <a href="pages/taskboard.html"><i class="ik ik-server"></i><span>Taskboard</span></a>
                                </div>-->

<!--                                <div class="nav-lavel">Pages</div>

                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-lock"></i><span>Authentication</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/login.html" class="menu-item">Login</a>
                                        <a href="pages/register.html" class="menu-item">Register</a>
                                        <a href="pages/forgot-password.html" class="menu-item">Forgot Password</a>
                                    </div>
                                </div>
                                <div class="nav-item has-sub">
                                    <a href="#"><i class="ik ik-file-text"></i><span>Other</span></a>
                                    <div class="submenu-content">
                                        <a href="pages/profile.html" class="menu-item">Profile</a>
                                        <a href="pages/invoice.html" class="menu-item">Invoice</a>
                                    </div>
                                </div>
                                <div class="nav-item">
                                    <a href="pages/layouts.html"><i class="ik ik-layout"></i><span>Layouts</span><span class="badge badge-success">New</span></a>
                                </div>-->
<!--                                <div class="nav-lavel">Other</div>
                                <div class="nav-item has-sub">
                                    <a href="javascript:void(0)"><i class="ik ik-list"></i><span>Menu Levels</span></a>
                                    <div class="submenu-content">
                                        <a href="javascript:void(0)" class="menu-item">Menu Level 2.1</a>
                                        <div class="nav-item has-sub">
                                            <a href="javascript:void(0)" class="menu-item">Menu Level 2.2</a>
                                            <div class="submenu-content">
                                                <a href="javascript:void(0)" class="menu-item">Menu Level 3.1</a>
                                            </div>
                                        </div>
                                        <a href="javascript:void(0)" class="menu-item">Menu Level 2.3</a>
                                    </div>
                                </div>
                                <div class="nav-item">
                                    <a href="javascript:void(0)" class="disabled"><i class="ik ik-slash"></i><span>Disabled Menu</span></a>
                                </div>
                                <div class="nav-item">
                                    <a href="javascript:void(0)"><i class="ik ik-award"></i><span>Sample Page</span></a>
                                </div>-->
<!--                                <div class="nav-lavel">Support</div>
                                <div class="nav-item">
                                    <a href="javascript:void(0)"><i class="ik ik-monitor"></i><span>Documentation</span></a>
                                </div>
                                <div class="nav-item">
                                    <a href="javascript:void(0)"><i class="ik ik-help-circle"></i><span>Submit Issue</span></a>
                                </div>-->


<?php
function displayMessageIcone(){
    $nbMessage = getTotalNoReadMessage();
    $html = '';
                    $html .= '<div id="bimp_fixe_tabs"></div>';
                      $html .= '<div class="dropdown modifDropdown">
                                
                                    <a class="nav-link dropdown-toggle" href="#" id="notiDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    
                                        <i class="fa fa-envelope"></i>';
                                        
                                        if($nbMessage)
                                            $html .= '<span class="badge bg-danger">'.$nbMessage.'</span>';
                                            
                            $html .= '</a>
                                    
                                    <div class="dropdown-menu dropdown-menu-right notification-dropdown" aria-labelledby="notiDropdown">
                                    
                                        <h4 class="header">Notifications</h4>
                                        
                                        <div class="notifications-wrap">
                                            '. displayNoReadMessages().'
                                        </div>
                                        
                                        <div class="footer">
                                        
                                            <a href="javascript:void(0);">See all activity</a>
                                            
                                        </div>
                                        
                                    </div>
                                    
                                </div>
                            ';
  
  return $html;
}

function displayAcountIcone(){
    global $user;
    $html = '
                            <div class="dropdown dropdown-profile modifDropdown">
                                <a class="dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img class="avatar" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=userphoto&entity=1&file=' . substr($user->id, -1) . "/" . substr($user->id, -2, 1) .  "/" . $user->photo . '" alt=""></a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                                    <a class="dropdown-item" href="' . DOL_URL_ROOT . '/user/card.php?id=' . $user->id . '"><i class="ik ik-user dropdown-icon"></i> Mon profil</a>
                                    <!--<a class="dropdown-item" href="#"><i class="ik ik-settings dropdown-icon"></i> Settings</a>-->
                                    <!--<a class="dropdown-item" href="#"><span class="float-right"><span class="badge badge-primary">6</span></span><i class="ik ik-mail dropdown-icon"></i> Inbox</a>-->
                                    <!--<a class="dropdown-item" href="#"><i class="ik ik-navigation dropdown-icon"></i> Message</a>-->
                                    <a class="dropdown-item" href="' . DOL_URL_ROOT . '/user/logout.php"><i class="ik ik-power dropdown-icon"></i> Se déconnecter</a>
                                </div>
                            </div>';
    return $html;
}