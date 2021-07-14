/**
 * @author Michael Avilan <mavilan@intelcost.com>
 */
var IndicatorsController = {
    a_resources : {
        config: '../ic_js/core/views/indicators/indicators_config.json?cache=1F',
        api: '../paa-stats/' 
    },
    a_config_data:null,
    a_combos_map:[],
    a_combos_esp_map:[],
    a_chart: null,
    a_data: [],
    a_data_providers : [],
    a_dimensiones:[],
    a_filtered_data: [],
    a_form: {},
    a_table: null,
    a_centinela : false,
    a_colors:[],
    
    /**
     * The constructor method
     */
    init: function() {
        IndicatorsController.a_resources.api = IndicatorsController.getAPIPath();
        IndicatorsController._loadConfigurationFile();
    },
    /**
     * Returns the API base URL
     */
    getAPIPath:function(){
        var path = '';
        switch(sessionStorage.navegacionActual){
            case 'pac/lineas/pac_linea_indicadores':
                path = '../Apis/paa-stats/';
            break;
            case 'requisiciones/requisiciones_indicadores':
                path = '../Apis/requisitions-stats/';
            break;
            case 'ofertas/indicadores/indicadores_estudios':
                path = '../Apis/market-researchs-stats/';
            break;
            case 'ofertas/indicadores/indicadores_ofertas':
                path = '../Apis/rfq-stats/';
            break;
            case 'aoc/aoc_indicadores':
                path = '../Apis/aoc-stats/';
            break;
            case 'ofertas/indicadores/indicadores_rfq':
                path = '../Apis/rfq-stats/';
            break;
            case 'contratos/contratos_indicadores':
                path = '../Apis/contracts-stats/';
            break;
        }
        return path;
    },
    /**
     * Loads the configuration file and initilizes the local variables 
     */
    _loadConfigurationFile:function(){
        AjaxDelegate.request(
            {
                type: 'GET',
                url: IndicatorsController.a_resources.config,
                data: {},
                dataType: 'json'
            }, 
            function(data){
                var tennants = [];
                IndicatorsController.a_config_data = data;
                for(var i=0;i<IndicatorsController.a_config_data.modules.length;i++){
                    if(
                        IndicatorsController.a_config_data.modules[i].route === sessionStorage.navegacionActual
                    ){
                        IndicatorsController.a_combos_map = data.modules[i].combos;
                        if(data.modules[i].especialCombos !== undefined){
                            IndicatorsController.a_combos_esp_map = data.modules[i].especialCombos;
                        }
                        tennants = data.modules[i].tennants;
                    }
                }
                for(var i=0;i<tennants.length;i++){
                    if(tennants[i].tennant === Number(sessionStorage.empresaid)){
                        IndicatorsController.a_dimensions = tennants[i].dimensions
                    }
                }
                //add listeners de los input de fecha Ocensa
                IndicatorsController.addListeners();
                IndicatorsController.requestLines();
            },
            function(data){
                //Fault
            }
        );
    },
    /**
     * Calls the lines service and populates the dimensions combo
     */
    requestLines: function() {
        DomUtils.showLoading('Cargando datos. Este proceso puede tomar algunos momentos, por favor espere');
        $.ajax({
            type: 'GET',
            url: IndicatorsController.a_resources.api + 'lines?client='+sessionStorage.empresaid,
            data: {},
            dataType: "json"
        }).done(function(data) {
            if(data.items.length > 0){
                IndicatorsController.a_data = data.items;
                IndicatorsUtils._populateCombos
                (
                    IndicatorsController.a_data, 
                    IndicatorsController.filterChange
                );
                DomUtils.populateCombo
                (
                    'dimensiones_combo', 
                    IndicatorsUtils.getOptions(IndicatorsController.a_dimensions)
                );
                //add listener especial Combos
                IndicatorsUtils.__setListenerEspecialCombos(
                    IndicatorsController.setEventEspCombos
                );
                var combo = document.getElementById('dimensiones_combo');
                combo.selectedIndex = 1;
                combo.dispatchEvent(new Event('change'));
            } else{
                swal.fire(
                    'Sin datos',
                    'No se han encontrado datos para generar los indicadores.',
                    'info'
                );
            }          
        }).fail(function(error) {
            console.log(error);
        });
    },
    /**
     * Attach the component events to its listeners
     */
    addListeners: function() {
        var dimensiones_combo = document.getElementById('dimensiones_combo');
        dimensiones_combo.addEventListener('change', IndicatorsController.dimensionesChange);
    },
    /**
     * This method is executed when the dimensions combo change it selected value
     * @param {*} event - The onchange event
     */
    dimensionesChange:function(event){
        DomUtils.showLoading('Filtrando información. Este proceso puede tomar algunos momentos, por favor espere');
        let msg = document.getElementById('noDim');
        msg.style.display = 'none'; 
        //reset de combos normales 
        for(var i=0;i<IndicatorsController.a_combos_map.length;i++){
          var combo = document.getElementById(IndicatorsController.a_combos_map[i].id);
          if(combo!=null){
              combo.selectedIndex=0;
              if(event.target.value === 'NULL'){
                  combo.disabled=true;
              }else{
                  combo.disabled=false;
              }
              if(event.target.value !== 'NULL' && IndicatorsController.a_centinela===true){                
                combo.dispatchEvent(new Event('change'));
              }
          }           
        }
        //reset combos especiales
        for(var i=0;i<IndicatorsController.a_combos_esp_map.length;i++){
            var combo = document.getElementById(IndicatorsController.a_combos_esp_map[i].id);
            if(combo!=null){
                combo.value = '';
                if(event.target.value === 'NULL'){
                    combo.disabled=true;
                }else{  
                    combo.disabled=false;
                }
            }           
        }
        if(event.target.value!='NULL' && event.target.value!=''){
            var data = IndicatorsUtils.groupByAttribute({ property: JSON.parse(event.target.value).value }, IndicatorsController.a_data);
            IndicatorsController.renderChart('chart_div', data.chartData, 300, 'BarChart', 'Número de líneas');
                if (data.chartValueData.length > 1) {
                IndicatorsController.renderChart('seccondChart_div', data.chartValueData, 300, 'BarChart', 'Valor total');
                
                } else {
                    var c1 = document.getElementById('chart_div');
                    var c2 = document.getElementById('seccondChart_div');
                    c1.innerHTML = 'No se encontraron líneas con la dimensión seleccionada';
                    if (c2 != null) {
                        c2.innerHTML = '';
                }
            }
            }else{
                DomUtils.hideLoading();
                $('.chart_paa_ind').html('');
                msg.style.display = 'block'; 
                let table = document.getElementById('data_items_div');
                table.innerHTML = `<tr><td colspan="4">Es necesario seleccionar una dimensión para poder agrupar datos y graficarlos</td></tr>`;
            }
        IndicatorsController.a_centinela=true;
    },
    /**
     * This method is executed when any of the filters combo change its selected value
     * @param {*} event 
     */
    filterChange:function(event=null,filtered = null){
        IndicatorsController.setEventEspCombos(event);
    },   
    //funcion que se ejecutacuano los combos especiales cambian
    setEventEspCombos: function(event){
        let mapEspCom = IndicatorsController.a_combos_esp_map;
        let dataLn = IndicatorsController.a_data;
        let dataF = undefined;
        for (let j = 0; j < mapEspCom.length; j++) {
            //evaluamos el tipo de input
            switch (mapEspCom[j].type) {
                case "date":
                    if(dataF == undefined){
                        dataF = IndicatorsController.filtraFechaLinea(mapEspCom[j],dataLn);
                    }else{
                        dataFAux = dataF;
                        dataF = IndicatorsController.filtraFechaLinea(mapEspCom[j],dataFAux);
                    }                    
                    break;        
                default:
                    dataF = dataLn;
                    break;
            }   
        }
        IndicatorsController.filteredData(event,dataF);
    },
    //funbcion que evalua las fechas de las lineas
    filtraFechaLinea: function(elementMap, dataLn){
        let data = dataLn;
        let dataF = [];
        let el = document.getElementById(elementMap.id);
        if(el !== null){
            if(el.value != ""){
                let dateI = el.value.split("-");
                let mes = dateI[1] - 1;
                //date(dia,mes,año)
                let date1 = new Date(dateI[0],mes,dateI[2]);
                date1 = date1.getTime() / 1000; //unix
                for (let i = 0; i < data.length; i++) {
                    if(data[i][elementMap.property]){
                        let date2 = data[i][elementMap.property].split(" ");
                        date2 = date2[0].split("-");
                        let mes2 = date2[1] - 1;
                        date2 = new Date(date2[0],mes2,date2[2]);
                        date2 = date2.getTime() / 1000; //unix
                        if(eval(date2 + elementMap.operator + date1)){
                            dataF.push(data[i]);
                        }
                    }
                }
                return dataF;
            }
        }
        return data;
    },
    filteredData:function(event=null,filtered = null){
        let msg = document.getElementById('noData');
        msg.style.display = 'none'; 
        $('.chart_paa_ind').html('');
        var dim_combo=document.getElementById('dimensiones_combo');
        var data = [];
        //var index = IndicatorsController.getIndex(event.target.id);
        DomUtils.showLoading('Filtrando información. Este proceso puede tomar algunos momentos, por favor espere');
        //filtramos informacion segun los combos
        for(var i=0;i<IndicatorsController.a_combos_map.length;i++){
            var combo = document.getElementById(IndicatorsController.a_combos_map[i].id);
            if(filtered === null && combo !=null){
                //la funcion _getFilteredData filtra con un for y una condicional
                //segun la propiedad seleccionada
                filtered = 
                IndicatorsController._getFilteredData
                (
                    IndicatorsController.a_data, 
                    IndicatorsController.a_combos_map[i].property,
                    combo
                );
            }else if(combo !=null){
                //aca ya enviamos informacion previamente
                //filtrada
                filtered = 
                IndicatorsController._getFilteredData
                (
                    filtered, 
                    IndicatorsController.a_combos_map[i].property,
                    combo
                ); 
            }
        }

        if(filtered.length>0){
            if(dim_combo.value!='NULL'){
                data = IndicatorsUtils.groupByAttribute
                (
                    { property: JSON.parse(dim_combo.value).value }, 
                    filtered
                );
            }
            
            if(event!=null){
                //Se llenan los combos de nuevo con informacion ya filtrada 
                IndicatorsUtils._populateCombos(filtered, IndicatorsController.filterChange, event.target.id);
             }
            //renderizamos las graficas
            IndicatorsController.renderChart
            (
                'chart_div', data.chartData, 300, 'BarChart', 'Dimensión vs número de líneas'
            );
            if(data.chartValueData.length>1){
                IndicatorsController.renderChart
                (
                    'seccondChart_div', data.chartValueData, 300, 'BarChart', 'Dimensión vs valor total'
                );
            }
            //mostrar graficos especificos, funcion ejecutada solo para Ocensa        
            if(sessionStorage.getItem('empresaid') == 20){
                IndicatorsController.renderEspChart(filtered);  
            }  
        }else{
            let table = document.getElementById('data_items_div');
            msg.style.display = 'block'; 
            table.innerHTML = `<tr><td colspan="4">El filtro seleccionado no genero datos</td></tr>`;
            DomUtils.hideLoading();
        }       
    },
    //mostrar graficos especificos, funcion ejecutada solo para Ocensa
    renderEspChart: function(dataF){
        var dim_combo = document.getElementById('dimensiones_combo');
        var planeacion_combo = document.getElementById('planeacion_combo');
        if(dim_combo.value !== "" && dim_combo.value !== 'NULL'){
            let dimInfo = JSON.parse(dim_combo.value);
            if(dimInfo.value == "estado_cliente"){
                IndicatorsController.renderPieChartEstates(dataF);
            }
        }
        if(planeacion_combo !== null){
            if(planeacion_combo.value !== "" && planeacion_combo.value !== 'NULL'){
                IndicatorsController.calcPorcentPlaneacion(planeacion_combo,dataF);
            }
        }
        //IndicatorsController.calcPorcentPlaneacion(event.target,filtered);
    },
    //funcion que renderiza un grafico de torta para mostrar los diferentes estados
    //de las Lineas
    renderPieChartEstates: function(dataF){
        var dim_combo=document.getElementById('dimensiones_combo');
        data = IndicatorsUtils.groupByAttribute
        (
            { property: JSON.parse(dim_combo.value).value }, 
            dataF
        );
        IndicatorsController.renderChart('pieChart_div', data.chartData, 50, 'PieChart', 'Estados de Línea',false,true);
    },
    //funcion que calcula el porcentaje de lineas
    //planeadas vs no planeadas y viceversa
    calcPorcentPlaneacion: function (target,dataF){
        let val = JSON.parse($('#'+target.getAttribute("id")).val());
        var dim_combo=document.getElementById('dimensiones_combo');
        if(dim_combo.value !== '' && dim_combo.value !== 'NULL'){
            dim_combo = JSON.parse(dim_combo.value);
        }
        let dataChart = IndicatorsUtils.__formatInfoLineasPlaneadas(dataF,IndicatorsController.a_data,val.value,dim_combo.value);
        IndicatorsController.renderChart
        (
            'porcPlaneados', dataChart, 65, 'BarChart', 'Líneas totales vs ' + val.value, true
        ); 
    },
    getIndex:function(id){
        for(var i=0;i<IndicatorsController.a_combos_map.length;i++){
            if(IndicatorsController.a_combos_map[i].id === id){
                return i;
            }
        }
    },
    /**
     * Returns the lines filtered
     * @param {array} items 
     * @param {string} property 
     * @param {*} value 
     */
    _getFilteredData:function(items, property, combo){
        var filtered = [];
        if(combo!=null && items!=undefined){
            for(var i=0;i<items.length;i++){
                if(combo.selectedOptions.length>0){
                    for(var j=0;j<combo.selectedOptions.length;j++){
                        var value = combo.selectedOptions[j].value;
                        if(value!='NULL'){
                            var value = JSON.parse(value).value;
                            if(items[i][property] === value){
                                filtered.push(items[i]);
                            }
                        }else{
                            filtered.push(items[i]);
                        }
                    }
                }else{
                    filtered.push(items[i]);
                }            
            }
            return filtered;
        }        
    },
    /**
     * Renders a Google Chart
     * @param {string} id - The chart container ID 
     * @param {*} chartData - The chart configuration Data 
     * @param {*} height  - The height of the chart expressed in pixels
     * @param {*} chartType - The chart type to render
     * @param {string} title - The chart title 
     */
    renderChart: function(id, chartData=[], height1=400, chartType='BarChart', title='', colorFijo = false, heightFijo = false) {
        if(!colorFijo){
            if(chartData.length-1 != IndicatorsController.a_colors.length){
                IndicatorsController.a_colors[0];
            }
            for(var i=1;i<chartData.length;i++){
                IndicatorsController.a_colors.push('color:'+IndicatorsController.generateRandomHexColor());
                chartData[i].push( IndicatorsController.a_colors[i-1] );
                chartData[i][0] = IndicatorsRenderers._getTranslation(chartData[i][0]);
            }
        }

        google.charts.load('current', {'packages':['corechart']});
        /**
         * Renders the charts into the GUI
         */
        function drawChart() {
            var height = (colorFijo || heightFijo) ? (height1*chartData.length) : (chartData.length) * 30;
            var data = google.visualization.arrayToDataTable(chartData);  
            var width = window.screen.availWidth * 0.40;
            var options = IndicatorsController.a_config_data.charts_definition;
            var chart = new google.visualization[chartType](document.getElementById(id));
            var el =document.getElementById(id);
            var gCanvas = document.getElementById('graphic_canvas');

            if(sessionStorage.navegacionActual === 'ofertas/indicadores/indicadores_estudios'){
                width = window.screen.availWidth;
            }    
            
            options.height = height;
            /*options.width = width;*/
            options.width = '100%';
            options.title = title;
            options.hAxis.textStyle.fontSize = 10;
            options.vAxis.textStyle.fontSize = 10;
            el.setAttribute('style', 'text-align: -webkit-center');

            google.visualization.events.addListener(chart, 'ready', function () {
                DomUtils.hideLoading();
            }); 
            chart.draw(data, options);
            /*if (chartData.length > 18) {
                gCanvas.setAttribute('style', 'height: ' + height * 0.8 + 'px; overflow-y:auto; overflow-x:auto; ');
                el.setAttribute('style', 'margin-top:-' + (height * 0.1 + 'px'));
            } else {
                gCanvas.setAttribute('style', 'height: ' + (height + 15) + 'px; overflow-y:auto; overflow-x:auto; ');
                el.setAttribute('style', 'margin-top:0px');
            }*/
        }        
        google.charts.setOnLoadCallback(drawChart);
    },
    generateRandomHexColor: function() {
        let ASCIIArray = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
        let newHexColor = "#";
        let hexSeed;
        for (let i = 0; i < 6; i++) {
            hexSeed = Math.floor(Math.random() * 16);
            newHexColor += ASCIIArray[hexSeed];
        }
        return newHexColor;
    },
    /**
     * Renders an image of the current page to be downloaded as a report
     */
    downloadReport : function(){
        var el = document.getElementById("indicadores_div"); 
        var w = window.offsetWidth;
        var h = window.offsetHeight;
    
        html2canvas(el, {
            scale: 8
        }).then(function(canvas) {
            document.body.appendChild(canvas);
            var el = document.getElementById('downloader_href');
            el.href = canvas.toDataURL("image/jpeg").replace("image/jpeg", "image/octet-stream");
            el.download = 'report.jpg';        
            el.click();
            document.body.removeChild(canvas);
        });
    },
    /**
     * Delegated method to navigate to a contract
     * @param {int} id - Contract ID
     */
    gotoContract:function(id){
        //Codificar id del contrato
        accederVistaInformacionContrato(SHA256(String(id)));
    }
}