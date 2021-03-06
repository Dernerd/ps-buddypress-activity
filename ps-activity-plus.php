<?php
/**
 * Plugin Name: PS BuddyPress Activity 
 * Plugin URI: https://n3rds.work/piestingtal_source/ps-buddypress-activity/
 * Description: Eine Verbesserung der Medienfreigabe im Facebook-Stil für die BuddyPress Aktivitätsbox.
 * Version: 1.0.2
 * Author: WMS N@W
 * Author URI: https://n3rds.work
 *
 * Text Domain: ps-activity-plus
 * Domain Path: /languages
 * License:     GPLv2 or later (license.txt)
 *
 * @package BuddyPress_Activity_Plus_reloaded
 */


/*
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require 'lib/external/plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://n3rds.work//wp-update-server/?action=get_metadata&slug=ps-buddypress-activity', 
	__FILE__, 
	'buddypress-activity' 
);

// For backward compatibility, we are not renaming the constants.
define( 'BPFB_PLUGIN_SELF_DIRNAME', basename( dirname( __FILE__ ) ) );
define( 'BPFB_PROTOCOL', ( is_ssl() ? 'https://' : 'http://' ) );

define( 'BPFB_PLUGIN_BASE_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'BPFB_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

load_plugin_textdomain( 'ps-activity-plus', false, BPFB_PLUGIN_SELF_DIRNAME . '/languages/' );

// Override image limit in wp-config.php.
if ( ! defined( 'BPFB_IMAGE_LIMIT' ) ) {
	define( 'BPFB_IMAGE_LIMIT', 5 );
}


$wp_upload_dir = wp_upload_dir();
define( 'BPFB_TEMP_IMAGE_DIR', $wp_upload_dir['basedir'] . '/bpfb/tmp/' );
define( 'BPFB_TEMP_IMAGE_URL', $wp_upload_dir['baseurl'] . '/bpfb/tmp/' );
define( 'BPFB_BASE_IMAGE_DIR', $wp_upload_dir['basedir'] . '/bpfb/' );
define( 'BPFB_BASE_IMAGE_URL', $wp_upload_dir['baseurl'] . '/bpfb/' );

/**
 * Helper.
 *
 * @property-read string                $path absolute path to the plugin directory.
 * @property-read string                $url absolute url to the plugin directory.
 * @property-read string                $basename plugin base name.
 * @property-read string                $version plugin version.
 */
class BPAPR_Activity_Plus_Reloaded {

	/**
	 * Plugin Version.
	 *
	 * @var string
	 */
	private $version = '1.0.2';

	/**
	 * Class instance
	 *
	 * @var static
	 */
	private static $instance = null;

	/**
	 * Plugins directory path
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Plugins directory url
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Plugin Basename.
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Protected properties. These properties are inaccessible via magic method.
	 *
	 * @var array
	 */
	private static $protected = array( 'instance' );

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->bootstrap();
	}

	/**
	 * Get class instance
	 *
	 * @return BPAPR_Activity_Plus_Reloaded
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstrap the core.
	 */
	private function bootstrap() {
		// Setup general properties.
		$this->path     = plugin_dir_path( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );

		// Only fire off if BP is actually loaded.
		add_action( 'bp_loaded', array( $this, 'load' ) );

		add_action( 'bp_loaded', array( $this, 'setup_constants' ) );
		add_action( 'bp_loaded', array( $this, 'setup' ) );

		require_once BPFB_PLUGIN_BASE_DIR . '/src/installer/class-bpapr-installer.php';
		register_activation_hook( __FILE__, array( 'BPAPR_Installer', 'install' ) );
	}

	/**
	 * Load dependencies.
	 */
	public function load() {

		require_once BPFB_PLUGIN_BASE_DIR . '/src/core/class-bpapr-data-container.php';
		require_once BPFB_PLUGIN_BASE_DIR . '/src/core/class-bpapr-data.php';
		require_once BPFB_PLUGIN_BASE_DIR . '/src/core/bpapr-functions.php';
		require_once BPFB_PLUGIN_BASE_DIR . '/src/core/bpapr-back-compat.php';

		require_once BPFB_PLUGIN_BASE_DIR . '/src/bootstrap/class-bpapr-assets-loader.php';

		require_once BPFB_PLUGIN_BASE_DIR . '/src/handlers/class-bpapr-activity-update-handler.php';
		require_once BPFB_PLUGIN_BASE_DIR . '/src/handlers/class-bpapr-preview-handler.php';
		require_once BPFB_PLUGIN_BASE_DIR . '/src/handlers/class-bpapr-delete-handler.php';

		require_once BPFB_PLUGIN_BASE_DIR . '/src/shortcodes/class-bpapr-shortcodes.php';

		// Group Documents integration.
		if ( defined( 'BP_GROUP_DOCUMENTS_IS_INSTALLED' ) && BP_GROUP_DOCUMENTS_IS_INSTALLED ) {
			// require_once BPFB_PLUGIN_BASE_DIR . '/lib/class-bpapr-group-documents.php';
		}

		if ( is_admin() ) {
			require_once BPFB_PLUGIN_BASE_DIR . '/src/admin/class-bpapr-admin.php';
			BPAPR_Admin::boot();
		}

		do_action( 'bpapr_loaded' );
	}

	/**
	 * Setup constants.
	 */
	public function setup_constants() {
		if ( ! defined( 'BPFB_LINKS_TARGET' ) ) {
			define( 'BPFB_LINKS_TARGET', BPAPR_Data::get( 'link_target', 'same' ) );
		}
	}

	/**
	 * Load plugin core files and assets.
	 */
	public function setup() {
		BPAPR_Preview_Handler::boot();
		BPAPR_Activity_Update_Handler::boot();
		BPAPR_Delete_Handler::boot();

		BPAPR_Assets_Loader::boot();
		BPAPR_Shortcodes::register();
	}

	/**
	 * On activation create table
	 */
	public function on_activation() {

	}

	/**
	 * Deactivation.
	 */
	public function on_deactivation() {
	}

	/**
	 * Magic method for accessing property as readonly(It's a lie, references can be updated).
	 *
	 * @param string $name property name.
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {

		if ( ! in_array( $name, self::$protected, true ) && property_exists( $this, $name ) ) {
			return $this->{$name};
		}

		return null;
	}
}

/**
 * Helper.
 *
 * @return BPAPR_Activity_Plus_reloaded
 */
function bpapr_activity_plus_reloaded() {
	return BPAPR_Activity_Plus_Reloaded::get_instance();
}

bpapr_activity_plus_reloaded();
