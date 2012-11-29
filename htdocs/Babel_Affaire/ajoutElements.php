<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 23 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : ajoutElements.php
  * GLE-1.2
  */
  //ajoute des éléments à une affaire
  //Lie une affaire à 1 ou n projets

  require_once('pre.inc.php');
  require_once('fct_affaire.php');
  require_once('Affaire.class.php');


  $socid = false;
  if ($user->societe_id > 0)
  {
    $action = '';
    $socid = $user->societe_id;
  }

  $id = -1;
  if ($_REQUEST['id'] > 0)
  {
    $id = $_REQUEST['id'];
    $affaireid = $_REQUEST['id'];
  }

  $langs->load('company');
  $langs->load('sendings');


    $jspath = DOL_URL_ROOT."/Synopsis_Common/jquery";
    $jsMainpath = DOL_URL_ROOT."/Synopsis_Common/js";
    $jqueryuipath = DOL_URL_ROOT."/Synopsis_Common/jquery/ui";
    $css = DOL_URL_ROOT."/Synopsis_Common/css";
    $imgPath = DOL_URL_ROOT."/Synopsis_Common/images";

    $js .= ' <link href="'.$css.'/jquery.treeview.css" title="default" type="text/css" rel="stylesheet">';
    $js .= ' <script src="'.$jspath.'/jquery.cookie.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jquery.treeview.js" type="text/javascript"></script>';
    $js .= ' <script src="'.$jspath.'/jquery.json.js" type="text/javascript"></script>';
    $js .= "<script>jQuery(document).ready(function(){ jQuery('#treeview').treeview({animated: 'slow',    collapsed: true
, unique: true,    persist: 'cookie', cookieId: 'gle-affaire-ajout-element-tree'  });
        jQuery('#contentBis').find('.ui-icon-trash').mouseover(function(){
            jQuery(this).parent().addClass('ui-state-default');
        });
        jQuery('#contentBis').find('.ui-icon-trash').mouseout(function(){
            jQuery(this).parent().removeClass('ui-state-default');
        });
        jQuery('#contentBis').find('.ui-icon-trash').click(function(){
            jQuery(this).parent().addClass('ui-state-active');
            curDelId = jQuery(this).parent().parent().attr('id');
            //confirm dialog
            jQuery('#deldialog').dialog('open');
            jQuery(this).parent().removeClass('ui-state-active');
        });
    jQuery('#deldialog').dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            Ok: function(){
                jQuery.ajax({
                    url: 'ajax/delElement-xml_response.php',
                    type: 'POST',
                    datatype:'xml',
                    data: 'id='+curDelId,
                    cache: false,
                    success: function(msg){
                        if (jQuery(msg).find('OK')){
                            //On le vire de la liste
                            var type = jQuery(msg).find('type').text();
                            var eid = jQuery(msg).find('eid').text();
                            var socid = jQuery(msg).find('soc').text();
                            var socname = jQuery(msg).find('socname').text();
                            jQuery('#contentBis').find('tbody.'+type).find('#'+eid).remove();
                            //on refraichit l'accordion
                            launchContent(socid,'<div>'+socname+'</div>');
                            jQuery('#deldialog').dialog('close');
                        }
                    }
                })
            },
            Annuler: function(){
                jQuery('#deldialog').dialog('close');
            }
        },
    });
  })</script>";
    $js .= "<script>var affaireId = ".$id."; DOL_URL_ROOT='".DOL_URL_ROOT."'; var curDelId='';</script>";
$js .= <<<EOF
<style>
    #contentBis th { color: white; text-transform: capitalize; }
    .ui-accordion-header {text-transform: capitalize;  }
    .treeview .hitarea { margin-right: 3px;}
    .treeview li { padding: 1px 0 3px 16px }
