"use strict";


class Odontogram {
    // static showsPrimaryProcedures = false;
    constructor(initialConfig) {

        this._procedures = [];
        this._config = [];
        let odontogram = this;

        // Mark the side of the tooth on click
        $('.tooth .side').click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            markSide($(this));
        });

        // Mark the entire tooth on click
        $('.tooth').click(function (event) {
            event.preventDefault();
            markTooth($(this));
        });

        // Captura KeyUP solo para el botón Eliminar y borrar completa el diente
        // 8 for backspace 48 for delete
        $(document).keyup(function (event) {
            if (isShowProceduresPanel() && event.which == 8) {
                clearToothProcedures();
            }

            if (isShowProceduresPanel() && event.which == 46) {
                clearSideProcedures(false); //no confirmation
            }
        });

        //Captura todas las pulsaciones de teclas
        $(document).keypress(function (event) {
            if (isShowProceduresPanel()) {
                event.preventDefault();

                //On ESC close the panel
                if (event.which == 27) {
                    hideProceduresPanel();
                } else {
                    if (typeof odontogram.procedures[event.which] !== "undefined") {
                        let pro = odontogram.procedures[event.which];
                        pro.activate();
                    } else {
                        console.log(event.which + ') Procedimiento no encontrado');
                    }
                }
            }
        });

        // Cuando se selecciona el procedimiento, enviar al odontograma.
        $('.list-group-item.list-group-item-action').click(function (event) {
            event.preventDefault();
            let pro = odontogram.procedures[$(this).data('action')];
            pro.activate();
        });

        // Muestra u oculta la sección principal
        $('.primary-toggle').click(togglePrimaryTeethSection);
        $('.close-panel').click(hideProceduresPanel);
        $('#offcanvas-bg').click(hideProceduresPanel);
        $('#offcanvas-menu .over-top').click(toggleProceduresPanel);

    }

    //Stablish all the procedures for the sistem
    set procedures(initialProcedures) {
        console.log('Loading all procedures');
        let pro = [];

        initialProcedures.map(function (item) {
            pro[item.key] = new Procedure(item.key, item.title, item.className, item.type, item.apply, item.clearBefore);
            pro[item.key].active;
        });
        this._procedures = pro;
    }

    //return all procedures
    get procedures() {
        return this._procedures;
    }

    // Setter for the initial config
    set config(initialConfig) {
        console.log('Cargando la config inicial');
        this._config = initialConfig;

        // shows the Primary Dentity section
        if (this._config.showsPrimary) {
            togglePrimaryTeethSection();
            $('.primary-toggle').prop('checked', true);
        }

        // Fill the comments section
        if (this._config.comments) {
            $('#comments').val(this._config.comments);
        }

        // Display the initial procedures
        if (this._config.procedures && JSON.parse(this._config.procedures).length > 0) {
            let initialProcedures = JSON.parse(this._config.procedures);
            for (var i = initialProcedures.length - 1; i >= 0; i--) {
                let this_pro = initialProcedures[i];
                let tooth = $('#tooth_' + this_pro.tooth);
                let side = (this_pro.side) ? tooth.find('.side_' + this_pro.side) : false;
                this._procedures[this_pro.pro].activate(tooth, side);
            }
        }
    }



    set changes(newChanges) {
        if (newChanges.length > 1) {
            for (var i = newChanges.length - 1; i >= 0; i--) {
                this._changes.push(newChanges[i]);
            }
        } else {
            this._changes.push(newChanges);
        }
        console.log('New changes mades');
        $('#procedures').val(JSON.stringify(this._changes));
    }

    get changes() {
        return this._changes;
    }


}






/************ Funciones **********/

function Procedure(key, title, className, type, apply, clearBefore) {
    this.key = key;
    this.title = title;
    this.className = className;
    this.type = type;
    this.apply = apply;
    this.clearBefore = clearBefore;
    this.activate = function (tooth, sides) {
        if (!tooth) {
            tooth = $('#odontogram .tooth.active');
        }
        if (!sides) {
            sides = $('#odontogram .side.active');
        }
        if (this.className == 'pro_angle' || this.className == 'pro_angle_done') {
            let thisPro = this;
            $(sides).each(function () {
                if ($(this).data('side') == 'left' || $(this).data('side') == 'right') {
                    if (thisPro.clearBefore) {
                        clearSideProcedures(false, this);
                    }
                    $(this).addClass(thisPro.className);
                    addChanges({
                        "tooth": tooth.data('id'),
                        "pro": thisPro.key,
                        "title": thisPro.title,
                        "side": $(this).data('side'),
                        "type": thisPro.type
                    });
                }
            });
        } else if (this.className == 'pro_restoration' || this.className == 'pro_restoration_done') {
            let thisPro = this;
            $(sides).each(function () {
                if ($(this).data('side') == 'top' || $(this).data('side') == 'bottom') {
                    if (thisPro.clearBefore) {
                        clearSideProcedures(false, this);
                    }
                    $(this).addClass(thisPro.className);
                    addChanges({
                        "tooth": tooth.data('id'),
                        "pro": thisPro.key,
                        "title": thisPro.title,
                        "side": $(this).data('side'),
                        "type": thisPro.type
                    });
                }
            });
        } else if (this.apply == 'side') {
            let thisPro = this;
            $(sides).each(function () {
                if (thisPro.clearBefore) {
                    clearSideProcedures(false, this);
                }
                $(this).addClass(thisPro.className);
                addChanges({
                    "tooth": tooth.data('id'),
                    "pro": thisPro.key,
                    "title": thisPro.title,
                    "side": $(this).data('side'),
                    "type": thisPro.type
                });
            });
        } else { // Definimos los procesos que se completaron y pasamos los datos que necesitamos
            if (this.clearBefore) {
                clearToothProcedures(false, tooth);
            }
            tooth.addClass(this.className);
            addChanges({
                "tooth": tooth.data('id'),
                "pro": this.key,
                "title": this.title,
                "side": false,
                "type": this.type
            });
        }


        hideProceduresPanel();
        console.log(this.title + ' Status:' + this.type);
    };
}



