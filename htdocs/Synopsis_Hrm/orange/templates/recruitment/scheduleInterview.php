<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

$noOfEmployees = $records['noOfEmployees'];
$employeeSearchList = $records['employeeSearchList'];
$application = $records['application'];
$num = $records['interview'];
$locRights=$_SESSION['localRights'];

$baseURL = "{$_SERVER['PHP_SELF']}?recruitcode={$_GET['recruitcode']}";
$action = ($num == 1) ? JobApplication::ACTION_SCHEDULE_FIRST_INTERVIEW : JobApplication::ACTION_SCHEDULE_SECOND_INTERVIEW;
$formAction = $baseURL . '&action=' . $action;

$picDir = "../../themes/{$styleSheet}/pictures/";
$iconDir = "../../themes/{$styleSheet}/icons/";

$backImg = $picDir . 'btn_back.gif';
$backImgPressed = $picDir . 'btn_back_02.gif';
?>
<html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="../../scripts/archive.js"></script>
<script type="text/javascript" src="../../scripts/octopus.js"></script>
<?php include ROOT_PATH."/lib/common/calendar.php"; ?>
<script>
	var employeeSearchList = new Array();
	
    var dateTimeFormat = YAHOO.OrangeHRM.calendar.format + " " + YAHOO.OrangeHRM.time.format;
    var firstInterviewDate = false;

<?php
    if ($num == 2) {
        $event = $application->getEventOfType(JobApplicationEvent::EVENT_SCHEDULE_FIRST_INTERVIEW);
        if ($event) {
            $eventTime = $event->getEventTime();
            if (!empty($eventTime)) {
                echo "\tfirstInterviewDate = '" . LocaleUtil::getInstance()->formatDateTime($eventTime) . "';";
           }
        }
    }
?>


    function goBack() {
        location.href = "<?php echo $baseURL; ?>&action=List";
    }

    /**
     * Check if second interview date is after the first interview date
     */
    function secondInterviewAfterFirst() {

        if (firstInterviewDate) {

            timeVal = strToTime(firstInterviewDate, dateTimeFormat);
            if (timeVal) {
                newTime = $('txtDate').value.trim() + " " + $('txtTime').value.trim();
                newTimeVal = strToTime(newTime, dateTimeFormat);

                if (newTimeVal && newTimeVal < timeVal) {
                    return true;
                }
            }
        }
        return false;
    }

	function validate() {

		err = false;
		msg = '<?php echo $lang_Error_PleaseCorrectTheFollowing; ?>\n\n';

        if ($('txtDate').value.trim() == '') {
            err = true;
            msg += "\t- <?php echo $lang_Recruit_JobApplication_PleaseSpecifyDate; ?>\n";
        } else {
            dateVal = strToDate($("txtDate").value, YAHOO.OrangeHRM.calendar.format);
            if (!dateVal) {
                err = true;
                msg += "\t- <?php echo $lang_Recruit_JobApplication_PleaseSpecifyValidDate; ?>" + YAHOO.OrangeHRM.calendar.format + "\n";
            }
        }

        timeVal = $('txtTime').value.trim();
        if (timeVal == '') {
            err = true;
            msg += "\t- <?php echo $lang_Recruit_JobApplication_PleaseSpecifyTime; ?>\n";
        } else {
            dateNow = formatDate(new Date(), YAHOO.OrangeHRM.calendar.format);
            timeVal = strToTime(dateNow + " " + timeVal, dateTimeFormat);
            if (!timeVal) {
                err = true;
                msg += "\t- <?php echo $lang_Recruit_JobApplication_PleaseSpecifyValidTime; ?>" + YAHOO.OrangeHRM.time.format + "\n";
            }
        }

        if (!err) {
            if (secondInterviewAfterFirst()) {
                err = true;
                msg += "\t- <?php echo $lang_Recruit_JobApplication_SecondInterviewShouldBeAfterFirst; ?>(" + firstInterviewDate + ")\n";
            }
        }

		errors = new Array();
        if ($('cmbInterviewer').value == -1) {
			err = true;
			msg += "\t- <?php echo $lang_Recruit_JobApplication_PleaseSpecifyInterviewer; ?>\n";
        }

		if (err) {
			alert(msg);
			return false;
		} else {
			return true;
		}
	}

    function save() {
    
    	$('cmbInterviewer').value = '-1';
    	
    	for (i in employeeSearchList) {
    		if ($('txtInterviewerSearch').value == employeeSearchList[i][0]) {
    			$('cmbInterviewer').value = employeeSearchList[i][2];
    			break;
    		}
    	}

		if (validate()) {
        	$('frmInterview').submit();
		} else {
			return false;
		}
    }

	function reset() {
		$('frmInterview').reset();
	}
	
	function showAutoSuggestTip(obj) {
		if (obj.value == '<?php echo $lang_Common_TypeHereForHints; ?>') {
			obj.value = '';
			obj.style.color = '#000000';
		}
	}	
	
