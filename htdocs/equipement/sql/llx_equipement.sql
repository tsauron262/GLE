-- ===================================================================
-- Copyright (C) 2012-2014 Charles-Fr Benke <charles.fr@benke.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_equipement
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  fk_product		integer DEFAULT 0,			-- produit auquel est rattache l'�quipement
  fk_product_batch	integer DEFAULT 0,			-- num�ro de batch/lot du produit auquel est rattache l'�quipement
  fk_soc_fourn		integer DEFAULT 0,			-- fournisseur de l'�quipement
  fk_soc_client		integer DEFAULT 0,			-- client propri�taire de l'�quipement
  ref				varchar(60) NOT NULL,		-- num�ro de s�rie interne de l'�quipement ou du lot (si lot = numversion + compteur si d�coup�)
  numimmocompta		text,						-- num�ro d'immobilisation comptable
  numversion		varchar(30) NULL,			-- version du produit du fournisseur (si gestion par lot)
  fk_facture_fourn	integer DEFAULT 0,			-- cl� de la facture d'achat au fournisseur
  fk_facture		integer DEFAULT 0,			-- cl� de la facture de vente au client
  fk_entrepot		integer DEFAULT 0,			-- lieu de stockage de l'�quipement
  quantity			integer DEFAULT 1 NOT NULL,	-- NEW : quantit� unitaire de l'�quipement (par d�faut � 1, > 0 si lot)
  price				double(24,8),				-- NEW : prix de vente HT de l'�quipement 
  pmp				double(24,8),				-- NEW : prix d'achat HT de l'�quipement
  unitweight		double(24,8),				-- NEW : poid de l'�quipement
  entity			integer DEFAULT 1 NOT NULL,	-- multi company id
  tms				timestamp,
  datec				datetime,					-- date de cr�ation de l'�quipement
  datev				datetime,					-- date de passage � l'�tat "valid�" de l'�quipement
  dateo				datetime,					-- date de d�but de vie de l'�quipement
  datee				datetime,					-- date de fin de vie de l'�quipement
  fk_user_author	integer,					-- createur de la fiche
  fk_user_valid		integer,					-- valideur de la fiche
  fk_statut			smallint  DEFAULT 0,		-- statut de l'�quipement
  fk_etatequipement integer,					-- �tat de l'�quipement
  localisation		text,						-- localisation de l'�quipement chez le client
  description		text,
  note_private		text,
  note_public		text,
  model_pdf			varchar(255),
  import_key 		VARCHAR( 14 ) NULL DEFAULT NULL,
  extraparams		varchar(255)				-- for stock other parameters with json format
)ENGINE=innodb;
