<?php

if($_GET['a'] == 'df'){
    require '../bimpcommercial/duplicata.php';
}
elseif($_GET['a'] == 'p'){
    require '../bimpinterfaceclient/client.php';
}
else{
    echo 'hello world';
}



?>
