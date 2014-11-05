<?php

$tabSql = array("ALTER TABLE  `llx_synopsischrono` ADD  `revisionNext` INT NOT NULL AFTER  `revision`");

$tabSql = array("UPDATE llx_synosischrono c1 set revisionNext = (SELECT id FROM llx_synopsischrono c2 WHERE c1.orig_ref = c2.orig_ref AND c2.revision = 3) AND revision = 2");


$text = "Ajout revisionNext au chrono";