YAHOO.OrangeHRM.container.init();
</script>

    <link href="../../themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">
    <style type="text/css">@import url("../../themes/<?php echo $styleSheet;?>/css/style.css"); </style>

    <style type="text/css">
    <!--

	.items {
		border-top: none;
		border-left: solid 1px #999999;
		border-right: solid 1px #999999;
		border-bottom: solid 1px #999999;
		padding: 4px;
		display: none;
		width: 240px;
	}

	#container {
		 display: table-row !important;
	}
	
	#dropdownPane {
		display: table-cell;
		border: none !important;
		text-align: left !important;
	}

	#txtEnhancedSearchBox {
		display: block;
		border-top: solid 1px #000000;
		border-left: solid 1px #000000;
		border-right: solid 1px #000000;
		border-bottom: solid 1px #000000;
	}
	
    label,select,input,textarea {
        display: block;  /* block float the labels to left column, set a width */
        width: 150px;
        float: left;
        margin: 10px 0px 2px 0px; /* set top margin same as form input - textarea etc. elements */
    }
    input[type="checkbox"] {
		width: 15px;
		background-color: transparent;
		vertical-align: bottom;
    }

    /* this is needed because otherwise, hidden fields break the alignment of the other fields */
    input[type="hidden"] {
        display: none;
        border: none;
        background-color: red;
    }

    label {
        text-align: left;
        width: 110px;
        padding-left: 10px;
    }

    select,input,textarea {
        margin-left: 10px;
    }

    input,textarea {
        padding-left: 4px;
        padding-right: 4px;
    }

    textarea {
        width: 330px;
        height: 150px;
    }

    form {
        min-width: 550px;
        max-width: 600px;
    }

    br {
        clear: left;
    }

    .roundbox {
        margin-top: 10px;
        margin-left: 0px;
        width: 500px;
    }

    .roundbox_content {
        padding:15px;
    }

	.hidden {
		display: none;
	}

	.display-block {
		display: block;
	}

    .calendarBtn {
        width: auto;
        border-style: none !important;
        border: 0px !important;
    }

    #txtDate, #txtTime {
        width: 100px;
    }

	#nohiringmanagers {
		font-style: italic;
		color: red;
        padding-left: 10px;
        width: 400px;
        border: 1px;
	}

	#container {
		display: table-row !important;
	}

	#dropdownPane {
		display: table-cell;
		border: none !important;
		text-align: left !important;
	}
	
	#employeeSearchAC {
 	    width:15em; /* set width here */
 	    padding-bottom:2em;
 	}
	
 	#employeeSearchAC {
 	    z-index:9000; /* z-index needed on top instance for ie & sf absolute inside relative issue */
 	}
	
    -->
	</style>
<?php include ROOT_PATH."/lib/common/autocomplete.php"; ?>
</head>
<body class="yui-skin-sam">
	<p>
		<table width='100%' cellpadding='0' cellspacing='0' border='0' class='moduleTitle'>
			<tr>
		  		<td width='100%'>
		  			<h2>

<?php
$applicantName = $application->getFirstName() . ' ' . $application->getLastName();
    if ($num == 1) {
        $heading = $lang_Recruit_JobApplication_ScheduleFirstInterview;
    } else {
        $heading = $lang_Recruit_JobApplication_ScheduleSecondInterview;
    }
    echo $heading . ' ' . $applicantName;
