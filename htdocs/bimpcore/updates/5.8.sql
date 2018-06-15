CREATE TABLE `llx_bs_ticket` (
  `id` int(11) NOT NULL,
  `id_contrat` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_client` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_contact` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_user_resp` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `ticket_number` varchar(128) NOT NULL,
  `priorite` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `impact` int(11) NOT NULL DEFAULT '1',
  `appels_timer` int(11) NOT NULL DEFAULT '0',
  `cover` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED NOT NULL DEFAULT '0'
);

ALTER TABLE `llx_bs_ticket`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `llx_bs_ticket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `llx_bs_inter` (
  `id` int(11) NOT NULL,
  `id_ticket` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tech_id_user` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timer` int(10) UNSIGNED NOT NULL,
  `priorite` int(11) NOT NULL DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '1',
  `description` text,
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL
);

ALTER TABLE `llx_bs_inter`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `llx_bs_inter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `llx_bimp_timer` (
  `id` int(11) NOT NULL,
  `obj_module` varchar(128) NOT NULL,
  `obj_name` varchar(128) NOT NULL,
  `id_obj` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(128) NOT NULL,
  `time_session` int(11) NOT NULL,
  `session_start` int(11) DEFAULT NULL,
  `id_user` int(10) UNSIGNED NOT NULL
);

ALTER TABLE `llx_bimp_timer`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `llx_bimp_timer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;



CREATE TABLE `llx_bs_note` (
  `id` int(11) NOT NULL,
  `id_ticket` int(10) UNSIGNED NOT NULL,
  `id_inter` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `visibility` int(11) NOT NULL,
  `content` text NOT NULL,
  `user_create` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_update` int(10) UNSIGNED DEFAULT NULL,
  `date_update` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE `llx_bs_note`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `llx_bs_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;