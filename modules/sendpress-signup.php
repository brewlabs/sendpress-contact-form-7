<?php
/**
 * SendPress - Contact Form 7 Integration
 */


add_action( 'init', 'wpcf7_add_shortcode_spnl', 5 );

function wpcf7_add_shortcode_spnl() {
	if( function_exists('wpcf7_add_shortcode') ) {
		wpcf7_add_shortcode( array( 'spnl', 'spnl*' ),
			'wpcf7_spnl_shortcode_handler', true );
	}
}

function wpcf7_spnl_shortcode_handler( $tag ) {

	// if cf7 is not active, leave
	if( ! class_exists( 'WPCF7_Shortcode' ) )
		return;

	// create a new tag
	$tag = new WPCF7_Shortcode( $tag );

	// if the tag doesn't have a name, return empty handed
	if( empty( $tag->name ) ) 
		return '';

	// check for errors and set the class
	$validation_error = wpcf7_get_validation_error( $tag->name );
	$class = wpcf7_form_controls_class( $tag->type );

	// if there were errors, add class
	if( $validation_error )
		$class .= ' wpcf7-not-valid';

	// init the atts array, add the class and id set in the shortcode
	$atts 				= array();
	$atts[ 'class' ]	= $tag->get_class_option( $class );
	$atts[ 'id' ]		= $tag->get_option( 'id', 'id', true );

	// get checkbox value
	// first get all of the lists
	$lists = wpcf7_spnl_get_lists();
	if( ! empty( $lists ) ) {

		$checkbox_values = array();
			
		// go through each list
		foreach( $lists as $l ) {

			// check if that list was added to the form
			if( $tag->has_option( 'sendpress_list_' . $l->ID ) ) {

				// add the list into the array of checkbox values
				$checkbox_values[] = 'sendpress_list_'. $l->ID;
			}
		}
	}

	// do we have any lists?
	if( ! empty ( $checkbox_values ) ) {

		// implode them all into a comma separated string
		$atts[ 'value' ] = implode( $checkbox_values, ',' );

	} else {

		// we apparently have no lists
		// set a 0 so we know to add the user to sendpress but not to any specific list
		$atts[ 'value' ] = '0';
	}

	// is it required?
	if( $tag->is_required() )
		$atts[ 'aria-required' ] = 'true';

	// set default checked state
	$atts[ 'checked' ] = ( $tag->has_option( 'default:on' ) ) ? 'checked' : '';

	// default tag value
	$value = (string) reset( $tag->values );

	// if the tag has a default value, add it
	if( '' !== $tag->content )
		$value = $tag->content;

	// if the tag has a posted value, add it
	if( wpcf7_is_posted() && isset( $_POST[ $tag->name ] ) ) {
		// $value = stripslashes_deep( $_POST[ $tag->name ] );
		$atts[ 'checked' ] = 'checked';
	} elseif ( wpcf7_is_posted() && ! isset( $_POST[ $tag->name ] ) ) {
		$atts[ 'checked' ] = '';
	}
	
	// set the name and the id of the field
	$atts[ 'name' ]	= $tag->name;
	$id 			= ( ! empty( $atts[ 'id' ] ) ) ? $atts[ 'id' ] : $atts[ 'name' ];

	// put all of the atts into a string for the field
	$atts = wpcf7_format_atts( $atts );

	// get the content from the tag to make the checkbox label
	$label 	= __( 'Sign up for the newsletter', 'mpcf7' );
	$values = $tag->values;
	if( isset( $values ) && ! empty( $values ) )
		$label = esc_textarea( $values[0] );

	// should the label be inside the span?
	if( $tag->has_option( 'label-inside-span' ) ) {

		// create the field
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap %1$s"><input type="checkbox" %2$s />&nbsp;<label for="%3$s">%4$s</label></span>&nbsp;%5$s',
			$tag->name, $atts, $id, $value, $validation_error );

	} else {

		// create the field
		$html = sprintf(
			'<span class="wpcf7-form-control-wrap %1$s"><input type="checkbox" %2$s />&nbsp;</span><label for="%3$s">%4$s</label>&nbsp;%5$s',
			$tag->name, $atts, $id, $value, $validation_error );
	}

	return $html;
}


/* Validation filter */

add_filter( 'wpcf7_validate_spnl', 'wpcf7_spnl_validation_filter', 10, 2 );
add_filter( 'wpcf7_validate_spnl*', 'wpcf7_spnl_validation_filter', 10, 2 );

function wpcf7_spnl_validation_filter( $result, $tag ) {

	// make sure that CF7 is installed and active
	if( ! class_exists( 'WPCF7_Shortcode' ) )
		return;

	// 
	$tag = new WPCF7_Shortcode( $tag );

	// get the type and name
	$type = $tag->type;
	$name = $tag->name;

	// if the tag was posted, set it
	$value = isset( $_POST[ $name ] ) ? ( string ) $_POST[ $name ] : '';

	// if it's required
	if( 'spnl*' == $type ) {

		// and empty
		if( '' == $value ) {

			// then fail it
			$result[ 'valid' ] = false;
			$result[ 'reason' ][ $name ] = wpcf7_get_message( 'invalid_required' );
		}
	}

	return $result;
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7_add_tag_generator_spnl', 20 );

function wpcf7_add_tag_generator_spnl() {

	if( ! class_exists( 'WPCF7_TagGenerator' ) )
		return;

	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 'spnl', __( 'SendPress Signup', 'mpcf7' ),
		'wpcf7_tg_pane_spnl' );
}

