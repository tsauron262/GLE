<?php
$tabSql = array(
    "INSERT INTO `llx_synopsischrono_key` (`id`, `nom`, `description`, `model_refid`, `type_valeur`, `type_subvaleur`, `extraCss`, `inDetList`, `rang`) VALUES
(1098, 'SAV PRO', '', 105, 4, NULL, '', 1, 0);",
    "INSERT INTO `llx_synopsischrono_key` (`id`, `nom`, `description`, `model_refid`, `type_valeur`, `type_subvaleur`, `extraCss`, `inDetList`, `rang`) VALUES
(1099, 'signature', '', 105, 9, NULL, 'hide', 0, 0);",
    "ALTER TABLE `llx_synopsischrono_chrono_105` ADD `SAV_PRO` INT NOT NULL DEFAULT 0;",
    "ALTER TABLE `llx_synopsischrono_chrono_105` ADD `signature` TEXT NOT NULL DEFAULT '';",
    "INSERT INTO `llx_document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES (NULL, 'pcpro', '1', 'synopsischrono_105', 'Prise en Charge Pro', NULL);"
    );

$text = "SAV ajout sav_pro reinit module apple!!!!!";
?>
