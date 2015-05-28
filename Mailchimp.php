<?php
/*------------------------------------------------------------------------------
** File:            Mailchimp.php
** Class:           Mailchimp
** Description:     Simple API class to interact with Mailchimp
** Dependencies:    None
** Version:         1.0
** Created:         10-Sep-2014
** Updated:         10-Sep-2014
** Author:          Bennett Stone
** Homepage:        www.phpdevtips.com 
**------------------------------------------------------------------------------
**
** Uses V2 of the mailchimp API: http://apidocs.mailchimp.com/api/2.0/
**
** Usage:
**
** require_once( 'Mailchimp.php' );
** $chimp = new Mailchimp( 'Mailchimp API Key', 'List ID' );
** 
** $data = array(
**     'FNAME' => 'John', 
**     'LNAME' => 'Doe'
** );
** $added = $chimp->add_user( 'user@email.com', $data );
**
** Initialize the class with signup and removal params set:
**
** $vars = array( 
**    'double_optin' => false, 
**    'send_welcome' => true, 
**    'update_existing' => true, 
**    'delete_member' => true, 
**    'send_goodbye' => true, 
**    'send_notify' => true
** );
** $chimp = new Mailchimp( 'Mailchimp API Key', 'List ID', $vars );
**
** Get user info:
** $user = $chimp->get_user( 'user@email.com' );
**
** Delete a user:
** $user = $chimp->unsubscribe( 'user@email.com' );
**
**------------------------------------------------------------------------------ */

class Mailchimp {

    private $api_key;
    private $list_id;
    private $endpoint = 'https://[DC].api.mailchimp.com/2.0';
    private $config_keys = array(
        'double_optin' => false,    //Require mailchimp to confirm user signup
        'send_welcome' => true,     //Send initial mailchimp welcome email
        'update_existing' => true,  //Update any existing users on repeat signup attempt
        'delete_member' => false,   //Delete unsubscribing members, 
        'send_goodbye' => true,     //Send unsubscribing user a goodbye email
        'send_notify' => true       //Send unsub notification to owner email as defined in mailchimp
    );
    
    
    /**
    * Construct function
    * @access public
    * @param array $vars
    * @return mixed
    */
    public function __construct( $api_key, $list_id, $vars = array() )
    {
        if( empty( $api_key ) || empty( $list_id ) )
        {
            throw new Exception( 'Please enter your mailchimp API key and list ID' );
        }
        
        $this->api_key = $api_key;
        $this->list_id = $list_id;
        
        foreach( $this->config_keys as $key => $default )
        {
            if( isset( $vars[$key] ) )
            {
                $this->settings[$key] = $vars[$key];
            }
            else
            {
                $this->settings[$key] = $default;
            }
        }
    } //end __construct()
    
    
    /**
     * Function to determine the appropriate endpoint for a given request
     * @access private
     * @param none
     * @return string
     */
    private function endpoint()
    {
        
        list( $key, $protocol ) = explode( '-', $this->api_key );
        $this->endpoint = str_replace( '[DC]', $protocol, $this->endpoint );
        return $this->endpoint;
        
    } //end endpoint()
    
    
    /**
     * Function to add a user (or update if the user exists and the param says so)
     * @access public
     * @param string $email
     * @param array $data
     * @return json
     */
    public function add_user( $email, $data = array() )
    {
        if( empty( $email ) || !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
        {
            return json_encode( array( 'success' => false, 'message' => 'Invalid email' ) );
        }
        
        $args = array(
            'apikey' => $this->api_key, 
            'id' => $this->list_id, 
            'email' => array(
                'email' => $email
            )
        );
        
        if( !empty( $data ) && is_array( $data ) )
        {
            $args['merge_vars'] = $data;
        }
        
        $response = $this->send_request( $args, 'subscribe' );
        if( isset( $response->error ) && !empty( $response->error ) )
        {
            return json_encode( array( 'success' => false, 'message' => $response->error ) );
        }
        else
        {   
            $message = 'Got it, you\'ve been added to our email list.';
            return json_encode( array( 'success' => true, 'message' => $message, 'extra' => $response ) );
        }
    } //end add_user()
    
    
    /**
     * Function to retrieve data for a user
     * @access public
     * @param string $email
     * @return array
     */
    public function get_user( $email )
    {
        $args = array(
            'apikey' => $this->api_key, 
            'id' => $this->list_id, 
            'emails' => array(
                array(
                    'email' => $email
                )
            )
        );
        
        $response = $this->send_request( $args, 'member-info' );
        if( $response['success_count'] > 0 && isset( $response['data'] ) )
        {
            return json_encode( array( 'success' => true, 'data' => $response['data'] ) );
        }
        else
        {
            return json_encode( array( 'success' => false, 'message' => 'You are not currently subscribed to any lists. Please signup first.' ) );
        }
        
    } //end get_user()
    
    
    public function unsubscribe( $email )
    {
        $args = array(
            'apikey' => $this->api_key, 
            'id' => $this->list_id, 
            'email' => array(
                'email' => $email
            )
        );
        $response = $this->send_request( $args, 'unsubscribe' );
        if( isset( $response['status'] ) && $response['status'] == 'error' )
        {
            return $response['error'];
        }
        else
        {
            return true;
        }
    }
    
    
    /**
     * Function to send the actual request to mailchimp to perform an action
     * @access private
     * @param array $args
     * @param string $type ('subscribe', 'member-info', 'unsubscribe')
     * @return mixed
     */
    private function send_request( $args = array(), $type = 'subscribe' )
    {
        $url = $this->endpoint().'/lists/'.$type.'.json';
        
        $end_data = array_merge( $this->settings, $args );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $end_data ) );
        $result = curl_exec( $ch );
        curl_close( $ch );

        return $result ? json_decode( $result, true ) : false;
        
    } //end send_request()

} //end class Mailchimp