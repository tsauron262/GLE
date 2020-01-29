<?php


    require_once("./pre.inc.php");
//Auth ajax local
if ($_COOKIE['logged'] != "OK")
{
    header('Location: index.php');
} else {
    require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
    require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

    $themeGPI =  $conf->global->MAIN_MODULE_BABELGPI_THEME;
    if ($themeGPI ."x" == "x")
    {
        $themeGPI = "start";
    }



    print '<link rel="stylesheet" type="text/css" media="screen" href="css/'.$themeGPI.'/jquery-ui-1.7.3.custom.css" />';
    print '<link rel="stylesheet" type="text/css" media="screen" href="jquery/jqGrid-3.5/css/ui.jqgrid.css" />';
//    print '    <link rel="stylesheet" type="text/css" media="screen" href="css/'.$themeGPI.'jquery-ui.css" />';
//    print '    <link rel="stylesheet" type="text/css" media="screen" href="css/'.$themeGPI.'ui.tabs.css" />';


    $header = <<<EOF



      <script type="text/javascript" src="jquery/jquery-1.3.2.js" ></script>
      <script type="text/javascript" src="jquery/jqGrid-3.5/jquery.jqGrid.js" ></script>
      <script type="text/javascript" src="jquery/jqGrid-3.5/js/jqModal.js" ></script>
      <script type="text/javascript" src="jquery/jqGrid-3.5/js/jqDnR.js" ></script>
      <script type="text/javascript" src="jquery/ui/jquery-ui.js" ></script>
      <script type="text/javascript" src="jquery/ui/ui.tabs.js" ></script>


      <script type="text/javascript">

//      var gridimgpath="jquery/jqGrid-3.5/themes/green/images";
        var socid = "";

    var soccode = Get_Cookie("soccode");
jQuery(document).ready(function() {
    jQuery("#logout").click(function(){
        logout();
    });
    //alert (soccode);
    jQuery.ajax({
       type: "POST",
       url: "ajax/soccode.php",
       data: "key="+soccode,
       async: false,
       success: function(msg){
            socid = jQuery(msg).find('socid').text();
       }
    });
}); // fin de document.ready



      function displayExtra(type,id,parentId)
      {
          var contractId = id;
          if (parentId + "x" != "x" && typeof(parentId) == "number")
          {
            contractId = parentId;
          }
//          alert (id + " "+ parentId + " " + contractId+ " "+typeof(parentId));
          jQuery.ajax({
               type: "POST",
               url: "ajax/contractDetail.php",
               data: "contratId="+contractId+"&type="+type,
               success: function(msg){
     //alert( "Data Saved: " + msg );

                    //TODO change r fragment en type+'fragment' sinon prob de tabs, idem tabs id

                    //main datas
                var societe = jQuery(msg).find('main').find('societe').text();
                var ref = jQuery(msg).find('main').find('ref').text();
                var dateCont = jQuery(msg).find('main').find('date').text();
                var projet = jQuery(msg).find('main').find('projet').text();
                var status = jQuery(msg).find('main').find('status').text();
                var remiseAbs = jQuery(msg).find('main').find('remiseAbs').text();
                longHtml = "";
                    if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet"  )
                    {
                        longHtml = '<div id="contractDiv">';
                        longHtml += '    <div id="tabsContract">';
                    } else if (type=='facture')
                    {
                        longHtml = '<div id="factureDiv">';
                        longHtml += '    <div id="tabsFacture">';
                    } else if (type=='propal')
                    {
                        longHtml = '<div id="propalDiv">';
                        longHtml += '    <div id="tabsPropal">';
                    } else if (type='contratGA')
                    {
                        longHtml = '<div id="contratGADiv">';
                        longHtml += '    <div id="tabsContractGA">';
                    }

                    longHtml += '        <ul>';
                    longHtml += '            <li><a href="#fragment-1"><span>D&eacute;tails</span></a></li>';
                    longHtml += '            <li><a href="#fragment-2"><span>Contacts</span></a></li>';
                    if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet"|| type =="contratGA" )
                    {
                    longHtml += '            <li><a href="#fragment-3"><span>Amortissement</span></a></li>';
                    }
                    longHtml += '            <li><a href="#fragment-4"><span>Produits</span></a></li>';
                    longHtml += '            <li><a href="#fragment-5"><span>Notes</span></a></li>';
                    longHtml += '            <li><a href="#fragment-6"><span>Documents</span></a></li>';
                    longHtml += '            <li><a href="#fragment-7"><span>Suivi</span></a></li>';
                    longHtml += '        </ul>';
                    longHtml += '        <div id="contractDivCartouche"><br/><br/>';
                    longHtml += '            <table class="border" style="border-collapse: collapse;" width="100%">';
                    longHtml += '                <tbody>';
                    longHtml += '                    <tr>';
                    longHtml += '                        <td width="25%">R&eacute;f.</td>';
                    longHtml += '                        <td colspan="3">'+ref+'</td>';
                    longHtml += '                    </tr>';
                    longHtml += '                    <tr>';
                    longHtml += '                        <td>Client</td>';
                    longHtml += '                        <td colspan="3">';
                    longHtml +=                             societe;
                    longHtml += '                       </td>';
                    longHtml += '                    </tr>';
                    if ("x"+remiseAbs != "x")
                    {
                        longHtml += '                    <tr>';
                        longHtml += '                        <td>Remise</td>';
                        longHtml += '                        <td colspan="3">'+remiseAbs+'&euro;</td>';
                        longHtml += '                    </tr>';
                    }
                    longHtml += '                    <tr>';
                    longHtml += '                        <td>&Eacute;tat</td>';
                    longHtml += '                        <td colspan="3">'+status+'</td>';
                    longHtml += '                    </tr>';
                    longHtml += '                    <tr>';
                    longHtml += '                        <td>Date</td>';
                    longHtml += '                        <td colspan="3">'+dateCont+'</td>';
                    longHtml += '                    </tr>';
                    longHtml += '                    <tr>';
                    longHtml += '                        <td>';
                    longHtml += '                            <table class="nobordernopadding" width="100%">';
                    longHtml += '                                <tbody>';
                    longHtml += '                                    <tr>';
                    longHtml += '                                        <td>Projet</td>';
                    longHtml += '                                        <td align="right">';
                    longHtml +=                                             projet
                    longHtml += '                                        </td>';
                    longHtml += '                                    </tr>';
                    longHtml += '                                </tbody>';
                    longHtml += '                            </table>';
                    longHtml += '                        </td>';
                    longHtml += '                        <td colspan="3"> </td>';
                    longHtml += '                    </tr>';
                    longHtml += '                </tbody>';
                    longHtml += '            </table>';
                    longHtml += '        </div>';
                    longHtml += '        <div id="fragment-1">';
                    longHtml += '            <div class="fiche">';
                    longHtml += '                <table class="noborder" width="100%">';
                    longHtml += '                    <tbody>';
                    var pairimpair = false;
                    var iter=0;
                    jQuery(msg).find('service').each(function(){

                        var num = jQuery(this).find('num').text();
                        var desc = jQuery(this).find('desc').text();
                        var tva = jQuery(this).find('tva').text();
                        var puht = jQuery(this).find('puht').text();
                        var qte = jQuery(this).find('qte').text();
                        var reduc = jQuery(this).find('reduc').text();
                        var reducPercent = jQuery(this).find('reducPercent').text();
                        var DateServiceStart = jQuery(this).find('DateServiceStart').text();
                        var DateServiceStop = jQuery(this).find('DateServiceStop').text();
                        var RealDateServiceStart = jQuery(this).find('RealDateServiceStart').text();
                        var RealDateServiceStop = jQuery(this).find('RealDateServiceStop').text();
                        var status = jQuery(this).find('status').text();
                        var cssclass = "";
                        if (pairimpair)
                        {
                            cssclass = "pair";
                        } else {
                            cssclass = "impair";
                        }
                        pairimpair = ! pairimpair;


                        longHtml += '                        <tr class="'+cssclass+'" height="16">';
                        if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet"|| type =="contratGA" )
                        {
                             longHtml += '                            <td class="tab" width="90" style="border-top: 1px solid rgb(51, 51, 51); border-left: 1px solid rgb(51, 51, 51); border-bottom: 1px solid rgb(51, 51, 51);">Service no '+num+'</td>';
                        }
                        longHtml += '                            <td class="tab" rowspan="2" style="border-top: 1px solid rgb(51, 51, 51); border-right: 1px solid rgb(51, 51, 51); border-bottom: 1px solid rgb(51, 51, 51);">';
                    if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet" || type =="contratGA")
                    {
                        longHtml += '                                <table class="noborder"  style="border-collapse: collapse;" width="100%">';
                        longHtml += '                                    <tbody>';
                        longHtml += '                                        <tr class="liste_titre">';
                                                    longHtml += '                                            <td style="width:700px;max-width:700px">Service</td>';
                        longHtml += '                                            <td style="width:50px" align="center">TVA</td>';
                        longHtml += '                                            <td style="width:150px" align="right">P.U. HT</td>';
                        longHtml += '                                            <td style="width:50px" align="center">Qt&eacute;</td>';
                        longHtml += '                                            <td style="width:50px" align="right">R&eacute;duc.</td>';
                        longHtml += '                                        </tr>';

                    } else  if (iter == 0)
                    {
                            longHtml += '                                <table class="noborder"  style="border-collapse: collapse;" width="100%">';
                            longHtml += '                                    <tbody>';
                            longHtml += '                                        <tr class="liste_titre">';
                            longHtml += '                                            <td style="width:700px;max-width:700px;">Service</td>';
                            longHtml += '                                            <td style="width:50px" align="center">TVA</td>';
                            longHtml += '                                            <td style="width:150px" align="right">P.U. HT</td>';
                            longHtml += '                                            <td style="width:50px" align="center">Qt&eacute;</td>';
                            longHtml += '                                            <td style="width:50px" align="right">R&eacute;duc.</td>';
                            longHtml += '                                        </tr>';
                        }  else {
                            longHtml += '                                <table class="noborder"  style="border-collapse: collapse;" width="100%">';
                            longHtml += '                                    <tbody>';
                        }
                        iter++;
                        longHtml += '                                        <tr class="'+cssclass+'" valign="top">';
                        longHtml += '                                            <td style="width:700px;max-width:700px;">'+desc+'</td>';
                        longHtml += '                                            <td style="width:50px" align="center">'+tva+'</td>';
                        longHtml += '                                            <td style="width:150px" align="right">'+puht+'</td>';
                        longHtml += '                                            <td style="width:50px" align="center">'+qte+'</td>';
                        longHtml += '                                            <td style="width:50px" align="right"> '+reducPercent+'</td>';
                        longHtml += '                                        </tr>';
                        if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet" || type =="contratGA")
                        {
                            longHtml += '                                        <tr class="'+cssclass+'">';
                            longHtml += '                                            <td colspan="5">';
                            longHtml += '                                                Date pr&eacute;vue mise en service: '+DateServiceStart+'  -  Date pr&eacute;vue fin de service: '+DateServiceStop;
                            longHtml += '                                            </td>';
                            longHtml += '                                        </tr>';
                            longHtml += '                                        <tr class="'+cssclass+'">';
                            longHtml += '                                            <td colspan="5">';
                            longHtml += '                                                <hr/>';
                            longHtml += '                                            </td>';
                            longHtml += '                                        </tr>';
                            longHtml += '                                    </tbody>';
                            longHtml += '                                </table>';
                            longHtml += '                                <table class="noborder" style="border-collapse: collapse;" width="100%">';
                            longHtml += '                                    <tbody>';
                            longHtml += '                                        <tr class="'+cssclass+'">';
                            longHtml += '                                            <td> Statut du service: ';
                            longHtml +=                                                 status;
                            longHtml += '                                            </td>';
                            longHtml += '                                            <td width="30" align="right">';
                            longHtml += '                                            </td>';
                            longHtml += '                                        </tr>';
                            longHtml += '                                        <tr class="'+cssclass+'">';
                            longHtml += '                                            <td>Date effective mise en service: '+RealDateServiceStart+' -  Date de cl&ocirc;ture: '+DateServiceStop;+'</td>';
                            longHtml += '                                            <td align="center"> </td>';
                            longHtml += '                                        </tr>';
                        }
                        longHtml += '                                    </tbody>';
                        longHtml += '                                </table>';
                        longHtml += '                            </td>';
                        longHtml += '                        </tr>';
                        longHtml += '                        <tr>';
                        longHtml += '                            <td style="border-right: 1px solid rgb(51, 51, 51);"> </td>';
                        longHtml += '                        </tr>';
                    });

                    longHtml += '                        <tr>';
                    longHtml += '                            <td style="border-right: 1px solid rgb(51, 51, 51);"> </td>';
                    longHtml += '                        </tr>';
                    longHtml += '                    </tbody>';
                    longHtml += '                </table>';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                    longHtml += '        <div id="fragment-2">';
                    longHtml += '            <div class="fiche">';
                    longHtml += '                <table class="noborder"  style="border-collapse: collapse;" width="100%">';
                    longHtml += '                    <tbody>';
                    longHtml += '                        <tr class="liste_titre">';
                    longHtml += '                            <td>Source</td>';
                    longHtml += '                            <td>Tiers</td>';
                    longHtml += '                            <td>Contacts</td>';
                    longHtml += '                            <td>Type de contact</td>';
                    longHtml += '                            <td align="center">&eacute;tat</td>';
                    longHtml += '                            <td colspan="2"> </td>';
                    longHtml += '                        </tr>';
                    jQuery(msg).find('contact').each(function(){
                        var marker = jQuery(this);

                        var source = marker.find('source').text();
                        var societe = marker.find('societe').text();
                        var nom = marker.find('nom').text();
                        var type = marker.find('type').text();
                        var statut = marker.find('statut').text();

                        longHtml += '                        <tr class="impair" valign="top">';
                        longHtml += '                            <td align="left">'+source+'</td>';
                        longHtml += '                            <td align="left">'+societe+'</td>';
                        longHtml += '                            <td>'+nom;
                        longHtml += '                            </td>';
                        longHtml += '                            <td>'+type+'</td>';
                        longHtml += '                            <td align="center">'+statut;
                        longHtml += '                            </td>';
                        longHtml += '                            <td nowrap="" align="center"/>';
                        longHtml += '                        </tr>';
                    });
                    longHtml += '                    </tbody>';
                    longHtml += '                </table>';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                    if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet" || type =="contratGA")
                    {

                    longHtml += '        <div id="fragment-3">';
                    longHtml += '            <div class="fiche">';
                        longHtml += '<table class="nobordernopadding"  style="border-collapse: collapse;" width=100%><tbody><tr>';
                            longHtml += '<th>Desc</th>';
                            longHtml += '<th>Qte</th>';
                            longHtml += '<th>Mensualit&eacute;</th>';
                            longHtml += '<th>Dur&eacute;e</th>';
                            longHtml += '<th>Dur&eacute;e restante</th>';
                            longHtml += '<th>Montant d&eacute;j&agrave; pay&eacute;</th>';
                            longHtml += '<th>Avancement</th>';
                    var pairimpair = false;
                    if (jQuery(msg).find('amortissement').length==0)
                    {
                        if (pairimpair){
                            pairimpair = false;
                            cssclass = "impair";
                        } else {
                            pairimpair = true;
                            cssclass = "pair";
                        }
                        longHtml += '</tr><tr class="'+cssclass+'"><td colspan=7>Pas de financement</td>';

                    } else {

                    jQuery(msg).find('amortissement').each(function(){
                        var marker = jQuery(this);

                        var desc = marker.find('desc').text();
                        var qte = marker.find('qte').text();
                        var taux = marker.find('taux').text();
                        var monthAmount = marker.find('monthAmount').text();
                        var totalCost = marker.find('totalCost').text();
                        var nbMonth = marker.find('nbMonth').text();
                        var nbMonthRest = marker.find('nbMonthRest').text();
                        var Payedamount = marker.find('Payedamount').text();
                        var percentPayed = marker.find('percentPayed').text();
                        var due = marker.find('due').text();
                        var cssclass = "";
                        if (pairimpair){
                            pairimpair = false;
                            cssclass = "impair";
                        } else {
                            pairimpair = true;
                            cssclass = "pair";
                        }
                        longHtml += '</tr><tr class="'+cssclass+'">';

                            longHtml += '<td>'+desc+'</td>';
                            longHtml += '<td align=center>'+qte+'</td>';
                            longHtml += '<td align=center>'+monthAmount+'</td>';
                            longHtml += '<td align=center>'+nbMonth+'</td>';
                            longHtml += '<td align=center>'+nbMonthRest+'</td>';
                            longHtml += '<td align=center>'+Payedamount+'</td>';
                            longHtml += '<td align=center>'+due+'</td>';

                    });
                    }
                        longHtml += '</tr></tbody></table>';

                    longHtml += '                ';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                }
                    longHtml += '        <div id="fragment-4">';
                    longHtml += '            <div class="fiche">';


                        longHtml += '<table class="nobordernopadding"  style="border-collapse: collapse;" width=100%><tbody><tr>';
                            longHtml += '<th class="ui-widget-header ui-state-default"></th>';
                            longHtml += '<th class="ui-widget-header ui-state-default">Desc</th>';
                            longHtml += '<th class="ui-widget-header ui-state-default">Qte</th>';
                            longHtml += '<th class="ui-widget-header ui-state-default">Prit unit. HT</th>';
                            longHtml += '<th class="ui-widget-header ui-state-default">Statut</th>';
                            longHtml += '<th class="ui-widget-header ui-state-default">Autres</th>';
                    var pairimpair = false;
                    jQuery(msg).find('produit').each(function(){
                        var marker = jQuery(this);

                        var desc = marker.find('desc').text();
                        var qte = marker.find('qte').text();
                        var puht = marker.find('puht').text();
                        var statut = marker.find('statut').text();
                        var duration = marker.find('duration').text();
                        var weight = marker.find('weight').text();
                        var volume = marker.find('volume').text();
                        var note = marker.find('note').text();
                        var photo = marker.find('photo').text();

                        var cssclass = "";
                        if (pairimpair){
                            pairimpair = false;
                            cssclass = "impair ui-state-active";
                        } else {
                            pairimpair = true;
                            cssclass = "pair ui-widget-content";
                        }
                        longHtml += '</tr><tr class="'+cssclass+'">';

                        if ('x'+photo == "x")
                        {
                            longHtml += '<td>&nbsp;</td>';
                        } else {
                            longHtml += '<td>'+photo+'</td>';
                        }
                            longHtml += '<td>'+desc+'</td>';
                            longHtml += '<td align=center>'+qte+'</td>';
                            longHtml += '<td align=center>'+puht+'&euro;</td>';
                            longHtml += '<td align=center>'+statut+'</td>';
                            longHtml += " <td><table><tr><td nowrap>";
                            if ("x"+duration != "x")
                            {
                                longHtml += "Dur&eacute;e : </td><td>"+duration+"</td></tr><tr><td>";
                            }
                            if ("x"+weight != "x")
                            {
                                longHtml += "Poids : </td><td>"+weight+"</td></tr><tr><td>";
                            }
                            if ("x"+volume != "x")
                            {
                                longHtml += "Volume : </td><td>"+volume+"</td></tr><tr><td>";
                            }
                            if ("x"+note != "x")
                            {
                                longHtml += "Note : </td><td>"+note+"</td></tr><tr><td>";
                            }
                            longHtml += " </td></tr></table></td>"
                    });
                    longHtml += '</tr></tbody></table>';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                    longHtml += '        <div id="fragment-5">';
                    longHtml += '            <div class="fiche">';
                        longHtml += '<table class="nobordernopadding"   style="border-collapse: collapse;"  width=100%><tbody>';
                            longHtml += '<tr><th>Public</th><td>'+jQuery(msg).find('notes').find('public').text();+'</td></tr>';
            //                longHtml += '<tr><th>Interne</th><td>'+jQuery(msg).find('notes').find('private').text();+'</td></tr>';
                        longHtml += '</tbody></table>';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                    longHtml += '        <div id="fragment-6">';
                    longHtml += '            <div class="fiche">';
                        longHtml += "<table cellpadding=15  style='border-collapse: collapse; width: 450px;'><tbody><tr><td class='ui-wiget-header ui-state-default'>Quantit&eacute; de document</td><td align=center class='ui-widget-content'>"+jQuery(msg).find("documents").find('totDoc').text()+"</td></tr>";
                        longHtml += "              <tr><td class='ui-widget-header ui-state-default'>Taille totale</td><td align=center class='ui-widget-content'>"+jQuery(msg).find("documents").find('TotalSizeOfAttachedFiles').text()+"Ko</td></tr></tbody></table>";
                    longHtml += '<table cellpadding=15  class="nobordernopadding"  style="border-collapse: collapse;" width=450><tbody>';
                        jQuery(msg).find('document').each(function(){
                            var url = jQuery(this).find('url').text();
                            var size = jQuery(this).find('size').text();
                            var date = jQuery(this).find('date').text();

                            longHtml += "<tr><td class='ui-widget-content' align=left>"+url+"</td>";
                            longHtml += "    <td class='ui-widget-content' align=right>"+size+"<br>";
                            longHtml += "    "+date+"</td></tr>";
                        });

                        longHtml += '</tbody></table>';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                    longHtml += '        <div id="fragment-7">';
                    longHtml += '            <div class="fiche">';
                        longHtml += '<table class="nopadding" style="border-collapse: collaspe;" width=100%><tbody>';
                        jQuery(msg).find('suivi').children().each(function(){
                            longHtml += "<tr><td class='ui-widget-header ui-state-default'>"+jQuery(this).text()+"</td></tr>";
                        });
                        longHtml += '</tbody></table>';
                    longHtml += '            </div>';
                    longHtml += '        </div>';
                    longHtml += '    </div>';
                    longHtml += '</div>';
                    if (type=="contract" ||type=="contrat" || type=="contractdet" || type=="contratdet" )
                    {
                        jQuery("#contractDiv").replaceWith(longHtml);
                        jQuery('#tabsContract').tabs({
                            cache: true,
                            spinner:"Chargement ...",
                            fx: { opacity: 'toggle' }
                        });
                    } else if (type=='facture')
                    {
                        jQuery("#factureDiv").replaceWith(longHtml);
                        jQuery('#tabsFacture').tabs({
                            cache: true,
                            spinner:"Chargement ...",
                            fx: { opacity: 'toggle' }
                        });
                    } else if (type=='propal')
                    {
//                      console.log(longHtml);
                        jQuery("#propalDiv").replaceWith(longHtml);
                        jQuery('#tabsPropal').tabs({
                            cache: true,
                            spinner:"Chargement ...",
                            fx: { opacity: 'toggle' }
                        });
                    } else if (type='contratGA')
                    {
                        jQuery("#contratGADiv").replaceWith(longHtml);
                        jQuery('#tabsContractGA').tabs({
                            cache: true,
                            spinner:"Chargement ...",
                            fx: { opacity: 'toggle' }
                        });
                    }
        }
   });
}
function Get_Cookie( check_name ) {
    // first we'll split this cookie up into name/value pairs
    // note: document.cookie only returns name=value, not the other components
    var a_all_cookies = document.cookie.split( ';' );
    var a_temp_cookie = '';
    var cookie_name = '';
    var cookie_value = '';
    var b_cookie_found = false; // set boolean t/f default f

    for ( i = 0; i < a_all_cookies.length; i++ )
    {
        // now we'll split apart each name=value pair
        a_temp_cookie = a_all_cookies[i].split( '=' );


        // and trim left/right whitespace while we're at it
        cookie_name = a_temp_cookie[0].replace(/^\s+|\s+jQuery/g, '');

        // if the extracted name matches passed check_name
        if ( cookie_name == check_name )
        {
            b_cookie_found = true;
            // we need to handle case where cookie has no value but exists (no = sign, that is):
            if ( a_temp_cookie.length > 1 )
            {
                cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+jQuery/g, '') );
            }
            // note that in cases where cookie is initialized but no value, null is returned
            return cookie_value;
            break;
        }
        a_temp_cookie = null;
        cookie_name = '';
    }
    if ( !b_cookie_found )
    {
        return null;
    }
}


