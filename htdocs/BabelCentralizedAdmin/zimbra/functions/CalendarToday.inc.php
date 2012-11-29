<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 * @author Corey Scott (corey.scott@gmail.com)
 */
include_once('./ZimbraSoapClient.inc.php');
include_once('./datatypes/Appointment.inc.php');
include_once('./Date_PHP/classes/MWDate.inc.php');

class CalendarToday extends ZimbraSoapClient
{
    /** */
    var $arrAppointments = null;

    /** */
    function getCalendarToday()
    {
        // get context
        $objContext = $this->_getContext();

        // set headers
        $this->addHeaders($objContext);

        // build args
        $arrArgs = array();
        $intTsStart     = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $intTsEnd       = mktime(23, 59, 59, date('m'), date('d'), date('Y'));

        $strFunctionName = 'GetApptSummariesRequest s="' . $intTsStart . '000" e="' . $intTsEnd . '000" ';

        // call function
        if (!$this->execute($strFunctionName, $arrArgs, 'urn:zimbraMail'))
        {
            return false;
        }

        // parse out the appointments
//            echo nl2br(print_r($this->objLastResponse, true));
        $arrAppointmentList = $this->objLastResponse->appt;
        foreach ($arrAppointmentList as $objCurrent)
        {
            $objNew = new Appointment();
            $arrAttrib = $objCurrent->attributes();
            $objNew->intID      = (int) $arrAttrib['id'];
            $objNew->strName    = (string) $arrAttrib['name'];
            $objNew->strLocation = (string) $arrAttrib['loc'];
            $objNew->boolAllDay = (bool) $arrAttrib['allDay'];

            // get appointment data
            $strFunctionName = 'GetAppointmentRequest id="' . $objNew->intID . '" ';
            if (!$this->execute($strFunctionName, $arrArgs, 'urn:zimbraMail'))
            {
                $this->arrAppointments[] = $objNew;
                continue;
            }
            $arrStartAttrib = $this->objLastResponse->appt->inv->comp->s->attributes();
            $objNew->dtStart = MWDate::setString($arrStartAttrib['d']);
            $arrEndAttrib = $this->objLastResponse->appt->inv->comp->e->attributes();
            $objNew->dtEnd = MWDate::setString($arrEndAttrib['d']);

            $this->arrAppointments[] = $objNew;
        }

        return true;
    }

}
?>
