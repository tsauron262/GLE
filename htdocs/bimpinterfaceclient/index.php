<?php

if(!isset($_REQUEST['context']) || isset($_REQUEST['context']) == 'public')
    require 'client.php';
else
    require 'admin.php';


?>
