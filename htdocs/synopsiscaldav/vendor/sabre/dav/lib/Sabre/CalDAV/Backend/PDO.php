<?php

namespace Sabre\CalDAV\Backend;

use Sabre\VObject;
use Sabre\CalDAV;
use Sabre\DAV;

/**
 * PDO CalDAV backend
 *
 * This backend is used to store calendar-data in a PDO database, such as
 * sqlite or MySQL
 *
 * @copyright Copyright (C) 2007-2013 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class PDO extends AbstractBackend {

    /**
     * We need to specify a max date, because we need to stop *somewhere*
     *
     * On 32 bit system the maximum for a signed integer is 2147483647, so
     * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
     * in 2038-01-19 to avoid problems when the date is converted
     * to a unix timestamp.
     */
    const MAX_DATE = '2038-01-01';

    /**
     * pdo
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * The table name that will be used for calendars
     *
     * @var string
     */
    protected $calendarTableName;

    /**
     * The table name that will be used for calendar objects
     *
     * @var string
     */
    protected $calendarObjectTableName;

    /**
     * List of CalDAV properties, and how they map to database fieldnames
     * Add your own properties by simply adding on to this array.
     *
     * Note that only string-based properties are supported here.
     *
     * @var array
     */
    public $propertyMap = array(
        '{DAV:}displayname' => 'displayname',
        '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
        '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => 'timezone',
        '{http://apple.com/ns/ical/}calendar-order' => 'calendarorder',
        '{http://apple.com/ns/ical/}calendar-color' => 'calendarcolor',
    );

    /**
     * Creates the backend
     *
     * @param \PDO $pdo
     * @param string $calendarTableName
     * @param string $calendarObjectTableName
     */
    public function __construct(\PDO $pdo, $calendarTableName = 'calendars', $calendarObjectTableName = 'calendarobjects') {

        error_reporting(E_ALL);
        ini_set('error_log', str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, SYSLOG_FILE));
        $this->pdo = $pdo;
        $this->calendarTableName = $calendarTableName;
        $this->calendarObjectTableName = $calendarObjectTableName;
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is
     *    accessed.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * @param string $principalUri
     * @return array
     */
    public function getCalendarsForUser($principalUri) {

        $fields = array_values($this->propertyMap);
        $fields[] = 'id';
        $fields[] = 'uri';
        $fields[] = 'ctag';
        $fields[] = 'components';
        $fields[] = 'principaluri';
        $fields[] = 'transparent';

        // Making fields a comma-delimited list
        $fields = implode(', ', $fields);
        $stmt = $this->pdo->prepare("SELECT " . $fields . " FROM " . $this->calendarTableName . " WHERE principaluri = ? ORDER BY calendarorder ASC");
        $stmt->execute(array($principalUri));

        $calendars = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row['principaluri'] = $principalUri;
            $components = array();
            if ($row['components']) {
                $components = explode(',', $row['components']);
            }

            $calendar = array(
                'id' => $row['id'],
                'uri' => $row['uri'],
                'principaluri' => $row['principaluri'],
                '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}getctag' => $row['ctag'] ? $row['ctag'] : '0',
                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet($components),
                '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' => new CalDAV\Property\ScheduleCalendarTransp($row['transparent'] ? 'transparent' : 'opaque'),
            );


            foreach ($this->propertyMap as $xmlName => $dbName) {
                $calendar[$xmlName] = $row[$dbName];
            }

            $calendars[] = $calendar;
        }

        return $calendars;
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string
     */
    public function createCalendar($principalUri, $calendarUri, array $properties) {

        $fieldNames = array(
            'principaluri',
            'uri',
            'ctag',
            'transparent',
        );
        $values = array(
            ':principaluri' => $principalUri,
            ':uri' => $calendarUri,
            ':ctag' => 1,
            ':transparent' => 0,
        );

        // Default value
        $sccs = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';
        $fieldNames[] = 'components';
        if (!isset($properties[$sccs])) {
            $values[':components'] = 'VEVENT,VTODO';
        } else {
            if (!($properties[$sccs] instanceof CalDAV\Property\SupportedCalendarComponentSet)) {
                throw new DAV\Exception('The ' . $sccs . ' property must be of type: \Sabre\CalDAV\Property\SupportedCalendarComponentSet');
            }
            $values[':components'] = implode(',', $properties[$sccs]->getValue());
        }
        $transp = '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp';
        if (isset($properties[$transp])) {
            $values[':transparent'] = $properties[$transp]->getValue() === 'transparent';
        }

        foreach ($this->propertyMap as $xmlName => $dbName) {
            if (isset($properties[$xmlName])) {

                $values[':' . $dbName] = $properties[$xmlName];
                $fieldNames[] = $dbName;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO " . $this->calendarTableName . " (" . implode(', ', $fieldNames) . ") VALUES (" . implode(', ', array_keys($values)) . ")");
        $stmt->execute($values);

        return $this->pdo->lastInsertId();
    }

    /**
     * Updates properties for a calendar.
     *
     * The mutations array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existent property is always successful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname.
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param string $calendarId
     * @param array $mutations
     * @return bool|array
     */
    public function updateCalendar($calendarId, array $mutations) {

        $newValues = array();
        $result = array(
            200 => array(), // Ok
            403 => array(), // Forbidden
            424 => array(), // Failed Dependency
        );

        $hasError = false;

        foreach ($mutations as $propertyName => $propertyValue) {

            switch ($propertyName) {
                case '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp' :
                    $fieldName = 'transparent';
                    $newValues[$fieldName] = $propertyValue->getValue() === 'transparent';
                    break;
                default :
                    // Checking the property map
                    if (!isset($this->propertyMap[$propertyName])) {
                        // We don't know about this property.
                        $hasError = true;
                        $result[403][$propertyName] = null;
                        unset($mutations[$propertyName]);
                        continue;
                    }

                    $fieldName = $this->propertyMap[$propertyName];
                    $newValues[$fieldName] = $propertyValue;
            }
        }

        // If there were any errors we need to fail the request
        if ($hasError) {
            // Properties has the remaining properties
            foreach ($mutations as $propertyName => $propertyValue) {
                $result[424][$propertyName] = null;
            }

            // Removing unused statuscodes for cleanliness
            foreach ($result as $status => $properties) {
                if (is_array($properties) && count($properties) === 0)
                    unset($result[$status]);
            }

            return $result;
        }

        // Success
        // Now we're generating the sql query.
        $valuesSql = array();
        foreach ($newValues as $fieldName => $value) {
            $valuesSql[] = $fieldName . ' = ?';
        }
        $valuesSql[] = 'ctag = ctag + 1';

        $stmt = $this->pdo->prepare("UPDATE " . $this->calendarTableName . " SET " . implode(', ', $valuesSql) . " WHERE id = ?");
        $newValues['id'] = $calendarId;
        $stmt->execute(array_values($newValues));

        return true;
    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param string $calendarId
     * @return void
     */
    public function deleteCalendar($calendarId) {

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ?');
        $stmt->execute(array($calendarId));

        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->calendarTableName . ' WHERE id = ?');
        $stmt->execute(array($calendarId));
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *   * size - The size of the calendar objects, in bytes.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param string $calendarId
     * @return array
     */
    public function getCalendarObjects($calendarId) {

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size FROM ' . $this->calendarObjectTableName . ' WHERE lastoccurence > "' . mktime(0, 0, 0, date("m") - 4, date("d"), date("Y")) . '" AND calendarid = ?');
        $stmt->execute(array($calendarId));

        $result = array();
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = array(
                'id' => $row['id'],
                'uri' => $row['uri'],
                'lastmodified' => $row['lastmodified'],
                'etag' => '"' . $row['etag'] . '"',
                'calendarid' => $row['calendarid'],
                'size' => (int) $row['size'],
            );
        }

        return $result;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri) {

        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, agendaplus, size FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row)
            return null;

        global $db, $conf;
        require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
        $action = new \ActionComm($db);
        $filename = '-id' . $row['id'] . ".ics";
                date_default_timezone_set("Europe/Paris");
        $action->build_exportfile('ical', 'event', 0, $filename, array('id' => $row['id']));
                date_default_timezone_set("Europe/Paris");
        $outputfile = $conf->agenda->dir_temp . '/' . $filename;
        
        


        $calData = file_get_contents($outputfile);

        $calendarData2 = array(); //$this->traiteTabIcs($row['agendaplus'], array());
        
        /* Participant */
        $action->id = $row['id'];
        $action->fetch_userassigned();
        foreach($action->userassigned as $val){
            if($val['id'] != $calendarId){
            $userT = new \User($db);
            $userT->fetch($val['id']);
            if($userT->email != "")
                $calendarData2[9999 . $row['id'] . $val['id']] = "ATTENDEE;RSVP=TRUE;PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICIPANT:mailto:".$userT->email;
            }
        }
        
        
        $calendarData2 = $this->traiteTabIcs($calData, $calendarData2);
        $calendarData2['UID'] = str_replace(".ics", "", $row['uri']);
        
        
        
        
        $calData = $this->traiteIcsTab($calendarData2);

        $return = array(
            'id' => $row['id'],
            'uri' => $row['uri'],
            'lastmodified' => $row['lastmodified'],
            'etag' => '' . $row['etag'] . '',
            'calendarid' => $row['calendarid'],
            'size' => (int) $row['size'],
            'calendardata' => $calData,
        );
//        dol_syslog("retour".print_r($return,1),3);

        return $return;
    }

    /**
     * Creates a new calendar object.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData) {
//        dol_syslog("deb".print_r($calendarData,1),3);
//        $extraData = $this->getDenormalizedData($calendarData);
//
//        $stmt = $this->pdo->prepare('INSERT INTO '.$this->calendarObjectTableName.' (calendarid, uri, calendardata, lastmodified, etag, size, componenttype, firstoccurence, lastoccurence) VALUES (?,?,?,?,?,?,?,?,?)');
//        $stmt->execute(array(
//            $calendarId,
//            $objectUri,
//            $calendarData,
//            time(),
//            $extraData['etag'],
//            $extraData['size'],
//            $extraData['componentType'],
//            $extraData['firstOccurence'],
//            $extraData['lastOccurence'],
//        ));
//        $stmt = $this->pdo->prepare('UPDATE '.$this->calendarTableName.' SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));
//
//        return '"' . $extraData['etag'] . '"';

        $extraData = $this->getDenormalizedData($calendarData);
//        dol_syslog(print_r($extraData,1),3);
        $calendarData2 = $this->traiteTabIcs($calendarData, array());
//        dol_syslog("iciciciciicic".print_r($calendarData2,1),3);
        
        

        global $db;
        require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
        $action = new \ActionComm($db);
        
        if(isset($calendarData2) && isset($calendarData2['DTSTART']) && stripos($calendarData2['DTSTART'], "DATE:") !== false){
                date_default_timezone_set("GMT");
                $action->fulldayevent = true;
                $extraData['lastOccurence'] -= 60;
        }
        else
                date_default_timezone_set("Europe/Paris");

        $action->datep = $extraData['firstOccurence'];
        $action->datef = $extraData['lastOccurence'];
        if (isset($calendarData2['SUMMARY']))
            $action->label = $calendarData2['SUMMARY'];
        if (isset($calendarData2['DESCRIPTION']))
            $action->note = $calendarData2['DESCRIPTION'];
        if (isset($calendarData2['LOCATION']))
            $action->location = $calendarData2['LOCATION'];

//            $action->array_options['agendaplus'] = $calendarData;
        $user = new \User($db);
        $user->fetch($calendarId);

        $action->type_id = 5;
        global $objectUriTemp, $objectEtagTemp, $objectDataTemp;
        $objectDataTemp = $calendarData;
        $objectEtagTemp = $extraData['etag'];
        $objectUriTemp = $objectUri;
        
        $this->traiteParticipant($action, $calendarData2, $user);
        
        $action->userownerid = $user->id;
        $action->add($user);

//        $this->userIdCaldavPlus($calendarId);
    }
    
    public function traiteParticipant($action, $calendarData2, $user){
        global $db;
        $tabMail = array();
        foreach($calendarData2 as $ligne){
            if(stripos($ligne, "ATTENDEE") !== false){
                $tabT = explode("mailto:", $ligne);
                if(isset($tabT[0]))
                        $tabMail[] = $tabT[1];
            }
            if(stripos($ligne, "CUTYPE") != false){
                $tabT = explode("mailto:", $ligne);
                if(isset($tabT[0]))
                        $tabMail[] = $tabT[1];
            }
        }
        
        $action->userassigned = array($user->id => array('id' => $user->id));
        foreach($tabMail as $mail){
            $mail = str_replace ("\n", "", $mail);
            $mail = str_replace ("\r", "", $mail);
            $sql = $db->query("SELECT rowid 
FROM  `llx_user` 
WHERE  `email` LIKE  '".$mail."'");
            if($db->num_rows($sql) > 0){
                $ligne = $db->fetch_object($sql);
                $action->userassigned[$ligne->rowid] = array('id' => $ligne->rowid);
            }
        }
    }

//    public function userIdCaldavPlus($calendarId) {
//        $tabT = getElementElement("user", "idCaldav", $calendarId);
//        if (isset($tabT[0]))
//            setElementElement("user", "idCaldav", $calendarId, $tabT[0]['d']);
//        else
//            addElementElement("user", "idCaldav", $object->userownerid, 1);
//    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData) {

        $extraData = $this->getDenormalizedData($calendarData);

        $stmt = $this->pdo->prepare('UPDATE ' . $this->calendarObjectTableName . ' SET etag = ?, agendaplus = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($extraData['etag'], $calendarData, /* $extraData['size'], $extraData['componentType'], $extraData['firstOccurence'], $extraData['lastOccurence'] , */ $calendarId, $objectUri));
