<?php
require_once '../main.inc.php';
?>

<html>
   <head></head>
   <body><span id="login_ok"></span></body>
   <script>
       <?php
        if(class_exists('Session')){
            echo "localStorage.setItem('bimp_hash', '".Session::getHash()."');";
        }
       ?>
   
   </script>
</html>