function Set_Cookie( name, value, expires, path, domain, secure )
{
// set time, it's in milliseconds
var today = new Date();
today.setTime( today.getTime() );

/*
if the expires variable is set, make the correct
expires time, the current script below will set
it for x number of days, to make it for hours,
delete * 24, for minutes, delete * 60 * 24
*/
if ( expires )
{
expires = expires * 1000 * 60 * 60 * 24;
}
var expires_date = new Date( today.getTime() + (expires) );

document.cookie = name + "=" +escape( value ) +
( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) +
( ( path ) ? ";path=" + path : "" ) +
( ( domain ) ? ";domain=" + domain : "" ) +
( ( secure ) ? ";secure" : "" );
}

      </script>

<script>
jQuery(document).ready(function(){
        jQuery('.menu li').mouseover(function(){ jQuery(this).addClass('ui-state-hover') } );
        jQuery('.menu li').mouseout(function(){ jQuery(this).removeClass('ui-state-hover') });
        jQuery('#menuTab').tabs({ cache: true,
                fx: { opacity: 'slide' }, spinner: 'Chargement ...',ajaxOptions: { async: false },
                load: function(event, ui) {
                    if (ui.index == 3)
                    {
                        logout();
                    } else if (ui.index == 2)
                    {
                        displayGridContratGA();
                    } else if (ui.index == 1)
                    {
                        displayGridContrat();
                    //} else if (ui.index == 1)
//                    {
//                        displayGridFacture();
                    } else {
                        displayGridPropal();
                    }

                     } });
}); //end ready


