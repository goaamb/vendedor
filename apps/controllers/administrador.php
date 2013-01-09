<?php
require_once 'basecontroller.php';
class Administrador extends BaseController {
	public function __construct() {
		parent::__construct ();
		$this->load->helper ( 'url' );
		$this->load->model ( "administrador_model", "administracion" );
		$this->load->model ( 'usuario_model', 'objusuario' );
	}
	
	public function index($pagina = false) {
		
		if($this->myuser)
		{
			if($this->myuser->tipo=='Administrador')
			{
				$data = array ();
				switch ($pagina) {
				case "dashboard" :
					$data['vista'] = 'seeall'; 
					$data['cantidadtotal'] = $this->administracion->contarreporte();
					$data['pendiente'] = $this->administracion->contarreporte('Pendiente');
					$data['nomarcado'] = $this->administracion->contarreporte('Procesando');
					$data['reporte'] = $this->administracion->datos(false, false, false, 0, $this->configuracion->variables ( "cantidadPaginacion" ));
					$view = "administrador/dashboard";
					break;
				case "pending" :
					$data['vista'] = 'pending';
					$data['cantidadtotal'] = $this->administracion->contarreporte();
					$data['pendiente'] = $this->administracion->contarreporte('Pendiente');
					$data['nomarcado'] = $this->administracion->contarreporte('Procesando');
					$data['reporte'] = $this->administracion->datos('Pendiente', false, false, 0, $this->configuracion->variables ( "cantidadPaginacion" ));
					$view = "administrador/dashboard";
					break;
				case "unmarked" :
					$data['vista'] = 'unmarked';
					$data['cantidadtotal'] = $this->administracion->contarreporte();
					$data['pendiente'] = $this->administracion->contarreporte('Pendiente');
					$data['nomarcado'] = $this->administracion->contarreporte('Procesando');
					$data['reporte'] = $this->administracion->datos('Procesando', false, false, 0, $this->configuracion->variables ( "cantidadPaginacion" ));
					$view = "administrador/dashboard";
					break;
				case "billing" :
					$data['vista'] = 'seeall';
					$data['cantidadtotal'] = $this->administracion->contarfactura();
					$data['curso'] = $this->administracion->contarfactura('En curso');
					$data['pendiente'] = $this->administracion->contarfactura('Pendiente');
					$data['pagado'] = $this->administracion->contarfactura('Pagado');
					$fechaclasificado = date("m")."-".date("Y");
					
					$data['reporte'] = $this->administracion->devolverfactura($estado = false, $valor = false, $tipo=false, $fechaclasificado);
					$data['grupofechas'] = $this->administracion->devuelvegrupofechas();
					$data['valordefecto'] = $fechaclasificado;
					
					$view = "administrador/billing";
					break;
				case "curse" :
					$data['vista'] = 'curse';
					$data['cantidadtotal'] = $this->administracion->contarfactura();
					$data['curso'] = $this->administracion->contarfactura('En curso');
					$data['pendiente'] = $this->administracion->contarfactura('Pendiente');
					$data['pagado'] = $this->administracion->contarfactura('Pagado');
					$fechaclasificado = date("m")."-".date("Y");
					
					$data['reporte'] = $this->administracion->devolverfactura('En curso', $valor = false, $tipo=false, $fechaclasificado);
					$data['valordefecto'] = $fechaclasificado;
					
					$view = "administrador/billing";
					break;	
				case "facpending" :
					$data['vista'] = 'pending';
					$data['cantidadtotal'] = $this->administracion->contarfactura();
					$data['curso'] = $this->administracion->contarfactura('En curso');
					$data['pendiente'] = $this->administracion->contarfactura('Pendiente');
					$data['pagado'] = $this->administracion->contarfactura('Pagado');
					$fechaclasificado = date("m")."-".date("Y");
					
					$data['reporte'] = $this->administracion->devolverfactura('Pendiente', $valor = false, $tipo=false, $fechaclasificado);
					$data['valordefecto'] = $fechaclasificado;
					$view = "administrador/billing";
					break;
				case "paid" :
					$data['vista'] = 'paid';
					$data['cantidadtotal'] = $this->administracion->contarfactura();
					$data['curso'] = $this->administracion->contarfactura('En curso');
					$data['pendiente'] = $this->administracion->contarfactura('Pendiente');
					$data['pagado'] = $this->administracion->contarfactura('Pagado');
					$fechaclasificado = date("m")."-".date("Y");
					
					$data['reporte'] = $this->administracion->devolverfactura('Pagado', $valor = false, $tipo=false, $fechaclasificado);
					$data['valordefecto'] = $fechaclasificado;
					$view = "administrador/billing";
					break;
					
				case "admipm" :	
					$data['idadmin'] = $this->myuser->id;	
					$data['mensaje'] = $this->administracion->mensajeadmin();		
					$view = "administrador/admipm";
					break;	
	
				case "messagereport" :					
						$data['mensaje'] = $this->administracion->devolvermensajeX($pagina);
						$view = "administrador/mensajedenuncia";
						break;
				default :
					$data['vista'] = 'seeall';
					$data['cantidadtotal'] = $this->administracion->contarreporte();
					$data['pendiente'] = $this->administracion->contarreporte('Pendiente');
					$data['nomarcado'] = $this->administracion->contarreporte('Procesando');
					$data['reporte'] = $this->administracion->datos(false, false, false, 0, $this->configuracion->variables ( "cantidadPaginacion" ));
					$view = "administrador/dashboard";
					break;
				}
				$this->loadGUI ( $view, $data );
			}
			else
			{
				redirect ( "login", "refresh" );
			}		
		}
		else
		{
			redirect ( "login", "refresh" );
			return;
		}
	}