function addChanges(newChanges = []) {

    let oldChanges = JSON.parse($('#procedures').val());
    oldChanges.push(newChanges)
    $('#procedures').val(JSON.stringify(oldChanges));
    console.log(oldChanges);

    var ul = $("<ul></ul>"); // Creamos un elemento ul
    var pendiente = new Array();
    for (let i = 0; i < oldChanges.length; i++) {
        var li = $("<li></li>") // Creamos un elemento li
        // Añadimos el title al elemento li
        li.append(oldChanges[i].title + " (" + oldChanges[i].type + ")");
        if (oldChanges[i].type === 'Pendiente') {
            pendiente.push(oldChanges[i]);

        }
        console.log(pendiente);
        ul.append(li); // Añadimos el li inicial al ul inicial.
    }
    $("#lista").html(ul);

}




/**
 * Mostrar en ocultar la sección de dientes primarios o dientes de leche
 */
function togglePrimaryTeethSection() {
    if ($('.primary').hasClass('show_primary')) {
        $('.primary').removeClass('show_primary').fadeOut();
        $('#showsPrimary').val(0);
    } else {
        $('.primary').addClass('show_primary').fadeIn();
        $('#showsPrimary').val(1);
    }
}




/**
 *  Muestra el menu de los procedimientos
 */
function showProceduresPanel() {
    $('#offcanvas-menu').addClass('show');
    $('.app-sidebar').addClass('d-none');
    $('#offcanvas-bg').fadeIn();
    $('#TooltipDemo').addClass('d-none');
}

// Oculta el menu de los procedimientos
function hideProceduresPanel() {
    $('#offcanvas-menu').removeClass('show');
    $('#offcanvas-bg').fadeOut();
    $('#odontogram .active').removeClass('active');
    $('#main-menu').removeClass('d-none');
    $('#TooltipDemo').removeClass('d-none');
}

// Oculta o muestra el menu de los procedimientos
function toggleProceduresPanel() {
    if ($('#offcanvas-menu').hasClass('show')) {
        hideProceduresPanel();
    } else {
        showProceduresPanel();
    }
}

//Regresa true si se ve el procedimiento
function isShowProceduresPanel() {
    return $('#offcanvas-menu').hasClass('show');
}



// Limpia el diente seleccionado de todos los procedimientos
function clearToothProcedures(with_confirm = true, tooth = false) {
    if (!with_confirm || confirm('Está seguro de borrar todos los procesos sobre este diente?')) {
        if (!tooth) {
            tooth = $('#odontogram .tooth.active');
        }
        let sides = $(tooth).find('.side');
        //remove tooth classes
        $(tooth).removeClass(function (index, className) {
            return (className.match(/(^|\s)pro_\S+/g) || []).join(' ');
        });
        //remove tooth procedures
        let changes = JSON.parse($('#procedures').val());
        let findings = [];
        for (var i = changes.length - 1; i >= 0; i--) {
            if (changes[i].tooth == $(tooth).data('id')) {
                changes.splice(i, 1);
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


// Limpia el lado seleccionado de los procedimientos
function clearSideProcedures(with_confirm = true, sides = false) {
    if (!with_confirm || confirm('Está seguro de borrar todos los procesos sobre este cara?')) {
        if (!sides) {
            sides = $('#odontogram .tooth.active .side.active');
        }
        //remove sides classes
        $(sides).removeClass(function (index, className) {
            return (className.match(/(^|\s)pro_\S+/g) || []).join(' ');
        });
        //remove side procedures form changes
        let changes = JSON.parse($('#procedures').val());
        $(sides).each(function () {
            let side = this;
            changes.forEach(function (change, index) {
                if (change.tooth == $(side).closest('.tooth').data('id') &&
                    change.side == $(side).data('side')) {
                    changes.splice(index, 1);
                }
            });
        });

        $('#procedures').val(JSON.stringify(changes));
        hideProceduresPanel();
    }
}



/***** Dientes  ****/


// Marque un lado y su diente como activos
function markSide(side) {
    if ($(side).hasClass('active')) {
        $(side).removeClass('active');
    } else {
        $(side).addClass('active');
        markTooth($(side).closest('.tooth'));
    }
}

// Marcar el diente como activo
function markTooth(tooth) {
    $(tooth).addClass('active');
    showProceduresPanel();
}
