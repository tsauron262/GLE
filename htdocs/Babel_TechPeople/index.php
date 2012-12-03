<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
 //box => derniere intervention
 //derniere ndf
 //derniere ndf valider

require("./pre.inc.php");


$transAreaType = $langs->trans("Tiers");


$jQueryDashBoardPath = DOL_URL_ROOT.'/Synopsis_Common/jquery/dashboard/';

$js = '
    <script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>
    <script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>
    <script type="text/javascript" src="'.$jQueryDashBoardPath.'jquery.dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'dashboard.css" />

    <script type="text/javascript" src="'.$jQueryDashBoardPath.'dashboard.js"></script>
    <link rel="stylesheet" type="text/css" href="'.$jQueryDashBoardPath.'demo.css" />
    <script type="text/javascript">var userid='.$user->id.';</script>
    <script type="text/javascript">var dashtype="8";</script>

';

    llxHeader($js,$langs->trans("Dep/Interv."),1);




    print '<div class="titre">Mon tableau de bord - Deplacements/Interventions</div>';
    print "<br/>";
    print "<br/>";
    print "<div style='padding: 5px 10px; width: 270px;' class='butAction ui-state-default ui-widget-header ui-corner-all'><em><span style='float: left; margin: -1px 3px 0px 0px' class='ui-icon ui-icon-info'></span><a href='#' onClick='addWidget()'>Ajouter des widgets &agrave; votre tableau de bord.</a></em></div>";
    print "<br/>";
    print '<div id="dashboard">';
    print '  You need javascript to use the dashboard.';
    print '</div>';

$db->close();

llxFooter('$Date: 2008/06/19 08:50:59 $ - $Revision: 1.60 $');


//require_once(DOL_DOCUMENT_ROOT."/boxes.php");


//// Simule le menu par defaut sur Home
//if (! isset($_GET["mainmenu"])) $_GET["mainmenu"]="home";
//
//$infobox=new InfoBox($db);




