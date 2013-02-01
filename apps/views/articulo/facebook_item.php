<?php
if (isset ( $articulo )) {
	$thisLink = "product/$articulo->id-" . normalizarTexto ( $articulo->titulo );
	$imagenes = explode ( ",", $articulo->foto );
	$ruta = "files/articulos/";
	$file = BASEPATH . "../$ruta";
	?>
<div class="wrapper clearfix">
	<article id="facebookProduct">
		<a
			href="javascript:history.go(-1)"
			title="Nuevo Anuncio" class="nuevo">Nuevo Anuncio</a> <a
			target="_blank" href="<?=$thisLink?>" title="<?=$articulo->titulo?>"
			class="contenido"> <span><?php
	print $articulo->titulo;
	?></span><?php
	if (count ( $imagenes ) > 0 && is_file ( $file . $imagenes [0] )) {
		?><span style=" background:transparent url(<?=$ruta.$imagenes [0]?>) center center no-repeat scroll;width:640px; height: 480px; display: block;margin: auto;"></span><?php
	}
	?></a>
	</article>
</div><?php
} else {
	$this->load->view ( "articulo/no-existe" );
}
?>