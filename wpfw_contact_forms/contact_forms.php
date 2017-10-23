<?php
/*
Plugin Name: WPFW - Contact Form Builder
Plugin URI: http://www.WordpressForward.com
Description: Build forms and apply them in your pages and posts.
Version: 1.0.1
Author: Catalin Nita
Author URI: http://www.WordpressForward.com
License: GNU General Public License v2 or later
*/

if (!function_exists("get_wp_path")) {
	function get_wp_path($filename) {
		$url = explode("wp-content", getcwd());
			if (count($url) <= 1) {
				$url = explode("wp-admin", getcwd());
				if (count($url) <= 1) {
					$url[0] = getcwd()."/";
				}
			}
		return $url[0].$filename;
	}
}
require_once(get_wp_path('wp-includes/pluggable.php'));
require_once(get_wp_path('wp-admin/includes/upgrade.php'));

global $wpdb;

$sql = "CREATE TABLE IF NOT EXISTS `es_forms` (
	  				`ID` bigint(20) NOT NULL AUTO_INCREMENT,
	  				`Name` varchar(255) NOT NULL,
	  				`Title` varchar(255) NOT NULL,
	  				`Description` text NOT NULL,
	  				`ButtonText` varchar(255) NOT NULL,
	  				`Email` varchar(255) NOT NULL,
	  				`SaveDb` Int(2) NOT NULL,
	  				`AutoCopy` Int(2) NOT NULL,
	  				`AutoCopyText` varchar(255) NOT NULL,
	  				`Captcha` Int(2) NOT NULL,
	  				`AutoResponder` bigint(20) NOT NULL,
						PRIMARY KEY (`ID`) 
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";									
$wpdb->query($sql);					

$sql = "CREATE TABLE IF NOT EXISTS `es_forms_fields` (
	  				`ID` bigint(20) NOT NULL AUTO_INCREMENT,
	  				`FormID` bigint(20) NOT NULL,
	  				`FieldName` varchar(255) NOT NULL,
	  				`FieldType` varchar(255) NOT NULL,
	  				`FieldValues` varchar(255) NOT NULL,
	  				`FieldDefaultValues` varchar(255) NOT NULL,
	  				`FieldRequired` int(2) NOT NULL,
	  				`SortOrder` bigint(20) NOT NULL,
	  				`EmailField` Int(2) NOT NULL,
						PRIMARY KEY (`ID`) 
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";		
$wpdb->query($sql);
					
$sql = "CREATE TABLE IF NOT EXISTS `es_forms_saved` (
	  				`ID` bigint(20) NOT NULL AUTO_INCREMENT,
	  				`FormID` bigint(20) NOT NULL,
	  				`Answer` text NOT NULL,
	  				`SessionID` varchar(255) NOT NULL,
						PRIMARY KEY (`ID`) 
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
$wpdb->query($sql);


add_action('init', 'autoresponders_register');
add_action('admin_menu', 'manage_forms_menu');  

function manage_forms_menu() {
	add_menu_page('Forms', 'Forms', 'administrator', 'Forms', 'manage_forms', plugins_url( 'images/admin/form_icon.png' , __FILE__ ), 999);
	add_submenu_page('Forms', 'Forms', 'Forms', 'administrator', 'manage_forms', 'manage_forms');	
	add_submenu_page('Forms', 'Saved info', 'Saved info', 'administrator', 'saved_info', 'saved_info');	
}

function autoresponders_register() {
 
	$labels = array(
		'name' => _x('Autoresponder emails', 'post type general name', 'wpfw'),
		'singular_name' => _x('Autoresponder', 'post type singular name', 'wpfw'),
		'add_new' => _x('Add New', 'Autoresponder', 'wpfw'),
		'add_new_item' => __('Add New Autoresponder', 'wpfw'),
		'edit_item' => __('Edit Autoresponder', 'wpfw'),
		'new_item' => __('New Autoresponder', 'wpfw'),
		'view_item' => __('View Autoresponder', 'wpfw'),
		'search_items' => __('Search Autoresponder emails', 'wpfw'),
		'not_found' =>  __('Nothing found', 'wpfw'),
		'not_found_in_trash' => __('Nothing found in Trash', 'wpfw'),
		'parent_item_colon' => ''
	);
 
	$args = array(
		'labels' => $labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_position' => 100,
 	  'show_in_menu' => 'Forms',
		'menu_icon' => '',
		'supports' => array('title','editor')
	  ); 
 
	register_post_type( 'autoresponder' , $args );
	
}

