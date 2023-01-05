
# Table validation
ALTER TABLE `llx_validate_comm` CHANGE `object` `type_de_piece` INT(11) NOT NULL;

# Table demande de validation
ALTER TABLE `llx_demande_validate_comm` CHANGE `object` `type_de_piece` INT(11) NOT NULL;
ALTER TABLE `llx_demande_validate_comm` CHANGE `id_object` `id_piece` INT(11) NOT NULL;