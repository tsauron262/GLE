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
 *
 */
?>
<script language="JavaScript">

function dwPopup() {
        var popup=window.open('../../templates/hrfunct/download.php?id=<?php echo isset($this->getArr['id']) ? $this->getArr['id'] : ''?>&ATTACH=<?php echo isset($this->getArr['ATTACH']) ? $this->getArr['ATTACH'] : ''?>','Downloads');
        if(!popup.opener) popup.opener=self;
}

function delAttach() {

	var check = false;
	with (document.frmEmp) {
		for (var i=0; i < elements.length; i++) {
			if ((elements[i].name == 'chkattdel[]') && (elements[i].checked == true)){
				check = true;
			}
		}
	}

	if(!check){
		alert('<?php echo $lang_hremp_SelectAtLEastOneAttachment; ?>')
		return;
	}

	document.frmEmp.attSTAT.value="DEL";
	qCombo(6);
}

function addAttach() {
	var fileName = document.frmEmp.ufile.value;
	fileName = trim(fileName);
	if (fileName == "") {
		alert("<?php echo $lang_hremp_PleaseSelectFile; ?>");
		return;
	}
	document.frmEmp.attSTAT.value="ADD";
	qCombo(6);
}

function viewAttach(att) {
	document.frmEmp.action=document.frmEmp.action + "&ATTACH=" + att;
	document.frmEmp.pane.value=6;
	document.frmEmp.submit();
}

function editAttach() {
	document.frmEmp.attSTAT.value="EDIT";
	qCombo(6);
}

<?php
	if(isset($_GET['ATT_UPLOAD']) && $_GET['ATT_UPLOAD'] == 'FAILED')
		echo "alert('" .$lang_lang_uploadfailed."');";
?>
</script>
<span id="parentPaneAttachments" >
<?php if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>
<?php		if(isset($this->getArr['ATTACH'])) {
				$edit = $this->popArr['editAttForm'];
		 		$disabled = ($locRights['edit']) ? "" : "disabled";
?>
	<div id="editPaneAttachments" >
       <input type="hidden" name="seqNO" value="<?php echo $edit[0][1]?>">
       <table width="352" height="120" border="0" cellpadding="0" cellspacing="0">
              <tr>
              	<td><?php echo $lang_hremp_filename?></td>
              	<td><?php echo $edit[0][3];?></td>
              </tr>
              <tr>
              	<td><?php echo $lang_Commn_description?></td>
              	<td><textarea name="txtAttDesc" <?php echo $disabled; ?> ><?php echo $edit[0][2]?></textarea></td>
              </tr>
              <tr>
              	<td>
              		<input type="button" value="<?php echo $lang_hremp_ShowFile; ?>"
              		class="button" onclick="dwPopup()">
              	</td>
              </tr>
			  <tr>
				<td>&nbsp;</td>
				<td>
<?php	if ($locRights['edit']) { ?>
        <img border="0" title="<?php echo $lang_hremp_Save; ?>" onClick="editAttach();" onmouseout="this.src='../../themes/beyondT/pictures/btn_save.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
<?php	} ?>
				</td>
				</tr>
		</table>
	</div>
<?php } else if ($locRights['add']) { ?>
<div id="addPaneAttachments" class="<?php echo ($this->popArr['empAttAss'] != null)?"addPane":""; ?>" >
	  <table width="352" height="120" border="0" cellpadding="0" cellspacing="0">
          <tr>
				<td valign="top"><?php echo $lang_hremp_path?></td>
				<td><input type="hidden" name="MAX_FILE_SIZE" value="1048576" />
					<input type="file" name="ufile"> <br>[<?php echo $lang_hremp_largefileignore?>]</td>
              </tr>
              <tr>
              	<td><?php echo $lang_Commn_description?></td>
              	<td><textarea name="txtAttDesc"></textarea></td>
              </tr>
			  <tr>
				<td>&nbsp;</td>
				<td>
        <img border="0" title="<?php echo $lang_hremp_Save; ?>" onClick="addAttach();" onmouseout="this.src='../../themes/beyondT/pictures/btn_save.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_save_02.gif';" src="../../themes/beyondT/pictures/btn_save.gif">
				</td>
			  </tr>
	   </table>
	 </div>
<?php } ?>
<?php
	$rset = $this->popArr['empAttAss'] ;
	if ($rset != null){ ?>
		<h3><?php echo $lang_hrEmpMain_assignattach?></h3>
<?php if ($locRights['add']) { ?>
		<img border="0" title="Add" onClick="showAddPane('Attachments');" onMouseOut="this.src='../../themes/beyondT/pictures/btn_add.gif';" onMouseOver="this.src='../../themes/beyondT/pictures/btn_add_02.gif';" src="../../themes/beyondT/pictures/btn_add.gif" />
<?php } ?>
<?php	if($locRights['delete']) { ?>
        <img title="<?php echo $lang_hremp_Delete; ?>" onclick="delAttach();" onmouseout="this.src='../../themes/beyondT/pictures/btn_delete.gif';" onmouseover="this.src='../../themes/beyondT/pictures/btn_delete_02.gif';" src="../../themes/beyondT/pictures/btn_delete.gif">
<?php 	} ?>

		<table border="0" width="450" align="center" class="tabForm">
			<tr>
                <td></td>
				<td><strong><?php echo $lang_hremp_filename?></strong></td>
				<td><strong><?php echo $lang_Commn_description?></strong></td>
				<td><strong><?php echo $lang_hremp_size?></strong></td>
				<td><strong><?php echo $lang_hremp_type?></strong></td>
			</tr>
<?php

	$disabled = ($locRights['delete']) ? "" : "disabled";
    for($c=0;$rset && $c < count($rset); $c++) {
?>
        <tr>
            <td><input type='checkbox' $disabled class='checkbox' name='chkattdel[]' value="<?php echo $rset[$c][1]; ?>"></td>
            <td><a href="#" title="<?php echo $rset[$c][2]; ?>" onmousedown="viewAttach('<?php echo $rset[$c][1]; ?>')" ><?php echo $rset[$c][3]; ?></a></td>
            <td><?php echo $rset[$c][2]; ?></td>
            <td><?php echo CommonFunctions::formatSiUnitPrefix($rset[$c][4]); ?>B</td>
            <td><?php echo $rset[$c][6]; ?></td>
        </tr>
<?php
        }
?>
          </table>
<?php } else if (!$locRights['add']) { ?>
	<p><?php echo $lang_empview_norecorddisplay; ?></p>
<?php }?>
<?php } ?>
</span>