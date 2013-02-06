<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : index.php
  * GLE-1.1
  */

  require_once('pre.inc.php');

  if ($user->rights->GeoBI->GeoBI->Affiche != 1){  accessforbidden(); }

  $js = '<script>';
  $js .= ' jQuery(document).ready(function(){ jQuery("#tabs").tabs({ spinner: "Chargement", cache: true,fx: { opacity: "toggle" }  }); });';
  $js.= 'jQuery(document).ready(function(){ jQuery("#chooseSoc").change(function(){ location.href="view.php?socid="+jQuery(this).find(":selected").val();  }); });';
  $js .= '</script>';
  llxHeader($js,"Geo - BI","",true);

  print "<div class='fiche'>";

  print '<div id="tabs">';
  print '    <ul>';
  print '        <li><a href="#fragment-2"><span>Cartes</span></a></li>';
  print '        <li><a href="#fragment-1"><span>Clients</span></a></li>';
  if ($user->rights->GeoBI->GeoBI->Modifie)
      print '        <li><a href="#fragment-3"><span>Param&eacute;trage</span></a></li>';
  print '    </ul>';

  print '<div id="fragment-1">';
  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."societe, Babel_GeoBI WHERE Babel_GeoBI.socid = ".MAIN_DB_PREFIX."societe.rowid order by ".MAIN_DB_PREFIX."societe.nom";
  $sql = $db->query($requete);
  if ($db->num_rows($sql) > 0)
  {
      print "<SELECT id='chooseSoc'>";
      while ($res=$db->fetch_object($sql))
      {
        print "<option value='".$res->rowid."'>".$res->nom."</option>";
      }
      print "</select>";

  } else {
    print "Pas de soci&eacute;t&eacute;s g&eacute;olocalis&eacute;e";
  }
  print "</div>";

  print '<div id="fragment-2">';
  print "<table><tr><td>";
  print "<a href='gmaps.php'>Carte globale</a>";
  print "<tr><td>";
  print "<a href='gmaps-propal.php'>Carte propositions commerciales</a>";
  print "<tr><td>";
  print "<a href='gmaps-commande.php'>Carte commandes clients</a>";
  print "<tr><td>";
  print "<a href='gmaps-exped.php'>Carte exp&eacute;ditions</a>";
  print "<tr><td>";
  print "<a href='gmaps-facture.php'>Carte factures</a>";
  print "</table>";
  print "</div>";

  if ($user->rights->GeoBI->GeoBI->Modifie)
  {
      print '<div id="fragment-3">';
      print "<table><tr><td>";
      print "<a href='globalImport.php'>Import en masse</a>";
      print "<tr><td>";
      print "<a href='import.php'>Import & Ajustement</a>";
      print "</table>";
      print "</div>";
      print "</div>";
      print "</div>";
  }

?>
