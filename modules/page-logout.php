<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */

register_module([
	"name" => "Logout",
	"version" => "0.6.1",
	"author" => "Starbeamrainbowlabs",
	"description" => "Adds an action to let users user out. For security reasons it is wise to add this module since logging in automatically opens a session that is valid for 30 days.",
	"id" => "page-logout",
	"code" => function() {
		
		/**
		 * @api		{post}	?action=logout	Logout
		 * @apiDescription	Logout. Make sure that your bot requests this URL when it is finished - this call not only clears your cookies but also clears the server's session file as well. Note that you can request this when you are already logged out and it will completely wipe your session on the server.
		 * @apiName		Logout
		 * @apiGroup	Authorisation
		 * @apiPermission	Anonymous
		 */
		
		/*
		 * ██       ██████   ██████   ██████  ██    ██ ████████ 
		 * ██      ██    ██ ██       ██    ██ ██    ██    ██    
		 * ██      ██    ██ ██   ███ ██    ██ ██    ██    ██    
		 * ██      ██    ██ ██    ██ ██    ██ ██    ██    ██    
		 * ███████  ██████   ██████   ██████   ██████     ██    
		 */
		add_action("logout", function() {
			global $env;
			$env->is_logged_in = false;
			unset($env->user);
			unset($env->user_data);
			//clear the session variables
			$_SESSION = [];
			session_destroy();
			
			exit(page_renderer::render_main("Logout Successful", "<h1>Logout Successful</h1>
		<p>Logout Successful. You can login again <a href='index.php?action=login'>here</a>.</p>"));
		});
	}
]);

?>
