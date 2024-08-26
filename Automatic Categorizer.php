<?php

/*  

Plugin Name: Automatic Categorizer

Description: Automatic product categorization according to custom keywords by bulk action. Autosave keywords for each category, UI to handle keywords! 

Author: Yousseif Ahmed 

Version: 1.4

*/


function get_category_keywords( $category_id ) {
  $category_keywords = get_option( 'product_categorization_category_keywords', array() );
  return isset( $category_keywords[ $category_id ] ) ? implode( ", ", $category_keywords[ $category_id ] ) : '';
}

// Sanitize and update the category keywords
function sanitize_category_keywords( $category_keywords ) {
  $sanitized_keywords = array();
  foreach ( $category_keywords as $category_id => $keywords ) {
      $sanitized_keywords[ $category_id ] = explode( ", ", sanitize_textarea_field( $keywords ) );
  }
  return $sanitized_keywords;
}

// Register and initialize the updated settings
function register_updated_settings() {
  // Define the settings section
  add_settings_section( 'product_categorization_section', 'Product Categorization Options', 'render_section_description', 'product_categorization_settings' );

  // Define the settings fields
  add_settings_field( 'product_categorization_categories', 'Categories', 'render_categories_field', 'product_categorization_settings', 'product_categorization_section' );

  // Register the settings
  register_setting( 'product_categorization_options', 'product_categorization_categories', 'sanitize_categories' );
  register_setting( 'product_categorization_options', 'product_categorization_category_keywords', 'sanitize_category_keywords' );
}

// Register the settings page
function register_custom_settings_page() {
  add_menu_page( 'Product Categorization Settings', 'Product Categorization', 'manage_options', 'product_categorization_settings', 'render_custom_settings_page' );
}

// Render the settings page
function render_custom_settings_page() {
  ?>
  <div class="wrap">
      <h1>Product Categorization Settings</h1>
        <mark> <?php if(!class_exists( 'WooCommerce' ) ){
            $this->$notice = "Install <a target=_blank href='".get_admin_url()."plugin-install.php?s=woocommerce&tab=search&type=term"."'>Woocommerce</a> first!";
    } ?> </mark>
    <?php if( isset($_GET['settings-updated']) ) { ?>
        <div id="message" class="updated">
            <p><strong><?php _e('Settings saved.') ?></strong></p>
        </div>
    <?php }?>	
      <form method="post" action="options.php">
          <?php
          settings_fields( 'product_categorization_options' );
          do_settings_sections( 'product_categorization_settings' );
          submit_button();
          ?>
      </form>
  </div>
  <?php
}

// Render the section description
function render_section_description() {
  echo '<p>Configure the keywords and corresponding categories for product categorization.</p>';
}
function render_categories_field() {
    $categories = get_option( 'product_categorization_categories', array() );
    $all_categories = get_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
    ?>

    <table class="form-table">
        <tr valign="top">
            <td>
                <?php foreach ( $all_categories as $cat ) : ?>
                    <label>
                        <input type="checkbox" name="product_categorization_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $categories ), true ); ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </label>
                    <br>
				
				 <textarea  name="product_categorization_category_keywords[<?php echo esc_attr( $cat->term_id ); ?>]"  rows="2" cols="200"><?php echo esc_textarea( get_category_keywords( $cat->term_id ) ); ?></textarea>
                    <p class="description">Enter the keywords related to this category, each on a separate line.</p>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>

<?php
}
// Sanitize the keywords
function sanitize_keywords( $keywords ) {
  return explode( ", ", sanitize_textarea_field( $keywords ) );
}

// Sanitize the categories
function sanitize_categories( $categories ) {
  return array_map( 'absint', $categories );
}

// Modify the categorization function
function categorize_products_by_title() {
  // Get selected product IDs
  $product_ids = isset( $_REQUEST['post'] ) ? array_map( 'absint', $_REQUEST['post'] ) : array();

  // Get keywords and categories from settings
  $categories = get_option( 'product_categorization_categories', array() );
  $category_keywords = get_option( 'product_categorization_category_keywords', array() );

  // Loop through products
  foreach ( $product_ids as $product_id ) {
      // Get product object
      $product = wc_get_product( $product_id );

      // Get title
      $title = $product->get_title();

      // Loop through selected categories
      foreach ( $categories as $category_id ) {
          $cat = get_term( $category_id, 'product_cat' );
          $cat_name = $cat->name;
          $cat_keywords = isset( $category_keywords[ $category_id ] ) ? $category_keywords[ $category_id ] : array();

          // If title contains any of the assigned keywords, assign the category
          if ( has_keywords( $title, $cat_keywords ) ) {
              wp_set_object_terms( $product_id, $cat_name, 'product_cat', true );
          }
      }
  }
}

function has_keywords( $string, $keywords ) {
  foreach ( $keywords as $keyword ) {
      if ( stristr( $string, $keyword ) !== false ) {
          return true;
      }
  }}

// Hook the modified function to the correctbulk action:



// Add the custom bulk action
add_filter( 'bulk_actions-edit-product', 'add_categorize_bulk_action' );
function add_categorize_bulk_action( $bulk_actions ) {
  $bulk_actions['handle_bulk_action_categorize'] = 'Categorize';
  return $bulk_actions;
}