	public function mensajereportador()
	{
		
		$data['mensaje'] = $this->administracion->devolvermensajeX($this->input->get('id'));
		$view = "administrador/mensajedenuncia";
		$this->loadGUI ( $view, $data );
	}
	public function buscar()
	{
		$data['vista'] = 'seeall';
		$data['pendiente'] = $this->administracion->contarreporte('Pendiente');
		$data['nomarcado'] = $this->administracion->contarreporte('Procesando');
		$data['cantidadtotal'] = $this->administracion->contarreporte();
		
		$valor = $this->input->get('valor');
		$tipo = $this->input->get('tipo');
		
		$data['reporte'] = $this->administracion->datos(false, $valor, $tipo, 0, $this->configuracion->variables ( "cantidadPaginacion" ));
		
		$view = "administrador/dashboard";
		$this->loadGUI ( $view, $data );
	}

	public function mostrarmodalvotos()
	{
		$id = $this->input->get('id');
		$data['id'] = $id;
		
		$data['seudonimo'] = $this->input->get('seu');
		
		$data['posi'] = $this->input->get('posi');
		$data['neg'] = $this->input->get('neg');
		
		$resultado = $this->administracion->devolverdatousuario($id,'Positivo','Venta');		
		foreach ($resultado as $row)
		{
			if($row->cantidad != '')
			{
				$data['posiventa'] = $row->cantidad;
			}
			else
			{
				$data['posiventa'] = 0;
			}
		}
		
		$resultado = $this->administracion->devolverdatousuario($id,'Positivo','Compra');
		foreach ($resultado as $row)
		{
			if($row->cantidad != '')
			{
				$data['posicompra'] = $row->cantidad;
			}
			else
			{
				$data['posicompra'] = 0;
			}
			
		}
		
		$resultado = $this->administracion->devolverdatousuario($id,'Negativo','Venta');
		foreach ($resultado as $row)
		{
			if($row->cantidad != '')
			{
				$data['negventa'] = $row->cantidad;
			}
			else
			{
				$data['negventa'] = 0;
			}
		}
		
		$resultado = $this->administracion->devolverdatousuario($id,'Negativo','Compra');
		foreach ($resultado as $row)
		{
			if($row->cantidad != '')
			{
				$data['negcompra'] = $row->cantidad;
			}
			else
			{
				$data['negcompra'] = 0;
			}
		}
		
		//$data['negventa'] = $this->administracion->devolverdatousuario($id,'Negativo','Venta');
		//$data['negcompra'] = $this->administracion->devolverdatousuario($id,'Negativo','Compra');
		
		$view = "modal/modal-votes-modification";
		$this->load->view ( $view,$data);
	}

	public function guardarbdvotos()
	{
		$id = $this->input->post('id');
		
		$posiventa = $this->input->post('posiventa');
		$posicompra = $this->input->post('posicompra');
		$negventa = $this->input->post('negventa');
		$negcompra = $this->input->post('negcompra');
		
		$positivo = $this->input->post('positotal');
		$negativo = $this->input->post('negtotal');
		
		
		
		$this->administracion->guardarvoto($id,$positivo,$negativo,$posiventa,$posicompra,$negventa,$negcompra);
		
		echo '1';
		
	}

