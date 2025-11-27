<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www3.gobiernodecanarias.org/medusa/ecoescuela/ate/
 * @package    documentate
 * @subpackage Documentate/admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    documentate
 * @subpackage Documentate/admin
 * @author     Área de Tecnología Educativa <ate.educacion@gobiernodecanarias.org>
 */
class Documentate_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();
		add_filter( 'plugin_action_links_' . plugin_basename( DOCUMENTATE_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Add settings link to the plugins page.
	 *
	 * @param array $links The existing links.
	 * @return array The modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=documentate_settings' ) . '">' . __( 'Settings', 'documentate' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Load the required dependencies for this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-documentate-admin-settings.php';

		if ( ! has_action( 'admin_menu', array( 'Documentate_Admin_Settings', 'create_menu' ) ) ) {
			new Documentate_Admin_Settings();
		}
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( 'settings_page_documentate_settings' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/documentate-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'settings_page_documentate_settings' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/documentate-admin.js', array( 'jquery' ), $this->version, true );
	}

	/**
	 * Check if collaborative editing is enabled.
	 *
	 * @return bool
	 */
	public static function is_collaborative_enabled() {
		$options = get_option( 'documentate_settings', array() );
		return isset( $options['collaborative_enabled'] ) && '1' === $options['collaborative_enabled'];
	}

	/**
	 * Get collaborative editor settings.
	 *
	 * @return array
	 */
	public static function get_collaborative_settings() {
		$options = get_option( 'documentate_settings', array() );
		return array(
			'enabled'         => isset( $options['collaborative_enabled'] ) && '1' === $options['collaborative_enabled'],
			'signalingServer' => isset( $options['collaborative_signaling'] ) && '' !== $options['collaborative_signaling']
				? $options['collaborative_signaling']
				: 'wss://signaling.yjs.dev',
		);
	}

	/**
	 * Enqueue collaborative editor assets for document edit screens.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_collaborative_editor( $hook_suffix ) {
		// Only on post edit screens.
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		// Only for documentate_document post type.
		$screen = get_current_screen();
		if ( ! $screen || 'documentate_document' !== $screen->post_type ) {
			return;
		}

		// Only if collaborative editing is enabled.
		if ( ! self::is_collaborative_enabled() ) {
			return;
		}

		$settings = self::get_collaborative_settings();
		$post_id  = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;

		// Get current user info.
		$current_user = wp_get_current_user();
		$user_name    = $current_user->display_name ? $current_user->display_name : $current_user->user_login;

		// Enqueue styles.
		wp_enqueue_style(
			'documentate-collaborative-editor',
			plugin_dir_url( __FILE__ ) . 'css/documentate-collaborative-editor.css',
			array(),
			$this->version
		);

		// Enqueue script as module.
		wp_enqueue_script(
			'documentate-collaborative-editor',
			plugin_dir_url( __FILE__ ) . 'js/documentate-collaborative-editor.js',
			array(),
			$this->version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// Add module type to script.
		add_filter( 'script_loader_tag', array( $this, 'add_module_type_to_collaborative_script' ), 10, 3 );

		// Pass settings to JavaScript.
		wp_localize_script(
			'documentate-collaborative-editor',
			'documentateCollaborative',
			array(
				'postId'          => $post_id,
				'signalingServer' => $settings['signalingServer'],
				'userName'        => $user_name,
				'userId'          => $current_user->ID,
				'siteUrl'         => get_site_url(),
			)
		);
	}

	/**
	 * Add type="module" to collaborative editor script tag.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string Modified script tag.
	 */
	public function add_module_type_to_collaborative_script( $tag, $handle, $src ) {
		if ( 'documentate-collaborative-editor' === $handle ) {
			$tag = str_replace( '<script ', '<script type="module" ', $tag );
		}
		return $tag;
	}
}
