$(window).on("load", function () {
    if ($("#idprof1").length > 0 && $("#idprof2").length > 0) 
    {
        var infoSoc = new InfoSoc($("#idprof1"), $("#idprof2"));
    }
});



function InfoSoc(elem,elem2) {
    this.elem = elem;
    this.elem2 = elem2;
    this.erreur = "";
    this.typeTier = $("#typent_id");
    this.pays = $("#selectcountry_id");
    this.useSiret = true;
    
    
    
    

    this.insertValue = function(result) {
        $("#name").val(result.Nom);
        $("#address").val(result.Adresse);
        $("#capital").val(result.Capital);
        $("#zipcode").val(result.CodeP);
        $("#town").val(result.Ville);
        $("#intra_vat").val(result.Tva);
        $("#phone").val(result.Tel);
        $("#name_alias_input").val(result.tradename);
        $("#idprof3").val(result.Naf);
        if($("#options_notecreditsafe").length > 0)
            $("#options_notecreditsafe").val("Limite : "+result.limit+" €\nNote : " + result.Note+"\n" + result.info);
        else
            $("#name_alias_input").val("Note : " + result.Note); 
        if(result.Siret != this.elem.val() && result.Siret != this.elem2.val())
            this.elem2.val(result.Siret);
    }
    
    this.init = function() {
        var moi = this;
        this.elem.change(function () {
            moi.checkData("siren");
        });
        this.elem.parent().append("<input type='button' id='checkData' value='Vérifié'/>");
        
        if(this.useSiret){
            this.elem2.change(function () {
                moi.checkData("siret");
            });
            this.elem2.parent().append("<input type='button' id='checkData2' value='Vérifié'/>");
        }
        
        
        $('#checkData').click(function(){
            moi.checkData("siren");
        });
        $('#checkData2').click(function(){
            moi.checkData("siret");
        });
        
        var isParticulier = ($("#typent_id").length == 0 || this.typeTier.val() == 8);
        
        var actu = this.elem.val();
        if(actu == "")
            actu = this.elem2.val();
        for(var i = 0; i< 5; i++)
            actu = actu.replace(" ", "").replace("-", "");
        if(!isParticulier && (actu == "" || !this.isSiretSiren(actu))){
            this.promptSiren(actu);
        }
    }
    
    
    this.promptSiren = function(textDef = "") {
        if(textDef == "")
            textDef = "SIREN";
        var siren = window.prompt("Numéro de SIREN/SIRET ou p pour un PARTICULIER, h pour un Hors de France \n "+this.erreur, textDef);
        if(siren == null){
            history.back();
            return 0;
        }
        siren = siren.replace(" ", "").replace(" ", "").replace(" ", "").replace(" ", "").replace("	", "").replace("   ", "");
        if(siren == "Hors" || siren == "h" || siren == "H"){
            $("#selectcountry_id option[value='1']").remove();
            return "";
        }
        if(siren == "PARTICULIER" || siren == "P" || siren == "p"){
            this.typeTier.val(8);
            return "";
        }
        if(siren == "EDUC" || siren == "E" || siren == "e"){
            this.typeTier.val(5);
            return "";
        }
        if(siren == "fille" || siren == "F" || siren == "f"){
            return "";
        }
        if(!this.isSiretSiren(siren) || siren == textDef){
            this.promptSiren(siren);
        }
        else{
            if(siren.length == 14)
                this.elem2.val(siren);
            this.elem.val(siren);
            this.checkData("siret");
        }
    }
    
    this.traiteSiren = function(siren){
        for(var i = 0; i< 5; i++)
            siren = siren.replace(" ", "").replace("-", "");
        return siren;
    }
    
    
    this.isSiretSiren = function(siren) {
        siren = this.traiteSiren(siren);
        this.erreur = "";
        if(siren == "" || siren == null){
            this.erreur = "SIRET/SIREN VIDE";
            return false;
        }
        if(siren.length != 14 && siren.length != 9){
            this.erreur = "UN SIREN COMPORTE 9 chiffres";
            if(this.useSiret)
                this.erreur += " UN SIRET COMPORTE 14 chiffres";
            return false;
        }
        if(siren != parseInt(siren)){
            this.erreur = "Un SIRET/SIREN est composé de chiffres";
            return false;
        }
        return true;
    }
        

    this.checkData = function(type) {
        moi =this;
        var siren = this.elem.val();
        if(this.isSiretSiren(this.elem2.val()) && type == "siret")
            siren = this.elem2.val();
        if(this.isSiretSiren(siren)){
            siren = this.traiteSiren(siren);
            datas = "siren=" + siren;
            jQuery.ajax({
                url: DOL_URL_ROOT + "/bimpcreditsafe/test2.php",
                data: datas,
                datatype: "json",
                type: "POST",
                cache: false,
                success: function (msg) {
                    var result = JSON.parse(msg);
                    if (result.Erreur == "132")
                        alert("Plus d'unité chez CREDIT SAFE");
                    else if (result.Erreur == "171")
                        alert("SIREN/SIRET non reconnue par CREDIT SAFE");
                    else if (typeof result.Erreur !== "undefined")
                        alert("Erreur inconnue code :" + result.Erreur);
                    else {
                        moi.insertValue(result);
                        moi.elem.val(siren.substring(0,9));
                    }
                }
            });
        }
        else{
            alert("SIRET/SIREN non conforme !! : "+siren+"  "+this.erreur);
        }
    };
    
    
    this.init();
}