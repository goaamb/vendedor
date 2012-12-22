<?php
define ( "NINGUNO", 0 );
define ( "OTRO", 1 );
define ( "MISMO", 2 );
$baneado = ($articulo->usuario->estado == "Baneado" || $articulo->estado == "Baneado");
$visible = false;
if ($transaccion && $transaccion->comprador) {
	$visible = $transaccion->comprador->id == $this->myuser->id;
} elseif ($articulo->comprador) {
	$visible = $articulo->comprador == $this->myuser->id;
}
if ($this->myuser && $this->myuser->estado == "Baneado") {
	$articulo->terminado = 1;
}
$visible = ($visible && $articulo->estado !== "Baneado");
if ($baneado) {
	$articulo->terminado = 1;
}
if (! $baneado || $visible) {
	
	$seudonimo = ucfirst ( $articulo->usuario->seudonimo );
	$tipo_usuario = NINGUNO; // 0 ninguno, 1 usuario no dueño del articulo, 2
	// usuario
	// dueño
	// del articulo
	$cantidadOfertas = 3;
	if ($usuario) {
		if ($usuario->id !== $articulo->usuario->id) {
			$tipo_usuario = OTRO;
		} else {
			$tipo_usuario = MISMO;
		}
		$cantidadOfertas = $this->configuracion->variables ( "maximoCantidad" ) - $this->articulo->cantidadOfertas ( $articulo->id, $usuario->id )->cantidad;
	}
	$siguiendo = isset ( $siguiendo ) ? $siguiendo : false;
	$thisLink = "product/$articulo->id-" . normalizarTexto ( $articulo->titulo );
	$b64tl = str_replace ( "=", "", base64_encode ( $thisLink ) );
	$redirectLogin = "login/$b64tl";
	$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
	$imagenes = explode ( ",", $articulo->foto );
	$ruta = "files/" . $articulo->usuario->id . "/";
	$file = BASEPATH . "../$ruta";
	$paisobj = ($this->myuser ? $this->myuser->pais : $this->pais);
	$pais = ($this->myuser && $this->myuser->pais && isset ( $this->myuser->pais->codigo2 ) ? $this->myuser->pais->codigo2 : $this->pais->codigo2);
	$continente = ($this->myuser && $this->myuser->pais && isset ( $this->myuser->pais->continente ) ? $this->myuser->pais->continente : $this->pais->continente);
	$gasto_envio = ($articulo->usuario->pais->codigo2 == $pais && $articulo->gastos_pais !== null ? $articulo->gastos_pais : false);
	$gasto_envio = ($gasto_envio !== false ? $gasto_envio : ($articulo->usuario->pais->continente == $continente && $articulo->gastos_continente !== null ? $articulo->gastos_continente : false));
	$gasto_envio = ($gasto_envio !== false ? $gasto_envio : ($articulo->gastos_todos !== null ? $articulo->gastos_todos : false));
	$gasto_envio = ($gasto_envio !== false ? $gasto_envio : ($articulo->envio_local ? 0 : false));
	$sincompra = false;
	if ($gasto_envio === false) {
		$sincompra = true;
	}
	?><link href="assets/css/articulo.css" type="text/css" rel="stylesheet" />
<script src="assets/js/articulo/vista-articulo.js"
	type="text/javascript"></script>
<div class="wrapper clearfix">
<?php $this->load->view("usuario/cabecera-perfil",array("seccion"=>"articulo"))?>
	<header class="cont-cab">
		<h1><?=$articulo->titulo?></h1>
		<p>
			Vendedor <a
				href="store/<?=strtolower($articulo->usuario->seudonimo);?>"
				title="ver perfil de <?=$seudonimo?>"><strong><?=$seudonimo?></strong></a>
			<a href="home/modal/votos/votos/<?=$articulo->usuario->id?>"
				class="nmodal"><span class="green">+<?=$articulo->usuario->positivo?></span>
			<?php if($articulo->usuario->negativo){?><span class="red">+<?=$articulo->usuario->negativo?></span><?php }?></a>
				<?php
	if ($tipo_usuario == OTRO) {
		?>| <a
				href="articulo/modal/enviar-mensaje-privado/mensaje/<?=$articulo->id?>"
				class="nmodal" title="Enviar mensaje privado">enviar mensaje privado</a>
			| <a href="store/<?=strtolower($articulo->usuario->seudonimo);?>"
				title="Tienda de <?=$seudonimo?>">ver su tienda</a> <?php
	}
	?>| <a href="home/modal/denunciar/articulo/<?=$articulo->id?>"
				title="denunciar" class="nmodal">denunciar</a>
		</p>
	</header>
	<div class="product-file clearfix">
		<div class="gallery">
			<div id="productGallery">
				<ul>
				<?php
	
	foreach ( $imagenes as $ima ) {
		if (is_file ( $file . $ima )) {
			?><li><div style=" background:transparent url(<?=$ruta.$ima?>) center center no-repeat scroll;width:640px; height: 480px;"></div></li><?php
		}
	}
	?>
				</ul>
			</div>
		</div>

		<div class="data"><?php
	$monto = $articulo->precio;
	if ($articulo->tipo == "Fijo") {
		$og = $this->articulo->darOfertaGanadora ( $articulo->id );
		if ($og) {
			$monto = $og->monto;
		}
	} else {
		$c = $this->articulo->mayorOferta ( $articulo->id, true );
		if ($c) {
			$monto = $monto = $c->monto_automatico;
			if ($usuario && $c->usuario === $usuario->id) {
				$monto_min = $c->monto + 0.5;
			} else {
				$monto_min = $monto + 0.5;
			}
		}
	}
	?>
			<h2>
				<span id="montoFinal"><?=formato_moneda($monto);?></span> $us
			</h2>
			<ul>
				<li><strong>AÑO:</strong></li>
				<li><strong>MARCA:</strong></li>
				<li><strong>MODELO:</strong></li>
				<li><strong>TIPO:</strong></li>
				<li><strong>KILOMETRAJE:</strong></li>
				<li><strong>CILINDRADA:</strong></li>
				<li><strong>COMBUSTIBLE:</strong></li>
				<li><strong>TELEFONO:</strong></li>
				<li><strong>CELULAR:</strong></li>
				<li><strong>CIUDAD:</strong></li>
			</ul>
			<div class="shareThis">
				<script type="text/javascript"
					src="http://w.sharethis.com/button/buttons.js"></script>
				<script type="text/javascript">stLight.options({publisher: "4e272322-c8ad-44a7-9ad4-c9b430126cd0"}); </script>
				<span class='st_facebook'></span> <span class='st_twitter'></span>
			</div>
			<?php if(isset($difTiempo)){?>
			<script type="text/javascript">contadorInverso('<?=$difTiempo?>','tiempoSubasta');</script>
			<?php }?>
		</div>
		<div class="post cl">
			<iframe name="iframeDescripcion" id="iframeDescripcion"
				src="articulo/mostrarDescripcion/<?=$articulo->id?>" frameborder="0"
				style="border: none; width: 100%; min-height: 30px; overflow: hidden;"
				scrolling="no" onload="calcularContenidoTamaño.call(this);"></iframe>
			<?php
	if (isset ( $aclaraciones ) && $aclaraciones && is_array ( $aclaraciones ) && count ( $aclaraciones ) > 0) {
		foreach ( $aclaraciones as $ta ) {
			?><p>
				<strong><?=traducir("Nota añadida por el vendedor hace")." ".calculaTiempoDiferencia($ta->fecha,false,true);?></strong><br /><?php
			print parse_text_html ( $ta->texto );
			?></p><?php
		}
	}
	?>
		</div>

	</div>
</div><?php
} else {
	$this->load->view ( "articulo/no-existe" );
}
?>