--
--  CyberOffice
--
--  @author    LVSinformatique <contact@lvsinformatique.com>
--  @copyright 2014 LVSInformatique
--  @license   NoLicence
--  @version   1.3.0
--

create table llx_c_cyberoffice
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  extrafield    varchar(64) NOT NULL,
  idpresta      integer DEFAULT 0 NOT NULL,
  active        tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;
ALTER TABLE llx_c_cyberoffice ADD UNIQUE INDEX uk_c_cyberoffice(extrafield);
create table llx_c_cyberoffice2
(
  rowid         integer AUTO_INCREMENT PRIMARY KEY,
  warehouse    integer NOT NULL,
  carrier      integer DEFAULT 0 NOT NULL,
  active        tinyint DEFAULT 1  NOT NULL
)ENGINE=innodb;
ALTER TABLE llx_c_cyberoffice2 ADD UNIQUE INDEX uk_c_cyberoffice2(carrier);
