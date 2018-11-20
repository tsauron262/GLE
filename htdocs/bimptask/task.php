<?php

function runBimpTask()
{
    global $bimp_fixe_tabs, $user;
    $content = "";
    $alert = false;

    $task = BimpObject::getInstance('bimptask', 'BIMP_Task');

    $tasks = $task->getList(array('id_user_owner' => (int) $user->id, 'status'        => array(
            'operator' => '<',
            'value'    => 4
    )));
    $i = 0;
    BIMP_Task::$nbNonLu = 0;
    BIMP_Task::$nbAlert = 0;
    foreach ($tasks as $taskData) {
        $task->fetch($taskData["id"]);
        if ($task->canView()) {
            $content .= $task->renderLight();
            $content .= "<br/>";
            $i++;
        }
    }
    $class = array();
    if (BIMP_Task::$nbAlert > 0) {
        $class[] = 'clignote';
        $alert = true;
    }




    if ($i > 0)
        $bimp_fixe_tabs->addTab("mYtask", "<span class='" . implode(" ", $class) . "' >" . $i . " tâche(s) en attente" . (BIMP_Task::$nbNonLu > 0 ? " <span class='red'>" . BIMP_Task::$nbNonLu . " message" . (BIMP_Task::$nbNonLu > 1 ? 's' : '') . " non lu" . (BIMP_Task::$nbNonLu > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content);




    $content = "";
    $tasks = $task->getList(array('id_user_owner' => 0,
        'status'        => array(
            'operator' => '<',
            'value'    => 4
    )));

    $i = 0;
    BIMP_Task::$nbNonLu = 0;
    BIMP_Task::$nbAlert = 0;
    foreach ($tasks as $taskData) {
        $task->fetch($taskData["id"]);
        if ($task->canView()) {
            $content .= $task->renderLight();
            $content .= "<br/>";
            $i++;
        }
    }

    $class = array();
    if (BIMP_Task::$nbAlert > 0) {
        $class[] = 'clignote';
        $alert = true;
    }

    if ($alert) {
        $content .= "<script>playAlert();</script>";
    } else {
        $content .= "<script>stopAlert();</script>";
    }


    if ($i > 0)
        $bimp_fixe_tabs->addTab("taskAPersonne", "<span class='" . implode(" ", $class) . "' >" . $i . " tâche(s) non attribuée(s)" . (BIMP_Task::$nbNonLu > 0 ? " <span class='red'>" . BIMP_Task::$nbNonLu . " message" . (BIMP_Task::$nbNonLu > 1 ? 's' : '') . " non lu" . (BIMP_Task::$nbNonLu > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content);
}