	public function cambiarestado()
	{
		$id = $this->input->post('reporte');
		$estado = $this->input->post('estado');
		$usuario = $this->myuser->id;
		$this->administracion->cambiarestado($id, $estado, $usuario);
		
		$reporteX = $this->administracion->devolverXrepoter($id);
		
		foreach ($reporteX as $row) 
		{
			?><td><?php echo $row->id;?></td>
					<td>es</td>
					<td><?php $fecha = new DateTime($row->fecha);
					echo $fecha->format('d-m-Y'); ?></td>
					<td> <?php if($row->descripcion != ''){?>
						<a href="#" class="actMenuB<?php echo $row->id;?>"><?php echo $row->asunto;?></a>
						<div class="act-menu-b w454">
							<div class="cont">
								<p><?php echo $row->descripcion;?></p>
							</div>
							<div class="arrow"></div>
						</div>
						<?php }else{echo $row->asunto;}?>
					</td>
					<td>
						<a href="#" class="actMenuB<?php echo $row->id;?>"><?php if($row->estadou=='Baneado'){ echo '<strike>'.$row->denuncianteseudonimo.'</strike>';}else{ echo $row->denuncianteseudonimo;}?></a> <span class="green">+<?php echo $row->denunciantevotopos;?></span> 
						<?php if($row->denunciantevotoneg > 0){?>
						<span class="red">-<?php echo $row->denunciantevotoneg;?></span>
						<?php }?>
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="store/<?php echo $row->denuncianteseudonimo;?>" target="_blank">Go to</a></li>
									<li><a href="javascript:historial('<?php echo $row->denuncianteseudonimo."','usuario";?>')">History</a></li>
									<li><a href="administrador/mostrarmodalmensaje?id=
										<?php echo $row->denunciante.'&seu='.$row->denuncianteseudonimo.'&posi='.$row->denunciantevotopos.'&neg='.$row->denunciantevotoneg;?>" class="">Send PM</a></li>
									<li>
										<a href="administrador/mostrarmodalvotos?id=
										<?php echo $row->denunciante.'&seu='.$row->denuncianteseudonimo.'&posi='.$row->denunciantevotopos.'&neg='.$row->denunciantevotoneg;?>" 
										class="nmodal">Vote</a>
									</li>
									<li><a href="javascript:banearusuario('<?php echo $row->denunciante; ?>','Baneado','usuario')" class="">Ban</a></li>
									<li><a href="javascript:banearusuario('<?php echo $row->denunciante; ?>','Activo','usuario')" class="">Unban</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>
					</td>
					<td>
					<?php 
						$usuariocadena = $row->seuusuario;
						$votopositivo = $row->posiusuario;
						$votonegativo = $row->negusuario;
						$idusuario = $row->idusuario;
						$estadovar = $row->estadou2;
						if($row->seuusuarioarticulo != '')
							{
								$usuariocadena = $row->seuusuarioarticulo;
								$votopositivo = $row->posiusuarioarticulo;
								$votonegativo = $row->negusuarioarticulo;
								$idusuario = $row->idusuarioarticulo;
								$estadovar = $row->estadou3;
							}
					?>
						<a href="#" class="actMenuB<?php echo $row->id;?>"><?php if($estadovar=='Baneado'){ echo '<strike>'.$usuariocadena.'</strike>';}else{ echo $usuariocadena;}?></a> <span class="green">+<?php echo $votopositivo;?></span> 
						<?php if($votonegativo > 0){?>						
						<span class="red">-<?php echo $votonegativo;?></span>
						<?php }?>
						
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="store/<?php echo $usuariocadena;?>" target="_blank">Go to</a></li>
									<li><a href="javascript:historial('<?php echo $usuariocadena."','usuario";?>')">History</a></li>
									<li><a href="administrador/mostrarmodalmensaje?id=<?php echo $idusuario.'&seu='.$usuariocadena.'&posi='.$votopositivo.'&neg='.$votonegativo;?>" class="">Send PM</a></li>
									<li><a href="administrador/mostrarmodalvotos?id=<?php echo $idusuario.'&seu='.$usuariocadena.'&posi='.$votopositivo.'&neg='.$votonegativo;?>" class="nmodal">Vote</a></li>
									<li><a href="javascript:banearusuario('<?php echo $idusuario;?>','Baneado','usuario');" onclick="banearusuario();" class="">Ban</a></li>
									<li><a href="javascript:banearusuario('<?php echo $idusuario;?>','Activo','usuario');" class="">Unban</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>
					</td>
					<td>
					<?php 
					$pagina = "store/$row->seuusuario";
					$idpagina = "$row->idusuario (Usuario)";
						if($row->paquete != '')
						{
							$pagina = 'paquete/';
							$idpagina = "$row->articulopaq";
						}
						
						if($row->idtitulo != '')
						{
							$pagina = "product/$row->idtitulo";
							$idpagina = "$row->idtitulo (Articulo)";
						}
					?>
						<a href="#" class="actMenuB<?php echo $row->id;?>"><?php echo $idpagina;?></a>
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="<?php echo $pagina;?>" target="_blank">Go to</a></li>
									<li><a href="modal-enable-page.html" class="nmodal">Enable</a></li>
									<li><a href="modal-disable-page.html" class="nmodal">Disable</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>	
					</td>
					<td>
						<a href="#" class="actMenuB<?php echo $row->id;?>">Mark as</a>
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="javascript:cambiarestado('<?php echo $row->id."','Finalizado";?>')"">Done</a></li>
									<li><a href="javascript:cambiarestado('<?php echo $row->id."','Pendiente";?>')"">Pending</a></li>
									<li><a href="javascript:cambiarestado('<?php echo $row->id."','Procesando";?>')"">Unmark</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>
					</td>
					<td><a href="#"><?php echo $row->markby. " ".$row->datemark;?></a></td>
		<?php 
		}
		
	}

