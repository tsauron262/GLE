<?php
/*
 * GLE by Synopsis et DRSI
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

/**     \defgroup   ProspectBabel     Module ProspectBabel
        \brief      Module pour inclure ProspectBabel dans Dolibarr
*/

/**
        \file       htdocs/core/modules/modProspectBabel.class.php
        \ingroup    ProspectBabel
        \brief      Fichier de description et activation du module de Prospection Babel
*/

include_once "DolibarrModules.class.php";

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modBabelGA extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelGA($DB)
    {
        $this->db = $DB ;
        $this->numero = 22233;

        $this->family = "OldGleModule";
        $this->name = "BabelGA";
        $this->description = "Gestion d'actif";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELGA';
        $this->special = 0;
        $this->picto='GA';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "Babel_GA.php";
//        $this->config_page_url = "";

        // Dependences
        $this->depends = array("modPropale",'modContrat','modFacture','modCommande');
        $this->requiredby = array();

        // Constantes
        $this->const = array();

//        // Boites
//        $this->boxes = array();

    // Boites
//    $this->boxes = array();
//    $r=0;
//    $this->boxes[$r][1] = "box_deplacement.php";
//    $r++;


        // Permissions
        $this->rights = array();
        $this->rights_class = 'GA';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s aux menus GA';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'AfficheGA'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de financement cessionnaire';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteAffiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifie les taux de financement cessionnaire';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteModifier'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de financement fournisseur';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteFournAffiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifie les taux de financement fournisseur';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteFournModifier'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de financement client';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteClientAffiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifie les taux de financement client';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteClientModifier'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de financement utilisateur';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteUserAffiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifie les taux de financement utilisateur';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'VenteUserModifier'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de marge (client, fournisseur, cessionnaire & utilisateur)';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'MargeAffiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Modifie les taux de marge (client, fournisseur, cessionnaire & utilisateur)';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'MargeModifier'; // Droit
        $r ++;


        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Administrer les taux';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Taux'; // Famille
        $this->rights[$r][5] = 'Admin'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Cr&eacute;&eacute;r une proposition de financement';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'Creer'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir une proposition de financement (n&eacute;c&eacute;ssite le droit d\'affichage sur les propositions commerciales)';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Clotur&eacute;r une proposition de financement';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'Cloturer'; // Droit
        $r ++;



        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Effacer une proposition de financement';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'Effacer'; // Droit
        $r ++;


        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Valider une proposition de financement avec un montant max de validation';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'Valider'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Valider toutes les propositions de financement';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'ValiderTous'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Interface de proposition de financement en mode Avanced';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'modeAdvanced'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Interface de proposition de financement en mode Medium';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'modeMedium'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Interface de proposition de financement en mode Simple';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'modeSimple'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de financement dans les propositions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'voirTaux'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Modifier les taux de financement dans les propositions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'modTaux'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les taux de marge dans les propositions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'voirTauxMarge'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Modifier les taux de marge dans les propositions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'propale'; // Famille
        $this->rights[$r][5] = 'modTauxMarge'; // Droit
        $r ++;



        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir les cessionnaires';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'cessionnaire'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Cr&eacute;&eacute;r un tiers de type cessionnaire';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'cessionnaire'; // Famille
        $this->rights[$r][5] = 'Ajouter'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Modifier un tiers de type cessionnaire';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'cessionnaire'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Effacer un tiers de type cessionnaire';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'cessionnaire'; // Famille
        $this->rights[$r][5] = 'Effacer'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir le stock de location';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'stock'; // Famille
        $this->rights[$r][5] = 'AfficheStock'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir le stock de location par cessionnaire';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'stock'; // Famille
        $this->rights[$r][5] = 'AfficheStockCess'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir le stock de location par fournisseur';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'stock'; // Famille
        $this->rights[$r][5] = 'AfficheStockFourn'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Voir le stock de location client';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'stock'; // Famille
        $this->rights[$r][5] = 'AfficheStockClient'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Cr&eacute;er un avenant';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'CreateAvenant'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Renouveller un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'RenewContrat'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'G&eacute;n&eacute;rer un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Generate'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Effacer un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Effacer'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'T&eacute;l&eacute;charger un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Telecharger'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Valider un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Valider'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."$r";// this->numero ."". 1
        $this->rights[$r][1] = 'Lire un contrat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Lire'; // Droit
        $r ++;


    // Menus
        //------
        $this->menus = array();            // List of menus to add
        $r=0;
//TODO menu
//         Top menu
//        $this->menu[$r]=array('fk_menu'=>0,
//                            'type'=>'top',
//                            'titre'=>'MenuGA',
//                            'mainmenu'=>'GA',
//                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
//                            'url'=>'/Babel_GA/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>110,
//                            'perms'=>'$user->rights->GA->all->AfficheMenu',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;

//        // Left menu linked to top menu
//
//        //index d module deplacement
//
//        $this->menu[$r]=array('fk_menu'=>'r=0',
//                            'type'=>'left',
//                            'titre'=>'Deplacements',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Babel_GA/deplacements/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->GA->ndf->AfficheMien || $user->rights->GA->ndf->Affiche',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//
////        1er lien
//        $this->menu[$r]=array('fk_menu'=>'r=0',
//                            'type'=>'left',
//                            'titre'=>'Cessionnaire',
//                            'mainmenu'=>'companies',
//                            'url'=>'/Babel_GA/cessionnaire.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->GA->cessionnaire->Afficher',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//        $this->menu[$r]=array('fk_menu'=>'r=1',
//                            'type'=>'left',
//                            'titre'=>'Cessionnaire',
//                            'mainmenu'=>'companies',
//                            'url'=>'/soc.php?action=create&createCessionnaire=1',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->GA->cessionnaire->Afficher',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;

//        $this->menu[$r]=array('fk_menu'=>'r=1',
//                            'type'=>'left',
//                            'titre'=>'Voir les NDF',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Babel_GA/deplacements/index.php?showall=1',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>2,
//                            'perms'=>'$user->rights->GA->ndf->Affiche',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//
//
//        //index menu intervention
//
//
//        // Left menu linked to top menu
//        $this->menu[$r]=array('fk_menu'=>'r=0',
//                            'type'=>'left',
//                            'titre'=>'Interventions',
//                            'mainmenu'=>'GA',
//                            'url'=>'/fichinter/index.php?leftmenu=ficheinter',
//                            'langs'=>'interventions',
//                            'position'=>1,
//                            'perms'=>'$user->rights->ficheinter->lire',
//                            'target'=>'',
//                            'user'=>0);
//        $rem = $r;
//        $r++;
//        //1er lien
//
//        $this->menu[$r]=array('fk_menu'=>'r='.$rem,
//                            'type'=>'left',
//                            'titre'=>'NewIntervention',
//                            'mainmenu'=>'GA',
//                            'url'=>'/fichinter/fiche.php?action=create&leftmenu=ficheinter',
//                            'langs'=>'interventions',
//                            'position'=>1,
//                            'perms'=>'$user->rights->ficheinter->creer',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//        //2eme lien
//
//        $this->menu[$r]=array('fk_menu'=>'r='.$rem,
//                            'type'=>'left',
//                            'titre'=>'ListOfInterventions',
//                            'mainmenu'=>'GA',
//                            'url'=>'/fichinter/index.php?leftmenu=ficheinter',
//                            'langs'=>'interventions',
//                            'position'=>2,
//                            'perms'=>'$user->rights->ficheinter->lire',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//
//        $this->menu[$r]=array('fk_menu'=>'r='.$rem ,
//                            'type'=>'left',
//                            'titre'=>'AllDI',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Synopsis_DemandeInterv/index.php?leftmenu=ficheinter',
//                            'langs'=>'interventions',
//                            'position'=>3,
//                            'perms'=>'$user->rights->synopsisdemandeinterv->lire',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//
//        $this->menu[$r]=array('fk_menu'=>'r='.$rem ,
//                            'type'=>'left',
//                            'titre'=>'NewDI',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Synopsis_DemandeInterv/fiche.php?action=create&leftmenu=ficheinter',
//                            'langs'=>'interventions',
//                            'position'=>4,
//                            'perms'=>'$user->rights->synopsisdemandeinterv->creer',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;


//        // Left menu linked to top menu
//        $this->menu[$r]=array('fk_menu'=>'r=0',
//                            'type'=>'left',
//                            'titre'=>'Note de frais',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Babel_GA/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->ficheinter->lire',
//                            'target'=>'',
//                            'user'=>0);
        // Left menu linked to top menu
//        $this->menu[$r]=array('fk_menu'=>'r=1',
//                            'type'=>'left',
//                            'titre'=>'Note de frais',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Babel_GA/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>1,
//                            'perms'=>'$user->rights->GA->AfficheMien',
//                            'target'=>'',
//                            'user'=>0);
//        $rem = $r;
//        $r++;
//        $this->menu[$r]=array('fk_menu'=>'r=2',
//                            'type'=>'left',
//                            'titre'=>'Faire une NDF',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Babel_GA/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>2,
//                            'perms'=>'$user->rights->GA->AfficheMien',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;
//        $this->menu[$r]=array('fk_menu'=>'r=3',
//                            'type'=>'left',
//                            'titre'=>'Voir les NDF',
//                            'mainmenu'=>'GA',
//                            'url'=>'/Babel_GA/deplacement/index.php',
//                            'langs' => 'synopsisGene@Synopsis_Tools',
//                            'position'=>3,
//                            'perms'=>'$user->rights->GA->Affiche',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;

    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array();

//    $requete = "ALTER TABLE ".MAIN_DB_PREFIX."propal ADD column isFinancement tinyint(1) default 0";
//    $this->db->query($requete);
//    $requete = "ALTER TABLE ".MAIN_DB_PREFIX."societe ADD column cessionnaire tinyint(1) default 0";
//    $this->db->query($requete);

    $menu=array();
 //Get the value of the parent menu:
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."menu where mainmenu = 'companies' and fk_menu = (SELECT rowid FROM ".MAIN_DB_PREFIX."menu where mainmenu = 'companies' and fk_menu =0 AND level=-1)  and level = 0  and position=0";
        $resql = $this->db->query($requete);
        $parentId = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $parentId = $res->rowid;
        }

        $requete = "SELECT max(position) + 1 as res1 FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu = 'companies' AND level = 1 ";
        $resql = $this->db->query($requete);
        $pos = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $pos = $res->res1;
        }
