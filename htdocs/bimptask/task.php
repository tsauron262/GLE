<?php

function runBimpTask()
{
    global $bimp_fixe_tabs, $user;
    $content = "";
    $alert = false;

    $task = BimpObject::getInstance('bimptask', 'BIMP_Task');

    $tasks = $task->getList(array('id_user_owner' => (int) $user->id, 'status' => array(
                    'operator' => '<',
                    'value'    => 4
                )));
    $i=0;
    BIMP_Task::$nbNonLu = 0;
    BIMP_Task::$nbAlert = 0;
    foreach($tasks as $taskData){
        $task->fetch($taskData["id"]);
        if($task->canView()){
            $content .= $task->renderLight();
            $content .= "<br/>";
            $i++;
        }
    } 
    $class = array();
    if(BIMP_Task::$nbAlert > 0){
        $class[] = 'clignote';
        $alert = true;
    }
    
    

    
    
    
    

    $content2 = "";
    $tasks = $task->getList(array('id_user_owner' => 0, 
        'status' => array(
                    'operator' => '<',
                    'value'    => 4
                )));
    
    $i2 = 0;
    BIMP_Task::$nbNonLu = 0;
    BIMP_Task::$nbAlert = 0;
    foreach($tasks as $taskData){
        $task->fetch($taskData["id"]);
        if ($task->canView()) {
            $content2 .= $task->renderLight();
            $content2 .= "<br/>";
            $i2++;
        }
    }
    
    $class = array();
    if(BIMP_Task::$nbAlert > 0){
        $class[] = 'clignote';
        $alert = true;
    }
    
    if($alert){
        $contentT .= "<script>playAlert();</script>";
    }
    else{
        $contentT .= "<script>stopAlert();</script>";
    }
    
    if($i2>0)
        $content2 .= $contentT;
    else
        $content .= $contentT;
    
    
    if ($i > 0)
        $bimp_fixe_tabs->addTab("mYtask", "<span class='".implode(" ", $class)."' >".$i . " tache(s) en attente" .(BIMP_Task::$nbNonLu > 0 ? " <span class='red'>".BIMP_Task::$nbNonLu." message non lu.</span>" : "")."</span>", $content);
    
    if ($i2 > 0)
        $bimp_fixe_tabs->addTab("taskAPersonne", "<span class='".implode(" ", $class)."' >".$i2 . " tache(s) non attribué" .(BIMP_Task::$nbNonLu > 0 ? " <span class='red'>".BIMP_Task::$nbNonLu." message non lu.</span>" : "")."</span>", $content2);
}
