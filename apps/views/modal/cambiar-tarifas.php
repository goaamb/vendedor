<div id="popUp">
	<div class="formA">
		<header>
			<h1>Cambiar tipo de tarifas</h1>
			<p>
				Tarifa actual: <?=$this->myuser->tipo_tarifa=="Comision"?traducir("A comisión variable"):traducir("Tarifa plana")?> <?=($this->myuser->nueva_tarifa?"( El siguiente mes: ".($this->myuser->nueva_tarifa=="Comision"?traducir("A comisión variable"):traducir("Tarifa plana")).")":"")?>
			</p>
		</header>
		<div class="wrap">
			<div class="line">
				<label><input type="radio" name="tipo_cambio" value="Comision"
					<?=($this->myuser->tipo_tarifa=="Comision"?"checked='checked'":"")?> />
					A comisión variable.</label><br /> <label><input type="radio"
					name="tipo_cambio" value="Plana"
					<?=($this->myuser->tipo_tarifa!="Comision"?"checked='checked'":"")?> />
					Tarifa plana.</label>
			</div>
		</div>
		<footer>
			<p class="actions">
				<a href="#" onclick="return cambiarTipoTarifa()" class="bt"
					title="Aceptar el cambio de tarifa">Aceptar</a> <span class="mhm">o</span>
				<a class="nyroModalClose">cancelar</a>
			</p>
		</footer>
	</div>
</div>

<script type="text/javascript">
	$(function() {
 	 	$('.nmodal').nyroModal();
	});
</script>