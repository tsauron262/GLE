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

$formAction="{$_SERVER['PHP_SELF']}?uniqcode={$this->getArr['uniqcode']}";
$btnAction="addSave()";
if ((isset($this->getArr['capturemode'])) && ($this->getArr['capturemode'] == 'updatemode')) {
	$formAction="{$formAction}&id={$this->getArr['id']}&capturemode=updatemode";
	$btnAction="addUpdate()";
}
$fieldTypes = array(CustomFields::FIELD_TYPE_STRING => $lang_customeFields_StringType,
	CustomFields::FIELD_TYPE_SELECT => $lang_customeFields_SelectType);
$extraClass = "hidden";

$available = $this->popArr['available'];

?>
<html>
<head>
<title><?php echo $lang_customeFields_Heading; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="../../scripts/archive.js"></script>
<script type="text/javascript" src="../../scripts/octopus.js"></script>
<script>

    function goBack() {
        location.href = "./CentralController.php?uniqcode=<?php echo $this->getArr['uniqcode']?>&VIEW=MAIN";
    }

    function addSave() {

        if (trim($('txtFieldName').value) == '') {
            alert ("<?php echo $lang_Admin_CustomeFields_PleaseSpecifyCustomFieldName; ?>");
            return false;
        }

		if (document.frmCustomField.cmbFieldType.value == <?php echo CustomFields::FIELD_TYPE_SELECT;?>) {
	        if (trim($('txtExtra').value) == '') {
	            alert ("<?php echo $lang_Admin_CustomeFields_PleaseSpecifySelectOptions; ?>");
	            return false;
	        }
		}

        document.frmCustomField.sqlState.value = "NewRecord";
        document.frmCustomField.submit();
    }

  function addUpdate() {

        if (trim($('txtFieldName').value) == '') {
            alert ("<?php echo $lang_Admin_CustomeFields_PleaseSpecifyCustomFieldName; ?>");
            return false;
        }

		if (document.frmCustomField.cmbFieldType.value == <?php echo CustomFields::FIELD_TYPE_SELECT;?>) {
	        if (trim($('txtExtra').value) == '') {
	            alert ("<?php echo $lang_Admin_CustomeFields_PleaseSpecifySelectOptions; ?>");
	            return false;
	        }
		}
		document.frmCustomField.sqlState.value  = "UpdateRecord";
		document.frmCustomField.submit();
	}

	function clearAll() {
		document.frmCustomField.txtFieldName.value='';
		document.frmCustomField.txtExtra.value=''
	}

	function hideextra() {
		if (document.frmCustomField.cmbFieldType.value == <?php echo CustomFields::FIELD_TYPE_SELECT;?>) {
			$('selectOptions').className = 'display-block';
		} else {
			$('selectOptions').className = 'hidden';
		}
	}

