<?php
$tabSql = array("UPDATE `llx_contrat` SET ref =  replace(ref, '-R2', '–C')",
        "UPDATE `llx_contrat` SET ref =  replace(ref, '-R', '–B')",
        "UPDATE `llx_propal` SET ref =  replace(ref, '-', '–')");


$text = "Maj de test";

?>