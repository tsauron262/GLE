$("#gridListProj").jqGrid({
        datatype: "json",
        url: "ajax/listHRessource-json.php?userId="+userId,
        colNames:['id', 'Nom','Poste', 'Co&ucirc;t horaire moyen'],
        colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true},
                    {name:'fullname',index:'fullname', width:90, align:"center"},
                    {name:'poste',index:'poste', width:80, align:"center",hidden: false},
                    {name:'cout',index:'cout', width:90, align:"center",editable:true,sortable:false,editrules:{number:true} },
                  ],
        rowNum:10,
        rowList:[10,20,30],
        imgpath: gridimgpath,
        pager: $('#gridListProjPager'),
        sortname: 'id',
        mtype: "POST",
        viewrecords: true,
        width: "900",
        height: 500,
        sortorder: "desc",
        //multiselect: true,
        caption: "Co&ucirc;t par ressources",
        forceFit : true,
        cellEdit: true,
        cellsubmit: 'clientArray',
        afterSaveCell : function(rowid,name,val,iRow,iCol) {
            if(name == 'cout') {
                var taxval = $("#gridListProj").getCell(rowid,3);
                var id = $("#gridListProj").getCell(rowid,0);
                $.ajax({
                    data: "id="+id+"&cost="+parseFloat(taxval),
                    url: "ajax/setEmpCost-xmlresponse.php",
                    type: "POST",
                    async: false,
                    success: function(msg){
                        $("#gridListProj").trigger("reloadGrid");
                    }
                });
            }
        },
}).navGrid('#gridListProjPager',
               { add:false,
                 del:false,
                 edit:false,
                 position:"left"
    });