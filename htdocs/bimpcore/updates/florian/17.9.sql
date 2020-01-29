CREATE INDEX uk_fk_remise_except ON llx_commandedet (fk_remise_except,fk_commande);
CREATE INDEX uk_fk_remise_except ON llx_propaldet (fk_remise_except,fk_propal);
CREATE INDEX entity_3 ON llx_actioncomm (entity,fk_soc,fk_action,datec,percent);