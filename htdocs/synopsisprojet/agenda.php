<?php
/*
 /**
  *
  * Name : agenda.php
  * GLE-1.0
  */



require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/core/lib/synopsis_project.lib.php");
require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");

  $projId = $_REQUEST['id'];
  $projet = new SynopsisProject($db);
  $projet->id = $projId;
  $projet->fetch($projet->id);

  //Pour un projet, trouver toutes les ressources (humaine / matériel)

  //Assigner des ressources global au projet

  //Faire gestion de ressource matériels dans GLE avec calendrier => planning occupation

  //Pour chacun afficher une table
  //Col 1 : nom de la ressource pour toutes les lignes
  //Col 2 : ligne 1 à n Taches supperpossable a la gantt sans dépendances
  //Col 2 : ligne n+1 bande occupation : si ressource equipe :> max Ressource :> = nb membre equipe * 100
  //                                     si ressource matos  :> max Ressource :> configurer default 100
  //                                     si ressource people :> max Ressource :> 100
  // Attn si vacances dans HRM



llxHeader($header,"Projet - Agenda","");
    $head=synopsis_project_prepare_head($projet);
    dol_fiche_head($head, 'Agenda', $langs->trans("Project"));

?>