function displayGridContrat()
{
        jQuery("#list11").jqGrid({
                url: 'ajax/overview_grid_init.php?socid='+socid,
                datatype: "xml",
                height: 250,
                width: 1200,
                ondblClickRow: function( rowid) {
                    displayExtra("contrat",rowid);
                },
                colNames: ['Id', 'Ref', 'Date','Status','Lignes'],
                colModel: [{
                    name: 'id',
                    index: 'id',
                    width: 55,
                    align: "center",
                    hidden: true
                }, {
                    name: 'ref',
                    index: 'ref',
                    width: 100,
                    align: "center"
                }, {
                    name: 'date_contrat',
                    index: 'date_contrat',
                    width: 90,
                    sorttype:"date",
                    formatter:"date",
                    align: "center",
                    formatoptions:{srcformat: 'Y-m-d H:i:s',
                                   newformat: 'd/m/Y'}
                }, {
                    name: 'status',
                    index: 'status',
                    width: 80,
                    align: "center"
                }, {
                    name: 'totEleme',
                    index: 'totEleme',
                    width: 80,
                    align: "center"
                }],
//                loadComplete: function(){
//                    jQuery("#list11 tr").each(function(){
//                        if (jQuery("#list11").getCell(this.id, 4) == "KO") {
//                            jQuery(this).css({
//                                backgroundColor: "#FF0000"
//                            })
//                        } else {
//                            if (jQuery("#list11").getCell(this.id, 8) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF0000"
//                                })
//                            }
//                            if (jQuery("#list11").getCell(this.id, 7) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF9900"
//                                })
//                            }
//                        }
//                    })
//                },
                rowNum: 20,
                rowList: [10, 20, 30],
                pager: jQuery('#pager11'),
                sortname: 'date_contrat',
                viewrecords: true,
                sortorder: "desc",
                multiselect: false,
                subGrid: true,
                subGridUrl: 'ajax/overview_grid_subgrid.php?',
                subGridRowExpanded: function(subgrid_id, row_id) {
                // we pass two parameters
                // subgrid_id is a id of the div tag created within a table
                // the row_id is the id of the row
                // If we want to pass additional parameters to the url we can use
                // the method getRowData(row_id) - which returns associative array in type name-value
                // here we can easy construct the following
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                    jQuery("#"+subgrid_table_id).jqGrid({
                      url: 'ajax/overview_grid_subgrid.php?&contratId='+row_id,
                      //url:"subgrid.php?q=2&id="+row_id,
                      datatype: "xml",
                      colNames: ['Id','Idproduit','Status','Label','Description','Ouverture','Cloture','qty','cout mensuel','cout total'],
                      colModel: [
                        {name:"id",index:"id",width:55,key:true,hidden:true},
                        {name:"fk_product",index:"fk_product",width:55,hidden:true},
                        {name:"statut",index:"statut",width:180},
                        {name:"label",index:"label",width:180},
                        {name:"description",index:"description",width:180},
                        {name:"date_ouverture",index:"date_ouverture",width:150,sortable:true,sorttype:"date",formatter:"date",formatoptions:{srcformat: 'Y-m-d',newformat: 'd/m/Y'}},
                        {name:"date_fin_validite",index:"date_fin_validite",width:150,sortable:true,sorttype:"date",formatter:"date",formatoptions:{srcformat: 'Y-m-d',newformat: 'd/m/Y'}},
                        {name:"qty",index:"qty",width:80},
                        {name:"total_ht",index:"total_ht",width:180},
                        {name:"coutTot",index:"total_ht",width:180,hidden:true},
                        ],
                      height: "100%",
                      rowNum:20,
                      sortname: 'date_ouverture',
                      sortorder: "asc",
                      ondblClickRow: function( rowid) {
                          displayExtra("contratDet",row_id,rowid);
                      },
//                      loadComplete: function(){
//                            jQuery('#'+subgrid_table_id+ ' tr').each(function(){
//                                if (this.id != '_empty')
//                                {
//                                    if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "KO") {
//                                        jQuery(this).css({
//                                            backgroundColor: "#FF0000"
//                                        })
//                                    } else if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "WARN") {
//                                            jQuery(this).css({
//                                                backgroundColor: "#FF9900"
//                                            })
//                                    }
//                                }
//                            })
//                        },
                   })
               },
               caption: "Vue g&eacute;n&eacute;rale",
               }).navGrid('#pager11', {
                    add: false,
                    edit: false,
                    del: false
                });

}


