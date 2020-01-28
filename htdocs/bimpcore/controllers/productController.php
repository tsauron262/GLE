<?php


class productController extends BimpController
{
    protected function ajaxProcessDisplayDetails()
    {
        $errors = array();
        $html = '';
        $id_product = (int) BimpTools::getValue('id');
        $type_of_object = BimpTools::getValue('typeofobject');
        
        // Instance of object
        if($type_of_object == 'BContract_contrat')
            $object = BimpObject::getInstance('bimpcontract', $type_of_object);
        else
            $object = BimpObject::getInstance('bimpcommercial', $type_of_object);

        // Instance of object line
        if($type_of_object == 'BContract_contrat')
            $object_child = BimpObject::getInstance('bimpcontract', $object->config->params['objects']['lines']['instance']);
        else
            $object_child = BimpObject::getInstance('bimpcommercial', $object->config->params['objects']['lines']['instance']);

        $list = new BC_ListTable($object);
        
        switch ($type_of_object) {
            case 'Bimp_Propal': // customer and supplier
                $condition = 'a.rowid = b.fk_propal';
                break;
            case 'Bimp_PropalFourn':
//              $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposaldet as pd";
//		$sql.= ", ".MAIN_DB_PREFIX."supplier_proposal as p";
                die(json_encode(array(
                    'errors'     => $errors,
                    'html'       => 'Non implémenté',
                    'request_id' => BimpTools::getValue('request_id', 0)
                )));
            case 'Bimp_Commande':
                $condition = 'a.rowid = b.fk_commande';
                break;
            case 'Bimp_CommandeFourn':
                $condition = 'a.rowid = b.fk_commande';
                break;
            case 'Bimp_Facture':
                $condition = 'a.rowid = b.fk_facture';
                break;
            case 'Bimp_FactureFourn':
                $condition = 'a.rowid = b.fk_facture_fourn';
                break;
            case 'BContract_contrat':
                $condition = 'a.rowid = b.fk_contrat';
                break;
        }
        $list->addJoin($object->dol_object->table_element_line, $condition, 'b');
        $list->addFieldFilterValue('b.fk_product', $id_product);
        $list->params['n'] = 10000;
        $html .= $list->renderHtml();

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }
}