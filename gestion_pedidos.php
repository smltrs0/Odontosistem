<?php

use Models\Ofertas\Oferta;
use Models\Proveedores\UsuariosProveedores;
use Traits\ConexionSOAPJDE;
use Models\Usuarios\Usuario;
use Traits\ViewsTemplateEmail;
use Models\Proveedores\Empresa;
use Models\Ofertas\OrdenPedidos;
use Models\Usuarios\UsuariosArea;
use Illuminate\Support\Facades\DB;
use Models\Ofertas\OrdenPedidosItems;
use Models\Maestras\MaestrasSubClientes;
use Models\Ofertas\OfertaAdjudicaciones;
use Models\Proveedores\ProveedoresAlqueria;
use Models\Ofertas\OrdenPedidosItemsCompania;
use Illuminate\Database\Capsule\Manager as Manager;

class modelo_gestion_pedidos{
	use ConexionSOAPJDE, ViewsTemplateEmail;

	public function __construct(){
		$this->intelcost = new intelcostClient();
		$this->modelo_usuario = new modelo_usuario();
		$this->modelo_pdf = new modelo_pdf();
		$this->modelo_proveedor = new modelo_proveedor();
		$this->modelo_flujos = new modelo_flujos_aprobacion();
	}

	public function obtenerListadoGestionDePedidos($empresaid, $pagina, $filtro){
		if(isset($empresaid) && !empty($empresaid) && isset($pagina) && !empty($pagina) && isset($filtro)){

			$filtro = !empty($filtro) ? '%'.$filtro.'%' : '';
			
			$itemsPerPag = 10;
			$parametrosTipos = '';
			$parametros = array();

			/*$sqlStm ="	SELECT COUNT(*) totalItems
						FROM ofertas O
						LEFT JOIN usuarios U
						ON O.duenio_oferta = U.id
						LEFT JOIN (SELECT * FROM orden_pedidos LIMIT 1) AS OP
						ON O.id = OP.cod_oferta
						WHERE O.id_cliente = ?
						AND O.estado = 'FINALIZADA'
						AND O.ronda = (
											SELECT MAX(ronda) FROM ofertas WHERE id_cliente = ?
											AND estado = 'FINALIZADA'";*/

			$sqlStm ="
				SELECT
					COUNT(*) totalItems
				FROM
					ofertas O
					LEFT JOIN usuarios U ON O.duenio_oferta = U.id
					LEFT JOIN orden_pedidos AS OP ON O.id = OP.cod_oferta
					INNER JOIN ( SELECT MAX( ronda ) as maximaRonda, MAX(id) as id FROM ofertas WHERE id_cliente = ? GROUP BY seq_id) AS RO ON (RO.id = O.id AND RO.maximaRonda = O.ronda) 
				WHERE
					O.id_cliente = ? 
					AND O.estado = 'FINALIZADA' 
					AND EXISTS (SELECT * FROM oferta_adjudicaciones as OA WHERE O.id = OA.id_oferta AND OA.estado = 'activo')
			";
					$parametros = array($empresaid, $empresaid);
					$parametrosTipos = 'ss';

			if(!empty($filtro)){
				$sqlStm .= " AND (O.seq_id LIKE ?
							OR O.objeto LIKE ?
							OR U.nombre LIKE ?)";

				array_push($parametros, $filtro, $filtro, $filtro);
				$parametrosTipos .= 'sss';
			}else{
				//$sqlStm .= ')';
			}

			$sqlStm .= "GROUP BY O.id";

			$error = 'Consultar total de items.';

			$responseTotal = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, $parametrosTipos, $parametros, $error);

			if($responseTotal->bool){
				$responseTotal = $responseTotal->msg->fetch_assoc();
				$responseTotal = $responseTotal['totalItems'];

				if($responseTotal > 0){

					$limitPag = $this->intelcost->paginacion_limit_inicio_fin($pagina, $itemsPerPag);

					/*$sqlStm ="	SELECT O.seq_id,
								O.id,
								O.objeto, 
								O.moneda, 
								U.nombre responsable,
								OP.cod_jde_confa idJDE,
								OP.id idOrdenCompra
								FROM ofertas O
								LEFT JOIN usuarios U
								ON O.duenio_oferta = U.id
								LEFT JOIN orden_pedidos AS OP
								ON O.id = OP.cod_oferta
								WHERE O.id_cliente = ?
								AND O.estado = 'FINALIZADA'
								AND O.ronda = (
													SELECT MAX(ronda) FROM ofertas WHERE id_cliente = ?
													AND estado = 'FINALIZADA'";*/
					
					$sqlStm ="
						SELECT
							O.seq_id,
							O.id,
							O.objeto,
							O.moneda,
							U.nombre responsable,
							OP.cod_jde_confa idJDE,
							OP.id idOrdenCompra,
							O.ronda 
						FROM
							ofertas O
							LEFT JOIN usuarios U ON O.duenio_oferta = U.id
							LEFT JOIN orden_pedidos AS OP ON O.id = OP.cod_oferta
							INNER JOIN ( SELECT MAX( ronda ) as maximaRonda, MAX(id) as id FROM ofertas WHERE id_cliente = ? GROUP BY seq_id) AS RO ON (RO.id = O.id AND RO.maximaRonda = O.ronda)
						WHERE
							O.id_cliente = ? 
							AND O.estado = 'FINALIZADA' 
							AND EXISTS (SELECT * FROM oferta_adjudicaciones as OA WHERE O.id = OA.id_oferta AND OA.estado = 'activo')
					";

					$parametros = array($empresaid, $empresaid);
					$parametrosTipos = 'ss';

					if(!empty($filtro)){
						$sqlStm .= " AND (O.seq_id LIKE ?
									OR O.objeto LIKE ?
									OR U.nombre LIKE ?)";

						array_push($parametros, $filtro, $filtro, $filtro);
						$parametrosTipos .= 'sss';
					}else{
						//$sqlStm .= ')';
					}

					$sqlStm .= "GROUP BY O.id";

					$sqlStm .= " LIMIT ".$limitPag['inicio'].", ".$limitPag['fin'];

					$error = 'Consultar items paginados';
					
					$responseItems = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, $parametrosTipos, $parametros, $error);

