<?php

class mf_ai
{
	var $post_type = __CLASS__;
	var $meta_prefix;

	function __construct()
	{
		$this->meta_prefix = $this->post_type.'_';
	}

	function block_render_callback($attributes)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);

		mf_enqueue_style('style_ai', $plugin_include_url."style.css");
		mf_enqueue_script('script_ai', $plugin_include_url."script.js", array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'loading_animation' => apply_filters('get_loading_animation', ''),
		));

		$out = "<div".parse_block_attributes(array('class' => "widget ai", 'attributes' => $attributes)).">
			<form".apply_filters('get_form_attr', "").">
				<p class='api_ai_run'></p>
				<div class='textarea_with_button'>"
					.show_textarea(array('name' => 'setting_ai_run_query', 'placeholder' => __("Ask me anything", 'lang_ai')))
					."<button type='button' name='btnAIRun'>
						<svg width='20' height='20' fill='currentColor'><path d='M2 10l16-6-6 16-2-7z'/></svg>
					</button>
				</div>
			</form>
		</div>";

		return $out;
	}

	function enqueue_block_editor_assets()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		wp_register_script('script_ai_block_wp', $plugin_include_url."block/script_wp.js", array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-block-editor'), $plugin_version, true);

		wp_localize_script('script_ai_block_wp', 'script_ai_block_wp', array(
			'block_title' => __("AI", 'lang_ai'),
			'block_description' => __("Display AI", 'lang_ai'),
		));
	}

	function init()
	{
		load_plugin_textdomain('lang_ai', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => __("AI Queries", 'lang_ai'),
				'singular_name' => __("AI Query", 'lang_ai'),
				'menu_name' => __("AI Queries", 'lang_ai'),
				'all_items' => __("List", 'lang_ai'),
				'edit_item' => __("Edit", 'lang_ai'),
				'view_item' => __("View", 'lang_ai'),
				'add_new_item' => __("Add New", 'lang_ai'),
			),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'menu_icon' => 'dashicons-superhero',
			'supports' => array('title', 'editor'),
			'hierarchical' => true,
			'has_archive' => false,
		));

		register_block_type('mf/ai', array(
			'editor_script' => 'script_ai_block_wp',
			'editor_style' => 'style_base_block_wp',
			'render_callback' => array($this, 'block_render_callback'),
		));
	}

	function settings_ai()
	{
		$options_area_orig = $options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = [];

		$arr_settings['setting_ai_mistral_api_key'] = __("API Key", 'lang_ai');

		if(get_option('setting_ai_mistral_api_key') != '')
		{
			$arr_settings['setting_ai_run_query'] = __("", 'lang_ai');
		}

		else
		{
			delete_option('setting_ai_run_query');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function pre_update_option($new_value, $option_key, $old_value)
	{
		if($new_value != '')
		{
			switch($option_key)
			{
				case 'setting_ai_mistral_api_key':
					$obj_encryption = new mf_encryption(__CLASS__);
					$new_value = $obj_encryption->encrypt($new_value, md5(AUTH_KEY));
				break;
			}
		}

		return $new_value;
	}

	function settings_ai_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("AI", 'lang_ai'));
	}

		function setting_ai_mistral_api_key_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$obj_encryption = new mf_encryption(__CLASS__);
			$option = $obj_encryption->decrypt($option, md5(AUTH_KEY));

			echo show_password_field(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='new-password'", 'description' => "<a href='https://console.mistral.ai/home'>".__("Get your Mistral API Key", 'lang_ai')."</a>"));
		}

		function setting_ai_run_query_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);

			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_script('script_ai', $plugin_include_url."script.js", array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'loading_animation' => apply_filters('get_loading_animation', ''),
			));

			echo "<p class='api_ai_run'></p>"
			.show_textarea(array('name' => $setting_key, 'placeholder' => __("Ask me anything", 'lang_ai')))
			.show_button(array('type' => 'button', 'name' => 'btnAIRun', 'text' => __("Run", 'lang_ai'), 'class' => 'button-secondary'));
		}

	function get_vendor_api_key()
	{
		$api_key = $api_vendor = "";

		$setting_ai_mistral_api_key = get_option('setting_ai_mistral_api_key');

		if($setting_ai_mistral_api_key != '')
		{
			$api_key = $setting_ai_mistral_api_key;
			$api_vendor = 'mistral';
		}

		return array($api_key, $api_vendor);
	}

	function meta_excerpt()
	{
		global $post;

		$out = "";

		$post_id = $post->ID;

		if($post_id > 0)
		{
			list($api_key, $api_vendor) = $this->get_vendor_api_key();

			if($api_key != '')
			{
				$plugin_include_url = plugin_dir_url(__FILE__);
				mf_enqueue_script('script_ai_page', $plugin_include_url."script_page.js", array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'loading_animation' => apply_filters('get_loading_animation', ''),
				));

				$out .= "<div".apply_filters('get_form_attr', "", ['class' => ["mf_ai_page_form"]]).">
					<p>".__("I can help you create content for this page. By clicking the button below I will give you suggestions from the information you have already entered as title, content etc. Then you can decide if you want to use my suggestion.", 'lang_ai')."</p>"
					."<div".get_form_button_classes().">"
						.show_button(array('type' => 'button', 'text' => __("Get Suggestion Now", 'lang_ai')))
						.input_hidden(array('name' => 'ai_action', 'value' => 'api_ai_suggestion'))
						.input_hidden(array('name' => 'ai_post_id', 'value' => $post_id))
					."</div>"
				."</div>";
			}

			else
			{
				$out .= sprintf(__("There were no API keys. Go to %sSettings%s to save your API key.", 'lang_api'), "<a href='". admin_url("options-general.php?page=settings_mf_base#settings_ai")."'>", "</a>");
			}
		}

		return $out;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		$meta_boxes[] = array(
			'id' => $this->meta_prefix.'information',
			'title' => __("Get AI Help", 'lang_ai'),
			'post_types' => array('post', 'page'),
			'context' => 'normal',
			'priority' => 'low',
			'fields' => array(
				array(
					'id' => $this->meta_prefix.'excerpt',
					'type' => 'custom_html',
					'callback' => array($this, 'meta_excerpt'),
				),
			),
		);

		return $meta_boxes;
	}

	function api_ai_init()
	{
		$json_output = array(
			'success' => false,
		);

		if(is_user_logged_in())
		{
			$user_data = get_userdata(get_current_user_id());

			$json_output['success'] = true;
			$json_output['heading'] = sprintf(__("Hello %s", 'lang_ai'), $user_data->first_name);
			$json_output['content'] = __("What can I do for you today?", 'lang_ai');
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function call_api($json_output)
	{
		global $done_text, $error_text;

		list($api_key, $api_vendor) = $this->get_vendor_api_key();

		if($api_key != '')
		{
			$obj_encryption = new mf_encryption(__CLASS__);
			$api_key = $obj_encryption->decrypt($api_key, md5(AUTH_KEY));

			$curl_data = array(
				'url' => "https://api.mistral.ai/v1/chat/completions",
				'catch_head' => true,
				'headers' => [
					'Content-Type: application/json',
					'Authorization: Bearer '.$api_key,
				],
				'post_data' => json_encode([
					'model' => 'mistral-small-latest',
					'messages' => [
						['role' => 'user', 'content' => $json_output['query']]
					]
				]),
			);

			list($content, $headers) = get_url_content($curl_data);

			/*$url = "https://api.anthropic.com/v1/messages";
			$apiKey = "YOUR_API_KEY";

			$headers = [
				"x-api-key: $apiKey",
				"content-type: application/json",
				"anthropic-version: 2023-06-01"
			];

			$data = [
				"model" => "claude-3-opus-20240229", // Or another available Claude model
				"max_tokens" => 256,
				"messages" => [
					[
						"role" => "user",
						"content" => "Explain the difference between PHP and Python."
					]
				]
			];

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);

			echo $response;

			#####################

			$url = "https://api.openai.com/v1/chat/completions";
			$apiKey = "YOUR_OPENAI_API_KEY";

			$headers = [
				"Authorization: Bearer $apiKey",
				"Content-Type: application/json"
			];

			$data = [
				"model" => "gpt-3.5-turbo", // Or another available ChatGPT model
				"messages" => [
					[
						"role" => "user",
						"content" => "Explain the difference between PHP and Python."
					]
				],
				"max_tokens" => 256
			];

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);

			// Decode and print the assistant's reply
			$arr_json = json_decode($response, true);
			echo $arr_json['choices'][0]['message']['content'];

			##################################

			$url = "https://api.perplexity.ai/chat/completions";
			$apiKey = "YOUR_API_KEY";

			$headers = [
				"Authorization: Bearer $apiKey",
				"Content-Type: application/json",
				"Accept: application/json"
			];

			$data = [
				"model" => "mistral-7b-instruct", // Or try "llama-13b-chat", "codellama-34b-instruct", etc.
				"stream" => false,
				"max_tokens" => 256,
				"messages" => [
					[
						"role" => "system",
						"content" => "Be precise and concise in your responses."
					],
					[
						"role" => "user",
						"content" => "How many stars are there in our galaxy?"
					]
				]
			];

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);

			// Decode and print the model's reply
			$arr_json = json_decode($response, true);
			echo $arr_json['choices'][0]['message']['content'];*/

			switch($headers['http_code'])
			{
				case 200:
				case 201:
					$arr_json = json_decode($content, true);

					if(isset($arr_json['choices'][0]['message']['content']) && $arr_json['choices'][0]['message']['content'] != '')
					{
						$json_output['success'] = true;
						$json_output['html'] = nl2br($arr_json['choices'][0]['message']['content']);

						//{"id":"[id]","object":"chat.completion","created":1747992080,"model":"mistral-small-latest","choices":[{"index":0,"message":{"role":"assistant","tool_calls":null,"content":""},"finish_reason":"stop","logprobs":null}],"usage":{"prompt_tokens":10,"total_tokens":24,"completion_tokens":14}}

						$post_data = array(
							'post_type' => $this->post_type,
							'post_title' => $json_output['query'],
							'post_content' => $arr_json['choices'][0]['message']['content'],
							'post_status' => 'publish',
							'meta_input' => apply_filters('filter_meta_input', array(
								$this->meta_prefix.'api_vendor' => $api_vendor,
								$this->meta_prefix.'http_code' => $headers['http_code'],
								$this->meta_prefix.'id' => $arr_json['id'],
								$this->meta_prefix.'response' => $content,
							)),
						);

						wp_insert_post($post_data);
					}

					else
					{
						do_log(__FUNCTION__.":".__LINE__.": ".var_export($curl_data, true)." -> ".var_export($headers, true)." + '".$content."'");

						$json_output['html'] = __("There was an unknown error. An administrator has been notified about this.", 'lang_ai');
					}
				break;

				default:
					if($content != '')
					{
						$json_output['html'] = $content;
					}

					else
					{
						do_log(__FUNCTION__.":".__LINE__.": ".var_export($curl_data, true)." -> ".var_export($headers, true)." + '".$content."'");

						$json_output['html'] = __("There was an unknown error. An administrator has been notified about this.", 'lang_ai');
					}
				break;
			}
		}

		else
		{
			$json_output['html'] = __("There were no API keys", 'lang_api');
		}

		return $json_output;
	}

	function api_ai_run()
	{
		$json_output = array(
			'success' => false,
		);

		$json_output['query'] = check_var('query');

		if(is_user_logged_in())
		{
			$json_output = $this->call_api($json_output);
		}

		else
		{
			$json_output['html'] = "<div class='wp-block-button aligncenter'>
				<a href='".wp_login_url()."?redirect_to=".$_SERVER['REQUEST_URI']."' class='wp-block-button__link'>".__("Log in to display this", 'lang_ai')."</a>
			</div>";
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}

	function api_ai_suggestion()
	{
		global $wpdb;

		$json_output = array(
			'success' => false,
		);

		$post_id = check_var('post_id', 'int');

		$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_excerpt, post_content FROM ".$wpdb->posts." WHERE ID = '%d' AND (post_excerpt != '' OR post_content != '')", $post_id));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$post_title = $r->post_title;
				$post_excerpt = $r->post_excerpt;
				$post_content = $r->post_content;

				$json_output['query'] = sprintf(__("Can you give me examples for page title and excerpt? The title should be between %d and %d characters including all characters and spaces. The excerpt should be between %d and %d characters including all characters and spaces. Can you also give me search word examples that could be useful to incorporate in the title, excerpt or content? The page currently have the title '%s', excerpt '%s' and body text '%s'.", 'lang_ai'), 15, 70, 100, 180, $post_title, $post_excerpt, $post_content);

				$json_output = $this->call_api($json_output);
			}
		}

		else
		{
			$json_output['html'] = __("The page that you want suggestions for is missing excerpt and content. For me to give you good recommendations there has to be some text content on the page.", 'lang_ai');
		}

		header('Content-Type: application/json');
		echo json_encode($json_output);
		die();
	}
}