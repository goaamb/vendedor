<?php
class Articulo_model extends CI_Model {
	public $id;
	public $usuario;
	public $titulo;
	public $descripcion;
	public $categoria;
	public $foto;
	public $precio;
	public $moneda;
	public $fecha_registro;
	public $estado;
	public $contactar_con;
	public $ciudad;
	public static $formas_pago = array (
			1 => "Otros",
			2 => "Pago contra reembolso",
			3 => "Transferencia bancaria",
			4 => "Paypal" 
	);
	public static $tarifa = array ();
	public function __construct() {
		parent::__construct ();
	}
	public function listarSeguimientosPorFinalizar($tiempo) {
		$tiempo = intval ( $tiempo ) * 3600;
		$sql = "select s.id as seguimiento,u.seudonimo,u.email,a.id,a.titulo from (select id,titulo,
				if(tipo='Subasta',
				duracion*86400-(unix_timestamp()-unix_timestamp(fecha_registro))
				," . $this->configuracion->variables ( "vencimientoOferta" ) . "*86400-(unix_timestamp()-unix_timestamp(fecha_registro))) as tiempo
		From articulo
		where terminado=0
		having tiempo<=$tiempo) as a 
		inner join siguiendo as s on a.id=s.articulo and s.notificado='No'
		inner join usuario as u on u.id=s.usuario
		order by u.email asc,a.titulo asc,a.id asc";
		return $this->db->query ( $sql )->result ();
	}
	public function modificarCantidad($a) {
		if ($a && $a->tipo == "Cantidad") {
			$this->db->update ( "articulo", array (
					"cantidad" => $a->cantidad,
					"cantidad_original" => $a->cantidad_original,
					"terminado" => 0,
					"fecha_terminado" => null 
			), array (
					"id" => $a->id 
			) );
		}
	}
	public function cargarTarifas() {
		$r = $this->db->order_by ( "tipo_tarifa asc,tipo_articulo asc,inicio asc" )->get ( "tarifa" )->result ();
		$tarifa = array ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			foreach ( $r as $t ) {
				$tt = $t->tipo_tarifa;
				$ta = $t->tipo_articulo;
				if (! isset ( $tarifa [$tt] )) {
					$tarifa [$tt] = array ();
				}
				switch ($tt) {
					case "Comision" :
						if (! isset ( $tarifa [$tt] [$ta] )) {
							$tarifa [$tt] [$ta] = array ();
						}
						$tarifa [$tt] [$ta] [] = array (
								"inicio" => $t->inicio,
								"porcentaje" => $t->porcentaje 
						);
						break;
					case "Plana" :
						$tarifa [$tt] [] = array (
								"inicio" => $t->inicio,
								"monto" => $t->monto,
								"nombre" => $t->nombre 
						);
						break;
				}
			}
		}
		self::$tarifa = $tarifa;
		
		// var_dump ( self::$tarifa );
	}
	public function darCuentasPorArticulos($articulos) {
		$narticulos = array ();
		$articulos = explode ( ",", $articulos );
		foreach ( $articulos as $a ) {
			if (trim ( $a ) !== "") {
				$narticulos [] = trim ( $a );
			}
		}
		$articulos = implode ( ",", $narticulos );
		if (trim ( $articulos ) !== "") {
			$cuentas = $this->db->query ( "select *,articulo.titulo as titulo,if(isnull(articulo.precio_oferta),articulo.precio,articulo.precio_oferta)as precio,cuenta.cantidad as cantidad from cuenta inner join articulo on articulo.id=cuenta.articulo where articulo in ($articulos) order by articulo.paquete asc" )->result ();
			if ($cuentas) {
				return $cuentas;
			}
		}
		return false;
	}
	public function darCuentasFakePorArticulos($articulos) {
		$narticulos = array ();
		$articulos = explode ( ",", $articulos );
		foreach ( $articulos as $a ) {
			if (trim ( $a ) !== "") {
				$narticulos [] = trim ( $a );
			}
		}
		$articulos = implode ( ",", $narticulos );
		if (trim ( $articulos ) !== "") {
			$cuentas = $this->db->query ( "select articulo.titulo as titulo,
articulo.precio as precio,
articulo.id as articulo,
if(articulo.tipo='Subasta',(select monto from cuenta where cuenta.articulo=articulo.id),'--') as monto,
articulo.fecha_registro as fecha,
if(articulo.tipo='Cantidad',(select cantidad from cuenta where cuenta.articulo=articulo.id),0) as cantidad  
from articulo
where articulo.id in ($articulos) order by articulo.titulo asc" )->result ();
			if ($cuentas) {
				return $cuentas;
			}
		}
		return false;
	}
	public function darFactura($factura) {
		$r = $this->db->where ( array (
				"id" => $factura 
		) )->get ( "factura" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			return $r [0];
		}
		return false;
	}
	public function darFacturas($usuario, $inicio = 0, $pagina = 0) {
		$this->load->model ( "Usuario_model", "usuarioM" );
		$usuario = $this->usuarioM->darUsuarioXId ( $usuario );
		if ($usuario) {
			$this->db->where ( array (
					"usuario" => $usuario->id 
			) );
			$this->db->order_by ( "mes desc" );
			$inicio = intval ( $inicio );
			$pagina = intval ( $pagina );
			if ($pagina) {
				$this->db->limit ( $pagina, $inicio );
			} else {
				$this->db->limit ( false, $inicio );
			}
			return $this->db->get ( "factura" )->result ();
		}
	}
	public function contarFacturas($usuario) {
		$this->load->model ( "Usuario_model", "usuarioM" );
		$usuario = $this->usuarioM->darUsuarioXId ( $usuario );
		if ($usuario) {
			$this->db->where ( array (
					"usuario" => $usuario->id 
			) );
			$this->db->select ( "count(id) as cantidad" );
			$r = $this->db->get ( "factura" )->result ();
			if ($r && is_array ( $r ) && count ( $r ) > 0) {
				return $r [0]->cantidad;
			}
		}
		return 0;
	}
	public function listarSubastas() {
		$this->db->where ( array (
				"tipo" => "Subasta",
				"terminado" => 0 
		) );
		return $this->darTodos ( "articulo" );
	}
	public function actualizarPublicacion($articulo) {
		if ($this->db->update ( "articulo", array (
				"fecha_registro" => date ( "Y-m-d H:i:s" ) 
		), array (
				"id" => $articulo 
		) )) {
			return $this->db->update ( "siguiendo", array (
					"notificado" => "No" 
			), array (
					"articulo" => $articulo 
			) );
		}
	}
	private function calcularCantidades(&$arbol, $cantidades) {
		if ($arbol && $cantidades) {
			$mc = 0;
			foreach ( $arbol as $id => &$padre ) {
				$c = 0;
				if (isset ( $cantidades [$id] )) {
					$c = $cantidades [$id];
				}
				$c += $this->calcularCantidades ( $padre ["hijos"], $cantidades );
				if ($c >= 0) {
					$padre ["datos"] ["cantidad"] = $c;
				}
				$mc += $c;
			}
			return $mc;
		}
		return 0;
	}
	public function darDatosCuentas($mes, $anio, $usuario) {
		$this->db->select ( "id" );
		$mes = intval ( $mes );
		if ($mes < 10) {
			$mes = "0" . $mes;
		}
		
		$this->db->where ( array (
				"mes" => "$mes-$anio",
				"usuario" => $usuario->id 
		) );
		$r = $this->db->get ( "factura" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			// print "ya existe la factura del $mes-$anio y usuario:
			// $usuario->id - $usuario->seudonimo<br/>";
			return false;
		}
		$total = 0;
		$articulos2 = array ();
		if ($usuario->tipo_tarifa == "Comision") {
			$this->db->where ( "fecha_envio between '" . date ( "$anio-$mes-01 00:00:00" ) . "' and '" . date ( "$anio-$mes-t 23:59:59" ) . "' and vendedor='$usuario->id'" );
			$r = $this->db->get ( "paquete" )->result ();
			if ($r && is_array ( $r ) && count ( $r ) > 0) {
				$paquetes = $r;
			}
			$articulos = array ();
			$transacciones = array ();
			
			if (isset ( $paquetes )) {
				foreach ( $paquetes as $p ) {
					$articulos = array_merge ( $articulos, explode ( ",", $p->articulos ) );
					$transacciones = array_merge ( $transacciones, explode ( ",", $p->transacciones ) );
				}
			}
			$as = array ();
			$ts = array ();
			
			foreach ( $articulos as $a ) {
				$a = $this->darArticulo ( $a );
				if ($a) {
					$total += floatval ( $a->precio_oferta ? $a->precio_oferta : $a->precio );
					$as [] = $a;
				}
			}
			
			foreach ( $transacciones as $t ) {
				$r = $this->db->where ( array (
						"id" => $t 
				) )->get ( "transaccion" )->result ();
				if ($r && is_array ( $r ) && count ( $r ) > 0) {
					$articulos2 [] = $r [0]->articulo;
					$total += floatval ( $r [0]->precio * $r [0]->cantidad );
					$ts [] = $r [0];
				}
			}
			if (! isset ( $paquetes )) {
				// print "no hay facturas<br/>";
				return false;
			}
			
			$monto = 0;
			foreach ( $as as $a ) {
				$this->db->where ( array (
						"articulo" => $a->id 
				) );
				$r = $this->db->get ( "cuenta" )->result ();
				if ($r && is_array ( $r ) && count ( $r ) > 0) {
					$tarifa = floatval ( $r [0]->monto );
				} else {
					$tarifa = $this->calcularTarifa ( $a, "Comision" );
					$this->db->insert ( "cuenta", array (
							"articulo" => $a->id,
							"paquete" => $a->paquete,
							"monto" => $tarifa,
							"fecha" => date ( "Y-m-d H:i:s" ),
							"usuario" => $usuario->id 
					) );
				}
				$monto += $tarifa;
			}
			foreach ( $ts as $t ) {
				$this->db->where ( array (
						"articulo" => $t->articulo 
				) );
				$r = $this->db->get ( "cuenta" )->result ();
				if ($r && is_array ( $r ) && count ( $r ) > 0) {
					$tarifa = floatval ( $r [0]->monto );
				} else {
					$tarifa = $this->calcularTarifa ( $t, "Comision", false, true );
					$this->db->insert ( "cuenta", array (
							"articulo" => $t->articulo,
							"paquete" => $t->paquete,
							"monto" => $tarifa,
							"fecha" => date ( "Y-m-d H:i:s" ),
							"usuario" => $usuario->id,
							"cantidad" => $t->cantidad 
					) );
				}
				$monto += $tarifa;
			}
		} else {
			$monto = 0;
			$tarifa = 0;
			
			$this->db->where ( array (
					"terminado" => 0,
					"usuario" => $usuario->id 
			) );
			$rs = $this->db->get ( "articulo" )->result ();
			$articulos = array ();
			foreach ( $rs as $a ) {
				$total += $a->precio;
				$articulos [] = $a->id;
				$tarifa = 0;
				if ($a->tipo == "Subasta") {
					$this->db->where ( array (
							"articulo" => $a->id 
					) );
					$r = $this->db->get ( "cuenta" )->result ();
					if ($r && is_array ( $r ) && count ( $r ) > 0) {
						$tarifa = floatval ( $r [0]->monto );
					} else {
						$tarifa = $this->calcularTarifa ( $a, "Comision" );
						$this->db->insert ( "cuenta", array (
								"articulo" => $a->id,
								"paquete" => null,
								"monto" => $tarifa,
								"fecha" => date ( "Y-m-d H:i:s" ),
								"usuario" => $usuario->id 
						) );
					}
				}
				$monto += $tarifa;
			}
			
			$monto += $this->calcularTarifa ( false, "Plana", $usuario->id );
		}
		$nc = 0;
		$r = $this->db->query ( "SELECT substr(codigo,1,length(codigo)-5) as nc from factura where substr(codigo,length(codigo)-3)='$anio' order by codigo desc limit 0,1" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			$nc = intval ( $r [0]->nc );
		}
		$nc ++;
		$iva = $monto * 0.18;
		$x = new stdClass ();
		$x->codigo = "$nc/$anio";
		$x->mes = "$mes-$anio";
		$x->usuario = $usuario->id;
		$x->fecha = date ( "Y-m-d H:i:s" );
		$x->articulos = isset ( $articulos ) ? implode ( ",", array_merge ( $articulos, $articulos2 ) ) : null;
		$x->monto_total = $total;
		$x->monto_tarifa = $monto;
		$x->iva = $iva;
		return $x;
	}
	private function darCategorias2($categoria = false, $categorias = false, $dosniveles = false) {
		$CI = &get_instance ();
		$CI->load->model ( "Categoria_model", "categoria" );
		$ret = array ();
		$cantidades = array ();
		if ($categorias && is_array ( $categorias ) && count ( $categorias ) > 0) {
			$cs = array ();
			foreach ( $categorias as $c ) {
				$cantidades [$c->categoria] = $c->cantidad;
			}
		}
		$arboles = Array ();
		$contados = array ();
		$cR = array ();
		foreach ( $cantidades as $id => $c ) {
			$arbol = $this->darPadres ( $id, $contados );
			foreach ( $arbol as $i => $v ) {
				$a = $arbol [$i];
				if ($i == 0) {
					if (! isset ( $cR [$a ["id"]] )) {
						$ret [$a ["id"]] = array (
								"datos" => array (
										"nombre" => $a ["nombre"],
										"cantidad" => $a ["cantidad"],
										"url" => $a ["url"],
										"nivel" => $a ["nivel"],
										"padre" => $a ["padre"] 
								),
								"hijos" => array () 
						);
						$cR [$a ["id"]] = &$ret [$a ["id"]];
					}
				} else {
					if (isset ( $cR [$a ["padre"]] )) {
						if (! isset ( $cR [$a ["padre"]] ["hijos"] [$a ["id"]] )) {
							$cR [$a ["padre"]] ["hijos"] [$a ["id"]] = array (
									"datos" => array (
											"nombre" => $a ["nombre"],
											"cantidad" => $a ["cantidad"],
											"url" => $a ["url"],
											"nivel" => $a ["nivel"],
											"padre" => $a ["padre"] 
									),
									"hijos" => array () 
							);
							$cR [$a ["id"]] = &$cR [$a ["padre"]] ["hijos"] [$a ["id"]];
						}
					}
				}
			}
		}
		$this->calcularCantidades ( $ret, $cantidades );
		if ($categoria) {
			if (isset ( $ret [$categoria] )) {
				foreach ( $ret [$categoria] ["hijos"] as &$r ) {
					$r ["hijos"] = array ();
				}
			} else {
				$ret = ($this->encuentraHijo ( $ret, $categoria ));
				$ret [$categoria] ["datos"] ["nivel"] = 1;
			}
		} else if ($dosniveles) {
			foreach ( $ret as $c => &$v ) {
				foreach ( $v ["hijos"] as &$vv ) {
					$vv ["hijos"] = array ();
				}
			}
		} else {
			foreach ( $ret as $c => &$v ) {
				$v ["hijos"] = array ();
			}
		}
		
		return $ret;
	}
	private function ordenarArbol(&$arbol) {
		if ($arbol && is_array ( $arbol ) && count ( $arbol ) > 0) {
			$keys = array_keys ( $arbol );
			$values = array_values ( $arbol );
			$cantidad = count ( $arbol );
			
			for($i = 0; $i < $cantidad; $i ++) {
				for($j = $i; $j < $cantidad; $j ++) {
					$ini = intval ( $values [$i] ["datos"] ["cantidad"] );
					$inik = $keys [$i];
					$valk = $keys [$j];
					$val = intval ( $values [$j] ["datos"] ["cantidad"] );
					if ($ini < $val) {
						$aux = $values [$i];
						$auxk = $keys [$i];
						$values [$i] = $values [$j];
						$keys [$i] = $keys [$j];
						$values [$j] = $aux;
						$keys [$j] = $auxk;
					}
				}
			}
			
			foreach ( $values as &$a ) {
				$a ["hijos"] = $this->ordenarArbol ( $a ["hijos"] );
			}
			return array_combine ( $keys, $values );
		}
		return array ();
	}
	private function encuentraHijo($arbol, $categoria) {
		if ($arbol && is_array ( $arbol ) && count ( $arbol ) > 0) {
			if (isset ( $arbol [$categoria] )) {
				return array (
						$categoria => $arbol [$categoria] 
				);
			} else {
				foreach ( $arbol as $id => $v ) {
					if ($c = $this->encuentraHijo ( $v ["hijos"], $categoria )) {
						return $c;
					}
				}
			}
		}
		return false;
	}
	private function darPadres($id, &$contados) {
		$CI = &get_instance ();
		$CI->load->model ( "Categoria_model", "categoria" );
		$arbol = array ();
		do {
			
			if (! isset ( $contados [$id] )) {
				$this->db->where ( array (
						"id" => $id 
				) );
				$resp = $this->darUno ( "categoria" );
				if ($resp) {
					$cn = $CI->categoria->darCategoriaNombre ( $id, $this->idioma->language->id );
					$contados [$id] = array (
							"nombre" => $cn->nombre,
							"cantidad" => $resp->cantidad,
							"url" => $cn->url_amigable,
							"nivel" => $resp->nivel,
							"padre" => $resp->padre,
							"id" => $resp->id 
					);
					array_unshift ( $arbol, $contados [$id] );
					$id = $resp->padre;
				} else {
					$id = false;
				}
			} else {
				array_unshift ( $arbol, $contados [$id] );
				$id = $contados [$id] ["padre"];
			}
		} while ( $id );
		return $arbol;
	}
	private function darDatosCategorias($categorias = false) {
		$CI = &get_instance ();
		$CI->load->model ( "Categoria_model", "categoria" );
		$ret = array ();
		if ($categorias && is_array ( $categorias ) && count ( $categorias ) > 0) {
			$cs = array ();
			$cantidades = array ();
			foreach ( $categorias as $c ) {
				$cs [] = $c->categoria;
				$cantidades [$c->categoria] = $c->cantidad;
			}
			$this->db->where_in ( "id", $cs );
			$datos = $this->darTodos ( "categoria" );
			
			if ($datos) {
				foreach ( $datos as $d ) {
					$nc = $CI->categoria->darCategoriaNombre ( $d->id, $this->idioma->language->id );
					if ($nc) {
						$ret [$d->id] = array (
								"datos" => array (
										"url" => $nc->url_amigable,
										"nombre" => $nc->nombre,
										"cantidad" => (isset ( $cantidades [$d->id] ) ? $cantidades [$d->id] : 0),
										"nivel" => $d->nivel 
								),
								"hijos" => array () 
						);
					}
				}
				foreach ( $datos as $d ) {
					if (! isset ( $ret [$d->padre] )) {
						$padre = $CI->categoria->darCategoriasX ( array (
								"categoria.id" => $d->padre 
						) );
						if ($padre && is_array ( $padre ) && count ( $padre ) > 0) {
							$padre = $padre [0];
							$nc = $CI->categoria->darCategoriaNombre ( $padre->id, $this->idioma->language->id );
							if ($nc) {
								$x = $ret [$d->id];
								$x ["datos"] ["nivel"] = 2;
								unset ( $ret [$d->id] );
								$ret [$padre->id] = array (
										"datos" => array (
												"url" => $nc->url_amigable,
												"nombre" => $nc->nombre,
												"cantidad" => $x ["datos"] ["cantidad"],
												"nivel" => 1 
										),
										"hijos" => array (
												$d->id => $x 
										) 
								);
							}
						}
					} else {
						$ret [$padre->id] ["hijos"] [$d->id] = $ret [$d->id];
						$ret [$padre->id] ["datos"] ["cantidad"] += $ret [$d->id] ["datos"] ["cantidad"];
						unset ( $ret [$d->id] );
					}
				}
			}
		}
		return $ret;
	}
	public function darCiudades() {
		return $this->db->query ( "select c.id,c.nombre,count(a.id) cantidad 
				from articulo a 
				inner join ciudad c on c.id=a.ciudad 
				group by c.id
				order by c.nombre" )->result ();
	}
	public function darCategorias($categoria = false) {
		$CI = &get_instance ();
		$CI->load->model ( "Categoria_model", "categoria" );
		$ret = array ();
		if (! $categoria) {
			$datos = $CI->categoria->darCategoriasX ( array (
					"nivel" => 1,
					"activo" => 1,
					"cantidad >" => 0 
			) );
			if ($datos) {
				foreach ( $datos as $d ) {
					$nc = $CI->categoria->darCategoriaNombre ( $d->id, $this->idioma->language->id );
					if ($nc) {
						$ret [$d->id] = array (
								"datos" => array (
										"url" => $nc->url_amigable,
										"nombre" => $nc->nombre,
										"cantidad" => $d->cantidad,
										"nivel" => $d->nivel 
								),
								"hijos" => array () 
						);
					}
				}
			}
		} else {
			$datos = $CI->categoria->darCategoriasX ( array (
					"categoria.id" => $categoria,
					"activo" => 1,
					"cantidad >" => 0 
			) );
			if ($datos) {
				$d = $datos [0];
				$nc = $CI->categoria->darCategoriaNombre ( $d->id, $this->idioma->language->id );
				$x = $d;
				if ($nc) {
					$ret [$x->id] = array (
							"datos" => array (
									"url" => $nc->url_amigable,
									"nombre" => $nc->nombre,
									"cantidad" => $d->cantidad,
									"nivel" => 1 
							),
							"hijos" => array () 
					);
					$hijos = $CI->categoria->darCategoriasX ( array (
							"padre" => $categoria,
							"activo" => 1,
							"cantidad >" => 0 
					) );
					if ($hijos) {
						// $hs = array ();
						foreach ( $hijos as $d ) {
							$nc = $CI->categoria->darCategoriaNombre ( $d->id, $this->idioma->language->id );
							if ($nc) {
								$ret [$d->id] = array (
										"datos" => array (
												"url" => $nc->url_amigable,
												"nombre" => $nc->nombre,
												"cantidad" => $d->cantidad,
												"nivel" => 2 
										),
										"hijos" => array () 
								);
							}
						}
						// $ret [$x->id] ["hijos"] = $hs;
						// $ret = array_merge ( $ret, $hs );
					}
				}
			}
		}
		
		return $ret;
	}
	function totalVentas($usuario) {
		return 0;
	}
	function soloPendientes($usuario) {
		return 0;
	}
	function totalMensajes($usuario) {
		return 0;
	}
	function totalCompras($usuario) {
		return 0;
	}
	function totalSeguimientos($usuario) {
		return 0;
	}
	function totalCuentas($usuario) {
		return 0;
	}
	function darTransaccion($trasaccion) {
		$r = $this->db->where ( array (
				"id" => $trasaccion 
		) )->get ( "transaccion" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			return $r [0];
		}
		return false;
	}
	function prepararArticulosXVendidosFecha($usuario, $pending = false) {
		if ($usuario) {
			$orderby = $this->input->get ( "orderby" );
			$asc = $this->input->get ( "asc" );
			$who = $this->input->get ( "who" );
			
			if ($who !== "selled") {
				$orderby = "time";
				$asc = "desc";
			}
			$wextra = "";
			if ($pending) {
				$wextra = " and estado in ('Sin gastos Envio','Sin Envio')";
			}
			
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			$sextra = ",if(isnull(articulo.precio_oferta),articulo.precio,articulo.precio_oferta) as precio";
			$sextra2 = ",articulo.precio";
			$fextra = "";
			switch ($asc) {
				case "asc" :
					$asc = "asc";
					break;
				default :
					$asc = "desc";
					break;
			}
			switch ($orderby) {
				case "charge" :
					// 1-,
					// 2-,3-
					$sextra .= ",if(articulo.estado='Sin Pago',1,if(articulo.estado='Sin Envio',2,3)) as aEstado";
					$sextra2 .= ",if(transaccion.estado='Sin Pago',1,if(transaccion.estado='Sin Envio',2,3)) as aEstado";
					$orderby = "aEstado $asc,paquete desc,fecha_terminado ";
					break;
				case "shipping" :
					// 1-,
					// 2-,3
					$sextra .= ",if(articulo.estado='Sin Envio',1,if(articulo.estado='Enviado',2,3)) as aEstado";
					$sextra2 .= ",if(transaccion.estado='Sin Envio',1,if(transaccion.estado='Enviado',2,3)) as aEstado";
					$orderby = "aEstado $asc,paquete desc,fecha_envio ";
					break;
				case "time" :
					$sextra .= ",ordernarFechaVendidos(articulo.paquete,'$asc',0) as fecha_conjunta";
					$sextra2 .= ",ordernarFechaVendidos(transaccion.paquete,'$asc',1) as fecha_conjunta";
					$orderby = "fecha_conjunta $asc,paquete asc,fecha_terminado ";
					break;
				case "price" :
					$sextra .= ",ordernarPrecioVendidos(articulo.paquete) as precio_total";
					$sextra2 .= ",ordernarPrecioVendidos(transaccion.paquete) as precio_total";
					$orderby = "precio_total $asc,paquete asc,precio_total ";
					break;
				default :
					$sextra .= ",if(articulo.estado='Sin gastos Envio',1,if(articulo.estado='Sin Pago',2,3)) as aEstado";
					$sextra2 .= ",if(transaccion.estado='Sin gastos Envio',1,if(transaccion.estado='Sin Pago',2,3)) as aEstado";
					$orderby = "aEstado $asc,paquete desc,gastos_envio ";
					break;
			}
			return "select * from ((SELECT 
null as transaccion,
articulo.cantidad,
articulo.id,
articulo.titulo,
articulo.tipo,
articulo.fecha_registro,
articulo.duracion,
articulo.usuario,
articulo.foto,
 (if(isnull(articulo.paquete),null,(select gastos_envio from paquete where paquete.id=articulo.paquete)))as gastos_envio,
articulo.estado,
articulo.comprador,
articulo.fecha_terminado,
articulo.paquete,
articulo.pagos,
(if(isnull(articulo.paquete),null,(select fecha_disputa1 from paquete where paquete.id=articulo.paquete)))as fecha_disputa1,
(if(isnull(articulo.paquete),null,(select tipo_pago from paquete where paquete.id=articulo.paquete)))as tipo_pago,
(if(isnull(articulo.paquete),null,(select fecha_pago from paquete where paquete.id=articulo.paquete)))as fecha_pago,
(if(isnull(articulo.paquete),null,(select denuncia1 from paquete where paquete.id=articulo.paquete)))as denuncia1,
(if(isnull(articulo.paquete),null,(select fecha from paquete where paquete.id=articulo.paquete)))as fecha_paquete,
(if(isnull(articulo.paquete),null,(select denuncia2 from paquete where paquete.id=articulo.paquete)))as denuncia2 ,
(if(isnull(articulo.paquete),null,(select denuncia3 from paquete where paquete.id=articulo.paquete)))as denuncia3,
(if(isnull(articulo.paquete),null,(select denuncia4 from paquete where paquete.id=articulo.paquete)))as denuncia4,
(if(isnull(articulo.paquete),null,(select fecha_envio from paquete where paquete.id=articulo.paquete)))as fecha_envio
$sextra


 FROM articulo 
INNER JOIN usuario ON usuario.id=articulo.usuario and usuario.id='$usuario' 

WHERE terminado = 1 and articulo.estado<>'A la venta' and articulo.estado<>'Finalizado' ) union (
select 

transaccion.id as transaccion,
transaccion.cantidad,
articulo.id,
articulo.titulo,
articulo.tipo,
articulo.fecha_registro,
articulo.duracion,
articulo.usuario,
articulo.foto,
 (if(isnull(transaccion.paquete),null,(select gastos_envio from paquete where paquete.id=transaccion.paquete)))as gastos_envio,
transaccion.estado,
transaccion.comprador,
transaccion.fecha_terminado,
transaccion.paquete,
articulo.pagos,
(if(isnull(transaccion.paquete),null,(select fecha_disputa1 from paquete where paquete.id=transaccion.paquete)))as fecha_disputa1,
(if(isnull(transaccion.paquete),null,(select tipo_pago from paquete where paquete.id=transaccion.paquete)))as tipo_pago,
(if(isnull(transaccion.paquete),null,(select fecha_pago from paquete where paquete.id=transaccion.paquete)))as fecha_pago,
(if(isnull(transaccion.paquete),null,(select denuncia1 from paquete where paquete.id=transaccion.paquete)))as denuncia1,
(if(isnull(transaccion.paquete),null,(select fecha from paquete where paquete.id=transaccion.paquete)))as fecha_paquete,
(if(isnull(transaccion.paquete),null,(select denuncia2 from paquete where paquete.id=transaccion.paquete)))as denuncia2 ,
(if(isnull(transaccion.paquete),null,(select denuncia3 from paquete where paquete.id=transaccion.paquete)))as denuncia3,
(if(isnull(transaccion.paquete),null,(select denuncia4 from paquete where paquete.id=transaccion.paquete)))as denuncia4,
(if(isnull(transaccion.paquete),null,(select fecha_envio from paquete where paquete.id=transaccion.paquete)))as fecha_envio
$sextra2

from transaccion 
inner join articulo on articulo.id=transaccion.articulo 
inner join usuario on articulo.usuario=usuario.id and usuario.id='$usuario'
where transaccion.estado<>'Finalizado'
))as x 
where 1 $wextra
ORDER BY $orderby $asc";
		}
		return false;
	}
	function adicionarVoto($usuario, $motivo, $cantidad = 1, $tipo = "positivo") {
		if ($tipo !== "positivo") {
			$tipo = "negativo";
		}
		$utipo = ucfirst ( $tipo );
		$tipo = strtolower ( $tipo );
		$CI = &get_instance ();
		$CI->load->model ( "Usuario_model", "usuario" );
		$usuario = $CI->usuario->darUsuarioXId ( $usuario );
		if ($usuario && $this->db->insert ( "voto", array (
				"usuario" => $usuario->id,
				"tipo" => $utipo,
				"asunto" => $motivo,
				"fecha" => date ( "Y-m-d H:i:s" ) 
		) )) {
			return $this->db->update ( "usuario", array (
					$tipo => intval ( $usuario->{$tipo} ) + $cantidad 
			), array (
					"id" => $usuario->id 
			) );
		}
		return false;
	}
	function confirmarRecepcion($tipo, $paquete) {
		$this->db->where ( array (
				"id" => $paquete,
				"estado" => "Enviado" 
		) );
		$res = $this->db->get ( "paquete" )->result ();
		if ($res && is_array ( $res ) && count ( $res ) > 0) {
			$res = $res [0];
			if ($tipo == 1) {
				$tipo = "Recibido";
				$this->adicionarVoto ( $res->vendedor, "Venta" );
				$this->adicionarVoto ( $res->comprador, "Compra" );
			} else {
				$tipo = "Disputa";
				$this->db->insert ( "reporte", array (
						"asunto" => "Artículo Recibido diferente de la descripción del anuncio",
						"paquete" => $paquete,
						"fecha" => date ( "Y-m-d H-i-s" ),
						"perfil" => $res->vendedor,
						"usuario" => $res->comprador 
				) );
			}
			if ($this->db->update ( "paquete", array (
					"estado" => $tipo,
					"fecha_recibido" => date ( "Y-m-d H:i:s" ),
					"denuncia3" => 0,
					"denuncia4" => 0 
			), array (
					"id" => $paquete 
			) )) {
				if (trim ( $res->articulos ) !== "") {
					$articulos = explode ( ",", $res->articulos );
					foreach ( $articulos as $a ) {
						$this->db->update ( "articulo", array (
								"estado" => $tipo 
						), array (
								"id" => $a 
						) );
					}
				}
				if (trim ( $res->transacciones ) !== "") {
					$transacciones = explode ( ",", $res->transacciones );
					foreach ( $transacciones as $t ) {
						$this->db->update ( "transaccion", array (
								"estado" => $tipo 
						), array (
								"id" => $t 
						) );
					}
				}
				return true;
			}
		}
		return false;
	}
	function listarArticulosEnviados($usuario) {
		$r = $this->db->query ( "select articulo.* from articulo inner join paquete on paquete.id=articulo.paquete and not isnull(paquete.fecha_envio) where usuario='$usuario'  " )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			return $r;
		}
		return false;
	}
	function sumarArticulosEnVentaFijo($usuario) {
		$r = $this->db->query ( "select if(tipo='Fijo',sum(precio),if(tipo='Cantidad',sum(precio*cantidad),0)) as precio from articulo where terminado=0 and (tipo='Fijo' or tipo='Cantidad') and usuario='$usuario'" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			return $r [0]->precio;
		}
		return false;
	}
	function usuariosConVentas($mes, $anio) {
		return $this->db->query ( "select usuario.id,usuario.seudonimo,usuario.nueva_tarifa from usuario inner join paquete on usuario.id=paquete.vendedor and paquete.fecha_envio between '$anio-$mes-01 00:00:00' and '$anio-$mes-" . date ( "t", strtotime ( date ( "$anio-$mes-01" ) ) ) . " 23:59:59' group by usuario.id" )->result ();
	}
	function enviarNotificacionCambioTarifaPlana($usuario, $total, $pos) {
		if ($pos > 0) {
			$CI = &get_instance ();
			
			$CI->load->library ( "Myemail" );
			$CI->myemail->enviarTemplate ( $usuario->email, traducir ( "Su tarifa cambio" ), "mail/notificacion-cambio-tarifa-plana", $params = array (
					"total" => $total,
					"tarifa_anterior" => self::$tarifa ["Plana"] [$pos - 1] ["nombre"],
					"monto_anterior" => self::$tarifa ["Plana"] [$pos - 1] ["monto"],
					"final_anterior" => self::$tarifa ["Plana"] [$pos] ["inicio"],
					"tarifa_actual" => self::$tarifa ["Plana"] [$pos] ["nombre"],
					"monto_actual" => self::$tarifa ["Plana"] [$pos] ["monto"] 
			) );
		}
	}
	function confirmarEnvio($paquete) {
		if ($paquete) {
			$paquete = $this->darPaquete ( $paquete );
			if ($paquete) {
				$articulo = array ();
				if (trim ( $paquete->articulos ) !== "") {
					$articulo = explode ( ",", $paquete->articulos );
				}
				$transaccion = array ();
				if (trim ( $paquete->transacciones ) !== "") {
					$transaccion = explode ( ",", $paquete->transacciones );
				}
				$this->db->update ( "paquete", array (
						"estado" => "Enviado",
						"fecha_envio" => date ( "Y-m-d H:i:s" ),
						"denuncia3" => 0 
				), array (
						"id" => $paquete->id 
				) );
				
				$CI = &get_instance ();
				$vendedor = $CI->usuario->darUsuarioXId ( $paquete->vendedor );
				if ($vendedor && (count ( $articulo ) > 0 || count ( $transaccion ) > 0)) {
					if ($vendedor->tipo_tarifa == "Comision") {
						if (count ( $articulo ) > 0) {
							$articulos = $this->db->where_in ( "id", $articulo )->get ( "articulo" )->result ();
							
							foreach ( $articulos as $a ) {
								$tarifa = $this->calcularTarifa ( $a, $vendedor->tipo_tarifa );
								$this->db->insert ( "cuenta", array (
										"articulo" => $a->id,
										"paquete" => $a->paquete,
										"monto" => $tarifa,
										"fecha" => date ( "Y-m-d H:i:s" ),
										"usuario" => $vendedor->id 
								) );
							}
						}
						if (count ( $transaccion ) > 0) {
							$transacciones = $this->db->where_in ( "id", $transaccion )->get ( "transaccion" )->result ();
							foreach ( $transacciones as $t ) {
								$tarifa = $this->calcularTarifa ( $t, $vendedor->tipo_tarifa, false, true );
								$this->db->insert ( "cuenta", array (
										"articulo" => $t->articulo,
										"paquete" => $t->paquete,
										"monto" => $tarifa,
										"fecha" => date ( "Y-m-d H:i:s" ),
										"usuario" => $vendedor->id,
										"cantidad" => $t->cantidad 
								) );
							}
						}
					}
				}
				if ($articulo && is_array ( $articulo ) && count ( $articulo ) > 0) {
					foreach ( $articulo as $a ) {
						$this->db->update ( "articulo", array (
								"estado" => "Enviado" 
						), array (
								"id" => $a 
						) );
					}
				}
				if ($transaccion && is_array ( $transaccion ) && count ( $transaccion ) > 0) {
					foreach ( $transaccion as $t ) {
						$this->db->update ( "transaccion", array (
								"estado" => "Enviado" 
						), array (
								"id" => $t 
						) );
					}
				}
				return true;
			}
		}
		return false;
	}
	function calcularTarifa($a, $tipo_tarifa, $u = false, $transaccion = false) {
		$tarifa = self::$tarifa;
		
		$mtarifa = 0;
		$monto = 0;
		$acumulativo = 0;
		if (! isset ( $tarifa [$tipo_tarifa] )) {
			return 0;
		}
		if ($tipo_tarifa == "Comision") {
			if (! $transaccion) {
				$precio = floatval ( $a->precio_oferta ? $a->precio_oferta : $a->precio );
				$tipo = $a->tipo;
			} else {
				$precio = floatval ( $a->precio * $a->cantidad );
				$tipo = "Fijo";
			}
			
			foreach ( $tarifa [$tipo_tarifa] [$tipo] as $i => $t ) {
				if ($i < count ( $tarifa [$tipo_tarifa] [$tipo] ) - 1) {
					if ($precio < floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i + 1] ["inicio"] ) && $precio >= floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i] ["inicio"] )) {
						$monto += ($precio - $acumulativo) * floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i] ["porcentaje"] ) / 100;
					} elseif ($precio >= floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i + 1] ["inicio"] )) {
						$monto += (floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i + 1] ["inicio"] ) - $acumulativo) * floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i] ["porcentaje"] ) / 100;
					}
					$acumulativo = floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i + 1] ["inicio"] );
				} else {
					if ($precio >= floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i] ["inicio"] )) {
						$monto += ($precio - $acumulativo) * floatval ( $tarifa [$tipo_tarifa] [$tipo] [$i] ["porcentaje"] ) / 100;
					}
				}
			}
		} else {
			$precio = $this->sumarArticulosEnVentaFijo ( $u );
			$costo = 0;
			foreach ( $tarifa [$tipo_tarifa] as $i => $t ) {
				if ($i + 1 < count ( $tarifa [$tipo_tarifa] ) - 1) {
					if ($precio < floatval ( $tarifa [$tipo_tarifa] [$i + 1] ["inicio"] )) {
						$costo = $tarifa [$tipo_tarifa] [$i] ["monto"];
					} else {
						$costo = $tarifa [$tipo_tarifa] [$i] ["monto"];
					}
				}
			}
			return $costo;
		}
		return $monto;
	}
	function prepararArticulosXCompradosFecha($usuario, $pending = false) {
		if ($usuario) {
			$orderby = $this->input->get ( "orderby" );
			$asc = $this->input->get ( "asc" );
			$who = $this->input->get ( "who" );
			
			if ($who !== "buyed") {
				$orderby = "time";
				$asc = "desc";
			}
			$wextra = "";
			if ($pending) {
				$wextra = " and estado in ('Sin Pago','Enviado')";
			}
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			$sextra = ",if(isnull(articulo.precio_oferta),articulo.precio,articulo.precio_oferta) as precio";
			$sextra2 = ",transaccion.precio";
			$fextra = "";
			switch ($asc) {
				case "asc" :
					$asc = "asc";
					break;
				default :
					$asc = "desc";
					break;
			}
			switch ($orderby) {
				case "charge" :
					// 1-,
					// 2-,3-
					$sextra .= ",if(articulo.estado='Sin Pago',1,if(articulo.estado='Sin Envio',2,3)) as aEstado";
					$sextra2 .= ",if(transaccion.estado='Sin Pago',1,if(transaccion.estado='Sin Envio',2,3)) as aEstado";
					$orderby = "aEstado $asc,paquete desc,fecha_terminado ";
					break;
				case "shipping" :
					// 1-,
					// 2-,3
					$sextra .= ",if(articulo.estado='Sin Envio',1,if(articulo.estado='Enviado',2,3)) as aEstado";
					$sextra2 .= ",if(transaccion.estado='Sin Envio',1,if(transaccion.estado='Enviado',2,3)) as aEstado";
					$orderby = "aEstado $asc,paquete desc,fecha_envio ";
					break;
				case "time" :
					$sextra .= ",ordernarFechaVendidos(articulo.paquete,'$asc',0) as fecha_conjunta";
					$sextra2 .= ",ordernarFechaVendidos(transaccion.paquete,'$asc',1) as fecha_conjunta";
					$orderby = "fecha_conjunta $asc,paquete asc,fecha_terminado ";
					break;
				case "price" :
					$sextra .= ",ordernarPrecioVendidos(articulo.paquete) as precio_total";
					$sextra2 .= ",ordernarPrecioVendidos(transaccion.paquete) as precio_total";
					$orderby = "precio_total $asc,paquete asc,precio_total ";
					break;
				default :
					$sextra .= ",if(articulo.estado='Sin gastos Envio',1,2) as aEstado";
					$sextra2 .= ",if(transaccion.estado='Sin gastos Envio',1,2) as aEstado";
					$orderby = "aEstado $asc,gastos_envio $asc,paquete ";
					break;
			}
			
			return "select * from ((SELECT
			null as transaccion,
			articulo.cantidad, 
articulo.id,
articulo.titulo,
articulo.tipo,
articulo.fecha_registro,
articulo.duracion,
articulo.usuario,
articulo.foto, 
(if(isnull(articulo.paquete),null,(select gastos_envio from paquete where paquete.id=articulo.paquete)))as gastos_envio,
articulo.estado,
articulo.comprador,
articulo.fecha_terminado,
articulo.paquete,
articulo.pagos,
(if(isnull(articulo.paquete),null,(select fecha_disputa1 from paquete where paquete.id=articulo.paquete)))as fecha_disputa1,
(if(isnull(articulo.paquete),null,(select tipo_pago from paquete where paquete.id=articulo.paquete)))as tipo_pago,
(if(isnull(articulo.paquete),null,(select fecha_pago from paquete where paquete.id=articulo.paquete)))as fecha_pago,
(if(isnull(articulo.paquete),null,(select denuncia1 from paquete where paquete.id=articulo.paquete)))as denuncia1 ,
(if(isnull(articulo.paquete),null,(select fecha from paquete where paquete.id=articulo.paquete)))as fecha_paquete,
(if(isnull(articulo.paquete),null,(select denuncia2 from paquete where paquete.id=articulo.paquete)))as denuncia2 ,
(if(isnull(articulo.paquete),null,(select denuncia3 from paquete where paquete.id=articulo.paquete)))as denuncia3,
(if(isnull(articulo.paquete),null,(select denuncia4 from paquete where paquete.id=articulo.paquete)))as denuncia4,
(if(isnull(articulo.paquete),null,(select fecha_envio from paquete where paquete.id=articulo.paquete)))as fecha_envio
$sextra

FROM articulo 
INNER JOIN usuario ON usuario.id=articulo.comprador and usuario.id='$usuario' 
WHERE terminado = 1 and articulo.estado<>'A la venta' and articulo.estado<>'Finalizado')
union
(SELECT 
transaccion.id as transaccion,
transaccion.cantidad,
articulo.id,
articulo.titulo,
articulo.tipo,
articulo.fecha_registro,
articulo.duracion,
articulo.usuario,
articulo.foto, 
(if(isnull(transaccion.paquete),null,(select gastos_envio from paquete where paquete.id=transaccion.paquete)))as gastos_envio,
transaccion.estado,
transaccion.comprador,
transaccion.fecha_terminado,
transaccion.paquete,
articulo.pagos,
(if(isnull(transaccion.paquete),null,(select fecha_disputa1 from paquete where paquete.id=transaccion.paquete)))as fecha_disputa1,
(if(isnull(transaccion.paquete),null,(select tipo_pago from paquete where paquete.id=transaccion.paquete)))as tipo_pago,
(if(isnull(transaccion.paquete),null,(select fecha_pago from paquete where paquete.id=transaccion.paquete)))as fecha_pago,
(if(isnull(transaccion.paquete),null,(select denuncia1 from paquete where paquete.id=transaccion.paquete)))as denuncia1 ,
(if(isnull(transaccion.paquete),null,(select fecha from paquete where paquete.id=transaccion.paquete)))as fecha_paquete,
(if(isnull(transaccion.paquete),null,(select denuncia2 from paquete where paquete.id=transaccion.paquete)))as denuncia2 ,
(if(isnull(transaccion.paquete),null,(select denuncia3 from paquete where paquete.id=transaccion.paquete)))as denuncia3,
(if(isnull(transaccion.paquete),null,(select denuncia4 from paquete where paquete.id=transaccion.paquete)))as denuncia4,
(if(isnull(transaccion.paquete),null,(select fecha_envio from paquete where paquete.id=transaccion.paquete)))as fecha_envio
$sextra2

FROM transaccion
inner join articulo on articulo.id=transaccion.articulo
INNER JOIN usuario ON usuario.id=transaccion.comprador and usuario.id='$usuario' 
where transaccion.estado<>'Finalizado'
)) as x 
where 1 $wextra
ORDER BY $orderby $asc ";
		}
		return false;
	}
	function prepararArticulosXEnCompraFecha($usuario) {
		if ($usuario) {
			$orderby = $this->input->get ( "orderby" );
			$asc = $this->input->get ( "asc" );
			$who = $this->input->get ( "who" );
			
			if ($who !== "on-buy") {
				$orderby = "time";
				$asc = "desc";
			}
			
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			$sextra = ",articulo.precio";
			$fextra = "";
			switch ($orderby) {
				case "title" :
					$orderby = "articulo.titulo";
					break;
				case "status" :
					// 1-maximo pujador,
					// 2-sobrepujado,3-ofertaenviada,4-ofertarechazada
					$sextra .= ",if(articulo.tipo='Subasta',if((select usuario from oferta where oferta.articulo=articulo.id order by monto_automatico desc,fecha asc limit 0,1)='$usuario',1,2),if((select estado from oferta where oferta.articulo=articulo.id and oferta.usuario='$usuario' order by monto desc limit 0,1 )='Pendiente',3,4))as oEstado";
					$orderby = "oEstado";
					break;
				case "price" :
					$sextra = ",if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "precio";
					break;
				default :
					$sextra .= ",if(articulo.tipo='Fijo',unix_timestamp(articulo.fecha_registro)+$vencimientoOferta- unix_timestamp(),unix_timestamp(articulo.fecha_registro)+articulo.duracion*86400 - unix_timestamp())as tiempo";
					$orderby = "tiempo";
					break;
			}
			switch ($asc) {
				case "asc" :
					$asc = "asc";
					break;
				default :
					$asc = "desc";
					break;
			}
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			return "SELECT articulo.cantidad,articulo.id,articulo.titulo,articulo.tipo,articulo.fecha_registro,articulo.duracion,articulo.usuario,articulo.foto,(select usuario from oferta where oferta.articulo=articulo.id order by monto desc limit 0,1) as maximoPujador ,(select estado from oferta where oferta.articulo=articulo.id and oferta.usuario='$usuario' order by monto desc limit 0,1) as estadoOferta $sextra
			FROM articulo
			inner join oferta on oferta.articulo=articulo.id and oferta.estado<>'Aceptado' and oferta.usuario='$usuario'
			WHERE terminado = 0 and articulo.estado='A la venta'
			group by articulo.id 
			ORDER by $orderby $asc";
		}
		return false;
	}
	function prepararArticulosXEnVentaFecha($usuario, $new = false) {
		if ($usuario) {
			$orderby = $this->input->get ( "orderby" );
			$asc = $this->input->get ( "asc" );
			$who = $this->input->get ( "who" );
			
			if ($who !== "on-sell") {
				$orderby = "time";
				$asc = "desc";
			}
			
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			$sextra = "";
			$fextra = "";
			switch ($orderby) {
				case "follower" :
					$orderby = "seguidores";
					break;
				case "deals" :
					$orderby = "nOfertas";
					$sextra .= ",(select count(oferta.id) from oferta inner join usuario on usuario.id=oferta.usuario and usuario.estado<>'Baneado' where articulo.id=oferta.articulo) as nOfertas";
					break;
				case "price" :
					$sextra .= ",if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "precio";
					break;
				default :
					$sextra .= ",if(articulo.tipo='Fijo',unix_timestamp(articulo.fecha_registro)+$vencimientoOferta- unix_timestamp(),unix_timestamp(articulo.fecha_registro)+articulo.duracion*86400 - unix_timestamp())as tiempo";
					$orderby = "tiempo";
					break;
			}
			switch ($asc) {
				case "asc" :
					$asc = "asc";
					break;
				default :
					$asc = "desc";
					break;
			}
			if ($new) {
				$fextra = "INNER JOIN (select count(oferta.id) as cantidad,oferta.articulo from oferta inner join articulo on oferta.articulo=articulo.id inner join usuario on usuario.id=oferta.usuario and usuario.estado<>'Baneado' where oferta.estado='Pendiente' and articulo.tipo='Fijo' group by oferta.articulo ) as s on s.articulo=articulo.id and s.cantidad>0";
				$sextra .= ",s.cantidad as ofertasPendientes";
			} else {
				$sextra .= ",(select count(oferta.id) from oferta inner join usuario on usuario.id=oferta.usuario and usuario.estado<>'Baneado' where oferta.articulo=articulo.id and oferta.estado='Pendiente') as ofertasPendientes";
			}
			return "SELECT articulo.cantidad,articulo.id,articulo.titulo,articulo.tipo,articulo.precio,articulo.fecha_registro,articulo.duracion,articulo.usuario,articulo.foto,(select count(siguiendo.id) from siguiendo where siguiendo.articulo=articulo.id) as seguidores $sextra
			FROM articulo
			INNER JOIN usuario ON usuario.id=articulo.usuario and usuario.id='$usuario'
			$fextra
			WHERE terminado = 0 and articulo.estado='A la venta' 
			ORDER by $orderby $asc";
		}
		return false;
	}
	function prepararArticulosXNoCompradosFecha($usuario) {
		if ($usuario) {
			$orderby = $this->input->get ( "orderby" );
			$asc = $this->input->get ( "asc" );
			$who = $this->input->get ( "who" );
			
			if ($who !== "no-buy") {
				$orderby = "time";
				$asc = "desc";
			}
			
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			$sextra = ",articulo.precio";
			$fextra = "";
			switch ($orderby) {
				case "title" :
					$orderby = "articulo.titulo";
					break;
				case "price" :
					$sextra = ",if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "precio";
					break;
				default :
					$orderby = "articulo.terminado";
					break;
			}
			switch ($asc) {
				case "asc" :
					$asc = "asc";
					break;
				default :
					$asc = "desc";
					break;
			}
			return "SELECT articulo.id,articulo.titulo,articulo.tipo,articulo.fecha_registro,articulo.duracion,articulo.usuario,articulo.foto,articulo.fecha_terminado $sextra
			FROM articulo
			inner join oferta on oferta.articulo=articulo.id and oferta.estado<>'Aceptado' and oferta.usuario='$usuario'
			WHERE terminado = 1 and (articulo.estado='Sin gastos Envio' or articulo.estado='A la venta' or articulo.estado='Sin Pago' or articulo.estado='Finalizado') and (articulo.comprador<>'$usuario' or isnull(articulo.comprador))
			group by articulo.id 
			ORDER by $orderby $asc";
		}
		return false;
	}
	function prepararArticulosXNoVendidosFecha($usuario) {
		if ($usuario) {
			$orderby = $this->input->get ( "orderby" );
			$asc = $this->input->get ( "asc" );
			$who = $this->input->get ( "who" );
			if ($who !== "no-sell") {
				$orderby = "time";
				$asc = "desc";
			}
			$sextra = "";
			switch ($orderby) {
				case "title" :
					$orderby = "articulo.titulo";
					break;
				case "type" :
					$orderby = "articulo.tipo";
					break;
				case "price" :
					$sextra .= ",if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "precio";
					break;
				default :
					$orderby = "articulo.terminado";
					break;
			}
			switch ($asc) {
				case "asc" :
					$asc = "asc";
					break;
				default :
					$asc = "desc";
					break;
			}
			
			return "SELECT articulo.cantidad,articulo.id,articulo.titulo,articulo.tipo,articulo.precio,articulo.fecha_registro,articulo.duracion,articulo.usuario,articulo.foto,articulo.fecha_terminado $sextra
			FROM articulo
			INNER JOIN usuario ON usuario.id=articulo.usuario and usuario.id='$usuario'
			WHERE terminado = 1 and (articulo.estado='A la venta' or articulo.estado='Finalizado') and (articulo.tipo<>'Cantidad' or (articulo.tipo='Cantidad' && articulo.cantidad>0))
			ORDER by $orderby $asc";
		}
		return false;
	}
	public function listarArticulosXCompradosFecha($usuario, $inicio, $total, $pending = false) {
		$query = $this->prepararArticulosXCompradosFecha ( $usuario, $pending );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				$grupos = 0;
				$count = $inicio;
				
				do {
					$ay = $res->row ( $count );
					$datos [] = $ay;
					if ($ay) {
						if ($count < $totalRes - 1) {
							$ax = $res->row ( $count + 1 );
							if ($ax && $ax->usuario != $ay->usuario) {
								$grupos ++;
							}
						}
					}
					$count ++;
				} while ( $count < $totalRes && $grupos + 1 <= $total );
				return array (
						$totalRes,
						$datos 
				);
			}
		}
		return false;
	}
	public function listarArticulosXVendidosFecha($usuario, $inicio, $total, $pending = false) {
		$query = $this->prepararArticulosXVendidosFecha ( $usuario, $pending );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				$grupos = 0;
				$count = $inicio;
				
				do {
					$ay = $res->row ( $count );
					$datos [] = $ay;
					if ($ay) {
						if ($count < $totalRes - 1) {
							$ax = $res->row ( $count + 1 );
							if ($ax->paquete) {
								if ($ax && $ax->paquete != $ay->paquete) {
									$grupos ++;
								}
							} else {
								if ($ax && $ax->comprador != $ay->comprador) {
									$grupos ++;
								}
							}
						}
					}
					$count ++;
				} while ( $count < $totalRes && $grupos + 1 <= $total );
				return array (
						$totalRes,
						$datos 
				);
			}
		}
		return false;
	}
	public function listarArticulosXEnCompraFecha($usuario, $new = false, $inicio, $total) {
		$query = $this->prepararArticulosXEnCompraFecha ( $usuario, $new );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				for($i = $inicio; $i < $inicio + $total; $i ++) {
					$datos [] = $res->row ( $i );
				}
				return array (
						$totalRes,
						$datos 
				);
			}
		}
		return false;
	}
	public function listarArticulosXEnVentaFecha($usuario, $new = false, $inicio, $total) {
		$query = $this->prepararArticulosXEnVentaFecha ( $usuario, $new );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				for($i = $inicio; $i < $inicio + $total; $i ++) {
					$datos [] = $res->row ( $i );
				}
				return array (
						$totalRes,
						$datos 
				);
			}
		}
		return false;
	}
	public function listarArticulosXNoCompradosFecha($usuario, $inicio, $total) {
		$query = $this->prepararArticulosXNoCompradosFecha ( $usuario );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				for($i = $inicio; $i < $inicio + $total; $i ++) {
					$datos [] = $res->row ( $i );
				}
				return array (
						$totalRes,
						$datos 
				);
			}
		}
		return false;
	}
	public function listarArticulosXNoVendidosFecha($usuario, $inicio, $total) {
		$query = $this->prepararArticulosXNoVendidosFecha ( $usuario );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				for($i = $inicio; $i < $inicio + $total; $i ++) {
					$datos [] = $res->row ( $i );
				}
				return array (
						$totalRes,
						$datos 
				);
			}
		}
		return false;
	}
	public function leerArticulosVendidos($usuario, $inicio = false, $preview = true, $pending = false) {
		if ($preview) {
			$totalpagina = $this->configuracion->variables ( "cantidadVendidos" );
		} else {
			$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		}
		if (! $inicio) {
			$inicio = 0;
		}
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["finalVendidos"] = 0;
		$x = $this->listarArticulosXVendidosFecha ( $usuario, $inicio, $totalpagina, $pending );
		if ($x) {
			list ( $data ["totalVendidos"], $data ["articulosVendidos"] ) = $x;
			$data ["finalVendidos"] = $inicio + count ( $data ["articulosVendidos"] );
			$this->procesarArticulos ( $data ["articulosVendidos"] );
		}
		return $data;
	}
	public function leerArticulosComprados($usuario, $inicio = false, $preview = true, $pending = false) {
		if ($preview) {
			$totalpagina = $this->configuracion->variables ( "cantidadVendidos" );
		} else {
			$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		}
		if (! $inicio) {
			$inicio = 0;
		}
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["finalComprados"] = 0;
		$x = $this->listarArticulosXCompradosFecha ( $usuario, $inicio, $totalpagina, $pending );
		if ($x) {
			list ( $data ["totalComprados"], $data ["articulosComprados"] ) = $x;
			$data ["finalComprados"] = $inicio + count ( $data ["articulosComprados"] );
			$this->procesarArticulos ( $data ["articulosComprados"] );
		}
		return $data;
	}
	public function leerArticulosEnCompra($usuario, $inicio = false, $preview = true, $new = false) {
		if ($preview) {
			$totalpagina = $this->configuracion->variables ( "cantidadVendidos" );
		} else {
			$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		}
		if (! $inicio) {
			$inicio = 0;
		}
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["finalEnCompra"] = 0;
		$x = $this->listarArticulosXEnCompraFecha ( $usuario, $new, $inicio, $totalpagina );
		if ($x) {
			
			list ( $data ["totalEnCompra"], $data ["articulosEnCompra"] ) = $x;
			$data ["countEnCompra"] = (count ( $data ["articulosEnCompra"] ));
			$data ["finalEnCompra"] = $inicio + count ( $data ["articulosEnCompra"] );
			$this->procesarArticulos ( $data ["articulosEnCompra"] );
		}
		return $data;
	}
	public function leerArticulosEnVenta($usuario, $inicio = false, $preview = true, $new = false) {
		if ($preview) {
			$totalpagina = $this->configuracion->variables ( "cantidadVendidos" );
		} else {
			$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		}
		if (! $inicio) {
			$inicio = 0;
		}
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["finalEnVenta"] = 0;
		$x = $this->listarArticulosXEnVentaFecha ( $usuario, $new, $inicio, $totalpagina );
		$data ["ofertasPendientes"] = $this->contarOfertasPendientes ( $usuario );
		if ($x) {
			
			list ( $data ["totalEnVenta"], $data ["articulosEnVenta"] ) = $x;
			$data ["countEnVenta"] = (count ( $data ["articulosEnVenta"] ));
			$data ["finalEnVenta"] = $inicio + count ( $data ["articulosEnVenta"] );
			$this->procesarArticulos ( $data ["articulosEnVenta"] );
		}
		return $data;
	}
	function contarOfertasPendientes($usuario) {
		$this->db->select ( "count(oferta.id) as cantidad" );
		$this->db->where ( array (
				"oferta.estado" => "Pendiente" 
		) );
		$this->db->join ( "articulo", "articulo.id=oferta.articulo and articulo.usuario='$usuario' and articulo.terminado=0 and articulo.tipo='Fijo'", "inner" );
		$this->db->group_by ( "oferta.articulo" );
		$res = $this->darTodos ( "oferta" );
		if ($res) {
			return count ( $res );
		}
		return 0;
	}
	public function leerArticulosNoComprados($usuario, $inicio = false, $preview = true) {
		if ($preview) {
			$totalpagina = $this->configuracion->variables ( "cantidadVendidos" );
		} else {
			$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		}
		if (! $inicio) {
			$inicio = 0;
		}
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["finalNoComprados"] = 0;
		$x = $this->listarArticulosXNoCompradosFecha ( $usuario, $inicio, $totalpagina );
		if ($x) {
			list ( $data ["totalNoComprados"], $data ["articulosNoComprados"] ) = $x;
			$data ["finalNoComprados"] = $inicio + count ( $data ["articulosNoComprados"] );
			$this->procesarArticulos ( $data ["articulosNoComprados"] );
		}
		return $data;
	}
	public function leerArticulosNoVendidos($usuario, $inicio = false, $preview = true) {
		if ($preview) {
			$totalpagina = $this->configuracion->variables ( "cantidadVendidos" );
		} else {
			$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		}
		if (! $inicio) {
			$inicio = 0;
		}
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["finalNoVendidos"] = 0;
		$x = $this->listarArticulosXNoVendidosFecha ( $usuario, $inicio, $totalpagina );
		if ($x) {
			list ( $data ["totalNoVendidos"], $data ["articulosNoVendidos"] ) = $x;
			$data ["finalNoVendidos"] = $inicio + count ( $data ["articulosNoVendidos"] );
			$this->procesarArticulos ( $data ["articulosNoVendidos"] );
		}
		return $data;
	}
	public function leerArticulos($pagina = 1, $criterio = false, $section = false, $orden = false, $ubicacion = false, $categoria = false, $idioma, $usuario = false) {
		$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		$pagina = intval ( $pagina );
		$pagina = $pagina > 0 ? $pagina : 1;
		$inicio = ($pagina - 1) * $totalpagina;
		$data ["categorias"] = $this->darCategorias ( $categoria );
		
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["criterio"] = $criterio;
		switch ($section) {
			case "item" :
				$tipo = "Fijo";
				break;
			case "auction" :
				$tipo = "Subasta";
				break;
			default :
				$tipo = false;
				break;
		}
		
		$x = $this->listarArticulosXCriterioFecha ( $criterio, $tipo, $orden, $ubicacion, $categoria, $idioma, $usuario, $inicio, $totalpagina );
		// print ($this->db->last_query ()) ;
		if ($x) {
			list ( $data ["total"], $data ["articulos"], $categorias, $data ["ciudades"] ) = $x;
			$data ["categorias"] = $this->darCategorias2 ( $categoria, $categorias, true );
			
			$data ["categorias"] = $this->ordenarArbol ( $data ["categorias"] );
			$this->procesarArticulos ( $data ["articulos"] );
		}
		return $data;
	}
	public function listarArticulosPendientes() {
		$this->db->select ( "*,if(tipo<>'Subasta',unix_timestamp ( fecha_registro ) + " . $this->configuracion->variables ( "vencimientoOferta" ) . " * 86400,unix_timestamp ( fecha_registro ) + duracion * 86400) as tiempo" );
		$this->db->having ( "tiempo<=unix_timestamp()" );
		$this->db->where ( array (
				"terminado" => 0 
		) );
		return $this->darTodos ( "articulo" );
	}
	public function cantidadOfertasPendientes($articulo) {
		$this->db->where ( array (
				"articulo" => $articulo,
				"estado" => "Pendiente" 
		) );
		$this->db->from ( "oferta" );
		return $this->db->count_all_results ();
	}
	public function listarOfertas($nolist = array(), $articulo = false, $subasta = false) {
		$this->db->join ( "usuario", "oferta.usuario=usuario.id", "inner" );
		$this->db->join ( "articulo", "oferta.articulo=articulo.id and articulo.terminado=0", "inner" );
		$this->db->select ( "oferta.id as id, oferta.monto as monto, oferta.usuario as usuario_id, usuario.seudonimo as seudonimo, usuario.codigo_oculto as codigo,oferta.articulo as articulo_id,oferta.monto_automatico as monto_automatico" );
		if ($articulo) {
			$this->db->where ( array (
					"articulo" => $articulo 
			) );
		}
		if ($subasta) {
			$this->db->where ( array (
					"articulo.tipo" => "Subasta" 
			) );
		}
		if (is_array ( $nolist ) && count ( $nolist ) > 0) {
			$this->db->where_not_in ( "oferta.id", $nolist );
		}
		if ($subasta) {
			$this->db->order_by ( "monto_automatico desc,fecha asc" );
		} else {
			$this->db->order_by ( "fecha asc" );
		}
		return $this->darTodos ( "oferta" );
	}
	public function siguiendo($articulo, $usuario) {
		$this->db->where ( array (
				"articulo" => $articulo,
				"usuario" => $usuario 
		) );
		return $this->darUno ( "siguiendo" );
	}
	public function seguir($articulo, $usuario) {
		if (! $this->siguiendo ( $articulo, $usuario )) {
			return $this->db->insert ( "siguiendo", array (
					"usuario" => $usuario,
					"articulo" => $articulo,
					"fecha" => date ( "Y-m-d H:s:i" ) 
			) );
		}
		return false;
	}
	public function cantidadOfertas($articulo, $usuario = false) {
		$this->db->select ( "count(oferta.id) as cantidad" );
		$this->db->where ( array (
				"articulo" => $articulo 
		) );
		if ($usuario) {
			$this->db->where ( array (
					"usuario" => $usuario 
			) );
		}
		$this->db->join ( "usuario", "usuario.id=oferta.usuario and usuario.estado<>'Baneado'" );
		return $this->darUno ( "oferta" );
	}
	public function darOfertas($articulo = false, $usuario = false, $subasta = false) {
		$basequery = "select o.id as oferta_id,u.id as user_id,u.seudonimo as seudonimo,u.codigo_oculto as codigo,o.monto as monto,o.fecha as fecha,o.estado as estado,u.positivo as positivo,u.negativo as negativo,o.monto_automatico as monto_automatico from oferta as o inner join usuario as u on u.id=o.usuario and u.estado<>'Baneado'";
		$baseorderby = "order by o.monto desc, o.id asc";
		if ($subasta) {
			$baseorderby = "order by o.monto_automatico desc, o.id asc";
		}
		$basewhere = $articulo ? " where o.articulo=$articulo" : "";
		$res = false;
		if ($usuario !== true) {
			$res = $this->db->query ( $basequery . " $basewhere $baseorderby" );
		}
		if ($usuario !== false) {
			$res = $this->db->query ( "$basequery and u.id='$usuario' $basewhere $baseorderby" );
		}
		if ($res) {
			if ($res) {
				$res = $res->result ();
				if ($res && is_array ( $res ) && count ( $res ) > 0) {
					return $res;
				}
			}
		}
		if ($articulo) {
			$this->db->where ( array (
					"articulo" => $articulo 
			) );
		}
		$this->db->join ( "usuario", "usuario.id=oferta.usuario and usuario.estado<>'Baneado'", "inner" );
		$orderby = "oferta.monto desc, oferta.id asc";
		if ($subasta) {
			$orderby = "oferta.monto_automatico desc, oferta.id asc";
		}
		$this->db->order_by ( $orderby );
		return $this->darTodos ( "oferta" );
	}
	public function maximaOferta($articulo, $usuario = false) {
		$this->db->select ( "max(monto) as cantidad" );
		$this->db->where ( array (
				"articulo" => $articulo 
		) );
		if ($usuario) {
			$this->db->where ( array (
					"usuario" => $usuario 
			) );
		}
		$this->db->join ( "usuario", "usuario.id=oferta.usuario and usuario.estado<>'Baneado'", "inner" );
		return $this->darUno ( "oferta" );
	}
	public function mayorOferta($articulo, $subasta = false) {
		$this->db->select ( "oferta.id,monto,usuario,monto_automatico" );
		$this->db->where ( array (
				"articulo" => $articulo 
		) );
		$this->db->join ( "usuario", "usuario.id=oferta.usuario && usuario.estado<>'Baneado'", "inner" );
		if (! $subasta) {
			$this->db->order_by ( "monto desc" );
		} else {
			$this->db->order_by ( "monto_automatico desc,oferta.id asc" );
		}
		$o = $this->darUno ( "oferta" );
		if ($o) {
			$c = $this->cantidadOfertas ( $articulo );
			if ($c->cantidad !== false) {
				$o->cantidad = $c->cantidad;
			}
			return $o;
		}
		return false;
	}
	public function ultimaOferta($articulo, $usuario) {
		$this->db->select ( "oferta.*" );
		$this->db->where ( array (
				"articulo" => $articulo,
				"usuario" => $usuario 
		) );
		$this->db->join ( "usuario", "usuario.id=oferta.usuario && usuario.estado<>'Baneado'", "inner" );
		$this->db->order_by ( "oferta.id desc" );
		$o = $this->darUno ( "oferta" );
		if ($o) {
			$c = $this->cantidadOfertas ( $articulo, $usuario );
			if ($c->cantidad !== false) {
				$o->cantidad = $c->cantidad;
			}
			return $o;
		}
		return false;
	}
	public function adicionarPaqueteSimilar($articulo) {
		if ($articulo) {
			$p = $this->paqueteSimilar ( $articulo );
			$CI = &get_instance ();
			$CI->load->model ( "Usuario_model", "usuario" );
			$articulo->usuario = $CI->usuario->darUsuarioXId ( $articulo->usuario );
			$articulo->usuario->pais = $articulo->usuario->darPais ( $articulo->usuario->pais );
			$articulo->comprador = $CI->usuario->darUsuarioXId ( $articulo->comprador );
			$articulo->comprador->pais = $articulo->comprador->darPais ( $articulo->comprador->pais );
			$articulos = array ();
			if ($p) {
				if (trim ( $p->articulos ) !== "") {
					$articulos = explode ( ",", $p->articulos );
				}
				$articulos [] = $articulo->id;
				$gastos = intval ( $p->gastos_envio );
				$monto = intval ( $p->monto ) + ($articulo->precio_oferta ? $articulo->precio_oferta : $articulo->precio);
				if ($articulo->usuario->pais == $articulo->comprador->pais) {
					$gastos += floatval ( $articulo->gastos_pais );
				} elseif ($articulo->usuario->pais->continente == $articulo->comprador->pais->continente) {
					$gastos += floatval ( $articulo->gastos_continente );
				} elseif ($articulo->envio_local) {
					$gastos += 0;
				} else {
					$gastos += floatval ( $articulo->gastos_todos );
				}
				$this->db->update ( "paquete", array (
						"articulos" => implode ( ",", $articulos ),
						"gastos_envio" => $gastos,
						"monto" => $monto 
				), array (
						"id" => $p->id 
				) );
				return $p->id;
			} else {
				$articulos [] = $articulo->id;
				$gastos = 0;
				$monto = 0 + ($articulo->precio_oferta ? $articulo->precio_oferta : $articulo->precio);
				if ($articulo->usuario->pais == $articulo->comprador->pais) {
					$gastos += floatval ( $articulo->gastos_pais );
				} elseif ($articulo->usuario->pais->continente == $articulo->comprador->pais->continente) {
					$gastos += floatval ( $articulo->gastos_continente );
				} elseif ($articulo->envio_local) {
					$gastos += 0;
				} else {
					$gastos += floatval ( $articulo->gastos_todos );
				}
				$this->db->insert ( "paquete", array (
						"vendedor" => $articulo->usuario->id,
						"comprador" => $articulo->comprador->id,
						"fecha" => date ( "Y-m-d H:i:s" ),
						"articulos" => implode ( ",", $articulos ),
						"gastos_envio" => $gastos,
						"monto" => $monto 
				) );
				return $this->db->insert_id ();
			}
		}
		return false;
	}
	public function adicionarPaqueteSimilarTransaccion($transaccion) {
		if ($transaccion) {
			$articulo = $this->darArticulo ( $transaccion->articulo );
			$p = $this->darPaquete ( $transaccion->paquete );
			if (! $p) {
				$articulo->comprador = $transaccion->comprador;
				$p = $this->paqueteSimilar ( $articulo );
			}
			$CI = &get_instance ();
			$transacciones = array ();
			$CI->load->model ( "Usuario_model", "usuario" );
			$articulo->usuario = $CI->usuario->darUsuarioXId ( $articulo->usuario );
			$articulo->usuario->pais = $articulo->usuario->darPais ( $articulo->usuario->pais );
			$transaccion->comprador = $CI->usuario->darUsuarioXId ( $transaccion->comprador );
			$transaccion->comprador->pais = $transaccion->comprador->darPais ( $transaccion->comprador->pais );
			$pid = false;
			if ($p) {
				if (trim ( $p->transacciones ) !== "") {
					$transacciones = explode ( ",", $p->transacciones );
				}
				$transacciones [] = $transaccion->id;
				
				$gastos = intval ( $p->gastos_envio );
				$monto = intval ( $p->monto ) + $transaccion->precio * $transaccion->cantidad;
				if ($articulo->usuario->pais == $transaccion->comprador->pais) {
					$gastos += intval ( $articulo->gastos_pais * $transaccion->cantidad );
				} elseif ($articulo->usuario->pais->continente == $transaccion->comprador->pais->continente) {
					$gastos += intval ( $articulo->gastos_continente * $transaccion->cantidad );
				} elseif ($articulo->envio_local) {
					$gastos += 0;
				} else {
					$gastos += intval ( $articulo->gastos_todos * $transaccion->cantidad );
				}
				/*
				 * var_dump ( $gastos ); var_dump ( $monto ); var_dump ( $p->id
				 * );
				 */
				$this->db->update ( "paquete", array (
						"transacciones" => implode ( ",", $transacciones ),
						"gastos_envio" => $gastos,
						"monto" => $monto 
				), array (
						"id" => $p->id 
				) );
				$pid = $p->id;
			} else {
				$transacciones [] = $transaccion->id;
				$gastos = 0;
				$monto = 0 + $transaccion->precio * $transaccion->cantidad;
				if ($articulo->usuario->pais == $transaccion->comprador->pais) {
					$gastos += intval ( $articulo->gastos_pais * $transaccion->cantidad );
				} elseif ($articulo->usuario->pais->continente == $transaccion->comprador->pais->continente) {
					$gastos += intval ( $articulo->gastos_continente * $transaccion->cantidad );
				} elseif ($articulo->envio_local) {
					$gastos += 0;
				} else {
					$gastos += intval ( $articulo->gastos_todos * $transaccion->cantidad );
				}
				/*
				 * var_dump ( $gastos ); var_dump ( $monto );
				 */
				$this->db->insert ( "paquete", array (
						"vendedor" => $articulo->usuario->id,
						"comprador" => $transaccion->comprador->id,
						"fecha" => date ( "Y-m-d H:i:s" ),
						"transacciones" => implode ( ",", $transacciones ),
						"gastos_envio" => $gastos,
						"monto" => $monto 
				) );
				$pid = $this->db->insert_id ();
			}
			if ($pid) {
				$this->db->update ( "transaccion", array (
						"paquete" => $pid 
				), array (
						"id" => $transaccion->id 
				) );
			}
			return $pid;
		}
		return false;
	}
	public function eliminarPaqueteSimilar($articulo) {
		$p = $this->paqueteSimilar ( $articulo );
		
		if ($p) {
			$articulos = explode ( ",", $p->articulos );
			foreach ( $articulos as $a ) {
				$this->db->update ( "articulo", array (
						"estado" => "Sin gastos Envio",
						"paquete" => null 
				), array (
						"id" => $a 
				) );
			}
			$transacciones = explode ( ",", $p->transacciones );
			foreach ( $transacciones as $t ) {
				$this->db->update ( "transaccion", array (
						"estado" => "Sin gastos Envio",
						"paquete" => null 
				), array (
						"id" => $t 
				) );
			}
			$this->db->delete ( "paquete", array (
					"id" => $p->id 
			) );
		}
	}
	public function paqueteSimilar($articulo) {
		if (is_object ( $articulo->comprador )) {
			$comprador = $articulo->comprador->id;
		} else {
			$comprador = $articulo->comprador;
		}
		$sql = "SELECT paquete.monto,paquete.gastos_envio,paquete.id,paquete.articulos,paquete.transacciones FROM paquete inner join articulo on articulo.paquete=paquete.id and articulo.pagos='$articulo->pagos' WHERE paquete.estado='Sin pago' and paquete.vendedor='$articulo->usuario' and paquete.comprador='$comprador'  and (paquete.denuncia2=0 or isnull(paquete.denuncia2) ) group by paquete.id";
		// print $sql;
		$r = $this->db->query ( $sql )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			return $r [0];
		}
		$sql = "SELECT paquete.monto,paquete.gastos_envio,paquete.id,paquete.articulos,paquete.transacciones
