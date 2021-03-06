<?php

/**
 * FooWidget Class
 */
class ContactForm extends WP_Widget {
    /** constructor */
    function ContactForm() {
        parent::WP_Widget(false, $name = 'ContactForm');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
       
       
       								<?php get_contact_widget(); ?>
       
                  
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
       
       return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        global $wpdb;
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wpfw'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            	<p><label><?php _e('Form name:', 'wpfw'); ?> 
            	<?php

							$forms = $wpdb->get_results("SELECT * FROM es_forms");
							?>
							<select name="es_form_name" id="es_form_name">
							<?php
							foreach ($forms as $form) {
								?>
								<option value="<?php echo $form->Name; ?>"><?php echo $form->Name; ?></option>
							<?php
							}
							?>
							</select>
            	</label></p>
        <?php 
    }

} // class SocialWidget

add_action('widgets_init', create_function('', 'return register_widget("ContactForm");'));

?>