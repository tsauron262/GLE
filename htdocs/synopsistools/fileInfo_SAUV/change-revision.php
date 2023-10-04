<?php
$tabSql = array("UPDATE `" . MAIN_DB_PREFIX . "contrat` SET ref =  replace(ref, '-R2', '–C')",
        "UPDATE `" . MAIN_DB_PREFIX . "contrat` SET ref =  replace(ref, '-R', '–B')",
        "UPDATE `" . MAIN_DB_PREFIX . "propal` SET ref =  replace(ref, '-', '–')");


$text = "Maj de test";

?>