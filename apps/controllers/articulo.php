<?php
require_once ("basecontroller.php");
class Articulo extends BaseController {
	public $nombrePujas = false;
	public function __construct() {
		parent::__construct ();
		$this->load->model ( "Categoria_model", "categoria" );
		$this->load->model ( "Articulo_model", "articulo" );
		$this->load->library ( 'image_lib' );
	}
	public function mostrarDescripcion($articulo) {
		$a = $this->articulo->darArticulo ( $articulo );
		if ($a) {
			$this->view ( "articulo/descripcion-articulo", array (
					"descripcion" => linkTargetBlank ( $a->descripcion ) 
			) );
		}
	}
	public function enviarPago() {
		$this->load->model ( "Paypal_model", "paypal" );
		$paquete = $this->input->post ( "paquete" );
		$tipo = $this->input->post ( "formaPago" );
		$exito = false;
		if ($tipo != 4) {
			$this->paypal->actualizarPaquete ( $paquete, $tipo );
			$exito = true;
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function confirmarEnvio() {
		$paquete = $this->input->post ( "paquete" );
		$exito = false;
		if ($paquete) {
			$exito = $this->articulo->confirmarEnvio ( $paquete );
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function confirmarRecepcion() {
		$paquete = $this->input->post ( "paquete" );
		$tipo = $this->input->post ( "tipo" );
		$exito = false;
		if ($tipo && $paquete) {
			$exito = $this->articulo->confirmarRecepcion ( $tipo, $paquete );
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function disputaRetrasoEnvio() {
		$p = $p = $this->articulo->darPaquete ( $this->input->post ( "paquete" ) );
		$exito = false;
		if ($p) {
			$this->db->insert ( "reporte", array (
					"asunto" => "Pago o Cobro Fraudulento",
					"paquete" => $p->id,
					"fecha" => date ( "Y-m-d H-i-s" ),
					"usuario" => $p->vendedor,
					"perfil" => $p->comprador 
			) );
			
			$exito = $this->db->update ( "paquete", array (
					"estado" => "Disputa" 
			), array (
					"id" => $p->id 
			) );
			foreach ( explode ( ",", $p->articulos ) as $a ) {
				if ($a) {
					$this->db->update ( "articulo", array (
							"estado" => "Disputa" 
					), array (
							"id" => $a 
					) );
				}
			}
			foreach ( explode ( ",", $p->transacciones ) as $t ) {
				if ($t) {
					$this->db->update ( "transaccion", array (
							"estado" => "Disputa" 
					), array (
							"id" => $t 
					) );
				}
			}
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function denunciarPago() {
		$p = $p = $this->articulo->darPaquete ( $this->input->post ( "paquete" ) );
		$exito = false;
		if ($p) {
			$exito = $this->db->update ( "paquete", array (
					"denuncia2" => 1,
					"fecha_denuncia2" => date ( "Y-m-d H:i:d" ) 
			), array (
					"id" => $p->id 
			) );
			if ($exito) {
				$p->vendedor = $this->usuario->darUsuarioXId ( $p->vendedor );
				$p->comprador = $this->usuario->darUsuarioXId ( $p->comprador );
				$articulos = array ();
				if (trim ( $p->articulos ) !== "") {
					$as = explode ( ",", $p->articulos );
					foreach ( $as as $a ) {
						$a = $this->articulo->darArticulo ( $a );
						if ($a) {
							$articulos [] = array (
									"id" => $a->id,
									"titulo" => $a->titulo 
							);
						}
					}
				}
				if (trim ( $p->transacciones ) !== "") {
					$ts = explode ( ",", $p->transacciones );
					foreach ( $ts as $t ) {
						$t = $this->articulo->darTransaccion ( $t );
						if ($t) {
							$a = $this->articulo->darArticulo ( $t->id );
							if ($a) {
								$articulos [] = array (
										"id" => $a->id,
										"titulo" => $a->titulo 
								);
							}
						}
					}
				}
				$this->load->library ( "myemail" );
				$xx = array (
						"comprador" => $p->comprador,
						"articulos" => $articulos 
				);
				$this->enviarMensajeDisputa ( $p->vendedor->id, "mail/retraso-pago-articulo-vendedor", $xx );
				$this->myemail->enviarTemplate ( $p->vendedor->email, "Retraso en el pago de un artículo", "mail/retraso-pago-articulo-vendedor", $xx );
				$yy = array (
						"vendedor" => $p->vendedor,
						"comprador" => $p->comprador,
						"articulos" => $articulos 
				);
				$this->enviarMensajeDisputa ( $p->comprador->id, "mail/retraso-pago-articulo-comprador", $yy );
				$this->myemail->enviarTemplate ( $p->comprador->email, "Retraso en el pago de un artículo", "mail/retraso-pago-articulo-comprador", $yy );
			}
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	function enviarMensajeDisputa($id, $template, $params) {
		$mensaje = $this->load->view ( $template, $params, true );
		$re = $this->db->select ( "id" )->where ( array (
				"tipo" => "Administrador" 
		) )->get ( "usuario", 1, 0 )->result ();
		if ($re && is_array ( $re ) && count ( $re ) > 0) {
			$this->db->insert ( "mensaje", array (
					"emisor" => $re [0]->id,
					"receptor" => $id,
					"mensaje" => $mensaje,
					"fecha" => date ( "Y-m-d H:i:s" ),
					"tipo" => "Admin" 
			) );
		}
	}
	public function denunciarRecibido() {
		$p = $p = $this->articulo->darPaquete ( $this->input->post ( "paquete" ) );
		$exito = false;
		if ($p) {
			$exito = $this->db->update ( "paquete", array (
					"denuncia4" => 1,
					"fecha_denuncia4" => date ( "Y-m-d H:i:d" ) 
			), array (
					"id" => $p->id 
			) );
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function denunciarEnvio() {
		$p = $p = $this->articulo->darPaquete ( $this->input->post ( "paquete" ) );
		$exito = false;
		if ($p) {
			$exito = $this->db->update ( "paquete", array (
					"denuncia3" => 1,
					"fecha_denuncia3" => date ( "Y-m-d H:i:d" ) 
			), array (
					"id" => $p->id 
			) );
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function denunciarGastosEnvio() {
		$as = trim ( $this->input->post ( "articulos" ) );
		$ts = trim ( $this->input->post ( "transacciones" ) );
		$exito = false;
		if ($as || $ts) {
			$as = explode ( ",", $as );
			$ts = explode ( ",", $ts );
			$articulos = array ();
			$transacciones = array ();
			$vendedor = false;
			$total = 0;
			foreach ( $as as $a ) {
				$a = $this->articulo->darArticulo ( $a );
				if ($a) {
					if (! $vendedor) {
						$vendedor = $a->usuario;
					}
					if ($a && ! $this->articulo->articuloPaquete ( $a->id ) && $a->usuario == $vendedor && $a->comprador == $this->myuser->id) {
						$precio = ($a->precio_oferta ? $a->precio_oferta : $a->precio);
						$total += $precio;
						$articulos [] = $a->id;
					}
				}
			}
			foreach ( $ts as $t ) {
				$r = $this->db->select ( "transaccion.*,articulo.usuario" )->join ( "articulo", "articulo.id=transaccion.articulo" )->where ( array (
						"transaccion.id" => $t 
				) )->get ( "transaccion" )->result ();
				if ($r && is_array ( $r ) && count ( $r ) > 0) {
					$t = $r [0];
					if (! $vendedor) {
						$vendedor = $t->usuario;
					}
					if (! $this->articulo->transaccionPaquete ( $t->id ) && $t->usuario == $vendedor && $t->comprador == $this->myuser->id) {
						$precio = $t->precio * $t->cantidad;
						$total += $precio;
						$transacciones [] = $t->id;
					}
				}
			}
			if ((count ( $articulos ) > 0 || count ( $transacciones ) > 0) && $vendedor && $total > 0) {
				$as = implode ( ",", $articulos );
				$ts = implode ( ",", $transacciones );
				$fecha = date ( "Y-m-d H:i:s" );
				$this->db->trans_begin ();
				try {
					if ($this->db->insert ( "paquete", array (
							"articulos" => $as,
							"transacciones" => $ts,
							"comprador" => $this->myuser->id,
							"vendedor" => $vendedor,
							"fecha" => $fecha,
							"gastos_envio" => 0,
							"monto" => $total,
							"denuncia1" => 1,
							"fecha_denuncia1" => $fecha 
					) )) {
						$pid = $this->db->insert_id ();
						foreach ( $articulos as $a ) {
							$this->db->update ( "articulo", array (
									"paquete" => $pid 
							), array (
									"id" => $a 
							) );
						}
						foreach ( $transacciones as $t ) {
							$this->db->update ( "transaccion", array (
									"paquete" => $pid 
							), array (
									"id" => $t 
							) );
						}
					}
				} catch ( Exception $e ) {
					$this->db->trans_rollback ();
				}
				if ($this->db->trans_status () === FALSE) {
					$this->db->trans_rollback ();
				} else {
					$this->db->trans_commit ();
					$exito = true;
				}
			}
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function enviarGastosEnvio() {
		$as = trim ( $this->input->post ( "articulos" ) );
		$ts = trim ( $this->input->post ( "transacciones" ) );
		$ge = floatval ( $this->input->post ( "gastos_envio" ) );
		$p = floatval ( $this->input->post ( "paquete" ) );
		$exito = false;
		if (($as || $ts) && $ge >= 0) {
			$as = explode ( ",", $as );
			$articulos = array ();
			$ts = explode ( ",", $ts );
			$transacciones = array ();
			$comprador = false;
			$total = 0;
			if ($p) {
				$p = $this->articulo->darPaquete ( $p );
				if ($p) {
					if ($p->gastos_envio != $ge) {
						if ($this->db->update ( "paquete", array (
								"gastos_envio" => $ge,
								"fecha" => date ( "Y-m-d H:i:s" ),
								"denuncia1" => 0 
						), array (
								"id" => $p->id 
						) )) {
							$exito = true;
						}
					}
					if ($p->denuncia1) {
						$pid = $p->id;
						$articulos = explode ( ",", $p->articulos );
						$transacciones = explode ( ",", $p->transacciones );
						$exito = true;
						foreach ( $articulos as $a ) {
							$this->db->update ( "articulo", array (
									"paquete" => $pid,
									"estado" => "Sin Pago" 
							), array (
									"id" => $a 
							) );
						}
						foreach ( $transacciones as $t ) {
							$this->db->update ( "transaccion", array (
									"paquete" => $pid,
									"estado" => "Sin Pago" 
							), array (
									"id" => $t 
							) );
						}
					}
				}
			} else {
				foreach ( $ts as $t ) {
					$r = $this->db->select ( "transaccion.*,articulo.usuario" )->where ( array (
							"transaccion.id" => $t 
					) )->join ( "articulo", "articulo.id=transaccion.articulo", "inner" )->get ( "transaccion" )->result ();
					if ($r && is_array ( $r ) && count ( $r ) > 0) {
						$t = $r [0];
						if (! $comprador) {
							$comprador = $t->comprador;
						}
						if (! $this->articulo->articuloPaquete ( $t->articulo ) && $t->comprador == $comprador && $t->usuario == $this->myuser->id) {
							$precio = $t->precio;
							$total += $precio * $t->cantidad;
							$transacciones [] = $t->id;
						}
					}
				}
				foreach ( $as as $a ) {
					$a = $this->articulo->darArticulo ( $a );
					if (! $comprador) {
						$comprador = $a->comprador;
					}
					
					if ($a && ! $this->articulo->articuloPaquete ( $a->id ) && $a->comprador == $comprador && $a->usuario == $this->myuser->id) {
						$precio = ($a->precio_oferta ? $a->precio_oferta : $a->precio);
						$total += $precio;
						$articulos [] = $a->id;
					}
				}
				if ((count ( $articulos ) > 0 || count ( $transacciones ) > 0) && $comprador && $total > 0) {
					$as = implode ( ",", $articulos );
					$ts = implode ( ",", $transacciones );
					// $comprador = $this->myuser->id;
					$fecha = date ( "Y-m-d H:i:s" );
					$this->db->trans_begin ();
					try {
						if ($this->db->insert ( "paquete", array (
								"articulos" => $as,
								"transacciones" => $ts,
								"comprador" => $comprador,
								"vendedor" => $this->myuser->id,
								"fecha" => $fecha,
								"gastos_envio" => $ge,
								"monto" => $total 
						) )) {
							$pid = $this->db->insert_id ();
							foreach ( $articulos as $a ) {
								$this->db->update ( "articulo", array (
										"paquete" => $pid,
										"estado" => "Sin Pago" 
								), array (
										"id" => $a 
								) );
							}
							foreach ( $transacciones as $t ) {
								$this->db->update ( "transaccion", array (
										"paquete" => $pid,
										"estado" => "Sin Pago" 
								), array (
										"id" => $t 
								) );
							}
						}
					} catch ( Exception $e ) {
						$this->db->trans_rollback ();
					}
					if ($this->db->trans_status () === FALSE) {
						$this->db->trans_rollback ();
					} else {
						$this->db->trans_commit ();
						$exito = true;
					}
				}
			}
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function darCabecera($articulo) {
		$a = $this->articulo->darArticulo ( $articulo );
		if ($a) {
			$a->usuario = $this->usuario->darUsuarioXId ( $a->usuario );
			$this->view ( "usuario/cabecera-perfil", array (
					"usuario" => $this->myuser,
					"seccion" => "articulo",
					"articulo" => $a 
			) );
		}
	}
	static public function gestorErrores($errno, $errstr, $errfile, $errline) {
		if (! (error_reporting () & $errno)) {
			return;
		}
		$result = "";
		switch ($errno) {
			case E_USER_ERROR :
				$result .= "<b>Mi ERROR</b> [$errno] $errstr<br />\n";
				$result .= "  Error fatal en la línea $errline en el archivo $errfile";
				$result .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
				$result .= "Abortando...<br />\n";
				break;
			case E_USER_WARNING :
				$result .= "<b>Mi WARNING</b> [$errno] $errstr<br />\n";
				break;
			case E_USER_NOTICE :
				$result .= "<b>Mi NOTICE</b> [$errno] $errstr<br />\n";
				break;
			default :
				$result .= "Tipo de error desconocido: [$errno] $errstr<br />\n";
				break;
		}
		print json_encode ( array (
				"error" => $result 
		) );
		return true;
	}
	public function ultimaPuja() {
		set_time_limit ( 3600 );
		$old = set_error_handler ( array (
				$this,
				"gestorErrores" 
		) );
		$articulo = $this->input->post ( "a" );
		if ($articulo && ($articulo = $this->articulo->darArticulo ( $articulo ))) {
			$ultimaPuja = $this->input->post ( "ultima" );
			do {
				sleep ( 5 );
				$c = $this->articulo->mayorOferta ( $articulo->id, true );
			} while ( $c && $ultimaPuja == $c->id );
			if ($c) {
				$this->output->set_output ( json_encode ( array (
						"resultado" => $c 
				) ) );
			} else {
				$this->output->set_output ( json_encode ( array (
						"error" => "No existen ofertas" 
				) ) );
			}
		}
	}
	public function actualizarPuja() {
		set_time_limit ( 3600 );
		
		$old = set_error_handler ( array (
				$this,
				"gestorErrores" 
		) );
		$res = false;
		$articulo = $this->input->post ( "a" );
		$listapujas = array ();
		$change = false;
		if ($articulo && ($articulo = $this->articulo->darArticulo ( $articulo ))) {
			$listapujas = $this->input->post ( "code" );
			if ($listapujas) {
				try {
					$listapujas = json_decode ( base64_decode ( $listapujas ) );
					if (! $listapujas) {
						$listapujas = array ();
					}
				} catch ( Exception $e ) {
					$listapujas = array ();
				}
			} else {
				$listapujas = array ();
			}
			if (is_object ( $listapujas )) {
				$listapujas = get_object_vars ( $listapujas );
			}
			if (is_array ( $listapujas )) {
				$ids = array ();
				foreach ( $listapujas as $i => $oferta ) {
					if (is_object ( $oferta )) {
						$listapujas [$i] = $oferta = get_object_vars ( $oferta );
					}
					$ids [] = $oferta ["id"];
				}
				$this->load->library ( "servicio" );
				$name = "Anonimo";
				if ($this->myuser) {
					$name = $this->myuser->seudonimo;
				}
				$this->servicio->initClient ( $name );
				$ofertas = false;
				do {
					if ($this->servicio->estaConectado ()) {
						
						// if ($this->servicio->estaConectado ()) {
						$ofertas = $this->servicio->leerComando ( "darTodasOfertas", array (
								implode ( ",", $ids ),
								$articulo->id 
						) );
						if ($ofertas && count ( $ofertas ) > 0) {
							$ofertas = array_reverse ( $ofertas );
							foreach ( $ofertas as $oferta ) {
								$ingreso = false;
								$xxx = array (
										"id" => $oferta [0],
										"monto" => $oferta [1],
										"usuario_id" => $oferta [2],
										"seudonimo" => $oferta [3],
										"codigo" => $oferta [4],
										"monto_automatico" => $oferta [5] 
								);
								foreach ( $listapujas as $i => $xoferta ) {
									if ($xxx ["monto_automatico"] > $xoferta ["monto_automatico"]) {
										array_splice ( $listapujas, $i, 0, array (
												$xxx 
										) );
										$ingreso = true;
										break;
									} elseif ($xxx ["monto_automatico"] == $xoferta ["monto_automatico"]) {
										if ($xxx ["id"] > $xoferta ["id"]) {
											array_splice ( $listapujas, $i, 0, array (
													$xxx 
											) );
											$ingreso = true;
											break;
										} else {
											if ($i - 1 <= 0) {
												array_unshift ( $listapujas, $xxx );
											} else {
												array_splice ( $listapujas, $i - 1, 0, array (
														$xxx 
												) );
											}
											$ingreso = true;
											break;
										}
									}
								}
								if (! $ingreso) {
									array_push ( $listapujas, $xxx );
								}
							}
							if (count ( $ofertas ) > 0) {
								$change = true;
							}
						}
						// $this->servicio->desconectClient ();
					} else {
						restore_error_handler ();
						$this->output->set_output ( json_encode ( array (
								"error" => "El servidor no esta en linea." 
						) ) );
						return;
					}
					
					sleep ( 1 );
				} while ( ! $ofertas || ($ofertas && count ( $ofertas ) <= 0) );
			}
		}
		restore_error_handler ();
		if (! $change) {
			$this->output->set_output ( json_encode ( array (
					"error" => "No se actualizo la lista" 
			) ) );
			return;
		}
		$this->output->set_output ( json_encode ( array (
				"resultado" => base64_encode ( json_encode ( $listapujas ) ) 
		) ) );
	}
	private function procesarOferta($oferta = false, $articulo = false, $tipo = false) {
		if ($tipo !== false) {
			$res = false;
			$json = false;
			if (! $oferta || ! $articulo) {
				$oferta = $this->input->post ( "oferta" );
				$articulo = $this->input->post ( "articulo" );
				$json = true;
			}
			if ($oferta && $articulo) {
				switch ($tipo) {
					case "aceptar" :
						$res = $this->articulo->aceptarOferta ( $oferta, $articulo );
						if ($res) {
							$a = $this->articulo->darArticulo ( $articulo );
							if ($a) {
								$this->load->library ( "myemail" );
								$this->myemail->enviarTemplate ( $this->myuser->email, "Vendió un artículo", "mail/vendido-articulo", array (
										"url" => base_url () . "product/$a->id-" . normalizarTexto ( $a->titulo ),
										"titulo" => $a->titulo 
								) );
								$u = $this->usuario->darUsuarioXId ( $a->comprador );
								if ($u) {
									$this->myemail->enviarTemplate ( $u->email, "Compró un artículo", "mail/comprado-articulo", array (
											"url" => base_url () . "product/$a->id-" . normalizarTexto ( $a->titulo ),
											"titulo" => $a->titulo 
									) );
								}
							}
						}
						break;
					case "rechazar" :
						$res = $this->articulo->rechazarOferta ( $oferta, $articulo );
						break;
				}
				if ($json) {
					$this->output->set_output ( json_encode ( array (
							"exito" => $res 
					) ) );
					return;
				} else {
					$a = $this->articulo->darArticulo ( $articulo );
					redirect ( "product/$a->id-" . normalizarTexto ( $a->titulo ) );
					return;
				}
			}
		}
		redirect ( "/" );
	}
	public function aceptarOferta($oferta = false, $articulo = false) {
		$this->procesarOferta ( $oferta, $articulo, "aceptar" );
	}
	public function rechazarOferta($oferta = false, $articulo = false) {
		$this->procesarOferta ( $oferta, $articulo, "rechazar" );
	}
	public function modal($modal, $tipo = false, $id = false, $extra = false) {
		$datos = array (
				"usuario" => $this->myuser 
		);
		switch ($tipo) {
			case "articulo" :
				$this->load->model ( "articulo_model", "articulo" );
				$datos ["articulo"] = $this->articulo->darArticulo ( $id );
				if ($extra) {
					$datos ["extra"] = intval ( $extra );
				}
				break;
			case "ofertas" :
				$this->load->model ( "articulo_model", "articulo" );
				$datos ["articulo"] = $this->articulo->darArticulo ( $id );
				if ($modal == "pujas") {
					$datos ["ofertas"] = $this->articulo->darOfertas ( $id, false, true );
				} else {
					$datos ["ofertas"] = $this->articulo->darOfertas ( $id );
				}
				if ($this->myuser && $datos ["articulo"]->usuario == $this->myuser->id) {
					$this->articulo->activarOfertasVistos ( $id );
				}
				break;
			case "ultimaOferta" :
				$this->load->model ( "articulo_model", "articulo" );
				$datos ["articulo"] = $this->articulo->darArticulo ( $id );
				$datos ["oferta"] = $this->articulo->ultimaOferta ( $id, $this->myuser->id );
				break;
			case "mensaje" :
				$this->load->model ( "articulo_model", "articulo" );
				$datos ["articulo"] = $this->articulo->darArticulo ( $id );
				$datos ["receptor"] = $this->usuario->darUsuarioXId ( $datos ["articulo"]->usuario );
				break;
			case "ultimaPuja" :
				$this->load->model ( "articulo_model", "articulo" );
				$datos ["articulo"] = $this->articulo->darArticulo ( $id );
				$datos ["oferta"] = $this->articulo->mayorOferta ( $id );
				break;
		}
		parent::modal ( $modal, $datos );
	}
	public function redimensionarThumbs() {
		$lista = $this->articulo->listarArticulos ();
		foreach ( $lista as $value ) {
			$usuario = $this->usuario->darUsuarioXId ( $value->usuario );
			if ($usuario) {
				$images = explode ( ",", $value->foto );
				$dir = BASEPATH . "../files/$usuario->seudonimo/";
				foreach ( $images as $ima ) {
					$imagen = imagenArticulo ( $usuario, $ima, "thumb" );
					$imageno = imagenArticulo ( $usuario, $ima, "original" );
					if ($imagen && $imageno) {
						$ext = strtolower ( pathinfo ( $ima, PATHINFO_EXTENSION ) );
						$name = strtolower ( pathinfo ( $ima, PATHINFO_FILENAME ) );
						$config ['new_image'] = "$dir$imagen";
						$config ['width'] = 140;
						$config ['height'] = 140;
						$config ['image_library'] = 'gd2';
						$config ['source_image'] = $dir . $imageno;
						$config ['create_thumb'] = false;
						$config ['maintain_ratio'] = TRUE;
						$config ['master_dim'] = "auto";
						$this->image_lib->initialize ( $config );
						$this->image_lib->resize ();
					}
				}
			}
		}
	}
	public function tiempoOfertas() {
		$lista = $this->articulo->darOfertas ();
		if ($lista) {
			foreach ( $lista as $oferta ) {
				$tiempo = preg_replace ( "/(\d{4}-\d{2}-\d{2} \d{2}:)(\d{2}):(\d{2})/", "$1$3:$2", $oferta->fecha );
				$this->db->update ( "oferta", array (
						"fecha" => $tiempo 
				), array (
						"id" => $oferta->oferta_id 
				) );
			}
		}
	}
	public function ofertar() {
		$json = array ();
		if ($this->myuser) {
			$id = $this->input->post ( "articulo" );
			$monto = $this->input->post ( "monto" );
			list ( $c, $o ) = $this->articulo->ofertar ( $id, $this->myuser->id, $monto );
			switch ($c) {
				case 1 :
					$json ["exito"] = $o;
					break;
				default :
					$json ["error"] = "Llego al maximo de ofertas permitido.";
					break;
			}
		}
		$this->output->set_output ( json_encode ( $json ) );
	}
	public function begin($id) {
		if ($this->myuser) {
			$articulo = $this->articulo->comenzar ( $id );
			if ($articulo) {
				redirect ( "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ) );
			} else {
				redirect ( "store/{$this->myuser->seudonimo}/sell/3/detail" );
			}
		} else {
			redirect ( "login" );
		}
	}
	public function follow($id) {
		if ($this->myuser) {
			if ($this->articulo->seguir ( $id, $this->myuser->id )) {
				$articulo = $this->articulo->darArticulo ( $id );
				redirect ( "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ) );
			} else {
				redirect ( "store/{$this->myuser->seudonimo}" );
			}
		} else {
			redirect ( "store/{$this->myuser->seudonimo}" );
		}
	}
	public function end($id) {
		$this->articulo->finalizar ( $id );
		redirect ( "store/{$this->myuser->seudonimo}/sell/3/detail" );
	}
	public function adicionarNota() {
		if ($this->myuser) {
			$articulo = $this->input->post ( "articulo" );
			$nota = $this->input->post ( "nota" );
			if ($articulo && $nota) {
				$this->articulo->adicionarNota ( $articulo, $nota );
			}
		}
	}
	public function edit($id = false, $new = false) {
		$data = array ();
		if ($this->isLogged ()) {
			if ($this->myuser->estado !== "Baneado") {
				$view = "articulo/new";
				$data ["articulo"] = $this->articulo->darArticulo ( $id );
				if ($new) {
					$data ["nuevo"] = true;
				}
				if ($data ["articulo"]) {
					$data ["arbol"] = $this->categoria->darArbolCategoria ( $data ["articulo"]->categoria, $this->idioma->language->id );
					if (count ( $_POST ) == 0) {
						$this->bindArticulo ( $data ["articulo"], $new );
					}
				} else {
					redirect ( "/" );
					return;
				}
			} else {
				redirect ( "/" );
				return;
			}
		} else {
			$view = "usuario/login";
		}
		$this->loadGUI ( $view, $data );
	}
	public function bindArticulo($a, $new = false) {
		$props = (get_object_vars ( $a ));
		if ($props && is_array ( $props )) {
			if (! $new) {
				$props ["imagenes"] = $props ["foto"];
			}
			$props ["tipo-precio"] = "precio-fijo-box";
			$props ["precio-oferta"] = $props ["precio"];
			foreach ( $props as $k => $v ) {
				$_POST [$k] = $v;
			}
			if (is_object ( $a->vehiculo )) {
				$props = (get_object_vars ( $a->vehiculo ));
				foreach ( $props as $k => $v ) {
					$_POST [$k] = $v;
				}
			}
			if (is_object ( $a->mascota )) {
				$props = (get_object_vars ( $a->mascota ));
				foreach ( $props as $k => $v ) {
					$_POST [$k] = $v;
				}
			}
			if (is_object ( $a->vivienda )) {
				$props = (get_object_vars ( $a->vivienda ));
				foreach ( $props as $k => $v ) {
					$_POST [$k] = $v;
				}
			}
		}
	}
	public function item($id, $transaccion = false) {
		$data = array ();
		$header = array ();
		$view = "articulo/articulo";
		$id = array_shift ( explode ( "-", $id ) );
		$this->articulo->adicionarVisita ( $id, $this->myuser );
		$data ["articulo"] = $this->articulo->darArticulo ( $id );
		$data ["transaccion"] = false;
		if (! $data ["articulo"]) {
			$this->loadGUI ( "articulo/no-existe", array (
					"id" => $id 
			) );
			return;
		} else {
			$data ["padre"] = $this->categoria->darArbolCategoria ( $data ["articulo"]->categoria, $this->idioma->language->id );
			$header ["headerTitle"] = $this->configuracion->variables ( "defaultHeaderTitle" ) . " - " . $data ["articulo"]->titulo;
			$header ["extraMeta"] = "<meta property='og:title' content='" . $data ["articulo"]->titulo . "'/>";
			$foto = array_shift ( explode ( ",", $data ["articulo"]->foto ) );
			$header ["extraMeta"] .= "<meta property='og:image' content='" . base_url () . "files/articulos/$foto'/>";
			if ($data ["articulo"]->descripcion) {
				$header ["extraMeta"] .= "<meta property='og:description' content='" . substr ( str_replace ( "\n", " ", strip_tags ( $data ["articulo"]->descripcion ) ), 0, 150 ) . "'/>";
			} else {
				$articulo = $data ["articulo"];
				switch ($data ["padre"]) {
					case "1" :
						$descripcion = "MARCA:{$articulo->vehiculo->marca}; MODELO:{$articulo->vehiculo->modelo}; TIPO:{$articulo->vehiculo->tipo}; KILOMETRAJE:{$articulo->vehiculo->kilometraje}; CILINDRADA:{$articulo->vehiculo->cilindrada}; COMBUSTIBLE:{$articulo->vehiculo->combustible}; CAJA:{$articulo->vehiculo->caja}; CONTACTAR CON:{$articulo->contactar_con}";
						break;
					
					case "2" :
						$descripcion = "Raza:{$articulo->mascota->raza}; Pedigri:{$articulo->mascota->pedigri}; Sexo:{$articulo->mascota->sexo}; CONTACTAR CON:{$articulo->contactar_con}";
						break;
					case "3" :
						;
						break;
					default :
						$descripcion = "CONTACTAR CON:{$articulo->contactar_con}";
						break;
				}
				$header ["extraMeta"] .= "<meta property='og:description' content='" . substr ( str_replace ( "\n", " ", strip_tags ( $descripcion ) ), 0, 150 ) . "'/>";
			}
			$data ["articulo"]->usuario = $this->usuario->darUsuarioXId ( $data ["articulo"]->usuario );
			if ($data ["articulo"]->usuario) {
				$images = explode ( ",", $data ["articulo"]->foto );
				$baseruta = "files/" . $data ["articulo"]->usuario->seudonimo . "/";
				$dir = BASEPATH . "../$baseruta";
				foreach ( $images as $ima ) {
					$imagen = imagenArticulo ( $data ["articulo"]->usuario, $ima );
					if ($imagen) {
						$header ["extraMeta"] .= "<meta property='og:image' content='" . base_url () . "$imagen' />";
					}
				}
				$data ["articulo"]->usuario->pais = $data ["articulo"]->usuario->darPais ();
				$data ["articulo"]->usuario->ciudad = $data ["articulo"]->usuario->darCiudad ();
				if ($this->myuser) {
					$data ["siguiendo"] = $this->articulo->siguiendo ( $id, $this->myuser->id );
				}
				if (! $data ["articulo"]->usuario->pais) {
					$data ["articulo"]->usuario->pais = new stdClass ();
					$data ["articulo"]->usuario->pais->nombre = "";
				}
				if (! $data ["articulo"]->usuario->ciudad) {
					$data ["articulo"]->usuario->ciudad = new stdClass ();
					$data ["articulo"]->usuario->ciudad->nombre = "";
				}
			}
		}
		$this->loadGUI ( $view, $data, $header );
	}
	public function getCategory($id) {
		$cats = $this->categoria->darSubCategorias ( $id );
		$retcat = $this->parseCategories ( $cats );
		$this->load->view ( "articulo/listacategorias", array (
				"categorias" => $retcat 
		) );
	}
	public function remove($imagen = false) {
		$user = $this->mysession->userdata ( "LVSESSION" );
		$return = array ();
		if (isset ( $user ) && isset ( $user ["usuario"] )) {
			$baseruta = "files/" . $user ["usuario"] . "/";
			$ruta = BASEPATH . "../" . $baseruta;
			$ext = strtolower ( pathinfo ( $imagen, PATHINFO_EXTENSION ) );
			$name = strtolower ( pathinfo ( $imagen, PATHINFO_FILENAME ) );
			if (is_file ( $ruta . $imagen )) {
				@unlink ( $ruta . $imagen );
			}
			if (is_file ( "$ruta$name.original.$ext" )) {
				@unlink ( "$ruta$name.original.$ext" );
			}
			if (is_file ( "$ruta$name.thumb.$ext" )) {
				@unlink ( "$ruta$name.thumb.$ext" );
			}
			$return = array (
					"quien" => $this->input->post ( "quien" ) 
			);
		} else {
			$return = array (
					'error' => true,
					'mensaje' => "No se pudo eliminar la imagen, intententelo mas tarde.",
					"quien" => $this->input->post ( "quien" ) 
			);
		}
		$this->output->set_content_type ( 'text/plain' )->set_output ( json_encode ( $return ) );
	}
	public function nuevo($id = false) {
		if ($id) {
			$this->edit ( $id, true );
			return;
		}
		$view = "articulo/new";
		
		$this->loadGUI ( $view );
	}
	public function registrarEvento($evento, $id) {
		$file = BASEPATH . "../log_files.txt";
		$texto = file_get_contents ( $file );
		$texto = $texto ? $texto : "";
		$textos = explode ( "\n", $texto );
		if ($textos > 1000) {
			array_splice ( $textos, 1000 );
			$texto = implode ( "\n", $textos );
		}
		$texto = date ( "[Y-m-d H:i:s]" ) . " - $id - $evento\r\n" . $texto;
		file_put_contents ( $file, $texto );
	}
	public function imprimirVariable($var) {
		ob_start ();
		print_r ( $var );
		return ob_get_clean ();
	}
	public function uploadImage($ouput = true) {
		return uploadImage ( $ouput );
	}
	public function process() {
		$this->load->model ( "categoria_model", "categoria" );
		$this->load->model ( "articulo_model", "articulo" );
		$this->load->helper ( "form" );
		$data = array ();
		if ($this->input->post ( "categoria" )) {
			$arbol = $this->categoria->darArbolCategoria ( $this->input->post ( "categoria" ), $this->idioma->language->id );
			$data = array (
					"arbol" => $arbol 
			);
		}
		switch ($this->input->post ( "__accion" )) {
			case "ingresar" :
				$data = array_merge ( $data, $this->post_ingresar () );
				break;
			case "modificar" :
				if ($this->myuser) {
					$data = array_merge ( $data, $this->post_modificar () );
				}
				break;
		}
		$data ["ciudades"] = $this->locacion->listarCiudades ( "BOL" );
		$cats = $this->categoria->darCategoriasXNivel ( 1 );
		$retcat = $this->parseCategories ( $cats );
		if ($this->input->post ( "categoria" )) {
			$data ["padre"] = $this->categoria->darArbolCategoria ( $this->input->post ( "categoria" ), $this->idioma->language->id );
		}
		return array_merge ( parent::process (), $data, array (
				"categorias" => $retcat 
		) );
	}
	public function post_pujar() {
		return $this->enviarPuja ( true );
	}
	public function enviarPuja($post = false) {
		$retcat = array ();
		$errores = array ();
		$id = $this->input->post ( "articulo" );
		$articulo = $this->articulo->darArticulo ( $id );
		if ($articulo) {
			if ($this->myuser) {
				$monto = $this->input->post ( "oferta" );
				if (floatval ( $monto ) > 0) {
					$c = $this->articulo->pujar ( $id, $this->myuser->id, $monto );
					switch ($c) {
						case 1 :
							$errores ["exito"] = true;
							break;
						case 2 :
							$errores ["ofertaError"] = "El articulo ya no se encuentra a la venta.";
							break;
						case 3 :
							$errores ["ofertaError"] = "Tu puja no puede ser menor a la que ya ofertaste antes.";
							break;
						case 0 :
							$errores ["ofertaError"] = "No alcanzo el Monto Minimo.";
							break;
						default :
							$errores ["ofertaError"] = "Ocurrio un error vuelva a intentarlo mas tarde.";
							break;
					}
				} else {
					$errores ["ofertaError"] = "El monto debe ser mayor a 0.";
				}
			}
		}
		if ($post) {
			redirect ( "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ) . "-send" );
			return $errores;
		} else {
			$this->output->set_output ( json_encode ( $errores ) );
		}
	}
	private function parseCategories($cats) {
		$retcat = array ();
		if ($cats && is_array ( $cats )) {
			foreach ( $cats as $categoria ) {
				$nombrecat = $this->categoria->darCategoriaNombre ( $categoria->id, $this->idioma->language->id );
				if ($nombrecat) {
					$retcat [$categoria->id] = array (
							"url" => $nombrecat->url_amigable,
							"nombre" => $nombrecat->nombre,
							"nivel" => $categoria->nivel,
							"cantidad" => $categoria->cantidad 
					);
				}
			}
		}
		return $retcat;
	}
	public function post_comprar() {
		$errores = array ();
		if ($this->myuser) {
			if ($this->myuser->estado !== "Incompleto" && $this->myuser->estado !== "Baneado") {
				$id = $this->input->post ( "articulo" );
				$xid = $this->articulo->comprar ( $id, $this->myuser->id );
				if (! $xid) {
					$errores ["ofertaError"] = "El articulo no se pudo comprar por favor intentelo mas tarde o bien este ya fue vendido.";
				} else {
					$a = $this->articulo->darArticulo ( $id );
					if ($a) {
						$usuario = $this->usuario->darUsuarioXId ( $a->usuario );
						if ($usuario) {
							$this->load->library ( "myemail" );
							$this->myemail->enviarTemplate ( $usuario->email, "Ha vendido un artículo", "mail/vendido-articulo", array (
									"url" => base_url () . "product/$a->id-" . normalizarTexto ( $a->titulo ),
									"titulo" => $a->titulo 
							) );
						}
						if ($a->tipo == "Cantidad") {
							redirect ( "product/$a->id-" . normalizarTexto ( $a->titulo ) . "/$xid", "refresh" );
						} else {
							redirect ( "product/$a->id-" . normalizarTexto ( $a->titulo ) . "-buy", "refresh" );
						}
					}
				}
			} else {
				redirect ( "edit/buy-sell", "refresh" );
			}
		}
		return $errores;
	}
	public function post_ofertar() {
		$errores = array ();
		if ($this->myuser) {
			$id = $this->input->post ( "articulo" );
			$monto = $this->input->post ( "oferta" );
			if (floatval ( $monto ) > 0) {
				list ( $c, $o, $r ) = $this->articulo->ofertar ( $id, $this->myuser->id, $monto );
				switch ($c) {
					case 1 :
						$errores ["exito"] = $o;
						$articulo = $this->articulo->darArticulo ( $id );
						if (! $r) {
							if ($articulo) {
								$usuario = $this->usuario->darUsuarioXId ( $articulo->usuario );
								if ($usuario) {
									$this->load->library ( "myemail" );
									$template = $this->load->view ( "mail/nuevas-oferta", array (
											"url" => base_url () . "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ),
											"titulo" => $articulo->titulo 
									), true );
									$this->myemail->enviar ( $usuario->email, "Tienes Nuevas Ofertas", $template );
								}
							}
						}
						redirect ( "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ) . "-send", "refresh" );
						break;
					case 2 :
						$errores ["ofertaError"] = "El articulo ya no se encuentra a la venta.";
						break;
					case 3 :
						$errores ["ofertaError"] = "El debe colocar un monto mayor a su ultima oferta.";
						break;
					default :
						$errores ["ofertaError"] = "Llego al maximo de ofertas permitido.";
						break;
				}
			} else {
				$errores ["ofertaError"] = "El monto debe ser mayor a 0.";
			}
		}
		return $errores;
	}
	public function post_modificar() {
		return $this->post_ingresar ( true );
	}
	public function post_ingresar($modificar = false) {
		if ($modificar && ! $this->input->post ( "id" )) {
			redirect ( "/" );
			return array ();
		}
		$errores = array ();
		$a = $this->articulo->darArticulo ( $this->input->post ( "id" ) );
		$this->load->library ( 'form_validation' );
		$config = array (
				array (
						'field' => 'titulo',
						'label' => 'Título',
						'rules' => 'required' 
				),
				array (
						'field' => 'categoria',
						'label' => 'Categoría',
						'rules' => 'required' 
				),
				array (
						'field' => 'imagenes',
						'label' => 'Portada',
						'rules' => 'required' 
				) 
		);
		
		$this->form_validation->set_error_delimiters ( '<span class="errorTxt">', '</span>' );
		$this->form_validation->set_rules ( $config );
		if ($this->form_validation->run ()) {
			if ($modificar) {
				$this->articulo->id = $this->input->post ( "id" );
			}
			$session = $this->mysession->userdata ( "LVSESSION" );
			$this->articulo->titulo = $this->input->post ( "titulo" );
			if ($this->myuser) {
				$this->articulo->usuario = $this->myuser->id;
			}
			$this->articulo->descripcion = $this->input->post ( "descripcion" );
			$this->articulo->categoria = $this->input->post ( "categoria" );
			$this->articulo->contactar_con = $this->input->post ( "contactar_con" );
			$this->articulo->ciudad = $this->input->post ( "ciudad" );
			$modo = $this->input->post ( "modo" );
			$imagenes = $this->input->post ( "imagenes" );
			switch ($modo) {
				case 2 :
					$files = isset ( $_FILES ) ? $_FILES : false;
					$imagenes = array ();
					if ($files && is_array ( $files ) && count ( $files ) > 0) {
						foreach ( $files as $f ) {
							if ($f ["error"] == 0) {
								$_FILES ["imagen"] = $f;
								$return = $this->uploadImage ( false );
								
								if ($return && ! isset ( $return ["error"] )) {
									$imagenes [] = $return ["name"] . "." . $return ["ext"];
								}
							}
						}
					}
					$imagenes = implode ( ",", $imagenes );
					break;
			}
			$padre = $this->categoria->darArbolCategoria ( $this->articulo->categoria, $this->idioma->language->id );
			$objeto = new stdClass ();
			$mascota = false;
			$vivienda = false;
			switch ($padre [0] ["id"]) {
				case 1 :
					$objeto->marca = $this->input->post ( "marca" );
					$objeto->modelo = $this->input->post ( "modelo" );
					$objeto->tipo = $this->input->post ( "tipo" );
					$objeto->kilometraje = $this->input->post ( "kilometraje" );
					$objeto->cilindrada = $this->input->post ( "cilindrada" );
					$objeto->combustible = $this->input->post ( "combustible" );
					$objeto->caja = $this->input->post ( "caja" );
					break;
				case 2 :
					$objeto->raza = $this->input->post ( "raza" );
					$objeto->pedigri = $this->input->post ( "pedigri" );
					$objeto->sexo = $this->input->post ( "sexo" );
					$objeto->observacion = $this->input->post ( "observacion" );
					$mascota = true;
					break;
				case 3 :
					$objeto->tipo_venta = $this->input->post ( "tipo_venta" );
					$objeto->direccion = $this->input->post ( "direccion" );
					$objeto->superficie = $this->input->post ( "superficie" );
					$objeto->dormitorios = $this->input->post ( "dormitorios" );
					$objeto->banos = $this->input->post ( "banos" );
					$objeto->antiguedad = $this->input->post ( "antiguedad" );
					$vivienda = true;
					break;
				default :
					;
					break;
			}
			$this->articulo->foto = $imagenes;
			$this->articulo->precio = $this->input->post ( "precio-oferta" );
			
			$this->articulo->moneda = 1;
			if (! $modificar) {
				$this->articulo->fecha_registro = date ( "Y-m-d H:i:s" );
				$this->articulo->estado = "A la venta";
			}
			if (count ( $errores ) == 0 && $this->articulo->registrar ( $modificar, $objeto, $mascota, $vivienda )) {
				$seccion = "nuevo";
				if ($modificar) {
					$seccion = "actualizado";
				}
				redirect ( "product/" . $this->articulo->id . "-" . normalizarTexto ( $this->articulo->titulo ) . "/$seccion" );
			}
		}
		return $errores;
	}
	function modificarCantidad() {
		$a = $this->articulo->darArticulo ( $this->input->post ( "articulo" ) );
		$c = intval ( $this->input->post ( "cantidad" ) );
		$exito = false;
		if ($a && $c > 0) {
			$diferencia = $c - $a->cantidad;
			$exito = $this->db->update ( "articulo", array (
					"cantidad" => $c,
					"cantidad_original" => $a->cantidad_original + $diferencia,
					"terminado" => 0,
					"fecha_terminado" => null 
			), array (
					"id" => $a->id 
			) );
			if ($exito) {
				setcookie ( "cantidad_guardada", $a->id, false, "/" );
			}
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
}

?>