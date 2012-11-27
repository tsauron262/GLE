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

//Lister les contrats demandés dans la commande
//Faire un bouton creer si pas encore creéer
//Si déjà creer rempalcer par un lien

    require_once('../../main.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
    require_once(DOL_DOCUMENT_ROOT ."/core/modules/commande/modules_commande.php");
    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
    if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
    if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
    if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');

    $langs->load('orders');
    $langs->load('sendings');
    $langs->load('companies');
    $langs->load('bills');
    $langs->load('propal');
    $langs->load("synopsisGene@Synopsis_Tools");
    $langs->load('deliveries');
    $langs->load('products');

    if (!$user->rights->commande->lire) accessforbidden();


    // Securite acces client
    $socid=0;
    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }
    $commande = new Synopsis_Commande($db);
    if ($user->societe_id >0 && isset($_REQUEST["id"]) && $_REQUEST["id"]>0)
    {
        $commande->fetch((int)$_REQUEST['id']);
        if ($user->societe_id !=  $commande->socid) {
            accessforbidden();
        }
    }


    $html = new Form($db);
    $formfile = new FormFile($db);

    $id = $_REQUEST['id'];
    if ($id > 0)
    {
        $commande->fetch($id);
        if ($mesg) print $mesg.'<br>';

        print "<table cellpadding=10 width=600>";
        print "<tr><th class='ui-widget-header ui-state-default'>Description";
        print "    <th class='ui-widget-header ui-state-default'>Qt&eacute;";
        print "    <th class='ui-widget-header ui-state-default'>Contrat associ&eacute;";

            $requete = "SELECT fk_product,
                               ".MAIN_DB_PREFIX."commandedet.qty,
                               ".MAIN_DB_PREFIX."commandedet.rowid
                          FROM ".MAIN_DB_PREFIX."commandedet,
                               llx_product
                         WHERE llx_product.rowid = ".MAIN_DB_PREFIX."commandedet.fk_product
                           AND fk_product_type = 2
                           AND fk_commande = ".$id;
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql))
            {
                $prodTmp = new Product($db);
                $prodTmp->fetch($res->fk_product);
                print "<tr><td class='ui-widget-content'>".$prodTmp->getNomUrl(1)." ".utf8_encode($prodTmp->libelle)."<td align=center class='ui-widget-content'>".$res->qty;
                $requete = "SELECT *
                              FROM ".MAIN_DB_PREFIX."contratdet
                             WHERE fk_commande_ligne = ".$res->rowid;
                $sql1 = $db->query($requete);
                $num = $db->num_rows($sql1);
                print "<td class='ui-widget-content' align=center>";
                if ($num > 0 && $num == $res->qty)
                {
                    $arr = array();
                    while ($res1 = $db->fetch_object($sql1))
                    {
                        $contrat = new Contrat($db);
                        $contrat->fetch($res1->fk_contrat);
                        $arr[] = $contrat->getNomUrl(1);
                    }
                    print join('<br/>',$arr);
                } else if ($num > 0 ){
                    $arr = array();
                    $iter=0;
                    while ($iter < max($num,$res->qty))
                    {
                        $res1 = $db->fetch_object($sql1);
                        if ($res1->fk_contrat> 0)
                        {
                            $contrat = new Contrat($db);
                            $contrat->fetch($res1->fk_contrat);
                            $arr[] = $contrat->getNomUrl(1);
                        } else {
                            $requete = "SELECT *
                                          FROM ".MAIN_DB_PREFIX."contrat
                                         WHERE fk_soc = ".$commande->socid;
                            $sql2 = $db->query($requete);
                            $longHtml = "";
                            if ($db->num_rows($sql2) >0)
                            {
                                $longHtml .= "<select name='fk_contrat' id='fk_contrat'>";
                                while ($res2 = $db->fetch_object($sql2)){
                                    $longHtml .= "<option value='".$res2->rowid."'>".$res2->ref."</option>";
                                }
                                $longHtml .= "</select>";
                                $longHtml .= "<button onClick='createContrat2(".$res->fk_product.",".$res->rowid.")' class='butAction'>Ajouter au contrat</button>";
                            }
                            $arr[]=$longHtml;
                        }
                        $iter++;
                    }
                    print join('<br/>',$arr);

                } else {
                    //Si contrat existant => liste déroulante + ajout d'une ligne de contrat = lien contrat <=> commande
                    $requete = "SELECT *
                                  FROM ".MAIN_DB_PREFIX."contrat
                                 WHERE fk_soc = ".$commande->socid;
                    $sql2 = $db->query($requete);
                    if ($db->num_rows($sql2) >0)
                    {
                        print "<select name='fk_contrat' id='fk_contrat'>";
                        while ($res2 = $db->fetch_object($sql2)){
                            print "<option value='".$res2->rowid."'>".$res2->ref."</option>";
                        }
                        print "</select>";
                        print "<button onClick='createContrat2(".$res->fk_product.",".$res->rowid.")' class='butAction'>Ajouter au contrat</button>";
                    } else {
                        //Sinon creer un nouveau contrat et reviens ici
                        print "<button onClick='createContrat(".$res->fk_product.")' class='butAction'>Cr&eacute;er le contrat</button>";
                    }
                }
            }
    print "</table>";
    }

print <<<EOF
<script>
function createContrat2(pId,ligneId)
{
//    //TODO ajoute la ligne, ouvre la page
    jQuery.ajax({
        url:"ajax/xml/addProdToContrat-xml_response.php",
        data:"id="+comId+"&contratId="+jQuery('#fk_contrat').find(':selected').val()+"&prodId="+pId+"&comLigneId="+ligneId,
        datatype:"xml",
        type:"POST",
        cache:false,
        success:function(msg){
            if(jQuery(msg).find('OK').length > 0)
                location.href=DOL_URL_ROOT+"/contrat/fiche.php?id="+jQuery('#fk_contrat').find(':selected').val();
        }
    });
    //TODO passer en paramètre le numéro de ligne pour l'enregistrer dans la liaison contratdet<->commandedet
}

function createContrat(pId)
{
    location.href=DOL_URL_ROOT+"/contrat/fiche.php?action=create&socid="+socId+"&comId="+comId+"&originLine="+pId+"&returnPrepacom=1&typeContrat=7";
}
</script>
EOF;
?>