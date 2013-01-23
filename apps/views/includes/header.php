<?php
$headerTitle = isset ( $headerTitle ) ? $headerTitle : (isset ( $this->configuracion ) ? $this->configuracion->variables ( "defaultHeaderTitle" ) : "");
$extraMeta = isset ( $extraMeta ) ? $extraMeta : "";
$publi = ($this->configuracion->variables ( "publicidad" ) == "Si");
$isFacebook = isset ( $isFacebook );
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?=$headerTitle?></title>
<base href="<?=base_url();?>" />
<meta name="description" content="" />
<meta name="keywords" content="" />
<?=$extraMeta?>
<link rel="stylesheet" type="text/css" href="assets/css/reset.css" />
<link rel="stylesheet" type="text/css" href="assets/css/style.css" />
<link rel="stylesheet" type="text/css" href="assets/css/extra.css" />
<link rel="stylesheet" type="text/css" href="assets/css/nyroModal.css" />
<link rel="stylesheet" type="text/css"
	href="assets/css/mediaqueries.css" />
<link rel="stylesheet" type="text/css"
	href="assets/css/mediaqueries.css" />
<link rel="stylesheet" type="text/css" href="assets/css/style2.css" />
<link rel="shortcut icon" href="assets/icon/favicon.ico" />
<link href="assets/icon/favicon.ico" rel="shortcut icon"
	type="image/x-icon" />
<!--[if lte IE 7]><link rel="stylesheet" type="text/css" href="assets/css/ie7.css" /><![endif]-->
<script type="text/javascript" src="assets/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="assets/js/general.js"></script>
<script type="text/javascript" src="assets/js/piscolabis.framework.js"></script>
<script type="text/javascript" src="assets/js/jquery.tablesorter.min.js"></script>
<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
<script type="text/javascript" src="assets/js/easySlider1.7.g.js"></script>
<script type="text/javascript"
	src="assets/js/jquery.nyroModal.custom.js"></script>
<!--[if IE 6]>
	<script type="text/javascript" src="assets/js/jquery.nyroModal-ie6.min.js">
<![endif]-->
<script type="text/javascript" src="assets/js/goaamb/G.js"></script>
<script type="text/javascript" src="assets/js/valid.js"></script>
<script type="text/javascript" src="assets/js/general2.js"></script>
<script type="text/javascript">
	SeudonimoUsuario='<?php if(isset($usuario) && $usuario){ print "$usuario->seudonimo";}?>';
	$(function() {
 	 	$('.nmodal').nyroModal();
	});
