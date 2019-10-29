ALTER TABLE `llx_bimp_c_values8sens`
  DROP PRIMARY KEY,
   ADD PRIMARY KEY(
     `id`,
     `type`);


INSERT INTO `llx_bimp_c_values8sens`(`id`, `label`, `type`) VALUES (0, '', 'categorie');
INSERT INTO `llx_bimp_c_values8sens`(`id`, `label`, `type`) VALUES (0, '', 'collection');
INSERT INTO `llx_bimp_c_values8sens`(`id`, `label`, `type`) VALUES (0, '', 'nature');
INSERT INTO `llx_bimp_c_values8sens`(`id`, `label`, `type`) VALUES (0, '', 'famille');