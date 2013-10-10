jQuery(function() {
    swfobject.embedSWF(
    DOL_URL_ROOT+'/Synopsis_Common/open-flash-chart/open-flash-chart.swf', "service_chart1", "400px", "400px",
    "9.0.0", "expressInstall.swf",
    {"data-file":DOL_URL_ROOT+"/Synopsis_Tools/dashboard/ajax/stat_produit_json.php?type=1&fullSize=1"} );
});