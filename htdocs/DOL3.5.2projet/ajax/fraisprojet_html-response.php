<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 7 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fraisprojet_html-response.php
  * GLE-1.1
  */
  require_once('../../main.inc.php');
  //require_once(DOL_DOCUMENT_ROOT."/core/lib/ressource.lib.php");
$projId = $_REQUEST['projId'];

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("synopsisGene@synopsistools");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);
// Initialisation de l'objet Societe
//
//if (! ($user->rights->SynopsisRessources->SynopsisRessources->Utilisateur || $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa))
//{
//    accessforbidden();
//}
$soc = new Societe($db);
$soc->fetch($socid);
print '<html><head></head><body>';

//jqgrid
 //Affiche les ressources du projet
    //Date début, date fin
    //Cout sur la période
    //Cout achat
    //Affiche toutes les ressources de la societe
    //reprendre Synopsis_Ressources en ro


    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';

    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';

    $js .= "<style type='text/css'>body { position: static; }</style>";

    $js .= ' <style>#editmodgridListRessources1{ min-width: 300px;}</style>';

    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."user
                 WHERE statut = 1
              ORDER BY firstname, lastname";
    $sql = $db->query($requete);
    $jsEditopts["userincomp"]="";
     $jsEditopts["userincomp"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("Sélection ->"))))  . ";";
    while ($res = $db->fetch_object($sql))
    {
        $jsEditopts["userincomp"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->firstname . " ".$res->name)))  . ";";
    }
    $requete = "SELECT unix_timestamp(datef) as Fdatef,
                       ref,
                       rowid,
                       total_ht
                  FROM ".MAIN_DB_PREFIX."facture_fourn
                 WHERE fk_projet = ".$projId."
              ORDER BY ref";
    $sql = $db->query($requete);
    $jsEditopts["factFourn"]="";
    $jsEditopts["factFourn"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("Sélection ->"))))  . ";";
    while ($res = $db->fetch_object($sql))
    {
        $jsEditopts["factFourn"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->ref . " ".date('d/m/Y',$res->Fdatef) . " ".price($res->total_ht)."euro")))  . ";";
    }

    $requete = "SELECT unix_timestamp(date_valid) as Fdatef,
                       ref as ref,
                       rowid,
                       total_ht
                  FROM ".MAIN_DB_PREFIX."commande_fournisseur
                 WHERE fk_projet = ".$projId."
              ORDER BY ref";
    $sql = $db->query($requete);
    $jsEditopts["cmdFourn"]="";
    $jsEditopts["cmdFourn"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("Sélection ->"))))  . ";";
    while ($res = $db->fetch_object($sql))
    {
        $jsEditopts["cmdFourn"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->ref . " ".date('d/m/Y',$res->Fdatef) . " ".price($res->total_ht)." euro")))  . ";";
    }


    $requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."Synopsis_projet_task
                 WHERE fk_projet = ".$projId."
              ORDER BY title";
    $sql = $db->query($requete);
    $jsEditopts["taskRess"]="";
    $jsEditopts["taskRess"].=  "-1:" . preg_replace("/'/","\\'",utf8_decode(utf8_encode(html_entity_decode("Sélection ->"))))  . ";";
    while ($res = $db->fetch_object($sql))
    {
        $jsEditopts["taskRess"].= $res->rowid . ":" . preg_replace("/'/","\\'",utf8_decode(utf8_encode($res->title)))  . ";";
    }




    $jsEditopts["userincomp"] = preg_replace('/;$/','',$jsEditopts["userincomp"]);
    $jsEditopts["factFourn"] = preg_replace('/;$/','',$jsEditopts["factFourn"]);
    $jsEditopts["cmdFourn"] = preg_replace('/;$/','',$jsEditopts["cmdFourn"]);
    $jsEditopts["taskRess"] = preg_replace('/;$/','',$jsEditopts["taskRess"]);
    $jqgridJs ="<script type='text/javascript'>";
    $jqgridJs .= 'var gridimgpath="'.$imgPath.'/images/";';
    $jqgridJs .= 'var userId="'.$user->id.'";';
    $jqgridJs .= 'var socId="'.$soc->id.'"; ';
    $jqgridJs .= 'var campId="'.$campagneId.'"; ';
    $jqgridJs .= 'var DOL_URL_ROOT="'.DOL_URL_ROOT.'"; ';
    $jqgridJs .= "var userincomp='".$jsEditopts["userincomp"]."'\n";
    $jqgridJs .= "var factFourn='".$jsEditopts["factFourn"]."'\n";
    $jqgridJs .= "var cmdFourn='".$jsEditopts["cmdFourn"]."'\n";
    $jqgridJs .= "var projId='".$projId."'\n";
    $jqgridJs .= "var taskRess='".$jsEditopts['taskRess']."'\n";


