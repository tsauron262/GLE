<!doctype html>
<html>
<head>
  <meta charset="UTF-8">		
  <title>Vérification du code</title>
  <style type="text/css">
	#content input {
	  margin: 0 5px;
	  text-align: center;
	  line-height: 80px;
	  font-size: 50px;
	  border: solid 1px #ccc;
	  box-shadow: 0 0 5px #ccc inset;
	  outline: none;
	  width: 70px;
	  transition: all 0.2s ease-in-out;
	  border-radius: 10px;
	}
	#content input:focus {
	  border-color: #ef7d00;
	  box-shadow: 0 0 5px #ef7d00 inset;
	}
	#content input::-moz-selection {
	  background: transparent;
	}
	#content input::selection {
	  background: transparent;
	}

button, .button {
  display: inline-block;
  position: relative;
  cursor: pointer;
  margin: 10px;
  -webkit-user-select: none;
     -moz-user-select: none;
      -ms-user-select: none;
          user-select: none;
  text-decoration: none;
  -webkit-perspective: 800px;
          perspective: 800px;
  border: none;
  background: none;
  color: #ef7d00;
  font-size: 24px;
  padding: 16px;
  transition: color .25s;
  margin-top:2%;
}
button:after, .button:after {
  opacity: 0;
  transition: all .25s;
  background: transparent;
}
button:before, .button:before {
  background: transparent;
}
button:hover:after, .button:hover:after {
  opacity: 1;
}
button > span, .button > span {
  position: relative;
  z-index: 2;
}

button.btn:hover, .button.btn:hover {
  color: #fff !important;
}
button.btn:after, .button.btn:after {
  background: #ef7d00 !important;
}

button:before, button:after, .button:before, .button:after {
  position: absolute;
  top: 0;
  left: 0;
  content: "";
  width: 100%;
  height: 100%;
}
body {
  background: #efefef;
  font-size: 62.5%;
  font-family: 'Lato', sans-serif;
  font-weight: 300;
  color: #B6B6B6;
}
body section {
  background: white;
  margin: 60px auto 120px;
  border-top: 15px solid #313A3D;
  text-align: center;
  padding: 50px 0 110px;
  width: 80%;
  max-width: 1100px;
}
body section h1 {
  margin-bottom: 40px;
  font-size: 4em;
  text-transform: uppercase;
  font-family: 'Lato', sans-serif;
  font-weight: 100;
}

/* Firefox */
input[type=number] {
    -moz-appearance: textfield;
}

/* Chrome */
input::-webkit-inner-spin-button,
input::-webkit-outer-spin-button { 
	-webkit-appearance: none;
	margin:0;
}

/* Opéra*/
input::-o-inner-spin-button,
input::-o-outer-spin-button { 
	-o-appearance: none;
	margin:0
}

#btn_renvoi {
    color:lightblue; 
    text-decoration:none; 
    font-size:15px;
}
</style>
<?php echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery.min.js?layout=classic&version=8.0.3"></script>'; ?>
<script>
	$(document).bind('paste', function(e) {
    alert(e.val());
    console.log(e);
});
	$(function() {
  "use strict";

  var body = $("body");

  

  function goToNextInput(e) {
    var key = e.which,
      t = $(e.target),
      sib = t.next("input");


    if (key != 9 && (key < 48 || key > 57)) {
      e.preventDefault();
      return false;
    }

    if (key === 9) {
      return true;
    }

    if (!sib || !sib.length) {
      sib = body.find("input").eq(0);
    }
    sib.select().focus();
  }

  function onKeyDown(e) {
    var key = e.which;
    if (key === 9 || (key >= 48 && key <= 57)) {
      return true;
    }

    e.preventDefault();
    return false;
  }

  function onFocus(e) {
    $(e.target).select();

  }

 
  body.on("keyup", "input", goToNextInput);
  body.on("keydown", "input", onKeyDown);
  body.on("click", "input", onFocus);

});
</script>
</head>
<body>
		<section id="content">
		    <h1>Saisir le code reçu par SMS</h1>
                    <h2><?= $message ?></h2>
		    <center>
		    	<form method="POST" action="">
		    		<input type="number" name="sms_code_1" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}" />
			        <input type="number" name="sms_code_2" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}" />
			        <input type="number" name="sms_code_3" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}"/>
			        <input type="number" name="sms_code_4" maxLength="1" size="1" min="0" max="9" pattern="[0-9]{1}" />
			      	<br />
		      		<button class='btn'>
			            <span>Envoyé</span>
			        </button>
		    	</form>
		    	<form>
		    		<button id="btn_renvoi">Code non reçus ? renvoyer le code</button>
		    	</form>
		        
      		</center>
		    
		</section>
	<center>
            <?php global $mysoc; ?>
		<img src="<?= DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . $mysoc->logo ?>">
              
	</center>
</body>
</html>




