<?php

function runBimpTask() {
    global $bimp_fixe_tabs, $user;
    $content = "";

    $task = BimpObject::getInstance('bimptask', 'BIMP_Task');

    $tasks = $task->getList(array('id_user_owner' => (int) $user->id, 'status' => array(
                    'operator' => '<',
                    'value'    => 4
                )));
    
    foreach($tasks as $taskData){
        $task->fetch($taskData["id"]);
        $content .= $task->renderLight();
        $content .= "<br/>";
    }

    $nbT = count($tasks);  
    if ($nbT > 0)
        $bimp_fixe_tabs->addTab("mYtask", $nbT . " tache(s) en attente", $content);
    
    
    

    
    $content = "";
    $tasks = $task->getList(array('id_user_owner' => 0, 
        'status' => array(
                    'operator' => '<',
                    'value'    => 4
                )));
    
    foreach($tasks as $taskData){
        $task->fetch($taskData["id"]);
        $content .= $task->renderLight();
        $content .= "<br/>";
    }

    $nbT = count($tasks);  
    if ($nbT > 0)
        $bimp_fixe_tabs->addTab("taskAPersonne", $nbT . " tache(s) non attribué", $content);
}
