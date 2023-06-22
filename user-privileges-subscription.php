<?php
/*

Plugin Name: User Privileges Subscription
Plugin URI: https://peterwhart.com/
Version: 1.0.1
Description: User Privileges Subscription allow Editor role user to admin (acces, edit, delete) messages for form subscription in Elementor
Author: Flavius
Author URI: https://peterwhart.com/
License: MIT
License URI: https://peterwhart.com/
Text Domain: peterwhart.com
Domain Path: https://peterwhart.com/
*/
 


/**
licence to Flavius G
*/

// this is where to add the snippets below


if (! defined ('ABSPATH')){
	echo "Nothing here!";
	die;
}


// si de aici scriu o functie care i da drept de citire la un userrole 'Editor' in Elementor PRO - submission

if (!class_exists('MainClassUserEditor'))
{

    class MainClassUserEditor
    {
        /** 
         * See if this user is just an editor (if they have edit_posts but not manage_options).
         * If they have manage_options, they can see the Form Submissions page anyway.
         * @return boolean
         */
        static function userEditorFla()
        {
            return current_user_can('edit_posts') && !current_user_can('manage_options');
        }

        /**
         * This is called around line 849 of wp-includes/rest-api/class-wp-rest-server.php by the ajax request which loads the data
         * into the form submissions view for Elementor (see the add_menu_page below). The ajax request checks the user has
         * the manage_options permission in modules/forms/submissions/data/controller.php within the handler's permission_callback.
         * This overrides that, and also for the call to modules/forms/submissions/data/forms-controller.php (which fills the
         * Forms dropdown on the submissions page). By changing the $route check below, you could open up more pages to editors.
         * @param array [endpoints=>hanlders]
         * @return array [endpoints=>hanlders]
         */
        static function filterRestEndpoints($endpoints)
        {
            if (self::userEditorFla()) 
            {
                error_reporting(0); // there are a couple of PHP notices which prevent the Ajax JSON data from loading
                foreach($endpoints as $route=>$handlers) //for each endpoint
                    if (strpos($route, '/elementor/v1/form') === 0) //it is one of the elementor endpoints forms, form-submissions or form-submissions/export
                        foreach($handlers as $num=>$handler) //loop through the handlers
                            if (is_array ($handler) && isset ($handler['permission_callback'])) //if this handler has a permission_callback
                                $endpoints[$route][$num]['permission_callback'] = function($request){return true;}; //handler always returns true to grant permission
            }
            return $endpoints;
        }

        /**
         * Add the submissions page to the admin menu on the left for editors only, as administrators
         * can already see it.
         */
        static function addOptionsPageFla()
        {
            if (!self::userEditorFla()) return;
            add_menu_page('Submissions', 'Submissions', 'edit_posts', 'e-form-submissions',  function(){echo '<div id="e-form-submissions"></div>';}, 'dashicons-email-alt');
        }

        /**
         * Hook up the filter and action. I can't check if they are an editor here as the wp_user_can function
         * is not available yet.
         */
        static function hookIntoWordpress()
        {
            add_filter ('rest_endpoints', array('MainClassUserEditor', 'filterRestEndpoints'), 1, 3);
            add_action ('admin_menu', array('MainClassUserEditor', 'addOptionsPageFla'));
        }
    }

    MainClassUserEditor::hookIntoWordpress();
} //a wrapper to see if the class already exists or not
