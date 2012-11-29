<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once DOL_DOCUMENT_ROOT . '/BabelJasper/WebDAV/Client/Stream.php';

if (!HTTP_WebDAV_Client_Stream::register()) {
    PEAR::raiseError("couldn't register WebDAV stream wrappers");
}

?>
