<?php

class commandesFournController extends BimpController
{

    public function renderProdsTab()
    {
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeFournLine');
//        Bimp_CommandeFournLine::checkAllQties();
        
        //        $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);

        $line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

        $bc_list = new BC_ListTable($line, 'general', 1, null, 'Liste des produits en commande', 'fas_bars');
        $bc_list->addJoin('commande_fournisseur', 'a.id_obj = parent.rowid', 'parent');
        $bc_list->addFieldFilterValue('parent.fk_statut', array(
            'operator' => '>=',
            'value'    => 3
        ));

//        if ($id_entrepot) {
//            $bc_list->addJoin('commande_extrafields', 'a.id_obj = cef.fk_object', 'cef');
//            $bc_list->addFieldFilterValue('cef.entrepot', $id_entrepot);
//        }

        return $bc_list->renderHtml();
    }
}
