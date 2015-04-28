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

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

if ($user->rights->BabelGSM->BabelGSM->AfficheDocuments !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
llxHeader();


print "<SCRIPT type='text/javascript'>";
print<<<EOF
var MenuDisplay = "false";
function MenuDisplayCSS()
{
    if (MenuDisplay=="false")
    {
        document.getElementById('menuDiv').style.display="block";
        MenuDisplay="true";
    } else {
        document.getElementById('menuDiv').style.display="none" ;
        MenuDisplay="false";
    }
}

function DisplayDet(inpt)
{
    location.href=inpt+".php";

}

EOF;
print "\n</SCRIPT>";

print "\n<STYLE type='text/css'>";
print<<<EOF
    #mnubut:HOVER
    {
        text-decoration: underline;
        cursor: pointer;
        color: #0000FF;
    }
    .menuDiv, #menudiv
    {
        display: none;
        position: absolute;
        z-index: 200;
        background-color: #FF0000;
        display: none;
        z-index: 200;
        float:left;
        max-width: 60%;
        height: 100%;
        border-width: 1px;
        border: Solid;
        border-color: #FF0000;
    }
    .menu:HOVER, #menu:HOVER
    {
        text-decoration: underline;
        cursor: pointer;
        color: #0000FF;
    }

EOF;
print "</STYLE>\n";

print "<DIV><SPAN id='mnubut' onClick='MenuDisplayCSS()'>Menu</SPAN></DIV>\n";

print "<DIV id='menuDiv' class='menuDiv' style=''>";
print "<UL><LI onClick='DisplayDet(\"index\")' class='menu'>Accueil";
print "    <LI onClick='DisplayDet(\"propal\")' class='menu'>Propal";
print "    <LI onClick='DisplayDet(\"commande\")' class='menu'>Commande";
print "    <LI onClick='DisplayDet(\"facture\")' class='menu'>Facture";
print "    <LI onClick='DisplayDet(\"paiement\")' class='menu'>Paiement";
print "    <LI onClick='DisplayDet(\"expedition\")' class='menu'>Expedition";
print "    <LI onClick='DisplayDet(\"stock\")' class='menu'>Stock";
print "    <LI onClick='DisplayDet(\"fournisseur\")' class='menu'>Fournisseur";
print "    <LI onClick='DisplayDet(\"client\")' class='menu'>Client";
print "    <LI onClick='DisplayDet(\"prospect\")' class='menu'>Proscpect";
print "    <LI onClick='DisplayDet(\"documents\")' class='menu'>Documents";
print "    <LI onClick='DisplayDet(\"intervention\")' class='menu'>Fiche Intervention";
print "</DIV>\n";

print "TODO";
$gsm->jsCorrectSize(true);

?>