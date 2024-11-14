ALTER TABLE llx_bimpcore_note ADD `tms` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();
ALTER TABLE llx_bimpcore_link ADD `tms` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

UPDATE llx_bimp_notification SET `method` = 'getNotesForUser' WHERE `method` = 'getNoteForUser';