?>
                    </h2>
		  		</td>
	  			<td valign='top' align='right' nowrap style='padding-top:3px; padding-left: 5px;'></td>
	  		</tr>
		</table>
	</p>
  	<div id="navigation" style="margin:0;">
  		<img title="Back" onMouseOut="this.src='<?php echo $backImg;?>';"
  			 onMouseOver="this.src='<?php echo $backImgPressed;?>'"
  			 src="<?php echo $backImg;?>" onClick="goBack();">
	</div>
    <?php $message =  isset($_GET['message']) ? $_GET['message'] : null;
    	if (isset($message)) {
			$col_def = CommonFunctions::getCssClassForMessage($message);
			$message = "lang_Common_" . $message;
	?>
	<div class="message">
		<font class="<?php echo $col_def?>" size="-1" face="Verdana, Arial, Helvetica, sans-serif">
			<?php echo (isset($$message)) ? $$message: ""; ?>
		</font>
	</div>
	<?php }	?>
  <div class="roundbox">

  <form name="frmInterview" id="frmInterview" method="post" action="<?php echo $formAction;?>">
		<?php
			$prevEmpNum = '-1';
			$empName = $lang_Common_TypeHereForHints;
		?>
		<input type="hidden" id="txtId" name="txtId" value="<?php echo $application->getId();?>"/><br/>
		<input type="hidden" name="cmbInterviewer" id="cmbInterviewer" value="<?php echo $prevEmpNum ?>" />

        <label for="txtDate"><span class="error">*</span> <?php echo $lang_Recruit_JobApplication_Schedule_Date; ?></label>
        <input type="text" id="txtDate" name="txtDate" value="" size="10" tabindex="1" />
        <input type="button" id="btnToDate" name="btnToDate" value="  " class="calendarBtn"/><br/>

        <label for="txtTime"><span class="error">*</span> <?php echo $lang_Recruit_JobApplication_Schedule_Time; ?></label>
        <input type="text" id="txtTime" name="txtTime" tabindex="2" /><br/>

        <div>
		<label for="container"><span class="error">*</span> <?php echo $lang_Recruit_JobApplication_Schedule_Interviewer; ?></label>
		<div class="yui-ac" id="employeeSearchAC" style="float: left">
 	 		      <input autocomplete="off" class="yui-ac-input" id="txtInterviewerSearch" type="text" value="<?php echo $empName ?>" tabindex="3"  onfocus="showAutoSuggestTip(this)" style="color: #999999" />
 	 		      <div class="yui-ac-container" id="employeeSearchACContainer" style="top: 28px; left: 10px;">
 	 		        <div style="display: none; width: 159px; height: 0px; left: 100em" class="yui-ac-content">
 	 		          <div style="display: none;" class="yui-ac-hd"></div>
 	 		          <div class="yui-ac-bd">
 	 		            <ul>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		              <li style="display: none;"></li>
 	 		            </ul>
 	 		          </div>
 	 		          <div style="display: none;" class="yui-ac-ft"></div>
 	 		        </div>
 	 		        <div style="width: 0pt; height: 0pt;" class="yui-ac-shadow"></div>
 	 	      </div>
    	</div>
    	</div>
        
        <br/>
		<?php
				if ($noOfEmployees == 0) {
		?>
			<div id="nohiringmanagers">
				<?php echo $lang_Recruit_NoHiringManagersNotice; ?>
			</div>
		<?php
				}
		?>
		<label for="txtNotes">&nbsp;<?php echo $lang_Recruit_JobApplication_Schedule_Notes; ?></label>
        <textarea id="txtNotes" name="txtNotes" tabindex="4"></textarea><br/>
		<br/><br/>

        <div align="left">
            <img onClick="save();" id="saveBtn"
            	onMouseOut="this.src='<?php echo $picDir;?>btn_save.gif';"
                onMouseOver="this.src='<?php echo $picDir;?>btn_save_02.gif';"
            	src="<?php echo $picDir;?>btn_save.gif">
        </div>
	</form>
    </div>
    <script type="text/javascript">
        <!--
        	if (document.getElementById && document.createElement) {
   	 			initOctopus();
			}

			<?php 
				$i = 0; 
				
				foreach ($employeeSearchList as $record) {
			?>
				employeeSearchList[<?php echo $i++; ?>] = new Array('<?php echo implode("', '", $record); ?>');
			<?php 
				}
			?>
			
			YAHOO.OrangeHRM.autocomplete.ACJSArray = new function() {
					
				// Instantiate second JS Array DataSource 
			    this.oACDS = new YAHOO.widget.DS_JSArray(employeeSearchList); 
			 
			    // Instantiate second AutoComplete 
			    this.oAutoComp = new YAHOO.widget.AutoComplete('txtInterviewerSearch','employeeSearchACContainer', this.oACDS); 
			    this.oAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
			    this.oAutoComp.typeAhead = false; 
			    this.oAutoComp.useShadow = true; 
			    this.oAutoComp.forceSelection = true; 
			    this.oAutoComp.formatResult = function(oResultItem, sQuery) { 
			        var sMarkup = oResultItem[0] + "<br />" + oResultItem[1] .fontsize(-1).fontcolor('#999999')  + "&nbsp;";
			        return (sMarkup);
			    };
		    
 	 		};
        -->
    </script>

    <div id="notice"><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</div>
    <div id="cal1Container" style="position:absolute;" ></div>
</body>
</html>
