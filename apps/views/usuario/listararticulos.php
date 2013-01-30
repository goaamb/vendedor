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
$pagina = (isset ( $pagina ) && intval ( $pagina ) > 0 ? intval ( $pagina ) : 1);
$npagina = $totalpagina > 0 ? ceil ( $total / $totalpagina ) : 1;
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
		<img style="display: none;" src="<?=$imagen?>"
			alt="<?=$articulo->titulo?>" />
		<div class="meta">
			<p>
				<strong><?=$articulo->precio!=0?formato_moneda($articulo->precio)." \$us":"Preguntar"?></strong>
			</p>
		</div>
		<ul>
			<li><h2>
					<a href="<?=$furl?>" title="<?=$articulo->titulo?>"><?=$articulo->titulo?></a>
				</h2>
				<i><?=$articulo->ciudad_nombre?></i></li>
		</ul>
	</div><?php
			}
		}
		if (false && $total > $totalpagina) {
			?><p class="ver-mas">
		<img src="assets/images/ico/ajax-loader-see-more.gif"
			alt="<?=traducir("Ver más")?>" style="display: none;" /> <a href="#"
			title="<?=traducir("Ver más productos")?>"
			onclick="return verMasArticulos('home','<?=$inicio?>','<?=$section?>');"><?=traducir("Ver más")?></a><a
			href="#" title="<?=traducir("Ir al primer artículo")?>"
			onclick="document.body.scrollTop=0; return false;"
			style="display: none;"><?=traducir("Ir al primer artículo")?></a>
	</p><?php
		} else {
			?><p class="ver-mas paginador"><?php
			if ($npagina > 1) {
				$inicio = 1;
				$fin = 10;
				if ($pagina > 5) {
					$inicio = $pagina - 5;
					$fin = $inicio + ($pagina % 2 == 0 ? 9 : 10);
				}
				if ($fin > $npagina) {
					$fin = $npagina;
				}
				if ($pagina > 1) {
					?><a href="?pagina=1" onclick="return cambiarPagina(1);">&lt;&lt;</a><a
			href="?pagina=<?=($pagina-1)?>"
			onclick="return cambiarPagina(<?=($pagina-1)?>);">&lt;</a><?php
				}
				for($i = $inicio; $i <= $fin; $i ++) {
					if ($i != $pagina) {
						?><a href="?pagina=<?=$i?>"
			onclick="return cambiarPagina(<?=$i?>);"><?=$i?></a><?php
					} else {
						?><span><?=$i?></span><?php
					}
				}
				if ($fin < $npagina) {
					?><a href="?pagina=<?=($pagina+1)?>"
			onclick="return cambiarPagina(<?=$pagina+1?>);">&gt;</a><a
			href="?pagina=<?=$npagina?>"
			onclick="return cambiarPagina(<?=$npagina?>);">&gt;&gt;</a><?php
				}
				?><?php
			}
			?></p><?php
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