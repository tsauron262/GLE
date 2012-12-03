jQuery(function() {
    swfobject.embedSWF(
    DOL_URL_ROOT+'/Babel_Common/open-flash-chart/open-flash-chart.swf', "ProjetMonActiviteEffective_chart1", "400px", "400px",
    "9.0.0", "expressInstall.swf",
    {"data-file":DOL_URL_ROOT+"/projet/activity/myactivity-pie-chart.php?dur=effective&userId="+userId} );
});