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

CREATE TABLE IF NOT EXISTS `llx_c_exp_tax_range`(
  `rowid`          int(11)  AUTO_INCREMENT,
  `fk_cat`		int(11) DEFAULT 1 NOT NULL,
  `range`        double DEFAULT 0 NOT NULL,   
  `active`		int(11) DEFAULT 1 NOT NULL,		          
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;

TRUNCATE TABLE `llx_c_exp_tax_range`;

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (4, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (4, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (4, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (5, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (5, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (5, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (6, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (6, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (6, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (7, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (7, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (7, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (8, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (8, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (8, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (9, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (9, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (9, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (10, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (10, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (10, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (11, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (11, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (11, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (12, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (12, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (12, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (13, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (13, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (13, 20000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (14, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (14, 5000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (14, 20000, 1);



INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (15, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (15, 2000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (15, 5000, 1);


INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (16, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (16, 3000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (16, 6000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (17, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (17, 3000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (17, 6000, 1);

INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (18, 0, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (18, 3000, 1);
INSERT INTO llx_c_exp_tax_range (`fk_cat`, `range`, `active`) values (18, 6000, 1);

