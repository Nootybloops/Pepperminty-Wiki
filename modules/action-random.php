<?php
register_module([
	"name" => "Random Page",
	"version" => "0.1",
	"author" => "Starbeamrainbowlabs",
	"description" => "Adds an action called 'random' that redirects you to a random page.",
	"id" => "action-random",
	"code" => function() {
		global $settings;
		/**
		 * @api {get} ?action=random Redirects to a random page.
		 * @apiName RawSource
		 * @apiGroup Page
		 * @apiPermission Anonymous
		 */
		
		add_action("random", function() {
			global $pageindex;
			
			$pageNames = array_keys(get_object_vars($pageindex));
			$randomPageName = $pageNames[array_rand($pageNames)];
			
			http_response_code(307);
			header("location: ?page=" . rawurlencode($randomPageName));
		});
		
		add_help_section("26-random-redirect", "Jumping to a random page", "<p>$settings->sitename has a function that can send you to a random page. To use it, click <a href='?action=random'>here</a>. $settings->admindetails_name ($settings->sitename's adminstrator) may have added it to one of the menus.</p>");
	}
]);

?>
