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
    public $uriTest = "BIMP-ERP-fgfjfhjfytcrt-2045804"; //35aef3ab-dd26-41b8-b361-f30dd6ff1bc4

    /**
     * Creates the backend
     *
     * @param \PDO $pdo
     * @param string $calendarTableName
     * @param string $calendarObjectTableName
     */

    public function __construct(\PDO $pdo, $calendarTableName = 'calendars', $calendarObjectTableName = 'calendarobjects') {
        global $conf;
        error_reporting(E_ALL);
        ini_set('error_log', str_replace("DOL_DATA_ROOT", DOL_DATA_ROOT, $conf->global->SYSLOG_FILE));
        $this->pdo = $pdo;
        $this->calendarTableName = $calendarTableName;
        $this->calendarObjectTableName = $calendarObjectTableName;

        global $infoEvent;
        $infoEvent = array();

        define("USER_EXTERNE_ID", 326);
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
        $stmt = $this->pdo->prepare("SELECT " . $fields . " FROM " . $this->calendarTableName . " WHERE login = ? ORDER BY calendarorder ASC");
        $stmt->execute(array(str_replace("principals/", "", $principalUri)));

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
            
            $row['calendarcolor'] = '#FF5733';
            $row['transparent'] = '0';


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
        //$valuesSql[] = 'ctag = ctag + 1';
        //dol_syslog("Update agenda ".$calendarId, 3,1, "_caldav2");


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
        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, size FROM ' . $this->calendarObjectTableName . ' WHERE '/* lastoccurence > "' . mktime(0, 0, 0, date("m") - 4, date("d"), date("Y")) . '" AND */ . ' calendarid = ?');
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
        global $db;
        $stmt = $this->pdo->prepare('SELECT id, dtstamp, CREATED, sequence, uri, lastmodified, etag, calendarid, participentExt, organisateur, agendaplus, size FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            dol_syslog('Objet introuvable req : SELECT id, dtstamp, CREATED, sequence, uri, lastmodified, etag, calendarid, participentExt, organisateur, agendaplus, size FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
            return null;
        }

//        $sql = $db->query('SELECT id, dtstamp, CREATED, sequence, uri, lastmodified, etag, calendarid, participentExt, organisateur, agendaplus, size FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = "'.$calendarId.'" AND uri = "'.$objectUri.'"');
//        if($db->num_rows($sql) < 1)
//            return null;
//        $row = $db->fetch_array($sql);


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
        if ($row['participentExt'] != "")
            $tabPartExtInt = explode(",", $row['participentExt']);
        else
            $tabPartExtInt = array();
        //echo "<pre>"; print_r($row);die;
        foreach ($action->userassigned as $val) {
//            if($val['id'] == $calendarId && $val['answer_status'] == -2)//supprimé pour cette user
//                return null;
            if ($val['id'] > 0 && $val["id"] != USER_EXTERNE_ID) {
                $userT = new \User($db);
                $userT->fetch($val['id']);
                if ($userT->email != "")
                    $tabPartExtInt[] = $userT->email . "|" . ($val['answer_status'] == 1 ? 'ACCEPTED' : ($val['answer_status'] < 0 ? 'DECLINED' : 'NEEDS-ACTION'));
            }
        }
        if (count($tabPartExtInt) > 1) {
            foreach ($tabPartExtInt as $part)
                if ($part != "" /* afficher lorga dans les attendee && $part != $row['organisateur'] */) {
                    $tabT3 = explode("|", $part);
                    $tmpMail = $tabT3[0];
                    $tmpEtat = (isset($tabT3[1]) ? $tabT3[1] : "ACCEPTED");


                    if ($row['organisateur'] == "")
                        $row['organisateur'] = $tmpMail;

                    if ($tmpMail == $row['organisateur']) {
//                        $extra = ";ROLE=CHAIR";
                        $extra = ";ROLE=REQ-PARTICIPANT";
                        $tmpEtat = "ACCEPTED";
                    } else
                        $extra = ";ROLE=REQ-PARTICIPANT";
                    $extra .= ($tmpEtat == "ACCEPTED" ? "" : ";RSVP=TRUE");

                    $calendarData2[] = "ATTENDEE;CUTYPE=INDIVIDUAL;PARTSTAT=" . $tmpEtat . $extra . ":mailto:" . $tmpMail;
                }
            //iciattendee 
//            $calendarData2[] = "ORGANIZER:mailto:" . $row['organisateur'];
            $cnOrga = "INC";
            $sql = $db->query("SELECT lastname, firstname FROM ".MAIN_DB_PREFIX."user WHERE email = '".$row['organisateur']."'");
            if($db->num_rows($sql)>0){
                $ln = $db->fetch_object($sql);
                $cnOrga = $ln->lastname." ".$ln->firstname;
            }
                
            $calendarData2[] = 'ORGANIZER;CN="'.$cnOrga.'":mailto:'.$row['organisateur'];
        }

        $action->fetch_optionals();
        if (isset($action->array_options['options_conf']) && $action->array_options['options_conf'] == true) {
            $calendarData2[] = 'CLASS:CONFIDENTIAL';
        }

        if ($row['organisateur'] != "")
            $calendarData2[] = 'X-OWNER:mailto:' . $row['organisateur'];

        $calendarData2[] = 'SEQUENCE:' . $row['sequence'];
        $calendarData2 = $this->traiteTabIcs($calData, $calendarData2);
        $calendarData2['UID'] = str_replace(".ics", "", $row['uri']);






        $calData = $this->traiteIcsTab($calendarData2);



        $calendarData2['LAST-MODIFIED'] = $row['lastmodified'];
        $calendarData2['CREATED'] = $row['CREATED'];
        if ($calendarData2['CREATED'] > $calendarData2['LAST-MODIFIED'])
            $calendarData2['LAST-MODIFIED'] = $calendarData2['CREATED'];

        date_default_timezone_set('UTC');
        //$calData = preg_replace('\'DTSTAMP:[0-9]+T[0-9]+Z\'', 'DTSTAMP:'. date("Ymd\THis\Z",$calendarData2['LAST-MODIFIED']), $calData);
        if ($row['dtstamp'] != "" && $row['dtstamp'] != "0")
            $calData = preg_replace('\'DTSTAMP:[0-9]+T[0-9]+Z\'', 'DTSTAMP:' . $row['dtstamp'], $calData);
        else {
            $dtstamp = gmdate('Ymd') . 'T' . gmdate('His') . "Z";
            $db->query("UPDATE " . MAIN_DB_PREFIX . "synopsiscaldav_event SET dtstamp = '" . $dtstamp . "' WHERE fk_object = " . $row['id']);
            $calData = preg_replace('\'DTSTAMP:[0-9]+T[0-9]+Z\'', 'DTSTAMP:' . $dtstamp, $calData);
        }
        date_default_timezone_set("Europe/Paris");

        //DECODAGE
        $calData = html_entity_decode($calData, ENT_QUOTES);

        $calData = str_replace("|ln|", "\\n", $calData);
        $calData = str_replace("|lna|", "\r\n ", $calData);
        $return = array(
            'id' => $row['id'],
            'uri' => $row['uri'],
            'lastmodified' => $calendarData2['LAST-MODIFIED'],
            'etag' => '' . $row['etag'] . '',
            'calendarid' => $row['calendarid'],
            'size' => (int) $row['size'],
            'calendardata' => $calData,
        );
        if($row['calendarid'] == 242){
            echo '<pre>';print_r($return);
            die;
        }
//        if(stripos($objectUri, $this->uriTest) > 0)
                $this->logIcs("get", $objectUri, $return, $calendarId);
//dol_syslog("GET OBJECT : ".$calendarId." ".$row["etag"]."   |   ".$objectUri."   |".print_r($return,1),3, 0, "_caldavLog");

        return $return;
    }
    
    
    function logIcs($action, $uri, $data, $idUser){
        $dir = "/data2/tempics/";
        if(is_dir($dir)){
            $dir .= $uri."/";
            if(!is_dir($dir))
                mkdir($dir);
            $objDateTime = new \DateTime('NOW');
            file_put_contents($dir.substr($uri,30)."-".$objDateTime->format("Y-m-d H:i:s:u")."-".microtime()."-".$idUser."-".$action."-".substr(urlencode($_SERVER['HTTP_USER_AGENT']), 0, 20).".txt", print_r($data,1));
        }
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
     * the exact same as this request boupdaedy, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData) {
        $calendarData = $this->traiteCalendarData($calendarData);



//        if (stripos($objectUri, $this->uriTest) > 0)
                $this->logIcs("create", $objectUri, $calendarData, $calendarId);
//            dol_syslog("Create : " . $calendarId . "    |   " . $objectUri . "   |" . print_r($calendarData, 1), 3, 0, "_caldavLog");
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
//        
//        
//        
        //Verif quil existe pas
        $stmt = $this->pdo->prepare('SELECT id, uri, lastmodified, etag, calendarid, participentExt, agendaplus, size FROM ' . $this->calendarObjectTableName . ' WHERE uri = ?');
        $stmt->execute(array($objectUri));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return $this->updateCalendarObject($calendarId, $objectUri, $calendarData);

            /* http_response_code(409);
              dol_syslog("Uri existe déja ".$objectUri,3,null,"_caldav2");
              die;
              return null; */
        } else {

            $extraData = $this->getDenormalizedData($calendarData);
//        dol_syslog(print_r($extraData,1),3);
            $calendarData2 = $this->traiteTabIcs($calendarData, array());
//        dol_syslog("iciciciciicic".print_r($calendarData,1),3);
//        $this->getRappel($calendarData2);

            global $db;
            require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
            $action = new \ActionComm($db);


            date_default_timezone_set("Europe/Paris");
            if (isset($calendarData2) && isset($calendarData2['DTSTART']) && stripos($calendarData2['DTSTART'], "DATE:") !== false) {
                //date_default_timezone_set("GMT");
                $action->fulldayevent = true;
                $extraData['firstOccurence'] -= 3600;
                $extraData['lastOccurence'] -= 3601;
            } //else
            //date_default_timezone_set("Europe/Paris");



            $tabR = array("LANGUAGE=fr-FR:", "LANGUAGE=en-EN:", "LANGUAGE=en-US:");
            foreach ($extraData as $clef => $val)
                $extraData[$clef] = str_replace($tabR, "", $val);


            $action->datep = $extraData['firstOccurence'];
            $action->datef = $extraData['lastOccurence'];
            if (isset($calendarData2['SUMMARY']))
                $action->label = str_replace($tabR, "", $calendarData2['SUMMARY']);
            if (isset($calendarData2['DESCRIPTION']))
                $action->note = str_replace($tabR, "", str_replace("\\n", "\n", $calendarData2['DESCRIPTION']));
            if (isset($calendarData2['LOCATION']))
                $action->location = str_replace($tabR, "", str_replace("\\n", "\n", $calendarData2['LOCATION']));

//            $action->array_options['agendaplus'] = $calendarData;
            $user = $this->getUser($calendarId);

            $action->type_id = 5;



            global $infoEvent;
            $infoEvent["data"] = $calendarData;
            $infoEvent["etag"] = $extraData['etag'];
            $infoEvent["uri"] = $objectUri;

            $this->traiteParticipantAndTime($action, $calendarData2, $calendarId);

            //$action->userownerid = $calendarId;
            $action->percentage = -1;
            if ($action->add($user) < 1)
                $this->forbiden("Create.");


            $this->traiteParticipantAndTime($action, $calendarData2, $calendarId);


//        $this->userIdCaldavPlus($calendarId);
        }
    }

    public function getUser($calendarId, $forceUserCalendar = false) {
        global $USER_CONNECT, $db;
        if (isset($USER_CONNECT) && is_object($USER_CONNECT) && !$forceUserCalendar) {
            $user = $USER_CONNECT;
        } else {
            $user = new \User($db);
            $user->fetch($calendarId);
        }
        return $user;
    }

    public function traiteParticipantAndTime($action, $calendarData2, $user) {
        global $db;
        $tabMail = array();
        $organisateur = "";
        if (is_object($user))
            $user = $user->id;
        $sequence = 0;

        $action->array_options['options_conf'] = false;
        foreach ($calendarData2 as $nom => $ligne) {

            $tab = array(CHR(13) => "", CHR(10) => "", " " => "");
            $ligne = strtr($ligne, $tab);
            //$ligne = str_replace("\r", "", $ligne);
            if (stripos($nom, "SEQUENCE") !== false) {
                $sequence = $ligne;
            }
            if (stripos($ligne, "CONFIDENTIAL") !== false) {
                $action->array_options['options_conf'] = true;
            }
            if (stripos($nom, "DTSTAMP") !== false) {
                $DTSTAMP = str_replace("DTSTAMP:", "", $ligne);
            }
            if (stripos($nom, "LAST-MODIFIED") !== false) {
                $last_modified = str_replace("LAST-MODIFIED:", "", $ligne);
            }
            if (stripos($ligne, "ATTENDEE") === 0 || stripos($ligne, "CUTYPE") === 0 || stripos($nom, "ATTENDEE") === 0) {
                $stat = "NEEDS-ACTION";
                if (preg_match("/^.*PARTSTAT=(.+);.+$/U", $ligne, $retour))
                    $stat = $retour[1];
                $tabT = explode("mailto:", strtolower($ligne));
                if (isset($tabT[2])) {
                    $mailT = str_replace(" ", "", $tabT[2]);
                    $tabMail[$mailT] = array($mailT, $stat);
                } elseif (isset($tabT[1])) {
                    $mailT = str_replace(" ", "", $tabT[1]);
                    $tabMail[$mailT] = array($mailT, $stat);
                } else {
                    if (preg_match("/^.*CN=(.+);.+$/U", $ligne, $retour)) {
                        $cn = $retour[1];
                        if (stripos($cn, '"') === false)
                            $cn = '"' . $cn . '"';
                        $sql = $db->query('SELECT email FROM `llx_user` WHERE concat(lastname, concat(" ", firstname)) = ' . $cn);
                        if ($db->num_rows($sql) > 0) {
                            $ln = $db->fetch_object($sql);
                            if ($ln->email != "")
                                $tabMail[$ln->email] = array($ln->email, $stat);
                        }
                    }
                }
            }
            if (stripos($ligne, "ORGANIZER") === 0 || stripos($nom, "ORGANIZER") === 0) {
                $tabT = explode("mailto:", strtolower($ligne));
                if (isset($tabT[2])) {
                    $mailT = str_replace(" ", "", $tabT[2]);
                    $organisateur = $mailT;
                    $tabMail[$mailT] = array($mailT, "ACCEPTED"); //Pour forcer l'organiser a etre invité
                } elseif (isset($tabT[1])) {
                    $mailT = str_replace(" ", "", $tabT[1]);
                    $organisateur = $mailT;
                    $tabMail[$mailT] = array($mailT, "ACCEPTED"); //Pour forcer l'organiser a etre invité
                } else {
                    if (preg_match("/^.*CN=(.+);.+$/U", $ligne, $retour)) {
                        $cn = $retour[1];
                        if (stripos($cn, '"') === false)
                            $cn = '"' . $cn . '"';
                        $sql = $db->query('SELECT email FROM `llx_user` WHERE concat(lastname, concat(" ", firstname)) = ' . $cn);
                        if ($db->num_rows($sql) > 0) {
                            $ln = $db->fetch_object($sql);
                            if ($ln->email != "") {
                                $tabMail[$ln->email] = array($ln->email, $stat);
                                $organisateur = $ln->email;
                            }
                        }
                    }
                }
            }
        }
//        if($organisateur == "" && isset($tabMail[0][0]))
//            $organisateur = $tabMail[0][0];
        $organisateur = str_replace(array("\n",
                ), "", $organisateur);
        $organisateur = str_replace("\n", "", $organisateur);
        $organisateur = str_replace("\r", "", $organisateur);
        
        $tabMailInc = array();
        $action->userownerid = $user;
        $action->userassigned = array($user => array('id' => $user));
        $okOrga = false;
        foreach ($tabMail as $tmp) {
            $mail = $tmp[0];
            $statut = (isset($tmp[1]) ? $tmp[1] : "NEEDS-ACTION");

            $sql = $db->query("SELECT rowid 
FROM  `" . MAIN_DB_PREFIX . "user` 
WHERE  `email` LIKE  '" . $mail . "'");
            if ($db->num_rows($sql) > 0) {
                $ligne = $db->fetch_object($sql);


                if ($organisateur == $mail) {
//                    $action->userdoneid = $ligne->rowid;
                    $action->userownerid = $ligne->rowid;
                    $statut = "ACCEPTED";
                    $okOrga = true;
                }


                $statutId = ($statut == "ACCEPTED" ? 1 : ($statut == "DECLINED" ? -1 : 0));
                $action->userassigned[$ligne->rowid] = array('id' => $ligne->rowid,
                    'answer_status' => $statutId);
            } else {
                $tabMailInc[] = $mail . "|" . $statut;
            }
        }
        if (!$okOrga) {
            if (count($action->userassigned) > 1/* || $organisateur != ""*/) {
                $action->userownerid = USER_EXTERNE_ID;
                $action->userassigned[USER_EXTERNE_ID] = array('id' => USER_EXTERNE_ID);
                if($organisateur == "")
                    $organisateur = "externe@bimp.fr";
            } elseif (isset($tabMail[0]))
                    $organisateur = $tabMail[0][0];
        }


        global $infoEvent;
        $infoEvent["dtstamp"] = $DTSTAMP;
        $infoEvent["sequence"] = $sequence;
        $infoEvent["organisateur"] = $organisateur;
        $infoEvent["participentExt"] = implode(",", $tabMailInc);


        if ($action->id > 0) {
            //dol_syslog("action ".$action->id." invit ext : ".print_r($tabMailInc,1),3);
            /* $req = "UPDATE " . MAIN_DB_PREFIX . "synopsiscaldav_event SET organisateur = '" . $organisateur . "', participentExt = '" . implode(",", $tabMailInc) . "'  ".($sequence > 0 ?", sequence = '" . $sequence . "'" : "")." WHERE fk_object = '" . $action->id . "'";
              $sql = $db->query($req); */
            
            if(!isset($last_modified) && isset($calendarData2['LAST-MODIFIED']))
                $last_modified = $calendarData2['LAST-MODIFIED'];

            if (!isset($last_modified))// || strtotime($calendarData2['LAST-MODIFIED']) < strtotime($DTSTAMP))
                $last_modified = $DTSTAMP;

            //date_default_timezone_set("GMT");
            $sql = "UPDATE `" . MAIN_DB_PREFIX . "actioncomm` SET " . (isset($calendarData2['CREATED']) ? "`datec` = '" . $db->idate(strtotime($calendarData2['CREATED'])) . "'," : "") . " `tms` = '" . $db->idate(strtotime($last_modified)) . "' WHERE `id` = " . $action->id . ";";

            $db->query($sql);
            //date_default_timezone_set("Europe/Paris");
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
    public function getRappel($data) {
        global $infoEvent;
        if (isset($data["TRIGGER"]) && stripos($data["TRIGGER"], "VALUE=DURATION"))
            $infoEvent["rappel"] = 15;
        return 0;
    }
    
    public function traiteCalendarData($calendarData){
        global $dataOrig, $dataOrig2, $dataOrig3;
        $dataOrig = $calendarData;
        $calendarData = str_replace("\x0D\x0A\x20", '', $calendarData);
        
        $calendarData = str_replace("\x0A\x20", '', $calendarData);
        $dataOrig3 = $calendarData;
        $calendarData = str_replace("\r\n ", "", $calendarData);
        return $calendarData;
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData) {
        $calendarData = $this->traiteCalendarData($calendarData);
        
//        if (stripos($objectUri, $this->uriTest) > 0)
                $this->logIcs("update", $objectUri, $calendarData, $calendarId);
//            dol_syslog("update : " . $calendarId . "    |   " . $objectUri . "   |" . print_r($calendarData, 1), 3, 0, "_caldavLog");

        $extraData = $this->getDenormalizedData($calendarData);

        $stmt = $this->pdo->prepare('UPDATE ' . $this->calendarObjectTableName . ' SET etag = ?, agendaplus = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($extraData['etag'], $calendarData, /* $extraData['size'], $extraData['componentType'], $extraData['firstOccurence'], $extraData['lastOccurence'] , */ $calendarId, $objectUri));
//        $stmt = $this->pdo->prepare('UPDATE ' . $this->calendarTableName . ' SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));


        global $infoEvent;
        $infoEvent["etag"] = $extraData['etag'];
//        $this->getRappel($extraData);
//        $this->userIdCaldavPlus($calendarId);


        global $db, $conf;
        $sql = $db->query("SELECT fk_object FROM " . MAIN_DB_PREFIX . "synopsiscaldav_event WHERE uri = '" . $objectUri . "'");
        if ($db->num_rows($sql) > 0) {
            $ligne = $db->fetch_object($sql);


            $calendarData2 = $this->traiteTabIcs($calendarData, array());



            require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
            $action = new \ActionComm($db);
            $action->fetch($ligne->fk_object);


            date_default_timezone_set("Europe/Paris");
            if (isset($calendarData2) && isset($calendarData2['DTSTART']) && stripos($calendarData2['DTSTART'], "DATE:") !== false) {
                //date_default_timezone_set("GMT");
                $action->fulldayevent = true;
                $extraData['firstOccurence'] -= -3600;
                $extraData['lastOccurence'] -= -3601;
            } else {
                //date_default_timezone_set("Europe/Paris");
                $action->fulldayevent = false;
            }

            $tabR = array("LANGUAGE=fr-FR:", "LANGUAGE=en-EN:", "LANGUAGE=en-US:");
            if (is_array($calendarData))
                foreach ($calendarData as $clef => $val)
                    $calendarData[$clef] = str_replace($tabR, "", $val);
            else
                $calendarData = str_replace($tabR, "", $calendarData);


            $action->datep = $extraData['firstOccurence'];
            $action->datef = $extraData['lastOccurence'];
            if (isset($calendarData2['SUMMARY']))
                $action->label = str_replace($tabR, "", $calendarData2['SUMMARY']);
            if (isset($calendarData2['DESCRIPTION']))
                $action->note = str_replace($tabR, "", str_replace("\\n", "\n", $calendarData2['DESCRIPTION']));
            if (isset($calendarData2['LOCATION']))
                $action->location = str_replace($tabR, "", $calendarData2['LOCATION']);

            //$action->userownerid = $calendarId;
//            $action->array_options['agendaplus'] = $calendarData;

            $user = $this->getUser($calendarId);


            $this->traiteParticipantAndTime($action, $calendarData2, $calendarId);

            if ($action->update($user) < 1)
                $this->forbiden("update. ".$action->error);


            $this->traiteParticipantAndTime($action, $calendarData2, $calendarId);
        }

        return '' . $extraData['etag'] . '';
    }

    function traiteTabIcs($tab, $tabResult = array()) {
//        $tabT = preg_replace("(mailto:[a-z1-9]+)\n ([a-z1-9]+[@])", "$1$2", $tabT);
        $tab = str_replace("\r\n ", "|lna|", $tab);
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
        $tabCore = $tabHead = $tabHead2 = array();
        foreach ($tabResult as $clef => $val) {
            //Pour ce tour
            if (stripos($val, "CUTYPE=INDIVIDUAL") > -1)
                $position = 'core';
            if (stripos($val, "CONFIDENTIAL") > -1)
                $position = 'core';
            if (stripos($val, "SEQUENCE") > -1)
                $position = 'core';

            if (stripos($val, "X-OWNER") > -1)
                $position = 'header';


            if (stripos($clef, "DESCRIPTION") > -1 || stripos($val, "DESCRIPTION:") == 0)
                $val = dol_trunc($val, 2000);

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

        $tabHead2 = array("BEGIN:VTIMEZONE",
            "TZID:Europe/Paris",
            "BEGIN:DAYLIGHT",
            "TZOFFSETFROM:+0100",
            "TZOFFSETTO:+0200",
            "TZNAME:CEST",
            "DTSTART:19700329T020000",
            "RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3",
            "END:DAYLIGHT",
            "BEGIN:STANDARD",
            "TZOFFSETFROM:+0200",
            "TZOFFSETTO:+0100",
            "TZNAME:CET",
            "DTSTART:19701025T030000",
            "RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10",
            "END:STANDARD",
            "END:VTIMEZONE");
//        if (isset($tabCore['DTSTART'])) {
//            $date = explode(":", $tabCore['DTSTART']);
//            if (isset($date[1]))
//                $tabHead2[] = 'DTSTART:' . $date[1];
//        }
//        $tabHead2[] = "END:STANDARD";
//        $tabHead2[] = "END:VTIMEZONE";
//        $tabCore["TZNAME"] = "CET";
//        $tabHead["TZNAME"] = "CET";
//        $tabHead["TZOFFSETFROM"] = "+0200";
//        $tabHead["TZOFFSETTO"] = "+0100";

        $tabAlarm = array();

//        $tabAlarm = array("BEGIN:VALARM", "DESCRIPTION:Alame chiante","ACTION:DISPLAY","TRIGGER;VALUE=DURATION:-PT15M","X-KDE-KCALCORE-ENABLED:TRUE","END:VALARM");
        $tabResult = array_merge(array("BEGIN:VCALENDAR"), $tabHead, $tabHead2, array("BEGIN:VEVENT"), $tabCore, $tabAlarm, array("END:VEVENT", "END:VCALENDAR"));

        return $tabResult;
    }

    function traiteIcsTab($tab) {
        $tab2 = array();
        foreach ($tab as $clef => $ligne) {
            $tabR = array(CHR(13) => "|ln|", CHR(10) => "|ln|", "\n" => "|ln|", "
" => "|ln|");
            $tabException = array("URL", "SUMMARY", "ORGANIZER", "LOCATION", "CATEGORIES", "DESCRIPTION", "UID");
            $ligne = strtr($ligne, $tabR);
            if (stripos($clef, 'SUMMARY') !== false || stripos($ligne, 'SUMMARY') !== false)
                $ligne = substr($ligne, 0, 2000);
            if (!is_integer($clef)) {
                if (stripos($ligne, "=") !== false && !in_array($clef, $tabException))
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


        $tabR = array(";LANGUAGE=fr-FR", ";LANGUAGE=en-EN", ";LANGUAGE=en-US");
        $calendarData = str_replace($tabR, "", $calendarData);

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
                $this->logIcs("delete", $objectUri, array(), $calendarId);

//        $stmt = $this->pdo->prepare('DELETE FROM '.$this->calendarObjectTableName.' WHERE calendarid = ? AND uri = ?');
//        $stmt->execute(array($calendarId,$objectUri));
//        $stmt = $this->pdo->prepare('UPDATE '. $this->calendarTableName .' SET ctag = ctag + 1 WHERE id = ?');
//        $stmt->execute(array($calendarId));
        if (stripos($objectUri, $this->uriTest) > 0)
            dol_syslog("Remove : " . $calendarId . "    |   " . $objectUri, 3, 0, "_caldavLog");



        $stmt = $this->pdo->prepare('SELECT id FROM ' . $this->calendarObjectTableName . ' WHERE calendarid = ? AND uri = ?');
        $stmt->execute(array($calendarId, $objectUri));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row)
            return null;

        global $db, $user;
        $user = $this->getUser($calendarId, true);
        require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
        $action = new \ActionComm($db);
        $action->fetch($row['id']);
        if($action->userownerid == $user->id || count($action->userassigned) == 1){
            if ($action->delete() < 1)
                $this->forbiden("delete.");
        }
        else{
            if(isset($action->userassigned[$user->id])){
                $action->userassigned[$user->id]['answer_status'] = -2;
                $action->update($user);
            }
        }
            
//        $this->userIdCaldavPlus($calendarId);
    }

    function forbiden($msg = "") {
//        global $USER_CONNECT;
//        
//        if(is_object($USER_CONNECT))
//            dol_syslog("Caldav Forbiden user connect ".$USER_CONNECT->id,3);
//        else
//            dol_syslog("Caldav Forbiden sans user connect",3);
        throw new DAV\Exception\Forbidden('Permission denied to ' . $msg);
//            header('HTTP/1.0 403 Forbidden');
//            header('Content-type: application/xml');
//            echo '<D:error xmlns:D="DAV:">
// <D:need-privileges>
// <D:resource>
// <D:href>/a</D:href>
// <D:privilege><D:unbind/></D:privilege>
// </D:resource>
// <D:resource>
// <D:href>/c</D:href>
// <D:privilege><D:bind/></D:privilege>
// </D:resource>
// </D:need-privileges>
//</D:error>';
//            die;
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
            // There was a time-range filter
//            if ($componentType == 'VEVENT' && isset($filters['comp-filters'][0]['uid'])) {
//                $uid = $filters['comp-filters'][0]['uid'];
//
//            }
            
            foreach($filters['comp-filters'][0]['prop-filters'] as $filter){
                if($filter['name'] == 'UID'){
                    $uid = '%'.$filter['text-match']['value'].'%';
                }
                
            }
        }

        if ($requirePostFilter) {
            $query = "SELECT uri, calendarid FROM " . $this->calendarObjectTableName . " WHERE calendarid = :calendarid";
        } else {
            $query = "SELECT uri FROM " . $this->calendarObjectTableName . " WHERE calendarid = :calendarid";
        }

        $values = array(
            'calendarid' => $calendarId,
        );

        if ($componentType) {
            $query .= " AND componenttype = :componenttype";
            $values['componenttype'] = $componentType;
        }

        if ($timeRange && $timeRange['start']) {
            $query .= " AND lastoccurence > :startdate";
            $values['startdate'] = $timeRange['start']->getTimeStamp();
        }
        if ($timeRange && $timeRange['end']) {
            $query .= " AND firstoccurence < :enddate";
            $values['enddate'] = $timeRange['end']->getTimeStamp();
        }
        if ($uid) {
            $query .= " AND uri LIKE :uri";
            $values['uri'] = $uid;
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
