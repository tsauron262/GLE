<?php

class indexController extends BimpController
{

    protected function ajaxProcessSetAsViewed()
    {
        $errors = array();
        $nb_set_as_viewed = 0;

        $filters = array(
            'obj_type' => BimpTools::getPostFieldValue('obj_type'),
            'obj_module' => BimpTools::getPostFieldValue('obj_module'),
            'obj_name' => BimpTools::getPostFieldValue('obj_name'),
            'id_obj' => BimpTools::getPostFieldValue('id_obj')
        );

        $notes = BimpCache::getBimpObjectObjects('bimpcore', 'BimpNote', $filters);

        foreach($notes as $n)
            $nb_set_as_viewed += $n->i_view();
                
        $ret = array(
            'errors'        => $errors,
            'nb_set_as_viewed' => $nb_set_as_viewed,
            'request_id'    => BimpTools::getValue('request_id', 0)
        );
        
        if(1 < $nb_set_as_viewed)
            $ret['success'] = $nb_set_as_viewed . " messages marqués comm lu.";
        
        return $ret;
    }
    
}