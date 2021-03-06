<?php
$padre = isset ( $arbol ) ? $arbol [0] ["id"] : 0;
$imagenes = $this->input->post ( "imagenes" );
if (trim ( $imagenes ) !== "") {
	$imagenes = explode ( ",", $imagenes );
} else {
	$imagenes = array ();
}
for($i = 0; $i < 6; $i ++) {
	if (! isset ( $imagenes [$i] )) {
		$imagenes [$i] = false;
	}
}
$nuevo = isset ( $nuevo ) ? $nuevo : false;
$modificar = isset ( $articulo ) && ! $nuevo;
$postFile = $modificar ? "product/edit/$articulo->id" : ($nuevo ? "product/nuevo/$articulo->id" : "product/nuevo");
if (isset ( $articulo )) {
	$terminado = 0;
	$productoLink = "product/$articulo->id-" . normalizarTexto ( $articulo->titulo );
}
$tipo_precio = $this->input->post ( "tipo-precio" ) ? $this->input->post ( "tipo-precio" ) : "precio-cantidad-box";
$cantidadVendidos = 0;
$editable = true;
?>
<script type="text/javascript"
	src="<?=base_url()?>assets/js/uploadprogress.js"></script>
<script type="text/javascript"
	src="<?=base_url()?>assets/js/articulo/articulo.js"></script>
<script type="text/javascript"
	src="<?=base_url()?>assets/js/swfupload/swfupload.js"></script>
<script type="text/javascript"
	src="<?=base_url()?>assets/js/editor/ckeditor.js"></script>
