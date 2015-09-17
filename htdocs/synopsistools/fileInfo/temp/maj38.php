<?php

$tabsql = array();
$tabSql[] = 'UPDATE  `bimp_150526`.`llx_Synopsis_Process_form_requete` SET  `requeteValue` =  \'SELECT c.rowid, c.fk_soc, s.nom, 
CONCAT(
    CONCAT(c.ref, 
        IF(us.lastname != "", 
            CONCAT(" (", 
                CONCAT(us.lastname, 
                    CONCAT(" ",
                        CONCAT(us.firstname, ")"
                        )
                    )
                )
            )
        , "")
     )
, 
CONCAT(
IF(c.note_private != "", CONCAT(" | ", c.note_private), "")
,

IF(c.note_public != "", CONCAT(" | ", c.note_public), "")
) ) as ref
FROM llx_societe s, llx_contrat c LEFT JOIN `llx_element_contact` ec ON `element_id` = c.rowid AND `fk_c_type_contact` =200 LEFT JOIN llx_user us ON us.rowid = ec.fk_socpeople WHERE c.fk_soc = s.rowid AND [[indexField]]\' WHERE `llx_Synopsis_Process_form_requete`.`id` =1007;';


$tabSql[] = 'UPDATE `llx_Synopsis_Process_lien` SET `sqlFiltreSoc`= replace(`sqlFiltreSoc`, "fk_societe", "fk_soc") WHERE 1';