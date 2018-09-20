<?php
namespace cntesttask;

use \WP;
use \Exception;

class Test {
  private static $instance = null;
  private static $plugin_file;
  public static $app_path;
  public static $app_url;
  public static $route;

  private function __construct($plugin_file)
  {
    self::$plugin_file = $plugin_file;
    self::$app_path = dirname($plugin_file) . "/app";
    self::$app_url = plugin_dir_url($plugin_file) . "app";

    spl_autoload_register(array(&$this, 'autoloader'));

    $this->initRoute();
    $this->initActions();
    $this->initFilters();
  }

  private function initRoute()
  {
    $route = $_SERVER['REQUEST_URI'];
    $params =  $_SERVER['QUERY_STRING'];

    if ($params) {
      $route = str_replace ('?'.$params, '', $route);
    }

    $route = trim ($route, '/');

    self::$route = $route;
  }

  public static function init($plugin_file)
  {
    if (!self::$instance || self::$instance == null) {
      self::$instance = new self($plugin_file);
    }
    return self::$instance;
  }

  private function initActions()
  {
    add_action('init', array('\cntesttask\Freelancer', 'init'));
    add_action('init', array('\cntesttask\FreelancerMetabox', 'init'));

    add_action('wp_enqueue_scripts', array($this, 'loadFrontendScripts'));
    add_action('wp_enqueue_scripts', array($this, 'loadFrontendStyles'), 20);

    if (in_array(self::$route, array('tasks', 'dashboard'))) {
      add_action('wp_footer', array($this, 'renderTaskModal'));
    }

    add_action('wp_ajax_cn_add_task', array($this, 'ajax_cn_add_task'));
    add_action('wp_ajax_nopriv_cn_add_task', array($this, 'ajax_cn_add_task_nopriv'));
  }

  private function initFilters()
  {
    add_filter('cn_tasks_thead_cols', array($this, 'addColHead'));
    add_filter('cn_tasks_tbody_row_cols', array($this, 'addColRow'));

    add_filter('cn_menu', array($this, 'addMenuItem'));

    // For older versions
    add_filter('wp_title', array($this, 'routeTitles'), 20);
    // For WP 4.4 and newer
    add_filter('document_title_parts', array($this, 'routeTitles'), 20);

    add_shortcode('cn_dashboard', array($this, 'dashboardShortcode'));
  }

  public function loadFrontendScripts()
  {
    wp_enqueue_script(
      'cntesttask',
      self::$app_url . "/assets/js/cntesttask.js",
      array('jquery'),
      null,
      true
    );
    wp_enqueue_script(
      'datatables',
      self::$app_url . "/vendor/datatables/js/jquery.dataTables.min.js",
      array('jquery'),
      null,
      true
    );
    wp_enqueue_script(
      'datatables-bootstrap',
      self::$app_url . "/vendor/datatables/js/dataTables.bootstrap.min.js",
      array('datatables'),
      null,
      true
    );
  }

  public function loadFrontendStyles()
  {
    wp_enqueue_style(
      'datatables',
      self::$app_url . "/vendor/datatables/css/dataTables.bootstrap.css"
    );
    wp_enqueue_style(
      'datatables-responsive',
      self::$app_url . "/vendor/datatables/css/dataTables.responsive.css"
    );
  }

