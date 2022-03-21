/** 
 * ODONTOGRAM 
 * for History Clinical
 * by H'Soberon
 * @ Glorfindel 2019
 */

'use strict';

(function($) {

  $(function() {
  	//TEMPORARY INITIAL PROCEDURES
  	//let initialData = '{"showsPrimary" : false, "comments" : "Se generaron varios procedimientos anteriores \\nCorona del 22 al 71", "procedures" : [{"tooth" : 48, "pro" : 49, "side" : false}, {"tooth" : 11, "pro" : 97, "side" : "left"}, {"tooth" : 11, "pro" : 99, "side" : "top"}, {"tooth" : 11, "pro" : 99, "side" : "center"}]}';

  	var odontogram = new Odontogram();

  	$.ajax({
		type: "GET",
		url: "./js/procedures.json", // All procedures
		success: function(initialProcedures) {
			odontogram.procedures = initialProcedures;

			// $.ajax({
			// 	type: "GET",
			// 	url: "./js/config.json", // Initial configuration file and previus procedures
			// 	success: function(initialConfig) {
			// 		odontogram.config = initialConfig;
			// 	}
			// });
		}
	});

  	

  	
  	//Captures the KeyUP only for the delete button and clear complete the tooth
  	//8 for backspace 48 for delete
  	$( document ).keyup(function( event ) {
  		if(isShowProceduresPanel() && event.which == 8){
  			clearToothProcedures();
  		}

  		if(isShowProceduresPanel() &&  event.which == 46){
  			clearSideProcedures(false); //no confirmation
  		}
  	});

  	//Captures all the keypress for app
  	$( document ).keypress(function( event ) {
	  if(isShowProceduresPanel()){
	  	event.preventDefault();

		//On ESC close the panel
		if(event.which == 27) {
			hideProceduresPanel();
		}else{
			if(typeof odontogram.procedures[event.which] !== "undefined"){
				let pro = odontogram.procedures[event.which];
				pro.activate();
			}else{
				console.log(event.which+') Procedimiento no encontrado');
			}
		}
	  }
	}) ;


	//When the procedures is selected, send to odontogram
	$('.list-group-item.list-group-item-action').click(function (event) {
		event.preventDefault();
		let pro = odontogram.procedures[$(this).data('action')];
		pro.activate();
	});

	
	$('.numbers-only').keypress(function(event) {
		if(event.which < 48 || event.which > 57) {
			event.preventDefault();
		}
	}
	);
	$('.cedula').keypress(function(event) {
		validarCedula(event);
	})

  });
})(jQuery);


function validarCedula(e) {
	var key = window.Event ? e.which : e.keyCode
	key = String.fromCharCode(key);
	var patron = /[0-9]/;
	var tecla_final = true;

	if(key == "v" || key == "V"){
		tecla_final = false;
	}else{
		if(!patron.test(key)) {
			tecla_final = false;
		}
	}
	return tecla_final;
}