					if($responseItems->bool){
						$responseItems = $responseItems->msg->fetch_all(MYSQLI_ASSOC);
						$totalPages = ceil((int)$responseTotal / $itemsPerPag);
						$finalData = array(
							'currentPage' 	=> $pagina,
							'totalPages' 	=> $totalPages,
							'items'			=> $responseItems
						);

						$this->intelcost->response->bool = true;
						$this->intelcost->response->msg = $finalData;
					}else{
						$this->intelcost->response->bool = false;
						$this->intelcost->response->msg = 'Error al consultar los items paginados.';
					}

				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = 'No se encontraron ordenes de compra.';
				}


			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'Error al consultar el total de items.';
			}
		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para obtener el listado.';
		}

		return $this->intelcost->response;
	}

	public function cargarInformacionGeneralEvento($eventoId){
		if(isset($eventoId) && !empty($eventoId)){

			$db_cliente = env('DB_NAME_DATABASE_CLIENT');
			$db_proveedores = env('DB_NAME_DATABASE_PROVIDER');

			$sqlStm = "SELECT O.seq_id secuencia, 
						O.objeto,
						A.clasedesc actividad,
						O.descripcion,
						U.nombre responsable,
						O.moneda,
						O.presupuesto,
						M.codigo codCentroDeCosto,
						M.nombre nombreCentroDeCosto
						FROM `$db_cliente`.`ofertas` O
						LEFT JOIN `$db_cliente`.`usuarios` U
						ON O.duenio_oferta = U.id
						LEFT JOIN  `$db_proveedores`.`mstactividades` A
						ON O.actividad = A.producid
						LEFT JOIN `$db_cliente`.`maestra_centros_de_costo` M
						ON O.id_area = M.id
						WHERE O.id = ?
						LIMIT 1";

			$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($eventoId), 'Consultando información general del evento.');

			if($response->bool){
				if($response->msg->num_rows > 0){
					$response = $response->msg->fetch_assoc();

					$this->intelcost->response->bool = true;
					$this->intelcost->response->msg = $response;
				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = 'No se encontró información del evento.';
				}
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'Error al consultar la información general del evento.';	
			}
		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para obtener el listado.';
		}

		return $this->intelcost->response;
	}

	public function cargarInformacionLotes($eventoId){
		if(isset($eventoId) && !empty($eventoId)){

			$sqlStm = "	SELECT OL.id_lote,
						OL.nombre_lote,
						OL.cod_compania,
						C.nombre sobre,
						OLI.id_item idItem,
						OLIDA.numero_linea posicion,
						OLI.descripcion,
						OLIDA.numero_articulo codigoArticulo,
						MUM.um medida_min,
						MUM.medida,
						OLI.cantidad,
						OLIDA.obligatorio,
						OLIDA.impuesto,
						OLIDA.referencia
						FROM oferta_lotes OL
						LEFT JOIN capitulos C
						ON OL.cod_sobre = C.id 
						LEFT JOIN oferta_lotes_items OLI
						ON OL.id_lote = OLI.cod_lote
						LEFT JOIN oferta_lotes_items_datos_adicionales OLIDA
						ON (OL.id_lote = OLIDA.cod_lote
						AND OLI.id_item = OLIDA.cod_item)
						LEFT JOIN mst_unidad_medidas MUM
						ON OLI.cod_unidad_medida = MUM.id_medida
						WHERE OL.cod_oferta = ?
						AND OL.estado = 1
						ORDER BY OLIDA.numero_linea ASC";

			$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($eventoId), 'Consultando información de los lotes.');

			if($response->bool){
				if($response->msg->num_rows > 0){
					$response = $response->msg->fetch_all(MYSQLI_ASSOC);

					$this->intelcost->response->bool = true;
					$this->intelcost->response->msg = $response;
				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = 'No se consiguió información de los lotes.';
				}
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'Error al realizar la consulta.';
			}

		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Datos insuficientes para consultar la información de los lotes.';
		}

		return $this->intelcost->response;
	}

	public function cargarAdjudicaciones($eventoId, $lotes){
		if(isset($eventoId) && !empty($eventoId) && isset($lotes) && !empty($lotes)){

			$db_cliente = env('DB_NAME_DATABASE_CLIENT');
			$db_proveedores = env('DB_NAME_DATABASE_PROVIDER');
			$sqlAdicional = '';
			if($_SESSION['empresaid'] == 9 || $_SESSION['empresaid'] == 20){
				$sqlAdicional = '
					OA.terminos_reajuste,
					OA.dias_revision as numero_dias_revision,
					OA.validez_contrato as numero_validez_contrato,
					OA.tasa_cambio,
					OA.carta_adjudicacion,
				';
			}

			$sqlStm = "	SELECT OA.id idAdjudicacion,
						UserEmpresa.usridxxx idUsuarioEmpresa,
						UserEmpresa.usrnomxx usuarioEmpresa,
						Empresa.id_empresa idEmpresa,
						Empresa.nitempxx NitEmpresa,
						Empresa.razonxxx empresa,
						OA.moneda,
						OA.valor,
						OA.porcentaje,
						OA.observacion,
						OA.fecha_creacion fechaCreacion,
						$sqlAdicional
						OP.id idOrdenCompra,
						OP.cod_jde_confa codJDE,
						OP.valor_adjudicado valorTotalAdjudicado,
						CASE
							WHEN OP.tipo_orden = '' THEN NULL
							ELSE OP.tipo_orden
						END tipoOrden,
					
						CASE
							WHEN OP.descripcion = '' THEN NULL
							ELSE OP.descripcion
						END descripcion,

						User.id idUsuarioCreacion,
						User.nombre usuarioCreacion
						FROM `$db_cliente`.`oferta_adjudicaciones` OA
						LEFT JOIN `$db_proveedores`.`sys00001` UserEmpresa
						ON OA.id_usuario = UserEmpresa.usridxxx
						LEFT JOIN `$db_proveedores`.`_0002103` Empresa
						ON UserEmpresa.cod_empresa = Empresa.id_empresa
						LEFT JOIN `$db_cliente`.`usuarios` User
						ON OA.usuario_creacion = User.id
						LEFT JOIN `$db_cliente`.`orden_pedidos` OP
						ON OP.cod_adjudicacion = OA.id
						WHERE id_oferta = ?";

			$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($eventoId), 'Consultando información de las adjudicaciones.');

			if($response->bool){
				if($response->msg->num_rows > 0){
					$response = $response->msg->fetch_all(MYSQLI_ASSOC);
					foreach ($response as $keyAdjudicacion => $adjudicacion){
						// $adjudicacion['valorTotalAdjudicado'] = (float)$adjudicacion['valorTotalAdjudicado'];
						foreach ($lotes as $keyLote => $lote){
							foreach ($lote->items as $keyItem => $item){

								$sqlStm = "	SELECT OLI.id_item,
											OLI.descripcion, 
											OLIDA.numero_linea posicion,
											OLIDA.numero_articulo codigoArticulo,
											OLIDA.obligatorio,
											OLIDA.impuesto,
											OLIDA.referencia,
											MUM.um medida_min,
											MUM.medida,
											OLI.cantidad,
											OLIP.valor,
											OPI.id as id_orden_item,

											CASE
												WHEN OPI.adjudicado = 'SI'  THEN true
												ELSE false
											END adjudicado,

											CASE
												WHEN OPI.cantidad > 0 THEN OPI.cantidad
												ELSE null 
											END cantidadAdjudicada

											FROM oferta_lotes_items_proveedores OLIP
											LEFT JOIN oferta_lotes_items OLI
											ON OLIP.cod_item = OLI.id_item
											LEFT JOIN oferta_lotes_items_datos_adicionales OLIDA
											ON OLI.id_item = OLIDA.cod_item
											LEFT JOIN mst_unidad_medidas MUM
											ON OLI.cod_unidad_medida = MUM.id_medida
											LEFT JOIN orden_pedidos_items OPI
											ON (OPI.cod_item = OLIP.cod_item
											AND OPI.cod_orden_pedidos = ?)
											WHERE OLIP.cod_item = ?
											AND (OLIP.usuario_creacion = ?
											OR OLIP.usuario_actualizacion = ?)
											AND OLIP.estado = 1
											ORDER BY OLIDA.numero_linea ASC
											LIMIT 1";

								$parametrosType = 'ssss';
								$parametros = array($adjudicacion['idOrdenCompra'], $item->id_item, $adjudicacion['idUsuarioEmpresa'], $adjudicacion['idUsuarioEmpresa']);

								$responseItem = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, $parametrosType, $parametros, 'Consultando información de los items de las adjudicaciones.');
								if($responseItem->bool){
									if($responseItem->msg->num_rows > 0){
										$responseItem = $responseItem->msg->fetch_assoc();
										if($_SESSION['empresaid'] == '9'){
											$adicionalItem = OrdenPedidosItemsCompania::with('infoCompania')->where('id_item_orden', $responseItem['id_orden_item'])->get();
										}else{
											$adicionalItem = [];
										}

										$responseItem['adicional'] = $adicionalItem;

										if(isset($response[$keyAdjudicacion]['lotes'])){
											$response[$keyAdjudicacion]['lotes'][$keyLote]['items'][$keyItem] = $responseItem;
										}else{
											$response[$keyAdjudicacion]['lotes'] = array();
											$response[$keyAdjudicacion]['lotes'][$keyLote] = array('idLote' => $lote->id_lote, 'items' => array());
											$response[$keyAdjudicacion]['lotes'][$keyLote]['items'][$keyItem] = $responseItem;
										}

									}else{
										$this->intelcost->response->bool = false;
										$this->intelcost->response->msg = 'No se encontraron algunos items de las adjudicaciones asociadas a esta oferta.';
									}
								}else{
									$this->intelcost->response->bool = false;
									$this->intelcost->response->msg = 'Error al consultar los items de las adjudicaciones.';
								}
							}
						}
					}

					$this->intelcost->response->bool = true;
					$this->intelcost->response->msg = $response;
				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = 'No se encontraron adjudicaciones asociadas a esta oferta.';
				}
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'Error al consultar las adjudicaciones.';
			}

		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Datos insuficientes para consultar las adjudicaciones.';
		}

		return $this->intelcost->response;
	}

	// Solo es necesario llamar este metodo para conseguir el token jde
	// private function generarConexionJDE(){
	// 	// Conexion y procesos en JDE

	// 	$ruta_ambiente = array(
	// 		['ruta'=>'https://app.confa.co:8687/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'local'],
 //               	['ruta'=>'https://app.confa.co:8687/JDEdwardsIntelCost/TerceroProveedorJdeWSService?wsdl','user'=>'','password' => '','ambiente' =>'calidad']

	// 	);

	// 	try {

	// 		// Se crea la conexion a JDE
	// 		$JDEdwards = $this->iniciarConexionJDE(new ConnectionSAP, $ruta_ambiente);

	// 		// Se solicita el Token a JDE con los parametros de autenticacion brindados
	// 		if(!isset($_SESSION['token-jde']) || empty($_SESSION['token-jde'])){
	// 		// unset($_SESSION['token-jde']);
	// 			$this->obtenerTokenJDE($JDEdwards, 'YmQ2YmU2YzkxYzRmNmM0ZUFEX0FQUCoyMDIwJA==', 'SmRlX2ludGVsQ29uZmE3YjdhNTNlMjM5NDAwYTEz');
	// 		}

	// 	} catch (Exception $e) {
	// 		$resp->bool= false;
	// 		$resp->msg = 'No fue posible realizar la comunicación con JDE.';
	// 		return $resp;            
	// 	}

	// 	return $JDEdwards;
	// }

	// private function obtenerTokenJDE($conexionJDE, $user, $password){
	// 	$this->obtenerTokenServicioJDE($conexionJDE, ['user' => $user, 'password' => $password]);
	// }

	public function guardadoParcial($idOferta, $adjudicaciones, $cod_jde = null, $guardadoCompleto = null){
		// dd($guardadoCompleto == true);
		// dd($idOferta, $adjudicaciones, $cod_jde);
		if(isset($idOferta) && !empty($idOferta) && isset($adjudicaciones) && !empty($adjudicaciones)){

			$sqlStm = "	SELECT *
						FROM orden_pedidos
						WHERE cod_oferta = ?";

		$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($idOferta), 'Consultar ordenes de compra previas.');

		if($response->bool){
			if($response->msg->num_rows > 0){
				// Actualizar parcialmente ordenes existente

				$response = $response->msg->fetch_all(MYSQLI_ASSOC);

				$adjudicaciones = json_decode($adjudicaciones, true);
				foreach ($adjudicaciones as $keyAdjudicacion => $adjudicacion){
					if($_SESSION['empresaid'] == 9 || $_SESSION['empresaid'] == 20){
						$ofertaAdjudicada = OfertaAdjudicaciones::where('id', $adjudicacion['idAdjudicacion'])
							->first();
						
						$ofertaAdjudicada->valor = $adjudicacion['valorTotalAdjudicado'];
						$ofertaAdjudicada->moneda = $adjudicacion['moneda'];
						$ofertaAdjudicada->observacion = $adjudicacion['observacion'];
						$ofertaAdjudicada->carta_adjudicacion = $adjudicacion['carta_adjudicacion'];
						$ofertaAdjudicada->porcentaje = $adjudicacion['porcentaje'];
						$ofertaAdjudicada->timestamps = false;
						$ofertaAdjudicada->terminos_reajuste = $adjudicacion['terminos_reajuste'];
						$ofertaAdjudicada->dias_revision = $adjudicacion['numero_dias_revision'];
						$ofertaAdjudicada->validez_contrato = $adjudicacion['numero_validez_contrato'];
						$ofertaAdjudicada->tasa_cambio = $adjudicacion['tasa_cambio'];
						$ofertaAdjudicada->save();
					}

					// dd($adjudicacion['tipoOrden']);
					// dd($adjudicacion['valorTotalAdjudicado']);
					if(is_null($cod_jde)){

						$sqlStm = "	UPDATE orden_pedidos
									SET valor_adjudicado = ?, tipo_orden = ?, descripcion =?
									WHERE cod_adjudicacion = ?";

						$typeParams = 'ssss';
						$params = array((string)$adjudicacion['valorTotalAdjudicado'], $adjudicacion['tipoOrden'], $adjudicacion['descripcion'], $adjudicacion['idAdjudicacion']);

					}else{

						$sqlStm = "	UPDATE orden_pedidos
									SET valor_adjudicado = ?, cod_jde_confa = ?, tipo_orden = ?, descripcion = ?
									WHERE cod_adjudicacion = ?";

						$typeParams = 'sssss';
						$params = array((string)$adjudicacion['valorTotalAdjudicado'], $cod_jde, $adjudicacion['tipoOrden'], $adjudicacion['descripcion'], $adjudicacion['idAdjudicacion']);

					}

					$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'UPDATE', true, $typeParams, $params, 'Actualizar datos orden de compra');

					if(!$response->bool){
						$this->intelcost->response->bool = false;
						$this->intelcost->response->msg = 'Error al actualizar las ordenes de compra para esta oferta.';
						return $this->intelcost->response;
					}

					// Se actualizan los datos para esta orden de compra
					$sqlStm = "SELECT id FROM orden_pedidos WHERE cod_adjudicacion = ? LIMIT 1";
					$typeParams = 's';
					$params = array($adjudicacion['idAdjudicacion']);

					$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, $typeParams, $params, 'Recuperar el ID de la orden actualizada');

					if($response->bool){
						if($response->msg->num_rows > 0){

							$response = $response->msg->fetch_assoc();
							$idOrdenCompra = $response['id'];

							foreach ($adjudicacion['lotes'] as $keyLote => $lote) {
								$idCurrentLote = $lote['idLote'];

								foreach ($lote['items'] as $keyItem => $item){

									$sqlStm = "	UPDATE orden_pedidos_items
												SET adjudicado = ?, cantidad = ?, usuario_creacion = ?
												WHERE cod_orden_pedidos = ? AND cod_item = ?";

									$typeParams = 'sssss';
									$params = array($item['adjudicado'] == true && $item['cantidadAdjudicada'] > 0 ? 'SI' : 'NO', $item['cantidadAdjudicada'], $_SESSION['idusuario'], $idOrdenCompra, $item['id_item']);

									$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'UPDATE', true, $typeParams, $params, 'Actualizar datos de los items de la orden de compra');

									if(!$response->bool){
										$this->intelcost->response->bool = false;
										$this->intelcost->response->msg = 'Error al actualizar los items de las ordenes de compra para esta oferta.';
										return $this->intelcost->response;
									}

									if($_SESSION['empresaid'] == 9){
										if(isset($item['companiasSeleccionadas'])){
											foreach ($item['companiasSeleccionadas'] as $key => $compania) {
												$ordenItem = OrdenPedidosItems::where('cod_orden_pedidos', $idOrdenCompra)->where('cod_item', $item['id_item'])->first();
												$datoAdicionalItemOrden = [
													'id_item_orden' => $ordenItem->id,
													'id_compania' => $compania['id'],
													'tiempo_entrega' => isset($item['fechaEntrega']) ? $item['fechaEntrega'] : '',
													'lote_minimo' => isset($item['loteMinimo']) ? $item['loteMinimo'] : '',
													'cantidad' => isset($item['loteMinimoCantidad']) ? $item['loteMinimoCantidad'] : '',
													'cantidad_seleccionada_compania' => isset($compania['cantidadSeleccionada']) ? $compania['cantidadSeleccionada'] : '', 
													'id_user_updated' => $_SESSION['idusuario'],
												];

												$datoAdicionalItemOrdenCondicional = [
													'id_item_orden' => $ordenItem->id,
													'id_compania' => $compania['id'],
												];

												OrdenPedidosItemsCompania::updateOrCreate($datoAdicionalItemOrdenCondicional, $datoAdicionalItemOrden);
											}
										}
									}
								}
							}

							$this->intelcost->response->bool = true;
							$this->intelcost->response->msg = 'Se han actualizado con éxito las ordenes de compra';

						}else{
							$this->intelcost->response->bool = false;
							$this->intelcost->response->msg = 'No fue posible actualizar la orden de compra.';
							return $this->intelcost->response;
						}
					}else{
						$this->intelcost->response->bool = false;
						$this->intelcost->response->msg = 'Error en la consulta retorno de la orden actualizada.';
						return $this->intelcost->response;
					}

				}
				

			}else{
				// Guardar parcialmente ordenes nuevas

				$adjudicaciones = json_decode($adjudicaciones, true);

				foreach ($adjudicaciones as $keyAdjudicacion => $adjudicacion){

					if(is_null($cod_jde)){
						$sqlStm = "	INSERT INTO orden_pedidos
									(cod_oferta,
									 cod_adjudicacion,
									 cod_jde_confa,
									 usuario_creador,
									 valor_adjudicado,
									 tipo_orden,
									 descripcion)
									 VALUES(?,?,NULL,?,?,?,?)";

						$typeParams = 'ssssss';
						$params = array($idOferta, $adjudicacion['idAdjudicacion'], $_SESSION['idusuario'], (string)$adjudicacion['valorTotalAdjudicado'], $adjudicacion['tipoOrden'], $adjudicacion['descripcion']);

					}else{
						$sqlStm = "	INSERT INTO orden_pedidos
									(cod_oferta,
									 cod_adjudicacion,
									 cod_jde_confa,
									 usuario_creador,
									 valor_adjudicado,
									 tipo_orden,
									 descripcion)
									 VALUES(?,?,?,?,?,?,?)";

						$typeParams = 'sssssss';

						$params = array($idOferta, $adjudicacion['idAdjudicacion'], $cod_jde, $_SESSION['idusuario'], (string)$adjudicacion['valorTotalAdjudicado'], $adjudicacion['tipoOrden'], $adjudicacion['descripcion']);
					}

					$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'INSERT', true, $typeParams, $params, 'Guardado de orden de compra');

					if(!$response->bool){
						$this->intelcost->response->bool = false;
						$this->intelcost->response->msg = 'Error al guardar ordenes de compra para esta oferta.';
						return $this->intelcost->response;
					}

					// Se guardan los datos de los items de esta adjudicacion

					$idOrden = $response->msg;

					foreach ($adjudicacion['lotes'] as $keyLote => $lote) {
						$idCurrentLote = $lote['idLote'];

						foreach ($lote['items'] as $keyItem => $item){

							$sqlStm = "	INSERT INTO orden_pedidos_items
										(cod_orden_pedidos,
										 cod_item,
										 cod_lote,
										 adjudicado,
										 cantidad,
										 usuario_creacion)
										 VALUES(?,?,?,?,?,?)";

							$typeParams = 'ssssss';
							$params = array($idOrden, $item['id_item'], $idCurrentLote, $item['adjudicado'] == true && $item['cantidadAdjudicada'] > 0 ? 'SI' : 'NO', $item['cantidadAdjudicada'], $_SESSION['idusuario']);

							$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'INSERT', true, $typeParams, $params, 'Guardado de orden de compra');
							if(!$response->bool){
								$this->intelcost->response->bool = false;
								$this->intelcost->response->msg = 'Error al guardar los items de las ordenes de compra para esta oferta.';
								return $this->intelcost->response;
							}

							if($_SESSION['empresaid'] == 9){
								if(isset($item['companiasSeleccionadas'])){
									foreach ($item['companiasSeleccionadas'] as $key => $compania) {
										$datoAdicionalItemOrden = new OrdenPedidosItemsCompania;
										$datoAdicionalItemOrden->id_item_orden = $response->msg;
										$datoAdicionalItemOrden->id_compania = $compania['id'];
										$datoAdicionalItemOrden->tiempo_entrega = isset($item['fechaEntrega']) ? $item['fechaEntrega'] : '';
										$datoAdicionalItemOrden->lote_minimo = isset($item['loteMinimo']) ? $item['loteMinimo'] : '';
										$datoAdicionalItemOrden->cantidad = isset($item['loteMinimoCantidad']) ? $item['loteMinimoCantidad'] : '';
										$datoAdicionalItemOrden->id_user_updated = $_SESSION['idusuario'];
										$datoAdicionalItemOrden->save();
									}
								}
							}
						}
					}

				}

				$this->intelcost->response->bool = true;
				$this->intelcost->response->msg = 'Se ha guardado con éxito las ordenes de compra';
			}
		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Error al consultar ordenes de compra previas para esta oferta.';
		}

		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para el guardado parcial de la orden.';
		}

		return $this->intelcost->response;
	}

	public function crearOrdenCompra($adjudicaciones, $idsJDEProvs, $lotes, $codUser, $idOferta){

		if(isset($adjudicaciones) && !empty($adjudicaciones) && isset($idsJDEProvs) && !empty($idsJDEProvs) && isset($codUser) && !empty($codUser)){
			
			$soapClient = new soapConfa();

			$adjudicaciones = json_decode($adjudicaciones);
			$lotes = json_decode($lotes);
			$numeroOJ  = $lotes[0]->nombre_lote;
			$companiaOjJDE = $lotes[0]->cod_compania;
			$idsJDEProvs = json_decode($idsJDEProvs, true);
			$idsJDEProvs = $idsJDEProvs['all'];
			$codsJDE = array();

			foreach ($adjudicaciones as $keyAdjudicacion => $adjudicacion){
				$detalleOrdenCompra = array();
				$encabezadoOrdenCompra = array('codigoAprobador' 		=> $codUser,
													 'codigoComprador'		=> $codUser,
													 'codigoProveedor'		=> $idsJDEProvs[$adjudicacion->idEmpresa],
													 'compañiaOriginal'		=> $companiaOjJDE,
													 'descripcion'			=> $adjudicacion->descripcion,
													 'numeroOJ'			=> $numeroOJ,
													 'tipoOrden'				=> $adjudicacion->tipoOrden);

				foreach ($adjudicacion->lotes as $keyLote => $lote){
					foreach ($lote->items as $keyItem => $item) {
						
						if((bool)$item->adjudicado && $item->cantidadAdjudicada > 0){

							array_push($detalleOrdenCompra, array(	'cantidadProducto' 		=> (double)$item->cantidadAdjudicada,
																		'codigoProducto'		=> (int)$item->codigoArticulo,
																		'costoUnitario'			=> (double)$item->valor,
																		'descripcionProducto'	=> $item->descripcion,
																		'impuestoAplicado'		=> $item->impuesto,
																		'numeroLineaOJ'		=> (double)$item->posicion,
																		'referencia'				=> $item->referencia,
																		'valorTotal'				=> $item->adjudicado == 0 ? 0 : (int)$item->cantidadAdjudicada * (int)$item->valor));

						}


					}
				}

				$parametros = array('orden_compra' => array('encabezadoOrdenCompra' => $encabezadoOrdenCompra, 'detalleOrdenCompra' => $detalleOrdenCompra));

				$response = $soapClient->crearOrdenDeCompraJDE($parametros);
				// dd($response);

				if($response->bool){

					$codOrdenCompraJDE = $response->msg;
					$codsJDE[$adjudicacion->idEmpresa] = array('provCodJDE' => $idsJDEProvs[$adjudicacion->idEmpresa], 'ordenCodJDE' => $codOrdenCompraJDE);
					$result = $this->guardadoParcial($idOferta, json_encode(array($adjudicacion)), $codOrdenCompraJDE);

					if(!$result->bool){
						$this->intelcost->response->bool = false;
						$this->intelcost->response->msg = 'Se ha producido un error al momento de crear la orden de compra';
						return $this->intelcost->response;
					}
					elseif($result->bool == true) {

                        $oferta = Oferta::select('*')->where('id', '=', $idOferta)->first();

                        $actividades = Manager::table('mst_actividades_confa')
                            ->leftjoin('mst_actividades_confa_links_pivot', 'mst_actividades_confa.id', '=', 'mst_actividades_confa_links_pivot.mst_actividades_confa_id')
                            ->leftjoin('mst_actividades_confa_links', 'mst_actividades_confa_links_pivot.mst_links_id', '=', 'mst_actividades_confa_links.id')
                            ->where('mst_actividades_confa.idProductoActividad', '=', $oferta->actividad)->get();

                        if (count($actividades) > 0) {
                            $actividadesList = '';
                            foreach ($actividades as $actividad) {
                                $actividadesList .= $actividad->link;
                            }

                            $html = "<ul>" . $actividadesList . "</ul>";
                            $asunto = '';
                            foreach ($adjudicaciones as $keyAdjudicacion2 => $adjudicacion2) {
                                $proveedor = UsuariosProveedores::select('*')->where('usridxxx', '=', $adjudicacion2->idUsuarioEmpresa)->first();
                                $this->intelcost->sendEmail($proveedor->usrmailx, $html, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"]);
                            }
                        }
//                        dd($adjudicaciones, $oferta, $actividades, $idsJDEProvs);
                        }
                        $this->intelcost->response->bool = true;
                        $this->intelcost->response->msg = 'Se ha creado la orden de compra y se ha notificado a los oferentes';



                    }else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = $response->msg;
					return $this->intelcost->response;
				}				
			}

			$this->intelcost->response->bool = true;
			$this->intelcost->response->msg = 'Ordenes de compra creadas con éxito en JDE';
			$this->intelcost->response->codsJDE = $codsJDE;


		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parámetros insuficientes para la creación de la orden de pedidos.';
		}

		return $this->intelcost->response;
	}

	public function verifyProvidersStatus($ids){
		if(isset($ids) && !empty($ids)){

			$soapClient = new soapConfa();

			foreach ($ids['all'] as $key => $value) {
				$response = $soapClient->consultarEstadoTerceroJDE(array('codigoJde' => $value));
				if($response->msg == 'INHABILITADO'){
					$ids['inhabilitados'][$key] = $value; 
				}
			}

			$this->intelcost->response->bool = true;
			$this->intelcost->response->msg = $ids;

		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parámetros insuficientes para la verificación de los proveedores.';
		}

		return $this->intelcost->response;
	}

	public function generarCodigoOrdenCompra(){
		$empresa = isset($_SESSION['empresaid']) && $_SESSION['empresaid'] <= 9 ? "0".$_SESSION['empresaid'] : null;
		$contador = OrdenPedidos::where('cod_jde_confa', 'like', '%$empresa')->count()+1;
		$codigo = "5".str_pad($contador, 4, '0', STR_PAD_LEFT).$empresa;
		return $codigo;
	}

	public function crearFlujoAprobacionOcensaOFinalizacion($request){
		/**
		 * Consulta de la oferta, cambio de estado y cambio de estado de la orden.
		 */
		$idOferta = $request->idOferta;
		$oferta = Oferta::find($idOferta);
		if($oferta->require_flujo == "1" && $request->aprobar == null){
			$flujo = true;
			$obtenerEmpresasAdjudicadas = OfertaAdjudicaciones::where('id_oferta', $idOferta)->where('valor', '>', '0')->get();
			/**
			 * Asignar flujo de aprobación
			 */
			$valorTotalEnPesos = 0;
			$valorTotalEnDolares = 0;
			$nivel = 0;
			$salarioMinimo = $this->intelcost->obtenerSalarioMinimo('COP');
			foreach ($obtenerEmpresasAdjudicadas as $key => $empresaAdjudicada) {
				if($empresaAdjudicada->moneda == 'COP'){
					$valorTotalEnPesos += $empresaAdjudicada->valor;
					$valorTotalEnDolares += $empresaAdjudicada->valor / 3809.60;
				}

				if($empresaAdjudicada->moneda == 'USD'){
					$valorTotalEnDolares += $empresaAdjudicada->valor / $empresaAdjudicada->tasa_cambio;
					$valorTotalEnPesos += $empresaAdjudicada->valor;
				}

			}

			$cantidadSalariosMinimos = $valorTotalEnPesos / $salarioMinimo->msg['valor'];

			if($cantidadSalariosMinimos > 0 && $cantidadSalariosMinimos <= 1500){
				$nivel = 1;
			}

			if($cantidadSalariosMinimos >= 1501){
				$nivel = 2;
			}

			if($valorTotalEnDolares >= 5000000){
				$nivel = 3;
			}

			switch($nivel){
                case "1":
                        //Director del área usuaria
						$id_paso = 78;
						$datosAdicionalesOferta = $oferta->infoAdicionalesOferta;
						$area_gerencia = json_decode($datosAdicionalesOferta->otros);
						$usuarioArea = UsuariosArea::where('id', $area_gerencia->area->id)->first();
                        $usuarios = Usuario::where('id', $usuarioArea->id_jefe)->get();
                        $arrayUsuarios = $usuarios->map(function($usuario) use ($id_paso){
                            return $usuario->id;
						});
						
                    break;
                case "2":
                        //Comité de Abastecimiento
                        $id_paso = 78;
                        $usuarios = Usuario::where('id_perfil', 87)
                                            ->get();
                        $arrayUsuarios = $usuarios->map(function($usuario) use ($id_paso){
                            return $usuario->id;
                        });
                    break;
                case "3":
                        //Junta directiva
                        $id_paso = 78;
                        $usuarios = Usuario::where('id_perfil', 87)
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

            $this->modelo_flujos->cambiarEstadoAprobacionesEliminadas($idOferta, 5, $id_paso);
            $respuesta_flujo = $this->modelo_flujos->cargarPerfilesAprobadoresRequeridos(5, $idOferta, array($arrayUsuarios));

		}else{
			$flujo = false;
			$oferta->estado =  "FINALIZADA";
			$oferta->timestamps = false;
			$oferta->save();
	
			$ordenPedido = OrdenPedidos::where('cod_oferta', $idOferta)->first();
			$ordenPedido->cod_jde_confa = $codigo = $this->generarCodigoOrdenCompra();
			$ordenPedido->timestamps = false;
			$ordenPedido->save();
			
			(new modelo_acciones_oferta())->enviarEmailAdjudicacion($idOferta);
			(new modelo_acciones_oferta())->enviarEmailAgradecimientos($idOferta, 'adjudicada');
		}

		$this->intelcost->response->bool = true;
		$this->intelcost->response->msg  = "se ha generado el flujo de aprobación";
		$this->intelcost->response->flujo = $flujo;
		return $this->intelcost->response;
	}

	public function crearOrdenAlqueria($request){
		/**
		 * Consulta de la oferta, cambio de estado y cambio de estado de la orden.
		 */
		$idOferta = $request->idOferta;
		
		$oferta = Oferta::find($idOferta);
		$oferta->estado =  "FINALIZADA";
		$oferta->timestamps = false;
		$oferta->save();

		$ordenPedido = OrdenPedidos::where('cod_oferta', $idOferta)->first();
		$ordenPedido->cod_jde_confa = $codigo = $this->generarCodigoOrdenCompra();
		$ordenPedido->timestamps = false;
		$ordenPedido->save();
		/**
		 * Consultar empresas que no tengan el system21 y ...
		 */

		$empresas = $request->idEmpresas;
		$usuariosPerfilContabilidad = Usuario::where('id_perfil', 132)->where('empresa_id', $_SESSION['empresaid'])->get();
		$proveedores = [];
		$subCompaniasNoEncontradas = [];
		foreach ($empresas as $key => $empresa) {
			$subclientesNoEncontrados = [];
			$proveedorEncontrado = Empresa::where('id_empresa', $empresa['idEmpresa'])
										->with(['infoErpSubcliente' => function($query) use ($empresa){
											$query->when($empresa['companias'], function($query) use ($empresa){
												$query->whereIn('subcliente', $empresa['companias'])
													->where('estado', 'activo')
													->where(function($query){
														$query->where('cod_erp', '!=', '')
															->whereNotNull('cod_erp');
												});
 											});
										}])
										->first();
			array_push($proveedores, $proveedorEncontrado);
			if(count($proveedorEncontrado->infoErpSubcliente) == 0 || count($empresa['companias']) != count($proveedorEncontrado->infoErpSubcliente)){
				foreach ($empresa['companias'] as $key => $compania) {
					$companiaEncontrada = $proveedorEncontrado->infoErpSubcliente->where('subcliente', $compania);
					if($companiaEncontrada->count() == 0){
						array_push($subclientesNoEncontrados, $compania);
					}
				}
			}

			array_push($subCompaniasNoEncontradas, $subclientesNoEncontrados);

		}
		if(count($subclientesNoEncontrados) > 0){
			foreach ($usuariosPerfilContabilidad as $usuario_iterador => $usuario) {
				$subclientes = [];
				foreach ($subCompaniasNoEncontradas as $iterador_subcliente => $subcliente_proveedor) {
					array_push($subclientes, MaestrasSubClientes::whereIn('id', $subcliente_proveedor)->get());
				}
				$html = $this->getViewEmail('notificacion_adjudicacion_compania_no_erp', [
					'empresas' => $proveedores,
					'companias' => $subclientes,
					'usuario' => $usuario,
					'oferta' => $oferta
				], [
					'renameVariable' => true, 
					'nameNewVariable' => "arrayData"
				]);
				// Enviar notificacion
				$asunto = 'Notificación - La orden generada del proceso '.$oferta->seq_id.', no se encontró ERP en unos proveedores ';
				(new communicationClient())->sendEmail($usuario['email'], $html, $asunto, "ComunicadoLogoCliente", $_SESSION["empresaid"]);
			}
		}
		(new modelo_acciones_oferta())->enviarEmailAdjudicacion($idOferta);
		(new modelo_acciones_oferta())->enviarEmailAgradecimientos($idOferta, 'adjudicada');

		$this->intelcost->response->bool = true;
		$this->intelcost->response->msg  = "se ha notificado y adjudicado el proceso correctamente.";
		$this->intelcost->response->codJDE = $codigo;
		return $this->intelcost->response;
	}

	public function crearProveedorJDEConfa($ids){
		if(isset($ids) && !empty($ids)){

			$jdeIds = array('preSaved' => array(), 'newSaved' => array(), 'all' => array());
			$soapClient = new soapConfa();

			foreach ($ids as $index => $id) {
				$sqlStm = "SELECT * FROM proveedores_confa WHERE cod_empresa = ? AND estado = 'ACTIVO'";
				$params = array($id);
				$typeParams = 's';

            			$response = $this->intelcost->prepareStatementQuery('intelcost', $sqlStm, 'SELECT', true, $typeParams, $params, "Consulta proveedores JDE Confa.");

            			if($response->bool){
            				if($response->msg->num_rows > 0){
            					$response = $response->msg->fetch_all(MYSQLI_ASSOC);
            					array_push($jdeIds['preSaved'], $response[0]['cod_jde_confa']);
            					$jdeIds['all'][$id] = $response[0]['cod_jde_confa'];
            				}else{

            					$request = array();

						$sqlStm = 'SELECT 	nitempxx,
												emailrepleg,
												direccxx,
												razonxxx,
												telefono,
												replegxx,
												CIU.nombre_ciu,
												DEP.nombre as depto,
												IT.act_principal
												FROM _0002103 EMP 
												LEFT JOIN mstciudades CIU 
												ON EMP.ciudidxx = CIU.idciudad
												LEFT JOIN mst_departamento AS DEP 
												ON DEP.codigo = CIU.id_departamento 
												AND DEP.pais = CIU.pais
												LEFT JOIN informacion_tributaria IT
												ON IT.cod_empresa = EMP.id_empresa
												WHERE EMP.id_empresa  = ? ';

						$typeParams = 'i';
						$params = array($id);
						$response = $this->intelcost->prepareStatementQuery('intelcost', $sqlStm, 'SELECT', true, $typeParams, $params, "Obteniendo información del proveedor.");

						if($response->bool){
							$data = $response->msg->fetch_assoc();
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

							 $params = [];

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

							$params['tercero'] = $tercero;
							$params['tipoTercero'] = 'P';

							$responseJDE = $soapClient->crearProveedorJDE($params);

							if($responseJDE->bool){
								$sqlStm = "	INSERT INTO proveedores_confa
											(cod_empresa, cod_jde_confa, usuario_registro, fecha_registro, estado)
											VALUES(?,?,?,?,?)";

								$typeParams = 'sssss';
								$params = array($id, $responseJDE->msg, $_SESSION['idusuario'], date("Y-m-d H:i:s"), 'activo');
								
								$response = $this->intelcost->prepareStatementQuery('intelcost', $sqlStm, 'INSERT', true, $typeParams, $params, "Insertar proveedor JDE Confa.");
								
								if(!$response->bool){
									$this->intelcost->response->bool = false;
									$this->intelcost->response->msg  = "Ocurrió un error al guardar el proveedor en la base de datos.";
									return $this->intelcost->response;
								}else{
			            					array_push($jdeIds['newSaved'], $responseJDE->msg);
			            					$jdeIds['all'][$id] = $responseJDE->msg;
								}

							}else{
								$this->intelcost->response->bool = false;
								$this->intelcost->response->msg  = $responseJDE->msg;
								return $this->intelcost->response;
							}

						}else{

							$this->intelcost->response->bool = false;
							$this->intelcost->response->msg  = "Ocurrió un error al consultar la informacion del proveedor.";
							return $this->intelcost->response;
						}

            				}
            			}else{
            				$this->intelcost->response->bool = false;
            				$this->intelcost->response->msg = 'Error en la consulta de proveedores';
					return $this->intelcost->response;
            			}
			}

			$this->intelcost->response->bool = true;
			$this->intelcost->response->msg = $jdeIds;

		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para la creación/consulta de los proveedores.';
		}

		return $this->intelcost->response;
	}

	public function generarPDF($datosGeneralAdjudicaciones, $datosPrincipales, $datosEspecificos, $evento){
		// dd($datosPrincipales);
		if(isset($datosGeneralAdjudicaciones) && !empty($datosGeneralAdjudicaciones) && isset($datosPrincipales) && !empty($datosPrincipales) && isset($evento) && !empty($evento) && isset($datosEspecificos) && !empty($datosEspecificos)){
			// dd($datosGeneralAdjudicaciones);
			$html = $this->modelo_pdf->headerPdf();
			$html .='<div style="text-align:right; margin-bottom:15px"><img src="../images/sliders/'.$_SESSION["cliente_logo"].'" style="width: 85px;height: 40px;"></div>';
			$html .= '<div style="margin-bottom:10px"><h3>Resumen de orden para el evento '.$evento.'</h3></div><hr>';
			$html .= '<div><h5>Información del evento</h5></div><hr>';
			$html .= str_replace('class="bg-primary"', '', $datosPrincipales);
			$html .= '<div><h5>Información general de las adjucicaciones</h5></div><hr>';
			$html .= $datosGeneralAdjudicaciones;
			$html .= str_replace('class="table table-bordered table-hover table-striped table-sm"', 'class="table-bordered table-striped"', $datosEspecificos);
			$html .= $this->modelo_pdf->footerPdf();
			// dd($html);
			$nameFile = 'ReporteGestionPedido_'.$evento.date('d-m-Y_H-i-s').'.pdf';
			if($this->modelo_pdf->generar_pdf($html,'../',$nameFile)){
				$this->intelcost->response->bool = true;
				$this->intelcost->response->msg = '../'.$nameFile;
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'Error al crear el reporte.';
			}
		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para la generación del reporte';
		}

		return $this->intelcost->response;
	}

	public function eliminarPDFdelServer($path){
		if(isset($path) && !empty($path)){
			if(file_exists($path)){
				if(unlink($path)){
					$this->intelcost->response->bool = true;
					$this->intelcost->response->msg = 'Reporte eliminado con éxito del servidor.';
				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = 'Ocurrio un error al intentar eliminar el reporte del servidor.';
				}
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'El archivo a eliminar no existe.';
			}
		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para la eliminación del reporte del servidor';
		}

		return $this->intelcost->response;
	}

	public function consultaRapida($idOferta){
		if(isset($idOferta) && !empty($idOferta)){

			$db_cliente = env('DB_NAME_DATABASE_CLIENT');
			$db_proveedores = env('DB_NAME_DATABASE_PROVIDER');

			$sqlStm = "	SELECT OP.cod_jde_confa codJDE,
						OP.valor_adjudicado valorAdjudicado,
						CP.usrnomxx contactoProv,
						IE.razonxxx razonSocial,
						PC.cod_jde_confa codProvJDE
						FROM `$db_cliente`.`orden_pedidos` OP
						LEFT JOIN `$db_cliente`.`oferta_adjudicaciones` OA
						ON OA.id = OP.cod_adjudicacion
						LEFT JOIN `$db_proveedores`.`sys00001` CP
						ON OA.id_usuario = CP.usridxxx
						LEFT JOIN `$db_proveedores`.`_0002103` IE
						ON CP.cod_empresa = IE.id_empresa
						LEFT JOIN `$db_proveedores`.`proveedores_confa` PC
						ON PC.cod_empresa = IE.id_empresa
		 				WHERE cod_oferta = ?";

			$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($idOferta), 'Consultando información para consulta rápida.');

			if($response->bool){
				if($response->msg->num_rows > 0){
					$response = $response->msg->fetch_all(MYSQLI_ASSOC);

					$this->intelcost->response->bool = true;
					$this->intelcost->response->msg = $response;
				}else{
					$this->intelcost->response->bool = false;
					$this->intelcost->response->msg = 'No se encontró información del evento solicitado.';	
				}
			}else{
				$this->intelcost->response->bool = false;
				$this->intelcost->response->msg = 'Ocurrió un problema al consultar la información para la consulta rápida.';	
			}


		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para la consulta rápida.';
		}

		return $this->intelcost->response;
	}

	public function getStatusPartialSave($eventos){

		if(isset($eventos) && !empty($eventos)){
			$eventos = json_decode($eventos);

			foreach ($eventos as $key => $evento){

				if(!empty($evento->idOrdenCompra) && (empty($evento->idJDE) || is_null($evento->idJDE))){
					$sqlStm = "	SELECT *
								FROM orden_pedidos
								WHERE cod_oferta = ? AND estado = 'activo'";

					$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($evento->id), 'Consultando información de estado de guardado.');

					if($response->bool){
						if($response->msg->num_rows > 0){
							$ordenes = $response->msg->fetch_all(MYSQLI_ASSOC);

							$sum = array('OLI' => 0, 'OPI' => 0);
							$justFirst = true;
							foreach ($ordenes as $keyOrden => $orden){
								$sqlStm = "	SELECT OPI.cantidad cantidadOPI,
											OLI.cantidad cantidadOLI
											FROM orden_pedidos_items OPI
											LEFT JOIN oferta_lotes_items OLI
											ON OLI.id_item = OPI.cod_item AND OLI.cod_lote = OPI.cod_lote
											WHERE OPI.cod_orden_pedidos = ?";
								
								$response = $this->intelcost->prepareStatementQuery('cliente', $sqlStm, 'SELECT', true, 's', array($orden['id']), 'Consultando información de estado de guardado.');

								if($response->bool){
									if($response->msg->num_rows > 0){
										$data = $response->msg->fetch_all(MYSQLI_ASSOC);

										foreach ($data as $keyData => $singleData) {
											$sum['OPI'] += (float)$singleData['cantidadOPI'];

											if($justFirst){
												$sum['OLI'] += (float)$singleData['cantidadOLI'];
											}
										}
										$justFirst = false;

									}else{
										$this->intelcost->response->bool = false;
										$this->intelcost->response->msg = 'No se encontró información para obtener los estados de los eventos con guardado parcial';
										return $this->intelcost->response;			
									}
								}else{
									$this->intelcost->response->bool = false;
									$this->intelcost->response->msg = 'Error al momento de consultar las ordenes de compra para validar los estados de guardado.';
									return $this->intelcost->response;
								}

							}
							// var_dump($sum);
							if($sum['OLI'] == $sum['OPI']){
								$evento->guardado = 'COMPLETO';
							}else{
								$evento->guardado = 'PARCIAL';
							}

						}else{
							$this->intelcost->response->bool = false;
							$this->intelcost->response->msg = 'No se encontró información para obtener los estados de los eventos con guardado parcial';
							return $this->intelcost->response;
						}
					}else{
						$this->intelcost->response->bool = false;
						$this->intelcost->response->msg = 'Error al momento de consultar las ordenes de compra para validar los estados de guardado.';
						return $this->intelcost->response;			
					}
				}
			}

			$this->intelcost->response->bool =  true;
			$this->intelcost->response->msg = $eventos;

		}else{
			$this->intelcost->response->bool = false;
			$this->intelcost->response->msg = 'Parametros insuficientes para obtener los estados de los eventos con guardado parcial';
		}

		return $this->intelcost->response;
	}

}
