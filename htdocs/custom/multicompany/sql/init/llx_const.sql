-- Copyright (C) 2009-2021 Regis Houssin  <regis.houssin@inodbox.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--


-- Hidden but specific to one entity
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_ENABLE_OVERWRITE_TRANSLATION')__,__ENCRYPT('1')__,'chaine','Enable translation overwrite',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_ENABLE_DEFAULT_VALUES')__,__ENCRYPT('1')__,'chaine','Enable default value overwrite',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MONNAIE')__,__ENCRYPT('EUR')__,'chaine','Currency',0,1);

insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MAIL_SMTP_SERVER')__,'','chaine','Host or ip address for SMTP server',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MAIL_SMTP_PORT')__,'','chaine','Port for SMTP server',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MAIL_EMAIL_FROM')__,__ENCRYPT('robot@domain.com')__,'chaine','email emitter for Dolibarr automatic emails',0,__ENTITY__);

--
-- IHM
--

insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MENU_STANDARD')__,__ENCRYPT('eldy_menu.php')__,'chaine','Module de gestion de la barre de menu du haut pour utilisateurs internes',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MENUFRONT_STANDARD')__,__ENCRYPT('eldy_menu.php')__,'chaine','Module de gestion de la barre de menu du haut pour utilisateurs externes',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MENU_SMARTPHONE')__,__ENCRYPT('eldy_menu.php')__,'chaine','Module de gestion de la barre de menu smartphone pour utilisateurs internes',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_MENUFRONT_SMARTPHONE')__,__ENCRYPT('eldy_menu.php')__,'chaine','Module de gestion de la barre de menu smartphone pour utilisateurs externes',0,__ENTITY__);

insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_THEME')__,__ENCRYPT('eldy')__,'chaine','Default theme',0,__ENTITY__);

--
-- Delai tolerance
--
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_ACTIONS_TODO')__,__ENCRYPT('7')__,'chaine','Tolérance de retard avant alerte (en jours) sur actions planifiées non réalisées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_ORDERS_TO_PROCESS')__,__ENCRYPT('2')__,'chaine','Tolérance de retard avant alerte (en jours) sur commandes clients non traitées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS')__,__ENCRYPT('7')__,'chaine','Tolérance de retard avant alerte (en jours) sur commandes fournisseurs non traitées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_PROPALS_TO_CLOSE')__,__ENCRYPT('31')__,'chaine','Tolérance de retard avant alerte (en jours) sur propales à cloturer',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_PROPALS_TO_BILL')__,__ENCRYPT('7')__,'chaine','Tolérance de retard avant alerte (en jours) sur propales non facturées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_CUSTOMER_BILLS_UNPAYED')__,__ENCRYPT('31')__,'chaine','Tolérance de retard avant alerte (en jours) sur factures client impayées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_SUPPLIER_BILLS_TO_PAY')__,__ENCRYPT('2')__,'chaine','Tolérance de retard avant alerte (en jours) sur factures fournisseur impayées',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_NOT_ACTIVATED_SERVICES')__,__ENCRYPT('0')__,'chaine','Tolérance de retard avant alerte (en jours) sur services à activer',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_RUNNING_SERVICES')__,__ENCRYPT('0')__,'chaine','Tolérance de retard avant alerte (en jours) sur services expirés',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_MEMBERS')__,__ENCRYPT('31')__,'chaine','Tolérance de retard avant alerte (en jours) sur cotisations adhérent en retard',0,__ENTITY__);
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE')__,__ENCRYPT('62')__,'chaine','Tolérance de retard avant alerte (en jours) sur rapprochements bancaires à faire',0,__ENTITY__);

--
-- ODT Path
--
insert into llx_const (name, value, type, visible, entity) VALUES (__ENCRYPT('PRODUCT_ADDON_PDF_ODT_PATH')__,   __ENCRYPT('DOL_DATA_ROOT/doctemplates/products')__, 'chaine', 0, __ENTITY__);
insert into llx_const (name, value, type, visible, entity) VALUES (__ENCRYPT('CONTRACT_ADDON_PDF_ODT_PATH)__',  __ENCRYPT('DOL_DATA_ROOT/doctemplates/contracts')__, 'chaine', 0, __ENTITY__);
insert into llx_const (name, value, type, visible, entity) VALUES (__ENCRYPT('USERGROUP_ADDON_PDF_ODT_PATH)__', __ENCRYPT('DOL_DATA_ROOT/doctemplates/usergroups')__, 'chaine', 0, __ENTITY__);
insert into llx_const (name, value, type, visible, entity) VALUES (__ENCRYPT('USER_ADDON_PDF_ODT_PATH')__,      __ENCRYPT('DOL_DATA_ROOT/doctemplates/users')__, 'chaine', 0, __ENTITY__);

--
-- Mail Mailing
--
insert into llx_const (name, value, type, note, visible, entity) values (__ENCRYPT('MAILING_EMAIL_FROM')__,__ENCRYPT('dolibarr@domain.com')__,'chaine','EMail emmetteur pour les envois d emailings',0,__ENTITY__);

