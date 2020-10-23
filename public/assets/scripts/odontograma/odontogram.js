/**
 * Odontogram class definition for
 * History Clinical
 * By H'Soberon
 * @ Glorfindel
 * 2019
 */



"use strict";


class Odontogram {
    // static showsPrimaryProcedures = false;
    constructor(initialConfig){

        this._procedures = [];
        this._config = [];

        let odontogram = this;



        // Mark the side of the tooth on click
        $('.tooth .side').click(function(event) {
            event.preventDefault();
            event.stopPropagation();
            markSide($(this));
        });

        // Mark the entire tooth on click
        $('.tooth').click(function(event) {
            event.preventDefault();
            markTooth($(this));
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



        // Shows or hide the primary section
        $('.primary-toggle').click(togglePrimaryTeethSection);

        $('.close-panel').click(hideProceduresPanel);

        $('#offcanvas-bg').click(hideProceduresPanel);

        $('#offcanvas-menu .over-top').click(toggleProceduresPanel);

    }


    //Stablish all the procedures for the sistem
    set procedures(initialProcedures){
        console.log('Loading all procedures');
        let pro = [];

        initialProcedures.map(function(item) {
            pro[item.key] = new Procedure(item.key, item.title, item.className, item.type, item.apply, item.clearBefore);
            pro[item.key].active;
        });
        this._procedures = pro;
    }

    //return all procedures
    get procedures(){
        return this._procedures;
    }

    // Setter for the initial config
    set config(initialConfig) {
        console.log('Loading initial config');
        this._config = initialConfig;

        // shows the Primary Dentity section
        if(this._config.showsPrimary) {
            togglePrimaryTeethSection();
            $('.primary-toggle').prop('checked', true);
        }

        // Fill the comments section
        if(this._config.comments) {
            $('#comments').val(this._config.comments);
        }

        // Display the initial procedures
        if(this._config.procedures && JSON.parse(this._config.procedures).length > 0) {
            let initialProcedures = JSON.parse(this._config.procedures);
            for (var i = initialProcedures.length - 1; i >= 0; i--) {
                let this_pro = initialProcedures[i];
                let tooth = $('#tooth_' + this_pro.tooth);
                let side = (this_pro.side) ? tooth.find('.side_'+this_pro.side) : false;
                this._procedures[this_pro.pro].activate(tooth, side);
            }
        }
    }



    set changes(newChanges) {
        if(newChanges.length > 1){
            for (var i = newChanges.length - 1; i >= 0; i--) {
                this._changes.push(newChanges[i]);
            }
        }else{
            this._changes.push(newChanges);
        }

        console.log('New changes mades');

        $('#procedures').val(JSON.stringify(this._changes));
    }

    get changes() {
        return this._changes;
    }



}








function Procedure(key, title, className, type, apply, clearBefore) {
    this.key = key;
    this.title = title;
    this.className = className;
    this.type = type;
    this.apply = apply;
    this.clearBefore = clearBefore;


    this.activate = function(tooth, sides) {

        if(!tooth){
            tooth = $('#odontogram .tooth.active');
        }

        if(!sides) {
            sides = $('#odontogram .side.active');
        }


        if(this.className == 'pro_angle' || this.className == 'pro_angle_done'){
            let thisPro = this;
            $(sides).each(function () {
                if($(this).data('side') == 'left' || $(this).data('side') == 'right'){
                    if(thisPro.clearBefore) {clearSideProcedures(false, this);}
                    $(this).addClass(thisPro.className);
                    addChanges({
                        "tooth" : tooth.data('id'),
                        "pro" : thisPro.key,
                        "title": thisPro.title,
                        "side" : $(this).data('side')
                    });
                }
            });
        }else if(this.className == 'pro_restoration' || this.className == 'pro_restoration_done'){
            let thisPro = this;
            $(sides).each(function () {
                if($(this).data('side') == 'top' || $(this).data('side') == 'bottom'){
                    if(thisPro.clearBefore) {clearSideProcedures(false, this);}
                    $(this).addClass(thisPro.className);
                    addChanges({
                        "tooth" : tooth.data('id'),
                        "pro" : thisPro.key,
                        "title": thisPro.title,
                        "side" : $(this).data('side')
                    });
                }
            });
        }else if(this.apply == 'side'){
            let thisPro = this;
            $(sides).each(function () {
                if(thisPro.clearBefore) {clearSideProcedures(false, this);}
                $(this).addClass(thisPro.className);
                addChanges({
                    "tooth" : tooth.data('id'),
                    "pro" : thisPro.key,
                    "title": thisPro.title,
                    "side" : $(this).data('side')
                });
            });
        }else{
            if(this.clearBefore) {clearToothProcedures(false, tooth);}
            tooth.addClass(this.className);
            addChanges({
                "tooth" : tooth.data('id'),
                "pro" : this.key,
                "title": this.title,
                "side" : false
            });
        }


        hideProceduresPanel();
        console.log(this.title);
    };
}



function addChanges(newChanges = []) {

    let oldChanges = JSON.parse($('#procedures').val());
    oldChanges.push(newChanges)
    $('#procedures').val(JSON.stringify(oldChanges));
    console.log(oldChanges);

    var ul = $("<ul></ul>"); // Creamos un elemento ul

    for( var i = 0; i < oldChanges.length; i++ ) {
        var li = $("<li></li>")// Creamos un elemento li
        // Añadimos el title y el autor al elemento li
        li.append(oldChanges[i].title + " (" + oldChanges[i].type + ")");

        // Comprobamos si el libro tiene partes
        if( oldChanges[i].Partes != undefined && oldChanges[i].Partes.length > 0 ) {
            var ulInterno = $("<ul></ul>"); // Creamos otro elemento ul

            for( var j = 0; j < oldChanges[i].Partes.length; j++ ) {
                // Por cada parte crearemos un elemento li que añadiremos al ul
                // que acabamos de crear
                ulInterno.append("<li>" + oldChanges[i].Partes[j].Tomo + " - " +
                    oldChanges[i].Partes[j].title + "</li>"
                );
            }
            // Añadimos el último elemento ul al li creado primero
            li.append(ulInterno);
        }
        ul.append(li); // Añadimos el li inicial al ul inicial.
    }
    $("#lista").html(ul);

}




/**
 * Show on hide the Primary teeths section
 */
function togglePrimaryTeethSection() {
    if($('.primary').hasClass('show_primary')){
        $('.primary').removeClass('show_primary').fadeOut();
        $('#showsPrimary').val(0);
    }else{
        $('.primary').addClass('show_primary').fadeIn();
        $('#showsPrimary').val(1);
    }
}




/**
 * Shows the Offcanvas menu with the procedures
 */
function showProceduresPanel() {
    $('#offcanvas-menu').addClass('show');
    $('#offcanvas-bg').fadeIn();
}

//Hide the Offcanvas menu with the procedures
function hideProceduresPanel() {
    $('#offcanvas-menu').removeClass('show');
    $('#offcanvas-bg').fadeOut();
    $('#odontogram .active').removeClass('active');
}

// Show on hide, hide if is showing
function toggleProceduresPanel() {
    if($('#offcanvas-menu').hasClass('show')){
        hideProceduresPanel();
    }else{
        showProceduresPanel();
    }
}

//Return true if the Procedures is showing
function isShowProceduresPanel() {
    return $('#offcanvas-menu').hasClass('show');
}



//clear the selected tooth of all procedures
function clearToothProcedures(with_confirm = true, tooth = false) {
    if(!with_confirm || confirm('Está seguro de borrar todos los procesos sobre este diente?')){
        if(!tooth) {
            tooth = $('#odontogram .tooth.active');
        }
        let sides = $(tooth).find('.side');
        //remove tooth classes
        $(tooth).removeClass (function (index, className) {
            return (className.match (/(^|\s)pro_\S+/g) || []).join(' ');
        });
        //remove tooth procedures
        let changes = JSON.parse($('#procedures').val());
        let findings = [];
        for (var i = changes.length - 1; i >= 0; i--) {
            if(changes[i].tooth == $(tooth).data('id')){
                changes.splice(i,1);
            }
        }
        // changes.forEach(function(change, index){
        // 	if(change.tooth == $(tooth).data('id')){
        // 		changes.splice(index,1);
        // 	}
        // });
        //update changes
        $('#procedures').val(JSON.stringify(changes));

        //remove sides procedures
        clearSideProcedures(false, sides);
        // console.log(changes);

        hideProceduresPanel();
    }
}


//clear the selected sides of all procedures
function clearSideProcedures(with_confirm = true, sides = false) {
    if(!with_confirm || confirm('Está seguro de borrar todos los procesos sobre este cara?')){
        if(!sides) {sides = $('#odontogram .tooth.active .side.active');}
        //remove sides classes
        $(sides).removeClass (function (index, className) {
            return (className.match (/(^|\s)pro_\S+/g) || []).join(' ');
        });
        //remove side procedures form changes
        let changes = JSON.parse($('#procedures').val());
        $(sides).each(function () {
            let side = this;
            changes.forEach(function(change, index){
                if(change.tooth == $(side).closest('.tooth').data('id') &&
                    change.side == $(side).data('side')){
                    changes.splice(index,1);
                }
            });
        });

        $('#procedures').val(JSON.stringify(changes));
        hideProceduresPanel();
    }
}



/**  *** THOOTHS *** */


//Mark this side and its tooth as active
function markSide(side) {
    if($(side).hasClass('active')){
        $(side).removeClass('active');
    }else{
        $(side).addClass('active');
        markTooth($(side).closest('.tooth'));
    }
}

//Mark the tooth  as active
function markTooth(tooth) {
    $(tooth).addClass('active');
    showProceduresPanel();
}



