<div id="popUp">
	<div class="formA">
		<form action="" method="post"
			onsubmit="return denunciarRecibido.call(this);"
			id="formDenunciaRecibido">
			<header>
				<h1>Denunciar artículo no recibido</h1>
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
				<p class="justify">Antes de denunciar este anuncio como no recibido,
					envía un mensaje privado al vendedor para intentar resolver el
					problema.</p>

				<p class="justify">Si no llegáis a un entente, denuncia la transacción; en ese momento tendras un plazo de <?=$this->configuracion->variables("denuncia4b");?> días para confirmar la recepción del artículo. Si en ese plazo no lo recibes abriremos una disputa en la que os pediremos comprobantes de pago y envío y decidiremos el reparto de votos.</p>
				<p class="justify">ATENCIÓN: esta accion no puede deshacerse y el
					retraso puede deberse a problemas con la compañia de envío, intenta
					contactar por mensaje privado al vendedor antes de denunciar.</p>
			</div>
			<footer>
				<p class="actions">
					<input type="hidden" name="paquete" value="<?=$paquete->id?>" /> <input
						type="submit" class="bt red" value="<?=traducir("Denunciar")?>" />
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