	public function listarmas()
	{
		//cantidadtotal: cantidadtotal , desde: desde ,muestranow:muestranow}
		$cantidadtotal = $this->input->post('cantidadtotal');
		$desde = $this->input->post('desde') + $this->configuracion->variables ( "cantidadPaginacion" );
		$desde2 = $this->input->post('desde');
		$muestranow = $this->input->post('muestrashow');
		$vista = $this->input->post('vista');
		$vista2 = $this->input->post('vista');
		
		if($vista == 'seeall'){$vista = false;}
		if($vista == 'pending'){$vista = 'Pendiente';}
		if($vista == 'unmarked'){$vista = 'Procesando';}
		
		$reporte = $this->administracion->datos($vista, false, false, $desde, $this->configuracion->variables ( "cantidadPaginacion" ));
		if(($reporte))
				{
		foreach($reporte as $row)
				{
				?>
				<tr id=fila<?php echo $row->id;?> <?php if($row->estado == "Finalizado"){echo 'class="bg-green"';}else{if($row->estado == "Pendiente"){echo 'class="bg-yellow"';}}?>>
					<td><?php echo $row->id;?></td>
					<td>es</td>
					<td><?php $fecha = new DateTime($row->fecha);
					echo $fecha->format('d-m-Y'); ?></td>
					<td> <?php if($row->descripcion != ''){?>
						<a href="#" class="actMenuB<?php echo $vista2.$desde2;?>"><?php echo $row->asunto;?></a>
						<div class="act-menu-b w454">
							<div class="cont">
								<p><?php echo $row->descripcion;?></p>
							</div>
							<div class="arrow"></div>
						</div>
						<?php }else{echo $row->asunto;}?>
					</td>
					<td>
						<a href="#" class="actMenuB<?php echo $vista2.$desde2;?>"><?php if($row->estadou=='Baneado'){ echo '<strike>'.$row->denuncianteseudonimo.'</strike>';}else{ echo $row->denuncianteseudonimo;}?></a> <span class="green">+<?php echo $row->denunciantevotopos;?></span> 
						<?php if($row->denunciantevotoneg > 0){?>
						<span class="red">-<?php echo $row->denunciantevotoneg;?></span>
						<?php }?>
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="store/<?php echo $row->denuncianteseudonimo;?>" target="_blank">Go to</a></li>
									<li><a href="javascript:historial('<?php echo $row->denuncianteseudonimo."','usuario";?>')">History</a></li>
									<li><a href="modal-enviar-mensaje-privado.html" class="nmodal">Send PM</a></li>
									<li>
										<a href="administrador/mostrarmodalvotos?id=
										<?php echo $row->denunciante.'&seu='.$row->denuncianteseudonimo.'&posi='.$row->denunciantevotopos.'&neg='.$row->denunciantevotoneg;?>" 
										class="nmodal">Vote</a>
									</li>
									<li><a href="modal-ban-user.html" class="nmodal">Ban</a></li>
									<li><a href="modal-unban-user.html" class="nmodal">Unban</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>
					</td>
					<td>
					<?php 
						$usuariocadena = $row->seuusuario;
						$votopositivo = $row->posiusuario;
						$votonegativo = $row->negusuario;
						$idusuario = $row->idusuario;
						$estadovar = $row->estadou2;
						if($row->seuusuarioarticulo != '')
							{
								$usuariocadena = $row->seuusuarioarticulo;
								$votopositivo = $row->posiusuarioarticulo;
								$votonegativo = $row->negusuarioarticulo;
								$idusuario = $row->idusuarioarticulo;
								$estadovar = $row->estadou3;
							}
					?>
						<a href="#" class="actMenuB<?php echo $vista2.$desde2;?>"><?php if($estadovar=='Baneado'){ echo '<strike>'.$usuariocadena.'</strike>';}else{ echo $usuariocadena;}?></a> <span class="green">+<?php echo $votopositivo;?></span> 
						<?php if($votonegativo > 0){?>						
						<span class="red">-<?php echo $votonegativo;?></span>
						<?php }?>
						
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="store/<?php echo $usuariocadena;?>" target="_blank">Go to</a></li>
									<li><a href="javascript:historial('<?php echo $usuariocadena."','usuario";?>')">History</a></li>
									<li><a href="modal-enviar-mensaje-privado.html" class="nmodal">Send PM</a></li>
									<li><a href="administrador/mostrarmodalvotos?id=<?php echo $idusuario.'&seu='.$usuariocadena.'&posi='.$votopositivo.'&neg='.$votonegativo;?>" class="nmodal">Vote</a></li>
									<li><a href="modal-ban-user.html" class="nmodal">Ban</a></li>
									<li><a href="modal-unban-user.html" class="nmodal">Unban</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>
					</td>
					<td>
					<?php 
					$pagina = "store/$row->seuusuario";
					$idpagina = "$row->idusuario (Usuario)";
						if($row->paquete != '')
						{
							$pagina = 'paquete/';
							$idpagina = "$row->articulopaq";
						}
						
						if($row->idtitulo != '')
						{
							$pagina = "product/$row->idtitulo";
							$idpagina = "$row->idtitulo (Articulo)";
						}
					?>
						<a href="#" class="actMenuB<?php echo $vista2.$desde2;?>"><?php echo $idpagina;?></a>
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="<?php echo $pagina;?>" target="_blank">Go to</a></li>
									<li><a href="modal-enable-page.html" class="nmodal">Enable</a></li>
									<li><a href="modal-disable-page.html" class="nmodal">Disable</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>	
					</td>
					<td>
						<a href="#" class="actMenuB<?php echo $vista2.$desde2;?>">Mark as</a>
						<div class="act-menu-b">
							<div class="cont">
								<ul>
									<li><a href="javascript:cambiarestado('<?php echo $row->id."','Finalizado";?>')"">Done</a></li>
									<li><a href="javascript:cambiarestado('<?php echo $row->id."','Pendiente";?>')"">Pending</a></li>
									<li><a href="javascript:cambiarestado('<?php echo $row->id."','Procesando";?>')"">Unmark</a></li>
								</ul>
							</div>
							<div class="arrow"></div>
						</div>
					</td>
					<td></td>
				</tr>
				
				<?php
				}}
	}
	