//
        $r=1;
//        // Left menu linked to top menu

        $menu[$r]=array('fk_menu'=>$parentId,
                            'type'=>'left',
                            'titre'=>'Cessionnaires',
                            'mainmenu'=>'companies',
                            'url'=>'/Babel_GA/cessionnaire.php?leftmenu=companies',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'rowid'=>$this->numero . $r,
                            'position'=>$pos,
                            'perms'=>'$user->rights->GA->cessionnaire->Afficher',
                            'target'=>'',
                            'level'=>1,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==companies'));
        $s=$r;$r++;
        $menu[$r]=array('fk_menu'=> $this->numero . $s ,
                            'type'=>'left',
                            'titre'=>'Cr&eacute;er un cessionnaire',
                            'mainmenu'=>'companies',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/soc.php?action=create&createCessionnaire=1&leftmenu=companies',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>0,
                            'perms'=>'$user->rights->GA->cessionnaire->Ajouter',
                            'target'=>'',
                            'level'=>2,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==companies'));
        $r++;


//Location

         // Get the value of the parent menu:
        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."menu where mainmenu = 'products'
                       AND fk_menu = (SELECT rowid FROM ".MAIN_DB_PREFIX."menu where mainmenu = 'products' and fk_menu =0 AND level=-1)
                       AND level = 0
                       AND titre='Stock'";
        $resql = $this->db->query($requete);
        $parentId = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $parentId = $res->rowid;
        }

        $requete = "SELECT max(position) + 1 as res1
                      FROM ".MAIN_DB_PREFIX."menu
                     WHERE mainmenu = 'products'
                       AND level = 1 ";
        $resql = $this->db->query($requete);
        $pos = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $pos = $res->res1;
        }

        // Left menu linked to top menu
            $menu[$r]=array('fk_menu'=>$parentId,
                            'type'=>'left',
                            'titre'=>'Stock de location',
                            'mainmenu'=>'products',
                            'url'=>'/Babel_GA/stock_GA.php?leftmenu=stock',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'rowid'=>$this->numero . $r,
                            'position'=>$pos,
                            'perms'=>'$user->rights->GA->stock->AfficheStock || $user->rights->GA->stock->AfficheStockCess || $user->rights->GA->stock->AfficheStockFourn || $user->rights->GA->stock->AfficheStockClient',
                            'target'=>'',
                            'level'=>1,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==stock'));
        $s=$r;$r++;
            $menu[$r]=array('fk_menu'=> $this->numero . $s ,
                            'type'=>'left',
                            'titre'=>'Stock de location global',
                            'mainmenu'=>'products',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/Babel_GA/stock_GA.php?leftmenu=stock',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>0,
                            'perms'=>'$user->rights->GA->stock->AfficheStock',
                            'target'=>'',
                            'level'=>2,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==stock'));
        $r++;

            $menu[$r]=array('fk_menu'=> $this->numero . $s ,
                            'type'=>'left',
                            'titre'=>'Stock de location par cess.',
                            'mainmenu'=>'products',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/Babel_GA/stock_GA.php?cessionnaire=1&leftmenu=stock',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GA->stock->AfficheStockCess',
                            'target'=>'',
                            'level'=>2,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==stock'));
        $r++;

            $menu[$r]=array('fk_menu'=> $this->numero . $s ,
                            'type'=>'left',
                            'titre'=>'Stock de location par fourn.',
                            'mainmenu'=>'products',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/Babel_GA/stock_GA.php?fournisseur=1&leftmenu=stock',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>2,
                            'perms'=>'$user->rights->GA->stock->AfficheStockFourn',
                            'target'=>'',
                            'level'=>2,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==stock'));
        $r++;
            $menu[$r]=array('fk_menu'=> $this->numero . $s ,
                            'type'=>'left',
                            'titre'=>'Stock de location par client',
                            'mainmenu'=>'products',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/Babel_GA/stock_GA.php?client=1&leftmenu=stock',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>3,
                            'perms'=>'$user->rights->GA->stock->AfficheStockClient',
                            'target'=>'',
                            'level'=>2,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==stock'));

//:rowid   menu_handler    module  type    mainmenu    fk_menu position    url target  titre   langs   level   leftmenu    perms   user    tms
//1711    auguria NULL    left    accountancy 5049    1   /compta/paiement/cheque/index.php?leftmenu=checks       MenuChequeDeposits  bills   0       $user->rights->facture->lire    2   2009-02-24 01:57:02
        $requete = "SELECT rowid
                      FROM ".MAIN_DB_PREFIX."menu
                     WHERE level = -1
                       AND type='top'
                       AND mainmenu = 'accountancy'";
        $resql = $this->db->query($requete);
        $pos = "";
        if ($resql)
        {
            $res = $this->db->fetch_object($resql);
            $gestMenuId = $res->rowid;
        }

        $r++;
            $menu[$r]=array('fk_menu'=> $gestMenuId ,
                            'type'=>'left',
                            'titre'=>'Cessionnaire',
                            'mainmenu'=>'accountancy',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/compta/index.php?leftmenu=cessionnaire',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>0,
                            'perms'=>'$user->rights->facture->creer',
                            'target'=>'',
                            'level'=>0,
                            'user'=>0,
                            "constraints"=>array(0=> '$conf->global->MAIN_MODULE_BABELGA && $user->rights->GA->cessionnaire->Afficher'));
        $s=$r;$r++;

        $r++;
        $menu[$r]=array('fk_menu'=> $this->numero . $s ,
                            'type'=>'left',
                            'titre'=>'Cr&eacute;er un cessionnaire',
                            'mainmenu'=>'accountancy',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/soc.php?action=create&createCessionnaire=1&leftmenu=cessionnaire',
                            'langs' => 'synopsisGene@Synopsis_Tools',
                            'position'=>1,
                            'perms'=>'$user->rights->GA->cessionnaire->Ajouter',
                            'target'=>'',
                            'level'=>2,
                            'user'=>0,
                            "constraints"=>array(0=>'$leftmenu==cessionnaire && $user->rights->GA->cessionnaire->Ajouter'));
        $r++;


            $menu[$r]=array('fk_menu'=> $this->numero .$s ,
                            'type'=>'left',
                            'titre'=>'NewBill',
                            'mainmenu'=>'accountancy',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/compta/addFacture.php?type=cess&leftmenu=cessionnaire',
                            'langs'=>'bills',
                            'position'=>2,
                            'perms'=>'$user->rights->facture->creer',
                            'target'=>'',
                            'level'=>0,
                            'user'=>0,
                            "constraints"=>array(0 => '$leftmenu==cessionnaire && $user->rights->GA->cessionnaire->Afficher'));

        $r++;

            $menu[$r]=array('fk_menu'=> $this->numero .$s ,
                            'type'=>'left',
                            'titre'=>'List',
                            'mainmenu'=>'accountancy',
                            'rowid'=>$this->numero . $r,
                            'url'=>'/Babel_GA/cessionnaire.php?leftmenu=cessionnaire',
                            'langs'=>'bills',
                            'position'=>3,
                            'perms'=>'$user->rights->facture->creer',
                            'target'=>'',
                            'level'=>0,
                            'user'=>0,
                            "constraints"=>array(0 => '$leftmenu==cessionnaire && $user->rights->GA->cessionnaire->Afficher'));


        foreach($menu as $key=>$val)
        {
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."menu
                                    (module, fk_menu, type, rowid, titre, mainmenu, url, langs, position, perms, target, user,level)
                             VALUES ('BabelGA', '".$val['fk_menu']."', '".$val['type']."', '".$val['rowid']."', '".$val['titre']."', '".$val['mainmenu']."', '".$val['url']."', '".$val['langs']."', '".$val['position']."', '".$val['perms']."', '".$val['target']."', '".$val['user']."',".$val['level'].")";
            $sql = $this->db->query($requete);
            if (!$sql) print $this->db->error;
        $lastId = $this->db->last_insert_id("".MAIN_DB_PREFIX."menu");
        if (is_array($val['constraints']))
        {
            foreach($val['constraints'] as $key=>$val)
            {
                $requete = 'SELECT * FROM ".MAIN_DB_PREFIX."menu_constraint WHERE action = "'.mysql_real_escape_string($val).'"';
                $sql=$this->db->query($requete);
                $constraintId = "";
                if (!$sql) {
                    //print '<div class="ui-state-error">Err Sql : La contrainte n\'a pas &eacute;t&eacute; appliqu&eacute;</div>';
                    $this->error="Error ".$this->db->lasterror();
                    //exit;
                }
                if ($this->db->num_rows($sql) > 0)
                {
                    $res=$this->db->fetch_object($sql);
                    {
                        $constraintId = $res->rowid;
                    }

                } else {
                    $requete = "SELECT max(rowid) + 1 as newId FROM ".MAIN_DB_PREFIX."menu_constraint";
                    $sql = $this->db->query($requete);
                    if (!$sql) {
                        //print '<div class="ui-state-error">Err Sql : La contrainte n\'a pas &eacute;t&eacute; appliqu&eacute;</div>';
                        $this->error="Error ".$this->db->lasterror();
                        //exit;
                    }
                    $res = $this->db->fetch_object($sql);
                    $newId = $res->newId;
                    $requete1 = "INSERT INTO ".MAIN_DB_PREFIX."menu_constraint (rowid,action) VALUES (".$newId.",'".$val."')";
                    $sql = $this->db->query($requete1);
                    if (!$sql) {
                        //print '<div class="ui-state-error">Err Sql : La contrainte n\'a pas &eacute;t&eacute; appliqu&eacute;</div>';
                        $this->error="Error ".$this->db->lasterror();
                    }
                    $constraintId = $newId;
                }
                $requete ="insert into ".MAIN_DB_PREFIX."menu_const (fk_menu, fk_constraint) VALUES (".$lastId.",".$constraintId.") ";
                $sql = $this->db->query($requete);
                if (!$sql) {
                    //print '<div class="ui-state-error">Err Sql : La contrainte n\'a pas &eacute;t&eacute; appliqu&eacute;</div>';
                    $this->error="Error ".$this->db->lasterror();
                    //exit;
                }

            }
        }

        }
    $sql = array();
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelee lors de la desactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu='companies' and module='BabelGA'";
    $this->db->query($requete);
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu='products' and module='BabelGA'";
    $this->db->query($requete);
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu='accountancy' and module='BabelGA'";
    $this->db->query($requete);
    $requete = "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE type='propalGA'";
    $this->db->query($requete);
    return $this->_remove($sql);
  }
}
?>