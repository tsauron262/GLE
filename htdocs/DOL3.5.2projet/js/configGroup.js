$("#gridListProj1").jqGrid({
        datatype: "json",
        url: "ajax/listTeam-json.php?userId="+userId,
        colNames:['id', 'Nom de l\'&eacute;quipe','Nbr d\'employ&eacute;s dans l\'&eacute;quipe', 'Co&ucirc;t moyen horaire par employ&eacute;','Coût r&eacute;el horaire par employ&eacute;'],
        colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true},
                    {name:'title',index:'title', width:90, align:"center"},
                    {name:'qte',index:'qte', width:80, align:"center",hidden: false},
                    {name:'cout',index:'cout', width:90, align:"center",sortable:false,editable:true,editrules:{number:true} },
                    {name:'coutReel',index:'coutReel', width:90, align:"center",sortable:false,editable:true,editrules:{number:true} },
                  ],
        rowNum:10,
        rowList:[10,20,30],
        imgpath: gridimgpath,
        pager: $('#gridListProj1Pager'),
        sortname: 'id',
        mtype: "POST",
        viewrecords: true,
        width: "900",
        height: 500,
        sortorder: "desc",
        //multiselect: true,
        caption: "Co&ucirc;t par groupe",
        forceFit : true,
        cellEdit: true,
        cellsubmit: 'clientArray',
        afterSaveCell : function(rowid,name,val,iRow,iCol) {
            if(name == 'cout') {
                var taxval = $("#gridListProj1").getCell(rowid,3);
                var id = $("#gridListProj1").getCell(rowid,0);
                $.ajax({
                    data: "id="+id+"&cost="+parseFloat(taxval),
                    url: "ajax/setGroupCost-xmlresponse.php",
                    type: "POST",
                    async: false,
                    success: function(msg){
                        $("#gridListProj1").trigger("reloadGrid");
                    }
                });
            }
        },
}).navGrid('#gridListProj1Pager',
               { add:false,
                 del:false,
                 edit:false,
                 position:"left"
    });