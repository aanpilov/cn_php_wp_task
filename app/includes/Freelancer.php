<?php
namespace cntesttask;

use \Exceptions;

class Freelancer {

  const POST_TYPE = 'freelancer';

  public static function init()
  {
    $labels = array(
      'name'               => _x( 'Freelancers', 'post type general name', 'cn' ),
      'singular_name'      => _x( 'Freelancer', 'post type singular name', 'cn' ),
      'menu_name'          => _x( 'Freelancers', 'admin menu', 'cn' ),
      'name_admin_bar'     => _x( 'Freelancer', 'add new on admin bar', 'cn' ),
      'add_new'            => _x( 'Add New', 'freelancer', 'cn' ),
      'add_new_item'       => __( 'Add New Freelancer', 'cn' ),
      'new_item'           => __( 'New Freelancer', 'cn' ),
      'edit_item'          => __( 'Edit Freelancer', 'cn' ),
      'view_item'          => __( 'View Freelancer', 'cn' ),
      'all_items'          => __( 'All Freelancers', 'cn' ),
      'search_items'       => __( 'Search Freelancers', 'cn' ),
      'parent_item_colon'  => __( 'Parent Freelancers:', 'cn' ),
      'not_found'          => __( 'No freelancers found.', 'cn' ),
      'not_found_in_trash' => __( 'No freelancers found in Trash.', 'cn' )
    );

    $args = array(
      'labels'             => $labels,
      'description'        => __( 'Description.', 'cn' ),
      'public'             => true,
      'publicly_queryable' => true,
      'show_ui'            => true,
      'show_in_menu'       => true,
      'query_var'          => true,
      'rewrite'            => array( 'slug' => 'freelancer' ),
      'capability_type'    => 'post',
      'has_archive'        => true,
      'hierarchical'       => false,
      'menu_position'      => null,
      'menu_icon'          => 'dashicons-admin-users',
      'supports'           => array( 'title', 'thumbnail')
    );
    register_post_type( self::POST_TYPE, $args );
  }

  public static function getAll()
  {
    $args = array(
      'numberposts' => -1,
      'post_type'   => self::POST_TYPE,
      'post_status' => 'publish',
      'orderby'     => 'post_title',
      'order'       => 'ASC'
    );

    $posts = get_posts( $args );

    if (!$posts) {
      return false;
    }

    return $posts;
  }

  public static function selectArray()
  {
    $freelancersArray = array(
      "" => "Not Selected"
    );
    $all = self::getAll();
    if ($all && count($all)) {
      foreach($all as $freelancer) {
        $freelancersArray[$freelancer->ID] = $freelancer->post_title;
      }
    }
    return $freelancersArray;
  }

  // Get freelancers with less than 2 tasks;
  public static function selectFreeArray()
  {
    $all = self::selectArray();

    global $wpdb;
    $meta_sql = "SELECT meta_value ID, COUNT(meta_value) as tasks_count FROM {$wpdb->postmeta} WHERE meta_key='_freelancer_id' GROUP BY meta_value ORDER BY tasks_count DESC";
    $meta = $wpdb->get_results($meta_sql);

    if (count($meta)) {
      foreach ($meta as $freelancer) {
        if ($freelancer->tasks_count >= 2) {
          unset($all[$freelancer->ID]);
        }
      }
    }
    return $all;
  }

}
