-- Delete alert without soc
DELETE FROM `llx_bimp_task` WHERE test_ferme LIKE "contratdet:rowid=% AND statut=5" AND dst="suivicontrat@bimp.fr"

