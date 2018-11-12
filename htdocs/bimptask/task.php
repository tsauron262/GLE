<?php

function runBimpTask()
{
    global $bimp_fixe_tabs, $user;
    $content = "";

    $task = BimpObject::getInstance('bimptask', 'BIMP_Task');

    $tasks = $task->getList(array('id_user_owner' => (int) $user->id, 'status' => array(
                    'operator' => '<',
                    'value'    => 4
                )));
    $i=0;
    BIMP_Task::$nbNonLu = 0;
    foreach($tasks as $taskData){
        $task->fetch($taskData["id"]);
        if($task->canView()){
            $content .= $task->renderLight();
            $content .= "<br/>";
            $i++;
        }
    } 
    if ($i > 0)
        $bimp_fixe_tabs->addTab("mYtask", $i . " tache(s) en attente" .(BIMP_Task::$nbNonLu > 0 ? " <span class='red'>".BIMP_Task::$nbNonLu." message non lu.</span>" : ""), $content);
    
    
    

    $content = "";
    $tasks = $task->getList(array('id_user_owner' => 0, 
        'status' => array(
                    'operator' => '<',
                    'value'    => 4
                )));
    
    $i = 0;
    BIMP_Task::$nbNonLu = 0;
    foreach($tasks as $taskData){
        $task->fetch($taskData["id"]);
        if ($task->canView()) {
            $content .= $task->renderLight();
            $content .= "<br/>";
            $i++;
        }
    }
    if ($i > 0)
        $bimp_fixe_tabs->addTab("taskAPersonne", $i . " tache(s) non attribué" .(BIMP_Task::$nbNonLu > 0 ? " <span class='red'>".BIMP_Task::$nbNonLu." message non lu.</span>" : ""), $content);
}
