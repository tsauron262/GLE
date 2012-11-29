<?php

/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 10 mars 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : propal-xmlresponse.php GLE-1.1
  */

require_once('../../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');

$id = $_REQUEST['id'];
$val = $_REQUEST['value'];

if ('x'.intval($id) == 'x')
{
    print "Erreur: Pas d'id de commande";
}
if (!$user->rights->commande->creer)
{
    print "Erreur: action interdite";
}
if ($val."x" != "x"){

    $requete = "UPDATE ".MAIN_DB_PREFIX."commande SET note_public ='".addslashes( utf8_decode($val))."' WHERE rowid =".$id;
    $sql = $db->query($requete);
    print htmlentities(utf8_decode($val));
} else {
    print "Erreur d'enregistrement";
}

print "";




?>