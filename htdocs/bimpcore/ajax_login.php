<?php
require_once '../main.inc.php';
?>

<html>
   <head></head>
   <body><span id="login_ok"></span></body>
   <script>
       <?php
        if(class_exists('Session')){
            echo "var bimp_storage = new BimpStorage();";
            echo "bimp_storage.set('bimp_hash', '".Session::getHash()."');";
        }
       ?>
   
   </script>
</html>