function wpcf7_tg_pane_spnl( $contact_form, $args = '' ) {

	$args = wp_parse_args( $args, array() );
	$type = 'spnl';

	$description = __( "SendPress Signup Form.", 'mpcf7' );

	$desc_link = '';

	?>

	<div class="control-box">
		<fieldset>
			<legend><?php echo esc_html( $description ); ?></legend>

			<table class="form-table">
				<tbody>
				 	<tr>
						<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>
						<td>
							<fieldset>
							<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
							<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
							</fieldset>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo esc_html( __( 'SendPress Lists', 'contact-form-7' ) ); ?></th>
						<td>					
							<?php
							// print sendpress lists
							echo wpcf7_spnl_get_list_inputs();
							?>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args[ 'content' ] . '-default:on' ); ?>"><?php echo esc_html( __( 'Checked by Default', 'contact-form-7' ) ); ?></label></th>
						<td><input type="checkbox" name="default:on" class="option" />&nbsp;<?php echo esc_html( __( "Make this checkbox checked by default?", 'contact-form-7' ) ); ?></td>
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args[ 'content' ] . '-values' ); ?>"><?php echo esc_html( __( 'Checkbox Label', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args[ 'content' ] . '-values' ); ?>" /><br />
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args[ 'content' ] . '-label-inside-span' ); ?>"><?php echo esc_html( __( 'Label Inside Span', 'contact-form-7' ) ); ?></label></th>
						<td><input type="checkbox" name="label-inside-span" class="option" />&nbsp;<?php echo esc_html( __( "Place the label inside the control wrap span?", 'contact-form-7' ) ); ?></td>
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args[ 'content' ] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args[ 'content' ] . '-name' ); ?>" /></td>
					</tr>

					<tr>
						<th scope="row"><label for="<?php echo esc_attr( $args[ 'content' ] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>
						<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args[ 'content' ] . '-id' ); ?>" /></td>
					</tr>

					<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args[ 'content' ] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args[ 'content' ] . '-class' ); ?>" /></td>
					</tr>

				</tbody>
			</table>
		</fieldset>
	</div>

	<div class="insert-box">
		<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

		<div class="submitbox">
			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
		</div>

		<br class="clear" />

		<p class="description mail-tag"><label for="<?php echo esc_attr( $args[ 'content' ] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args[ 'content' ] . '-mailtag' ); ?>" /></label></p>
	</div>

	<?php
}

/* Process the form field */

add_action( 'wpcf7_before_send_mail', 'wpcf7_sendpress_before_send_mail' );

function wpcf7_sendpress_before_send_mail( $contactform ) {

	// make sure the user has sendpress (Wysija) and CF7 installed & active
	if( ! class_exists( 'SendPress' ) || ! class_exists( 'WPCF7_Submission' )  )
		return;

	// 
	if( ! empty( $contactform->skip_mail ) )
		return;

	// 
	$posted_data = null;
		
	// get the instance that was submitted and the posted data
	$submission 	= WPCF7_Submission::get_instance();
	$posted_data 	= ( $submission ) ? $submission->get_posted_data() : null;

	// and make sure they have something in their contact form
	if( empty( $posted_data ) )
		return;

	// get the tags that are in the form
	$manager 		= WPCF7_ShortcodeManager::get_instance();
	$scanned_tags 	= $manager->get_scanned_tags();

	// let's go add the user
	wpcf7_sendpress_subscribe_to_lists( $posted_data, $scanned_tags );
}

