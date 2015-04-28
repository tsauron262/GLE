<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 6 janv. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : pilotage.php
  * GLE-1.1
  */

require_once('pre2.inc.php');
llxHeader();

if (!($user->admin || $user->local_admin)) accessforbidden();

$langs->load("synopsisGene@Synopsis_Tools");

$h=0;
  $head[$h][0] = DOL_URL_ROOT.'/Babel_GPI/admin.php';
  $head[$h][1] = $langs->trans('Admin GPI');
  $head[$h][2] = 'admin GPI';
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/Babel_GPI/index.php" target=\"_blank';
  $head[$h][1] = $langs->trans('Index GPI  (nouvelle fen&ecirc;tre)');
  $head[$h][2] = 'Index Externe';
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/Babel_GPI/pilotage.php';
  $head[$h][1] = $langs->trans('Pilotage GPI');
  $head[$h][2] = 'Pilotage GPI';
  $h++;

dol_fiche_head($head, 'Pilotage GPI', $langs->trans("CustomerPilotage"));

print "<p><h2 class='ui-widget-header ui-state-default' style='padding: 5px 15px; '> Acc&egrave;s client : </h2></p>";
print "<br>";
print "<pre  class='ui-widget-content ui-state-highlight' style='padding : 5px 10px; width: auto'><span class='ui-icon ui-icon-extlink' style='float: left; margin-right: 5px;'></span><a href='".DOL_URL_ROOT."Babel_GPI/index.php'>".DOL_URL_ROOT."Babel_GPI/index.php</pre>";

?>
<br>
<hr>
<br>
<br>
<p><h2 class='ui-widget-header ui-state-default' style='padding: 5px 15px'> R&eacute;capitulatif : </h2></p>
<br>
<table class="titre" style='border-collapse: collapse;' width=800 cellpadding=15>
<tr><th style='border: 1px Solid black; padding: 10px; color: white'>Nom de la soci&eacute;t&eacute;</th><th style='border: 1px Solid black;  padding: 10px; color: white'>Compte externe ?</th>
<?php
$requete = "SELECT DISTINCT ".MAIN_DB_PREFIX."societe.nom,
                    ".MAIN_DB_PREFIX."societe.rowid
               FROM ".MAIN_DB_PREFIX."contrat,
                    ".MAIN_DB_PREFIX."societe
              WHERE ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."contrat.fk_soc";

$sql = $db->query($requete);
while ($res=$db->fetch_object($sql))
{
    $okPass = "-";
    $requetePre = "SELECT *
                     FROM Babel_financement_access
                    WHERE fk_soc = ".$res->rowid;
    $sqlPre = $db->query($requetePre);
    $requete = "";
    $account="";
    if ($db->num_rows($sqlPre) > 0)
    {
        $okPass="x";
    }
    if ($pairImpair == "pair") { $pairImpair = "impair"; } else { $pairImpair = "pair"; }
    print "<tr class='".$pairImpair."'>
                <td style='border:1px Solid black;' width=60%>". $res->nom . "</td>
                <td style='border:1px Solid black;' align='center'>".$okPass . "</td>
           </tr>";
}
?>
</table>

</html>