$jqgridJs .= <<<EOF


    var grid;
    function launch_panel0(){
        jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr'])




        var get = "&projId="+projId;
        grid = jQuery("#gridListRessources1").jqGrid({
                datatype: "json",
                url: "ajax/fraisprojet_json.php?userId="+userId+get,
                colNames:['id',"D&eacute;signation", "T&acirc;che",'Acheteur','Date achat','Co&ucirc;t','Commande fournisseur','Facture fournisseur'],
                colModel:[  {name:'rowid',index:'rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                            {
                                name:'nom',
                                index:'nom',
                                width:80,
                                align:"center",
                                editable:true,
                                searchoptions:{
                                sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},
                                formoptions:{ elmprefix:"*  " },
                                editrules:{required:true},
                                editoptions: {
                                    style: 'width:100px',
                                }
                            },
                            {
                                name:'tache',
                                index:'tache',
                                display:"none",
                                align:"center",
                                editable:false,
                                hidden:false,
                                hidedlg:false,
                                edithidden: true,
                                search:true,
                                stype: 'select',
                                edittype: 'select',
                                editable: true,
                                formoptions:{ elmprefix:"*   " },
                                searchoptions:{sopt:['eq','ne'],  value: taskRess,},
                                editrules:{required:true,minValue:1,integer:true},
                                editoptions: {
                                    value: taskRess,
                                    style: 'width:100px',
                                }
                            },
                            {
                                name:'acheteur',
                                index:'acheteur',
                                width:150,
                                align: 'center',
                                stype: 'select',
                                edittype: 'select',
                                editable: true,
                                formoptions:{ elmprefix:"*  " },
                                editrules:{required:true,minValue:1,integer:true},
                                searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"],  value: userincomp,},
                                editoptions: {
                                    value: userincomp,
                                    style: 'width:100px',
                                },
                            },
                            {
                                name:'dateAchat',
                                index:'dateAchat',
                                width:90,
                                align:"center",
                                sorttype:"date",
                                editable:true,
                                sorttype:"date",
                                formatter:'date',
                                formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y"},
                                editable:true,
                                editrules:{required:true},
                                formoptions:{ elmprefix:"*  " },
                                searchoptions:{
                                    dataInit:function(el){
                                        jQuery(el).datepicker(jQuery.datepicker.regional['fr']);
                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                        //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                    },
                                    sopt:['eq','ne',"le",'lt',"ge","gt"],
                                },
                                editoptions:{
                                    style: 'width:100px',
                                    dataInit:function(el){
                                        jQuery(el).datepicker(jQuery.datepicker.regional['fr']);
                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                        //jQuery("#ui-timepicker-div").addClass("promoteZ");
                                    }
                                },
                            },
                            {
                                name:'cout',
                                index:'cout',
                                width:80,
                                align:"center",
                                editable:true,
                                formoptions:{ elmprefix:"*  " },
                                formatter: "currency",
                                editrules:{required:true,minValue:0,float:true},
                                searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                                editoptions: {
                                    style: 'width:100px',
                                }
                            },
                            {
                                name:'commandRef',
                                index:'commandRef',
                                width:80,
                                stype: 'select',
                                edittype: 'select',
                                align:"center",
                                formoptions:{ elmprefix:"   " },
                                editable:true,
                                searchoptions:{sopt:['eq','ne'], value: cmdFourn},
                                editoptions: {
                                    value: cmdFourn,
                                    style: 'width:100px',
                                }

                            },
                            {
                                name:'factureRef',
                                index:'factureRef',
                                width:80,
                                stype: 'select',
                                edittype: 'select',
                                align:"center",
                                formoptions:{ elmprefix:"   ", },
                                editable:true,
                                searchoptions:{sopt:['eq','ne'], value: factFourn},
                                editoptions: {
                                    value: factFourn,
                                    style: 'width:100px',
                                }
                            }

                          ],
                rowNum:10,
                rowList:[10,20,30],
                imgpath: gridimgpath,
                pager: jQuery('#gridListRessources1Pager'),
                sortname: 'id',
                mtype: "POST",
                editurl: "ajax/fraisprojet_ajax.php?usrId="+userId+get,
                viewrecords: true,
            autowidth: true,
                height: 500,
                sortorder: "desc",
                //multiselect: true,
                caption: "<span style='padding:4px; font-size: 13px;'>Frais de projet</span>",
                viewsortcols: true,
                loadComplete: function(){
                //    alert ("loadComplete");
                },
EOF;
//TODO add right
        $jqgridJs.= '    }).navGrid("#gridListRessources1Pager",'."\n";
        $jqgridJs.= '                   { add:true,'."\n";
        $jqgridJs.= '                     del:true,'."\n";
        $jqgridJs.= '                     edit:true,'."\n";
        $jqgridJs.= '                     search:true,'."\n";
        $jqgridJs.= '                     position:"left"'."\n";
        $jqgridJs.= '                     });'."\n";


    //

    $jqgridJs.= '}'."\n";

    $jqgridJs.= '</script>'."\n";


    $js .= $jqgridJs;
    print $js;
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Ressources/ressource.class.php');
    $ress = new Ressource($db);
//    llxHeader($js,$langs->trans("Ressources"),"Ressources","1");
//    top_menu($js,$langs->trans("Ressources"), "",1,false);
    //Affiche un jqgrid de suivie


    print '<div class="tabBar">';


    print '<table id="gridListRessources1" class="scroll" cellpadding="0" cellspacing="0"></table>';
    print '<div id="gridListRessources1Pager" class="scroll" style="text-align:center;"></div>';
    print "</div>";

    print "</body></html>";
?>