function manage_forms() {
	global $wpdb;
	
	if (!$_GET['formid'] && !$_GET['addformid']) {
		content_manage_forms();
	}	
	else {
	if ($_GET['formid']) {
		edit_form();				
	}				
	if ($_GET['addformid'] && !$_GET['fieldid']) {
		if ($_GET['fielddeleteid']) {
			delete_field();
		}
		if ($_POST['updatefield'] == 1) {
			update_field();
		}
		
		if ($_POST['addfield'] == 1) {
			add_new_field();
		}
		if ($_POST['updatefields'] == 1) {
			update_all_fields();
		}		
		manage_form_fields();				
	}
	else {
		if ($_GET['fieldid']) {
			edit_field();
		}
	}					
	}
}

function content_manage_forms() {
	global $wpdb, $_POST;
	
	if ($_POST['addform'] == 1) {
		add_new_form();				
	}

	if ($_GET['deleteid']) {
		delete_form();				
	}	
	if ($_POST['updateform'] == 1) {
		update_form();				
	}			
	
	$forms = $wpdb->get_results("SELECT * FROM es_forms");

	$content = '<div class="wrap">';
	$content .= '<div id="icon-themes" class="icon32"><br /></div>';
	$content .= '<h2>Manage forms</H2><br/>';
	$content .= '<div id="col-container">';
	$content .= '<div id="col-right">';	
	$content .= '<div class="col-wrap">';	
	$content .= '<table class="widefat post fixed">';	
	$content .= '<thead>';
	$content .= '<tr>';
	$content .= '<th class="manage-column column-title" style="width: 15%;">Form name</th>';
	$content .= '<th class="manage-column column-title" style="width: 30%; text-align: center;">Actions</th>';
	$content .= '</tr>';
	$content .= '</thead>';
	
	$content .= '<tbody>';
	
	$rownr = 1;
	foreach($forms as $form) {
	$content .= '<tr';
	if ($rownr%2 == 1) {
		$content .= ' class="alternate"';
	}
	$content .= '>';
	$content .= '<td><b>'.$form->Name.'</b></td>';
	$content .= '<td style="text-align: center;">';
	$content .= '<a style="margin-right: 14px;" href="admin.php?page=Forms&formid='.$form->ID.'">EDIT</a>';
	$content .= '<a style="margin-right: 14px;" href="admin.php?page=Forms&addformid='.$form->ID.'">MANAGE&nbsp;FIELDS</a>';
	$content .= '<a href="admin.php?page=Forms&deleteid='.$form->ID.'">DELETE</a></td>';
	$content .= '</tr>';		
	
	$rownr++;		
	}
	$content .= '</tbody>';
	$content .= '<tfoot>';
	$content .= '<tr>';
	$content .= '<th class="manage-column column-title">Form name</th>';
	$content .= '<th class="manage-column column-title" style="text-align: center;">Actions</th>';
	$content .= '</tr>';
	$content .= '</tfoot>';	
	$content .= '</table><br/>';
	$content .= '</div>';
	$content .= '</div>';
	
	echo $content;
	
	$content = '<div id="col-left">';
	$content .= '<div class="form-wrap">';
	$content .= '<h3>Add New Form</h3>';
	$content .= '<form name=addpromoslider method=POST action="admin.php?page=Forms">';
	$content .= '<input type=hidden name=addform value=1>';	
	
	// form name
	$content .= '<div class="form-field">';
	$content .= '<label>Form name</label>';
	$content .= '<input type="text" name="form_name">';
	$content .= '<p>This name will help you to identify the form in edit post section.</p>';
	$content .= '</div>';
	
	// form title
	$content .= '<div class="form-field">';
	$content .= '<label>Form Title</label>';
	$content .= '<input type="text" name="form_title">';
	$content .= '<p>Form title as will be displayed in your website.</p>';
	$content .= '</div>';
	
	// form description
	$content .= '<div class="form-field">';
	$content .= '<label>Form Description (optional)</label>';
	$content .= '<textarea name="form_description" rows=6></textarea>';
	$content .= '<p>Form description as will be displayed in your website.</p>';
	$content .= '</div>';	
	
	// submit button text
	$content .= '<div class="form-field">';
	$content .= '<label>Submit Button Text</label>';
	$content .= '<input type="text" name="form_button">';
	$content .= '<p>Form button text. (example: Send message)</p>';
	$content .= '</div>';	
	
	// save answers in db
	$content .= '<div class="form-field">';
	$content .= '<label>Save answers</label>';
	$content .= '<input type="checkbox" checked="checked" name="save_db" style="width: 30px; float: left; margin: 5px 0px 15px 0px;">';
	$content .= '<p>Check this if you want to save forms answers in database.</p>';
	$content .= '</div>';
	
	// email where the messages are sent
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Email</label>';
	$content .= '<input type="text" name="form_email">';
	$content .= '<p>Email where the form will be sent.</p>';
	$content .= '</div>';		
	
	// include autocopy
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Auto copy</label>';
	$content .= '<input type="checkbox" checked="checked" name="autocopy" style="width: 30px; float: left; margin: 5px 0px 15px 0px;">';
	$content .= '<p>Check this if you want to let users to receive a copy of the messages they are sending.</p>';
	$content .= '</div>';		
			
	// autocopy field text
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Auto copy option text</label>';
	$content .= '<input type="text" name="autocopytext">';
	$content .= '<p>Example: E-mail a copy of this message to your own address</p>';
	$content .= '</div>';		
	
	// add captcha
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Include captcha</label>';
	$content .= '<input type="checkbox" checked="checked" name="captcha" style="width: 30px; float: left; margin: 5px 0px 15px 0px;">';
	$content .= '<p>Check this to add a captcha to this form</p>';
	$content .= '</div>';					
	
	// autoresponder
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Autoresponder emails</label>';
	
	$content .= '<select name="autoresponder">';
	$content .= '<option value="0">none</option>';
	
	$autoresponder = new WP_Query(array("post_type"=>"autoresponder","orderby"=>"title","order"=>"ASC"));
	while($autoresponder->have_posts()):$autoresponder->the_post();
		$content .= '<option value="'.get_the_ID().'">'.get_the_title().'</option>';
	endwhile;
	
	$content .= '</select>';
	$content .= '<p>Select auto responder template for this form. If you don\'t want to set up an autoresponder, please select <b>none</b>. You can add <a href="edit.php?post_type=autoresponder">autoresponder templates here</a>.</p>';
	$content .= '</div>';		
	
	$content .= '<p class="submit"><input type=submit value="Add New Form"></p>';
	$content .= '</form>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</div>';
	
	echo $content;
	
}

