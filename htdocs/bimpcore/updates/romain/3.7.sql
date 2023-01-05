UPDATE llx_societe s SET s.cond_reglement = (SELECT cp.rowid FROM llx_c_payment_term cp WHERE cp.libelle="A réception de facture") 
WHERE s.fk_typent = (SELECT ct.id FROM llx_c_typent ct WHERE ct.libelle="Particulier")
