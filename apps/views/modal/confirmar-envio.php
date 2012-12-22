<div id="popUp">
	<div class="formA">
		<form class="clearfix d-b" method="post" id="formConfirmarEnvio"
			onsubmit="return confirmarEnvio.call(this);">
			<header>
				<h1><?=traducir("Confirmar envío")?></h1>
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
			<div class="wrap" style="padding-bottom: 0px;">
				<p class="justify">Al confirmar el envío afirmas haber cobrado el
					importe integro de la transacción y haber enviado todos los
					artículos pagados en esta</p>
				<p>
					<br /> <br /> <br /> <br /> <br />
				</p>
				<p class="justify">ATENCIÓN: esta acción no puede; por favor
					asegúrate de que ya haz realizado el envío</p>
			</div>
			<!--wrap-->
			<footer>
				<p class="actions">
					<input type="hidden" name="paquete" value="<?=$paquete->id?>" /> <input
						type="submit" class="bt" value="<?=traducir("Confirmar envío")?>" />
					<span class="mhm">o</span> <a class="nyroModalClose">cancelar</a>
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