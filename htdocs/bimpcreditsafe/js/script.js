$(window).on("load", function () {
    if ($("#idprof1").length > 0) 
    {
        var infoSoc = new InfoSoc($("#idprof1"), $("#idprof2"));
    }
});



function InfoSoc(elem,elem2) {
    this.elem = elem;
    this.elem2 = elem2;
    this.erreur = "";
    this.typeTier = $("#typent_id");
    this.useSiret = true;
    
    
    
    

    this.insertValue = function(result) {
        $("#name").val(result.Nom);
        $("#address").val(result.Adresse);
        $("#capital").val(result.Capital);
        $("#zipcode").val(result.CodeP);
        $("#town").val(result.Ville);
        $("#intra_vat").val(result.Tva);
        $("#phone").val(result.Tel);
        $("#idprof3").val(result.Naf);
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
        
        if(!isParticulier && (this.elem.val() == "" || !this.isSiretSiren(this.elem.val()))){
            this.promptSiren(this.elem.val());
        }
    }
    
    
    this.promptSiren = function(textDef = "") {
        if(textDef == "")
            textDef = "SIREN";
        var siren = window.prompt("Numéro de SIREN/SIRET ou p pour un PARTICULIER \n "+this.erreur, textDef);
        if(siren == null)
            siren = "";
        siren = siren.replace(" ", "").replace(" ", "").replace(" ", "").replace(" ", "").replace("	", "").replace("   ", "");
        if(siren == "PARTICULIER" || siren == "P" || siren == "p"){
            this.typeTier.val(8);
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
    
    
    this.isSiretSiren = function(siren) {
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