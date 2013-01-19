<?php
$padre = isset ( $padre ) ? $padre [0] ["id"] : "";
define ( "NINGUNO", 0 );
define ( "OTRO", 1 );
define ( "MISMO", 2 );
$baneado = ($articulo->usuario && ($articulo->usuario->estado == "Baneado" || $articulo->estado == "Baneado"));
$visible = false;

if ($this->myuser && $this->myuser->estado == "Baneado") {
	$articulo->terminado = 1;
}
$propietario = isset ( $this->myuser )&& is_object ( $this->myuser ) &&  ($this->myuser->tipo == "Administrador" ||( isset ( $articulo->usuario ) && is_object ( $articulo->usuario ) && $this->myuser->id == $articulo->usuario->id));
$visible = ($visible && $articulo->estado !== "Baneado");
if ($baneado) {
	$articulo->terminado = 1;
}
if (! $baneado || $visible) {
	$siguiendo = isset ( $siguiendo ) ? $siguiendo : false;
	$thisLink = "product/$articulo->id-" . normalizarTexto ( $articulo->titulo );
	$b64tl = str_replace ( "=", "", base64_encode ( $thisLink ) );
	$redirectLogin = "login/$b64tl";
	$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
	$imagenes = explode ( ",", $articulo->foto );
	$ruta = "files/articulos/";
	$file = BASEPATH . "../$ruta";
	?><link href="assets/css/articulo.css" type="text/css" rel="stylesheet" />
<script src="assets/js/articulo/vista-articulo.js"
	type="text/javascript"></script>
<div class="wrapper clearfix">
	<header class="cont-cab">
		<h1><?php
	print $articulo->titulo;
	if ($propietario) {
		?><a style="float: right;" href="product/edit/<?=$articulo->id?>">Editar</a><?php
	}
	?></h1>
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
	?>
			<h2>
				<span id="montoFinal"><?=$monto!=0?formato_moneda($monto):"Preguntar";?></span>
				$us
			</h2>
			<ul>
			<?php
	switch ($padre) {
		case "1" :
			?><li><strong>MARCA:</strong> <?=$articulo->vehiculo->marca?></li>
				<li><strong>MODELO:</strong> <?=$articulo->vehiculo->modelo?></li>
				<li><strong>TIPO:</strong>  <?=$articulo->vehiculo->tipo?></li>
				<li><strong>KILOMETRAJE:</strong>  <?=$articulo->vehiculo->kilometraje?></li>
				<li><strong>CILINDRADA:</strong>  <?=$articulo->vehiculo->cilindrada?></li>
				<li><strong>COMBUSTIBLE:</strong>  <?=$articulo->vehiculo->combustible?></li>
				<li><strong>CAJA:</strong>  <?=$articulo->vehiculo->caja?></li>
				<li><strong>CONTACTAR CON:</strong> <?=$articulo->contactar_con?></li><?php
		break;
		case "2" :
			?><li><strong>RAZA:</strong> <?=$articulo->mascota->raza?></li>
				<li><strong>PEDIGRI:</strong> <?=$articulo->mascota->pedigri?></li>
				<li><strong>SEXO:</strong> <?=$articulo->mascota->sexo?></li>
				<li><strong>OBSERVACION:</strong> <?=$articulo->mascota->observacion?></li>
				<li><strong>CONTACTAR CON:</strong> <?=$articulo->contactar_con?></li><?php
		break;
		case "3" :
			?><li><strong>Tipo Venta:</strong> <?=$articulo->vivienda->tipo_venta?></li>
				<li><strong>Dirección:</strong> <?=$articulo->vivienda->direccion?></li>
				<li><strong>Superficie:</strong> <?=$articulo->vivienda->superficie?></li>
				<li><strong>Dormitorios:</strong> <?=$articulo->vivienda->dormitorios?></li>
				<li><strong>Baños:</strong> <?=$articulo->vivienda->banos?></li>
				<li><strong>Antigüedad:</strong> <?=$articulo->vivienda->antiguedad?></li><?php
		break;
	}
	?>
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