<script type="text/javascript"><?php
if ($usuario && $usuario->estado === "Incompleto") {
	?>
	var completoUsuario=false;
$(function(){
	$(".user-box .nmodal").click();
});
<?php
} else {
	?>var completoUsuario=true;<?php
}
$maxCarTitulo = 80;
?>
CKEDITOR.config.font_defaultLabel="Arial";
CKEDITOR.config.fontSize_defaultLabel="15";
CKEDITOR.config.contentsCss="<?=base_url()?>assets/css/editorDefault.css";
CKEDITOR.config.removeFormatTags = 'b,big,code,del,dfn,em,font,i,ins,kbd';
CKEDITOR.config.width = 650;
CKEDITOR.config.toolbar =
	[
		['Font','FontSize','TextColor','BGColor','-','Bold', 'Italic','Underline', '-', 'NumberedList', 'BulletedList', '-', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','Source']
	];
</script>
<link href="<?=base_url()?>assets/css/nuevo.css" type="text/css"
	rel="stylesheet" />
<div class="wrapper clearfix">
	<header class="cont-cab mbl">
		<h1>Pon a la venta tu artículo GRATIS</h1>
	</header>

	<div class="formA">
		<form id="formFake" data-submit="validFormFake">
			<div class="line">
				<label for="" class="col652"><span class="numero">1</span> Título y
					categoría del artículo o lote: <span class="f-r grey pt13"
					id="controlTitulo"><?php $titulo=set_value("titulo"); if($titulo){ print "Tiene ".($maxCarTitulo-strlen($titulo))." caracteres restantes.";}else{ print "Máximo $maxCarTitulo caracteres.";}?></span></label>
				<div class="recuadro con-consejo"><?php
				if ($editable) {
					?>
					<input type="text" class="enfoque required" data-consejo="consejo1"
						name="titulo" value="<?=$titulo?>" maxlength="<?=$maxCarTitulo?>"
						onkeyup="verificarMaximo.call(this,<?=$maxCarTitulo?>,'controlTitulo');"
						data-error-required="Añade un título." />
					<?php
				} else {
					?><span class="input"><?=$titulo;?></span><?php
				}
				?></div>
				<?=form_error("titulo");?><span class="errorTxt" id="tituloError"></span>
			</div>
			<div class="line clearfix w100">
				<div
					class="line-category <?php if(isset($arbol) && count($arbol)>0){ print "l-cat-selected";}?>"
					id="lista1">
						<?php $this->load->view("articulo/listacategorias")?>
					</div>
				<div class="postCat"
					<?php if(isset($arbol) && count($arbol)>1){ print "style='display:block;'";}?>>
					<img src="assets/images/ico/cat-arrow-right.png" />
				</div>
				<div
					class="line-category <?php if(!isset($arbol) ||(isset($arbol) && count($arbol)<=1)){ print "hidden";}else{print "l-cat-selected";}?>"
					id="lista2">
					<span class="choice"><?php if(isset($arbol[1])){ print $arbol[1]["nombre"];}?></span>
				</div>
				<div class="postCat"
					<?php if(isset($arbol) && count($arbol)>2){ print "style='display:block;'";}?>>
					<img src="assets/images/ico/cat-arrow-right.png" />
				</div>
				<div
					class="line-category <?php if(!isset($arbol) ||(isset($arbol) && count($arbol)<=2)){ print "hidden";}else{print "l-cat-selected";}?>"
					id="lista3">
					<span class="choice"><?php if(isset($arbol[2])){ print $arbol[2]["nombre"];}?></span>
				</div>
				<div class="postCat">
					<img src="assets/images/ico/cat-arrow-right.png" />
				</div>
				<div class="line-category hidden" id="lista4"></div>&nbsp<?=form_error("categoria");?>
			<span class="errorTxt" id="categoriaError"></span>
			<?php
			if ($editable) {
				?><div class="postCat mhn cl w225"
					<?php if(isset($arbol)){print "style='display:block;'";}?>>
					<a href="#" class="reset-line-categories">Cambiar categorías</a>
				</div><?php
			}
			?>
			</div>
			<div class="line con-consejo">
				<span class="numero">2</span> <label for="">Descripción del artículo
					o lote:</label>
				<?php
				if ($editable) {
					?><p>
					<textarea class="enfoque ckeditor" data-consejo="consejo2" rows="5"
						cols="" name="descripcion"><?=set_value("descripcion");?></textarea>
					<?=form_error("descripcion");?><span class="errorTxt"
						id="descripcionError"></span>
				</p><?php
				} else {
					?><div class="textarea"><?=$articulo->descripcion?></div><?php
				}
				?></div>
		</form>
		<?php
		if ($editable) {
			?><div class="line clearfix" id="uploader1">
			<p class="mbn w650">
				<span class="numero">3</span> <label>Sube al menos 1 foto:(Máximo
					por foto 4Mb)</label> <label class="f-r pt13 mrg0">¿Prefieres el <a
					href='#' title='cargador clásico'
					onclick="return cambiarModoClasico();">cargador clásico</a>?
				</label>
			</p>
			<p id="errorUploading" class="errorTxt"></p>
			<?php
			$this->load->view ( "articulo/upload_photo", array (
					"classcapaphoto" => "portada",
					"idcapaphoto" => "capaimagen1",
					"textocapaphoto" => "foto portada",
					"quiencapaphoto" => "1",
					"imagen" => $imagenes [0] 
			) );
			for($i = 2; $i <= 6; $i ++) {
				$classcapaphoto = "";
				if ($imagenes [$i - 2]) {
					$classcapaphoto = "";
				}
				if ($i == 6) {
					$classcapaphoto .= " mrn";
				}
				$this->load->view ( "articulo/upload_photo", array (
						"classcapaphoto" => $classcapaphoto,
						"idcapaphoto" => "capaimagen$i",
						"textocapaphoto" => "subir foto",
						"quiencapaphoto" => $i,
						"imagen" => $imagenes [$i - 1] 
				) );
			}
			?>&nbsp;<?=form_error ( "imagenes" );?><span class="errorTxt"
				id="imagenesError"></span>
			<div id="divFileProgressContainer"></div>
		</div><?php
		} else {
			?><div class="line clearfix" id="uploader1">
			<p class="mbn">
				<label>Imágenes</label>
			</p>
			<?php
			foreach ( $imagenes as $img ) {
				if ($img) {
					?><div class="uploader" style="background: transparent url(<?=imagenArticulo($usuario,$img,"thumb")?>) center center no-repeat;"></div><?php
				} else {
					?><div class="uploader"></div><?php
				}
			}
			?>
		</div><?php
		}
		?>
		<?=form_open($postFile,array("id"=>"formItem","data-submit"=>"validFormItem"));?>
		<?php
		if ($editable) {
			?><div class="line clearfix" id="uploader2" style="display: none;">
			<p class="mbn col652">
				<span class="numero">3</span> <label>Sube al menos 1 foto:(Máximo
					por foto 4Mb)</label> <label class="f-r pt13 mgr0">¿Prefieres el <a
					href='#' title='cargador moderno'
					onclick="return cambiarModoModerno();">cargador normal</a>?
				</label>
			</p>
			<p class="fleft">
				<label><input name="file1" type="file" /></label><br /> <label><input
					name="file2" type="file" /></label><br /> <label><input
					name="file3" type="file" /></label><br /> <label><input
					name="file4" type="file" /></label><br /> <label><input
					name="file5" type="file" /></label><br /> <label><input
					name="file6" type="file" /></label>
			</p>
			<span class="errorTxt" id="imagenesFileError" style="display: none;"><?=traducir("Debe ingresar almenos una imagen")?></span>
		</div>
		<input type="hidden" name="titulo" class="required"
			value="<?=set_value("titulo")?>"
			data-error-required="Añade un título." /> <input type="hidden"
			name="categoria" class="required" value="<?=set_value("categoria")?>"
			data-error-required="Añade una categoría." />
		<textarea name="descripcion" style="display: none;"
			data-error-required="Añade una descripción."><?=set_value("descripcion")?></textarea>
		<input type="hidden" name="imagenes"
			value="<?=set_value("imagenes")?>" class="required"
			data-error-required="Añade una imagen." /><?php
		}
		?>
		<div class="line clearfix d-b mbl">
			<div class="opciones">
				<p style="margin-bottom: 3px;">
					<span class="numero">4</span> <label>Precio:</label>
				</p>
				<input type="hidden" name="tipo-precio" value="precio-fijo-box" />
				<div class="dinero">
			<?php
			if ($editable) {
				?>
			<div id="precio-fijo-box" class="tipo-precio-box">
						<div class="recuadro w225 con-moneda f-l d-b mbm">
							<input type="text" class="t-r decimal propio"
								name="precio-oferta" data-error-funcion="validarPrecioOferta"
								data-error-propio="Añade el precio."
								value="<?=my_set_value("precio-oferta")?>" />
						</div>
						<span class="EUR">$us</span>
				&nbsp;<?=form_error("precio-oferta")?><span class="errorTxt"
							id="precio-ofertaError"></span>
					</div><?php
			}
			?></div>
			</div>
			<div class="line" id="destino-envios">
				<input type="hidden" value="1" name="envio_local" />
				<p>
					<span class="numero">5</span> <label for="" class="mbn">Características:</label>
				</p>
				<div class="dinero">
			<?php
			if ($editable) {
				?><p class="durac">
						<label>Contactar con:</label> <input type="text"
							class="t-r texto " name="contactar_con"
							value="<?=my_set_value("contactar_con","")?>" />
					</p>
					<span class="errorTxt" id="contactoError"></span>
					<div style="clear: both;"></div>
					<p class="durac">
						<label>Ciudad:</label> <select name="ciudad" class="t-r texto"><?php
				foreach ( $ciudades as $ciudad ) {
					?><option value="<?=$ciudad->id?>"
								<?=my_set_select("ciudad",$ciudad->id,$ciudad->id==196)?>><?=$ciudad->nombre?></option><?php
				}
				?></select>
					</p>
					<span class="errorTxt" id="ciudadError"></span>
					<div style="clear: both;"></div>

					<div id="vehiculoCaracteristicas" style="<?php
				if (! isset ( $padre ) || (isset ( $padre ) && $padre !== "1")) {
					print "display: none;";
				}
				?>">
						<p class="durac">
							<label>Marca:</label> <input type="text" class="t-r texto "
								name="marca" value="<?=my_set_value("marca","")?>"
								onfocus="verListaMarcas.call(this);"
								onblur="ocultaListaMarcas.call(this);"
								onkeyup="verListaMarcas.call(this);" autocomplete="off" /><?php
				if (isset ( $marcas ) && is_array ( $marcas ) && count ( $marcas ) > 0) {
					?><span id="listaMarcas" onclick="clearTimeout(timeMarcas);"><strong><?php
					foreach ( $marcas as $m ) {
						?><i onclick="seleccionarMarca.call(this)"><?=$m->marca?></i><?php
					}
					?></strong></span><?php
				}
				?></p>

						<span class="errorTxt" id="marcaError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Modelo:</label> <input type="text" class="t-r texto "
								name="modelo" value="<?=my_set_value("modelo","")?>" />
						</p>
						<span class="errorTxt" id="modeloError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Tipo:</label> <input type="text" class="t-r texto "
								name="tipo" value="<?=my_set_value("tipo","")?>" />
						</p>
						<span class="errorTxt" id="tipoError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Kilometraje:</label> <input type="text" class="t-r texto "
								name="kilometraje" value="<?=my_set_value("kilometraje","")?>" />
						</p>
						<span class="errorTxt" id="kilometrajeError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Cilindrada:</label> <input type="text" class="t-r texto "
								name="cilindrada" value="<?=my_set_value("cilindrada","")?>" />
						</p>
						<span class="errorTxt" id="cilindradaError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Combustible:</label> <input type="text" class="t-r texto "
								name="combustible" value="<?=my_set_value("combustible","")?>" />
						</p>
						<span class="errorTxt" id="combustibleError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Caja:</label> <input type="text" class="t-r texto "
								name="caja" value="<?=my_set_value("caja","")?>" />
						</p>
						<span class="errorTxt" id="cajaError"></span>
						<div style="clear: both;"></div>
					</div>
					<div id="mascotaCaracteristicas" style="<?php
				if (! isset ( $padre ) || (isset ( $padre ) && $padre !== "2")) {
					print "display: none;";
				}
				?>">
						<p class="durac">
							<label>Raza:</label> <input type="text" class="t-r texto "
								name="raza" value="<?=my_set_value("raza","")?>" />
						</p>
						<span class="errorTxt" id="razaError"></span>
						<div style="clear: both;"></div>
						<div class="opciones">
							<p>
								<label>Pedigri:</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="pedigri" value="Si"
									<?=my_set_radio("pedigri","",true)?> /> Si</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="pedigri" value="No"
									<?=my_set_radio("pedigri","")?> /> No</label>
							</p>
						</div>
						<span class="errorTxt" id="pedigriError"></span>
						<div style="clear: both;"></div>
						<div class="opciones">
							<p>
								<label>Sexo:</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="sexo" value="Macho"
									<?=my_set_radio("sexo","",true)?> /> Macho</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="sexo"
									value="Hembra" <?=my_set_radio("sexo","")?> /> Hembra</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="sexo"
									value="Hembra y Macho" <?=my_set_radio("sexo","")?> /> Hembra y
									Macho</label>
							</p>
						</div>
						<span class="errorTxt" id="pedigriError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Observación:</label> <input type="text" class="t-r texto "
								name="observacion" value="<?=my_set_value("observacion","")?>" />
						</p>
						<span class="errorTxt" id="observacionError"></span>
						<div style="clear: both;"></div>
					</div>
					<div id="viviendaCaracteristicas" style="<?php
				if (! isset ( $padre ) || (isset ( $padre ) && $padre !== "3")) {
					print "display: none;";
				}
				?>">
						<div class="opciones">
							<p>
								<label>Tipo venta:</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="tipo_venta"
									value="Alquiler" <?=my_set_radio("tipo_venta","",true)?> />
									Alquiler</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="tipo_venta"
									value="Anticretico" <?=my_set_radio("tipo_venta","")?> />
									Anticretico</label>
							</p>
							<p>
								<label><input type="radio" class="t-r" name="tipo_venta"
									value="Venta" <?=my_set_radio("tipo_venta","")?> /> Venta</label>
							</p>
						</div>
						<span class="errorTxt" id="tipo_ventaError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Direccion:</label> <input type="text" class="t-r texto "
								name="direccion" value="<?=my_set_value("direccion","")?>" />
						</p>
						<span class="errorTxt" id="direccionError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Superficie:</label> <input type="text"
								class="t-r texto entero" name="superficie"
								value="<?=my_set_value("superficie","")?>" />
						</p>
						<span class="errorTxt" id="superficieError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Dormitorios:</label> <input type="text"
								class="t-r texto entero" name="dormitorios"
								value="<?=my_set_value("dormitorios","")?>" />
						</p>
						<span class="errorTxt" id="dormitoriosError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Baños:</label> <input type="text" class="t-r texto entero"
								name="banos" value="<?=my_set_value("banos","")?>" />
						</p>
						<span class="errorTxt" id="banosError"></span>
						<div style="clear: both;"></div>
						<p class="durac">
							<label>Antigüedad:</label> <input type="text"
								class="t-r texto entero" name="antiguedad"
								value="<?=my_set_value("antiguedad","")?>" />
						</p>
						<span class="errorTxt" id="antiguedadError"></span>
						<div style="clear: both;"></div>
					</div><?php
			}
			?></div>
			</div>
			<div class="line">
				<input type="hidden" name="forma-pago[]" value="4" />

			</div>
			<div class="ver-mas">
		<?
		if ($modificar) {
			?><input type="hidden" name="id" value="<?=$articulo->id?>" /><?php }?>
			<input type="hidden" name="__accion"
					value="<?=($modificar?"modificar":"ingresar");?>" /> 
				<?php
				?>
					<input type="hidden" name="modo" value="1" /> <input type="submit"
					value="<?=($modificar?"Actualizar":"Poner a la venta");?>"
					class="bt" /> 
					<?php
					if (! isset ( $isFacebook ) || (isset ( $isFacebook ) && ! $isFacebook)) {
						?><span class="mhm">o</span> <a href=""
					title="cancelar e ir al Inicio">cancelar</a><?php
					}
					?>
					
			</div><?=form_close()?>
</div>

		<div id="consejo1" class="consejos">
			<p>
				<strong>Consejos:</strong>
			</p>
			<ul>
				<li>Utiliza palabras clave que atraigan a los compradores, como
					marcas o categorías.</li>
				<li>Piensa cómo buscarías tú el artículo si lo quisieras comprar y
					escríbelo así.</li>
				<li>Escribe con corrección ortográfica y sin mayúsculas, una buena
					presentación da confianza al comprador.</li>
			</ul>
			<span class="arrow"></span>
		</div>
		<!--consejos-->

		<div id="consejo2" class="consejos">
			<p>
				<strong>Consejos:</strong>
			</p>
			<ul>
				<li>Describe claramente lo que vendes, no omitas anomalías ni
					desperfectos.</li>
				<li>Incluye todo aquello que tú preguntarías si fueras el comprador.</li>
				<li>Escribe con corrección ortográfica y sin mayúsculas, una buena
					presentación da confianza al comprador.</li>
				<li>Puedes añadir descripciones HTML para personalizar tus anuncios.</li>
			</ul>
			<span class="arrow"></span>
		</div>
	</div>
	<div style="display: none;" id="otrosErrores"></div>