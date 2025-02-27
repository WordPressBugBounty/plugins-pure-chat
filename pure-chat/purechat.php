<?php
/**
 * @package Pure_Chat
 * @version 2.41
 */
/*
Plugin Name: Pure Chat - Live Chat & More!
Plugin URI:
Description: Website chat, simplified. Love purechat? Spread the word! <a href="https://wordpress.org/support/view/plugin-reviews/pure-chat">Click here to review the plugin!</a>
Author: Pure Chat by Ruby
Version: 2.41
Author URI: https://purechat.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tested up to: 6.7
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include 'variables.php';

class Pure_Chat_Plugin {
	var $version = 5;

	public static function activate()	{
		Pure_Chat_Plugin::clear_cache();
	}

	public static function deactivate()	{
		Pure_Chat_Plugin::clear_cache();
	}



	function __construct() {
		add_action('wp_footer', array( &$this, 'pure_chat_load_snippet') );

		add_action('admin_menu', array( &$this, 'pure_chat_menu' ) );
		add_action('wp_ajax_pure_chat_update', array( &$this, 'pure_chat_update' ) );
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
		$this->update_plugin();
	}

	function update_plugin() {
		update_option('purechat_plugin_ver', $this->version);
	}

	function pure_chat_menu() {
		add_menu_page('Pure Chat', 'Pure Chat', 'manage_options', 'purechat-menu', array( &$this, 'pure_chat_generateAcctPage' ), esc_url(plugins_url().'/pure-chat/favicon.ico'));
	}
	function enqueue_admin_styles($hook) {
        // Only enqueue the stylesheet on the Pure Chat admin page
        if ($hook != 'toplevel_page_purechat-menu') {
            return;
        }
        wp_enqueue_style('purechat-styles', esc_url(plugins_url('/pure-chat/purechatStyles.css')), array(), $this->version);
    }

	function pure_chat_update() {
		if (!current_user_can('manage_options')) {
			return;
		}

		if (!isset($_POST['purechatwid'])) {
			error_log('Required purechatwid is missing thus we return.');
			return;
		}
		if (!isset($_POST['purechatwname'])) {
			error_log('Optional purechatwname is missing.');
		}
	
		if (!isset($_POST['pure_chat_nonce'])) {
			error_log('Nonce (pure_chat_nonce) field is missing.');
			return;
		}
        $pure_chat_nonce = sanitize_text_field(wp_unslash($_POST['pure_chat_nonce']));

		if (!wp_verify_nonce($pure_chat_nonce, 'pure_chat_update_nonce')) {
			error_log('Nonce verification failed.');
			return;
		}
		$purechatwid = sanitize_text_field(wp_unslash($_POST['purechatwid']));
        $purechatwname = isset($_POST['purechatwname']) ? sanitize_text_field(wp_unslash($_POST['purechatwname'])) : '';

		if(isset($_POST['action']) && $_POST['action'] == 'pure_chat_update' && strlen($purechatwid) == 36)
		{
			update_option('purechat_widget_code', $purechatwid);
			if (isset($_POST['purechatwname'])) {
				update_option('purechat_widget_name', $purechatwname);
			}
		}
	}

	function pure_chat_load_snippet() {
		global $current_user;
		if(esc_js(get_option('purechat_widget_code')))
		{
			echo sprintf("<script type='text/javascript' data-cfasync='false'>window.purechatApi = { l: [], t: [], on: function () { this.l.push(arguments); } }; (function () { var done = false; var script = document.createElement('script'); script.async = true; script.type = 'text/javascript'; script.src = 'https://app.purechat.com/VisitorWidget/WidgetScript'; document.getElementsByTagName('HEAD').item(0).appendChild(script); script.onreadystatechange = script.onload = function (e) { if (!done && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete')) { var w = new PCWidget({c: '%s', f: true }); done = true; } }; })();</script>", esc_js(get_option('purechat_widget_code')));
		}
		else
		{
			echo("<!-- Please select a widget in the wordpress plugin to activate purechat -->");
		}
	}

	private static function clear_cache() {
		if (function_exists('wp_cache_clear_cache')) {
			wp_cache_clear_cache();
		}
	}

	function pure_chat_generateAcctPage() {
		global $purechatHome;
		?>
		<head></head>
		<?php
		if (isset($_POST['purechatwid']) && isset($_POST['purechatwname'])) {
			error_log('inside pure_chat_generateAcctPage');
			check_admin_referer('pure_chat_update_nonce', 'pure_chat_nonce');
    		$this->pure_chat_update();
		}
		?>
		<p>
		<div class="purechatbuttonbox">
			<img src="<?php echo esc_url(plugins_url('/pure-chat/logo.png')); ?>" alt="Pure Chat logo">
			<div class = "purechatcontentdiv">
				<?php
				if (esc_js(get_option('purechat_widget_code') == '' )) {
					?>
					<p>Pure Chat allows you to chat in real time with visitors to your WordPress site. Click the button below to get started by logging in to Pure Chat and selecting a chat widget!</p>
					<p>The button will open a widget selector in an external page. Keep in mind that your Pure Chat account is separate from your WordPress account.</p>
				<?php
				} else {
				?>
					<h4>Your current chat widget is:</h4>
					<h1 class="purechatCurrentWidgetName"><?php echo esc_html(get_option('purechat_widget_name')); ?></h1>
					Widget ID:<h3 class="purechatCurrentWidgetCode"><?php echo esc_html(get_option('purechat_widget_code')); ?></h3>
					<p>Would you like to switch widgets?</p>
				<?php
				}
				?>
			</div>
			<form method="post" action="" id="pureChatForm">
				<?php wp_nonce_field('pure_chat_update_nonce', 'pure_chat_nonce'); ?>
				<input type="hidden" name="action" value="pure_chat_update">
				<input type="button" class="purechatbutton" value="Pick a widget!" onclick="openPureChatChildWindow()">
			</form>
			<p>
		</div>
		<script>
			var pureChatChildWindow;
			var purechatNameToPass = "<?php echo esc_js(get_option('purechat_widget_name')); ?>";
			var purechatIdToPass = "<?php echo esc_js(get_option('purechat_widget_code')); ?>";
			var purechatNonce = '<?php echo esc_js(wp_create_nonce('pure_chat_update_nonce')); ?>';
			function openPureChatChildWindow() {
				pureChatChildWindow = window.open('<?php echo esc_url($purechatHome); ?>/home/pagechoicewordpress?widForDisplay=' + purechatIdToPass +
                                      '&nameForDisplay=' + purechatNameToPass, 'Pure Chat');
			}
			var url = ajaxurl;
			window.addEventListener('message', function(event) {
				 // Add origin check
				 var purechatHome = <?php echo json_encode(esc_url($purechatHome)); ?>;
				 console.log('purechatHome origin: ' + purechatHome);
				if (event.origin != purechatHome) {
					console.log('Untrusted origin: ' + event.origin);
					return;
				}
				var purechatWidgetName= event?.data.name ? event?.data?.name : '';
				var purechatWidgetId= event.data.id;
				var data = {
					'action': 'pure_chat_update',
					'purechatwid': purechatWidgetId,
					'purechatwname': purechatWidgetName,
					'pure_chat_nonce': purechatNonce
				};
				if (!purechatWidgetId) {
					console.log("We escaped ajax post call because event data is is", purechatWidgetId )
					return
				}
				jQuery.post(url, data).done(function(){
					console.log("ajax call was successful with data", url, data)
					location.reload(); // we refresh the page that that the new selected widget information is displayed
				}).fail(function(){
					console.log("ajax call failed")
				})
			}, false);
		</script>
		<div class="purechatlinkbox">
			<p><a href="https://app.purechat.com/user/dashboard" target="_blank">Your Pure Chat dashboard page</a> is your place to answer chats, add more widgets, customize their appearance with images and text, manage users, and more!</p>
		</div>
		<?php
	}
}

register_activation_hook(__FILE__, array('Pure_Chat_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Pure_Chat_Plugin', 'deactivate'));

new Pure_Chat_Plugin();
?>
