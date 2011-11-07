<div class="chat-live"><?php echo wfMsg('chat-live') ?></div>
<h1><?php echo $chatHeadline ?></h1>

<?php //print_r($chatters) ?>

<?php if ( !empty($totalInRoom) ) { ?>
<div class="chat-whos-here">
	<h2><?php echo wfMsg('chat-whos-here', $totalInRoom) ?></h2>
	<?php if(!empty($chatters)){ ?>
	<ul>
		<?php foreach($chatters as $chatter) { ?>
			<li>
				<img src='<?php echo $chatter['avatarUrl'] ?>' class='avatar'/>
				<div class="UserStatsMenu">
					<div class="info">
						<img src="<?php echo $chatter['avatarUrl'] ?>">
						<span class="username"><?php echo $chatter['username'] ?></span>
						<span class="edits"><?php echo wfMsg('chat-edit-count', $chatter['editCount']) ?></span>
						<?php if($chatter['showSince']): ?>
							<span class="since"><?php echo wfMsg('chat-member-since', $chatter['since']) ?></span>
						<?php endif; ?>
					</div>
					<ul class="actions">
						<li class="profile"><a href="<?php echo $chatter['profileUrl'] ?>">User Profile</a></li>
						<li class="contribs"><a href="<?php echo $chatter['contribsUrl'] ?>">Contributions</a></li>
					</ul>
				</div>				
			</li>
		<?php } ?>
	</ul>
	<?php } ?>
</div>
<?php } ?>

<div class="chat-join">
	<?php echo $profileAvatar ?>
	<button onclick="onChatButtonClick()"<?php echo ($isLoggedIn?"":" class='loginToChat'"); ?>>
		<img src="<?php echo $buttonIconUrl ?>">
		<?php echo $buttonText ?>
	</button>
</div>

<script>
	function onChatButtonClick(){
		var isLoggedIn = <?php echo ($isLoggedIn ? "true" : "false") ?>;
		var message = 'protected'; // gives the 'login required to perform this action' message at the top of the login box
		if(isLoggedIn){
			<?php echo $chatClickAction ?>
		} else {
			showComboAjaxForPlaceHolder(false, "", function() {
				AjaxLogin.doSuccess = function() {
					$('.modalWrapper').children().not('.close').not('.modalContent').not('h1').remove();
					$('.modalContent').load(wgServer + wgScript + '?action=ajax&rs=moduleProxy&moduleName=ChatRail&actionName=AnonLoginSuccess&outputType=html');
				}
			}, false, message); // show the 'login required for this action' message.
		}
	} // end onChatButtonClick()
</script>