</style>
<script>
var currObj = "";
var currId = "";
function launchContent(pId,obj){
    currId = pId;
    currObj = obj;
    jQuery.ajax({
        url:"ajax/getElements_xml-response.php",
        data: "id="+pId+"&affaireId="+affaireId,
        datatype:"xml",
        type:"POST",
        success:function(msg){
            var longHtml = '<div id="accordion">'
            jQuery(msg).find('group').each(function(){
                var gid = jQuery(this).attr('id');
                longHtml += '<h3><a href="#">'+gid+'</a></h3><div><table class="tableContent" width=100% id="'+gid+'" cellpadding=5>';
                longHtml += "<thead><tr><th class='ui-widget-header ui-state-default' align=left>Ref</th>\
                                        <th class='ui-widget-header ui-state-default'>Tot. HT</th>\
                                        <th class='ui-widget-header ui-state-default'>Ajouter</th></thead><tbody>";
                jQuery(this).find('element').each(function(){
                    var eid = jQuery(this).find('rowid').text();
                    var ref = jQuery(this).find('ref').text();
                    var amount = jQuery(this).find('amount').text();
                    longHtml += '<tr class="ui-widget-content" id="'+eid+'">\
                                        <td id="ref">'+ref+'</td>\
                                        <td align=center>'+amount+'</td>\
                                        <td align="center"><span onClick="addElement(\''+eid+'\',\''+gid+'\',this)" class="ui-icon ui-icon-circle-plus"></span></td>';
                })
                longHtml += "</tbody>";
                longHtml+='</table></div>';
            });
            longHtml += "</div>";
            jQuery('#content').replaceWith('<div id="content">'+longHtml+'</div>');
            jQuery('#accordion').accordion({ animated: 'bounceslide', active: false, collapsible: true , autoHeight: false, });
            jQuery('#nomSocElement').replaceWith('<span id="nomSocElement"> - Tiers : '+jQuery(obj).text()+'</span>');
            jQuery('#accordion').find('h3').each(function(){
                var accContent= jQuery(this).next('div');
                var cnt = accContent.find('tbody').find('tr').length;
                jQuery(this).find('a').append('&nbsp;<span style="display: inline;">('+cnt+')</span>');
            });
        }
    })
}
var ContentArray= new Array();
function refreshContentArray()
{
    jQuery('#contentBis').find('table tr').each(function(){
        var gid = jQuery(this).attr('id');
        if (!is_array(ContentArray[gid])){
            ContentArray[gid]=new Array();
        }
//        console.log('gid '+gid);
        jQuery(this).find('tr').each(function(){
            var eid = jQuery(this).attr('id');
            var ref = jQuery(this).find('#ref').text();
            ContentArray[gid][eid]=ref;
        });
    });
}
function dumpContentArray()
{
    console.log(jQuery.toJSON(ContentArray));
}
function resetContentArray()
{
    ContentArray=new Array();
}
function is_array(input){
    return typeof(input)=='object'&&(input instanceof Array);
  }


function addElement(eid,gid,obj)
{
    //0 ask si on prend en compte les elements liés (facture etc ...);

    //1 Send to Ajax to add in db
    jQuery.ajax({
        url: 'ajax/addElement-xml_response.php',
        data: "id="+affaireId+"&eid="+eid+"&gid="+gid,
        datatype: "xml",
        type: 'POST',
        success: function(msg){
            if (jQuery(msg).find('OK').length > 0)
            {
                jQuery(obj).parent().parent().remove();
                jQuery(msg).find('group').each(function(){
                    var type = jQuery(this).attr('id');
                    jQuery(this).find('element').each(function(){
                        var ref = jQuery(this).find('ref').text();
                        var soc = jQuery(this).find('soc').text();
                        var statut = jQuery(this).find('statut').text();
                        //prob 1 seul element dans la reponse
                        if (jQuery('#contentBis').find('table').find('tbody.'+type).length > 0)
                        {
                            jQuery('#contentBis').find('table').find('tbody.'+type).append('<tr><td nowrap>'+soc+'<td nowrap>'+ref+'<td nowrap align=right>'+statut+"<td nowrap width=16><span class='ui-icon ui-icon-trash' style='margin-top: -6px;'></span>");
                        } else {
                            jQuery('#contentBis').find('table').append('<thead class="'+type+'"><tr><th colspan=4>'+type+'</th></thead> \
                                                                              <tbody class="'+type+'"><tr class="ui-widget-content '+type+'"><td  nowrap>'+soc+'<td nowrap>'+ref+'<td nowrap  align=right>'+statut+'<td width=16><span class="ui-icon ui-icon-trash"  style="margin-top: -6px;"></span></tbody>');
                        }
                    });
                });
                //refresh accordion
                launchContent(currId,currObj);
            }
        }
    })
    //2 on Success => delete element from list & add to contentbis
}
</script>
EOF;