</script>
</head>
<body>
	<div id="ieMessage" style="display: none;">
		<div class="contenido">
			El presente Navegador es demasiado obsoleto para desplegar la pagina,
			y en el caso de ingresar articulos podria causar inconvenientes, se
			recomienda usar: <a href="http://www.mozilla.org/es-ES/firefox/new/">Mozilla
				Firefox</a> y/o <a
				href="https://www.google.com/intl/es/chrome/browser/?hl=es">Google
				Chrome</a><span onclick="$('#ieMessage').hide()">x</span>
		</div>
	</div>
	<header class="header"
		<?php
		if ($isFacebook) {
			print "style='display:none;'";
		}
		?>>
		<div class="logosection">
			<div class="contenedor">
				<div class="slogan"></div>
				<div class="logo"></div>
				<div class="barrasuperior">
					<div class="barrasuperiorfondo"></div>
					<div class="user-box">
			<?php
			if ($usuario) {
				$imagen = imagenPerfil ( $usuario, "small" );
				?>
				<a href="store/<?=$this->myuser->seudonimo?>" title="ir a tu perfil" class="avatar" style="background-image: url(<?=$imagen."?rand=".rand();?>);"></a>
						<a href="store/<?=$this->myuser->seudonimo?>"
							title="ir a tu perfil" class="sep">Mi perfil</a> <?php
				if ($usuario->tipo == 'Administrador') {
					?><a href="administration/dashboard" title="ir a la administración"
							class="sep">Dashboard</a> <?php
				}
				?><a href="product/nuevo" title="Publicar" class="sep">Publicar</a>
						<a href="logout" title="Salir">Salir</a>
			<?php }else {?><a href="product/nuevo" title="Publicar" class="sep">Publicar</a><a
							href="login" title="Ingresa con tu cuenta" class="sep">Entrar</a>
						<a href="register" title="Registrate" class="">Registrarse</a>
			<?php }?>
			</div>
					<!--user-box-->
					<div class="search-box">
						<form action="" method="get"
							onsubmit="return cambiarCriterioBusqueda.call(this.criterio);">
							<p class="label-on-field">
								<input type="text" class="texto OwnTextBox" name="criterio"
									data-text="<?=traducir("Escriba aqui");?>"
									data-class="OwnTextBoxNoData"
									value="<?=$this->input->get("criterio");?>" />
								<script type="text/javascript">
							var ot = $(".OwnTextBox");
							if (ot) {
								for ( var i = 0; i < ot.length; i++) {
									G.OwnTextBox.convert(ot[i]);
								}
							}</script>
								<span class="resetBusqueda"
									<?=($this->input->get("criterio")?"style='display:block';":"")?>
									onclick="resetBusqueda();">x</span>
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="wrapper clearfix inferiorCabecera">
			<div class="menu">
				<div class="fondo gradiente"></div>
				<ul>
					<li><a href="">Inicio</a></li>
					<li><a href="aboutus">Acerca de nosotros</a></li>
					<li><a href="services">Servicios</a></li>
				</ul>
			</div>
			<!--user-box-->
			<div class="icon-box">
				<a href="?categoria=1"
					onclick="cambiarBusquedaCategoria(1);return false;"><img
					src="assets/images/html/automovil.png" /><span>Vehículos</span></a>
				<a href="?categoria=2"
					onclick="cambiarBusquedaCategoria(2);return false;"><img
					src="assets/images/html/perro.png" /><span>Mascotas</span></a> <a
					href="?categoria=3"
					onclick="cambiarBusquedaCategoria(3);return false;"><img
					src="assets/images/html/casa.png" /><span>Viviendas</span></a> <a
					href="product/nuevo" title="Publicar" class="ultimo"><img
					src="assets/images/html/publicar.png" /><span>Publicar</span></a>
			</div>

			<div class="search-box">
				<div class="fondo gradiente"></div>
				<form action="" method="get"
					onsubmit="return cambiarCriterioBusqueda.call(this.criterio);">
					<p class="label-on-field">
					<?php
					if (isset ( $categorias ) && is_array ( $categorias ) && count ( $categorias ) > 0) {
						if (! isset ( $seccion )) {
							$seccion = "category";
						}
						$seccion .= "/";
						$keys = array_keys ( $categorias );
						?><select onchange="cambiarBusquedaCategoria(this.value)"><option
								value="">Todas las Categorias</option> <?php
						$unico = (count ( $categorias ) == 1);
						foreach ( $categorias as $k => $categoria ) {
							$d = $categoria ["datos"];
							?><option value="<?=$k?>"
								<?php if(isset($_GET["categoria"]) && $_GET["categoria"]==$k){print "selected='selected'";}?>><?=$d["nombre"];?> (<?=$d["cantidad"]?>)</option><?php
						}
						?></select><?php
					}
					?>
						<input type="text" class="texto OwnTextBox" name="criterio"
							data-text="<?=traducir("Escriba aqui");?>"
							data-class="OwnTextBoxNoData"
							value="<?=$this->input->get("criterio");?>" />
						<script type="text/javascript">
							var ot = $(".OwnTextBox");
							if (ot) {
								for ( var i = 0; i < ot.length; i++) {
									G.OwnTextBox.convert(ot[i]);
								}
							}</script>
						<span class="resetBusqueda"
							<?=($this->input->get("criterio")?"style='display:block';":"")?>
							onclick="resetBusqueda();">x</span>
					</p>


				</form>
			</div>
		</div>
		<!--wrapper-->
	</header>
	<div class="content">