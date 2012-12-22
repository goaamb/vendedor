<?php
$criterio = (isset ( $criterio ) && trim ( $criterio ) !== "" ? $criterio : false);
$inicio = (isset ( $inicio ) ? intval ( $inicio ) + 1 : 0);
$totalpagina = (isset ( $totalpagina ) ? intval ( $totalpagina ) : 0);
$total = (isset ( $total ) ? intval ( $total ) : 0);
$orden = (isset ( $orden ) ? $orden : "");
$ubicacion = (isset ( $ubicacion ) ? $ubicacion : "");
$categoria = (isset ( $categoria ) ? $categoria : "");
$articulos = (isset ( $articulos ) ? $articulos : null);
$vencimientoOferta = intval ( $this->configuracion->variables ( "vencimientoOferta" ) ) * 86400;
$profile = (isset ( $profile ) ? $profile : null);
?><section class="result-list">
	<header class="cont-cab">
		<h1><?=(isset($criterio) && trim($criterio)!==""?traducir("Búsqueda:")." ".$criterio:($profile?traducir("Tienda de")." ".$usuario->seudonimo:traducir("Últimos artículos")))?></h1>
		<p>
			<?=$total?> artículos, mostrando del <strong><?=count($articulos)>0?$inicio:0;?></strong>
			al <strong id="contadorFinal"><?=($inicio+count($articulos)-1)?></strong>
		</p>
	</header><?php
	if (isset ( $articulos ) && is_array ( $articulos ) && count ( $articulos ) > 0) {
		foreach ( $articulos as $i => $articulo ) {
			if ($articulo->usuario) {
				$imagen = array_shift ( explode ( ",", $articulo->foto ) );
				$imagen = imagenArticulo ( $articulo->usuario, $imagen, "thumb" );
				if ($imagen) {
					list ( , $h ) = getimagesize ( BASEPATH . "../$imagen" );
					$furl = "product/" . $articulo->id . "-" . normalizarTexto ( $articulo->titulo );
					?><div
		class="item clearfix <?php
					if ($i + 1 == count ( $articulos )) {
						print "last-child";
						if ($total <= $totalpagina) {
							print " border";
						}
					}
					
					?>">
		<a href="<?=$furl?>" title="<?=$articulo->titulo?>"><span class="imagen" style="background: white url(<?=$imagen?>) no-repeat top right scroll;width:140px;height:<?=$h?>px;"></span></a>
		<div class="meta">
			<p>
				<strong><?=formato_moneda($articulo->tipo=="Fijo" || $articulo->tipo=="Cantidad"?$articulo->precio:$articulo->mayorPuja)." \$us"?></strong>
			</p>
			<p><?php
					if ($articulo->tipo == "Fijo" || $articulo->tipo == "Cantidad") {
					
					} else if (intval ( $articulo->cantidadPujas ) > 0) {
						?><span class="italic"><?php
						print (isset ( $articulo->cantidadPujas )) ? intval ( $articulo->cantidadPujas ) . " " . traducir ( "pujas" ) : "";
						?></span><?php
					}
					?></p>
			<p class="grey"><?
					if ($articulo->tipo == "Fijo" || $articulo->tipo == "Cantidad") {
						print calculaTiempoDiferencia ( date ( "Y-m-d H:i:s" ), strtotime ( $articulo->fecha_registro ) + $vencimientoOferta, true );
					} else {
						print calculaTiempoDiferencia ( date ( "Y-m-d H:i:s" ), strtotime ( $articulo->fecha_registro ) + $articulo->duracion * 86400, true );
					}
					?></p>

		</div>
		<ul>
			<li><h2>
					<a href="<?=$furl?>" title="<?=$articulo->titulo?>"><?=$articulo->titulo?></a>
				</h2></li>
			<li class="grey"><?=traducir("Ubicación").": ".(isset($articulo->pais_nombre)?$articulo->pais_nombre:"")?></li>
		</ul>
	</div><?php
				}
			}
		}
		if ($total > $totalpagina) {
			?><p class="ver-mas">
		<img src="assets/images/ico/ajax-loader-see-more.gif"
			alt="<?=traducir("Ver más")?>" style="display: none;" /> <a href="#"
			title="<?=traducir("Ver más productos")?>"
			onclick="return verMasArticulos('home','<?=$inicio?>','<?=$section?>');"><?=traducir("Ver más")?></a><a
			href="#" title="<?=traducir("Ir al primer artículo")?>"
			onclick="document.body.scrollTop=0; return false;"
			style="display: none;"><?=traducir("Ir al primer artículo")?></a>
	</p><?php
		}
	} else {
		?><div class="item clearfix last-child"
		style="text-align: center; padding: 20px 0px;"><?php
		if (! isset ( $usuarioPropio ) || (isset ( $usuarioPropio ) && ! $usuarioPropio)) {
			if ($profile) {
				print traducir ( "Para poner
		artículos a la venta utiliza el link vender en el menú superior." );
			} else {
				print traducir ( "La búsqueda ha devuelto 0 artículos." );
			}
		} else {
			print traducir ( "Sin artículos en venta." );
		}
		?></div>
	<p class="ver-mas"></p><?php
	}
	?>		</section>