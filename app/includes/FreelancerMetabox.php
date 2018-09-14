<?php
namespace cntesttask;

use \Exception;

class FreelancerMetabox {

  public static $instance = null;

  public static function init()
  {
    if (!self::$instance || self::$instance == null) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  public function __construct()
  {
    if ( is_admin() ) {
      add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
      add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
    }
  }

  public function init_metabox() {
    add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );
    add_action( 'save_post',      array( $this, 'save_metabox' ), 10, 2 );
  }

  public function add_metabox() {
    add_meta_box(
      'freelancers-list',
      __( 'Freelancers', 'cn' ),
      array( $this, 'render_metabox' ),
      'task',
      'side',
      'default'
    );
  }

  public function render_metabox($post)
  {
    wp_nonce_field( 'custom_nonce_action', 'custom_nonce' );
    $list = Freelancer::selectArray();
    $current = get_post_meta($post->ID, '_freelancer_id', true);
    $selected = "";
    ?>

    <select name="_freelancer_id" id="freelancers-list">
      <?php foreach ($list as $id => $name) {
        $selected = $current == $id?"selected='selected'":'';
      ?>
      <option value="<?php print $id ?>" <?php print $selected; ?>><?php print $name ?></option>
      <?php
      } ?>
    </select>

    <?php
  }

  public function save_metabox($post_id, $post)
  {
    $nonce_name   = isset( $_POST['custom_nonce'] ) ? $_POST['custom_nonce'] : '';
    $nonce_action = 'custom_nonce_action';

    // Check if nonce is set.
    if ( ! isset( $nonce_name ) ) {
      return;
    }

    // Check if nonce is valid.
    if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
      return;
    }

    // Check if user has permissions to save data.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
    }

    // Check if not an autosave.
    if ( wp_is_post_autosave( $post_id ) ) {
      return;
    }

    // Check if not a revision.
    if ( wp_is_post_revision( $post_id ) ) {
      return;
    }

    if ('task' == $_POST['post_type']) {
      if (isset($_POST['_freelancer_id']) && $_POST['_freelancer_id']) {
        update_post_meta($post_id, '_freelancer_id', (int) $_POST['_freelancer_id']);
      } else {
        delete_post_meta($post_id, '_freelancer_id');
      }
    }

  }
}