if ($id > 0 )
{
   llxHeader($js,'Affaires - Ajout',"",1);

//    llxHeader("",$langs->trans("Referers"));
    print "<br/>";
    print_fiche_titre($langs->trans("Espace affaire - ajout d'&eacute;l&eacute;ments"));
    print "<br/>";
    $affaire = new Affaire($db);
    $affaire->fetch($affaireid);

    print_cartoucheAffaire($affaire,'Ajout');

    print "<br/>";
    print "<br/>";
    print "<div class='titre'>Ajout d'&eacute;l&eacute;ments</div>";

    print "<br/>";
    print "<br/>";


  print '<table width=1024 height=80% cellpadding=0 style="border-collapse: collapse;">';
  print "<tr><td width=200' class='ui-widget-content' valign=top>";

  //Arbre avec les clients par nom
    $title=$langs->trans("ListOfThirdParties");

    $sql = "SELECT s.rowid, s.nom, s.ville";
    $sql.= ", s.prefix_comm, s.client, s.fournisseur ";
    if ($conf->global->MAIN_MODULE_BABELGA)
    {
        $sql.=", s.cessionnaire ";
    }
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE 1=1 ";
    if ($socid)
    {
        $sql .= " AND s.rowid = ".$socid;
    }
    if (! $user->rights->societe->client->voir && ! $socid) //restriction
    {
        $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    }
    if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
    {
        if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
    }
    $sql .= " ORDER BY s.nom ";


    print "<div style='padding: 5px 3px;' class='ui-wiget-header ui-state-default'>".$title."</div>";

    $result = $db->query($sql);
    print "<ul id='treeview' style='margin-top: 0px;'>";
    $list1  = "";
    $list2  = "";
    $list3  = "";
    $list4  = "";
    if ($result)
    {
        while ($res = $db->fetch_object($result))
        {
            if ($res->client == 1)
            {
                $list1 .= "<li><a href='#' onclick='launchContent(".$res->rowid.",this)'>".img_object('company','company').' '.$res->nom."</a>";
            }
            if ($res->client == 2)
            {
                $list2 .= "<li><a href='#' onclick='launchContent(".$res->rowid.",this)'>".img_object('company','company').' '.$res->nom."</a>";
            }
            if ($res->fournisseur == 1)
            {
                $list3 .= "<li><a href='#' onclick='launchContent(".$res->rowid.",this)'>".img_object('company','company').' '.$res->nom."</a>";
            }
            if ($res->cessionnaire && $res->cessionnaire == 1)
            {
                $list4 .= "<li><a href='#' onclick='launchContent(".$res->rowid.",this)'>".img_object('company','company').' '.$res->nom."</a>";
            }
        }
    } else {
        dol_print_error($db);
    }
    print "<ul style='margin-top: 0px;'><li><span  style='font-size:12px; padding: -2px 3px; border: 0px transparent;' class='titre ui-state-active'>Clients</span><ul style='margin-top: 0px;'>".$list1."</ul></li>
           </ul>
           <ul style='margin-top: 0px;'><li><span  style='font-size:12px; padding: -2px 3px; border: 0px transparent;' class='titre ui-state-active'>Prospects</span><ul style='margin-top: 0px;'>".$list2."</ul></li>
           </ul>
           <ul style='margin-top: 0px;'><li><span  style='font-size:12px; padding: -2px 3px; border: 0px transparent;' class='titre ui-state-active'>Fournisseurs</span><ul style='margin-top: 0px;'>" . $list3."</ul></li>
           </ul>
           <ul style='margin-top: 0px;'><li><span  style='font-size:12px; padding: -2px 3px; border: 0px transparent;' class='titre ui-state-active'>Cessionnaire</span><ul style='margin-top: 0px;'>" . $list4."</ul></li>";
    print "</ul>";



  print "<td class='ui-widget-content' valign=top>";
  print '<div class="ui-wiget-header ui-state-default" style="padding: 5px 3px;">&Eacute;l&eacute;ments<span id="nomSocElement"></span></div>';
  print "<div id='content'></div>";

  print "<td width=300 class='ui-widget-content' valign=top>";
  print '<div class="ui-wiget-header ui-state-default" style="padding: 5px 3px;">Contenu de l\'affaire</div>';
  print "<div id='contentBis'>";

  print "<table width=100% >";

  $requete = "SELECT * FROM Babel_Affaire_Element WHERE affaire_refid = ".$id." ORDER BY type";
  $sql = $db->query($requete);

  require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
  require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
  require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
  require_once(DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php');
  require_once(DOL_DOCUMENT_ROOT.'/livraison/livraison.class.php');
  require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
  require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
  require_once(DOL_DOCUMENT_ROOT."/Synopsis_DemandeInterv/demandeInterv.class.php");
  require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
  require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
  require_once(DOL_DOCUMENT_ROOT."/Babel_TechPeople/deplacements/deplacement.class.php");

  $tmpSoc = new Societe($db);
  $htmlArr = array();
  while ($res=$db->fetch_object($sql))
  {
    $ref = "";
    $type = "";
    $statut = "";
    $socHtml = "";
    $err= false;
     switch ($res->type){
        case ($res->type == 'propal' ||$res->type ==  'propale'):
        {
            $obj=new Propal($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'commande':
        {
            $obj=new Commande($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'expedition':
        {
            $obj=new Expedition($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'livraison':
        {
            $obj=new Livraison($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'facture':
        {
            $obj=new Facture($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'facture fournisseur':
        {
            $obj=new FactureFournisseur($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref_supplier;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'commande fournisseur':
        {
            $obj=new CommandeFournisseur($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'projet':
        {
            $obj=new Project($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'DI':
        {
            $obj=new DemandeInterv($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'FI':
        {
            $obj=new Fichinter($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'contact':
        {
            $obj=new Contact($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->ref;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'domain':
        {
            $obj=new Domain($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->label;
            $statut = $obj->getLibStatut(5);
            $socHtml = "-";
        }
        break;
        case 'SSLCert':
        {
            $obj=new Project($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->label;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        case 'Licence':
        {
            $obj=new Project($db);
            $obj->fetch($res->element_id);
            $type= $res->type;
            $ref = $obj->label;
            $statut = $obj->getLibStatut(5);
            $socHtml = $obj->societe->getNomUrl(1,'',12);
        }
        break;
        default:
        {
            $err = true;
        }
     }

     if (!$err)
     {
        $htmlArr[$type][] = "<tr id='".$res->id."' class='".$type."'>
                                <td nowrap>".$socHtml
                              ."<td nowrap>".$ref
                              ."<td nowrap align=right>".$statut
                              ."<td nowrap width=16><span class='ui-icon ui-icon-trash' style='margin-top: -6px;'></span>";
     }
  }
  //sort($htmlArr);
  //var_dump($htmlArr);
  foreach($htmlArr as $type => $html)
  {
    print '<thead class="'.$type.'"><tr><th colspan=4>'.$type."</tr></thead><tbody class='".$type."'>";
    print join(' ',$html);
    print "</tbody>";
  }
  print "</table>";

  print "</div>";

  //Contenu

  //1 Tab projet, commande, propal, facture, expédition, livraison, .. et domaines

  print "</table>";

} else {
//Mode list
   llxHeader($js,'Affaires - Ajout',"",1);


    $requete = "SELECT * FROM Babel_Affaire";
    $sql = $db->query($requete);
    print "<table>";
    while ($res = $db->fetch_object($sql))
    {
        print "<tr id='".$res->id."'><td><a href='".$_SERVER['PHP_SELF']."?id=".$res->id."'>".$res->nom."</a>";
    }
    print "</table>";

}
//dialog efface l element
print "<div id='deldialog'>";
print "<p>&Ecirc;tes vous sur de vouloir supprimer cet &eacute;l&eacute;ment ?</p><div id='detail'></div>";
print "</div>";

//print "<button onClick='refreshContentArray(); dumpContentArray();'>dump</button>";


?>