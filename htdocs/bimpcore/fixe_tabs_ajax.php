<?php

$random_id = (int) $_POST['randomId'];

if (!$random_id) {
    die(-1);
}

require_once __DIR__ . "/../main.inc.php";

$key = 'fixe_tabs_reload';