function displayGridFacture()
{
        jQuery("#list12").jqGrid({
                url: 'ajax/overview_gridFact_init.php?socid='+socid,
                datatype: "xml",
                height: 250,
                width: 1200,
                ondblClickRow: function( rowid) {
                    displayExtra("facture",rowid);
                },
                colNames: ['Id', 'Ref', 'Date','Status','Lignes'],
                colModel: [{
                    name: 'id',
                    index: 'id',
                    width: 55,
                    align: "center",
                    hidden: true
                }, {
                    name: 'ref',
                    index: 'ref',
                    width: 100,
                    align: "center"
                }, {
                    name: 'date_facture',
                    index: 'datef',
                    width: 90,
                    sorttype:"date",
                    formatter:"date",
                    align: "center",
                    formatoptions:{srcformat: 'Y-m-d H:i:s',
                                   newformat: 'd/m/Y'}
                }, {
                    name: 'status',
                    index: 'fk_statut',
                    width: 80,
                    align: "center"
                }, {
                    name: 'totEleme',
                    index: 'totEleme',
                    width: 80,
                    align: "center"
                }],
//                loadComplete: function(){
//                    jQuery("#list12 tr").each(function(){
//                        if (jQuery("#list12").getCell(this.id, 4) == "KO") {
//                            jQuery(this).css({
//                                backgroundColor: "#FF0000"
//                            })
//                        } else {
//                            if (jQuery("#list12").getCell(this.id, 8) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF0000"
//                                })
//                            }
//                            if (jQuery("#list12").getCell(this.id, 7) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF9900"
//                                })
//                            }
//                        }
//                    })
//                },
                rowNum: 20,
                rowList: [10, 20, 30],
                pager: jQuery('#pager12'),
                sortname: 'datef',
                viewrecords: true,
                sortorder: "desc",
                multiselect: false,
                subGrid: true,
                subGridUrl: 'ajax/overview_gridFact_subgrid.php?',
                subGridRowExpanded: function(subgrid_id, row_id) {
                // we pass two parameters
                // subgrid_id is a id of the div tag created within a table
                // the row_id is the id of the row
                // If we want to pass additional parameters to the url we can use
                // the method getRowData(row_id) - which returns associative array in type name-value
                // here we can easy construct the following
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                    jQuery("#"+subgrid_table_id).jqGrid({
                      url: 'ajax/overview_gridFact_subgrid.php?&contratId='+row_id,
                      //url:"subgrid.php?q=2&id="+row_id,
                      datatype: "xml",
                      colNames: ['Id','Idproduit','Description','Qty','Total HT'],
                      colModel: [
                        {name:"id",index:"id",width:55,key:true,hidden:true},
                        {name:"fk_product",index:"fk_product",width:55,hidden:true},
                        {name:"description",index:"description",width:180},
                        {name:"qty",index:"qty",width:80},
                        {name:"total_ht",index:"total_ht",width:180},
                        ],
                      height: "100%",
                      rowNum:20,
                      sortname: 'description',
                      sortorder: "asc",
                      ondblClickRow: function( rowid) {
                          displayExtra("factureDet",row_id,rowid);
                      },
//                      loadComplete: function(){
//                            jQuery('#'+subgrid_table_id+ ' tr').each(function(){
//                                if (this.id != '_empty')
//                                {
//                                    if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "KO") {
//                                        jQuery(this).css({
//                                            backgroundColor: "#FF0000"
//                                        })
//                                    } else if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "WARN") {
//                                            jQuery(this).css({
//                                                backgroundColor: "#FF9900"
//                                            })
//                                    }
//                                }
//                            })
//                        },
                   })
               },
               caption: "Vue g&eacute;n&eacute;rale",
               }).navGrid('#pager12', {
                    add: false,
                    edit: false,
                    del: false
                });

}

