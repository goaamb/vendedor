<div id="popUp">
	<div class="formA">
		<form action="" method="post"
			onsubmit="return confirmarRecepcion.call(this);"
			id="formConfirmarRecepcion">
			<header>
				<h1>Confirmar recepción</h1>
				<p>
					Vendedor <a href="store/<?=$vendedor->seudonimo?>"
						<?php if($vendedor->estado=="Baneado"){print "class='baneado'";}?>
						title="Ver perfil de <?=$vendedor->seudonimo?>"><strong><?=$vendedor->seudonimo?></strong></a>
					<a href="home/modal/votos/votos/<?=$vendedor->id?>"
						class="nmodal green">+<?=$vendedor->positivo?></a> 
					<?php if($vendedor->negativo>0){?>
					<a href="home/modal/votos/votos/<?=$vendedor->id?>"
						class="nmodal red">-<?=$vendedor->negativo?></a> <?php }?><span
						class="dark-grey">|</span> <a
						href="home/modal/enviar-mensaje-privado/mensaje/<?=$vendedor->id?>"
						class="nmodal" title="Enviar mensaje privado">enviar mensaje
						privado</a>
				</p>
			</header>
			<div class="wrap">
				<p class="justify">Si no recibes el artículo en <?=$this->configuracion->variables("denuncia4a")?> días podrás
					denunciarlo aquí, en tu lista de artículos comprados.</p>
				<table class="no-border mvl black">
					<tr>
						<td><input type="radio" name="confirma" value="1" /></td>
						<td>He recibido el artículo y estoy conforme (ambos recibiréis un
							voto positivo)</td>
					</tr>
					<tr>
						<td><input type="radio" name="confirma" value="2" /></td>
						<td>He recibido el artículo pero no coincide con la descripción
							del vendedor y quiero una devolución (abriremos una disputa en la
							que os pediremos comprobantes y decidiremos el reparto de votos)</td>
					</tr>
				</table>
				<p class="justify">ATENCIÓN: Esta acción no puede deshacerse; en
					caso de no estar conforme con el artículo recibido intenta primero
					contactar con el vendedor por mensaje privado.</p>
			</div>
			<!--wrap-->
			<footer>
				<p class="actions">
					<input type="hidden" name="paquete" value="<?=$paquete->id?>" /> <input
						type="submit" class="bt" value="Confirmar recepción" /> <span
						class="mhm">o</span> <a class="nyroModalClose">cancelar</a>
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