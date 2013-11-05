<?php

require_once('../../main.inc.php');


if($_REQUEST['action'] == "rm"){
    delElementElement($_REQUEST['sourcetype'], $_REQUEST['targettype'], $_REQUEST['idsource'], $_REQUEST['idtarget'], $_REQUEST['ordre']);
    echo "ok";
}
if($_REQUEST['action'] == "add"){
    addElementElement($_REQUEST['sourcetype'], $_REQUEST['targettype'], $_REQUEST['idsource'], $_REQUEST['idtarget'], $_REQUEST['ordre']);
    echo "ok";
}