function add_new_form() {
	global $wpdb, $_POST;
	
	$sliderexists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM es_forms WHERE Name = '".$_POST['form_name']."'" ));
	if ($sliderexists) {
		$form_name = $_POST['form_name'].'_copy';
	}	
	else {
		$form_name = $_POST['form_name'];
	}
	
	if ($_POST['save_db'] == 'on') { $save = 1; } else { $save = 0; }
	if ($_POST['captcha'] == 'on') { $captcha = 1; } else { $captcha = 0; }	
	if ($_POST['autocopy'] == 'on') { $autocopy = 1; } else { $autocopy = 0; }		
	
	
	
	$wpdb->query("INSERT INTO es_forms
			(Name, Title, Description, ButtonText, Email, SaveDb, Captcha, AutoCopy, AutoCopyText, AutoResponder)
				VALUES ('".$form_name."', '".$_POST['form_title']."', '".$_POST['form_description']."', '".$_POST['form_button']."', '".$_POST['form_email']."', ".$save.", ".$captcha.", ".$autocopy.", '".$_POST['autocopytext']."', ".$_POST['autoresponder'].")");	
				
	
}

function delete_form() {
	global $wpdb, $_GET;
	
	$wpdb->query("DELETE FROM es_forms WHERE ID = ".$_GET['deleteid']);	
	
}

function edit_form() {
	global $wpdb, $_POST, $_GET;
	
	$form = $wpdb->get_results("SELECT * FROM es_forms WHERE ID = ".$_GET['formid']);
	
	$content = '<div class="wrap">';
	$content .= '<div id="icon-themes" class="icon32"><br /></div>';
	$content .= '<h2>Edit '.$form[0]->Name.'</H2><br/>';

	$content .= '<div class="form-wrap">';
	$content .= '<form name=addpromoslider method=POST action="admin.php?page=Forms">';
	$content .= '<input type=hidden name=updateform value=1>';	
	$content .= '<input type=hidden name=formid value='.$form[0]->ID.'>';	
	
	$content .= '<div class="form-field">';
	$content .= '<label>Form Title</label>';
	$content .= '<input type="text" name="form_title" value="'.$form[0]->Title.'">';
	$content .= '<p>Form title as will be displayed in your website.</p>';
	$content .= '</div>';
	
	$content .= '<div class="form-field">';
	$content .= '<label>Form Description</label>';
	$content .= '<textarea name="form_description" rows=6>'.$form[0]->Description.'</textarea>';
	$content .= '<p>Form description as will be displayed in your website.</p>';
	$content .= '</div>';
	
	$content .= '<div class="form-field">';
	$content .= '<label>Submit Button Text</label>';
	$content .= '<input type="text" name="form_button" value="'.$form[0]->ButtonText.'">';
	$content .= '<p>Form button text. (example: Send message)</p>';
	$content .= '</div>';	

	$content .= '<div class="form-field">';
	$content .= '<label>Save answers</label>';
	$content .= '<input type="checkbox" '; if ($form[0]->SaveDb == 1) { $content .= 'checked="checked"'; } $content .= 'name="save_db" style="width: 30px; float: left; margin: 5px 0px 15px 0px;">';
	$content .= '<p>Check this if you want to save forms answers in database.</p>';
	$content .= '</div>';			
	
	$content .= '<div class="form-field">';
	$content .= '<label>Email</label>';
	$content .= '<input type="text" name="form_email" value="'.$form[0]->Email.'">';
	$content .= '<p>Email where the form will be sent.</p>';
	$content .= '</div>';			
	
	// include autocopy
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Auto copy</label>';
	$content .= '<input type="checkbox" '; if ($form[0]->AutoCopy == 1) { $content .= 'checked="checked"'; } $content .= ' name="autocopy" style="width: 30px; float: left; margin: 5px 0px 15px 0px;">';
	$content .= '<p>Check this if you want to let users to receive a copy of the messages they are sending.</p>';
	$content .= '</div>';		
			
	// autocopy field text
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Auto copy option text</label>';
	$content .= '<input type="text" name="autocopytext"  value="'.$form[0]->AutoCopyText.'" />';
	$content .= '<p>Example: E-mail a copy of this message to your own address</p>';
	$content .= '</div>';		
	
	// add captcha
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Include captcha</label>';
	$content .= '<input type="checkbox" '; if ($form[0]->Captcha == 1) { $content .= 'checked="checked"'; } $content .= ' name="captcha" style="width: 30px; float: left; margin: 5px 0px 15px 0px;">';
	$content .= '<p>Check this to add a captcha to this form</p>';
	$content .= '</div>';					
	
	// autoresponder
	$content .= '<div class="form-field">';
	$content .= '<label style="clear: left;">Autoresponder emails</label>';
	$content .= '<select name="autoresponder">';
	$content .= '<option value="0">none</option>';
	$autoresponder = new WP_Query(array("post_type"=>"autoresponder","orderby"=>"title","order"=>"ASC"));
	while($autoresponder->have_posts()):$autoresponder->the_post();
		$content .= '<option value="'.get_the_ID().'"'; if($form[0]->AutoResponder == get_the_ID()) { $content .= ' selected="selected"'; } $content .= '>'.get_the_title().'</option>';
	endwhile;
	$content .= '</select>';
	$content .= '<p>Select auto responder template for this form. If you don\'t want to set up an autoresponder, please select <b>none</b>. You can add <a href="edit.php?post_type=autoresponder">autoresponder templates here</a>.</p>';
	$content .= '</div>';		
	
	$content .= '<p class="submit"><input type=submit value="Save"></p>';
	$content .= '</form>';
	$content .= '</div>';
	$content .= '</div>';
	
	echo $content;	
	
}

function update_form() {
	global $wpdb, $_POST;
	
	if ($_POST['save_db'] == 'on') { $save = 1; } else { $save = 0; }	
	if ($_POST['captcha'] == 'on') { $captcha = 1; } else { $captcha = 0; }	
	if ($_POST['autocopy'] == 'on') { $autocopy = 1; } else { $autocopy = 0; }	
	
	$wpdb->query("UPDATE es_forms SET 
			Title = '".$_POST['form_title']."',
			Description = '".$_POST['form_description']."',
			ButtonText = '".$_POST['form_button']."',
			Email = '".$_POST['form_email']."',
			SaveDb = ".$save.",
			Captcha = ".$captcha.",
			AutoCopy = ".$autocopy.",
			AutoCopyText = '".$_POST['autocopytext']."',
			AutoResponder = '".$_POST['autoresponder']."'
				WHERE ID = ".$_POST['formid']);	
	
}


function manage_form_fields() {
	global $_GET, $_POST, $wpdb;
	
	$fields = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE FormID = ".$_GET['addformid']." ORDER BY SortOrder");
	$form = $wpdb->get_results("SELECT * FROM es_forms WHERE ID = ".$_GET['addformid']);

	$content = '<div class="wrap">';
	$content .= '<div id="icon-themes" class="icon32"><br /></div>';
	$content .= '<h2><a href="admin.php?page=Forms">Manage forms</a> > Add fields</H2><br/>';
	$content .= '<div id="col-container">';
	$content .= '<div id="col-right">';	
	$content .= '<div class="col-wrap">';	
	$content .= '<form name="updatefields" action="admin.php?page=Forms&addformid='.$_GET['addformid'].'" method="post">';	
	$content .= '<input type="hidden" name="updatefields" value="1">';
	$content .= '<table class="widefat post fixed">';	
	$content .= '<thead>';
	$content .= '<tr>';
	$content .= '<th class="manage-column column-title" style="width: 25%;">Field name</th>';
	$content .= '<th class="manage-column column-title" style="width: 15%;">Field type</th>';
	$content .= '<th class="manage-column column-title" style="width: 20%;">Field values</th>';
	if ($form[0]->AutoCopy == 1|| $form[0]->AutoResponder != 0) { 
	$content .= '<th class="manage-column column-title" style="width: 12%; text-align: center;">Email field*</th>';
	}	
	$content .= '<th class="manage-column column-title" style="width: 12%; text-align: center;">Required</th>';
	$content .= '<th class="manage-column column-title" style="width: 10%; text-align: center;">Order</th>';
	$content .= '<th class="manage-column column-title" style="width: 18%; text-align: center;">Actions</th>';
	$content .= '</tr>';
	$content .= '</thead>';
	
	$content .= '<tbody>';

	$rownr = 1;
	foreach($fields as $field) {
		
	if ($field->FieldRequired == 1) {
		$fieldrequired = '<input type=checkbox name="field_required_'.$field->ID.'" checked>';
	}
	else {
		$fieldrequired = '<input type=checkbox name="field_required_'.$field->ID.'">';
	}	
	
	if ($field->EmailField == 1) { 
		$emailfield = '<input type=radio name="email_field" value="'.$field->ID.'" checked>';
	}
	else {
		$emailfield = '<input type=radio name="email_field" value="'.$field->ID.'">';
	}
		
	$content .= '<tr';
	if ($rownr%2 == 1) {
		$content .= ' class="alternate"';
	}
	$content .= '>';
	$content .= '<td>'.$field->FieldName.'</td>';
	$content .= '<td>'.$field->FieldType.'</td>';
	$content .= '<td>'.$field->FieldValues.'</td>';
	if ($form[0]->AutoCopy == 1|| $form[0]->AutoResponder != 0) { 
		$content .= '<td style="text-align: center;">'.$emailfield.'</td>';
	}
	$content .= '<td style="text-align: center;">'.$fieldrequired.'</td>';
	$content .= '<td style="text-align: center;"><input type=text style="width: 30px; text-align: center;" name="field_order_'.$field->ID.'" value="'.$field->SortOrder.'"></td>';
	$content .= '<td style="text-align: center;">';
	$content .= '<a style="margin-right: 14px;" href="admin.php?page=Forms&addformid='.$_GET['addformid'].'&fieldid='.$field->ID.'">EDIT</a>';
	$content .= '<a href="admin.php?page=Forms&addformid='.$_GET['addformid'].'&fielddeleteid='.$field->ID.'">DELETE</a>';
	$content .= '</td>';
	$content .= '</tr>';		
		
	$rownr++;
	}
	$content .= '</tbody>';
	$content .= '<tfoot>';
	$content .= '<tr>';
	$content .= '<th class="manage-column column-title">Field name</th>';
	$content .= '<th class="manage-column column-title">Field type</th>';
	$content .= '<th class="manage-column column-title">Field values</th>';
	if ($form[0]->AutoCopy == 1|| $form[0]->AutoResponder != 0) { 
	$content .= '<th class="manage-column column-title" style="width: 12%; text-align: center;">Email field*</th>';
	}
	$content .= '<th class="manage-column column-title">Required</th>';
	$content .= '<th class="manage-column column-title" style="text-align: center;">Order</th>';
	$content .= '<th class="manage-column column-title" style="text-align: center;">Actions</th>';
	$content .= '</tr>';
	$content .= '</tfoot>';	
	$content .= '</table><br/>';
	$content .= '<input class="button-secondary action" type="submit" value="update all fields">';
	if ($form[0]->AutoCopy == 1 || $form[0]->AutoResponder != 0) { 
	$content .= '<p>*please select email field, this is correlated with auto copy option. If your users will choose to receive an autocopy of the message the email will be sent.</p>';
	}
	$content .= '</form>';
	$content .= '</div>';
	$content .= '</div>';
	
	echo $content;
	
	$content = '<div id="col-left">';
	$content .= '<div class="form-wrap">';
	$content .= '<h3>Add New Field</h3>';
	$content .= '<form name=addpromoslider method=POST action="admin.php?page=Forms&addformid='.$_GET['addformid'].'">';
	$content .= '<input type=hidden name=addfield value=1>';	
	$content .= '<div class="form-field">';
	$content .= '<label>Field name</label>';
	$content .= '<input type="text" name="field_name">';
	$content .= '<p>Field title as it will be displayed on your website.</p>';
	$content .= '</div>';
	$content .= '<div class="form-field">';
	$content .= '<label>Field type</label>';
	$content .= '<select name="field_type">';
	$content .= '<option value="text field">text field</option>';	
	$content .= '<option value="select box">select box</option>';		
	$content .= '<option value="radio buttons">radio buttons</option>';		
	$content .= '<option value="check boxes">check boxes</option>';		
	$content .= '<option value="textarea">textarea</option>';	
	$content .= '<option value="date">date</option>';	
	$content .= '</select>';
	$content .= '</div>';
	$content .= '<div class="form-field">';
	$content .= '<label>Field Values</label>';
	$content .= '<textarea name="field_values" rows=6></textarea>';
	$content .= '<p>Comma separated. Required just for: <b>radio buttons</b>, <b>check boxes</b> and <b>select boxes</b>.</p>';
	$content .= '</div>';
	$content .= '<div class="form-field">';
	$content .= '<label>Field Default Value(s)</label>';
	$content .= '<textarea name="field_default_values" rows=6></textarea>';
	$content .= '<p>Default value. Enter the same as one of your field values. <b>For checkboxes enter comma separated all values for default checked boxes.</b></p>';	
	$content .= '</div>';			
	$content .= '<div class="form-field">';
	$content .= '<label><input type="checkbox" name="field_required" style="width: 20px;">Field required</label>';
	$content .= '<p>Check this if the filed will be required.</p>';
	$content .= '</div>';	
	$content .= '<div class="form-field">';
	$content .= '<label>Order</label>';
	$content .= '<input type="text" name="field_order">';
	$content .= '<p>Lower number will be displayed higher in list.</p>';
	$content .= '</div>';		
	$content .= '<p class="submit"><input type=submit value="Add New Field"></p>';
	$content .= '</form>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</div>';
	
	echo $content;	
	
}

function add_new_field() {
	global $wpdb, $_GET, $_POST;

	if ($_POST['field_required'] == 'on') {
		$fieldrequired = 1;	
	}
	else {
		$fieldrequired = 0;	
	}
	
	$wpdb->query("INSERT INTO es_forms_fields
			(FormID, FieldName, FieldType, FieldValues, FieldDefaultValues, FieldRequired, SortOrder)
				VALUES (".$_GET['addformid'].", '".$_POST['field_name']."', '".$_POST['field_type']."', '".str_replace(", ", ",", $_POST['field_values'])."', '".str_replace(", ", ",", $_POST['field_default_values'])."', ".$fieldrequired." , ".$_POST['field_order'].")");		
	
}

function delete_field() {
	global $wpdb, $_GET;
	
	$wpdb->query("DELETE FROM es_forms_fields
			WHERE ID = ".$_GET['fielddeleteid']);	
	
}

function edit_field() {
	global $wpdb, $_GET, $_POST;
	
	$field = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE ID = ".$_GET['fieldid']);
	
	$content = '<div class="wrap">';
	$content .= '<div id="icon-themes" class="icon32"><br /></div>';
	$content .= '<h2><a href="admin.php?page=Forms">Manage forms</a> > <a href="admin.php?page=Forms&addformid='.$_GET['addformid'].'">Add fields</a> > Edit field</H2><br/>';
	$content .= '<div class="form-wrap">';
	$content .= '<form name=addpromoslider method=POST action="admin.php?page=Forms&addformid='.$_GET['addformid'].'">';
	$content .= '<input type=hidden name=updatefield value=1>';	
	$content .= '<input type=hidden name=fieldid value='.$_GET['fieldid'].'>';	
	$content .= '<div class="form-field">';
	$content .= '<label>Field name</label>';
	$content .= '<input type="text" name="field_name" value="'.$field[0]->FieldName.'">';
	$content .= '<p>Field title as it will be displayed on your website.</p>';	
	$content .= '</div>';
	$content .= '<div class="form-field">';
	$content .= '<label>Field type</label>';
	$content .= '<select name="field_type">';
	$content .= '<option value="text field"'; if ($field[0]->FieldType == "text field") { $content .= ' selected="selected"'; } $content .= '>text field</option>';	
	$content .= '<option value="select box"'; if ($field[0]->FieldType == "select box") { $content .= ' selected="selected"'; } $content .= '>select box</option>';		
	$content .= '<option value="radio buttons"'; if ($field[0]->FieldType == "radio buttons") { $content .= ' selected="selected"'; } $content .= '>radio buttons</option>';		
	$content .= '<option value="check boxes"'; if ($field[0]->FieldType == "check boxes") { $content .= ' selected="selected"'; } $content .= '>check boxes</option>';		
	$content .= '<option value="textarea"'; if ($field[0]->FieldType == "textarea") { $content .= ' selected="selected"'; } $content .= '>textarea</option>';	
	$content .= '<option value="date"'; if ($field[0]->FieldType == "date") { $content .= ' selected="selected"'; } $content .= '>date</option>';		
	$content .= '</select>';
	$content .= '</div>';
	$content .= '<div class="form-field">';
	$content .= '<label>Field Values</label>';
	$content .= '<textarea name="field_values" rows=6>'.$field[0]->FieldValues.'</textarea>';
	$content .= '<p>Comma separated. Required just for: <b>radio buttons</b>, <b>check boxes</b> and <b>select boxes</b>.</p>';	
	$content .= '</div>';	
	$content .= '<div class="form-field">';
	$content .= '<label>Field Default Value(s)</label>';
	$content .= '<textarea name="field_default_values" rows=6>'.$field[0]->FieldDefaultValues.'</textarea>';
	$content .= '<p>Default value. Enter the same as one of your field values. <b>For checkboxes enter comma separated all values for default checked boxes.</b></p>';	
	$content .= '</div>';		
	$content .= '<div class="form-field">';
	$content .= '<label><input type="checkbox" name="field_required" style="width: 20px;"';
	if ($field[0]->FieldRequired == 1) { $content .= ' checked'; }
	$content .= '>Field required</label>';
	$content .= '<p>Check this if the filed will be required.</p>';	
	$content .= '</div>';	
	$content .= '<div class="form-field">';
	$content .= '<label>Order</label>';
	$content .= '<input type="text" name="field_order" value="'.$field[0]->SortOrder.'">';
	$content .= '<p>Lower number will be displayed higher in list.</p>';	
	$content .= '</div>';		
	$content .= '<p class="submit"><input type=submit value="Save"></p>';
	$content .= '</form>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</div>';
	
	echo $content;		
	
}

function update_field() {
	global $wpdb, $_POST;
	
	if ($_POST['field_required'] == 'on') {
		$fieldrequired = 1;
	}
	else {
		$fieldrequired = 0;
	}
	
	$wpdb->query("UPDATE es_forms_fields SET 
			FieldName = '".$_POST['field_name']."',
			FieldType = '".$_POST['field_type']."',
			FieldValues = '".str_replace(", ", ",", $_POST['field_values'])."',
			FieldDefaultValues = '".str_replace(", ", ",", $_POST['field_default_values'])."',
			FieldRequired = ".$fieldrequired.",
			SortOrder = ".$_POST['field_order']."
				WHERE ID = ".$_POST['fieldid']);	
	
}


function update_all_fields() {
		global $wpdb, $_POST, $_GET;
		
		$fields = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE FormID = ".$_GET['addformid']);
		
		foreach($fields as $field) {
		
		if ($_POST['field_required_'.$field->ID] == 'on') {
			$fieldrequired = 1;
		}
		else {
			$fieldrequired = 0;
		}
		
		if ($_POST['email_field'] == $field->ID) {
			$emailfield = 1;
		}
		else {
			$emailfield = 0;
		}		
		
		$wpdb->query("UPDATE es_forms_fields SET 
			FieldRequired = ".$fieldrequired.",
			EmailField = ".$emailfield.",
			SortOrder = ".$_POST['field_order_'.$field->ID]."
				WHERE ID = ".$field->ID);	
			
		}
	
}

function saved_info() {
	global $wpdb;

	if ($_GET['delid']) {
		delete_answer();
	}
	
	if ($_GET['formid']) {
		show_answers();
	}
	else {
		show_forms();
	}
	
}

function delete_answer() {
	global $wpdb;
	
	$wpdb->query("DELETE FROM es_forms_saved WHERE ID = ".$_GET['delid']);
	
}

function show_answers() {
	global $wpdb;
	
	$forminfo = $wpdb->get_results("SELECT * FROM es_forms WHERE ID = ".$_GET['formid']);
	$formfields = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE FormID = ".$_GET['formid']." ORDER BY SortOrder");
	
	
	$content = '<div class="wrap">';
	$content .= '<div id="icon-themes" class="icon32"><br /></div>';
	$content .= '<h2><a href="admin.php?page=saved_info">Select form</a> > Answers for '.$forminfo[0]->Name.'</H2><br/>';

	$content .= '<table class="widefat post">';
	$content .= '<thead>';		
	$content .= '<tr>';		
	foreach($formfields as $formfield) {
		$content .= '<th class="manage-column column-title">'.$formfield->FieldName.'</th>';		
	}
	$content .= '<th class="manage-column column-title">Actions</th>';		
	$content .= '</tr>';	
	$content .= '</thead>';		

	$content .= '<tbody>';		
	
	$formanswers = $wpdb->get_results("SELECT * FROM es_forms_saved WHERE FormID = ".$_GET['formid']);
	$nr = 1;
	foreach($formanswers as $formanswer) {
	
	$forma = unserialize($formanswer->Answer);
	
	if ($nr%2 == 1) {
	$content .= '<tr class="author-self status-publish iedit">';		
	}
	else {
	$content .= '<tr class="alternate author-self status-publish iedit">';	
	}
	
	foreach($formfields as $formfield) {
	
	if ($formfield->FieldType == 'check boxes') {
		$options = explode(",", $formfield->FieldValues);
		$content .= '<td nowrap>';
		$content .= '<ul>';
		foreach($options as $key => $value) {
			$sel = 0;
			for($i = 1 ; $i <= count($options) ; $i++) {
				if ($value == str_replace("yyy", "'", str_replace("_", " ", $forma['field_'.$formfield->ID.'_'.$i]))) {
					$sel = 1;
				}
			}
			$content .= '<li>';
			if ($sel == 1) {
				$content .= $value;
			}
			else {
				$content .= '<span style="color: #DDD;">'.$value.'</span>';
			}
			
			$content .= '</li>';
		}
		$content .= '</ul>';
		$content .= '</td>';
	}
	else {
		$content .= '<td'; if ($formfield->FieldType != 'textarea') { $content .= ' nowrap'; } $content .= '>'.str_replace("yyy", "'", str_replace("_", " ", $forma['field_'.$formfield->ID])).'</td>';	
	}
	
	}
	$content .= '<td><a href="admin.php?page=saved_info&formid='.$_GET['formid'].'&delid='.$formanswer->ID.'">Delete</a></td>';	
	$content .= '</tr>';	
		
	$nr++;
	}
	$content .= '</tbody>';		
	
	$content .= '<tfoot>';		
	$content .= '<tr>';		
	foreach($formfields as $formfield) {
		$content .= '<th class="manage-column column-title">'.$formfield->FieldName.'</th>';		
	}	
	$content .= '<th class="manage-column column-title">Actions</th>';		
	$content .= '</tr>';	
	$content .= '</tfoot>';		
	
	$content .= '</table>';
	
	echo $content;	
	
}

function show_forms() {
	
	global $wpdb;
	
	$content = '<div class="wrap">';
	$content .= '<div id="icon-themes" class="icon32"><br /></div>';
	$content .= '<h2>Select form</H2><br/>';

	$content .= '<table class="widefat post fixed">';
	$content .= '<thead>';		
	$content .= '<tr>';		
	$content .= '<th class="manage-column column-title">Form name</th>';		
	$content .= '<th class="manage-column column-title">Actions</th>';		
	$content .= '</tr>';	
	$content .= '</thead>';		

	$content .= '<tbody>';		

	
	$forms = $wpdb->get_results("SELECT * FROM es_forms WHERE SaveDb = 1 ORDER BY Name ASC");
	
	$nr = 1;
	foreach($forms as $form) {
	
	if ($nr%2 == 1) {
	$content .= '<tr class="author-self status-publish iedit">';		
	}
	else {
	$content .= '<tr class="alternate author-self status-publish iedit">';	
	}
	$content .= '<td>'.$form->Name.'</td>';	
	$content .= '<td><a href="admin.php?page=saved_info&formid='.$form->ID.'">VIEW ANSWERS</a></td>';		
	$content .= '</tr>';	
		
	$nr++;
	}
	$content .= '</tbody>';		
	
	$content .= '<tfoot>';		
	$content .= '<tr>';		
	$content .= '<th class="manage-column column-title">Form name</th>';		
	$content .= '<th class="manage-column column-title">Actions</th>';		
	$content .= '</tr>';	
	$content .= '</tfoot>';		
	
	$content .= '</table>';
	
	echo $content;
		
	
}

include("contact_forms_tiny_mce.php");
include("contact_forms_functions.php");
include("contact_forms_widget.php");



?>