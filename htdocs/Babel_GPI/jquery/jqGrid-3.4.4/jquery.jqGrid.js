function jqGridInclude()
{
    var pathtojsfiles = "jquery/jqGrid-3.4.4/js/"; // need to be ajusted
    // set include to false if you do not want some modules to be included
    var combineIntoOne = false;
    var combinedInclude = new Array();
    var combinedIncludeURL = "combine.php?type=javascript&files=";
    var minver = true;
    var modules = [
        { include: true, incfile:'grid.locale-en.js',minfile: 'grid.locale-en.js'}, // jqGrid translation
        { include: true, incfile:'grid.base.js',minfile: 'grid.base.js'}, // jqGrid base
        { include: true, incfile:'grid.common.js',minfile: 'grid.common.js' }, // jqGrid common for editing
        { include: true, incfile:'grid.formedit.js',minfile: 'grid.formedit.js' }, // jqGrid Form editing
        { include: true, incfile:'grid.inlinedit.js',minfile: 'grid.inlinedit.js' }, // jqGrid inline editing
        { include: true, incfile:'grid.celledit.js',minfile: 'grid.celledit.js' }, // jqGrid cell editing
        { include: true, incfile:'grid.subgrid.js',minfile: 'grid.subgrid.js'}, //jqGrid subgrid
        { include: true, incfile:'grid.treegrid.js',minfile: 'grid.treegrid.js'}, //jqGrid treegrid
        { include: true, incfile:'grid.custom.js',minfile: 'grid.custom.js'}, //jqGrid custom
        { include: true, incfile:'grid.postext.js',minfile: 'grid.postext.js'}, //jqGrid postext
        { include: true, incfile:'grid.tbltogrid.js',minfile: 'grid.tbltogrid.js'}, //jqGrid table to grid
        { include: true, incfile:'grid.setcolumns.js',minfile: 'grid.setcolumns.js'}, //jqGrid setcolumns
        { include: true, incfile:'grid.import.js',minfile: 'grid.import.js'}, //jqGrid import
        { include: true, incfile:'jquery.fmatter.js',minfile: 'jquery.fmatter.js'}, //jqGrid formater
        { include: true, incfile:'json2.js',minfile: 'json2.js'}, //json utils
        { include: true, incfile:'JsonXml.js',minfile: 'JsonXml.js'} //xmljson utils
    ];
    var filename;
    for(var i=0;i<modules.length; i++)
    {
        if(modules[i].include === true) {

            if (minver !== true) filename = pathtojsfiles+modules[i].incfile;
            else filename = pathtojsfiles+modules[i].minfile;
            if (combineIntoOne !== true) {
                if(jQuery.browser.safari || jQuery.browser.msie ) {
                    jQuery.ajax({url:filename,dataType:'script', async:false, cache: true});
                } else {
                    IncludeJavaScript(filename);
                }
            } else {
                combinedInclude[combinedInclude.length] = filename;
            }
        }
    }
    if ((combineIntoOne === true) && (combinedInclude.length>0) ) {
        var fileList = implode(",",combinedInclude);
        IncludeJavaScript(combinedIncludeURL+fileList);
    }
    function implode( glue, pieces ) {
    // http://kevin.vanzonneveld.net
    //original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    //example 1: implode(' ', ['Kevin', 'van', 'Zonneveld']);
    //returns 1: 'Kevin van Zonneveld'
        return ( ( pieces instanceof Array ) ? pieces.join ( glue ) : pieces );
    };

    function IncludeJavaScript(jsFile)
    {
        var oHead = document.getElementsByTagName('head')[0];
        var oScript = document.createElement('script');
        oScript.type = 'text/javascript';
        oScript.charset = 'utf-8';
        oScript.src = jsFile;
        oHead.appendChild(oScript);
    };
};
jqGridInclude();