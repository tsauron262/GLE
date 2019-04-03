<?php

if($_REQUEST['fc'] == 'adminInterface')
    require 'admin.php';
else
    require 'client.php';


?>