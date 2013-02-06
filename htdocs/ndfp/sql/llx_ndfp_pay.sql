-- ============================================================================
-- Copyright (C) 2012 Mikael Carlavan  <mcarlavan@qis-network.com>
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
-- ============================================================================

CREATE TABLE IF NOT EXISTS `llx_ndfp_pay`(
  `rowid`			int(11) AUTO_INCREMENT,
  `fk_payment`      int(11) NOT NULL DEFAULT 0,
  `amount`          double(24,8) DEFAULT 0,
  `fk_user`         integer NOT NULL DEFAULT 0,
  `fk_user_author`  integer NOT NULL DEFAULT 0,           
  `payment_number`  varchar(50),       
  `note`            text,
  `fk_bank`         integer NOT NULL,
  `datec`           datetime NOT NULL,          
  `datep`           datetime,         
  `tms`				timestamp NOT NULL,    
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;


