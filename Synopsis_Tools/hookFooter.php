<?php
    if (stripos($_SERVER['REQUEST_URI'], "index.php") != false){
        afficherDashboard();
        miniHisto();
    }
  function afficherDashboard() {
 /*   global $langs, $user;
    $jQueryDashBoardPath = DOL_URL_ROOT . '/Synopsis_Tools/dashboard/';


    if (stripos($_SERVER['REQUEST_URI'], "product/index.php") != false) {
        $type = ($_REQUEST['type'] . "x" == "x" ? -1 : $_REQUEST['type']) + 1;
        if ($type . "x" == "x") {
            $type = false;
        }
        if ($type == 2) {
            $dashType = 11;
            $titre = $langs->trans("Services");
        } else if ($type == 1) {
            $dashType = 10;
            $titre = $langs->trans("Products");
        } else {
            $dashType = 6;
            $titre = $langs->trans("ProductsAndServices");
        }
    } elseif (stripos($_SERVER['REQUEST_URI'], "societe/index.php") != false) {
        $dashType = 9;
        $titre = $langs->trans("Societe");
    } elseif (stripos($_SERVER['REQUEST_URI'], "comm/index.php") != false) {
        $dashType = 3;
        $titre = $langs->trans("Ventes");
    } else {
        $dashType = 0;
        $titre = $langs->trans("Accueil");
    }


    print '
    <script>
        var DOL_URL_ROOT="' . DOL_URL_ROOT . '";
        var DOL_DOCUMENT_ROOT="' . DOL_DOCUMENT_ROOT . '";
        var dashtype="' . $dashType . '";
        var userid=' . $user->id . ';
    </script>
    
    <script type="text/javascript" src="' . $jQueryDashBoardPath . 'jquery.dashboard.js"></script>
    <script type="text/javascript" src="' . $jQueryDashBoardPath . 'dashboard.js"></script>
    <script type="text/javascript" src="' . $jQueryDashBoardPath . '/Synopsis_Tools/dashboard2/jquery/jquery-1.3.2.min.js"></script>
        
    <link rel="stylesheet" type="text/css" href="' . $jQueryDashBoardPath . 'demo.css" />
    <link rel="stylesheet" type="text/css" href="' . $jQueryDashBoardPath . 'dashboard.css" />';
    print "<br/>";
    print "<br/>";
    print '<div class="titre">Mon tableau de bord - ' . $titre . '</div>';

    print "<br/>";
    print "<br/>";
    print "<a class='editLayout'>edit layout</a>";
    print "<div id='editLayout'></div>";
    print "<div style='padding: 5px 10px; width: 290px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';
    print '<div style="position: absolute; overflow: hidden; z-index: 1000; outline: 0px none; height: auto; left: 625px; top: 676px;" class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-addWidgetDialog"><div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix" unselectable="on" style="-moz-user-select: none;"><span class="ui-dialog-title" id="ui-dialog-title-addWidgetDialog" unselectable="on" style="-moz-user-select: none;">Ajouter un widget</span><a href="#" class="ui-dialog-titlebar-close ui-corner-all" role="button" unselectable="on" style="-moz-user-select: none;"><span class="ui-icon ui-icon-closethick" unselectable="on" style="-moz-user-select: none;">close</span></a></div><div id="addWidgetDialog" class="ui-dialog-content ui-widget-content"><table width="100%" cellpadding="5">                                                         <tbody><tr><th width="50%" class="ui-state-default ui-widget-header">Disponible                                                             </th><th width="50%" class="ui-state-default ui-widget-header">Ajouter                                                         </th></tr><tr><td valign="top" class="ui-widget-content">                                                                <ul id="Dispo" class="list">                                                              </ul></td><td valign="top" class="ui-widget-content">                                                                <ul id="Ajoute" class="list">                                                    </ul></td></tr></tbody></table><em>Cliquer sur le nom du module que vous voulez ajouter<em></em></em></div><div class="ui-resizable-handle ui-resizable-n" unselectable="on" style="-moz-user-select: none;"></div><div class="ui-resizable-handle ui-resizable-e" unselectable="on" style="-moz-user-select: none;"></div><div class="ui-resizable-handle ui-resizable-s" unselectable="on" style="-moz-user-select: none;"></div><div class="ui-resizable-handle ui-resizable-w" unselectable="on" style="-moz-user-select: none;"></div><div class="ui-resizable-handle ui-resizable-se ui-icon ui-icon-gripsmall-diagonal-se ui-icon-grip-diagonal-se" style="z-index: 1001; -moz-user-select: none;" unselectable="on"></div><div class="ui-resizable-handle ui-resizable-sw" style="z-index: 1002; -moz-user-select: none;" unselectable="on"></div><div class="ui-resizable-handle ui-resizable-ne" style="z-index: 1003; -moz-user-select: none;" unselectable="on"></div><div class="ui-resizable-handle ui-resizable-nw" style="z-index: 1004; -moz-user-select: none;" unselectable="on"></div><div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix"><button type="button" class="ui-state-default ui-corner-all">Ok</button><button type="button" class="ui-state-default ui-corner-all">Annuler</button></div></div>';*/
      
      
}  
?>    

      
      
   
      
      
      
      
      
    