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
<?php if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'addmode') { ?>
	<table height="250" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td><?php echo $lang_compstruct_country?></td>
			 <td><select <?php echo $locRights['add'] ? '':'disabled'?> name="cmbCountry" onChange="document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait; ?>....'; xajax_populateStates(this.value);">
						  		<option value="0">-- <?php echo $lang_districtinformation_selectcounlist?> --</option>
<?php		$cntlist = $this->popArr['cntlist'];
							    		for($c=0; $cntlist && count($cntlist)>$c ;$c++)
							    			if($editArr['COUNTRY'] == $cntlist[$c][0])
							    				echo "<option selected value='" . $cntlist[$c][0] . "'>" . $cntlist[$c][1] . "</option>";
							    			else
							    				echo "<option value='" . $cntlist[$c][0] . "'>" . $cntlist[$c][1] . "</option>";
							    ?>
			 </select></td>
			   <td width="50">&nbsp;</td>
			   <td><?php echo $lang_hremp_street1?></td>
			 <td><input type="text" name="txtStreet1" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtStreet1']))?$this->postArr['txtStreet1']:''?>"></td>
	  </tr>
			 <tr>
			 <td><?php echo $lang_compstruct_state?></td>
			 <td><div id="lrState" name="lrState">
					<?php if (isset($editArr['COUNTRY']) && ($editArr['COUNTRY'] == 'US')) { ?>
							<select name="txtState" id="txtState" disabled>
							    	<option value="0">--- <?php echo $lang_districtinformation_selstatelist?> ---</option>
							     	<?php	$statlist = $this->popArr['provlist'];
							    		for($c=0; $statlist && count($statlist)>$c ;$c++)
							    			if($editArr['STATE'] == $statlist[$c][1])
							    				echo "<option selected value='" . $statlist[$c][1] . "'>" . $statlist[$c][2] . "</option>";
							    			else
							    				echo "<option value='" . $statlist[$c][1] . "'>" . $statlist[$c][2] . "</option>";
							    	?>
	    	   </select>
							    	<?php } else { ?>
							    	<input type="text" disabled name="txtState" id="txtState" value="<?php echo isset($editArr['STATE']) ? $editArr['STATE'] : ''?>">
							    	<?php } ?>
							    	</div>
							    	<input type="hidden" name="cmbProvince" id="cmbProvince" value="<?php echo isset($editArr['STATE']) ? $editArr['STATE'] : ''?>"></td>
			  <td width="50">&nbsp;</td>
			  <td><?php echo $lang_hremp_street2?></td>
			 <td><input type="text" name="txtStreet2" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtStreet2']))?$this->postArr['txtStreet2']:''?>"></td>
	  </tr>
			 <tr>
			 <td><?php echo $lang_hremp_city?></td>
			 <td><input type="text" name="cmbCity" id="cmbCity" value="<?php echo $edit[0][3]?>" <?php echo $locRights['add'] ? '':'disabled'?> ></td>
			<td width="50">&nbsp;</td>
			 <td><?php echo $lang_compstruct_ZIP_Code?></td>
			 <td><input type="text" name="txtzipCode" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtzipCode']))?$this->postArr['txtzipCode']:''?>"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_hmtele?></td>
			 <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtHmTelep" value="<?php echo (isset($this->postArr['txtHmTelep']))?$this->postArr['txtHmTelep']:''?>"></td>
			 <td width="50">&nbsp;</td>
			<td><?php echo $lang_hremp_mobile?></td>
			 <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtMobile" value="<?php echo (isset($this->postArr['txtMobile']))?$this->postArr['txtMobile']:''?>"></td>
			 </tr>
			 <tr>
			<td><?php echo $lang_hremp_worktele?></td>
			 <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtWorkTelep" value="<?php echo (isset($this->postArr['txtWorkTelep']))?$this->postArr['txtWorkTelep']:''?>"></td>
			 <td width="50">&nbsp;</td>
			 <td><?php echo $lang_hremp_workemail?></td>
			 <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtWorkEmail" value="<?php echo (isset($this->postArr['txtWorkEmail']))?$this->postArr['txtWorkEmail']:''?>"></td>
			 </tr>
			 <tr>
			<td><?php echo $lang_hremp_otheremail?></td>
			 <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtOtherEmail" value="<?php echo (isset($this->postArr['txtOtherEmail']))?$this->postArr['txtOtherEmail']:''?>"></td>
			 </tr>

</table>

<?php } if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>

	<table onclick="setUpdate(4)" onkeypress="setUpdate(4)" height="250" border="0" cellpadding="0" cellspacing="2">