function displayGridPropal()
{
        jQuery("#list13").jqGrid({
                url: 'ajax/overview_gridPropal_init.php?socid='+socid,
                datatype: "xml",
                height: 250,
                width: 1200,
                ondblClickRow: function( rowid) {
                    displayExtra("propal",rowid);
                },
                colNames: ['Id', 'Ref', 'Date','Status','Lignes'],
                colModel: [{
                    name: 'id',
                    index: 'id',
                    width: 55,
                    align: "center",
                    hidden: true
                }, {
                    name: 'ref',
                    index: 'ref',
                    width: 100,
                    align: "left"
                }, {
                    name: 'datep',
                    index: 'datep',
                    width: 90,
                    sorttype:"date",
                    formatter:"date",
                    align: "center",
                    formatoptions:{srcformat: 'Y-m-d H:i:s',
                                   newformat: 'd/m/Y'}
                }, {
                    name: 'status',
                    index: 'fk_statut',
                    width: 80,
                    align: "left"
                }, {
                    name: 'totEleme',
                    index: 'totEleme',
                    width: 80,
                    align: "center"
                }],
                //loadComplete: function(){
//                    jQuery("#list13 tr").each(function(){
//                        if (jQuery("#list13").getCell(this.id, 4) == "KO") {
//                            jQuery(this).css({
//                                backgroundColor: "#FF0000"
//                            })
//                        } else {
//                            if (jQuery("#list13").getCell(this.id, 8) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF0000"
//                                })
//                            }
//                            if (jQuery("#list13").getCell(this.id, 7) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF9900"
//                                })
//                            }
//                        }
//                    })
//                },
                rowNum: 20,
                rowList: [10, 20, 30],
                pager: jQuery('#pager13'),
                sortname: 'datep',
                viewrecords: true,
                sortorder: "desc",
                multiselect: false,
                subGrid: true,
                subGridUrl: 'ajax/overview_gridPropal_subgrid.php?',
                subGridRowExpanded: function(subgrid_id, row_id) {
                // we pass two parameters
                // subgrid_id is a id of the div tag created within a table
                // the row_id is the id of the row
                // If we want to pass additional parameters to the url we can use
                // the method getRowData(row_id) - which returns associative array in type name-value
                // here we can easy construct the following
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                    jQuery("#"+subgrid_table_id).jqGrid({
                      url: 'ajax/overview_gridPropal_subgrid.php?&contratId='+row_id,
                      //url:"subgrid.php?q=2&id="+row_id,
                      datatype: "xml",
 //                       colNames: ['Id','Idproduit','Status','D&eacute;signation','Description','Qt&eacute;','Loyer','Total HT'],
                    colNames: ['Id','Idproduit','Description','Qt&eacute;','Loyer','Total HT'],
                      colModel: [
                        {name:"id",index:"id",width:55,key:true,hidden:true},
                        {name:"fk_product",index:"fk_product",width:55,hidden:true},
//                        {name:"statut",index:"fk_statut",width:180,align: "center"},
//                        {name:"label",index:"label",width:180,align: "center"},
                        {name:"description",index:"description",width:180,align: "left"},
                        {name:"qty",index:"qty",width:80,align: "center"},
                        {name:"monthlycost",index:"monthlycost",width:180,align: "center",},
                        {name:"total_ht",index:"total_ht",width:180,align: "center",},
                        ],
                      height: "100%",
                      rowNum:20,
                      sortname: 'id',
                      sortorder: "asc",
//                      ondblClickRow: function( rowid) {
//                          displayExtra("contratDet",row_id,rowid);
//                      },
                      //loadComplete: function(){
//                            jQuery('#'+subgrid_table_id+ ' tr').each(function(){
//                                if (this.id != '_empty')
//                                {
//                                    if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "KO") {
//                                        jQuery(this).css({
//                                            backgroundColor: "#FF0000"
//                                        })
//                                    } else if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "WARN") {
//                                            jQuery(this).css({
//                                                backgroundColor: "#FF9900"
//                                            })
//                                    }
//                                }
//                            })
//                        },
                   })
               },
               caption: "Propositions commerciales",
               }).navGrid('#pager13', {
                    add: false,
                    edit: false,
                    del: false
                });

}
function displayGridContratGA()
{
        jQuery("#list14").jqGrid({
                url: 'ajax/overview_gridGA_init.php?socid='+socid,
                datatype: "xml",
                height: 250,
                width: 1200,
                ondblClickRow: function( rowid) {
                    displayExtra("contratGA",rowid);
                },
                colNames: ['Id', 'Ref', 'Date','Status','Lignes'],
                colModel: [{
                    name: 'id',
                    index: 'id',
                    width: 55,
                    align: "center",
                    hidden: true
                }, {
                    name: 'ref',
                    index: 'ref',
                    width: 100,
                    align: "center"
                }, {
                    name: 'date_contrat',
                    index: 'date_contrat',
                    width: 90,
                    sorttype:"date",
                    formatter:"date",
                    align: "center",
                    formatoptions:{srcformat: 'Y-m-d H:i:s',
                                   newformat: 'd/m/Y'}
                }, {
                    name: 'status',
                    index: 'status',
                    width: 80,
                    align: "center"
                }, {
                    name: 'totEleme',
                    index: 'totEleme',
                    width: 80,
                    align: "center"
                }],
//                loadComplete: function(){
//                    jQuery("#list14 tr").each(function(){
//                        if (jQuery("#list14").getCell(this.id, 4) == "KO") {
//                            jQuery(this).css({
//                                backgroundColor: "#FF0000"
//                            })
//                        } else {
//                            if (jQuery("#list14").getCell(this.id, 8) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF0000"
//                                })
//                            }
//                            if (jQuery("#list14").getCell(this.id, 7) > 0) {
//                                jQuery(this).css({
//                                    backgroundColor: "#FF9900"
//                                })
//                            }
//                        }
//                    })
//                },
                rowNum: 20,
                rowList: [10, 20, 30],
                pager: jQuery('#pager14'),
                sortname: 'date_contrat',
                viewrecords: true,
                sortorder: "desc",
                multiselect: false,
                subGrid: true,
                subGridUrl: 'ajax/overview_grid_subgrid.php?',
                subGridRowExpanded: function(subgrid_id, row_id) {
                // we pass two parameters
                // subgrid_id is a id of the div tag created within a table
                // the row_id is the id of the row
                // If we want to pass additional parameters to the url we can use
                // the method getRowData(row_id) - which returns associative array in type name-value
                // here we can easy construct the following
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                    jQuery("#"+subgrid_table_id).jqGrid({
                      url: 'ajax/overview_grid_subgrid.php?&contratId='+row_id,
                      //url:"subgrid.php?q=2&id="+row_id,
                      datatype: "xml",
                      colNames: ['Id','Idproduit','Status','Label','Description','Ouverture','Cloture','Qty','Cout mensuel','Cout total'],
                      colModel: [
                        {name:"id",index:"id",width:55,key:true,hidden:true},
                        {name:"fk_product",index:"fk_product",width:55,hidden:true},
                        {name:"statut",index:"statut",width:180},
                        {name:"label",index:"label",width:180},
                        {name:"description",index:"description",width:180},
                        {name:"date_ouverture",index:"date_ouverture",width:150,sortable:true,sorttype:"date",formatter:"date",formatoptions:{srcformat: 'Y-m-d',newformat: 'd/m/Y'}},
                        {name:"date_fin_validite",index:"date_fin_validite",width:150,sortable:true,sorttype:"date",formatter:"date",formatoptions:{srcformat: 'Y-m-d',newformat: 'd/m/Y'}},
                        {name:"qty",index:"qty",width:80},
                        {name:"total_ht",index:"total_ht",width:180},
                        {name:"coutTot",index:"total_ht",width:180,hidden:true},
                        ],
                      height: "100%",
                      rowNum:20,
                      sortname: 'date_ouverture',
                      sortorder: "asc",
                      ondblClickRow: function( rowid) {
                          displayExtra("contratDet",row_id,rowid);
                      },
//                      loadComplete: function(){
//                            jQuery('#'+subgrid_table_id+ ' tr').each(function(){
//                                if (this.id != '_empty')
//                                {
//                                    if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "KO") {
//                                        jQuery(this).css({
//                                            backgroundColor: "#FF0000"
//                                        })
//                                    } else if (jQuery("#"+subgrid_table_id).getCell(this.id, 3) == "WARN") {
//                                            jQuery(this).css({
//                                                backgroundColor: "#FF9900"
//                                            })
//                                    }
//                                }
//                            })
//                        },
                   })
               },
               caption: "Contrats de location",
               }).navGrid('#pager14', {
                    add: false,
                    edit: false,
                    del: false
                });

}
function logout()
{
    Set_Cookie("logged","logout");
    jQuery.ajax({
       type: "POST",
       url: "ajax/logout.php",
       data: "key="+soccode,
       async: false,
       success: function(msg){
            location.reload();
       }
    });
}

