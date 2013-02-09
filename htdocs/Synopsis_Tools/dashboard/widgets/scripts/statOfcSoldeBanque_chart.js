jQuery(function() {
    swfobject.embedSWF(
    DOL_URL_ROOT+'/Synopsis_Common/open-flash-chart/open-flash-chart.swf', "statOfcSoldeBanque_chart1", "900px", "400px",
    "9.0.0", "expressInstall.swf",
    {"data-file":DOL_URL_ROOT+"/Synopsis_Tools/dashboard/ajax/stat_statOfcSoldeBanque_json.php?fullSize=1"} );
});