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

    $nonLu1 = BIMP_Task::$nbNonLu;







    $content2 = "";
    $tasks = $task->getList(array('id_user_owner' => 0,
        'status'        => array(
            'operator' => '<',
            'value'    => 4
    )));

    $i2 = 0;
    BIMP_Task::$nbNonLu = 0;
    BIMP_Task::$nbAlert = 0;
    foreach ($tasks as $taskData) {
        $task->fetch($taskData["id"]);
        if ($task->canView()) {
            $content2 .= $task->renderLight();
            $content2 .= "<br/>";
            $i2++;
        }
    }

    $class2 = array();
    if (BIMP_Task::$nbAlert > 0) {
        $class2[] = 'clignote';
        $alert = true;
    }

    if ($alert) {
        $contentT .= "<script>playAlert();</script>";
    } else {
        $contentT .= "<script>stopAlert();</script>";
    }

    if ($i2 > 0)
        $content2 .= $contentT;
    else
        $content .= $contentT;
    $nonLu2 = BIMP_Task::$nbNonLu;


    if ($i > 0)
        $bimp_fixe_tabs->addTab("mYtask", "<span class='" . implode(" ", $class) . "' >" . $i . " tâche(s) en attente" . ($nonLu1 > 0 ? " <span class='red'>" . $nonLu1 . " message" . ($nonLu1 > 1 ? 's' : '') . " non lu" . ($nonLu1 > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content);

    if ($i2 > 0)
        $bimp_fixe_tabs->addTab("taskAPersonne", "<span class='" . implode(" ", $class2) . "' >" . $i2 . " tâche" . ($i2 > 1 ? 's' : '') . " non attribuée" . ($i2 > 1 ? 's' : '') . ($nonLu2 > 0 ? " <span class='red'>" . $nonLu2 . " message" . ($nonLu2 > 1 ? 's' : '') . " non lu" . ($nonLu2 > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content2);
}
