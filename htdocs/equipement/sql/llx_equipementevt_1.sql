-- ===================================================================
-- Copyright (C) 2012-2015 Charlie Benke <charlie@patas-monkey.com>
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

create table llx_equipementevt
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  fk_equipement				integer,
  fk_equipementevt_type		integer NOT NULL,	-- type d'�v�nement sur l'�quipement
  datec						datetime,			-- date de cr�ation de la ligne d'�v�nement
  description				text,				-- description de la ligne d'ev�nement
  fk_user_author			integer,			-- cr�ateur de l'�v�nement
  datee						datetime,			-- date de d�but de l'�v�nement
  dateo						datetime,			-- date de fin de l'�v�nement
  tms						timestamp,
  fulldayevent				smallint(6),		-- �v�nement en mode journ�e
  total_ht					double(24,8),		-- cout associ� � l'�v�nement
  fk_fichinter				integer,			-- intervention li� � l'�v�nement
  fk_contrat				integer,			-- contrat li� � l'�v�nement
  fk_expedition				integer,			-- exp�dition li� � l'�v�nement
  fk_project				integer,			-- projet li� � l'�v�nement
  fk_operation				integer,			-- operation li� � l'�v�nement
  import_key 				VARCHAR( 14 ) NULL DEFAULT NULL
)ENGINE=innodb;