///*
//* Affichage page
//*/
//
//llxHeader();
//
//
//print_fiche_titre($langs->trans("TechPeopleArea"));
//
//if (! empty($conf->global->MAIN_MOTD))
//{
//    $conf->global->MAIN_MOTD=eregi_replace('<br[ /]*>$','',$conf->global->MAIN_MOTD);
//    if (! empty($conf->global->MAIN_MOTD))
//    {
//        print "\n<!-- Start of welcome text -->\n";
//        print '<table width="100%" class="notopnoleftnoright"><tr><td>';
//        print dol_htmlentitiesbr($conf->global->MAIN_MOTD);
//        print '</td></tr></table><br>';
//        print "\n<!-- End of welcome text -->\n";
//    }
//}
//
//
///*
// * Tableau de bord d'etats Dolibarr (statistiques)
// * Non affiche pour un utilisateur externe
// */
//if ($user->societe_id == 0)
//{
//    print '<br>';
//    print '<table class="noborder" width="100%">';
//
//
//    $var=true;
//
//    //print memory_get_usage()."<br>";
//
//    // Boucle et affiche chaque ligne du tableau
//    foreach ($keys as $key=>$val)
//    {
//        if ($conditions[$key])
//        {
//            $classe=$classes[$key];
//            // Cherche dans cache si le load_state_board deja realise
//            if (! isset($boardloaded[$classe]) || ! is_object($boardloaded[$classe]))
//            {
//                include_once($includes[$key]);
//
//                $board=new $classe($db);
//                $board->load_state_board($user);
//                $boardloaded[$classe]=$board;
//            }
//            else $board=$boardloaded[$classe];
//
//            $var=!$var;
//            if ($langfile[$key]) $langs->load($langfile[$key]);
//            $title=$langs->trans($titres[$key]);
//            print '<tr '.$bc[$var].'><td width="16">'.img_object($title,$icons[$key]).'</td>';
//            print '<td>'.$title.'</td>';
//            print '<td align="right"><a href="'.$links[$key].'">'.$board->nb[$val].'</a></td>';
//            print '</tr>';
//
//            //print $includes[$key].' '.memory_get_usage()."<br>";
//        }
//    }
//
//    print '</table>';
//}
//
//
///*
// * Dolibarr Working Board
// */
//
//$nboflate=0;
//$var=true;
//
////
//// Ne pas inclure de sections sans gestion de permissions
////
//
//
///*
// * Affichage des boites
// *
// */
//$boxarray=$infobox->listboxes("1",$user);       // 0=valeur pour la page accueil
//$boxid_left = array();
//$boxid_right = array();
//
//if (sizeof($boxarray))
//{
//    print '<br>';
//    print_fiche_titre($langs->trans("Bo&icirc;tes d'informations"));
//    print '<table width="100%" class="notopnoleftnoright">';
//    print '<tr><td class="notopnoleftnoright">'."\n";
//
//    // Affichage colonne gauche
//    print "\n<!-- Box container -->\n";
//    print '<table width="100%" style="border-collapse: collapse; border: 0px; margin: 0px; padding: 0px;"><tr><td width="50%" valign="top">'."\n";
//    print '<div id="left">'."\n";
//
//    $ii=0;
//    foreach ($boxarray as $key => $box)
//    {
//        //print "xxx".$key."-".$value;
//        if (eregi('^A',$box->box_order)) // colonne A
//        {
//            $ii++;
////            print 'box_id '.$boxarray[$ii]->box_id.' ';
////        print 'box_order '.$boxarray[$ii]->box_order.'<br>';
//            $boxid_left[$key] = $boxarray[$key]->box_id;
//            // Affichage boite key
//            $box->loadBox($conf->box_max_lines);
//            $box->showBox();
//        }
//    }
//
//    // If no box on left, we show add an invisible empty box
//    if ($ii==0)
//    {
//        $box->box_id='A';
//        $box->info_box_head=array();
//        $box->info_box_contents=array();
//        $box->showBox();
//    }
//
//    print "</div>\n";
//    print '</td>';
//    print "<!-- End box container -->\n";
//    print "\n";
//
//    // Affichage colonne droite
//    print "\n<!-- Box container -->\n";
//    print '<td width="50%" valign="top">';
//    //    print '<div id="right" style="position: absolute; display: block; width: 50%; padding: 0px; margin: 0px; float: right;">'."\n";
//    print '<div id="right">'."\n";
//
//    $ii=0;
//    $boxarray=$infobox->listboxes("1",$user);       // on regenere la liste pour eviter les erreurs avec les empty box
//    foreach ($boxarray as $key => $box)
//    {
//        if (eregi('^B',$box->box_order)) // colonne B
//        {
//            $ii++;
//            //print 'key:'.$key.'<br>';
//            //print 'box_id '.$boxarray[$key]->box_id.' ';
//            //print 'box_order '.$boxarray[$key]->box_order.'<br>';
//            $boxid_right[$key] = $boxarray[$key]->box_id;
//            // Affichage boite key
//            $box->loadBox($conf->box_max_lines);
//            $box->showBox();
//        }
//    }
//
//    // If no box on right, we show add an invisible empty box
//    if ($ii==0)
//    {
//        $box->box_id='B';
//        $box->info_box_head=array();
//        $box->info_box_contents=array();
//        $box->showBox();
//    }
//
//    print "</div>\n";
//    print "</td></tr></table>\n";
//    print "<!-- End box container -->\n";
//    print "\n";
//
//    print "</td></tr>";
//    print "</table>";
//}
//
//if ($conf->use_javascript_ajax)
//{
//    print "\n";
//    print '<script type="text/javascript" language="javascript">
//    function updateOrder(){
//    var left_list = cleanSerialize(Sortable.serialize(\'left\'));
//    var right_list = cleanSerialize(Sortable.serialize(\'right\'));
//    var boxorder = \'A:\' + left_list + \'-B:\' + right_list;
//    //alert( \'boxorder=\' + boxorder );
//    var userid = \''.$user->id.'\';
//    var url = "ajaxbox.php";
//    o_options = new Object();
//    o_options = {asynchronous:true,method: \'get\',parameters: \'boxorder=\' + boxorder + \'&userid=\' + userid};
//    var myAjax = new Ajax.Request(url, o_options);
//  }'."\n";
//    print '// <![CDATA['."\n";
//
//    print 'Sortable.create(\'left\', {'."\n";
//    print 'tag:\'div\', '."\n";
//    print 'containment:["left","right"], '."\n";
//    print 'constraint:false, '."\n";
//    print "handle: 'boxhandle',"."\n";
//    print 'onUpdate:updateOrder';
//    print "});\n";
//
//    print 'Sortable.create(\'right\', {'."\n";
//    print 'tag:\'div\', '."\n";
//    print 'containment:["right","left"], '."\n";
//    print 'constraint:false, '."\n";
//    print "handle: 'boxhandle',"."\n";
//    print 'onUpdate:updateOrder';
//    print "});\n";
//
//    print '// ]]>'."\n";
//    print '</script>'."\n";
//}
//
//// Juste pour eviter bug IE qui reorganise mal div precedents si celui-ci absent
//if (! $conf->browser->firefox)
//{
//    print '<div class="tabsAction">&nbsp;</div>';
//}


//$db->close();
//
//llxFooter('$Date: 2008/08/08 17:58:50 $ - $Revision: 1.116 $');
?>