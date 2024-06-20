<?php
/*
 * Config fields
 * You should configure this before using this contact form.
 */
$recipientEmailAddress = 'test@mail.com';
$recipientName = 'YOUR_NAME_HERE';

// Configure the message that will appear to the user after he fills and sends the form
$thankYouMessage = 'Thank you for your message. We will be getting back to you as soon as possible.';
// Configure the email subject that will appear in your email application. Please note that you can override this by setting the 'isSubjectField' to true
$emailSubject = 'New form submission.';

/*
* Fields configuration for validation
*/
$fieldsConfig = array(
	// request a quote form
	'cf_1' => array(
		array(
			'fieldId' => 'name',
			'fieldName' => 'Name',
			'isEmailField' => false,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the name field'
			)
		),
		array(
			'fieldId' => 'email',
			'fieldName' => 'Email',
			'isEmailField' => true,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the email field',
				'is_valid_email' => 'Please enter a valid email',
			)
		),
		array(
			'fieldId' => 'phone',
			'fieldName' => 'Phone',
			'isEmailField' => false,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the phone field'
			)
		),
		array(
			'fieldId' => 'date',
			'fieldName' => 'Date',
			'isEmailField' => false,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the date field'
			)
		),
		array(
			'fieldId' => 'time',
			'fieldName' => 'Time',
			'isEmailField' => false,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the date field'
			)
		),
		array(
			'fieldId' => 'service',
			'fieldName' => 'Service',
			'isEmailField' => false,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the date field'
			)
		),
		array(
			'fieldId' => 'clinic',
			'fieldName' => 'Clinic',
			'isEmailField' => false,
			'isSubjectField' => false,
			'validations' =>  array(
				'value_not_empty' => 'Please specify a value for the date field'
			),
		)
	)
);

$additionalHeaders = array(
	'MIME-Version: 1.0',
	'Content-Type: text/html; charset=UTF-8'
);

/* You can add new allowed tags or remove the existing one, inside the quotes below */
$allowedTags = '<div><p><span><h1><h2><h3><h4><h5><h6><br><hr><code><pre><blockquote><cite>';
$debug = false;

function jsonSuccess( $data ) {
	header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	if( ! empty( $_POST['isAjaxForm'] ) ){
		exit( json_encode( array( 'success' => true, 'data' => $data ) ) );
	}
	exit( $data );

}
function jsonError( $data ) {
	header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	if( is_string($data)){
		$data = array( $data );
	}


	if( ! empty( $_POST['isAjaxForm'] ) ){
		exit( json_encode( array( 'success' => false, 'data' => $data ) ) );
	}
	else{
		exit( implode("\n", $data ) );
	}



}
function value_not_empty( $value ){
	return ( ! empty( $value ) );
}

function is_valid_email( $value ){
	return filter_var($value, FILTER_VALIDATE_EMAIL);
}
function esc_html( $value, $stripTags = true, $allowTags = '' ) {
	if( $stripTags ){
		$value = strip_tags($value, $allowTags );
	}
	return trim( $value );
}
function unescape_html( $value ) {
	return stripslashes( $value );
}

if( 'POST' != strtoupper($_SERVER['REQUEST_METHOD']) ){
	jsonError( 'Invalid Request.' );
}



// Setup the post fields list
$postFields = $_POST;

// Ignore fields that should'n't be sent
$defaultFields = array(
	'cf_type'
);

// Filter post fields so only allowed one can be sent
foreach( $fieldsConfig as $formId => $formConfig ){
	foreach( $formConfig as $value){
		array_push($defaultFields, $value['fieldId']);
	}
}

// Make sure the fields are there
if( empty($postFields)) {
	jsonError( 'Invalid request, fields are missing.');
}

if( ! isset($postFields['cf_type'])){
	jsonError( 'Invalid Request: Form type is missing' );
}

if( ! array_key_exists( $postFields['cf_type'], $fieldsConfig ) ){
	jsonError( 'Invalid Request: Invalid form type' );
}
$cfType = $postFields['cf_type'];

// Holds the errors generated during the request
$errors = array();

// Sanitize data
foreach( $postFields as $fieldName => &$fieldValue ) {
	$fieldValue = esc_html( $fieldValue, true, $allowedTags );
	// Unescape fields
	unescape_html($fieldValue);
}

// Validate request
foreach( $fieldsConfig[$cfType] as $field ) {
	if( isset( $postFields[$field['fieldId']] ) ){
		// Loop trough each validation and call it
		if( is_array( $field['validations'] ) ){
			foreach( $field['validations'] as $fn => $errMessage) {
				$result = call_user_func( $fn, $postFields[$field['fieldId']] );
				if( ! $result ){
					array_push( $errors, $errMessage );
				}
			}
		}

	}
}

// Check for errors
if( ! empty($errors)){
	jsonError( $errors );
}

$userEmail = '';
$message = '';

// Build email message
foreach( $fieldsConfig[$cfType] as $field ){
	// Check to see if this is the user email field
	if( isset( $field['isEmailField'] ) && $field['isEmailField'] ){
		$userEmail = $postFields[$field['fieldId']];
	}

	// Check to see if this is a dynamic email subject
	if( isset( $field['isSubjectField'] ) && $field['isSubjectField'] ){
		$emailSubject = $postFields[$field['fieldId']];
	}

	if( ! isset( $postFields[$field['fieldId']] ) ){
		continue;
	}

	// Build the message
	$message .= $field['fieldName'] . ':'. $postFields[$field['fieldId']] . "\n";
}

// Configure headers
array_push( $additionalHeaders, sprintf( 'From: %s<%s>', $recipientName, $recipientEmailAddress ) );

// Only add the reply to if we have a user email configured
if ( $userEmail ){
	array_push( $additionalHeaders, sprintf( 'Reply-to: %s', $userEmail ) );
}
array_push( $additionalHeaders, 'X-Mailer: PHP/' . phpversion() );
$additionalHeaders = implode( "\r\n", $additionalHeaders);

$result = @mail( $recipientEmailAddress, $emailSubject, $message, $additionalHeaders );
if( true != $result ) {
	if( $debug ){
		jsonError($result);
	}
	else {
		jsonError('An error occurred, please try again later.');
	}
}

jsonSuccess($thankYouMessage);