function wpcf7_sendpress_subscribe_to_lists( $posted_data, $scanned_tags = array() ) {

	// set defaults for sendpress user data
	$user_data = array(
		'email'		=> '',
		'firstname' => '',
		'lastname'  => ''
	);

	// get form data
	$user_data[ 'email' ]		= isset( $posted_data[ 'your-email' ] ) ? trim( $posted_data[ 'your-email' ] ) : '';
	$user_data[ 'firstname' ]	= isset( $posted_data[ 'your-name' ] )	? trim( $posted_data[ 'your-name' ] ) : '';
	if( isset( $posted_data[ 'your-first-name' ] ) && ! empty( $posted_data[ 'your-first-name' ] ) )
		$user_data[ 'firstname' ] = trim( $posted_data[ 'your-first-name' ] );
	
	if( isset( $posted_data[ 'your-last-name' ] ) && ! empty( $posted_data[ 'your-last-name' ] ) )
		$user_data[ 'lastname' ] = trim( $posted_data[ 'your-last-name' ] );
	
	// set up our arrays
	$sendpress_signups 	= array();
	$sendpress_lists   	= array();

	// go through each tag and find our tags
	foreach( $scanned_tags as $tag ) {

		// if it's our tag, add it to the array
		if( $tag[ 'basetype' ] == 'spnl' )
			$sendpress_signups[] = $tag[ 'name' ];
	}

	// if we have signup fields
	if( ! empty( $sendpress_signups ) ) {

		// go through each field
		foreach( $sendpress_signups as $sendpress_signup_field ) {
			
			// trim off our extra data
			$_field = str_replace( 'sendpress_list_', '', trim( $posted_data[ $sendpress_signup_field ] ) );

			// add the list id to the array
			if( ! empty ( $_field ) )
				$sendpress_lists = array_unique( array_merge( $sendpress_lists, explode( ',', $_field ) ) );
		}
	}

	// if we have no lists, exit
	if( empty( $sendpress_lists ) )
		return;

	// configure the list
	$data = array(
		'user'		=> $user_data,
		'user_list' => array( 'list_ids' => $sendpress_lists )
	);

	// if akismet is set make sure it's valid
	$akismet = isset( $contactform->akismet ) ? (array) $contactform->akismet : null;
	$akismet = $akismet; // temporarily, not in use!

	// add the subscriber to the Wysija list
	SendPress_Data::subscribe_user(implode(",", $sendpress_lists), $user_data[ 'email' ], $user_data[ 'firstname' ], $user_data[ 'lastname' ]);
}


/**
 * Create a formatted list of input's for the CF7 tag generator
 *
 * @return string
 */
function wpcf7_spnl_get_list_inputs ( ) {

	$html = '';

	// get lists
	$lists = wpcf7_spnl_get_lists();

	if ( is_array( $lists ) && ! empty( $lists ) ) {
		foreach ( $lists as $l ) {

			// add input to returned html
			$input_name = wpcf7_spnl_get_list_input_field_name();
			$input = "<input type='checkbox' name='" . $input_name . "' class='option' />%s<br />";
			$html .= sprintf( $input, $l->ID, $l->post_title );
		}
	}

	return $html;
}


/**
 * Create input name for both the form processing & tag generation
 *
 * @return string
 */
function wpcf7_spnl_get_list_input_field_name ( ) {
	return 'sendpress_list_%d';
}



add_filter( 'wpcf7_mail_tag_replaced', 'wpcf7_mail_tag_replaced_spnl', 10, 2 );
/**
 *
 * @param string $replaced modifier
 * @param string $submitted submitted value from contact form
 * @return string
 */
function wpcf7_mail_tag_replaced_spnl($replaced, $submitted) {

	if (wpcf7_is_spnl_element($submitted)) {
		$list_names = wpcf7_get_list_names(explode(',', $submitted));
		$replaced = implode(', ', $list_names);
	}

	return $replaced;
}

/**
 * Get name of lists based on their ids
 * @param array $list_ids
 */
function wpcf7_get_list_names(Array $list_ids = array()) {
	// make sure we have the class loaded
	$sendpress_lists = array();
	$sendpress_lists_name= array();
	if (class_exists( 'SendPress' )) {
		// go through each field
		foreach( $list_ids as $sendpress_signup_field ) {
			
			// trim off our extra data
			$_field = str_replace( 'sendpress_list_', '', trim( $sendpress_signup_field ) );

			// add the list id to the array
			if( ! empty ( $_field ) )
				$sendpress_lists = array_unique( array_merge( $sendpress_lists, explode( ',', $_field ) ) );
		}
		$args = array('post__in' =>  $sendpress_lists);
		$r = SendPress_Data::get_lists($args);
		if( isset($r) ){
			foreach ($r->posts as $list) {
				$sendpress_lists_name[] = $list->post_title;
			}
		}
	}
	return $sendpress_lists_name;
}

/**
 * Make sure the current element is spnl
 * @param 	string $submitted submitted value from contact form
 * @return 	boolean
 */
function wpcf7_is_spnl_element( $submitted ) {

	// for Contact-Form-7 3.9 and above, http://contactform7.com/2014/07/02/contact-form-7-39-beta/
	if( class_exists( 'WPCF7_Submission' ) ) {

		// get the submission object
		$submission = WPCF7_Submission::get_instance();

		// if there's an object, get the posted data
		if( $submission )
			$posted_data = $submission->get_posted_data();

	} else {

		// if they are on an old version of cf7, get straight _POST data
		$posted_data = $_POST;
	}

	// if we have data
	if( ! empty( $posted_data ) ) {

		// find all of the keys in $posted_data that belong to sendpress-cf7's plugin
		$keys 				= array_keys( $posted_data );
		$sendpress_signups 	= preg_grep( '/^spnl.*/', $keys );

		// if we have posted fields
		if( ! empty( $sendpress_signups ) ) {

			// go through each one
			foreach( $sendpress_signups as $sendpress_signup_field ) {

				// get the value
				$_field = trim( $posted_data[ $sendpress_signup_field ] );

				// and see if it matches
				if( $_field == $submitted )
					return true;

			}
		}
	}
	return false;
}
// that's all folks!