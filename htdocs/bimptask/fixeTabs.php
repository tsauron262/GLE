<?php

require_once DOL_DOCUMENT_ROOT . "/bimpcore/classes/FixeTabs_module.class.php";

class FixeTabs_bimptask extends FixeTabs_module {

    function init() {
        $content = "";
        $alert = false;
        $maxTaskView = 25;

        $task = BimpObject::getInstance('bimptask', 'BIMP_Task');

        $tasks = $task->getList(array('id_user_owner' => (int) $this->user->id, 'status' => array(
                'operator' => '<',
                'value' => 4
        )), null, null,'date_update');
        $i = $j = 0;
        BIMP_Task::$nbNonLu = 0;
        BIMP_Task::$nbAlert = 0;
        foreach ($tasks as $taskData) {
            $task->fetch($taskData["id"]);
            if ($task->can("view")) {
                if ($j < $maxTaskView) {
                    $content .= $task->renderLight();
                    $content .= "<br/>";
                    $i++;
                }
                $j++;
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
            'status' => array(
                'operator' => '<',
                'value' => 4
        )), null, null,'date_update');

        $i2 = $j2 = 0;
        BIMP_Task::$nbNonLu = 0;
        BIMP_Task::$nbAlert = 0;
        foreach ($tasks as $taskData) {
            $task->fetch($taskData["id"]);
            if ($task->can("view")) {
                if ($j2 < $maxTaskView) {
                    $content2 .= $task->renderLight();
                    $content2 .= "<br/>";
                    $i2++;
                }
                $j2++;
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
            $this->bimp_fixe_tabs->addTab("mYtask", "<span class='" . implode(" ", $class) . "' >" . $i . (($j != $i) ? " / " . $j : "") . " tâche(s) en attente" . ($nonLu1 > 0 ? " <span class='red'>" . $nonLu1 . " message" . ($nonLu1 > 1 ? 's' : '') . " non lu" . ($nonLu1 > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content);

        if ($i2 > 0)
            $this->bimp_fixe_tabs->addTab("taskAPersonne", "<span class='" . implode(" ", $class2) . "' >" . $i2 . (($j2 != $i2) ? " / " . $j2 : "") . " tâche" . ($i2 > 1 ? 's' : '') . " non attribuée" . ($i2 > 1 ? 's' : '') . ($nonLu2 > 0 ? " <span class='red'>" . $nonLu2 . " message" . ($nonLu2 > 1 ? 's' : '') . " non lu" . ($nonLu2 > 1 ? 's' : '') . ".</span>" : "") . "</span>", $content2);
    }

    function can($right){
        global $conf;
        $retour = false;
        if($right == "view"){
            if (isset($conf->global->MAIN_MODULE_BIMPTASK)) {
                $task = BimpObject::getInstance("bimptask", "BIMP_Task");
                if ($task->can("view"))
                    $retour = true;
            }
        }
        return $retour;
    }
    
    function displayHead() {
        $html = '';
        $html .= '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/bimptask/views/css/task.css"/>';
        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimptask/views/js/task.js"></script>';
        return $html;
    }
}
