<?php
/*
 ** GLE by Synopsis et DRSI
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
$SCHEDULING_WS_URI = "http://localhost:8080/jasperserver/services/ReportScheduler?wsdl";


class JobTrigger {
  public $id; // long
  public $version; // int
  public $timezone; // string
  public $startDate; // dateTime
  public $endDate; // dateTime
}

class JobCalendarTrigger extends JobTrigger {
  public $minutes; // string
  public $hours; // string
  public $daysType; // CalendarDaysType
  public $weekDays; // ArrayOf_xsd_int
  public $monthDays; // string
  public $months; // ArrayOf_xsd_int
}

class JobMailNotification {
  public $id; // long
  public $version; // int
  public $toAddresses; // ArrayOf_xsd_string
  public $subject; // string
  public $messageText; // string
  public $resultSendType; // ResultSendType
  public $skipEmptyReports; // boolean
}

class JobParameter {
  public $name; // string
  public $value; // anyType
}

class JobRepositoryDestination {
  public $id; // long
  public $version; // int
  public $folderURI; // string
  public $sequentialFilenames; // boolean
  public $overwriteFiles; // boolean
}

class JobSimpleTrigger extends JobTrigger {
  public $occurrenceCount; // int
  public $recurrenceInterval; // int
  public $recurrenceIntervalUnit; // IntervalUnit
}

class Job {
  public $id; // long
  public $version; // int
  public $reportUnitURI; // string
  public $username; // string
  public $label; // string
  public $description; // string
  public $simpleTrigger; // JobSimpleTrigger
  public $calendarTrigger; // JobCalendarTrigger
  public $parameters; // ArrayOfJobParameter
  public $baseOutputFilename; // string
  public $outputFormats; // ArrayOf_xsd_string
  public $outputLocale; // string
  public $repositoryDestination; // JobRepositoryDestination
  public $mailNotification; // JobMailNotification
}

class JobSummary {
  public $id; // long
  public $version; // int
  public $reportUnitURI; // string
  public $username; // string
  public $label; // string
  public $state; // RuntimeJobState
  public $previousFireTime; // dateTime
  public $nextFireTime; // dateTime
}

class IntervalUnit {
  const MINUTE = 'MINUTE';
  const HOUR = 'HOUR';
  const DAY = 'DAY';
  const WEEK = 'WEEK';
}

class CalendarDaysType {
  const ALL = 'ALL';
  const WEEK = 'WEEK';
  const MONTH = 'MONTH';
}

class ResultSendType {
  const SEND = 'SEND';
  const SEND_ATTACHMENT = 'SEND_ATTACHMENT';
}

class RuntimeJobState {
  const UNKNOWN = 'UNKNOWN';
  const NORMAL = 'NORMAL';
  const EXECUTING = 'EXECUTING';
  const PAUSED = 'PAUSED';
  const COMPLETE = 'COMPLETE';
  const ERROR = 'ERROR';
}


/**
 * ReportSchedulerService class
 *
 *
 *
 */
class ReportSchedulerService extends SoapClient {

  private static $classmap = array(
                                    'JobTrigger' => 'JobTrigger',
                                    'JobCalendarTrigger' => 'JobCalendarTrigger',
                                    'JobMailNotification' => 'JobMailNotification',
                                    'JobParameter' => 'JobParameter',
                                    'JobRepositoryDestination' => 'JobRepositoryDestination',
                                    'JobSimpleTrigger' => 'JobSimpleTrigger',
                                    'Job' => 'Job',
                                    'JobSummary' => 'JobSummary',
                                    'IntervalUnit' => 'IntervalUnit',
                                    'CalendarDaysType' => 'CalendarDaysType',
                                    'ResultSendType' => 'ResultSendType',
                                    'RuntimeJobState' => 'RuntimeJobState',
                                   );

  public function ReportSchedulerService($wsdl, $username, $password, $options = array()) {
    foreach(self::$classmap as $key => $value) {
      if(!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }

    $options['login'] = $username;
    $options['password'] = $password;

    parent::__construct($wsdl, $options);
  }

  /**
   *
   *
   * @param long $id
   * @return void
   */
  public function deleteJob($id) {
    return $this->__soapCall('deleteJob', array($id),       array(
            'uri' => 'http://www.jasperforge.org/jasperserver/ws',
            'soapaction' => ''
           )
      );
  }

  /**
   *
   *
   * @param ArrayOf_xsd_long $ids
   * @return void
   */
  public function deleteJobs($ids) {
    return $this->__soapCall('deleteJobs', array($ids),       array(
            'uri' => 'http://www.jasperforge.org/jasperserver/ws',
            'soapaction' => ''
           )
      );
  }

  /**
   *
   *
   * @param long $id
   * @return Job
   */
  public function getJob($id) {
    return $this->__soapCall('getJob', array($id),       array(
            'uri' => 'http://www.jasperforge.org/jasperserver/ws',
            'soapaction' => ''
           )
      );
  }

  /**
   *
   *
   * @param Job $job
   * @return Job
   */
  public function scheduleJob(Job $job) {
    return $this->__soapCall('scheduleJob', array($job),       array(
            'uri' => 'http://www.jasperforge.org/jasperserver/ws',
            'soapaction' => ''
           )
      );
  }

  /**
   *
   *
   * @param Job $job
   * @return Job
   */
  public function updateJob(Job $job) {
    return $this->__soapCall('updateJob', array($job),       array(
            'uri' => 'http://www.jasperforge.org/jasperserver/ws',
            'soapaction' => ''
           )
      );
  }

  /**
   *
   *
   * @param
   * @return ArrayOfJobSummary
   */
  public function getAllJobs() {
    return $this->__soapCall('getAllJobs', array(),       array(
            'uri' => 'http://www.jasperforge.org/jasperserver/ws',
            'soapaction' => ''
           )
      );
  }

}

?>
