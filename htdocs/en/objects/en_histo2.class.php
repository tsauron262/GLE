<?php

class en_histo2 extends BimpObject
{
    public function getHeaderBtnList(){
        $btn = array();
        $btn[] = array(
                'label'       => 'Script MAJ',
                'icon_before' => 'fas_cogs',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $this->getJsActionOnclick('createHisto', array(), array())
                ) 
            );
        return $btn;
    }

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
    
    
    public function actionCreateHisto(){
        
        BimpCore::setMaxExecutionTime(2400);
        global $db;
        $warnings = array();
        $sql = $db->query("
                CREATE TABLE `".MAIN_DB_PREFIX."en_historyArch2` (
  `cmd_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `value` double(24,8) DEFAULT 0.00000000,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`cmd_id`,`date`),
  UNIQUE KEY `unique` (`cmd_id`,`date`),
  KEY `id` (`id`)
);
");
        $sql = $db->query("TRUNCATE TABLE `".MAIN_DB_PREFIX."en_historyArch2`;");
        $sql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."en_historyArch2 (SELECT cmd_id, DATE(datetime), value, id FROM ".MAIN_DB_PREFIX."en_historyArch WHERE id IN (
SELECT MAX(a.id) FROM ".MAIN_DB_PREFIX."en_historyArch a LEFT JOIN ".MAIN_DB_PREFIX."en_cmd a___parent ON a___parent.id = a.cmd_id WHERE (a___parent.generic_type = 'CONSUMPTION') GROUP BY cmd_id, DATE(datetime)
));");

        $sql = $db->query("SELECT count(cmd_id) as nb, date FROM ".MAIN_DB_PREFIX."en_historyArch2 GROUP BY date HAVING nb != 23 ORDER BY date DESC;");
        while ($ln = $db->fetch_object($sql)){
            $sql2 = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."en_cmd WHERE id NOT IN (SELECT cmd_id FROM ".MAIN_DB_PREFIX."en_historyArch2 WHERE date = '".$ln->date."') AND generic_type = 'CONSUMPTION';");
            while($ln2 = $db->fetch_object($sql2)){
                $db->query("INSERT INTO ".MAIN_DB_PREFIX."en_historyArch2 (cmd_id, date, value)  (SELECT ".$ln2->id.", '".$ln->date."', value FROM ".MAIN_DB_PREFIX."en_historyArch2 WHERE cmd_id = ".$ln2->id." AND date < '".$ln->date."' ORDER BY date DESC LIMIT 1);");
            }
        }
    }
    
    
    public function actionCreateTables($data){
        $base = $data['base'];
        BimpCore::setMaxExecutionTime(2400);
        global $db;
        $tables = array(
            'historyArch',
            
        );
        
        foreach($tables as $table){
            $newName = MAIN_DB_PREFIX.'en_'.$table;
            $sql = $db->query("DELETE VIEW `".$newName."`;");
            if($table != 'historyArch'){
                $sql = $db->query("CREATE view ".$newName." as SELECT (datetime*10+cmd_id) as id, cmd_id, datetime, value FROM ".$base.".".$table.";");
            }
            else{
                $sql = $db->query("CREATE view ".$newName." as SELECT * FROM ".$base.".".$table.";");
            }
        }
        
    }
}