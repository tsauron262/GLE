-- Ajout du status fermé, on fait suivre les inventaires déjà fermé
UPDATE llx_bl_inventory SET status=3 WHERE status=2

-- Insertion du nouvel entrepôt pour les invenatires

INSERT INTO `llx_entrepot` (`datec`, `tms`, `ref`, `entity`, `description`, `lieu`, `address`, `zip`, `town`, `fk_departement`, `fk_pays`, `statut`, `fk_user_author`, `model_pdf`, `import_key`, `fk_parent`, `ship_to`, `has_entrepot_commissions`, `has_users_commissions`) VALUES
('2019-09-25 22:00:00', '2019-09-25 22:00:00', 'INV', 1, 'Entrepôt fictif dans lequel on met les différences de stock lors de la fermeture d''inventaire', 'STOCK INVENTAIRE', NULL, NULL, NULL, NULL, 0, 1, NULL, NULL, NULL, 0, '', 0, 1);