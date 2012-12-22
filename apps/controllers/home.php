<?php
require_once 'basecontroller.php';
class Home extends BaseController {
	public function __construct() {
		parent::__construct ();
		$this->load->model ( "Categoria_model", "categoria" );
		$this->load->model ( "Articulo_model", "articulo" );
	}
	public function cancelarEnviosPendientes() {
		$exito = false;
		$id = $this->input->post ( "id" );
		if ($id && intval ( $id ) > 0) {
			$exito = $this->db->delete ( "envio_correo", array (
					"correo_masivo" => $id,
					"estado" => "Pendiente" 
			) );
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function enviarMails() {
		$c = $this->configuracion->variables ( "cantidadEmails" );
		$r = $this->db->select ( "envio_correo.id,correo_masivo.asunto,correo_masivo.mensaje,envio_correo.destinatario,envio_correo.nombre" )->where ( array (
				"envio_correo.estado" => "Pendiente" 
		) )->join ( "correo_masivo", "correo_masivo.id=envio_correo.correo_masivo", "inner" )->get ( "envio_correo", $c, 0 )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			$this->load->library ( "myemail" );
			foreach ( $r as $e ) {
				if (trim ( $e->destinatario ) != "") {
					if ($this->myemail->enviarTemplate ( $e->destinatario, str_ireplace ( "%nombre%", $e->nombre, $e->asunto ), "mail/mail-base", array (
							"mensaje" => str_ireplace ( "%nombre%", $e->nombre, $e->mensaje ) 
					) )) {
						$this->db->update ( "envio_correo", array (
								"estado" => "Enviado",
								"fecha" => date ( "Y-m-d H:i:s" ) 
						), array (
								"id" => $e->id 
						) );
						print "[" . date ( "Y-m-d H:i:s" ) . "] Enviado a $e->destinatario.<br/>";
						sleep ( 1 ); // espera para no saturar el server
					}
				}
			}
		}
	}
	public function newsletter() {
		if ($this->myuser) {
			if ($this->myuser->tipo == "Administrador") {
				$data = $this->procesarNewsletter ();
				$data = array_merge ( $data, $this->obtenerEnvios () );
				$data ["vista"] = "newsletter";
				$this->loadGUI ( "administrador/newsletter", $data );
			} else {
				redirect ( "store/{$this->myuser->seudonimo}", "refresh" );
			}
		} else {
			redirect ( "login", "refresh" );
		}
	}
	public function obtenerEnvios() {
		return array (
				"envios" => $this->db->select ( "id,asunto,mensaje,fecha,obtenerPorcentajeEnvio(id) as porcentaje" )->order_by ( "fecha desc" )->get ( "correo_masivo" )->result () 
		);
	}
	private function procesarNewsletter() {
		$retorno = array (
				"errores" => array () 
		);
		if (isset ( $_POST ["asunto"] )) {
			$asunto = $this->input->post ( "asunto" );
			$mensaje = $this->input->post ( "mensaje" );
			$destino = $this->input->post ( "destino" );
			
			if ($asunto && $mensaje && $destino) {
				$mensaje = base64_decode ( $mensaje );
				$emails = array ();
				switch ($destino) {
					case "1" :
						$r = $this->db->select ( "email,seudonimo" )->where ( array (
								"notificaciones" => "1" 
						) )->get ( "usuario" )->result ();
						if ($r && is_array ( $r ) && count ( $r ) > 0) {
							foreach ( $r as $u ) {
								$emails [$u->email] = $u->seudonimo;
							}
							unset ( $r );
						}
						break;
					case "2" :
						if (isset ( $_FILES ) && isset ( $_FILES ["excel"] ))
							$emails = $this->importarEmails ( $_FILES ["excel"] );
						break;
				}
				/*
				 * $emails = array ( "goaamb@gmail.com" => "Alvaro Justo Michel
				 * Barrera" );
				 */
				if (count ( $emails ) > 0) {
					if ($this->db->insert ( "correo_masivo", array (
							"asunto" => $asunto,
							"mensaje" => $mensaje,
							"fecha" => date ( "Y-m-d H:i:s" ) 
					) )) {
						$eid = $this->db->insert_id ();
						$retorno ["exito"] = 1;
						foreach ( $emails as $e => $n ) {
							if (trim ( $e ) !== "") {
								$this->db->insert ( "envio_correo", array (
										"correo_masivo" => $eid,
										"destinatario" => $e,
										"nombre" => $n 
								) );
							}
						}
					}
				} else {
					$retorno ["errores"] [] = "general-No-Email";
				}
			} else {
				if (! $asunto)
					$retorno ["errores"] [] = "asunto";
				if (! $mensaje)
					$retorno ["errores"] [] = "mensaje";
				if (! $destino)
					$retorno ["errores"] [] = "destino";
			}
		}
		return $retorno;
	}
	private function importarEmails($files) {
		$emails = array ();
		if (isset ( $files ["tmp_name"] ) && is_file ( $files ["tmp_name"] )) {
			ini_set ( "max_execution_time", 3600 );
			ini_set ( "memory_limit", "256M" );
			require_once (BASEPATH . "../apps/libraries/PHPExcel/IOFactory.php");
			$objPHPExcel = PHPExcel_IOFactory::load ( $files ["tmp_name"] );
			$h = $objPHPExcel->getActiveSheet ();
			$r = 2;
			$c = 0;
			$count = 0;
			$categorias = array ();
			do {
				$nombre = $h->getCellByColumnAndRow ( $c, $r )->getValue ();
				$email = $h->getCellByColumnAndRow ( $c + 1, $r )->getValue ();
				if (trim ( $email ) !== "") {
					$p = "/[\w-\.]{3,}@([\w-]{2,}\.)*([\w-]{2,}\.)[\w-]{2,4}/";
					if (preg_match ( $p, $email )) {
						$emails [$email] = $nombre;
						$count ++;
					}
				}
				$r ++;
			} while ( trim ( $email ) !== "" );
		}
		return $emails;
	}
	public function importarCategorias() {
		$data = array ();
		$archivo = (isset ( $_FILES ) && isset ( $_FILES ["archivo"] )) ? $_FILES ["archivo"] : false;
		if ($archivo && is_file ( $archivo ["tmp_name"] )) {
			require_once (BASEPATH . "../apps/libraries/PHPExcel/IOFactory.php");
			$objPHPExcel = PHPExcel_IOFactory::load ( $archivo ["tmp_name"] );
			$h = $objPHPExcel->getActiveSheet ();
			$r = 2;
			$c = 0;
			$count = 0;
			do {
				$celda = $h->getCellByColumnAndRow ( $c, $r )->getValue ();
				if (trim ( $celda ) !== "") {
					$padre = $celda;
					$this->db->where ( array (
							"id" => $padre 
					) );
					$rc = $this->db->get ( "categoria" )->result ();
					if (! ($rc && is_array ( $rc ) && count ( $rc ) > 0)) {
						continue;
					}
					$espanol = $h->getCellByColumnAndRow ( $c + 1, $r )->getValue ();
					$ingles = $h->getCellByColumnAndRow ( $c + 2, $r )->getValue ();
					$categoria = new stdClass ();
					$categoria->padre = $rc [0]->id;
					$categoria->nivel = $rc [0]->nivel + 1;
					$categoria->activo = $rc [0]->activo;
					$categoria->cantidad = 0;
					if ($this->db->insert ( "categoria", $categoria )) {
						$cid = $this->db->insert_id ();
						$this->db->insert ( "nombrecategoria", array (
								"categoria" => $cid,
								"lenguaje" => 4,
								"nombre" => $espanol,
								"url_amigable" => normalizarTexto ( $espanol ) 
						) );
						$this->db->insert ( "nombrecategoria", array (
								"categoria" => $cid,
								"lenguaje" => 2,
								"nombre" => $ingles,
								"url_amigable" => normalizarTexto ( $ingles ) 
						) );
					}
					
					$count ++;
				}
				$r ++;
			} while ( trim ( $celda ) !== "" );
			if ($count > 0) {
				$data ["Mensaje"] = "Se importaron $count Categorías";
			} else {
				$data ["Error"] = "No se importo ninguna Categoría";
			}
		}
		$this->loadGUI ( "importar_categorias", $data );
	}
	private function obtenerCategorias($padre) {
		$this->db->select ( "categoria.id as id,nombrecategoria.nombre as nombre" );
		$this->db->where ( array (
				"padre" => $padre,
				"activo" => 1 
		) );
		$this->db->order_by ( "nombre asc" );
		$retorno = array ();
		$this->db->join ( "nombrecategoria", "nombrecategoria.categoria=categoria.id and nombrecategoria.lenguaje=4" );
		$r = $this->db->get ( "categoria" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			foreach ( $r as $c ) {
				$retorno [$c->id] = array (
						"nombre" => $c->nombre,
						"hijos" => $this->obtenerCategorias ( $c->id ) 
				);
			}
		}
		return $retorno;
	}
	public function categorias() {
		$c = $this->obtenerCategorias ( 0 );
		$this->load->library ( "PHPExcel" );
		$objPHPExcel = $this->phpexcel;
		
		$h = $objPHPExcel->setActiveSheetIndex ( 0 );
		$objPHPExcel->getActiveSheet ()->setTitle ( 'Categorias' );
		$v = 0;
		$cc = "A";
		$this->imprimirCategorias ( $c, $h, $v, $cc );
		
		$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel2007' );
		$base = "files/" . rand () . ".xlsx";
		$dir = BASEPATH . "../$base";
		$objWriter->save ( $dir );
		redirect ( $base, "refresh" );
	}
	private function imprimirCategorias($ca, $h, &$v, $cc) {
		foreach ( $ca as $id => $c ) {
			$v ++;
			if (count ( $c ["hijos"] ) > 0) {
				$h->setCellValue ( $cc . $v, $c ["nombre"] . "(" . $id . ")" );
				$h->mergeCells ( "$cc$v:" . chr ( ord ( $cc ) + 1 ) . "$v" )->getStyle ( "$cc$v:" . chr ( ord ( $cc ) + 1 ) . "$v" )->applyFromArray ( array (
						'font' => array (
								'bold' => true 
						),
						'alignment' => array (
								'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT 
						) 
				) );
				$this->imprimirCategorias ( $c ["hijos"], $h, $v, chr ( ord ( $cc ) + 1 ) );
			} else {
				$h->setCellValue ( $cc . $v, $c ["nombre"] );
				$h->setCellValue ( chr ( ord ( $cc ) + 1 ) . "$v", $id );
			}
		}
	}
	public function denunciar() {
		$exito = false;
		if ($this->myuser) {
			$articulo = $this->input->post ( "articulo" );
			$perfil = $this->input->post ( "usuario" );
			$asunto = $this->input->post ( "motivo" );
			$descripcion = $this->input->post ( "descripcion" );
			
			if ($asunto) {
				$exito = $this->db->insert ( "reporte", array (
						"asunto" => $asunto,
						"usuario" => $this->myuser->id,
						"perfil" => $perfil ? $perfil : null,
						"descripcion" => $descripcion,
						"fecha" => date ( "Y-m-d H:i:s" ),
						"articulo" => $articulo ? $articulo : null,
						"estado" => "Pendiente" 
				) );
			}
		}
		$this->output->set_output ( json_encode ( array (
				"exito" => $exito 
		) ) );
	}
	public function integrarNombre() {
		$this->db->select ( "id,nombre,apellido" );
		$r = $this->db->get ( "usuario" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			foreach ( $r as $u ) {
				$this->db->update ( "usuario", array (
						"nombre" => $u->nombre . " " . $u->apellido,
						"apellido" => "" 
				), array (
						"id" => $u->id 
				) );
			}
		}
	}
	public function crearFacturas() {
		$anio = date ( "Y" );
		$mes = date ( "m" );
		$us = $this->usuario->darUsuarios ();
		if ($us && is_array ( $us ) && count ( $us ) > 0) {
			foreach ( $us as $u ) {
				$this->crearCuentas ( $mes, $anio, $u->id );
				$this->cambiarTipoTarifa ( $u );
			}
		}
	}
	private function cambiarTipoTarifa($usuario) {
		if ($usuario && $usuario->nueva_tarifa) {
			$this->db->update ( "usuario", array (
					"nueva_tarifa" => null,
					"tipo_tarifa" => $usuario->nueva_tarifa 
			), array (
					"id" => $usuario->id 
			) );
		}
	}
	private function crearCuentas($mes, $anio, $usuario) {
		$this->load->model ( "Usuario_model", "usuario" );
		$usuario = $this->usuario->darUsuarioXId ( $usuario );
		if ($usuario) {
			$x = $this->articulo->darDatosCuentas ( $mes, $anio, $usuario );
			if ($x) {
				$this->db->insert ( "factura", array (
						"codigo" => $x->codigo,
						"mes" => $x->mes,
						"usuario" => $x->usuario,
						"fecha" => $x->fecha,
						"articulos" => $x->articulos,
						"monto_total" => $x->monto_total,
						"monto_tarifa" => $x->monto_tarifa,
						"iva" => $x->iva,
						"tipo_tarifa" => $usuario->tipo_tarifa 
				) );
				print "se creo con exito la factura del $mes-$anio y usuario: $usuario->id-$usuario->seudonimo<br/>";
			} else {
				print "No se creo la factura posiblemente no tenga datos o bien ya fue creada<br/>";
			}
		} else {
			print "No existe el usurio<br/>";
		}
	}
	public function enviarMail() {
		$asunto = trim ( $this->input->post ( "asunto" ) );
		$mensaje = trim ( $this->input->post ( "mensaje" ) );
		$nombre = trim ( $this->input->post ( "nombre" ) );
		$email = trim ( $this->input->post ( "email" ) );
		if ($asunto !== "" && $mensaje !== "" && $nombre !== "" && $email !== "") {
			$emailto = "info@lovende.com";
			// $emailto = "goaamb@gmail.com";
			$this->load->library ( "myemail" );
			var_dump ( $this->myemail->enviarTemplate ( $emailto, $asunto, "mail/enviar-mail", array (
					"asunto" => $asunto,
					"nombre" => $nombre,
					"mensaje" => nl2br ( $mensaje ),
					"email" => $email 
			) ) );
		}
	}
	public function estatica($view) {
		$this->loadGUI ( $view );
	}
	public function cambiarCarpetas() {
		$res = $this->db->get ( "usuario" );
		if ($res) {
			$res = $res->result ();
			if ($res) {
				foreach ( $res as $usuario ) {
					$dir = BASEPATH . "../files/$usuario->seudonimo";
					print $dir . " -> ";
					if (is_dir ( $dir )) {
						$dird = BASEPATH . "../files/$usuario->id";
						print $dird;
						rename ( $dir, $dird );
					}
					print "<br/>";
				}
			}
		}
	}
	public function modal($modal, $tipo = false, $id = false, $extra = false, $pagos = false) {
		$data = array ();
		if ($tipo == "mail") {
			$asuntos = array (
					1 => traducir ( "Consulta de tarifas" ),
					2 => traducir ( "Consulta de privacidad" ) 
			);
			$id = array_search ( $id, array_keys ( $asuntos ) ) !== false ? $id : 1;
			$data ["asunto"] = $asuntos [$id];
		} else {
			if ($this->isLogged ()) {
				switch ($tipo) {
					case "mensaje" :
						$data ["receptor"] = $this->usuario->darUsuarioXId ( $id );
						break;
					case "articulo" :
						$data ["articulo"] = $this->articulo->darArticulo ( $id );
						break;
					case "usuario" :
						$data ["usuario"] = $this->usuario->darUsuarioXId ( $id );
						break;
					case "votos" :
						$data ["usuario"] = $this->usuario->darUsuarioXId ( $id );
						$data ["mes1"] = $this->usuario->darVotos ( $id, 1 );
						$data ["mes6"] = $this->usuario->darVotos ( $id, 6 );
						$data ["mes12"] = $this->usuario->darVotos ( $id, 12 );
						$data ["todos"] = $this->usuario->darVotos ( $id );
						break;
					case "myuser" :
						$data ["usuario"] = $this->usuario->darUsuarioXId ( $this->myuser->id );
						break;
					case "articulosComprados" :
						$this->load->model ( "Paypal_model", "paypal" );
						$data ["comprador"] = $this->usuario->darUsuarioXId ( $id );
						if ($data ["comprador"]) {
							$data ["comprador"]->pais = $data ["comprador"]->darPais ();
							$data ["comprador"]->ciudad = $data ["comprador"]->darCiudad ();
							$data ["articulos"] = $this->articulo->listarArticulosPorComprar ( $data ["comprador"]->id, $this->myuser->id, $extra, $pagos );
							$data ["paquete"] = $this->articulo->darPaquete ( $extra );
							if ($data ["paquete"]) {
								$data ["vendedor"] = $this->usuario->darUsuarioXId ( $data ["paquete"]->vendedor );
							}
						}
						break;
					case "articulosVendedor" :
						$this->load->model ( "Paypal_model", "paypal" );
						$data ["vendedor"] = $this->usuario->darUsuarioXId ( $id );
						if ($data ["vendedor"]) {
							$data ["vendedor"]->pais = $data ["vendedor"]->darPais ();
							$data ["vendedor"]->ciudad = $data ["vendedor"]->darCiudad ();
							$data ["articulos"] = $this->articulo->listarArticulosPorComprar ( $this->myuser->id, $data ["vendedor"]->id, $extra, $pagos );
							$data ["paquete"] = $this->articulo->darPaquete ( $extra );
							if ($data ["paquete"]) {
								$data ["comprador"] = $this->usuario->darUsuarioXId ( $data ["paquete"]->comprador );
							}
						}
						break;
					case "paquete" :
						$this->load->model ( "Paypal_model", "paypal" );
						$data ["comprador"] = $this->usuario->darUsuarioXId ( $id );
						$data ["paquete"] = $this->articulo->darPaquete ( $extra );
						if ($data ["paquete"]) {
							$data ["vendedor"] = $this->usuario->darUsuarioXId ( $data ["paquete"]->vendedor );
						}
						break;
					case "comprador" :
						$data ["comprador"] = $this->usuario->darUsuarioXId ( $id );
						if ($data ["comprador"]) {
							$data ["comprador"]->pais = $data ["comprador"]->darPais ();
							$data ["comprador"]->ciudad = $data ["comprador"]->darCiudad ();
						}
						break;
					case "vendedor" :
						$data ["vendedor"] = $this->usuario->darUsuarioXId ( $id );
						if ($data ["vendedor"]) {
							$data ["vendedor"]->pais = $data ["vendedor"]->darPais ();
							$data ["vendedor"]->ciudad = $data ["vendedor"]->darCiudad ();
						}
						break;
					case "facturaDetalle" :
						$mes = date ( "m" );
						$anio = date ( "Y" );
						$bmes = "$mes-$anio";
						$data ["factura"] = false;
						if ($id == "x") {
							$data ["factura"] = ($this->articulo->darDatosCuentas ( $mes, $anio, $this->myuser ));
						} else {
							$data ["factura"] = $this->articulo->darFactura ( $id );
						}
						if ($data ["factura"]) {
							$data ["usuario"] = $this->usuario->darUsuarioXId ( $data ["factura"]->usuario );
							if ($data ["usuario"]) {
								$data ["usuario"]->pais = $this->usuario->darPais ( $data ["usuario"]->pais );
								if ($data ["usuario"]->tipo_tarifa == "Comision") {
									$data ["cuentas"] = $this->articulo->darCuentasPorArticulos ( $data ["factura"]->articulos );
								} else {
									$data ["cuentas"] = $this->articulo->darCuentasFakePorArticulos ( $data ["factura"]->articulos );
								}
							}
						}
						break;
					case "denunciamensaje" :
						if ($this->myuser) {
							$data ['reportador'] = $this->usuario->darUsuarioXId ( $pagos );
							$data ['idmensaje'] = $extra;
							$data ['reportado'] = $this->usuario->darUsuarioXId ( $id );
							break;
						} else {
							redirect ( "login", "refresh" );
							return;
						}
				}
			} else {
				$modal = "redirect-login";
			}
		}
		parent::modal ( $modal, $data );
	}
	public function leerDirectorio($path) {
		$directorio = dir ( $path );
		$files = array ();
		while ( $archivo = $directorio->read () ) {
			if ($archivo != "." && $archivo != "..") {
				$f = $path . $archivo;
				if (is_file ( $f )) {
					$files [] = $f;
				} else {
					$d = $f . "/";
					$files = array_merge ( $files, $this->leerDirectorio ( $d ) );
				}
			}
		}
		$directorio->close ();
		return $files;
	}
	public function removerImagenesInnescesarias() {
		ini_set ( "max_execution_time", 3600 );
		$this->db->select ( "imagen,id" );
		$res = $this->db->get ( "usuario" );
		$res = $res->result ();
		$imagenes = array ();
		
		foreach ( $res as $r ) {
			if (! isset ( $imagenes [$r->id] )) {
				$imagenes [$r->id] = array ();
			}
			$imagenes [$r->id] [] = pathinfo ( $r->imagen, PATHINFO_FILENAME );
		}
		$this->db->select ( "foto,usuario.id as id" );
		$this->db->join ( "usuario", "usuario.id=articulo.usuario" );
		$res = $this->db->get ( "articulo" );
		$res = $res->result ();
		foreach ( $res as $r ) {
			if (! isset ( $imagenes [$r->id] )) {
				$imagenes [$r->id] = array ();
			}
			$f = explode ( ",", $r->foto );
			foreach ( $f as $i ) {
				$imagenes [$r->id] [] = pathinfo ( $i, PATHINFO_FILENAME );
			}
		}
		
		$base = "files/";
		$dir = BASEPATH . "../$base";
		$fs = $this->leerDirectorio ( $dir );
		
		$e = false;
		foreach ( $fs as $f ) {
			foreach ( $imagenes as $id => $i ) {
				foreach ( $i as $img ) {
					if (trim ( $img ) !== "") {
						$fx = $id . "/" . $img . ".";
						if (strstr ( $f, $fx ) !== false) {
							$e = true;
							break (2);
						}
					}
				}
			}
			if (! $e) {
				print "D - $f<br/>";
				@unlink ( $f );
			}
		}
	}
	public function verMas() {
		$criterio = $this->input->post ( "criterio" );
		$pagina = $this->input->post ( "pagina" );
		$section = $this->input->post ( "section" );
		$orden = $this->input->post ( "orden" );
		$ubicacion = $this->input->post ( "ubicacion" );
		$categoria = $this->input->post ( "categoria" );
		$usuario = $this->input->post ( "usuario" );
		$data ["total"] = 0;
		$data ["final"] = 0;
		$data = array_merge ( $this->articulo->leerArticulos ( $pagina, $criterio, $section, $orden, $ubicacion, $categoria, $this->idioma->language->id, $usuario ), array (
				"section" => $section 
		) );
		$json = array ();
		$a = $data ["articulos"] ? $data ["articulos"] : false;
		if (isset ( $a ) && $a && is_array ( $a ) && count ( $a ) > 0) {
			$a = $data ["articulos"];
			$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
			foreach ( $data ["articulos"] as $articulo ) {
				$x = array ();
				
				$imagen = array_shift ( explode ( ",", $articulo->foto ) );
				$imagen = imagenArticulo ( $articulo->usuario, $imagen, "thumb" );
				if ($imagen) {
					$x ["id"] = $articulo->id;
					$x ["titulo"] = $articulo->titulo;
					$x ["tipo"] = $articulo->tipo;
					$x ["furl"] = "product/" . $x ["id"] . "-" . normalizarTexto ( $x ["titulo"] );
					$x ["imagen"] = $imagen;
					list ( , $x ["height"] ) = getimagesize ( BASEPATH . "../$imagen" );
					$x ["precio"] = formato_moneda ( $articulo->precio ) . " \$us";
					if ($x ["tipo"] == "Fijo") {
						$x ["cantidadOfertas"] = $articulo->cantidadOfertas . " " . traducir ( "ofertas" );
						$x ["cO"] = $articulo->cantidadOfertas;
						$x ["tiempo"] = calculaTiempoDiferencia ( date ( "Y-m-d H:i:s" ), strtotime ( $articulo->fecha_registro ) + $vencimientoOferta, true );
						$x ["textoOferta"] = "";
					} elseif ($x ["tipo"] == "Cantidad") {
						$x ["cC"] = $articulo->cantidad;
						$x ["textoOferta"] = "";
						$x ["tiempo"] = calculaTiempoDiferencia ( date ( "Y-m-d H:i:s" ), strtotime ( $articulo->fecha_registro ) + $vencimientoOferta, true );
					} else {
						$x ["mayorPuja"] = formato_moneda ( $articulo->mayorPuja ) . " \$us";
						$x ["cantidadPujas"] = $articulo->cantidadPujas . " " . traducir ( "pujas" );
						$x ["cP"] = $articulo->cantidadPujas;
						$x ["tiempo"] = calculaTiempoDiferencia ( date ( "Y-m-d H:i:s" ), strtotime ( $articulo->fecha_registro ) + $articulo->duracion * 86400, true );
					}
					$x ["pais_nombre"] = traducir ( "Ubicación:" ) . " " . $articulo->pais_nombre;
					$json [] = $x;
				}
			}
		}
		$this->output->set_output ( json_encode ( array (
				"articulos" => $json,
				"total" => $data ["total"],
				"final" => $data ["inicio"] + count ( $json ) 
		) ) );
	}
	public function index($noExiste = false) {
		parent::index ( true );
		if (! $noExiste) {
			$uri = explode ( "/", uri_string () );
			$section = array_shift ( $uri );
			$action = array_shift ( $uri );
			
			$data = array_merge ( $this->articulo->leerArticulos ( 0, $this->input->get ( "criterio" ), $action, $this->input->get ( "orden" ), $this->input->get ( "ubicacion" ), $this->input->get ( "categoria" ), $this->idioma->language->id ), array (
					"section" => $action,
					"orden" => $this->input->get ( "orden" ),
					"ubicacion" => $this->input->get ( "ubicacion" ),
					"categoria" => $this->input->get ( "categoria" ) 
			) );
			$this->loadGUI ( "home", $data );
		} else {
			$cats = $this->categoria->darCategoriasXNivel ( 1 );
			$retcat = $this->parseCategories ( $cats );
			$this->loadGUI ( "no-existe", array (
					"categorias" => $retcat 
			) );
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
	public function process() {
		$data = array ();
		return array_merge ( parent::process (), $data );
	}
	public function info() {
		phpinfo ();
	}
}