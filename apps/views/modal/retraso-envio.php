<div id="popUp">
	<div class="formA">
		<form class="clearfix d-b" action="post" id="formRetrasoEnvio"
			onsubmit="return verificarRetrasoEnvio.call(this)">
			<header>
				<h1>Retraso en el envío de los artículos denunciado</h1>
				<p>
					Comprador <a href="store/<?=$comprador->seudonimo?>"
						<?php if($comprador->estado=="Baneado"){print "class='baneado'";}?>
						title="Ver perfil de <?=$comprador->seudonimo?>"><strong><?=$comprador->seudonimo?></strong></a>
					<a href="home/modal/votos/votos/<?=$comprador->id?>"
						class="nmodal green">+<?=$comprador->positivo?></a> 
					<?php if($comprador->negativo>0){?>
					<a href="home/modal/votos/votos/<?=$comprador->id?>"
						class="nmodal red">-<?=$comprador->negativo?></a> <?php }?><span
						class="dark-grey">|</span> <a
						href="home/modal/enviar-mensaje-privado/mensaje/<?=$comprador->id?>"
						class="nmodal" title="Enviar mensaje privado">enviar mensaje
						privado</a>
				</p>
			</header>
			<div class="wrap">
				<p class="justify">El comprador ha denunciado tu retraso en el envío
					de los artículos. Debes enviarlos o, sino recibiste el pago, marca
					esa opción aquí. Si no envías o marcas la opción de que no has recibido el pago, en <?=$this->configuracion->variables("denuncia3b");?> días la transacción se cerrará y recibirás un voto negativo.</p>
				<p class="justify">Si marcas que no has recibido el pago, abriremos
					una disputa donde os pediremos comprobantes de pago y decidiremos
					el reparto de votos.</p>
				<p>
					<label><input type="checkbox" name="nopago" value="1" /> Todavía no
						he recibido el pago.</label>
				</p>
				<p>&nbsp;</p>
			</div>
			<footer>
				<p class="actions">
					<input type="hidden" name="paquete" value="<?=$paquete->id?>" /> <input
						type="submit" class="bt" value="<?=traducir("Aceptar")?>" /> <span
						class="mhm">o</span> <a class="nyroModalClose">Cerrar</a>
				</p>
			</footer>
		</form>
	</div>
</div>
<script type="text/javascript">
	$(function() {
 	 	$('.nmodal').nyroModal();
	});
</script>