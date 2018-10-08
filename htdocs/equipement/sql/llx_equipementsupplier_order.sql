-- ===================================================================
-- Copyright (C) 2013 Charles-Fr Benke <charles.fr@benke.fr>
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

create table llx_equipementsupplier_order
(
 	rowid				integer AUTO_INCREMENT PRIMARY KEY,
	fk_product			integer DEFAULT 0,			-- produit auquel est rattache l'�quipement
	fk_commande			integer,
	fk_commandedet		integer,
	position 			integer
)ENGINE=innodb;
