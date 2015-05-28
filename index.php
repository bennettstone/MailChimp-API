<?php
/**
 * index.php
 * Provides example form, includes jquery, and handles the form submission to mailchimp
 */
 
 
require_once( 'Mailchimp.php' );

$chimp = new Mailchimp( 'YOURAPIKEY-us9', 'YOURLISTID', array( 'send_welcome' => false ) );

$data = array(
    'FNAME' => 'Firstname', 
    'LNAME' => 'Lastname'
);
//$added = $chimp->add_user( 'you@email.com', $data );


//$user = $chimp->get_user( 'you@email.com' );

//$user = $chimp->unsubscribe( 'you@email.com' );

echo '<pre>';
print_r( $added );
echo '</pre>';