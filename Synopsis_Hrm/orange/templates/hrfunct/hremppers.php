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

        <table height="200" border="0" cellpadding="0" cellspacing="0">
        <tr>
                    <td><font color=#ff0000>*</font><?php echo $lang_hremp_ssnno?></td>
                    <td><input type="text" name="txtNICNo" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtNICNo']))?$this->postArr['txtNICNo']:''?>"></td>
                    <td width="50">&nbsp;</td>
                    <td><?php echo $lang_hremp_nationality?></td>
                    <td><select <?php echo $locRights['add'] ? '':'disabled'?> name="cmbNation">
                        <option value="0"><?php echo $lang_hremp_selectnatio?></option>
<?php
                    $nation = $this->popArr['nation'];
                    for($c=0;$nation && $c < count($nation);$c++)
                                    echo '<option value=' . $nation[$c][0] . '>' . $nation[$c][1] .'</option>';
?>
                    </select></td>
        </tr>
                <tr>
                <td><?php echo $lang_hremp_sinno?></td>
                    <td><input type="text" name="txtSINNo" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['txtSINNo']))?$this->postArr['txtSINNo']:''?>"></td>
                    <td width="50">&nbsp;</td>
                <td><?php echo $$lang_hremp_dateofbirth?></td>
                <td><input type="text" name="DOB" <?php echo $locRights['add'] ? '':'disabled'?> value="<?php echo (isset($this->postArr['DOB']))?LocaleUtil::getInstance()->formatDate($this->postArr['DOB']):''?>" />
                    <input type="button" <?php echo $locRights['add'] ? '':'disabled'?> class="button" value="   " /></td>
                </tr>
                <tr>
                <td><?php echo $lang_hremp_otherid?></td>
                <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtOtherID" value="<?php echo (isset($this->postArr['txtOtherID']))?$this->postArr['txtOtherID']:''?>"></td>
                <td>&nbsp;</td>
                <td><?php echo $lang_hremp_maritalstatus?></td>
                <td><select <?php echo $locRights['add'] ? '':'disabled'?> name="cmbMarital">
                    <option><?php echo $$lang_hremp_selmarital?></option>
<?php
                    for($c=0;count($arrMStat)>$c;$c++)
                        if(isset($this->postArr['cmbMarital']) && $this->postArr['cmbMarital']==$arrMStat[$c])
                            echo "<option selected>" .$arrMStat[$c]."</option>";
                        else
                            echo "<option>" .$arrMStat[$c]."</option>";
?>
                </select></td>
                </tr>
                <tr>
                <td></td>
            <td><input type="hidden" <?php echo $locRights['add'] ? '':'disabled'?> name="chkSmokeFlag" <?php echo (isset($this->postArr['chkSmokeFlag']) && $this->postArr['chkSmokeFlag']=='1'?'checked':'')?> value="1"></td>
            <td width="50">&nbsp;</td>
                <td><?php echo $lang_hremp_gender?></td>
                <td valign="middle"><?php echo $lang_Common_Male;?><input <?php echo $locRights['add'] ? '':'disabled'?> type="radio" name="optGender" value="1" checked><?php echo $lang_Common_Female;?><input <?php echo $locRights['add'] ? '':'disabled'?> type="radio" name="optGender" value="2" <?php echo (isset($this->postArr['optGender']) && isset($this->postArr['optGender'])==2)?'checked':''?>></td>
                </tr>
                <tr>
                <td><?php echo $lang_hremp_dlicenno?></td>
                <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtLicenNo" value="<?php echo (isset($this->postArr['txtLicenNo']))?$this->postArr['txtLicenNo']:''?>"></td>
                <td>&nbsp;</td>
                <td><?php echo $lang_hremp_licexpdate?></td>
                <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtLicExpDate" value="<?php echo (isset($this->postArr['txtLicExpDate']))?LocaleUtil::getInstance()->formatDate($this->postArr['txtLicExpDate']):''?>" />
                    <input type="button" <?php echo $locRights['add'] ? '':'disabled'?> class="button" value="   " />
                </td>
                </tr>
                <tr>
                <td><?php echo $lang_hremp_militaryservice?></td>
                <td><input type="text" <?php echo $locRights['add'] ? '':'disabled'?> name="txtMilitarySer" value="<?php echo (isset($this->postArr['txtMilitarySer']))?$this->postArr['txtMilitarySer']:''?>"></td>
                <td>&nbsp;</td>
                <td></td>
                    <td><input type='hidden' <?php echo $locRights['add'] ? '':'disabled'?> name="cmbEthnicRace">
                </tr>
