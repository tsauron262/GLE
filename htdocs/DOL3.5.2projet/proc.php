<?php

require_once('./../main.inc.php');

class toto
{
    public function toto($db){
        $this->db = $db;
    }
    public $storeArr = array();
    public function recursive_function($parent='') {
        $i=1;
        $level = 0;
        $requete = "SELECT rowid,
                           title,duration_effective,
                           ifnull(fk_task_parent,0) as fk_task_parent
                      FROM ".MAIN_DB_PREFIX."projet_task
                     WHERE priority = 3
                       AND fk_task_parent";
        $requete .= " ='$parent' ";
        $res=$this->db->query($requete) or die ("Fail recursive");
        while ($row=$this->db->fetch_array($res)) {
            $parentID = $row['fk_task_parent'] ;
            if (!$this->storeArr[$parentID]['parent']){ $level ++ ;  } else { $level =  $this->storeArr[$parentID]['level'] + 1;  }

            $this->storeArr[$row['rowid']] = array('title' => $row['title'] , "level" => $level, "parent" => $row['fk_task_parent'] );
//var_dump($this->storeArr);
            echo "rowid: ".$row['rowid']." title: ".strtoupper($row['title'])." id parent: ".$row['fk_task_parent'].' level: '.$this->storeArr[$row['rowid']]['level']."<br>";
            $this->recursive_function($row['rowid']);
            ++$i;
        }
    }
}


// ****************
// RUN THE FUNCTION
// ****************
$parent="0";

$toto = new toto($db);
$toto->storeArr[0]['parent']=0;
$toto->storeArr[0]['level']=1;
print_r ($toto->recursive_function($parent));






//        $requete1 = "SELECT avg(".MAIN_DB_PREFIX."projet_task.progress) as moyenne FROM ".MAIN_DB_PREFIX."projet_task WHERE fk_task_parent =  ".$row['fk_task_parent'] ;
//        print $requete1 . "<br>";
//        $sql1 = $db->query($requete1);
//        $res1 = $db->fetch_object($sql1);


?>