<?php
		$edit = $this->popArr['editPermResArr'];
?>
          <tr>
			  <td><?php echo $lang_compstruct_country?></td>
						  <td colspan="4"><select name="cmbCountry" disabled onchange="document.getElementById('status').innerHTML = '<?php echo $lang_Commn_PleaseWait;?>....'; xajax_populateStates(this.value);">
						  		<option value="0"><?php echo $lang_districtinformation_selectcounlist?></option>
					<?php
								$cntlist = $this->popArr['cntlist'];
								for($c=0;$cntlist && count($cntlist)>$c;$c++)
									if($edit[0][4]==$cntlist[$c][0])
										echo "<option selected value='" .$cntlist[$c][0] . "'>" . $cntlist[$c][1] . '</option>';
									else
										echo "<option value='" .$cntlist[$c][0] . "'>" . $cntlist[$c][1] . '</option>';
					?>
						  </select></td>
      </tr>
		<tr>
			<td><?php echo $lang_hremp_street1?></td>
			<td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtStreet1" value="<?php echo (isset($this->postArr['txtStreet1']))?$this->postArr['txtStreet1']:$edit[0][1]?>"></td>
			 <td width="60">&nbsp;</td>
			 <td><?php echo $lang_hremp_street2?></td>
			  <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtStreet2" value="<?php echo (isset($this->postArr['txtStreet2']))?$this->postArr['txtStreet2']:$edit[0][2]?>"></td>
		</tr>
		 <tr>
			 <td><?php echo $lang_hremp_city?></td>
			 <td><input type="text" name="cmbCity" id="cmbCity" value="<?php echo $edit[0][3]?>" disabled></td>
			<td width="60">&nbsp;</td>
			<td><?php echo $lang_compstruct_state?></td>
						  <td><div id="lrState" name="lrState">
					<?php if (isset($edit[0][4]) && ($edit[0][4] == 'US')) { ?>
							<select name="txtState" id="txtState" disabled>
							    	<option value="0">--- <?php echo $lang_districtinformation_selstatelist?> ---</option>
							     	<?php	$provlist = $this->popArr['provlist'];
							    		for($c=0; $provlist && count($provlist)>$c ;$c++)
							    			if($edit[0][5] == $provlist[$c][1])
							    				echo "<option selected value='" . $provlist[$c][1] . "'>" . $provlist[$c][2] . "</option>";
							    			else
							    				echo "<option value='" . $provlist[$c][1] . "'>" . $provlist[$c][2] . "</option>";
							    	?>
					    	</select>
							    	<?php } else { ?>
							    	<input type="text" disabled name="txtState" id="txtState" value="<?php echo isset($edit[0][5]) ? $edit[0][5] : ''?>">
							    	<?php } ?>
							    	</div>
							    	<input type="hidden" name="cmbProvince" id="cmbProvince" value="<?php echo isset($edit[0][5]) ? $edit[0][5] : ''?>"></td>
			</tr>
			<tr>
			 <td><?php echo $lang_compstruct_ZIP_Code?></td>
			 <td><input type="text" name="txtzipCode" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> value="<?php echo (isset($this->postArr['txtzipCode']))?$this->postArr['txtzipCode']:$edit[0][6]?>"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_hmtele?></td>
			 <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtHmTelep" value="<?php echo (isset($this->postArr['txtHmTelep']))?$this->postArr['txtHmTelep']:$edit[0][7]?>"></td>
			 <td width="60">&nbsp;</td>
			<td><?php echo $lang_hremp_mobile?></td>
			 <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtMobile" value="<?php echo (isset($this->postArr['txtMobile']))?$this->postArr['txtMobile']:$edit[0][8]?>"></td>
			 </tr>
			 <tr>
			 <td><?php echo $lang_hremp_worktele?></td>
			 <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtWorkTelep" value="<?php echo (isset($this->postArr['txtWorkTelep']))?$this->postArr['txtWorkTelep']:$edit[0][9]?>"></td>
			 <td width="60">&nbsp;</td>
			 <td></td>
			 <td></td>
			</tr>
			<tr>
			 <td><?php echo $lang_hremp_workemail?></td>
			 <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtWorkEmail" value="<?php echo (isset($this->postArr['txtWorkEmail']))?$this->postArr['txtWorkEmail']:$edit[0][10]?>"></td>
			  <td width="60">&nbsp;</td>
			 <td><?php echo $lang_hremp_otheremail?></td>
			 <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtOtherEmail" value="<?php echo (isset($this->postArr['txtOtherEmail']))?$this->postArr['txtOtherEmail']:$edit[0][11]?>"></td>
			 </tr>

</table>

<?php } ?>
