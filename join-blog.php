<?php
/**
 * Plugin Name: Join Blog Widget
 * Version: 1.0.2
 * Plugin URI: https://buddydev.com/plugins/join-blog-widget/
 * Author: BuddyDev
 * Author URI: https://buddydev.com
 * Description: Allow your users to join a sub blog on a multisite blog network. You can select the roles for the user/message to be shown.
 * License: GPL
 */

// Do not allow to access the file directly over web.
if( ! defined( 'ABSPATH' ) ) {
    exit( 0 );
}

/**
 * Special Note: I created this plugin to be used with My other plugin BuddyPress Multi Network(https://buddydev.com/plugins/buddypress-multi-network/).
 * It will enable site admins to show a widget and allow users to join their network.
 * You can use it for other purposes as you want.
 */
class BPDevJoinBlogWidget extends WP_Widget {

	public function __construct( $id_base = false, $name = false, $widget_options = array(), $control_options = array() ) {

	    if ( ! $name ) {
			$name = __( 'Join Blog Widget' );
		}

		parent::__construct( $id_base, $name, $widget_options, $control_options );
		//I know I am burdening the widget to handle ajax request, May be a bad standard of coding but suits much better in this situation where data varies per widget
		//hopefully, I will put it in helper in next release and use global $wp_registered_widgets
		add_action( 'wp_ajax_join_blog', array( $this, 'add_user' ) );
	}

	public function widget( $args, $instance ) {

		$user_id = get_current_user_id();
		$blog_id = get_current_blog_id();

		// if the user is not logged in or the user is already a member of the blog, do not show this widget.
		if ( empty( $user_id ) || is_user_member_of_blog( $user_id, $blog_id ) ) {
			return false;
		}

		// display the widget with button to ask joining.
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}

		echo "<a data-id='{$this->id}' class='bpdev-join-blog' href='" . get_blogaddress_by_id( $blog_id ) . "?action=join-blog&_wpnonce=" . wp_create_nonce( 'join-blog-' . $this->id ) . "'>" . $instance['button_text'] . "</a>";
		echo $args['after_widget'];

	}

	/**
     * Update widget settings
     *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                    = $old_instance;
		$instance['title']           = $new_instance['title'];
		$instance['button_text']     = $new_instance['button_text'];
		$instance['message_success'] = $new_instance['message_success'];
		$instance['message_error']   = $new_instance['message_error'];
		$instance['role']            = $new_instance['role'];

		return $instance;
	}

	/**
     * Display widget settings form
	 */
	public function form( $instance ) {
		$default = array( 'title'           => _x( 'Join Blog', 'Widget title', 'join-blog-widget' ),
		                  'role'            => 'subscriber',
		                  'button_text'     => _x( 'Join this Blog', 'button label', 'join-blog-widget' ),
		                  'message_success' => _x( 'You have successfully joined this blog.', 'success message on joining', 'join-blog-widget' ),
		                  'message_error'   => _x( 'There was a problem joining this blog. Please try again later.', 'Failure message for unable to join', 'join-blog-widget' )
		);
		$args    = wp_parse_args( (array) $instance, $default );
		?>
        <p>
            <label for="<?php $this->get_field_id( 'title' ); ?>"><?php _e( 'Title' ); ?><br/>
                <input type="text" id="<?php $this->get_field_id( 'title' ); ?>"
                       name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $args['title'] ); ?>" class="widefat"/>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id( 'role' ); ?>"><?php _e( 'Role' ); ?><br/>
				<?php $this->print_role_dd( $args['role'] ); ?>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id( 'button_text' ); ?>"><?php _e( 'Join Button Label' ); ?><br/>
                <input type="text" id="<?php $this->get_field_id( 'button_text' ); ?>"
                       name="<?php echo $this->get_field_name( 'button_text' ); ?>"
                       value="<?php echo $args['button_text']; ?>" class="widefat"/>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id( 'message_success' ); ?>"> <?php _e( 'Message on Successful Joining' ); ?>
                <textarea id="<?php $this->get_field_id( 'message_success' ); ?>"
                          name="<?php echo $this->get_field_name( 'message_success' ); ?>" class="widefat"><?php echo $args['message_success']; ?></textarea>
            </label>
        </p>
        <p>
            <label for="<?php $this->get_field_id( 'message_error' ); ?>"> <?php _e( 'Error Message' ); ?>
                <textarea id="<?php $this->get_field_id( 'message_error' ); ?>"
                          name="<?php echo $this->get_field_name( 'message_error' ); ?>" class="widefat"><?php echo $args['message_error']; ?></textarea>
            </label>
        </p>


	<?php }


	/**
     * Add user to blog via ajax
     */
	public function add_user() {
		//nonce check ?

		$user_id = get_current_user_id();
		$blog_id = get_current_blog_id();

		$option = get_option( $this->option_name );
		$id     = $_POST['widget-id'];//get the widget which sent this request

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'join-blog-' . $id ) ) {
			wp_die( 'Sorry, please try again later!' );
		}


		//find the numeric id from this id
		$numeric_id = str_replace( $this->id_base . '-', '', $id );//remove base id to find the numeric id
		$numeric_id = absint( $numeric_id );

		$current_widget_option = $option[ $numeric_id ];//get the options for current widget
		$role                  = $current_widget_option['role'];

		//if the user is not logged in or the user is already a member of the blog, do not show this widget

		if ( empty( $user_id ) || is_user_member_of_blog( $user_id, $blog_id ) ) {
			return false;
		}

		if ( add_user_to_blog( $blog_id, $user_id, $role ) ) {
			echo $current_widget_option['message_success'];
		} else {
			echo $current_widget_option['message_error'];
		}
		exit( 0 );

	}

	// helper
	private function print_role_dd( $selected = 'subscriber' ) {

		?>
        <select name="<?php echo $this->get_field_name( 'role' ); ?>" id="<?php echo $this->get_field_id( 'role' ); ?>">
			<?php wp_dropdown_roles( $selected ); ?>
        </select>
		<?php
	}

}


/**
 * Register the widget
 */
function bpdev_register_join_blog_widget() {

	register_widget( 'BPDevJoinBlogWidget' );
}
add_action( 'widgets_init', 'bpdev_register_join_blog_widget' );


/**
 * Helper class
 */
class BPDevJoinBlogHelper {

	private static $instance;


	private function __construct() {

		add_action( 'wp_head', array( $this, 'ajax_url' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_js' ) );
	}


	public static function get_instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Load js
	 */
	public function load_js() {

		$plugin_path    = plugin_dir_url( __FILE__ );
		$plugin_js_path = $plugin_path . 'assets/join-blog.js';
		wp_enqueue_script( 'join-blog-widget', $plugin_js_path, array( 'jquery' ) );
	}


	/**
     * BuddyPress creates ajaxurl, no need to define ajaxurl then
     * If BuddyPress is not active, add ajaxurl in the head
     */
	public function ajax_url() {
		if ( function_exists( 'bp_is_active' ) ) {
			return;
		}
		?>
        <script type="text/javascript">
            var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' );?>";
        </script>

		<?php
	}
}

BPDevJoinBlogHelper::get_instance();

