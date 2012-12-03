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
 require_once("pre.inc.php");
 require_once("main.inc.php");
$langs->load("synopsisGene@Synopsis_Tools");

 $processid = $_GET['process_id'];
$filejs = 'js/BabelProcess.js';
$line = file_get_contents($filejs);
llxHeader("","Process");

//tips js
print "<SCRIPT TYPE='text/javascript'>";
print $line;
print "</SCRIPT>";


 print "<TABLE width=90%>";
 //Nom du process
 print "<tr><th align=left style='padding-left: 10pt;'>Nom du process";
 print "    <input type='text' name='title'/>";
 print "</table>";

 //Choix de l'input
 // 1 propal
 // 2 commande sans produits
 // 3 commande avec produits
 // 4 commandes avec services
 // 5 facture
 // 6 paiement

 //Etape et options
 print "<div id='etape_1'>";
 print "   <table width=90% class='border'>";
 print "       <tr ><th rowspan=6 style='border: 1px Solid #000000' ><span id='StepNum_1' >Step <select name='StepNumSel_1'><option value='1' SELECTED>1</option></select></span></tr>";
 print "       <tr class='pair'><td>".$langs->trans('BabelStepName')."</td><td><input style='width: 350px' type='text' name='name_1'></td>";
 print "       <tr class='impair'><td>".$langs->trans('BabelMandatory')."</td><td><span name='chgName' style='font-weight: 600' id='span_mandatory_1'>non</span> <input type='checkbox' name='mandatory_1'\n onClick='chkBoxString(this);' \n></td>";
 print "       <tr class='pair'><td>".$langs->trans('BabelStepDesc')."</td><td><input style='width: 350px'  type='text' name='desc_1'></td>";
 print "       <tr class='impair'><td>".$langs->trans('BabelStepDoc')."</td><td><input style='width: 350px'  type='file' name='file_1'></td>";
 print "       <tr class='pair'><td>".$langs->trans('BabelStepValidator')."</td><td>";
 print "               <SELECT style='width: 350px'  name='validator_id'>";
 $requete = "SELECT firstname," .
        "           name, " .
        "           rowid " .
        "      FROM ".MAIN_DB_PREFIX."user " .
        "     WHERE ".MAIN_DB_PREFIX."user.rowid <> 1" .
        "  order by name";
 $resql = $db->query($requete);
 if ($resql)
 {
    while ($res=$db->fetch_object($resql))
    {
        print "<OPTION value='".$res->rowid."'>".$res->name ." " . $res->firstname."</OPTION>";
    }
 }
 print "               </SELECT></td>";
 print "<TR><TD class='impair' colspan = 3 align='left' style='padding-left: 3pt;'>";
 print " <div id='plus_1'  class='buttonajax' style='padding: 10px; max-width: 12px; float: right;' onClick='addStep(this)'> ".img_picto('plus',"edit_add")."</div>";
 print " <div id='moins_1' class='buttonajax' style='padding: 10px; max-width: 12px; float: right;' onClick='delStep(this)'>".img_picto('moins','edit_remove')." </div>";
 print "</th> </TR>";
 print "</table>";
 print "</DIV>";

 //Model de documents

?>