</script>



<style>
    .ui-jqgrid-bdiv { width: 101% !important; }
    .ui-jqgrid-view td,     .ui-jqgrid-view th { font-size: 75%; }
</style>
EOF;

$header .= "<style>";
$header .= ".ui-tabs .ui-tabs-nav .ui-state-default a { color: #555555 !important;} ";
$header .= ".ui-tabs .ui-tabs-nav .ui-state-hover a { color: #111111 !important; } ";
$header .= ".ui-tabs .ui-tabs-nav .ui-state-active a { color: #111111 !important; } ";
$header .= ".ui-jqgrid-title { color: #ffffff; }";
$header .= "</style>";

llxHeader($header,"",1);

//    //choose soc
//    $requete = "SELECT rowid,nom
//                  FROM ".MAIN_DB_PREFIX."societe
//                 WHERE rowid in (SELECT fk_soc FROM ".MAIN_DB_PREFIX."contrat)
//              ORDER BY ".MAIN_DB_PREFIX."societe.nom";
//    $sql = $db->query($requete);
//
//    print "<form method='post' action='index.php' id='form'>";
//    print "<SELECT style='width: 200px;' onChange='lauchGrid()' name='soc' id='soc'>";
//    print "<option SELECTED value='-1'>Selectioner -></option>";
//    while ($res=$db->fetch_object($sql))
//    {
//        print "<option value='".$res->rowid."'>".$res->nom."</option>";
//    }
//    print "</SELECT></form>";


print '<div id="menuTab">';
print '    <ul id="menu" class="menu ui-widget">';

print '        <li class="ui-widget-header ui-state-default"><a href="propal.php">Proposition Commerciale</a></li>';

//print '        <li class="ui-widget-header ui-state-default"><a href="facture.php">Facture</a></li>';
print '        <li class="ui-widget-header ui-state-default"><a href="contrat.php">Contrat de services</a></li>';
print '        <li class="ui-widget-header ui-state-default"><a href="contratGA.php">Location</a></li>';

print '        <li class="ui-widget-header ui-state-default"><a id="logout" href="clientAccess.php">Logout</a></li>';
print '</div>';



  //Si le produit dans la commande est de type produit => affiche le produit, la photo
  //Si le produit dans la commande est de type service => affiche le service, la dur&eacute;e, la date de d&eacute;but
  //Affiche les details du contrat
  //Add on financement


}


?>