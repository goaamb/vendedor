<?php
$seccionPerfil = isset ( $seccionPerfil ) ? $seccionPerfil : "";
$seccion = (isset ( $seccion ) ? $seccion : "");
$totalVentas = isset ( $totalVentas ) ? $totalVentas : 0;
$totalCompras = isset ( $totalCompras ) ? $totalCompras : 0;
$totalSeguimientos = isset ( $totalSeguimientos ) ? $totalSeguimientos : 0;
$totalMensajes = isset ( $totalMensajes ) ? $totalMensajes : 0;
$totalCuentas = isset ( $totalCuentas ) ? $totalCuentas : 0;
?><div id="cabeceraPerfil"><?php
if (isset ( $seccion ) && isset ( $usuario ) && $usuario) {
	$imagen = imagenPerfil ( $usuario );
	$ver = false;
	switch ($seccion) {
		case "perfil" :
			$ver = true;
			?><script type="text/javascript">
			usuarioID="<?=$usuario->id;?>";
			</script><?php
		break;
		case "articulo" :
			$ver = true;
		break;
	}
	if ($ver) {
		?><div class="user-data clearfix">
		<div class="image">
			<div style="background:white url(<?=$imagen."?rand=".rand();?>) center center no-repeat scroll;width:150px;height:150px;"></div>
		</div>
		<section>
			<header>
				<h1>
					<span
						<?php
		if ($usuario->estado == "Baneado") {
			print "class='baneado'";
		}
		?>><?=ucfirst($usuario->seudonimo)?></span> <span class="localization"><?=($usuario->pais?$usuario->pais->nombre:"")?></span>
				</h1>
			<?php if($seccion=="perfil" && $this->myuser && $this->myuser->id == $usuario->id && $this->myuser->estado!=="Baneado"){?>
			<p class="actions">
					<a href="edit" title="editar">Editar</a>
				</p>
			<?php }?>
		</header><?php
		$baneado = false;
		switch ($seccion) {
			case "perfil" :
				if ($this->myuser && $this->myuser->id == $usuario->id) {
					if ($this->myuser->estado !== "Baneado") {
						$listaSecciones = array (
								array (
										"url" => "store/" . $usuario->seudonimo,
										"seccion" => "Tienda" 
								) 
						);
					} else {
						$baneado = true;
					}
				} else {
					if ($usuario->estado == "Baneado") {
						$baneado = true;
					} else {
						$class = "nmodal";
						$listaSecciones = array (
								array (
										"url" => "store/" . $usuario->seudonimo,
										"seccion" => "Tienda" 
								) 
						);
					}
				}
				
				if ($baneado) {
					if ($this->myuser && $this->myuser->id == $usuario->id) {
						?><div class="justify red">
				<p>Tu cuenta fue suspendida por uno de los siguientes motivos:</p>
				<ul class="list-dot">
					<li><strong>Retraso en el pago de facturas:</strong> realiza el
						pago de tus facturas con retraso para reactivar tu cuenta y tus
						anuncios</li>
					<li><strong>Violación de los términos de uso:</strong> para
						consultas <a href="mailto:support@vendedor.com">envíanos un e-mail</a>.</li>
				</ul>
			</div><?php
					} else {
						?><div class="justify red">
				<p>La cuenta de este usuario ha sido suspendida</p>
			</div><?php
					}
				} else {
					?>
		<p class="actions"><?php
					$uri = uri_string ();
					if (array_search ( $section, array (
							"auction",
							"item" 
					) ) !== false) {
						$uri = "store/" . $usuario->seudonimo;
					}
					foreach ( $listaSecciones as $i => $secciones ) {
						if ($uri == $secciones ["url"]) {
							print traducir ( $secciones ["seccion"] );
						} else {
							?><a href="<?=$secciones["url"]?>"
					<?=(isset($secciones["class"])?"class='".$secciones["class"]."'":"");?>
					title="<?=traducir($secciones["seccion"])?>"><?=traducir($secciones["seccion"])?></a><?php
						}
						if ($i < count ( $listaSecciones ) - 1) {
							print " | ";
						}
					}
					?></p>
			<div class="justify">
				<p><?=(trim($usuario->descripcion)!==""?parse_text_html( $usuario->descripcion):"Para añadir la foto de perfil o texto aquí utiliza el link editar en la esquina superior derecha")?></p>
			</div>
		<?php
				}
			break;
			
			case "articulo" :
			break;
		}
		?></section>
	</div><?php
	}
}
?></div>