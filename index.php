<?php
/*
Plugin Name: MF AI
Plugin URI: https://github.com/frostkom/mf_ai
Description: Integrate an AI search on your site or get help from AI to craft a titel, exerpt and content for each page
Version: 1.2.0
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_ai
Domain Path: /lang

Requires Plugins: meta-box
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_ai = new mf_ai();

	add_action('enqueue_block_editor_assets', array($obj_ai, 'enqueue_block_editor_assets'));
	add_action('init', array($obj_ai, 'init'));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_ai');

		add_action('admin_init', array($obj_ai, 'settings_ai'));
		add_filter('pre_update_option', array($obj_ai, 'pre_update_option'), 10, 3);

		add_action('rwmb_meta_boxes', array($obj_ai, 'rwmb_meta_boxes'));
	}

	if(wp_doing_ajax())
	{
		add_action('wp_ajax_api_ai_init', array($obj_ai, 'api_ai_init'));
		add_action('wp_ajax_nopriv_api_ai_init', array($obj_ai, 'api_ai_init'));

		add_action('wp_ajax_api_ai_run', array($obj_ai, 'api_ai_run'));
		add_action('wp_ajax_nopriv_api_ai_run', array($obj_ai, 'api_ai_run'));

		add_action('wp_ajax_api_ai_suggestion', array($obj_ai, 'api_ai_suggestion'));
	}

	function uninstall_ai()
	{
		include_once("include/classes.php");

		$obj_ai = new mf_ai();

		mf_uninstall_plugin(array(
			'post_types' => array($obj_ai->post_type),
			'options' => array('setting_ai_mistral_api_key', 'setting_ai_run_query'),
		));
	}
}