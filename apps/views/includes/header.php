<?php
$headerTitle = isset ( $headerTitle ) ? $headerTitle : (isset ( $this->configuracion ) ? $this->configuracion->variables ( "defaultHeaderTitle" ) : "");
$extraMeta = isset ( $extraMeta ) ? $extraMeta : "";
$publi = ($this->configuracion->variables ( "publicidad" ) == "Si");
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
<?php
if ($publi) {
	?>
	<div class="top-publi">
		<script type="text/javascript"><!--
google_ad_client = "ca-pub-1382616588916243";
/* vendedor-header */
google_ad_slot = "3474018681";
google_ad_width = 728;
google_ad_height = 90;
//-->
</script>
		<script type="text/javascript"
			src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
	</div><?php
}
?>
	<header class="header">
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
				?><a
							href="<?=($usuario->estado==="Incompleto"?"home/modal/informacion-compra-venta":"product/nuevo");?>"
							title="ir a subastas"
							class="<?=($usuario->estado==="Incompleto"?"nmodal":"");?> sep">Vender</a>
						<a href="logout" title="Salir">Salir</a>
			<?php }else {?><a href="login" title="Ingresa con tu cuenta"
							class="sep">Entrar</a> <a href="register" title="Registrate"
							class="">Registrarse</a>
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
					<li><a href="">Home</a></li>
					<li><a href="estatica/aboutus">About us</a></li>
					<li><a href="estatica/services">Services</a></li>
					<li><a href="estatica/portfolio">Portfolio</a></li>
					<li><a href="estatica/contactus">Contact us</a></li>
				</ul>
			</div>
			<!--user-box-->
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