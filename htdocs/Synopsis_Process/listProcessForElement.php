<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 23 fevr. 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : listProcessForElement.php
 * GLE-1.2
 */
require_once("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
$id = $_REQUEST['id'];
$type = $_REQUEST['type'];
//        require_once('Var_Dump.php');
//        var_dump::display($user->rights);
// Securite acces client
$socid = 0;
if ($user->societe_id > 0) {
    $socid = $user->societe_id;
}
switch ($type) {
    case 'Commande': {
            if (!$user->rights->commande->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");
                $obj = new Commande($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Propal': {
            if (!$user->rights->commande->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                $obj = new Propal($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Facture': {
            if (!$user->rights->facture->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
                $obj = new Facture($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Expedition': {
            if (!$user->rights->expedition->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php");
                $obj = new Expedition($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'ActionComm': {
            if (!$user->rights->expedition->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
                $obj = new ActionComm($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Affaire': {
            if (!$user->rights->affaire->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Affaire/Affaire.class.php");
                $obj = new Affaire($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'FactureFournisseur': {
            //var_dump($user->rights->fournisseur->facture->lire);
            if (!$user->rights->fournisseur->facture->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php");
                $obj = new FactureFournisseur($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'CommandeFournisseur': {
            //var_dump($user->rights->fournisseur->facture->lire);
            if (!$user->rights->fournisseur->commande->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php");
                $obj = new CommandeFournisseur($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Contrat': {
            if (!$user->rights->contrat->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
                $obj = new Contrat($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Campagne': {
            if (!$user->rights->prospectbabe->Prospection->Affiche)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/BabelProspect/Campagne.class.php");
                $obj = new Campagne($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Chrono': {
            if (!$user->rights->synopsischrono->read)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
                $obj = new Chrono($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Societe': {
            if (!$user->rights->societe->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                if ($user->societe_id != $id) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Contact': {
            if (!$user->rights->societe->client->voir)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
                $obj = new Contact($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;

    case 'synopsisdemandeinterv': {
            if (!$user->rights->synopsisdemandeinterv->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php");
                $obj = new Synopsisdemandeinterv($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Fichinter': {
            if (!$user->rights->ficheinter->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php");
                $obj = new Fichinter($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Livraison': {
            if (!$user->rights->expedition->livraison->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/livraison/class/livraison.class.php");
                $obj = new Livraison($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Paiement': {
            if (!$user->rights->facture->paiement)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/Paiement.class.php");
                $obj = new Paiement($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'PaiementFourn': {
            if (!$user->rights->fournisseur->facture->creer)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/fourn/paiement/paiementfourn.class.php");
                $obj = new PaiementFourn($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Product': {
            if (!$user->rights->produit->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
                $obj = new Product($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Project': {
            if (!$user->rights->projet->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                $obj = new Project($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
    case 'Project': {
            if (!$user->rights->projet->lire)
                accessforbidden();
            if ($user->societe_id > 0 && $id > 0) {
                require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                $obj = new Project($db);
                $obj->fetch((int) $id);
                if ($user->societe_id != $obj->socid) {
                    accessforbidden();
                }
            }
        }
        break;
}
$obj = false;

if ($id > 0 && $type . "x" != "x") {
    printHead($type, $id);
    $arrProcess = getIdleProcess($db, $type, $id);

    if (count($arrProcess) > 0) {
        $html = "";
        $html .= "<table cellpadding=15 width=100%>";
        $html .= "<tr><th class='ui-widget-header ui-state-default'>Nom";
        $html .= "    <th class='ui-widget-header ui-state-default'>Cr&eacute;e le";
        $html .= "    <th class='ui-widget-header ui-state-default'>Der. modif. le";
        $html .= "    <th class='ui-widget-header ui-state-default'>Statut";
        $iter = 0;
        foreach ($arrProcess as $key => $tabTemp) {
            foreach ($tabTemp as $val) {
                $tmp = new Process($db);
                $tmp->fetch($val['process']);
                $tmp->getGlobalRights();
                $tmp = "process" . $val['process'];
                if (!($user->rights->process->lire || $user->rights->process_user->$tmp->voir))
                    continue;
                $iter++;
                $html .= "<tr><td class='ui-widget-content'>";
                $pDet = new processDet($db);
                $pDet->fetch($val['processdet']);
                $html .= $pDet->getNomUrl(1);
                $html .= "    <td class='ui-widget-content'>";
                $html .= date('d/m/Y h:i', strtotime($pDet->date_create));
                $html .= "    <td class='ui-widget-content'>";
                $html .= date('d/m/Y h:i', strtotime($pDet->date_modify));
                $html .= "    <td class='ui-widget-content'>";
                $html .= $pDet->getLibStatut(4);
            }
        }
        $html .= "</table>";
        if ($iter == 0) {
            print "<div class='ui-state-highlight'>Pas de process visible dans cette commande</div>";
        } else {
            print $html;
        }
    } else {
        print "<div class='ui-state-highlight'>Pas de process dans cette " . $type . "</div>";
    }
} else {
    accessforbidden();
}






llxFooter('$Date: 2008/09/10 22:23:38 $ - $Revision: 1.60.2.2 $');
?>
