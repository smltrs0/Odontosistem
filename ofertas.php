<?php
use Illuminate\Database\Capsule\Manager as DB;
use Models\Vistas\Offers;
use Models\Vistas\Activities;
use Models\Ofertas\Oferta;
use Traits\ConexionSOAPJDE;
use Models\Usuarios\Usuario;
use Models\Ofertas\OfertaLote;
use Models\Ofertas\VisitaObra;
use Traits\ViewsTemplateEmail;
use Models\Solicitud\Solicitud;
use Models\Ofertas\OfertaMoneda;
use Models\Ofertas\OrdenPedidos;
use Models\Maestras\UnidadMedida;
use Models\Ofertas\OfertaDocumentos;
use Models\Ofertas\OfertaLogEventos;
use Rap2hpoutre\FastExcel\FastExcel;
use Models\Ofertas\OfertaParticipantes;
use Models\Maestras\MaestraCentrosCosto;
use Models\Ofertas\OfertaAdjudicaciones;
use Models\Proveedores\UsuariosProveedores;
use Models\Ofertas\OfertaDatosAdicionales;
use Models\Ofertas\OfertaCriteriosEvaluacion;
use Models\Ofertas\OfertaDocumentosOferentes;
use Models\Ofertas\OfertaUsuariosPermisosFAQ;
use Models\Ofertas\OfertaLoteColumnaAdicional;
use Models\Ofertas\OfertaAdjudicacionesEmpresas;
use Models\Precalificaciones\PrecalificacionesN;
use Illuminate\Database\Capsule\Manager as Database;
use Models\Ofertas\OfertaLoteItemProveedorAdicional;
use Models\Ofertas\OfertaDocumentosCriteriosOferentes;
use Models\Ofertas\RelacionOfertaDocumentosCategorias;
use Models\Bibliotecas\RelacionBibliotecaItemsCategorias;
use Models\Ofertas\OfertaLoteItemInformacionAdicionalOdl;
use Models\Ofertas\OfertaUsuariosPermisosCriteriosTecnicos;
use Models\Ofertas\OfertaCriteriosEvaluacionDatosAdicionales;
use Models\Ofertas\OfertaEvaluacionProveedorResultadoTecnico;
use Models\Ofertas\OfertaDocumentosCriteriosOferentesEvaluaciones;
use Models\Ofertas\OfertaEvaluacionProveedorResultadoTecnicoCriterios;

ini_set('error_log', '../ic_intelcost/logs/log_errors.log'); // Logging file
class modelo_oferta{
    use ConexionSOAPJDE, ViewsTemplateEmail;

    protected static $ambiente, $bd_cliente, $bd_proveedores;

    public function __construct(){
        $this->intelcost = new intelcostClient();
        $this->modelo_usuario = new modelo_usuario();
        $this->modelo_proveedor = new modelo_proveedor();
        //$this->modelo_acciones_oferta = new modelo_acciones_oferta();   //la clase modelo_acciones_oferta se extendió a ofertas.
        //$this->modelo_solpeds = new modelo_solpeds();
        $this->modelo_capitulos = new modelo_capitulos();
        $this->modelo_pdf = new modelo_pdf();
        $this->modeloComunicaciones = new communicationClient();
        $this->modelo_contrato = new modelo_contrato();
        $this->modelo_ofertas_accionesWs = new modelo_ofertas_accionesWs();
        $this->modelo_flujos          = new modelo_flujos_aprobacion();
        $this->modelo_actividad = new modelo_actividad();
        self::$ambiente = $this->intelcost->obtenerAmbienteCliente();
        self::$bd_cliente = env('DB_NAME_DATABASE_CLIENT');
        self::$bd_proveedores = env('DB_NAME_DATABASE_PROVIDER');
    }

    public function listarOfertasIndicadores($request){
        $response = new stdClass();
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

		$allRequest = $request->inputs();

		if($allRequest['method'] != 'POST'){
			header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
			$response->bool = false;
			$response->msg = $_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed";
			return $response;
        }
        if($allRequest['filtros']['tipo'] == 2){
            $ofertasIdsRondas = Oferta::where('id_cliente', $_SESSION['empresaid'])
                                        ->where('estado', 'FINALIZADA')
                                        ->groupBy('seq_id')
                                        ->orderBy('ronda', 'DESC')
                                        ->select([
                                            Database::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT( CONCAT(id) ), ',', 1), ',', -1) AS id_primera_ronda"),
                                            Database::raw("SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT( CONCAT(id) ), ',', COUNT(id)), ',', -1) AS id_ultima_ronda"),
                                            'seq_id'
                                        ])
                                        ->get();
        }

        if($allRequest['filtros']['tipo'] == 1){
            $ofertasDias = Oferta::where('id_cliente', $_SESSION['empresaid'])
                                        ->where(function($query){
                                            $query->where('estado', 'EN EVALUACION')
                                                ->orWhere('estado', 'EN ADJUDICACION')
                                                ->orWhere('estado', 'FINALIZADA');
                                        })
                                        ->get()
                                        ->map(function($oferta){
                                            $oferta->dias = $oferta->dias_oferta;
                                            return ['dias' => $oferta->dias];
                                        });

            $ofertasDias = $ofertasDias->sum('dias') / $ofertasDias->count();
        }

        $ofertas = Oferta::when(!empty($allRequest['filtros']['estado']), function($query) use ($allRequest){
                    $query->where(function($query) use ($allRequest){
                        foreach ($allRequest['filtros']['estado'] as $key => $estado) {
                            if($key == 0){
                                $query->where('estado', 'like', '%'.$estado.'%');
                            }else{
                                $query->orWhere('estado', 'like', '%'.$estado.'%');
                            }
                        }
                    });
                })
                ->when(empty($allRequest['filtros']['estado']), function($query) use ($allRequest){
                    $query->where(function($query){
                        $query->where('estado', 'EN EVALUACION')
                            ->orWhere('estado', 'EN ADJUDICACION')
                            ->orWhere('estado', 'FINALIZADA');
                    });
                })
                ->when(!empty($allRequest['filtros']['secuencia']), function($query) use ($allRequest){
                    $query->where('seq_id', 'like', '%'.$allRequest['filtros']['secuencia'].'%');
                })
                ->when($allRequest['filtros']['tipo'] == 2, function($query) use ($ofertasIdsRondas){
                    $query->whereIn('id', $ofertasIdsRondas->pluck('id_ultima_ronda'));
                })
                ->when(!empty($allRequest['filtros']['comprador']), function($query) use ($allRequest){
                    $query->whereIn('usuario_creacion', $allRequest['filtros']['comprador']);
                })
                ->when(!empty($allRequest['filtros']['centro_costo']), function($query) use ($allRequest){
                    $query->whereIn('id_area', $allRequest['filtros']['centro_costo']);
                })
                ->when(!empty($allRequest['filtros']['fecha_inicio']), function($query) use ($allRequest){
                    $query->where('fecha_inicio', $allRequest['filtros']['fecha_inicio']);
                })
                ->when(!empty($allRequest['filtros']['fecha_cierre']), function($query) use ($allRequest){
                    $query->where('fecha_cierre', $allRequest['filtros']['fecha_cierre']);
                })
                ->where('id_cliente', $_SESSION['empresaid'])
                ->with('infoUsuarioCreacion')
                ->get()
                ->map(function($oferta) use ($allRequest, $ofertasIdsRondas){
                    if($allRequest['filtros']['tipo'] == 1){
                        $oferta->dias = $oferta->dias_oferta;
                    }

                    if($allRequest['filtros']['tipo'] == 2){
                        $valorAdjudicacion = $oferta->infoProveedoresAdjudicados->sum('valor');
                        $oferta->valor_total_adjudicado = $valorAdjudicacion;
                        $oferta_primera_ronda = Oferta::where('id', $ofertasIdsRondas->where('seq_id', $oferta->seq_id)->pluck('id_primera_ronda')[0])
                                                                                ->first();
                        $oferta->valor_ofertado_ultima_ronda = $oferta->valor_ofertado;                                              
                        $oferta->valor_ofertado_primera_ronda = $oferta_primera_ronda->valor_ofertado;                                              
                    }
                    return $oferta;
                });
        
        if($ofertas->count() > 0){
            $response->bool = true;
            $response->msg = $ofertas;
            if($allRequest['filtros']['tipo'] == 1){
                $response->promedio = $ofertasDias;
            }
        }else{
            $response->bool = false;
            $response->msg = 'No se encontraron ofertas';
        }
        return $response;
    }

    public function autoCompletarCentroCostoIndicadores($request){
        $response = new stdClass();
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

		$allRequest = $request->inputs();

		if($allRequest['method'] != 'POST'){
			header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
			$response->bool = false;
			$response->msg = $_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed";
			return $response;
        }


        $centrosCosto = MaestraCentrosCosto::where('id_cliente', $_SESSION['empresaid'])
                                            ->where('estado', 'activo')
                                            ->select([
                                                'nombre',
                                                'codigo',
                                                'id'
                                            ])
                                            ->get();

        if($centrosCosto->count() > 0){
            $response->bool = true;
            $response->msg = $centrosCosto;
        }else{
            $response->bool = false;
            $response->msg = 'No se encontraron centros de costo';
        }
        return $response;
    }

    public function desvicularPrecalificacion($request){
        $response = new stdClass();
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

		$allRequest = $request->inputs();

		if($allRequest['method'] != 'POST'){
			header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
			$response->bool = false;
			$response->msg = $_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed";
			return $response;
        }
        
        if($allRequest['oferta_id']){
            OfertaDatosAdicionales::where('oferta_id', $allRequest['oferta_id'])
                    ->update(['precalificacion_id' => null]);
            
            $response->bool = true;
            $response->msg = 'Se ha eliminado correctamente la precalificación asociada a la oferta';
            return $response;
        }else{
            header("406 Not Acceptable", true, 406);
            $response->bool = false;
			$response->msg = 'Action Not Allowed';
			return $response;
        }

    }

    public function adjudicacionConfa(Request $request){
        $response = new stdClass();
        // Valida que tenga sesión
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

        // Valida que tenga el id de la oferta
        if (!$request->idOferta) {
            header("406 Not Acceptable", true, 406);
            $response->bool = false;
			$response->msg = 'Action Not Allowed';
			return $response;
        }

        // Consultar oferta
        $oferta = Oferta::with('infoAdicionalesOferta')->find($request->idOferta);

        // Si tiene el objeto de flujos de aprobación, generará le flujo de aprobación correspondiente
        if ($request->parametrosFlujos && $oferta->ronda == 1) {
            switch($request->parametrosFlujos['nivelAprobadores']){
                case "1":
                        //Perfil jefatura de compras
                        $id_paso = 75;
                        $usuarios = Usuario::where('id_perfil', 155)->get();
                        $arrayUsuarios = $usuarios->map(function($usuario) use ($id_paso){
                            return $usuario->id;
                        });

                    break;
                case "2":
                        //Perfil jefatura y gerencia correspondiente
                        $id_paso = 75;
                        $centro_costo = MaestraCentrosCosto::find($oferta->id_area);
                        if($centro_costo){
                            $centro_costo_gerencia = $centro_costo->load('relacionGerenciaServicio.gerencia');
                            $centro_costo_gerencia->relacionGerenciaServicio->gerencia->nombre = mb_strtoupper($centro_costo_gerencia->relacionGerenciaServicio->gerencia->nombre, 'UTF-8');
                            //$centro_costo = $centro_costo_gerencia;  
                            $ids_usuarios = $centro_costo_gerencia->relacionGerenciaServicio->gerencia->relacionUsuarios->map(function($usuario){
                                return $usuario->infoUsuario->id;
                            });
                        }

                        $usuarios = Usuario::where('id_perfil', 155)
                                            ->orWhereIn('id', $ids_usuarios)
                                            ->get();

                        $arrayUsuarios = $usuarios->map(function($usuario) use ($id_paso){
                            return $usuario->id;
                        });
                    break;
                case "3":
                        //Perfil jefatura y comité <- usuario único
                        $id_paso = 75;
                        $usuarios = Usuario::where('id_perfil', 155)
                                            ->orWhere('id', 3826)
                                            ->get();
                        $arrayUsuarios = $usuarios->map(function($usuario) use ($id_paso){
                            return $usuario->id;
                        });
                    break;
                case "4":
                        //Jefe de compras y consejo directivo
                        $id_paso = 75;
                        $usuarios = Usuario::where('id_perfil', 155)
                                            //->orWhere('id', 3663)
                                            ->get();
                        $arrayUsuarios = $usuarios->map(function($usuario) use ($id_paso){
                            return $usuario->id;
                        });
                    break;     
                default:
                        $response->bool = false;
                        $response->msg = 'Hubo un error al identificar el tipo de aprobación, por favor vuelva a ingresar a al proceso / evento';
                        return $response;
                    break;
            }
            $arrayUsuarios = [
                'id_usuario' => $arrayUsuarios->toArray(),
                'id_paso' => $id_paso
            ];

            $this->modelo_flujos->cambiarEstadoAprobacionesEliminadas($request->idOferta, $request->parametrosFlujos['id_modulo'], $id_paso);
            $respuesta_flujo = $this->modelo_flujos->cargarPerfilesAprobadoresRequeridos(5, $request->idOferta, array($arrayUsuarios));
        }
        
        $oferta->timestamps = false; 
        if($oferta->ronda == 1){
            $oferta->estado = "EN APROBACION";
        }else{
            $oferta->estado = "FINALIZADA";
        }
        $oferta->save();

        (new modelo_acciones_oferta)->finalizarAdjudicacion($oferta->id, json_encode($request->porAdjudicar), '', 'aprobacion', [], $oferta->ronda);
        $response->bool = true;
		$response->msg = 'Se ha guardado correctamente la adjudicación';
		return $response;
    }

    public function actualizarActaAdjudicacion(Request $request){
        $response = new stdClass();
        // Valida que tenga sesión
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

        // Valida que tenga el id de la oferta
        if (!$request->id_oferta) {
            header("406 Not Acceptable", true, 406);
            $response->bool = false;
			$response->msg = 'Action Not Allowed';
			return $response;
        }

        // Consultar oferta
        $oferta = Oferta::with('infoAdicionalesOferta')->find($request->id_oferta);
        $oferta->carta_adjudicacion = $request->ruta;
        $oferta->timestamps = false;
        $oferta->save();

        $response->bool = true;
		$response->msg = 'Se ha guardado correctamente la adjudicación';
		return $response;
    }

    public function guardarPermisosFAQ(Request $request){
        if (!$request->id_oferta && !$request->permisos) {
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Parámetros incorrectos";
            return $this->intelcost->response;
        }

        Database::transaction(function() use ($request){
            foreach ($request->permisos as $key => $permiso) {
                if ($key == 0) {
                    OfertaUsuariosPermisosFAQ::where('id_oferta', $request->id_oferta)
                                            ->where('id_usuario', $permiso['id_usuario'])
                                            ->where('estado', 1)
                                            ->update([
                                                'estado' => 2
                                            ]);
                }
                
                $update_or_create = [
                    'id_usuario' => $permiso['id_usuario'],
                    'id_oferta' => $request->id_oferta,
                    'id_categoria' => !empty($permiso['id_categoria']) ? $permiso['id_categoria'] : null,
                    'created_at' => date('Y-m-d H:s:i'),
                    'estado' => 1
                ];
                $conditional = [
                    'id_usuario' => $permiso['id_usuario'],
                    'id_oferta' => $request->id_oferta,
                    'id_categoria' => !empty($permiso['id_categoria']) ? $permiso['id_categoria'] : null
                ];

                OfertaUsuariosPermisosFAQ::updateOrCreate($conditional, $update_or_create);
            }
        });
        $respuesta = OfertaUsuariosPermisosFAQ::where('id_oferta', $request->id_oferta)->where('estado', 1)->get();
        // $agruparPorUsuario = [];
        // foreach ($respuesta as $key => $permisoCategoria) {
        //     if ($permisoCategoria->id_categoria != 0) {
        //         $permisoCategoria->scopeCategoria($permisoCategoria, $permisoCategoria->id_categoria);
        //         $permisoCategoria->nombre = $permisoCategoria->categoriaPermiso->nombre;
        //     }else{
        //         $permisoCategoria->nombre = "Otros";
        //     }
        //     $agruparPorUsuario[$permisoCategoria['id_usuario']][] = $permisoCategoria; 
        // }
        // $respuesta = $agruparPorUsuario;

        $return = new stdClass();
        $return->bool = true;
        $return->msg = $respuesta;
        return $return;
    }

    public function proximaOferta(){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlOfer="SELECT MAX(id) FROM ofertas";
            $CscOfer = $dbConection->query($SqlOfer);
            $results = $CscOfer->fetch_array();
            if($results){
                $cur_auto_id = $results['MAX(id)'] + 1;
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =  $cur_auto_id;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    function subirDecimal($numero, $precision = 2){
        $precision = (int) str_pad('1', $precision, '0');
        return (ceil($numero * $precision) / $precision);
    }

    public function obtenerOfertasParaContratos($filtros, $pagina) {
        $tieneParametros = false;
        $tipoParametros = "";
        $parametros = array();
        $cantidad_busqueda = $_SESSION['empresaid'] == 8 ? 10 : 15;

        $SqlQuery = "SELECT OF.id,OF.seq_id,LOWER(OF.objeto) objeto,OF.fecha_actualizacion,LOWER(US.nombre) nombre FROM ofertas OF INNER JOIN usuarios US ON OF.usuario_creacion = US.id ";
        if ($_SESSION["empresaid"] == 10) {
            $SqlQuery.=" LEFT JOIN aoc USING (id_aoc) ";
            $SqlQuery .=" WHERE OF.estado='FINALIZADA' AND OF.id_cliente='" . $_SESSION["empresaid"] . "' AND OF.tipo='rfq' AND aoc.id_abogado ='".$_SESSION["idusuario"]."' ";
        }else{
            $SqlQuery .=" WHERE OF.estado='FINALIZADA' AND OF.id_cliente='" . $_SESSION["empresaid"] . "'";
            if($_SESSION["empresaid"] == 8){
                $SqlQuery .=" AND CAST(OF.fecha_actualizacion AS DATE) >= '2019-02-01' ";
            }else if($_SESSION["empresaid"] == 20){
                $SqlQuery .=" AND OF.tipo != 'estudio' ";
            }
        }
        if (isset($filtros["id"]) && !empty($filtros["id"])) {
            $tieneParametros = true;
            $SqlQuery .= " AND OF.seq_id LIKE ? ";
            $sql_total_busqueda .= " AND OF.seq_id LIKE ? ";
            $tipoParametros .= "s";
            array_push($parametros, "%" . $filtros["id"] . "%");
        }
        if (isset($filtros["objeto"]) && !empty($filtros["objeto"])) {
            $tieneParametros = true;
            $SqlQuery .= " AND OF.objeto LIKE ? ";
            $tipoParametros .= "s";
            array_push($parametros, "%" . $filtros["objeto"] . "%");
        }
        if ($_SESSION["empresaid"] == 10) {
            if (isset($filtros["comprador"]) && !empty($filtros["comprador"])) {
                $tieneParametros = true;
                $SqlQuery .= " AND OF.usuario_creacion = ? ";
                $tipoParametros .= "s";
                array_push($parametros, "" . $filtros["comprador"] . "");
            }
            if (isset($filtros["centroCosto"]) && !empty($filtros["centroCosto"])) {
                $tieneParametros = true;
                $SqlQuery .= " AND aoc.id_centro_gestor = ? ";
                $tipoParametros .= "s";
                array_push($parametros, "" . $filtros["centroCosto"] . "");
            }
        }
        $limitPag = $this->intelcost->paginacion_limit_inicio_fin($pagina, $cantidad_busqueda);
        
        /* Consulta de cantidad */ 
        $csc_total_busqueda = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', $tieneParametros, $tipoParametros, $parametros, "Cantidad de registros ofertas para contratos.");
        $total_registros = $csc_total_busqueda->msg->num_rows;
        $paginas_totales = (int) $this->subirDecimal(($total_registros == 0 ? 1 : $total_registros) / 15, 1);

        $SqlQuery .= " ORDER BY OF.seq_id DESC LIMIT " . $limitPag['inicio'] . ", " . $limitPag['fin'];
       
        $sqlOfertas = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', $tieneParametros, $tipoParametros, $parametros, "Obtener listado ofertas para contratos.");
        $modelo_ofertas_cuadro_cotizacion = new OfertasCuadroCotizacion();
        if ($sqlOfertas->bool) {
            if ($sqlOfertas->msg->num_rows > 0) {
                $rows = array();
                while ($row = $sqlOfertas->msg->fetch_assoc()) {
                    array_push($rows, $row);
                }
                $arrResultado = array();
                foreach ($rows as $row) {
                    $respuestaAsociacion = $this->modelo_contrato->validarAsociacionOfertaContrato($row["id"], $modelo_ofertas_cuadro_cotizacion);

                    if ($respuestaAsociacion->bool) {
                        $row["nombre"] = ucwords($row["nombre"]);
                        //Se valida para poder convertir el primer caracter en mayuscula por si vienen un carcater especial no salga undefined
                        $row["objeto"] = mb_strtoupper(mb_substr($row["objeto"],0,1)).mb_substr($row["objeto"],1);
                        
                        if (!empty($row["fecha_actualizacion"]) && $row["fecha_actualizacion"] != "0000-00-00") {
                            $row["fecha_adjudicacion"] = $this->intelcost->castiarFechayHoraIntelcost($row["fecha_actualizacion"]);
                        } else {
                            $row["fecha_adjudicacion"] = "N/A";
                        }
                        $row["id"] = hash("sha256", $row["id"]);
                        array_push($arrResultado, json_encode($row));
                    }
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrResultado);
                $this->intelcost->response->paginas = $paginas_totales;
            } else {
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontraron resultados.";
            }
        } else {
            $this->intelcost->response = $sqlOfertas;
        }
        return $this->intelcost->response;
    }

    public function obtenerOfertaBasicaCabecera($cod_oferta, $find = ['*']){
        $find = implode(',', (array) $find);

        $sql_query_oferta = "SELECT $find FROM ofertas WHERE id_cliente = $_SESSION[empresaid] AND (id = ? OR SHA2(id, '256') = ?)";

        $csc_oferta = $this->intelcost->prepareStatementQuery('cliente', $sql_query_oferta, 'select', true, 'is', array((int) $cod_oferta, $cod_oferta), "Obtener oferta");

        if ($csc_oferta->bool) {
            if ($csc_oferta->msg->num_rows > 0) {
                $oferta = $csc_oferta->msg->fetch_assoc();
            }else{
                $oferta = [];
            }
        }else{
            $oferta = [];
        }
        $csc_oferta->msg = (object) $oferta;
        return $csc_oferta;
    }

    public function obtenerInformacionOfertaParaContrato($codOferta){
        if($_SESSION["empresaid"]!=10){
            $consulta_oferta = $this->obtenerOfertaBasicaCabecera($codOferta, ['id']);
            // $pcc = (new OfertasCuadroCotizacion())->obtenerContratosAsociadosAlCuadro((string) $consulta_oferta->msg->id);
            // if ($pcc->bool && count($pcc_decode = json_decode($pcc->msg)) > 0) {
            //     $id_pcc = (is_null($pcc_decode{0}->id_pcc)?0:$pcc_decode{0}->id_pcc);
            // }else{
            //     $id_pcc = "0";
            // }
            //$SqlQuery = "SELECT id,seq_id,actividad,LOWER(ofertas.objeto)objeto,fecha_creacion,usuario_creacion,LOWER(moneda) moneda,maestra1, maestra2, descripcion LEFT JOIN paa_linea_otrocamp FROM `ofertas` WHERE id_cliente ='".$_SESSION["empresaid"]."' AND SHA2(id,'256') = ?";
            $SqlQuery = "SELECT ofertas.id, ofertas.seq_id, ofertas.actividad, LOWER(ofertas.objeto) AS objeto, ofertas.fecha_creacion, ofertas.usuario_creacion, LOWER(ofertas.moneda) AS moneda, ofertas.maestra1, ofertas.maestra2, ofertas.descripcion, paa_campos.area, paa_campos.direccion FROM `ofertas` LEFT JOIN paa_linea_otrocamp AS paa_campos ON paa_campos.pcc_id = \"$id_pcc\" WHERE ofertas.id_cliente ='".$_SESSION["empresaid"]."' AND SHA2(ofertas.id,'256') = ? GROUP BY paa_campos.pcc_id";
            $contratos_asociados = [];
            $solicitud_asociados = [];
            if ($_SESSION["empresaid"]==8) {
                $SqlQuery_contratos_asociados = "SELECT * FROM contratos WHERE SHA2(oferta_id, 256) = ?";
                $sql_contrato_asociado = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery_contratos_asociados, 'select', true, "s", array($codOferta), "Obtener oferta para contrato.");
                if ($sql_contrato_asociado->bool) {
                    while ($row = $sql_contrato_asociado->msg->fetch_assoc()) {
                        array_push($contratos_asociados, $row);
                    }
                }
            }
        }else{
            $SqlQuery="SELECT ofertas.id,ofertas.seq_id,actividad,LOWER(ofertas.objeto) objeto,ofertas.fecha_creacion,ofertas.usuario_creacion, "
                    . "LOWER(ofertas.moneda) as moneda,ofertas.presupuesto,ofertas.maestra1, ofertas.maestra2,aoc_fund_normativo.id_tipo_contrato,mst_tipo_contrato.titulo,"
                    . "aoc_fund_normativo.plazo,aoc_fund_normativo.fecha_inicio,aoc_fund_normativo.fecha_fin,aoc.usuario_solicitante,usuarios.nombre as nombre_solicitante, "
                    . "aoc.id_aoc,aoc.tipo_supervision,aoc.modalidad,aoc.id_centro_gestor,usuarios_area.nombre as nombre_centro_gestor,aoc_fund_normativo.id_forma_pago, "
                    . "aoc.usuario_creacion as id_comprador,US2.nombre as nombre_comprador,paa_linea.clase_adquisicion,requisiciones.id_profesional_contrat as id_abogado,US3.nombre AS nombre_abogado ";
            $SqlQuery.=" FROM `ofertas` ";
            $SqlQuery.="LEFT JOIN aoc USING(id_aoc)LEFT JOIN aoc_fund_normativo USING(id_aoc) ";
            $SqlQuery.="LEFT JOIN mst_tipo_contrato ON (aoc_fund_normativo.id_tipo_contrato=mst_tipo_contrato.id_tipo_contrato) ";
            $SqlQuery.='LEFT JOIN usuarios ON (aoc.usuario_solicitante=usuarios.id) ';
            $SqlQuery.="LEFT JOIN usuarios US2 ON (aoc.usuario_creacion = US2.id) ";
            $SqlQuery.="LEFT JOIN usuarios_area ON (usuarios_area.id=aoc.id_centro_gestor)";
            $SqlQuery.="LEFT JOIN paa_linea ON (paa_linea.id=aoc.id_paa_linea)";
            $SqlQuery.="LEFT JOIN requisiciones ON(requisiciones.id=ofertas.id_requisicion)";
            $SqlQuery.="LEFT JOIN usuarios US3 ON (requisiciones.id_profesional_contrat = US3.id)";
            $SqlQuery.="WHERE ofertas.id_cliente='".$_SESSION["empresaid"]."' AND SHA2(ofertas.id,'256')= ? ";
        }
        $sqlOferta = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', true, "s", array($codOferta), "Obtener oferta para contrato.");

        if($sqlOferta->bool){
            if($sqlOferta->msg->num_rows > 0){
                $row = $sqlOferta->msg->fetch_assoc();
                        $cantContratosOferentes="SELECT COUNT(id) as cant_oferentes FROM contratos WHERE oferta_id ='".$row["id"]."' ";
                $sqlContratosOferentes = $this->intelcost->prepareStatementQuery('cliente', $cantContratosOferentes, 'select', false, "", "", "Obtener info cant contratos con ofertas asociadas.");
                        $resultContratosOferentes = $sqlContratosOferentes->msg->fetch_assoc();
                        if($_SESSION["empresaid"]==10){
//                  $SqlQuerySupervInterv="SELECT aoc_s.*,usu.nombre,con.numero_contrato,CAST(aoc_s.tipo AS UNSIGNED) as tipo_id FROM `aoc_supervision` aoc_s LEFT JOIN usuarios usu ON(aoc_s.id_supervisor=usu.id) LEFT JOIN contratos con ON(con.id=aoc_s.id_interventor) WHERE aoc_id_aoc='".$row["id_aoc"]."'";
                    $SqlQuerySupervInterv="SELECT aoc_tipo.*,"
                                ."IF (aoc_tipo.tipo = 'supervisor',(SELECT usu.nombre FROM usuarios AS usu WHERE usu.id = aoc_tipo.id_supervision),'') AS nombre,"
                                ."IF (aoc_tipo.tipo = 'interventor',(SELECT numero_contrato FROM contratos AS co WHERE co.id = aoc_tipo.id_supervision),'') AS numero_contrato,"
                                ."IF (aoc_tipo.tipo = 'interventor',2,1) AS tipo_id "
                                ."FROM aoc_tipo_super AS aoc_tipo WHERE id_aoc = '".$row["id_aoc"]."'";
                                
                                $sqlSupervInterv = $this->intelcost->prepareStatementQuery('cliente', $SqlQuerySupervInterv, 'select', false, "", "", "Obtener info interventor.");
                    if($sqlSupervInterv->bool){
                        $arrSupervInterv = array();
                        while ($resultSupervInterv = $sqlSupervInterv->msg->fetch_assoc()) {
                            if ($resultSupervInterv['tipo_id'] == 2) {
                                $resultSupervInterv['id_interventor'] = hash("sha256", $resultSupervInterv['id_supervision']);
                            }
                            $resultSupervInterv['id_supervisor'] =  ($resultSupervInterv['id_supervision']);
                            $arrSupervInterv[$resultSupervInterv['tipo_id']][] = $resultSupervInterv;
                        }
                        $row["dataSupervInterv"]=json_encode($arrSupervInterv);
                    }
                }else if($_SESSION["empresaid"]==20){
                    $SqlQuery_solicitud_asociado = "SELECT SOL.id id_solicitud,PAA.id id_paa_linea,PAA.id_actividad,SOL.tiempo_ejecucion,PAA.id_centro_costo,MSE.bodega lugar_ejecucion,us.nombre administrador_prinicipal,SOL.titular,us2.nombre administrador_delegado,SOL.delegado,(SELECT nombre from usuarios where id =".$row['usuario_creacion']." ) usuario_creacion FROM solicitud SOL LEFT JOIN oferta_datos_adicionales ODA ON SOL.id = ODA.solicitud_id INNER JOIN paa_linea PAA ON SOL.id_paa_linea =  PAA.id INNER JOIN solicitud_estacion SOLE ON SOLE.id_solicitud = SOL.id INNER JOIN mst_estacion MSE ON SOLE.id_estacion = MSE.id LEFT JOIN usuarios us ON us.id=SOL.titular LEFT JOIN usuarios us2 ON us2.id=SOL.delegado WHERE SHA2(ODA.oferta_id, 256) = ? ";

                    $sql_solicitud_asociado = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery_solicitud_asociado, 'select', true, "s", array($codOferta), "Obtener oferta para contrato.");
             
                    if ($sql_solicitud_asociado->bool) {
                        while ($raw = $sql_solicitud_asociado->msg->fetch_assoc()) {
                            $raw['nombre_actividad'] = $this->obtenerNombreActividad($raw['id_actividad']);
                            $raw['area_usuaria'] = $this->modelo_usuario->obtenerAreaUsuaria($raw["id_centro_costo"])->msg;
                            array_push($solicitud_asociados, $raw);
                        }
                    }
                    $dbConection = $this->intelcost->db->createConection("cliente");
                    $SqlDocumentos  = "SELECT * FROM oferta_documentos WHERE SHA2(id_oferta,256) ='".$codOferta."' AND estado = 'activo' ORDER BY seq_id ASC";
                    $CscDocumentosOferta=$dbConection->query($SqlDocumentos);
                    $arrDocsOfer = [];
                    if($CscDocumentosOferta){
                        while( $doc = $CscDocumentosOferta->fetch_assoc()){
                            $coleccion = collect();
                            $doc["titulo"] = ($doc["titulo"]);
                            $doc["ruta"] = $this->intelcost->generaRutaServerFiles($doc["ruta"], "cliente");
                            $doc["contenido"] = (($doc['tipo'] == "archivo") ? $this->intelcost->generaRutaServerFiles($doc["contenido"], "cliente") : $doc["contenido"]);

                            //Si tiene activo el check de categorización
                            if (!empty($_SESSION['modulos_personalizados']) && array_search("15", array_column($_SESSION['modulos_personalizados'], 'cod_modulo_personalizado')) !== false){
                                $categorias_enlazadas = RelacionOfertaDocumentosCategorias::where('id_item_oferta_documento', $doc['id'])->first();
                                try {
                                    if (count(json_decode($categorias_enlazadas->ids_categorias, true)) > 0) {
                                        foreach ($categorias_enlazadas->categoriaAsociadas->toArray() as $categoria) {
                                            $coleccion->push($categoria);
                                        }
                                    }

                                    $doc["categorias"] = $categorias_enlazadas->toArray();
                                } catch (Error $e) {
                                    $doc["categorias"] = [];
                                }
                            }

                            array_push($arrDocsOfer, $doc);
                        }
                    }                 
                    $row["documentos_oferta"] = $arrDocsOfer;
                }
                $row["objeto"]=mb_strtoupper(mb_substr($row["objeto"],0,1)).mb_substr($row["objeto"],1);
                $respuestaAdjudcaciones=$this->obtenerAdjudicacion($row["id"]);
                if($respuestaAdjudcaciones->bool){
                    $row["adjudicaciones"]=$respuestaAdjudcaciones->msg;
                }else{
                    $row["adjudicaciones"]="[]";
                }
                $row["actividadNombre"] = $this->obtenerNombreActividad($row["actividad"]);
                $row["cant_asociacion_contratos"] = $resultContratosOferentes['cant_oferentes'];
                $row["contratos_asociados"] = $contratos_asociados;
                $row["monedas"] = $this->obtenerMonedasAdicionales($row['id']);
                $row["id"]= hash("sha256",$row["id"]);
                
                if ($_SESSION["empresaid"]==20) {
                    $row["solicitud_asociada"] = $solicitud_asociados;
                    $row["id_o"]= $row["id"];
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =json_encode($row);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontraron resultados.";
            }
        }else{
            $this->intelcost->response = $sqlOferta;
        }
        return $this->intelcost->response;
    }

    public function obtenerInformacionBasicaOfertaSha256IDnoCryp($codOferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $codOferta = $this->intelcost->realEscapeStringData($codOferta);
            $SqlQuery="SELECT id,seq_id,actividad,LOWER(objeto) objeto,fecha_creacion,usuario_creacion,LOWER(moneda) moneda,maestra1,fecha_actualizacion,descripcion, ";
            $SqlQuery.="maestra2 FROM `ofertas` WHERE id_cliente='".$_SESSION["empresaid"]."' AND SHA2(id,'256')='".$codOferta."'; ";
                        $CscQuery=$dbConection->query($SqlQuery);
            if($CscQuery){
                if($CscQuery->num_rows!=0){
                    $row=$CscQuery->fetch_assoc();
                    $row["objeto"]=mb_strtoupper(mb_substr($row["objeto"],0,1)).mb_substr($row["objeto"],1);
                    $respuestaAdjudcaciones=$this->obtenerAdjudicacion($row["id"]);
                    $row["actividadNombre"] = $this->obtenerNombreActividad($row["actividad"]);
                    if(!empty($row["fecha_actualizacion"]) && $row["fecha_actualizacion"]!="0000-00-00"){
                        $row["fecha_adjudicacion"]=$this->intelcost->castiarFechayHoraIntelcost($row["fecha_actualizacion"]);
                    }else{
                        $row["fecha_adjudicacion"]="N/A";
                    }
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =json_encode($row);
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="No se encontro la oferta para asociar.";
                }

            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea - obtener informacion oferta para asociar al contrato.";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    private function obtenerInformacionBasicaLote($lote){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlQuery="SELECT id_lote,cod_cliente,cod_oferta,cod_sobre,nombre_lote,numero_solped,id_pcc,presupuesto,moneda,estado ";
            $SqlQuery.="FROM `oferta_lotes` WHERE sha1(id_lote)='".$lote."' AND estado=1; ";
            $CscQuery=$dbConection->query($SqlQuery);
            if($CscQuery){
                if($CscQuery->num_rows!=0){
                    $row=$CscQuery->fetch_assoc();
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =json_encode($row); 
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Sin resultados.";    
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea - obtener info basica lote.";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    private function obtenerLotes($idOferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlQuery="SELECT * FROM oferta_lotes WHERE cod_oferta='".$idOferta."' AND estado='activo'; ";
            //echo $SqlQuery;
            $CscQuery=$dbConection->query($SqlQuery);
            if($CscQuery){
                if($CscQuery->num_rows!=0){
                    $row=$CscQuery->fetch_assoc();
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =json_encode($row); 
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Sin resultados.";    
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea - obtener  lotes de la oferta.";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function obtenerLotesConItems($idOferta){
        $SqlQuery = "SELECT * FROM oferta_lotes WHERE cod_oferta = ? AND estado='activo'; ";
        $CscQuery = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', true, "i", array((int)$idOferta), "obtener lotes items oferta.");
        if($CscQuery->bool){
            if($CscQuery->msg->num_rows > 0){
                $lotes = array();
                while($lote = $CscQuery->msg->fetch_assoc()){
                    array_push($lotes, $lote);
                }
                $arrayLotes = array();
                foreach ($lotes as $lote) {
                    $lote['items'] = array();
                    $SqlQueryItems ="SELECT t1.*,t2.um FROM oferta_lotes_items t1 INNER JOIN mst_unidad_medidas t2 ON t1.cod_unidad_medida = t2.id_medida WHERE t1.cod_lote = $lote[id_lote] AND t1.estado='activo'; ";
                    $CscQueryItems = $this->intelcost->prepareStatementQuery('cliente', $SqlQueryItems, 'select', false, "", "", "obtener items lote oferta.");
                    if($CscQueryItems->bool){
                        // dd($SqlQueryItems);
                        if($CscQueryItems->msg->num_rows > 0){
                            while($lote_item = $CscQueryItems->msg->fetch_assoc()){
                                array_push($lote["items"], $lote_item);
                            }
                        }
                    }
                    array_push($arrayLotes, $lote);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrayLotes); 
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Sin resultados.";    
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "error al cosultar lotes.";
        }
        return $this->intelcost->response;
    }


    public function eliminarLoteOferta($idOferta, $idLote){
        $queryEliminarLote = "UPDATE `oferta_lotes` SET estado = 'eliminada', fecha_actualizacion='".date("Y-m-d h:i:s")."', usuario_actualizacion ='".$_SESSION["idusuario"]."' WHERE cod_oferta = ? AND SHA1(id_lote)= ? ";

        $sqlEliminaLote = $this->intelcost->prepareStatementQuery('cliente', $queryEliminarLote, 'update', true, "is", array((int)$idOferta, $idLote), "Eliminar lote oferta.");
        if($sqlEliminaLote->bool){
            $this->eliminarItemsLote($idLote);
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "Se ha eliminado correctamente el lote.";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el lote de la oferta.";
        }
        return $this->intelcost->response;
    }

    public function eliminarItemsLote($idLote){
        $SqlQueryItem = "UPDATE `oferta_lotes_items` SET estado = 'eliminado', fecha_actualizacion='".date("Y-m-d h:i:s")."', usuario_actualizacion ='".$_SESSION["idusuario"]."' WHERE  SHA1(cod_lote)= ?";

        $sqlEliminaItems = $this->intelcost->prepareStatementQuery('cliente', $SqlQueryItem, "update", true, "s", array($idLote), "Eliminar items de lote.");
        if($sqlEliminaItems->bool){
            if($sqlEliminaItems->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="Se ha eliminado correctamente los items y el lote.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se logró eliminar los items del lote de la oferta.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar los items del lote de la oferta.";
        }
        return $this->intelcost->response;
    }

    public function eliminarItemLoteOferta($idItemLote){
        $SqlQueryItem = "UPDATE `oferta_lotes_items` SET estado = 'eliminado', fecha_actualizacion='".date("Y-m-d h:i:s")."', usuario_actualizacion ='".$_SESSION["idusuario"]."' WHERE SHA1(id_item)= ?";
        $sqlEliminaItem = $this->intelcost->prepareStatementQuery('cliente', $SqlQueryItem, "update", true, "s", array($idItemLote), "Eliminar item de lote.");
        if($sqlEliminaItem->bool){
            if($sqlEliminaItem->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="Se ha eliminado correctamente el item y el lote.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se logró eliminar el item del lote de la oferta.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el item del lote de la oferta.";
        }
        return $this->intelcost->response;
    }
    private function IdUnidadMedidaPorCodigoCilente($codigo,$cliente){
        //$SqlQueryItem = "SELECT id_medida FROM mst_unidad_medidas WHERE id_medida = ? AND id_empresa = ? ";
        //$sqlAprobadores = $this->intelcost->prepareStatementQuery('cliente', $SqlQueryItem, "select", true, "ii", array((int)$codigo,(int) $cliente), "obtener unidad medida lote.");
        $SqlQueryItem = "SELECT id_medida FROM mst_unidad_medidas WHERE id_medida = ?";
        $sqlAprobadores = $this->intelcost->prepareStatementQuery('cliente', $SqlQueryItem, "select", true, "i", array((int)$codigo), "obtener unidad medida lote.");
        if($sqlAprobadores->bool){
            if($sqlAprobadores->msg->num_rows > 0){
                $unidad_medida = $sqlAprobadores->msg->fetch_assoc();
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $unidad_medida;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontraron unidades de medidad.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el item del lote de la oferta.";
        }
        return $this->intelcost->response;
    }
    public function guardarLotesItemsOferta($idOferta, $data){
        if(!empty($data) && isset($data)){
            $arrLotes = json_decode($data);
            if(isset($arrLotes) && count($arrLotes) != 0){
                foreach ($arrLotes as $key => $value) {
                    $objLoteCompleto = $value;
                    $objLote = $objLoteCompleto->lote;
                    $banderaAccion = false;
                    $tipoQuery = "";
                    if(!isset($objLote->codSobre) || $objLote->codSobre == "" || $objLote->codSobre == "otros"){
                        $objLote->codSobre="37";
                    }
                    if(isset($objLote->codLote) && !empty($objLote->codLote)){
                        //actualizar
                        if(isset($objLote->companiaId) && !empty($objLote->companiaId)){
                        // CASO PARA CONFA
                            $SqlQuery="UPDATE `oferta_lotes` SET cod_sobre= ?,nombre_lote= ?, cod_compania = ?, usuario_actualizacion='".$_SESSION["idusuario"]."',fecha_actualizacion='".date("Y-m-d h:i:s")."' WHERE cod_oferta= ? AND SHA1(id_lote)= ? ";
                            $parametros = array((int)$objLote->codSobre , $objLote->titulo, $objLote->companiaId, (int)$idOferta, $objLote->codLote);
                            $tipoParametros = "issis";
                            $banderaAccion = false;
                            $tipoQuery = "UPDATE";
                        }else{
                        // RESTO DE CASOS
                            $SqlQuery="UPDATE `oferta_lotes` SET cod_sobre= ?,nombre_lote= ?, usuario_actualizacion='".$_SESSION["idusuario"]."',fecha_actualizacion='".date("Y-m-d h:i:s")."' WHERE cod_oferta= ? AND SHA1(id_lote)= ? ";
                            $parametros = array((int)$objLote->codSobre , $objLote->titulo, (int)$idOferta, $objLote->codLote);
                            $tipoParametros = "isis";
                            $banderaAccion = false;
                            $tipoQuery = "UPDATE";
                        }
                    }else{
                        //crear

                        if(isset($objLote->companiaId) && !empty($objLote->companiaId)){
                        // CASO PARA CONFA CON EL ID DE LA COMPANIA ASOCIADO A LA OJ
                            $SqlQuery="INSERT INTO `oferta_lotes` (cod_cliente, cod_oferta, cod_sobre, nombre_lote, cod_compania, usuario_creacion) VALUES ('".$_SESSION["empresaid"]."', ?, ?, ?, ?, '".$_SESSION["idusuario"]."');";
                            $parametros = array((int)$idOferta, (int)$objLote->codSobre, $objLote->titulo, $objLote->companiaId);
                            $tipoParametros = "iiss";
                            $banderaAccion = true;
                            $tipoQuery = "INSERT";
                        }else{
                            // RESTO DE CASOS
                            $SqlQuery="INSERT INTO `oferta_lotes` (cod_cliente, cod_oferta, cod_sobre, nombre_lote, usuario_creacion) VALUES ('".$_SESSION["empresaid"]."', ?, ?, ?, '".$_SESSION["idusuario"]."');";
                            $parametros = array((int)$idOferta, (int)$objLote->codSobre, $objLote->titulo);
                            $tipoParametros = "iis";
                            $banderaAccion = true;
                            $tipoQuery = "INSERT";
                        }

                    }
                    $CscLote = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, $tipoQuery, true, $tipoParametros, $parametros, "Guardar lotes oferta.");
                    if($CscLote->bool){
                        if($banderaAccion && $tipoQuery == "INSERT"){
                            $objLote->codLote = $CscLote->msg;
                        }
                        if(isset($objLote->codLote) && !empty($objLote->codLote)){
                            
                            foreach ($objLoteCompleto->items as $key => $dataItems) {
                                $objItems = $dataItems;
                                if(is_string($objItems->codUnidadMedida)){
                                    $consultar_unidad = $this->IdUnidadMedidaPorCodigoCilente($objItems->codUnidadMedida,$_SESSION["empresaid"]);
                                    if($consultar_unidad->bool){
                                        $objItems->codUnidadMedida = $consultar_unidad->msg["id_medida"];
                                    }else{
                                        $objItems->codUnidadMedida = 398;
                                    }
                                }

                                $tipoQueryItem = "";
                                if(isset($objItems->codItem) && !empty($objItems->codItem)){
                                    $SqlItem = "UPDATE `oferta_lotes_items` SET descripcion= ?, cod_unidad_medida=  ?, cantidad= ?,numero_solped=?,moneda=?,presupuesto=?,id_pcc=?, usuario_actualizacion= '".$_SESSION["idusuario"]."',fecha_actualizacion='".date("Y-m-d h:i:s")."' WHERE SHA1(id_item)= ? AND SHA1(cod_lote)= ?; ";
                                    $parametrosItem = array($objItems->descripcion, (int)$objItems->codUnidadMedida, floatval($objItems->cantidad),$objItems->solped,$objItems->moneda,$objItems->presupuesto,(int)$objItems->id_pcc, $objItems->codItem, $objLote->codLote);
                                    $tipoParametrosItem = "sidsssiss";
                                    $tipoQueryItem = "UPDATE";
                                }else{

                                    if(!$banderaAccion){
                                        $respuestaLote = $this->obtenerInformacionBasicaLote($objLote->codLote);
                                        if($respuestaLote->bool){
                                            $objLoteRes = json_decode($respuestaLote->msg);
                                            $objLote->codLote = $objLoteRes->id_lote;
                                        }
                                    }
                                    $SqlItem = "INSERT INTO `oferta_lotes_items` (cod_lote, descripcion, cod_unidad_medida, cantidad,numero_solped,moneda,presupuesto,id_pcc, usuario_creacion) VALUES ( ?, ?, ?, ?,?,?,?,?,'".$_SESSION["idusuario"]."');";
                                    $parametrosItem = array((int)$objLote->codLote, $objItems->descripcion, (int)$objItems->codUnidadMedida,floatval($objItems->cantidad),$objItems->solped,$objItems->moneda,$objItems->presupuesto,(int)$objItems->id_pcc);
                                    $tipoParametrosItem = "isidsssi";
                                    $tipoQueryItem = "INSERT";
                                }
                                if((int)$objItems->codUnidadMedida != 0 && floatval($objItems->cantidad) != 0){
                                    $cscLoteItem = $this->intelcost->prepareStatementQuery('cliente', $SqlItem, $tipoQueryItem, true, $tipoParametrosItem, $parametrosItem, "Guardar lotes items oferta.");
                                    if($cscLoteItem->bool){
                                        if($cscLoteItem->msg > 0){
                                            if($_SESSION['empresaid'] == 9){
                                                $dataItems->codigoArticulo = '1';
                                                $dataItems->posicionItem = '1';
                                                $dataItems->tipoDocumento = 'N/A';
                                            }

                                            if((isset($dataItems->codigoArticulo) && !empty($dataItems->codigoArticulo)) && (isset($dataItems->posicionItem) && !empty($dataItems->posicionItem)) && (isset($dataItems->tipoDocumento) && !empty($dataItems->tipoDocumento))){
                                                
                                                // CONSULTAS PARA EL GUARDADO DE LOS DATOS ADICIONALES DE CONFA
                                                if($_SESSION['empresaid'] == 25 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9){
                                                    
                                                    if($cscLoteItem->bool){
                                                        if($cscLoteItem->msg > 0){
                                                            if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9){
                                                                $dataItems->impuesto = 'N/A';
                                                                $dataItems->referencia = 'N/A';
                                                            }

                                                            if(isset($dataItems->codigoArticulo) && !empty($dataItems->codigoArticulo) && isset($dataItems->posicionItem) && !empty($dataItems->posicionItem) && isset($dataItems->tipoDocumento) && !empty($dataItems->tipoDocumento) && isset($dataItems->impuesto) && !empty($dataItems->impuesto) && isset($dataItems->referencia)){
                                                                if($tipoQueryItem == 'INSERT'){
                                                                    
                                                                    $SqlItem = "SELECT MAX(id_item) AS lastId FROM oferta_lotes_items";
                                                                    $parametrosItem = '';
                                                                    $tipoParametrosItem = '';
                                                                    $tipoQueryItem = "SELECT";
                                                                    
                                                                    $lastIdItem = $this->intelcost->prepareStatementQuery('cliente', $SqlItem, $tipoQueryItem, false, $tipoParametrosItem, $parametrosItem, "Obtener id para asociar data adicional para CONFA.");
                                                                    
                                                                    $lastIdItem = $lastIdItem->msg->fetch_assoc();
                                                                    $lastIdItem = $lastIdItem['lastId'];
                                                                    
                                                                    $SqlItem = "INSERT INTO `oferta_lotes_items_datos_adicionales` (cod_lote, cod_item, numero_linea, numero_articulo, tipo_documento, obligatorio, impuesto, referencia, usuario_creacion, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '".$_SESSION["idusuario"]."',  '".date("Y-m-d h:i:s")."');";
                                                                    $parametrosItem = array((int)$objLote->codLote, (int)$lastIdItem, (int)$dataItems->posicionItem, (int)$dataItems->codigoArticulo, $dataItems->tipoDocumento, (int)$dataItems->obligatorio, $dataItems->impuesto, $dataItems->referencia);
                                                                    $tipoParametrosItem = "iiiisiss";
                                                                    $tipoQueryItem = "INSERT";

                                                                    $cscLoteItem = $this->intelcost->prepareStatementQuery('cliente', $SqlItem, $tipoQueryItem, true, $tipoParametrosItem, $parametrosItem, "Guardar lotes items oferta.");
                                                                }
                                                                if($tipoQueryItem == 'UPDATE'){

                                                                    $SqlItem = "UPDATE `oferta_lotes_items_datos_adicionales` SET obligatorio=?, usuario_actualizacion= '".$_SESSION["idusuario"]."',fecha_actualizacion='".date("Y-m-d h:i:s")."' WHERE SHA1(cod_item)= ? AND SHA1(cod_lote) = ?;";
                                                                    $parametrosItem = array((int)$dataItems->obligatorio, $dataItems->codItem, $dataItems->codLote);
                                                                    $tipoParametrosItem = "iss";
                                                                    $tipoQueryItem = "UPDATE";

                                                                    $cscLoteItem = $this->intelcost->prepareStatementQuery('cliente', $SqlItem, $tipoQueryItem, true, $tipoParametrosItem, $parametrosItem, "Actualizar items lotes.");
                                                                }

                                                            }
                                                        }
                                                    }

                                                }
                                            }
                                        }
                                    }

                                    if($cscLoteItem->bool){
                                        
                                        if($cscLoteItem->msg > 0){
                                            $this->intelcost->response->bool = true;
                                            $this->intelcost->response->msg ="Se han guardado correctamente los lotes con los items.";
                                        }else{
                                            $this->intelcost->response->bool = false;
                                            $this->intelcost->response->msg ="No se logró guardar item en lote 1.";
                                        }
                                    }else{
                                        $this->intelcost->response->bool = false;
                                        $this->intelcost->response->msg ="No se logró guardar item en lote 2.";
                                    }
                                }else{
                                    $this->adicionarRegistroLog("Query: ".$SqlItem. " || Parametros: ". json_encode($parametrosItem),PEL_ERROR);
                                }
                                    
                            }
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg ="No se logró obtener el identificador del lote para guardar los items.";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Error al guardar lotes e items para la oferta.";
                    }
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontraron lotes e items para la oferta.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="No se encontraron lotes e items para la oferta.";
        }
        return $this->intelcost->response;
    }

    
    public function listarOfertas($filtros, $tipo, $pagina){
        $SqlQuery = "SELECT t1.id,t1.seq_id, t1.solpeds_relacionadas, t1.id_cliente, t1.objeto, t1.actividad, t1.descripcion, t1.moneda, t1.presupuesto, t1.maestra1, t1.maestra2,
        t1.fecha_inicio, t1.hora_inicio, t1.fecha_cierre, t1.hora_cierre, t1.ronda, t1.id_aoc, t1.id_requisicion, t1.fecha_limite_msg_observacion, t1.usuario_creacion, t1.estado,
        t1.fecha_creacion, t1.id_area, oferta_usuarios_internos.id_usuario, t2.nombre usuario_creador, t2.id usuario_creador_id, OL.nombre_lote";
        if((int) $_SESSION["empresaid"] == 9){
            $SqlQuery .= ",  replace(JSON_UNQUOTE(JSON_EXTRACT(ODA.otros, '$.solicitudTexto')), '\"', '') as solicitud ";            
        }
        $SqlQuery .= " FROM ofertas t1 
        LEFT JOIN oferta_usuarios_internos ON t1.id = oferta_usuarios_internos.id_oferta 
        LEFT JOIN oferta_usuarios_aprobadores OUA ON t1.id = OUA.id_oferta
        LEFT JOIN oferta_lotes OL ON t1.id = OL.cod_oferta";        
        if((int) $_SESSION["empresaid"] == 9){
            $SqlQuery .= " INNER JOIN oferta_datos_adicionales ODA ON t1.id = ODA.oferta_id";            
        }
		if(isset($filtros['cuantia']) && !empty($filtros['cuantia']) && $filtros['cuantia'] != 'ALL'){
    		$SqlQuery .= " LEFT JOIN oferta_maestras_generales OMG ON  OMG.cod_oferta = t1.id ";
		}

        $SqlQuery .= " INNER JOIN usuarios t2 ON t1.usuario_creacion = t2.id WHERE t1.id_cliente= '".$_SESSION["empresaid"]."' AND t1.tipo= ? AND t1.estado != 'INACTIVO'";

        $SqlQuery_cons_cant ="SELECT count(t1.seq_id) as total FROM ofertas t1 LEFT JOIN oferta_lotes OL ON t1.id = OL.cod_oferta";
        if(isset($filtros['cuantia']) && !empty($filtros['cuantia']) && $filtros['cuantia'] != 'ALL'){
        	 $SqlQuery_cons_cant .=" LEFT JOIN oferta_maestras_generales OMG ON OMG.cod_oferta = t1.id ";
        }
        if((int) $_SESSION["empresaid"] == 9){
            $SqlQuery_cons_cant .= " INNER JOIN oferta_datos_adicionales ODA ON t1.id = ODA.oferta_id";            
        }
         $SqlQuery_cons_cant .= " WHERE t1.id_cliente='".$_SESSION["empresaid"]."' AND t1.tipo= ? AND t1.estado != 'INACTIVO'";

         // dd($SqlQuery_cons_cant);

        $tipoParametros = "s";
        if($_SESSION['perfilId'] == 54){
            $tipo = 'cerrada';
        }
        $parametros = array($tipo);

        $SqlQuery_query = '';
        if(isset($filtros["id"]) && $filtros["id"] != ""){
            $tipoParametros .= "s";
            array_push($parametros, "%".$filtros["id"]."%");
            $SqlQuery_query .=" AND t1.seq_id LIKE ? ";
        }
        // CAMPOS PROPIOS DE CONFA
        if(isset($filtros['oj']) && !empty($filtros['oj'])){
        	$tipoParametros .= "s";
            array_push($parametros, "%".$filtros["oj"]."%");
            $SqlQuery_query .=" AND OL.nombre_lote LIKE ? ";
        }
        if(isset($filtros['inicioEventoFecha']) && !empty($filtros['inicioEventoFecha'])){
        	$tipoParametros .= "s";
            array_push($parametros, $filtros["inicioEventoFecha"]);
            $SqlQuery_query .=" AND t1.fecha_inicio = ? ";
        }
        if(isset($filtros['cierreEventoFecha']) && !empty($filtros['cierreEventoFecha'])){
        	$tipoParametros .= "s";
            array_push($parametros, $filtros["cierreEventoFecha"]);
            $SqlQuery_query .=" AND t1.fecha_cierre = ? ";
        }
        if(isset($filtros['cuantia']) && !empty($filtros['cuantia']) && $filtros['cuantia'] != 'ALL'){
        	$tipoParametros .= "s";
            array_push($parametros, $filtros["cuantia"]);
            $SqlQuery_query .=" AND OMG.cod_maestra = ? ";
        }
        if(isset($filtros['centroDeCostoGerencia']) && !empty($filtros['centroDeCostoGerencia'])){
        	$tipoParametros .= "s";
            array_push($parametros, $filtros["centroDeCostoGerencia"]);
            $SqlQuery_query .=" AND t1.id_area = ? ";
        }
        // FIN DE CAMPOS PROPIOS DE CONFA
        if(isset($filtros["solped"]) && $filtros["solped"] != ""){
            $tipoParametros .= "s";
            array_push($parametros, "%".$filtros["solped"]."%");
            $SqlQuery_query .=" AND t1.solpeds_relacionadas LIKE ? ";   
        }
        if(isset($filtros["responsable"]) && $filtros["responsable"] != ""){
            $listaUsuarios = $this->modelo_usuario->listarIdsUsuarioNombre($filtros["responsable"]);
            if($listaUsuarios != false){
                $arr_usuarios_filtrados = implode(",", $listaUsuarios);
                $SqlQuery_query .=" AND t1.usuario_creacion IN (".$arr_usuarios_filtrados.")";  
            }
        }
        if(isset($filtros["objeto"]) && $filtros["objeto"] != ""){
            $tipoParametros .= "ss";
            array_push($parametros, "%".$filtros["objeto"]."%");
            array_push($parametros, "%".$filtros["objeto"]."%");
            $SqlQuery_query .=" AND ( t1.objeto LIKE ? OR t1.descripcion LIKE ? )";
        }
        if(isset($filtros["estado"]) && $filtros["estado"] != "" && $filtros["estado"] != "ALL"){
            $tipoParametros .= "s";
            array_push($parametros, $filtros["estado"]);
            $SqlQuery_query .=" AND t1.estado LIKE ? ";
        }

        if(isset($filtros["ceco"]) && $filtros["ceco"] != ""){
            $tipoParametros .= "i";
            array_push($parametros, $filtros["ceco"]);
            $SqlQuery_query .=" AND t1.id_area = ? ";
        }
        if((int) $_SESSION['empresaid'] != 10){
            if(isset($_SESSION["Tipousuario"]) && !empty($_SESSION["Tipousuario"])){
                if( $_SESSION["Tipousuario"] == "3" && ($_SESSION["empresaid"] != 14 && $_SESSION["empresaid"] != 26 && $_SESSION["perfilId"] != "100" && $_SESSION["perfilId"] != "59" && $_SESSION["perfilId"] != "144" && $_SESSION["perfilId"] != "153")){
                    //Compradores
                    if($_SESSION['empresaid'] == 20 && ($_SESSION["perfilId"] == "87" || $_SESSION["perfilId"] == "110" || $_SESSION["perfilId"] == "54" || $_SESSION["perfilId"] == "107" || $_SESSION["perfilId"] == "203")){
                        $SqlQuery .= " ";
                    }else{
                        $SqlQuery .= " AND (t1.usuario_creacion='".$_SESSION["idusuario"]."' OR (oferta_usuarios_internos.id_usuario = '".$_SESSION["idusuario"]."' AND oferta_usuarios_internos.estado='activo')) ";
                    }
                }else if( $_SESSION["Tipousuario"] == "2" || $_SESSION["Tipousuario"] == "4" || $_SESSION["perfilId"] == "110" || $_SESSION["perfilId"] == "144" || $_SESSION["perfilId"] == "153"){
                    //Adminsitradores y auditores
                    $SqlQuery .= " ";
                }

                if( $_SESSION["Tipousuario"] == "5" && ($_SESSION["empresaid"] != 14 && $_SESSION["perfilId"] != "100" && $_SESSION["empresaid"] != 25 && $_SESSION["perfilId"] != "59") && $this->modelo_usuario->validarRolExistente([140])->bool == false){
                    if($_SESSION['empresaid'] == 20 && ($_SESSION["perfilId"] == "87" || $_SESSION["perfilId"] == "110" || $_SESSION["perfilId"] == "54" || $_SESSION["perfilId"] == "107" || $_SESSION["perfilId"] == "203")){
                        $SqlQuery .= " ";
                    }else{
                        $SqlQuery .= " AND (oferta_usuarios_internos.id_usuario = '".$_SESSION["idusuario"]."' AND oferta_usuarios_internos.estado='activo') ";
                    }
                }
                if( ($_SESSION["perfilId"] == "43" && $_SESSION["empresaid"] == 14) || ($_SESSION["empresaid"] == 26 && $_SESSION["perfilId"] == "43") || ($_SESSION["empresaid"] == 27 && $_SESSION["perfilId"] == "43")){
                    $SqlQuery .= " AND ((oferta_usuarios_internos.id_usuario = '$_SESSION[idusuario]' AND oferta_usuarios_internos.estado = 'activo') OR (OUA.id_usuario_aprobador = '$_SESSION[idusuario]' AND OUA.estado = 'activo'))";
                }
            }
        }else{
            //excluya solo auditores
            if(!$this->modelo_usuario->validarRolExistente([14,21,35])->bool){
                //inlcuir las personas de flujo de aprobacion y usuarios internos
                $perfiles_consulta_ofertas_metro = [8,5,24,28,48,7,10,11,12,27,49];
                if (isset($_SESSION["perfilId"]) && (in_array ($_SESSION["perfilId"],$perfiles_consulta_ofertas_metro))  ) {
                    $SqlQuery .=" AND ( (EXISTS (SELECT NULL FROM flujos_aprobacion_perfil_vs_usuario as flujo WHERE flujo.id_objeto=t1.id and flujo.id_modulo='14' and flujo.id_usuario='".$_SESSION["idusuario"]."' and flujo.id_cliente='".$_SESSION["empresaid"]."') or EXISTS(SELECT NULL FROM aoc WHERE id_aoc=t1.id_aoc AND id_abogado='".$_SESSION["idusuario"]."') or EXISTS(SELECT NULL FROM requisiciones WHERE id=t1.id_requisicion AND id_profesional_contrat='".$_SESSION["idusuario"]."') or t1.usuario_creacion='".$_SESSION["idusuario"]."') or oferta_usuarios_internos.id_usuario = '".$_SESSION["idusuario"]."' AND oferta_usuarios_internos.estado='activo')";
                    //$SqlQuery .=" OR  (EXISTS (SELECT NULL FROM flujos_aprobacion_perfil_vs_usuario as flujo WHERE flujo.id_objeto=t1.id and flujo.id_modulo='14' and flujo.id_usuario='".$_SESSION["idusuario"]."' and flujo.id_cliente='".$_SESSION["empresaid"]."') or t1.usuario_creacion='".$_SESSION["idusuario"]."')";
                }
            }
            if(isset($filtros['modulo_consulta']) && $filtros['modulo_consulta'] == "rfq_aprobacion"){
                if(isset($_SESSION["perfilId"]) && (int) $_SESSION["perfilId"] == 12 && ($filtros["estado"] == "EN APROBACION" || $filtros["estado"] == "EN ADJUDICACION")){
                    $SqlQuery .=" AND ( (EXISTS (SELECT NULL FROM flujos_aprobacion_perfil_vs_usuario as flujo WHERE flujo.id_objeto=t1.id and flujo.id_modulo='14' and flujo.id_usuario='".$_SESSION["idusuario"]."' and flujo.id_cliente='".$_SESSION["empresaid"]."') or t1.usuario_creacion='".$_SESSION["idusuario"]."') or oferta_usuarios_internos.id_usuario = '".$_SESSION["idusuario"]."' AND oferta_usuarios_internos.estado='activo')";
                }
            }
        }
        if(isset($filtros["numeroSolicitud"]) && $filtros["numeroSolicitud"] != "" && (int) $_SESSION["empresaid"] == 9){            
            $tipoParametros .= "s";
            array_push($parametros,"%".$filtros["numeroSolicitud"]."%");
            $SqlQuery_query .=" AND JSON_EXTRACT(ODA.otros, '$.solicitudTexto') LIKE ? ";
        }
        //$SqlQuery .= ")";
        $SqlQuery_cons_cant .= $SqlQuery_query;
        if($_SESSION["empresaid"] == "10"){
            if(isset($filtros["modulo"]) && $filtros["modulo"] != "" && $filtros["modulo"]  == "aoc"){
            $SqlQuery_query .=" and NOT EXISTS (SELECT NULL FROM aoc WHERE aoc.id_estudio_mercado=t1.id and aoc.estado!='eliminado')";
            }
            $SqlQuery_query .=" GROUP BY t1.id ORDER BY t1.fecha_creacion DESC";    
        }else if($_SESSION["empresaid"] == "14" || $_SESSION["empresaid"] == "26" || $_SESSION["empresaid"] == "27" || $_SESSION["empresaid"] == "20" || $_SESSION["empresaid"] == "25"){
             $SqlQuery_query .=" GROUP BY t1.id ORDER BY t1.fecha_creacion DESC, t1.hora_inicio DESC";
        }else{
            $SqlQuery_query .=" GROUP BY t1.seq_id ORDER BY CAST(t1.seq_id AS UNSIGNED) DESC";              
        }   
        $SqlQuery .= $SqlQuery_query;
        $sqlOfertasCont = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery_cons_cant, 'select', true, $tipoParametros, $parametros, "Obtener Cantidad listado de ofertas.");
        if($sqlOfertasCont->bool){
            if($sqlOfertasCont->msg->num_rows > 0){
                $res = array();
                $resTotal = $sqlOfertasCont->msg->fetch_assoc();
                // dd($resTotal);
                $res["cant_resultados"] = $resTotal["total"];
                $res["cant_paginas"] =  ceil($resTotal['total'] / 10);

                if($pagina != false){
                    $limitPag = $this->intelcost->paginacion_limit_inicio_fin($pagina, 10);
                    $SqlQuery .= " LIMIT ".$limitPag['inicio'].",".$limitPag['fin']."" ;
                }
                // dd($SqlQuery);
                $sqlOfertas = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', true, $tipoParametros, $parametros, "Obtener listado de ofertas.");

                if($sqlOfertas->bool){
                        
                    if($sqlOfertas->msg->num_rows > 0){
                        $rows = array();
                        while ($row = $sqlOfertas->msg->fetch_assoc()) {
                            array_push($rows, $row);
                        }
                        $arrOfer = array();
                        $dbConection = $this->intelcost->db->createConection("cliente");
                        foreach($rows as $row){
                            if( $filtros["estado"]  != "ALL"){
                                $row["actividadNombre"] = $this->obtenerNombreActividad($row["actividad"]);
                            }else{
                                $row["actividadNombre"] = $row["actividad"];
                            }
                            if((int) $_SESSION["empresaid"] == 9){
                                $arrayValidarSolicitud = preg_split('/(?<=\D)(?=\d)|\d+\K/', $row["solicitud"]); //se divide texto en array
                                foreach ($arrayValidarSolicitud as $solicitud) {
                                    if (is_numeric($solicitud) && strlen($solicitud) > 3 ) { //Variable para asegurar que una solicitud sea mayor a 3 digitos y solo sea numerica
                                        $row["solicitud"] = $solicitud;
                                        break;
                                    }
                                }
                            }

                            // Obtener usuarios internos de la oferta
                            $SqlUsuariosInternos  = "SELECT * FROM oferta_usuarios_internos T1 ";
                            $SqlUsuariosInternos  .= "INNER JOIN (SELECT id ,email, nombre,cargo FROM usuarios) T2 ON T1.id_usuario = T2.id ";
                            $SqlUsuariosInternos  .= "WHERE T1.id_oferta='".$row["id"]."' AND T1.estado LIKE 'activo'";
                            
                            $cssUsuariosInternos=$dbConection->query($SqlUsuariosInternos);
                            $arrUsuariosInternos = [];
                            if($cssUsuariosInternos){
                                if($cssUsuariosInternos->num_rows > 0){
                                    while( $usuarioInterno = $cssUsuariosInternos->fetch_assoc()){
                                        array_push($arrUsuariosInternos, $usuarioInterno);
                                    }
                                }
                            }

                            /*if($row['id_centro_gestor']!=0){
                            $result=$this->listarMaestrasAreasid($row['id_centro_gestor']);
                            $row['nombre_centro_gestor']= $result->msg;
                            }else{
                            $row['nombre_centro_gestor']= "";   
                            }*/
                            $row['nombre_centro_gestor']= "";   
                            $row['seq_id_aoc']="";
                            if($row['id_aoc']!=0){
                              $res_aoc=$this->listarAocid($row['id_aoc']);
                              if($res_aoc->bool){
                              $row['seq_id_aoc']=$res_aoc->msg;
                              }
                            }
                            $row['seq_id_requisicion']="";
                            if($row['id_requisicion']!=0){
                              $res_requisicion=$this->listarRequisicionid($row['id_requisicion']);
                              if($res_requisicion->bool){
                              $row['seq_id_requisicion']=$res_requisicion->msg["seq_id"];
                              }
                            }
                            $row["usuarios_internos"] = $arrUsuariosInternos;
                            
                            $row["usuario_creador"]=($row["usuario_creador"]);
                            array_push($arrOfer, json_encode($row));
                        }
                        $res["resultados"] = $arrOfer;
                        $response= json_encode($res);
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg =$response;
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg ="No se encontraron resultados 1000 de ofertas.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Se presentó un error al consultar listado de ofertas.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontraron resultados de ofertas.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Se presentó un error al consultar cantidad ofertas.";
        }
        return $this->intelcost->response;
    }


    public function listarAocid($id){
        $dbConection = $this->intelcost->db->createConection("cliente");

        if($dbConection  && $_SESSION["gSesId"]){
            $id = $this->intelcost->clearStringXss($id);
            $sql = 'SELECT seq_id FROM aoc WHERE id_aoc="'.$id.'"';
            $csc=$dbConection->query($sql);
            if($csc){
                
                $row=$csc->fetch_assoc();
                
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $row['seq_id'];
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error de consulta.".$dbConection->error;
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexion a la plataforma";
        }
        return $this->intelcost->response;
    }

    public function listarRequisicionid($id){
        $dbConection = $this->intelcost->db->createConection("cliente");

        if($dbConection  && $_SESSION["gSesId"]){
            $id = $this->intelcost->clearStringXss($id);
            $sql = 'SELECT req.seq_id,(SELECT seq_id FROM paa_linea WHERE paa_linea.id=req.id_paa_linea and req.estado!="cancelada") as seq_id_linea,req.id_paa_linea FROM requisiciones as req WHERE req.id="'.$id.'"';

            $csc=$dbConection->query($sql);
            if($csc){
                if($csc->num_rows > 0){
                    $row=$csc->fetch_assoc();
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = $row;
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="No se encontró requisición.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error de consulta.".$dbConection->error;
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexion a la plataforma";
        }
        return $this->intelcost->response;
    }

    

    private function obtenerActividades(){

        $dbConection = $this->intelcost->db->createConection("intelcost");
        if($dbConection){
            $Sqlactividades  = "SELECT * FROM mstactividades";
            $CscActividades=$dbConection->query($Sqlactividades);
            if($CscActividades){
                return $CscActividades;
            }else{
                return false;
            }
            $dbConection->close();
        }else{
            return false;
        }
    }

    public function obtenerNombreActividad($idAct){
        $dbConection = $this->intelcost->db->createConection("intelcost");
        if($dbConection){
            $idAct = $this->intelcost->realEscapeStringData($idAct);
            $SqlOferta  = "SELECT producdesc FROM mstactividades WHERE `producid` ='".$idAct."' LIMIT 1";
            $CscOferta=$dbConection->query($SqlOferta);
            if($CscOferta){ 
                $row = $CscOferta->fetch_assoc();
                return ($row["producdesc"]);
            }else{
                return false;
            }
            $dbConection->close();
        }else{
            return false;
        }
    }
    
    public function obtenetenerNombreMaestra($idMaestra){
        $SqlOferta  = "SELECT * FROM maestras_criterios t1 LEFT JOIN maestras t2 ON t1.maestra_id = t2.id WHERE t1.id = ? LIMIT 1";
        $CscOferta = $this->intelcost->prepareStatementQuery('cliente', $SqlOferta, 'SELECT', true, "i", array((int) $idMaestra), "Obtener nombre maestra.");
        if($CscOferta->bool){
            if($CscOferta->msg->num_rows > 0){
                $row = $CscOferta->msg->fetch_assoc();
                $res["criterio"]=($row["criterio"]);
                $res["maestra"]= ($row["nombre"]);
                return $res;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function obtener_participante($idUsuario){
        if(!empty($idUsuario)){
            $queryParticipante = "SELECT USR.usridxxx AS id_usuario, LOWER(USR.usrnomxx) AS nombre, LOWER(USR.usrlogxx) AS email, USR.teridxxx AS nit, UPPER(P.razonxxx) as empresa FROM sys00001 AS USR INNER JOIN _0002103 AS P ON P.id_empresa = USR.cod_empresa WHERE USR.usridxxx = ?";
        
            $SqlUsr = $this->intelcost->prepareStatementQuery('intelcost', $queryParticipante, 'SELECT', true, "i", array((int) $idUsuario), "Obtener usuario participante.");
            if($SqlUsr->bool){
                $rowUsr = $SqlUsr->msg->fetch_assoc(); //consulta obtiene la data del usuario
                //arreglo de datos
                $objParti['bool'] = true;
                $objParti['msg']['empresa'] = $rowUsr['empresa'];
                $objParti['msg']['email'] = $rowUsr['email'];
                $objParti['msg']['participante'] = ($rowUsr['nombre']);
                $objParti['msg']['nit'] = $rowUsr['nit'];
            }else{
                $objParti['msg'] = 'No se encontró el usuario.';
                $objParti['bool'] = false;
            }
        }else{
            $objParti['msg'] = 'No se encontró el usuario.';
            $objParti['bool'] = false;
        }
        return json_encode($objParti);
    }

    private function obtenerDatosUsuariosIntelcost($Usuarios, $arrayPar){
        $arrParticipantes = array();
        $dbConectionIntelcost = $this->intelcost->db->createConection("intelcost");
        if($dbConectionIntelcost){
            foreach ($arrayPar as $oferente) {
                $queryInfoParticipante = "SELECT LOWER(USR.usrnomxx) AS nombre, LOWER(USR.usrlogxx) AS email, USR.teridxxx AS nit, _0002103.razonxxx as empresa FROM sys00001 AS USR INNER JOIN _0002103 ON _0002103.id_empresa = USR.cod_empresa WHERE usridxxx = $oferente[id_usuario] ";
                $sqlInfoParticipante = $dbConectionIntelcost->query($queryInfoParticipante);
                if($sqlInfoParticipante){
                    if($sqlInfoParticipante->num_rows > 0){
                        $resParticipante = $sqlInfoParticipante->fetch_assoc();
                        $participante = array(
                            "id" => $oferente["id"],
                            "id_usuario" => $oferente["id_usuario"],
                            "arrLotesParticipante" => $oferente["arrLotesParticipante"],
                            "estado_part" => $oferente["estado_participacion"],
                            "estado_participacion" => $this->castearEstadoParticipacion($oferente['estado_participacion']),
                            "empresa" => $resParticipante['empresa'],
                            "nit" => $resParticipante['nit'],
                            "nombre" => ucwords($resParticipante['nombre']),
                            "email_usuario" => $resParticipante['email'],
                            "email" => $resParticipante['email'],
                            "carta_invitacion" => $oferente["carta_invitacion"],
                            "fecha_actualizacion" => $oferente["fecha_actualizacion"],
                            "fecharegistro" => $oferente["fecharegistro"],
                            "porcentaje_participacion" => $oferente["porcentaje_participacion"],
                            "observaciones" => $oferente["observaciones"]
                        );
                        array_push($arrParticipantes, $participante);
                    }
                }
            } //var_dump($arrParticipantes); die();
            return $arrParticipantes;
        }else{
            return false;
        }
    }

    public function obtenerEstadoOferta($id){
        $queryOferta  = "SELECT estado,fecha_cierre,hora_cierre,fecha_inicio,hora_inicio,tipo,maestra1 FROM ofertas t1 WHERE id = ? LIMIT 1";
        $SqlOferta = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, 'select', true, "i", array((int)$id), "Obtener estado oferta.");
        if($SqlOferta->bool){
            if($SqlOferta->msg->num_rows > 0){
                $objOferta = $SqlOferta->msg->fetch_assoc();
                $objOferta['id']= $id;
                if($objOferta["estado"] == "PUBLICADA"){
                    $objOferta['tiempo_restante']=$this->intelcost->convertir_tiempo($objOferta['fecha_cierre'],$objOferta['hora_cierre']);
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = json_encode($objOferta);
                }else if($objOferta["estado"] == "APROBADA"){
                    $objOferta['tiempo_restante']=$this->intelcost->convertir_tiempo($objOferta['fecha_inicio'],$objOferta['hora_inicio']);
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = json_encode($objOferta);
                }else{
                    $objOferta['tiempo_restante']= 0 ;
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = json_encode($objOferta);
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontraron resultados.";
            }
        }else{
            $this->intelcost->response = $SqlOferta;
        }
        return $this->intelcost->response;
    }

    public function obtenerHistorialBitacoraOferta($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $queryBitacora = "SELECT TGO.id, TGO.seq_id, TGO.solpeds_relacionadas as solpeds, TGO.objeto, TGO.actividad as id_actividad, TGO.descripcion, TGO.usuario_aprobacion, TGO.fecha_aprobacion, TGO.moneda, TGO.presupuesto, TGO.maestra1, MC.requiere_aprobacion, TGO.maestra2, TGO.fecha_inicio, TGO.hora_inicio, TGO.fecha_cierre, TGO.hora_cierre, TGO.apertura_capitulos, TGO.fecha_limite_msg as bool_mensajes, TGO.fecha_limite_restrictivo as bool_condicion_mensajes, TGO.fecha_limite_msg_fecha as fecha_limite, TGO.fecha_limite_hora as hora_limite, TGO.fecha_limite_msg_observacion as fecha_limite_obs, TGO.regional, TGO.usuario_creacion, TGO.fecha_creacion, TGO.usuario_actualizacion, TGO.fecha_actualizacion, TGO.estado, TGO.evaluacion_financiera, TGO.duenio_oferta, TGO.accion, TGO.fecha_modificacion, TGO.tipo, UC.nombre as nombre_creador, UAC.nombre as nombre_edita FROM tg_ofertas AS TGO LEFT JOIN usuarios AS UC ON UC.id = TGO.usuario_creacion LEFT JOIN usuarios AS UAC ON UAC.id = TGO.usuario_actualizacion LEFT JOIN maestras_criterios AS MC ON MC.id = TGO.maestra1 WHERE TGO.id = ?";
            if($SqlBitacora = $dbConection->prepare($queryBitacora)){
                if($SqlBitacora->bind_param('i', $idOferta)){
                    if($SqlBitacora->execute()){
                        if($resultado = $SqlBitacora->get_result()){
                            if($resultado->num_rows > 0){
                                $historicoBitacora = array();
                                $arrayLlavesValidar = array("solpeds", "objeto", "id_actividad", "descripcion", "usuario_aprobacion", "moneda", "presupuesto", "maestra1", "maestra2", "fecha_inicio", "hora_inicio", "fecha_cierre", "apertura_capitulos", "hora_cierre", "bool_mensajes", "bool_condicion_mensajes", "fecha_limite", "hora_limite", "fecha_limite_obs", "usuario_creacion", "regional", "estado");
                                $arrayHistorialOferta = array();
                                $arrayValidaOferta = array();
                                while ($objOferta = $resultado->fetch_assoc()) {
                                    $objOferta['fecha_limite_par'] = (($objOferta['fecha_limite'] == "0000-00-00" || $objOferta['fecha_limite'] == "") ? "" : $this->intelcost->castiarFechaIntelcost($objOferta['fecha_limite']));
                                    $objOferta['hora_limite_par'] = (($objOferta['hora_limite'] == "00:00:00" || $objOferta['hora_limite'] == "") ? "" : $objOferta['hora_limite']);
                                    $objOferta['fecha_limite_obs_par'] = (($objOferta['fecha_limite_obs_par'] == "0000-00-00" || $objOferta['fecha_limite_obs_par'] == "") ? "" : $this->intelcost->castiarFechaIntelcost($objOferta['fecha_limite_obs_par']));
                                    if(!empty($arrayValidaOferta)){
                                        $cambios = array();
                                        $accion = "Proceso modificado.";
                                        $banderaCambios = false;
                                        foreach ($objOferta as $columna => $dato) {
                                            if(in_array($columna, $arrayLlavesValidar) && $arrayValidaOferta[$columna] != $objOferta[$columna]){
                                                $banderaCambios = true;
                                                if($columna == "id_actividad"){
                                                    $cambios['actividad'] = $this->obtenerNombreActividad($dato);
                                                }
                                                if($columna == "maestra1"){
                                                    $cambios["maestra1_criterio"] = "";
                                                    $array_criterios = explode(',', $dato);
                                                    foreach($array_criterios as $criterio){
                                                        $maestraRes = $this->obtenetenerNombreMaestra($criterio);
                                                        $cambios["maestra1_lbl"] = $maestraRes["maestra"];
                                                        array_push($cambios["maestra1_criterio"], $maestraRes["criterio"]);
                                                    }
                                                }
                                                if($columna == "maestra2"){
                                                    $cambios["maestra2_criterio"] = "";
                                                    $array_criterios = explode(',', $dato);
                                                    foreach($array_criterios as $criterio){
                                                        $maestraRes = $this->obtenetenerNombreMaestra($criterio);
                                                        $cambios["maestra2_lbl"] = $maestraRes["maestra"];
                                                        array_push($cambios["maestra2_criterio"], $maestraRes["criterio"]);
                                                    }
                                                }
                                                if($columna == "usuario_aprobacion"){
                                                    $accion = "Aprobación proceso.";
                                                    $cambios["fecha_aprobacion"] = $this->intelcost->castiarFechayHoraIntelcost($objOferta['fecha_aprobacion']);
                                                }
                                                if($columna == "solpeds" || $columna == "objeto" || $columna == "descripcion" || $columna == "moneda" || $columna == "presupuesto" || $columna == "id_actividad" || $columna == "maestra1" || $columna == "maestra2" || $columna == "regional"){
                                                    $accion = "Contenido del proceso. ";
                                                }
                                                if($columna == "fecha_inicio" || $columna == "hora_inicio" || $columna == "fecha_cierre" || $columna == "hora_cierre"){
                                                    $accion = "Cronograma del proceso. ";
                                                }
                                                if($columna == "fecha_limite" || $columna == "hora_limite" || $columna == "bool_condicion_mensajes" || $columna == "fecha_limite_obs"){
                                                    $cambios['fecha_limite_par'] = (($objOferta['fecha_limite'] == "0000-00-00" || $objOferta['fecha_limite'] == "") ? "" : $this->intelcost->castiarFechaIntelcost($objOferta['fecha_limite']));
                                                    $cambios['hora_limite_par'] = (($objOferta['hora_limite'] == "00:00:00" || $objOferta['hora_limite'] == "") ? "" : $objOferta['hora_limite']);
                                                    $cambios['fecha_limite_obs_par'] = (($objOferta['fecha_limite_obs_par'] == "0000-00-00" || $objOferta['fecha_limite_obs_par'] == "") ? "" : $this->intelcost->castiarFechaIntelcost($objOferta['fecha_limite_obs_par']));
                                                }

                                                if($columna == "apertura_capitulos"){
                                                    $accion = "Apertura de capítulos.";
                                                    $queryAperturaCap = "SELECT HA.fecha_creacion, U.nombre FROM historial_aprobaciones AS HA INNER JOIN usuarios AS U ON HA.usuario_id = U.id WHERE HA.tipo_historial = 'apertura capítulos' AND HA.oferta_id = $idOferta";                                                
                                                    $sqlAperturaCap = $dbConection->query($queryAperturaCap);
                                                    if($sqlAperturaCap){
                                                        if($sqlAperturaCap->num_rows > 0){
                                                            $dataApertura = $sqlAperturaCap->fetch_assoc();
                                                            $cambios['fecha_apertura'] = $this->intelcost->castiarFechayHoraIntelcost($dataApertura['fecha_creacion']);
                                                            $objOferta['nombre_edita'] = $dataApertura['nombre'];
                                                            $cambios['usuario_apertura'] = $dataApertura['nombre'];
                                                        }
                                                    }
                                                }
                                                if($columna == "estado"){
                                                    $accion ="Cambio de estado.";
                                                    if($dato == "EN APROBACION" && ($_SESSION['empresaid'] == "14" || $_SESSION['empresaid'] == "26" || $_SESSION['empresaid'] == "6"  || $_SESSION["empresaid"] == 25 || $_SESSION['empresaid'] == "27" || $_SESSION['empresaid'] == "20" || $_SESSION['empresaid'] == "9")){
                                                        $accion = "Solicitud de aprobación.";
                                                        $queryAprobacion = "SELECT HA.fecha_creacion, HA.observacion, U.nombre FROM historial_aprobaciones AS HA INNER JOIN usuarios AS U ON HA.usuario_id = U.id WHERE HA.tipo_historial = 'Solicitud Aprobación' AND HA.oferta_id = $idOferta";                                               
                                                        $sqlAprobacion = $dbConection->query($queryAprobacion);
                                                        if($sqlAprobacion){
                                                            if($sqlAprobacion->num_rows > 0){
                                                                $dataAprobacion = $sqlAprobacion->fetch_assoc();
                                                                $cambios['fecha_solicitud'] = $this->intelcost->castiarFechayHoraIntelcost($dataAprobacion['fecha_creacion']);
                                                                $objOferta['nombre_edita'] = $dataAprobacion['nombre'];
                                                                $cambios['usuario_solicita'] = $dataAprobacion['nombre'];
                                                                $cambios['observacion_aprobacion'] = $dataAprobacion['observacion'];
                                                            }
                                                        }
                                                    }
                                                    if($dato == "APROBADA" && $objOferta['requiere_aprobacion'] == "si" && ($_SESSION['empresaid'] == "14" || $_SESSION['empresaid'] == "26" || $_SESSION['empresaid'] == "6"  || $_SESSION["empresaid"] == 25 || $_SESSION["empresaid"] == 9 || $_SESSION['empresaid'] == "27" || $_SESSION['empresaid'] == "20")){
                                                        $accion = "Aprobación proceso.";
                                                        $queryAprobacion = "SELECT HA.fecha_creacion, HA.observacion, U.nombre FROM historial_aprobaciones AS HA INNER JOIN usuarios AS U ON HA.usuario_id = U.id WHERE HA.tipo_historial = 'Oferta Aprobada' AND HA.oferta_id = $idOferta";                                                
                                                        $sqlAprobacion = $dbConection->query($queryAprobacion);
                                                        if($sqlAprobacion){
                                                            if($sqlAprobacion->num_rows > 0){
                                                                $dataAprobacion = $sqlAprobacion->fetch_assoc();
                                                                $cambios['requiere_aprobacion'] = $objOferta['requiere_aprobacion'];
                                                                $cambios['fecha_aprobacion'] = $this->intelcost->castiarFechayHoraIntelcost($dataAprobacion['fecha_creacion']);
                                                                $objOferta['nombre_edita'] = $dataAprobacion['nombre'];
                                                                $cambios['usuario_apueba'] = $dataAprobacion['nombre'];
                                                                $cambios['observacion_aprobacion'] = $dataAprobacion['observacion'];
                                                            }
                                                        }
                                                    }
                                                    if($dato == "APROBADA" && $_SESSION['empresaid'] == 10){
                                                        //APROBACION PARA METRO.
                                                        $accion = "Aprobación proceso.";
                                                        $queryUsuario = "SELECT nombre FROM usuarios WHERE id = $objOferta[usuario_aprobacion] ";
                                                        $sqlUsuarioAprueba = $dbConection->query($queryUsuario);
                                                        if($sqlUsuarioAprueba){
                                                            if($sqlUsuarioAprueba->num_rows > 0){
                                                                $dataUsAprueba = $sqlUsuarioAprueba->fetch_assoc();
                                                                $objOferta['fecha_modificacion'] = $objOferta['fecha_aprobacion'];
                                                                $objOferta['nombre_edita'] = $dataUsAprueba['nombre'];

                                                            }
                                                        }
                                                    }
                                                    if($dato == "RECHAZADA" && $objOferta['requiere_aprobacion'] == "si" && ($_SESSION['empresaid'] == "14" || $_SESSION['empresaid'] == "26" || $_SESSION['empresaid'] == "6"  || $_SESSION["empresaid"] == 25 || $_SESSION["empresaid"] == 9 || $_SESSION['empresaid'] == "27" || $_SESSION['empresaid'] == "20")){
                                                        $accion = "Proceso rechazado.";
                                                        $queryAprobacion = "SELECT HA.fecha_creacion, HA.observacion, U.nombre FROM historial_aprobaciones AS HA INNER JOIN usuarios AS U ON HA.usuario_id = U.id WHERE HA.tipo_historial = 'Oferta Rechazada' AND HA.oferta_id = $idOferta";                                               
                                                        $sqlAprobacion = $dbConection->query($queryAprobacion);
                                                        if($sqlAprobacion){
                                                            if($sqlAprobacion->num_rows > 0){
                                                                $dataAprobacion = $sqlAprobacion->fetch_assoc();
                                                                $cambios['requiere_aprobacion'] = $objOferta['requiere_aprobacion'];
                                                                $cambios['fecha_rechazo'] = $this->intelcost->castiarFechayHoraIntelcost($dataAprobacion['fecha_creacion']);
                                                                $objOferta['nombre_edita'] = $dataAprobacion['nombre'];
                                                                $cambios['usuario_rechaza'] = $dataAprobacion['nombre'];
                                                                $cambios['observacion_rechazo'] = $dataAprobacion['observacion'];
                                                            }
                                                        }
                                                    }
                                                }
                                                $cambios[$columna] = $dato;

                                            }
                                        }
                                        if($banderaCambios){
                                            $respuesta = array();
                                            $respuesta['accion'] = $accion;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($objOferta['fecha_modificacion']);
                                            $respuesta['usuario'] = (!empty($objOferta['nombre_edita']) ? ucwords($objOferta['nombre_edita']) : "Sistema");
                                            $respuesta['data'] = $cambios;
                                            array_push($arrayHistorialOferta, $respuesta);
                                        }
                                        $arrayValidaOferta = $objOferta;
                                    }else{
                                        $objOferta['actividad'] = $this->obtenerNombreActividad($objOferta['id_actividad']);
                                        if(!empty($objOferta["maestra1"])){
                                            $objOferta["maestra1_criterio"] = "";
                                            $array_criterios = explode(',', $objOferta["maestra1"]);
                                            foreach($array_criterios as $criterio){
                                                $maestraRes = $this->obtenetenerNombreMaestra($criterio);
                                                $objOferta["maestra1_lbl"] = $maestraRes["maestra"];
                                                $objOferta["maestra1_criterio"] .= "&bull; ".$maestraRes["criterio"]."<br />";
                                            }

                                        }
                                        if(!empty($objOferta["maestra2"])){
                                            $array_criterios = explode(',', $objOferta["maestra2"]);
                                            $objOferta["maestra2_criterio"] = "";
                                            foreach($array_criterios as $criterio){
                                                $maestraRes = $this->obtenetenerNombreMaestra($criterio);
                                                $objOferta["maestra2_lbl"] = $maestraRes["maestra"];
                                                $objOferta["maestra2_criterio"] .= "&bull; ".$maestraRes["criterio"]."<br />";
                                            }
                                        }
                                        $arrayValidaOferta = $objOferta;
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación proceso.";
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($objOferta['fecha_modificacion']);
                                        $respuesta['usuario'] = ucwords($objOferta['nombre_creador']);
                                        $respuesta['data'] = $objOferta;
                                        array_push($arrayHistorialOferta, $respuesta);
                                    }
                                }

                                $historicoBitacora['proceso'] = $arrayHistorialOferta;
                                
                                //HISTORIAL VISITAS DE OBRA.
                                $historicoBitacora['visitaObra'] = array();
                                $objVisitaObra = $this->obtenerHistorialBitacoraOfertaVisitaObra($idOferta);
                                if($objVisitaObra->bool){
                                    $historicoBitacora['visitaObra'] = $objVisitaObra->msg;
                                }
                                //DOCUMENTOS OFERTA.
                                $historicoBitacora['documentosOferta'] = array();
                                $objDocumentosOferta = $this->obtenerHistorialBitacoraOfertaDocumentos($idOferta);
                                if($objDocumentosOferta->bool){
                                    $historicoBitacora['documentosOferta'] = $objDocumentosOferta->msg;
                                }
                                //ENTREGABLES DE LA OFERTA.
                                $historicoBitacora['entregablesOferta'] = array();
                                $objEntregablesOferta = $this->obtenerHistorialBitacoraOfertaEntregables($idOferta);
                                if($objEntregablesOferta->bool){
                                    $historicoBitacora['entregablesOferta'] = $objEntregablesOferta->msg;
                                }
                                //LOTES
                                $historicoBitacora['lotesOferta'] = array();
                                $objLotesItemsOferta = $this->obtenerHistorialBitacoraOfertaLotes($idOferta);
                                if($objLotesItemsOferta->bool){
                                    $historicoBitacora['lotesOferta'] = $objLotesItemsOferta->msg;
                                }
                                //USUARIOS INTERNOS
                                $historicoBitacora['usuariosInternos'] = array();
                                $objUsuariosInternos = $this->obtenerHistorialBitacoraOfertaUsuariosInternos($idOferta);
                                if($objUsuariosInternos->bool){
                                    $historicoBitacora['usuariosInternos'] = $objUsuariosInternos->msg;
                                }
                                //APROBADORES
                                $historicoBitacora['usuariosAprobadores'] = array();
                                $objUsuariosAprobadores = $this->obtenerHistorialBitacoraOfertaAprobadores($idOferta);
                                if($objUsuariosAprobadores->bool){
                                    $historicoBitacora['usuariosAprobadores'] = $objUsuariosAprobadores->msg;
                                }
                                //PARTICIPANTES
                                $historicoBitacora['participantes'] = array();
                                $historicoParticipantes = $this->obtenerHistorialBitacoraOfertaPaticipantes($idOferta);
                                if($historicoParticipantes->bool){
                                    $historicoBitacora['participantes'] = $historicoParticipantes->msg;
                                }
                                //HISTORIAL EVALUACIONES
                                $historicoBitacora['evaluaciones'] = array();
                                $historicoEvaluaciones = $this->obtenerHistorialBitacoraOfertaEvaluaciones($idOferta);
                                if($historicoEvaluaciones->bool){
                                    $historicoBitacora['evaluaciones'] = $historicoEvaluaciones->msg;
                                }
                                //ADJUDICACIÓN
                                $historicoBitacora['adjudicacion'] = array();
                                $historicoAdjudicaciones = $this->obtenerHistorialBitacoraOfertaAdjudicacion($idOferta);
                                if($historicoAdjudicaciones->bool){
                                    $historicoBitacora['adjudicacion'] = $historicoAdjudicaciones->msg;
                                }
                                
                                //DELEGACIONES 
                                $historicoBitacora['delegaciones'] = array();
                                $objDelegaciones = $this->obtenerHistorialBitacoraOfertaDelegaciones($idOferta);
                                if($objDelegaciones->bool){
                                    $historicoBitacora['delegaciones'] = $objDelegaciones->msg;
                                }

                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg = $historicoBitacora;
                            }else{
                                $this->intelcost->response->bool = false;
                                $this->intelcost->response->msg ="No se encontró información.";
                            }
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg ="Error al recuperar los datos.";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg ="Error de ejecución consulta.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Error parametros.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error de ejecución.";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión.";
        }
        return $this->intelcost->response;
    }

    private function obtenerHistorialBitacoraOfertaDelegaciones($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayDelegaciones = array();
            $queryDelegaciones = "SELECT LOWER(UD1.nombre)AS usuario_anterior, LOWER(US2.nombre) AS usuario_actual, OD.fecha_inicio, OD.fecha_fin, OD.estado, OD.observaciones, OD.duenio_original, OD.fecha_registro, LOWER(USC.nombre) AS delegador FROM ofertas_delegadas AS OD INNER JOIN usuarios AS US2 ON US2.id = OD.duenio_actual_oferta INNER JOIN usuarios AS UD1 ON UD1.id = OD.duenio_anterior_oferta LEFT JOIN usuarios AS USC ON USC.id = OD.usuario_registro WHERE OD.id_oferta = ? ORDER BY OD.id ASC";
            if($sqlDelegaciones = $dbConection->prepare($queryDelegaciones)){
                if($sqlDelegaciones->bind_param('i', $idOferta)){
                    if($sqlDelegaciones->execute()){
                        if($resultado = $sqlDelegaciones->get_result()){
                            if($resultado->num_rows > 0){
                                while ($delegacion = $resultado->fetch_assoc()) {
                                    $delegacion['usuario_anterior'] = ucwords($delegacion['usuario_anterior']);
                                    $delegacion['usuario_actual'] = ucwords($delegacion['usuario_actual']);
                                    $delegacion['delegador'] = (!(empty($delegacion['delegador'])) ? ucwords($delegacion['delegador']) : "Sistema");
                                    $delegacion['fecha_inicio'] = $this->intelcost->castiarFechayHoraIntelcost($delegacion['fecha_inicio']);
                                    $delegacion['fecha_fin'] = $this->intelcost->castiarFechayHoraIntelcost($delegacion['fecha_fin']);
                                    $delegacion['fecha_registro'] = $this->intelcost->castiarFechayHoraIntelcost($delegacion['fecha_registro']);
                                    $delegacion['delegador'] = (((int) $delegacion['usuario_registro'] == 0) ? $delegacion['delegador'] : "Sistema");
                                    array_push($arrayDelegaciones, $delegacion);
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayDelegaciones;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión.";
        }
        return $this->intelcost->response;
    }

    private function obtenerHistorialBitacoraOfertaVisitaObra($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayVisitasObra = array();
            $queryHistorialVisitas = "SELECT TG.tipo, TG.fecha, TG.lugar, TG.hora, TG.responsable, TG.telefono, TG.obligatorio, TG.observaciones, TG.acta, TG.fecha_creacion, TG.usuario_actualizacion, TG.fecha_actualizacion, TG.estado, TG.accion, TG.fecha_modificacion, O.estado AS estado_visita, LOWER(U.nombre) as nombre_creador, LOWER(UA.nombre) as nombre_actualiza FROM oferta_visitasobra AS O INNER JOIN tg_oferta_visitasobra AS TG ON TG.id = O.id LEFT JOIN usuarios AS U ON U.id = TG.usuario_actualizacion LEFT JOIN usuarios AS UA ON UA.id = TG.usuario_actualizacion WHERE O.oferta_id = ? ORDER BY O.id, TG.fecha_modificacion ";
            if($sqlHistorialVisitas = $dbConection->prepare($queryHistorialVisitas)){
                if($sqlHistorialVisitas->bind_param('i', $idOferta)){
                    if($sqlHistorialVisitas->execute()){
                        if($resultado = $sqlHistorialVisitas->get_result()){
                            if($resultado->num_rows > 0){
                                $validaVisita = array();
                                $arrayLlavesValidar = array("tipo", "fecha", "lugar", "hora", "responsable", "telefono", "obligatorio", "observaciones", "acta", "usuario_actualizacion", "estado", "estado_visita");
                                while ($visita = $resultado->fetch_assoc()) {
                                    $visita['fecha_visita'] = $this->intelcost->castiarFechaIntelcost($visita['fecha']);
                                    if(!empty($validaVisita)){
                                        if($visita['accion'] == "INSERTO"){
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación visita.";
                                            $respuesta['data'] = $visita;
                                            $respuesta['usuario'] = ucwords($visita['nombre_creador']);
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($visita['fecha_creacion']);
                                            array_push($arrayVisitasObra, $respuesta);
                                            $validaVisita = $visita;
                                        }else{
                                            $cambios = array();
                                            $banderaCambios = false;
                                            foreach ($visita as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaVisita[$columna] != $visita[$columna]){
                                                    $banderaCambios = true;
                                                    $cambios[$columna] = $dato;
                                                }
                                            }
                                            if($banderaCambios){
                                                $respuesta = array();
                                                $respuesta['accion'] = "Modificación visita.";
                                                $respuesta['data'] = $cambios;
                                                $respuesta['usuario'] = ucwords($visita['nombre_actualiza']);
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($visita['fecha_modificacion']);
                                                array_push($arrayVisitasObra, $respuesta);
                                            }
                                            $validaVisita = $visita;
                                        }
                                    }else{
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación visita.";
                                        $respuesta['data'] = $visita;
                                        $respuesta['usuario'] = ucwords($visita['nombre_creador']);
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($visita['fecha_creacion']);
                                        array_push($arrayVisitasObra, $respuesta);
                                        $validaVisita = $visita;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayVisitasObra;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora visita obra.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaDocumentos($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayDocumentosOferta = array();
            $queryDocOferta = "SELECT O.id, TG.seq_id, TG.ruta, TG.titulo, TG.tipo, TRIM(TG.contenido) as contenido, O.fecha_registro, TG.estado, TG.usuario_actualizacion, TG.fecha_actualizacion, TG.accion, TG.fecha_modificacion, LOWER(U.nombre) AS usuario_modifica FROM oferta_documentos AS O INNER JOIN tg_oferta_documentos AS TG ON TG.id = O.id LEFT JOIN usuarios AS U ON U.id = TG.usuario_actualizacion WHERE O.id_oferta = ? ORDER BY O.id, TG.fecha_modificacion";
            if($sqlDocOferta = $dbConection->prepare($queryDocOferta)){
                if($sqlDocOferta->bind_param('i', $idOferta)){
                    if($sqlDocOferta->execute()){
                        if($resultado = $sqlDocOferta->get_result()){
                            if($resultado->num_rows > 0){
                                $creadorDocumento = "";
                                //SÍ EL REGISTRO NO CONTIENE EL ID DEL USUARIO QUE GENERA LA ACCIÓN, ASIGNAR AL RESPONSABLE DEL EVENTO.
                                /*$queryDuenioOferta = "SELECT LOWER(u.nombre) as nombre, u.id FROM ofertas AS o INNER JOIN usuarios AS u ON u.id = o.duenio_oferta WHERE o.id = $idOferta ";
                                $sqlDuenioOferta = $dbConection->query($queryDuenioOferta);
                                if($sqlDuenioOferta){
                                    $dataDuenioOferta = $sqlDuenioOferta->fetch_assoc();
                                    $creadorDocumento = $dataDuenioOferta['nombre'];
                                }*/
                                $validaDocOferta = array();
                                $arrayLlavesValidar = array("seq_id", "ruta", "titulo", "tipo", "contenido", "estado");
                                while ($documento = $resultado->fetch_assoc()) {
                                    $documento['ruta'] = (($documento['ruta'] != "") ? $this->intelcost->generaRutaServerFiles($documento["ruta"], "cliente") : "");
                                    if($documento['tipo'] == "archivo" && $documento['contenido'] != ""){
                                        $documento['contenido'] = $this->intelcost->generaRutaServerFiles($documento["contenido"], "cliente");
                                    }
                                    if(!empty($validaDocOferta)){
                                        if(($documento['accion'] == "INSERTO")){
                                            $documento['estado_documento'] = (($documento['estado'] == "activo2" || $documento['estado'] == "INACTIVO") ? "eliminado" : $documento['estado']);
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación documento.";
                                            $respuesta['usuario'] = ((!empty($documento['usuario_modifica'])) ? ucwords($documento['usuario_modifica']) : "No registra." );
                                            $respuesta['data'] = $documento;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($documento['fecha_modificacion']);
                                            array_push($arrayDocumentosOferta, $respuesta);
                                            $validaDocOferta = $documento;
                                        }else{
                                            $accion = "Modificación documento.";
                                            $cambios = array();
                                            $banderaCambios = false;
                                            foreach ($documento as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaDocOferta[$columna] != $documento[$columna]){
                                                    $banderaCambios = true;
                                                    if ($columna == 'contenido') {
                                                        $cambios['tipo'] = $documento['tipo'];
                                                    }
                                                    if($columna == "estado" && ($dato == "activo2" || $dato == "INACTIVO") ){
                                                        $accion = "Documento eliminado.";
                                                        $cambios['titulo'] = $documento['titulo'];
                                                    }
                                                    $cambios[$columna] = $dato;
                                                }
                                            }
                                            if($banderaCambios){
                                                $cambios['ruta'] = $documento['ruta'];
                                                $cambios['contenido'] = $documento['contenido'];
                                                $cambios['estado_documento'] = (($documento['estado'] == "activo2" || $documento['estado'] == "INACTIVO") ? "eliminado" : $documento['estado']);
                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['usuario'] = (($documento['usuario_modifica'] == "" || empty($documento['usuario_modifica'])) ? "No registra." : ucwords($documento['usuario_modifica']));
                                                $respuesta['data'] = $cambios;
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($documento['fecha_modificacion']);
                                                array_push($arrayDocumentosOferta, $respuesta);
                                            }
                                        }
                                        $validaDocOferta = $documento;
                                    }else{
                                        $documento['estado_documento'] = (($documento['estado'] == "activo2" || $documento['estado'] == "INACTIVO") ? "eliminado" : $documento['estado']);
                                        $respuesta = array();
                                        $respuesta['accion'] = (($documento['accion'] == "INSERTO") ? "Creación documento.": "Modificación documento.");
                                        $respuesta['usuario'] = ((!empty($documento['usuario_modifica'])) ? ucwords($documento['usuario_modifica']) : "No registra." );
                                        $respuesta['data'] = $documento;
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($documento['fecha_modificacion']);
                                        array_push($arrayDocumentosOferta, $respuesta);
                                        $validaDocOferta = $documento;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayDocumentosOferta;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora documentos oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaEntregables($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayEntregablesOferta = array();
            $queryEntregablesOferta = "SELECT O.id, TG.titulo, TG.descripcion, TG.doc_id, TG.seq_id, TG.obligatorio, TG.tipo, TG.contenido, TG.estado, O.fecha_creacion, TG.usuario_actualizacion, TG.fecha_actualizacion, TG.sobre, TG.evaluable, TG.tipo_evaluacion, TG.parametro_evaluacion, TG.accion, TG.fecha_modificacion, LOWER(U.nombre) as usuario_modifica, U.email, CA.nombre as sobre_nombre FROM oferta_documentos_oferentes AS O INNER JOIN tg_oferta_documentos_oferentes AS TG ON TG.id = O.id LEFT JOIN usuarios AS U ON U.id = TG.usuario_actualizacion LEFT JOIN capitulos AS CA ON CA.id = TG.sobre WHERE O.oferta_id = ? ORDER BY O.id ASC, TG.fecha_modificacion ASC";
            if($sqlEntOferta = $dbConection->prepare($queryEntregablesOferta)){
                if($sqlEntOferta->bind_param('i', $idOferta)){
                    if($sqlEntOferta->execute()){
                        if($resultado = $sqlEntOferta->get_result()){
                            if($resultado->num_rows > 0){
                                $validaEntregable = array();
                                $ultimoId = 0;
                                $arrayLlavesValidar = array("titulo", "descripcion", "seq_id", "obligatorio", "tipo", "contenido", "estado", "sobre");
                                if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION["empresaid"] == 25 || $_SESSION["empresaid"] == 9 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20){
                                    array_push($arrayLlavesValidar, "evaluable", "tipo_evaluacion", "parametro_evaluacion");
                                }
                                while ($entregable = $resultado->fetch_assoc()) {
                                    $entregable['tipo'] = (empty($entregable['tipo']) ? "archivo" : $this->castearTipoDocumento($entregable['tipo']));
                                    if($_SESSION['empresaid'] != 14 && $_SESSION['empresaid'] != 26 && $_SESSION['empresaid'] != 27 && $_SESSION['empresaid'] != 20){
                                        unset($entregable['evaluable']);
                                    }else{
                                        $fecha_creacion = strtotime(date('Y-m-d H:i:s', strtotime($entregable['fecha_creacion'])));
                                        $fecha_limite = strtotime(date('Y-m-d H:i:s', strtotime("2019-01-11 00:00:00")));
                                        if($fecha_creacion <= $fecha_limite){
                                            $entregable['evaluable'] = "si";
                                        }
                                    }
                                    if($entregable['evaluable'] == "si"){
                                        switch ($entregable['tipo_evaluacion']) {
                                            case "puntuable":
                                                $entregable['id_tipo_evaluacion'] = 1;
                                                break;
                                            case "cumple - no cumple":
                                                $entregable['id_tipo_evaluacion'] = 2;
                                                break;
                                            default:
                                                $entregable['id_tipo_evaluacion'] = 2;
                                                $entregable['tipo_evaluacion'] = "cumple - no cumple";
                                                break;
                                        }
                                    }
                                    if(!empty($validaEntregable)){
                                        if($ultimoId != (int) $entregable['id']){
                                            $entregable['sobre_nombre'] = (!empty($entregable['sobre_nombre']) ? $entregable['sobre_nombre'] : "Otro.");
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación entregable.";
                                            $respuesta['data'] = $entregable;
                                            $respuesta['usuario'] = (($entregable['usuario_modifica'] == "" || empty($entregable['usuario_modifica'])) ? "No registra." : ucwords($entregable['usuario_modifica']));
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($entregable['fecha_creacion']);
                                            array_push($arrayEntregablesOferta, $respuesta);
                                        }else{
                                            $cambios = array();
                                            $banderaCambios = false;
                                            $accion = "Modificación entregable.";
                                            foreach ($entregable as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaEntregable[$columna] != $entregable[$columna]){
                                                    $banderaCambios = true;
                                                    if($columna == "estado"){
                                                        $dato = "Eliminado";
                                                        $accion = "Entregable eliminado.";
                                                        $cambios['titulo'] = $entregable['titulo'];
                                                    }
                                                    if($columna == "sobre"){
                                                        $cambios['sobre_nombre'] = (!empty($entregable['sobre_nombre']) ? $entregable['sobre_nombre'] : "Otro.");
                                                    }
                                                    if($columna == "parametro_evaluacion"){
                                                        $cambios['id_tipo_evaluacion'] = $entregable['id_tipo_evaluacion'];
                                                    }
                                                    $cambios[$columna] = $dato;
                                                }
                                            }
                                            if($banderaCambios){
                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['data'] = $cambios;
                                                $respuesta['usuario'] = (($entregable['usuario_modifica'] == "" || empty($entregable['usuario_modifica'])) ? "No registra." : ucwords($entregable['usuario_modifica']));
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($entregable['fecha_modificacion']);
                                                array_push($arrayEntregablesOferta, $respuesta);
                                            }
                                        }
                                        $ultimoId = $entregable['id'];
                                        $validaEntregable = $entregable;
                                    }else{
                                        $ultimoId = (int) $entregable['id'];
                                        $entregable['sobre_nombre'] = (!empty($entregable['sobre_nombre']) ? $entregable['sobre_nombre'] : "Otro.");
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación entregable.";
                                        $respuesta['data'] = $entregable;
                                        $respuesta['usuario'] = (($entregable['usuario_modifica'] == "" || empty($entregable['usuario_modifica'])) ? "No registra." : ucwords($entregable['usuario_modifica']));
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($entregable['fecha_creacion']);
                                        array_push($arrayEntregablesOferta, $respuesta);
                                        $validaEntregable = $entregable;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayEntregablesOferta;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora entregables oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaLotes($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayLotesOferta = array();
            $queryLotesOferta = "SELECT OL.id_lote, OL.nombre_lote, C.nombre FROM oferta_lotes AS OL LEFT JOIN capitulos AS C ON C.id = OL.cod_sobre WHERE OL.cod_oferta = ?";
            if($sqlLotesOferta = $dbConection->prepare($queryLotesOferta)){
                if($sqlLotesOferta->bind_param('i', $idOferta)){
                    if($sqlLotesOferta->execute()){
                        if($resultado = $sqlLotesOferta->get_result()){
                            if($resultado->num_rows > 0){
                                while ($lote = $resultado->fetch_assoc()) {
                                    $queryTgLotesOferta = "SELECT OL.id_lote, TG.secuencia, TG.nombre_lote, TG.cod_sobre, TG.estado, OL.fecha_creacion, TG.accion, TG.fecha_modificacion, TG.usuario_creacion as id_creador, LOWER(UC.nombre) as nombre_creador, TG.usuario_actualizacion as id_modifica, LOWER(UM.nombre) as usuario_modifica, CA.nombre as sobre_nombre FROM oferta_lotes AS OL INNER JOIN tg_oferta_lotes AS TG ON TG.id_lote = OL.id_lote LEFT JOIN usuarios AS UC ON UC.id = TG.usuario_creacion LEFT JOIN usuarios AS UM ON UM.id = TG.usuario_actualizacion LEFT JOIN capitulos AS CA ON CA.id = TG.cod_sobre WHERE OL.id_lote = $lote[id_lote] ORDER BY OL.id_lote, TG.fecha_modificacion";
                                    $sqlTgLotesOferta = $dbConection->query($queryTgLotesOferta);
                                    if($sqlTgLotesOferta){
                                        if($sqlTgLotesOferta->num_rows > 0){
                                            $validaLote = array();
                                            $arrayLlavesValidar = array("secuencia", "nombre_lote", "cod_sobre", "estado");
                                            $arrayLotes = array();
                                            while ($tgLote = $sqlTgLotesOferta->fetch_assoc()) {
                                                if(!empty($validaLote)){
                                                    if(($tgLote['accion'] == "INSERTO")){
                                                        $respuesta = array();
                                                        $respuesta['accion'] = "Creación lote.";
                                                        $respuesta['usuario'] = ucwords($tgLote['nombre_creador']);
                                                        $respuesta['data'] = $tgLote;
                                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($tgLote['fecha_creacion']);
                                                        array_push($arrayLotes, $respuesta);
                                                    }else{
                                                        $cambios = array();
                                                        $banderaCambios = false;
                                                        $accion = "Modificación lote.";
                                                        foreach ($tgLote as $columna => $dato) {
                                                            if(in_array($columna, $arrayLlavesValidar) && $validaLote[$columna] != $tgLote[$columna]){
                                                                $banderaCambios = true;
                                                                if($columna == "cod_sobre"){
                                                                    $cambios['sobre_nombre'] = (!empty($tgLote['sobre_nombre']) ? $tgLote['sobre_nombre'] : "otro.");
                                                                }
                                                                if($columna == "estado" && $dato == "eliminada"){
                                                                    $cambios['nombre_lote'] = $tgLote['nombre_lote'];
                                                                    $accion = "Lote eliminado";
                                                                }
                                                                $cambios[$columna] = $dato;
                                                            }
                                                        }
                                                        if($banderaCambios){
                                                            $respuesta = array();
                                                            $respuesta['accion'] = $accion;
                                                            $respuesta['usuario'] = ucwords($tgLote['usuario_modifica']);
                                                            $respuesta['data'] = $cambios;
                                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($tgLote['fecha_modificacion']);
                                                            array_push($arrayLotes, $respuesta);
                                                        }
                                                    }
                                                    $validaLote = $tgLote;
                                                }else{
                                                    $respuesta = array();
                                                    $respuesta['accion'] = "Creación lote.";
                                                    $respuesta['usuario'] = ucwords($tgLote['nombre_creador']);
                                                    $respuesta['data'] = $tgLote;
                                                    $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($tgLote['fecha_creacion']);
                                                    array_push($arrayLotes, $respuesta);
                                                    $validaLote = $tgLote;
                                                }
                                            }
                                            $lote['historial_lote'] = $arrayLotes;
                                            $lote['historial_items'] = array();
                                            $objItems = $this->obtenerHistorialBitacoraOfertaLotesItems($lote['id_lote']);
                                            if($objItems->bool){
                                                $lote['historial_items'] = $objItems->msg;
                                            }
                                            $lote['nombre_lote'] = ((empty($lote['nombre_lote'])) ? "Otro." : $lote['nombre_lote']);
                                            array_push($arrayLotesOferta, $lote);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayLotesOferta;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora lotes oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaLotesItems($idLote){
        $idLote = (int) $idLote;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayLotesItemsOferta = array();
            $queryLotesItemsOferta = "SELECT OL.id_item, OL.estado, TG.secuencia, TG.descripcion, TG.cod_unidad_medida as id_medida, MU.medida, MU.descripcion as desc_medida, TG.cantidad, TG.usuario_creacion, LOWER(UC.nombre) as nombre_creador, TG.fecha_creacion, TG.usuario_actualizacion, LOWER(UM.nombre) as nombre_modifica, TG.fecha_actualizacion, TG.estado AS estado_tg, TG.accion, TG.fecha_modificacion FROM oferta_lotes_items AS OL INNER JOIN tg_oferta_lotes_items AS TG ON TG.id_item = OL.id_item LEFT JOIN usuarios AS UC ON UC.id = TG.usuario_creacion LEFT JOIN usuarios AS UM ON UM.id = TG.usuario_actualizacion LEFT JOIN mst_unidad_medidas AS MU ON MU.id_medida = TG.cod_unidad_medida WHERE OL.cod_lote = ? order by OL.id_item, TG.fecha_modificacion";
            if($sqlLotesItemsOferta = $dbConection->prepare($queryLotesItemsOferta)){
                if($sqlLotesItemsOferta->bind_param('i', $idLote)){
                    if($sqlLotesItemsOferta->execute()){
                        if($resultado = $sqlLotesItemsOferta->get_result()){
                            if($resultado->num_rows > 0){
                                $validaItem = array();
                                $arrayLlavesValidar = array("secuencia", "descripcion", "id_medida", "cantidad", "estado");
                                while ($item = $resultado->fetch_assoc()) {
                                    if(!empty($validaItem)){
                                        if($item['accion'] == "INSERTO"){
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación item lote.";
                                            $respuesta['usuario'] = ucwords($item['nombre_creador']);
                                            $respuesta['data'] = $item;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($item['fecha_creacion']);
                                            array_push($arrayLotesItemsOferta, $respuesta);
                                        }else{
                                            $banderaCambios = false;
                                            $accion = "Modificación lote.";
                                            foreach ($item as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaItem[$columna] != $item[$columna]){
                                                    $banderaCambios = true;
                                                    if($columna == "estado" && $dato == "eliminado"){
                                                        $accion = "Item eliminado.";
                                                    }
                                                }
                                            }
                                            if($banderaCambios){
                                                $cambios = array();
                                                $cambios['descripcion'] = $item['descripcion'];
                                                $cambios['medida'] = $item['medida'];
                                                $cambios['id_medida'] = $item['id_medida'];
                                                $cambios['medida'] = $item['medida'];
                                                $cambios['desc_medida'] = $item['desc_medida'];
                                                $cambios['cantidad'] = $item['cantidad'];

                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['usuario'] = ucwords($item['nombre_modifica']);
                                                $respuesta['data'] = $cambios;
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($item['fecha_modificacion']);
                                                array_push($arrayLotesItemsOferta, $respuesta);
                                            }
                                        }
                                        $validaItem = $item;
                                    }else{
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación item lote.";
                                        $respuesta['usuario'] = ucwords($item['nombre_creador']);
                                        $respuesta['data'] = $item;
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($item['fecha_creacion']);
                                        array_push($arrayLotesItemsOferta, $respuesta);
                                        $validaItem = $item;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayLotesItemsOferta;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora lotes - items oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaUsuariosInternos($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayUsuariosInternos = array();
            $queryUsuariosInternos = "SELECT TG.id, LOWER(UE.nombre) as usuario_evaluador, TG.accesos, TG.envio_eval as envio_evaluacion, TG.fecha_envio_eval as fecha_envio_evaluacion, TG.usuarioregistro, TG.fecharegistro, TG.fecha_actualizacion, TG.usuario_actualizacion, TG.estado, TG.accion, TG.fecha_modificacion, LOWER(UC.nombre) as usuario_creador, UM.nombre as usuario_modifica FROM oferta_usuarios_internos AS OU INNER JOIN tg_oferta_usuarios_internos AS TG ON TG.id = OU.id LEFT JOIN usuarios AS UC ON UC.id = TG.usuarioregistro LEFT JOIN usuarios AS UM ON UM.id = TG.usuario_actualizacion INNER JOIN usuarios AS UE ON UE.id = OU.id_usuario WHERE OU.id_oferta = ? ORDER BY TG.id, TG.fecha_modificacion";
            if($sqlUsuariosInternos = $dbConection->prepare($queryUsuariosInternos)){
                if($sqlUsuariosInternos->bind_param('i', $idOferta)){
                    if($sqlUsuariosInternos->execute()){
                        if($resultado = $sqlUsuariosInternos->get_result()){
                            if($resultado->num_rows > 0){
                                $validaUsuario = array();
                                $arrayLlavesValidar = array("accesos", "estado", "envio_evaluacion", "fecha_envio_evaluacion");
                                while ($usuario = $resultado->fetch_assoc()) {
                                    if(!empty($validaUsuario)){
                                        if(($usuario['accion'] == "INSERTO")){
                                            $usuario['usuario_evaluador'] = ucwords($usuario['usuario_evaluador']);
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación evaluador.";
                                            $respuesta['usuario'] = ucwords($usuario['usuario_creador']);
                                            $respuesta['data'] = $usuario;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($usuario['fecharegistro']);
                                            array_push($arrayUsuariosInternos, $respuesta);
                                        }else{
                                            $cambios = array();
                                            $accion = "";
                                            $banderaCambios = false;
                                            foreach ($usuario as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaUsuario[$columna] != $usuario[$columna]){
                                                    $banderaCambios = true;
                                                    $cambios['usuario_evaluador'] = ucwords($usuario['usuario_evaluador']);
                                                    if($columna == "accesos"){
                                                        $accion = "Modificación de accesos.";
                                                    }
                                                    if($columna == "fecha_envio_evaluacion" && $dato != "0000-00-00 00:00:00"){
                                                        $accion = "Remisión de evaluación.";
                                                        $dato = $this->intelcost->castiarFechayHoraIntelcost($dato);
                                                    }
                                                    if($columna == "estado" && $dato == "INACTIVO"){
                                                        $dato = "Eliminado";
                                                        $accion = "Usuario eliminado";
                                                    }
                                                    $cambios[$columna] = $dato;
                                                }
                                            }
                                            if($banderaCambios){
                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['usuario'] = ucwords($usuario['usuario_modifica']);
                                                $respuesta['data'] = $cambios;
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($usuario['fecha_modificacion']);
                                                array_push($arrayUsuariosInternos, $respuesta);
                                            }
                                        }
                                        $validaUsuario = $usuario;
                                    }else{
                                        $usuario['usuario_evaluador'] = ucwords($usuario['usuario_evaluador']);
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación evaluador.";
                                        $respuesta['usuario'] = ucwords($usuario['usuario_creador']);
                                        $respuesta['data'] = $usuario;
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($usuario['fecharegistro']);
                                        array_push($arrayUsuariosInternos, $respuesta);
                                        $validaUsuario = $usuario;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayUsuariosInternos;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora lotes - items oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaAprobadores($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayAprobadores = array();
            $queryAprobadores = "SELECT TG.id_usuario_aprobador, LOWER(UA.nombre) as nombre_aprobador, TG.accesos, TG.fecha_creacion, TG.id_usuario_creacion, UC.nombre as usuario_creador, TG.fecha_modificacion, TG.id_usuario_modifica, UM.nombre as usuario_modifica, TG.estado, TG.accion FROM oferta_usuarios_aprobadores AS OA INNER JOIN tg_oferta_usuarios_aprobadores AS TG ON TG.id = OA.id LEFT JOIN usuarios AS UA ON UA.id = TG.id_usuario_aprobador LEFT JOIN usuarios AS UC ON UC.id = TG.id_usuario_creacion LEFT JOIN usuarios AS UM ON UM.id = TG.id_usuario_modifica WHERE OA.id_oferta = ? ORDER BY OA.id, TG.fecha_modificacion ";
            if($sqlAprobadores = $dbConection->prepare($queryAprobadores)){
                if($sqlAprobadores->bind_param('i', $idOferta)){
                    if($sqlAprobadores->execute()){
                        if($resultado = $sqlAprobadores->get_result()){
                            if($resultado->num_rows > 0){
                                $validaUsuario = array();
                                $arrayLlavesValidar = array("accesos", "estado");
                                while ($usuario = $resultado->fetch_assoc()) {
                                    $arrayAccesos = array();
                                    if($usuario['accesos'] != "" && $usuario['accesos'] != "[]"){
                                        $accesos = json_decode($usuario['accesos']);
                                        foreach ($accesos as $acceso) {
                                            $queryAcceso = "SELECT CA.id as sobre, OD.titulo, CA.nombre as sobre_nombre FROM oferta_documentos_oferentes AS OD LEFT JOIN capitulos AS CA ON CA.id = OD.sobre WHERE OD.id = $acceso";
                                            $sqlAcceso = $dbConection->query($queryAcceso);
                                            if($sqlAcceso){
                                                if($sqlAcceso->num_rows > 0){
                                                    $dataAcceso = $sqlAcceso->fetch_assoc();
                                                    array_push($arrayAccesos, $dataAcceso);
                                                }
                                            }
                                        }
                                    }
                                    $usuario['dataAccesos'] = json_encode($arrayAccesos);
                                    if(!empty($validaUsuario)){
                                        if(($usuario['accion'] == "INSERTO")){
                                            $usuario['nombre_aprobador'] = ucwords($usuario['nombre_aprobador']);
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación aprobador.";
                                            $respuesta['usuario'] = ucwords($usuario['usuario_creador']);
                                            $respuesta['data'] = $usuario;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($usuario['fecha_creacion']);
                                            array_push($arrayAprobadores, $respuesta);
                                            $validaUsuario = $usuario;
                                        }else{
                                            $cambios = array();
                                            $accion = "";
                                            $banderaCambios = false;
                                            foreach ($usuario as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaUsuario[$columna] != $usuario[$columna]){
                                                    $banderaCambios = true;
                                                    $cambios['nombre_aprobador'] = ucwords($usuario['nombre_aprobador']);
                                                    if($columna == "accesos"){
                                                        $accion = "Modificación de accesos.";
                                                        $cambios['dataAccesos'] = $usuario['dataAccesos'];
                                                    }
                                                    if($columna == "estado" && $dato == "inactivo"){
                                                        $dato = "Eliminado";
                                                        $accion = "Aprobador eliminado";
                                                    }
                                                    $cambios[$columna] = $dato;
                                                }
                                            }
                                            if($banderaCambios){
                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['usuario'] = ucwords($usuario['usuario_modifica']);
                                                $respuesta['data'] = $cambios;
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($usuario['fecha_modificacion']);
                                                array_push($arrayAprobadores, $respuesta);
                                            }
                                        }
                                        $validaUsuario = $usuario;
                                    }else{
                                        $usuario['nombre_aprobador'] = ucwords($usuario['nombre_aprobador']);
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación aprobador.";
                                        $respuesta['usuario'] = ucwords($usuario['usuario_creador']);
                                        $respuesta['data'] = $usuario;
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($usuario['fecha_creacion']);
                                        array_push($arrayAprobadores, $respuesta);
                                        $validaUsuario = $usuario;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayAprobadores;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora lotes - items oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaPaticipantes($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayParticipantes = array();
            $queryParticipantes = "SELECT TG.id_usuario, TG.id_proveedor, TG.nombre_contacto, TG.email_usuario, TG.carta_invitacion, TG.fecharegistro, TG.estado, TG.estado_participacion, TG.fecha_actualizacion, TG.usuario_actualizacion, TG.observaciones, TG.accion, TG.fecha_modificacion, LOWER(UC.nombre) as usuario_creador, LOWER(UM.nombre) as usuario_modifica FROM oferta_participantes AS OP INNER JOIN tg_oferta_participantes AS TG ON TG.id = OP.id LEFT JOIN usuarios AS UC ON UC.id = TG.usuario_registro LEFT JOIN usuarios AS UM ON UM.id = TG.usuario_actualizacion WHERE OP.id_oferta = ? ORDER BY OP.id, TG.fecha_modificacion";
            if($sqlParticipantes = $dbConection->prepare($queryParticipantes)){
                if($sqlParticipantes->bind_param('i', $idOferta)){
                    if($sqlParticipantes->execute()){
                        if($resultado = $sqlParticipantes->get_result()){
                            if($resultado->num_rows > 0){
                                $validaParticipante = array();
                                $arrayLlavesValidar = array("nombre_contacto", "email_usuario", "carta_invitacion", "estado", "estado_participacion", "observaciones");
                                $dataParticipantes = array();
                                while ($participante = $resultado->fetch_assoc()) {
                                    array_push($dataParticipantes, $participante);
                                }
                                foreach ($dataParticipantes as $participante) {
                                    $participante['razon_social'] = "";
                                    $objUsuario = $this->modelo_usuario->obtenerInformacionUsuarioProv($participante['id_usuario']);
                                    if($objUsuario->bool){
                                        $dataUsuario = json_decode($objUsuario->msg);
                                        $participante['razon_social'] = $dataUsuario->razon_social;
                                    }
                                    if(!empty($validaParticipante)){
                                        if(($participante['accion'] == "INSERTO")){
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación participante.";
                                            $respuesta['usuario'] = ucwords($participante['usuario_creador']);
                                            $respuesta['data'] = $participante;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($participante['fecharegistro']);
                                            array_push($arrayParticipantes, $respuesta);
                                        }else{
                                            $accion = "Modificación participante.";
                                            $cambios = array();
                                            $banderaCambios = false;
                                            $usuarioModifica = "";
                                            foreach ($participante as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaParticipante[$columna] != $participante[$columna]){
                                                    $banderaCambios = true;
                                                    $cambios[$columna] = $dato;
                                                    $usuarioModifica = $participante['usuario_modifica'];
                                                    if($columna == "estado_participacion"){
                                                        $cambios['nombre_contacto'] = $participante['nombre_contacto'];
                                                        $cambios['email_usuario'] = $participante['email_usuario'];
                                                        switch ($dato) {
                                                            case 'ofe_consultada': 
                                                                $accion = "Oferta consultada.";
                                                                $usuarioModifica = $participante['nombre_contacto'];
                                                            break;
                                                            case 'ofe_declinada': 
                                                                $accion = "Oferta declinada."; 
                                                                $cambios['observaciones'] = $participante['observaciones'];
                                                                $usuarioModifica = $participante['nombre_contacto'];
                                                            break;
                                                            case 'ofe_enviada': 
                                                                $accion = "Oferta enviada."; 
                                                                $usuarioModifica = $participante['nombre_contacto'];
                                                            break;
                                                            default: $accion = "Modificación participante."; break;
                                                        }
                                                    }
                                                    if($columna == "estado" && $dato == "eliminado"){
                                                        $accion = "Participante eliminado.";
                                                        $cambios['nombre_contacto'] = $participante['nombre_contacto'];
                                                        $cambios['email_usuario'] = $participante['email_usuario'];
                                                    }
                                                }
                                            }
                                            if($banderaCambios){
                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['data'] = $cambios;
                                                $respuesta['usuario'] = ucwords($usuarioModifica);
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($participante['fecha_modificacion']);
                                                array_push($arrayParticipantes, $respuesta);
                                            }
                                        }
                                        $validaParticipante = $participante;
                                    }else{
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación participante.";
                                        $respuesta['usuario'] = ucwords($participante['usuario_creador']);
                                        $respuesta['data'] = $participante;
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($participante['fecharegistro']);
                                        array_push($arrayParticipantes, $respuesta);
                                        $validaParticipante = $participante;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayParticipantes;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora lotes - items oferta.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaEvaluaciones($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayEvaluaciones = array();
            $queryEvaluaciones = "SELECT TG.ruta, TG.observacion, TG.fecha_creacion, TG.fecha_actualizacion, TG.estado, TG.accion, TG.fecha_modificacion, LOWER(UC.nombre) AS nombre_creador, LOWER(UM.nombre) AS usuario_modifica FROM oferta_evaluaciones AS OE INNER JOIN tg_oferta_evaluaciones AS TG ON TG.id = OE.id LEFT JOIN usuarios AS UC ON UC.id = TG.usuario_creacion LEFT JOIN usuarios AS UM ON UM.id = TG.usuario_actualizacion WHERE OE.id_oferta = ? ORDER BY OE.id, TG.fecha_modificacion ASC";
            if($sqlEvaluaciones = $dbConection->prepare($queryEvaluaciones)){
                if($sqlEvaluaciones->bind_param('i', $idOferta)){
                    if($sqlEvaluaciones->execute()){
                        if($resultado = $sqlEvaluaciones->get_result()){
                            if($resultado->num_rows > 0){
                                $validaEvaluacion = array();
                                $arrayLlavesValidar = array("ruta", "observacion", "estado");
                                while ($evaluacion = $resultado->fetch_assoc()) {
                                    if($evaluacion['ruta'] != null && $evaluacion['ruta'] != '' )
                                    {
                                        $evaluacion['ruta'] = $this->intelcost->generaRutaServerFiles($evaluacion['ruta'],'cliente');
                                    }
                                    if(!empty($validaEvaluacion)){
                                        if(($evaluacion['accion'] == "INSERTO")){
                                            $respuesta = array();
                                            $respuesta['accion'] = "Creación evaluación.";
                                            $respuesta['usuario'] = ucwords($evaluacion['nombre_creador']);
                                            $respuesta['data'] = $evaluacion;
                                            $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($evaluacion['fecha_creacion']);
                                            array_push($arrayEvaluaciones, $respuesta);
                                        }else{
                                            $cambios = array();
                                            $banderaCambios = false;
                                            $accion = "Modificación evaluación.";
                                            foreach ($evaluacion as $columna => $dato) {
                                                if(in_array($columna, $arrayLlavesValidar) && $validaEvaluacion[$columna] != $evaluacion[$columna]){
                                                    $banderaCambios = true;
                                                    if($columna == "estado" && $dato == "eliminado"){
                                                        $accion = "Evaluación eliminada.";
                                                    }
                                                }
                                            }
                                            if($banderaCambios){
                                                $respuesta = array();
                                                $respuesta['accion'] = $accion;
                                                $respuesta['usuario'] = ucwords($evaluacion['usuario_modifica']);
                                                $respuesta['data'] = $evaluacion;
                                                $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($evaluacion['fecha_actualizacion']);
                                                array_push($arrayEvaluaciones, $respuesta);
                                            }
                                        }
                                        $validaEvaluacion = $evaluacion;
                                    }else{
                                        $respuesta = array();
                                        $respuesta['accion'] = "Creación evaluación.";
                                        $respuesta['usuario'] = ucwords($evaluacion['nombre_creador']);
                                        $respuesta['data'] = $evaluacion;
                                        $respuesta['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($evaluacion['fecha_creacion']);
                                        array_push($arrayEvaluaciones, $respuesta);
                                        $validaEvaluacion = $evaluacion;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayEvaluaciones;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora evaluaciones.";
        }
        return $this->intelcost->response;  
    }

    private function obtenerHistorialBitacoraOfertaAdjudicacion($idOferta){
        $idOferta = (int) $idOferta;
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrayAdjudicacion = array();
            $queryAdjudicacion = "SELECT OA.id_usuario, OA.moneda, FORMAT(OA.valor, 2) as valor, OA.porcentaje, OA.observacion, OA.carta_adjudicacion, OA.fecha_creacion, OA.usuario_creacion,LOWER(UC.nombre) as usuario_adjudica FROM oferta_adjudicaciones AS OA LEFT JOIN usuarios AS UC ON UC.id = OA.usuario_creacion WHERE OA.id_oferta = ? ORDER BY OA.id";
            if($sqlAdjudicacion = $dbConection->prepare($queryAdjudicacion)){
                if($sqlAdjudicacion->bind_param('i', $idOferta)){
                    if($sqlAdjudicacion->execute()){
                        if($resultado = $sqlAdjudicacion->get_result()){
                            if($resultado->num_rows > 0){
                                while ($adjudicacion = $resultado->fetch_assoc()) {
                                    $proveedor = $this->getDataProveedor($adjudicacion['id_usuario']);
                                    $adjudicacion['contacto'] = ucwords($proveedor['usrnomxx']);
                                    $adjudicacion['proveedor'] = strtoupper($proveedor['razonxxx']);
                                    $adjudicacion['usuario_adjudica'] = ucwords($adjudicacion['usuario_adjudica']);
                                    $adjudicacion['carta_adjudicacion'] = $this->intelcost->generaRutaServerFiles($adjudicacion['carta_adjudicacion'], "cliente");
                                    $adjudicacion['accion'] = "adjudicación";
                                    $adjudicacion['fecha_accion'] = $this->intelcost->castiarFechayHoraIntelcost($adjudicacion['fecha_creacion']);
                                    array_push($arrayAdjudicacion, $adjudicacion);
                                }
                            }
                        }
                    }
                }
            }
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $arrayAdjudicacion;
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error de conexión bitacora adjudicaciones.";
        }
        return $this->intelcost->response;  
    }

    public function obtenerOferta($id, $peticionPdf = false){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlOferta = "SELECT *, t1.estado as estado_oferta FROM ofertas t1  INNER JOIN( SELECT nombre usuario_creador,email usuario_creador_email, id usuario_creador_id FROM usuarios ) t2 ON t1.usuario_creacion = t2.usuario_creador_id  LEFT JOIN( SELECT nombre_comunicaciones clienteNombre, id clienteId, logo clienteLogo FROM clientes ) t3 ON t1.id_cliente = t3.clienteId LEFT JOIN( SELECT id as id_maestra1, requiere_aprobacion, prefijo_criterio, criterio FROM maestras_criterios ) t4 ON t1.maestra1 = t4.id_maestra1  LEFT JOIN( SELECT nombre usuario_aprobador, email usuario_aprobador_email, id usuario_aprobador_id FROM usuarios) t5 ON t1.usuario_aprobacion = t5.usuario_aprobador_id LEFT JOIN ( SELECT id as id_acciones, mostrar_presupuesto, notificacion_seguros FROM ofertas_acciones ) t6 ON t1.id = t6.id_acciones WHERE t1.id= ? AND t1.id_cliente ='".$_SESSION["empresaid"]."' LIMIT 1";
            if($CscOferta = $dbConection->prepare($SqlOferta)){
                $id = (int) $id;
                if($CscOferta->bind_param('i', $id)){
                    if($CscOferta->execute()){
                        if($resultado = $CscOferta->get_result()){
                            if($resultado->num_rows > 0){
                                $row = $resultado->fetch_assoc();

                                $row["tipotexto_print"] = "";
                                if($row["tipo"] == "estudio"){
                                    $row["tipotexto_print"] = "Estudio de mercado";
                                }else if($row["tipo"] == "rfq"){
                                    $row["tipotexto_print"] = "RFQ";
                                }else if($row["tipo"] == "convenio"){
                                    $row["tipotexto_print"] = "Convenio";
                                }else if($row["tipo"] == "abierta"){
                                    $row["tipotexto_print"] = "Oferta / Evento participación Abierta";
                                }else if($row["tipo"] == "cerrada"){
                                    $row["tipotexto_print"] = "Oferta / Evento participación Cerrada";
                                }else if($row["tipo"] == "publico"){
                                    $row["tipotexto_print"] = "Oferta / Evento participación pública";
                                }else{
                                    $row["tipotexto_print"] = "Oferta / Evento";
                                }

                                if($row["modalidad_seleccion"] == "sol_pri_of"){
                                    $row["modalidad_seleccion_txt"] = "Solicitud privada de ofertas";
                                }else if($row["modalidad_seleccion"] == "sol_pub_of"){
                                    $row["modalidad_seleccion_txt"] = "Solicitud publica de ofertas";
                                }else if($row["modalidad_seleccion"] == "sol_uni_of"){
                                    $row["modalidad_seleccion_txt"] = "Solicitud unica de ofertas";
                                }else{
                                    $row["modalidad_seleccion_txt"] = "";
                                }

                                if ($row['fecha_limite_msg'] == 1) {
                                    $row['restriccion_caducada'] = strtotime(date('Y-m-d H:i:s')) > strtotime(date('Y-m-d H:i:s', strtotime($row['fecha_limite_msg_fecha'].' '.$row['fecha_limite_hora'])));
                                }else{
                                    $row['restriccion_caducada'] = false;
                                }
                                //actividad
                                $row["actividadtexto"] = $this->obtenerNombreActividad($row["actividad"]);

                                $row["id_area_nombre"] = "";
                                if($row["id_area"] != "" &&  $row["id_area"] != "0"){
                                    $res_area = $this->modelo_usuario->obtenerAreaUsuaria($row["id_area"]);
                                    if($res_area->bool){
                                        $obj_res = json_decode($res_area->msg);
                                        $row["id_area_nombre"] = $obj_res->nombre;  
                                    }
                                    
                                }

                                if (isset($_SESSION['empresaid']) && $_SESSION['empresaid'] == 25) {
                                    $row["centro_costo"] = [];
                                    if($row["id_area"] != "" &&  $row["id_area"] != "0"){
                                        $centro_costo = MaestraCentrosCosto::find($row["id_area"]);
                                        if($centro_costo){
                                            $centro_costo_gerencia = $centro_costo->load('relacionGerenciaServicio.gerencia');
                                            $centro_costo_gerencia->relacionGerenciaServicio->gerencia->nombre = mb_strtoupper($centro_costo_gerencia->relacionGerenciaServicio->gerencia->nombre, 'UTF-8');
                                            $row["centro_costo"] = $centro_costo_gerencia;  
                                        }
                                    }
                                }

                                if($_SESSION['empresaid'] == 25 && $row['estado'] == 'EN APROBACION'){
                                    $adjudicaciones = OfertaAdjudicaciones::where('id_oferta', $row['id'])
                                                         ->get();
                                    if($adjudicaciones->count() > 0){
                                        $salarioMinimo = $this->intelcost->obtenerSalarioMinimo('COP');
                                        if($salarioMinimo->bool){
                                            $salarioMinimo = $salarioMinimo->msg['valor'];
                                        }else{
                                            $salarioMinimo = 0;
                                        }
                                        $totalValoresAdjudicados = 0;
                                        $adjudicaciones->map(function($adjudicacion) use (&$totalValoresAdjudicados){
                                            $totalValoresAdjudicados = floatval($totalValoresAdjudicados) + floatval($adjudicacion->valor);
                                        });

                                        $cantidadSMMLV = $totalValoresAdjudicados / floatval($salarioMinimo);
                                        if($cantidadSMMLV >= 101){
                                            $cartaAdjudicacionPerfil = true;
                                        }else{
                                            $cartaAdjudicacionPerfil = false;
                                        }
                                    }else{
                                        $cartaAdjudicacionPerfil = false;
                                    }

                                    $row["requiereCartaAdjudicacion"] = $cartaAdjudicacionPerfil;
                                 }

                                if (isset($_SESSION['empresaid']) && ($_SESSION['empresaid'] == 9 || $_SESSION['empresaid'] == 20)) {
                                    $row["ordenGenerada"] = '';
                                    $ordenGenerada = OrdenPedidos::where('cod_oferta', $id)->first();
                                    if($ordenGenerada){
                                        $row["ordenGenerada"] = $ordenGenerada->cod_jde_confa;
                                    }
                                }
                                
                                $row["requisicion_seq"] = "";
                                $row["linea_seq"] = ""; 
                                if($row["id_requisicion"] != "" &&  $row["id_requisicion"] != "0"){
                                    $res_requisicion=$this->listarRequisicionid($row['id_requisicion']);
                                    if($res_requisicion->bool){
                                        $row['requisicion_seq']=$res_requisicion->msg["seq_id"];
                                        $row["linea_seq"] = $res_requisicion->msg["seq_id_linea"];
                                        $row["id_paa_linea"] = $res_requisicion->msg["id_paa_linea"];
                                    }
                                }
                                $row["aoc_seq"] = "";   
                                if($row["id_aoc"] != "" &&  $row["id_aoc"] != "0"){
                                    $res_requisicion=$this->listarAocid($row['id_aoc']);
                                    if($res_requisicion->bool){
                                        $row['aoc_seq']=$res_requisicion->msg;
                                    }
                                }

                                if(!empty($row['soportes_existencia'])){
                                    $objSoportesExistencia = json_decode($row['soportes_existencia']);
                                    $soportesExistencia = array();
                                    foreach ($objSoportesExistencia as $soporte) {
                                        $soporte->sop_url = $this->intelcost->generaRutaServerFiles($soporte->sop_url, "cliente");
                                        array_push($soportesExistencia, $soporte);
                                    }
                                    $row['soportes_existencia'] = json_encode($soportesExistencia);
                                }
                                
                                $row["maestra1Txt"] = "";
                                $row["fecha_limite_msg_restrictivo"] = 0;
                                if($row["regional"] != 0){
                                    $row["regionalNombre"] = $this->obtenerNombreRegional($row["regional"]);
                                }

                                $respuestaMaestrasAsociadas=$this->obtenerMaestrasAsociadasOferta($id);
                                if($respuestaMaestrasAsociadas->bool){
                                    $row["arrMaestrasAsociadas"] = $respuestaMaestrasAsociadas->msg;
                                }else{
                                    $row["arrMaestrasAsociadas"] = "[]";
                                }
                                
                                $array_criterios= explode(',', $row["maestra1"]);
                                foreach($array_criterios as $criterio){
                                    $maestraRes = $this->obtenetenerNombreMaestra($criterio);
                                    $row["maestra1Txt"] .= "&bull; ".$maestraRes["criterio"]."<br />";
                                    $row["maestra1Name"] = ($maestraRes["maestra"]);
                                }
                                $row["maestra2Txt"] = "";
                                $array_criterios2= explode(',', $row["maestra2"]);
                                foreach($array_criterios2 as $criterio2){
                                    $maestraRes2 = $this->obtenetenerNombreMaestra($criterio2);
                                    $row["maestra2Txt"] .= "&bull; ".$maestraRes2["criterio"]."<br />";
                                    $row["maestra2Name"] = ($maestraRes2["maestra"]);
                                }
                                $row["maestra3Txt"] = "";
                                $array_criterios3= explode(',', $row["maestra3"]);
                                foreach($array_criterios3 as $criterio3){
                                    $maestraRes3 = $this->obtenetenerNombreMaestra($criterio3);
                                    $row["maestra3Txt"] .= "&bull; ".$maestraRes3["criterio"]."<br />";
                                    $row["maestra3Name"] = ($maestraRes3["maestra"]);
                                }
                                // Obtener documentos de la oferta
                                $SqlDocumentos  = "SELECT * FROM oferta_documentos WHERE id_oferta='".$id."' AND estado = 'activo' ORDER BY seq_id ASC";
                                $CscDocumentosOferta=$dbConection->query($SqlDocumentos);
                                $arrDocsOfer = [];
                                if($CscDocumentosOferta){
                                    while( $doc = $CscDocumentosOferta->fetch_assoc()){
                                        $coleccion = collect();
                                        $doc["titulo"] = ($doc["titulo"]);
                                        $doc["ruta"] = $this->intelcost->generaRutaServerFiles($doc["ruta"], "cliente");
                                        $doc["contenido"] = (($doc['tipo'] == "archivo") ? $this->intelcost->generaRutaServerFiles($doc["contenido"], "cliente") : $doc["contenido"]);

                                        //Si tiene activo el check de categorización
                                        if (!empty($_SESSION['modulos_personalizados']) && array_search("15", array_column($_SESSION['modulos_personalizados'], 'cod_modulo_personalizado')) !== false){
                                            $categorias_enlazadas = RelacionOfertaDocumentosCategorias::where('id_item_oferta_documento', $doc['id'])->first();
                                            try {
                                                if (count(json_decode($categorias_enlazadas->ids_categorias, true)) > 0) {
                                                    foreach ($categorias_enlazadas->categoriaAsociadas->toArray() as $categoria) {
                                                        $coleccion->push($categoria);
                                                    }
                                                }

                                                $doc["categorias"] = $categorias_enlazadas->toArray();
                                            } catch (Error $e) {
                                                $doc["categorias"] = [];
                                            }
                                        }

                                        array_push($arrDocsOfer, $doc);
                                    }
                                }

                                $row['id_pcc'] = "";
                                if((int) $_SESSION['empresaid'] == 8){
                                    $ofertasCuadroEconomico = new OfertasCuadroCotizacion();
                                    $objIdpcc = $ofertasCuadroEconomico->obtenerIdppcAsociadoOferta($id);
                                    if($objIdpcc->bool){
                                        $row['id_pcc'] = $objIdpcc->msg;
                                    }
                                }

                                $datosAdicionales = OfertaDatosAdicionales::with('precalificacion')
                                                        ->with('solicitud')
                                                        ->where('oferta_id', $id)
                                                        ->where('estado', '!=', 'eliminado')
                                                        ->get()[0];
                                if($datosAdicionales){
                                    $row['precalificacion'] = $datosAdicionales->toArray();
                                    $row['solicitud'] = $datosAdicionales->solicitud ? $datosAdicionales->solicitud->toArray() : [];
                                    $row['fecha_maxima_respuesta'] = $datosAdicionales->fecha_maximo_respuesta;
                                    try {
                                        $row['otros'] = json_decode($datosAdicionales->otros);
                                    } catch (\Error $e) {
                                        $row['otros'] = $datosAdicionales->otros;
                                    } 
                                }else{
                                    $row['precalificacion'] = [];
                                    $row['solicitud'] = [];
                                    $row['fecha_maxima_respuesta'] = null;
                                    $row['otros'] = null;
                                }

                                if (isset($_SESSION['empresaid']) && $_SESSION['empresaid'] == 20) {
                                    $row["logEventos"] = '';
                                    $logEventos = OfertaLogEventos::where('id_oferta', $id)->with('infoUsuarioResponsable')->get();
                                    if($logEventos){
                                        $row["logEventos"] = $logEventos;
                                    }
                                }

                                $row["documentos_oferta"] = $arrDocsOfer;
                                // Obtener documendos del oferente
                                $SqlDocumentosOferente = "SELECT * FROM oferta_documentos_oferentes T1 WHERE T1.oferta_id='".$id."' AND estado = 'activo' ".(($_SESSION['empresaid'] != 14 && $_SESSION['empresaid'] != 26 && $_SESSION['empresaid'] != 27 && $_SESSION['empresaid'] != 20 && $_SESSION['empresaid'] != 25 && $_SESSION['empresaid'] != 9) ? " ORDER BY sobre" : " ORDER BY sobre, fecha_creacion");
                                $CscDocumentosOferente = $dbConection->query($SqlDocumentosOferente);
                                $arrDocsOferentes = [];
                                $cantidadDocumentosOferentes = 0;
                                if($CscDocumentosOferente){
                                    while( $docOfe = $CscDocumentosOferente->fetch_assoc()){
                                        switch ($docOfe["obligatorio"]) {
                                            case 1:
                                            $docOfe["obligatorio"] ="SI";
                                            $cantidadDocumentosOferentes++;
                                            break;
                                            default:
                                            $docOfe["obligatorio"] ="NO";
                                            break;
                                        }
                                        if($docOfe["sobre"] !=""){
                                            $res_sobre = $this->modelo_capitulos->obtenerNombreCapitulo($docOfe["sobre"]);
                                            $docOfe["sobre_nombre"] = $res_sobre->msg;
                                        }else{
                                            $docOfe["sobre_nombre"] = "otro";
                                        }
                                        $docOfe["titulo"] = ($docOfe["titulo"]);
                                        $docOfe["descripcion"] = ($docOfe["descripcion"]);
                                        $docOfe["evaluable"] =  (($docOfe["evaluable"]  == "si") ? true : false);
                                        switch ($docOfe["tipo_evaluacion"]) {
                                            case 'puntuable':
                                                $docOfe["id_tipo_evaluacion"] = 1;
                                                break;
                                            case 'cumple - no cumple':
                                                $docOfe["id_tipo_evaluacion"] = 2;
                                                break;
                                            default:
                                                $docOfe["id_tipo_evaluacion"] = 0;
                                                break;
                                        }

                                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                                            // Criterios legal por documento
                                            $docOfe["criterios"] = OfertaDocumentosCriteriosOferentes::where('id_item_documento', $docOfe['id'])
                                                                        ->where('estado', 'Activo')
                                                                        ->get()
                                                                        ->map(function($criterio){
                                                                            $criterio->otros = json_decode($criterio->otros);
                                                                            return $criterio;
                                                                        });
                                        }
                                        array_push($arrDocsOferentes, $docOfe);
                                    }
                                }
                                $row["documentos_oferente"] = $arrDocsOferentes;
                                
                                // Obtener visitas de obra
                                /*$SqlvisitasObra  = "SELECT * FROM oferta_visitasobra WHERE oferta_id='".$id."' AND estado = 'activo' ORDER BY id DESC";
                                $cssVisitaObra=$dbConection->query($SqlvisitasObra);
                                $arrVisitasObra = [];
                                while( $visita = $cssVisitaObra->fetch_assoc()){
                                    $visita["observaciones"]= ($visita["observaciones"]);
                                    $visita["lugar"]= ($visita["lugar"]);
                                    $visita["responsable"]= ($visita["responsable"]);
                                    $visita["telefono"]= ($visita["telefono"]);
                                    $visita["acta"]= $this->intelcost->generaRutaServerFiles($visita["acta"],'cliente');
                                    
                                    array_push($arrVisitasObra, $visita);
                                }
                                $row["visitas_obra"] = $arrVisitasObra;*/

                                $visitasDeObra = VisitaObra::where('oferta_id', $id)
                                                    ->activo()
                                                    ->get()
                                                    ->map(function($visita){
                                                        return [
                                                            'observaciones' => $visita->observaciones,
                                                            'lugar' => $visita->lugar,
                                                            'responsable' => $visita->responsable,
                                                            'telefono' => $visita->telefono,
                                                            'acta' => $this->intelcost->generaRutaServerFiles($visita->acta, 'cliente'),
                                                            'fecha' => $visita->fecha,
                                                            'hora' => $visita->hora,
                                                            'tipo' => $visita->tipo,
                                                            'obligatorio' => $visita->obligatorio,
                                                            'id' => $visita->id,
                                                        ];
                                                    });
 
                                $row["visitas_obra"] = $visitasDeObra;

                                // Obtener usuarios internos de la oferta
                                $SqlUsuariosInternos  = "SELECT * FROM oferta_usuarios_internos T1 ";
                                $SqlUsuariosInternos .= "INNER JOIN (SELECT id ,email, nombre,cargo FROM usuarios) T2 ON T1.id_usuario = T2.id ";
                                $SqlUsuariosInternos .= "WHERE T1.id_oferta='".$id."' AND estado = 'activo'";
                                $cssUsuariosInternos=$dbConection->query($SqlUsuariosInternos);
                                $arrUsuariosInternos = [];
                                if($cssUsuariosInternos){
                                    while( $usuarioInterno = $cssUsuariosInternos->fetch_assoc()){
                                        $usuarioInterno['nombre']=($usuarioInterno['nombre']);
                                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                                            $usuarioInterno['accesos_criterios_tecnicos'] = OfertaUsuariosPermisosCriteriosTecnicos::where('id_usuario', $usuarioInterno['id_usuario'])->where('id_oferta', $id)->first();
                                        }
                                        array_push($arrUsuariosInternos, $usuarioInterno);
                                    }
                                }

                                if (!empty($_SESSION['modulos_personalizados']) && array_search("15", array_column($_SESSION['modulos_personalizados'], 'cod_modulo_personalizado')) !== false) {
                                    foreach ($arrUsuariosInternos as &$usuarioInterno) {
                                        $categorias_seleccionadas = $this->obtenerPermisoCategoriaUsuario($usuarioInterno['id_usuario'], $usuarioInterno['id_oferta']);
                                        if ($categorias_seleccionadas->bool) {
                                            $usuarioInterno['categorias_seleccionadas'] = $categorias_seleccionadas->msg;
                                        }else{
                                            $usuarioInterno['categorias_seleccionadas'] = [];
                                        }
                                    }
                                }

                                $row["usuarios_internos"] = $arrUsuariosInternos;

                                //usuarios aprobadores
                                $row["usuarios_aprobadores"] = array();
                                if((int) $_SESSION['empresaid'] == 14 || (int) $_SESSION['empresaid'] == 26 || $_SESSION["empresaid"] == 25 || (int) $_SESSION['empresaid'] == 27 || (int) $_SESSION['empresaid'] == 20 || (int) $_SESSION['empresaid'] == 9){
                                    $queryAprobadores = "SELECT OUA.id, OUA.id_oferta, OUA.id_usuario_aprobador, OUA.accesos, US.nombre, US.email FROM oferta_usuarios_aprobadores AS OUA INNER JOIN usuarios AS US ON US.id = OUA.id_usuario_aprobador WHERE OUA.id_oferta = $id AND OUA.estado = 'activo'";
                                    $sqlUsuariosAprobadores = $dbConection->query($queryAprobadores);
                                    if($sqlUsuariosAprobadores){
                                        if($sqlUsuariosAprobadores->num_rows > 0){
                                            $array_docOferentes = array();
                                            foreach ($row["documentos_oferente"] as $doc) {
                                                array_push($array_docOferentes, $doc['id']);
                                            }
                                            while( $aprobador = $sqlUsuariosAprobadores->fetch_assoc()){
                                                $accesos_aprobador = (array) json_decode($aprobador["accesos"]);
                                                $accesos_validados = array();
                                                foreach ($accesos_aprobador as $doc) {
                                                    if(in_array($doc, $array_docOferentes)){
                                                        array_push($accesos_validados, $doc);
                                                    }
                                                }
                                                $aprobador['accesos'] = json_encode($accesos_validados);
                                                array_push($row["usuarios_aprobadores"], $aprobador);
                                            }
                                        }
                                    }
                                }
                                $sobres_relacionados = $this->modelo_capitulos->obtenerSobresOferta($id);
                                if($sobres_relacionados->bool){
                                    $row["sobres_relacionados"] = $sobres_relacionados->msg;
                                }else{
                                    $row["sobres_relacionados"] = false;
                                }
                                
                                if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                                    if($row['usuario_creacion'] == $_SESSION['idusuario'] || $_SESSION['Tipousuario'] == 2){
                                        $permisosCustom = ['permisos' => collect()];
                                        $permisosCustom['permisos']->push(['nombre' => 'all', 'id' => 'all']);
                                        $row["permisos_criterios"] = $permisosCustom;
                                    }else{
                                        $permisos = OfertaUsuariosPermisosCriteriosTecnicos::where('id_oferta', $id)->where('id_usuario', $_SESSION['idusuario'])->first();
                                        $permisos->permisos = $permisos->permisosJson;
                                        $row["permisos_criterios"] = $permisos;
                                    }
                                    $criterios_evaluacion = OfertaCriteriosEvaluacion::where('id_oferta', $id)
                                                                ->activo()
                                                                ->with(['obtenerDatosAdicionales' => function($query){
                                                                    $query->activo();
                                                                }])
                                                                ->get();
                                    if($criterios_evaluacion->count() > 0){
                                        $row["criterios_evaluacion"] = $criterios_evaluacion;
                                    }else{
                                        $row["criterios_evaluacion"] = false;
                                    }
                                }else{
                                    $criterios_evaluacion = $this->obtenerCriteriosOferta($id);
                                    if($criterios_evaluacion->bool){
                                        $row["criterios_evaluacion"] = $criterios_evaluacion->msg;
                                    }else{
                                        $row["criterios_evaluacion"] = false;
                                    }
                                }
                                
                                // Obtener participantes de la oferta
                                //$SqlParticipantes = "SELECT OFP.id, OFP.id_usuario, OFP.estado_participacion, OFP.email_usuario, OFP.carta_invitacion, OFP.fecha_actualizacion, OFP.fecharegistro, OFP.observaciones, count(PAR.id_participacion) as participaciones FROM oferta_participantes AS OFP LEFT JOIN ( SELECT OFOC.id as id_participacion, OFOC.id_usuario, OFOC.id_oferta FROM oferta_documentos_ofertascliente AS OFOC INNER JOIN oferta_documentos_oferentes AS ODO ON OFOC.id_documento_oferente = ODO.id AND OFOC.id_oferta = ODO.oferta_id where ODO.obligatorio = 1 AND ODO.estado = 'activo') PAR ON PAR.id_usuario = OFP.id_usuario AND PAR.id_oferta = OFP.id_oferta WHERE OFP.id_oferta = $id AND OFP.estado = 'activo' group by OFP.id_usuario";
                                $SqlParticipantes = "SELECT OFP.id, OFP.id_usuario, OFP.estado_participacion, OFP.email_usuario, OFP.carta_invitacion, OFP.fecha_actualizacion, OFP.fecharegistro, OFP.observaciones FROM oferta_participantes AS OFP WHERE OFP.id_oferta = $id AND OFP.estado = 'activo' GROUP BY OFP.id_usuario ORDER BY OFP.estado_participacion DESC limit 800";
                                $cssParticipantes=$dbConection->query($SqlParticipantes);

                                $arrIdParticipantes = []; //Solo ID
                                $arrParticipantes = []; //Cargue de toda la info

                                $arrResul = [];
                                if($cssParticipantes){
                                    while($participante = $cssParticipantes->fetch_assoc()){
                                        
                                        $arrLotesPart=[];
                                        if(!($peticionPdf && (int) $_SESSION['empresaid'] == 10 && $row['tipo'] == "rfq")){
                                        //if(!$peticionPdf && $_SESSION['empresaid'] != 10 ){
                                            $SqlLotPar="SELECT OL.id_lote,OL.cod_oferta,OL.cod_sobre,OL.nombre_lote,OL.usuario_creacion, ";
                                            $SqlLotPar.="OL.fecha_creacion,CA.nombre FROM oferta_lotes OL INNER JOIN capitulos CA ON  ";
                                            $SqlLotPar.="OL.cod_sobre = CA.id WHERE OL.cod_oferta='".$id."' AND OL.estado=1;";

                                            $CscLotPar=$dbConection->query($SqlLotPar);
                                            if($CscLotPar){
                                                if($CscLotPar->num_rows > 0){
                                                    while ($rowLotPar=$CscLotPar->fetch_assoc()) {
                                                        $resItems=$this->obtenerItemsLoteOfertaParticipante($rowLotPar['id_lote'],$participante['id_usuario']);
                                                        if($resItems->bool){
                                                            $rowLotPar['itemsLote']=$resItems->msg;
                                                        }else{
                                                            $rowrowLotParLote['itemsLote']=false;
                                                        }
                                                        $resPosicion=$this->obtenerPosicionLoteOferta($rowLotPar['id_lote'],$participante['id_usuario']);
                                                        if($resPosicion->msg){
                                                            $rowLotPar['posicionLote']=$resPosicion->msg;
                                                        }else{
                                                            $rowLotPar['posicionLote']="N/A";
                                                        }
                                                        array_push($arrLotesPart,json_encode($rowLotPar));
                                                    }
                                                }
                                            }
                                        }

                                        $participante['participaciones'] = 0;
                                        if(!$peticionPdf){
                                            $queryParticipaciones = "SELECT COUNT(ODO.id) AS part FROM oferta_documentos_ofertascliente AS ODO INNER JOIN oferta_documentos_oferentes AS OFOC ON ODO.id_documento_oferente = OFOC.id WHERE OFOC.oferta_id = $id AND ODO.id_usuario = $participante[id_usuario] AND OFOC.obligatorio = 1 AND OFOC.estado = 'activo'";
                                            $cssParticipaciones = $dbConection->query($queryParticipaciones);
                                            if($cssParticipaciones){
                                                $respParticipantes = $cssParticipaciones->fetch_assoc();
                                                $participante['participaciones'] = $respParticipantes['part'];
                                            }
                                        }
                                        $participante['porcentaje_participacion'] = ( $participante['participaciones'] > 0 ? round(($participante['participaciones'] * 100) / $cantidadDocumentosOferentes, 2) : 0);
                                        $participante['arrLotesParticipante']=$arrLotesPart;
                                        array_push($arrIdParticipantes, $participante['id_usuario']);
                                        array_push($arrParticipantes, $participante);
                                    }
                                }
                                if(count($arrIdParticipantes) != 0){
                                    $id_participantes = implode(',',$arrIdParticipantes);
                                    $arrResul = $this->obtenerDatosUsuariosIntelcost($id_participantes,$arrParticipantes);
                                }
                                $participantesOrdenados = $this->intelcost->array_sort($arrResul, 'empresa', SORT_ASC);
                                $row["participantes"] = $participantesOrdenados;

                                if(!$peticionPdf){
                                    $mensajes = "SELECT (SELECT COUNT(id_mensaje) FROM mensajes WHERE id_oferta = $row[id] AND id_cliente = $_SESSION[empresaid] AND id_creador <> $_SESSION[idusuario] AND tipo_mensaje != 'interno') as cant_buzon, (SELECT COUNT(id_mensaje) FROM mensajes WHERE id_oferta = $row[id] AND id_cliente = $_SESSION[empresaid] AND tipo_mensaje != 'interno') as cant_historial, (SELECT COUNT(id_mensaje) FROM mensajes WHERE id_oferta = $row[id] AND id_cliente = $_SESSION[empresaid] AND tipo_mensaje = 'interno') as mensajes_internos,(SELECT COUNT(id_mensaje) FROM mensajes WHERE id_oferta = $row[id] AND id_cliente = $_SESSION[empresaid] AND tipo_mensaje != 'interno' AND bandera = 0) as mensajes_ext_sin_leer,(SELECT COUNT(id_mensaje) FROM mensajes WHERE id_oferta = $row[id] AND id_cliente = $_SESSION[empresaid] AND tipo_mensaje = 'interno' AND bandera = 0) as mensajes_int_sin_leer;";

                                    $Cscmensajes=$dbConection->query($mensajes);
                                    
                                    if($Cscmensajes){
                                        $rowMensajes = $Cscmensajes->fetch_assoc();
                                        $row['mensajes_buzon'] = $rowMensajes['cant_buzon'];
                                        $row['mensajes_historial'] = $rowMensajes['cant_historial'];
                                        $row['mensajes_internos'] = $rowMensajes['mensajes_internos'];
                                        $row['mensajes_ext_sin_leer'] = $rowMensajes['mensajes_ext_sin_leer'];
                                        $row['mensajes_int_sin_leer'] = $rowMensajes['mensajes_int_sin_leer'];
                                    }
                                }
                                
                                $arrSobres=[];
                                $SqlSob="SELECT id, LOWER(nombre) nombre FROM `capitulos` WHERE cliente_id ='".$_SESSION["empresaid"]."' AND estado='activo';";
                                $CscSob=$dbConection->query($SqlSob);
                                if($CscSob){
                                    while($rowSobres = $CscSob->fetch_assoc()){
                                        array_push($arrSobres,json_encode($rowSobres));
                                    }
                                }
                                //carga select de sobres al agregar items.
                                $row['sobresEmpresa']=json_encode($arrSobres);

                                // Carga de los Sobres de la oferta

                                $arrSobres=[];
                                /*$SqlSobresOferta="SELECT * FROM ofertas_sobres WHERE id_oferta='".$id."' AND estado=1 ";
                                $CscSobres=$dbConection->query($SqlSobresOferta);
                                if($CscSobres){
                                    while ($rowSobre=$CscSobres->fetch_assoc()) {
                                        array_push($arrSobres,json_encode($rowSobre));
                                    }
                                }   */                          
                                $row['sobres_oferta']=json_encode($arrSobres);

                                // Carga de los lotes de la oferta
                                $arrLotes=[];

                                $SqlLotes="SELECT OL.id_lote,OL.cod_sobre,LOWER(OL.nombre_lote) nombre_lote,LOWER(CA.nombre) titulo_sobre, cod_compania, CA.obligatorio FROM oferta_lotes OL LEFT JOIN capitulos CA ON OL.cod_sobre = CA.id WHERE OL.cod_oferta='".$id."' AND OL.estado=1 ";
                                $CscLotes=$dbConection->query($SqlLotes);
                                if($CscLotes){
                                    
                                    while ($rowLote=$CscLotes->fetch_assoc()) {
                                        $rowLote['nombre_lote']=ucwords(($rowLote['nombre_lote']));
                                        $rowLote['titulo_sobre']=ucwords(($rowLote['titulo_sobre']));
                                        $rowLote['columnasAdicionales']=$this->obtenerColumnasAdicionales($rowLote["id_lote"]);
                                        $resItems=$this->obtenerItemsLoteOferta($rowLote['id_lote']);
                                        $rowLote['id_lote']=sha1($rowLote['id_lote']);
                                        if($resItems->bool){
                                            $rowLote['itemsLote']=$resItems->msg;
                                        }else{
                                            $rowLote['itemsLote']=false;
                                        }
                                        array_push($arrLotes,json_encode($rowLote));
                                    }
                                }
                                                               
                                
                                $row['arrLotesOferta']=json_encode($arrLotes);
                                
                                // Carga de evaluaciones de la oferta
                                if((($_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25) && $row["estado"] == "EN APROBACION") || $row["estado"] == "EN EVALUACION" || $row["estado"] == "EN ADJUDICACION" || $row["estado"] == "FINALIZADA" || $row["estado"] == "DESIERTA"){
                                    $modelo_acciones_oferta = new modelo_acciones_oferta();
                                    $row["evaluacion"] = $modelo_acciones_oferta->listarEvaluaciones($id);
                                }

                                if($row["estado"] == "EN EVALUACION" || $row["estado"] == "EN ADJUDICACION"){
                                    $respuesta = $this->validarEvaluacionesOferta($id);
                                    if($respuesta->bool){
                                        $row["estado_evaluacion_aprobacion"] = $respuesta->msg;
                                    }else{
                                        $row["estado_evaluacion_aprobacion"] = false;   
                                    }
                                }

                                // Carga de adjudicaciones de la oferta
                                if($row["estado"] == "EN ADJUDICACION" || $row["estado"] == "FINALIZADA" || ($row["estado"] == "EN APROBACION" && $_SESSION['empresaid'] == 25)){
                                    $row["adjudicacion"] = $this->obtenerAdjudicacion($id);
                                }

                                // Funciona en el js ofertas_detalle línea 454
                                $row['aperturaCapitulos'] = true;
                                if (!$peticionPdf && ($_SESSION['empresaid'] == 8 || $_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 6 || $_SESSION['empresaid'] == 25 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9) ){
                                    // Historial de seguimient - Ofertas
                                    $arrHistorial=[];
                                    $SqlHistorial = "SELECT * FROM historial_aprobaciones t1
                                    INNER JOIN (
                                        SELECT  nombre usuario_historial_oferta, id usuario_historial_id_oferta FROM usuarios
                                    ) t2 ON t1.usuario_id = t2.usuario_historial_id_oferta      
                                    WHERE t1.oferta_id = $id
                                    ORDER BY t1.fecha_creacion DESC";
                                    $CscHistorial = $dbConection->query($SqlHistorial);
                                    if($CscHistorial){
                                        while ($rowHisto = $CscHistorial->fetch_assoc()) {
                                            $rowHisto['fecha_creacion'] = $this->intelcost->castiarFechayHoraIntelcost($rowHisto['fecha_creacion']);
                                            // Destruir variables innecesarias
                                            unset($rowHisto['usuario_historial_id_oferta']);
                                            unset($rowHisto['usuario_id']);
                                            array_push($arrHistorial,json_encode($rowHisto));
                                        }
                                    }
                                    $row['arrHistorialOfertas']=json_encode($arrHistorial);
                                    $row['arrRondas'] = "";
                                    if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION["empresaid"] == 25 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9){
                                        $queryRondas = "SELECT id as id_oferta_ronda, seq_id as cod_terpel, ronda, CONCAT(seq_id, '-', ronda) as codigo_terpel FROM `ofertas` where seq_id = '$row[seq_id]' and id_cliente = $_SESSION[empresaid] and estado != 'INACTIVO'";
                                        $sqlRondas = $dbConection->query($queryRondas);
                                        if($sqlRondas){
                                            $arrayRondas = array();
                                            if($sqlRondas->num_rows > 0){
                                                while ($ronda = $sqlRondas->fetch_assoc()) {
                                                    array_push($arrayRondas, $ronda);
                                                }
                                            }
                                            $row['arrRondas'] = json_encode($arrayRondas);
                                        }
                                        if($row['apertura_capitulos'] == 0){
                                            $row['aperturaCapitulos'] = false;
                                        }
                                    }
                                }

                                //para ODL consultamos las maestras del modulo
                                if($_SESSION['empresaid'] == 8){
                                    $maestrasMod = $this->obtenerMaestrasItemsModulos($id);
                                    $row['maestrasMod'] = $maestrasMod;
                                }

                                //monedas adicionales
                                $row['monedas'] = $this->obtenerMonedasAdicionales($row['id']);
                                //fin de monedas adicionales
                                unset($row['apertura_capitulos']);
                                $RUsr = json_encode($row);
                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg =$RUsr;
                            }else{
                                $this->intelcost->response->bool = false;
                                $this->intelcost->response->msg ="No se encontró la oferta.";
                            }
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg ="Error al obtener la información de la oferta.";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg ="Error al ejecutar la consulta.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Error al preparar los datos para la consulta.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error al preparar la consulta.";
            }               
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    private function obtenerMaestrasItemsModulos($idOferta){
        $arrResultado = [];
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlQuery="SELECT mst_items_md.titulo_item, mst_tlt_item.titulo
            FROM oferta_maestras_generales mst_g
            inner join mst_general_titulos_items_modulos mst_items_md
            on mst_g.cod_maestra = mst_items_md.cod_item
            inner join mst_general_titulos_modulos mst_tlt_item
            on mst_items_md.cod_titulo = mst_tlt_item.cod_maestra
            where mst_g.cod_oferta = ".$idOferta." and mst_g.estado = 'activo'";
            $CscQuery=$dbConection->query($SqlQuery);
            if($CscQuery){
                while ($row = $CscQuery->fetch_assoc()) {
                    array_push($arrResultado,json_encode($row));
                }
            }
        }
        return $arrResultado;
    }

    private function obtenerColumnasAdicionales($lote){
		$resultado = OfertaLoteColumnaAdicional::where('cod_lote',$lote)
        ->where('estado','activo')
        ->with('detalleSobre')
        ->get();
		return $resultado;
	}

    private function obtenerMonedasAdicionales($id_oferta){
        $resultado = OfertaMoneda::where('id_oferta',$id_oferta)
        ->where('estado','activo')
        ->get();
        return $resultado;
    }

    private function obtenerColumnasAdicionalesResultado($lote,$item,$usuario){
        $resultado = OfertaLoteItemProveedorAdicional::where('usuario_creacion',$usuario)
        ->where('cod_item',$item)
        ->where('estado','activo')
        ->with('obtenerColumnaAdicional')
        ->get();
        return $resultado;
    }

    public function obtenerPermisoCategoriaUsuario($id_usuario, $id_oferta){
        $retorno_respuesta = new stdClass();
        $consulta_permisos_categoria_usuario = OfertaUsuariosPermisosFAQ::with('categoria')->where('id_oferta', $id_oferta)->where('id_usuario', $id_usuario)->activo()->get();
        if($consulta_permisos_categoria_usuario){
            $categorias = collect();
            $consulta_permisos_categoria_usuario->map(function($categoria) use ($categorias){
                $categorias->push(['id' => $categoria->categoria->id, 'nombre' => $categoria->categoria->nombre]);
            });

            $retorno_respuesta->bool = true;
            $retorno_respuesta->msg = $categorias;
        }else{
            $retorno_respuesta->bool = false;
            $retorno_respuesta->msg = [];
        }  
        return $retorno_respuesta;
    }

    protected function validarEvaluacionesOferta($oferta, $parametros = null){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){

            if(!isset($parametros['accion']) || $parametros['accion'] != "finalizar_evaluacion"){
                
                $queryValidaPart = "SELECT Count(V.id) AS total, O.apertura_capitulos FROM informacion_proveedores_invitados_ofertas AS V INNER JOIN ofertas AS O ON O.id = V.id_oferta WHERE V.id_oferta = ? AND V.estado_participacion = 'ofe_enviada'";
                if($CscValidaPart = $dbConection->prepare($queryValidaPart)){
                    if($CscValidaPart->bind_param('s', $oferta)){
                        if($CscValidaPart->execute()){
                            if($resultadoVal = $CscValidaPart->get_result()){

                                $validacion = $resultadoVal->fetch_assoc();
                                if($validacion['total'] <= 1 || $validacion['apertura_capitulos'] == 0){
                                    $this->intelcost->response->bool = true;
                                    $this->intelcost->response->msg ="Evaluada_Aprobada";
                                    return $this->intelcost->response;
                                }
                            }
                        }
                    }
                }
            }

            $SqlQuery = 'SELECT O.id AS codigo_oferta, ODP.id AS codigo_documento_proveedor, ODP.id_usuario AS codigo_usuario_proveedor, group_concat( DISTINCT OED.id ) AS codigos_evaluacion, group_concat( DISTINCT OED.resultado_evaluacion ) AS resultados_evaluacion, group_concat( DISTINCT OEDH.id_historial ) AS codigos_historial_aprobacion, group_concat( DISTINCT OEDH.valoracion ) AS valoraciones_aprobacion, OD.obligatorio FROM oferta_documentos_oferentes AS OD INNER JOIN oferta_documentos_ofertascliente AS ODP ON OD.id = ODP.id_documento_oferente INNER JOIN ofertas AS O ON O.id = OD.oferta_id INNER JOIN oferta_participantes AS OP ON O.id = OP.id_oferta AND ODP.id_usuario = OP.id_usuario LEFT JOIN oferta_evaluacion_documento AS OED ON OED.documento_id = ODP.id AND OED.estado = "activo" LEFT JOIN oferta_evaluacion_documento_historial AS OEDH ON OED.id = OEDH.id_evaluacion WHERE O.id = ? AND OP.estado = "activo" AND OP.estado_participacion = "ofe_enviada" AND OD.evaluable = "si" GROUP BY ODP.id ORDER BY codigo_usuario_proveedor ASC';

            if($CscQuery = $dbConection->prepare($SqlQuery)){
                if($CscQuery->bind_param('s', $oferta)){
                    if($CscQuery->execute()){
                        if($resultado = $CscQuery->get_result()){

                            $contadorNoEvaluadas = 0;
                            $contadorNoAprobadas = 0;
                            while ($row = $resultado->fetch_assoc()) {
                                if($row['obligatorio'] == 0){
                                    continue;
                                }

                                if(empty($row["codigos_evaluacion"])){
                                    $contadorNoEvaluadas++;
                                }
                                if(empty($row["valoraciones_aprobacion"])){
                                    $contadorNoAprobadas++;
                                }else{
                                    if (strpos($row["valoraciones_aprobacion"],"aprobado") === false) {
                                        $contadorNoAprobadas++;                         
                                    }
                                }
                            }

                            if($contadorNoEvaluadas == 0 && $contadorNoAprobadas == 0){
                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg ="Evaluada_Aprobada";
                            }else if($contadorNoEvaluadas == 0 && $contadorNoAprobadas != 0){
                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg ="Solamente_Evaluada_Pendiente_Aprobacion";
                            }else if($contadorNoEvaluadas != 0 && $contadorNoAprobadas != 0){
                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg ="Pendiente_Evaluar_Aprobar";
                            }else{
                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg ="Pendiente_Evaluar_Aprobar";
                            }
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg ="Error al obtener resultados";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg ="Error al consultar";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Error al preparar consulta";   
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error Conexion 2";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function guardarHistorialMensajes($datos){
        # Valida que los campos especificos 
        if ($this->intelcost->validador($datos, "accion") && $this->intelcost->validador($datos, "id") && $this->intelcost->validador($datos, "id_usuario")) {
            # Convitiendo variable sin etiqueta script
            $datos["obs"] = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $datos["obs"]);
            # Creando la Query
            $SqlGuardarHistorial = "INSERT INTO historial_aprobaciones ";
            $SqlGuardarHistorial .= "(`oferta_id`, `usuario_id`, `observacion`, `tipo_historial`, `fecha_creacion`) ";
            $SqlGuardarHistorial .= "VALUES ( ?, ?, ?, ?, '".date("Y-m-d H:i:s")."')";
            $parametros = array((int) $datos["id"], (int) $_SESSION['idusuario'], (string) $datos["obs"], (string) $datos["accion"]);
            # Ejecutando la query
            $insertHistorial = $this->intelcost->prepareStatementQuery('cliente', $SqlGuardarHistorial, 'insert', true, "iiss", $parametros, "Guardar historial de mensajes");
            # Validar si la query se realizó correctamente
            if($insertHistorial->bool){
                $CsHistorial = 'SELECT * from historial_aprobaciones WHERE `oferta_id` = ?';
                $sqlHistorial = $this->intelcost->prepareStatementQuery('cliente', $CsHistorial, 'SELECT', true, "i", array((int) $datos["id"]), "Retornar historial de mensajes");
                $this->intelcost->response = $sqlHistorial;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al guardar el historial de mensajes.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Datos incompletos";
        }
        return $this->intelcost->response;
    }

    public function obtenerItemsLoteOferta($lote){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $lote = $this->intelcost->realEscapeStringData($lote);
            $arrItems=[];
            $SqlItem="SELECT OLI.id_item,OLI.cod_lote,OLI.descripcion,OLI.cantidad,OLI.cod_unidad_medida,MUM.um,MUM.medida,OLI.fecha_creacion, MUM.descripcion desUnidadMedida,MUM.decimales, OLIDA.numero_linea, OLIDA.numero_articulo, OLIDA.tipo_documento, OLIDA.obligatorio itemObligatorio, OLIDA.impuesto, OLIDA.referencia FROM oferta_lotes_items OLI LEFT JOIN oferta_lotes_items_datos_adicionales OLIDA ON OLI.id_item = OLIDA.cod_item INNER JOIN mst_unidad_medidas MUM ON OLI.cod_unidad_medida = MUM.id_medida WHERE OLI.cod_lote='".$lote."' AND OLI.estado=1 ORDER BY OLIDA.numero_linea; ";
            $CscItem=$dbConection->query($SqlItem);
            if($CscItem){
                while($row=$CscItem->fetch_assoc()){
                    $row['datosAdicionales'] = $this->obtenerInformacionAdicional($row['id_item']);
                    $row['id_item']=sha1($row['id_item']);
                    array_push($arrItems,json_encode($row));
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =json_encode($arrItems);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea obtener items de un lote.";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    private function obtenerInformacionAdicional($item){
        $resultado = OfertaLoteItemInformacionAdicionalOdl::where('cod_item',$item)
        ->where('estado','activo')
        ->first();
        return $resultado;
    }

    public function enviarAprobacionOferta($data, $obs){
        $obs = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $obs);

        $SqlOfer = 'UPDATE ofertas SET `estado` = "EN APROBACION", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ?';
        $SqlOfer = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int) $data->id), "Enviar aprobación de oferta.");

        if($SqlOfer->bool){
            if($SqlOfer->msg > 0){
                if((int) $data->id_cliente != 14 && (int) $data->id_cliente != 26 && (int) $data->id_cliente != 27 && (int) $data->id_cliente != 20 && (int) $data->id_cliente != 6){
                    $contenidoMail  = "<p><b>Estimado(a):</b> Aprobador</p><br> ";
                    $contenidoMail .= "Le informamos que ".$_SESSION["usuario"].", ha enviado un evento para <b>su aprobación</b> previa a la publicación.<br><br>";
                    if($obs!=""){
                        $contenidoMail .= "Las observaciones son las siguientes: <br>". ($obs)."<br>";
                    }
                    $contenidoMail .= "<p style='font-size:12px'>Link de acceso: <a href='https://".$_SESSION["url"]."'>".$_SESSION["url"]."</a></p><br />";
                    $subject = 'Proceso para aprobación - '.$data->objeto;
                    $this->enviarEmailSistemaAprobaciones($data->usuario_creador_email, $contenidoMail, $data, $subject);

                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "Oferta actualizada correctamente";
                }else{
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "Se ha enviado una nueva solicitud de aprobación para el evento ".$data->seq_id.". ";
                    $notifica = $this->notificacionSolicitudAprobacion($data, $obs);
                    if(!$notifica){
                        $this->intelcost->response->msg .= " No se ha logrado notificar al aprobador.";
                    }
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Actualizacion de oferta erronea";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Actualizacion de oferta erronea";
        }
        return $this->intelcost->response;
    }

    public function obtenerItemsLoteOfertaParticipante($lote,$idUsuario){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $lote = $this->intelcost->realEscapeStringData($lote);
            $idUsuario = $this->intelcost->realEscapeStringData($idUsuario);
            $arrItems=[];
            $SqlItem="SELECT OLI.id_item,OLI.cod_lote,OLI.descripcion,OLI.cantidad,OLI.cod_unidad_medida,MUM.um,MUM.medida,OLI.fecha_creacion, ";
            $SqlItem.="MUM.descripcion desUnidadMedida,MUM.decimales FROM oferta_lotes_items OLI INNER JOIN mst_unidad_medidas MUM ON  ";
            $SqlItem.="OLI.cod_unidad_medida = MUM.id_medida WHERE OLI.cod_lote='".$lote."' AND OLI.estado=1; ";
            $CscItem=$dbConection->query($SqlItem);
            if($CscItem){
                if($CscItem->num_rows > 0){
                    while($row=$CscItem->fetch_assoc()){
                        $row['columnasAdicionales'] = $this->obtenerColumnasAdicionalesResultado($lote,$row['id_item'],$idUsuario);
                        $SqlItemPar="SELECT * FROM `oferta_lotes_items_proveedores` WHERE cod_item='".$row['id_item']."' AND usuario_creacion='".$idUsuario."';";
                        $CscItemPar=$dbConection->query($SqlItemPar);
                        if($CscItemPar){
                            if($CscItemPar->num_rows!=0){
                                $rowItemPar=$CscItemPar->fetch_assoc();
                                $row['valorParticipante']=$rowItemPar['valor'];
                            }else{
                                $row['valorParticipante']="N/A";
                            }
                        }else{
                            $row['valorParticipante']=false;
                        }

                        $row['id_item']=sha1($row['id_item']);
                        array_push($arrItems,json_encode($row));
                    }
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =json_encode($arrItems);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea obtener items de un lote.";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function obtenerPosicionLoteOferta($lote,$idUsuario){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $lote = $this->intelcost->realEscapeStringData($lote);
            $idUsuario = $this->intelcost->realEscapeStringData($idUsuario);
            $SqlPos="SELECT (@rownum :=@rownum + 1) posicion,A1.usuario_creacion,A1.total FROM (SELECT @rownum := 0) r, ";
            $SqlPos.="(SELECT OFIP.usuario_creacion,SUM((CLI.cantidad * OFIP.valor)) total ";
            $SqlPos.="FROM oferta_lotes_items CLI INNER JOIN oferta_lotes_items_proveedores OFIP ON OFIP.cod_item = CLI.id_item ";
            $SqlPos.="WHERE CLI.cod_lote = '".$lote."' GROUP BY OFIP.usuario_creacion ORDER BY  total ASC) A1 ;";
            
            $CscPos=$dbConection->query($SqlPos);
            if($CscPos){
                $posicion_res="N/A";
                while ($rowPos=$CscPos->fetch_assoc()) {
                    if($rowPos['usuario_creacion']==$idUsuario){
                        $posicion_res=$rowPos["posicion"];
                    }
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =$posicion_res;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error al consultar";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function obtenerEvaluacionDocumentosOfertaEvaluador($id_oferta, $id_evaluador){
        $queryHistorial = "SELECT OFE.id, OFE.documento_id, PAR.id_usuario as proveedor, OFE.observaciones obs_evaluacion, OFE.cumpleNoCumple as valoracion, OFE.resultado_evaluacion, OFE.fecha_creacion as fecha_evaluacion, OFEH2.valoracion as valoracion_aprobacion, OFEH2.observaciones obs_aprobacion, OFEH2.fecha_aprobacion as fecha_aprobacion, DOCOF.tipo, DOCOF.titulo, DOCOF.descripcion, DOCOF.evaluable, DOCOF.fecha_creacion, DOCOF.tipo_evaluacion, DOCOF.parametro_evaluacion, capitulos.nombre, UE.nombre evaluador, UA.nombre aprobador FROM oferta_evaluacion_documento AS OFE LEFT JOIN oferta_evaluacion_documento_historial AS OFEH2 ON OFEH2.id_evaluacion = OFE.id AND OFEH2.valoracion != 'evaluado' INNER JOIN oferta_documentos_ofertascliente AS PAR ON PAR.id = OFE.documento_id LEFT JOIN oferta_documentos_oferentes AS DOCOF ON DOCOF.id = PAR.id_documento_oferente LEFT JOIN capitulos ON capitulos.id = DOCOF.sobre LEFT JOIN usuarios UE ON UE.id = OFE.usuario_id LEFT JOIN usuarios UA ON UA.id = OFEH2.id_usuario WHERE OFE.oferta_id = ? AND OFE.usuario_id = ? ORDER BY PAR.id_usuario, DOCOF.sobre, OFE.documento_id ASC";
        $sqlHistorial = $this->intelcost->prepareStatementQuery('cliente', $queryHistorial, 'select', true, "ii", array((int) $id_oferta, (int) $id_evaluador), "Obtener historial de evaluación de documentos evaluador.");
        if($sqlHistorial->bool){
            if($sqlHistorial->msg->num_rows > 0){
                $arrayHistorial = array();
                while ($row = $sqlHistorial->msg->fetch_assoc()) {
                    array_push($arrayHistorial, $row);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrayHistorial);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se han realizado evaluaciones para este evento.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Se presentó un error al consultar el historial de evaluación de documentos.";
        }
        return $this->intelcost->response;  
    }

    public function obtenerHistorialEvaluacionAprobacionIdDocumento($idDocumentoOferente){

        $queryDocumento = "SELECT ODC.id_usuario AS id_proveedor, ODC.id_oferta, ODC.ruta, ODC.fecha_creacion, OD.id AS id_documento_oferente, OD.titulo, OD.descripcion, OD.tipo, OD.contenido, OD.fecha_creacion AS fecha_creacion_doc, OD.evaluable, OD.tipo_evaluacion, OD.parametro_evaluacion, U.nombre AS nombre_comprador, U.username as email, U.id AS id_comprador, CA.id AS id_sobre, CA.nombre AS sobre FROM oferta_documentos_ofertascliente AS ODC INNER JOIN oferta_documentos_oferentes AS OD ON OD.id = ODC.id_documento_oferente INNER JOIN ofertas AS O ON O.id = ODC.id_oferta LEFT JOIN usuarios AS U ON U.id = O.usuario_creacion LEFT JOIN capitulos AS CA ON CA.id = OD.sobre WHERE ODC.id = ?"; 
        $sqlDocumento = $this->intelcost->prepareStatementQuery('cliente', $queryDocumento, 'select', true, "i", array((int) $idDocumentoOferente), "Obtener info documento - historial evaluación y aprobación documento.");
        if($sqlDocumento->bool){
            if($sqlDocumento->msg->num_rows > 0){
                $dataDocumento = $sqlDocumento->msg->fetch_assoc();
                $fecha_creacion = strtotime(date('Y-m-d H:i:s', strtotime($dataDocumento['fecha_creacion_doc'])));
                $fecha_limite = strtotime(date('Y-m-d H:i:s', strtotime("2019-01-11 00:00:00")));
                if($fecha_creacion <= $fecha_limite || ($_SESSION['empresaid'] != 14 && $_SESSION['empresaid'] != 26 && $_SESSION['empresaid'] != 25 && $_SESSION['empresaid'] != 27 && $_SESSION['empresaid'] != 20 && $_SESSION['empresaid'] != 9)){
                    $archivoParticipacion['evaluable'] = true;
                }
                switch ($dataDocumento['tipo_evaluacion']) {
                    case "puntuable":
                        $dataDocumento['id_tipo_evaluacion'] = 1;
                        break;
                    case "cumple - no cumple":
                        $dataDocumento['id_tipo_evaluacion'] = 2;
                        break;
                    default:
                        $dataDocumento['id_tipo_evaluacion'] = 2;
                        $dataDocumento['tipo_evaluacion'] = "cumple - no cumple";
                        break;
                }
                $array_aprobadores = array();
                $queryAprobadores = "SELECT OUA.id_usuario_aprobador as id_aprobador, OUA.accesos, U.nombre, U.username as email FROM oferta_usuarios_aprobadores AS OUA INNER JOIN usuarios AS U ON U.id = OUA.id_usuario_aprobador WHERE OUA.id_oferta = $dataDocumento[id_oferta] AND OUA.estado = 'activo'";

                $sqlAprobadores = $this->intelcost->prepareStatementQuery('cliente', $queryAprobadores, 'select', false, "", "", "Obtener aprobadores - historial evaluación y aprobación documento.");
                if($sqlAprobadores->bool){
                    if($sqlAprobadores->msg->num_rows > 0){
                        while ($aprobador = $sqlAprobadores->msg->fetch_assoc()) {
                            $accesos = (array) json_decode($aprobador['accesos']);
                            if(in_array($dataDocumento['id_documento_oferente'], $accesos)){
                                //$aprobador['nombre'] = ucwords(strtolower($aprobador['nombre']));
                                array_push($array_aprobadores, $aprobador);
                            }
                        }
                    }
                }
                array_push($array_aprobadores, array("id_aprobador" => $dataDocumento['id_comprador'], "nombre" => $dataDocumento['nombre_comprador'], "email" => $dataDocumento['email']));

                $queryEvaluaciones = "SELECT OEV.id, OEV.observaciones, OEV.fecha_creacion AS fecha_evaluacion, OEV.cumpleNoCumple, OEV.resultado_evaluacion, OEV.usuario_id as id_evaluador, U.nombre AS nombre_evaluador, U.username AS email_evaluador FROM oferta_evaluacion_documento AS OEV INNER JOIN usuarios AS U ON U.id = OEV.usuario_id WHERE OEV.documento_id = $idDocumentoOferente ";
                $sqlEvaluaciones = $this->intelcost->prepareStatementQuery('cliente', $queryEvaluaciones, 'select', false, "", "", "Obtener evaluaciones - historial evaluación y aprobación documento.");

                $dataDocumento['evaluaciones'] = array();
                if($sqlEvaluaciones->bool){
                    if($sqlEvaluaciones->msg->num_rows > 0){
                        $evaluacionesArray = array();
                        while ($evaluacion = $sqlEvaluaciones->msg->fetch_assoc()) {
                            $evaluacion['nombre_evaluador'] = ucwords($evaluacion['nombre_evaluador']);
                            array_push($evaluacionesArray, $evaluacion);
                        }
                        $arrayfinalEvaluaciones = array();
                        foreach ($evaluacionesArray as $key => $evaluacion) {
                            $arrayAprobaciones = array();
                            foreach ($array_aprobadores as $aprobador) {
                                $aprobacion = array();
                                $aprobacion['aprobacion'] = false;
                                $aprobacion['id_aprobador'] = $aprobador['id_aprobador'];
                                $aprobacion['nombre_aprobador'] = $aprobador['nombre'];
                                $aprobacion['email_aprobador'] = $aprobador['email'];

                                $queryAprobaciones = "SELECT fecha_aprobacion, observaciones, valoracion FROM oferta_evaluacion_documento_historial WHERE id_evaluacion = $evaluacion[id] AND id_documento = $idDocumentoOferente AND id_usuario = $aprobador[id_aprobador] AND tipo_usuario_registra = 'aprobador'";
                                $sqlAprobaciones = $this->intelcost->prepareStatementQuery('cliente', $queryAprobaciones, 'select', false, "", "", "Obtener Aprobaciones - historial evaluación y aprobación documento.");
                                if($sqlAprobaciones->bool){
                                    if($sqlAprobaciones->msg->num_rows > 0){
                                        $aprobacion['aprobacion'] = true;
                                        $dataAprobacion = $sqlAprobaciones->msg->fetch_assoc();
                                        $aprobacion['fecha_aprobacion'] = $dataAprobacion['fecha_aprobacion'];
                                        $aprobacion['fecha_aprobacion_cast'] = $this->intelcost->castiarFechayHoraIntelcost($dataAprobacion['fecha_aprobacion']);
                                        $aprobacion['observaciones'] = $dataAprobacion['observaciones'];
                                        $aprobacion['valoracion'] = $dataAprobacion['valoracion'];

                                    }else{
                                        $aprobacion['mensaje_error'] = "El aprobador $aprobador[nombre] no ha realizado la respectiva aprobación.";
                                    }
                                }else{
                                    $aprobacion['mensaje_error'] = "Se presentó un error al consultar la aprobación del usuario $aprobador[nombre].";
                                }
                                array_push($arrayAprobaciones, $aprobacion);
                            }
                            $evaluacion['aprobaciones'] = $arrayAprobaciones;
                            $evaluacion['fecha_evaluacion_cast'] = $this->intelcost->castiarFechayHoraIntelcost($evaluacion['fecha_evaluacion']);
                            array_push($arrayfinalEvaluaciones, $evaluacion);
                        }
                        $dataDocumento['evaluaciones'] = $arrayfinalEvaluaciones;
                    }
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $dataDocumento;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontró información sobre este documento.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Se presentó un error al consultar el historial de evaluación del documento.";
        }
        return $this->intelcost->response;  
    }

    public function validarAprobacionDocumentoOferente($objDocumento){
        $query = "SELECT id_historial, id_usuario, id_evaluacion FROM `oferta_evaluacion_documento_historial` WHERE id_evaluacion = ? AND id_documento = ? AND id_usuario = ? AND tipo_usuario_registra = 'aprobador'";
        $sql = $this->intelcost->prepareStatementQuery('cliente', $query, 'select', true, "iii", array((int) $objDocumento->idEvaluacion, (int) $objDocumento->IdDocumento, (int) $objDocumento->idAprobador), "Validar aprobación evaluación documento.");
        if($sql->bool){
            if($sql->msg->num_rows > 0){
                $resp = $sql->msg->fetch_assoc();
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $resp;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontró aprobación sobre evaluadión del documento.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Se presentó un error al consultar aprobación evaluación del documento.";
        }
        return $this->intelcost->response;  
    }

    public function obtenerAdjudicacion($id) {
        //$SqlOfer = 'SELECT * FROM oferta_adjudicaciones WHERE id_oferta = ? ';
        //QUERY PARA FORMATO NÚMERICO
        $SqlOfer = 'SELECT *, FORMAT(valor, 2) as valor_formato FROM oferta_adjudicaciones WHERE id_oferta = ? ';
        $SqlUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'select', true, "i", array((int) $id), "Obtener adjudicación de oferta.");

        if ($SqlUsr->bool) {
            if ($SqlUsr->msg->num_rows > 0) {
                $adjudicaciones = array();
                while ($adjudicaicon = $SqlUsr->msg->fetch_assoc()) {
                    $adjudicaicon['carta_adjudicacion'] = $this->intelcost->generaRutaServerFiles($adjudicaicon['carta_adjudicacion'], "cliente");
                    if (isset($_SESSION['empresaid']) && ($_SESSION['empresaid'] == 27)) {
                        $datosAdicionalesAdjudicacionEmpresas = OfertaAdjudicacionesEmpresas::where('id_oferta_adjudicacion', $adjudicaicon['id'])->porActivo()->with('empresaMaestra')->get();
                        $adjudicaicon['datos_adicionales_empresas'] = $datosAdicionalesAdjudicacionEmpresas->toArray();
                    }
                    array_push($adjudicaciones, $adjudicaicon);
                }
                $arrAdjudicacion = [];
                
                $queryContratosAdjudicacion = 'SELECT proveedor_id FROM contratos WHERE oferta_id = ?';
                $sqlContratosAdjudicacion = $this->intelcost->prepareStatementQuery('cliente', $queryContratosAdjudicacion, 'select', true, "i", array((int) $id), "Obtener contratos asociados con adjudicación.");
                $dataContratosAdjudicacion = array();
                if($sqlContratosAdjudicacion->bool){
                    while ($arrDataCoAdj = $sqlContratosAdjudicacion->msg->fetch_assoc()) {
                        array_push($dataContratosAdjudicacion, $arrDataCoAdj['proveedor_id']);
                    }
                }
                
                foreach ($adjudicaciones as $adjudicaicon) {
                    $usuario = $this->modelo_usuario->obtenerUsuarioIntelcost($adjudicaicon["id_usuario"]);
                    
                    if ($usuario && $usuario != "false") {
                        $usuarioObj = json_decode($usuario);
                        if (isset($usuarioObj->usrnomxx)) {
                            $adjudicaicon["UsuarioAdjudicado"] = $usuarioObj->usrnomxx;
                        } else {
                            $adjudicaicon["UsuarioAdjudicado"] = "No encontrado";
                        }
                        if (isset($usuarioObj->razonxxx)) {
                            $adjudicaicon["EmpresaAdjudicada"] = $usuarioObj->razonxxx;
                        } else {
                            $adjudicaicon["EmpresaAdjudicada"] = "No encontrado";
                        }
                        if (isset($usuarioObj->teridxxx)) {
                            $adjudicaicon["nitEmpresaAdjudicada"] = $usuarioObj->teridxxx;
                        } else {
                            $adjudicaicon["nitEmpresaAdjudicada"] = "";
                        }

                        $adjudicaicon["cod_empresa"] = $usuarioObj->id_empresa;
                        $adjudicaicon["posee_contrato_adjudicado"] ="no";

                        if(in_array($adjudicaicon["nitEmpresaAdjudicada"], $dataContratosAdjudicacion)){
                            $adjudicaicon["posee_contrato_adjudicado"] ="si";
                        }

                    } else {
                        $adjudicaicon["UsuarioAdjudicado"] = "No encontrado";
                        $adjudicaicon["EmpresaAdjudicada"] = "No encontrado";
                        $adjudicaicon["nitEmpresaAdjudicada"] = "No encontrado";
                        $adjudicaicon["cod_empresa"] = "";
                        $adjudicaicon["posee_contrato_adjudicado"] = "no";
                    }

                    array_push($arrAdjudicacion, $adjudicaicon);
                    
                }
                
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrAdjudicacion);
            } else {
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontraron resultados.";
            }
        } else {
            $this->intelcost->response = $SqlUsr;
        }
        return $this->intelcost->response;
    }

    public function obtenerAdjudicacionSha256($id){
            //$SqlOfer = 'SELECT * FROM oferta_adjudicaciones WHERE SHA2(id_oferta,"256") = ? ';
            $SqlOfer = 'SELECT *, FORMAT(valor, 2) as valor_formato FROM oferta_adjudicaciones WHERE SHA2(id_oferta,"256") = ? ';
            $sqlAdjudicacion = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'select', true, "s", array($id), "Obtener adjudicación.");
            if ($sqlAdjudicacion->bool) {
                if ($sqlAdjudicacion->msg->num_rows > 0) {
                    $rows = array();
                    while ($row = $sqlAdjudicacion->msg->fetch_assoc()) {
                        array_push($rows, $row);
                    }
                    $arrAdjudicacion = [];
                    
                    $queryContratosAdjudicacion = 'SELECT proveedor_id FROM contratos WHERE SHA2(oferta_id,"256") = ?';
                    $sqlContratosAdjudicacion = $this->intelcost->prepareStatementQuery('cliente', $queryContratosAdjudicacion, 'select', true, "s", array($id), "Obtener contratos asociados con adjudicación.");
                    $dataContratosAdjudicacion = array();
                    if($sqlContratosAdjudicacion->bool){
                        while ($arrDataCoAdj = $sqlContratosAdjudicacion->msg->fetch_assoc()) {
                            array_push($dataContratosAdjudicacion, $arrDataCoAdj['proveedor_id']);
                        }
                    }

                    foreach ($rows as $adjudicaicon) {
                        $usuario = $this->modelo_usuario->obtenerUsuarioIntelcost($adjudicaicon["id_usuario"]);
                        if ($usuario && $usuario != "false") {
                            $usuarioObj = json_decode($usuario);
                            if (isset($usuarioObj->usrnomxx)) {
                                $adjudicaicon["UsuarioAdjudicado"] = $usuarioObj->usrnomxx;
                            } else {
                                $adjudicaicon["UsuarioAdjudicado"] = "No encontrado";
                            }
                            if (isset($usuarioObj->razonxxx)) {
                                $adjudicaicon["EmpresaAdjudicada"] = $usuarioObj->razonxxx;
                            } else {
                                $adjudicaicon["EmpresaAdjudicada"] = "No encontrado";
                            }
                            if (isset($usuarioObj->teridxxx)) {
                                $adjudicaicon["nitEmpresaAdjudicada"] = $usuarioObj->teridxxx;
                            } else {
                                $adjudicaicon["nitEmpresaAdjudicada"] = "";
                            }
                            $adjudicaicon["cod_empresa"] = $usuarioObj->id_empresa;
                            $adjudicaicon["posee_contrato_adjudicado"] ="no";

                            if(in_array($adjudicaicon["nitEmpresaAdjudicada"], $dataContratosAdjudicacion)){
                                $adjudicaicon["posee_contrato_adjudicado"] ="si";
                            }

                        } else {
                            $adjudicaicon["UsuarioAdjudicado"] = "No encontrado";
                            $adjudicaicon["EmpresaAdjudicada"] = "No encontrado";
                            $adjudicaicon["nitEmpresaAdjudicada"] = "No encontrado";
                            $adjudicaicon["cod_empresa"] = "";
                            $adjudicaicon["posee_contrato_adjudicado"] = "no";
                        }
                        /*if(!in_array($adjudicaicon["nitEmpresaAdjudicada"], $dataContratosAdjudicacion)){
                            array_push($arrAdjudicacion, $adjudicaicon);
                        }*/
                        array_push($arrAdjudicacion, $adjudicaicon);
                    }
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = json_encode($arrAdjudicacion);
                } else {
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "No se encontraron resultados.";
                }
            } else {
                $this->intelcost->response = $sqlAdjudicacion;
            }
            return $this->intelcost->response;
        }

    protected function obtenerMaestrasAsociadasOferta($cod_oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlQuery="SELECT OMG.id_maestra,OMG.cod_oferta,OMG.usuario_registro,OMG.fecha_registro, ";
            $SqlQuery.="OMG.estado,MGTIM.titulo_item,MGTM.titulo,MGTM.descripcion,MGTM.cod_maestra, ";
            $SqlQuery.="MGTIM.cod_item FROM oferta_maestras_generales OMG INNER JOIN mst_general_titulos_items_modulos ";
            $SqlQuery.="MGTIM ON OMG.cod_maestra = MGTIM.cod_item INNER JOIN mst_general_titulos_modulos MGTM ON ";
            $SqlQuery.="MGTIM.cod_titulo = MGTM.cod_maestra WHERE OMG.cod_oferta='".$cod_oferta."' AND OMG.estado=1;";
            $CscQuery=$dbConection->query($SqlQuery);
            if($CscQuery){
                $arrResultado=[];
                while($row = $CscQuery->fetch_assoc()){
                    array_push($arrResultado,json_encode($row));
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrResultado);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea - obtener maestras asociadas.";
            }

        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function crearOferta($ofModelo, $tipooferta){
        if(isset($_SESSION["idusuario"])){
            $aprobacion = 'no';
            if($_SESSION["empresaid"] == 10){
                $seqId = $this->obtenerSecuenciaMetro($ofModelo);   
            }else if($_SESSION["empresaid"] == 14 || $_SESSION["empresaid"] == 26){
                $seqId = $this->generarCodigoTerpel($ofModelo->maestra1, $_SESSION["empresaid"]);
            }else if($_SESSION["empresaid"] == 27){
                $seqId = $this->generarCodigoVanti($ofModelo->maestra1, $_SESSION["empresaid"],$ofModelo);
            }else if($_SESSION["empresaid"] == 20){
                $seqId = $this->generarCodigoOcensa($_SESSION["empresaid"], $ofModelo);
            }else if($_SESSION["empresaid"] == 9){ 
                $seqId = $this->generarCodigoAlqueria($_SESSION["empresaid"], $ofModelo); 
            }else if($_SESSION["empresaid"] == 25){ 
                $seqId = $this->generarCodigoConfa('PO', $_SESSION["empresaid"]);
            }else{
                $seqId = $this->obtenerSiguienteScuenciaId();
            }

            if($seqId){
                if(!isset($ofModelo->solpedRelacionadas)){
                    $ofModelo->solpedRelacionadas = "";
                }
                if(!isset($ofModelo->oferta_autoproroga)){
                    $ofModelo->oferta_autoproroga = 0;
                }
                if(!isset($ofModelo->presupuesto)){
                    $ofModelo->presupuesto = 0;
                }
                if(!isset($ofModelo->inputsolped)){
                    $ofModelo->inputsolped = '';
                }
                if(!isset($ofModelo->moneda)){
                    $ofModelo->moneda = 'COP';
                }
                if(!isset($ofModelo->maestra2)){
                    $ofModelo->maestra2 = '';
                }
                if(!isset($ofModelo->maestra3)){
                    $ofModelo->maestra3 = '';
                }
                
                $presupuesto = str_replace(",","", $ofModelo->presupuesto);
                $queryOferta = 'INSERT INTO ofertas (`id_cliente`,`seq_id`,`solpeds_relacionadas`,`tipo`, `objeto`, `actividad`, `descripcion`,`vigencia`,`moneda`, `presupuesto`, `maestra1`, `maestra2`, `maestra3`, `fecha_inicio`, `hora_inicio`, `fecha_cierre`, `hora_cierre`, `fecha_limite_msg`, `fecha_limite_restrictivo`, `fecha_limite_msg_fecha`, `fecha_limite_hora`, `fecha_limite_msg_observacion`,`autoproroga`,`id_area`,  `id_requisicion`,`id_aoc`,`modalidad_seleccion`, `regional`, `usuario_creacion`, `usuario_actualizacion`, `fecha_actualizacion`, `estado`,`duenio_oferta`, `ronda`,`require_flujo`) VALUES ("'.$_SESSION["empresaid"].'", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,"'.$_SESSION["idusuario"].'","'.$_SESSION["idusuario"].'","'.date("Y-m-d H:i:s").'","ACTIVO","'.$_SESSION["idusuario"].'", 1, ?)';

                $parametros = array($seqId, $ofModelo->solpedRelacionadas, $ofModelo->tipooferta, $ofModelo->objeto, $ofModelo->actividad_id, $ofModelo->descripcion,$ofModelo->vigencia, $ofModelo->moneda, $presupuesto, $ofModelo->maestra1, $ofModelo->maestra2, $ofModelo->maestra3, $ofModelo->fecha_inicio, $ofModelo->hora_inicio, $ofModelo->fecha_cierre, $ofModelo->hora_cierre, (int)$ofModelo->fecha_msg_check, (int)$ofModelo->limite_restrictivo_check, $ofModelo->fecha_msg_fecha, $ofModelo->fecha_limite_hora, $ofModelo->fecha_msg_observacion, (int)$ofModelo->oferta_autoproroga, (int)$ofModelo->id_area_oferta,(int)$ofModelo->requisicion_id, (int)$ofModelo->aoc_id,$ofModelo->modalidad_seleccion, (int)$ofModelo->regional, (int)$ofModelo->requiere_flujo_aprobacion);
                $tipoParametros = "ssssssssssssssssiisssiiiisii";

                $insertOferta = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, 'insert', true, $tipoParametros, $parametros, "Crear oferta.");
                
                if($insertOferta->bool){
                    $res["LastId"] = $insertOferta->msg;
                    $res["seqId"] = $seqId;

                    //WS terpel                     
                    if($_SESSION["empresaid"] == "14" || $_SESSION["empresaid"] == 14){
                        $objOferta = $this->obtenerOfertaBasicaCabecera($insertOferta->msg);
                        if($objOferta->bool){
                            $dataOferta = $objOferta->msg;
                            /*INTEGRACIÓN TERPEL BIZAGI - solo se ejecuta la integración cuando sea la primera ronda del evento. */
                            if((int) $dataOferta->ronda == 1 && (int) $dataOferta->id_cliente == 14) {
                                $NotificarCreacionProcesoCompra = $this->modelo_ofertas_accionesWs->NotificarCreacionProcesoCompra($dataOferta);
                                $res["respTerpel"] = $NotificarCreacionProcesoCompra->msg;
                            }else{
                                $res["respTerpel"] = false;
                            }
                        }else{
                            $res["respTerpel"] = false;
                        }
                    }

                    //Campos adicionales
                    if(
                        isset($_POST['precalificacion_id']) || 
                        isset($_POST['solicitud_asociada']) ||
                        isset($_POST['otros']) ||
                        isset($_POST['fecha_maximo_respuesta'])
                    ){
                        $datos = [
                            'oferta_id' => $insertOferta->msg,
                            'precalificacion_id' => !empty($_POST['precalificacion_id']) ? $_POST['precalificacion_id'] : null,
                            'solicitud_id' => !empty($_POST['solicitud_asociada']) ? $_POST['solicitud_asociada'] : null,
                            'otros' => !empty($_POST['otros']) ? $_POST['otros'] : null,
                            'fecha_maximo_respuesta' => !empty($_POST['fecha_maximo_respuesta']) ? $_POST['fecha_maximo_respuesta'] : null,
                        ];
                        
                        $condicional = [
                            'oferta_id' => $insertOferta->msg
                        ];
            
                        OfertaDatosAdicionales::updateOrCreate($condicional, $datos);
                    }

                    //Flujos de aprobacion
                    if($_SESSION["empresaid"] == "10" && $ofModelo->tipooferta == "rfq" ){
                        $this->asociarFlujosRfqMetro($ofModelo, $res["LastId"],$ofModelo->requiere_flujo_aprobacion);
                        $modelo_acciones_oferta = new modelo_acciones_oferta();
                        $objCrearAccionesAdicinoales = $modelo_acciones_oferta->crearAccionesAdicionales($res["LastId"], $ofModelo);
                    }
                    if($_SESSION["empresaid"] == "27"){
                        if ((int) $ofModelo->requiere_flujo_aprobacion) {
                            $this->asociarFlujosRfqVanti($ofModelo, $res["LastId"],$ofModelo->requiere_flujo_aprobacion,$ofModelo->tipooferta);
                        }else{
                            $this->asociarFlujosRfqVanti($ofModelo, $res["LastId"],$ofModelo->requiere_flujo_aprobacion,$ofModelo->tipooferta, true);
                        }
                    }

                    if($_SESSION['empresaid'] == 20){
                        if(isset($ofModelo->criterios_evaluacion)){
                            $criteriosTecnicos = collect(json_decode($ofModelo->criterios_evaluacion));
                            if($criteriosTecnicos->count() > 0){
                                $this->guardarCriteriosTecnicos($criteriosTecnicos, $insertOferta->msg);
                            }
                        }
                    }

                    if(isset($ofModelo->arrMaestrasGenerales)){
                        $this->asociarMaestrasOferta($ofModelo->arrMaestrasGenerales, $res["LastId"]);
                    }
                    if(isset($ofModelo->sobres_oferta)){
                        $this->asociarSobreOferta($ofModelo->sobres_oferta, $res["LastId"]);
                    }
                    if(isset($ofModelo->criterios_evaluacion)){
                        $res_criterios = $this->asociarCriterioOferta(json_decode($ofModelo->criterios_evaluacion), $res["LastId"]);
                    }
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =$res;
                }else{
                    
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Error al crear la oferta.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No logramos obtener un id valido para su oferta, verifique los datos solicitados";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="La session ha caducado";
        }
        return $this->intelcost->response;
        
    }
    public function obtenerCriteriosOferta($id_oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlQuery="SELECT id,id_oferta,criterio,valor FROM `ofertas_criterios_evaluacion` WHERE id_oferta = '".$id_oferta."' AND estado =1";
            $CscQuery=$dbConection->query($SqlQuery);
            $arr_response = [];
            if($CscQuery){
                if($CscQuery->num_rows!=0){
                    while($row = $CscQuery->fetch_assoc()){
                        array_push($arr_response, json_encode($row));
                    }
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =$arr_response;
                }else{
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg ="";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error consulta de criterio";
            }
            
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }
    private function asociarCriterioOferta($arrCriterios,$id_oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            if(isset($arrCriterios) && count($arrCriterios)!=0 && isset($id_oferta) && !empty($id_oferta)){

                //$arrCriteriosActuales="";
                foreach ($arrCriterios as $key => $criterio) {
                    //$respuestaConsultaCriterio=$this->obtenerCriteriosOferta($id_oferta);
                    //if($respuestaConsultaCriterio->bool){
                        //if(empty($respuestaConsultaCriterio->msg)){
                    
                            $SqlQuery="INSERT INTO `ofertas_criterios_evaluacion` (id_oferta,criterio,valor) VALUES ";
                            $SqlQuery.="('".$id_oferta."','".$criterio->crit."','".$criterio->punt."')";
                            $CscQuery=$dbConection->query($SqlQuery);
                            if($CscQuery){
                                $this->intelcost->response->bool = true;
                                $this->intelcost->response->msg ="Inserccion criterio exitosa";
                            }else{
                                $this->intelcost->response->bool = false;
                                $this->intelcost->response->msg ="Error inserccion criterio";
                            }
                        //}
                    //}
                }
                
                /*if(count($arrCriteriosActuales)!=0){
                    $stringCriteriosActuales=implode(",", $arrCriteriosActuales);
                    $SqlQuery="UPDATE `ofertas_criterios_evaluacion` SET estado=2 WHERE id_oferta='".$id_oferta."' ";
                    $SqlQuery.="AND criterio NOT IN (".$stringCriteriosActuales.");";
                    $CscQuery=$dbConection->query($SqlQuery);
                }*/
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }
    private function asociarMaestrasOferta($arrMaestra,$cod_oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            if(isset($arrMaestra) && count($arrMaestra)!=0 && isset($cod_oferta) && !empty($cod_oferta)){

                $arrMaestrasActuales=[];
                foreach ($arrMaestra as $key => $value) {
                    $respuestaMaestra=$this->obtenerMaestraAsociadaOferta($value,$cod_oferta);
                    array_push($arrMaestrasActuales,$value);
                    if($respuestaMaestra->bool){
                        if(empty($respuestaMaestra->msg)){
                            $SqlQuery="INSERT INTO `oferta_maestras_generales` (cod_oferta,cod_maestra,usuario_registro) VALUES ";
                            $SqlQuery.="('".$cod_oferta."','".$value."','".$_SESSION["idusuario"]."')";
                            $CscQuery=$dbConection->query($SqlQuery);
                        }
                    }
                }
                
                if(count($arrMaestrasActuales)!=0){
                    $stringMaestrasActuales=implode(",", $arrMaestrasActuales);
                    $SqlQuery="UPDATE `oferta_maestras_generales` SET estado=2 WHERE cod_oferta='".$cod_oferta."' ";
                    $SqlQuery.="AND cod_maestra NOT IN (".$stringMaestrasActuales.");";
                    $CscQuery=$dbConection->query($SqlQuery);
                }
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    protected function obtenerMaestraAsociadaOferta($cod_maestra,$cod_oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlQuery="SELECT id_maestra,cod_oferta,cod_maestra FROM `oferta_maestras_generales` ";
            $SqlQuery.="WHERE cod_oferta='".$cod_oferta."' AND cod_maestra='".$cod_maestra."' AND estado=1;";
            $CscQuery=$dbConection->query($SqlQuery);
            if($CscQuery){
                if($CscQuery->num_rows!=0){
                    $row = $CscQuery->fetch_assoc();
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =json_encode($row);
                }else{
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg ="";
                }

            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea - validar maestra asociada oferta.";
            }

        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }
    private function asociarSobreOferta($arrSobres,$id_oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arrSobres = json_decode($arrSobres);
            if(isset($arrSobres) && count($arrSobres)!=0 && isset($id_oferta) && !empty($id_oferta)){

                $arrMaestrasActuales=[];
                $SqlQuery="SELECT id_sobre FROM  `ofertas_sobres` WHERE id_oferta='".$id_oferta."' ";
                $CscQuery=$dbConection->query($SqlQuery);
                $sobres_existentes =[];
                if($CscQuery){
                    while($row = $CscQuery->fetch_assoc()){
                        array_push($sobres_existentes,$row["id_sobre"]);
                    }                   
                }
                foreach ($arrSobres as $key => $value) {
                    if(in_array($value, $sobres_existentes)){
                        $SqlQuery="UPDATE `ofertas_sobres` SET estado = '1' WHERE id_oferta ='".$id_oferta."' AND  id_sobre ='".$value."' ";
                        $CscQuery=$dbConection->query($SqlQuery);
                    }else{
                        $SqlQuery="INSERT INTO `ofertas_sobres` (id_oferta,id_sobre,estado) VALUES ('".$id_oferta."','".$value."','1')";
                        $CscQuery=$dbConection->query($SqlQuery);
                    }
                }
                
                /*if(count($arrMaestrasActuales)!=0){
                    $stringMaestrasActuales=implode(",", $arrMaestrasActuales);
                    $SqlQuery="UPDATE `oferta_maestras_generales` SET estado=2 WHERE id_oferta='".$id_oferta."' ";
                    $SqlQuery.="AND cod_maestra NOT IN (".$stringMaestrasActuales.");";
                    $CscQuery=$dbConection->query($SqlQuery);
                }*/
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function crearEstudioExistente($ofModelo){
        if(isset($_SESSION["idusuario"])){
            if($_SESSION["empresaid"] == 10){
                $seqId = $this->obtenerSecuenciaMetro($ofModelo);   
            }else{
                $seqId = false;
            }
            if($seqId){
                $SqlOfer = 'INSERT INTO ofertas (`id_cliente`,`seq_id`,`tipo`, `usuario_creacion`, `usuario_actualizacion`, `fecha_actualizacion`, `estado`,`duenio_oferta`,`id_requisicion`,`id_area`,`objeto`,`descripcion`,`actividad`) VALUES ("'.$_SESSION["empresaid"].'", "'.$seqId.'", "estudio","'.$_SESSION["idusuario"].'","'.$_SESSION["idusuario"].'","'.date("Y-m-d H:i:s").'","FINALIZADA","'.$_SESSION["idusuario"].'", ?, ?, ?, ?, ?)';

                $parametros = array((int) $ofModelo->id_requisicion, (int) $ofModelo->id_area_oferta, $ofModelo->obj_requisi, $ofModelo->obj_requisi, $ofModelo->act_econo);
                $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'insert', true, "iisss", $parametros, "Crear estudio existente");
                if($CscUsr->bool){
                    $this->intelcost->response->bool = true;
                    $res = new stdClass();
                    $res->seq_id =  $seqId;
                    $res->id =  $CscUsr->msg;
                    $this->intelcost->response->msg = $res;
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Error al crear el estudio.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Parametro invalido seqId";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="La session ha caducado";
        }
        return $this->intelcost->response;
    }

    /* luego de almacenado el estudio, se actualizan los soportes que lo sustentan */
    public function relacionSoporteEstudioExistente($id_oferta, $soporte){
        if(isset($_SESSION["idusuario"])){
            $SqlActualesSoportes = 'SELECT soportes_existencia FROM ofertas WHERE `id_cliente`= "'.$_SESSION["empresaid"].'" AND `id`= ? LIMIT 1';
            $CscActualesSoportes = $this->intelcost->prepareStatementQuery('cliente', $SqlActualesSoportes, 'select', true, "i", array((int) $id_oferta), "Obtener relación soporte estudios existentes.");
            $arr_soportes = [];
            if($CscActualesSoportes->bool){
                $soportes_str = $CscActualesSoportes->msg->fetch_assoc();
                $soportes = json_decode($soportes_str["soportes_existencia"]);
                if($soportes){
                    foreach($soportes as $exi_soporte){
                        array_push($arr_soportes,$exi_soporte);
                    }
                }
            }

            /*Agregar el nuevo */
            $soporte_obj = new stdClass();

            
            $nombre_archivo = explode("?X-Amz-Content", $soporte["nombre_archivo"]);
            $soporte_obj->sop_url = $soporte["ruta"].$nombre_archivo[0];
            $soporte_obj->sop_nombre = $soporte["titulo"];
            array_push($arr_soportes,$soporte_obj);

            /*Actualizar la oferta */
            $str_soportes = json_encode($arr_soportes);
            $params = array($str_soportes,(int) $_SESSION["empresaid"],(int) $id_oferta);
            $SqlOfer = "UPDATE ofertas  SET `soportes_existencia` = ?, `usuario_actualizacion` = $_SESSION[idusuario], `fecha_actualizacion`='".date("Y-m-d H:i:s")."' WHERE `id_cliente`= ? AND `id`= ? ";
            $cscOfer = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "sii", $params, "Actualizar oferta relación soporte estudios existentes.");
            if($cscOfer->bool){
                if($cscOfer->msg > 0){
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "soporte registrado";
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Error al relacionar soporte 2.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error al relacionar soporte.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="La session ha caducado";
        }
        return $this->intelcost->response;
    }

    public function copiarOfertas($objDataEventos){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            
            $arrRes = [];

            if(!empty($objDataEventos) && $objDataEventos != null){
                $objEventos = json_decode($objDataEventos);
                foreach ($objEventos as $key => $value) {
                    $arr_Eventos = [];
                    $arr_Oferta = [];
                    if(!empty($value->id_evento) && $value->id_evento != null){
                        if($_SESSION["empresaid"] == 10){
                            $idNuevaOferta = $this->obtenerSecuenciaMetro($ofModelo);   
                        }else if($_SESSION["empresaid"] == 14 || $_SESSION["empresaid"] == 26){
                            $idNuevaOferta = $this->generarCodigoTerpel($ofModelo->maestra1, $_SESSION["empresaid"]);
                        }else if($_SESSION["empresaid"] == 27){
                            $idNuevaOferta = $this->generarCodigoVanti($ofModelo->maestra1, $_SESSION["empresaid"],$ofModelo);
                        }else if($_SESSION["empresaid"] == 20){
                            $idNuevaOferta = $this->generarCodigoOcensa($_SESSION["empresaid"], $ofModelo);
                        }else if($_SESSION["empresaid"] == 9){
                            $idNuevaOferta = $this->generarCodigoAlqueria($_SESSION["empresaid"], $ofModelo);
                        }else if($_SESSION["empresaid"] == 25){
                            $idNuevaOferta = $this->generarCodigoConfa('PO', $_SESSION["empresaid"]);
                        }else{
                            $idNuevaOferta = $this->obtenerSiguienteScuenciaId();
                        }

                        if(!empty($idNuevaOferta) && isset($idNuevaOferta)){
                            $OfertaOriginal = $this->obtenerOferta($value->id_evento);
                            
                            if($OfertaOriginal->bool){
                                $OfertaOriginal = json_decode($OfertaOriginal->msg);
                                //Informacion general - Tabla oferta
                                $arr_Oferta['tipooferta'] =$OfertaOriginal->tipo;
                                $arr_Oferta['objeto']=$OfertaOriginal->objeto;
                                //$arr_Oferta['solpedRelacionadas'] = $OfertaOriginal->solpeds_relacionadas;
                                $arr_Oferta['actividad_id']=$OfertaOriginal->actividad;
                                $arr_Oferta['descripcion']=$OfertaOriginal->descripcion;
                                $arr_Oferta['moneda']=$OfertaOriginal->moneda;
                                $arr_Oferta['maestra1']=$OfertaOriginal->maestra1;
                                $arr_Oferta['maestra2']=$OfertaOriginal->maestra2;
                                $arr_Oferta['regional']=$OfertaOriginal->regional;
                                $arr_Oferta['presupuesto']='';
                                $arr_Oferta['fecha_inicio']=date('Y-m-d');
                                $arr_Oferta['hora_inicio']=date('H:i:s');
                                $arr_Oferta['fecha_cierre']=date('Y-m-d');
                                $arr_Oferta['hora_cierre']=date('H:i:s');
                                $arr_Oferta['fecha_msg_check']='';
                                $arr_Oferta['limite_restrictivo_check'] = $OfertaOriginal->fecha_limite_restrictivo;
                                $arr_Oferta['fecha_msg_fecha']= $OfertaOriginal->fecha_limite_msg_fecha;
                                $arr_Oferta['fecha_limite_hora']= $OfertaOriginal->fecha_limite_hora;
                                $arr_Oferta['fecha_msg_observacion']= $OfertaOriginal->fecha_limite_msg_observacion;
                                $arr_Oferta['oferta_autoproroga']= $OfertaOriginal->autoproroga;
                                $arr_Oferta['regional']= $OfertaOriginal->regional;
                                $arr_Oferta['id_area_oferta']=$OfertaOriginal->id_area;
                                $arr_Oferta['requisicion_id']=$OfertaOriginal->id_requisicion;
                                $arr_Oferta['aoc_id']=$OfertaOriginal->id_aoc;
                                $arr_Oferta['estado']=$OfertaOriginal->estado;

                                $arr_Oferta['modalidad_seleccion']=$OfertaOriginal->modalidad_seleccion;
                                $ofertaObj= (object)$arr_Oferta;

                                //Insertar Información General
                                $res = $this->crearOferta($ofertaObj,'cerrada');        
                                if($res->bool){
                                    $idOfertaCreada = $res->msg['LastId'];//Con este ID se continua guardado la otra informacion
                                    $SeqOferta = $res->msg['seqId'];
                                    
                                    //Insertar contenido - Tabla oferta_documentos
                                    $contConOk = 0;
                                    $contConError = 0;
                                    foreach ($OfertaOriginal->documentos_oferta as $keyCon => $dataCon) {
                                        $dataCon = (array) $dataCon;
                                        $creacionDoc = $this->crearDocumentoOferta2($idOfertaCreada, $dataCon);
                                        if($resCon->bool){
                                            $contConOk++;
                                        }else{
                                            $contConError++;
                                        }
                                    }

                                    //Insertar documentacion solicitada - oferta_documentos_oferentes
                                    $contDocOk =0;
                                    $contDocError =
                                    0;
                                    foreach ($OfertaOriginal->documentos_oferente as $keyDoc => $dataDoc) {
                                        $doc_id = random_int(intval(1000000000),intval (9999999999));
                                        if($dataDoc->obligatorio == 'SI'){
                                            $dataDoc->obligatorio = 1;
                                            $dataDoc->obliga = "si";
                                        }else{
                                            $dataDoc->obligatorio = 0;
                                            $dataDoc->obliga = "no";
                                        }
                                        if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26  || $_SESSION["empresaid"] == 25 || $_SESSION['empresaid'] == 10 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9){
                                            $dataDoc->evaluable = (($dataDoc->evaluable) ? "si" : "no");
                                            $dataDoc->parametros_evaluacion = json_decode($dataDoc->parametro_evaluacion);
                                            $dataDoc->tipo_evaluacion = $dataDoc->id_tipo_evaluacion;
                                            $resCon = $this->crearDocumentoOfererente2($idOfertaCreada, $dataDoc);
                                        }else{
                                            $resCon = $this->crearDocumentoOfererente($idOfertaCreada, $doc_id, $dataDoc->titulo, $dataDoc->descripcion, $dataDoc->obligatorio, false, $dataDoc->sobre);
                                        }
                                        if($resCon->bool){
                                            $contDocOk++;
                                        }else{
                                            $contDocError++;
                                        }
                                    }
                                    //Insertar usuarios Internos
                                    $contUsOk =0;
                                    $contUsError =0;
                                    foreach ($OfertaOriginal->usuarios_internos as $keyUi => $dataUi) {
                                        
                                        $resUi = $this->crearUsuarioInternoOferta($idOfertaCreada, $dataUi->id_usuario, '', "", $dataUi);
                                        if($resUi->bool){
                                            $contUsOk++;
                                        }else{
                                            $contUsError++;
                                        }
                                    }

                                    $contParOk =0;
                                    $contParError =0;
                                    foreach ($OfertaOriginal->participantes as $keyPar => $dataPar) {
                                        $resPar = $this->creaParticipanteOferta($idOfertaCreada, $dataPar->id_usuario, $dataPar->nombre, $dataPar->nit, $dataPar->email_usuario);
                                        if($resPar->bool){
                                            $contParOk++;
                                        }else{
                                            $contParError++;
                                        }
                                    }

                                    if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9){
                                        $datosAdicionalesOferta = OfertaDatosAdicionales::where('oferta_id', $value->id_evento)->activo()->first();
                                        $replicaAdicional = $datosAdicionalesOferta->replicate();
                                        $replicaAdicional->oferta_id = $idOfertaCreada;
                                        $replicaAdicional->save();
                                    }

                                    //contadores documentos oferentes
                                    $arr_Eventos['documentosOk'] = $contDocOk;
                                    $arr_Eventos['documentosError'] = $contDocError;
                                    //contadores contenido
                                    $arr_Eventos['contenidoOk'] = $contConOk;
                                    $arr_Eventos['contenidoError'] = $contConError;
                                    //Usuarios Internos
                                    $arr_Eventos['contUsOk'] = $contUsOk;
                                    $arr_Eventos['contUsError'] = $contUsError;
                                    //Participantes
                                    $arr_Eventos['contParOk'] = $contParOk;
                                    $arr_Eventos['contParError'] = $contParError;
                                    //respuesta
                                    $arr_Eventos['id_evento'] = $SeqOferta;
                                    $arr_Eventos['accion'] = 'Se ha copiado correctamente, se ha creado el Proceso/ Evento N° xx';

                                }else{
                                    $arr_Eventos['id_evento'] = $value->id_evento;
                                    $arr_Eventos['accion'] = 'No se logro copiar el evento';
                                }
                            }else{
                                $arr_Eventos['id_evento'] = $value->id_evento;
                                $arr_Eventos['accion'] = 'No se logro obtener la información';
                            }
                        }else{
                            $arr_Eventos['id_evento'] = $value->id_evento;
                            $arr_Eventos['accion'] = 'No se logro obtener el siguiente ID';
                        }
                    }else{
                        $arr_Eventos['id_evento'] = $value->id_evento;
                        $arr_Eventos['accion'] = 'No se logro copiar';
                        
                    }
                    array_push($arrRes,$arr_Eventos);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =json_encode($arrRes);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se detecto ningun evento ha copiar";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    /**
    * @method crearRondaOferta => este metodo es inicialmente para terpel. Se copia la información del evento indicado e incrementando el valor del campo ronda. El evento solo puede ser copiado si se encuentra en estado en evaluacion o en adjudicacion.
    * @return El evento retorna un arreglo con el id de la nueva oferta, y contadores de acciones ok o errores.
    */
    public function crearRondaOferta($id_oferta){
        $objOferta = $this->obtenerOferta($id_oferta);
        if($objOferta->bool){
            $dataOferta = json_decode($objOferta->msg);
            if($dataOferta->estado = "FINALIZADA" && $_SESSION['empresaid'] == 25){
                OfertaAdjudicaciones::where('id_oferta', $id_oferta)
					->get()
					->map(function($adjudicacion_empresa) use ($id_oferta){
						$adjudicacion_empresa->timestamps = false;
						$adjudicacion_empresa->observacion = "Modificación oferta ".$id_oferta." adjudicación eliminada ".date('Y-m-d H:i:s');
						$adjudicacion_empresa->estado = "eliminado";
                        $adjudicacion_empresa->save();
                    });
                OrdenPedidos::where('cod_oferta', $idOferta)->update(['estado' => 'eliminado']);
            }

            if($dataOferta->estado = "EN EVALUACION" || $dataOferta->estado = "EN ADJUDICACION"){
                if($_SESSION['empresaid'] == 25){
                    $ofertaObj = Oferta::where('seq_id', $dataOferta->seq_id)
                            ->with(['gestionPedido' => function($query){
                                $query->whereNotNull('cod_jde_confa')
                                    ->where('estado', 'activo');
                            }])
                            ->orderBy('ronda', 'DESC')->first();
                    if($ofertaObj && ($ofertaObj->ronda != $dataOferta->ronda)){
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "No es posible crear una nueva ronda sobre este proceso/evento, porque se encuentra en una ronda antigua. Actualmente el proceso se encuentra en la ronda <b>$ofertaObj->ronda</b> y el estado es: <b>$ofertaObj->estado</b>";
                        return $this->intelcost->response;
                    }

                    if($ofertaObj->gestionPedido){
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "No es posible crear una nueva ronda sobre este proceso/evento, porque ya tiene una orden de pedido generada";
                        return $this->intelcost->response;
                    }
                }

                $ronda = $dataOferta->ronda + 1;
                $queryOferta = "INSERT INTO ofertas (`id_cliente`, `seq_id`, `solpeds_relacionadas`, `tipo`, `objeto`, `actividad`, `descripcion`, `moneda`, `presupuesto`, `maestra1`, `maestra2`, `fecha_inicio`, `hora_inicio`, `fecha_cierre`, `hora_cierre`, `usuario_creacion`, `estado`, `duenio_oferta`, `ronda`, `vigencia`, `require_flujo`, `id_area`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '".date("Y-m-d")."', NOW(), '".date("Y-m-d")."', NOW(), $_SESSION[idusuario], 'ACTIVO', $_SESSION[idusuario], '$ronda', ?, ?, ?)";

                $parametros = array((int) $dataOferta->clienteId, $dataOferta->seq_id, $dataOferta->solpeds_relacionadas, $dataOferta->tipo, $dataOferta->objeto, $dataOferta->actividad, $dataOferta->descripcion, $dataOferta->moneda, $dataOferta->presupuesto, $dataOferta->maestra1, $dataOferta->maestra2, $dataOferta->vigencia, $dataOferta->require_flujo, $dataOferta->id_area);

                $nuevaRonda = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, 'insert', true, "isssssssssssii", $parametros, "Creación ronda.");
                if($nuevaRonda->bool){
                    $nuevo_id_oferta = $nuevaRonda->msg;
                    $arrayRespuesta = array();
                    $arrayRespuesta['id_oferta'] = $nuevo_id_oferta;
                    $arrayRespuesta['seq_id'] = $dataOferta->seq_id;
                    $arrayRespuesta['objeto'] = $dataOferta->objeto;
                    $arrayRespuesta['ronda'] = $ronda;
                    $arrayRespuesta['documentos_contenido'] = 0;
                    $arrayRespuesta['documentos_contenido_error'] = 0;
                    foreach ($dataOferta->documentos_oferta as $documento) {
                        $arrayDocumentos = (array) $documento;
                        $creacionDoc = $this->crearDocumentoOferta2($nuevo_id_oferta, $arrayDocumentos);
                        if($creacionDoc->bool){
                            $arrayRespuesta['documentos_contenido']++;
                        }else{
                            $arrayRespuesta['documentos_contenido_error']++;
                        }
                    }

                    $arrayRespuesta['documentos_oferente'] = 0;
                    $arrayRespuesta['documentos_oferente_error'] = 0;
                    foreach ($dataOferta->documentos_oferente as $documento_oferente) {
                        $documento_oferente->existente = false;
                        if($documento_oferente->obligatorio == 'SI'){
                            $documento_oferente->obligatorio = 1;
                            $documento_oferente->obliga = "si";
                        }else{
                            $documento_oferente->obligatorio = 0;
                            $documento_oferente->obliga = "no";
                        }
                        $documento_oferente->evaluable = (($documento_oferente->evaluable) ? "si" : "no");
                        $documento_oferente->parametros_evaluacion = json_decode($documento_oferente->parametro_evaluacion);
                        $documento_oferente->tipo_evaluacion = $documento_oferente->id_tipo_evaluacion;
                        $creaDocOferente = $this->crearDocumentoOfererente2($nuevo_id_oferta, $documento_oferente);
                        if($creaDocOferente->bool){
                            $arrayRespuesta['documentos_oferente']++;
                        }else{
                            $arrayRespuesta['documentos_oferente_error']++;
                        }
                    }

                    $arrayRespuesta['usuarios_internos'] = 0;
                    $arrayRespuesta['usuarios_internos_error'] = 0;
                    foreach ($dataOferta->usuarios_internos as $usuario) {
                        
                        $creaUsuarioInterno = $this->crearUsuarioInternoOferta($nuevo_id_oferta, $usuario->id_usuario, '', '', $usuario);
                        if($creaUsuarioInterno->bool){
                            $arrayRespuesta['usuarios_internos']++;
                        }else{
                            $arrayRespuesta['usuarios_internos_error']++;
                        }
                    }

                    $arrayRespuesta['participantes'] = 0;
                    $arrayRespuesta['participantes_error'] = 0;
                    foreach ($dataOferta->participantes as $participante) {
                        
                        $creaParticipante = $this->creaParticipanteOferta($nuevo_id_oferta, $participante->id_usuario, $participante->nombre, $participante->nit, $participante->email_usuario);
                        if($creaParticipante->bool){
                            $arrayRespuesta['participantes']++;
                        }else{
                            $arrayRespuesta['participantes_error']++;
                        }
                    }

                    $arrayRespuesta['lotes'] = 0;
                    $arrayRespuesta['lotes_error'] = 0;
                    $arrayRespuesta['lotes_items'] = 0;
                    $arrayRespuesta['lotes_items_error'] = 0;
                    $dataOferta->arrLotesOferta = json_decode($dataOferta->arrLotesOferta);
                    foreach ($dataOferta->arrLotesOferta as $sobre) {
                        $sobre = json_decode($sobre);
                        $insert_lote = "INSERT INTO `oferta_lotes` (cod_cliente, cod_oferta, cod_sobre, nombre_lote, usuario_creacion, cod_compania) VALUES ('".$dataOferta->clienteId."', $nuevo_id_oferta, ".$sobre->cod_sobre.", '".$sobre->nombre_lote."', '".$_SESSION["idusuario"]."', '".$sobre->cod_compania."');";
                        $sqlLote = $this->intelcost->prepareStatementQuery('cliente', $insert_lote, 'insert', false, "", "", "crear lote ronda.");
                        if($sqlLote->bool){
                            $arrayRespuesta['lotes']++;
                            $cod_lote = $sqlLote->msg;
                            $itemsLote = json_decode($sobre->itemsLote);
                            foreach ($itemsLote as $item) {
                                $item = json_decode($item);
                                $item->descripcion = str_replace("'" ,'',$item->descripcion);
                                $queryItem = "INSERT INTO `oferta_lotes_items` (cod_lote, descripcion, cod_unidad_medida, cantidad, usuario_creacion, secuencia) VALUES ( '".$cod_lote."', '".$item->descripcion."', '".$item->cod_unidad_medida."', '".$item->cantidad."', '".$_SESSION["idusuario"]."', '".$item->secuencia."');";
                                $sqlItemLote = $this->intelcost->prepareStatementQuery('cliente', $queryItem, 'insert', false, "", "", "crear item lote ronda.");
                                if($sqlItemLote->bool){
                                    $arrayRespuesta['lotes_items']++;
                                }else{
                                    $arrayRespuesta['lotes_items_error']++;
                                }
                            }
                        }else{
                            $arrayRespuesta['lotes_error']++;
                        }
                    }
                    /**
                     * Infoadicional Lotes e items Ocensa
                     */

                    if($_SESSION['empresaid'] == 20){
                        $lotesRonda1 = OfertaLote::where('cod_oferta', $id_oferta)->with('obtenerItems.obtenerInformacionAdicionalItem')->get();

                        OfertaLote::where('cod_oferta', $nuevo_id_oferta)->with('obtenerItems')
                                        ->get()
                                        ->map(function($lote, $iterador) use ($lotesRonda1) {
                                            foreach ($lote->obtenerItems as $key => $item) {
                                                foreach ($lotesRonda1[$iterador]->obtenerItems as $key_item => $itemRonda) {
                                                    if($itemRonda->descripcion == $item->descripcion && $itemRonda->cantidad == $item->cantidad){
                                                        if($itemRonda->obtenerInformacionAdicionalItem){
                                                            $replicaDatosAdicionales = $itemRonda->obtenerInformacionAdicionalItem->replicate();
                                                            $replicaDatosAdicionales->cod_item = $item->id_item;
                                                            $replicaDatosAdicionales->save();
                                                        }
                                                    }
                                                }
                                            }
                                            return $lote;
                                        });
                    }

                    /**
                     * Infoadicional Lotes e items generales (Confa)
                     */

                    if($_SESSION['empresaid'] == 25){
                        $lotesRonda1 = OfertaLote::where('cod_oferta', $id_oferta)->with('obtenerItems.obtenerInformacionAdicionalItems')->get();

                        OfertaLote::where('cod_oferta', $nuevo_id_oferta)->with('obtenerItems')
                                        ->get()
                                        ->map(function($lote, $iterador) use ($lotesRonda1) {
                                            foreach ($lote->obtenerItems as $key => $item) {
                                                foreach ($lotesRonda1[$iterador]->obtenerItems as $key_item => $itemRonda) {
                                                    if($itemRonda->descripcion == $item->descripcion && $itemRonda->cantidad == $item->cantidad){
                                                        if($itemRonda->obtenerInformacionAdicionalItems){
                                                            $replicaDatosAdicionales = $itemRonda->obtenerInformacionAdicionalItems->replicate();
                                                            $replicaDatosAdicionales->cod_item = $item->id_item;
                                                            $replicaDatosAdicionales->save();
                                                        }
                                                    }
                                                }
                                            }
                                            return $lote;
                                        });
                    }

                    /**
                     * crear datos adicionales
                     */

                    if($_SESSION['empresaid'] == 20){
                        $datosAdicionalesOferta = OfertaDatosAdicionales::where('oferta_id', $id_oferta)->activo()->first();
                        $replicaAdicional = $datosAdicionalesOferta->replicate();
                        $replicaAdicional->oferta_id = $nuevo_id_oferta;
                        $replicaAdicional->save();
                    }

                    /**
                     * crear datos criterios
                     */

                    if($_SESSION['empresaid'] == 20){
                        OfertaCriteriosEvaluacion::where('id_oferta', $id_oferta)
                            ->activo()
                            ->get()
                            ->map(function($criterio) use ($nuevo_id_oferta){
                                /**
                                 * Criterios
                                 */
                                $nuevoCriterio = $criterio->replicate();
                                $nuevoCriterio->id_oferta = $nuevo_id_oferta;
                                $nuevoCriterio->save();
                                /**
                                 * Criterios datos adicionales
                                 */
                                if($criterio->obtenerDatosAdicionales){
                                    $nuevoCriterioDatoAdicional = $criterio->obtenerDatosAdicionales->replicate();
                                    $nuevoCriterioDatoAdicional->id_criterio_oferta = $nuevoCriterio->id;
                                    $nuevoCriterioDatoAdicional->save();
                                }
                            });
                    }

                    /**
                     * crear documentos criterios
                     */

                    if($_SESSION['empresaid'] == 20){
                        $documentosOferentesOfertaAntigua = OfertaDocumentosOferentes::where('oferta_id', $id_oferta)
                                                                ->activo()
                                                                ->get();

                        OfertaDocumentosOferentes::where('oferta_id', $nuevo_id_oferta)
                            ->activo()
                            ->get()
                            ->map(function($documento) use ($documentosOferentesOfertaAntigua){
                                /**
                                 * Documentos criterios por documento
                                 */
                                foreach ($documentosOferentesOfertaAntigua as $key => $documentoOferenteAntiguo) {
                                    if($documentoOferenteAntiguo->titulo == $documento->titulo && $documentoOferenteAntiguo->criterios){
                                        $objetoCriterios = $documentoOferenteAntiguo->obtenerCriterios;
                                        foreach ($objetoCriterios as $key => $criterio) {
                                            $nuevoCriterio = $criterio->replicate();
                                            $nuevoCriterio->id_item_documento = $documento->id;
                                            $nuevoCriterio->save();
                                        }
                                    }
                                }
                            });
                    }

                    $queryFinOferta = "UPDATE ofertas SET estado = 'FINALIZADA', `usuario_actualizacion` = $_SESSION[idusuario] WHERE id = $id_oferta ";
                    $sqlFinOferta = $this->intelcost->prepareStatementQuery('cliente', $queryFinOferta, 'update', false, "", "", "Cierre actual evento.");
                    if($sqlFinOferta->bool){
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg = $arrayRespuesta;
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se presentó un error al finalizar el actual proceso/evento ".$dataOferta->seq_id.".";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Se presentó un error al crear la ronda.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No es posible crear una nueva ronda sobre este proceso/evento, ya que se encuentra en estado ".$dataOferta->estado.".";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al obtener la información del evento. ".$objOferta->msg;
        }
        return $this->intelcost->response;
    }

    private function obtenerSecuenciaMetro($oferta){
        if(isset($oferta)){
            if($oferta->tipooferta=="estudio"){
                if(isset($oferta->requisicion_id) && $oferta->requisicion_id != ""){
                    $sql = "SELECT id FROM ofertas WHERE id_requisicion= ? and estado!='CANCELADA' and estado!='DESIERTA' and estado!='LIBERADO'";
                    $result = $this->intelcost->prepareStatementQuery('cliente', $sql, 'select', true, "i", array((int) $oferta->requisicion_id),"Consultar si existe una solicitud enlazada a un estudio");

                    if($result->bool){
                        if($result->msg->num_rows == 0){
                            $seq_id_glob =substr($oferta->requisicion_seq_id, 0, -1);
                            $seq_id_glob =  $seq_id_glob."E";
                        }else{
                            return false;   
                        }

                    }else{
                        return false;
                    }
                    
                }else{
                    $Sqlofe = "SELECT count(id) cont FROM `ofertas` WHERE tipo='estudio' AND id_requisicion ='' ";
                    $sqlOfeEstudiosNoVincu = $this->intelcost->prepareStatementQuery('cliente', $Sqlofe, 'select', false, null,null, "Obtener cant estudios no vinculantes.");
                    if($sqlOfeEstudiosNoVincu->bool){
                        if($sqlOfeEstudiosNoVincu->msg->num_rows > 0){
                             $ContEstudiosNoVincu = $sqlOfeEstudiosNoVincu->msg->fetch_assoc();
                             $cont = $ContEstudiosNoVincu["cont"];
                        }else{
                            $cont = 0;
                        }
                    }
                    $seq_id_glob =  "00000".$cont."ET";
                }
            }else if($oferta->tipooferta=="estudio_existente"){ 
                // En caso de que sea un estudio que ya existe y no tenga requisicion enlazada
                if(isset($oferta->seq_requisicion) && $oferta->seq_requisicion != ""){
                    $seq_id_glob =substr($oferta->seq_requisicion, 0, -1);
                    // $seq_id_glob =   $seq_id_glob."ET";
                    $seq_id_glob =  $seq_id_glob."E";
                }else{
                    $Sqlofe = "SELECT count(id) cont FROM `ofertas` WHERE tipo='estudio' AND id_requisicion ='' ";
                    $sqlOfeEstudiosNoVincu = $this->intelcost->prepareStatementQuery('cliente', $Sqlofe, 'select', false, null,null, "Obtener cant estudios no vinculantes.");
                    if($sqlOfeEstudiosNoVincu->bool){
                        if($sqlOfeEstudiosNoVincu->msg->num_rows > 0){
                             $ContEstudiosNoVincu = $sqlOfeEstudiosNoVincu->msg->fetch_assoc();
                             $cont = $ContEstudiosNoVincu["cont"];
                        }else{
                            $cont = 0;
                        }
                    }
                    $seq_id_glob =  "00000".$cont."ET";
                }
            }else{
                if(isset($oferta->aoc_id) && $oferta->aoc_id != ""){
                    
                    $sql = "SELECT id FROM ofertas WHERE id_aoc= ? and estado!='CANCELADA' and estado!='DESIERTA'and estado!='LIBERADO' ";
                    $result = $this->intelcost->prepareStatementQuery('cliente', $sql, 'select', true, "i", array((int) $oferta->aoc_id),"Consultar si existe un aoc enlazada a un rfq");

                    if($result->bool){

                        if($result->msg->num_rows == 0){
                            $seq_id_glob =substr($oferta->aoc_seq_id, 0, -1);
                            $seq_id_glob =  $seq_id_glob."R";
                        }else{
                            return false;   
                        }

                    }else{
                        return false;
                    }
                    
                }else{
                    $seq_id_glob =  "TEMPORALR";
                    //return false;
                }
                
            }
            return $seq_id_glob;
        }else{
            return false;
        }
    }

    private function generarCodigoTerpel($modalidad, $id_empresa = 14){
        $queryPrefijo = "SELECT MC.prefijo_criterio as prefijo, MC.requiere_aprobacion as aprobacion FROM maestras AS M INNER JOIN maestras_criterios AS MC ON MC.maestra_id = M.id WHERE MC.id = $modalidad AND M.cliente_id = $id_empresa";
        $sqlPrefijo = $this->intelcost->prepareStatementQuery("cliente", $queryPrefijo, "select", false, "", "", "Consultar prefijo código terpel");
        if($sqlPrefijo->bool){
            if($sqlPrefijo->msg->num_rows > 0){
                $resPrefijo = $sqlPrefijo->msg->fetch_assoc();
                $prefigo = $resPrefijo['prefijo'];
                $aprobacion = $resPrefijo['aprobacion'];
                //$queryConsecutivo = "SELECT count(id) + 1 as consecutivo FROM ofertas WHERE id_cliente = 14 AND estado != 'INACTIVO'";
                $queryConsecutivo = "SELECT count(consecutivo) + 1 as consecutivo FROM ( SELECT count(seq_id) AS consecutivo FROM ofertas WHERE id_cliente = $id_empresa AND estado != 'INACTIVO' GROUP BY seq_id) as count";
                $sqlConsecutivo = $this->intelcost->prepareStatementQuery("cliente", $queryConsecutivo, "select", false, "", "", "generar consecutivo código terpel");
                if($sqlConsecutivo->bool){
                    $resConsecutivo = $sqlConsecutivo->msg->fetch_assoc();
                    $consecutivo = str_pad($resConsecutivo['consecutivo'], 5, "0", STR_PAD_LEFT);
                    $anio = date("Y");
                    $codigoTerpel = $prefigo."-".$consecutivo."-$anio";
                    return $codigoTerpel;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    private function generarCodigoVanti($modalidad=295, $id_empresa = 27,$objOferta){
        if($objOferta->tipooferta == 'estudio'){
            // Sólo para estudio de mercado
            // Diseño - RFI-00001-2020
            // Nuevo diseño 2020RFI00001
            $prefigo = 'RFI';
            //$queryConsecutivo = "SELECT count(id) + 1 as consecutivo FROM ofertas WHERE id_cliente = 14 AND estado != 'INACTIVO'";
            $queryConsecutivo = "SELECT count(consecutivo) + 1 as consecutivo FROM ( SELECT count(seq_id) AS consecutivo FROM ofertas WHERE id_cliente = $id_empresa AND estado != 'INACTIVO' AND tipo = 'estudio' GROUP BY seq_id) as count";
            $sqlConsecutivo = $this->intelcost->prepareStatementQuery("cliente", $queryConsecutivo, "select", false, "", "", "generar consecutivo código vanti");
            if($sqlConsecutivo->bool){
                $resConsecutivo = $sqlConsecutivo->msg->fetch_assoc();
                $consecutivo = str_pad($resConsecutivo['consecutivo'], 4, "0", STR_PAD_LEFT);
                $anio = date("Y");
                //$codigoVanti = $prefigo."-".$consecutivo."-$anio";
                $codigoVanti = $anio.$prefigo.$consecutivo;
                return $codigoVanti;
            }else{
                return false;
            }
        }else{
            // Diferente a estudio
            // Diseño - 2020000000001
            $actividad_desc = $this->obtenerNombreActividad($objOferta->actividad_id);
            $actividad = substr($actividad_desc,0,3);
            $anio = substr(date("Y"),2);
            //$queryConsecutivo = "SELECT count(id) + 1 as consecutivo FROM ofertas WHERE id_cliente = 14 AND estado != 'INACTIVO'";
            $queryConsecutivo = "SELECT count(consecutivo) + 201 as consecutivo FROM ( SELECT count(seq_id) AS consecutivo FROM ofertas WHERE id_cliente = $id_empresa AND estado != 'INACTIVO' AND (tipo = 'cerrada' OR tipo = 'rfq') GROUP BY seq_id) as count";
            $sqlConsecutivo = $this->intelcost->prepareStatementQuery("cliente", $queryConsecutivo, "select", false, "", "", "generar consecutivo código terpel");
            if($sqlConsecutivo->bool){
                $resConsecutivo = $sqlConsecutivo->msg->fetch_assoc();
                $consecutivo = str_pad($resConsecutivo['consecutivo'], 4, "0", STR_PAD_LEFT);
                $codigoVanti = $anio.$actividad.$consecutivo;
                return $codigoVanti;
            }else{
                return false;
            }

        }    
    }

    private function generarCodigoAlqueria($empresaid, $objOferta, $id = null){
        /** 
         * Cuando tenga una solicitud y sea Stock la estructura de la oferta debe ser la siguiente:
         * 
         * xxxxxxO <- es la misma que se maneja de una solicitud pero se remueve la letra y se reemplaza por la "O" de ofertas. 
         */
        switch($objOferta->tipooferta){
            case 'estudio':
                $letra = "E";
            break;
            default:
                $letra = "R";
            break;
        }
        
        if(
            !empty($_POST['solicitud_asociada']) || !empty($_POST['data']['solicitud_asociada'])
        ){
            $solicitud = $_POST['solicitud_asociada'] ?? $_POST['data']['solicitud_asociada']; 
            $obtenerSolicitud = Solicitud::find($solicitud);
            if($obtenerSolicitud){
                $secuencial = $obtenerSolicitud->toArray()['seq_id'];
                $secuencial = substr($secuencial, 0, strpos($secuencial, "S"));
                return $secuencial.$letra;
            }else{
                return false;
            }
        }else{
            /**
             * Cuando el evento cualquiera sea non-stock y no tenga una solicitud asociada, se deberá tomar con el secuencial de Vanti
             */
   
            $tipo_oferta = $objOferta->tipooferta;
            $ofertasEncontradas = Oferta::where('tipo', $tipo_oferta)
                                        ->where('estado', '!=', 'INACTIVO')
                                        ->where('id_cliente', $empresaid);
            if(!empty($id)){
                $ofertasEncontradas = $ofertasEncontradas->get()->search(function($oferta) use ($id) {
                    return $oferta->id == $id;
                });
            }else{
                $ofertasEncontradas = $ofertasEncontradas->count();
            }
            $consecutivo = str_pad($ofertasEncontradas+1, 6, "0", STR_PAD_LEFT);
            return $consecutivo.$letra;
        }
    }

    private function generarCodigoOcensa($empresaid, $objOferta, $id = null){
        /** 
         * Cuando tenga una solicitud y sea Stock la estructura de la oferta debe ser la siguiente:
         * 
         * xxxxxxO <- es la misma que se maneja de una solicitud pero se remueve la letra y se reemplaza por la "O" de ofertas. 
         */
        switch($objOferta->tipooferta){
            case 'convenio':
                $letra = "V";
            break;
            case 'estudio':
                $letra = "E";
            break;
            default:
                $letra = "R";
            break;
        }
        
        if(
            (!empty($_POST['solicitud_asociada']) || !empty($_POST['data']['solicitud_asociada']))
        ){
            $solicitud = $_POST['solicitud_asociada'] ?? $_POST['data']['solicitud_asociada']; 
            $obtenerSolicitud = Solicitud::find($solicitud);
            if($obtenerSolicitud){
                $secuencial = $obtenerSolicitud->toArray()['seq_id'];
                $secuencial = substr($secuencial, 0, strpos($secuencial, "S"));
                return $secuencial.$letra;
            }else{
                return false;
            }
        }else{
            /**
             * Cuando el evento cualquiera sea non-stock y no tenga una solicitud asociada, se deberá tomar con el secuencial de Vanti
             */
   
            $tipo_oferta = $objOferta->tipooferta;
            $ofertasEncontradas = Oferta::where('tipo', $tipo_oferta)
                                        ->where('estado', '!=', 'INACTIVO')
                                        ->where('ronda', 1)
                                        ->where(function($query){
                                            $query->whereHas('infoAdicionalesOferta', function($query) {
                                                $query->where(function($query){
                                                        $query->where('estado', '!=', 'activo')
                                                            ->orWhere('solicitud_id');
                                                    });
                                            });
                                            $query->orWhereDoesntHave('infoAdicionalesOferta');
                                        })
                                        ->where('id_cliente', $empresaid)
                                        ->get();
            if($id){
                $posicion = 0;
                $ofertasEncontradas->filter(function($oferta, $iterador) use ($id, &$posicion){
                    if($oferta->id == $id){
                        $posicion = $iterador+1;
                        return $posicion;    
                    }
                });
                
                $consecutivo = str_pad($posicion, 6, "0", STR_PAD_LEFT);

                $secuencialEncontrado = $ofertasEncontradas->filter(function($oferta) use ($consecutivo, $letra, $id){
                    if($oferta->seq_id == "$consecutivo$letra" && $oferta->id != $id){
                        return $oferta;
                    }
                });
                
                if($secuencialEncontrado->count() > 0){
                    $consecutivo = str_pad($ofertasEncontradas->count()+1, 6, "0", STR_PAD_LEFT);
                }else{
                    if($consecutivo == '000000'){
                        $consecutivo = str_pad($ofertasEncontradas->count()+1, 6, "0", STR_PAD_LEFT);
                    }
                }
            }else{
                $ofertasEncontradas = $ofertasEncontradas->count()+1;
                $consecutivo = str_pad($ofertasEncontradas, 6, "0", STR_PAD_LEFT);
            }

            return $consecutivo.$letra;
        }
    }

    private function generarCodigoConfa($prefijo, $empresaid){
        // $queryPrefijo = "SELECT MC.prefijo_criterio as prefijo, MC.requiere_aprobacion as aprobacion FROM maestras AS M INNER JOIN maestras_criterios AS MC ON MC.maestra_id = M.id WHERE MC.id = $modalidad AND M.cliente_id = $empresaid";
        // $sqlPrefijo = $this->intelcost->prepareStatementQuery("cliente", $queryPrefijo, "select", false, "", "", "Consultar prefijo código confa");
        // if($sqlPrefijo->bool){
        //     if($sqlPrefijo->msg->num_rows > 0){
                
        //     }else{
        //         return false;
        //     }
        // }else{
        //     return false;
        // }
        $prefigo = $prefijo;
        $aprobacion = $resPrefijo['aprobacion'];
        //$queryConsecutivo = "SELECT count(id) + 1 as consecutivo FROM ofertas WHERE id_cliente = 14 AND estado != 'INACTIVO'";
        $queryConsecutivo = "SELECT count(consecutivo) + 1 as consecutivo FROM ( SELECT count(seq_id) AS consecutivo FROM ofertas WHERE id_cliente = $empresaid AND estado != 'INACTIVO' GROUP BY seq_id) as count";
        $sqlConsecutivo = $this->intelcost->prepareStatementQuery("cliente", $queryConsecutivo, "select", false, "", "", "generar consecutivo código terpel");
        if($sqlConsecutivo->bool){
            $resConsecutivo = $sqlConsecutivo->msg->fetch_assoc();
            $consecutivo = str_pad($resConsecutivo['consecutivo'], 5, "0", STR_PAD_LEFT);
            $anio = date("Y");
            $codigoTerpel = $prefigo."-".$consecutivo."-$anio";
            return $codigoTerpel;
        }else{
            return false;
        }
    }

    private function obtenerSiguienteScuenciaId(){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $SqlOfer = 'SELECT MAX(CAST(seq_id AS UNSIGNED)) seq_id FROM ofertas WHERE id_cliente ="'.$_SESSION["empresaid"].'"';
            $CscLastId=$dbConection->query($SqlOfer);
            if($CscLastId){
                $lastId = $CscLastId->fetch_assoc();
                return intval ($lastId["seq_id"])+1;    
            }else{
                return false;
            }
            $dbConection->close();
        }else{
            return false;
        }
    }

    public function encontrarMaestras($ofModelo){
        $maestras = [];
        foreach ($ofModelo as $key => $oferta_valor) {
            if (strpos($key, 'maestra') !== false) {
                if (!empty($oferta_valor)) {
                    array_push($maestras, $oferta_valor);
                }
            }
        }
        return $maestras;
    }

    public function editarOferta($id, $ofModelo){
        $valides = $this->validarOferta(json_encode($ofModelo),'edicion');
        if($valides->bool){
            if(isset($_SESSION["idusuario"])){
                $modificaciones = $this->compararActualizacionOferta($id, $ofModelo);
                $jsonModificaciones = $modificaciones->msg;
                if( !$modificaciones->bool && $modificaciones->msg == "EN EVALUACION"){
                    $banderaModificaciones = $modificaciones->bool;
                    $msgModificaciones = $modificaciones->msg;
                
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="La oferta , ya está en proceso de evaluación";
                }else{
                    if($_SESSION['empresaid'] == 20 && $modificaciones->bool){
                        $jsonModificaciones = collect(json_decode($modificaciones->msg));
                        $banderaModificacionesCierre = false;
                        if($jsonModificaciones->where('titulo', "Fecha Cierre")->where('estado', "PUBLICADA")->count() > 0){
                            $banderaModificacionesCierre = true;
                        }

                        if($jsonModificaciones->where('titulo', "Hora Cierre")->where('estado', "PUBLICADA")->count() > 0){
                            $banderaModificacionesCierre = true;
                        }
                        
                        if($banderaModificacionesCierre){
                            OfertaLogEventos::create([
                                'id_oferta' => $id,
                                'id_usuario_responsable' => $_SESSION['idusuario'],
                                'id_cliente' => $_SESSION['empresaid'],
                                'tipo_evento' => 'Aumento de tiempo',
                                'mensaje' => $_POST['razones_oferta'],
                                'estado' => 'Activo',
                            ]);
                        }
                    }

                    if($_SESSION['empresaid'] == 20){
                        if($ofModelo->tipooferta != 'estudio'){
                            $solpeds = OfertaLote::where('cod_oferta', $id)->with('obtenerItems.obtenerInformacionAdicionalItem')->get()->map(function($lote, $iterador){
                                foreach ($lote->obtenerItems as $key => $item) {
                                    $lote->documento = $item->obtenerInformacionAdicionalItem->documento;
                                }
                                return $lote->documento;
                            });
                            if($solpeds->count() > 0){
                                $ofModelo->solpedRelacionadas = implode(",", $solpeds->unique()->toArray());
                            }
                        }
                    }

                    if(!isset($ofModelo->solpedRelacionadas)){
                        $ofModelo->solpedRelacionadas = "";
                    }
                    $presupuesto = str_replace(",", "", $ofModelo->presupuesto);
                    if(!isset($ofModelo->maestra2)){
                        $ofModelo->maestra2 = '';
                    }

                    if(!isset($ofModelo->maestra3)){
                        $ofModelo->maestra3 = '';
                    }

                    if(!isset($ofModelo->inputsolped)){
                        $ofModelo->inputsolped = '';
                    }
                    
                    if(isset($ofModelo->oferta_autoproroga) && $ofModelo->oferta_autoproroga == "true"){
                        $ofModelo->oferta_autoproroga = 1;
                    }else{
                        $ofModelo->oferta_autoproroga = 0;
                    }

                    //Actualizar secuencial
                    if($_SESSION["empresaid"] == "20"){
                        if($ofModelo->ronda <= 1){
                            $secuencia = $this->generarCodigoOcensa($_SESSION["empresaid"], $ofModelo, $id);
                        }
                    }

                    if($_SESSION["empresaid"] == "9"){
                        if($ofModelo->ronda <= 1){
                            $secuencia = $this->generarCodigoAlqueria($_SESSION["empresaid"], $ofModelo, $id);
                        }
                    }

                    if(empty($ofModelo->fecha_msg_fecha)){
                        $ofModelo->fecha_msg_fecha = '0000-00-00';
                    }

                    
                    //$SqlOfer = 'UPDATE ofertas SET `objeto`= ?, `actividad`= ?, `descripcion`= ?, `moneda`= ?, `presupuesto`= ?, `maestra1`= ?, `maestra2`= ?, `solpeds_relacionadas`= ?, `fecha_limite_msg`= ?, `fecha_limite_restrictivo`= ?, `fecha_limite_msg_fecha`= ?, `fecha_limite_hora`= ?, `fecha_limite_msg_observacion`= ?,`id_requisicion`= ?,`id_aoc`= ?,`id_area`= ?,`modalidad_seleccion`= ?,`require_flujo`= ?,`fecha_actualizacion`= "'.date("Y-m-d H:i:s").'", `usuario_actualizacion`="'.$_SESSION["idusuario"].'"';
                    $SqlOfer = 'UPDATE ofertas SET `objeto`= ?, `actividad`= ?, `descripcion`= ?,`vigencia`= ?, `moneda`= ?, `presupuesto`= ?, `maestra1`= ?, `maestra2`= ?, `maestra3`= ?,`solpeds_relacionadas`= ?, `fecha_limite_msg`= ?, `fecha_limite_restrictivo`= ?, `fecha_limite_msg_fecha`= ?, `fecha_limite_hora`= ?, `fecha_limite_msg_observacion`= ?,`id_area`= ?,`modalidad_seleccion`= ?,`require_flujo`= ?,`fecha_actualizacion`= "'.date("Y-m-d H:i:s").'", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `autoproroga`= ? ';
                    $parametros = array($ofModelo->objeto, $ofModelo->actividad_id, $ofModelo->descripcion, $ofModelo->vigencia, $ofModelo->moneda, $presupuesto, $ofModelo->maestra1, $ofModelo->maestra2, $ofModelo->maestra3, $ofModelo->solpedRelacionadas, (int)$ofModelo->fecha_msg_check, (int)$ofModelo->limite_restrictivo_check, $ofModelo->fecha_msg_fecha, $ofModelo->fecha_limite_hora, $ofModelo->fecha_msg_observacion, $ofModelo->id_area_oferta, $ofModelo->modalidad_seleccion,$ofModelo->requiere_flujo_aprobacion,$ofModelo->oferta_autoproroga);
                    $tipoParametros = "ssssssssssiisssisii";

                    if($secuencia){
                        $SqlOfer .= ', `seq_id` = ?';
                        array_push($parametros, $secuencia);
                        $tipoParametros .= "s";
                        $ofModelo->seq_id = $secuencia;
                    }

                    if(isset($ofModelo->fecha_inicio)){
                        $SqlOfer .= ', `fecha_inicio`= ?';
                        array_push($parametros, $ofModelo->fecha_inicio);
                        $tipoParametros .= "s";
                    }
                    if(isset($ofModelo->hora_inicio)){
                        $SqlOfer .= ', `hora_inicio`= ?';
                        array_push($parametros, $ofModelo->hora_inicio);
                        $tipoParametros .= "s";
                    }
                    if(isset($ofModelo->fecha_cierre)){
                        $SqlOfer .= ', `fecha_cierre`= ?';      
                        array_push($parametros, $ofModelo->fecha_cierre);
                        $tipoParametros .= "s";
                    }
                    if(isset($ofModelo->hora_cierre)){
                        $SqlOfer .= ', `hora_cierre`= ?';   
                        array_push($parametros, $ofModelo->hora_cierre);
                        $tipoParametros .= "s";
                    }
                    $SqlOfer .= ' WHERE `id`= ?';
                    array_push($parametros, $id);
                    $tipoParametros .= "i";
                    $updateOferta = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'update', true, $tipoParametros, $parametros, "Editar oferta.");

                    if($updateOferta->bool){
                        // if($updateOferta->msg > 0){
                            if($modificaciones->bool &&  $modificaciones->msg!= "[]" &&  $jsonModificaciones!= "ACTIVO" &&  $jsonModificaciones!= "EN APROBACION" && $jsonModificaciones != "" && $jsonModificaciones != "[]"){
                                // activar sobre el server
                                $this->emailConfirmacionEdicionOferta(json_encode($ofModelo), $jsonModificaciones);
                            }

                            //Flujos de aprobacion
                            if($_SESSION["empresaid"] == "10" && $ofModelo->tipooferta == "rfq" ){
                                $this->asociarFlujosRfqMetro($ofModelo, $id,$ofModelo->requiere_flujo_aprobacion);
                                $modelo_acciones_oferta = new modelo_acciones_oferta();
                                $objCrearAccionesAdicinoales = $modelo_acciones_oferta->crearAccionesAdicionales($id, $ofModelo);
                            }

                            //Campos adicionales
                            if(
                                isset($_POST['data']['precalificacion_id']) || 
                                isset($_POST['data']['solicitud_asociada']) ||
                                isset($_POST['data']['otros']) ||
                                isset($_POST['data']['fecha_maximo_respuesta'])
                            ){
                                $datos = [
                                    'oferta_id' => $id,
                                    'precalificacion_id' => !empty($_POST['data']['precalificacion_id']) ? $_POST['data']['precalificacion_id'] : null,
                                    'solicitud_id' => !empty($_POST['data']['solicitud_asociada']) ? $_POST['data']['solicitud_asociada'] : null,
                                    'otros' => !empty($_POST['data']['otros']) ? $_POST['data']['otros'] : null,
                                    'fecha_maximo_respuesta' => !empty($_POST['data']['fecha_maximo_respuesta']) ? $_POST['data']['fecha_maximo_respuesta'] : null,
                                    'estado' => 'activo',
                                ];
        
                                $condicional = [
                                    'oferta_id' => $id
                                ];
                    
                                OfertaDatosAdicionales::updateOrCreate($condicional, $datos);
                            }else{
                                OfertaDatosAdicionales::where('oferta_id', $id)->update(['estado' => 'eliminado']);
                            }

                            if($_SESSION["empresaid"] == "27" || $_SESSION['empresaid'] == "20" ){
                                if ((int) $ofModelo->requiere_flujo_aprobacion) {
                                    $this->asociarFlujosRfqVanti($ofModelo, $id,$ofModelo->requiere_flujo_aprobacion,$ofModelo->tipooferta);
                                }else{
                                    $this->asociarFlujosRfqVanti($ofModelo, $id,$ofModelo->requiere_flujo_aprobacion,$ofModelo->tipooferta, true);
                                }
                            }
                            
                            if((int) $_SESSION["empresaid"] == 14 || (int) $_SESSION["empresaid"] == 26 || (int) $_SESSION["empresaid"] == 27 || (int) $_SESSION["empresaid"] == 20){
                                $modelo_acciones_oferta = new modelo_acciones_oferta();
                                $objCrearAccionesAdicinoales = $modelo_acciones_oferta->crearAccionesAdicionales($id, $ofModelo);
                            }

                            if(isset($ofModelo->arrMaestrasGenerales)){
                                $this->asociarMaestrasOferta($ofModelo->arrMaestrasGenerales, $id);
                            }
                            if(isset($ofModelo->sobres_oferta)){
                                $this->asociarSobreOferta($ofModelo->sobres_oferta, $id);
                            }
                            
                            if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                                if(isset($ofModelo->criterios_evaluacion)){
                                    $criteriosTecnicos = collect(json_decode($ofModelo->criterios_evaluacion));
                                    if($criteriosTecnicos->count() > 0){
                                        $this->guardarCriteriosTecnicos($criteriosTecnicos, $id);
                                    }
                                }
                            }else{
                                if(isset($ofModelo->criterios_evaluacion)){
                                    $res_criterios = $this->asociarCriterioOferta(json_decode($ofModelo->criterios_evaluacion), $id);
                                    
                                }
                            }
                            $res["LastId"] = $id;
                            $res["seqId"] = $ofModelo->seq_id;
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg = json_encode($res);

                        /*$SqlOfer .= '`fecha_limite_msg`='.$ofModelo->fecha_msg_check.', `fecha_limite_restrictivo`= "'.$ofModelo->limite_restrictivo_check.'", `fecha_limite_msg_fecha`="'.$ofModelo->fecha_msg_fecha.'", `fecha_limite_hora`="'.$ofModelo->fecha_limite_hora.'", `fecha_limite_msg_observacion`="'.$ofModelo->fecha_msg_observacion.'",`fecha_actualizacion`= "'.date("Y-m-d H:i:s").'",`usuario_actualizacion`="'.$_SESSION["idusuario"].'"'; 
                        $SqlOfer .= ' WHERE `id`="'.$id.'"';
                        
                        $CscUsr = $dbConection->query($SqlOfer);*/
                        // }else{
                        //  $this->intelcost->response->bool = false;
                        //  $this->intelcost->response->msg = "No se logró editar la oferta.";
                        // }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se ha presentado un error al editar la oferta.";
                    }
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "La sesión ha caducado.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = $valides->msg;
        }
        return $this->intelcost->response;
    }

    private function emailConfirmacionEdicionOferta($oferta,$modificacionesObj){
        //$modeloComunicaciones = new communicationClient();
        $modificaciones = json_decode($modificacionesObj);
        $ofertaobj = json_decode($oferta);
        $participantes = json_decode($ofertaobj->participantesOferta);
        
        // Generacion contenido de modificaciones
        $flag_estado_noti = false;
        $emailContentmodificaciones ="";
        foreach ($modificaciones as $modifi) {
            if($modifi->titulo == "Fecha Cierre" || $modifi->titulo == "Hora Cierre"){
                $emailContentmodificaciones .= "<div style='background: #ba819a;padding: 5px;'><b>".$modifi->titulo."</b>: ".$modifi->nuevoValor."</div>";
                $flag_estado_noti = true;
            }else{
                $emailContentmodificaciones .= "<div style='padding: 5px;'><b>".$modifi->titulo."</b>: ".$modifi->nuevoValor."</div><br />";
            }
        }
        $ofertaMod = $this->obtenerOferta($ofertaobj->idoferta);
        if($ofertaMod->bool){
            $ofertaObj = json_decode($ofertaMod->msg);
            if ($flag_estado_noti) {
                $dbConection = $this->intelcost->db->createConection("cliente");
                $queryOferta = "UPDATE ofertas SET estado_notificacion = 'recordatorio_cierre' WHERE id = ".$ofertaObj->id ;
                $updateOferta = $dbConection->query($queryOferta);  
            }

            if($ofertaObj->estado == "PUBLICADA"){
                // Generacion contenido de email de noticacion
                $subject = "La invitación al proceso - ".$ofertaObj->seq_id .' ha sido editada por el responsable.'.$ofertaobj->tipo;
                foreach($participantes as $participante){
                    $emailContent  = "Estimados(a): ".$participante->provNombre." <br /> <br />";
                    if(isset($ofertaObj->modalidad_seleccion) && $ofertaObj->modalidad_seleccion == 'sol_uni_of'){
                        $emailContent .= "La oferta (No ".$ofertaObj->seq_id.") <b>".$ofertaObj->objeto."</b>, la cual consultó, ha sido editada por el responsable. <br /><br />";
                    }else if($ofertaobj->tipooferta == "cerrada" || $ofertaobj->tipooferta == "estudio" || $ofertaobj->tipooferta == "convenio" || $ofertaobj->tipooferta == "rfq"){
                        $emailContent .= "El proceso / evento (No ".$ofertaObj->seq_id.") <b>".$ofertaObj->objeto.(($ofertaobj->id_cliente == 14 || $ofertaobj->id_cliente == 26 || $ofertaobj->id_cliente == 27 || $ofertaobj->id_cliente == 20) ? "-".$ofertaobj->ronda : "")."</b>, al cual se encuentra invitado, ha sido editado por el responsable.<br><br>";
                    }else if($ofertaobj->tipooferta == "abierta" || $ofertaobj->tipooferta == "publico"){
                        $emailContent .= "Le informamos que el proceso ".$ofertaobj->seq_id." - ".$ofertaObj->objeto." consultado por su compañía ha sido modificado.<br><br>";
                    }
                    
                    $emailContent .= "Algunos de los datos que han sido actualizados son: <br /><br />";
                    $emailContent .= $emailContentmodificaciones;
                    $emailContent .= "<br /><br />Puede acceder al sistema a través de <a href='https://www.intelcost.com/intelcost'>www.intelcost.com</a>, usando su usuario y contraseña para consultar el evento actualizado.";
                    $destinatario = $participante->email;

                    $obj_adicionales = new stdClass();
                    $obj_adicionales->relacion_id = $ofertaobj->idoferta;
                    $obj_adicionales->modulo_id = 5;

                    $comunicar = $this->modeloComunicaciones->sendEmail($destinatario,$emailContent,$subject,"ComunicadoLogoCliente", $ofertaObj->id_cliente, $obj_adicionales);
                    
                }

                //ODL y TERPEL no desean que sus usuarios internos reciban notificaciones
                if( $_SESSION["empresaid"] != 8 && $_SESSION["empresaid"] != 14 && $_SESSION["empresaid"] != 26 && $_SESSION["empresaid"] != 27 && $_SESSION["empresaid"] != 20){
                    /*NOTIFICACIÓN A USUARIOS INTERNOS*/
                    $conexion = $this->intelcost->db->createConection("cliente");

                    if($conexion){
                        $queryUsuariosInternos = "SELECT UI.id_usuario, U.nombre, U.email, U.empresa_id FROM oferta_usuarios_internos AS UI INNER JOIN usuarios AS U ON UI.id_usuario = U.id WHERE UI.id_oferta = ".$ofertaobj->idoferta." AND UI.`estado` != 'INACTIVO'";
                        $sqlUsuariosInternos = $conexion->query($queryUsuariosInternos);
                        
                        if($sqlUsuariosInternos && ($sqlUsuariosInternos->num_rows > 0)){
                            while( $usuarioInterno = $sqlUsuariosInternos->fetch_array()){

                                $destinatario = $usuarioInterno['email'];
                                $emailContent  = "Estimado(a): ".$usuarioInterno['nombre']." <br /> <br />";
                                $emailContent .= "La oferta (No ".$ofertaObj->seq_id.") <b>".$ofertaObj->objeto."</b>, a la cual se encuentra invitado como usuario interno, ha sido editada por el responsable. <br /><br />";                         
                                $emailContent .= "Algunos de los datos que han sido actualizados son: <br /><br />";
                                $emailContent .= $emailContentmodificaciones;
                                $emailContent .= "<br /><br />Puede acceder al sistema a través de <a href='https://www.intelcost.com/nuevo_cliente'>www.intelcost.com/nuevo_cliente</a>, usando su usuario y contraseña para consultar el evento actualizado.";

                                $obj_adicionales = new stdClass();
                                $obj_adicionales->relacion_id = $ofertaobj->idoferta;
                                $obj_adicionales->modulo_id = 5;

                                $comunicar = $this->modeloComunicaciones->sendEmail($destinatario,$emailContent,$subject,"ComunicadoLogoCliente", $usuarioInterno['empresa_id'],$obj_adicionales);
                            }
                        }
                    }
                }
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /*NOTIFICACION PARA TERPEL - CONCONCRETO*/
    private function notificacionSolicitudAprobacion($objOferta, $observacion){
        $objAprobadores = $this->modelo_usuario->obtenerUsuariosAprobadoresEventos($objOferta->id_cliente);
        if($objAprobadores->bool){
            $aprobadores = json_decode($objAprobadores->msg);
            
            $asunto = "Solicitud de aprobación evento - ".$objOferta->seq_id." - ".$objOferta->objeto;
            foreach ($aprobadores as $usuario) {
                $contenidoMail  = "<p><b>Estimado(a):</b> Aprobador</p><br> ";
                $contenidoMail .= "Le informamos que <b>".(($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20) ? "el equipo de compras " : $_SESSION["usuario"])."</b> ha enviado una solicitud de <b> aprobación</b> de un proceso previo a la publicación.<br><br>";
                $contenidoMail .= "<h3>Proceso: ".$objOferta->seq_id."</h3>";
                $contenidoMail .= "<div style='padding-left:10%'>";
                $contenidoMail .= "<p><b>Objeto:</b> ".$objOferta->objeto."</p>";
                $contenidoMail .= "<p><b>Descripción:</b> ".$objOferta->descripcion."</p>";
                $contenidoMail .= "</div><br>";
                if(!empty($observacion)){
                    $contenidoMail .= "<br><b>Observaciones:</b> <br>$observacion";
                }
                $contenidoMail .= "<p>Link de acceso: <a href='https://".$_SESSION["url"]."'>".$_SESSION["url"]."</a></p>";
                $contenidoMail .= "<p style='font-size:12px;margin-top:50px;'>Para asegurar un correcto uso de la herramienta puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico <a href='mailto:soporte@intelcost.com' target='_blank'> soporte@intelcost.com</a>. Adicionalmente, una vez ingrese al sistema podrá solicitar soporte a través de nuestro chat en vivo.</p>";
                $obj_adicionales = new stdClass();
                $obj_adicionales->relacion_id = isset($ofertaobj->idoferta) ? $ofertaobj->idoferta : isset($ofertaobj->id) ? $ofertaobj->id : null;
                $obj_adicionales->modulo_id = 5;

                $email = $this->modeloComunicaciones->sendEmail($usuario->email, $contenidoMail, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"],$obj_adicionales);
            }
            return $email;
        }else{
            return false;
        }
    }
    
    /*NOTIFICACION PARA TERPEL*/
    public function notifcacionAprobacionEvento($objOferta, $estadoAprobacion, $observacion){
        $asunto = "";
        $msgAprobacion = "";
        if($estadoAprobacion == "APROBADA"){
            $asunto = "Aprobación evento - ".$objOferta->seq_id." - ".$objOferta->objeto;
            $msgAprobacion = "<b style='color: #018501'>APROBADO</b>";
        }else if($estadoAprobacion == "RECHAZADA"){
            $asunto = "Evento rechazado - ".$objOferta->seq_id." - ".$objOferta->objeto;
            $msgAprobacion = "<b style='color: #B20202'>RECHAZADO</b>";
        }
        $emailContent  = "<p>Estimado(a): <b>".(($objOferta->id_cliente == 14 || $objOferta->id_cliente == 26 || $objOferta->id_cliente == 27 || $objOferta->id_cliente == 20) ? "Equipo de compras.": $objOferta->usuario_creador)."</b> </p><br> ";
        $emailContent .= "Le informamos que el proceso <b>".$objOferta->seq_id."</b> ha sido $msgAprobacion por parte de ".(($objOferta->id_cliente == 14 || $objOferta->id_cliente == 26 || $objOferta->id_cliente == 27 || $objOferta->id_cliente == 20) ? " la dirección de compras" : $_SESSION["usuario"]).".";
        if(!empty($observacion)){
            $emailContent .= "<br><br><b>Observaciones:</b> <br>$observacion";
        }
        $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Para asegurar un correcto uso de la herramienta puede consultar el <a href='http://www.intelcost.com/manual.pdf' target='_blank' style='text-decoration:none'>manual de usuario</a> o puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico <a  href='mailto:soporte@intelcost.com' target='_blank'>soporte@intelcost.com</a>. Adicionalmente una vez ingrese al sistema podrá pedir soporte a trav&eacute;s de nuestro chat en vivo.</p>";
        $obj_adicionales = new stdClass();
        $obj_adicionales->relacion_id = isset($ofertaobj->idoferta) ? $ofertaobj->idoferta : isset($ofertaobj->id) ? $ofertaobj->id : null;
        $obj_adicionales->modulo_id = 5;
        $email = $this->modeloComunicaciones->sendEmail($objOferta->usuario_creador_email, $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
        return $email;
    }

    public function crearArregloDocumentoOferta2($oferta, $arregloItems){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $arregloItems = json_decode($arregloItems); 
            $documentos_insertados = 0;
            $documentos_insertados_error = 0;
            $documentos_actualizados = 0;
            $documentos_actualizados_error = 0;
            foreach($arregloItems as $item){
                if(!isset($item->valor)){
                    $item->valor = "";
                }
                if(!isset($item->posicion)){
                    $item->posicion = 0;
                }
                if(isset($item->contenido)){
                    $item->valor = $item->contenido;
                }
                if(!isset($item->descripcion)){
                    $item->descripcion = "";
                }
                if(!isset($item->titulo)){
                    $item->titulo = "";
                }
                if(!isset($item->tipo)){
                    $item->tipo = "archivo";
                }else if($item->tipo == "Archivo"){
                    $item->tipo = "archivo";
                }

                $id_documento = '';
                // Si no existe lo creamos
                if(!isset($item->id_documento) || $item->id_documento == undefined){
                    $SqlOfer = "INSERT INTO oferta_documentos ( `contenido`, `id_oferta`,`seq_id`, `titulo`, `tipo`, `usuario_actualizacion`, `descripcion`) VALUES ( ?, ?, ?, ?, ?, $_SESSION[idusuario], ?)";
                    $parametros = array($item->valor, (int)$oferta, (int)$item->posicion, $item->titulo, $item->tipo, $item->descripcion);
                    $tipoParametros = "siisss";
                    $sqlCreaDoc = $this->intelcost->prepareStatementQuery("cliente", $SqlOfer, "insert", true, $tipoParametros, $parametros, "Crear documento en oferta 2.");
                    if($sqlCreaDoc->bool){
                        $id_documento = $sqlCreaDoc->msg;
                        $documentos_insertados++;
                    }else{
                        $documentos_insertados_error++;
                    }
                }else{
                    // Si existe lo actualizamos
                    $SqlUpdateDocsOfer = 'UPDATE oferta_documentos SET `contenido` = ? , `seq_id` = ?, `titulo` = ?, `tipo` = ?,`fecha_actualizacion` = ?,`usuario_actualizacion` = ?, `descripcion` = ? WHERE id=?';
                    $parametros = array($item->valor, (int)$item->posicion, $item->titulo, $item->tipo, date("Y-m-d"), (int)$_SESSION["idusuario"], $item->descripcion, $item->id_documento);
                    $tipoParametros = "sisssisi";

                    $sqlUpdaDoc = $this->intelcost->prepareStatementQuery("cliente", $SqlUpdateDocsOfer, "update", true, $tipoParametros, $parametros, "actualizar documento en oferta 2.");
                    if($sqlUpdaDoc->bool){
                        $id_documento = $item->id_documento;
                        $documentos_actualizados++;
                    }else{
                        $documentos_actualizados_error++;
                    }
                }//Fin Else de existencia

                if (!empty($_SESSION['modulos_personalizados']) && array_search("15", array_column($_SESSION['modulos_personalizados'], 'cod_modulo_personalizado')) !== false){
                    $relacion_categorias_data = [
                        'id_item_oferta_documento' => $id_documento,
                        'ids_categorias' => $item->categorias
                    ];

                    if (!isset($item->id_documento) || $item->id_documento == undefined) {
                        $relacion_categorias_data['created_at'] = date('Y-m-d H:i:s');
                    }

                    $relacion_categorias_condicional = [
                        'id_item_oferta_documento' => $id_documento,
                        'estado' => 'activo'
                    ];

                    RelacionOfertaDocumentosCategorias::updateOrCreate($relacion_categorias_condicional, $relacion_categorias_data);
                }
            }//Fin foreach que recorre el arreglo de documentos
            $this->intelcost->response->bool = true ;
            $this->intelcost->response->msg ='Finalizo con documentos_insertados'.$documentos_insertados.' Documentos_insertados_error'.$Documentos_insertados_error.' Documentos_actualizados'.$documentos_actualizados.' Documentos_actualizados_error'.$documentos_actualizados_error;
            
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="DB creacion erronea";
        }
        return $this->intelcost->response;
    }

    public function crearDocumentoOferta2($oferta, $item){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            
            if(!isset($item["valor"])){
                $item["valor"] = "";
            }
            if(isset($item["contenido"])){
                $item["valor"] = $item["contenido"];
            }
            if(!isset($item["titulo"])){
                $item["titulo"] = "";
            }
            if(!isset($item["tipo"])){
                $item["tipo"] = "archivo";
            }
            
            $SqlOfer = "INSERT INTO oferta_documentos ( `contenido`, `id_oferta`, `titulo`, `tipo`, usuario_actualizacion) VALUES ( ?, ?, ?, ?, $_SESSION[idusuario])";
            $parametros = array($item["valor"], (int)$oferta, $item["titulo"], $item["tipo"]);
            $tipoParametros = "siss";

            $sqlCreaDoc = $this->intelcost->prepareStatementQuery("cliente", $SqlOfer, "insert", true, $tipoParametros, $parametros, "Crear documento en oferta 2.");
            if($sqlCreaDoc->bool){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "documento relacionado a oferta";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="documento creacion erronea";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="DB creacion erronea";
        }
        return $this->intelcost->response;
    }

    public function crearDocumentoOferta($oferta, $ruta, $nombre, $titulo, $idDocumento){
        $query = "";
        $tipoQuery = "";
        if($idDocumento == ""){
            $query = "INSERT INTO oferta_documentos ( `ruta`, `id_oferta`, `titulo`, `usuario_actualizacion`) VALUES (?, ?, ?, $_SESSION[idusuario])";
            $parametros = array($ruta, (int)$oferta, $titulo);
            $tipoParametros = "sis";
            $tipoQuery = "INSERT";
        }else{
            $query = "UPDATE oferta_documentos SET `ruta` = ?, `titulo` = ?, `usuario_actualizacion` = $_SESSION[idusuario], fecha_actualizacion = ? WHERE id = ? ";
            $parametros = array($ruta, $titulo, (int)$idDocumento, date("Y-m-d"));
            $tipoParametros = "sssi";
            $tipoQuery = "UPDATE";
        }
        $sqlguardarDocumento = $this->intelcost->prepareStatementQuery("cliente", $query, $tipoQuery, true, $tipoParametros, $parametros, "Crear documento en oferta.");
        if($sqlguardarDocumento->bool){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "documento relacionado a oferta";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "creación del documento erronea.";
        }
        return $this->intelcost->response;
    }

    public function crearCriteriosDocumentoOferente2($criterios, $id){
        try {
            $criterios = json_decode($criterios->criterios);
        } catch (\Throwable $th) {
            //throw $th;
        }

        OfertaDocumentosCriteriosOferentes::where('id_item_documento', $id)->update(['estado' => 'Eliminado']);

        foreach ($criterios as $key => $criterio) {
            $condicional = [
				'id' => $criterio->id,
			];

			$arreglo = [
				'id_item_documento' => $id,
				'id_usuario_creador' => $_SESSION['idusuario'],
				'criterio' => $criterio->nombre != '' ? $criterio->nombre : $criterio->criterio,
				'otros' => json_encode($criterio->otros),
				'tipo' => $criterio->tipo,
				'estado' => 'Activo',
            ];
            
            OfertaDocumentosCriteriosOferentes::updateOrCreate($condicional, $arreglo);
        }
    }

    public function crearDocumentoOfererente2($id_oferta, $doc_sol_oferente){
        if($doc_sol_oferente->obliga == "si" || $doc_sol_oferente->obliga == "SI" || $doc_sol_oferente->obliga == "1"){
            $doc_sol_oferente->obliga = 1;
        }else{
            $doc_sol_oferente->obliga = 0;
        }
        if(isset($doc_sol_oferente->parametros_evaluacion) && !empty($doc_sol_oferente->parametros_evaluacion)){
            $doc_sol_oferente->parametros_evaluacion = json_encode($doc_sol_oferente->parametros_evaluacion);
        }
        if(!$doc_sol_oferente->existente){
            $parametros = array($doc_sol_oferente->titulo, $doc_sol_oferente->tipo, $doc_sol_oferente->contenido, (int) $doc_sol_oferente->obliga, (int) $id_oferta, $doc_sol_oferente->evaluable, (int) $doc_sol_oferente->tipo_evaluacion, $doc_sol_oferente->parametros_evaluacion);
            $tipoParametros = "sssiisis";
            if($doc_sol_oferente->sobre != "" || $doc_sol_oferente->sobre != null){
                $SqlOfer = "INSERT INTO oferta_documentos_oferentes (`titulo`, `tipo`,`contenido`, `obligatorio`, `oferta_id`, `evaluable`, `tipo_evaluacion`, `parametro_evaluacion`, `sobre`, `usuario_actualizacion`) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, $_SESSION[idusuario])";
                array_push($parametros, (int) $doc_sol_oferente->sobre);
                $tipoParametros .= "i";
            }else{
                $SqlOfer = "INSERT INTO oferta_documentos_oferentes (`titulo`, `tipo`,`contenido`, `obligatorio`, `oferta_id`, `evaluable`, `tipo_evaluacion`, `parametro_evaluacion`, `usuario_actualizacion`) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, $_SESSION[idusuario])";
            }
            $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'INSERT', true, $tipoParametros, $parametros, "Crear documento oferente 2.");
            if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                $this->crearCriteriosDocumentoOferente2($doc_sol_oferente, $CscUsr->msg);
            }
        }else{
            if($doc_sol_oferente->docId != ""){
                $SqlOfer = "UPDATE oferta_documentos_oferentes SET `titulo` = ?, `tipo` = ?, `contenido` = ?, `obligatorio` = ?, `evaluable` = ?, `tipo_evaluacion` = ?, `parametro_evaluacion` = ?, usuario_actualizacion = $_SESSION[idusuario], fecha_actualizacion = ? WHERE `id` = ? AND oferta_id = ?";
                $parametros = array($doc_sol_oferente->titulo, $doc_sol_oferente->tipo, $doc_sol_oferente->contenido, $doc_sol_oferente->obliga, $doc_sol_oferente->evaluable, (int) $doc_sol_oferente->tipo_evaluacion, $doc_sol_oferente->parametros_evaluacion, date("Y-m-d"), (int) $doc_sol_oferente->docId, (int) $id_oferta);
                $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "sssisissii", $parametros, "Crear documento oferente 3.");
                if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                    $this->crearCriteriosDocumentoOferente2($doc_sol_oferente, $doc_sol_oferente->docId);
                }
            }else{
                $CscUsr->bool = false;
            }
        }
        if($CscUsr->bool){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "documento ".htmlentities($doc_sol_oferente->titulo)." relacionado a oferta";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="documento creacion erronea. 2909f";
        }
        return $this->intelcost->response;
    }

    public function crearDocumentoOfererente($oferta, $docid, $titulo, $descripcion, $obliga, $existente, $sobre){
        $SqlOfer = null;
        $tipoquery = "";
        if(!$existente || $existente=="false"){
            if($sobre!=""||$sobre!=null){
                $SqlOfer = "INSERT INTO oferta_documentos_oferentes ( `doc_id`, `titulo`, `descripcion`, `obligatorio`, `oferta_id`, `sobre`, `usuario_actualizacion`) VALUES ( ?,  ?, ?, ?, ?, ?, $_SESSION[idusuario])";
                $parametros = array($docid, $titulo, $descripcion, (int)$obliga, (int)$oferta, $sobre);
                $tipoParametros = "sssiis";
            }else{
                $SqlOfer = "INSERT INTO oferta_documentos_oferentes ( `doc_id`, `titulo`, `descripcion`, `obligatorio`, `oferta_id`, `usuario_actualizacion`) VALUES ( ?, ?, ?, ?, ?, $_SESSION[idusuario])";
                $parametros = array($docid, $titulo, $descripcion, (int)$obliga, (int)$oferta);
                $tipoParametros = "sssii";
            }
            $tipoquery = "INSERT";
        }else{
            if($docid != ""){
                
                $SqlOfer = "UPDATE oferta_documentos_oferentes SET  `titulo` = ?, `descripcion` = ?, `obligatorio` = ?, usuario_actualizacion = $_SESSION[idusuario], fecha_actualizacion = ? WHERE `doc_id` = ? AND oferta_id = ?";
                $parametros = array($titulo, $descripcion, (int)$obliga, date("Y-m-d"), $docid, (int)$oferta);
                $tipoParametros = "ssissi";
            }
            $tipoquery = "UPDATE";
        }
        if($SqlOfer != null){
            $crearDocOfe = $this->intelcost->prepareStatementQuery("cliente", $SqlOfer, $tipoquery, true, $tipoParametros, $parametros, "Crear documento oferente en oferta.");
            if($crearDocOfe->bool){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "documento relacionado a oferta";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="documento creacion erronea 2944.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="documento creacion erronea 2948.";
        }
        return $this->intelcost->response;
    }

    public function eliminarParticipantesOferta($idoferta){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $idoferta = $this->intelcost->realEscapeStringData($idoferta);
            $SqlEliminarParticipantes = 'UPDATE  oferta_participantes SET estado = "eliminado" ,`usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id_oferta` = "'.$idoferta.'"';
            //$CscUsr=mysqli_query($SqlEliminarParticipantes,$dbConection);
            $CscUsr = true;
            if($CscUsr){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Participantes desvinculados de oferta";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Participantes eliminacion erronea";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    public function generarCartaInvitacionOcensa($idParticipante, $idOferta){
        $participante = OfertaParticipantes::where('id', $idParticipante)->with('infoEmpresa')->first();
        $objOferta = Oferta::where('id', $idOferta)
                        ->with(['visitasObra' => function($query){
                            $query->where('tipo', 'reunion');
                        }])
                        ->with('infoUsuarioCreacion')
                        ->first();
        $jefeOficina = Usuario::where('id_perfil', 87)->first();
        $html = $this->getViewEmail('html_carta_invitacion_ocensa', [
            'participante' => $participante,
            'oferta' => $objOferta,
            'jefeOficina' => $jefeOficina,
        ], [
            'renameVariable' => true, 
            'nameNewVariable' => "datos"
        ]);

        $rutaInvitacion = '../ic_files/ic_ofertas/'.$_SESSION['empresaid'].'/';
        if (!is_dir($rutaInvitacion)) {
            mkdir($rutaInvitacion);         
        }
        $nombreInvitacion = "Carta de invitacion - ".$objOferta->seq_id." - ".uniqid().".pdf";

        if($this->modelo_pdf->generar_pdf($html, $rutaInvitacion, $nombreInvitacion)){
            $participante->carta_invitacion = $rutaInvitacion.$nombreInvitacion;
            $participante->timestamps = false;
            $participante->save();
        }
    }

    public function creaParticipanteOferta($idoferta, $idParticipante, $nombreParticipante, $provid, $email){
        if($idParticipante == null || $idParticipante == "" || $idParticipante == 0){
            $objCrearUsuario = $this->modelo_usuario->crearUsuarioIntelcost($nombreParticipante, $email, $provid, $_SESSION["empresaid"]);
        }

        $objUsuIntelcost = $this->modelo_proveedor->obtenerContantoEmpresaPorCorreo($email);
        if($objUsuIntelcost->bool){
            $usuarioIntelcost = json_decode($objUsuIntelcost->msg);
            $idParticipante = $usuarioIntelcost->usridxxx;
            $provid = $usuarioIntelcost->nit;
            $nombreParticipante = $usuarioIntelcost->nombre;

            $SqlOferSear = 'SELECT count(id) as cont FROM oferta_participantes WHERE `id_usuario` = ? AND `id_oferta` = ? AND estado != "eliminado"';
            $parametrosVal = array((int) $idParticipante, (int) $idoferta);
            $validarParticipante = $this->intelcost->prepareStatementQuery("cliente", $SqlOferSear, "select", true, "ii", $parametrosVal, "Validar usuario participantes.");
            if($validarParticipante->bool){
                $conSearch = $validarParticipante->msg->fetch_assoc();
                if((int) $conSearch["cont"] == 0){
                    
                    $SqlOfer = 'INSERT INTO oferta_participantes ( `id_usuario`, `id_proveedor`, `nombre_contacto`,`email_usuario`, `id_oferta`, `usuario_registro`) VALUES ( ?, ?, ?, ?, ?, ?)';
                    $parametros = array((int)$idParticipante, $provid, $nombreParticipante, $email, (int)$idoferta, (int)$_SESSION["idusuario"]);
                    $sqlCreaPart = $this->intelcost->prepareStatementQuery("cliente", $SqlOfer, "INSERT", true, "isssii", $parametros, "Crear participante en oferta.");
                    $idParticipante = $sqlCreaPart->msg;
                    if($sqlCreaPart->bool){
                        $ofertaMod = $this->obtenerEstadoOferta($idoferta);
                        if($ofertaMod->bool){
                            $ofertaObj = json_decode($ofertaMod->msg);

                            if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                                $this->generarCartaInvitacionOcensa($idParticipante, $idoferta);
                            }

                            if($ofertaObj->estado == "PUBLICADA"){
                                $objOferta = $this->obtenerOferta($idoferta);
                                if($objOferta->bool){
                                    $this->enviarEmailInvitacionOferta($email, $nombreParticipante, $objOferta->msg, $idParticipante);
                                }
                            }
                        }
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg = "Participante relacionado a oferta";
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg ="Se presentó un error al guardar el invitado.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Ya ha sido invitado a esta oferta.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error al validar participante.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = $objUsuIntelcost->msg;
        }
        return $this->intelcost->response;
    }

    private function enviarEmailInvitacionOferta($email,$nombre,$oferta,$idParticipante){

        $ofertaobj = json_decode($oferta);
        //obtener nombre y empresa del usuario proveedor
        $contentParticipanteOferta  = "<p>Estimado(a): ".$nombre."<br /><br /><br />Le damos la bienvenida a la plataforma de eventos en línea de INTELCOST.</p> ";
        if($ofertaobj->tipo == "cerrada" || $ofertaobj->tipo == "estudio" || $ofertaobj->tipo == "convenio" || $ofertaobj->tipo == "rfq"){
            $contentParticipanteOferta .= "Le informarmos que <b>".$ofertaobj->clienteNombre."</b> - ".$ofertaobj->usuario_creador.", lo(a) ha invitado a participar en el siguiente proceso; <b>".$ofertaobj->seq_id.(($ofertaobj->id_cliente == 14 || $ofertaobj->id_cliente == 26 || $ofertaobj->id_cliente == 27 || $ofertaobj->id_cliente == 20) ? "-".$ofertaobj->ronda : "")." - ".$ofertaobj->objeto."</b><br> ";
            $contentParticipanteOferta .= "El cual se encuentra ahora publicado para su participación.<br /><br />";
        }else if(($ofertaobj->tipo == "abierta" || $ofertaobj->tipo == "publico") && $ofertaobj->estado == "PUBLICADA"){
            $contentParticipanteOferta .= "Le informamos que el proceso ".$ofertaobj->seq_id.(($ofertaobj->id_cliente == 14 || $ofertaobj->id_cliente == 26 || $ofertaobj->id_cliente == 27 || $ofertaobj->id_cliente == 20) ? "-".$ofertaobj->ronda : "")." consultado por su compañía ha sido modificado.<br><br>";
        }
        $contentParticipanteOferta .= "<h3>Acceso</h3>";
        $contentParticipanteOferta .= "<div style='padding-left:10%;'>";
        switch ($ofertaobj->id_cliente) {
            case 26:
                $contentParticipanteOferta .= "<b>Vinculo:</b> <a href='https://proveedoresterpelpanama.intelcost.com' target='_blank' style='text-decoration:none'>www.intelcost.com/intelcost</a><br />";
                break;
            
            default:
                if($ofertaobj->id_cliente){
                    $respuesta_cliente = (new modelo_cliente)->obtenerCliente($ofertaobj->id_cliente);
                    if($respuesta_cliente->bool){
                        $cliente = json_decode($respuesta_cliente->msg);
                        $contentParticipanteOferta .= "<b>Vinculo:</b> <a href='https://".(!empty($cliente->acceso_proveedores) ? $cliente->acceso_proveedores : 'proveedores.intelcost.com')."' target='_blank' style='text-decoration:none'>www.".(!empty($cliente->acceso_proveedores) ? $cliente->acceso_proveedores : 'proveedores.intelcost.com')."</a><br />";
                    }else{
                        $contentParticipanteOferta .= "<b>Vinculo:</b> <a href='https://proveedores.intelcost.com' target='_blank' style='text-decoration:none'>www.intelcost.com/intelcost</a><br />";
                    }
                }else{
                    $contentParticipanteOferta .= "<b>Vinculo:</b> <a href='https://proveedores.intelcost.com' target='_blank' style='text-decoration:none'>www.intelcost.com/intelcost</a><br />";
                }
                break;
        }

        $contentParticipanteOferta .= "<b>Usuario:</b> ".$email."<br />";

        /*$queryUsuario = "SELECT usrpassx FROM sys00001 WHERE usridxxx = ?";
        $sqlUsuario = $this->intelcost->prepareStatementQuery('intelcost', $queryUsuario, 'select', true, "i", array((int) $idParticipante), "Obtener password participante oferta.");
        $participanteInfoPass = "";
        if($sqlUsuario->bool){
            if($sqlUsuario->msg->num_rows > 0){
                $participanteInfo = $sqlUsuario->msg->fetch_assoc();
                $participanteInfoPass = $participanteInfo['usrpassx'];
            }
        } 
        if($participanteInfoPass != ""){
            $contentParticipanteOferta .= "<b>Contraseña:</b> ".$participanteInfoPass."<br />";
        }*/

        $contentParticipanteOferta .= "<p style='font-size:12px'>Recuerde que puede ingresar a la platorma usando su usuario registrado y contraseña asignada.<br /> Sí no recuerda la contraseña, puede utilizar la opción de <b>¿Has olvidado tu contraseña?</b>.</p><br />";
        $subject = 'Invitacion a participacion en Oferta - '.$ofertaobj->objeto.'.';
        $this->enviarEmailConfirmacionPublicacion($email,$contentParticipanteOferta,$oferta,$subject);
    }

    private function enviarEmailConfirmacionPublicacion($recipient,$content,$oferta,$subject){
        $ofertaObj = json_decode($oferta);
        $emailContent = "";
        $emailContent .= $content;
        $emailContent .= "<h3>Proceso ".$ofertaObj->seq_id.(($ofertaobj->id_cliente == 14 || $ofertaobj->id_cliente == 26 || $ofertaobj->id_cliente == 27 || $ofertaobj->id_cliente == 20) ? "-".$ofertaobj->ronda : "")."</h3>";
        //$emailContent .= "<div style='padding-left:10%'>";
        $emailContent .= "<p><b>Objeto:</b> ".$ofertaObj->objeto."</p>";
        $emailContent .= "<p><b>Descripción:</b> ".$ofertaObj->descripcion."</p>";
        $emailContent .= "<p><b>Cronograma Inicial</b> (Hora de Colombia / GMT -5)</p>";
        $emailContent .= "<p style='margin:0'>Fecha inicio: ".$ofertaObj->fecha_inicio." - ".$ofertaObj->hora_inicio."</p>";
        $emailContent .= "<p style='margin:0'>Fecha cierre: ".$ofertaObj->fecha_cierre." - ".$ofertaObj->hora_cierre."</p>";
        $emailContent .= "<p style='margin:0'>Fecha límite envío mensajes: ".$ofertaObj->fecha_limite_msg_fecha."</p>";

        if(isset($ofertaObj->vfecha) && $ofertaObj->vfecha != null){
            if($ofertaObj->vobligatorio == 0){
                $ofertaObj->vobligatorio = "NO";
            }else{
                $ofertaObj->vobligatorio = "SI";
            }
            $emailContent .= "<br /><p><b>Visita de Obra</b> </p>";
            $emailContent .= "<p style='margin:0'>Fecha visita: ".$ofertaObj->vfecha."</p>";
            $emailContent .= "<p style='margin:0'>Obligatoria : ".$ofertaObj->vobligatorio."</p>";
            $emailContent .= "<p style='margin:0'>Lugar visita: ".$ofertaObj->vlugar."</p>";
            $emailContent .= "<p style='margin:0'>Responsable visita: ".$ofertaObj->vresponsable."</p>";
            $emailContent .= "<p style='margin:0'>Teléfono Responsable: ".$ofertaObj->vtelefono."</p>";
            $emailContent .= "<p style='margin:0'>Observaciones: ".$ofertaObj->vobservaciones."</p>";
        }

        $obj_adicionales = new stdClass();
        $obj_adicionales->relacion_id = isset($ofertaObj->idoferta) ? $ofertaObj->idoferta : isset($ofertaObj->id) ? $ofertaObj->id : null;
        $obj_adicionales->modulo_id = 5;
        $comunicar = $this->modeloComunicaciones->sendEmail($recipient, $emailContent, $subject, "ComunicadoLogoCliente", $ofertaObj->id_cliente,$obj_adicionales);
        return $comunicar;
    }

    public function crearUsuarioInternoOferta($idoferta, $idParticipante, $nombre, $accesos, $usuariosInterno){
        if($idParticipante != null){
            $SqlOferSear = 'SELECT count(*) as cont, estado FROM oferta_usuarios_internos WHERE `id_usuario` = ? AND  `id_oferta` = ? ';
        }
        $objValidarUsuario = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'select', true, "ii", array((int) $idParticipante, (int) $idoferta), "Validar usuario interno.");
        if($objValidarUsuario->bool){
            $conSearch = $objValidarUsuario->msg->fetch_assoc();
            //Validacion de existencia del usuario en la oferta
            if($conSearch["cont"] == "0"){
                $SqlOfer = "INSERT INTO oferta_usuarios_internos ( `id_oferta`, `id_usuario`, `accesos`, `usuarioregistro`) VALUES ( ?, ?, ?, $_SESSION[idusuario])";
                $parametros = array((int)$idoferta, (int)$idParticipante, $accesos);
                $insertusuario = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'INSERT', true, "iis", $parametros, "Insertar usuario interno.");
                if($insertusuario->bool){
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "Usuario relacionado a oferta";
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Usuario relacion erronea";
                }
            }else{
                if(!empty($accesos)){
                    $SqlOfer = 'UPDATE oferta_usuarios_internos SET `accesos` = ?, `estado` = "activo", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id_oferta`= ? AND `id_usuario` = "'.$idParticipante.'"';

                    $parametros = array($accesos, (int)$idoferta);
                    $updateUsuario = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "si", $parametros, "Actualizar usuario interno.");

                    if($updateUsuario->bool){
                        $this->intelcost->response->bool = true;
                        if($conSearch["estado"] == "INACTIVO"){
                            $this->intelcost->response->msg = "Usuario re vinculado a la oferta oferta";                            
                        }else{
                            $this->intelcost->response->msg = "Usuario actualizacion en oferta";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg ="Usuario actualizacion en relacion erronea 1";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Permisos no asignados";
                }
            }
            if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                $condicional = [
                    'id_oferta' => $idoferta,
                    'id_usuario' => $idParticipante,
                    'estado' => 'activo',
                ];

                $arregloPermisos = [
                    'id_oferta' => $idoferta,
                    'id_usuario' => $idParticipante,
                    'permisos' => $usuariosInterno->accesos_criterios ?? json_encode([]),
                ];
                
                OfertaUsuariosPermisosCriteriosTecnicos::updateOrCreate($condicional, $arregloPermisos);
            }

        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al crear usuario interno (validar).";
        }
        return $this->intelcost->response;
    }

    public function agregarUsuariosEvaluadoresOferta($idOferta, $arrayEvaluadores){
        $mensajeOk = array();
        $mensajeErrores = "";
        $bandera = true;
        foreach ($arrayEvaluadores as $evaluador) {
            $objCrearEvaluador = $this->crearUsuarioInternoOferta($idOferta, $evaluador->id, $evaluador->nombre, $evaluador->accesos, $evaluador);
            if ($objCrearEvaluador->bool) {
                array_push($mensajeOk, $evaluador->nombre);
                $notificacion = $this->notificacionUsuarioEvaluador($idOferta, $evaluador->id);
                if(!$notificacion){
                    $bandera = false;
                    $mensajeErrores .= "Error al notificar al evaluador ".$evaluador->nombre.".";   
                }
            }else{
                $bandera = false;
                $mensajeErrores .= "Error al crear el evaluador ".$evaluador->nombre.". Error: ".$objCrearEvaluador->msg;
            }
        }
        $this->intelcost->response->bool = true;
        $this->intelcost->response->msg = "Se asociaron al proceso como evaluadores a los usuarios; ".implode(", ", $mensajeOk);
        if(!$bandera){
            $this->intelcost->response->msg .= $mensajeErrores;
        }
        return $this->intelcost->response;
    }

    private function notificacionUsuarioEvaluador($idOferta, $idUsuario){
        $queryOferta = "SELECT O.seq_id, O.objeto, O.descripcion, O.id_cliente, O.estado, U.nombre AS nombre_comprador, U.username AS email_comprador, UE.nombre AS nombre_evaluador, UE.username AS email_evaluador FROM ofertas AS O INNER JOIN usuarios AS U ON U.id = O.usuario_creacion INNER JOIN oferta_usuarios_internos AS OU ON OU.id_oferta = O.id INNER JOIN usuarios AS UE ON UE.id = OU.id_usuario WHERE O.id = $idOferta AND OU.id_usuario = $idUsuario ";
        $sqlNotificacion = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, 'select', false, "", "", "query oferta notificación evaluador.");
        if($sqlNotificacion->bool){
            if($sqlNotificacion->msg->num_rows > 0){
                $dataNotificacion = $sqlNotificacion->msg->fetch_assoc();
                $asunto = "Proceso $dataNotificacion[seq_id] - $dataNotificacion[objeto].";
                $emailContent  = "<p><b>Estimado(a):</b> $dataNotificacion[nombre_evaluador]</p>.<br> ";
                $emailContent .= "Le informamos qué ud ha sido invitado(a) como evaluador en el proceso / evento $dataNotificacion[seq_id], el cual se encuentra en estado de $dataNotificacion[estado].";
                $emailContent .= "<p>Link de acceso: <a href='https://".$_SESSION["url"]."'>".$_SESSION["url"]."</a></p>";
                $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Para asegurar un correcto uso de la herramienta puede contactarnos al tel&eacute;fono +57 1 489 8100, o al correo electrónico <a href='mailto:soporte@intelcost.com' target='_blank'> <b>soporte@intelcost.com</b></a>. Adicionalmente, una vez ingrese al sistema, podrá solicitar soporte a través de nuestro chat en vivo.</p>";

                $obj_adicionales = new stdClass();
                $obj_adicionales->relacion_id = $idOferta;
                $obj_adicionales->modulo_id = 5;

                $email = $this->modeloComunicaciones->sendEmail($dataNotificacion['email_comprador'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
                return $email;
            }else{
                return false;   
            }
        }else{
            return false;
        }
    }
    
    private function notificacionUsuarioAprobador($idOferta, $idUsuario){
        $queryOferta = "SELECT O.seq_id, O.objeto, O.descripcion, O.id_cliente, O.estado, U.nombre AS nombre_comprador, U.username AS email_comprador, UA.nombre AS nombre_aprobador, UA.username AS email_aprobador FROM ofertas AS O INNER JOIN usuarios AS U ON U.id = O.usuario_creacion INNER JOIN oferta_usuarios_aprobadores AS OA ON OA.id_oferta = O.id INNER JOIN usuarios AS UA ON UA.id = OA.id_usuario_aprobador WHERE O.id = $idOferta AND OA.id_usuario_aprobador = $idUsuario ";
        $sqlNotificacion = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, 'select', false, "", "", "query oferta notificación evaluador.");
        if($sqlNotificacion->bool){
            if($sqlNotificacion->msg->num_rows > 0){
                $dataNotificacion = $sqlNotificacion->msg->fetch_assoc();
                $asunto = "Proceso $dataNotificacion[seq_id] - $dataNotificacion[objeto].";
                $emailContent  = "<p><b>Estimado(a):</b> $dataNotificacion[nombre_aprobador]</p>.<br> ";
                $emailContent .= "Le informamos qué ud ha sido invitado(a) como aprobador en el proceso / evento $dataNotificacion[seq_id], el cual se encuentra en estado de $dataNotificacion[estado].";
                $emailContent .= "<p>Link de acceso: <a href='https://".$_SESSION["url"]."'>".$_SESSION["url"]."</a></p>";
                $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Para asegurar un correcto uso de la herramienta puede contactarnos al tel&eacute;fono +57 1 489 8100, o al correo electrónico <a href='mailto:soporte@intelcost.com' target='_blank'> <b>soporte@intelcost.com</b></a>. Adicionalmente, una vez ingrese al sistema, podrá solicitar soporte a través de nuestro chat en vivo.</p>";

                $obj_adicionales = new stdClass();
                $obj_adicionales->relacion_id = $idOferta;
                $obj_adicionales->modulo_id = 5;

                $email = $this->modeloComunicaciones->sendEmail($dataNotificacion['email_aprobador'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
                return $email;
            }else{
                return false;   
            }
        }else{
            return false;
        }
    }

    public function eliminarProveedorOferta($idOferta, $emailProveedor){
        $SqlOferSear = 'UPDATE oferta_participantes SET `estado` = "eliminado",`usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'"  WHERE `email_usuario` = ? AND `id_oferta` = ?';
        $sqlEliminaProv = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'UPDATE', true, "si", array($emailProveedor, (int)$idOferta), "Eliminar proveedor de la oferta.");
        if($sqlEliminaProv->bool){
            if($sqlEliminaProv->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="Proveedor eliminado del proceso / evento.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al eliminar el proveedor.";    
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el proveedor.";
        }
        return $this->intelcost->response;
    }

    public function eliminarProveedorCompletoOferta($idOferta, $nitProveedor){
        $SqlOferSear = "UPDATE oferta_participantes SET `estado` = 'eliminado', `usuario_actualizacion` = $_SESSION[idusuario], `fecha_actualizacion` = '".date("Y-m-d H:i:s")."' WHERE `id_proveedor` = ? AND `id_oferta` = ? ";
        $sqlEliminaProv = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'UPDATE', true, "si", array($nitProveedor, (int)$idOferta), "Eliminar proveedor completo de la oferta.");
        if($sqlEliminaProv->bool){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg ="Proveedor eliminado del proceso / evento.";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el proveedor.";
        }
        return $this->intelcost->response;  
    }

    public function guardarUsuariosAprobadoresProceso($idOferta, $objAprobadores){
        $boolGuardarAprobadores = true;
        $errorGuardar = "";
        $banderaValidarAccesos = false;
        $idComprador = null;
        $queryOferta = "SELECT estado, usuario_creacion FROM ofertas WHERE id = ?";
        $objestadoOferta = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, "select", true, "i", array((int) $idOferta), "Obtener estado oferta.");
        if($objestadoOferta->bool){
            $respEstadoOf = $objestadoOferta->msg->fetch_assoc();
            $idComprador = $respEstadoOf['usuario_creacion'];
            if($respEstadoOf['estado'] == "EN EVALUACION"){
                $banderaValidarAccesos = true;
            }
        }   
        foreach ($objAprobadores as $aprobador) {
            if((int) $aprobador->id_usuario != (int) $idComprador){
                if(!empty($aprobador->id_aprobador)){
                    $queryAprobador = "UPDATE `oferta_usuarios_aprobadores` SET `accesos` = ?, `fecha_modificacion` = '".date("Y-m-d H:i:s")."', id_usuario_modifica = $_SESSION[idusuario] WHERE id = ? ";
                    $parametros = array($aprobador->accesos, (int) $aprobador->id_aprobador);
                    $tipoParametros = "si";
                    $tipoQuery = "update";
                    $sqlGuardarAprobador = $this->intelcost->prepareStatementQuery('cliente', $queryAprobador, "update", true, "si", $parametros, "Guardar usuarios aprobadores.");
                    if(!$sqlGuardarAprobador->bool){
                        $boolGuardarAprobadores = false;
                        $errorGuardar .= "Error al actualizar id usuario ".$aprobador->id_usuario.". ";
                    }
                }else{
                    $guardarAprobador = true;
                    if($banderaValidarAccesos){
                        //ESTO SE REALIZA SOLO SÍ EL PROCESO SE ENCUENTA EN EVALUACIÓN PARA VERIFICAR QUÉ SOBRE LOS IDS DE ACCESOS, NO SE HAYAN REALIZADO APROBACIONES FINALES POR PARTE DEL COMPRADOR, EN DADO CASO, NO SE LE PERMITIRÁ ASIGNAR ACCESO A TAL DOCUMENTO.
                        $arrayAccesosValidados = array();
                        $accesosAsignados = json_decode($aprobador->accesos);
                        foreach ($accesosAsignados as $acceso) {
                            $queryPart = "SELECT id FROM oferta_documentos_ofertascliente WHERE id_documento_oferente = $acceso ";
                            $sqlPart = $this->intelcost->prepareStatementQuery('cliente', $queryPart, "select", false, "", "", "");
                            if($sqlPart->bool){
                                if($sqlPart->msg->num_rows > 0){
                                    $arrayIds = array();
                                    while ($resPart = $sqlPart->msg->fetch_assoc()){
                                        array_push($arrayIds, $resPart['id']);
                                    }
                                    $idsParticipaciones = implode(",", $arrayIds);
                                    $queryAprob = "SELECT COUNT(id_historial) as aprobaciones FROM oferta_evaluacion_documento_historial WHERE id_documento IN($idsParticipaciones) AND id_usuario = $idComprador AND tipo_usuario_registra = 'aprobador' AND valoracion = 'aprobado'";
                                    $sqlAprob = $this->intelcost->prepareStatementQuery('cliente', $queryAprob, "select", false, "", "", "");
                                    if($sqlAprob->bool){
                                        $respAprob = $sqlAprob->msg->fetch_assoc();
                                        if((int) $respAprob['aprobaciones'] == 0){
                                            array_push($arrayAccesosValidados, $acceso);
                                        }
                                    }
                                }
                            }
                        }
                        if(count($arrayAccesosValidados) > 0){
                            $aprobador->accesos = json_encode($arrayAccesosValidados);
                            $queryAprobador = "INSERT INTO `oferta_usuarios_aprobadores`(`id_oferta`, `id_usuario_aprobador`, `accesos`, `id_usuario_creacion`) VALUES (?, ?, ?, $_SESSION[idusuario])";
                            $parametros = array((int) $idOferta, (int) $aprobador->id_usuario, $aprobador->accesos);
                            $sqlGuardarAprobador = $this->intelcost->prepareStatementQuery('cliente', $queryAprobador, "insert", true, "iis", $parametros, "Guardar usuario aprobador proceso en evaluación.");
                            if(!$sqlGuardarAprobador->bool){
                                $boolGuardarAprobadores = false;
                                $errorGuardar .= "Error al crear al aprobador ".$aprobador->nombre.". ";
                            }else{
                                $notificar = $this->notificacionUsuarioAprobador($idOferta, $aprobador->id_usuario);
                            }
                        }else{
                            $boolGuardarAprobadores = false;
                            $errorGuardar .= "No se pudo guardar al aprobador ".$aprobador->nombre.". Los accesos asignados ya fueron evaluados y aprobados por parte del comprador. ";
                        }
                    }else{
                        $queryAprobador = "INSERT INTO `oferta_usuarios_aprobadores`(`id_oferta`, `id_usuario_aprobador`, `accesos`, `id_usuario_creacion`) VALUES (?, ?, ?, $_SESSION[idusuario])";
                        $parametros = array((int) $idOferta, (int) $aprobador->id_usuario, $aprobador->accesos);
                        $sqlGuardarAprobador = $this->intelcost->prepareStatementQuery('cliente', $queryAprobador, "insert", true, "iis", $parametros, "Guardar usuario aprobador proceso en evaluación.");
                        if(!$sqlGuardarAprobador->bool){
                            $boolGuardarAprobadores = false;
                            $errorGuardar .= "Error al guardar id usuario ".$aprobador->id_usuario.". ";
                        }
                    }
                }
            }else{
                $boolGuardarAprobadores = false;
                $errorGuardar .= "El comprador no puede ser agregado como aprobador, ya que por defecto el comprador es tomado como aprobador final.";
            }
        }
        if($boolGuardarAprobadores){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "Se ha guardado la información de aprobadores.";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al guardar: $errorGuardar";
        }

        $varTemp= $this->intelcost->response;

        if ($boolGuardarAprobadores == true){
            $this->notificarAprobadorAdicionalAgregado($idOferta);
        }
        return $varTemp;
    }

    public function obtenerUsuariosAprobadoresDocumentos($idOferta){
        $queryAprobadores = "SELECT OUA.id, OUA.id_usuario_aprobador, OUA.accesos, US.nombre, US.email FROM oferta_usuarios_aprobadores AS OUA INNER JOIN usuarios AS US ON US.id = OUA.id_usuario_aprobador WHERE OUA.id_oferta = ? AND OUA.estado = 'activo' ORDER BY OUA.id";
        $sqlAprobadores = $this->intelcost->prepareStatementQuery('cliente', $queryAprobadores, "select", true, "i", array((int) $idOferta), "obtener usuarios aprobadores.");
        if($sqlAprobadores->bool){
            if($sqlAprobadores->msg->num_rows > 0){
                $arrayUsuariosAp = array();
                while ($usuario = $sqlAprobadores->msg->fetch_assoc() ) {
                    array_push($arrayUsuariosAp, $usuario);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $arrayUsuariosAp;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontraron usuarios aprobadores.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al consultar usuarios aprobadores documentos.";
        }
        return $this->intelcost->response;  
    }

    public function eliminarUsuarioAprobadorProceso($id_aprobador){
        $query = "UPDATE `oferta_usuarios_aprobadores` SET `estado` = 'inactivo', `id_usuario_modifica` = $_SESSION[idusuario], `fecha_modificacion` ='".date("Y-m-d H:i:s")."' WHERE `id` = ? AND `estado` = 'activo' ";
        $sql = $this->intelcost->prepareStatementQuery('cliente', $query, "update", true, "i", array((int) $id_aprobador), "Eliminar usuario aprobador.");
        if($sql->bool){
            if($sql->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Se ha eliminado el aprobador correctamente.";
            }else{
                $this->intelcost->response->bool = False;
                $this->intelcost->response->msg = "No se logró eliminar el aprobador.";             
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al eliminar el usuario aprobador.";
        }
        return $this->intelcost->response;
    }

    public function eliminarUsuarioInternoOferta($idOferta,$idUsuario){
        $SqlOferSear = "UPDATE oferta_usuarios_internos SET `estado` = 'INACTIVO', usuario_actualizacion = $_SESSION[idusuario], fecha_actualizacion ='".date("Y-m-d H:i:s")."' WHERE `id_usuario` = ? AND  `id_oferta` = ? ";
        $sqlEliminaUs = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'UPDATE', true, "ii", array((int)$idUsuario, (int)$idOferta), "Eliminar usuario interno de la oferta.");
        if($sqlEliminaUs->bool){
            if($sqlEliminaUs->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="Usuario inactivado";  
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al eliminar el usuario interno.";  
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el usuario interno.";
        }
        return $this->intelcost->response;
    }
    
    public function eliminarCriterioExistenteOferta($idOferta,$id_criterio){
        $SqlOferSear = 'UPDATE ofertas_criterios_evaluacion SET `estado` = "0" WHERE `id` = ? AND  `id_oferta` = ? ';
        $sqlEliminaUs = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'UPDATE', true, "ii", array((int)$id_criterio, (int)$idOferta), "Eliminar criterio de la oferta.");
        if($sqlEliminaUs->bool){
            if($sqlEliminaUs->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="criterio inactivado"; 
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al eliminar el criterio."; 
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el criterio.";
        }
        return $this->intelcost->response;
    }

    public function eliminarDocumentoOferta($idOferta,$idDocumento){
        //$SqlOferSear = 'UPDATE oferta_documentos SET `estado` = "INACTIVO" WHERE `id` = ? AND  `id_oferta` = ? ';
        $SqlOferSear = 'UPDATE oferta_documentos SET `estado` = "activo2", usuario_actualizacion = '.$_SESSION['idusuario'].', fecha_actualizacion = "'.date("Y-m-d").'" WHERE `id` = ? AND `id_oferta` = ? ';
        $sqlEliminaDoc = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'UPDATE', true, "ii", array((int)$idDocumento, (int)$idOferta), "Eliminar documento de la oferta.");
        if($sqlEliminaDoc->bool){
            if($sqlEliminaDoc->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="documento inactivado";    
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al eliminar el documento.";    
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el documento.";
        }
        return $this->intelcost->response;
    }

    public function eliminarDocumentosContenidoOferta($idOferta){
        $query = 'UPDATE oferta_documentos SET `estado` = "activo2", usuario_actualizacion = '.$_SESSION['idusuario'].', fecha_actualizacion = "'.date("Y-m-d").'" WHERE `id_oferta` = ? ';
        $sql = $this->intelcost->prepareStatementQuery('cliente', $query, 'UPDATE', true, "i", array((int) $idOferta), "Eliminar documentación petición oferta.");
        if($sql->bool){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "Se ha eliminado la documentación del proceso / evento.";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar la documentación del proceso / evento.";
        }
        return $this->intelcost->response;
    }

    public function eliminarDocumentoOferente($idOferta,$idDocumento){
        $SqlOferSear = 'UPDATE oferta_documentos_oferentes SET `estado` = "INACTIVO", usuario_actualizacion = '.$_SESSION['idusuario'].', fecha_actualizacion = "'.date("Y-m-d").'" WHERE `id` = ? AND  `oferta_id` = ?';
        $sqlEliminaDocOf = $this->intelcost->prepareStatementQuery('cliente', $SqlOferSear, 'UPDATE', true, "ii", array((int)$idDocumento, (int)$idOferta), "Eliminar documento oferente de la oferta.");

        if($sqlEliminaDocOf->bool){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg ="documento oferente inactivado";   
            /*if($sqlEliminaDocOf->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="documento oferente inactivado";   
            }
            else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al eliminar el documento oferente .";  
            }*/
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al eliminar el documento oferente .";
        }
        return $this->intelcost->response;
    }

    public function eliminarEntregablesOferta($idOferta){
        $query = "UPDATE oferta_documentos_oferentes SET `estado` = 'INACTIVO', usuario_actualizacion = $_SESSION[idusuario], fecha_actualizacion = '".date("Y-m-d")."' WHERE `oferta_id` = ? AND estado = 'activo'";
        $sql = $this->intelcost->prepareStatementQuery('cliente', $query, 'UPDATE', true, "i", array((int) $idOferta), "Eliminar todos los entregables de la oferta.");
        if($sql->bool){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "Se eliminaron todos los entregables correctamente.";
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al eliminar los entregables.";
        }
        return $this->intelcost->response;
    }

    public function cancelarOferta($idOferta){
        $ofertaMod = $this->obtenerOferta($idOferta);
        $estado = "CANCELADA";
        if ($ofertaMod->bool) {
            $oferta = json_decode($ofertaMod->msg);
            if ($oferta->estado == "EN EVALUACION") {
                $estado = "DESIERTA";
            }
        }
        $SqlOfer = 'UPDATE ofertas SET `estado` = "'.$estado.'", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ? ';

        $sqlCancelaOf = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int)$idOferta), "Cancelar oferta.");
        if($sqlCancelaOf->bool){
            if($sqlCancelaOf->msg > 0){
                $ofertaMod = $this->obtenerOferta($idOferta);
                if($ofertaMod->bool){
                    //$ofertaObj = json_decode($ofertaMod->msg);
                    $resSAP = [];
                    //$resSAP = $this->modelo_solpeds->actualizarSolpedSAPQAS("999999",$ofertaObj->solpeds_relacionadas);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = ["Oferta actualizada correctamente",json_encode($resSAP)];
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Cancelación de oferta erronea";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al cancelar la oferta.";
        }
        return $this->intelcost->response;
    }

    private function emailConfirmacionCancelacionOferta($idoferta){
        //$modeloComunicaciones = new communicationClient();
        $ofertaMod = $this->obtenerOferta($idoferta);
        if($ofertaMod->bool){
            $ofertaObj = json_decode($ofertaMod->msg);
            // Generacion contenido de email de noticacion
            $ofertaObj->clienteNombre = strtoupper($ofertaObj->clienteNombre);
            $subject = $ofertaObj->clienteNombre." - HA CANCELADO EL PROCESO (".$ofertaObj->seq_id.") - ".$ofertaObj->objeto;
            $emailContent  = "Estimado(a): ".$ofertaObj->usuario_creador." <br /> <br />";
            $emailContent .= "<b > ".$ofertaObj->clienteNombre."</b> ha cancelado el evento: <br /> <br />";
            
            $emailContent .= "<p>ID     : (No ".$ofertaObj->seq_id.")</p>";
            $emailContent .= "<p>Objeto : ".$ofertaObj->objeto."</p>";
            $emailContent .= "<p>Descripción : ".$ofertaObj->descripcion."</p>";
            $emailContent .= "<p>Responsable : ".$ofertaObj->usuario_creador."</p>";
            $Recipients = $ofertaObj->usuario_creador_email;
            //$Recipients = "jmontoya@intelcost.com";
            $obj_adicionales = new stdClass();
            $obj_adicionales->relacion_id = $idoferta;
            $obj_adicionales->modulo_id = 5;
            $comunicar = $this->modeloComunicaciones->sendEmail($Recipients, $emailContent, $subject, "ComunicadoLogoCliente", $ofertaObj->id_cliente, $obj_adicionales);
            // CORREO PARA LOS PARTICIPANTES QUE ENVIARON OFERTA, se envian uno a uno por temas de personalizacion de informacion y privacidad
            foreach($ofertaObj->participantes as $participante){
                
                if($participante->estado_participacion == "Oferta Enviada"){
                    $emailContent  = "Estimado(a): ".$participante->nombre." <br /> <br />";
                    $emailContent .= "<p><b >".$ofertaObj->clienteNombre."</b> ha cancelado el evento: </p><br /> <br />";
                    
                    $emailContent .= "<p>ID     : (No ".$ofertaObj->seq_id.")</p>";
                    $emailContent .= "<p>Objeto : ".$ofertaObj->objeto."</p>";
                    $emailContent .= "<p>Descripción : ".$ofertaObj->descripcion."</p>";
                    $emailContent .= "<p>Responsable : ".$ofertaObj->usuario_creador."</p>";
                    $Recipients = $participante->email;
                    //$Recipients = "jmontoya@intelcost.com";
                    $obj_adicionales = new stdClass();
                    $obj_adicionales->relacion_id = $idoferta;
                    $obj_adicionales->modulo_id = 5;
                    $comunicar = $this->modeloComunicaciones->sendEmail($Recipients, $emailContent, $subject, "ComunicadoLogoCliente", $ofertaObj->id_cliente,$obj_adicionales);
                }
            }
            return true;
        }else{
            return false;
        }
    }

    public function activarOferta($nuevoEstado,$idOferta){
        $SqlOfer = 'UPDATE ofertas SET `estado` = ?, `usuario_actualizacion`= "'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ? ';
        $sqlActivarOf = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "si", array($nuevoEstado, (int)$idOferta), "Activar / inactivar oferta.");
        if($sqlActivarOf->bool){
            if($sqlActivarOf->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Oferta actualizada correctamente";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Actualizacion de oferta erronea.";   
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Actualizacion de oferta erronea.";
        }
        return $this->intelcost->response;
    }

    public function liberarEstudio($idOferta){

        //Se valida que el estudio de mercado no tenga un AOC asociado sin cancelar para su liberación.
        $sql = "SELECT count(id_aoc) as cont FROM aoc WHERE id_estudio_mercado = ? AND estado != 'cancelado'";

        $csc = $this->intelcost->prepareStatementQuery('cliente', $sql, "select", true, "i", array((int) $idOferta), "verificamos si existe un aoc asociada al estudio de mercado.");

        if($csc->bool){
            if($csc->msg > 0){

                $respContAoc = $csc->msg->fetch_assoc();
                if($respContAoc["cont"]==0){

                    $SqlOfer = 'UPDATE ofertas SET `estado` = "LIBERADO", `usuario_actualizacion`= "'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ? ';
                    $sqlActivarOf = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int)$idOferta), "Liberar estudio.");
                    if($sqlActivarOf->bool){
                        if($sqlActivarOf->msg > 0){
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg = "Estudio liberado correctamente";
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg = "Liberacion de oferta erronea.";  
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Liberacion de oferta erronea.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "No se puede liberar un estudio de mercado si tiene asociado un AOC sin cancelar.";
                }

            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al consultar AOC.";
            }

        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al consultar AOC.";
        }
        return $this->intelcost->response;
    }

    public function publicarOferta($idOferta){
        Seguimiento::setAgregarSeguimientoDetallado(array(
            'tabla' => 'ofertas',
            'columna' => 'id',
            'valor' => $idOferta,
            'registro_id' => $idOferta,
        ), 5, "Publicar Oferta");

        $SqlOfer = 'UPDATE ofertas SET `estado` = "PUBLICADA", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ? ';
        $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int)$idOferta), "Publicar oferta.");

        Seguimiento::setAgregarSeguimientoDetallado(array(
            'tabla' => 'ofertas',
            'columna' => 'id',
            'valor' => $idOferta,
            'registro_id' => $idOferta,
        ), 5, "Publicar Oferta", false);

        if($CscUsr->bool){
            if($CscUsr->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Oferta actualizada correctamente";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "publicación de oferta erronea."; 
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "publicación de oferta erronea.";
        }
        return $this->intelcost->response;
    }

    public function validarSolicitudSolped($id_oferta){
        $response = new stdClass();
        $oferta = Oferta::where('id', $id_oferta)->with('infoAdicionalesOferta')->first();

        if((empty($oferta->infoAdicionalesOferta) || empty($oferta->infoAdicionalesOferta->solicitud_id)) && empty($oferta->solpeds_relacionadas)){
            $response->bool = false;
            $response->msg = 'Debe seleccionar una solicitud o seleccionar por lo menos 1 item de un lote para el proceso';
            return $response;
        }

        if(!empty($oferta->infoAdicionalesOferta->solicitud_id) && empty($oferta->solpeds_relacionadas)){
            $response->bool = false;
            $response->msg = 'No se encontraron posiciones en el proceso, por favor validar que el proceso tenga posiciones';
            return $response;
        }

        $response->bool = true;
        $response->msg = 'Ok';
        return $response;
    }
    
    public function validarProveedoresPrecalificados($id_oferta){
        $response = new stdClass();
        $ofertaConPrecalificacion = OfertaDatosAdicionales::where('oferta_id', $id_oferta)->activo()->first();
        if($ofertaConPrecalificacion && $ofertaConPrecalificacion->precalificacion){
            $empresas = $ofertaConPrecalificacion->precalificacion->obtenerEmpresasPrecalificadas->filter(function($empresa){
                if($empresa->infoEmpresa){
                    $resultadoPrecalificados = collect($empresa->infoEmpresa->precalificacionResultados);
                    if(
                        $resultadoPrecalificados->where('cod_precalificacion', $empresa->cod_precalificacion)
                        ->where('listas', '!=', 'NO PASA')
                        ->where('legal', '!=', 'NO PASA')
                        ->where('hse', '!=', 'NO PASA')
                        ->where('financiera', '!=', 'NO PASA')
                        ->where('tecnica', '!=', 'NO PASA')
                        ->count() > 0
                    ){
                        return $empresa->infoEmpresa;
                    }
                }
            });
            
            $retornoPrecalificaciones = collect();
			foreach ($empresas as $key => $empresa) {
				$retornoPrecalificaciones->push($empresa->infoEmpresa);
            }
            
            $empresas =  $retornoPrecalificaciones;
            
            $nits = $empresas->pluck('nitempxx');
            $participantesOferta = OfertaParticipantes::whereIn('id_proveedor', $nits)
                                            ->where('estado', 'activo')
                                            ->where('id_oferta', $id_oferta)
                                            ->get()
                                            ->map(function($participante){
                                                return $participante->infoEmpresa;
                                            });

            $faltantes = collect();
            foreach($empresas as $key => $empresa){
                $encontrados = $participantesOferta->where('id_empresa', $empresa['id_empresa']);

                if($encontrados->count() == 0){
                    $faltantes->push($empresa->toArray());
                }

            }

            if($faltantes->count() > 0){
                $response->bool = true;
                $response->msg = $faltantes;
            }else{
                $response->bool = false;
                $response->bypass = true;
                $response->msg = 'Tiene precalificación y cumple con los proveedores';
            }

        }else{
            $response->bool = false;
            $response->msg = [];
        }
        return $response;
    }

    public function aprobarOferta($idOferta){

        $ofertasCuadroEconomico = new OfertasCuadroCotizacion();
        $respuestaSolpedsAsociadas = $ofertasCuadroEconomico->obtenerContratosAsociadosAlCuadro($idOferta);
        $solpeds_relacionadas = true;
        /*if($_SESSION["empresaid"] == 8 && $respuestaSolpedsAsociadas->bool){
            $arrSolicitudes = json_decode($respuestaSolpedsAsociadas->msg);
             if(count($arrSolicitudes) == 0){
                $objMaestras = $this->obtenerMaestrasAsociadasOferta($idOferta);
                if($objMaestras->bool){

                    $dataMaestras = json_decode($objMaestras->msg);
                    $banderaModalidad = true;

                    foreach ($dataMaestras as $maestra){
                        $maestra = json_decode($maestra);
                        if((int) $maestra->cod_maestra == 3 && (int) $maestra->cod_item == 7){
                            $banderaModalidad =false;
                        }
                    }
                    //Se valida que la modalidad no sea sondeo de mercado y que aplique la condicion para relacionar solped
                    if($banderaModalidad){
                        $solpeds_relacionadas = false;
                    }
                }else{
                    $solpeds_relacionadas = false;
                }

            }
        }*/

        if($_SESSION["empresaid"] == 20){
            $consultaOferta = Oferta::where('id', $idOferta)->first();
            
            if($consultaOferta->tipo != 'estudio'){
                $validacionSolpedSolicitud = $this->validarSolicitudSolped($idOferta);
                if(!$validacionSolpedSolicitud->bool){
                    return $validacionSolpedSolicitud;
                }
                
                $validado = $this->validarProveedoresPrecalificados($idOferta);
                if(!$validado->bool && !isset($validado->bypass)){
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Debe seleccionar una precalificación al momento de seleccionar una proveedor";
                    return $this->intelcost->response;
                }
            }
        }
        
        if($solpeds_relacionadas){
            $arrParametros = [];
            $arrParametros["empresa"] = $_SESSION["empresaid"];
            $arrParametros["estado_asignado"] = 11;
            $arrParametros["oferta"] = $idOferta;
            $modelo_acciones_oferta = new modelo_acciones_oferta();
            $modelo_acciones_oferta->actualizarEstadoSolpedSAP($arrParametros);

            Seguimiento::setAgregarSeguimientoDetallado(array(
                'tabla' => 'ofertas',
                'columna' => 'id',
                'valor' => $idOferta,
                'registro_id' => $idOferta,
            ), 5, "Aprobar Oferta");

            $SqlOfer = "UPDATE ofertas SET `estado` = 'APROBADA', `usuario_actualizacion` = $_SESSION[idusuario], `fecha_actualizacion` = '".date("Y-m-d H:i:s")."'";
            if($_SESSION['empresaid'] != 14 || $_SESSION['empresaid'] != 26 || $_SESSION['empresaid'] != 27 || $_SESSION['empresaid'] != 20){
                $SqlOfer .= ", usuario_aprobacion = $_SESSION[idusuario], fecha_aprobacion = '".date("Y-m-d H:i:s")."'";
            }
            $SqlOfer .= " WHERE `id`= ? AND (estado = 'EN APROBACION' || estado = 'ACTIVO')";
            $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int)$idOferta), "Aprobar oferta.");

            Seguimiento::setAgregarSeguimientoDetallado(array(
                'tabla' => 'ofertas',
                'columna' => 'id',
                'valor' => $idOferta,
                'registro_id' => $idOferta,
            ), 5, "Aprobar Oferta", false);

            if($CscUsr->bool){
                
                //Funcion para actualizar solped con un servicio web externo. En caso de fallo no afectara la ejecución de la funcionalidad
                $arrParametros = ["empresa" => $_SESSION["empresaid"],"estado_asignado" => 11,"oferta" => $idOferta];
                $modelo_acciones_oferta->actualizarEstadoSolpedSAP($arrParametros);

                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Oferta actualizada correctamente.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Aprobación de oferta erronea.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "No tiene ninguna solped relacionada para su publicación.";
        }
          return $this->intelcost->response;
    }

    public function ponerEnFirmeOferta($idOferta){
        $SqlOfer = 'UPDATE ofertas SET `estado` = "EN FIRME", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ?';
        $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int)$idOferta), "Aprobar oferta.");
        if($CscUsr->bool){
            if($CscUsr->msg > 0){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Oferta actualizada correctamente";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Aprobación de oferta erronea.";  
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Aprobación de oferta erronea.";
        }
        return $this->intelcost->response;
    }
#modificando
    public function rechazarOferta($data, $obs){
        $estado = "ACTIVO";
        if((int) $data->clienteId == 14 || (int) $data->clienteId == 26 || (int) $data->clienteId == 27 || (int) $data->clienteId == 20){
            $estado = "RECHAZADA";
        }
        $SqlOfer = 'UPDATE ofertas SET `estado` = "'.$estado.'", `usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'" WHERE `id`= ? AND `estado` = "EN APROBACION"';
        $CscUsr = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "i", array((int) $data->id), "Rechazar oferta.");
        if($CscUsr->bool){
            if($CscUsr->msg > 0){
                if((int) $data->clienteId != 14 && (int) $data->clienteId != 26 && (int) $data->clienteId != 27 && (int) $data->clienteId != 20){
                    $contenidoMail  = "<p><b>Estimado(a):</b> ".$data->usuario_creador."</p><br> ";
                    $contenidoMail .= "Le informamos que ".$_SESSION["usuario"].", ha <b>rechazado</b> el evento para previa a la publicación.";
                    $contenidoMail .= "<p style='font-size:12px'>Link de acceso: <a href='".$_SESSION["url"]."'>".$data->clienteNombre."</a></p><br />";
                    if($obs!=""){
                        $obs = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $obs);
                        $contenidoMail  .= "Las observaciones son las siguientes: ". ($obs)."<br>";
                    }
                    $subject = 'Proceso rechazado - '.$data->objeto;
                    $this->enviarEmailSistemaAprobaciones($data->usuario_creador_email, $contenidoMail, $data, $subject);
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "Oferta actualizada correctamente";
                }else{
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "Se ha rechazado el evento ".$data->seq_id.". ";
                    $envioNotificacion = $this->notifcacionAprobacionEvento($data, "RECHAZADA", $obs);
                    if(!$envioNotificacion){
                        $this->intelcost->response->msg .= "No se ha logrado notificar al comprador.";
                    }
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Aprobación de oferta erronea.";  
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Actualizacion de oferta erronea";
        }
        return $this->intelcost->response;
    }

    public function validarOferta($oferta,$tipo){

        if(isset($oferta) && $oferta != ""){
            $flag=true;
            $flagResponse = [];
            $strngResponse ="";
            $ofertaObj = json_decode($oferta);

            /*if($_SESSION["empresaid"] == 20){
                $validado = $this->validarProveedoresPrecalificados($ofertaObj->id);
                if($validado->bool){
                    $flag = false;
                    foreach($validado->msg as $empresaFaltante){
                        array_push($flagResponse, "<b>*</b> Hace falta agregar un contacto de la empresa <b>$empresaFaltante[razonxxx]</b> para continuar con el proceso<br>");
                    }
                }
            }*/
            if($_SESSION['empresaid'] == 25 && $ofertaObj->arrLotesOferta == "[]"){
                $flag = false;
                array_push($flagResponse, "Debe asociar un lote");
            }
            if($ofertaObj->actividad == "" && $_SESSION['empresaid'] != 8){
                $flag = false;
                array_push($flagResponse, "Debe definir una actividad");
            }
            if($ofertaObj->objeto == ""){
                $flag = false;
                array_push($flagResponse, "Debe definir un objeto ");
            }
            if($ofertaObj->descripcion == ""){
                $flag = false;
                array_push($flagResponse, "Debe definir una descripción");
            }
            if(!isset($ofertaObj->presupuesto) || ( $ofertaObj->presupuesto == "" && $ofertaObj->tipooferta != "estudio") ){
                $flag = false;
                array_push($flagResponse, "Debe definir un presupuesto");
            }
            if(strtotime($ofertaObj->fecha_cierre) < strtotime(date("Y-m-d")) && $ofertaObj->maestra1 != "rfq_sin"){
                $flag = false;
                array_push($flagResponse, "La fecha de cierre no puede estar en el pasado");
            }
            if(strtotime($ofertaObj->fecha_cierre) == strtotime(date("Y-m-d")) && $tipo!="edicion"){
                date_default_timezone_set("America/Lima");
                $horaactual = date('H:i', (strtotime(date('H:i'))+60*60)  );
                if($ofertaObj->hora_cierre <= $horaactual ){
                    $flag = false;
                    array_push($flagResponse, "La hora de cierre no puede estar en el pasado, ni en la hora siguiente al inicio");
                }
            }
            if(strtotime($ofertaObj->fecha_inicio) == strtotime($ofertaObj->fecha_cierre) && $ofertaObj->maestra1 != "rfq_sin"){
                if(isset($ofertaObj->hora_inicio)){
                    if($ofertaObj->hora_inicio >= $ofertaObj->hora_cierre){
                        $flag = false;
                        array_push($flagResponse, "La fecha de cierre debe de ser mayor o igual a la fecha de inicio");
                    }
                }
            }
            if($ofertaObj->fecha_cierre < $ofertaObj->fecha_inicio){
                $flag = false;
                array_push($flagResponse, "La fecha de cierre debe de ser mayor o igual a la fecha de inicio");
            }
            if($tipo != "edicion" && $ofertaObj->maestra1 != "rfq_sin"){
                if(strtotime($ofertaObj->fecha_inicio) < strtotime(date("Y-m-d"))){
                    $flag = false;
                    array_push($flagResponse, "La fecha de inicio no puede estar en el pasado");
                }
                if(strtotime($ofertaObj->fecha_inicio) == strtotime(date("Y-m-d"))){
                    date_default_timezone_set("America/Lima");
                    $horaactual = date('H:i');
                    if($ofertaObj->hora_inicio <= $horaactual ){
                        $flag = false;
                        array_push($flagResponse, "La hora y fecha de inicio no puede estar en el pasado");
                    }
                }
                if($_SESSION["empresaid"] != "6" && $_SESSION["empresaid"] != "1" && $_SESSION["empresaid"] != "9"){
                    if( count($ofertaObj->documentos_oferta) <= 0){
                        $flag = false;
                        array_push($flagResponse, "Debe definir documentos / contenido");
                    }
                }
                if( count($ofertaObj->documentos_oferente) <= 0){
                    $flag = false;
                    array_push($flagResponse, "Debe definir documentos solicitados a oferentes");
                }
                if( count($ofertaObj->participantes) <= 0){
                    if ($_SESSION["empresaid"] == "10" && $ofertaObj->modalidad_seleccion == 'sol_pub_of') {
                        
                    }else{
                        if($ofertaObj->tipo != "abierta" && $ofertaObj->tipo != "publico"){
                            //VALIDACIÓN PARA OFERENTES CONCONCRETO.
                            if(!($_SESSION['empresaid'] == 6 && $tipo == "solicitarAprobacion" && $ofertaObj->requiere_aprobacion == "si")) {
                                $flag = false;
                                array_push($flagResponse, "Debe definir participantes / Oferentes");
                            }
                        }
                    }
                }
                //validacion de regla de escritura para cuadro económico.
                //if($_SESSION['empresaid'] == 1 || $_SESSION['empresaid'] == 6 || $_SESSION['empresaid'] == 8 || $_SESSION['empresaid'] == 10 || $_SESSION['empresaid'] == 14 ){
                if($_SESSION['empresaid'] == 1 || $_SESSION['empresaid'] == 6 || $_SESSION['empresaid'] == 8 || $_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 200){
                    $arrExcel = array();
                    $ofertasCuadroEconomico = new OfertasCuadroCotizacion();
                    $objCuadro = $ofertasCuadroEconomico->consultarDatosCuadroCotizacion((string) $ofertaObj->id);
                    if($objCuadro->bool){
                        $dataExcel = json_decode($objCuadro->msg);
                        $arrExcel = $dataExcel->excel;
                        if(isset($arrExcel) && count($arrExcel) > 0){
                            $banderaValor = false;
                            foreach ($arrExcel as $celda) {
                                if(isset($celda->valor)){
                                    if(!empty($celda->valor)){
                                        $banderaValor = true;
                                    }
                                }
                            };
                            /* if($banderaValor){
                                $objReglas = $ofertasCuadroEconomico->consultarReglas((string) $ofertaObj->id, "escritura_proveedor");
                                if($objReglas->bool){
                                    $reglas = (array) json_decode($objReglas->msg);
                                    if(count($reglas) == 0){
                                        $flag = false;
                                        array_push($flagResponse, "El cuadro económico debe tener regla de escritura para los oferentes.");
                                    }
                                }
                            } */
                        }
                        //VALIDACION PARA ODL EN CUANTO A LA MAESTRA MODALIDAD (sondeo de mercado).
                        if($_SESSION['empresaid'] == 8){
                            $objMaestras = $this->obtenerMaestrasAsociadasOferta($ofertaObj->id);
                            if($objMaestras->bool){
                                $dataMaestras = json_decode($objMaestras->msg);
                                $banderaModalidad = false;
                                $codModalidad = "";
                                $modalidad = "";
                                foreach ($dataMaestras as $maestra) {
                                    $maestra = json_decode($maestra);
                                    if((int) $maestra->cod_maestra == 3){
                                        $banderaModalidad = true;
                                        $codModalidad = (int) $maestra->cod_item;
                                        $modalidad = $maestra->titulo_item;
                                    }
                                }
                                if($banderaModalidad){
                                    // / es el cod_item en la tabla mst_general_titulos_items_modulos en donde se almacenan las maestras de ODL. 
                                    if($codModalidad != 7){
                                        if(count($arrExcel) == 0){
                                            $flag = false;
                                            array_push($flagResponse, "No se ha creado el cuadro económico para la modalidad $modalidad .");
                                        }
                                    }
                                }else{
                                    $flag = false;
                                    array_push($flagResponse, "No se ha seleccionado una modalidad.");
                                }
                            }else{
                                $flag = false;
                                array_push($flagResponse, "Error al validar maestras.");
                            }
                        }
                    }else{
                        $flag = false;
                        array_push($flagResponse, "Se presentó un error al consultar cuadro de cotización.");
                    }
                }
            }
            
            if($flag){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg ="Oferta valida";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = implode(", ", $flagResponse);
                $this->intelcost->response->array = json_encode($flagResponse);
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "oferta no encontrada";
        }
        return $this->intelcost->response;
    }

    public function contadorOfertas(){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $Sqlofe   ="SELECT count(id) cont, ";
            $Sqlofe  .=" IFNULL(sum(case when tipo = 'cerrada' then 1 else 0 end), 0) countCerrada, ";
            $Sqlofe  .=" IFNULL(sum(case when tipo = 'abierta' then 1 else 0 end), 0) countAbierta, ";
            $Sqlofe  .=" IFNULL(sum(case when tipo = 'publico' then 1 else 0 end), 0) countPublica, ";
            $Sqlofe  .=" IFNULL(sum(case when tipo = 'rfq' then 1 else 0 end), 0) countRfq, ";
            $Sqlofe  .=" IFNULL(sum(case when tipo = 'estudio' then 1 else 0 end), 0) countEstudios ";
            $Sqlofe  .=" FROM ofertas t1";
            $Sqlofe  .=" WHERE id_cliente='".$_SESSION["empresaid"]."' AND estado != 'INACTIVO'";
            $CscOferta= $dbConection->query($Sqlofe);
            if($CscOferta){
                $conSearch = $CscOferta->fetch_assoc();
                $res["total"] = $conSearch["cont"];
                $res["Ofecerradas"] = $conSearch["countCerrada"];
                $res["Ofeabiertas"] = $conSearch["countAbierta"];
                $res["Ofepublicas"] = $conSearch["countPublica"];
                $res["ofertasRFQ"] = $conSearch["countRfq"];
                $res["ofertasEstudios"] = $conSearch["countEstudios"];
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $res;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "0";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Conexion erronea oferta";
        }
        return $this->intelcost->response;
    }

    public function validacionAperturaSobreEconomico($id_contacto, $id_oferta){
        $aprobacionTecnica = 'Sin calificar';
        $aprobacionLegal = 'Sin calificar';
        Database::transaction(function () use ($id_contacto, $id_oferta, &$aprobacionTecnica, &$aprobacionLegal){
            /**
             * Consultar resultados criterios técnicos
             */
            $ofertaEvaluacionProveedor = OfertaEvaluacionProveedorResultadoTecnico::where('id_oferta', $id_oferta)
                        ->where('id_usuario_proveedor', $id_contacto)
                        ->activo()
                        ->first();
            /**
             * Validación sobre los resultados de los criterios
             */
            if($ofertaEvaluacionProveedor && $ofertaEvaluacionProveedor->resultado_final == 'Cumple'){
                if($ofertaEvaluacionProveedor->bloqueo == 1){
                    $aprobacionTecnica =  'Cumple';
                }else{
                    $aprobacionTecnica =  'Cumple pero falta terminar evaluación';
                }
            }elseif($ofertaEvaluacionProveedor && $ofertaEvaluacionProveedor->resultado_final == 'No cumple'){
                $aprobacionTecnica =  'No cumple';
            }

            /**
             * Consultar documentos oferentes
             */
            $ofertaEvaluacionProveedorDocumentos = OfertaDocumentosOferentes::where('oferta_id', $id_oferta)
                        ->activo()
                        ->with(['participacionProveedor' => function($query) use ($id_contacto, $id_oferta){
                            $query->where('id_usuario', $id_contacto)
                                ->with(['calificacionParticipacionProveedor' => function($query){
                                    $query->where('estado', 'activo');
                                }]);
                        }])
                        ->get();

            /**
             * Validación sobre los documentos calificados
             */
            if($ofertaEvaluacionProveedorDocumentos->count() > 0 && $ofertaEvaluacionProveedorDocumentos->where('evaluable', 'si') > 0){
                //$cantidadOriginalTotal = $ofertaEvaluacionProveedorDocumentos->count();
                $cantidades = [
                    'cantidadOriginalTotalEvaluable' => $ofertaEvaluacionProveedorDocumentos->where('evaluable', 'si')->count(),
                    'cantidadCalificado' => 0,
                    'cantidadCumple' => 0,
                    'cantidadNocumple' => 0,
                    'cantidadIncompleto' => 0,
                    'cantidadSinAprobacion' => 0
                ];

                $ofertaEvaluacionProveedorDocumentos->where('evaluable', 'si')
                        ->map(function($documento) use (&$cantidades){
                            if($documento->participacionProveedor->count() > 0){
                                $validacionCantidades = [
                                    'validacionCumple' => 0,
                                    'validacionNoCumple' => 0,
                                    'validacionSinAprobacion' => 0,
                                    'validacionAprobacionIncompleta' => 0,
                                    'cantidadDocumentosPorValidar' => $documento->participacionProveedor->count(),
                                ];
                                
                                foreach ($documento->participacionProveedor as $key_prov => $participanteProveedor) {
                                    if($participanteProveedor->calificacionParticipacionProveedor && $participanteProveedor->calificacionParticipacionProveedor->cumpleNoCumple == "Cumple"){
                                        $validacionCantidades['validacionCumple']++;
                                        
                                    }elseif($participanteProveedor->calificacionParticipacionProveedor && $participanteProveedor->calificacionParticipacionProveedor->cumpleNoCumple == "No cumple"){
                                        $validacionCantidades['validacionNoCumple']++;
                                    }
                                    
                                    if(is_null($participanteProveedor->calificacionParticipacionProveedor->aprobacionDocumento)){
                                        $validacionCantidades['validacionSinAprobacion']++;
                                    }
                                }
                                
                                if($validacionCantidades['validacionCumple'] == $validacionCantidades['cantidadDocumentosPorValidar']){
                                    $cantidades['cantidadCumple']++;
                                    $cantidades['cantidadCalificado']++;
                                }elseif($validacionCantidades['validacionNoCumple'] == $validacionCantidades['cantidadDocumentosPorValidar']){
                                    $cantidades['cantidadNocumple']++;
                                    $cantidades['cantidadCalificado']++;
                                }elseif(
                                        ($validacionCantidades['validacionCumple'] > 0 && $validacionCantidades['validacionCumple'] != $validacionCantidades['cantidadDocumentosPorValidar']) &&
                                        ($validacionCantidades['validacionNoCumple'] > 0 && $validacionCantidades['validacionNoCumple'] != $validacionCantidades['cantidadDocumentosPorValidar'])
                                    ){
                                    $cantidades['cantidadCalificado']++;
                                    $cantidades['cantidadIncompleto']++;
                                }

                                if($validacionCantidades['validacionSinAprobacion'] == $validacionCantidades['cantidadDocumentosPorValidar']){
                                    $cantidades['cantidadSinAprobacion']++;
                                }

                            }
                        });

                if($cantidades['cantidadCalificado'] == $cantidades['cantidadOriginalTotalEvaluable']){
                    if($cantidades['cantidadSinAprobacion'] == 0 && $cantidades['cantidadCumple'] == $cantidades['cantidadOriginalTotalEvaluable']){
                        $aprobacionLegal = 'Cumple';
                    }elseif($cantidades['cantidadNocumple'] == 0 && $cantidades['cantidadCumple'] == $cantidades['cantidadOriginalTotalEvaluable']){
                        $aprobacionLegal = 'Calificado pero falta aprobación (Cumplen)';
                    }elseif($cantidades['cantidadSinAprobacion'] == 0 && $cantidades['cantidadNocumple'] == $cantidades['cantidadOriginalTotalEvaluable']){
                        $aprobacionLegal = 'No cumple';
                    }elseif($cantidades['cantidadCumple'] == 0 && $cantidades['cantidadNocumple'] == $cantidades['cantidadOriginalTotalEvaluable']){
                        $aprobacionLegal = 'Calificado pero falta aprobación (No cumplen)';
                    }elseif($cantidades['cantidadSinAprobacion'] == 0){
                        $aprobacionLegal = 'No cumple';
                    }elseif($cantidades['cantidadSinAprobacion'] > 0){
                        $aprobacionLegal = 'Cumple '.$cantidades['cantidadCumple'].' documento(s) y no cumplen '.$cantidades['cantidadNocumple'].' documento(s) - Falta aprobación';
                    }
                }elseif($cantidades['cantidadIncompleto'] > 0 || $cantidades['cantidadCalificado'] > 0){
                    $aprobacionLegal = 'Cumple '.$cantidades['cantidadCumple'].' documento(s) y no cumplen '.$cantidades['cantidadNocumple'].' documento(s) - Calificación incompleta '.$cantidades['cantidadOriginalTotalEvaluable'].' documento(s)';
                }

            }

            // No aplica o no se adicionaron
            if(!$ofertaEvaluacionProveedor){
                $tieneCriteriosTecnicos = OfertaCriteriosEvaluacion::where('id_oferta', $id_oferta)->count();
                if($tieneCriteriosTecnicos == 0){
                    $aprobacionTecnica = "No aplica / No se adicionaron";
                }
            }

            if($ofertaEvaluacionProveedorDocumentos->count() == 0){
                $aprobacionLegal = "No aplica / No se adicionaron";
            }
        });
        return [
                'tecnica' => $aprobacionTecnica, 
                'legal' => $aprobacionLegal, 
                'conclusion' => 
                    $aprobacionTecnica == 'Cumple' && $aprobacionLegal == 'Cumple' || 
                    $aprobacionTecnica == 'No aplica / No se adicionaron' && $aprobacionLegal == 'Cumple' ||
                    $aprobacionTecnica == 'Cumple' && $aprobacionLegal == 'No aplica / No se adicionaron' ||
                    $aprobacionTecnica == 'No aplica / No se adicionaron' && $aprobacionLegal == 'No aplica / No se adicionaron'
        ];
    }

    public function usuariosParticipantes($idOferta, $estado){
        $Sqlofe = "SELECT * FROM oferta_participantes WHERE id_oferta = ? AND estado != 'eliminado'";
        $parametros = array((int) $idOferta);
        $tipoParametros = "i";
        if($estado == "oferta_enviada"){
            $estado = "ofe_enviada";
        }
        if($estado != "all"){
            $Sqlofe .= " AND estado_participacion = ? ";
            array_push($parametros, $estado);
            $tipoParametros .= "s";
        }
        $SqlParticipantes = $this->intelcost->prepareStatementQuery('cliente', $Sqlofe, 'select', true, $tipoParametros, $parametros, "Obtener listado usuarios participantes.");
        
        if($SqlParticipantes->bool){
            if($SqlParticipantes->msg->num_rows > 0){
                $participantes = array();
                while( $participante = $SqlParticipantes->msg->fetch_assoc()){
                    if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                        $participante['estadosAprobaciones'] = $this->validacionAperturaSobreEconomico($participante['id_usuario'], $idOferta);
                    }
                    array_push($participantes, $participante);
                }
                $arrParticipantes = array();
                foreach ($participantes as $participante) {
                    //Se adiciona el comentario por que no se esta usando en "obtenerOfertaEnviada" en la ejecucion del proceso y si esta generando carga al sistemas
                    /*if($participante["estado_participacion"] == "ofe_enviada"){
                        $ofertaEnviada = $this->obtenerOfertaEnviada($participante["id_usuario"], $idOferta);
                        if($ofertaEnviada->bool){
                            $archivosEnviados = json_decode($ofertaEnviada->msg);
                            if($archivosEnviados->archivos){
                                $participante["oferta_enviada"] = $archivosEnviados;
                            }else{
                                $participante["oferta_enviada"] = "[]";
                            }
                        }else{
                            $participante["oferta_enviada"] = "[]";
                        }
                    }*/

                    // Obtener adjudicaciones 
                    if($_SESSION['empresaid'] == 25){
                       $adjudicacion = OfertaAdjudicaciones::where('id_usuario', $participante["id_usuario"])
                                            ->where('id_oferta', $idOferta)
                                            ->first();
                        if($adjudicacion){
                            $adjudicacion = $adjudicacion->toArray();
                        }else{
                            $adjudicacion = '[]';
                        }
                        
                        $participante["adjudicacion_ingresada"] = $adjudicacion;
                    }
                    
                    if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                        $participante['resultadoEvaluacionTecnica'] = OfertaEvaluacionProveedorResultadoTecnico::where('id_oferta', $idOferta)
                                                                    ->activo()
                                                                    ->where('id_usuario_proveedor', $participante['id_usuario'])
                                                                    ->with(['obtenerCriteriosEvaluadosTecnicos' => function($query){
                                                                        $query->activo();
                                                                    }])
                                                                    ->first();
                    }

                    $participante["oferta_enviada"] = "[]";
                    $participante["estado_participacion_cast"] = $this->castearEstadoParticipacion($participante["estado_participacion"]);
                    
                    $participanteInfoStr = $this->modelo_usuario->obtenerUsuarioIntelcost($participante["id_usuario"]);
                    if($participanteInfoStr != "[]"){
                        $participanteInfo= json_decode($participanteInfoStr);
                        
                        if($participanteInfo){
                            $participante["proveedor"] = $participanteInfo->razonxxx;
                            if(isset($participanteInfo->carta_invitacion)){
                                $participante["carta_invitacion"] = $participanteInfo->carta_invitacion;
                            }else{
                                $participante["carta_invitacion"] =  "";
                                
                            }
                            $participante["contacto"] = $participanteInfo->usrnomxx;
                        }else{
                            $participante["carta_invitacion"] =  "";
                            $participante["contacto"] =  "";
                            $participante["proveedor"]  = "proveedor no identificado";
                        }
                    }else{
                        $participante["proveedor"] = 'No encontrado';
                        $participante["contacto"] = 'No encontrado';
                    }

                    if(!isset($participante["observaciones"])){
                        $participante["observaciones"]= "";
                    }
                    array_push($arrParticipantes, $participante);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrParticipantes);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontraron participantes.";
            }
        }else{
            $this->intelcost->response = $SqlParticipantes;
        }
        return $this->intelcost->response;
    }

    public function obtenerPreciosLotesOfertaEnviada($idUsuario,$idOferta){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $idOferta = $this->intelcost->realEscapeStringData($idOferta);
            $arrLotesOferta=[];
            $SqlLotesOferta="SELECT OL.id_lote,OL.nombre_lote,CA.id cod_sobre,CA.nombre sobre ";
            $SqlLotesOferta.="FROM oferta_lotes AS OL INNER JOIN capitulos CA ON OL.cod_sobre = CA.id ";
            $SqlLotesOferta.="WHERE OL.cod_oferta='".$idOferta."' AND OL.estado=1 ORDER BY CA.id; ";
            $CscLotesOferta = $dbConection->query($SqlLotesOferta);
            if($CscLotesOferta){
                $idUsuario = $this->intelcost->realEscapeStringData($idUsuario);
                while ($rowLotes = $CscLotesOferta->fetch_assoc()) {
                    
                    $resItems=$this->obtenerItemsLoteOfertaParticipante($rowLotes['id_lote'],$idUsuario);
                    if($resItems->bool){
                        $rowLotes['itemsLote']=$resItems->msg;
                    }else{
                        $rowLotes['itemsLote']=false;
                    }
                    $resPosicion=$this->obtenerPosicionLoteOferta($rowLotes['id_lote'],$idUsuario);
                    if($resPosicion->msg){
                        $rowLotes['posicionLote']=$resPosicion->msg;
                    }else{
                        $rowLotes['posicionLote']="N/A";
                    }   
                    array_push($arrLotesOferta,json_encode($rowLotes));
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($arrLotesOferta);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Consulta erronea obtener lotes ofertas";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Conexion erronea oferta";
        }
        return $this->intelcost->response;
    }

    public function obtenerInformacionFlujoUsuario($id_objeto, $id_modulo, $id_usuario){
        $sql_informacion_flujo_aprobacion = "SELECT * FROM flujos_aprobacion_perfil_vs_usuario WHERE id_objeto = ? AND id_modulo = ? AND id_usuario = ?";
        $csc_informacion_flujo_aprobacion = $this->intelcost->prepareStatementQuery('cliente', $sql_informacion_flujo_aprobacion, 'select', true, "iii", array((int) $id_objeto, (int) $id_modulo, (int) $id_usuario), "Obtener flujo aprobación usuario asignado");
        if($csc_informacion_flujo_aprobacion->msg->num_rows > 0){
            // $row = $csc_informacion_flujo_aprobacion->msg->fetch_assoc(); // Para descomentar si es necesario enviar dato
            return true;
        }else{
            return false;
        }
    }

    public function obtenerOfertaEnviada($idUsuario,$idOferta){

        $ofeinfo = $this->obtenerInformacionBasicaOfertaSha256IDnoCryp(hash('sha256',$idOferta));
        if($ofeinfo->bool == true){
            $ofeinfoJson = json_decode($ofeinfo->msg);
            $usuario_creador_id = $ofeinfoJson->usuario_creacion;
        }else{
            $usuario_creador_id = 0;
        }

        $Sqlofe = "SELECT * FROM oferta_documentos_ofertascliente t1 LEFT JOIN ( SELECT id as idOfeDoOfe,doc_id, titulo,descripcion,obligatorio,sob_id, sob_nombre, sob_obl, fecha_creacion as fecha_creacion_doc, evaluable, tipo_evaluacion, parametro_evaluacion FROM oferta_documentos_oferentes a1 LEFT JOIN (SELECT nombre sob_nombre,id sob_id, obligatorio sob_obl FROM capitulos WHERE cliente_id= ? ) a2 ON a2.sob_id = a1.sobre WHERE estado = 'activo' ) t2 ON t2.idOfeDoOfe = t1.id_documento_oferente LEFT JOIN (SELECT id docIdInterno, documento_id docId, observaciones, file_url, cumpleNoCumple, resultado_evaluacion, subsanable, usuario_id FROM oferta_evaluacion_documento where estado = 'activo') of ON t1.id = of.docId LEFT JOIN( SELECT id as id_evaluador, nombre as nombre_evaluador FROM usuarios) US ON of.usuario_id = US.id_evaluador WHERE id_oferta = ? AND  id_usuario = ? ORDER BY t2.sob_id, t2.fecha_creacion_doc";

        $sqlOfeEnviada = $this->intelcost->prepareStatementQuery('cliente', $Sqlofe, 'select', true, "iii", array((int)$_SESSION["empresaid"], (int) $idOferta, (int) $idUsuario), "Obtener oferta enviada.");

        if($sqlOfeEnviada->bool){
            if($sqlOfeEnviada->msg->num_rows > 0){
                $arrayDocumuentosParticipante = array();
                while( $archivoParticipacion = $sqlOfeEnviada->msg->fetch_assoc()){
                    if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                        $archivoParticipacion['criterios'] = OfertaDocumentosCriteriosOferentes::where('id_item_documento', $archivoParticipacion['id_documento_oferente'])
                                                                    ->where('estado', 'Activo')
                                                                    ->with(['evaluacionesCriterios' => function($query) use ($archivoParticipacion){
                                                                        $query->where('id_item_documento_interno', $archivoParticipacion['docId'])
                                                                            ->ultimaEvaluacion();
                                                                    }])
                                                                    ->get();
                    }
                    array_push($arrayDocumuentosParticipante, $archivoParticipacion);
                }
                $archivoParticipaciones = array();
                foreach ($arrayDocumuentosParticipante as $archivoParticipacion) {
                    if($archivoParticipacion["file_url"] != ""){
                        $archivoParticipacion['tipo'] = "archivo";
                        $archivoParticipacion['file_url'] = $this->intelcost->generaRutaServerFiles($archivoParticipacion['file_url'], 'cliente');
                        $archivoParticipacion['contenido'] = $archivoParticipacion['file_url'];                 
                    }else{
                        $archivoParticipacion['contenido'] = $archivoParticipacion['contenido'];
                    }
                    $archivoParticipacion['evaluable'] = ($archivoParticipacion['evaluable'] == "si") ? true : false;
                    //TEPORALMENTE PARA QUE SE PUEDAN EVALUAR DOSCUMENTOS 
                    $fecha_creacion = strtotime(date('Y-m-d H:i:s', strtotime($archivoParticipacion['fecha_creacion_doc'])));
                    $fecha_limite = strtotime(date('Y-m-d H:i:s', strtotime("2019-01-11 00:00:00")));
                    if($fecha_creacion <= $fecha_limite){
                        $archivoParticipacion['evaluable'] = true;
                    }
                    switch ($archivoParticipacion['tipo_evaluacion']) {
                        case "puntuable":
                            $archivoParticipacion['id_tipo_evaluacion'] = 1;
                            break;
                        case "cumple - no cumple":
                            $archivoParticipacion['id_tipo_evaluacion'] = 2;
                            break;
                        default:
                            $archivoParticipacion['id_tipo_evaluacion'] = 2;
                            $archivoParticipacion['tipo_evaluacion'] = "cumple - no cumple";
                            break;
                    }
                    $archivoParticipacion['ultima_evaluacion'] = "";
                    if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION["empresaid"] == 25 || $_SESSION["empresaid"] == 9){
                        $queryAntEVal = "SELECT observaciones, resultado_evaluacion, cumpleNoCumple FROM oferta_evaluacion_documento WHERE documento_id = $archivoParticipacion[id] AND estado = 'activo' ORDER BY id DESC limit 1";
                        $sqlAntEval = $this->intelcost->prepareStatementQuery('cliente', $queryAntEVal, 'select', false, "", "", "Obtener última evaluación.");
                        if($sqlAntEval->bool){
                            if($sqlAntEval->msg->num_rows > 0){
                                $resAntEval = $sqlAntEval->msg->fetch_assoc();
                                $archivoParticipacion['ultima_evaluacion'] = $resAntEval;
                            }
                        }
                    }
                    array_push($archivoParticipaciones, $archivoParticipacion);
                }


                // Obtener los accesos del usuario actual
                if(isset($_SESSION["idusuario"]) && !empty($_SESSION["idusuario"])){
                    $queryUsuInterno = "SELECT accesos FROM oferta_usuarios_internos t1 WHERE id_oferta = $idOferta AND id_usuario = $_SESSION[idusuario] AND estado = 'activo' LIMIT 1";
                    $sqlUsuInterno = $this->intelcost->prepareStatementQuery('cliente', $queryUsuInterno, 'select', false, "", "", "Obtener permisos usuario interno.");
                    $misaccesos = array();
                    if($sqlUsuInterno->bool){
                        if($sqlUsuInterno->msg->num_rows > 0){
                            $usuarioInterno = $sqlUsuInterno->msg->fetch_assoc();
                            foreach(json_decode($usuarioInterno["accesos"]) as $acceso){
                                // agregamos el id del documento oferente
                                array_push($misaccesos, $acceso->fileId);
                            }
                        }
                    }
                }else{
                    $misaccesos = array();
                }
                if(isset($_SESSION["idusuario"]) && !empty($_SESSION["idusuario"])){
                    $queryAccesosAprob = "SELECT accesos FROM `oferta_usuarios_aprobadores` WHERE id_oferta = $idOferta AND id_usuario_aprobador = $_SESSION[idusuario] AND estado = 'activo' ";
                    $sqlUsuAccesosAprob = $this->intelcost->prepareStatementQuery('cliente', $queryAccesosAprob, 'select', false, "", "", "Obtener permisos usuario aprobador.");
                    $accesosAprobador = array();
                    if($sqlUsuAccesosAprob->bool){
                        $respAccesos = $sqlUsuAccesosAprob->msg->fetch_assoc();
                        $accesosAprobador = (array) json_decode($respAccesos['accesos']);
                    }
                }else{
                    $accesosAprobador = array();
                }

                $contSobresObligatorios = 0;
                $contSobres = 0;
                $sobreSwitcher = NULL;
                $arrArchivosOferta  = [];
                foreach ($archivoParticipaciones as $archivoParticipacion) {
                    $banderaAccesos = false;

                    if( $_SESSION['empresaid'] != 10 && $_SESSION['empresaid'] != 14 && $_SESSION['empresaid'] != 26 && $_SESSION['empresaid'] != 27 && $_SESSION['empresaid'] != 20 && $_SESSION['empresaid'] != 25){
                        // se elimina el tipo de usuario 3 de la condicional o ya que afecta la visualizacion general y los permisos de quien ve que cosas
                        if(($_SESSION["Tipousuario"] =="3"  && $usuario_creador_id == $_SESSION["idusuario"]) ||( $_SESSION["Tipousuario"] =="2" || $_SESSION["Tipousuario"] =="4" ) || in_array($archivoParticipacion["id_documento_oferente"], $misaccesos) || in_array($archivoParticipacion["doc_id"], $misaccesos) ){
                            $banderaAccesos = true;
                        }
                    }else{
                        if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION["empresaid"] == 25){
                            if( (($_SESSION["Tipousuario"] =="3" || $_SESSION["Tipousuario"] =="2" || $_SESSION["Tipousuario"] =="4")) || (in_array($archivoParticipacion["id_documento_oferente"], $misaccesos) || in_array($archivoParticipacion["doc_id"], $misaccesos)  || in_array($archivoParticipacion["id_documento_oferente"], $accesosAprobador) )) {
                                $banderaAccesos = true;
                            }
                        }else if($_SESSION['empresaid'] == 10){
                            //
                            $perfiles_consulta_ofertas_metro = [5,8,24,28,48,7,10,11,27,49];
                            if (isset($_SESSION["perfilId"]) && (in_array ($_SESSION["perfilId"],$perfiles_consulta_ofertas_metro))  ) {
                                $banderaAccesos = true;
                            }
                            if(($_SESSION["Tipousuario"] =="3" || $_SESSION["Tipousuario"] =="2" || $_SESSION["Tipousuario"] =="4") || in_array($archivoParticipacion["id_documento_oferente"], $misaccesos)){
                                $banderaAccesos = true;
                            }
                            if ($this->obtenerInformacionFlujoUsuario($idOferta,14, $_SESSION['idusuario'])) {
                                $banderaAccesos = true;
                            }
                        }
                    }
                    
                    if($banderaAccesos){
                        $archivoParticipacion["ruta"] = $this->intelcost->generaRutaServerFiles($archivoParticipacion["ruta"], "proveedores");
                        $flag_extension_compresion = "true";
                        if( strpos($archivoParticipacion["ruta"], '.rar') !== false || strpos($archivoParticipacion["ruta"], '.7zip') !== false|| strpos($archivoParticipacion["ruta"], '.7z') !== false){
                            if(strpos($archivoParticipacion["ruta"], '.rar') !== false){
                                $flag_extension_compresion = '.rar';
                            }else if(strpos($archivoParticipacion["ruta"], '.7z') !== false){
                                $flag_extension_compresion = '.7z';
                            }else{
                                $flag_extension_compresion = '.7zip';
                            }
                        }
                        $archivoParticipacion["rutazip"] = '';

                        //Parametrizando datos nulos
                        if($archivoParticipacion["sob_id"] == NULL){
                            $archivoParticipacion["sob_id"] = 0;
                            $contSobresObligatorios = $contSobresObligatorios;
                        }
                        if($archivoParticipacion["sob_obl"] == NULL){
                            $archivoParticipacion["sob_obl"] = 0;
                        }
                        if($archivoParticipacion["sob_nombre"] == NULL){
                            $archivoParticipacion["sob_nombre"] = "Otros";
                        }

                        //Contador de sobres para evaluar si cumple con las calificaciones
                        if($sobreSwitcher !== $archivoParticipacion["sob_id"] ){
                            $contSobres++;
                            if($archivoParticipacion["sob_obl"] == 1){
                                $contSobresObligatorios++;
                            }
                            $sobreSwitcher = $archivoParticipacion["sob_id"];
                        }
                        $archivoParticipacion["sob_nombre"] = ($archivoParticipacion["sob_nombre"]);
                        $archivoParticipacion["descripcion"] = ($archivoParticipacion["descripcion"]);
                        $archivoParticipacion["titulo"] = ($archivoParticipacion["titulo"]);
                        $archivoParticipacion["tipo"] = isset($archivoParticipacion["tipo"]) ? $archivoParticipacion["tipo"] : "";
                        if( $archivoParticipacion["tipo"] ==""){
                            $archivoParticipacion["tipo"] = "archivo";
                        }else if ($archivoParticipacion["tipo"] =="bool") {
                            $archivoParticipacion["tipo"] = "Si / No";
                        }else if ($archivoParticipacion["tipo"] =="select") {
                            $archivoParticipacion["tipo"] = "Selección única";
                        }else if ($archivoParticipacion["tipo"] =="check") {
                            $archivoParticipacion["tipo"] = "Selección mútiple";
                        }else if ($archivoParticipacion["tipo"] =="texto") {
                            $archivoParticipacion["tipo"] = "Texto";
                        }

                        if($archivoParticipacion["titulo"] != NULL){
                            array_push($arrArchivosOferta, $archivoParticipacion);
                        }
                    }
                }

                $resOfertaEnviada["archivos"] = $arrArchivosOferta;
                $sobreEconomicoPermiso = true;
                if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                    $validacionSobres = $this->validacionAperturaSobreEconomico($idUsuario, $idOferta);
                    if($validacionSobres['conclusion']){
                        $sobreEconomicoPermiso = true;
                    }else{
                        $sobreEconomicoPermiso = false;
                    }
                }

                if($sobreEconomicoPermiso){
                    $ofertaLotesEnviada = $this->obtenerPreciosLotesOfertaEnviada($idUsuario,$idOferta);
                    if($ofertaLotesEnviada->bool){
                        $resOfertaEnviada["oferta_lotes_enviados"] = $ofertaLotesEnviada->msg;
                    }else{
                        $resOfertaEnviada["oferta_lotes_enviados"] = false;
                    }
                }else{
                    $resOfertaEnviada["oferta_lotes_enviados"] = false;
                    $resOfertaEnviada["oferta_lotes_enviados_permiso"] = $sobreEconomicoPermiso;
                }

                $calificaciones_sobres_proveedor_mod = $this->modelo_capitulos->obtenerCalificacionesProveedorOferta($idUsuario,$idOferta);

                if($calificaciones_sobres_proveedor_mod->bool){
                    $calificaciones_sobres_proveedor = json_decode($calificaciones_sobres_proveedor_mod->msg);
                    $resOfertaEnviada["calificacion_sobres"] = $calificaciones_sobres_proveedor->arrcalificacionesProveedor;
                    $resOfertaEnviada["calificacion_sobres_detalles"] = $calificaciones_sobres_proveedor->arrcalificacionesProveedorDetalles;
                }else{
                    $resOfertaEnviada["calificacion_sobres"] = [];
                    $resOfertaEnviada["calificacion_sobres_detalles"] = [];
                }
                
                $resOfertaEnviada["contSobres"] = $contSobres;
                $resOfertaEnviada["contSobresObligatorios"] = $contSobresObligatorios;

                $resOfertaEnviada["aprobadores"] = [];
                $objAprobadoresOferta = $this->obtenerUsuariosAprobadoresDocumentos($idOferta);
                if($objAprobadoresOferta->bool){
                    $resOfertaEnviada["aprobadores"] = $objAprobadoresOferta->msg;
                }

                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode($resOfertaEnviada);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontraron ofertas enviadas.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al obtener oferta enviada.";
        }

        return $this->intelcost->response;
    }

    public function castearEstadoParticipacion($estado){
        $casteo = "";
        switch ($estado) {
            case 'ofe_enviada':
            $casteo = "Oferta Enviada";
            break;
            case 'inactivo':
            $casteo = "Oferta no consultada";
            break;
            case 'ofe_consultada':
            $casteo = "Oferta consultada";
            break;
            case 'ofe_declinada':
            $casteo = "Oferta declinada";
            break;
            default:
            $casteo = "Estado no disponible";
            break;
        }
        return $casteo;
    }

    public function castearTipoDocumento($tipoDocumento){
        $tipo = "";
        switch($tipoDocumento){
            case 'texto': $tipo = "Texto"; break;
            case 'archivo': $tipo = "Archivo"; break;
            case 'select': $tipo = "Selección múltiple"; break;
            case 'check': $tipo = "Selección única"; break;
            case 'bool': $tipo = "Si/no"; break;
            default: $tipo = "Archivo"; break;
        }
       return $tipo;
    }

    protected function obtenerNombreRegional($idRegional){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $idRegional = $this->intelcost->realEscapeStringData($idRegional);
            $Sqlofe  ="SELECT * FROM regionales t1";
            $Sqlofe .=" WHERE id='".$idRegional."' LIMIT 1";
            $cssRegional=$dbConection->query($Sqlofe);
            if($cssRegional){
                if($cssRegional->num_rows!=0){
                    $regionalObj = $cssRegional->fetch_assoc();
                    return ($regionalObj["nombre"]);
                }else{
                    return false;
                }
            }else{
                return false;
            }
            $dbConection->close();
        }else{
            return false;
        }
    }

    protected function obtenerFechaParticipacionEstado($idParticipacion, $estado){
        $fechaParticipacion = "";
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $queryFechaReal = "";
            switch ($estado) {
                case 'ofe_enviada':
                    $queryFechaReal = "SELECT TG.fecha_actualizacion FROM oferta_participantes AS OP INNER JOIN tg_oferta_participantes AS TG ON TG.id = OP.id AND TG.estado_participacion = OP.estado_participacion WHERE OP.id = ".$idParticipacion." AND TG.estado_participacion = 'ofe_enviada' ORDER BY TG.fecha_actualizacion DESC LIMIT 1";
                    $sql = $dbConection->query($queryFechaReal);
                    if($sql){
                        if($sql->num_rows > 0){
                            $dataFecha = $sql->fetch_assoc();
                            $fechaParticipacion = $dataFecha['fecha_actualizacion'];
                        }
                    }
                    break;
                case "inactivo":
                    $queryFechaReal = "SELECT TG.fecha_modificacion FROM oferta_participantes AS OP INNER JOIN tg_oferta_participantes AS TG ON TG.id = OP.id AND TG.estado_participacion = OP.estado_participacion WHERE OP.id = ".$idParticipacion." AND TG.estado_participacion = 'inactivo' AND TG.accion = 'INSERTO' ORDER BY TG.fecha_modificacion ASC LIMIT 1";
                    $sql = $dbConection->query($queryFechaReal);
                    if($sql){
                        if($sql->num_rows > 0){
                            $dataFecha = $sql->fetch_assoc();
                            $fechaParticipacion = $dataFecha['fecha_modificacion'];
                        }
                    }
                    break;
                case 'ofe_consultada':
                    $queryFechaReal = "SELECT OP.fecha_actualizacion, TG.fecha_modificacion FROM oferta_participantes AS OP INNER JOIN tg_oferta_participantes AS TG ON TG.id = OP.id AND TG.estado_participacion = OP.estado_participacion WHERE OP.id = ".$idParticipacion." AND TG.estado_participacion = 'ofe_consultada' AND TG.accion = 'ACTUALIZO' ORDER BY TG.fecha_modificacion ASC LIMIT 1";
                    $sql = $dbConection->query($queryFechaReal);
                    if($sql){
                        if($sql->num_rows > 0){
                            $dataFecha = $sql->fetch_assoc();
                            $fechaParticipacion = $dataFecha['fecha_actualizacion'];
                            if($fechaParticipacion == "" || $fechaParticipacion == "0000-00-00 00:00:00"){
                                $fechaParticipacion = $dataFecha['fecha_modificacion'];
                            }
                        }
                    }
                    break;
                case 'ofe_declinada':
                    $queryFechaReal = "SELECT TG.fecha_actualizacion FROM oferta_participantes AS OP INNER JOIN tg_oferta_participantes AS TG ON TG.id = OP.id AND TG.estado_participacion = OP.estado_participacion WHERE OP.id = ".$idParticipacion." AND TG.estado_participacion = 'ofe_declinada' ORDER BY TG.fecha_actualizacion ASC LIMIT 1";
                    $sql = $dbConection->query($queryFechaReal);
                    if($sql){
                        if($sql->num_rows > 0){
                            $dataFecha = $sql->fetch_assoc();
                            $fechaParticipacion = $dataFecha['fecha_actualizacion'];
                            if($fechaParticipacion == "" || $fechaParticipacion == "0000-00-00 00:00:00"){
                                $fechaParticipacion = $dataFecha['fecha_modificacion'];
                            }
                        }
                    }
                    break;
            }
        }
        return $fechaParticipacion;
    }

    private function compararActualizacionOferta($id,$ofModelo){
        $queryOferta = 'SELECT * FROM ofertas WHERE id = ? LIMIT 1';
        $SqlOferta = $this->intelcost->prepareStatementQuery('cliente', $queryOferta, 'select', true, "i", array((int) $id), "Comparación edición oferta.");

        if($SqlOferta->bool){
            if($SqlOferta->msg->num_rows > 0){
                $oferta = $SqlOferta->msg->fetch_assoc();
                if($oferta['estado'] == 'PUBLICADA'){
                    $respuestaComparacion = [];
                    if( ($oferta["objeto"]) != $ofModelo->objeto){
                        $observacion1 = new stdClass();
                        $observacion1->titulo = "Objeto";
                        $observacion1->anteriorValor = ($oferta["objeto"]);
                        $observacion1->nuevoValor = $ofModelo->objeto;
                        array_push($respuestaComparacion,$observacion1);
                    }
                    if( ($oferta["descripcion"]) != $ofModelo->descripcion){
                        $observacion2 = new stdClass();
                        $observacion2->titulo = "Descripcion";
                        $observacion2->anteriorValor = ($oferta["descripcion"]);
                        $observacion2->nuevoValor = $ofModelo->descripcion;
                        array_push($respuestaComparacion,$observacion2);
                    }
                    /*if( $oferta["presupuesto"] != $ofModelo->presupuesto){
                        $observacion3 = new stdClass();
                        $observacion3->titulo = "Presupuesto";
                        $observacion3->anteriorValor = $oferta["presupuesto"];
                        $observacion3->nuevoValor = $ofModelo->presupuesto;
                        array_push($respuestaComparacion,$observacion3);
                    }*/
                    if( $oferta["fecha_cierre"] != $ofModelo->fecha_cierre){
                        $observacion4 = new stdClass();
                        $observacion4->titulo = "Fecha Cierre";
                        $observacion4->anteriorValor = $oferta["fecha_cierre"];
                        $observacion4->nuevoValor = $ofModelo->fecha_cierre;
                        $observacion4->estado = $oferta['estado'];
                        array_push($respuestaComparacion,$observacion4);
                    }
                    if( $oferta["hora_cierre"] != ($ofModelo->hora_cierre)){
                        $observacion5 = new stdClass();
                        $observacion5->titulo = "Hora Cierre";
                        $observacion5->anteriorValor = $oferta["hora_cierre"];
                        $observacion5->nuevoValor = $ofModelo->hora_cierre;
                        $observacion4->estado = $oferta['estado'];
                        array_push($respuestaComparacion,$observacion5);
                    }
                    if( $ofModelo->fecha_msg_check!="0" && ($oferta["fecha_limite_msg_fecha"] != $ofModelo->fecha_msg_fecha)){
                        $observacion6 = new stdClass();
                        $observacion6->titulo = "Fecha Limite envio de mensajes";
                        $observacion6->anteriorValor = $oferta["fecha_limite_msg_fecha"];
                        $observacion6->nuevoValor = $ofModelo->fecha_msg_fecha;
                        array_push($respuestaComparacion,$observacion6);
                    }
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =json_encode($respuestaComparacion);
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg =$oferta['estado'];
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontró la oferta.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error ejecución en comparacion de actualización.";
        }
        return $this->intelcost->response;
    }

    public function cronRecordatorioOfertas(){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection && isset($_SESSION["idusuario"])){
            $host_db = "localhost";
            $prebd="cliente";
            $user_db = "cliente_admin";
            $passwd_db = "39085402";
            $user_db = "root";
            $passwd_db = "";
            //$conexion = mysql_connect($host_db,$user_db,$passwd_db);
            $conexion = new mysqli($host_db, $user_db, $passwd_db, $prebd, "3306");
            //TODO OPTIMIZAR LA CONSULTA CON LAS FUNCIONES QUE YA EXISTEN EN EL MODELO
            $SqlOferta ="SELECT * FROM ofertas t1 ";
            $SqlOferta .=" INNER JOIN( SELECT nombre usuario_creador,email email_usuario_creador, id usuario_creador_id FROM usuarios ) t2 ON t1.usuario_creacion = t2.usuario_creador_id";
            $SqlOferta .=" INNER JOIN( SELECT id as id_cliente, nombre nombreCliente , logo logoCliente FROM clientes ) t3 ON t1.id_cliente = t3.id_cliente";
            $SqlOferta .=" LEFT JOIN( SELECT lugar vlugar, fecha vfecha, responsable vresponsable, telefono vtelefono, obligatorio vobligatorio, observaciones vobservaciones, oferta_id voferta_id FROM oferta_visitasobra ) t4 ON t1.id = t4.voferta_id";
            $SqlOferta .=" WHERE (t1.estado ='APROBADA' OR t1.estado ='PUBLICADA') AND (t1.fecha_inicio <='".date('Y-m-d')."' OR t1.fecha_cierre <='".date('Y-m-d')."' ) ";
            $CscOfer=$conexion->query($SqlOferta);
            if($CscOfer){
                while($oferta = $CscOfer->fetch_array()){
                    if($oferta["estado"]=="PUBLICADA" && $oferta["fecha_cierre"] <= date('Y-m-d')){
                        if(time() >= strtotime($oferta["hora_cierre"])-86400 ){

                            $this->recordatorioCierre($oferta);
                            //echo "ln 63";
                        }
                    }
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No hay ofertas para notificar";   
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion en comparacion de actualizacion";  
        }
        return $this->intelcost->response;
    }

    public function obtenerDocumentosPorSobreParticipante($id_oferta,$id_usuario){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $id_oferta = $this->intelcost->realEscapeStringData($id_oferta);
            $id_usuario = $this->intelcost->realEscapeStringData($id_usuario);
            $arrDocumentos=[];
            $SqlDoc="SELECT ODOC.id_usuario,ODOC.id_documento_oferente,ODOC.ruta,ODOC.fecha_creacion,ODO.titulo, ODO.descripcion,ODO.obligatorio,ODO.sobre,CAP.nombre,ODOC.id,ODOC.id_oferta FROM oferta_documentos_ofertascliente ODOC INNER JOIN oferta_documentos_oferentes ODO ON ODO.id = ODOC.id_documento_oferente LEFT JOIN capitulos CAP ON ODO.sobre = CAP.id WHERE ODOC.id_oferta='".$id_oferta."' AND ODOC.id_usuario ='".$id_usuario."' ORDER BY CAP.id;";
            $CscDoc=$dbConection->query($SqlDoc);
            if($CscDoc){
                while ($row=$CscDoc->fetch_assoc()) {
                    $row['nombre'] = (empty($row['nombre']) ? "Otro" : $row['nombre']);
                    if($row['obligatorio']==1){
                        $row['obligatorio']="SI";
                    }else{
                        $row['obligatorio']="NO";
                    }
                    array_push($arrDocumentos,json_encode($row));
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg =json_encode($arrDocumentos);
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Consulta erronea obtener documentos participante.";   
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion."; 
        }
        return $this->intelcost->response;
    }

    public function obtenerDocumentosPorSobre($id_oferta, $idSobre, $id_usuario){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection && isset($_SESSION["idusuario"])){

            $id_oferta = $this->intelcost->realEscapeStringData($id_oferta);
            $idSobre = $this->intelcost->realEscapeStringData($idSobre);
            $id_usuario = $this->intelcost->realEscapeStringData($id_usuario);
        //TODO OPTIMIZAR LA CONSULTA CON LAS FUNCIONES QUE YA EXISTEN EN EL MODELO
            $SqlDocs ="SELECT * FROM oferta_documentos_ofertascliente t1 ";
            $SqlDocs .=" INNER JOIN( SELECT id idDoc, sobre idSobre, titulo tituloDoc, obligatorio obliDoc, descripcion FROM oferta_documentos_oferentes ) t2 ON t1.id_documento_oferente = t2.idDoc";
            $SqlDocs .=" WHERE id_oferta = '".$id_oferta."' AND idSobre ='".$idSobre."' AND id_usuario ='".$id_usuario."'";
            
            $CscDocumentosOferta = $dbConection->query($SqlDocs);
            if($CscDocumentosOferta){
                $arrDocsOfer = [];
                while( $doc = $CscDocumentosOferta->fetch_assoc()){
                    array_push($arrDocsOfer, $doc);
                }
                    
                $rta= json_encode($arrDocsOfer);
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $rta;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No hay documentos por mostrar";   
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion en comparacion de actualizacion";  
        }
        return $this->intelcost->response;
    }

    public function obtenerSobresPorCliente(){
        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection && isset($_SESSION["idusuario"])){
        //TODO OPTIMIZAR LA CONSULTA CON LAS FUNCIONES QUE YA EXISTEN EN EL MODELO
            $SqlSObres ="SELECT * FROM capitulos t1 WHERE estado ='ACTIVO' ";
            $CscSobres = $dbConection->query($SqlSObres);
            if($CscSobres){
                $arrDocsOfer = [];
                while( $doc =  $CscSobres->fetch_assoc()){
                    
                    array_push($arrDocsOfer, $doc);
                }
                $rta= json_encode($arrDocsOfer);
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $rta;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No hay sobres por mostrar";   
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion en comparacion de actualizacion";  
        }
        return $this->intelcost->response;
    }

    public function recordatorioCierre($dataOferta){
        $modelo_usuario = new modelo_usuario();
        $host_db = "localhost";
        $prebd="cliente";
        $user_db2 = "intelcos_admin";
        $passwd_db2 = "39085402";
        //$user_db2 = "root";
        //$passwd_db2 = "";
        $oferta = (object) $dataOferta;
        $obj = $this->intelcost->realEscapeStringData($oferta);
        //$conexion2 = mysql_connect($host_db,$user_db2,$passwd_db2);
        $participantes = $this->usuariosParticipantes($obj->seq_id,"all");
        $arrPart = json_decode($participantes->msg);
        foreach( $arrPart as $participa){
            if($participa->estado_participacion =="inactivo"||$participa->estado_participacion=="ofe_consultada")
            {
                //$SqlGetParticipante   = "SELECT * FROM intelcos_0001.sys00001 WHERE `usrmailx`='".$participa->email_usuario."' LIMIT 1";
                //$cscGetParti=mysqli_query($SqlGetParticipante,$conexion2);
                //$usuarioObj = mysql_fetch_assoc($cscGetParti);
                
                $estadoOferta ="";
                if ($participa->estado_participacion=="ofe_consultada")
                {
                    $estadoOferta = "<b> OFERTA CONSULTADA</b>";
                }
                if ($participa->estado_participacion=="inactivo")
                {
                    $estadoOferta = "<b> NO INGRESO</b>";
                }

                $contentParticipanteOferta  = "<p>Cordial saludo,</p> ";
                $contentParticipanteOferta  .= "Esta es una alerta de recordatorio indicando que el evento de la referencia a la fecha está programado para cerrar el día <b>".$obj->fecha_cierre." (aaaa-mm-dd) - ".$obj->hora_cierre." </b>, hora de Colombia. A la fecha su oferta figura como : ".$estadoOferta.".</br></br>";
                $contentParticipanteOferta .= " Si es de su interés participar, debe cargar la documentación solicitada y utilizar la opción ENVIAR OFERTA en la sección INFO. SOLICITADA antes de la fecha y hora de cierre estipulada en el evento.<br /><br />";
                $contentParticipanteOferta .= "<h4>Recuerde:</h4>";
                $contentParticipanteOferta .= "<li>Si requiere cargar más de un documento por opción, puede hacerlo usando un archivo comprimido (por ejemplo .zip).</li> </br>";
                $contentParticipanteOferta .= "<li>Puede ir cargando archivos de forma parcial con el fin de adelantar la gestión y/o agilizar la carga de documentos pesados. </li></br>";
                $contentParticipanteOferta .= "<li>Una vez cargue la totalidad de archivos obligatorios debe utilizar la opción ENVIAR OFERTA para que la misma sea recibida por el comprador, su oferta sólo podrá ser consultada una vez CIERRE el evento. </li></br>";
                $contentParticipanteOferta .= "<li>Una vez envíe su oferta el sistema le notificará en la plataforma y al correo electrónico el envío de la misma. </li> </br>";
                $contentParticipanteOferta .= "<li>Le recomendamos no dejar para último momento el envío de su oferta, después de la fecha Y HORA de cierre el sistema NO permitirá el envío de ofertas. </li> </br>";
                $contentParticipanteOferta .= "<p style='font-size:12px'>Link de acceso.<br /> https://intelcost.com/intelcost/</p><br />";
                $subject = 'noreply - RECORDATORIO CIERRE EVENTO PROXIMAMENTE - '.$obj->nombreCliente." - ".$obj->objeto;
                $resEnvio = $this->enviarEmailRecordatorio($participa->email_usuario,$contentParticipanteOferta,$obj,$subject);
                if($resEnvio){
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg ="Se envio el mail";    
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="No se envio el mail"; 
                }
            }
        }
    }

    public function enviarEmailRecordatorio($recipient,$content,$oferta,$subject){

        if($oferta->id_cliente == "1"){
            $mailheaders  = 'MIME-Version: 1.0' . "\r\n";
            $mailheaders .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
            $mailheaders .= 'From: intelcost.com <contacto@intelcost.com>' . "\r\n" .
            'Reply-To: contacto@intelcost.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
            $emailContent = '<table style="padding:3%;background: #fff;width:100%"><tr><td width="100%"><img src="'.$oferta->logoCliente.'" style="float:left;width:100px"><img src="http://www.intelcost.com/images/logo.png" style="float:right;width:100px"></td></tr></table><table style="background: #f5f5f5;width:100%;font-family:sans-serif;border-collapse: collapse; " cellspacing="0">';

            $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%;width: 90%; margin:auto">';
            $emailContent .= $content;
            $emailContent .= "<h3>Oferta No ".$oferta->seq_id."</h3>";
            $emailContent .= "<div style='padding-left:10%'>";
            $emailContent .= "<p><b>Objeto:</b> ".($oferta->objeto)."</p>";
            $emailContent .= "<p><b>Descripcion:</b> ".($oferta->descripcion)."</p>";


            $emailContent .= "</div>";
            $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Para asegurar un correcto uso de la herramienta puede consultar el manual de uso, dando click en el siguiente link: <a href='http://www.intelcost.com/manual.pdf' target='_blank' style='text-decoration:none'>www.intelcost.com/manual.pdf</a> o puede contactarnos al tel&eacute;fono +57 1 489 8100, o al correo electrónico soporte@intelcost.com. Adicionalmente, una vez ingrese al sistema podr&aacute; pedir soporte a trav&eacute;s de nuestro chat en vivo.</p>";
            $emailContent .= '</td></tr>';


            $emailContent .= '<tr><td style="width: 90%;float:left;padding: 2% 5%;background:#bc3f76;color: #fff;"><div style="float: left;width: 40%"><p style="font-size:12px;">Atentamente,<br><br>El equipo de INTELCOST<br><a style="color:#fff" href="www.intelcost.com">www.intelcost.com</a><br><b>Email: </b><a href="mailto:soporte@intelcost.com" target="_blank">soporte@intelcost.com</a><br><b>Telefono: </b>322-832 19 84<br></p></div><div style="float: right;width: 50%;text-align: right"><img src="http://www.intelcost.com/images/logo-dark.png" width="100px"></div></td></tr>';

            $confSend= mail($recipient,$subject,$emailContent,$mailheaders);
            $confSend= mail('contacto@intelcost.com',$subject,$emailContent,$mailheaders);

            return $confSend;
        }else{
            return false;
        }
    }

    public function actualizarCarta($idOferta,$idUsuario, $ruta){

        $dbConection = $this->intelcost->db->createConection("cliente");
        if($dbConection){
            $idOferta = $this->intelcost->realEscapeStringData($idOferta);
            $idUsuario = $this->intelcost->realEscapeStringData($idUsuario);
            $ruta = $this->intelcost->realEscapeStringData($ruta);
            $SqlOfer = 'UPDATE oferta_participantes SET `carta_invitacion` = "'.$ruta.'",`usuario_actualizacion`="'.$_SESSION["idusuario"].'", `fecha_actualizacion`="'.date("Y-m-d H:i:s").'"  WHERE `id_usuario`= "'.$idUsuario.'" AND `id_oferta`= "'.$idOferta.'"';
            
            $CscUsr = $dbConection->query($SqlOfer);
            $ofertaMod = $this->obtenerOferta($idOferta);
            if($ofertaMod->bool){
                $ofertaObj = json_decode($ofertaMod->msg);
                if($ofertaObj->estado=="PUBLICADA"){
                    foreach ($ofertaObj->participantes as $part) {
                        if($part->id_usuario == $idUsuario){
                            $obj_adicionales = new stdClass();
                            $obj_adicionales->relacion_id = $idOferta;
                            $obj_adicionales->modulo_id = 5;
                            $emailContent ="Hemos actualizado su carta de invitación para la participación del evento ".$ofertaObj->objeto;
                            $comunicar = $this->modeloComunicaciones->sendEmailAttachment($part->email,$emailContent,"Actualización carta de invitacion","proveedor",$ofertaObj->id_cliente,$ruta,$obj_adicionales);
                            break;
                        }
                    }
                }
            }
            if($CscUsr){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "carta invitacion actualizada correctamente";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Actualizacion de la carta invitacion erronea";
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Error Conexion";
        }
        return $this->intelcost->response;
    }

    
    /*
     * Returns the documents evaluated into an offer
     * $offerID {int} Offer ID
     * */
    public function getOfferDocumentsEvaluationList($offerID){
        $bd_cliente = self::$bd_cliente;
        $bd_proveedores = self::$bd_proveedores;
        
        $dbConection = $this->intelcost->db->createConection("cliente");
        $query = "SELECT 
        $bd_cliente.usuarios.nombre as user_name,
        $bd_cliente.usuarios.email as user_email,
        $bd_cliente.usuarios.tipo as user_type,
        $bd_cliente.offerer_envelopes_evaluated_documents.*,
        $bd_cliente.offers.id_us as offerer_id,
        $bd_proveedores.sys00001.usrnomxx as offerer_name
        FROM 
        $bd_cliente.offerer_envelopes_evaluated_documents,
        $bd_cliente.offers,
        $bd_cliente.usuarios,
        $bd_proveedores.sys00001
        WHERE
        $bd_cliente.offerer_envelopes_evaluated_documents.offer_id=?
        AND
        $bd_cliente.offerer_envelopes_evaluated_documents.offer_document_evaluation_user = $bd_cliente.usuarios.id
        AND
        $bd_cliente.offers.id_oferta = $bd_cliente.offerer_envelopes_evaluated_documents.offer_id
        AND
        $bd_cliente.offerer_envelopes_evaluated_documents.offerer_id = $bd_proveedores.sys00001.usridxxx
        GROUP BY 
        $bd_cliente.offerer_envelopes_evaluated_documents.evaluation_id
        ORDER BY $bd_cliente.offerer_envelopes_evaluated_documents.offerer_id ASC";
        $statement = $this->intelcost->prepareStatementQuery('cliente', $query, 'select', true, "i", array((int) $offerID), "Obtener accesos - 
            remitir evaluación.");
        $documents = $statement->msg;
        $result=new stdClass();
        $result->documents=array();
        while ($row = $documents->fetch_object()) {
            array_push($result->documents, $row);
        }
        return $result;
    }
    

    public function imprimirInformacionParticipantes($data, $oferta){
        $ofertaobj = json_decode($oferta->msg);
    
        ob_start();
        $html = ob_get_clean();
        $html = ($html);
        $html .=$this->modelo_pdf->headerPdf();
        $html .='
        <div class="row">

        <table class="table text-center noMarginButton">
        <tbody>
        <tr class="bg-titulosPrimarios">
        <td style="width:50%"><img src="../images/sliders/'.$_SESSION["cliente_logo"].'" style="width: 150px;height: 60px;"></td>
        <td style="width:50%"></td>
        <td style="width:50%">
        <p class="texto">Oferta N°'.$ofertaobj->seq_id.'</p>
        <p class="texto">Estado: '.$ofertaobj->estado.' </p>
        <p class="texto">Tipo: '.$ofertaobj->tipo.' </p>
        <p class="texto">Objeto: '.$ofertaobj->objeto.'</p>
        </td>
        </tr>
        </tbody>
        </table>

        </div>

        <div class="row">
        <div class="container-fluid">
        <p>Proveedores de la red Intelcost</p>
        <table class="table table-bordered text-center noMarginButton">
        <tbody>
        <tr class="bg-primary">
        <td style="width: 40%">Responsable</td>
        <td >Objeto</td>
        </tr>
        <tr class="textoBd">
        <td>'.$ofertaobj->usuario_creador.'</td>
        <td>'.$ofertaobj->objeto.'</td>
        </tr>
        </tbody>
        </table>
        <div style="height:15px"></div>
        <table class="table table-bordered text-center noMarginButton">
        <tbody>';

        $n = 0;
        if ($data != NULL) {
            foreach ($data as $empresa) {
                if ($n > 0) {
                    $html .= '
                    </tbody>
                    </table>
                    <div style="height: 50px"></div>
                    <table class="table table-bordered text-center noMarginButton">
                    <tbody>
                    ';
                }
                $html .= '
                <tr class="bg-primary">
                <td style="width: 40%">Empresa / Razon Social</td>
                <td >NIT</td>
                <td >Sitio Web</td>
                <td >Teléfono</td>
                </tr>
                <tr class="textoBd">
                <td>'.$empresa['razonxxx'].'</td>
                <td>'.$empresa['nit'].'</td>
                <td>'.$empresa['linkwebx'].'</td>
                <td>'.$empresa['telefono'].'</td>
                </tr>
                <tr class="bg-primary">
                <td colspan="4">contactos</td>
                </tr>
                <tr class="bg-primary">
                <td  colspan="2">Nombre</td>
                <td >Correo</td>
                <td >Teléfono</td>
                </tr>';
                if (isset($empresa['contactos'])) {
                    foreach ($empresa['contactos'] as $contacto) {
                        $html .= '
                        <tr class="textoBd">
                        <td colspan="2">'.($contacto['nombre']).'</td>
                        <td>'.$contacto['emailcont'].'</td>
                        <td>'.$contacto['telefcont'].'</td>
                        </tr>';
                    }   
                }
                $n++;
            }
        }
        
        $html .= '
        </tbody>
        </table>
        </div>
        </div>
        ';
        
        $html .= $this->modelo_pdf->footerPdf();
        $arrFile = [];
        $arrFile['ruta'] = '../ic_files/';
        if (!is_dir($arrFile['ruta'])) {
            mkdir($arrFile['ruta']);         
        }
        $arrFile['ruta'] = '../ic_files/ic_oferta_contactos/';
        if (!is_dir($arrFile['ruta'])) {
            mkdir($arrFile['ruta']);         
        }
        $arrFile['ruta'] = '../ic_files/ic_oferta_contactos/'.$_SESSION['idusuario'].'/';
        if (!is_dir($arrFile['ruta'])) {
            mkdir($arrFile['ruta']);         
        }
        $arrFile['nombre'] = "ReporteOfertaContactos_N".$ofertaobj->seq_id.".pdf";
        $nombre_fichero =  $arrFile['ruta'].$arrFile['nombre'];
        $cont = 0;
        while(file_exists($nombre_fichero)) {
            $cont++;
            $arrFile['ruta'] = '../ic_files/ic_oferta_contactos/'.$_SESSION['idusuario'].'/';
            $arrFile['nombre'] = "ReporteOfertaContactos_N".$ofertaobj->seq_id."-".$cont.".pdf";
            $nombre_fichero =  $arrFile['ruta'].$arrFile['nombre'];
        }
        if($this->modelo_pdf->generar_pdf($html,$arrFile['ruta'],$arrFile['nombre'])){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = json_encode($arrFile);
        }else{
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = "No se logro crear el PDF";
        }
        return $this->intelcost->response;
    }

    private function enviarEmailSistemaAprobaciones($recipient, $content, $data, $subject){

        $emailContent .= $content;
        $emailContent .= "<h3>Oferta No ".$data->seq_id."</h3>";
        $emailContent .= "<div style='padding-left:10%'>";
        $emailContent .= "<p><b>Objeto:</b> ".$data->objeto."</p>";
        $emailContent .= "<p><b>Descripcion:</b> ".$data->descripcion."</p>";

        $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Para asegurar un correcto uso de la herramienta puede consultar el manual de uso, dando click en el siguiente link: <a href='http://www.intelcost.com/manual.pdf' target='_blank' style='text-decoration:none'>www.intelcost.com/manual.pdf</a> o puede contactarnos al tel&eacute;fono +57 1 489 8100, o al correo electronico soporte@intelcost.com. Adicionalmente una vez ingrese al sistema podr&aacute; pedir soporte a trav&eacute;s de nuestro chat en vivo.</p>";

        $confSend= $this->modeloComunicaciones->sendEmail($recipient, $emailContent,$subject,"ComunicadoLogoCliente",$_SESSION["empresaid"]);
        if($_SESSION['empresaid'] == 8){
            $confSend= $this->modeloComunicaciones->sendEmail('contacto@intelcost.com',$emailContent, $subject, "ComunicadoLogoCliente", $_SESSION["empresaid"]);
            $confSend= $this->modeloComunicaciones->sendEmail('fabio.latorre@odl.com.co',$emailContent, $subject,"ComunicadoLogoCliente", $_SESSION["empresaid"]);
        }
        return $confSend;
    }

    public function calificarDocumento($datos){
        //Valida que las opciones sean reales cumpleNoCumple
        if (isset($datos['cumpleNoCumple']) && $datos['cumpleNoCumple'] === "sicumple") {
            $datos['cumpleNoCumple'] = 1;
        }else{
            if (isset($datos['cumpleNoCumple']) && $datos['cumpleNoCumple'] === "nocumple") {
                $datos['cumpleNoCumple'] = 2;
            }else{
                if (!empty($datos['cumpleNoCumple'])) {
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Opción no reconocida o no valida ERROR-".__LINE__;
                    return $this->intelcost;
                }else{
                    $datos['cumpleNoCumple'] = "";
                }
            }
        }
        //Valida que las opciones sean reales subsanable
        if (isset($datos['subsanable']) && $datos['subsanable'] === "si") {
            $datos['subsanable'] = 1;
        }else{
            if (isset($datos['subsanable']) && $datos['subsanable'] === "no") {
                $datos['subsanable'] = 2;
            }else{
                if (!empty($datos['subsanable'])) {
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Opción no reconocida o no valida ERROR-".__LINE__;
                    return $this->intelcost;
                }else{
                    $datos['subsanable'] = "";
                }
            }
        }

        $dbConection = $this->intelcost->db->createConection("cliente");
        if ($dbConection) {
            //Sube al servidor un archivo si lo hay
            if (isset($_FILES["anexo"])) {
                $file_url = $this->uploadFile($_FILES["anexo"], $datos['oferta_id'], $datos['documento_id']);
                if (!$file_url->bool) {
                    $this->intelcost->response = $file_url;
                    return $this->intelcost->response;
                }
                $file_url = $file_url->msg;
            }else{
                $file_url = "";
            }

            $SqlQueryExist = "SELECT documento_id, file_url, cumpleNoCumple, subsanable FROM oferta_evaluacion_documento WHERE documento_id = '$datos[documento_id]' AND estado = 'activo' ";
            $CscValideExist = $dbConection->query($SqlQueryExist);
            if ($CscValideExist->num_rows > 0) {
                $row = $CscValideExist->fetch_assoc();
                if ($row['cumpleNoCumple'] != "Cumple" || $row['subsanable'] != "No") {
                    $SqlQuery = "UPDATE oferta_evaluacion_documento SET observaciones = '".$datos['observaciones']."', cumpleNoCumple = '".$datos['cumpleNoCumple']."' ";
                    $msg = "Se han actualizado correctamente los datos";
                    if ($file_url != "") {
                        $SqlQuery .= ", file_url = '".$file_url."' ";
                        $this->deleteArchive($row['file_url']);
                    }else{
                        $file_url = $row['file_url'];
                    }
                    $SqlQuery .= ", subsanable = '".$datos['subsanable']."', usuario_actualizacion = $_SESSION[idusuario], fecha_actualizacion ='".date("Y-m-d H:i:s")."' WHERE documento_id = '".$row['documento_id']."'";
                }else{
                    $msg = "Se interrumpió la actualización, ya fue calificado con exito";
                }
            }else{
                $SqlQuery = "INSERT INTO oferta_evaluacion_documento (documento_id, usuario_id, oferta_id, observaciones, file_url, ";
                $SqlQuery .= "fecha_creacion, cumpleNoCumple, subsanable) VALUES ('".$datos['documento_id']."', '".$datos['usuario_id']."', ";
                $SqlQuery .= "'".$datos['oferta_id']."', '".$datos['observaciones']."', '".$file_url."',";
                $SqlQuery .= "'".date('Y-m-d H:i:s')."', '".$datos['cumpleNoCumple']."', '".$datos['subsanable']."')";
                $msg = "Se han guardado correctamente los datos";
            }
            $CscEvaluacionDocumento = $dbConection->query($SqlQuery);
            $data['archivos']['documento_id'] = $datos['documento_id'];
            if ($datos['cumpleNoCumple'] == 1 || $datos['subsanable'] == 2) {
                $data['archivos']['terminado'] = true;
                if ($datos['subsanable'] == 2) {
                    $data['archivos']['subsanable'] = "No";
                }
            }else{
                $data['archivos']['terminado'] = false;
                $data['archivos']['subsanable'] = "Si";
            }
            $semaforo['color'] = "naranja";
            $semaforo['mensaje'] = "Sin evaluar";
            if ($datos['cumpleNoCumple'] == 1) {
                $semaforo['color'] = "verde";
                $semaforo['mensaje'] = "Evaluado, cumple";
            }else{
                if ($datos['subsanable'] == 2) {
                    $semaforo['color'] = "rojo";
                    $semaforo['mensaje'] = "No cumple";
                }else{
                    $semaforo['color'] = "azul";
                    $semaforo['mensaje'] = "No cumple pero es subsanable";
                }
            }

            if ($CscEvaluacionDocumento) {
                if (isset($datos['cumpleNoCumple']) && $datos['cumpleNoCumple'] == 2 || isset($row['cumpleNoCumple']) && $row['cumpleNoCumple'] != "Cumple") {
                    $correo = $this->enviarCorreoCreador($datos['oferta_id'], $datos['documento_id'], $datos['usuario_id']);
                    if (!$correo->bool) {
                        $msg = $correo->msg;
                    }
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = json_encode(array($data, "msg" => $msg, "url_file" => $file_url, "semaforo" => $semaforo));
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Error al insertar nuevo dato ERROR-".__LINE__;
            }
            $dbConection->close();
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error Conexion ERROR-".__LINE__;
        }
        return $this->intelcost->response;
    }

    public function evaluacionIndividualDocumentosOferente($ObjDocumento){
        $id_evaluacion = null;
        if(isset($ObjDocumento->id_evaluacion) && !empty($ObjDocumento->id_evaluacion)){
            $queryValidarAprobacion = "SELECT count(id_historial) as cantidad FROM oferta_evaluacion_documento_historial WHERE id_evaluacion = ? AND tipo_usuario_registra = 'aprobador' ";
            $sqlValidarAprobacion = $this->intelcost->prepareStatementQuery('cliente', $queryValidarAprobacion, 'select', true, "i", array((int) $ObjDocumento->id_evaluacion), "Validar cantidad aprobaciones evaluación.");
            if($sqlValidarAprobacion->bool){
                $resValidarAprobacion = $sqlValidarAprobacion->msg->fetch_assoc();
                if( (int) $resValidarAprobacion['cantidad'] > 0){
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "El documento ".$ObjDocumento->titulo." ya registra aprobaciones, por lo cual no se permite editar su evaluación.";
                }else{
                    if((int) $ObjDocumento->id_tipo_evaluacion == 1){
                        $ObjDocumento->resultado_evaluacion = $ObjDocumento->calificacion;
                        $ObjDocumento->calificacion = $ObjDocumento->calificacion == 0 ? 2 : 1;
                    }
                    if((int) $ObjDocumento->id_tipo_evaluacion == 2){
                        $ObjDocumento->resultado_evaluacion = ($ObjDocumento->calificacion == 1) ? "Cumple" : "No cumple";
                    }
                    $queryUpdate = "UPDATE oferta_evaluacion_documento SET cumpleNoCumple = ?, resultado_evaluacion = '$ObjDocumento->resultado_evaluacion', observaciones = ? WHERE id = ? ";
                    $parametrosUpdate = array($ObjDocumento->calificacion, $ObjDocumento->observacion, (int) $ObjDocumento->id_evaluacion);
                    $sqlUpdate = $this->intelcost->prepareStatementQuery('cliente', $queryUpdate, 'update', true, "ssi", $parametrosUpdate, "Editar - actualizar evaluación documento oferente.");
                    if($sqlUpdate->bool){
                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                            OfertaDocumentosCriteriosOferentesEvaluaciones::where('id_item_documento_interno', $ObjDocumento->docId)->update(['estado' => 'Historial', 'updated_at' => date('Y-m-d H:i:s')]);
                        }
                        $id_evaluacion = $ObjDocumento->id_evaluacion;
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se presentó un error al guardar la evaluación.";
                    }
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Se presentó un error al consultar aprobaciones sobre la evaluación.";
            }
        }else{
            $queryAntiguaCalificacion = "UPDATE oferta_evaluacion_documento SET estado = 2, `usuario_actualizacion` = $_SESSION[idusuario], `fecha_actualizacion` = '".date("Y-m-d H:i:s")."' WHERE documento_id = ? AND oferta_id = ? AND estado = 1 ";
            $sqlAntiguaCalificacion = $this->intelcost->prepareStatementQuery('cliente', $queryAntiguaCalificacion, 'update', true, "ii", array((int) $ObjDocumento->docId, (int) $ObjDocumento->ofertaId), "modificar anteriores calificaciones documento.");
            if($sqlAntiguaCalificacion->bool){
                if((int) $ObjDocumento->id_tipo_evaluacion == 1){
                    $ObjDocumento->resultado_evaluacion = $ObjDocumento->calificacion;
                    $ObjDocumento->calificacion = $ObjDocumento->calificacion == 0 ? 2 : 1;
                }
                if((int) $ObjDocumento->id_tipo_evaluacion == 2){
                    $ObjDocumento->resultado_evaluacion = ($ObjDocumento->calificacion == 1) ? "Cumple" : "No cumple";
                }
                $queryCalificacion = "INSERT INTO oferta_evaluacion_documento (documento_id, usuario_id, oferta_id, cumpleNoCumple, resultado_evaluacion, fecha_creacion, subsanable, observaciones) VALUES (?, $_SESSION[idusuario], ?, ?, '$ObjDocumento->resultado_evaluacion', '".date("Y-m-d H:i:s")."', '', ?)";
                $parametros = array((int) $ObjDocumento->docId, (int) $ObjDocumento->ofertaId, $ObjDocumento->calificacion, $ObjDocumento->observacion);
                $sqlCalificacion = $this->intelcost->prepareStatementQuery('cliente', $queryCalificacion, 'insert', true, "iiss", $parametros, "Guardar calificación individual documento.");
                if($sqlCalificacion->bool){
                    if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                        OfertaDocumentosCriteriosOferentesEvaluaciones::where('id_item_documento_interno', $ObjDocumento->docId)->update(['estado' => 'Historial', 'updated_at' => date('Y-m-d H:i:s')]);
                    }
                    $id_evaluacion = $sqlCalificacion->msg;     
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Se presentó un error al guardar la evaluación.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Se presentó un error al almacenar la evaluación.";
            }
        }
        if($id_evaluacion != null){
            $queryRegHistorial = "INSERT INTO `oferta_evaluacion_documento_historial` (`id_evaluacion`, `id_documento`, `id_usuario`, `tipo_usuario_registra`, `valoracion`, `observaciones`) VALUES ($id_evaluacion, ".$ObjDocumento->docId.", $_SESSION[idusuario], 'evaluador', 'evaluado', ?)";
            $sqlRegHistorial = $this->intelcost->prepareStatementQuery('cliente', $queryRegHistorial, 'insert', true, "s", array($ObjDocumento->observacion), "Guardar historial evaluación documentos.");
            if($sqlRegHistorial->bool){
                if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                    $criterios = collect();
                    foreach (json_decode($ObjDocumento->criterios) as $key => $criterio) {
                        $criterios->push([
                            'id_criterio' => $criterio->id_criterio,
                            'id_item_documento_interno' => $ObjDocumento->docId,
                            'id_item_documento_oferente' => $ObjDocumento->id_documento_oferente,
                            'id_evaluacion' => $id_evaluacion,
                            'id_usuario_creador' => $_SESSION['idusuario'],
                            'tipo' => $criterio->tipo,
                            'respuesta' => $criterio->resultado_criterio,
                            'observacion' => $criterio->observaciones_criterio,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                    OfertaDocumentosCriteriosOferentesEvaluaciones::insert($criterios->toArray());
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = "Se guardó la información de la evaluación correctamente.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Se presentó un error al guardar la el historial de la evaluación.";
            }   
        }
        $varTemporal = json_decode(json_encode($this->intelcost->response));
            $this->validarUltimaEvaluacionTecnica($ObjDocumento->ofertaId);
        return $varTemporal;
    }

    public function guardarAprobacionEvaluacionIndividual($objAprobacion){
        $objAprobacion->aprobacion = (int) $objAprobacion->aprobacion;
        if(isset($objAprobacion->aprobacion) && ($objAprobacion->aprobacion == 1 || $objAprobacion->aprobacion == 2)){

            $queryInsertAprobacion = "INSERT INTO `oferta_evaluacion_documento_historial` ( `id_evaluacion`, `id_documento`, `id_usuario`, `tipo_usuario_registra`, `observaciones`, `valoracion`) VALUES (?, ?, $_SESSION[idusuario], 'aprobador', ?, ".$objAprobacion->aprobacion.")";
            $parametros = array((int) $objAprobacion->id_evaluacion, (int) $objAprobacion->docId, $objAprobacion->observacion);
            $sqlInsertAprobacion = $this->intelcost->prepareStatementQuery('cliente', $queryInsertAprobacion, 'insert', true, "iis", $parametros, "Guardar aprobación individual.");
            if($sqlInsertAprobacion->bool){
                $arrRespuesta = [];
                // SOLO SE INACTIVA LA EVALUACIÓN PARA QUE SE REALICE UNA NUEVA, CUANDO EL COMPRADOR RECHAZA LA ACTUAL.
                if($objAprobacion->aprobacion == 2){
                    $queryUpdEval = "UPDATE `oferta_evaluacion_documento` SET `estado` = 2, `usuario_actualizacion` = $_SESSION[idusuario], `fecha_actualizacion` = '".date("Y-m-d H:i:s")."' WHERE `id` = ? ";
                    $sqlUpdEval = $this->intelcost->prepareStatementQuery('cliente', $queryUpdEval, 'insert', true, "i", array((int) $objAprobacion->id_evaluacion), "actualizar evaluación documento - historial.");
                    if($sqlUpdEval->bool){
                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                            OfertaDocumentosCriteriosOferentesEvaluaciones::where('id_evaluacion', $objAprobacion->id_evaluacion)->update(['estado' => 'Historial', 'updated_at' => date('Y-m-d H:i:s')]);
                        }
                        $notificacion = $this->notificacionEvaluacionDocumentoIndividualRechazado($objAprobacion->id_evaluacion, $objAprobacion->observacion);
                        $mensaje = "Se ha rechazado la evaluación correctamente.";
                        if($notificacion){
                            $mensaje = $mensaje . "Se ha notificado al evaluador para que realice la evaluación nuevamente.";
                        }else{
                            $mensaje = $mensaje . "Se presentó un error al notificar al evaluador.";
                        }
                        array_push($arrRespuesta, $mensaje);
                        $respuesta = $this->validarEvaluacionesOferta($objAprobacion->idOferta);
                        array_push($arrRespuesta,$respuesta->msg);

                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg = $arrRespuesta;
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se presentó un error al guardar actualizar el estado de la evaluación.";
                    }
                }else{

                    $notificarAprobadores = $this->notificarAprobadoresOferta($objAprobacion);

                    $respuesta = $this->validarEvaluacionesOferta($objAprobacion->idOferta);
                    array_push($arrRespuesta,"Se guardó la aprobación correctamente.");
                    array_push($arrRespuesta,$respuesta->msg);

                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = $arrRespuesta;
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Se presentó un error al guardar el historial de la evaluación.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "El concepto de aprobación no es válido.";
        }

        if($objAprobacion->aprobacion == 2){
            // Existe un rechazo se lanza solo cuando es rechazada
            $this->notificarRechazoAprobacion($objAprobacion);
        }
        
        // validarUltimaEvaluacionTecnica hace uso de prepareStatementQuery esto hace que se reescriba "$this->intelcost->response"
        $varTemporal = json_decode(json_encode($this->intelcost->response));
        // $this->validarUltimaEvaluacionTecnica($objAprobacion->idOferta);
        return $varTemporal;
    }

    private function notificarAprobadoresOferta($objAprobacion){
        $queryAprobadores = "SELECT OUA.id_usuario_aprobador AS id_usuario, U.nombre, U.username as email FROM oferta_usuarios_aprobadores AS OUA INNER JOIN usuarios AS U ON U.id = OUA.id_usuario_aprobador WHERE OUA.id_oferta = ? AND OUA.estado = 'activo' ORDER BY OUA.id ASC ";
        $sqlAprobadores = $this->intelcost->prepareStatementQuery('cliente', $queryAprobadores, 'select', true, "i", array((int) $objAprobacion->idOferta), "Obtener aprobadores - notificar aprobación.");
        if($sqlAprobadores->bool){
            $banderaNotificarComprador = false;
            if($sqlAprobadores->msg->num_rows > 0){
                $arrayAprobadores = array();
                while ($aprobador = $sqlAprobadores->msg->fetch_assoc()) {
                    array_push($arrayAprobadores, $aprobador);
                }
                $banderaValidar = true;
                $usuarioNotificar = array();
                foreach ($arrayAprobadores as $key => $aprobador) {
                    if($banderaValidar){
                        $queryValidarAprobacion = "SELECT count(id_historial) as total FROM oferta_evaluacion_documento_historial WHERE id_evaluacion = ".$objAprobacion->id_evaluacion." AND id_documento = ".$objAprobacion->docId." AND id_usuario = $aprobador[id_usuario] AND tipo_usuario_registra = 'aprobador' AND valoracion = 'aprobado' ";
                        $sqlValidarAprobacion = $this->intelcost->prepareStatementQuery('cliente', $queryValidarAprobacion, 'select', false, "", "", "");
                        if($sqlValidarAprobacion->bool){
                            $resValidarAprob = $sqlValidarAprobacion->msg->fetch_assoc();
                            if((int) $resValidarAprob['total'] == 0){
                                $banderaValidar = false;
                                $usuarioNotificar = $aprobador;
                            }
                        }
                    }
                }
                if(!empty($usuarioNotificar)){
                    $queryInfoCorreo = "SELECT OFD.titulo, OF.seq_id, OF.objeto, OF.descripcion, ODC.id_usuario as proveedor, OF.id as idOferta FROM oferta_documentos_ofertascliente AS ODC INNER JOIN oferta_documentos_oferentes AS OFD ON ODC.id_documento_oferente = OFD.id INNER JOIN ofertas AS OF ON OFD.oferta_id = OF.id WHERE ODC.id = ".$objAprobacion->docId;
                    $sqlInfoCorreo = $this->intelcost->prepareStatementQuery('cliente', $queryInfoCorreo, 'select', false, "", "", "");
                    if($sqlInfoCorreo->bool){
                        $infoCorreo = $sqlInfoCorreo->msg->fetch_assoc();
                        $proveedor = $this->getDataProveedor($infoCorreo['proveedor']);
                        $asunto = "Solicitud de aprobación - Proceso/evento $infoCorreo[seq_id] - $infoCorreo[objeto].";
                        $emailContent  = '<table style="background: #f5f5f5;width:100%;font-family:sans-serif;border-collapse: collapse; " cellspacing="0">';
                        $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%;width: 90%; margin:auto">';
                        $emailContent .= "<p>Estimado/a: <b>".$usuarioNotificar['nombre']."</b></p><br>";
                        $emailContent .= "<div style='padding-left:10%'>";
                        $emailContent .= "<p>Es requerido/a para efectuar la valoración y aprobación del documento $infoCorreo[titulo] correspondiente al proceso $infoCorreo[seq_id] - $infoCorreo[objeto] para el oferente <b>".$proveedor['usrnomxx']." | ".$proveedor['razonxxx']."</b>.</p>";
                        $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico soporte@intelcost.com. Adicionalmente, una vez ingrese al sistema podrá solicitar soporte a través de nuestro chat en línea.</p>";
                        $emailContent .= "</div>";
                        $emailContent .= '</td></tr>';
                        $emailContent .= '</table>';

                        $obj_adicionales = new stdClass();
                        $obj_adicionales->relacion_id = $infoCorreo['idOferta'];
                        $obj_adicionales->modulo_id = 5;

                        $enviar = $this->modeloComunicaciones->sendEmail($usuarioNotificar['email'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
                        if($enviar){
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg = "Se ha notificado al siguiente aprobador.";
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg = "Se presentó un error al notificar al siguiente aprobador.";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se presentó un error al consultar información - notificación aprobador.";
                    }
                }else{
                    $banderaNotificarComprador = true;
                }
            }else{
                $banderaNotificarComprador = true;
            }
            if($banderaNotificarComprador){
                $queryInfoCorreo = "SELECT OFD.titulo, OF.seq_id, OF.objeto, ODC.id_usuario as proveedor, U.nombre as comprador, U.email as email, OF.id as idOferta FROM oferta_documentos_ofertascliente AS ODC INNER JOIN oferta_documentos_oferentes AS OFD ON ODC.id_documento_oferente = OFD.id INNER JOIN ofertas AS OF ON OFD.oferta_id = OF.id INNER JOIN usuarios AS U ON U.id = OF.usuario_creacion WHERE ODC.id = ".$objAprobacion->docId;
                    $sqlInfoCorreo = $this->intelcost->prepareStatementQuery('cliente', $queryInfoCorreo, 'select', false, "", "", "");
                if($sqlInfoCorreo->bool){
                    $infoCorreo = $sqlInfoCorreo->msg->fetch_assoc();
                    $proveedor = $this->getDataProveedor($infoCorreo['proveedor']);
                    $asunto = "Aprobación finalizada - Proceso/evento $infoCorreo[seq_id] - $infoCorreo[objeto].";
                    $emailContent  = '<table style="background: #f5f5f5;width:100%;font-family:sans-serif;border-collapse: collapse; " cellspacing="0">';
                    $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%;width: 90%; margin:auto">';
                    $emailContent .= "<p>Estimado/a: <b>".$infoCorreo['comprador']."</b></p><br>";
                    $emailContent .= "<div style='padding-left:10%'>";
                    $emailContent .= "<p>Se ha realizado la aprobación sobre el documento $infoCorreo[titulo] correspondiente al proceso $infoCorreo[seq_id] - $infoCorreo[objeto] para el oferente <b>".$proveedor['usrnomxx']." | ".$proveedor['razonxxx']."</b>.</p>";
                    $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico soporte@intelcost.com. Adicionalmente, una vez ingrese al sistema podrá solicitar soporte a través de nuestro chat en línea.</p>";
                    $emailContent .= "</div>";
                    $emailContent .= '</td></tr>';
                    $emailContent .= '</table>';

                    $obj_adicionales = new stdClass();
                    $obj_adicionales->relacion_id = $infoCorreo['idOferta'];
                    $obj_adicionales->modulo_id = 5;

                    $enviar = $this->modeloComunicaciones->sendEmail($infoCorreo['email'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
                    if($enviar){
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg = "Se ha notificado al siguiente aprobador.";
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se presentó un error al notificar al siguiente aprobador.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Se presentó un error al consultar información - notificación aprobador.";
                }
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al consultar aprobadores.";
        }
    }

    public function generarEvaluacionGlobalOferente($objAprobacion){
        $objAprobacion->aprobacion = (int) $objAprobacion->aprobacion;
        if(isset($objAprobacion->aprobacion) && ($objAprobacion->aprobacion == 1 || $objAprobacion->aprobacion == 2)){

            //CREAR ARREGLO DE APROBADORES UBICANDO AL COMPRADOR EN LA ÚLTIMA POCISIÓN.
            $array_aprobadores = array();
            //se usa para que solo se agreguen usuarios aprobadores en orden y validar los mismos evitando que se valide información de todos los agregados causando que se haga lenta la función.
            $banderaAgregar = true;
            $objAprobadores = $this->obtenerUsuariosAprobadoresDocumentos($objAprobacion->id_oferta);
            if($objAprobadores->bool){
                foreach ($objAprobadores->msg as $objAprobador) {
                    if($banderaAgregar){
                        $aprobador = array("id_aprobador" => $objAprobador['id_usuario_aprobador'], "nombre" => $objAprobador['nombre'], "accesos" => $objAprobador['accesos'], "tipo_aprobador" => "aprobador");
                        array_push($array_aprobadores, $aprobador);
                        if($_SESSION['idusuario'] == $objAprobador['id_usuario_aprobador']){
                            $banderaAgregar = false;
                        }
                    }
                }
            }
            if($banderaAgregar){    
                $queryComprador = "SELECT O.id, U.nombre as comprador, O.usuario_creacion as id_comprador, CONCAT('[\"', GROUP_CONCAT(OD.id SEPARATOR '\", \"'), '\"]') AS accesos FROM ofertas AS O INNER JOIN usuarios AS U ON U.id = O.usuario_creacion LEFT JOIN oferta_documentos_oferentes AS OD ON OD.oferta_id = O.id AND OD.evaluable = 'si' AND OD.estado = 'activo' WHERE O.id = ".$objAprobacion->id_oferta;
                $sqlComprador = $this->intelcost->prepareStatementQuery('cliente', $queryComprador, 'select', false, "", "", "Validar Comprador aprobación global.");
                if($sqlComprador->bool){
                    if($sqlComprador->msg->num_rows > 0){
                        $dataComprador = $sqlComprador->msg->fetch_assoc();
                        $comprador = array("id_aprobador" => $dataComprador['id_comprador'], "nombre" => $dataComprador['comprador'], "accesos" => $dataComprador['accesos'], "tipo_aprobador" => "comprador");
                        array_push($array_aprobadores, $comprador);
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Error al obtener comprador del proceso.";
                        return $this->intelcost->response;
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Se presentó un error al validar el comprador del proceso.";
                    return $this->intelcost->response;
                }
            }
            //VALIDAR QUE EL USUARIO LOGUEADO SE ENCUENTRE DENTRO DE LOS POSIBLES APROBADORES
            $dbConection = $this->intelcost->db->createConection("");
            if(!$dbConection){
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Se presentó un error de conexión - aprobación global.";
                return $this->intelcost->response;
            }

            if(in_array($_SESSION['idusuario'], array_column($array_aprobadores, "id_aprobador"))){
                $arrayAprobables = array();
                //VALIDAR UNO A UNO SÍ EL DOCUMENTO DE CADA ACCESO YA FUE APROBADO POR c/u APROBADORES.
                $queryDoc = "SELECT OD.titulo, Count(OEH.id_historial) AS total, OD.id AS id_documento, OP.id AS id_proveedor, OE.id AS id_evaluacion, OE.usuario_id as id_evaluador, OEH.id_historial, OEH.id_usuario, OD.obligatorio FROM oferta_documentos_oferentes AS OD LEFT JOIN oferta_documentos_ofertascliente AS OP ON OP.id_documento_oferente = OD.id AND OP.id_usuario = ? LEFT JOIN oferta_evaluacion_documento AS OE ON OE.documento_id = OP.id AND OE.estado = 'activo' LEFT JOIN oferta_evaluacion_documento_historial AS OEH ON OEH.id_documento = OP.id AND OEH.tipo_usuario_registra = 'aprobador' AND OEH.id_evaluacion = OE.id AND OEH.id_usuario = ? WHERE OD.id = ? ";
                foreach ($array_aprobadores as $key => $aprobador) {
                    $arrayDocsAprob = array();
                    $accesos = json_decode($aprobador['accesos']);
                    $Id_usuario_proveedor = (int) $objAprobacion->id_usuario;
                    foreach ($accesos as $id_documento) {
                        $aprobador['id_aprobador'] = (int) $aprobador['id_aprobador'];
                        $id_documento = (int) $id_documento;
                        if($sqlDoc = $dbConection->prepare($queryDoc)){
                            if($sqlDoc->bind_param('iii', $Id_usuario_proveedor, $aprobador['id_aprobador'], $id_documento)){
                                if($sqlDoc->execute()){
                                    if($resultado = $sqlDoc->get_result()){
                                        $dataDoc = $resultado->fetch_assoc();

                                        //SI ALGÚN DOCUMENTO NO HA SIDO EVALUADO, SE DETIENE EL PROCESO DE APROBACIÓN.
                                        if(empty($dataDoc['id_evaluacion'])){
                                            if($dataDoc['obligatorio'] == 1){
                                                $this->intelcost->response->bool = false;
                                                $this->intelcost->response->msg = "No se ha evaluado el documento <b>$dataDoc[titulo]</b>. ";
                                                return $this->intelcost->response;
                                            }
                                        }else{
                                            $aprobado = ($dataDoc['total'] > 0 ? true : false);
                                            $dataDoc['id_proveedor'] = ($dataDoc['id_proveedor'] != null && $dataDoc['id_proveedor'] != "") ? $dataDoc['id_proveedor'] : "";
                                            array_push($arrayDocsAprob, array("id_documento" => $id_documento, "documento" => $dataDoc['titulo'], "aprobado" => $aprobado, "id_evaluacion" =>  $dataDoc['id_evaluacion'], "id_evaluador" =>  $dataDoc['id_evaluador'], "id_proveedor" => $dataDoc['id_proveedor']));
                                        }
                                    }else{
                                        $this->intelcost->response->bool = false;
                                        $this->intelcost->response->msg = "Error de consulta 4";
                                        return $this->intelcost->response;
                                    }
                                }else{
                                    $this->intelcost->response->bool = false;
                                    $this->intelcost->response->msg = "Error de consulta 3";
                                    return $this->intelcost->response;
                                }
                            }else{
                                $this->intelcost->response->bool = false;
                                $this->intelcost->response->msg = "Error de consulta 2";
                                return $this->intelcost->response;
                            }
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg = "Error de consulta 1";
                            return $this->intelcost->response;
                        }
                    }
                    $aprobador['aprobaciones'] = $arrayDocsAprob;
                    array_push($arrayAprobables, $aprobador);
                }
                
                $mensajeNoAprobados = array();
                $aprobables = array();
                foreach ($arrayAprobables as $key => $aprobador) {
                    //SOLO SE REALIZA LA EJECUCIÓN DE VALIDACIÓN Y APROBACIÓN PARA EL APROBADOR LOGUEADO.
                    if($_SESSION['idusuario'] == $aprobador['id_aprobador']){
                        $validar_anterior = true;
                        if($key == 0){
                            $validar_anterior = false;
                        }
                        //validar las aprobaciones del aprobador anterior
                        if($validar_anterior){
                            $aprobacionesAnteriorAprobador = $arrayAprobables[$key-1]['aprobaciones'];
                            //VALIDAMOS CADA DOCUMENTO CON LOS QUE APROBÓ EL ANTERIOR APROBADOR
                            foreach ($aprobador['aprobaciones'] as $acceso) {
                                //SE VALIDA SÍ EL DOCUMENTO DEL ACTUAL APROBADOR SE ENCUENTRA ENTRE LOS QUE APROBÓ EL ANTERIOR, SÍ NO EXISTE, SE AGREGA A LA PILA DE LOS QUE SERÁN APROBADOS.
                                if(in_array($acceso['id_documento'], array_column($aprobacionesAnteriorAprobador, "id_documento"))){
                                    $index = array_search($acceso['id_documento'], array_column($aprobacionesAnteriorAprobador, "id_documento"));
                                    //SE VALIDA SÍ EL APROBADOR YA REALIZÓ LA APROBACIÓN DEL DOCUMENTO. SÍ YA SE APROBÓ, SE AGREGA A LA PILA PARA QUE SEA APROBADO POR EL ACTUAL APROBADOR. EN CASO CONTRARIO, SE AGREGA A LOS MENSAJES DE DOCUMENTOS NO APROBADOS POR EL ANTERIOR APROBADOR.
                                    if($aprobacionesAnteriorAprobador[$index]['aprobado']){
                                        array_push($aprobables, $acceso);
                                    }else{
                                        array_push($mensajeNoAprobados, "El documento ".$aprobacionesAnteriorAprobador[$index]['documento']." no ha sido aprobado por el aprobador $aprobador[nombre].");
                                    }
                                }else{
                                    array_push($aprobables, $acceso);
                                }
                            }
                        }else{
                            $aprobables = $arrayAprobables[$key]['aprobaciones'];
                        }
                    }
                }
                if(COUNT($aprobables) > 0){
                    $objAprobacion->usuarios_validacion = array();
                    $contRgistrados = 0;
                    foreach ($aprobables as $aprobable) {
                        if(!$aprobable['aprobado']){
                            $queryAprobacion = "INSERT INTO `oferta_evaluacion_documento_historial` ( `id_evaluacion`, `id_documento`, `id_usuario`, `tipo_usuario_registra`, `observaciones`, `valoracion`) VALUES ($aprobable[id_evaluacion], $aprobable[id_proveedor], $_SESSION[idusuario], 'aprobador', '".$objAprobacion->observacion."', ".$objAprobacion->aprobacion.")";
                            $sqlAprobacion = $dbConection->query($queryAprobacion);
                            if($queryAprobacion){
                            //if(true){
                                $contRgistrados++;
                                if($objAprobacion->aprobacion == 2){
                                    $queryEvaluacion = "UPDATE `oferta_evaluacion_documento` SET `estado` = 2, `usuario_actualizacion` = $_SESSION[idusuario], `fecha_actualizacion` = '".date("Y-m-d H:i:s")."' WHERE `id` = $aprobable[id_evaluacion] ";
                                    $sqlEvaluacion = $dbConection->query($queryEvaluacion);
                                    if($sqlEvaluacion){
                                    //if(true){
                                        if(!in_array($aprobable['id_evaluador'], $objAprobacion->usuarios_validacion)){
                                            array_push($objAprobacion->usuarios_validacion, $aprobable['id_evaluador']);
                                        }
                                    }else{
                                        $this->intelcost->response->bool = false;
                                        $this->intelcost->response->msg = "Ocurrió un error, no se logró guardar el rechazo a la evaluación para el documento $aprobable[documento].";
                                        return $this->intelcost->response;
                                    }
                                }
                            }else{
                                $this->intelcost->response->bool = false;
                                $this->intelcost->response->msg = "Ocurrió un error, no se logró guardar la aprobación para el documento $aprobable[documento].";
                                return $this->intelcost->response;
                            }
                        }else{
                            array_push($mensajeNoAprobados, "El documento <b>$aprobable[documento]</b> ya había sido aprobado anteriormente.");
                        }
                    }
                    if($objAprobacion->aprobacion == 2){
                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                            OfertaDocumentosCriteriosOferentesEvaluaciones::where('id_evaluacion', $objAprobacion->id_evaluacion)->update(['estado' => 'Historial', 'updated_at' => date('Y-m-d H:i:s')]);
                        }
                        $envioNotificacion = $this->notificacionAprobacionGlobalDocumentosOferente($objAprobacion);
                        if($contRgistrados > 0){
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg = "Se ha rechazado la evaluación correctamente.<br> ".implode("<br>", $mensajeNoAprobados);;
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg = "No se almacenaron registros.<br>".implode("<br>", $mensajeNoAprobados);
                        }
                        if($envioNotificacion){
                            $this->intelcost->response->msg .= "<br>Se ha notificado al evaluador para que realice la evaluación nuevamente.";
                        }else{
                            $this->intelcost->response->msg .= "<br>Se presentó un error al notificar al evaluador.";
                        }
                    }else{
                        if($contRgistrados > 0){
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg = "Se guardó la aprobación general correctamente. <br>".implode("<br>", $mensajeNoAprobados);;
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg = "No se almacenaron registros. <br>".implode("<br>", $mensajeNoAprobados);
                        }
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Ocurrió un error, no se logró validar documentos para aprobar.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "Su usuario no se encuentra relacionado como aprobador en el proceso.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "El concepto de aprobación no es válido.";
        }
        
        // validarUltimaEvaluacionTecnica hace uso de prepareStatementQuery esto hace que se reescriba "$this->intelcost->response"
        $varTemporal = json_decode(json_encode($this->intelcost->response));
            $this->validarUltimaEvaluacionTecnica($objAprobacion->id_oferta);
        return $varTemporal;
    }

    public function remitirEvaluacionOferentes($idOferta){
        $querAccesos = "SELECT accesos FROM oferta_usuarios_internos WHERE id_oferta = ? AND id_usuario = $_SESSION[idusuario] AND estado = 'activo' ";
        $sqlAccesos = $this->intelcost->prepareStatementQuery('cliente', $querAccesos, 'select', true, "i", array((int) $idOferta), "Obtener accesos - remitir evaluación.");
        if($sqlAccesos->bool){
            if($sqlAccesos->msg->num_rows > 0){
                $resAccesos = $sqlAccesos->msg->fetch_assoc();
                $misAccesos = (array) json_decode($resAccesos['accesos']);

                $dbConection = $this->intelcost->db->createConection("cliente");
                if($dbConection){
                    $queryParticipantes = "SELECT id_usuario, id_proveedor, nombre_contacto, email_usuario FROM oferta_participantes WHERE id_oferta = $idOferta AND estado_participacion = 'ofe_enviada' ";
                    $sqlParticipantes = $dbConection->query($queryParticipantes);
                    if($sqlParticipantes){
                        if($sqlParticipantes->num_rows > 0){
                            $arrayParticipantes = array();
                            while($participante = $sqlParticipantes->fetch_assoc()){
                                array_push($arrayParticipantes, $participante);
                            }
                            $banderaRemitir = true;
                            foreach ($arrayParticipantes as $participante) {
                                foreach ($misAccesos as $acceso) {
                                    $queryValidacion = "SELECT OD.id, OD.titulo, OD.evaluable, OD.fecha_creacion as fecha_creacion_doc, ODC.id AS id_doc_proveedor, ODC.fecha_creacion, IFNULL(OEV.id, 0) AS id_evaluacion, OEV.usuario_id AS id_evaluador FROM oferta_documentos_oferentes AS OD LEFT JOIN oferta_documentos_ofertascliente AS ODC ON ODC.id_documento_oferente = OD.id AND ODC.id_usuario = $participante[id_usuario] LEFT JOIN oferta_evaluacion_documento AS OEV ON OEV.documento_id = ODC.id AND OEV.estado = 'activo' WHERE OD.estado = 'activo' AND (OD.id = ".$acceso->fileId." OR OD.doc_id = ".$acceso->fileId.")";
                                    $sqlValidacion = $dbConection->query($queryValidacion);
                                    if($sqlValidacion){
                                        if($sqlValidacion->num_rows > 0){
                                            $validacion = $sqlValidacion->fetch_assoc();
                                            $validacion['evaluable'] = ($validacion['evaluable'] == "si") ? true : false;
                                            $fecha_creacion = strtotime(date('Y-m-d H:i:s', strtotime($validacion['fecha_creacion_doc'])));
                                            $fecha_limite = strtotime(date('Y-m-d H:i:s', strtotime("2019-01-11 00:00:00")));
                                            if($fecha_creacion <= $fecha_limite){
                                                $validacion['evaluable'] = true;
                                            }
                                            if($validacion['evaluable']){
                                                if($validacion['id_evaluacion'] == 0){
                                                    $banderaRemitir = false;
                                                    $this->intelcost->response->bool = false;
                                                    $this->intelcost->response->msg = "No se ha evaluado el documento ".$acceso->fileName." para el proveedor $participante[nombre_contacto]. ";
                                                    return $this->intelcost->response;  
                                                }
                                            }
                                        }else{
                                            $banderaRemitir = false;
                                            $this->intelcost->response->bool = false;
                                            $this->intelcost->response->msg = "No se encontró el documento ".$acceso->fileName.".";
                                            return $this->intelcost->response;  
                                        }
                                    }else{
                                        $banderaRemitir = false;
                                        $this->intelcost->response->bool = false;
                                        $this->intelcost->response->msg = "Error al validar el documento ".$acceso->fileName.".";
                                        return $this->intelcost->response;  
                                    }
                                }
                            }
                            if($banderaRemitir){
                                $queryHistorial = "INSERT INTO historial_aprobaciones (`oferta_id`, `usuario_id`, `tipo_historial`, `fecha_creacion`) VALUES ($idOferta, $_SESSION[idusuario], 'Evaluación remitida', '".date("Y-m-d H:i:s")."')";
                                $sqlHistorial = $dbConection->query($queryHistorial);
                                if($sqlHistorial){
                                    $queryEenvio = "UPDATE oferta_usuarios_internos SET usuario_actualizacion = $_SESSION[idusuario], fecha_actualizacion = '".date("Y-m-d H:i:s")."', envio_eval = 'si', fecha_envio_eval = '".date("Y-m-d H:i:s")."'  WHERE id_oferta = $idOferta AND id_usuario = $_SESSION[idusuario] ";
                                    $sqlEnvio = $dbConection->query($queryEenvio);
                                    if($sqlEnvio){
                                        $notificacion = $this->notificarEvaluacionComprador($idOferta);
                                        $this->intelcost->response->bool = true;
                                        $this->intelcost->response->msg = "Evaluación finalizada.";
                                        if($notificacion){
                                            $this->intelcost->response->msg .= "Se ha notificado al comprador.";
                                        }else{
                                            $this->intelcost->response->msg .= "Se presentó un error al notificar al comprador.";
                                        }
                                        $queryHistorialOferta = "SELECT HO.observacion, HO.tipo_historial, HO.fecha_creacion, U.nombre FROM historial_aprobaciones AS HO INNER JOIN usuarios AS U ON HO.usuario_id = U.id WHERE HO.oferta_id = $idOferta ORDER BY HO.fecha_creacion DESC";
                                        $sqlHistorialOferta = $dbConection->query($queryHistorialOferta);
                                        $arrayHistorial = array();
                                        if($sqlHistorialOferta){
                                            while ($historial = $sqlHistorialOferta->fetch_assoc()) {
                                                $historial['fecha_creacion'] = $this->intelcost->castiarFechayHoraIntelcost($historial['fecha_creacion']);
                                                array_push($arrayHistorial, $historial);
                                            }
                                        }
                                        $this->intelcost->response->historial = $arrayHistorial;
                                    }else{
                                        $this->intelcost->response->bool = false;
                                        $this->intelcost->response->msg = "Se presentó un error al guardar el envío de la evaluación."; 
                                    }
                                }else{
                                    $this->intelcost->response->bool = false;
                                    $this->intelcost->response->msg = "Se presentó un error al enviar la validación.";
                                }
                            }
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg = "No se encontró participación en el proceso.";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg = "Se presentó un error al consultar participantes - remitir evaluación.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg = "Error de conexión.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se encontraron permisos evaluador.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al consultar permisos evaluador - remitir evaluación.";
        }
        $varTemporal = $this->intelcost->response;
            if ($this->intelcost->response->bool == true){
                // Determinamos si la oferta no tiene ningún aprobador
                $aprobadores = DB::table('oferta_usuarios_aprobadores')->where('id_oferta', '=', $idOferta)->where('estado', '!=', 'inactivo')->get();
                if (count($aprobadores) == 0){
                    $this->evaluacionOfertaTecnica($idOferta);
                }
            }
        return $varTemporal;
    }

    private function notificarEvaluacionComprador($idOferta){
        $queryDataOferta = "SELECT O.seq_id, O.objeto, U.nombre, U.username as email FROM ofertas O LEFT JOIN usuarios U ON O.usuario_creacion = U.id WHERE O.id = $idOferta";
        $sqlDataOferta = $this->intelcost->prepareStatementQuery('cliente', $queryDataOferta, 'select', false, "", "", "Obtener información básica oferta - notificación evaluación.");
        if($sqlDataOferta->bool){
            if($sqlDataOferta->msg->num_rows > 0){
                $dataOferta = $sqlDataOferta->msg->fetch_assoc();
                $asunto = "Evaluación finalizada. Proceso - ".$dataOferta['seq_id'] .".";
                $emailContent  = '<table style="background: #f5f5f5; width:100%; font-family:sans-serif; border-collapse: collapse;" cellspacing="0">';
                $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%; width: 90%; margin:auto;">';
                $emailContent .= "<div style='padding-left:10%'>";
                $emailContent .= "<p>Estimado(a): ".$dataOferta['nombre']."</p><br>";
                $emailContent .= "<p>El evaluador: <b>".$_SESSION['usuario']."</b> ha remitido la evaluación realizada sobre el proceso <b>".$dataOferta['seq_id'].".</b> - ".$dataOferta['objeto']."</p><br>";
                $emailContent .= "</div>";
                $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico soporte@intelcost.com. Adicionalmente, una vez ingrese al sistema, podrá solicitar soporte a través de nuestro chat en línea.</p>";
                $emailContent .= '</td></tr>';
                $emailContent .= '</table>';

                $obj_adicionales = new stdClass();
                $obj_adicionales->relacion_id = $idOferta;
                $obj_adicionales->modulo_id = 5;

                $enviar = $this->modeloComunicaciones->sendEmail($dataOferta['email'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
                return $enviar;
            }else{
                return false;
            }       
        }else{
            return false;
        }
    }

    private function uploadFile($file, $oferta_id, $docid){
        $response = new stdClass();
        if (isset($file)) {
            $ruta = "../ic_files/empresas/".$_SESSION['empresaid']."/";
            if (!file_exists($ruta)) {
                $response = $this->validarDirectory($ruta, __LINE__);
                if (!$response->bool) {
                    return $response;
                }
            }
            $ruta = "../ic_files/empresas/".$_SESSION['empresaid']."/ofertas/";
            if (!file_exists($ruta)) {
                $response = $this->validarDirectory($ruta, __LINE__);
                if (!$response->bool) {
                    return $response;
                }
            }
            $ruta = "../ic_files/empresas/".$_SESSION['empresaid']."/ofertas/".$oferta_id."/";
            if (!file_exists($ruta)) {
                $response = $this->validarDirectory($ruta, __LINE__);
                if (!$response->bool) {
                    return $response;
                }
            }
            $ruta = "../ic_files/empresas/".$_SESSION['empresaid']."/ofertas/".$oferta_id."/evaluacion_documento/";
            if (!file_exists($ruta)) {
                $response = $this->validarDirectory($ruta, __LINE__);
                if (!$response->bool) {
                    return $response;
                }
            }
            $ruta = "../ic_files/empresas/".$_SESSION['empresaid']."/ofertas/".$oferta_id."/evaluacion_documento/".$docid."/";
            if (!file_exists($ruta)) {
                $response = $this->validarDirectory($ruta, __LINE__);
                if (!$response->bool) {
                    return $response;
                }
            }

            $path = pathinfo($file["name"]);
            $ext = strtolower($path["extension"]);
            if ($ext == "pdf" || $ext == "jpg" || $ext == "docx" || $ext == "doc" || $ext == "png" || $ext == "xlsx" || $ext == "xls" || $ext == "zip" || $ext == "rar") {
                $name = $path["filename"];
                $name = $this->intelcost->validarNombreArchivos($name);
                $name = $name.".".$ext;
                if (is_dir($ruta) && is_writable($ruta)) {
                    if (move_uploaded_file($file["tmp_name"], $ruta.$name)) {
                        $response->bool = true;
                        $response->msg = $ruta.$name;
                        $this->intelcost->cargarArchivosS3($ruta.$name, 'cliente');
                        $this->intelcost->cargarArchivosServerFtp($ruta.$name, 'cliente');
                    }else{
                        $response->bool = false;
                        $response->msg = "No se logro cargar el adjunto en el servidor, intentelo nuevamente.";
                    }
                }else{
                    $response->bool = false;
                    $response->msg = "El directorio no existe o no puede escribirse en el ERROR-".__LINE__;
                }
            }else{
                $response->bool = false;
                $response->msg = "Sólo es permitido los archivos con extension PDF, JPG, PNG, DOCX, DOC, XLSX, XLS y paquete de archivos en formato ZIP <b>\"".$file['name']."\"</b>";
            }
        }else{
            $response->bool = false;
            $response->msg = "No hay archivo ERROR-".__LINE__;
        }
        return $response;
    }

    private function validarDirectory($ruta, $line){
        $response = new stdClass();
        mkdir($ruta);
        if (is_writable($ruta)) {
            $response->bool = true;
            $response->msg = '';
        }else{
            $response->bool = false;
            $response->msg = "No fue posible crear la ruta ERROR-".$line;
        }
        return $response;
    }

    private function deleteArchive($archive){
        if (file_exists($archive)) {
            //unlink($archive);
        }
    }
    private function getDataProveedor($proveedor_id){
        $SqlQuery = "SELECT sys00001.usrnomxx, sys00001.usrmailx, _0002103.razonxxx FROM sys00001 INNER JOIN _0002103 ON _0002103.id_empresa = sys00001.cod_empresa WHERE sys00001.usridxxx = ?";
        $CscCreadorDatos = $this->intelcost->prepareStatementQuery('intelcost', $SqlQuery, 'select', true, "i", array((int) $proveedor_id), "Obtener proveedores");
        if ($CscCreadorDatos->bool) {
            return $CscCreadorDatos->msg->fetch_assoc();
        }else{
            return "";
        }
    }
    private function enviarCorreoCreador($oferta_id, $archivo_id, $usuario_id){
        $response = new stdClass();
        $SqlQuery = "SELECT OED.observaciones, OED.file_url, OED.fecha_creacion, OED.cumpleNoCumple, OED.subsanable, OED.usuario_id, ofertas.usuario_creacion, ofertas.seq_id, usuarios.id, usuarios.nombre, usuarios.email, oferta_documentos_ofertascliente.id_documento_oferente, ofertas.seq_id, OED.documento_id, oferta_documentos_oferentes.titulo, oferta_documentos_ofertascliente.id_usuario, capitulos.nombre nombre_sobre FROM ofertas LEFT JOIN usuarios ON ofertas.usuario_creacion = usuarios.id LEFT JOIN oferta_evaluacion_documento OED ON ofertas.id = OED.oferta_id LEFT JOIN oferta_documentos_ofertascliente ON oferta_documentos_ofertascliente.id = OED.documento_id LEFT JOIN oferta_documentos_oferentes ON oferta_documentos_oferentes.id = oferta_documentos_ofertascliente.id_documento_oferente LEFT JOIN capitulos ON oferta_documentos_oferentes.sobre = capitulos.id WHERE ofertas.id = ? AND OED.documento_id = ? ";
        $CscCreadorDatos = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', true, "ii", array((int) $oferta_id, (int) $archivo_id), "Obtener datos envio correo");

        if ($CscCreadorDatos->bool) {
            $url_completa = $_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'ic')-1);
            $data = $CscCreadorDatos->msg->fetch_assoc();
            $proveedor = $this->getDataProveedor($data['id_usuario']);
            $datosEvaluador = $this->getDatosUsuarioSesion($usuario_id)->fetch_assoc();
            $subject = "Evaluación del documento ".$data['titulo'].' / '.$data['seq_id'];
            $archive = (empty($data['file_url']) ? '' : /*str_replace('../', '', $data['file_url'])*/ $this->intelcost->generaRutaServerFiles($data['file_url'], 'cliente'));
            $emailContent = '<table style="background: #f5f5f5;width:100%;font-family:sans-serif;border-collapse: collapse; " cellspacing="0">';

            $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%;width: 90%; margin:auto">';
            $emailContent .= '<p>Resumen de la evaluación del documento: </p>';
            $emailContent .= "<h3>Oferta No. ".$data['seq_id']."</h3>";
            $emailContent .= "<div style='padding-left:10%'>";
            $emailContent .= "<p><b>Proveedor:</b> ".$proveedor['usrnomxx']." | ".$proveedor['razonxxx']."</p>";
            $emailContent .= "<p><b>Documento:</b> ".$data['titulo']."</p>";
            $emailContent .= "<p><b>Evaluador:</b> ".$datosEvaluador['nombre']."</p>";
            $emailContent .= "<p><b>Evaluación:</b> ".$data['cumpleNoCumple']."</p>";
            if ($data['cumpleNoCumple'] != "Cumple") {
                $emailContent .= "<p><b>¿Subsanable?:</b> ".$data['subsanable']."</p>";
                $emailContent .= "<p><b>Observaciones:</b> ".$data['observaciones']."</p>";
                if ($archive != "") {
                    $emailContent .= "<p><b>Anexo:</b> <a href='//".$url_completa."/".$archive."' target='_blank'>Link Aquí</a></p>";
                }
            }

            $emailContent .= "</div>";
            $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Puede contactarnos al tel&eacute;fono +57 1 489 8100, o al correo electronico soporte@intelcost.com. Adicionalmente una vez ingrese al sistema podr&aacute; pedir soporte a trav&eacute;s de nuestro chat en vivo.</p>";
            $emailContent .= '</td></tr>';

            $obj_adicionales = new stdClass();
            $obj_adicionales->relacion_id = $oferta_id;
            $obj_adicionales->modulo_id = 5;

            $enviar = $this->modeloComunicaciones->sendEmail($data['email'],$emailContent,$subject,"ComunicadoLogoCliente",$_SESSION["empresaid"], $obj_adicionales);
            if ($enviar) {
                $response->bool = true;
                $response->msg = "Mensaje enviado";
            }else{
                $response->bool = false;
                $response->msg = "No fue posible enviar el mail";
            }
        }else{
            $response->bool = false;
            $response->msg = "Ha ocurrido un error en la consulta ERROR-".__LINE__;
        }
        return $response;
    }

    private function notificacionEvaluacionDocumentoIndividualRechazado($id_evaluacion, $observacion){
        $objEvaluacion = $this->obtenerInformacionEvaluacionDocumentoId($id_evaluacion);
        if($objEvaluacion->bool){
            $dataEvaluacion = $objEvaluacion->msg;
            $proveedor = $this->getDataProveedor($dataEvaluacion['id_usuario']);

            $asunto = "Evaluación rechazada. Documento ".$dataEvaluacion['titulo'].' / evento - '.$dataEvaluacion['seq_id'];
            $emailContent  = '<table style="background: #f5f5f5;width:100%;font-family:sans-serif;border-collapse: collapse; " cellspacing="0">';
            $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%;width: 90%; margin:auto">';
            $emailContent .= "<p>Estimado/a: <b>".$dataEvaluacion['nombre_evaluador']."</b></p><br>";
            $emailContent .= "<p>El aprobador <b>$_SESSION[usuario]</b> ha rechazado la evaluación del siguiente documento: </p>";
            $emailContent .= '<p>Resumen del documento: </p>';
            $emailContent .= "<h3>Evento - <b>".$dataEvaluacion['seq_id']."</b></h3>";
            $emailContent .= "<div style='padding-left:10%'>";
            $emailContent .= "<p><b>Proveedor:</b> ".$proveedor['usrnomxx']." | ".$proveedor['razonxxx']."</p>";
            $emailContent .= "<p><b>Documento:</b> ".$dataEvaluacion['titulo']."</p>";
            $emailContent .= "<p><b>Evaluación:</b> ".$dataEvaluacion['cumpleNoCumple']."</p>";
            $emailContent .= "<p><b>Fecha de evaluación:</b> ".$this->intelcost->castiarFechayHoraIntelcost($dataEvaluacion['fecha_evaluacion'])."</p>";
            $emailContent .= "<p><b>Motivo de rechazo:</b> ".$observacion."</p>";
            $emailContent .= "</div>";
            $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico soporte@intelcost.com. Adicionalmente, una vez ingrese al sistema podrá solicitar soporte a través de nuestro chat en línea.</p>";
            $emailContent .= '</td></tr>';
            $emailContent .= '</table>';

            $obj_adicionales = new stdClass();
            $obj_adicionales->relacion_id = $id_evaluacion;
            $obj_adicionales->modulo_id = 19;

            $enviar = $this->modeloComunicaciones->sendEmail($dataEvaluacion['email_evaluador'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
            return $enviar;
        }else{
            return false;
        }
    }

    private function notificacionAprobacionGlobalDocumentosOferente($objAprobacion){
        $queryDataOferta = "SELECT O.seq_id, O.objeto, U.nombre FROM ofertas O LEFT JOIN usuarios U ON O.usuario_creacion = U.id WHERE O.id = ".$objAprobacion->id_oferta;
        $sqlDataOferta = $this->intelcost->prepareStatementQuery('cliente', $queryDataOferta, 'select', false, "", "", "Obtener información básica oferta - notificación aprobación general.");
        if($sqlDataOferta->bool){
            if($sqlDataOferta->msg->num_rows > 0){
                $dataOferta = $sqlDataOferta->msg->fetch_assoc();
                $proveedor = $this->getDataProveedor($objAprobacion->id_usuario);

                $asunto = "Evaluación rechazada. Proceso - ".$dataOferta['seq_id'] ." / ".$dataOferta['objeto'];
                /*SE RECORRE EL ARREGLO DE USUARIOS QUE HAYAN EVALUADO LOS DOCUMENTOS DEL OFETENTE*/
                foreach ($objAprobacion->usuarios_validacion as $usuario) {
                    $objUsuario = $this->getDatosUsuarioSesion($usuario)->fetch_assoc();
                    $emailContent  = '<table style="background: #f5f5f5;width:100%;font-family:sans-serif;border-collapse: collapse; " cellspacing="0">';
                    $emailContent .= '<tr><td style="float:left;background: #fff;padding:0% 5% 5%;width: 90%; margin:auto">';
                    $emailContent .= "<p>Estimado/a: <b>".$objUsuario['nombre']."</b></p><br>";
                    $emailContent .= "<div style='padding-left:10%'>";
                    $emailContent .= "<p>El comprador <b>".$dataOferta['nombre']."</b> ha rechazado la evaluación realizada en el evento <b>".$dataOferta['seq_id'] ." / ".$dataOferta['objeto']."</b> para el oferente <b>".$proveedor['usrnomxx']." | ".$proveedor['razonxxx'].".</b></p>";
                    $emailContent .= "<p style='font-size:12px;margin-top:50px;'>Puede contactarnos al teléfono +57 1 489 8100, o al correo electrónico soporte@intelcost.com. Adicionalmente, una vez ingrese al sistema podrá solicitar soporte a través de nuestro chat en línea.</p>";
                    $emailContent .= "</div>";
                    $emailContent .= '</td></tr>';
                    $emailContent .= '</table>';

                    $obj_adicionales = new stdClass();
                    $obj_adicionales->relacion_id = $objAprobacion->id_oferta;
                    $obj_adicionales->modulo_id = 5;

                    $enviar = $this->modeloComunicaciones->sendEmail($objUsuario['email'], $emailContent, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"], $obj_adicionales);
                }
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    private function obtenerInformacionEvaluacionDocumentoId($id_evaluacion){
        $queryEvaluacion = "SELECT OFE.id_cliente, OED.cumpleNoCumple, OED.observaciones, OFE.usuario_creacion, OFE.seq_id, US.id, US.nombre, US.email, ODOP.id_documento_oferente, OED.documento_id, OED.fecha_creacion as fecha_evaluacion, ODO.titulo, ODOP.id_usuario, CAP.nombre AS nombre_sobre, US2.nombre as nombre_evaluador, US2.username AS email_evaluador FROM ofertas AS OFE LEFT JOIN usuarios AS US ON OFE.usuario_creacion = US.id LEFT JOIN oferta_evaluacion_documento AS OED ON OFE.id = OED.oferta_id LEFT JOIN oferta_documentos_ofertascliente AS ODOP ON ODOP.id = OED.documento_id LEFT JOIN oferta_documentos_oferentes AS ODO ON ODO.id = ODOP.id_documento_oferente LEFT JOIN capitulos AS CAP ON ODO.sobre = CAP.id LEFT JOIN usuarios AS US2 ON OED.usuario_id = US2.id WHERE OED.id = ? ";
        $sqlEvaluacion = $this->intelcost->prepareStatementQuery('cliente', $queryEvaluacion, 'select', true, "i", array((int) $id_evaluacion), "Obtener información evaluación.");
        if($sqlEvaluacion->bool){
            if($sqlEvaluacion->msg->num_rows > 0){
                $evaluacion = $sqlEvaluacion->msg->fetch_assoc();
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $evaluacion;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="No se encontró la evaluación.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Se presentó un error al consultar la información de la evaluación.";
        }
        return $this->intelcost->response;
    }

    private function getDatosUsuarioSesion($usuario_id){
        $SqlQueryEvaluador = "SELECT nombre, email FROM usuarios WHERE id = ?";
        $CscEvaluadorDatos = $this->intelcost->prepareStatementQuery('cliente', $SqlQueryEvaluador, 'select', true, "i", array((int) $usuario_id), "Obtener datos usuario - evaluador");
        if ($CscEvaluadorDatos->bool) {
            return $CscEvaluadorDatos->msg;
        }else{
            $response->bool = false;
            $response->msg = "Ha ocurrido un error en la consulta ERROR-".__LINE__;
        }
        return $response;
    }

    public function aprobarAdjudicacionOferta($id_oferta){
        $resultado = $this->obtenerFechaAprobacionAdjudicacion($id_oferta);
        if(empty($resultado->msg)){
            $params = array(date("Y-m-d h:i:s"),(int) $_SESSION["empresaid"],(int) $id_oferta);
            $SqlOfer = 'UPDATE ofertas SET `fecha_autorizacion_ajudicacion` = ?, usuario_actualizacion = '.$_SESSION['idusuario'].' WHERE `id_cliente`= ? AND `id`= ? ';
            $cscOfer = $this->intelcost->prepareStatementQuery('cliente', $SqlOfer, 'UPDATE', true, "sii", $params, "Actualizar ofecha aprobacion oferta");
            if($cscOfer->bool){
                if($cscOfer->msg > 0){
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg = "fecha actualizada";
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="Error al actualizar fecha aprobacion de adjudicacion.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Error al relacionar soporte.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "ya_tiene_evaluacion";
        }
        return $this->intelcost->response;
        
    }

    public function obtenerFechaAprobacionAdjudicacion($idOferta){
        $sqlEvalua  ="SELECT fecha_autorizacion_ajudicacion FROM ofertas  WHERE id = ? ";

        $SqlUsr = $this->intelcost->prepareStatementQuery('cliente', $sqlEvalua, 'select', true, "i", array((int) $idOferta), "obtener carta adjudicacion de oferta.");
        if($SqlUsr->bool){
            if($SqlUsr->msg->num_rows > 0){
                 $fechaAprobacion = $SqlUsr->msg->fetch_assoc();
                 $fechaAprobacion = $fechaAprobacion["fecha_autorizacion_ajudicacion"];
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $fechaAprobacion;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "no se encontraron evaluaciones.";
            }
        }else{
            $this->intelcost->response = $SqlUsr;
        }
        return $this->intelcost->response;
    }

    public function obtenerTiposEventosCliente(){
        $query = "SELECT tipo FROM ofertas WHERE id_cliente = $_SESSION[empresaid] GROUP BY tipo";
        $sql = $this->intelcost->prepareStatementQuery('cliente', $query, 'select', false, "", "", "obtener listado tipos eventos.");
        if($sql->bool){
            if($sql->msg->num_rows > 0){
                $eventos = array();
                while ($evento = $sql->msg->fetch_assoc() ) {
                    array_push($eventos, $evento);
                }
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg = $eventos;
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg = "No se han creado eventos.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Se presentó un error al cargar tipos de evntos del cliente.";
        }
        return $this->intelcost->response;
    }

    protected static function castEstadoParticipacion($estado){
        $estadoResp = "";
        switch ($estado) {
            case 'inactivo':
                $estadoResp = "Oferta NO consultada";
                break;
            case 'ofe_consultada':
                $estadoResp = "Oferta consultada";
                break;
            case 'ofe_declinada':
                $estadoResp = "Oferta declinada";
                break;
            case 'ofe_enviada':
                $estadoResp = "Oferta enviada";
                break;
            default:
                $estadoResp = "";
                break;
        }
        return $estadoResp;
    }

    public function listadoProveedoresinvitadosEventos($filtros, $pagina){
        $query= 'id_cliente = ?';
        $parametros [] = $_SESSION["empresaid"];
        if(isset($filtros["proveedor"]) && !empty($filtros["proveedor"])){
            $query .= " AND offers.proveedor LIKE ? ";
            array_push($parametros, "%".$filtros["proveedor"]."%");
        }
        if(isset($filtros["usuario"]) && !empty($filtros["usuario"])){
            $query .= " AND 
            (
                offers.usuario LIKE ? OR 
                offers.email LIKE ?
            )";
            array_push($parametros, "%".$filtros["usuario"]."%", "%".$filtros["usuario"]."%");
        }
        if(isset($filtros["id_evento"]) && !empty($filtros["id_evento"])){
            $query .= " AND offers.evento = ? ";
            if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION["empresaid"] == 25){
                array_push($parametros, $filtros["id_evento"]);
            }else{
                array_push($parametros, (int) $filtros["id_evento"]);
            }
        }
        if(isset($filtros["tipo_evento"]) && !empty($filtros["tipo_evento"])){
            $query .= " AND offers.tipo = ? ";
            array_push($parametros, $filtros["tipo_evento"]);
        }
        if(isset($filtros["estado_participacion"]) && !empty($filtros["estado_participacion"])){
            $query .= " AND offers.estado_participacion = ? ";
            array_push($parametros, $filtros["estado_participacion"]);
        }
        if(isset($filtros["comprador"]) && !empty($filtros["comprador"])){
            $query .= " AND offers.usuario_creacion = ? ";
            array_push($parametros, (int) $filtros["comprador"]);
        }
        if(isset($filtros["id_evento"]) && !empty($filtros["id_evento"])){
            $query .= " AND offers.evento = ? ";
            $tipoParametros .= "i";
            array_push($parametros, (int) $filtros["id_evento"]);
        }
        if(isset($filtros["desde"]) && !empty($filtros["desde"])){
            $query .= " AND offers.invitacion >= CONCAT(?,' 00:00:00') ";
            array_push($parametros, $filtros["desde"]);
        }
        if(isset($filtros["hasta"]) && !empty($filtros["hasta"])){
            $query .= " AND offers.invitacion <= CONCAT(?,' 23:59:59') ";
            array_push($parametros, $filtros["hasta"]);
        }
        $query .= " ORDER BY offers.id_oferta desc ";
        if(isset($pagina) && !empty($pagina) && $pagina > 0){
            $limitPag = $this->intelcost->paginacion_limit_inicio_fin($pagina, 10);
            $query .= " LIMIT ".$limitPag['inicio'].",".$limitPag['fin'];
        }
        /* Inicio consulta */
        $totalOfertas = Offers::whereRaw($query,array($parametros))
        ->with(['infoActivities' => function($query){
            $query->select('codigo_actividad','producdesc as actividad');
        }])
        ->with(['infoUsuarioProveedor' => function($query){
            $query->select('usridxxx','usrnomxx','usrmailx','cod_empresa');
            //if($query->with('empresa')){                        
                $query->with(['empresa' => function($query){
                    $query->select('ciudidxx','nitempxx','razonxxx','id_empresa');
                    if($query->with('ciudad')){
                        $query->with(['ciudad' => function($query){
                            $query->select('idciudad','nombre_ciu');
                        }]);
                    }
                }]);
            //}
        }])
        //->orderByDesc('id_oferta');
        ->get();
        if ($totalOfertas->count() > 0){
            $cant_resultados = $totalOfertas->count();
            $arrayOfertas = $totalOfertas->toArray();         
            $arrResultado = [];
            foreach ( $arrayOfertas as $key => $datosOferta) {
                $row['evento'] = $datosOferta["evento"];
                $row['tipo'] = $datosOferta["tipo"];
                $row['objeto'] = $datosOferta["objeto"];
                $row['descripcion'] = $datosOferta["descripcion"];
                $row['comprador'] = ucwords($datosOferta["comprador"]);
                $row['estado_oferta'] = $datosOferta["estado_oferta"];
                $row['fecha_inicio'] = $datosOferta["fecha_inicio"];
                $row['hora_inicio'] = $datosOferta["hora_inicio"];
                $row['fecha_cierre'] = $datosOferta["fecha_cierre"];
                $row['hora_cierre'] = $datosOferta["hora_cierre"];
                $row['proveedor'] = $datosOferta["info_usuario_proveedor"]["empresa"]["nitempxx"];
                $row['razon_social'] = $datosOferta["info_usuario_proveedor"]["empresa"]["razonxxx"];                
                if ($datosOferta["info_usuario_proveedor"]["empresa"]["ciudad"]["nombre_ciu"]) {
                    $row['ciudad'] = $datosOferta["info_usuario_proveedor"]["empresa"]["ciudad"]["nombre_ciu"];    
                }else{
                    $row['ciudad'] = "N/A";
                }
                $row['usuario'] = $datosOferta["info_usuario_proveedor"]["usrnomxx"];
                $row['email'] = $datosOferta["info_usuario_proveedor"]["usrmailx"];
                $row['fecha_invitacion'] = $datosOferta["invitacion"];
                $row['estado_participacion'] = $this->castEstadoParticipacion($datosOferta['estado_participacion']);
                $row['codigo_actividad'] = $datosOferta["codigo_actividad"];
                $row['actividad'] = $datosOferta["info_activities"]["actividad"];
                $row['modalidad_seleccion'] = $datosOferta["modalidad_seleccion"];
                $row['invitacion'] = $this->intelcost->castiarFechayHoraIntelcost($row["invitacion"]);
                $row['bollAdjudicacion'] = "NO";
                array_push($arrResultado, $row);
            }
            $respuesta = array("data" => $arrResultado, "cantidad_resultados" => $cant_resultados);
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg =json_encode($respuesta);
        }
        else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="No se encontró resultados sobre los proveedores invitados a eventos.";
        }
        /*$sqlTotal = "SELECT count(offers.id_oferta) as total FROM offers WHERE offers.id_cliente = '".$_SESSION["empresaid"]."' ";
        //$sqlTotal = "SELECT count(offers.id_oferta) as total FROM offers WHERE offers.id_cliente = '1' ";

        $SqlQuery = "SELECT 
        offers.*, 
        activities.producdesc as actividad";
        if(self::$ambiente == "LOCAL"){
            //$SqlQuery .= ", moneda_adjudicacion, valor_adjudicacion ";
        }
        $SqlQuery .= " FROM activities, offers ";
        if(self::$ambiente == "LOCAL"){
            //$SqlQuery .= " LEFT JOIN (SELECT moneda AS moneda_adjudicacion, FORMAT(valor, 2) as valor_adjudicacion, id_oferta, id_usuario as id_participante FROM oferta_adjudicaciones ) A ON offers.id_oferta = A.id_oferta AND offers.id_us = A.id_participante ";
        }
        $SqlQuery .= " WHERE activities.codigo_actividad = offers.codigo_actividad AND offers.id_cliente = '".$_SESSION["empresaid"]."'";
        //$SqlQuery .= " WHERE activities.codigo_actividad = offers.codigo_actividad AND offers.id_cliente = '16' ";
        $parametros = array();
        $tipoParametros = "";

        $query = "";
        if(isset($filtros["proveedor"]) && !empty($filtros["proveedor"])){
            $query .= " AND offers.proveedor LIKE ? ";
            $tipoParametros .= "s";
            array_push($parametros, "%".$filtros["proveedor"]."%");
        }
        if(isset($filtros["usuario"]) && !empty($filtros["usuario"])){
            $query .= " AND 
            (
                offers.usuario LIKE ? OR 
                offers.usuario.email LIKE ?
            )";
            $tipoParametros .= "ss";
            array_push($parametros, "%".$filtros["usuario"]."%", "%".$filtros["usuario"]."%");
        }
        if(isset($filtros["id_evento"]) && !empty($filtros["id_evento"])){
            $query .= " AND offers.evento = ? ";
            if($_SESSION['empresaid'] == 14 || $_SESSION['empresaid'] == 26 || $_SESSION['empresaid'] == 27 || $_SESSION['empresaid'] == 20 || $_SESSION["empresaid"] == 25){
                $tipoParametros .= "s";
                array_push($parametros, $filtros["id_evento"]);
            }else{
                $tipoParametros .= "i";
                array_push($parametros, (int) $filtros["id_evento"]);
            }
        }
        if(isset($filtros["tipo_evento"]) && !empty($filtros["tipo_evento"])){
            $query .= " AND offers.tipo = ? ";
            $tipoParametros .= "s";
            array_push($parametros, $filtros["tipo_evento"]);
        }
        if(isset($filtros["estado_participacion"]) && !empty($filtros["estado_participacion"])){
            $query .= " AND offers.estado_participacion = ? ";
            $tipoParametros .= "s";
            array_push($parametros, $filtros["estado_participacion"]);
        }
        if(isset($filtros["comprador"]) && !empty($filtros["comprador"])){
            $query .= " AND offers.usuario_creacion = ? ";
            $tipoParametros .= "i";
            array_push($parametros, (int) $filtros["comprador"]);
        }
        if(isset($filtros["id_evento"]) && !empty($filtros["id_evento"])){
            $query .= " AND offers.evento = ? ";
            $tipoParametros .= "i";
            array_push($parametros, (int) $filtros["id_evento"]);
        }
        if(isset($filtros["desde"]) && !empty($filtros["desde"])){
            $query .= " AND CAST(offers.invitacion AS DATE) >= ? ";
            $tipoParametros .= "s";
            array_push($parametros, $filtros["desde"]);
        }
        if(isset($filtros["hasta"]) && !empty($filtros["hasta"])){
            $query .= " AND CAST(offers.invitacion AS DATE) <= ? ";
            $tipoParametros .= "s";
            array_push($parametros, $filtros["hasta"]);
        }
        
        $tieneParametros = false;
        if(count($parametros) > 0){
            $tieneParametros = true;
        }
        $sqlTotal .= $query;
        
        $cscTotal = $this->intelcost->prepareStatementQuery('cliente', $sqlTotal, 'select', $tieneParametros, $tipoParametros, $parametros, "Obtener total proveedores invitados eventos.");
        
        if($cscTotal->bool){
            $resTotal = $cscTotal->msg->fetch_assoc();
            $cant_resultados = ceil($resTotal['total'] / 10);
            $SqlQuery .= $query." ORDER BY offers.id_oferta desc ";
            if(isset($pagina) && !empty($pagina) && $pagina > 0){
                $limitPag = $this->intelcost->paginacion_limit_inicio_fin($pagina, 10);
                $SqlQuery .= " LIMIT ".$limitPag['inicio'].",".$limitPag['fin'];
            }
            $CscQuery = $this->intelcost->prepareStatementQuery('cliente', $SqlQuery, 'select', $tieneParametros, $tipoParametros, $parametros, "Obtener proveedores invitados eventos.");
            if($CscQuery->bool){
                if($CscQuery->msg->num_rows > 0){
                    $arrEventos = array();
                    while ($row = $CscQuery->msg->fetch_assoc()) {
                        array_push($arrEventos, $row);
                    }
                    $arrResultado = array();
                    foreach($arrEventos as $row){
                        $row['fecha_invitacion'] = $row['invitacion'];
                        $row['invitacion'] = $this->intelcost->castiarFechayHoraIntelcost($row['invitacion']);
                        $row['estado_participacion'] = $this->castEstadoParticipacion($row['estado_participacion']);
                        $row['comprador'] = ucwords($row['comprador']);
                        $objUsuario = $this->modelo_usuario->obtenerInformacionUsuarioProv($row['id_us']);
                        $row['razon_social'] = "";
                        $row['ciudad'] = "";
                        if($objUsuario->bool){
                            $dataUsuario = json_decode($objUsuario->msg);
                            $row['razon_social'] = $dataUsuario->razon_social;
                            $row['ciudad'] = ($dataUsuario->ciudad != null) ? ucwords($dataUsuario->ciudad) : "No registra";
                        }
                        $row['id_us'] = "";

                        $row['bollAdjudicacion'] = "NO";
                        /*if(isset($row['valor_adjudicacion']) && $row['valor_adjudicacion'] > 0 ){
                            $row['bollAdjudicacion'] = "SI";
                        }*/
                        /*array_push($arrResultado, $row);
                    }
                    $respuesta = array("data" => $arrResultado, "cantidad_resultados" => $cant_resultados);
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg =json_encode($respuesta);
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg ="No se encontraron resultados.";
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Se presentó un error al consultar proveedores invitados eventos.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Se presentó un error al consultar total proveedores invitados eventos.";
        }*/
        return $this->intelcost->response;
    }

    public function validarPublicacionOferta($objOferta){
        $dbConection = $this->intelcost->db->createConection("cliente");

        if(isset($objOferta) && !empty($objOferta)){
            //Como no tiene aprobaciones procede a validar si la persona que esta realizando la acción puede aprobar
            //Valida el estado de la oferta
            if($objOferta->estado == "EN APROBACION"){
                //Se obtiene los respectivos aprobadores
                $SqlQueryApro = "SELECT id_usuario FROM flujos_aprobacion_perfil_vs_usuario WHERE id_objeto = '".$objOferta->id."' AND estado='aprobador_publicacion'";
                $CscQueryApro = $dbConection->query($SqlQueryApro);
                if($CscQueryApro){
                    $banderaAprobación = false;
                    while($row = $CscQueryApro->fetch_assoc()){
                        if($row["id_usuario"] == $_SESSION["idusuario"]){
                            $banderaAprobación = true;
                        }
                    }
                    if($banderaAprobación){
                        $SqlQuery = "UPDATE ofertas SET estado = 'APROBADA', `usuario_actualizacion`= $_SESSION[idusuario] WHERE id='".$objOferta->id."' AND id_cliente ='".$objOferta->id_cliente."' ";
                        $CscQuery = $dbConection->query($SqlQuery);
                        if($CscQuery){
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg ="APROBADO";
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg =   "Consulta erronea - Error al intentar enviar el RFQ para su aprobación.";
                        }
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg =   "Se debe tener el perfil de Ordenador de Gastos para poder publicar el RFQ.";
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg =   "Oferta no encontrada para aprobacion";
                }
                        
            }else if($objOferta->estado == "ACTIVO"){
                //Se procede a cambiar el estado de la oferta a EN APROBACION para que ingrese el aprobador
                $SqlQuery = "UPDATE ofertas SET estado = 'EN APROBACION', `usuario_actualizacion`= $_SESSION[idusuario] WHERE id='".$objOferta->id."' AND id_cliente ='".$objOferta->id_cliente."' ";
                $CscQuery = $dbConection->query($SqlQuery);
                if($CscQuery){
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg ="ENVIO_APROBACION";
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg =   "Consulta erronea - Error al intentar enviar el RFQ para su aprobación.";
                }
                
            }else if($objOferta->estado == "APROBADA"){
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="El RFQ esta en espera de ser publicada por la plataforma.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg ="Estado del RFQ desconocido.Consulte con el administrador de la herramienta.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg ="Oferta no encontrada para aprobacion";
        }
        return $this->intelcost->response;
    }

    private function asociarFlujosRfqMetro($oferta,$id_oferta,$requiere_flujo){
        $dbConection = $this->intelcost->db->createConection("cliente");
        $arr_usu_perfil = [];


            //Flujo de publicacion - 10
        // ordenador del gasto

        if($oferta->id_area_oferta){
            $area_usuaria_mod = $this->modelo_usuario->obtenerAreaUsuaria($oferta->id_area_oferta);
            if($area_usuaria_mod->bool){
                $area_usuaria = json_decode($area_usuaria_mod->msg);
                //ORDENADOR DEL GASTO
                $obj_perfil             = new stdClass();
                $obj_perfil->id_perfil  = 12;
                $obj_perfil->id_usuario = $area_usuaria->ordenador_gasto;
                $obj_perfil->estado = 'aprobador_publicacion';
                $obj_perfil->id_paso = 37;
            
            }


        }        

            
           /* $CscQuery = $dbConection->query($SqlQuery);
            $SqlQuery = "INSERT INTO flujos_aprobacion_perfil_vs_usuario (id_usuario,id_perfil,id_objeto,id_cliente,id_modulo,aprobacion_id,estado,fecha_actualizacion) VALUES ";
            $SqlQuery .= "(1396,12,'".$id_oferta."',10,14,0,'aprobador_publicacion','".date("Y-m-d H:i:s")."'); ";
            $CscQuery = $dbConection->query($SqlQuery);*/
            array_push($arr_usu_perfil, $obj_perfil);

        if($requiere_flujo == 1){
        // Comprador
            $obj_perfil             = new stdClass();
            $perfil_consul_mod = $this->modelo_usuario->consultarIdPerfil($_SESSION["idusuario"]);
            if($perfil_consul_mod->bool){
                $obj_perfil->id_perfil  = $perfil_consul_mod->msg;  
            }else{
                $obj_perfil->id_perfil  = 5;    
            }
            $obj_perfil->id_usuario = $_SESSION["idusuario"];
            $obj_perfil->id_paso = 38;
            array_push($arr_usu_perfil, $obj_perfil);

        // jefe contratacion
            $obj_perfil             = new stdClass();
            $obj_perfil->id_perfil  = 11;
            //$obj_perfil->id_usuario = 1398; Se deja como 0 para que cualquiera con el id_perfil 11 , pueda realizarlo.
            $obj_perfil->id_usuario = 0;
            $obj_perfil->id_paso = 39;
            array_push($arr_usu_perfil, $obj_perfil);

        // jefe comprador
            $obj_perfil = new stdClass(); 
            $obj_perfil->id_perfil = 7; 
            $obj_perfil->id_paso = 40;
            $respuesta_area=$this->modelo_usuario->consultarIdArea($_SESSION["idusuario"]);
            if($respuesta_area->bool){
                $respuesta_jefe=$this->modelo_usuario->consultarJefeArea($respuesta_area->msg);
                if($respuesta_jefe->bool && $respuesta_jefe->msg != null  && $respuesta_jefe->msg != ""){
                    $obj_perfil->id_usuario =$respuesta_jefe->msg;
                    
                    $jefe_perfil_consul_mod = $this->modelo_usuario->consultarIdPerfil($obj_perfil->id_usuario);
                    if($jefe_perfil_consul_mod->bool){
                        $obj_perfil->id_perfil  = $jefe_perfil_consul_mod->msg; 
                    }

                }else{
                    $obj_perfil->id_usuario =0;
                }            
            }else{
                $obj_perfil->id_usuario =0;
            }
            array_push($arr_usu_perfil, $obj_perfil);
        }
            // Carga de los aprobadores, sin validacion de exitencia
            //¿eliminar los que estan notificado? actualizar los extitenes?
        $respose_flujos = $this->modelo_flujos->cargarPerfilesAprobadoresRequeridos("14", $id_oferta, $arr_usu_perfil);
        if (!$respose_flujos->bool) {
            var_dump($respose_flujos->msg);
        }
    }

    private function asociarFlujosRfqVanti($oferta,$id_oferta,$requiere_flujo,$tipo_oferta, $eliminar = false){
        $dbConection = $this->intelcost->db->createConection("cliente");
        // $arr_usu_perfil = [];
        if($requiere_flujo == 1){
      
            /*$obj_perfil             = new stdClass();
            $perfil_consul_mod = $this->modelo_usuario->consultarIdPerfil($_SESSION["idusuario"]);
            if($perfil_consul_mod->bool){
                $obj_perfil->id_perfil  = $perfil_consul_mod->msg;  
            }else{
                $obj_perfil->id_perfil  = 1;    
            }
            $obj_perfil->id_usuario = $_SESSION["idusuario"];*/
            $obj_perfil             = new stdClass();
            $obj_perfil->id_perfil  = 100; //Perfil Aprobador
            $obj_perfil->id_usuario = 0;
        }
        if ($tipo_oferta != 'estudio') {
            $obj_perfil->id_paso = 67;
            if ($eliminar) {
                $this->modelo_flujos->cambiarEstadoAprobacionesEliminadas($id_oferta,"34", $obj_perfil->id_paso);    
            }else{
                $this->modelo_flujos->cambiarEstadoAprobacionesEliminadas($id_oferta,"34", $obj_perfil->id_paso);
                $respose_flujos = $this->modelo_flujos->cargarPerfilesAprobadoresRequeridos("34", $id_oferta, array($obj_perfil));
                if (!$respose_flujos->bool) {
                    var_dump($respose_flujos->msg);
                }
            }
        }else{
            $obj_perfil->id_paso = 68;
            if ($eliminar) {
                $this->modelo_flujos->cambiarEstadoAprobacionesEliminadas($id_oferta,"33", $obj_perfil->id_paso);
            }else{
                $this->modelo_flujos->cambiarEstadoAprobacionesEliminadas($id_oferta,"33", $obj_perfil->id_paso);
                $respose_flujos = $this->modelo_flujos->cargarPerfilesAprobadoresRequeridos("33", $id_oferta, array($obj_perfil));
                if (!$respose_flujos->bool) {
                    var_dump($respose_flujos->msg);
                }
            }
        }
        
    }

    public function cargueMasivoItemsLotes($archivo){
        if(strpos($archivo["ruta"], 's3.amazonaws.com') === false){
            $archivo = $archivo["ruta"].$archivo["nombre_archivo"];
        }else{
            $archivo_temporal = file_get_contents($archivo["ruta"].$archivo["nombre_archivo"]);
            $archivo = tempnam(sys_get_temp_dir(), "tmp_file_xlsx_").'.xlsx';
            file_put_contents($archivo, $archivo_temporal);
        }

        if (file_exists($archivo)) {

            require_once "../ic_libs/PHPExcel/Classes/PHPExcel.php";
            $excelReader = PHPExcel_IOFactory::createReaderForFile($archivo);
            $excelObj    = $excelReader->load($archivo);
            $sheetCount = $excelObj->getSheetCount();
            
            $this->intelcost->cargarArchivosS3($archivo, 'cliente');

            $this->intelcost->cargarArchivosServerFtp($archivo, 'cliente');
            $data               = [];                   
            $ingresados         = 0;
            $errores_data       = 0;
            $no_encontrados_sap = 0;
            $codigosNoEncontrados = "";

            for($current_sheeet = 0; $current_sheeet < $sheetCount; ++$current_sheeet) {
                if($_SESSION['empresaid'] == 9 && $current_sheeet > 0){
                    continue;
                }
                $worksheet   = $excelObj->getSheet($current_sheeet); //
                $sheetTitle = $worksheet->getTitle();
                $lastRow     = $worksheet->getHighestRow();
                
                for ($row = 2; $row <= $lastRow; $row++) {
                    
                    if($_SESSION['empresaid'] == 10){
                        $codigo_sap = $worksheet->getCell('A' . $row)->getValue();
                        $filtro = array('numero' => $codigo_sap,'familia'=>'','tipo'=>'','descripcion'=>'','borrado'=>'','bloqueados'=>''); 
                        $modelo_sap_metro      = new modelo_sap_metro();
                        $modelo_sap_metro_res = $modelo_sap_metro->obtenerListadoMaterialesRequisicionesMateriales($filtro);
                        if ($modelo_sap_metro_res->bool) {
                            $datos  = json_decode($modelo_sap_metro_res->msg, true);
                            $data[] = [
                                'lote'       => ($current_sheeet+1),
                                'lote_nombre'       => $sheetTitle,
                                'material'       => $datos['Material'],
                                'cantidad'       => $worksheet->getCell('B' . $row)->getValue(),
                                'descripcion'    => $datos['Descripcion'],
                                'unidad_medida'  => $datos['Unidad'],
                            ];
                            $ingresados++;
                        } else {
                            $no_encontrados_sap++;
                            if($no_encontrados_sap>1){$codigosNoEncontrados.=",";}
                            $codigosNoEncontrados .= $codigo_sap;
                        }
                    }elseif($_SESSION['empresaid'] == 9){
                        if(!empty($worksheet->getCell('B' . $row)->getValue())){
                            $data[] = [
                                'lote'           => ($current_sheeet+1),
                                'lote_nombre'    => $sheetTitle,
                                'posicion'       => intval($worksheet->getCell('A' . $row)->getValue()),
                                'cod_articulo'   => intval($worksheet->getCell('C' . $row)->getValue()),
                                'cantidad'       => floatval($worksheet->getCell('E' . $row)->getValue()),
                                'descripcion'    => $worksheet->getCell('B' . $row)->getValue(),
                                'unidad_medida'  => $worksheet->getCell('D' . $row)->getValue(),
                                'cod_unidad_medida' => UnidadMedida::where('id_empresa', 6)->where('um', $worksheet->getCell('D' . $row)->getValue())->first()->id_medida,
                                'obligatorio'    => !empty($worksheet->getCell('F' . $row)->getValue()) ? '1' : '0',
                            ];
                            $ingresados++;
                        }
                    }elseif($_SESSION['empresaid'] == 20){
                        if(!empty($worksheet->getCell('A' . $row)->getValue())){
                            $data[] = [
                                'lote'              => ($current_sheeet+1),
                                'DOCUMENTO'         => ($current_sheeet+1),
                                'POSICION'          => $row,
                                'DESCRIPCION'       => $sheetTitle,
                                'lote_nombre'       => $sheetTitle,
                                'CANTIDADPENDIENTE' => floatval($worksheet->getCell('C' . $row)->getValue()),
                                'cantidad'          => floatval($worksheet->getCell('C' . $row)->getValue()),
                                'descripcion_item'  => $worksheet->getCell('A' . $row)->getValue(),
                                'unidad_medida'     => $worksheet->getCell('B' . $row)->getValue(),
                                'cod_unidad_medida' => UnidadMedida::where('id_empresa', 6)->where('um', $worksheet->getCell('B' . $row)->getValue())->first()->id_medida,
                                'UNIDAD'            => $worksheet->getCell('B' . $row)->getValue(),
                                'ALMACEN'           => '',
                                'CENTRO'            => '',
                                'GRUPO'             => '',
                                'GPOCOMPRA'         => '',
                                'CANTIDADORIGINAL'  => floatval($worksheet->getCell('C' . $row)->getValue()),
                                'CANTIDADPENDIENTE' => floatval($worksheet->getCell('C' . $row)->getValue()),
                                'MONEDA'            => 'COP',
                                'FECHAENTREGA'      => '',
                                'FECHALIBERACION'   => '',
                                'PRECIO'            => '',
                            ];
                            $ingresados++;
                        }
                    }else{

                    }

                }

                $respuesta = array(
                    "ingresados"     => $ingresados,
                    "errores"        => "0",
                    "no_encontrados" => $no_encontrados_sap,
                    "data_vacios"    => "0",
                    "errores_data"   => "0",
                    "data"           => $data,
                    "codigosNoEncontrados"           => $codigosNoEncontrados,
                    "ruta"           => $archivo,
                );
                /*$obj_notificacion       = new stdClass();
                $obj_notificacion->referencia  = "Resultado cargue archivo lotes e items catalogados";
                $obj_notificacion->modulo  = "Solicitud";
                $obj_notificacion->numero  = "N/A";
                $obj_notificacion->contenido  = "El cargue de tu archivo de lotes e items catalogados arrojó el siguiente resultado:<br>Código cargados exitósamente: ".$ingresados."<br>Códigos no cargados: ".$no_encontrados_sap.($no_encontrados_sap>0?", descriptas a continuación":"")."<br><br>".$codigosNoEncontrados;

                $this->intelcost->enviar_email_notificaciones_metro($obj_notificacion);*/

                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg  = $respuesta;
            }

        } else {
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "No se encontró archivo temporal.";
        }
        return $this->intelcost->response;
    }

    public function obtenerTokenJDE($conexionJDE, $user, $password){
        $this->obtenerTokenServicioJDE($conexionJDE, ['user' => $user, 'password' => $password]);
    }


    public function obtenerInformacionOJ($request){
        $request = (object) $request->input('filtros');
        if (!isset($request->numero_documento_servicio) || $request->numero_documento_servicio == '' || !isset($request->compania) || $request->compania == '') {
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "Parametros incorrectos";
            return $this->intelcost->response;
        }
        
        $resultadoValidacion = OfertaLote::where('cod_cliente',$_SESSION['empresaid'])
        ->where('cod_compania',$request->compania)
        ->where('nombre_lote',$request->numero_documento_servicio)
        ->where('estado','activo')
        ->first();
        
        if(empty($resultadoValidacion)){
            $ruta_ambiente = array(
                ['ruta'=>'https://app.confa.co:8687/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'local'],
                ['ruta'=>'https://app.confa.co:8687/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'calidad']
            );
    
            $JDEdwards = $this->iniciarConexionJDE(new ConnectionSAP, $ruta_ambiente);
            $this->obtenerTokenJDE($JDEdwards, 'YmQ2YmU2YzkxYzRmNmM0ZUFEX0FQUCoyMDIwJA==', 'SmRlX2ludGVsQ29uZmE3YjdhNTNlMjM5NDAwYTEz');
            $consultarOJ = [
                'numOrd' => $request->numero_documento_servicio,
                'compania' => $request->compania,
            ];
            $respuestaServicio = $this->consultarOJJDE($JDEdwards, $_SESSION['token-jde'], $consultarOJ, 'http://ws.interfacejde.confa.co/');
        }else{
            $object_return = new \stdClass();
            $object_return->bool = false;
            $object_return->msg = "La OJ se encuentra asociada en otro evento.";
            $respuestaServicio = $object_return;
        }

        return $respuestaServicio;
    }
    public function crearProveedorJdeConfa($request){
        $bandera_crear_proveedorJDE = false;
        $sqlProveedor = '';
        $tipoParametros = '';
        if (isset($request['cod_empresa']) && $request['cod_empresa'] != '') {
            $sqlProveedor = 'SELECT * FROM proveedores_confa WHERE cod_empresa = ? AND estado = "activo" ';
            $tipoParametros = 'i';
            $parametros = array($request['cod_empresa']);
        }else if (isset($request['cod_usuario']) && $request['cod_usuario'] != '') {
            $sqlProveedor = 'SELECT * FROM proveedores_confa PC LEFT JOIN sys00001 US ON PC.cod_empresa = US.cod_empresa WHERE US.usridxxx = ? AND PC.estado = "activo" ' ;
            $tipoParametros = 'i';
            $parametros = array($request['cod_usuario']);
        }else if (isset($request['email']) && $request['email'] != ''){
            $sqlProveedor = 'SELECT * FROM proveedores_confa LEFT JOIN sys00001 US ON PC.cod_empresa = US.cod_empresa WHERE US.usrmailx = ? AND PC.estado = "activo" ';
            $tipoParametros = 's';
            $parametros = array($request['email']);
        }

        if($sqlProveedor != ''){
            $cscProveedor = $this->intelcost->prepareStatementQuery('intelcost', $sqlProveedor, 'select', true, $tipoParametros, $parametros, "Consulta proveedore JDE Confa.");
            if ($cscProveedor->bool) {
                if ($cscProveedor->msg->num_rows==0) {
                    $bandera_crear_proveedorJDE = true;
                }else{
                    $data = $cscProveedor->msg->fetch_assoc();
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg  = "El proveedor ya se encuentra registrado en JDE Confa";
                    $this->intelcost->response->id_proveedor_jde  = $data;
                    return $this->intelcost->response;
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg  = "Fallo consulta de proveedor JDE";
                return $this->intelcost->response;
            }
            
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "Parametros vacios para crear proveedor JDE";
            return $this->intelcost->response;
        }

        if(!isset($request['identificacion'])){
            $sqlProveedorInformacion = 'SELECT nitempxx,emailrepleg,direccxx,razonxxx,telefono,replegxx,CIU.nombre_ciu,DEP.nombre as depto,IT.act_principal FROM _0002103 EMP LEFT JOIN mstciudades CIU ON EMP.ciudidxx = CIU.idciudad LEFT JOIN mst_departamento AS DEP ON DEP.codigo = CIU.id_departamento AND DEP.pais = CIU.pais LEFT JOIN informacion_tributaria IT ON IT.cod_empresa = EMP.id_empresa WHERE EMP.id_empresa  = ? ';
            $tipoParametros = 'i';
            $parametros = array($request['cod_empresa']);
            $cscProveedorInfo = $this->intelcost->prepareStatementQuery('intelcost', $sqlProveedorInformacion, 'select', true, $tipoParametros, $parametros, "informacion proveedor.");

            if ($cscProveedorInfo->bool) {
                $data = $cscProveedorInfo->msg->fetch_assoc();
                $request['ciudad'] = $data['nombre_ciu'];
                $request['clasificacion_industria'] = $data['act_principal'];
                $request['email'] = $data['emailrepleg'];
                $request['departamento'] =  $data['depto'];
                $request['direccion'] =  $data['direccxx'];
                $request['identificacion'] = $data['nitempxx'];
                $request['nombre'] = $data['razonxxx'];
                $request['representante_legal'] =  $data['replegxx'];
                if(strpos($data['telefono'], "/_*_/")){
                    $data_telefono = explode("/_*_/", $data['telefono']);
                    $request['prefijo_telefono'] = $data_telefono[0];
                    $request['telefono'] = str_replace("/_*_/", " - ", $data['telefono']);
                }else{
                    $request['telefono'] = $data['telefono'];
                    $request['prefijo_telefono'] = substr($request['telefono'],0,3); 
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg  = "Ocurrió un error al consultar la informacion del proveedor.";
                return $this->intelcost->response;
            }
        }

        if ($bandera_crear_proveedorJDE) {
            $ruta_ambiente = array(
                ['ruta'=>'https://app.confa.co:8687/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'local'],
                ['ruta'=>'https://app.confa.co:8687/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'calidad'],
                ['ruta'=>'https://app.confa.co:8886/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'productivo']
            );
            $JDEdwards = $this->iniciarConexionJDE(new ConnectionSAP, $ruta_ambiente);
            $this->obtenerTokenJDE($JDEdwards, 'YmQ2YmU2YzkxYzRmNmM0ZUFEX0FQUCoyMDIwJA==', 'SmRlX2ludGVsQ29uZmE3YjdhNTNlMjM5NDAwYTEz');
            $parametros = [];
            $tercero = [
                'ciudad' => substr($request['ciudad'],0,20),
                'clasificacion_industrial' => $request['clasificacion_industria'],
                'correo_electronico' => $request['email'],
                'departamento' => substr($request['departamento'],0,20),
                'direccion' => substr($request['direccion'],0,40),
                'identificacion' => $request['identificacion'],
                'nombre' => substr($request['nombre'],0,40),
                'prefijo_telefono' => $request['prefijo_telefono'],
                'representante_legal' => substr($request['representante_legal'],0,40),
                'telefono' => substr($request['telefono'],strpos($request['telefono'], '-')+2)
                ];
            $tipo_tercero = [
                    'tipoTercero' =>'P'
                    ];
            $parametros['tercero'] = $tercero;  
            $parametros['tipoTercero'] = 'P';
            $resp = $this->crearProveedorJDE($JDEdwards, $_SESSION['token-jde'], $parametros, 'http://ws.interfacejde.confa.co/');
            // var_dump($parametros);
           if ($resp->bool || (!$resp->bool && strrpos($resp->msg, 'EXISTE_PROVEEDOR_HABILITADO'))){
                if(!$resp->bool){
                    $resp->msg = explode(':', $resp->msg)[1];
                }
                $sqlProveedor = 'INSERT INTO proveedores_confa ( cod_empresa, cod_jde_confa, usuario_registro, fecha_registro, estado ) VALUES ( ?, ?, '.$_SESSION["idusuario"].', "'.date("Y-m-d H:i:s").'", "activo" )';
                    $tipoParametros = 'ii';
                    $parametros = array($request['cod_empresa'],(int) $resp->msg);
                    $cscProveedor = $this->intelcost->prepareStatementQuery('intelcost', $sqlProveedor, 'insert', true, $tipoParametros, $parametros, "Insertar proveedor JDE Confa.");
                    if ( $cscProveedor->bool ) {
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg  =  $resp->msg;
                    }else{

                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg  =  $cscProveedor->msg;

                    }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg  =  $resp->msg;
            }
        }else{

            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "No se creo el proveedor JDE Confa.";
        }
        return $this->intelcost->response;   
    }

    public function generarCuadroComparativoLotes($idOferta, $seqId, $moneda){
        if(isset($idOferta) && !empty($idOferta) && isset($seqId) && !empty($seqId) && isset($moneda) && !empty($moneda)){

            $db_cliente = env('DB_NAME_DATABASE_CLIENT');
            $db_proveedores = env('DB_NAME_DATABASE_PROVIDER');

            $sqlStm = "SELECT
                lote.cod_oferta,
                lote.id_lote,
                lote.nombre_lote,
                lote.cod_compania,
                lote.cod_sobre,
                capitulos.nombre nombreSobre,
                items.id_item,
                adicionalesItems.numero_linea,
                adicionalesItems.obligatorio itemObligatorio,
                adicionalesItems.impuesto impuesto_adicional,
                items.descripcion,
                items.cantidad,
                maestraMed.medida unidadMedida,
                participantes.id_proveedor nitEmpresa,
                empresas.razonxxx nombreEmpresa,
                participantes.id_usuario idUsuario,
                participantes.nombre_contacto proveedor,
                IF(ofertaEnviada.valor IS NOT NULL,ofertaEnviada.valor,0) as valorUnitario,
                IF(ofertaEnviada.valor IS NOT NULL,ofertaEnviada.valor,0) * items.cantidad total
                FROM
                `$db_cliente`.`oferta_lotes` lote
                INNER JOIN `$db_cliente`.`oferta_lotes_items` items ON lote.id_lote = items.cod_lote
                INNER JOIN `$db_cliente`.`mst_unidad_medidas` maestraMed ON items.cod_unidad_medida = maestraMed.id_medida
                INNER JOIN `$db_cliente`.`oferta_participantes` participantes ON participantes.id_oferta = lote.cod_oferta
                left JOIN `$db_cliente`.`oferta_lotes_items_proveedores` ofertaEnviada ON ofertaEnviada.cod_item = items.id_item
                AND ( ofertaEnviada.usuario_creacion = participantes.id_usuario OR ofertaEnviada.usuario_actualizacion = participantes.id_usuario )
                LEFT JOIN `$db_cliente`.`oferta_lotes_items_datos_adicionales` adicionalesItems ON adicionalesItems.cod_item = items.id_item
                INNER JOIN `$db_proveedores`.`_0002103` empresas ON empresas.nitempxx = participantes.id_proveedor
                INNER JOIN `$db_cliente`.`capitulos` capitulos ON lote.cod_sobre = capitulos.id
                WHERE
                lote.cod_oferta = ?
                AND lote.estado = 1
                AND participantes.estado_participacion = 'ofe_enviada'
                ORDER BY
                id_lote,
                participantes.id_usuario,
                adicionalesItems.numero_linea";

            $response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'select', true, 'i', array($idOferta), "Cargar datos para cuadro comparativo de ofertas.");

            if($response->bool){
                if($response->msg->num_rows > 0){

                    
                    $response = $response->msg->fetch_all(MYSQLI_ASSOC);
                    $newResponse = collect();
                    foreach ($response as $res_data_iterador => $item) {
                        // Validación ocensa con relación al sobre legal y técnico para mostrar la comparación.
                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                            $validacionSobres = $this->validacionAperturaSobreEconomico($item['idUsuario'], $idOferta);
                            if($validacionSobres['conclusion']){
                                $newResponse->push($item);
                            }
                        }else{
                            $newResponse->push($item);
                        }
                    }
                    $sinFinalizarSobres = $newResponse->count();
                    $response = $newResponse->toArray();

                    require '../ic_libs/PHPExcel/Classes/PHPExcel.php';
                    $excel = new PHPExcel();

                    if($excel){

                        $excel->getProperties()
                        ->setCreator("Intelcost")
                        ->setLastModifiedBy("Intelcost")
                        ->setTitle("Cuadro comparatipo oferta(".$seqId.")")
                        ->setSubject("Cuadro comparatipo oferta(".$seqId.")")
                        ->setDescription("Cuadro comparatipo oferta(".$seqId.")")
                        ->setKeywords("")
                        ->setCategory("Reporte");

                        $excel->setActiveSheetIndex(0);

                        $inicioPuntero1 = '';
                        $inicioPuntero2 = '';
                        $inicioPuntero3 = '';
                        $inicioPuntero4 = '';
                        $inicioPuntero5 = '';
                        $inicioPuntero6 = '';
                        if($_SESSION['empresaid'] == 25){
                            $inicioPuntero1 = 'A';
                            $inicioPuntero2 = '3';
                            $inicioPuntero3 = 'F';
                            $inicioPuntero4 = '1';
                            $inicioPuntero5 = '4';
                            $inicioPuntero6 = 'E';
                        }else{
                            $inicioPuntero1 = 'A';
                            $inicioPuntero2 = '3';
                            $inicioPuntero3 = 'E';
                            $inicioPuntero4 = '1';
                            $inicioPuntero5 = '4';
                            $inicioPuntero6 = 'D';
                        }

                        // PUNTEROS

                        $column = $inicioPuntero1;
                        $row = $inicioPuntero2;     //INICIA EN LA FILA 3 DEBIDO A QUE LA FILA 1 ESTA PARA LOS DATOS DEL LOTE Y LA 2 PARA LOS DATOS DE LOS PROVEEDORES

                        $providerColumn = $inicioPuntero3;
                        $providerColumnMerging = $inicioPuntero3;
                        $providerRow = $inicioPuntero4;

                        $providerRowItem = $inicioPuntero5;
                        $providerColumnItem = array('prev' => $inicioPuntero3, 'current' => $inicioPuntero3);

                        // FIN PUNTEROS

                        $headerReady = false;
                        $headerLoteReady = false;
                        $firstLote = true;
                        $counting = 1;
                        $firstProvider = $response[0]['idUsuario'];
                        $itemId = array();
                        $loteId = array($response[0]['id_lote']);
                        $headerCurrentProviderReady  = false;
                        $minorPricePerItem = array();
                        $allPricesSet = array();

                        // RECORRIDO DE TODOS LOS ITEMS Y ESTRUCTURACION DE CUADRO (EXCEL)
                        foreach ($response as $item => $dataItem){

                            if(!in_array($dataItem['id_item'], $itemId)){

                                if(!isset($allPricesSet[$dataItem['idUsuario']])){
                                    $allPricesSet[$dataItem['idUsuario']] = array('bool' =>true, 'total' => 0);
                                }

                                $itemId[$counting-1] = $dataItem['id_item'];

                                if(!$headerCurrentProviderReady){

                                    // $excel->getActiveSheet()->mergeCells('A'.$rowHeaderLote.':'.$dataLoteMerge.$rowHeaderLote);

                                    if(!$headerLoteReady){                                        
                                        if(is_null($excel->getActiveSheet()->getCell($inicioPuntero1.$providerRow)->getValue())){

                                            $dataBis = array();
                                            $provBis = 0;
                                            foreach ($response as $itemBis => $dataItemBis){
                                                if(!in_array($dataItemBis['idUsuario'], $dataBis)){
                                                    array_push($dataBis, $dataItemBis['idUsuario']);
                                                    $provBis++;
                                                }
                                            }
                                            
                                            $dataLoteMerge = $inicioPuntero6; 
                                            for($i = 1; $i <= $provBis; $i++){
                                                $dataLoteMerge++;
                                                $dataLoteMerge++;
                                            }

                                            $excel->getActiveSheet()->mergeCells($inicioPuntero1.$providerRow.':'.$dataLoteMerge.$providerRow);


                                            if($_SESSION['empresaid'] == 25){
                                                $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'OJ: '.$dataItem['nombre_lote'].' | COMPAÑÍA: '.$dataItem['cod_compania'].' | SOBRE: '.$dataItem['nombreSobre']);
                                            }else{
                                                $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'Nombre del Lote: '.$dataItem['nombre_lote'].' | SOBRE: '.$dataItem['nombreSobre']);
                                            }

                                            $excel->getActiveSheet()->getStyle($inicioPuntero1.$providerRow)->getFont()->setBold(true);
                                            $excel->getActiveSheet()->getStyle($inicioPuntero1.$providerRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                                            $providerRow++;
                                        }
                                        $headerLoteReady = true;
                                        
                                    }

                                    if($_SESSION['empresaid'] == 25){
                                        $providerColumnMerging++;
                                    }

                                    $excel->getActiveSheet()->mergeCells($providerColumn.$providerRow.':'.++$providerColumnMerging.$providerRow);
                                    $excel->getActiveSheet()->setCellValue($providerColumn.$providerRow,$dataItem['nombreEmpresa'].' ('.$dataItem['proveedor'].')');
                                    $excel->getActiveSheet()->getStyle($providerColumn.$providerRow)->getFont()->setBold(true);

                                    // HEADERS DATA PROVEEDORES
                                    $providerRow++;
                                    $providerColumnMerging = $providerColumn;

                                    if($_SESSION['empresaid'] == 25){
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Valor unitario sin impuestos ('.$moneda.') u/n');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                        $providerColumnMerging++;
                                        
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal sin impuesto ('.$moneda.')');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                    }else{
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Valor ('.$moneda.') u/n');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                    }

                                    if($_SESSION['empresaid'] == 25){
                                        $providerColumnMerging++;
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal con impuesto ('.$moneda.')');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                    }else{
                                        $providerColumnMerging++;
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                    }
                                    
                                    

                                    if($firstLote){
                                        $providerRow = '2';
                                    }else{
                                        $providerRow--;
                                    }

                                    $providerColumn++;
                                    $providerColumn++;
                                    if($_SESSION['empresaid'] == 25){
                                        $providerColumn++;
                                    }
                                    $providerColumnMerging = $providerColumn;

                                    $headerCurrentProviderReady = true;
                                }

                                $counting++;

                            }else{
                                if(!isset($allPricesSet[$dataItem['idUsuario']])){
                                    $allPricesSet[$dataItem['idUsuario']] = array('bool' =>true, 'total' => 0);
                                }
                                $headerCurrentProviderReady = false;
                                $itemId = array();
                                $itemId[$counting-1] = $dataItem['id_item'];

                                $counting = 0;

                                $providerColumnItem['current']++;
                                $providerColumnItem['current']++;
                                
                                if($_SESSION['empresaid'] == 25){
                                     $providerColumnItem['current']++;
                                }

                                $providerColumnItem['prev'] = $providerColumnItem['current'];

                                if($firstLote){
                                    $providerRowItem = $inicioPuntero5;
                                }else{
                                    $providerRowItem = $providerRow+2;
                                }
                            }

                            

                            if($_SESSION['empresaid'] == 25){

                                $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem,$dataItem['valorUnitario'] == null || $dataItem['valorUnitario'] <= 0 ? 'NO OFERTADO' : $dataItem['valorUnitario']);
                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getNumberFormat()->setFormatCode("$#,##0.00");
                                $providerColumnItem['current']++;

                                if(!empty($dataItem['impuesto_adicional'])){
                                    $valorIvaCast=$this->castImpuestoConfa($dataItem['impuesto_adicional']);
                                    if($valorIvaCast > 0){
                                        $valorIva=$valorIvaCast."%";
                                        $subtotalConIva = ($dataItem['valorUnitario'] == null || $dataItem['valorUnitario'] <= 0) ? 'NO OFERTADO' : ((($valorIvaCast / 100)*$dataItem['valorUnitario'])+$dataItem['valorUnitario']) * $dataItem['cantidad'] ;
                                        $subtotalSinIva = ($dataItem['valorUnitario'] == null || $dataItem['valorUnitario'] <= 0) ? 'NO OFERTADO' : $dataItem['valorUnitario'] * $dataItem['cantidad'];
                                    }else{
                                        $valorIva="0%";
                                        $subtotalConIva = $dataItem['valorUnitario'] == null || $dataItem['valorUnitario'] <= 0 ? 'NO OFERTADO' : $dataItem['valorUnitario'] * $dataItem['cantidad'];
                                        $subtotalSinIva = $subtotalConIva;
                                    }
                                }else{
                                    $valorIva="0%";
                                    $subtotalConIva = $dataItem['valorUnitario'] == null || $dataItem['valorUnitario'] <= 0 ? 'NO OFERTADO' : $dataItem['valorUnitario'] * $dataItem['cantidad'];
                                    $subtotalSinIva = $subtotalConIva;
                                }

                                $dataItem['total'] = $subtotalConIva;

                                $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem,$subtotalSinIva);

                            }else{
                                $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem,$dataItem['valorUnitario'] == null || $dataItem['valorUnitario'] <= 0 ? 'NO OFERTADO' : $dataItem['valorUnitario']);
                            }

                            // Condicionales para setear los valores menores en verde o rojo si no fue ofertado el item
                            if(isset($minorPricePerItem[$providerRowItem]) && !is_null($dataItem['valorUnitario']) && $dataItem['valorUnitario'] > 0){
                                if($minorPricePerItem[$providerRowItem]['valueCurrentCellMinorPriceInRow'] > $dataItem['total']){

                                $limitCurrentColumnColor = $providerColumnItem['current'];
                                $limitCurrentColumnColor++;

                                // Sentencia para eliminar el color verde de las celdas que ya no tienen el valor menor en la fila
                                $excel->getActiveSheet()
                                 ->getStyle($minorPricePerItem[$providerRowItem]['currentColumnMinorPriceInRow'].$minorPricePerItem[$providerRowItem]['currentRowMinorPriceInRow'].':'.++$minorPricePerItem[$providerRowItem]['currentColumnMinorPriceInRow'].$minorPricePerItem[$providerRowItem]['currentRowMinorPriceInRow'])
                                 ->getFill()
                                 ->setFillType(PHPExcel_Style_Fill::FILL_NONE);

                                $minorPricePerItem[$providerRowItem] = array('currentColumnMinorPriceInRow'         => $providerColumnItem['current'],
                                                                                                                    'limitCurrentColumnColor'                       => $limitCurrentColumnColor,
                                                                                                                    'currentRowMinorPriceInRow'               => $providerRowItem,
                                                                                                                    'valueCurrentCellMinorPriceInRow'     => (float)$dataItem['total']);

                                 $excel->getActiveSheet()
                                 ->getStyle($minorPricePerItem[$providerRowItem]['currentColumnMinorPriceInRow'].$minorPricePerItem[$providerRowItem]['currentRowMinorPriceInRow'].':'.$minorPricePerItem[$providerRowItem]['limitCurrentColumnColor'].$minorPricePerItem[$providerRowItem]['currentRowMinorPriceInRow'])
                                 ->getFill()
                                 ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                                 ->getStartColor()
                                ->setRGB('08E437');

                                }
                            }else if(!is_null($dataItem['valorUnitario']) && $dataItem['valorUnitario'] > 0){

                                $limitCurrentColumnColor = $providerColumnItem['current'];
                                $limitCurrentColumnColor++;

                                $minorPricePerItem[$providerRowItem] = array('currentColumnMinorPriceInRow'         => $providerColumnItem['current'],
                                                                                                                    'limitCurrentColumnColor'                       => $limitCurrentColumnColor,
                                                                                                                    'currentRowMinorPriceInRow'               => $providerRowItem,
                                                                                                                    'valueCurrentCellMinorPriceInRow'     => (float)$dataItem['total']);

                                $excel->getActiveSheet()
                                 ->getStyle($minorPricePerItem[$providerRowItem]['currentColumnMinorPriceInRow'].$minorPricePerItem[$providerRowItem]['currentRowMinorPriceInRow'].':'.$minorPricePerItem[$providerRowItem]['limitCurrentColumnColor'].$minorPricePerItem[$providerRowItem]['currentRowMinorPriceInRow'])
                                 ->getFill()
                                 ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                                 ->getStartColor()
                                ->setRGB('08E437');
                            }else if(is_null($dataItem['valorUnitario'])){

                                $mergingColumnNoPrice = $providerColumnItem['current'];
                                $mergingColumnNoPrice++;

                                $excel->getActiveSheet()->mergeCells($providerColumnItem['current'].$providerRowItem.':'.$mergingColumnNoPrice.$providerRowItem);

                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                                $excel->getActiveSheet()
                                 ->getStyle($providerColumnItem['current'].$providerRowItem)
                                 ->getFill()
                                 ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                                 ->getStartColor()
                                ->setRGB('FF6347');

                                $allPricesSet[$dataItem['idUsuario']] = false;
                            }

                            // Sentencia para activar el seteado del formato de moneda
                            $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getNumberFormat()->setFormatCode("$#,##0.00");

                            $providerColumnItem['current']++;
                            if($dataItem['total'] != null){
                                $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem, $dataItem['total']);
                                if($allPricesSet[$dataItem['idUsuario']]['bool']){
                                    $allPricesSet[$dataItem['idUsuario']]['total'] += $dataItem['total'];
                                }
                                // Sentencia para activar el seteado del formato de moneda
                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getNumberFormat()->setFormatCode("$#,##0.00");
                            }
                            $providerRowItem++;

                            $providerColumnItem['current'] = $providerColumnItem['prev'];


                            if(!$headerReady){
                                if($_SESSION['empresaid'] == 25 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 9){
                                    $excel->getActiveSheet()->setCellValue($column.$row,'Posición');
                                }else{
                                    $excel->getActiveSheet()->setCellValue($column.$row,'Cod');
                                }
                                $excel->getActiveSheet()->getStyle($column.$row)->getFont()->setBold(true);
                                $column++;

                                if($_SESSION['empresaid'] == 25){
                                    $excel->getActiveSheet()->setCellValue($column.$row,'Impuesto');
                                    $excel->getActiveSheet()->getStyle($column.$row)->getFont()->setBold(true);
                                    $column++;
                                }

                                
                                $excel->getActiveSheet()->setCellValue($column.$row,'Descripción');
                                $excel->getActiveSheet()->getStyle($column.$row)->getFont()->setBold(true);
                                $column++;
                                $excel->getActiveSheet()->setCellValue($column.$row,'Cantidad');
                                $excel->getActiveSheet()->getStyle($column.$row)->getFont()->setBold(true);
                                $column++;
                                $excel->getActiveSheet()->setCellValue($column.$row,'Unidad medida');
                                $excel->getActiveSheet()->getStyle($column.$row)->getFont()->setBold(true);

                                $column = $inicioPuntero1;
                                $row++;

                                $headerReady = true;
                            }

                            if ($dataItem['idUsuario'] == $firstProvider){
                                if($_SESSION['empresaid'] == 25 || $_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 20){
                                    $excel->getActiveSheet()->setCellValue($column.$row,$dataItem['numero_linea']);
                                }else{
                                    $excel->getActiveSheet()->setCellValue($column.$row, $counting-1);
                                }
                                $column++;

                                if($_SESSION['empresaid'] == 25){

                                    if(!empty($dataItem['impuesto_adicional'])){
                                        $valorIvaCast2=$this->castImpuestoConfa($dataItem['impuesto_adicional']);
                                        if($valorIvaCast2> 0){
                                            $valorIva2=$valorIvaCast2."%";
                                        }else{
                                            $valorIva2="0%";
                                        }
                                    }else{
                                        $valorIva2="0%";
                                    }
                                    $excel->getActiveSheet()->setCellValue($column.$row,$valorIva2);
                                    $column++;
                                }

                                if((bool)$dataItem['itemObligatorio']){
                                    $excel->getActiveSheet()->setCellValue($column.$row,$dataItem['descripcion'].' (Obligatorio)');
                                }else{
                                    $excel->getActiveSheet()->setCellValue($column.$row,$dataItem['descripcion']);
                                }
                                $column++;
                                $excel->getActiveSheet()->setCellValue($column.$row,$dataItem['cantidad']);
                                $column++;
                                $excel->getActiveSheet()->setCellValue($column.$row,$dataItem['unidadMedida']);
                            }


                            $row++;
                            $column = $inicioPuntero1;

                            $indexPostItem = $item;
                            $indexPostItem++;

                             if((in_array($response[$indexPostItem]['id_item'], $itemId) || $response[$indexPostItem]['id_item'] == null) && $allPricesSet[$dataItem['idUsuario']]['bool']){
                                if($_SESSION['empresaid'] == 25){
                                    $providerColumnItem['current']++;
                                }
                                $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem, 'TOTAL');
                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getFont()->setBold(true);
                                $providerColumnItem['current']++;

                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getNumberFormat()->setFormatCode("$#,##0.00");
                                $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem,  $allPricesSet[$dataItem['idUsuario']]['total']);
                                $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getFont()->setBold(true);
                                
                                $providerColumnItem['current'] = $providerColumnItem['prev'];
                             }else{

                                if(!in_array($response[$indexPostItem]['id_lote'], $loteId) && !is_null($response[$indexPostItem]['id_lote'])){

                                     if(!$headerCurrentProviderReady){

                                        // $excel->getActiveSheet()->mergeCells('A'.$rowHeaderLote.':'.$dataLoteMerge.$rowHeaderLote);

                                        if(!$headerLoteReady){
                                            if(is_null($excel->getActiveSheet()->getCell($inicioPuntero1.$providerRow)->getValue())){

                                                $dataBis = array();
                                                $provBis = 0;
                                                foreach ($response as $itemBis => $dataItemBis){
                                                    if(!in_array($dataItemBis['idUsuario'], $dataBis)){
                                                        array_push($dataBis, $dataItemBis['idUsuario']);
                                                        $provBis++;
                                                    }
                                                }
                                                
                                                $dataLoteMerge = $inicioPuntero6; 
                                                for($i = 1; $i <= $provBis; $i++){
                                                    $dataLoteMerge++;
                                                    $dataLoteMerge++;
                                                }

                                                $excel->getActiveSheet()->mergeCells($inicioPuntero1.$providerRow.':'.$dataLoteMerge.$providerRow);


                                                if($_SESSION['empresaid'] == 25){
                                                    $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'OJ: '.$dataItem['nombre_lote'].' | COMPAÑÍA: '.$dataItem['cod_compania'].' | SOBRE: '.$dataItem['nombreSobre']);
                                                }else{
                                                    $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'Nombre del Lote: '.$dataItem['nombre_lote'].' | SOBRE: '.$dataItem['nombreSobre']);
                                                }

                                                $excel->getActiveSheet()->getStyle($inicioPuntero1.$providerRow)->getFont()->setBold(true);
                                                $excel->getActiveSheet()->getStyle($inicioPuntero1.$providerRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                                                $providerRow++;
                                            }
                                            $headerLoteReady = true;
                                        }
                                        if($_SESSION['empresaid'] == 25){
                                            $providerColumnMerging++;
                                        }
                                        $excel->getActiveSheet()->mergeCells($providerColumn.$providerRow.':'.++$providerColumnMerging.$providerRow);
                                        $excel->getActiveSheet()->setCellValue($providerColumn.$providerRow,$dataItem['nombreEmpresa'].' ('.$dataItem['proveedor'].')');
                                        $excel->getActiveSheet()->getStyle($providerColumn.$providerRow)->getFont()->setBold(true);

                                        // HEADERS DATA PROVEEDORES
                                        $providerRow++;
                                        $providerColumnMerging = $providerColumn;
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Valor ('.$moneda.') u/n');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);

                                        
                                        $providerColumnMerging++;
                                        $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal');
                                        // Sentencia para activar el estilo de negritas
                                        $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);

                                        if($firstLote){
                                            $providerRow = '2';
                                        }else{
                                            $providerRow--;
                                        }

                                        $providerColumn++;
                                        $providerColumn++;
                                        if($_SESSION['empresaid'] == 25){
                                            $providerColumn++;
                                        }
                                        $providerColumnMerging = $providerColumn;

                                        $headerCurrentProviderReady = true;
                                    }


                                    $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem, 'TOTAL');
                                    $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                                    $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getFont()->setBold(true);
                                    $providerColumnItem['current']++;

                                    $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getNumberFormat()->setFormatCode("$#,##0.00");
                                    $excel->getActiveSheet()->setCellValue($providerColumnItem['current'].$providerRowItem,  $allPricesSet[$dataItem['idUsuario']]['total']);
                                    $excel->getActiveSheet()->getStyle($providerColumnItem['current'].$providerRowItem)->getFont()->setBold(true);
                                    
                                    $providerRow += $counting;

                                    $providerColumnItem['current'] = $inicioPuntero3;
                                    $providerColumnItem['prev'] = $inicioPuntero3;
                                    $counting = 1;
                                    $allPricesSet = array();
                                    $itemId = array();
                                    $column = $inicioPuntero1;
                                    $providerColumn = $inicioPuntero3;
                                    $providerColumnMerging = $inicioPuntero3;

                                    $headerReady = false;
                                    $headerCurrentProviderReady = false;
                                    $headerLoteReady = false;
                                    $firstLote = false;

                                    $providerRow += 6;

                                    $row = $providerRow+2;

                                    $providerRowItem = $row+1;

                                    array_push($loteId, $response[$indexPostItem]['id_lote']);
                                }
                             }

                            
                             if(!$headerCurrentProviderReady){

                                // $excel->getActiveSheet()->mergeCells('A'.$rowHeaderLote.':'.$dataLoteMerge.$rowHeaderLote);

                                if(!$headerLoteReady){
                                    if(is_null($excel->getActiveSheet()->getCell($inicioPuntero1.$providerRow)->getValue())){

                                        $dataBis = array();
                                        $provBis = 0;
                                        foreach ($response as $itemBis => $dataItemBis){
                                            if(!in_array($dataItemBis['idUsuario'], $dataBis)){
                                                array_push($dataBis, $dataItemBis['idUsuario']);
                                                $provBis++;
                                            }
                                        }
                                        
                                        $dataLoteMerge = $inicioPuntero6; 
                                        for($i = 1; $i <= $provBis; $i++){
                                            $dataLoteMerge++;
                                            $dataLoteMerge++;
                                        }

                                        $excel->getActiveSheet()->mergeCells($inicioPuntero1.$providerRow.':'.$dataLoteMerge.$providerRow);

                                        // Condicional que evita que en los lotes consecutivos se repitan los headers
                                        if(is_null($response[$indexPostItem]['id_lote'])){
                                            if($_SESSION['empresaid'] == 25){
                                                $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'OJ: '.$dataItem['nombre_lote'].' | COMPAÑÍA: '.$dataItem['cod_compania'].' | SOBRE: '.$dataItem['nombreSobre']);
                                            }else{
                                                $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'Nombre del Lote: '.$dataItem['nombre_lote'].' | SOBRE: '.$dataItem['nombreSobre']);
                                            }
                                        }else{
                                            if($_SESSION['empresaid'] == 25){
                                                $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'OJ: '.$response[$indexPostItem]['nombre_lote'].' | COMPAÑÍA: '.$response[$indexPostItem]['cod_compania'].' | SOBRE: '.$response[$indexPostItem]['nombreSobre']);
                                            }else{
                                                $excel->getActiveSheet()->setCellValue($inicioPuntero1.$providerRow,'Nombre del Lote: '.$response[$indexPostItem]['nombre_lote'].' | SOBRE: '.$response[$indexPostItem]['nombreSobre']);
                                            }
                                        }

                                        $excel->getActiveSheet()->getStyle($inicioPuntero1.$providerRow)->getFont()->setBold(true);
                                        $excel->getActiveSheet()->getStyle($inicioPuntero1.$providerRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                                        $providerRow++;
                                    }
                                    $headerLoteReady = true;
                                }
                                if($_SESSION['empresaid'] == 25){
                                    $providerColumnMerging++;
                                }
                                $excel->getActiveSheet()->mergeCells($providerColumn.$providerRow.':'.++$providerColumnMerging.$providerRow);
                                $excel->getActiveSheet()->setCellValue($providerColumn.$providerRow,$dataItem['nombreEmpresa'].' ('.$dataItem['proveedor'].')');
                                $excel->getActiveSheet()->getStyle($providerColumn.$providerRow)->getFont()->setBold(true);

                                // HEADERS DATA PROVEEDORES
                                $providerRow++;
                                $providerColumnMerging = $providerColumn;
                                

                                if($_SESSION['empresaid'] == 25){
                                    $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Valor unitario sin impuestos ('.$moneda.') u/n');
                                    // Sentencia para activar el estilo de negritas
                                    $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                    $providerColumnMerging++;
                                    
                                    $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal sin impuesto ('.$moneda.')');
                                    // Sentencia para activar el estilo de negritas
                                    $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);

                                    $providerColumnMerging++;
                                    $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal con impuesto ('.$moneda.')');
                                    // Sentencia para activar el estilo de negritas
                                    $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                }else{
                                    $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Valor ('.$moneda.') u/n');
                                    // Sentencia para activar el estilo de negritas
                                    $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                    $providerColumnMerging++;
                                    $excel->getActiveSheet()->setCellValue($providerColumnMerging.$providerRow,'Subtotal');
                                    // Sentencia para activar el estilo de negritas
                                    $excel->getActiveSheet()->getStyle($providerColumnMerging.$providerRow)->getFont()->setBold(true);
                                }

                                if($firstLote){
                                    $providerRow = '2';
                                }else{
                                    $providerRow--;
                                }

                                $providerColumn++;
                                $providerColumn++;
                                if($_SESSION['empresaid'] == 25){
                                    $providerColumn++;
                                    $providerColumn++;
                                }
                                $providerColumnMerging = $providerColumn;

                                $headerCurrentProviderReady = true;
                            }

                        }

                        // Ocensa
                        if($_SESSION['empresaid'] == 20 || $_SESSION['empresaid'] == 25){
                            if($sinFinalizarSobres == 0){
                                $this->intelcost->response->bool = false;
                                $this->intelcost->response->msg  = 'No se encontraron proveedores que cumplan con la validación del sobre técnico y legal';
                                return $this->intelcost->response;
                            }
                        }

                        // FIN DE RECORRIDO DE ITEMS Y ESTRUCTURACION DE CUADRO (EXCEL)

                        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
                        header('Content-Disposition: attachment;filename="fileExcel.xlsx"');
                        header('Cache-Control: max-age=0');

                        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                        $path = '../Cuadro_Comparativo_Lotes('.$seqId.')'.date('d-m-Y-H.i.s').'.xlsx';
                        $writer->save($path);

                        if(file_exists($path)){
                            $this->intelcost->response->bool = true;
                            $this->intelcost->response->msg  = $path;
                        }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg  = 'Error al crear el archivo Excel.';
                        }

                    }else{
                            $this->intelcost->response->bool = false;
                            $this->intelcost->response->msg  = "Error al crear el archivo Excel para el cuadro comparativo.";
                    }

                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg  = "No se encontró información sobre la oferta.";     
                }
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg  = "Error al consultar la data necesaria para generacion del cuadro comparativo.";
            }
        }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg  = "Datos insuficientes para la generacion del cuadro.";
        }

        return $this->intelcost->response;
    }

    private function castImpuestoConfa($impuesto = null){
        if(!empty($impuesto)){
            switch ($impuesto) {
                case '3':
                    return 16;
                break;
                case '4':
                    return 5;
                break;
                case '6':
                    return 19;
                break;
                default:
                    return 0;
                break;
            }
        }else{  
            return 0;
        }
    }

    public function eliminarCuadroComparativoLotes($path){
        if(isset($path) && !empty($path)){
            if(file_exists($path)){

                unlink($path);

                if(!file_exists($path)){
                    $this->intelcost->response->bool = true;
                    $this->intelcost->response->msg  = "Se ha eliminado el archivo Excel con exito del servidor.";
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg  = "No se pudo eliminar el archivo Excel del servidor.";
                }
            }
            else
            {
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg  = "La ruta especificada para eliminar el archivo Excel del servidor no existe.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "Datos insuficientes para la eliminacion del Excel en el servidor.";
        }

        return $this->intelcost->response;
    }

    public function reporteIndiceAhorro(Request $request){
        $response = new stdClass();
        // Valida que tenga sesión
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

        // Valida que tenga el id de la oferta
        if (!$request->id_oferta) {
            header("406 Not Acceptable", true, 406);
            $response->bool = false;
			$response->msg = 'Action Not Allowed';
			return $response;
        }

        // Consultar oferta
        $oferta = Oferta::with('infoAdicionalesOferta')
                        ->with('actividades')
                        ->with('infoUsuarioCreacion')
                        ->with(['infoLote' => function($query){
                            $query->where('estado', 'activo')
                                ->with([
                                    'obtenerItems.obtenerInformacionAdicionalItems', 
                                    'obtenerItems.obtenerMedida',
                                    'obtenerItems.infoRegistroPrecioProveedor'
                                ]);
                        }])
                        ->with([
                            'infoProveedoresAdjudicados.infoProveedor.empresa', 
                            'infoProveedoresAdjudicados.infoOrdenCompra.infoItemOrdenPedido.infoCompaniasPedido.infoCompania'
                        ])
                        ->find($request->id_oferta);
        $rutaTemplatePDF = dirname(dirname(__DIR__)).'/ic_vistas/template_pdf/';
        $ruta = dirname(dirname(__DIR__)).'/export_temp/';
        $nombre = 'Índice de ahorro del evento '.$oferta->seq_id.' - '.uniqid().'.pdf';
        $this->template_url_html = $rutaTemplatePDF; 

        $html = $this->getViewEmail('indice_ahorro', [
            'oferta' => $oferta
        ], [
            'renameVariable' => true, 
            'nameNewVariable' => "datos"
        ]);
        
        $respuestaPdf = $this->modelo_pdf->generar_pdf($html, $ruta, $nombre);
        if($respuestaPdf){
            $response->bool = true;
            $response->msg = ['ruta' => '../export_temp/', 'nombre' => $nombre];
        }else{
            $response->bool = true;
            $response->msg = "No se logro crear el PDF";
        }
        return $response;
    }

    public function guardarRFQSinPublicacion($datos){

        $datos = (object) $datos;

        $seqId = $this->obtenerSecuenciaMetro($datos);

        if($datos->id_oferta == ""){
            $rfq = new Oferta();

            $rfq->id_cliente = $_SESSION["empresaid"];
            $rfq->seq_id = $seqId;
            $rfq->tipo = "rfq";
            $rfq->usuario_creacion = $_SESSION["idusuario"];
            $rfq->estado = "ACTIVO";
            $rfq->id_aoc = $datos->aoc_id;
            $rfq->duenio_oferta = $_SESSION["idusuario"];
            $rfq->maestra1 = "rfq_sin";
        }else{
            $rfq = Oferta::where('id', '=',$datos->id_oferta)->first();
        }
        
        $rfq->usuario_actualizacion = $_SESSION["idusuario"];
        $rfq->fecha_actualizacion = date("Y-m-d H:i:s");
        $rfq->id_area = $datos->id_area_oferta;
        $rfq->objeto = $datos->objeto;
        $rfq->descripcion = $datos->descripcion;
        $rfq->actividad = $datos->actividad_id;
        $rfq->moneda = $datos->moneda;
        $rfq->presupuesto = $datos->presupuesto;
        $rfq->modalidad_seleccion = $datos->modalidad_seleccion;

        $rfq->save();

        if($rfq && count($rfq->toArray()) > 0){

            $res = new stdClass();
            $res->seq_id =  $rfq->seq_id;
            $res->id =  $rfq->id;

            $this->guardarSoporte($rfq->id,$datos);
            $this->guardarMoneda($rfq->id,$datos);
            $this->asociarFlujosRfqMetro($datos,$rfq->id,0);

            if(count($datos->oferente) > 0){
                foreach ($datos->oferente as $key => $value) {
                    if($value['contactos']){
                        foreach ($value['contactos'] as $key => $participante) {
                           $this->creaParticipanteOferta($rfq->id, $participante['userId'], $participante['provNombre'], $participante['provNit'], $participante['email']);
                        }
                    }else{
                        $this->creaParticipanteOferta($rfq->id, $value['userId'], $value['provNombre'], $value['provNit'], $value['email']);
                    }
                }
            }

            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg = $res;

        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al crear el RFQ";
        }   
        
        return $this->intelcost->response;
    }

    public function guardarSoporte($oferta,$datos){

        foreach ($datos->soporte as $key => $value) {

            if($value['id_registro'] == ""){
                $soporte = new OfertaDocumentos();
                $soporte->id_oferta = $oferta;
                $soporte->usuario_actualizacion = $_SESSION["idusuario"];
                $soporte->titulo = $value['titulo'];
                $soporte->ruta = $value['archivo'];
                
            }else {
                $soporte = OfertaDocumentos::where('id', '=',$value['id_registro'])->first();
                $soporte->titulo = $value['titulo'];
                $soporte->ruta = $value['archivo'];
                $soporte->usuario_actualizacion = $_SESSION["idusuario"];
            }

            $soporte->save();
        }
    }

    public function guardarMoneda($oferta,$datos){

        foreach ($datos->monedas as $key => $value) {

            if($value['id_registro'] == ""){
                $moneda = new OfertaMoneda();
                $moneda->id_oferta = $oferta;
                $moneda->moneda = $value["moneda"];
                $moneda->valor = $value['presupuesto'];
                
            }else {
                $moneda = OfertaMoneda::where('id', '=',$value['id_registro'])->first();
                $moneda->moneda = $value['moneda'];
                $moneda->valor = $value['presupuesto'];
            }

            $moneda->save();
        }
    }

    public function desactivarRegistro($id_registro, $tipo){

        if ($tipo == "moneda") {
            $tabla = "oferta_moneda";
        }else if($tipo == "documento"){
            $tabla = "oferta_documentos";
        }

        if($tabla!=""){

            $sql = 'UPDATE '.$tabla.' SET estado="eliminado" WHERE id = ? ';

            $csc= $this->intelcost->prepareStatementQuery('cliente', $sql, 'UPDATE', true, "i", array((int) $id_registro), "Desactivar registro.");

            if($csc->bool){
                $this->intelcost->response->bool = true;
                $this->intelcost->response->msg  = "Registro desactivado.";
            }else{
                $this->intelcost->response->bool = false;
                $this->intelcost->response->msg  = "Se presentó un error al desactivar el registro.";
            }
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "Tipo no válido.";
        }
        return $this->intelcost->response;
    }

    public function guardarProveedorAdjudicado($oferta){
        
        $objOferta = Oferta::where('id', '=',$oferta['id'])->first();

        if($objOferta && count($objOferta->toArray()) > 0){

            foreach ($objOferta->infoOfertaParticipantes as $key => $value) {

                $adjudicacion = new OfertaAdjudicaciones();
                $adjudicacion->id_oferta = $oferta['id'];
                $adjudicacion->id_usuario = $value->id_usuario;
                $adjudicacion->moneda = $oferta['moneda'];
                $adjudicacion->valor = $oferta['presupuesto'];
                $adjudicacion->porcentaje = '100';
                $adjudicacion->usuario_creacion = $oferta['usuario_creacion'];

                $adjudicacion->save();

            }

        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg = "Error al consultar la oferta";
        } 

    }

    public function obtenerLotesOferta($oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        $arrLotes=[];
        $SqlLotes="SELECT OL.id_lote,OL.cod_sobre,LOWER(OL.nombre_lote) nombre_lote,LOWER(CA.nombre) titulo_sobre, cod_compania, CA.obligatorio FROM oferta_lotes OL LEFT JOIN capitulos CA ON OL.cod_sobre = CA.id WHERE OL.cod_oferta='".$oferta."' AND OL.estado=1 ";
        $CscLotes=$dbConection->query($SqlLotes);
        if($CscLotes){
            while ($rowLote=$CscLotes->fetch_assoc()) {
                $rowLote['nombre_lote']=ucwords(($rowLote['nombre_lote']));
                $rowLote['titulo_sobre']=ucwords(($rowLote['titulo_sobre']));
                $rowLote['columnasAdicionales']=$this->obtenerColumnasAdicionales($rowLote["id_lote"]);
                $resItems=$this->obtenerItemsLoteOferta($rowLote['id_lote']);
                $rowLote['id_lote']=sha1($rowLote['id_lote']);
                if($resItems->bool){
                    $rowLote['itemsLote']=$resItems->msg;
                }else{
                    $rowLote['itemsLote']=false;
                }
                array_push($arrLotes,json_encode($rowLote));
            }
        }
        $this->intelcost->response->bool = true;
        $this->intelcost->response->msg = json_encode($arrLotes);
        return $this->intelcost->response;
    }

    public function guardarCriteriosTecnicos(object $criteriosTecnicos, $idOferta){
        //Deja en estado eliminado si el caso se borran items
        OfertaCriteriosEvaluacion::where('id_oferta', $idOferta)->update(['estado' => '0']);

        foreach ($criteriosTecnicos as $key => $criterio) {
            $condicional = [
                'id' => empty($criterio->id) ? '' : $criterio->id
            ];

            $arregloCriterio = [
                'id_oferta' => $idOferta,
                'criterio' => $criterio->nombre_criterio,
                'valor' => '0',
                'estado' => '1',
                'fecha_creacion' => date('Y-m-d'),
            ];

            //Al momento de encontrar el item cambiará el estado con la información o creará un nuevo elemento
            $registro = OfertaCriteriosEvaluacion::updateOrCreate($condicional, $arregloCriterio);

            if(!empty($criterio->forma_verificacion)){
                $condicional = [
                    'id_criterio_oferta' => $registro->id
                ];
    
                $arregloCriterio = [
                    'id_criterio_oferta' => $registro->id,
                    'forma_verificacion' => $criterio->forma_verificacion,
                    'estado' => 'activo',
                ];

                OfertaCriteriosEvaluacionDatosAdicionales::updateOrCreate($condicional, $arregloCriterio);
            }
        }
    }

    public function guadarCriteriosTenicosEvaluacion(Request $request){
        $response = new stdClass();
        // Valida que tenga sesión
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

        // Valida que tenga el id de la oferta
        if (!$request->id_oferta) {
            header("406 Not Acceptable", true, 406);
            $response->bool = false;
			$response->msg = 'Action Not Allowed';
			return $response;
        }

        
        $proveedoresCalificados = json_decode($request->proveedoresCalificacdo);
        
        foreach ($proveedoresCalificados as $key => $proveedor) {
            $criterios = collect(json_decode($proveedor->criterios));
            $id_usuario_proveedor = $criterios->pluck('id_usuario')->first();

            $condicional = [
                'id_oferta' => $request->id_oferta,
                'id_usuario_proveedor' => $id_usuario_proveedor,
                'estado' => 'activo',
            ];

            $arrayContenido = [
                'id_oferta' => $request->id_oferta,
                'id_usuario_creador' => $_SESSION['idusuario'],
                'id_usuario_actualizador' => $_SESSION['idusuario'],
                'id_usuario_proveedor' => $id_usuario_proveedor,
                'resultado_final' => $proveedor->criterio_final,
                'observaciones' => '',
                'bloqueo' => $request->finalizar ? 1 : 0,
            ];
            $id_cabecera = OfertaEvaluacionProveedorResultadoTecnico::updateOrCreate($condicional, $arrayContenido);
            foreach ($criterios as $key => $criterio) {
                $condicional = [
                    'id_criterio' => $criterio->id_criterio,
                    'id_cabecera_resultado_tecnico' => $id_cabecera->id,
                    'estado' => 'activo',
                ];

                $arrayContenido = [
                    'id_criterio' => $criterio->id_criterio,
                    'id_cabecera_resultado_tecnico' => $id_cabecera->id,
                    'resultado' => $criterio->criterio,
                ];

                OfertaEvaluacionProveedorResultadoTecnicoCriterios::updateOrCreate($condicional, $arrayContenido);
            }
        }

        $response->bool = true;
		$response->msg = 'Se ha guardado correctamente la evaluación de criterios técnicos';
        return $response;
    }
    
    public function obtenerLotesOfertaAux($oferta){
        $dbConection = $this->intelcost->db->createConection("cliente");
        $arrPart=[];
        $participantes="select id_usuario from oferta_participantes where id_oferta = $oferta and estado = 'activo';";
        $arrParticipante=$dbConection->query($participantes);
        if($arrParticipante){
            while ($participante=$arrParticipante->fetch_assoc()) {
                $var;
                $ofertaLotesEnviada = $this->obtenerPreciosLotesOfertaEnviada($participante["id_usuario"],$oferta);
                if($ofertaLotesEnviada->bool){
                    $var["infoProd"] = json_decode($ofertaLotesEnviada->msg);
                }else{
                    $var["infoProd"] = false;
                }
                $infoParticipante = $this->obtener_participante($participante["id_usuario"]);
                $infoParticipante = json_decode($infoParticipante);
                if($infoParticipante->bool){
                    $var["info_part"] = $infoParticipante->msg;
                }else{
                    $var["info_part"] = false;
                }
                array_push($arrPart,$var);
            }
        }
        return $this->clasfLotes($arrPart);
    }

    //funcion que clasifica la informacion por lotes
    private function clasfLotes($arrPart){
        //$arrPart = json_decode($arrPart);
        $arrLotes = [];
        for ($i=0; $i < count($arrPart) ; $i++) { 
            for ($j=0; $j < count($arrPart[$i]["infoProd"]); $j++) { 
                $item = json_decode($arrPart[$i]["infoProd"][$j]);
                $item = json_decode($item->itemsLote);
                for ($k=0; $k < count($item); $k++) { 
                    $itemF = json_decode($item[$k]);
                    $itemF->info_par = $arrPart[$i]["info_part"];
                    if(isset($arrLotes[$itemF->cod_lote]['items'])){
                        array_push($arrLotes[$itemF->cod_lote]['items'],$itemF);
                    }else{
                        $arrLotes[$itemF->cod_lote]['items'] = [];
                        $info_lote = json_decode($arrPart[$i]["infoProd"][$j]);
                        $arrLotes[$itemF->cod_lote]['infoLote'] = $info_lote->nombre_lote;
                        array_push($arrLotes[$itemF->cod_lote]['items'],$itemF);
                    }       
                }   
            }
        }
        return $this->clasfItemsLoteOf($arrLotes);
    }

    //funcion que clasifica los productos dentro de los 
    //lotes de acuerdo al oferente
    private function clasfItemsLoteOf($arrLotes){
        $arrLotesAux = [];
        foreach ($arrLotes as $lote => $itemP) {
            $arrLotesAux[$lote]["infoLote"] = $itemP["infoLote"];
            foreach ($itemP as $key => $item) {
                if($key == 'items'){
                    foreach ($item as $key => $itemF) {
                        if(isset($arrLotesAux[$lote]["empresas"][$itemF->info_par->empresa])){
                            array_push($arrLotesAux[$lote]["empresas"][$itemF->info_par->empresa],$itemF);
                        }else{
                            $arrLotesAux[$lote]["empresas"][$itemF->info_par->empresa] = [];
                            array_push($arrLotesAux[$lote]["empresas"][$itemF->info_par->empresa],$itemF);
                        }
                    }
                }
            }
        }
        return $arrLotesAux;
    }

    public function generarExcelLotesEnv($oferta,$borrar = null,$path = null){
		if(isset($borrar) && $borrar && isset($path)){
			$this->borrarInfLotesGen($path);
		}else{
			try{
				require_once '../ic_libs/PHPExcel/Classes/PHPExcel.php';
                $infoLotes = $this->obtenerLotesOfertaAux($oferta);
                if(count($infoLotes) > 0){
                    // Crea un nuevo objeto PHPExcel
                    $objPHPExcel = new PHPExcel();
        
                    // Establecer propiedades
                    $objPHPExcel->getProperties()
                    ->setCreator("Intelcost")
                    ->setLastModifiedBy("Intelcost")
                    ->setTitle("Informe general de oferta por lotes")
                    ->setSubject("Informe general de oferta por lotes")
                    ->setDescription("Informe general de oferta por lotes")
                    ->setKeywords("")
                    ->setCategory("Reporte");
        
                    // Agregar Informacion
                    $objPHPExcel = $this->asigItemsLotesExcel($infoLotes,$objPHPExcel);
                    
                    // Se modifican los encabezados del HTTP para indicar que se envia un archivo de Excel.
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment;filename="fileExcel.xlsx"');
                    header('Cache-Control: max-age=0');
                    
                    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
                    $path = '../Informe_general_lotes('.$oferta.')'.date('d-m-Y-H.i.s').'.xlsx';
                    $objWriter->save($path);
                    
                    if(file_exists($path)){
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg  = $path;
                        //$this->intelcost->response->dato  =  $infoLotes;
                    }else{
                        $this->intelcost->response->bool = false;
                        $this->intelcost->response->msg  = 'Error al crear el archivo Excel.';
                    }
                }else{
                    $this->intelcost->response->bool = false;
                    $this->intelcost->response->msg  = 'Sin informacion suficiente por parte de la oferta';
                }				
			}catch(Exception $e){ 
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg ="Error al genera rel informe:" . $e; 
			}
        }
        return $this->intelcost->response;	 	
    }
    
    //funcion que recorre la infromacion de los lotes 
    private function asigItemsLotesExcel($info,$objPHPExcel){
        //borramos el libro por defecto
        $objPHPExcel->removeSheetByIndex(
            $objPHPExcel->getIndex(
                $objPHPExcel->getSheetByName('Worksheet')
            )
        );
        $index = 0;
        foreach ($info as $codLote => $lote) {
            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex($index)->setTitle("Lote-".($index+1));
            $this->agregaFilasExcel($lote,$objPHPExcel->setActiveSheetIndex($index));
            $index++;
        }
        return $objPHPExcel;
    }

    //funcion que agrega las filas a las hojas del excel
    //informe general de lotes
    private function agregaFilasExcel($lote, &$objPHPExcel){
        $indexAux = 0;
        foreach ($lote["empresas"] as $nombreEmp => $empresa) {
            //formateamos columnas adicionales si existen
            $colsAdd = $this->columnasAdd($empresa);
            //titulo
            $objPHPExcel->setCellValue('A'.($indexAux+1), "Nombre lote: "
            .$lote["infoLote"]." - Empresa: ".$nombreEmp)
            ->mergeCells('A'.($indexAux+1).':F'.($indexAux+1));
            $objPHPExcel->getStyle('A'.($indexAux+1))->getFont()->setBold(true);
            $indexAux++;
            //subtitulos 
            $objPHPExcel->setCellValue('A'.($indexAux+1),"Cod");
            $objPHPExcel->setCellValue('B'.($indexAux+1),"Descripcion");
            $objPHPExcel->setCellValue('C'.($indexAux+1),"Unidad de medida");
            $objPHPExcel->setCellValue('D'.($indexAux+1),"Cantidad");
            $objPHPExcel->setCellValue('E'.($indexAux+1),"Valor Unitario");
            $objPHPExcel->setCellValue('F'.($indexAux+1),"Subtotal");
            //agregamos los titulos de columas adicionales si existen
            $columnaI = 'G';
            if(count($colsAdd) > 0){
                foreach ($colsAdd as $columna => $datosCol) {
                    $colsAdd[$columna] = $columnaI;
                    $objPHPExcel->setCellValue($columnaI.($indexAux+1),$columna);
                    $columnaI++;
                }
            }
            $objPHPExcel->getStyle('A'.($indexAux+1).':'.$columnaI.($indexAux+1))->getFont()->setBold(true);
            $indexAux++;
            //valores
            $indexIni = $indexAux+1;
            foreach ($empresa as $index => $item) {
                $objPHPExcel->setCellValue('A'.($indexAux+1),($index+1));
                $objPHPExcel->setCellValue('B'.($indexAux+1),$item->descripcion);
                $objPHPExcel->setCellValue('C'.($indexAux+1),$item->desUnidadMedida);
                $objPHPExcel->setCellValue('D'.($indexAux+1),$item->cantidad);
                $objPHPExcel->setCellValue('E'.($indexAux+1),$item->valorParticipante);
                $objPHPExcel->setCellValue('F'.($indexAux+1),'=(D'.($indexAux+1).'*E'.($indexAux+1).')');
                //agregamos los valores de columas adicionales si existen
                foreach ($item->columnasAdicionales as $key => $columAdd) {
                    $letra = $colsAdd[$columAdd->obtener_columna_adicional->titulo];
                    $objPHPExcel->setCellValue($letra.($indexAux+1),$columAdd->valor);
                }
                $objPHPExcel->getStyle('E'.($indexAux+1))->getNumberFormat()->setFormatCode('$#,##0.00');
                $objPHPExcel->getStyle('F'.($indexAux+1))->getNumberFormat()->setFormatCode('$#,##0.00');
                $indexAux++;
            }
            //footer   
            $indexFin = $indexAux;
            $objPHPExcel->setCellValue('E'.($indexAux+1),"Total");
            $objPHPExcel->setCellValue('F'.($indexAux+1),'=sum(F'.$indexIni.':F'.$indexFin.')');
            $objPHPExcel->getStyle('E'.($indexAux+1).':F'.($indexAux+1))->getFont()->setBold(true);
            $objPHPExcel->getStyle('F'.($indexAux+1))->getNumberFormat()->setFormatCode('$#,##0.00');
            $indexAux += 5;     
        }
        return $objPHPExcel;
    }
    
    //funcion que recorre los items e identifica sus columnas adicionales
    private function columnasAdd($empresa){
        $columnasAd = [];
        foreach ($empresa as $key => $items) {
             foreach ($items->columnasAdicionales as $key => $item) {
                $tl = $item->obtener_columna_adicional->titulo;
                if(isset($columnasAd[$tl])){
                    array_push($columnasAd[$tl],[]);  
                }else{
                    $columnasAd[$tl] = [];
                    array_push($columnasAd[$tl],[]); 

                }          
            }             
        }
        return $columnasAd;
    }


	//funcion que borra el archivo excel de informe gerenal de lotes
	private function borrarInfLotesGen($path){
		if(isset($path) && !empty($path)){
			if(file_exists($path)){

				unlink($path);

				if(!file_exists($path)){
					$this->intelcost->response->bool = true;
					$this->intelcost->response->msg  = "Se ha eliminado el formato con exito del servidor.";
				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg  = "No se pudo eliminar el archivo Excel del servidor.";
				}
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg  = "La ruta especificada para eliminar el archivo Excel del servidor no existe.";
			}
		}else{
		$this->intelcost->response->bool = false;
		$this->intelcost->response->msg  = "Datos insuficientes para la eliminacion del Excel en el servidor.";
		}
        return $this->intelcost->response;	
    }	
    
    public function formatoAdjudicacion($oferta){
        $FormatosOferta = new FormatosOferta($oferta);
        $modelo_word = new modelo_word();
        $ruta = '../ic_files/';
        if (!is_dir($ruta)) {
            mkdir($ruta);
        }
        $ruta = '../ic_files/ofertas/';
        if (!is_dir($ruta)) {
            mkdir($ruta);
        }
        $fecha = new DateTime();
        $nombre = "Formato_adjudicacion-". $oferta."-".$fecha->getTimestamp().".docx";
        $resultadoOferta = Oferta::where('id',$oferta)
        ->with('infoUsuarioCreacion')
        ->with('infoOfertaParticipantes.infoEmpresa')
        ->with('infoOfertaParticipantes.infoEvaluacionTecnica')
        ->with('infoProveedoresAdjudicados.infoProveedor.empresa')
        ->with('infoAdicionalesOferta.precalificacion.obtenerEmpresasPrecalificadas.resultado')
        ->with('infoAdicionalesOferta.precalificacion.obtenerEmpresasPrecalificadas.infoEmpresa')
        ->first();
        $html = $FormatosOferta->formatoAdjudicacionOcensa($resultadoOferta);
        $generarDoc = $modelo_word->generar_word($html,$ruta,$nombre);
        if ($generarDoc) {
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg  = $ruta.$nombre;
        } else {
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "No se logro crear el formato con extensión .docx";
        }
        return $this->intelcost->response;
    }

    public function formatoOfertaMercantil($oferta,$codigoAdjudicacion,$codigoEmpresa,$codigoUsuarioEmpresa){
        $FormatosOferta = new FormatosOferta($oferta);
        $modelo_word = new modelo_word();
        $ruta = '../ic_files/';
        if (!is_dir($ruta)) {
            mkdir($ruta);
        }
        $ruta = '../ic_files/ofertas/';
        if (!is_dir($ruta)) {
            mkdir($ruta);
        }
        $fecha = new DateTime();
        $resultadoOferta = Oferta::where('id',$oferta)
        ->with(['infoProveedoresAdjudicados' => function($query) use ($codigoAdjudicacion){
            $query->where('id','=', $codigoAdjudicacion)->with('infoProveedor.empresa');
        }])
        ->with('obtenerMaestra1')
        ->with('infoUsuarioCreacion')
        ->with(['infoOfertaParticipantes' => function($query) use ($codigoUsuarioEmpresa){
            $query->where('id_usuario','=', $codigoUsuarioEmpresa);
        }])
        ->with(['infoLote.obtenerItems.infoRegistroPrecioProveedor' => function($query) use ($codigoUsuarioEmpresa){
            $query->where('usuario_creacion','=', $codigoUsuarioEmpresa);
        }])
        ->first();
        $nombre = "Formato_oferta_mercantil-". $oferta."-".$fecha->getTimestamp().".docx";
        $html = $FormatosOferta->formatoOfertaMercantilOcensa($resultadoOferta);
        $generarDoc = $modelo_word->generar_word($html,$ruta,$nombre);
        if ($generarDoc) {
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg  = $ruta.$nombre;
        } else {
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "No se logro crear el formato con extensión .docx";
        }
        return $this->intelcost->response;
    }

    public function formatoOfertaCartaAdjudicacion($oferta,$codigoAdjudicacion,$codigoEmpresa,$codigoUsuarioEmpresa){
        $FormatosOferta = new FormatosOferta($oferta);
        $modelo_word = new modelo_word();
        $ruta = '../ic_files/';
        if (!is_dir($ruta)) {
            mkdir($ruta);
        }
        $ruta = '../ic_files/ofertas/';
        if (!is_dir($ruta)) {
            mkdir($ruta);
        }
        $fecha = new DateTime();
        $resultadoOferta = Oferta::where('id',$oferta)
        ->with(['infoProveedoresAdjudicados' => function($query) use ($codigoAdjudicacion){
            $query->where('id','=', $codigoAdjudicacion)->with('infoProveedor.empresa');
        }])
        ->with('obtenerMaestra1')
        ->with('infoUsuarioCreacion')
        ->with(['infoOfertaParticipantes' => function($query) use ($codigoUsuarioEmpresa){
            $query->where('id_usuario','=', $codigoUsuarioEmpresa)->with(['infoEmpresa.usuariosProveedores' => function($query) use ($codigoUsuarioEmpresa){
                $query->where('usridxxx',$codigoUsuarioEmpresa);
            }]);
        }])
        ->with(['infoLote.obtenerItems.infoRegistroPrecioProveedor' => function($query) use ($codigoUsuarioEmpresa){
            $query->where('usuario_creacion','=', $codigoUsuarioEmpresa);
        }])
        ->first();
        $nombre = "Formato_oferta_mercantil-". $oferta."-".$fecha->getTimestamp().".docx";
        $html = $FormatosOferta->formatoCartadeAdjudicacionOcensa($resultadoOferta);
        $generarDoc = $modelo_word->generar_word($html,$ruta,$nombre);
        if ($generarDoc) {
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg  = $ruta.$nombre;
        } else {
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "No se logro crear el formato con extensión .docx";
        }
        return $this->intelcost->response;
    }

    public function indicadorOfertasProveedores(){
        $resultado = OfertaAdjudicaciones::whereHas('oferta', function($q){
            $q->where('id_cliente',25)
            ->where('estado','FINALIZADA');
        })
        ->with('oferta','infoProveedor.empresa')
        ->get()
        ->groupBy('infoProveedor.cod_empresa')
        ->take(20);
        
        if(!empty($resultado)){
            $this->intelcost->response->bool = true;
            $this->intelcost->response->msg  = json_encode($resultado);    
        }else{
            $this->intelcost->response->bool = false;
            $this->intelcost->response->msg  = "No se encontraron parametros para los indicadores";
        }
        return $this->intelcost->response;
    }

    public function guardarNumeroActaConfa($request){
        $response = new stdClass();
		if(!isset($_SESSION['empresaid'])){
			header("401 Unauthorized", true, 401);
			$response->bool = false;
			$response->msg = "401 Unauthorized";
			return $response;
		}

		$allRequest = $request->inputs();

		if($allRequest['method'] != 'POST'){
			header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
			$response->bool = false;
			$response->msg = $_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed";
			return $response;
        }


        $oferta = Oferta::where('id', $allRequest['id_oferta'])->first();

        if($oferta){
            $oferta->soportes_existencia = json_encode(['numero_acta' => $allRequest['numero_acta']]);
            $oferta->timestamps = false;
            $oferta->save();
            $response->bool = true;
            $response->msg = 'Se ha guardado correctamente';
        }else{
            $response->bool = false;
            $response->msg = 'No se encontraron centros de costo';
        }
        return $response;
    }

    private function validarUltimaEvaluacionTecnica($idOferta)
    {

        if ($_SESSION['empresaid'] == 14){
            $logOfertas = DB::table('ofertas_log_ws_bizagi_terpel')->where('id_oferta', '=', $idOferta)->orderBy('id', 'DESC')->limit(1)->get();
            $usuariosAprobadores = DB::table('oferta_usuarios_aprobadores')->where('id_oferta', '=', $idOferta)->get();
            $ofertaRes= Oferta::select('estado', 'seq_id', 'usuario_creacion', 'solpeds_relacionadas')->where('id', $idOferta)->first();
            $seq_id = $ofertaRes->seq_id;

          

            $aprobaciones = DB::select(DB::raw("SELECT o.id as codigo_oferta, odp.id as codigo_documento_proveedor, odp.id_usuario as codigo_usuario_proveedor, group_concat( distinct oed.id ) as codigos_evaluacion, group_concat( distinct oed.resultado_evaluacion ) as resultados_evaluacion, group_concat( distinct oedh.id_historial ) as codigos_historial_aprobacion, group_concat( distinct oedh.valoracion ) as valoraciones_aprobacion, od.obligatorio from oferta_documentos_oferentes as od inner join oferta_documentos_ofertascliente as odp on od.id = odp.id_documento_oferente inner join ofertas as o on o.id = od.oferta_id inner join oferta_participantes as op on o.id = op.id_oferta and odp.id_usuario = op.id_usuario left join oferta_evaluacion_documento as oed on oed.documento_id = odp.id and oed.estado = 'activo' left join oferta_evaluacion_documento_historial as oedh on oed.id = oedh.id_evaluacion where o.id = ".$idOferta." and op.estado = 'activo' and op.estado_participacion = 'ofe_enviada' and od.evaluable = 'si' group by odp.id order by codigo_usuario_proveedor asc"));

            $contadorAprobacionesAprobadas = 0;
            $contadorAprobacionesRechazadas = 0;

            foreach ($aprobaciones as $aprobacion) {
                if (preg_match('/aprobado/', $aprobacion->valoraciones_aprobacion)) {
                    $contadorAprobacionesAprobadas++;
                } elseif (preg_match('/rechazado/', $aprobacion->valoraciones_aprobacion)) {
                    $contadorAprobacionesRechazadas++;
                }
            }

            $estadoOfertaRegistrado =  DB::select(DB::raw("select o.id as codigo_oferta, odp.id as codigo_documento_proveedor, odp.id_usuario as codigo_usuario_proveedor, group_concat( distinct oed.id ) as codigos_evaluacion, group_concat( distinct oed.resultado_evaluacion ) as resultados_evaluacion, group_concat( distinct oedh.id_historial ) as codigos_historial_aprobacion, group_concat( distinct oedh.valoracion ) as valoraciones_aprobacion, SUBSTRING_INDEX(oedh.valoracion, ',', -1) AS ultimo_estado, od.obligatorio from oferta_documentos_oferentes as od inner join oferta_documentos_ofertascliente as odp on od.id = odp.id_documento_oferente inner join ofertas as o on o.id = od.oferta_id inner join oferta_participantes as op on o.id = op.id_oferta and odp.id_usuario = op.id_usuario left join oferta_evaluacion_documento as oed on oed.documento_id = odp.id left join oferta_evaluacion_documento_historial as oedh on oed.id = oedh.id_evaluacion where o.id = ".$idOferta." and op.estado = 'activo' and op.estado_participacion = 'ofe_enviada' and od.evaluable = 'si' and oedh.TIPO_USUARIO_REGISTRA = 'aprobador' group by odp.id order by codigo_usuario_proveedor asc"));

            $ofertaRechazadas= 0;
            foreach ($estadoOfertaRegistrado as $ofertaRegistrada) {
                if (preg_match('/rechazado/', $ofertaRegistrada->valoracion)) {
                    $ofertaRechazadas++;
                }
            }

            $ultimasAprobaciones= DB::select(DB::raw("select oedh.id_historial as id_historial, oedh.fecha_aprobacion, oedh.tipo_usuario_registra , oedh.id_usuario as usuario_evaluador, oedh.tipo_usuario_registra, oedh.valoracion, oedh.observaciones, u.email from ofertas as ofe left join oferta_evaluacion_documento as oed on ofe.id = oed.oferta_id left join oferta_evaluacion_documento_historial as oedh on oed.documento_id = oedh.id_documento inner join usuarios u on oedh.id_usuario = u.id where ofe.id = ".$idOferta." group by oedh.id_historial order by oedh.fecha_aprobacion desc limit ".count($aprobaciones)));

            // Solo busca
            $totalAprobacionesCreadorEvento = DB::select(DB::raw("select odp.id as codigo_documento_proveedor, odp.id_usuario as codigo_usuario_proveedor, oed.resultado_evaluacion, oedh.id_historial, oedh.valoracion, oedh.id_usuario, od.obligatorio, o.usuario_creacion from oferta_documentos_oferentes as od inner join oferta_documentos_ofertascliente as odp on od.id = odp.id_documento_oferente inner join ofertas as o on o.id = od.oferta_id inner join oferta_participantes as op on o.id = op.id_oferta and odp.id_usuario = op.id_usuario inner join oferta_evaluacion_documento as oed on oed.documento_id = odp.id inner join oferta_evaluacion_documento_historial as oedh on oed.id = oedh.id_evaluacion where o.id = ".$idOferta." and op.estado = 'activo' and op.estado_participacion = 'ofe_enviada' and od.evaluable = 'si' and oedh.tipo_usuario_registra = 'aprobador' and oedh.id_usuario = o.usuario_creacion group by oed.documento_id order by codigo_usuario_proveedor asc "));

                    $contadorAprobacionesUsuarioCreador = 0;
            foreach ($totalAprobacionesCreadorEvento as $totalAprobacionCreador){
                if ($totalAprobacionCreador->valoracion == 'aprobado'){
                    $contadorAprobacionesUsuarioCreador++;
                }
            }
            // dd($ultimasAprobaciones);
            $contadorUsuarioCreadorAprobadas = 0;
            $contadorUsuarioCreadorRechazadas = 0;
            foreach ($ultimasAprobaciones as $ultimaAprobacion){
                if ($ultimaAprobacion->valoracion == 'aprobado' && $ultimaAprobacion->tipo_usuario_registra == 'aprobador' && $ultimaAprobacion->usuario_evaluador == $ofertaRes->usuario_creacion) {
                    $contadorUsuarioCreadorAprobadas++;
                } elseif ($ultimaAprobacion->valoracion  == 'rechazado' && $ultimaAprobacion->tipo_usuario_registra == 'aprobador' && $ultimaAprobacion->usuario_evaluador == $ofertaRes->usuario_creacion) {
                    $contadorUsuarioCreadorRechazadas++;
                }
            }

            $AprobacionAdicional= false;
            foreach ($logOfertas as $logOferta){
                if (preg_match('/AprobacionAdicional/', $logOferta->contenido)){
                    // Existe una aprobación adicional generada
                    $AprobacionAdicional = true;
                }
            }
            dd($contadorAprobacionesAprobadas, $contadorUsuarioCreadorAprobadas, $AprobacionAdicional);


            if (count($aprobaciones) == $contadorAprobacionesUsuarioCreador && $AprobacionAdicional == false){
                // Si las aprobaciones son de el creador del evento y son todas aprobadas
                // CÓDIGO 11
                $this->modelo_ofertas_accionesWs->notificacionValidacionEvaluacionTecnicaTerpel($idOferta, "Validado", $seq_id, $ofertaRes->solpeds_relacionadas);

            }elseif(count($aprobaciones) == ($contadorUsuarioCreadorAprobadas + $contadorUsuarioCreadorRechazadas) && $contadorUsuarioCreadorRechazadas > 0){
                // CÓDIGO 11
                $this->modelo_ofertas_accionesWs->notificacionValidacionEvaluacionTecnicaTerpel($idOferta, "Rechazado", $seq_id, $ofertaRes->solpeds_relacionadas);
            }elseif ( $AprobacionAdicional == true && count($aprobaciones) == $contadorAprobacionesAprobadas ){
// Si el ultimo evento registrado en el log es una AprobacionAdicional
                $this->modelo_ofertas_accionesWs->notificacionAprobacionEvaluacionTecnicaTerpel($idOferta, 'Aprobada', $seq_id, $ofertaRes->solpeds_relacionadas);

            }elseif($AprobacionAdicional == true && $contadorAprobacionesRechazadas > 0 ){

                $this->modelo_ofertas_accionesWs->notificacionAprobacionEvaluacionTecnicaTerpel($idOferta, 'Rechazado', $seq_id, $ofertaRes->solpeds_relacionadas);
            }
            elseif (count($aprobaciones) == $contadorAprobacionesAprobadas && $AprobacionAdicional == false){
                // Todas las aprobaciones fueron aprobadas y existen aprobadores
                $this->evaluacionOfertaTecnica($idOferta);
            }

            if($ultimasAprobaciones[0]->usuario_evaluador == $ofertaRes->usuario_creacion && $ultimasAprobaciones[0]->valoracion == 'rechazado'){
                // Falta simplificar verificar si existe una rechazada
                $this->modelo_ofertas_accionesWs->notificacionValidacionEvaluacionTecnicaTerpel($idOferta, "Rechazado", $seq_id, $ofertaRes->solpeds_relacionadas);
            }

        }

    }


     public function notificarAprobadorAdicionalAgregado ($idOferta){
         $ofertaRes= Oferta::select('*')->where('id', $idOferta)->first();
         if ($ofertaRes->estado = "EN EVALUACION"){
             $this->modelo_ofertas_accionesWs->notificacionValidacionEvaluacionTecnicaTerpel($idOferta, "AprobacionAdicional", $ofertaRes->seq_id, $ofertaRes->solpeds_relacionadas);
         }
     }

     public function evaluacionOfertaTecnica($idOferta){
             $ofertaRes= Oferta::select('seq_id', 'solpeds_relacionadas')->where('id', $idOferta)->first();
         $seq_id= $ofertaRes->seq_id;
         // * EJECUTAR AJUSTAR EVALUACIÓN TÉCNICA *// -> solo se envia el código del evento código 12
         $this->modelo_ofertas_accionesWs->notificacionAjustarEvaluacionTecnicaTerpel($idOferta, $seq_id, $ofertaRes->solpeds_relacionadas);
         //*  EJECUTAR  EVALUACIÓN OFERTA TÉCNICA *// -> solo se envia el código del evento código 10
         $this->modelo_ofertas_accionesWs->notificacionEvaluacionOfertaTecnicaTerpel($idOferta, $seq_id, $ofertaRes->solpeds_relacionadas);
    }

    public function notificarRechazoAprobacion($ofeObj){

    }



 }
 ?>
