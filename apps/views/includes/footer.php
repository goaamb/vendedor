<?php
$isFacebook = isset ( $isFacebook );
?></div>
<footer class="footer"
	<?php
	if ($isFacebook) {
		print "style='display:none;'";
	}
	?>>
	<div class="wrapper">
		<div class="f-l"></div>
		<div class="f-r">
			<p>
				<a href="http://www.facebook.com/clasificados.vendedor.bolivia"
					title="Ir al Facebook"><img src="assets/images/ico/ico-fb-24.png"
					alt="Facebook" /></a>
			</p>
		</div>
		<div class="central"></div>
	</div>
</footer>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-37648398-1']);
  _gaq.push(['_trackPageview']);
  setTimeout('_gaq.push([\'_trackEvent\', \'NoBounce\', \'Over 10 seconds\'])',10000);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</body>
</html>