<!doctype html>
<html lang="en">
<head>
	<title><?php echo $roomName ?>: <?php echo $roomTopic ?></title>
	<link rel="shortcut icon" href="<?php echo $wgFavicon ?>">

	<!-- Make IE recognize HTML5 tags. -->
	<!--[if IE]>
		<script>/*@cc_on'abbr article aside audio canvas details figcaption figure footer header hgroup mark menu meter nav output progress section summary time video'.replace(/\w+/g,function(n){document.createElement(n)})@*/</script>
	<![endif]-->

	<!-- CSS -->
	<link rel="stylesheet" href="<?php echo $wgScriptPath ?>/extensions/Chat/css/chat.css">

	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/lib/jquery-1.5.1.js?<?php echo $wgStyleVersion ?>"></script>	
	<script src="<?php echo $wgScriptPath ?>/resources/mediawiki/mediawiki.js?<?php echo $wgStyleVersion ?>"></script>	
	<!-- JS -->
	<?php echo $globalVariablesScript ?>
	<?php //TODO: use js var?>

</head>
<body class="<?php echo $bodyClasses ?>">

	<header id="ChatHeader" class="ChatHeader">
		<h1 class="public wordmark">
			<a href="<?php echo $mainPageURL ?>">
			<?php if ($themeSettings['wordmark-type'] == 'graphic') { ?>
			<!--<img src="<?php echo $themeSettings['wordmark-image-url'] ?>">-->
			<?php } else { ?>
			<span class="font-<?php echo $themeSettings['wordmark-font']?>"><?php echo $themeSettings['wordmark-text'] ?></span>
			<?php } ?>
			</a>
		</h1>
		<h1 class="private"></h1>
		<div class="User"></div>
	</header>

	<section id="WikiaPage" class="WikiaPage">
	
		<div id="Rail" class="Rail">
			<h1 class="public wordmark selected">
				<!-- FIXME: fix and uncomment all the images in here. -ib -->
				<!--<img src="<?php echo $wgBlankImgUrl ?>" class="chevron">-->
				<?php if ($themeSettings['wordmark-type'] == 'graphic') { ?>
				<!--<img src="<?php echo $themeSettings['wordmark-image-url'] ?>" class="wordmark">-->
				<?php } else { ?>
				<span class="font-<?php echo $themeSettings['wordmark-font']?>"><?php echo $themeSettings['wordmark-text'] ?></span>
				<?php } ?>
				<span id="MsgCount_<?php echo $roomId ?>" class="splotch">0</span>
			</h1>
			<ul id="WikiChatList" class="WikiChatList"></ul>
			<h1 class="private">Private Messages</h1>
			<ul id="PrivateChatList" class="PrivateChatList"></ul>
		</div>

		<form id="Write" class="Write" onsubmit="return false">
			<!--<img src="<?php echo $avatarUrl ?>">-->
			<textarea name="message"></textarea>
			<input type="submit">
		</form>

	</section>

	<div id="UserStatsMenu" class="UserStatsMenu"></div>

	<!-- HTML Templates -->
	<script type='text/template' id='message-template'>
		<!--<img class="avatar" src="<%= avatarSrc %>"/>-->
		<span class="time"><%= timeStamp %></span>
		<span class="username"><%= name %></span>
		<span class="message"><%= text %></span>
	</script>
	<script type='text/template' id='inline-alert-template'>
		<%= text %>
	</script>
	<script type='text/template' id='user-template'>
		<!--<img src="<%= avatarSrc %>"/>-->
		<span class="username"><%= name %></span>
		<div class="details">
			<span class="status">Away</span>
		</div>
		<% if(isPrivate) { %>
			<span id="MsgCount_<%= roomId %>" class="splotch">0</span>
		<% } %>
		<div class="UserStatsMenu">
			<div class="info">
				<!--<img src="<%= avatarSrc %>"/>-->
				<span class="username"><%= name %></span>
				<span class="edits"><?php echo $editCountStr ?></span>
				<span class="since"><?php echo $memberSinceStr ?></span>
			</div>
			<ul class="actions">
				
			</ul>
		</div>
	</script>
	<script type='text/template' id='user-action-template'><li class="<%= actionName %>"><a href="#"><%= actionDesc %></a></li></script>
	<?php //TODO: use AM ?>
	<!-- Load these after the DOM is built -->
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/lib/socket.io.client.js?<?php echo $wgStyleVersion ?>"></script>
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/lib/jquery.wikia.js?<?php echo $wgStyleVersion ?>"></script>
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/lib/jquery.json-1.3.js?<?php echo $wgStyleVersion ?>"></script>
	
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/lib/underscore.js?<?php echo $wgStyleVersion ?>"></script>
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/lib/backbone.js?<?php echo $wgStyleVersion ?>"></script>
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/models/models.js?<?php echo $wgStyleVersion ?>"></script>
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/controllers/controllers.js?<?php echo $wgStyleVersion ?>"></script>
	<script src="<?php echo $wgScriptPath ?>/extensions/Chat/js/views/views.js?<?php echo $wgStyleVersion ?>"></script>
</body>
</html>
