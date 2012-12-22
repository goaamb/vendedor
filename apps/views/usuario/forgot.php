<div class="wrapper clearfix">
	<header class="cont-cab mbl">
		<h1>¿Olvidaste tu contraseña?</h1>
		<p>Debes ingresar a continuación el email con el que tienes un
			registro en vendedor.com para que sea enviado un mail con tu nueva
			contraseña y un enlace que por seguridad te permitira verificar que
			eres tu quien hizo el cambio.</p>
	</header>
	<div class="formB">
		<?php
		print form_open ( 'forgot' );
		?><input type="hidden" name="__accion" value="olvidar" /><span
			class="errorTxt"><?print (isset($error)?$error:"") ?></span>
		<p>
			<label for="">Email ( Con el que te registraste )</label> <input
				type="mail" class="texto w225" name="email"
				value="<?php echo set_value('email',($usuario?$usuario->email:"")); ?>" /> <?php print form_error("email") ;?>
		</p>
		<div class="ver-mas">
			<div class="clearfix registro-dual">
				<input type="submit" value="Enviar" class="bt" name="olvidar" />
			</div>
		</div>
		</form>
	</div>
</div>