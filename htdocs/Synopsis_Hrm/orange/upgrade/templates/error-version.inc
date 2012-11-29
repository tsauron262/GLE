<h2>Sorry! Your Current OrangeHRM Version Is Not Supported</h2>

<p>OrangeHRM 2.4.1 Upgrader supports only version 2.2.2.2 and 2.3. Your version is <?php echo $oldVersion; ?></p>

<form name="frmVersionCheck" method="post" action="./">
<input type="hidden" name="hdnState" value="versionError" />
<input type="submit" name="btnSubmit" value="Back to OrangeHRM Home"  size="80" />
</form>