	public function cambiarvermas()
	{
		$cantidadtotal = $this->input->post('cantidadtotal');
		$desde = $this->input->post('desde') + $this->configuracion->variables ( "cantidadPaginacion" );
		$muestranow = $this->input->post('muestranow');
		$vista = $this->input->post('vista');
		if($cantidadtotal >= $desde){
		?>
			<a title="Ver más" href="javascript:vermaslista('<?php echo $cantidadtotal."','".$desde."','".$muestranow."','".$vista;?>')">Ver más</a>
		<?php } 		
	}

	public function guardarmensaje()
	{
		$id = $this->input->post('id');
		$mensaje = $this->input->post('mensaje');

		$this->administracion->guardarnotificacion($id,$mensaje);
		echo "1";
	}
	
	public function buscarnotificacion()
	{
		$data['vista'] = 'seeall';
		
		$valor = $this->input->get('valor');
		$tipo = $this->input->get('tipo');
		
		$data['idadmin'] = $this->myuser->id;
		$data['mensaje'] = $this->administracion->mensajeadmin($valor);
		
		$view = "administrador/admipm";
		$this->loadGUI ( $view, $data );
	}

	//mensaje PM
	public function mostrarmodalmensaje()
	{
		$id = $this->input->get('id');
		$data['id'] = $id;
		
		if(($this->administracion->vermensajeXusuario($id))== 0)
		{
			$data['seudonimo'] = $this->input->get('seu');
		
			$data['posi'] = $this->input->get('posi');
			$data['neg'] = $this->input->get('neg');
			$view = "administrador/modal/enviar-mensaje-privado";
		
			$this->load->view ( $view, $data);
		}
		else
		{
			$seudonimo = $this->input->get('seu');
			$view = "messageprofile/$seudonimo/ADMINLOVENDE/inboxadmin";
			redirect( $view);
		}
	}
	