//        $stmt = $this->pdo->prepare('UPDATE ' . $this->calendarTableName . ' SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));


        global $objectEtagTemp;
        $objectEtagTemp = $extraData['etag'];

//        $this->userIdCaldavPlus($calendarId);


        global $db, $conf;
        $sql = $db->query("SELECT fk_object FROM " . MAIN_DB_PREFIX . "synopsiscaldav_event WHERE uri = '" . $objectUri . "'");
        if ($db->num_rows($sql) > 0) {
            $ligne = $db->fetch_object($sql);


            $calendarData2 = $this->traiteTabIcs($calendarData, array());



            require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
            $action = new \ActionComm($db);
            $action->fetch($ligne->fk_object);
            
            
            if(isset($calendarData2) && isset($calendarData2['DTSTART']) && stripos($calendarData2['DTSTART'], "DATE:") !== false){
                    date_default_timezone_set("GMT");
                    $action->fulldayevent = true;
                    $extraData['lastOccurence'] -= 60;
            }
            else{
                    date_default_timezone_set("Europe/Paris");
                    $action->fulldayevent = false;
            }
            
            $action->datep = $extraData['firstOccurence'];
            $action->datef = $extraData['lastOccurence'];
            if (isset($calendarData2['SUMMARY']))
                $action->label = $calendarData2['SUMMARY'];
            if (isset($calendarData2['DESCRIPTION']))
                $action->note = $calendarData2['DESCRIPTION'];
            if (isset($calendarData2['LOCATION']))
                $action->location = $calendarData2['LOCATION'];

//            $action->array_options['agendaplus'] = $calendarData;
            $user = new \User($db);
            $user->fetch($calendarId);


            $this->traiteParticipant($action, $calendarData2, $user);
            
            $action->update($user);
        }

        return '"' . $extraData['etag'] . '"';
    }

    function traiteTabIcs($tab, $tabResult = array()) {
//        $tabT = preg_replace("(mailto:[a-z1-9]+)\n ([a-z1-9]+[@])", "$1$2", $tabT);
        $tab = str_replace("\n ", "", $tab);
        $tabT = explode("\n", $tab);
        foreach ($tabT as $ligneT) {
            $tabT2 = array();
            if (stripos($ligneT, 'BEGIN:') === false && !(stripos($ligneT, 'END:') === 0) && stripos($ligneT, "ATTENDEE;") === false && stripos($ligneT, "CUTYPE") === false)
                $tabT2 = preg_split("/[:;]/", $ligneT);
            if (isset($tabT2[1]))
                $tabResult[$tabT2[0]] = preg_replace("/" . $tabT2[0] . "[:;]/", "", $ligneT);
            else
                $tabResult[] = $ligneT;
        }
        $position = '';
        $tabCore = $tabHead = array();
        foreach ($tabResult as $clef => $val) {
            //Pour ce tour
            if (stripos($val, "PARTICIPANT") > -1)
                $position = 'core';
            
            //pour le tour d'apres
            if (stripos($val, "BEGIN:VCALENDAR") > -1)
                $position = 'header';
            elseif (stripos($val, "BEGIN:VEVENT") > -1)
                $position = 'core';
            elseif (stripos($val, "END:VEVENT") > -1 || stripos($val, "END:VCALENDAR") > -1)
                $position = '';
            elseif ($val != "") {
                if ($position == 'header')
                    $tabHead[$clef] = $val;
                if ($position == 'core')
                    $tabCore[$clef] = $val;
            }
        }
//        $tabCore["TZNAME"] = "CET";
//        $tabHead["TZNAME"] = "CET";
//        $tabHead["TZOFFSETFROM"] = "+0200";
//        $tabHead["TZOFFSETTO"] = "+0100";

        $tabResult = array_merge(array("BEGIN:VCALENDAR"), $tabHead, array("BEGIN:VEVENT"), $tabCore, array("END:VEVENT", "END:VCALENDAR"));

        return $tabResult;
    }

    function traiteIcsTab($tab) {
        $tab2 = array();
        foreach ($tab as $clef => $ligne) {
            if (!is_integer($clef)) {
                if (stripos($ligne, "=") !== false && $clef != "URL")
                    $tab2[] = $clef . ";" . $ligne;
                else
                    $tab2[] = $clef . ":" . $ligne;
            } else
                $tab2[] = $ligne;
        }
        return implode("\n", $tab2);
    }

    /**
     * Parses some information from calendar objects, used for optimized
     * calendar-queries.
     *
     * Returns an array with the following keys:
     *   * etag
     *   * size
     *   * componentType
     *   * firstOccurence
     *   * lastOccurence
     *
     * @param string $calendarData
     * @return array
     */
    protected function getDenormalizedData($calendarData) {

        $vObject = VObject\Reader::read($calendarData);
        $componentType = null;
        $component = null;
        $firstOccurence = null;
        $lastOccurence = null;
        foreach ($vObject->getComponents() as $component) {
            if ($component->name !== 'VTIMEZONE') {
                $componentType = $component->name;
                break;
            }
        }
        if (!$componentType) {
            throw new \Sabre\DAV\Exception\BadRequest('Calendar objects must have a VJOURNAL, VEVENT or VTODO component');
        }
        if ($componentType === 'VEVENT') {
            $firstOccurence = $component->DTSTART->getDateTime()->getTimeStamp();
            // Finding the last occurence is a bit harder
            if (!isset($component->RRULE)) {
                if (isset($component->DTEND)) {
                    $lastOccurence = $component->DTEND->getDateTime()->getTimeStamp();
                } elseif (isset($component->DURATION)) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate->add(VObject\DateTimeParser::parse($component->DURATION->getValue()));
                    $lastOccurence = $endDate->getTimeStamp();
                } elseif (!$component->DTSTART->hasTime()) {
                    $endDate = clone $component->DTSTART->getDateTime();
                    $endDate->modify('+1 day');
                    $lastOccurence = $endDate->getTimeStamp();
                } else {
                    $lastOccurence = $firstOccurence;
                }
            } else {
                $it = new VObject\RecurrenceIterator($vObject, (string) $component->UID);
                $maxDate = new \DateTime(self::MAX_DATE);
                if ($it->isInfinite()) {
                    $lastOccurence = $maxDate->getTimeStamp();
                } else {
                    $end = $it->getDtEnd();
                    while ($it->valid() && $end < $maxDate) {
                        $end = $it->getDtEnd();
                        $it->next();
                    }
                    $lastOccurence = $end->getTimeStamp();
                }
            }
        }

        return array(
            'etag' => md5($calendarData),
            'size' => strlen($calendarData),
            'componentType' => $componentType,
            'firstOccurence' => $firstOccurence,
            'lastOccurence' => $lastOccurence,
        );
    }

    /**
     * Deletes an existing calendar object.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return void
     */
    public function deleteCalendarObject($calendarId, $objectUri) {

//        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri = ?');
//        $stmt->execute(array($calendarId,$objectUri));
//        $stmt = $this->pdo->prepare('UPDATE '. $this->calendarTableName .' SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));



        $stmt = $this->pdo->prepare('SELECT id FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row)
            return null;

        global $db, $conf, $user;
        $user = new \User($db);
        $user->fetch($calendarId);
        require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
        $action = new \ActionComm($db);
        $action->fetch($row['id']);
        $action->delete();
//        $this->userIdCaldavPlus($calendarId);
    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * Note that it is extremely likely that getCalendarObject for every path
     * returned from this method will be called almost immediately after. You
     * may want to anticipate this to speed up these requests.
     *
     * This method provides a default implementation, which parses *all* the
     * iCalendar objects in the specified calendar.
     *
     * This default may well be good enough for personal use, and calendars
     * that aren't very large. But if you anticipate high usage, big calendars
     * or high loads, you are strongly adviced to optimize certain paths.
     *
     * The best way to do so is override this method and to optimize
     * specifically for 'common filters'.
     *
     * Requests that are extremely common are:
     *   * requests for just VEVENTS
     *   * requests for just VTODO
     *   * requests with a time-range-filter on a VEVENT.
     *
     * ..and combinations of these requests. It may not be worth it to try to
     * handle every possible situation and just rely on the (relatively
     * easy to use) CalendarQueryValidator to handle the rest.
     *
     * Note that especially time-range-filters may be difficult to parse. A
     * time-range filter specified on a VEVENT must for instance also handle
     * recurrence rules correctly.
     * A good example of how to interprete all these filters can also simply
     * be found in \Sabre\CalDAV\CalendarQueryFilter. This class is as correct
     * as possible, so it gives you a good idea on what type of stuff you need
     * to think of.
     *
     * This specific implementation (for the PDO) backend optimizes filters on
     * specific components, and VEVENT time-ranges.
     *
     * @param string $calendarId
     * @param array $filters
     * @return array
     */
    public function calendarQuery($calendarId, array $filters) {

        $result = array();
        $validator = new \Sabre\CalDAV\CalendarQueryValidator();

        $componentType = null;
        $requirePostFilter = true;
        $timeRange = null;

        // if no filters were specified, we don't need to filter after a query
        if (!$filters['prop-filters'] && !$filters['comp-filters']) {
            $requirePostFilter = false;
        }

        // Figuring out if there's a component filter
        if (count($filters['comp-filters']) > 0 && !$filters['comp-filters'][0]['is-not-defined']) {
            $componentType = $filters['comp-filters'][0]['name'];

            // Checking if we need post-filters
            if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['time-range'] && !$filters['comp-filters'][0]['prop-filters']) {
                $requirePostFilter = false;
            }
            // There was a time-range filter
            if ($componentType == 'VEVENT' && isset($filters['comp-filters'][0]['time-range'])) {
                $timeRange = $filters['comp-filters'][0]['time-range'];

                // If start time OR the end time is not specified, we can do a
                // 100% accurate mysql query.
                if (!$filters['prop-filters'] && !$filters['comp-filters'][0]['comp-filters'] && !$filters['comp-filters'][0]['prop-filters'] && (!$timeRange['start'] || !$timeRange['end'])) {
                    $requirePostFilter = false;
                }
            }
        }

        if ($requirePostFilter) {
            $query = "SELECT uri, calendardata FROM " . $this->calendarObjectTableName . " WHERE calendarid = :calendarid";
        } else {
            $query = "SELECT uri FROM " . $this->calendarObjectTableName . " WHERE calendarid = :calendarid";
        }

        $values = array(
            'calendarid' => $calendarId,
        );

        if ($componentType) {
            $query.=" AND componenttype = :componenttype";
            $values['componenttype'] = $componentType;
        }

        if ($timeRange && $timeRange['start']) {
            $query.=" AND lastoccurence > :startdate";
            $values['startdate'] = $timeRange['start']->getTimeStamp();
        }
        if ($timeRange && $timeRange['end']) {
            $query.=" AND firstoccurence < :enddate";
            $values['enddate'] = $timeRange['end']->getTimeStamp();
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);

        $result = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($requirePostFilter) {
                if (!$this->validateFilterForObject($row, $filters)) {
                    continue;
                }
            }
            $result[] = $row['uri'];
        }

        return $result;
    }

}
