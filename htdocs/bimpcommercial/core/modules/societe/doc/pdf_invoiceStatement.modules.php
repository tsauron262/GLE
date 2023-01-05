<?php

require_once DOL_DOCUMENT_ROOT.'/bimpcore/pdf/classes/InvoiceStatementPDF.php';


/**
 *	Class to generate PDF Invoice Statement PDF
 */
class pdf_invoiceStatement extends InvoiceStatementPDF
{
    public function initData() {
        parent::initData();
    }
}
