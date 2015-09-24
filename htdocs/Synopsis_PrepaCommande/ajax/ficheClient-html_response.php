<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 13 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fiche-xml_response.php
  * GLE-1.2
  */
    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
    require_once(DOL_DOCUMENT_ROOT ."/core/modules/commande/modules_commande.php");
    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
    if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
    if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');

    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/ui.jqgrid.css" />';
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$jspath.'/jqGrid-3.5/css/jquery.searchFilter.css" />';

    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';

    $langs->load('orders');
    $langs->load('sendings');
    $langs->load('companies');
    $langs->load('bills');
    $langs->load('propal');
    $langs->load("synopsisGene@synopsistools");
    $langs->load('deliveries');
    $langs->load('products');

    if (!$user->rights->commande->lire) accessforbidden();


    // Securite acces client
    $socid=0;
    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }
    if ($user->societe_id >0 && isset($_GET["id"]) && $_GET["id"]>0)
    {
        $commande = new Synopsis_Commande($db);
        $commande->fetch((int)$_GET['id']);
        if ($user->societe_id !=  $commande->socid) {
            accessforbidden();
        }
    }

print $js;
    $html = new Form($db);
    $formfile = new FormFile($db);

    $id = $_REQUEST['id'];
    if ($id > 0)
    {
        if ($mesg) print $mesg.'<br>';

        $commande = new Synopsis_Commande($db);
        if ( $commande->fetch($id) > 0)
        {
            $soc = new Societe($db);
            $soc->fetch($commande->socid);
            print "<script>var socid=".$commande->socid.';</script>';

            print '<table class="border" width="700" cellpadding=10>';

            // Societe
            print '<tr><th class="ui-state-default ui-widget-header">'.$langs->trans('Company').'</th>';
            print '<td colspan="4" class="ui-widget-content">'.utf8_encodeRien($soc->getNomUrl(1)).'</td>';
            print '</tr>';

//Date anniversaire des contrats
//Liste des commandes
//JqGrid
            print '<table id="list2"></table> <div id="pager2"></div>';
//Liste des contrats
//JqGrid
            print '<table id="list1"></table> <div id="pager1"></div>';

//Boutons Coordonnées, modifier

            print '</table><br>';
            print "\n";
        }
    }
if ($user->rights->SynopsisPrepaCom->all->AfficherPrix)
{
    print <<<EOF
    <script>
jQuery(document).ready(function(){
    jQuery('#list2').jqGrid({
        url:'ajax/xml/commandeHisto-json_response.php?userId='+userId+'&id='+socid,
        datatype: 'json',
        colNames:['rowid','Ref','Date', 'Total HT', 'Statut'],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'ref',index:'ref', width:90, align: 'left'},
                   {name:'date_commande',index:'date_commande', width:100,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                   {name:'total_ht',index:'total_ht', width:90, align: 'right'},
                   {name:'fk_statut',index:'fk_statut', width:90, align: 'right'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 700,
        height: 150,
        pager: '#pager2',
        sortname: 'rowid',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'desc',
        caption:'Historique commande'
    });
    jQuery('#list2').jqGrid('navGrid','#pager2',{edit:false,add:false,del:false,search:true});

    jQuery('#list1').jqGrid({
        url:'ajax/xml/contratHisto-json_response.php?userId='+userId+'&id='+socid,
        datatype: 'json',
        colNames:['rowid','Ref','Date', 'Statut'],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'ref',index:'ref', width:90, align: 'left'},
                   {name:'date_contrat',index:'date_contrat', width:100,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                   {name:'fk_statut',index:'fk_statut', width:90, align: 'right'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 700,
        height: 150,
        pager: '#pager1',
        sortname: 'rowid',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'desc',
        caption:'Historique contrat'
    });
    jQuery('#list1').jqGrid('navGrid','#pager1',{edit:false,add:false,del:false,search:true});

     jQuery.validator.addMethod(
                            'FRDate',
                            function(value, element) {
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                           );
    jQuery.validator.addMethod(
                            'FRDateNotRequired',
                            function(value, element) {
                                if (value+'x' == 'x') return true;
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                           );
    });
</script>
EOF;
} else{
        print <<<EOF
    <script>
jQuery(document).ready(function(){
    jQuery('#list2').jqGrid({
        url:'ajax/xml/commandeHisto-json_response.php?userId='+userId+'&id='+socid,
        datatype: 'json',
        colNames:['rowid','Ref','Date', 'Statut'],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'ref',index:'ref', width:90, align: 'left'},
                   {name:'date_commande',index:'date_commande', width:100,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                   {name:'fk_statut',index:'fk_statut', width:90, align: 'right'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 700,
        height: 150,
        pager: '#pager2',
        sortname: 'rowid',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'desc',
        caption:'Historique commande'
    });
    jQuery('#list2').jqGrid('navGrid','#pager2',{edit:false,add:false,del:false,search:true});

    jQuery('#list1').jqGrid({
        url:'ajax/xml/contratHisto-json_response.php?userId='+userId+'&id='+socid,
        datatype: 'json',
        colNames:['rowid','Ref','Date', 'Statut'],
        colModel:[ {name:'rowid',index:'rowid', width:55, hidden: true,hidedlg: true, search: false},
                   {name:'ref',index:'ref', width:90, align: 'left'},
                   {name:'date_contrat',index:'date_contrat', width:100,
                            align:'center',
                            sorttype:'date',
                            formatter:'date',
                            formatoptions:{srcformat:'Y-m-d',newformat:'d/m/Y'},
                            editable:false,
                            searchoptions:{
                                dataInit:function(el){
                                    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);
                                    jQuery(el).datepicker({
                                        regional: 'fr',
                                        changeMonth: true,
                                        changeYear: true,
                                        showButtonPanel: true,
                                        constrainInput: true,
                                        gotoCurrent: true,
                                        dateFormat: 'dd/mm/yy',
                                    });
                                    jQuery('#ui-datepicker-div').addClass('promoteZ');
                                },
                                sopt:['eq','ne','le','lt','ge','gt'],
                            },
                    },
                   {name:'fk_statut',index:'fk_statut', width:90, align: 'right'},
                 ],
        rowNum:10,
        rowList:[10,30,50],
        width: 700,
        height: 150,
        pager: '#pager1',
        sortname: 'rowid',
        beforeRequest: function(){
            jQuery('.fiche').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
        },
        viewrecords: true,
        sortorder: 'desc',
        caption:'Historique contrat'
    });
    jQuery('#list1').jqGrid('navGrid','#pager1',{edit:false,add:false,del:false,search:true});

     jQuery.validator.addMethod(
                            'FRDate',
                            function(value, element) {
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                           );
    jQuery.validator.addMethod(
                            'FRDateNotRequired',
                            function(value, element) {
                                if (value+'x' == 'x') return true;
                                return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
                            },
                            'La date doit &ecirc;tre au format dd/mm/yyyy'
                           );
    });
</script>
EOF;
}
?>