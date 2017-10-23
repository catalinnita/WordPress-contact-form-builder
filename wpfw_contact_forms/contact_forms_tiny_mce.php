<?php

function add_es_forms_button() {
   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
     return;
   if ( get_user_option('rich_editing') == 'true') {
     add_filter('mce_external_plugins', 'add_es_forms_tinymce_plugin');
     add_filter('mce_buttons', 'register_es_forms_button');
   }
}

add_action('init', 'add_es_forms_button');


function register_es_forms_button($buttons) {
   array_push($buttons, "|", "es_forms");
   return $buttons;
}

function add_es_forms_tinymce_plugin($plugin_array) {
   $plugin_array['es_forms'] = plugins_url( 'js/contact_forms.js' , __FILE__ );
   
   return $plugin_array;
}

function es_forms_my_refresh_mce($ver) {
  $ver += 3;
  return $ver;
}

add_filter( 'tiny_mce_version', 'es_forms_my_refresh_mce');

if ( is_admin() ) { 
wp_register_style('shortcodes_style', plugins_url( 'styles/shortcodes_window.css' , __FILE__ ), false, '1.0');
wp_enqueue_style('shortcodes_style');
}

function build_es_forms_window($content) {
	global $wpdb;	
	
	$content = $content;
	$content .= '<div id="es_forms_window" class="shortcode_window">';
	$content .= '<div class="title"><span class="close"><img src="../wp-includes/js/thickbox/tb-close.png"></span>Insert contact form</div>';
	$forms = $wpdb->get_results("SELECT * FROM es_forms");
	$content .= '<div class="shortcode_window_content">';
	$content .= 'Select form&nbsp;&nbsp;';
	$content .= '<select name="es_form_name" id="es_form_name">';
	foreach ($forms as $form) {
		$content .= '<option value="'.$form->Name.'">'.$form->Name.'</option>';
	}
	$content .= '</select>';
	$content .= '<p style="margin-top: 10px;"><input type=button id="es_form_select_butt" value="Insert form" class="button-secondary"></p>';

	$content .= '</div>';
	$content .= '</div>';

	echo $content;
}

add_filter('admin_footer', 'build_es_forms_window');

?>