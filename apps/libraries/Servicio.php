<?php
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
class Servicio {
	var $articulo = array ();
	var $CI = false;
	var $socket = false;
	var $address = "localhost";
	var $port = "604438";
	var $ofertas = array ();
	var $clients = array ();
	var $limitRead = 32768;
	var $paquetes = array ();
	function __construct() {
		$this->CI = &get_instance ();
	}
	function reMapPaquetes() {
		$db = &$this->CI->db;
		$db->where ( "(denuncia1=1 or denuncia2=1 or denuncia3=1 or denuncia4=1) and estado<>'Finalizado'" );
		$res = $db->get ( "paquete" );
		if ($res && ($r = $res->result ())) {
			$this->paquetes = $r;
		}
		print "ReMapPaquetes: " . count ( $this->paquetes ) . " paquetes\n";
	}
	function verificarDenuncias() {
		$this->CI->load->library ( "Configuracion" );
		$this->CI->load->library ( "myemail" );
		$d1 = floatval ( $this->CI->configuracion->variables ( "denuncia1b" ) );
		$d2 = floatval ( $this->CI->configuracion->variables ( "denuncia2b" ) );
		$d2c = floatval ( $this->CI->configuracion->variables ( "denuncia2c" ) );
		$d3 = floatval ( $this->CI->configuracion->variables ( "denuncia3b" ) );
		$d4 = floatval ( $this->CI->configuracion->variables ( "denuncia4b" ) );
		$ahora = time ();
		foreach ( $this->paquetes as $i => $p ) {
			$td1 = $ahora - (strtotime ( $p->fecha_denuncia1 ) + $d1 * 86400);
			$articulos = explode ( ",", $p->articulos );
			$transacciones = explode ( ",", $p->transacciones );
			if ($td1 >= 0 && $p->denuncia1) {
				$this->CI->db->update ( "paquete", array (
						"estado" => "Finalizado" 
				), array (
						"id" => $p->id 
				) );
				$this->CI->articulo->adicionarVoto ( $p->vendedor, "Venta", 1, "negativo" );
				foreach ( $articulos as $a ) {
					$this->CI->articulo->finalizar ( $a, "Finalizado" );
				}
				foreach ( $transacciones as $t ) {
					$this->CI->articulo->finalizar ( $t, "Finalizado", true );
				}
				print "Finalizado: $p->id, articulos:$p->articulos, transacciones: $p->transacciones, por Denuncia 1 \n";
				unset ( $this->paquetes [$i] );
			}
			if (isset ( $p ) && $p->denuncia2 && ! $p->fecha_disputa1) {
				$td2 = $ahora - (strtotime ( $p->fecha_denuncia2 ) + $d2 * 86400);
				$articulos = explode ( ",", $p->articulos );
				if ($td2 >= 0 && $p->denuncia2) {
					$p->fecha_disputa1 = date ( "Y-m-d H:i:s" );
					$this->CI->db->update ( "paquete", array (
							"estado" => "Disputa",
							"fecha_disputa1" => $p->fecha_disputa1 
					), array (
							"id" => $p->id 
					) );
					$as = array ();
					foreach ( $articulos as $a ) {
						$this->CI->articulo->finalizar ( $a, "Disputa" );
						$a = $this->CI->articulo->darArticulo ( $a );
						if ($a) {
							$as [] = array (
									"id" => $a->id,
									"titulo" => $a->titulo 
							);
						}
					}
					foreach ( $transacciones as $t ) {
						$this->CI->articulo->finalizar ( $t, "Disputa", true );
						$t = $this->CI->articulo->darTransaccion ( $t );
						if ($t) {
							$a = $this->CI->articulo->darArticulo ( $t->articulo );
							if ($a) {
								$as [] = array (
										"id" => $a->id,
										"titulo" => $a->titulo 
								);
							}
						}
					}
					$this->CI->db->insert ( "reporte", array (
							"asunto" => "No Pagado",
							"paquete" => $p->id,
							"fecha" => date ( "Y-m-d H-i-s" ),
							"perfil" => $p->comprador,
							"usuario" => $p->vendedor 
					) );
					$rid = $this->CI->db->insert_id ();
					if ($rid) {
						$p->vendedor = $this->CI->usuario->darUsuarioXId ( $p->vendedor );
						$p->comprador = $this->CI->usuario->darUsuarioXId ( $p->comprador );
						$xx = array (
								"vendedor" => $p->vendedor,
								"comprador" => $p->comprador,
								"articulos" => $as,
								"disputa" => $rid 
						);
						$yy = array (
								"comprador" => $p->comprador,
								"articulos" => $as,
								"disputa" => $rid 
						);
						$this->enviarMensajeDisputa ( $p->comprador->id, "mail/disputa-impago-comprador", $xx );
						$this->CI->myemail->enviarTemplate ( $p->comprador->email, "Disputa $rid por impago", "mail/disputa-impago-comprador", $xx );
						$this->enviarMensajeDisputa ( $p->vendedor->id, "mail/disputa-impago-vendedor", $yy );
						$this->CI->myemail->enviarTemplate ( $p->vendedor->email, "Disputa $rid por impago", "mail/disputa-impago-vendedor", $yy );
					}
					print "Entro en Disputa Unpaid: $p->id, articulos:$p->articulos, transacciones: $p->transacciones, por Denuncia 2 \n";
					unset ( $this->paquetes [$i] );
				}
			}
			if (isset ( $p ) && $p->fecha_disputa1) {
				$td2c = $ahora - (strtotime ( $p->fecha_disputa1 ) + $d2c * 86400);
				$articulos = explode ( ",", $p->articulos );
				if ($td2c >= 0) {
					$this->CI->db->update ( "paquete", array (
							"estado" => "Finalizado" 
					), array (
							"id" => $p->id 
					) );
					// $this->CI->articulo->adicionarVoto ( $p->vendedor,
					// "Venta", 1, "negativo" );
					$as = array ();
					foreach ( $articulos as $a ) {
						$a = $this->CI->articulo->darArticulo ( $a );
						if ($a) {
							$this->CI->db->delete ( "oferta", array (
									"articulo" => $a->id 
							) );
							$as [] = array (
									"id" => $a->id,
									"titulo" => $a->titulo 
							);
						}
					}
					foreach ( $transacciones as $t ) {
						$t = $this->CI->articulo->darTransaccion ( $t );
						if ($t) {
							$a = $this->CI->articulo->darArticulo ( $t->articulo );
							if ($a) {
								
								$this->CI->db->update ( "articulo", array (
										"cantidad" => $a->cantidad + $t->cantidad 
								), array (
										"id" => $a->id 
								) );
								
								$this->CI->db->delete ( "oferta", array (
										"articulo" => $a->id 
								) );
								$as [] = array (
										"id" => $a->id,
										"titulo" => $a->titulo 
								);
							}
						}
					}
					$this->CI->db->update ( "articulo", array (
							"terminado" => 0,
							"fecha_terminado" => null,
							"paquete" => null,
							"estado" => "A la venta",
							"comprador" => null,
							"fecha_registro" => date ( "Y-m-d H:i:s" ),
							"precio_oferta" => null 
					), array (
							"paquete" => $p->id 
					) );
					
					$this->CI->db->delete ( "transaccion", array (
							"paquete" => $p->id 
					) );
					$rid = $this->CI->db->where ( array (
							"paquete" => $p->id 
					) )->get ( "reporte", 1, 0 )->result ();
					if ($rid && is_array ( $rid ) && count ( $rid ) > 0) {
						$rid = $rid [0];
						$this->CI->db->update ( "reporte", array (
								"estado" => "Finalizado" 
						), array (
								"id" => $rid->id 
						) );
						
						$p->vendedor = $this->CI->usuario->darUsuarioXId ( $p->vendedor );
						$p->comprador = $this->CI->usuario->darUsuarioXId ( $p->comprador );
						$xx = array (
								"vendedor" => $p->vendedor,
								"articulos" => $as,
								"disputa" => $rid->id 
						);
						$yy = array (
								"comprador" => $p->comprador,
								"articulos" => $as,
								"disputa" => $rid->id 
						);
						$this->enviarMensajeDisputa ( $p->comprador->id, "mail/disputa-impago-finalizado-comprador", $xx );
						$this->CI->myemail->enviarTemplate ( $p->comprador->email, "Disputa $rid->id por impago finalizado", "mail/disputa-impago-finalizado-comprador", $xx );
						$this->enviarMensajeDisputa ( $p->comprador->id, "mail/disputa-impago-finalizado-vendedor", $yy );
						$this->CI->myemail->enviarTemplate ( $p->vendedor->email, "Disputa $rid->id por impago finalizada", "mail/disputa-impago-finalizado-vendedor", $yy );
					}
					print "Se termino disputa Unpaid: $p->id, articulos:$p->articulos, transacciones: $p->transacciones, por Disputa Unpaid \n";
					unset ( $this->paquetes [$i] );
				}
			}
			if (isset ( $p )) {
				$td3 = $ahora - (strtotime ( $p->fecha_denuncia3 ) + $d3 * 86400);
				$articulos = explode ( ",", $p->articulos );
				if ($td3 >= 0 && $p->denuncia3) {
					$this->CI->db->update ( "paquete", array (
							"estado" => "Finalizado" 
					), array (
							"id" => $p->id 
					) );
					$tipo = "Disputa";
					$this->CI->db->insert ( "reporte", array (
							"asunto" => "Pago o Cobro Fraudulento",
							"paquete" => $p->id,
							"fecha" => date ( "Y-m-d H-i-s" ) 
					) );
					$this->CI->articulo->adicionarVoto ( $p->comprador, "Venta", 1, "negativo" );
					foreach ( $articulos as $a ) {
						$this->CI->articulo->finalizar ( $a, "Finalizado" );
					}
					foreach ( $transacciones as $t ) {
						$this->CI->articulo->finalizar ( $t, "Finalizado", true );
					}
					print "Finalizado: $p->id, articulos:$p->articulos, transacciones: $p->transacciones, por Denuncia 3 \n";
					unset ( $this->paquetes [$i] );
				}
			}
			if (isset ( $p )) {
				$td4 = $ahora - (strtotime ( $p->fecha_denuncia3 ) + $d4 * 86400);
				$articulos = explode ( ",", $p->articulos );
				if ($td3 >= 0 && $p->denuncia4) {
					$this->CI->db->update ( "paquete", array (
							"estado" => "Finalizado" 
					), array (
							"id" => $p->id 
					) );
					$this->CI->db->insert ( "reporte", array (
							"asunto" => "Articulo no recibido",
							"paquete" => $p->id,
							"fecha" => date ( "Y-m-d H-i-s" ) 
					) );
					$this->CI->articulo->adicionarVoto ( $p->vendedor, "Venta", 1, "negativo" );
					foreach ( $articulos as $a ) {
						$this->CI->articulo->finalizar ( $a, "Finalizado" );
					}
					foreach ( $transacciones as $t ) {
						$this->CI->articulo->finalizar ( $t, "Finalizado", true );
					}
					print "Finalizado: $p->id, articulos:$p->articulos, transacciones: $p->transacciones, por Denuncia 4 \n";
					unset ( $this->paquetes [$i] );
				}
			}
		}
	}
	function enviarMensajeDisputa($id, $template, $params) {
		$mensaje = $this->CI->load->view ( $template, $params, true );
		$re = $this->CI->db->select ( "id" )->where ( array (
				"tipo" => "Administrador" 
		) )->get ( "usuario", 1, 0 )->result ();
		if ($re && is_array ( $re ) && count ( $re ) > 0) {
			$this->CI->db->insert ( "mensaje", array (
					"emisor" => $re [0]->id,
					"receptor" => $id,
					"mensaje" => $mensaje,
					"fecha" => date ( "Y-m-d H:i:s" ),
					"tipo" => "Admin" 
			) );
		}
	}
	function estaConectado() {
		return isset ( $this->socket ) && $this->socket;
	}
	function killUser($params) {
		$i = false;
		if (is_array ( $params )) {
			if (count ( $params ) > 0) {
				$i = array_shift ( $params );
			}
		}
		if ($i !== false && isset ( $this->clients [$i] )) {
			echo "Killing Client ", $this->clients [$i] ['name'], " disconnected!", "\n";
			unset ( $this->clients [$i] );
		} else {
			echo "El cliente no existe", "\n";
		}
		return true;
	}
	function memoryUsed() {
		$mem = memory_get_usage ();
		print $mem . "\n";
		return base64_encode ( serialize ( $mem ) );
	}
	function leerComando($comando, $params = false) {
		if ($this->estaConectado ()) {
			if (is_array ( $params ) && count ( $params ) > 0) {
				$comando .= "|" . implode ( "|", $params );
			}
			$mensaje = "comando=$comando";
			$cmd = "";
			$data = false;
			$count = 0;
			while ( $cmd !== "salir" && $cmd !== "data" && $count < 10000 && ! $data ) {
				$this->enviarMensaje ( $this->socket, $mensaje );
				$out = @socket_read ( $this->socket, $this->limitRead );
				if (! $out) {
					unset ( $this->socket );
					return false;
				}
				$out = trim ( $out );
				if ($out) {
					$aux = explode ( ":=:", $out );
					if (count ( $aux ) > 0) {
						$cmd = array_shift ( $aux );
					}
					if (count ( $aux ) > 0) {
						$data = array_shift ( $aux );
					}
				}
				$count ++;
			}
			if ($data) {
				return unserialize ( base64_decode ( $data ) );
			}
		}
		return false;
	}
	function initServer() {
		$this->socket = socket_create ( AF_INET, SOCK_STREAM, SOL_TCP );
		if ($this->socket === false) {
			throw new ServicioExcepcion ( "socket_create() falló: razón: " . socket_strerror ( socket_last_error () ) );
		}
		if (socket_bind ( $this->socket, $this->address, $this->port ) === false) {
			throw new ServicioExcepcion ( "socket_bind() falló: razón: " . socket_strerror ( socket_last_error ( $this->socket ) ) );
		}
		
		if (socket_listen ( $this->socket, 5 ) === false) {
			throw new ServicioExcepcion ( "socket_listen() falló: razón: " . socket_strerror ( socket_last_error ( $this->socket ) ) );
		}
	}
	function initClient($name = false) {
		$this->socket = socket_create ( AF_INET, SOCK_STREAM, SOL_TCP );
		$result = @socket_connect ( $this->socket, $this->address, $this->port );
		
		if ($result === false) {
			
			if ($this->socket) {
				socket_close ( $this->socket );
				$this->socket = false;
			}
		} else {
			
			if ($name) {
				$this->leerComando ( "clientName", array (
						$name 
				) );
			}
		}
	}
	function desconectClient() {
		if ($this->socket) {
			socket_close ( $this->socket );
		}
	}
	function revisarVigenciaOfertas() {
		$this->CI->db->reconnect ();
		$this->CI->load->model ( "articulo_model", "articulo" );
		$this->CI->load->model ( "usuario_model", "usuario" );
		$this->CI->load->library ( "myemail" );
		$articulos = $this->CI->articulo->listarArticulosPendientes ();
		if ($articulos) {
			$ahora = time ();
			foreach ( $articulos as $articulo ) {
				if ($articulo->tipo == "Subasta") {
					$tiempo = strtotime ( $articulo->fecha_registro ) + $articulo->duracion * 86400;
					if ($tiempo <= $ahora) {
						$this->reMapOfertas ( array (
								$articulo->id 
						) );
						$usuario = $this->CI->usuario->darUsuarioXId ( $articulo->usuario );
						if ($usuario->estado != "Baneado" && $articulo->estado != "Baneado" && isset ( $this->ofertas [$articulo->id] ) && is_array ( $this->ofertas [$articulo->id] ) && count ( $this->ofertas [$articulo->id] ) > 0) {
							if (count ( $this->ofertas [$articulo->id] ) > 0) {
								print "Articulo: $articulo->id - $articulo->titulo, Finalizado con pujas\n";
								$this->CI->articulo->aceptarOferta ( $this->ofertas [$articulo->id] [0] [0], $articulo->id, true );
								if ($usuario) {
									$this->CI->myemail->enviarTemplate ( $usuario->email, "Tu articulo fue vendido", "mail/vendido-articulo", array (
											"url" => base_url () . "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ),
											"titulo" => $articulo->titulo 
									) );
								}
								$usuario = $this->CI->usuario->darUsuarioXId ( $articulo->comprador );
								if ($usuario) {
									$this->CI->myemail->enviarTemplate ( $usuario->email, "Ha comprado un artículo", "mail/comprado-articulo", array (
											"url" => base_url () . "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ),
											"titulo" => $articulo->titulo 
									) );
								}
							}
						} else {
							print "Articulo: $articulo->id - $articulo->titulo, Finalizado sin pujas\n";
							$this->CI->db->delete ( "oferta", array (
									"articulo" => $articulo->id 
							) );
							$this->CI->articulo->finalizar ( $articulo->id );
							if ($usuario) {
								$this->CI->myemail->enviarTemplate ( $usuario->email, "Tu articulo finalizó sin venderse", "mail/articulo-finalizado", array (
										"url" => base_url () . "product/$articulo->id-" . normalizarTexto ( $articulo->titulo ),
										"titulo" => $articulo->titulo,
										"seudonimo" => $usuario->seudonimo 
								) );
							}
						}
						unset ( $this->ofertas [$articulo->id] );
						print date ( "Y-m-d H:i:s" ) . " - Se Finalizo el articulo: $articulo->id - $articulo->titulo - $articulo->fecha_registro - $articulo->duracion\n";
					}
				} else {
					$this->CI->load->library ( "configuracion" );
					$tiempo = strtotime ( $articulo->fecha_registro ) + intval ( $this->CI->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
					if ($tiempo <= $ahora) {
						if ($this->CI->articulo->actualizarPublicacion ( $articulo->id )) {
							print "[" . date ( "Y-m-d H:i:s" ) . "] - Cambiando la fecha de publicacion del articulo: $articulo->id - $articulo->titulo\n";
						}
					}
				}
			}
		}
	}
	function notificarArticulosPorVencer() {
		$this->CI->db->reconnect ();
		$this->CI->load->model ( "articulo_model", "articulo" );
		$this->CI->load->library ( "myemail" );
		$seguimientos = $this->CI->articulo->listarSeguimientosPorFinalizar ( $this->CI->configuracion->variables ( "notificacionSeguimiento" ) );
		if ($seguimientos && is_array ( $seguimientos ) && count ( $seguimientos ) > 0) {
			$emails = array ();
			foreach ( $seguimientos as $s ) {
				if (! isset ( $emails [$s->email] )) {
					$emails [$s->email] = array (
							"seudonimo" => $s->seudonimo,
							"articulos" => array (),
							"seguimientos" => array () 
					);
				}
				$emails [$s->email] ["articulos"] [$s->id] = $s->titulo;
				$emails [$s->email] ["seguimientos"] [] = $s->seguimiento;
			}
			if (count ( $emails ) > 0) {
				foreach ( $emails as $email => $econtenido ) {
					if (trim ( $email ) !== "" && count ( $econtenido ["articulos"] ) > 0) {
						if ($this->CI->myemail->enviarTemplate ( $email, "Artículos en seguimiento que finalizan hoy", "mail/articulos-finalizan-hoy", array (
								"seudonimo" => $econtenido ["seudonimo"],
								"articulos" => $econtenido ["articulos"] 
						) )) {
							foreach ( $emails [$email] ["seguimientos"] as $s ) {
								if (intval ( $s ) > 0) {
									$this->CI->db->update ( "siguiendo", array (
											"notificado" => "Si" 
									), array (
											"id" => $s 
									) );
								}
							}
							print "[" . date ( "d/m/Y H:i:s" ) . "] - Email enviado para notificar articulos que finalizan en sus seguimiento <" . $email . ">.\n";
						}
					}
				}
			}
		}
	}
	function run2() {
		set_time_limit ( 0 );
		$ahora = time ();
		$nahora = $ahora;
		$thora = 3600;
		$hora = floor ( $ahora / $thora );
		$nhora = 0;
		$this->reMapOfertas ();
		$this->reMapPaquetes ();
		$count = 0;
		if ($this->estaConectado ()) {
			while ( TRUE ) {
				if ($nahora !== $ahora) {
					$nahora = $ahora;
					$this->revisarVigenciaOfertas ();
					$this->verificarDenuncias ();
				}
				if ($nhora !== $hora) {
					$nhora = $hora;
					$this->notificarArticulosPorVencer ();
				}
				$ahora = time ();
				$hora = floor ( $ahora / $thora );
				$count ++;
				if ($count % 43200 === 0) {
					$this->reMapOfertas ();
					$this->reMapPaquetes ();
				}
				// print "Durmiendo 1 segundo.\n";
				sleep ( 1 );
			}
		}
	}
	function run() {
		if ($this->estaConectado ()) {
			$welcomeMessage = "Bienvenido al servidor de consulta de ofertas";
			set_time_limit ( 0 );
			$max_clients = 5000;
			socket_listen ( $this->socket, $max_clients );
			
			$this->clients = array (
					"0" => array (
							"socket" => $this->socket 
					) 
			);
			$ahora = time ();
			$nahora = $ahora;
			$this->reMapOfertas ();
			while ( TRUE ) {
				if ($nahora !== $ahora) {
					$nahora = $ahora;
					$this->revisarVigenciaOfertas ();
				}
				$read [0] = $this->socket;
				
				for($i = 1; $i < count ( $this->clients ) + 1; ++ $i) {
					if (isset ( $this->clients [$i] ) && $this->clients [$i] != NULL) {
						$read [$i + 1] = $this->clients [$i] ['socket'];
					}
				}
				$ready = socket_select ( $read, $write = NULL, $except = NULL, $tv_sec = NULL );
				if (in_array ( $this->socket, $read )) {
					for($i = 1; $i < $max_clients + 1; ++ $i) {
						if (! isset ( $this->clients [$i] )) {
							$this->clients [$i] ['socket'] = socket_accept ( $this->socket );
							
							socket_getpeername ( $this->clients [$i] ['socket'], $ip );
							
							$this->clients [$i] ['name'] = $ip;
							$this->clients [$i] ['nope'] = false;
							$this->clients [$i] ['connect'] = date ( "Y-m-d H:i:s" );
							$this->clients [$i] ['data'] = false;
							
							echo "New client connected: " . $i . " \r\n";
							break;
						} elseif ($i == $max_clients - 1) {
							echo "To many Clients connected!" . "\r\n";
						}
						
						if ($ready < 1) {
							continue;
						}
					}
				}
				$data = false;
				for($i = 1; $i < $max_clients + 1; ++ $i) {
					if (isset ( $this->clients [$i] ) && in_array ( $this->clients [$i] ['socket'], $read )) {
						$data = @socket_read ( $this->clients [$i] ['socket'], $this->limitRead, PHP_NORMAL_READ );
						if ($data === false) {
							echo "Client ", $this->clients [$i] ['name'], " disconnected!", "\n";
							unset ( $this->clients [$i] );
							break;
						}
						if ($data) {
							if (! $this->clients [$i] ['nope']) {
								echo "Client ", $this->clients [$i] ['name'], " retrieving data...\n";
							}
							if ($data !== $this->clients [$i] ['data']) {
								$this->clients [$i] ['data'] = $data;
								print "[" . date ( "Y-m-d H:i:s" ) . "] data=$data\n";
							}
							$res = $this->processData ( $data, $i );
							if ($res) {
								if ($res === "exit") {
									$this->enviarMensaje ( $this->clients [$i] ['socket'], "Thanks for trying my Custom Socket Server, goodbye." );
									echo "Client ", $this->clients [$i] ['name'], " is exiting.", "\n";
									unset ( $this->clients [$i] );
									continue;
								}
								if ($res === "shutdown") {
									$this->enviarMensaje ( $this->clients [$i] ['socket'], "Bye." );
									echo "Shutdowning server.", "\n";
									break 2;
								}
								echo ($this->clients [$i] ['name'] . " is sending a message!: $res" . "\n");
								$this->enviarMensaje ( $this->clients [$i] ['socket'], "data:=:" . $res );
								break;
							} else {
								
								$this->enviarMensaje ( $this->clients [$i] ['socket'], "salir", true, $i );
								break;
							}
						}
					}
				}
				$ahora = time ();
				
				print "Durmiendo 1 segundo.\n";
				sleep ( 1 );
			}
		}
	}
	private function enviarMensaje(&$socket, $mensaje, $nope = false, $pos = false) {
		if ($socket) {
			if ($nope && $pos !== false) {
				if (! $this->clients [$pos] ["nope"]) {
					echo ($this->clients [$pos] ["name"] . " send a nope message!" . "\n");
				}
				$this->clients [$pos] ["nope"] = true;
			} else {
				$this->clients [$pos] ["nope"] = false;
			}
			if (@socket_write ( $socket, "$mensaje\n" )) {
				return true;
			} else {
				unset ( $socket );
			}
		}
		return false;
	}
	private function processData($data, $pos) {
		$data = trim ( $data );
		if ($data) {
			$cmds = explode ( "=", $data );
			if (count ( $cmds ) > 1) {
				switch ($cmds [0]) {
					case "comando" :
						$params = explode ( "|", $cmds [1] );
						$method = array_shift ( $params );
						if (method_exists ( $this, $method )) {
							return $this->$method ( $params, $pos );
						}
						break;
				}
			}
		}
		return false;
	}
	function salir() {
		return "shutdown";
	}
	function clientName($params = false, $pos = false) {
		$name = false;
		if (is_array ( $params )) {
			if (count ( $params ) > 0) {
				$name = array_shift ( $params );
				if (trim ( $name ) === "") {
					$name = false;
				}
			}
		}
		print "Setting Name: $name at pos $pos\n";
		if ($name && $pos !== false) {
			$this->clients [$pos] ["name"] = $name;
		}
		return false;
	}
	function darTodasOfertas($params = false) {
		$notin = array ();
		$articulo = false;
		if (is_array ( $params )) {
			if (count ( $params ) > 0) {
				$notin = array_shift ( $params );
				if (trim ( $notin ) === "") {
					$notin = false;
				} else {
					$notin = explode ( ",", $notin );
				}
			}
			if (count ( $params ) > 0) {
				$articulo = array_shift ( $params );
			}
		}
		if (! isset ( $this->ofertas )) {
			$this->ofertas = array ();
		}
		if ($articulo) {
			if (! isset ( $this->ofertas [$articulo] )) {
				$this->ofertas [$articulo] = array ();
			}
			
			if (count ( $this->ofertas [$articulo] ) > 0) {
				$res = array ();
				$notin = $notin ? $notin : array ();
				foreach ( $this->ofertas [$articulo] as $oferta ) {
					if (array_search ( $oferta [0], $notin ) === false) {
						array_push ( $res, $oferta );
					}
				}
				if (count ( $res ) > 0) {
					return base64_encode ( serialize ( $res ) );
				}
			}
		} elseif (count ( $this->ofertas ) > 0) {
			return base64_encode ( serialize ( $this->ofertas ) );
		}
		return false;
	}
	function verClientesConectados() {
		var_dump ( $this->clients );
		return false;
	}
	function reMapOfertas($params = false) {
		print "Remapping\n";
		$articulo = false;
		if (is_array ( $params )) {
			if (count ( $params ) > 0) {
				$articulo = array_shift ( $params );
			}
		}
		if ($articulo) {
			unset ( $this->ofertas [$articulo] );
			$this->ofertas [$articulo] = array ();
		} else {
			unset ( $this->ofertas );
			$this->ofertas = array ();
		}
		
		$this->CI->db->reconnect ();
		$this->CI->load->model ( "articulo_model", "articulo" );
		$ofertas = $this->CI->articulo->listarOfertas ( false, $articulo, true );
		if ($ofertas) {
			foreach ( $ofertas as $oferta ) {
				if (! isset ( $this->ofertas [$oferta->articulo_id] )) {
					$this->ofertas [$oferta->articulo_id] = array ();
				}
				array_push ( $this->ofertas [$oferta->articulo_id], array (
						$oferta->id,
						$oferta->monto,
						$oferta->usuario_id,
						$oferta->seudonimo,
						$oferta->codigo,
						$oferta->monto_automatico 
				) );
			}
		}
		print date ( "Y-m-d H:i:s" ) . ": " . count ( $this->ofertas ) . " articulos\n";
		return true;
	}
	function __destruct() {
		if (isset ( $this->socket ) && $this->socket) {
			socket_close ( $this->socket );
		}
	}
}
class ServicioExcepcion extends Exception {
	/*
	 * (non-PHPdoc) @see Exception::__construct()
	 */
	public function __construct($message = null, $code = null, $previous = null) {
		parent::__construct ( $message, $code, $previous );
	}
}

?>