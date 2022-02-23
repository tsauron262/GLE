<?php

require("../main.inc.php");

llxHeader();


require_once DOL_DOCUMENT_ROOT.'/bimpapple/classes/GSX_v2.php';
echo GSX_v2::phantomAuth();

die('fin');