</script>

    <link href="../../themes/<?php echo $styleSheet;?>/css/style.css" rel="stylesheet" type="text/css">
    <style type="text/css">@import url("../../themes/<?php echo $styleSheet;?>/css/style.css"); </style>

    <style type="text/css">
    <!--

    label,select,input,textarea {
        display: block;  /* block float the labels to left column, set a width */
        width: 150px;
        float: left;
        margin: 10px 0px 2px 0px; /* set top margin same as form input - textarea etc. elements */
    }

    /* this is needed because otherwise, hidden fields break the alignment of the other fields */
    input[type=hidden] {
        display: none;
        border: none;
        background-color: red;
    }

    label {
        text-align: left;
        width: 75px;
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
        width: 250px;
    }

    form {
        min-width: 550px;
        max-width: 600px;
    }

    br {
        clear: left;
    }

    .version_label {
        display: block;
        float: left;
        width: 150px;
        font-weight: bold;
        margin-left: 10px;
        margin-top: 10px;
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
    -->
</style>
</head>
<body>
	<p>
		<table width='100%' cellpadding='0' cellspacing='0' border='0' class='moduleTitle'>
			<tr>
		  		<td width='100%'>
		  			<h2><?php echo $lang_customeFields_Heading; ?></h2>
		  		</td>
	  			<td valign='top' align='right' nowrap style='padding-top:3px; padding-left: 5px;'></td>
	  		</tr>
		</table>
	</p>
  	<div id="navigation" style="margin:0;">
  		<img title="Back" onMouseOut="this.src='../../themes/beyondT/pictures/btn_back.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_back_02.gif';"  src="../../themes/beyondT/pictures/btn_back.gif" onClick="goBack();">
	</div>
	<font color="red" face="Verdana, Arial, Helvetica, sans-serif">
    <?php
            if (isset($this->getArr['message'])) {
                $expString  = $this->getArr['message'];
                $expString = explode ("%",$expString);
                $length = sizeof($expString);
                for ($x=0; $x < $length; $x++) {
                    echo " " . $expString[$x];
                }
            }
   ?>
   </font>
  <form name="frmCustomField" method="post" action="<?php echo $formAction;?>">
        <input type="hidden" name="sqlState" value="">
        <div class="roundbox">
      <?php if ((isset($this->getArr['capturemode'])) && ($this->getArr['capturemode'] == 'addmode')) { ?>
			<label for="txtId"><?php echo $lang_CustomFields_CustomFieldNumber; ?></label>

			<select id="txtId" name="txtId" tabindex="1">
            	<?php foreach ($available as $av) {?>
            	<option value="<?php echo $av;?>"><?php echo $av;?></option>
            	<?php } ?>
			</select><?php if (count($available) == 0) {
							 echo $lang_Admin_CustomeFields_MaxCustomFieldsCreated;
			}?>
			<br/>

			<label for="txtFieldName"><span class="error">*</span> <?php echo $lang_customeFields_FieldName; ?></label>
            <input type="text" id="txtFieldName" name="txtFieldName" tabindex="2"/>
			<br/>
            <label for="cmbFieldType"><span class="error">*</span> <?php echo $lang_customeFields_Type; ?></label>
            <select name="cmbFieldType" id="cmbFieldType" tabindex="3" onchange="hideextra();">
            	<?php foreach ($fieldTypes as $key=>$fieldType) {?>
            	<option value="<?php echo $key;?>"><?php echo $fieldType;?></option>
            	<?php } ?>
            </select><br/>
            <div id="selectOptions" class="<?php echo $extraClass; ?>">
			<label for="txtExtra"><?php echo $lang_customeFields_SelectOptions; ?></label>
            <input type="text" id="txtExtra" name="txtExtra" tabindex="2"/>
            <div id="notice">&nbsp;<?php echo $lang_Admin_CustomeFields_SelectOptionsHint; ?></div>
            </div>
            <br>
       <?php } else if ((isset($this->getArr['capturemode'])) && ($this->getArr['capturemode'] == 'updatemode')) {

			$message = $this->popArr['editArr'];
			if ($message->getFieldType() == CustomFields::FIELD_TYPE_SELECT) {
				$extraClass = "display-block";
			}
		?>
			<label for="txtId"><?php echo $lang_customeFields_FieldName; ?></label>
			<input type="text" id="txtId" name="txtId" value="<?php echo $message->getFieldNumber(); ?>" tabindex="1" readonly/>
            <br/>
			<label for="txtFieldName"><span class="error">*</span> <?php echo $lang_customeFields_FieldName; ?></label>
            <input type="text" id="txtFieldName" name="txtFieldName" value="<?php echo $message->getName(); ?>" tabindex="2"/>
			<br/>
            <label for="cmbFieldType"><?php echo $lang_customeFields_Type; ?></label>
            <select name="cmbFieldType" id="cmbFieldType" tabindex="3" onchange="hideextra();">
            	<?php foreach ($fieldTypes as $key=>$fieldType) {
            			$selected = ($message->getFieldType() == $key)? "selected" : "";
            	?>
            	<option <?php echo $selected; ?> value="<?php echo $key;?>"><?php echo $fieldType;?></option>
            	<?php } ?>
            </select><br/>
            <div id="selectOptions" class="<?php echo $extraClass; ?>">
			<label for="txtExtra"><?php echo $lang_customeFields_SelectOptions; ?></label>
            <input type="text" id="txtExtra" name="txtExtra" tabindex="2" value="<?php echo $message->getExtraData();?>"/>
            <div id="notice">&nbsp;<?php echo $lang_Admin_CustomeFields_SelectOptionsHint; ?></div>
            </div>
			<br/>
		<?php } ?>
            <div align="center">
	            <img onClick="<?php echo $btnAction; ?>;" onMouseOut="this.src='../../themes/beyondT/pictures/btn_save.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
				<img src="../../themes/beyondT/pictures/btn_clear.gif" onMouseOut="this.src='../../themes/beyondT/pictures/btn_clear.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_clear_02.gif';" onClick="clearAll();" >
            </div>
        </div>
    </form>
    <script type="text/javascript">
        <!--
        	if (document.getElementById && document.createElement) {
   	 			initOctopus();
			}
        -->
    </script>
    <div id="notice"><?php echo preg_replace('/#star/', '<span class="error">*</span>', $lang_Commn_RequiredFieldMark); ?>.</div>
</body>
</html>
