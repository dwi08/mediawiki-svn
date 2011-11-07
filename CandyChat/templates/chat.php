<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Candy - Chats are not dead yet</title>
	<link rel="shortcut icon" href="<?php print $candyPath ?>/res/img/favicon.png" type="image/gif" />
	<link rel="stylesheet" type="text/css" href="<?php print $candyPath ?>/res/default.css" />
	
	<!-- TODO: use mw's native copy of jQuery -->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
	<script type="text/javascript" src="<?php print $candyPath ?>/libs/libs.bundle.js"></script>
	<script type="text/javascript" src="<?php print $candyPath ?>/candy.bundle.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			Candy.init('http-bind/', {
				core: { debug: true, autojoin: ['<?php print $room ?>'] },
				view: { resources: '<?php print $candyPath ?>/res/' }
			});
			
			Candy.Core.connect(<?php print '"' . $authParams['username'] . '@localhost", "__IdentityApiToken__' . $authParams['token'] . '"' ?>);
		});
	</script>
</head>
<body>
	<div id="candy"></div>
</body>
</html>
