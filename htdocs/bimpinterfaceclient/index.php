<?php

if($_REQUEST['context'] == 'public')
    require 'client.php';
else
    require 'admin.php';


?>