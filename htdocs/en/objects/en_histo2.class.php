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
        $btn[] = array(
                'label'       => 'Create view',
                'icon_before' => 'fas_cogs',
                'classes'     => array('btn', 'btn-default'),
                'attr'        => array(
                    'onclick' => $this->getJsActionOnclick('createTables', array('base'=>'jeedom_crouz'), array())
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
            if($cmdData->getData('id_cmd_parent') < 1){
                $fields[$cmdData->id] = array(
                   "title"      => $cmdData->getData('name'),
                   'field'     => 'value',
                   'calc'      => 'MAX',
                   'filters'    => array(
                       'cmd_id'     => $cmdData->id
                   ),
                   'visible'    => ($cmdData->getData('id_cmd_parent') > 0)? 0 : 1,


                );
            }
        }
//        echo '<pre>'; print_r($fields);die;
        return $fields;
    }
    
    
    public function actionCreateHisto($data, &$success){
        $success = 'MAj OK';
        $errors = $warnings = array();
        BimpCore::setMaxExecutionTime(2400);
        global $db;
        $warnings = array();
        $sql = $db->query("TRUNCATE TABLE `".MAIN_DB_PREFIX."en_historyArch2`;");
        $sql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."en_historyArch2 (cmd_id, date, value) (SELECT cmd_id, DATE(datetime), value FROM ".MAIN_DB_PREFIX."en_historyArch WHERE id IN (
SELECT MAX(a.id) FROM ".MAIN_DB_PREFIX."en_historyArch a LEFT JOIN ".MAIN_DB_PREFIX."en_cmd a___parent ON a___parent.id = a.cmd_id WHERE (a___parent.generic_type = 'CONSUMPTION') GROUP BY cmd_id, DATE(datetime)
));");

        $sql = $db->query("SELECT count(cmd_id) as nb, date FROM ".MAIN_DB_PREFIX."en_historyArch2 GROUP BY date HAVING nb != 23 ORDER BY date DESC;");
        while ($ln = $db->fetch_object($sql)){
            $sql2 = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."en_cmd WHERE id NOT IN (SELECT cmd_id FROM ".MAIN_DB_PREFIX."en_historyArch2 WHERE date = '".$ln->date."') AND generic_type = 'CONSUMPTION';");
            while($ln2 = $db->fetch_object($sql2)){
                $db->query("INSERT INTO ".MAIN_DB_PREFIX."en_historyArch2 (cmd_id, date, value)  (SELECT ".$ln2->id.", '".$ln->date."', value FROM ".MAIN_DB_PREFIX."en_historyArch2 WHERE cmd_id = ".$ln2->id." AND date < '".$ln->date."' ORDER BY date DESC LIMIT 1);");
            }
        }
        
        return array('errors'=> $errors, 'warnings' => $warnings);
    }
    
    
    public function actionCreateTables($data, &$success){
        $success = 'MAj OK';
        $errors = $warnings = array();
        $base = $data['base'];
        BimpCore::setMaxExecutionTime(2400);
        global $db;
        $tables = array(
            'historyArch',
            'cmd_infos',
            'cmd',
            'eqLogic',
            'historyArch2'
        );
        
        
        
        $sql = $db->query("
                CREATE TABLE IF NOT EXISTS ".$base.".historyArch2 (
  `cmd_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `value` double(24,8) DEFAULT 0.00000000,
  `id` int(28) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`cmd_id`,`date`)
);
");
        
        $sql = $db->query("
                CREATE TABLE IF NOT EXISTS ".$base.".cmd_infos (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cmd` int(11) NOT NULL,
  `id_cmd_parent` int(11) NOT NULL,
  `role` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
);
");
        
        foreach($tables as $table){
            $newName = MAIN_DB_PREFIX.'en_'.$table;
            $sql = $db->query("DROP VIEW IF EXISTS `".$newName."`;");
            if($table == 'historyArch'){
                $sql = $db->query("CREATE view ".$newName." as SELECT CONCAT(cmd_id, UNIX_TIMESTAMP(datetime)) as id, cmd_id, datetime, value FROM ".$base.".".$table.";");
            }
            else{
                $sql = $db->query("CREATE view ".$newName." as SELECT * FROM ".$base.".".$table.";");
            }
        }
        
        $sql = $db->query("UPDATE `".MAIN_DB_PREFIX."en_cmd` SET name = (SELECT name FROM `".MAIN_DB_PREFIX."en_eqLogic` obj WHERE obj.id = eqLogic_id) WHERE `name` LIKE 'Consommation';");

        
        return array('errors'=> $errors, 'warnings' => $warnings);
        
    }
}