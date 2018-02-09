<?php


include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';


//$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/styles.css');
//$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/ajax.js');

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id = GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int');
$ref = GETPOST('ref');
$action = GETPOST('action','aZ09');

$object = new CommandeFournisseur($db);
$object->fetch($id, $ref);

/*
 * View
 */
$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('',$langs->trans("Order"),$help_url);

$form = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
    if ($result >= 0)
    {
        $object->fetch_thirdparty();

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $head = ordersupplier_prepare_head($object);

        $title=$langs->trans("SupplierOrder");
        dol_fiche_head($head, 'bimpordersupplier', $title, -1, 'order');

		// Supplier order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref='<div class="refidno">';
		// Ref supplier
		$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
		// Project
		if (! empty($conf->projet->enabled))
		{
		    $langs->load("projects");
		    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		    if ($user->rights->fournisseur->commande->creer)
		    {
		        if ($action != 'classify')
		            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
		            $morehtmlref.=' : ';
		        	if ($action == 'classify') {
		                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
		                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		                $morehtmlref.='<input type="hidden" name="action" value="classin">';
		                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
		                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		                $morehtmlref.='</form>';
		            } else {
		                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		            }
		    } else {
		        if (! empty($object->fk_project)) {
		            $proj = new Project($db);
		            $proj->fetch($object->fk_project);
		            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
		            $morehtmlref.=$proj->ref;
		            $morehtmlref.='</a>';
		        } else {
		            $morehtmlref.='';
		        }
		    }
		}
		$morehtmlref.='</div>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';


		$cssclass="titlefield";
        print '</div>';

        dol_fiche_end();
    }
    else
    {
        /* Order not found */
        $langs->load("errors");
        print $langs->trans("ErrorRecordNotFound");
    }
}

/**
 * Start Fiche
 */



/**
 * End Fiche
 */

llxFooter();

$db->close();