  public function renderTaskModal()
  {
    ?>
    <div class="modal fade modal-add-task" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
            <h4 class="modal-title" id="myLargeModalLabel">Add new task</h4>
          </div>
          <div class="modal-body">
            <form>
              <div class="form-group">
                <label for="task-title" class="control-label">Task title:</label>
                <input type="text" class="form-control" id="task-title">
              </div>
              <div class="form-group">
                <label for="freelancer_id" class="control-label">Freelancer:</label>
                <select class="form-control" id="freelancer_id">
                  <?php $list = Freelancer::selectFreeArray();
                    foreach ($list as $id=>$name) {
                      ?>
                      <option value="<?php print $id ?>"><?php print $name ?></option>
                      <?php
                    }
                  ?>
                </select>
              </div>
            </form>
            <p class="message alert"></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary js-add-task">Add</button>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  public function dashboardShortcode()
  {
    ob_start();
    ?>
    <div class="row">

      <div class="col-lg-3 col-md-6">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <div class="row">
              <div class="col-xs-3">
                <i class="fa fa-users fa-5x"></i>
              </div>
              <div class="col-xs-9 text-right">
                <div class="huge"><?php print $this->countPosts('freelancer'); ?></div>
                <div>Freelancers</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="panel panel-green">
          <div class="panel-heading">
            <div class="row">
              <div class="col-xs-3">
                <i class="fa fa-tasks fa-5x"></i>
              </div>
              <div class="col-xs-9 text-right">
                <div class="huge"><?php print $this->countPosts('task'); ?></div>
                <div>Tasks</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <?php
    $html = ob_get_clean();
    return $html;
  }

  private function countPosts($post_type)
  {
    $args = array(
      'post_type' => $post_type,
      'post_status' => 'publish',
      'posts_per_page' => -1
    );
    return count(get_posts($args));
  }

  public function addColHead($cols)
  {
    $insertCol = array(__('Freelancer', 'cn'));
    array_splice($cols, 2, 0, $insertCol);
    return $cols;
  }

  public function addColRow($cols) {
    $id = (int) str_replace('#', '', $cols[0]);
    $name = get_the_title(get_post_meta($id, '_freelancer_id', true));
    $name = $name?$name:'Not Selected';
    array_splice($cols, 2, 0, array($name));
    return $cols;
  }

  public function addMenuItem($menu) {
    if ("tasks" == self::$route) {
      $menu['#addTask'] = array(
        'title' => 'Add New Task',
        'icon' => 'fa-plus-circle'
      );
    }
    return $menu;
  }

  public function routeTitles($title)
  {
    $routeTitles = array(
      'tasks' => 'Tasks',
      'dashboard' => 'Dashboard'
    );

    if (isset($routeTitles[self::$route])) {
      $title['title'] = $routeTitles[self::$route];
    }

    return $title;
  }

  public function ajax_cn_add_task()
  {
    $response = array(
      "status" => "success",
      "message" => "Success"
    );
    if (isset($_POST['title']) && isset($_POST['freelancer_id'])) {
      $title = esc_html($_POST['title']);
      $freelancer_id = (int) $_POST['freelancer_id'];
      $id = $this->createTask($title, $freelancer_id);
    } else {
      $response['status'] = "error";
      $response['message'] = "Invalid data, please try again later.";
    }
    print_r(json_encode($response));
    die();
  }

  public function ajax_cn_add_task_nopriv()
  {
    $response = array(
      'status' => 'warning',
      'message' => 'You must be logged in to create new tasks'
    );
    print_r(json_encode($response));
    die();
  }

  private function createTask($title, $freelancer_id = null)
  {
    $postarr = array(
      'ID' => null,
      'post_type' => 'task',
      'post_status' => 'publish',
      'post_title' => esc_html($title)
    );

    $id = wp_insert_post($postarr);

    if ($freelancer_id && $id) {
      update_post_meta($id, '_freelancer_id', (int) $freelancer_id);
    }

    return $id;
  }

  public function autoloader($class)
  {
    $folders = array(
      'includes'
    );

    $parts = explode ('\\',$class);
    array_shift ($parts);
    $class_name = array_shift ($parts);

    foreach ($folders as $folder) {
      $plugin_file = self::$app_path . '/' . $folder . '/' . $class_name . '.php';
      if (!file_exists ($plugin_file)) {
        continue;
      }

      return require_once $plugin_file;

      if (!class_exists ($class)) {
        continue;
      }
    }
  }
}
