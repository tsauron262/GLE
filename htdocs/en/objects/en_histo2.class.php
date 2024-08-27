<?php

class en_histo2 extends BimpObject
{


    //graph
    public function getFieldsGraphRepProd(){
        $fields = array();
        $cmds = BimpCache::getBimpObjectObjects('en', 'en_cmd', array(
            'generic_type'      => 'CONSUMPTION',
            'role'              => array('operator' => '>', 'value' => '1')
        ), 'role', $sortorder = 'desc');
        foreach($cmds as $cmdData){
            $fields[$cmdData->id] = array(
               "title"      => $cmdData->getData('name'),
               'field'     => 'value',
               'calc'      => 'MAX',
               'filters'    => array(
                   'cmd_id'     => $cmdData->id
               )
                
                
            );
        }
        return $fields;
    }
    
    public function getFieldsGraphConso(){
        $fields = array();
        $cmds = BimpCache::getBimpObjectObjects('en', 'en_cmd', array(
            'generic_type'      => 'CONSUMPTION',
            'role'              => array('operator' => '>', 'value' => '0')
        ), 'role', $sortorder = 'desc');

        $i=0;
        foreach($cmds as $cmdData){
            $fields[$cmdData->id] = array(
               "title"      => $cmdData->getData('name'),
               'field'     => 'value',
               'calc'      => 'MAX',
               'filters'    => array(
                   'cmd_id'     => $cmdData->id
               ),
               'visible'    => ($cmdData->getData('role') > 1 || $cmdData->getData('id_cmd_parent') > 0)? 0 : 1,
               'type'       => (($cmdData->getData('role') == 1))? 'stackedColumn' : 'stackedArea',
               'reverse'    => ($cmdData->getData('role') == 3)? 1 : 0,
                
                
            );
        }
        
        return $fields;
    }
    
    public function getFieldsGraphRepConso(){
        $fields = array();
        $cmds = BimpCache::getBimpObjectObjects('en', 'en_cmd', array(
            'generic_type'      => 'CONSUMPTION',
            'role'              => '1'
        ), 'role', $sortorder = 'desc');

        foreach($cmds as $cmdData){
            $fields[$cmdData->id] = array(
               "title"      => $cmdData->getData('name'),
               'field'     => 'value',
               'calc'      => 'MAX',
               'filters'    => array(
                   'cmd_id'     => $cmdData->id
               )
                
                
            );
        }
//        echo '<pre>'; print_r($fields);die;
        return $fields;
    }
}