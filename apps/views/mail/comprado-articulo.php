<?php
$this->load->view ( "mail/mail-cabecera" );
?>
<h1
	style="color: #333333; font-size: 28px; font-family: Arial, Helvetica, sans-serif; margin: 0 0 20px">Ha
	comprado un artículo</h1>
<p
	style="color: #333333; font-size: 15px; font-family: Arial, Helvetica, sans-serif; margin: 0 0 15px">
	Ha comprado correctamente el artículo <a href="<?=$url?>"
		style="text-decoration: none; color: #035f8d;"><?=$titulo?></a>.
</p>
<p>
	Puede verlo en su listado de <a href="<?=base_url()."store/{$this->myuser->seudonimo}/self/1/detail";?>"
		style="text-decoration: none; color: #035f8d;">artículos comprados</a>
</p>
<?php
$this->load->view ( "mail/mail-pie" );
?>