<?php

require_once('../../main.inc.php');


if($_REQUEST['action'] == "rm"){
    if(delElementElement($_REQUEST['sourcetype'], $_REQUEST['targettype'], $_REQUEST['idsource'], $_REQUEST['idtarget'], $_REQUEST['ordre']))
    echo "ok";
}
if($_REQUEST['action'] == "add"){
    if(addElementElement($_REQUEST['sourcetype'], $_REQUEST['targettype'], $_REQUEST['idsource'], $_REQUEST['idtarget'], $_REQUEST['ordre']))
    echo "ok";
}
if($_REQUEST['action'] == "set"){
    if(setElementElement($_REQUEST['sourcetype'], $_REQUEST['targettype'], $_REQUEST['idsource'], $_REQUEST['idtarget'], $_REQUEST['ordre']))
    echo "ok";
}