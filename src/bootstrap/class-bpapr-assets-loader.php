<?php
/**
 * Assets Loader
 *
 * @package    BuddyPress Activity Plus Reloaded
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2019, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Assets Loader.
 */
class BPAPR_Assets_Loader {

	/**
	 * Data to be send as localized js.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Boot it.
	 */
	public static function boot() {
		$self = new self();
		add_action( 'bp_enqueue_scripts', array( $self, 'register' ) );
		add_action( 'bp_enqueue_scripts', array( $self, 'enqueue' ), 11 );
		add_action( 'wp_head', array( $self, 'load_extra_configs' ) );
	}

	/**
	 * Register assets.
	 */
	public function register() {
		$this->register_vendors();
		$this->register_core();
	}

	/**
	 * Load assets.
	 */
	public function enqueue() {
		if ( ! is_user_logged_in() || ! $this->needs_loading() ) {
			return;
		}

		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'ps-activity-plus' );

		wp_localize_script( 'ps-activity-plus', 'BPAPRJSData', $this->data );

		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'ps-activity-plus-uploader' );
		if ( ! current_theme_supports( 'bpfb_interface_style' ) ) {
			wp_enqueue_style( 'ps-activity-plus' ); // backward compatibility.
		}

		if ( ! current_theme_supports( 'bpfb_toolbar_icons' ) ) { // back compat.
			wp_enqueue_style( 'ps-activity-plus-toolbar' );
		}

		// back compat.
		do_action( 'bpfb_add_cssjs_hooks' );
	}

	/**
	 * Register vendor scripts.
	 */
	private function register_vendors() {

		$version = bpapr_activity_plus_reloaded()->version;

		wp_register_script( 'qq-file-uploader', BPFB_PLUGIN_URL . '/assets/js/external/fileuploader.js', array( 'jquery' ), $version );

	}

	/**
	 * Register core assets.
	 */
	private function register_core() {
		// @todo change later.
		$version = bpapr_activity_plus_reloaded()->version;

		wp_register_script( 'ps-activity-plus', BPFB_PLUGIN_URL . '/assets/js/ps-activity-plus.js', array( 'qq-file-uploader' ), $version );

		wp_register_style( 'ps-activity-plus-uploader', BPFB_PLUGIN_URL . '/assets/css/external/fileuploader.css', false, $version );
		wp_register_style( 'ps-activity-plus', BPFB_PLUGIN_URL . '/assets/css/ps-activity-plus.css', false, $version );
		wp_register_style( 'ps-activity-plus-toolbar', BPFB_PLUGIN_URL . '/assets/css/ps-activity-plus-toolbar.css', false, $version );


		$this->data = array(
			'add_photos_tip'           => __( 'Bilder hinzufügen', 'ps-activity-plus' ),
			'add_photos'               => __( 'Bilder-Beitrag senden', 'ps-activity-plus' ),
			'add_remote_image'         => __( 'Bild-URL hinzufügen', 'ps-activity-plus' ),
			'add_another_remote_image' => __( 'Füge weitere Bild-URL hinzu', 'ps-activity-plus' ),
			'add_videos'               => __( 'Videos hinzufügen', 'ps-activity-plus' ),
			'add_video'                => __( 'Video-Beitrag senden', 'ps-activity-plus' ),
			'add_links'                => __( 'Links hinzufügen', 'ps-activity-plus' ),
			'add_link'                 => __( 'Linkbeitrag senden', 'ps-activity-plus' ),
			'add'                      => __( 'Hinzufügen', 'ps-activity-plus' ),
			'cancel'                   => __( 'Abbrechen', 'ps-activity-plus' ),
			'preview'                  => __( 'Vorschau', 'ps-activity-plus' ),
			'drop_files'               => __( 'Ziehe Dateien hier hin, um sie hochzuladen', 'ps-activity-plus' ),
			'upload_file'              => __( 'Eine Datei hochladen', 'ps-activity-plus' ),
			'choose_thumbnail'         => __( 'Wähle eine Miniaturansicht', 'ps-activity-plus' ),
			'no_thumbnail'             => __( 'Keine Vorschau', 'ps-activity-plus' ),
			'paste_video_url'          => __( 'Füge hier die Video-URL ein', 'ps-activity-plus' ),
			'paste_link_url'           => __( 'Link hier einfügen', 'ps-activity-plus' ),
			'images_limit_exceeded'    => sprintf( __( "Du hast versucht, zu viele Bilder hinzuzufügen. Es wird nur %d veröffentlicht.", 'ps-activity-plus' ), BPFB_IMAGE_LIMIT ),
			// Variables
			'_max_images'              => BPFB_IMAGE_LIMIT,
			'isGroup'                  => bp_is_group() ? 1 : 0,
			'groupID'                  => bp_is_group() ? bp_get_current_group_id() : 0,
		);

	}

	/**
	 * Introduces `plugins_url()` and other significant URLs as root variables (global).
	 */
	public function load_extra_configs() {

		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		$data = apply_filters(
			'bpfb_js_data_object',
			array(
				'root_url'     => BPFB_PLUGIN_URL,
				'temp_img_url' => BPFB_TEMP_IMAGE_URL,
				'base_img_url' => BPFB_BASE_IMAGE_URL,
				'theme'        => BPAPR_Data::get( 'theme', 'default' ),
				'alignment'    => BPAPR_Data::get( 'alignment', 'left' ),
			)
		);
		printf( '<script type="text/javascript">var BPAPRConfig=%s;</script>', json_encode( $data ) );

		if ( 'default' === $data['theme'] || current_theme_supports( 'bpfb_toolbar_icons' ) ) {
			return;
		}


		$url = BPFB_PLUGIN_URL;
		?>
		<style type="text/css">
			@font-face {
				font-family: 'bpfb';
				src: url('<?php echo $url;?>/assets/css/external/font/bpfb.eot');
				src: url('<?php echo $url;?>/assets/css/external/font/bpfb.eot?#iefix') format('embedded-opentype'),
				url('<?php echo $url;?>/assets/css/external/font/bpfb.woff') format('woff'),
				url('<?php echo $url;?>/assets/css/external/font/bpfb.ttf') format('truetype'),
				url('<?php echo $url;?>/assets/css/external/font/bpfb.svg#icomoon') format('svg');
				font-weight: normal;
				font-style: normal;
			}
		</style>
		<?php
	}


	/**
	 * Load admin css.
	 */
	public function admin_enqueue_styles() {
	}

	/**
	 * Do we need to load.
	 *
	 * @return bool
	 */
	private function needs_loading() {
		$enabled = bp_is_activity_component() || bp_is_user_activity() || bp_is_group_activity();

		return apply_filters( 'bpfb_inject_dependencies', $enabled );
	}
}
