update `llx_societe` set `date_atradius` = '2022-01-01' WHERE `date_atradius` = '2021-01-01';
update `llx_societe` set `date_atradius` = '2022-01-01' WHERE `date_atradius` = '0000-00-00 00:00:00';

DELETE FROM llx_bimpcore_note WHERE content LIKE "L'encours ICBA pour le client % n'est valable que jusqu'au 01/01/2022<br/>Il convient de le renouveler avant cette date";
DELETE FROM llx_bimpcore_note WHERE content LIKE "L'encours ICBA pour le client % n'est valable que jusqu'au 30/11/0000<br/>Il convient de le renouveler avant cette date";