</table>

<?php } if(isset($this->getArr['capturemode']) && $this->getArr['capturemode'] == 'updatemode') { ?>

    <table onclick="setUpdate(1)" onkeypress="setUpdate(1)" height="200" border="0" cellpadding="0" cellspacing="0">
    <?php
            $edit = $this->popArr['editPersArr'];
    ?>

          <tr>

    <td><?php echo $lang_hremp_ssnno?></td>
                    <td><input type="text" name="txtNICNo" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> value="<?php echo (isset($this->postArr['txtNICNo']))?$this->postArr['txtNICNo']:$edit[0][7]?>">
                    <?php if (isset($_GET['reqcode']) && ($_GET['reqcode'] === "ESS")) { ?>
                    <input type="hidden" name="txtNICNo" value="<?php echo (isset($this->postArr['txtNICNo']))?$this->postArr['txtNICNo']:$edit[0][7]?>" />
                    <?php } ?>
                    </td>
                    <td width="50">&nbsp;</td>
                    <td><?php echo $lang_hremp_nationality?></td>
                    <td><select <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="cmbNation">
                        <option value="0"><?php echo $lang_hremp_selectnatio; ?></option>
<?php
                    $nation = $this->popArr['nation'];
                    for($c=0;$nation && count($nation)>$c;$c++)
                        if(isset($this->postArr['cmbNation'])) {
                            if($this->postArr['cmbNation']==$nation[$c][0])
                                echo "<option selected value='" . $nation[$c][0] . "'>" .$nation[$c][1]. "</option>";
                            else
                                echo "<option value='" . $nation[$c][0] . "'>" .$nation[$c][1]. "</option>";
                        } elseif($edit[0][4]==$nation[$c][0])
                                echo "<option selected value='" . $nation[$c][0] . "'>" .$nation[$c][1]. "</option>";
                            else
                                echo "<option value='" . $nation[$c][0] . "'>" .$nation[$c][1]. "</option>";

?>
                    </select></td>
                </tr>
                <tr>
                <td><?php echo $lang_hremp_sinno?></td>
                    <td><input type="text" name="txtSINNo" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> value="<?php echo (isset($this->postArr['txtSINNo']))?$this->postArr['txtSINNo']:$edit[0][8]?>">
                    <?php if (isset($_GET['reqcode']) && ($_GET['reqcode'] === "ESS")) { ?>
                    <input type="hidden" name="txtSINNo" value="<?php echo (isset($this->postArr['txtSINNo']))?$this->postArr['txtSINNo']:$edit[0][8]?>" />
                    <?php } ?>
                    </td>
                    <td width="50">&nbsp;</td>
                <td><?php echo $lang_hremp_dateofbirth?></td>
                <td nowrap>
                    <input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="DOB" id="DOB" value="<?php echo (isset($this->postArr['DOB']))?LocaleUtil::getInstance()->formatDate($this->postArr['DOB']):LocaleUtil::getInstance()->formatDate($edit[0][3]); ?>" size="10" />
                    <input type="button" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?>  value="  " class="calendarBtn" />
                </td>
                </tr>
                <tr>
                <td><?php echo $lang_hremp_otherid?></td>
                <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtOtherID" value="<?php echo (isset($this->postArr['txtOtherID']))?$this->postArr['txtOtherID']:$edit[0][9]?>">
                </td>
                <td>&nbsp;</td>
                <td><?php echo $lang_hremp_maritalstatus?></td>
                <td><select <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="cmbMarital">
                    <option value="0"><?php echo $lang_hremp_selmarital?></option>
<?php
                    for($c=0;count($arrMStat)>$c;$c++)

                        if(isset($this->postArr['cmbMarital'])) {
                            if($this->postArr['cmbMarital']==$arrMStat[$c])
                            {
                                echo "<option selected>" .$arrMStat[$c]."</option>";
                            }
                        else
                            echo "<option>" .$arrMStat[$c]."</option>";
                        } elseif($edit[0][6]==$arrMStat[$c])
                                    echo "<option selected>" .$arrMStat[$c]."</option>";
                                else
                                    echo "<option>" .$arrMStat[$c]."</option>";
?>
                </select></td>
                </tr>
                <tr>
            <td>
<?php
            if(isset($this->postArr['chkSmokeFlag'])) { ?>
            <input type="hidden" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="chkSmokeFlag" <?php echo $this->postArr['chkSmokeFlag']=='1'?'checked':''?> value="1">
<?php             } else { ?>
            <input type="hidden" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="chkSmokeFlag" <?php echo $edit[0][1]==1?'checked':''?> value="1">
<?php } ?>              </td>
                <td>&nbsp;</td>
                <td><?php echo $lang_hremp_gender?></td>
                <td valign="middle"><?php echo $lang_Common_Male;?><input <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> type="radio" name="optGender" value="1" checked>

<?php                if(isset($this->postArr['optGender'])) { ?>
                <?php echo $lang_Common_Female;?><input type="radio" name="optGender" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> value="2" <?php echo ($this->postArr['optGender']==2)?'checked':''?>></td>

<?php                } else {  ?>
                <?php echo $lang_Common_Female;?><input type="radio" name="optGender" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> value="2" <?php echo ($edit[0][5]==2)?'checked':''?>>

                </td>
<?php } ?>
                </tr>
                <tr>
                <td><?php echo $lang_hremp_dlicenno?></td>
                <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtLicenNo" value="<?php echo (isset($this->postArr['txtLicenNo']))?$this->postArr['txtLicenNo']:$edit[0][10]?>">
                    <?php if (isset($_GET['reqcode']) && ($_GET['reqcode'] === "ESS")) { ?>
                    <input type="hidden" name="txtLicenNo" value="<?php echo (isset($this->postArr['txtLicenNo']))?$this->postArr['txtLicenNo']:$edit[0][10]?>" />
                    <?php } ?>
                    </td>
                <td>&nbsp;</td>
                <td><?php echo $lang_hremp_licexpdate?></td>
                <td nowrap>
                <input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtLicExpDate" id="txtLicExpDate" value="<?php echo (isset($this->postArr['txtLicExpDate']))?LocaleUtil::getInstance()->formatDate($this->postArr['txtLicExpDate']):LocaleUtil::getInstance()->formatDate($edit[0][11]); ?>" size="10" />
                <input type="button" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> value="  " class="calendarBtn" name="btnLicExpDate"></td></tr>
                <tr>
                <td><?php echo $lang_hremp_militaryservice?></td>
                <td><input type="text" <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="txtMilitarySer" value="<?php echo (isset($this->postArr['txtMilitarySer']))?$this->postArr['txtMilitarySer']:$edit[0][12]?>"></td>
                <td>&nbsp;</td>
                <td></td>
                    <td><input type='hidden' <?php echo (isset($this->postArr['EditMode']) && $this->postArr['EditMode']=='1') ? '' : 'disabled'?> name="cmbEthnicRace">
                     </td>
                </tr>
                </table>
<?php } ?>
