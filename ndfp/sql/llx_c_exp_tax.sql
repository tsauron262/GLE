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

CREATE TABLE IF NOT EXISTS `llx_c_exp_tax`(
  `rowid`          int(11)  AUTO_INCREMENT,
  `fk_cat`	int(11) DEFAULT 0 NOT NULL,
  `fk_range`	int(11) DEFAULT 0 NOT NULL,	  	  
  `coef`        double DEFAULT 0 NOT NULL,  
  `offset`      double DEFAULT 0 NOT NULL,	          
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;

TRUNCATE TABLE `llx_c_exp_tax`;

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (4, 1, 0.405, 0);
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (4, 2, 0.242, 818);
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (4, 3, 0.283, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (5, 4, 0.487, 0);
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (5, 5, 0.274, 1063);
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (5, 6, 0.327, 0); 

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (6, 7, 0.536, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (6, 8, 0.300, 1180); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (6, 9, 0.359, 0); 

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (7, 10, 0.561, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (7, 11, 0.316, 1223); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (7, 12, 0.377, 0); 

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (8, 13, 0.587, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (8, 14, 0.332, 1278); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (8, 15, 0.396, 0); 

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (9, 16, 0.619, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (9, 17, 0.352, 1338); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (9, 18, 0.419, 0); 

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (10, 19, 0.635, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (10, 20, 0.368, 1338); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (10, 21, 0.435, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (11, 22, 0.668, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (11, 23, 0.391, 1383); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (11, 24, 0.460, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (12, 25, 0.681, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (12, 26, 0.410, 1358); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (12, 27, 0.478, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (13, 28, 0.717, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (13, 29, 0.426, 1458); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (13, 30, 0.499, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (14, 31, 0.729, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (14, 32, 0.444, 1423); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (14, 33, 0.515, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (15, 34, 0.266, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (15, 35, 0.063, 406); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (15, 36, 0.144, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (16, 37, 0.333, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (16, 38, 0.083, 750); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (16, 39, 0.208, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (17, 40, 0.395, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (17, 41, 0.069, 978); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (17, 42, 0.232, 0);

INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (18, 43, 0.511, 0); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (18, 44, 0.067, 1332); 
INSERT INTO llx_c_exp_tax (`fk_cat`, `fk_range`, `coef`, `offset`) values (18, 45, 0.289, 0);
