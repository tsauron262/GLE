
var Amortize = {
    fixedfmt: function(num, decimals){
        // Number.toFixed is a Javascript 1.5 feature,
        // so we need to test for it here.
        if (num)
        {
            var returnVar = num.toFixed ? num.toFixed(decimals) : Math.round(num * 100) / 100;
            return returnVar
        } else {
            return (0);
        }
    },

    fmtmoney: function(num){
        if (Math.abs(num) < 0.005)
            num = 0;
        var tmp = this.fixedfmt(num, 2);
        return tmp
    },

    fmtpct: function(num){
        return this.fixedfmt(num * 100, 2) + "%";
    },


    // Get numbers from the input fields.
    getInput: function(tot){
        var field;
        var obj = new Object();
        obj.marge = parseFloat($('#marge').val()) / 100;
        if ("x" + obj.marge == "x") {
            obj.marge = 0;
        }
        obj.principal = parseFloat(tot) * (1 + obj.marge);
        obj.tot = parseFloat(tot);
        obj.frais = parseFloat(tot) * (obj.marge);
        field = jQuery("#amortperiod").val();
        obj.amortperiod = parseFloat(field) / 12;
        field = jQuery("#paymentsperyear").val();
        obj.payperyear = parseInt(field);
        field = jQuery("#interest").val();
        obj.interest = parseFloat(field) / 100;
        if (jQuery("#echu").attr('type') == 'checkbox')
        {
            if (jQuery("#echu").attr('checked') == true) {
                obj.type = 0; // 0 -> terme echu 1 -> terme a echoir
            } else {
                obj.type = 1; // 0 -> terme echu 1 -> terme a echoir
            }
        } else if (jQuery("#echu").attr('type') == 'hidden')
        {
            obj.type = jQuery("#echu").val();
        }

        //console.log(obj);


        return obj;
    },

    main: function(tot){
        var obj = new Object();

        var input = this.getInput(tot);
        if (!input)
            return false;
        obj = input;
        var intrate = input.interest / input.payperyear;
        obj.intrate = intrate;
        var numpayments = input.amortperiod * input.payperyear;
        obj.numpayments = numpayments;

        var x = Math.pow(1 + intrate, numpayments);
        var payments = intrate * input.principal * x / ((x - 1) * (1 + intrate * input.type));
        obj.payments = payments;


        var balance = input.principal;
        var sumint = 0;
        var sumprin = 0;
        var sumpay = 0;

        obj.lines = new Array();
        for (var i = 1; i <= numpayments; ++i) {
            var objLine = new Object();
            sumpay += payments;
            var thisint = balance * intrate;
            sumint += thisint;

            var thisprin = payments - thisint;
            sumprin += thisprin;

            balance -= thisprin;
            objLine.iter = i;
            objLine.payment = payments;
            objLine.thisint = thisint;
            objLine.thisprin = thisprin;
            objLine.sumpay = sumpay;
            objLine.sumint = sumint;
            objLine.sumprin = sumprin;
            objLine.sumprin = balance;
            obj.lines[i] = objLine;

        }
        this.CalculeTEG(tot);
        obj.tegper = this.tegper;
        obj.teganprop = this.teganprop;
        obj.teganactu=this.teganactu;

        return (obj);
    },
    iter : 20,
    cal_v: function(){
        this.G = 0;
        this.M = 0;
        this.V = 0;

        for (var w = 0; w < this.z; w++) {
            this.xa = eval(1 + this.L);
            this.xb = (-this.QQ[w]);
            this.xc = (this.AA[w]);
            this.xd = (-this.G);
            this.x1 = Math.pow(this.xa, this.xb);
            this.x2 = Math.pow(this.xa, this.xd);
            this.V = (this.xc * ((1 - this.x1) / this.L) * this.x2);
            this.M = eval(this.M + this.V);
            this.G = this.G + this.QQ[w];
        }

    },

    CalculeTEG: function(tot){
        //initialisation variables pret
        this.ict="";     //variable de boucle
        this.itx="";     //taux de periode du taux nominal
        this.ytx="";     //variable intermediaire
        this.ztx="";     //          -do-
        this.btx="";     //          -do-
        this.ntx="";     //inversion signe de toteche
        this.xch="";     //variable de traitement des saisies
        this.xch1="";    //      -do-
        this.xch11="";   //      -do-
        this.capital=""; //le capital emprunte
        this.taux="";    //le taux
        this.FrequenceAn=""; //le nombre d'echeances par an
        this.toteche=""; //le nombre total d'echeances sur la duree du prêt
        this.frais="";   //le montant des frais
        this.assech="";  //assurance par echeance
        this.rembou="";  //echeance comprenant l'assurance

        //initialisation variables teg
        this.capired="";     //part de capital restant apres deduc. frais
        this.f="";           //frequence des versements
        this.z="";           //nb de groupes de versements
        this.w=""    ;       //var de boucle
        this.v="";           //le montant d'un versement
        this.n="";           //le nb de versements du groupr
        this.L="";           //var de Calcul
        this.V1="";          //    -do-
        this.V2="";          //    -do-
        this.V="";           //    -do-
        this.T1="";          //    -do-
        this.T2="";          //    -do-
        this.Tt="";          //    -do-
        this.M="";           //    -do-
        this.G="";           //    -do-
        this.ctx="";         //var de boucle
        this.tegper="";      //teg de la periode

        this.G = 0;
        this.M = 0;
        this.V = 0;
        this.z = 2;

        this.AA=new Array(100);
        this.QQ=new Array(200);


        // mise a nul des variables de resultat
        this.tauper = "";
        this.echeance = "";
        this.totint = "";
        this.coutass = "";
        this.rembou = "";
        this.tegper = "";
        this.capired = "";
        this.teganprop = "";

     // mise a nul des variables de saisie
        this.capital = "";
        this.taux = "";
        this.FrequenceAn = "";
        this.toteche = "";
        this.frais = "0";
        this.assech = "0";

        var input = this.getInput(tot);
        if (!input)
            return false;
        obj = input;



        this.capital = parseFloat(obj.tot);
        this.taux = parseFloat(obj.interest);
        this.FrequenceAn =  parseFloat(obj.payperyear);
        this.toteche = obj.payperyear * (obj.amortperiod + 0.00000000000001) ;
        this.assech = 0.00000000000000001; // permet de lancer le calcul sinon rien //en affichage
        this.frais = obj.frais + 0.00000000000000001;

        this.ntx = -this.toteche;
        this.itx = this.taux / this.FrequenceAn;
        this.btx = 1 + this.itx;
        this.ztx = Math.pow(this.btx, this.ntx);
        this.ytx = 1 - this.ztx;
        this.echeance = Math.round(this.capital * this.itx / this.ytx * 100) / 100;
        this.tauper = Math.round((this.itx * 100) * 1000000) / 1000000;
        this.totint = Math.round(((this.echeance * this.toteche) - this.capital) * 100) / 100;
        this.coutass = Math.round((this.assech * this.toteche) * 100) / 100;
        this.rembou = Math.round((this.echeance + this.assech) * 100) / 100;
        this.couttot = Math.round((this.totint + this.frais + this.coutass) * 100) / 100;
        this.capired = Math.round((this.capital - this.frais) * 100) / 100;
        var returnArr = new Array();
        //Calcul teg

        this.f = parseFloat(this.FrequenceAn);
        this.n = 2;
        this.AA[0] = -(this.capital - this.frais);
        this.AA[1] = (this.echeance + this.assech);
        this.QQ[0] = 1;
        this.QQ[1] = this.toteche;



        this.L = 0.4 / this.f;
        this.cal_v();
        this.V1 =this.M;
        this.T1 = this.L;


        this.L = 0.01 / this.f;
        this.cal_v();
        this.V2 = this.M;
        this.T2 = this.L;
        this.Tt = eval(((this.T2 * this.V1) - (this.T1 * this.V2)) / (this.V1 - this.V2));
        this.L = this.Tt;
        this.cal_v();
        for (this.ctx = 1; this.ctx < 1500; this.ctx++) {
            this.V1 = this.M;
            this.T1 = this.L;
            this.Tt = eval(((this.T2 * this.V1) - (this.T1 * this.V2)) / (this.V1 - this.V2));
            this.L = this.Tt; //alert('9l '+L);
            this.cal_v();
            if (this.M > 0 && Math.abs(this.M) < 0.000001) {
                this.tegper = Math.round((this.Tt * 100) * 1000000) / 1000000; // de periode
                this.teganprop = Math.round((this.tegper * this.f) * 10000) / 10000; // proportionnel
                this.teganactu = Math.round(((Math.pow((this.Tt + 1), this.f) - 1) * 100) * 10000) / 10000; //actualisé
                if (this.tegper> 100 ) this.tegper = '-';
                returnArr['tegper']=this.tegper;
                if (this.teganprop> 100 ) this.teganprop = '-';
                returnArr['teganprop']=this.teganprop;
                if (this.teganactu> 100 ) this.teganactu = '-';
                returnArr['teganactu']=this.teganactu;

            }
        }
        return(returnArr);

    }
}