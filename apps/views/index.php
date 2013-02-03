<div class="wrapper clearfix">
	<div class="main-col">
		<article>
			<h1>Vehiculos</h1>
			<ul>
		<?php
		if (isset ( $vehiculos ) && is_array ( $vehiculos ) && count ( $vehiculos )) {
			foreach ( $vehiculos as $vehiculo ) {
				$furl = "product/" . $vehiculo->id . "-" . normalizarTexto ( $vehiculo->titulo );
				$imagen = array_shift ( explode ( ",", $vehiculo->foto ) );
				$imagen = imagenArticulo ( false, $imagen, "thumb" );
				if ($imagen) {
					?><li><a href="<?=$furl?>" title="<?=$vehiculo->titulo?>"><img
						src="<?=$imagen?>" width="80" alt="<?=$vehiculo->titulo?>" /><span><?=$vehiculo->titulo?></span><span><?=formato_moneda($vehiculo->precio)."\$us"?></span></a></li><?php
				}
			}
		}
		?>
	</ul>
		</article>
		<article>
			<h1>Mascotas</h1>
			<ul>
		<?php
		if (isset ( $mascotas ) && is_array ( $mascotas ) && count ( $mascotas )) {
			foreach ( $mascotas as $mascota ) {
				$furl = "product/" . $mascota->id . "-" . normalizarTexto ( $mascota->titulo );
				$imagen = array_shift ( explode ( ",", $mascota->foto ) );
				$imagen = imagenArticulo ( false, $imagen, "thumb" );
				if ($imagen) {
					?><li><a href="<?=$furl?>"><img src="<?=$imagen?>" width="80"
						alt="<?=$mascota->titulo?>" /><span><?=$mascota->titulo?></span><span><?=formato_moneda($mascota->precio)."\$us"?></span></a></li><?php
				}
			}
		}
		?>
	</ul>
		</article>
		<article>
			<h1>Viviendas</h1>
			<ul>
		<?php
		if (isset ( $viviendas ) && is_array ( $viviendas ) && count ( $viviendas )) {
			foreach ( $viviendas as $vivienda ) {
				$furl = "product/" . $vivienda->id . "-" . normalizarTexto ( $vivienda->titulo );
				$imagen = array_shift ( explode ( ",", $vivienda->foto ) );
				$imagen = imagenArticulo ( false, $imagen, "thumb" );
				if ($imagen) {
					?><li><a href="<?=$furl?>"><img src="<?=$imagen?>" width="80"
						alt="<?=$vivienda->titulo?>" /><span><?=$vivienda->titulo?></span><span><?=formato_moneda($vivienda ->precio)."\$us"?></span></a></li><?php
				}
			}
		}
		?>
	</ul>
		</article>
	</div>
</div>