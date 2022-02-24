<?php

require("../main.inc.php");

llxHeader();


require_once DOL_DOCUMENT_ROOT.'/bimpapple/classes/GSX_v2.php';
echo GSX_v2::phantomAuth(GSX_v2::$default_ids['apple_id'], GSX_v2::$default_ids['apple_pword']);

die('fin');