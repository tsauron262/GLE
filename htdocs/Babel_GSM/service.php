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
    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit
//TODO: secondary menu => deco comme le premier, mais dans une autre couleur + entete avec icone
require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");
if ($user->rights->BabelGSM->BabelGSM_com->AfficheServices !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader("", "Dolibarr Clients", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);
$gsm->MainInit();


print '<TABLE  width="100%" class="nobordernopadding">';
//Liste les propal, le montant total HT, le status
$requete = "SELECT llx_product.rowid" .
        "     FROM llx_product " .
        "    WHERE llx_product.fk_product_type = 1" . //produits
        " ORDER BY label" .
        "          ";
//print $requete;
$resql=$db->query($requete);
$pair= true;
print "<TR><TH>Ref</TH><TH>Libelle</TH><th>Prix HT</TH><th>Statut</th>";
if ($resql)
{
    while ($res=$db->fetch_object($resql))
    {
        if ($pair)
        {
            $pair=false;
            print "<TR class='pair'>";
        } else {
            $pair=true;
            print "<TR class='impair'>";
        }
        $prod = new Product($db);
        $prod->fetch($res->rowid);
        $soc = new Societe($db);
        $soc->fetch($prod->socid);

        print "    <td><a href='product_detail.php?product_id=".$prod->id."'> ".img_object("product","product")."&nbsp;".$prod->ref." </a></td>";
        print "    <td><a href='product_detail.php?product_id=".$prod->id."'> ".img_object("product","product")."&nbsp;".$prod->libelle." </a></td>";
        print "    <TD align=center>".price($prod->price,0,'',1,0)."&nbsp;&euro;</TD>";
        print "    <TD>".$prod->getLibStatut(4)."</TD>";

        print "</TR>";
    }
}
print "</TABLE>";
$gsm->jsCorrectSize(true);


?>