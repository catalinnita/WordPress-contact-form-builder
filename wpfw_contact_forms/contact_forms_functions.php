<?php
if ( !is_admin() ) { 
wp_register_style('form_style', plugins_url('styles/form.css', __FILE__ ), false, '1.0');
wp_register_style('form_widget_style', plugins_url('styles/widget.css', __FILE__ ), false, '1.0');
//wp_register_style('calendar_style', get_template_directory_uri().'/includes/contact_forms/calendar/smoothness/jquery-ui-1.8.16.custom.css', false, '1.0');
wp_enqueue_style('form_style');
wp_enqueue_style('form_widgetstyle');
//wp_enqueue_style('calendar_style');

wp_enqueue_script('form_script', plugins_url('js/custom_fields.js', __FILE__ ), false, '1.0', true);	
//wp_enqueue_script('jqueryui', get_template_directory_uri().'/includes/contact_forms/calendar/jquery-ui-1.8.16.custom.min.js', false, '1.0', true);	

}

function contact_form($atts) {
	
	global $_POST, $wpdb;
	
	extract(shortcode_atts(array(
		'name' => '',
		'vars' => $_POST
	), $atts));			
	
// ---------------------
// process form 
// ---------------------
	
	$contact_info = $wpdb->get_results("SELECT * FROM es_forms WHERE Name = '".$name."'");	
	
	if ($vars['sent'] == 1) {
			
			
			
			$contact_fields = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE FormID = '".$contact_info[0]->ID."' ORDER BY SortOrder");
			
			$error = 0;
			// form validation 
			foreach($contact_fields as $contact_field) {
				
				if ($contact_field->FieldRequired == 1) {
					
					if ($contact_field->FieldType != 'check boxes') {
						if (!$vars['field_'.$contact_field->ID]) {
							
							$error = 1;
							${'error_'.$contact_field->ID} = 1;
						
						}
					}
					else {
						$values = explode(',', $contact_field->FieldValues);
						$checknr = 1;
						$error = 1;
						foreach ($values as $fvalue) {
							if ($vars['field_'.$contact_field->ID.'_'.$checknr]) {
								$error = 0;
							}								
							$checknr++;
						}
						if ($error == 1) {
							${'error_'.$contact_field->ID} = 1;
						}									
					}
				}
				
				if ($contact_field->EmailField == 1) {
					$user_email = $vars['field_'.$contact_field->ID];
				}
				
			}
			
			// captcha test
			if ($contact_info[0]->Captcha == 1) {
				
				if (md5($_POST['norobot']) != $_SESSION['randomnr2'])	{ 
					$error = 2;
				}
				
			}
			
			if ($error == 0) {
			
			
			// save in database
			if ($contact_info[0]->SaveDb == 1) {
				$answer = serialize($_POST);
				
				//$checkanswer = $wpdb->get_results("SELECT * FROM es_forms_saved WHERE SessionID = '".session_id()."'");
				//if (!$checkanswer[0]->ID) {
				$wpdb->query("INSERT INTO es_forms_saved (FormID, Answer, SessionID) VALUES (".$contact_info[0]->ID.", '".$answer."', '".session_id()."') ");
				//}
			}
			
			
			// send email
			$to = $contact_info[0]->Email;
			$subject = 'New message from your clients';
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
			$headers .= 'From: '.get_bloginfo('name').' <website@lifestyleholidaysvc.com>' . "\r\n";
			$message = '<table border=0 cellspacing=0 cellpadding=10>
										<tr>
											<td bgcolor="#00538d" colspan=2><font size=3 color="#FFFFFF"><b>You have received a new message from your clients:</b></font></td>
										</tr>';

$contact_fields = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE FormID = '".$contact_info[0]->ID."' ORDER BY SortOrder");
$rownr = 1;
foreach($contact_fields as $contact_field) {
if ($rownr % 2 == 1) {
$message .= '
<tr>
';
}
else {
$message .= '
<tr bgcolor="#E0E0E0">
';	
}

$message .= '
<td><b>'.$contact_field->FieldName.':</b></td>';
	if ($contact_field->FieldType != 'check boxes') {
		$message .= '<td>'.str_replace("yyy", "'", str_replace("_", " ", $vars['field_'.$contact_field->ID])).'</td>';
	}
	else {
		$values = explode(',', $contact_field->FieldValues);
		$message .= '<td>';
		$checknr = 1;
		$notfirst = 1;
		foreach ($values as $fvalue) {
			if ($vars['field_'.$contact_field->ID.'_'.$checknr]) {
				if ($notfirst != 1) {
					$message .= ', ';
				}
				$message .= str_replace("yyy", "'", str_replace("_", " ", $vars['field_'.$contact_field->ID.'_'.$checknr]));
				$notfirst = 0;
			}
			$checknr++;
		}
		$message .= '</td>';
	}

$message .= '</tr>';
$rownr++;
}

$message .= '
</table>
			';
			
			wp_mail($to, $subject, $message, $headers, $attachments);
			
			// send autocopy
			if ($contact_info[0]->AutoCopy == 1 && $_POST['autocopy'] == 'autocopy') {
				$to = $user_email;
				$subject = 'Copy of '.get_the_title();
			
				wp_mail($to, $subject, $message, $headers, $attachments);
			}
			
			if ($contact_info[0]->AutoResponder != 0) {
				$to = $user_email;
				$post = get_post($contact_info[0]->AutoResponder);
				
				$subject = $post->post_title;
				$message = apply_filters("the_content", $post->post_content);
				
				wp_mail($to, $subject, $message, $headers, $attachments);
				
			}
			
			
			
		}
			
	}
	
// ---------------------
// contact form display
// ---------------------
	
	$content .= '
	<div class="ContactForm">
	<h1>'.$contact_info[0]->Title.'</h1>
	<p>'.$contact_info[0]->Description.'</p>
	<form name="'.str_replace(' ', '_', $name).'" id="'.str_replace(' ', '_', $name).'" method="post" action="'.get_permalink(get_the_ID()).'">
	<input type="hidden" name="sent" value="1" />
	<div class="CleanerLeft"></div>';
	
	if ($error == 1) {
		
		$message = '[message_box_error]The message has not been sent, please complete the highlighted fields.[/message_box_error] ';
					
		$content .= do_shortcode($message);
		
	}
	
	if ($error == 2) {
		
		$message = '[message_box_error]Text for captcha is not correct.[/message_box_error] ';
					
		$content .= do_shortcode($message);
		
	}	
	
	if ($vars['sent'] == 1 && $error == 0) {
		$message = '[message_box_success]The message has been sent successfuly.[/message_box_success] ';
					
		$content .= do_shortcode($message);
	}
	
	$contact_fields = $wpdb->get_results("SELECT * FROM es_forms_fields WHERE FormID = '".$contact_info[0]->ID."' ORDER BY SortOrder");
	
	foreach($contact_fields as $contact_field) {
		
		$content .= '<label';
		if (${'error_'.$contact_field->ID} == 1) {
			$content .= ' class="highlighted"';
		}
		$content .= '>'.$contact_field->FieldName;
		if ($contact_field->FieldRequired == 1) {
			$content .= '<span class="required" title="required field"></span>';
		}
		$content .= '</label>';
		$content .= '<div class="field">';
		switch($contact_field->FieldType) {
			
			// text field
			case 'text field': 
				if ($_POST['field_'.$contact_field->ID]) {
					$default_value = $_POST['field_'.$contact_field->ID];
				}
				else {
					$default_value = $contact_field->FieldDefaultValues;
				}
				
				$content .= '<input type="text" name="field_'.$contact_field->ID.'" value="'.$default_value.'" />';
			break;
			
			case 'date': 
				if ($_POST['field_'.$contact_field->ID]) {
					$default_value = $_POST['field_'.$contact_field->ID];
				}
				else {
					if ($_GET['date']) { 
						$default_value = $_GET['date'];
					}
					else {
						$default_value = $contact_field->FieldDefaultValues;
					}
				}
				
				$content .= '<input class="datepicker" type="text" name="field_'.$contact_field->ID.'" value="'.$default_value.'" />';
			break;			
			
			// select box
			case 'select box': 
				if ($_POST['field_'.$contact_field->ID]) {
					$default_value = $_POST['field_'.$contact_field->ID];
				}
				else {
					$default_value = $contact_field->FieldDefaultValues;
				}			
				
		
				
				$content .= '<input type="hidden" name="field_'.$contact_field->ID.'" value="'.$default_value.'" />';
				
				
				
				$content .= '<span class="SelectBoxHeader" id="field_'.$contact_field->ID.'">'.$default_value.'</span>';
				$content .= '<ul class="SelectBoxOptions">';
				$values = explode(',', $contact_field->FieldValues);
				foreach ($values as $fvalue) {
					$content .= '<li class="SelectBoxOption" id="'.str_replace(" ", "_", $fvalue).'">'.$fvalue.'</li>';
				}
				
				$content .= '</ul>';
			break;
			
			// radio buttons
			case 'radio buttons': 
				if ($_POST['field_'.$contact_field->ID]) {
					$default_value = $_POST['field_'.$contact_field->ID];
				}
				else {
					$default_value = $contact_field->FieldDefaultValues;
				}			
				
				$values = explode(',', $contact_field->FieldValues);
				$content .= '<input type="hidden" name="field_'.$contact_field->ID.'" value="'.$default_value.'" />';
				$content .= '<ul>';
				foreach ($values as $fvalue) {
					$content .= '<li><span id="'.str_replace("'", "yyy", str_replace(" ", "_", $fvalue)).'" class="RadioButtonOption';
					if ($fvalue == $default_value) {
						$content .= ' Selected';
					}					
					$content .= '"></span><span class="boxvalue">'.$fvalue.'</span></li>';
				}
				$content .= '</ul>';

			break;
			
			// check boxes
			case 'check boxes': 
			
				$values = explode(',', $contact_field->FieldValues);
				$defaultvalues = explode(',', $contact_field->FieldDefaultValues);
				$content .= '<ul>';
				$checknr = 1;
				foreach ($values as $fvalue) {
					
					$checked = 0;
					foreach($defaultvalues as $defaultvalue) {
						if ($defaultvalue == $fvalue) {
							$checked = 1;
						}
					}
					
					$content .= '<li>';
					$content .= '<input type="hidden" name="field_'.$contact_field->ID.'_'.$checknr.'" value="';
					if (($vars['sent'] == 1 && $_POST['field_'.$contact_field->ID.'_'.$checknr] == str_replace("'", "yyy", str_replace(" ", "_", $fvalue))) || ($vars['sent'] != 1 && $checked == 1)) {
						$content .= str_replace("'", "yyy", str_replace(" ", "_", $fvalue));
					}								
					$content .= '" />';
					$content .= '<span id="'.str_replace("'", "yyy", str_replace(" ", "_", $fvalue)).'" class="CheckboxOption';
					if (($vars['sent'] == 1 && $_POST['field_'.$contact_field->ID.'_'.$checknr] == str_replace("'", "yyy", str_replace(" ", "_", $fvalue))) || ($vars['sent'] != 1 && $checked == 1)) {
						$content .= ' Selected';
					}								
					$content .= '"></span><span class="boxvalue">'.$fvalue.'</span></li>';
					$checknr++;
				}			
				$content .= '</ul>';
			break;						
			
			// textarea
			case 'textarea': 
				if ($_POST['field_'.$contact_field->ID]) {
					$default_value = $_POST['field_'.$contact_field->ID];
				}
				else {
					$default_value = $contact_field->FieldDefaultValues;
				}						
			 $content .= '<textarea cols="10" rows="5" name="field_'.$contact_field->ID.'">'.$default_value.'</textarea>';
			break;						
			
		}
		$content .= '</div>';
		
	}

	// autocopy field
	if ($contact_info[0]->AutoCopy == 1) {
		$content .= '<label></label>';
		$content .= '<div class="field">';
		$content .= '<ul>';
		$content .= '<li>';
		$content .= '<input type="hidden" name="autocopy" value="';
		if ($vars['sent'] == 1 && $_POST['autocopy'] == 'autocopy') {
			$content .= 'autocopy';
		}
		$content .= '" />';
		$content .= '<span id="autocopy" class="CheckboxOption';
		if ($vars['sent'] == 1 && $_POST['autocopy'] == 'autocopy') {
			$content .= ' Selected';
		}								
		$content .= '"></span><span class="boxvalue">'.$contact_info[0]->AutoCopyText.'</span>';		
		$content .= '</li>';
		$content .= '</ul>';
		$content .= '</div>';
	}
	
	// captcha
	if ($contact_info[0]->Captcha == 1) {
		$content .= '<label></label>';
		$content .= '<div class="field">';
			$content .= '<img style="margin-bottom: 5px;" src="'.get_template_directory_uri().'/includes/contact_forms/captcha.php" />';
		$content .= '</div>';
		
		$content .= '<label></label>';
		$content .= '<div class="field">';
			$content .= '<input class="input" type="text" name="norobot" />';
		$content .= '</div>';
		
	}

	$content .= '<div class="cleaner"></div>';
	$content .= '<input type="submit" class="button" value="'.$contact_info[0]->ButtonText.'" />';
	$content .= '</form>';
	$content .= '</div>';
	$content .= '<div class="Cleaner"></div>';
	
	return $content;
	
}

add_shortcode('contact', 'contact_form');

function message_box_error($atts, $content) {

	extract(shortcode_atts(array( ), $atts));
	
	return '<div class="message_box_error">'.$content.'</div><div class="cleaner"></div>';
	
}

add_shortcode('message_box_error', 'message_box_error');

function message_box_success($atts, $content) {
	extract(shortcode_atts(array( ), $atts));
	
	return '<div class="message_box_success">'.$content.'</div><div class="cleaner"></div>';
	
}

add_shortcode('message_box_success', 'message_box_success');

?>