	public function enviarmensajepm()
	{
		$id = $this->input->post('id');
		
		$texto = $this->input->post('textoenviar');		
		
		$this->administracion->guardarmensajepm($id,$texto);
		
		echo '1';
	}
	
	//factura
	public function buscarfactura()
	{
				
		$data['vista'] = 'seeall';
		$data['cantidadtotal'] = $this->administracion->contarfactura();
		$data['curso'] = $this->administracion->contarfactura('En curso');
		$data['pendiente'] = $this->administracion->contarfactura('Pendiente');
		$data['pagado'] = $this->administracion->contarfactura('Pagado');
				
		$valor = $this->input->get('valor');
		
		
		$data['reporte'] = $this->administracion->devolverfactura(false,$valor, $tipo );
		$view = "administrador/billing";
		$this->loadGUI ( $view, $data );
	}

	public function buscarfacturaXfecha()
	{
		$data['vista'] = 'seeall';
		$data['cantidadtotal'] = $this->administracion->contarfactura();
		$data['curso'] = $this->administracion->contarfactura('En curso');
		$data['pendiente'] = $this->administracion->contarfactura('Pendiente');
		$data['pagado'] = $this->administracion->contarfactura('Pagado');
		$fechaclasificado = $this->input->get('valor');
				
		$data['reporte'] = $this->administracion->devolverfactura($estado = false, $valor = false, $tipo=false, $fechaclasificado);
		$data['grupofechas'] = $this->administracion->devuelvegrupofechas();
		$data['valordefecto'] = $fechaclasificado;
		
		$view = "administrador/billing";
		$this->loadGUI ( $view, $data );
	}
	
	public function baneo()
	{
		$idobjeto = $this->input->post('usuario');
		$tipo = $this->input->post('estado');
		$tabla = $this->input->post('tabla');
		$idreporte = $this->input->post('idreporte');
		
		$this->administracion->banearcampos($idobjeto, $tipo, $tabla);
		
		$darreporte = $this->administracion->darXreporte($idreporte);
		
		$idusuario = '';
		$usuarioemail = '';
		$motivo = '';
		$anuncio = '';		
		$pass = '';
		$seudonimo = '';
		foreach ($darreporte as $row)
		{
			if($row->paquete != '')
			{
				$idusuario = $row->idusuario;
				$usuarioemail = $row->seuusuario;
				$motivo = $row->asunto;
				$anuncio = $row->paquete;
				$idarticulo = $row->articulopaq;
				$pass = $row->u2password;
				$seudonimo = $row->seu2;
			}
			else
			{
			
				if($row->idtitulo != '')
				{
					$idusuario = $row->idusuarioarticulo;
					$usuarioemail = $row->seuusuarioarticulo;
					$motivo = $row->asunto;
					$anuncio = $row->idtitulo;
					$idarticulo = $row->idarticulo;
					$pass = $row->u3password;
					$seudonimo = $row->seu3;
				}
				else
				{
					if($row->mensaje != '')
					{
						$idusuario = $row->idusuarioarticulo;
						$usuarioemail = $row->seuusuario;
						$motivo = $row->asunto;
						$anuncio = false;
						$idarticulo = $row->mensaje;
						$pass = $row->u2password;
						$seudonimo = $row->seu2;
					}
					else
					{					
											
							$idusuario = $row->idusuario;
							$usuarioemail = $row->seuusuario;
							$motivo = $row->asunto;
							$anuncio = false;
							$idarticulo = false;
							$pass = $row->u2password;
							$seudonimo = $row->seu2;
						
					}
				}
			}
		} 
		
		if($tipo == 'Baneado')
		{
				$this->load->library ( "myemail" );					
				$this->myemail->enviarTemplate ( $usuarioemail, "Cuenta de usuario suspendida", "administrador/mail/usuario-suspendido", array (
	         										"motivo" => $motivo,
													"anuncio" => $anuncio,
													"idarticulo" => $idarticulo
	      										) );		
	      		
		}
		else
		{
			
				$this->load->library ( "myemail" );					
				$this->myemail->enviarTemplate ( $usuarioemail, "Cuenta de usuario reactivada", "administrador/mail/usuario-reactivada", array (
	         										"seudonimo" => $seudonimo,
													"contrasena" => $pass
	      										) );	
		}
		
		echo "1";
	}
	
//mensaje PM
	public function mostrarmodalbaneo()
	{
		$data['idobjeto'] = $this->input->get('usuario');
		$data['seudonimo'] = $this->input->get('seudonimo');
		$data['posi'] = $this->input->get('posi');
		$data['neg'] = $this->input->get('neg');
		$data['tipo'] = $this->input->get('tipo');
		$data['tabla'] = 'usuario';
		$data['idreporte'] = $this->input->get('idreporte');

			if($this->input->get('tipo') == 'Baneado')
			{
				$view = "administrador/modal/modalban";	
			}
			else
			{
				$view = "administrador/modal/modalunban";
			}
			
		
			$this->load->view ( $view, $data);
		
	}

