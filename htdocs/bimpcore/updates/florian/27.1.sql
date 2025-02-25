ALTER TABLE `llx_bimpcore_signature_signataire`
	ADD `phone` varchar(255) NOT NULL DEFAULT '' AFTER `email`,
	ADD `signature_idx` int(11) NOT NULL DEFAULT 1;
