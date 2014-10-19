<?php
class CronSynopsis{
    public function __construct($db){
        $this->db = $db;
}

    public function netoyage(){
        $this->db->query("DELETE FROM ".MAIN_DB_PREFIX."element_element WHERE  `sourcetype` LIKE  'resa'");
    }
    
    
    
    
    
    
    public function testChrono(){
        
    }
}