	public function obtenerfacturaBD($fecha)
	{
		$this->db->select ( "fac.id, fac.codigo, fac.mes,usu.id as idusu, usu.seudonimo, fac.fecha, fac.monto_total, fac.monto_tarifa, fac.iva,
		 fac.estado, fac.tipo_tarifa, fac.paypal_id, usu.positivo, usu.negativo " );
		$this->db->join('usuario as usu','usu.id = fac.usuario','left');
		$this->db->where ( "fac.mes", $fecha);
		$this->db->order_by ( "fac.id asc" );
		$retorno = array ();
		
		$r = $this->db->get ( "factura as fac" )->result ();
		if ($r && is_array ( $r ) && count ( $r ) > 0) {
			foreach ( $r as $c ) {
				$retorno [$c->id] = array (
						"id" => $c->id,
						"codigo" => $c->codigo,
						"mes" => $c->mes,
						"usuario" => $c->seudonimo,
						"fecha" => $c->fecha,
						"monto_total" => $c->monto_total,
						"monto_tarifa" => $c->monto_tarifa,
						"iva" => $c->iva,
						"estado" => $c->estado,
						"tipo_tarifa" => $c->tipo_tarifa
				);
			}
		}
		return $retorno;
	}
	
	public function exportarexcel()
	{
		$fecha = $this->input->get('fecha');
		$c = $this->obtenerfacturaBD ( $fecha );
		$this->load->library ( "PHPExcel" );
		$objPHPExcel = $this->phpexcel;
		
		$h = $objPHPExcel->setActiveSheetIndex ( 0 );
		$objPHPExcel->getActiveSheet ()->setTitle ( 'Categorias' );
		
				$h->setCellValue ( "A1", "id");
				$h->setCellValue ( "B1", "usuario");
				$h->setCellValue ( "C1", "codigo");
				$h->setCellValue ( "D1", "mes");
				$h->setCellValue ( "E1", "fecha");
				$h->setCellValue ( "F1", "monto_total");
				$h->setCellValue ( "G1", "monto_tarifa");
				$h->setCellValue ( "H1", "iva");
				$h->setCellValue ( "I1", "estado");
				$h->setCellValue ( "J1", "tipo_tarifa");
				
				
		
		$v = 2;
		$cc = "B";
		$this->imprimirfacturas ( $c, $h, $v, $cc );
		
		$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel2007' );
		$base = "files/" . rand () . ".xlsx";
		$dir = BASEPATH . "../$base";
		$objWriter->save ( $dir );
		redirect ( $base, "refresh" );
	}
	
	public function imprimirfacturas($ca, $h, &$v, $cc)
	{
		if(count($ca)>0)
		{
		foreach ( $ca as $id => $c ) {
			$v ++;
			if (count ( $c ["id"] ) > 0) {
				$h->setCellValue ( "A" . $v, $c ["id"]);
				$h->setCellValue ( "B" . $v, $c ["usuario"]);
				$h->setCellValue ( "C" . $v, $c ["codigo"]);
				$h->setCellValue ( "D" . $v, $c ["mes"]);
				$h->setCellValue ( "E" . $v, $c ["fecha"]);
				$h->setCellValue ( "F" . $v, $c ["monto_total"]);
				$h->setCellValue ( "G" . $v, $c ["monto_tarifa"]);
				$h->setCellValue ( "H" . $v, $c ["iva"]);
				$h->setCellValue ( "I" . $v, $c ["estado"]);
				$h->setCellValue ( "J" . $v, $c ["tipo_tarifa"]);
				
				$this->imprimirfacturas ( $c ["id"], $h, $v, chr ( ord ( $cc ) + 1 ) );
			} else {
				$h->setCellValue ( $cc . $v, $c ["usuario"] );
				$h->setCellValue ( chr ( ord ( $cc ) + 1 ) . "$v", $id );
			}
		}
		}
	}
	
	//guardar reporte en base de datos
	public function guardarbdreporte()
	{
		
		$reportador = $this->input->post('reportador');
	
		$reportado = $this->input->post('reportado');
		$idmensaje = $this->input->post('idmensaje');
		
		$descripcion = $this->input->post('descripcion');
		$motivo = $this->input->post('motivo');
	
		$this->administracion->guardarreporte($reportador,$reportado,$idmensaje,$descripcion,$motivo);
	
		echo '1';
	
	}
	
	//pageid
	public function pageidenabledisable()
	{		
		//articulo
		if($this->input->get('tipo')=='articulo')
		{
			$data['idarticulo'] = $this->input->get('id');
			$data['estadoanterior'] = $this->input->get('estadoanterior');
			$data['accion'] = $this->input->get('accion');
			
			$this->load->model('articulo_model');
				
				$data['articulo'] = $this->articulo_model->darArticulo($this->input->get('id'));
				
					if($this->input->get('accion') == 'enable')
					{
						$view = "administrador/modal/modalenablepage";	
					}
					else
					{
						$view = "administrador/modal/modaldisablepage";
					}
					
				
					$this->load->view ( $view, $data);
		}
		//mensaje
		if($this->input->get('tipo')=='mensaje')
		{
			$data['id'] = $this->input->get('id');
			$data['accion'] = $this->input->get('accion');
			
			if($this->input->get('accion') == 'enable')
			{
				$view = "administrador/modal/modalenablepagemessage";	
			}
			else
			{
				$view = "administrador/modal/modaldisablepagemessage";
			}
			$this->load->view ( $view, $data);
		}
		
	}
	
	public function pageidarticulo()
	{
		$idobjeto = $this->input->post('idarticulo');
		$estado = $this->input->post('estado');
		$accion = $this->input->post('accion');
		
		if($this->input->post('accion') == 'disable')
		{
			$this->administracion->baneararticulo($idobjeto, 'Baneado', $estado);
			
			$datosarticulos = $this->administracion->darXarticulo($idobjeto);
						
			foreach ($datosarticulos as $row)
			{
				$this->load->library ( "myemail" );					
				$this->myemail->enviarTemplate ( $row->email, "Anuncio eliminado", "administrador/mail/anuncio-eliminado", array (
	         										"idarticulo" => $idobjeto,
													"articulo" => $row->titulo
	      										) );		
	      		$this->load->library ( "myemail" );   
			}				   			
      			
		}else{
			$this->administracion->baneararticulo($idobjeto, $estado);
			
			$datosarticulos = $this->administracion->darXarticulo($idobjeto);
						
			foreach ($datosarticulos as $row)
			{
				$this->load->library ( "myemail" );					
				$this->myemail->enviarTemplate ( $row->email, "Anuncio reactivado", "administrador/mail/anuncio-reactivado", array (
	         										"idarticulo" => $idobjeto,
													"articulo" => $row->titulo
	      										) );		
	      		$this->load->library ( "myemail" );   
			}				   	
		}
		
		echo "1";
	}
	
	public function pageidmensaje()
	{
		$idmensaje = $this->input->post('idmensaje');		
		$accion = $this->input->post('accion');
		
		if($this->input->post('accion') == 'disable')
		{
			$this->administracion->banearmensaje($idmensaje, 'Baneado');
		}else{
			$this->administracion->banearmensaje($idmensaje, 'Denunciado');
		}
		
		echo "1";
	}
	
	//envio de emails pruebas
	
	public function emailprueba()
	{
	
	}
	
}

?>