FROM paquete 
inner join transaccion on transaccion.paquete=paquete.id
inner join articulo on articulo.id=transaccion.articulo and articulo.pagos='$articulo->pagos' 
WHERE paquete.estado='Sin pago' and paquete.vendedor='$articulo->usuario' and paquete.comprador='$comprador'  and (paquete.denuncia2=0 or isnull(paquete.denuncia2)) group by paquete.id";
		$r = $this->db->query ( $sql )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			return $r [0];
		}
		return false;
	}
	public function articuloPaquete($id) {
		$r = $this->db->query ( "select * from paquete where articulos like '$id' or articulos like '%,$id' or articulos like '$id,%' or articulos like '%,$id,%'" )->result ();
		return ($r && is_array ( $r ) && count ( $r ) > 0) ? $r [0] : false;
	}
	public function transaccionPaquete($id) {
		$r = $this->db->query ( "select * from paquete where transacciones like '$id' or transacciones like '%,$id' or transacciones like '$id,%' or transacciones like '%,$id,%'" )->result ();
		return ($r && is_array ( $r ) && count ( $r ) > 0) ? $r [0] : false;
	}
	public function darPaquete($paquete) {
		$this->db->where ( array (
				"id" => $paquete 
		) );
		return $this->darUno ( "paquete" );
	}
	function prepararArticulosPorComprar($comprador, $vendedor, $paquete = false, $pagos = false) {
		if ($vendedor) {
			$jextra = "";
			$wextra = "";
			$jextra2 = "";
			$wextra2 = "";
			if ($paquete) {
				$wextra = " articulo.paquete='$paquete'";
				$wextra2 = " transaccion.paquete='$paquete'";
			}
			if (! $wextra) {
				$wextra = "terminado = 1 and articulo.estado<>'A la venta' and articulo.estado<>'Finalizado' and articulo.usuario='$vendedor' and isnull(articulo.paquete)";
				$wextra2 = "articulo.usuario='$vendedor' and isnull(transaccion.paquete) and transaccion.estado<>'Finalizado'";
				$jextra = "INNER JOIN usuario ON usuario.id=articulo.comprador and usuario.id='$comprador'";
				$jextra2 = "INNER JOIN usuario ON usuario.id=transaccion.comprador and usuario.id='$comprador'";
			}
			
			if ($pagos) {
				$wextra .= " and articulo.pagos='" . str_replace ( "-", ",", $pagos ) . "'";
				$wextra2 .= " and articulo.pagos='" . str_replace ( "-", ",", $pagos ) . "'";
			}
			/*
			 * return "SELECT
			 * articulo.id,articulo.titulo,articulo.tipo,if(isnull(articulo.precio_oferta),articulo.precio,articulo.precio_oferta)
			 * as
			 * precio,articulo.fecha_registro,articulo.duracion,articulo.usuario,articulo.foto,
			 * articulo.categoria as
			 * categoria,articulo.comprador,articulo.fecha_terminado,articulo.estado,
			 * (if(isnull(articulo.paquete),null,(select gastos_envio from
			 * paquete where paquete.id=articulo.paquete)))as
			 * gastos_envio,articulo.pagos FROM articulo $jextra WHERE $wextra
			 * ORDER BY articulo.titulo desc";
			 */
			
			return "select * from ((SELECT 
articulo.cantidad,
articulo.id,
articulo.titulo,
articulo.tipo,
if(isnull(articulo.precio_oferta),articulo.precio,articulo.precio_oferta) as precio,
articulo.fecha_registro,
articulo.duracion,
articulo.usuario,
articulo.foto, 
articulo.categoria as categoria,
articulo.comprador,
articulo.fecha_terminado,
articulo.estado, 
(if(isnull(articulo.paquete),null,(select gastos_envio from paquete where paquete.id=articulo.paquete)))as gastos_envio,
articulo.pagos,
null as transaccion

FROM articulo 
$jextra 
WHERE $wextra)
union
(SELECT 
transaccion.cantidad,
transaccion.articulo,
articulo.titulo,
articulo.tipo,
transaccion.precio*transaccion.cantidad,
articulo.fecha_registro,
articulo.duracion,
articulo.usuario,
articulo.foto, 
articulo.categoria as categoria,
transaccion.comprador,
transaccion.fecha_terminado,
transaccion.estado, 
(if(isnull(transaccion.paquete),null,(select gastos_envio from paquete where paquete.id=transaccion.paquete)))as gastos_envio,
articulo.pagos,
transaccion.id as transaccion 

FROM transaccion
INNER JOIN articulo ON transaccion.articulo=articulo.id
$jextra2
WHERE  $wextra2))as articulo 
ORDER BY articulo.titulo desc";
		}
		return false;
	}
	public function listarArticulosPorComprar($comprador, $vendedor, $paquete = false, $pagos = false) {
		$query = $this->prepararArticulosPorComprar ( $comprador, $vendedor, $paquete, $pagos );
		// print $query;
		$res = $this->db->query ( $query );
		$data = $this->darResuts ( $res );
		$this->procesarArticulos ( $data );
		return $data;
	}
	public function obtenerVendidosDeCantidad($articulo) {
		$this->db->where ( array (
				"articulo" => $articulo 
		) );
		$this->db->select ( "count(id) as cantidad" );
		$r = $this->db->get ( "transaccion" )->result ();
		return ($r && is_array ( $r ) && count ( $r ) > 0) ? $r [0]->cantidad : 0;
	}
	public function comprar($articulo, $usuario) {
		$a = $this->darArticulo ( $articulo );
		if ($a && $a->terminado !== 1 && $a->estado == "A la venta") {
			if ($a->tipo !== "Cantidad") {
				$res = $this->db->update ( "oferta", array (
						"estado" => "Rechazado" 
				), array (
						"articulo" => $articulo 
				) );
				if ($res) {
					$a->comprador = $usuario;
					$pid = $this->adicionarPaqueteSimilar ( $a );
					// $this->eliminarPaqueteSimilar ( $a );
					if ($this->db->update ( "articulo", array (
							"paquete" => $pid,
							"estado" => "Sin Pago",
							"fecha_terminado" => date ( "Y-m-d H:i:s" ),
							"terminado" => 1,
							"comprador" => $usuario 
					), array (
							"id" => $articulo 
					) )) {
						return $articulo;
					}
				}
			} else {
				$cantidad = $this->input->post ( "cantidad" );
				$cantidad = intval ( $cantidad );
				if ($cantidad > $a->cantidad) {
					$cantidad = $a->cantidad;
				}
				if ($cantidad <= 0) {
					return false;
				}
				$a->comprador = $usuario;
				$this->db->where ( array (
						"articulo" => $a->id,
						"comprador" => $usuario,
						"estado" => "Sin Pago" 
				) );
				$r = $this->db->get ( "transaccion" )->result ();
				$tid = false;
				if ($r && is_array ( $r ) && count ( $r ) > 0) {
					$t = $r [0];
					$this->db->update ( "transaccion", array (
							"fecha_terminado" => date ( "Y-m-d H:i:s" ),
							"cantidad" => $t->cantidad + $cantidad 
					), array (
							"id" => $t->id 
					) );
					$t->cantidad = $cantidad;
					$pid = $this->adicionarPaqueteSimilarTransaccion ( $t );
					$tid = $t->id;
				} else {
					$this->db->insert ( "transaccion", array (
							"articulo" => $articulo,
							"precio" => $a->precio,
							"moneda" => $a->moneda,
							"estado" => "Sin Pago",
							"fecha_terminado" => date ( "Y-m-d H:i:s" ),
							"comprador" => $usuario,
							"cantidad" => $cantidad 
					) );
					$tid = $this->db->insert_id ();
					$rr = $this->db->where ( array (
							"id" => $tid 
					) )->get ( "transaccion" )->result ();
					if ($rr && is_array ( $rr ) && count ( $rr ) > 0) {
						$pid = $this->adicionarPaqueteSimilarTransaccion ( $rr [0] );
					}
				}
				if ($a->cantidad - $cantidad > 0) {
					if ($this->db->update ( "articulo", array (
							"cantidad" => $a->cantidad - $cantidad 
					), array (
							"id" => $articulo 
					) )) {
						return $tid;
					}
				} else {
					if ($this->db->update ( "articulo", array (
							"cantidad" => 0,
							"terminado" => 1,
							"fecha_terminado" => date ( "Y-m-d H:i:S" ) 
					), array (
							"id" => $articulo 
					) )) {
						return $tid;
					}
				}
			}
		}
		return false;
	}
	public function datosOferta($articulo, $usuario) {
		$this->db->select ( "count(oferta.id) as cantidad,max(monto) as maximo" );
		$this->db->where ( array (
				"articulo" => $articulo,
				"usuario" => $usuario 
		) );
		$this->db->join ( "usuario", "usuario.id=oferta.usuario and usuario.estado<>'Baneado'", "inner" );
		return $this->darUno ( "oferta" );
	}
	public function darOfertaGanadora($articulo) {
		$this->db->select ( "monto,monto_automatico" );
		$this->db->where ( array (
				"articulo" => $articulo,
				"oferta.estado" => "Aceptado" 
		) );
		$this->db->join ( "usuario", "usuario.id=oferta.usuario and usuario.estado<>'Baneado'", "inner" );
		return $this->darUno ( "oferta" );
	}
	public function aceptarOferta($oferta, $articulo, $subasta = false) {
		$a = $this->darArticulo ( $articulo );
		if ($a && $a->terminado !== 1) {
			$res = $this->db->update ( "oferta", array (
					"estado" => "Rechazado" 
			), array (
					"articulo" => $articulo,
					"id !=" => $oferta 
			) );
			if ($res) {
				$res = $this->db->update ( "oferta", array (
						"estado" => "Aceptado" 
				), array (
						"id" => $oferta 
				) );
				if ($res) {
					$this->db->select ( "usuario,monto,monto_automatico" );
					$this->db->where ( array (
							"id" => $oferta 
					) );
					$oferta = $this->darUno ( "oferta" );
					
					// var_dump($expression);
					if ($oferta) {
						$CI = &get_instance ();
						$a->comprador = $oferta->usuario;
						$pid = $this->adicionarPaqueteSimilar ( $a );
						return $this->db->update ( "articulo", array (
								"paquete" => $pid,
								"estado" => "Sin Pago",
								"fecha_terminado" => date ( "Y-m-d H:i:s" ),
								"terminado" => 1,
								"comprador" => $oferta->usuario,
								"precio_oferta" => ($subasta ? $oferta->monto_automatico : $oferta->monto) 
						), array (
								"id" => $articulo 
						) );
					}
				}
			}
		}
		return false;
	}
	public function rechazarOferta($oferta, $articulo) {
		$a = $this->darArticulo ( $articulo );
		if ($a && $a->terminado !== 1) {
			return $this->db->update ( "oferta", array (
					"estado" => "Rechazado" 
			), array (
					"id =" => $oferta 
			) );
		}
		return false;
	}
	public function desactivarOfertasVistos($articulo) {
		/*
		 * if ($articulo) { $this->db->update ( "articulo", array (
		 * "ofertas_visto" => 0 ), array ( "id" => $articulo ) ); }
		 */
	}
	public function activarOfertasVistos($articulo) {
		/*
		 * if ($articulo) { $this->db->update ( "articulo", array (
		 * "ofertas_visto" => 1 ), array ( "id" => $articulo ) ); }
		 */
	}
	public function ofertar($articulo, $usuario, $monto) {
		$a = $this->darArticulo ( $articulo );
		if ($a && $a->estado == "A la venta") {
			$monto = $this->parseDecimal ( $monto );
			$c = $this->datosOferta ( $articulo, $usuario );
			$m = $this->configuracion->variables ( "maximoCantidad" );
			if ($c && $c->cantidad < $m) {
				$uo = $this->ultimaOferta ( $articulo, $usuario );
				if (! $uo || ($uo && $uo->monto < $monto)) {
					$datos = array (
							"monto" => $monto,
							"usuario" => $usuario,
							"articulo" => $articulo,
							"fecha" => date ( "Y-m-d H:i:s" ) 
					);
					if ($a->precio_rechazo && $a->precio_rechazo > $monto) {
						$datos ["estado"] = "Rechazado";
					}
					if ($this->db->insert ( "oferta", $datos )) {
						$this->desactivarOfertasVistos ( $articulo );
						return array (
								1,
								$m - 1 - $c->cantidad,
								$a->precio_rechazo && $a->precio_rechazo > $monto 
						);
					}
				} else {
					return array (
							3,
							0,
							false 
					);
				}
			}
			return array (
					0,
					0,
					false 
			);
		}
		return array (
				2,
				0,
				false 
		);
	}
	private function enviarMailSobrepujados($articulo, $usuario) {
		$emails = $this->db->query ( "SELECT usuario.email as email FROM `oferta` inner join usuario on oferta.usuario=usuario.id WHERE oferta.articulo=$articulo->id and oferta.usuario<>$usuario group by oferta.usuario" );
		if ($emails) {
			$emails = $emails->result ();
			if ($emails && is_array ( $emails ) && count ( $emails ) > 0) {
				$this->load->library ( "myemail" );
				foreach ( $emails as $e ) {
					$this->myemail->enviarTemplate ( $e->email, "Te han sobrepujado", "mail/sobre-pujado", array (
							"url" => base_url () . "product/$articulo->id - " . normalizarTexto ( $articulo->titulo ),
							"titulo" => $articulo->titulo 
					) );
				}
			}
		}
	}
	public function pujar($articulo, $usuario, $monto, $oferta = true) {
		$a = $this->darArticulo ( $articulo );
		if ($a && $a->estado == "A la venta") {
			$monto = $this->parseDecimal ( $monto );
			$c = $this->mayorOferta ( $articulo, true );
			$m = $this->maximaOferta ( $articulo, $usuario );
			$monto_automatico = $a->precio;
			$minimoMonto = $a->precio;
			$ganador = false;
			$mismoMonto = false;
			if ($c) {
				if ($m->cantidad >= $monto) {
					return 3; // tu oferta no puede ser menor a la que ofertaste
						          // antes;
				}
				
				if ($c->cantidad > 0) {
					if ($c->monto_automatico >= $monto) {
						return 0; // no se alcanzo el minimo
					} else {
						if ($c->monto > $monto) {
							$datos = array (
									"monto" => $c->monto,
									"usuario" => $c->usuario,
									"articulo" => $articulo,
									"fecha" => date ( "Y-m-d H:i:s" ),
									"tipo" => "Subasta",
									"monto_automatico" => $monto + 0.5 
							);
							$this->db->insert ( "oferta", $datos );
							$monto_automatico = $monto;
						} elseif ($c->monto == $monto) {
							$datos = array (
									"monto" => $c->monto,
									"usuario" => $c->usuario,
									"articulo" => $articulo,
									"fecha" => date ( "Y-m-d H:i:s" ),
									"tipo" => "Subasta",
									"monto_automatico" => $monto 
							);
							$this->db->insert ( "oferta", $datos );
							$monto_automatico = $monto;
						} else {
							if ($c->usuario == $usuario) {
								$monto_automatico = $c->monto_automatico;
								$mismoMonto = true;
							} else {
								$monto_automatico = $c->monto + 0.5;
								$ganador = true;
							}
						}
					}
				}
			} else {
				if ($minimoMonto > $monto) {
					return 0;
				}
			}
			if (! $mismoMonto) {
				$datos = array (
						"monto" => $monto,
						"usuario" => $usuario,
						"articulo" => $articulo,
						"fecha" => date ( "Y-m-d H:i:s" ),
						"tipo" => "Subasta",
						"monto_automatico" => $monto_automatico 
				);
				$exito = false;
				if ($this->db->insert ( "oferta", $datos )) {
					if ($c && $ganador) {
						$this->load->model ( "usuario_model", "umodel" );
						$u = $this->umodel->darUsuarioXId ( $c->usuario );
						if ($u) {
							$this->load->library ( "myemail" );
							$this->myemail->enviarTemplate ( $u->email, "Te han sobrepujado", "mail/sobre-pujado", array (
									"url" => base_url () . "product/$a->id - " . normalizarTexto ( $a->titulo ),
									"titulo" => $a->titulo 
							) );
						}
					}
					$exito = true;
				}
			} else {
				if ($this->db->update ( "oferta", array (
						"monto" => $monto 
				), array (
						"id" => $c->id 
				) )) {
					$exito = true;
				}
			}
			if ($exito) {
				$this->desactivarOfertasVistos ( $articulo );
				return 1;
			}
		}
		return 2;
	}
	public function finalizar($articulo, $estado = false, $transaccion = false) {
		if (! $transaccion) {
			$a = $this->darArticulo ( $articulo );
			if ($a) {
				if ($a->terminado == 0) {
					$this->quitarCantidad ( $a->categoria );
					$x = array (
							"terminado" => 1,
							"fecha_terminado" => date ( "Y-m-d H:i:s" ) 
					);
					if ($estado) {
						$x ["estado"] = $estado;
					}
					$x = $this->db->update ( "articulo", $x, array (
							"id" => $articulo 
					) );
					if ($x && $a->estado = "A la venta" && ! $estado) {
						$this->db->update ( "oferta", array (
								"estado" => "Rechazado" 
						), array (
								"articulo" => $a->id 
						) );
					}
					return $x;
				} else {
					$this->db->update ( "articulo", array (
							"terminado" => 1,
							"fecha_terminado" => date ( "Y-m-d H:i:s" ),
							"estado" => $estado 
					), array (
							"id" => $articulo 
					) );
				}
			}
		} else {
			$this->db->update ( "transaccion", array (
					"fecha_terminado" => date ( "Y-m-d H:i:s" ),
					"estado" => $estado 
			), array (
					"id" => $articulo 
			) );
		}
		return false;
	}
	public function comenzar($articulo) {
		$a = $this->darArticulo ( $articulo );
		if ($a && $a->terminado == 1) {
			$this->adicionarCantidad ( $a->categoria );
			if ($this->db->update ( "articulo", array (
					"terminado" => 0,
					"fecha_registro" => date ( "Y-m-d H:i:s" ) 
			), array (
					"id" => $articulo 
			) )) {
				$this->db->delete ( "oferta", array (
						"articulo" => $a->id 
				) );
				return $a;
			}
		}
		return false;
	}
	public function parseDecimal($n) {
		if ($n !== null) {
			return str_replace ( ",", ".", "" . $n );
		}
		return $n;
	}
	public function listarDirectorio() {
		$f = scandir ( BASEPATH . "../files/temporal/" );
		array_shift ( $f );
		array_shift ( $f );
		return $f;
	}
	public function registrar($modificar = false, $vehiculo = false, $mascota = false, $vivienda = false) {
		$this->precio = $this->parseDecimal ( $this->precio );
		$datos = array (
				"usuario" => $this->usuario,
				"titulo" => $this->titulo,
				"descripcion" => strip_not_allowed ( $this->descripcion, "form,marquee,script,input,textarea,button" ),
				"categoria" => $this->categoria,
				"foto" => $this->foto,
				"precio" => $this->precio,
				"moneda" => $this->moneda,
				"contactar_con" => $this->contactar_con,
				"ciudad" => $this->ciudad 
		);
		if ($modificar) {
			$this->db->where ( array (
					"id" => $this->id 
			) );
			$this->db->select ( "categoria" );
			$c = $this->darUno ( "articulo" );
			if ($c) {
				if ($this->categoria !== $c->categoria) {
					$this->quitarCantidad ( $c->categoria );
					$this->adicionarCantidad ( $this->categoria );
				}
			}
			if (is_object ( $vehiculo )) {
				if (! $mascota && ! $vivienda) {
					$this->db->update ( "vehiculo", $vehiculo, array (
							"articulo" => $this->id 
					) );
				} elseif (! $vivienda) {
					$this->db->update ( "mascota", $vehiculo, array (
							"articulo" => $this->id 
					) );
				} else {
					$this->db->update ( "vivienda", $vehiculo, array (
							"articulo" => $this->id 
					) );
				}
			}
			return $this->db->update ( "articulo", $datos, array (
					"id" => $this->id 
			) );
		} else {
			$datos ["fecha_registro"] = $this->fecha_registro;
			$datos ["estado"] = $this->estado;
			if ($this->categoria) {
				$this->adicionarCantidad ( $this->categoria );
			}
			if ($this->db->insert ( "articulo", $datos )) {
				$this->id = $this->db->insert_id ();
				if (is_object ( $vehiculo )) {
					$vehiculo->articulo = $this->id;
					if (! $mascota && ! $vivienda) {
						$this->db->insert ( "vehiculo", $vehiculo );
					} elseif (! $vivienda) {
						$this->db->insert ( "mascota", $vehiculo );
					} else {
						$this->db->insert ( "vivienda", $vehiculo );
					}
				}
				return $this->id;
			}
		}
		
		return false;
	}
	public function darArticulo($id) {
		$this->db->where ( array (
				"id" => $id 
		) );
		
		$a = $this->darUno ( "articulo" );
		if (is_object ( $a )) {
			$this->db->where ( array (
					"articulo" => $id 
			) );
			$a->vehiculo = $this->darUno ( "vehiculo" );
			$this->db->where ( array (
					"articulo" => $id 
			) );
			$a->mascota = $this->darUno ( "mascota" );
			$this->db->where ( array (
					"articulo" => $id 
			) );
			$a->vivienda = $this->darUno ( "vivienda" );
			$this->db->where ( array (
					"id" => $a->ciudad 
			) );
			$a->ciudad = $this->darUno ( "ciudad" );
		}
		return $a;
	}
	function listarArticulosXUsuarioXNoventa($usuario, $categoria = false, $inicio = 0, $total = 10) {
		$this->db->where ( array (
				"usuario" => $usuario,
				"terminado" => 1,
				"articulo.estado" => "A la venta" 
		) );
		return $this->listarArticulos ( $categoria, $inicio, $total );
	}
	function listarArticulosXUsuario($usuario, $categoria = false, $inicio = 0, $total = 10) {
		$this->db->where ( array (
				"usuario" => $usuario,
				"terminado" => 0 
		) );
		return $this->listarArticulos ( $categoria, $inicio, $total );
	}
	public function listarArticulos($categoria = false, $inicio = 0, $total = 10) {
		if ($categoria) {
			$this->db->where ( array (
					"categoria" => $categoria 
			) );
		}
		$this->db->select ( "articulo.*,pais.nombre as pais_nombre" );
		$this->db->join ( "usuario", "usuario.id=articulo.usuario", "inner" );
		$this->db->join ( "pais", "pais.codigo3=usuario.pais", "inner" );
		$this->db->order_by ( "articulo.id", "desc" );
		return $this->darTodos ( "articulo", $total, $inicio );
	}
	// no temporal mas relevante
	public function listarArticulosXCriterioRelevante($criterio = false, $tipo = false, $categoria = false, $inicio = 0, $total = 10) {
		$criterio = explode ( " ", trim ( preg_replace ( "/(\s+)/im", " ", $criterio ) ) );
		if ($criterio && is_array ( $criterio ) && count ( $criterio ) > 0) {
			$datos = array ();
			$ands = array_merge ( array (), $criterio );
			$ors = array ();
			$notin = array ();
			$extra = "";
			$initialAnds = count ( $ands );
			do {
				if (count ( $ands ) > 0 || count ( $ors ) > 0) {
					$extra = "and (";
					if (count ( $ands ) > 0) {
						$extra .= "(";
						$tands = array ();
						foreach ( $ands as $a ) {
							$tands [] = "titulo like '%$a%'";
						}
						$extra .= implode ( " and ", $tands );
						$extra .= ")";
					}
					if (count ( $ands ) == 1 && count ( $ors ) > 0) {
						$extra .= " or ";
						$extra .= "(";
						$tors = array ();
						foreach ( $ors as $o ) {
							$tors [] = "titulo like '%$o%'";
						}
						$extra .= implode ( " or ", $tors );
						$extra .= ")";
					}
					$extra .= ")";
				}
				if ($tipo) {
					$extra .= " and tipo='$tipo'";
				}
				if (count ( $notin ) > 0) {
					$extra .= " and articulo.id not in(" . implode ( ",", $notin ) . ")";
				}
				$query = "SELECT articulo.*, pais.nombre as pais_nombre
				FROM (articulo)
				INNER JOIN usuario ON usuario.id=articulo.usuario
				INNER JOIN pais ON pais.codigo3=usuario.pais
				WHERE terminado = 0
				$extra
				ORDER BY fecha_registro desc
				LIMIT $total";
				$res = $this->db->query ( $query );
				$res = $this->darResuts ( $res );
				$cantidad = $res ? count ( $res ) : 0;
				array_push ( $ors, array_pop ( $ands ) );
				if ($cantidad > 0) {
					if ($cantidad < $total) {
						foreach ( $res as $r ) {
							$notin [] = $r->id;
						}
					}
					$datos = array_merge ( $datos, $res );
				}
				$total = $total - $cantidad;
			} while ( $cantidad < $total && count ( $ors ) < $initialAnds );
			return $datos;
		}
		return false;
	}
	public function prepararConsultaArticuloXCriterioFecha($criterio = false, $tipo = false, $orden = false, $ubicacion = false, $categoria = false, $idioma = false, $usuario = false) {
		$CI = &get_instance ();
		$ciudad = $CI->input->get ( "ciudad" );
		$cextra = "";
		if ($ciudad) {
			$cextra = " and ciudad.id='$ciudad'";
		}
		if (trim ( $orden ) === "" && $usuario) {
			$orden = "finaliza";
		}
		$criterio = explode ( " ", trim ( preg_replace ( "/(\s+)/im", " ", $criterio ) ) );
		if ($criterio && is_array ( $criterio ) && count ( $criterio ) > 0) {
			$precio = "articulo.precio";
			$orderby = "ORDER BY fecha_registro desc";
			$adicionalSelect = "";
			$extra = "";
			if ($ubicacion) {
				$ubicacion = explode ( "-", $ubicacion );
				if (count ( $ubicacion ) > 1) {
					switch ($ubicacion [0]) {
						case "P" :
							$extra .= " and pais.codigo3='" . $ubicacion [1] . "' ";
							break;
						
						default :
							$extra .= " and pais.continente='" . $ubicacion [1] . "' ";
							break;
					}
				}
			}
			switch ($orden) {
				case "finaliza" :
					$orderby = "ORDER BY tiempo asc";
					$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
					$adicionalSelect = ",unix_timestamp(articulo.fecha_registro) as tiempo";
					break;
				case "mas-alto" :
					$precio = "if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "ORDER BY precio desc";
					break;
				case "mas-bajo" :
					$precio = "if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "ORDER BY precio asc";
					break;
			}
			$ors = array_merge ( array (), $criterio );
			if (count ( $ors ) > 0) {
				$extra .= "and (";
				if (count ( $ors ) > 0) {
					$extra .= "(";
					$tors = array ();
					foreach ( $ors as $o ) {
						$tors [] = "titulo like '%" . str_replace ( "'", "\\'", $o ) . "%'";
					}
					$extra .= implode ( " and ", $tors );
					$extra .= ")";
				}
				$extra .= ")";
			}
			if ($tipo) {
				if ($tipo !== "Fijo") {
					$extra .= " and articulo.tipo='$tipo'";
				} else {
					$extra .= " and (articulo.tipo='$tipo' or articulo.tipo='Cantidad')";
				}
			}
			if ($usuario) {
				$extra .= " and usuario.id='$usuario'";
			}
			if ($categoria) {
				$this->load->model ( "categoria_model", "categoria_model" );
				$ids = $this->categoria_model->darArbolHijos ( $categoria );
				if ($ids && is_array ( $ids ) && count ( $ids ) > 0) {
					$extra .= " and categoria in(" . implode ( ",", $ids ) . ")";
				}
			}
			$query = "SELECT articulo.id,articulo.titulo,$precio,articulo.fecha_registro,articulo.usuario,articulo.foto,articulo.ciudad, ciudad.nombre as ciudad_nombre, articulo.categoria as categoria $adicionalSelect
			FROM (articulo)
			LEFT JOIN usuario ON usuario.id=articulo.usuario and usuario.estado<>'Baneado'
			INNER JOIN ciudad ON ciudad.id=articulo.ciudad $cextra
			WHERE articulo.estado<>'Finalizado'
			$extra
			$orderby";
			return $query;
		}
		return false;
	}
	// mas fecha menos relevancia
	public function listarArticulosXCriterioFecha($criterio = false, $tipo = false, $orden = false, $ubicacion = false, $categoria = false, $idioma = false, $usuario = false, $inicio = 0, $total = 10) {
		$query = $this->prepararConsultaArticuloXCriterioFecha ( $criterio, $tipo, $orden, $ubicacion, $categoria, $idioma, $usuario );
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				for($i = $inicio; $i < $inicio + $total; $i ++) {
					$datos [] = $res->row ( $i );
				}
				$rquery = $query;
				$query = "select categoria,count(categoria)as cantidad from ($rquery) as s group by categoria";
				$res = $this->db->query ( $query );
				$categorias = $this->darResuts ( $res );
				$query = "select c.id,c.nombre,count(s.ciudad)as cantidad from ($rquery) as s inner join ciudad c on c.id=s.ciudad group by c.id";
				$res = $this->db->query ( $query );
				$ciudades = $this->darResuts ( $res );
				return array (
						$totalRes,
						$datos,
						$categorias,
						$ciudades 
				);
			}
		}
		return false;
	}
	public function listarArticulosXCriterio($criterio = false, $tipo = false, $categoria = false, $inicio = 0, $total = 10) {
		if ($criterio) {
			$this->db->like ( array (
					"titulo" => $criterio 
			) );
		}
		if ($tipo) {
			$this->db->like ( array (
					"tipo" => $tipo 
			) );
		}
		return $this->listarArticulosXFecha ( $categoria, $inicio, $total );
	}
	public function listarArticulosXFecha($categoria = false, $inicio = 0, $total = 10) {
		if ($categoria) {
			$this->db->where ( array (
					"categoria" => $categoria 
			) );
		}
		$this->db->where ( array (
				"terminado" => 0 
		) );
		$this->db->select ( "articulo.*,pais.nombre as pais_nombre" );
		$this->db->join ( "usuario", "usuario.id=articulo.usuario", "inner" );
		$this->db->join ( "pais", "pais.codigo3=usuario.pais", "inner" );
		$this->db->order_by ( "fecha_registro", "desc" );
		return $this->darTodos ( "articulo", $total, $inicio );
	}
	public function procesarArticulos(&$lista) {
		if ($lista && is_array ( $lista ) && count ( $lista ) > 0) {
			$usuarios = array ();
			$this->load->model ( "Usuario_model", "usuarioM" );
			foreach ( $lista as $articulo ) {
				$u = $articulo->usuario;
				if (is_object ( $u )) {
					$u = $u->id;
				}
				if (! isset ( $usuarios [$u] )) {
					$usuarios [$u] = $this->usuarioM->darUsuarioXId ( $u );
				}
				if (isset ( $articulo->comprador ) && $articulo->comprador) {
					$u = $articulo->comprador;
					if (is_object ( $u )) {
						$u = $u->id;
					}
					if (! isset ( $usuarios [$u] )) {
						$usuarios [$u] = $this->usuarioM->darUsuarioXId ( $u );
					}
					$articulo->comprador = $usuarios [$u];
				}
			}
		}
	}
	public function contarArticulosXFecha($categoria = false) {
		if ($categoria) {
			$this->db->where ( array (
					"categoria" => $categoria 
			) );
		}
		$this->db->where ( array (
				"terminado" => 0 
		) );
		$this->db->select ( "articulo.*,pais.nombre as pais_nombre" );
		$this->db->join ( "usuario", "usuario.id=articulo.usuario", "inner" );
		$this->db->join ( "pais", "pais.codigo3=usuario.pais", "inner" );
		$this->db->order_by ( "fecha_registro", "desc" );
		$this->db->from ( "articulo" );
		return $this->db->count_all_results ();
	}
	public function adicionarCantidad($categoria) {
		$this->db->where ( array (
				"id" => $categoria 
		) );
		$this->db->select ( "cantidad,padre" );
		$c = $this->darUno ( "categoria" );
		if ($c) {
			if ($this->db->update ( "categoria", array (
					"cantidad" => intval ( $c->cantidad ) + 1 
			), array (
					"id" => $categoria 
			) )) {
				if ($c->padre) {
					return $this->adicionarCantidad ( $c->padre );
				}
				return true;
			}
		}
		return false;
	}
	public function quitarCantidad($categoria) {
		$this->db->where ( array (
				"id" => $categoria 
		) );
		$this->db->select ( "cantidad,padre" );
		$c = $this->darUno ( "categoria" );
		if ($c) {
			if ($this->db->update ( "categoria", array (
					"cantidad" => intval ( $c->cantidad ) - 1 
			), array (
					"id" => $categoria 
			) )) {
				if ($c->padre) {
					return $this->quitarCantidad ( $c->padre );
				}
				return true;
			}
		}
		return false;
	}
	public function listarNotas($articulo) {
		$this->db->where ( array (
				"articulo" => $articulo 
		) );
		return $this->darTodos ( "aclaracion" );
	}
	public function adicionarNota($articulo, $nota) {
		return $this->db->insert ( "aclaracion", array (
				"articulo" => $articulo,
				"texto" => strip_tags ( $nota ),
				"fecha" => date ( "Y-m-d H:i:s" ) 
		) );
	}
	public function adicionarVisita($articulo, $usuario) {
		$this->db->where ( array (
				"id" => $articulo 
		) );
		if ($usuario) {
			$this->db->where ( array (
					"usuario !=" => $usuario->id 
			) );
		}
		$this->db->select ( "visita" );
		$v = $this->darUno ( "articulo" );
		if ($v) {
			return $this->db->update ( "articulo", array (
					"visita" => intval ( $v->visita ) + 1 
			), array (
					"id" => $articulo 
			) );
		}
		return false;
	}
	private function darUno($tabla) {
		$res = $this->db->get ( $tabla );
		if ($res) {
			$res = $res->result ();
			if ($res && is_array ( $res ) && count ( $res ) > 0) {
				return $res [0];
			}
		}
		return false;
	}
	private function darResuts($res) {
		if ($res) {
			$res = $res->result ();
			if ($res && is_array ( $res ) && count ( $res ) > 0) {
				return $res;
			}
		}
		return false;
	}
	private function darTodos($tabla, $inicio = false, $total = false) {
		$res = $this->db->get ( $tabla, $inicio, $total );
		return $this->darResuts ( $res );
	}
	public function numArticulosXCategoriaXUser($user) {
		return $this->numArticulosXCategoria ( $user );
	}
	public function numArticulosXCategoria($user = false) {
		$res = $this->db->query ( "SELECT count(a.id) as cantidad,a.categoria as categoria FROM articulo as a where " . ($user ? "a.usuario=$user and" : "") . " a.terminado=0 group by a.categoria" );
		return $this->darResuts ( $res );
	}
	public function leerMensajes($usuario, $pagina = 1) {
		$sql = "select m.*,ue.seudonimo emisor_seudonimo,ue.imagen,ur.seudonimo receptor_seudonimo,ur.imagen 
				from mensaje m 
				inner join usuario ur on ur.id=m.receptor 
				inner join usuario ue on ue.id=m.emisor 
				where  m.id in (
					select id from mensaje 
					where receptor='$usuario' 
					group by  emisor 
					order by fecha desc) 
				or m.id in (
					select id from mensaje 
					where emisor='$usuario'
					group by receptor 
					order by fecha desc)";
		return array ();
	}
	public function articulosSeguidos($pagina = 1, $criterio = false, $section = false, $orden = false, $ubicacion = false, $categoria = false, $usuario = false, $limite = false) {
		$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
		$pagina = intval ( $pagina );
		$pagina = $pagina > 0 ? $pagina : 1;
		$inicio = ($pagina - 1) * $totalpagina;
		
		// $data ["categorias"] = $this->darCategorias ( $categoria );
		$data ["inicio"] = $inicio;
		$data ["totalpagina"] = $totalpagina;
		$data ["criterio"] = $criterio;
		switch ($section) {
			case "item" :
				$tipo = "Fijo";
				break;
			case "auction" :
				$tipo = "Subasta";
				break;
			default :
				$tipo = false;
				break;
		}
		
		$x = $this->listarArticulosXCriterioFecha2 ( $criterio, $tipo, $orden, $ubicacion, $categoria, false, $usuario, $inicio, $totalpagina, $limite );
		// print ($this->db->last_query ()) ;
		if ($x) {
			list ( $data ["total"], $data ["articulos"], $categorias ) = $x;
			
			if (trim ( $criterio ) !== "" || $usuario) {
				$data ["categorias"] = $this->darCategorias2 ( $categoria, $categorias, true );
			} else {
				$data ["categorias"] = $this->darCategorias ( $categoria );
			}
			$data ["categorias"] = $this->ordenarArbol ( $data ["categorias"] );
			$this->procesarArticulos ( $data ["articulos"] );
		}
		return $data;
	}
	public function listarArticulosXCriterioFecha2($criterio = false, $tipo = false, $orden = false, $ubicacion = false, $categoria = false, $idioma = false, $usuario = false, $inicio = 0, $total = 10, $limite = false) {
		$query = $this->prepararConsultaArticuloXCriterioFecha2 ( $criterio, $tipo, $orden, $ubicacion, $categoria, $idioma, $usuario, $limite );
		
		$res = $this->db->query ( $query );
		if ($res) {
			$totalRes = $res->num_rows ();
			if ($inicio < $totalRes) {
				$total = ($totalRes < $inicio + $total) ? $totalRes - $inicio : $total;
				$datos = array ();
				for($i = $inicio; $i < $inicio + $total; $i ++) {
					$datos [] = $res->row ( $i );
				}
				$query = "select categoria,count(categoria)as cantidad from ($query) as s group by categoria";
				$res = $this->db->query ( $query );
				$categorias = $this->darResuts ( $res );
				return array (
						$totalRes,
						$datos,
						$categorias 
				);
			}
		}
		return false;
	}
	public function prepararConsultaArticuloXCriterioFecha2($criterio = false, $tipo = false, $orden = false, $ubicacion = false, $categoria = false, $idioma = false, $usuario = false, $limite = false) {
		if (trim ( $orden ) === "" && $usuario) {
			$orden = "finaliza";
		}
		$criterio = explode ( " ", trim ( preg_replace ( "/(\s+)/im", " ", $criterio ) ) );
		if ($criterio && is_array ( $criterio ) && count ( $criterio ) > 0) {
			$precio = "articulo.precio";
			$orderby = "ORDER BY fecha_registro desc";
			$adicionalSelect = "";
			$extra = "";
			if ($ubicacion) {
				$ubicacion = explode ( "-", $ubicacion );
				if (count ( $ubicacion ) > 1) {
					switch ($ubicacion [0]) {
						case "P" :
							$extra .= " and pais.codigo3='" . $ubicacion [1] . "' ";
							break;
						
						default :
							$extra .= " and pais.continente='" . $ubicacion [1] . "' ";
							break;
					}
				}
			}
			switch ($orden) {
				
				case "mas-alto" :
					$precio = "if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "ORDER BY precio desc";
					break;
				case "mas-bajo" :
					$precio = "if(articulo.tipo='Fijo',articulo.precio,mayorPuja(articulo.id)) as precio";
					$orderby = "ORDER BY precio asc";
					break;
			}
			$ors = array_merge ( array (), $criterio );
			if (count ( $ors ) > 0) {
				$extra .= "and (";
				if (count ( $ors ) > 0) {
					$extra .= "(";
					$tors = array ();
					foreach ( $ors as $o ) {
						$tors [] = "titulo like '%" . str_replace ( "'", "\\'", $o ) . "%'";
					}
					$extra .= implode ( " and ", $tors );
					$extra .= ")";
				}
				$extra .= ")";
			}
			if ($tipo) {
				if ($tipo !== "Fijo") {
					$extra .= " and articulo.tipo='$tipo'";
				} else {
					$extra .= " and (articulo.tipo='$tipo' or articulo.tipo='Cantidad')";
				}
			}
			if ($usuario) {
				$extra .= " and siguiendo.usuario='$usuario'";
			}
			if ($categoria) {
				$this->load->model ( "categoria_model", "categoria_model" );
				$ids = $this->categoria_model->darArbolHijos ( $categoria );
				if ($ids && is_array ( $ids ) && count ( $ids ) > 0) {
					$extra .= " and categoria in(" . implode ( ",", $ids ) . ")";
				}
			}
			
			$listado = "";
			if ($limite != false) {
				$totalpagina = $this->configuracion->variables ( "cantidadPaginacion" );
				$listado = "limit $limite,$totalpagina";
			}
			
			$query = "SELECT articulo.cantidad,siguiendo.id as idseguimiento, siguiendo.usuario as usuarioseguimiento,articulo.id,articulo.titulo,articulo.tipo,$precio,articulo.fecha_registro,articulo.duracion,articulo.usuario,articulo.foto, articulo.categoria as categoria $adicionalSelect
			FROM (articulo,siguiendo)
			WHERE articulo.id=siguiendo.articulo and siguiendo.usuario=$usuario 
			$orderby $listado";
			return $query;
		}
		return false;
	}
}
?>