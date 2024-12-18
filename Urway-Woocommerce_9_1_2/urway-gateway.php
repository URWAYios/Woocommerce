<?php

$bd=ABSPATH.'wp-content/plugins/'.dirname( plugin_basename( __FILE__ ) );
set_include_path($bd.'/Urway_Payment'.PATH_SEPARATOR.get_include_path());
require_once($bd."/urwayLib/urwaylib.php");
require_once dirname(__FILE__).'/config.php';


class Urway_Payment extends WC_Payment_Gateway {
   public $domain;
  // Constructor method
  public function __construct() {
    $this->id  = 'urway_payment';
  
    $this ->method_title = __('Urway Payment', 'urway_payment');
    $this->method_description = __('Accept payments through  Custom Gateway', 'Urway Payment');
    $this -> icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/urway.png';
       $this->domain = 'urway_payment';
    // Other initialization code goes here
    
    $this->init_form_fields();
    $this->init_settings();
  
      $this -> title = $this -> settings['title'];
      $this -> description = $this -> settings['description'];
      $this -> merchant_id = isset($this -> settings['merchant_id']);
      $this -> merchant_key = $this -> settings['merchant_key'];
      $this -> terminalId = isset($this -> settings['terminalId']);
      $this -> url = $this -> settings['url'];
      $this -> gateway_server = isset($this -> settings['gateway_server']);
      $this -> transaction_method = isset($this -> settings['transaction_method']);
      $this -> channel = $this -> settings['channel'];
      $this -> redirect_page_id = isset($this -> settings['redirect_page_id']);
      $this -> liveurl = 'http://www.abc.com';
      $this -> msg['message'] = "";
      $this -> msg['class'] = "";
      $this->options = array(
                'PG' => __( 'URWAY PG', $this->domain ),
            //     'STC' => __( 'STC PAY', $this->domain ),
            );
       //new for stc pay
      //add_action( 'woocommerce_checkout_create_order', array( $this, 'save_order_payment_type_meta_data' ), 10, 1 );
      add_action('init', array(&$this, 'check_urway_payment_response'));
      //update for woocommerce >2.0
      add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_urway_payment_response' ) );

      add_action('valid-urway_payment-request', array(&$this, 'SUCCESS'));
      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
      } else {
        add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
      }
      add_action('woocommerce_receipt_urway_payment', array(&$this, 'receipt_page'));
      add_action('woocommerce_thankyou_urway_payment',array(&$this, 'thankyou_page'));
      add_action('woocommerce_checkout_update_order_meta', 'save_transaction_type_meta');
      add_action( 'wp_enqueue_scripts', 'enqueue_custom_payment_styles' );
    

  }
  
  /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     **/
    public function admin_options(){
      echo '<h3>'.__('URWAY Payment Gateway', 'urway_payment').'</h3>';
      echo '<p>'.__('URWAY is most popular payment gateway for online shopping in KSA').'</p>';
      echo '<table class="form-table">';
      $this -> generate_settings_html();
      echo '</table>';

    }
    /**
     *  There are no payment fields for urway_payment, but we want to show the description if set.
     **/
   //new for stc pay
   function payment_fields(){
      if($this -> description)
    {  
    echo wpautop(wptexturize($this -> description));
      
    echo '<style>#transaction_type_field label.radio { display:inline-block; margin:0 .8em 0 .4em}</style>';

            $option_keys = array_keys($this->options);

            woocommerce_form_field( 'transaction_type', array(
                'type'          => 'radio',
                'class'         => array('transaction_type form-row-wide'),
                'label'         => __('Payment Type', $this->domain),
                'options'       => $this->options,
            ), reset( $option_keys ) );
    }
    }
    //  function payment_fields(){
    //   if($this -> description) echo wpautop(wptexturize($this -> description));
    // }
    
    
  //new for stc pay
   /**
         * Save the chosen payment type as order meta data.
         *
         * @param object $order
         * @param array $data
         */
        // public function save_order_payment_type_meta_data( $order) {
        //     if ( isset($_POST['transaction_type']) )
        //         $order->update_meta_data('_transaction_type', esc_attr($_POST['transaction_type']) );
        // }

    
  public function init_form_fields() {
    
    $this->form_fields = array(
      'enabled' => array(
        'title'   => __('Enable/Disable', 'Urway Payment'),
        'type'    => 'checkbox',
        'label'   => __('Enable My Custom Gateway', 'urway_payment'),
        'default' => 'yes',
      ),
      'title' => array(
            'title' => __('Title:', 'Urway Payment'),
            'type'=> 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'urway_payment'),
            'default' => __('Urway Payment', 'Urway Payment')),
          'description' => array(
            'title' => __('Description:', 'urway_payment'),
            'type' => 'textarea',
            'description' => __('This controls the description which the user sees during checkout.', 'urway_payment'),
            'default' => __('Pay securely by Credit or Debit card or net banking through Urway Payment Secure Servers.', 'Urway Payment')),      
      'password' => array(
            'title' => __('Password', 'urway_payment'),
            'type' => 'text',
            'description' =>  __('Password.', 'urway_payment')
            ),      
      'merchant_key' => array(
            'title' => __('Merchant Key', 'urway_payment'),
            'type' => 'text',
            'description' =>  __('Merchant Key.', 'urway_payment')
            ),      
      'aggregator_id' => array(
            'title' => __('Terminal ID', 'urway_payment'),
            'type' => 'text',
            'description' =>  __('Terminal ID.', 'urway_payment')
            ),
        'transaction_method' => array(

            'title' => __('Transaction Type', 'urway_payment'),

            'type' => 'select',

            'options' => array("1"=>"Purchase","4"=>"Authorization","13"=>"STC Pay"),

            'description' => __('Transaction Type','woocom_plugin')

            ),
        /*  'gateway_server' => array(
            'title' => __('Gateway Server', 'urway_payment'),
            'type' => 'select',
            'options' => array("0"=>"Select","sandbox"=>"Sandbox","live"=>"Live"),
            'description' => __('urway_payment Gateway module as activated by urway_paymentPay.','urway_payment')
            ),*/
      'url' => array(
            'title' => __('Request URL', 'urway_payment'),
            'type' => 'text',
            'description' =>  __('Request URL.', 'urway_payment')
            ),
          
          'channel' => array(
            'title' => __('Channel', 'urway_payment'),
            'type' => 'select',
            'options' => array("0"=>"Select","WEB"=>"Web","MOBILE"=>"Mobile"),
            'description' => __('Channel.','urway_payment')
            )
        
    );
  }
function receipt_page($order){
      echo '<p>'.__('Thank you for your order, please click the button below to pay with urway_payment.', 'urway_payment').'</p>';
      echo $this -> generate_urway_payment_form($order);
    }
    function thankyou_page($order){

      //echo '<p>'.__('Thank you for your order.', 'woocom_plugin').'</p>';

      //echo $this -> generate_woocom_plugin_form($order);

    }
  
  // Process the payment
  // public function process_payment($order_id) {
  //   $order = wc_get_order($order_id);
  //   $payment_type = $order->get_meta('_transaction_type');
  //   //echo $payment_type;die();
  //     return array('result' => 'success', 
  //       'redirect' => add_query_arg(
  //         'order-pay',
  //         $order->get_id(),
  //         $order->get_checkout_payment_url(true),
  //         add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))
  //       )
  //       )
  //     );
   
  // }
  public   function process_payment($order_id){
      $order = new WC_Order($order_id);
      if ( $order->has_status( 'failed' ) ) {
        $order->update_status( 'pending', __( 'Retrying payment. Order status changed to pending.', 'urway_payment' ) );
    }
    return array('result' => 'success', 'redirect' => add_query_arg('order-pay',
        $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay')))));
  }
   public function result($order,$result)
    {
      if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
        $link = "https"; 
      else
        $link = "http"; 
  
      // Here append the common URL characters. 
      $link .= "://"; 
  
      // Append the host(domain name, ip) to the URL. 
      $link .= $_SERVER['HTTP_HOST']; 
  
// Append the requested resource location to the URL 
 $link .= $_SERVER['REQUEST_URI']; 
  $link = preg_split( "/(\?|!)/", $link );  
    echo '
    <html>
<head>
<style>
.text-danger strong {
        color: #9f181c;
    }
    .receipt-main {
      background: #ffffff none repeat scroll 0 0;
      border-bottom: 12px solid #333333;
      border-top: 12px solid #9f181c;
      margin-top: 50px;
      margin-bottom: 50px;
      padding: 30px 30px !important;
      position: relative;
      box-shadow: 0 1px 21px #acacac;
      color: #333333;
      font-family: open sans;
    }
    .receipt-main p {
      color: #333333;
      font-family: open sans;
      line-height: 1.42857;
    }
    .receipt-footer h1 {
      font-size: 15px;
      font-weight: 400 !important;
      margin: 0 !important;
    }
    .receipt-main::after {
      background: #414143 none repeat scroll 0 0;
      content: "";
      height: 5px;
      left: 0;
      position: absolute;
      right: 0;
      top: -13px;
    }
    .receipt-main thead {
      background: #414143 none repeat scroll 0 0;
    }
    .receipt-main thead th {
      color:#fff;
    }
    .receipt-right h5 {
      font-size: 16px;
      font-weight: bold;
      margin: 0 0 7px 0;
    }
    .receipt-right p {
      font-size: 12px;
      margin: 0px;
    }
    .receipt-right p i {
      text-align: center;
      width: 18px;
    }
    .receipt-main td {
      padding: 9px 20px !important;
    }
    .receipt-main th {
      padding: 13px 20px !important;
    }
    .receipt-main td {
      font-size: 13px;
      font-weight: initial !important;
    }
    .receipt-main td p:last-child {
      margin: 0;
      padding: 0;
    } 
    .receipt-main td h2 {
      font-size: 20px;
      font-weight: 900;
      margin: 0;
      text-transform: uppercase;
    }
    .receipt-header-mid .receipt-left h1 {
      font-weight: 100;
      margin: 34px 0 0;
      text-align: right;
      text-transform: uppercase;
    }
    .receipt-header-mid {
      
      overflow: hidden;
    }
    
    #container {
      background-color: #dcdcdc;
    }
</style>
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<!------ Include the above in your HEAD tag ---------->
</head>
<body>';
if($result!='Fraud'){
echo '
<div class="container">
  <div class="row">
    
        <div class="receipt-main col-xs-10 col-sm-10 col-md-6 col-xs-offset-1 col-sm-offset-1 col-md-offset-3">
                
      <div class="row">
        <div class="receipt-header receipt-header-mid">';
        if($result=='UnSuccessful'){
        echo '<div style="background-color: red;" id="unsuccess"><h2 align="center">Your Transaction is '.$result.'</h2></div>';
        }
        else
        {
        echo '<div style="background-color: green;" id="success"><h2 align="center">Your Transaction is '.$result.'</h2></div>';
        }
        echo'
        
          <div class="col-xs-8 col-sm-8 col-md-8 text-left">
            <div class="receipt-right">
              <p><b>Email :</b> '.$order -> get_billing_email().'</p>
              <p><b>Address :</b> '.$order -> get_billing_address_1().'</p>
            </div>
          </div>
          <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="receipt-left">
              <h1>Receipt</h1>
            </div>
          </div>
        </div>
            </div>
      
            <div>
      ';
      foreach ($order->get_items() as $key => $lineItem) {
            
  echo '
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="col-md-9">'.$lineItem['name'].'</td>
                            <td class="col-md-3"><i class="fa fa-inr"></i> '.$lineItem['total'].'</td>
                        </tr>
                       
                     
                        <tr>
                            <td class="text-right">
                            <p>
                                <strong>Total Amount: </strong>
                            </p>
              </td>
                            <td>
                            <p>
                                <strong><i class="fa fa-inr"></i> '.$order -> get_total().' '. get_woocommerce_currency().'/-</strong>
                            </p> 
              </td>
                        </tr>
                   
                    </tbody>
                </table>';
        }
        echo '
            </div>
      
      <div class="row">
        <div class="receipt-header receipt-header-mid receipt-footer">
          <div class="col-xs-8 col-sm-8 col-md-8 text-left">
            <div class="receipt-right">
              <p><b>Date :</b> '.date("Y-m-d").'</p>
            </div>
          </div>
          <div><a href="'.$link[0].'"><h2 align="center">Back To Home</h2></a></div>
          <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="receipt-left">
              
            </div>
          </div>
        </div>
            </div>
      
        </div>    
  </div>
</div>';
}else{
    echo '
    <div class="container">
  <div class="row">
    
        <div class="receipt-main col-xs-10 col-sm-10 col-md-6 col-xs-offset-1 col-sm-offset-1 col-md-offset-3">
                
      <div class="row">
        <div class="receipt-header receipt-header-mid">
          <div style="background-color: red;"><h2 align="center">Thank you for shopping with us. This is fraud Transaction. Data is tempered. Please contact with administrator...</h2></div>
        </div>
        </div>
        </div>
        </div>
        </div>
    ';
    }
echo '
</body>
</html>
    ';
    
    die;
  }
      /**
     * Check for valid urway_payment server callback
     **/    
    function check_urway_payment_response(){
    global $woocommerce;
    
   
    //print_r($_GET) ;die();
    if(!empty($_GET['TranId']) )
    {

      $order    = new WC_Order($_GET['TrackId']);

      $orderStatus=$order->get_status();
      // $order -> update_status('pending');
      //echo $_GET['TranId'];die();
      $transauthorised = false;
      $merchant_key=$this->merchant_key;
      
      $requestHash ="".$_GET['TranId']."|".$merchant_key."|".$_GET['ResponseCode']."|".$_GET['amount']."";
  
      $hash=hash('sha256', $requestHash);
      $url= $this -> settings['url'];

      

      //Security API Call

      $host= gethostname();

      $ip = gethostbyname($host);

      $terminalId= $this -> settings['aggregator_id'];

      $password=$this -> settings['password'];

     

      $currencycode = get_woocommerce_currency();


      $txn_details1= "".$_GET['TrackId']."|".$terminalId."|".$password."|".$merchant_key."|".$_GET['amount']."|".$currencycode."";

      $requestHash1   = urwayLib::_Hashcreation($txn_details1);;  

      

      $apifields = array(

        'trackid' => $_GET['TrackId'],

        'terminalId' => $terminalId,

        'action' => '10',

        'merchantIp' =>$ip,
        'customerIp' =>$ip,

        'password'=> $password,
        
        'country'=>$order -> get_billing_country(),

        'currency' => $currencycode,

        'transid'=>$_GET['TranId'],

        'amount' => $_GET['amount'],

        'udf5'=>"",

        'udf3'=>"",

        'udf4'=>"",

        'udf1'=>"",

        'udf2'=>"",

        'requestHash' => $requestHash1

            );

    $apifields_string = json_encode($apifields);  
    //print_r($apifields_string);die();

    $ch = curl_init($url);

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

      curl_setopt($ch, CURLOPT_POSTFIELDS, $apifields_string);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($ch, CURLOPT_HTTPHEADER, array(

              'Content-Type: application/json',

              'Content-Length: ' . strlen($apifields_string))

            );

              curl_setopt($ch, CURLOPT_TIMEOUT, 5);

              curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);



              //execute post

              $apiresult = curl_exec($ch);
              
              //print_r($apiresult);die;

              $urldecodeapi=(json_decode($apiresult,true));

              $inquiryResponsecode=$urldecodeapi['responseCode'];

              $inquirystatus=$urldecodeapi['result'];

            
              //End Security API Call 

              //echo $orderStatus;die();

    
   if($orderStatus=='pending')

    {

      if($hash === $_GET['responseHash'] && ($_GET['Result']==="Successful" || $_GET['Result']==="SUCCESS" || $_GET['Result']==="Success"))

      { 
         // echo $inquirystatus;die();
          if($inquirystatus=='Successful'|| $inquirystatus=='SUCCESS' || $inquiryResponsecode=='000' || $inquirystatus=='Success'){

              $woocommerce -> cart -> empty_cart();
              $order    = new WC_Order($_GET['TrackId']);
        
              $transauthorised = true;
              $this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
              $this -> msg['class'] = 'woocommerce';
              $this ->msg['type']="info";
             
         
                  
              $order -> payment_complete();
              $order -> update_status('processing');
              $order -> add_order_note('Payment Gateway has processed the payment. Ref Number: '.$_GET['TrackId']);
              //$order -> add_order_note($this->msg['message']);
              // $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
                
        
            //$redirect_url=$redirect_url."checkout/order-received/".$_GET['TrackId']."/?key=".$order -> order_key;           
              // wp_redirect( $redirect_url );die;

              $order_received_url = $order->get_checkout_order_received_url();
              wp_redirect($order_received_url);
              //echo $this -> result($order,"Successful");die;
              exit;    

          }

          else

          {
              $this -> msg['class'] = 'woocommerce';
              $this -> msg['message'] = "Thank you for shopping with us. However, the payment has been declined.";
              $this -> msg['type']= "error";
        
              $order -> update_status('failed');
                  $order -> add_order_note('failed');
                  $order -> add_order_note($this->msg['message']);
              $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
        
             $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );          
            //$redirect_url=$this->get_return_url ($order);
            wc_add_notice($this->msg['message'], 'error');
            wp_redirect( $redirect_url );
            //echo $this -> result($order,"UnSuccessful");die;
            exit;   
            
            

          }

        

      }

      else

      {

        // echo $inquirystatus;die();

        if($inquirystatus=='UnSuccessful' || $inquirystatus=='Failure'){

          $order  = new WC_Order($_GET['TrackId']);

                

        $transauthorised = false;
        
        //echo $_GET['ResponseCode'];die();  

          if($_GET['ResponseCode'] == "625"){

             $this -> msg['class'] = 'woocommerce';
              $this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been Failed.";
              $this -> msg['type']= "error";
              
              $order -> update_status('failed');
                  $order -> add_order_note('failed');
                  $order -> add_order_note($this->msg['message']);
              $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
        
              $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );          
              //$redirect_url=$this->get_return_url ($order);
              wc_add_notice($this->msg['message'], 'error');
              wp_redirect( $redirect_url );
              // echo $this -> result($order,"UnSuccessful");die;
              exit();

        }else

        {

          $this -> msg['class'] = 'woocommerce';
          $this -> msg['message'] = "Thank you for shopping with us. However, the Payment has been Failed.";
          $this -> msg['type']= "error";
          
          $order -> update_status('failed');
              $order -> add_order_note('failed');
              $order -> add_order_note($this->msg['message']);
          $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
    
          $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );          
          //$redirect_url=$this->get_return_url ($order);
          wc_add_notice($this->msg['message'], 'error');
          wp_redirect( $redirect_url );
          // echo $this -> result($order,"UnSuccessful");die;
          exit;
            
            }

         

      }else

          {

            $order -> update_status('failed');

            $order -> add_order_note('Failed');
            $this -> msg['class'] = 'woocommerce';

            $this -> msg['message'] = "Thank you for shopping with us . However, the payment has been declined.";

            $this -> msg['type']= "error";

            $order -> add_order_note($this->msg['message']);
            
            
            $order -> add_order_note('Payment Gateway has processed the payment. Ref Number: '.$_GET['TrackId']);
        
            $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
  
            $redirect_url=$redirect_url."checkout/order-received/".$_GET['TrackId']."/?key=".$order -> order_key;
                
            wc_add_notice($this->msg['message'], 'error');
            wp_redirect( $redirect_url );die;
            // echo $this -> result($order,"UnSuccessful");die;
            
            //$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
  
            //$redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );
     

            //wp_redirect( $redirect_url );die;

          }

            }

    }

    else

    {
    
        $order -> update_status('failed');

        $order -> add_order_note('Failed');

        $order -> add_order_note($this->msg['message']);
            
        $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
        
       $redirect_url = add_query_arg( array(
            'msg'   => urlencode($this->msg['message']),
            'type'  => isset($this->msg['class']) ? $this->msg['class'] : '', // Ensure 'class' key exists
            'alert' => isset($this->msg['type']) ? $this->msg['type'] : '',   // Ensure 'type' key exists
          ), $redirect_url );      

        wc_add_notice($this->msg['message'], 'error');
        wp_redirect( $redirect_url );die;
       //echo $this -> result($order,"UnSuccessful");die;

        $this -> msg['class'] = 'woocommerce';

        $this -> msg['message'] = "Thank you for shopping with us.  Invalid Transaction. Please contact with administrator.";

        $this -> msg['type']= "error";

    }

            

      $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
      
      $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );
      //$redirect_url=$this->get_return_url ($order);
      wc_add_notice($this->msg['message'], 'error');
      wp_redirect( $redirect_url );die;
      //echo $this -> result($order,"Fraud");die;

    }

    }
  /**
     * Generate urway_payment button link
     **/    
    public function generate_urway_payment_form($order_id)
  {
    
     
      global $woocommerce;
      $order = new WC_Order($order_id);
      $stcFlag= get_post_meta($order->get_id(), '_transaction_type', true ) ;

      $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
     
      //For wooCoomerce 2.0
      $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
      $order_id = $order_id.''.date("ymds").rand();
      //echo $redirect_url;die();
      //do we have a phone number?
      //get currency  

      $id=$order->get_id();
      $address = $order -> get_billing_address_1();
      if ($order ->get_billing_address_2() != "")
        $address = $address.' '.$order -> get_billing_address_2();

  
 
      $currencycode = get_woocommerce_currency();    
      $merchantTxnId = $order_id;
      $orderAmount = $order -> get_total();     
      //$action = customgatewayLib::getCpUrl($this->gateway_server); 
      
    
      $success_url =  $redirect_url;
      $failure_url =  $redirect_url;
      
      $host= gethostname();
      $ip = gethostbyname($host);
      
      $terminalId= $this -> settings['aggregator_id'];
      $password=$this -> settings['password'];
      
      $txn_details= "".$id."|".$terminalId."|".$password."|".$this->merchant_key."|".$orderAmount."|".$currencycode."";
      $requestHash  = urwayLib::_Hashcreation($txn_details);
      
      $url= $this -> settings['url'];
      $Transaction_type=$this -> settings['transaction_method'];
      //echo  $Transaction_type;die();
      if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
        $plugin_root_url = "https"; 
      else
        $plugin_root_url = "http"; 
    
        // Here append the common URL characters. 
        $plugin_root_url .= "://"; 
          
        // Append the host(domain name, ip) to the URL. 
        $plugin_root_url .= $_SERVER['HTTP_HOST']; 
          
        $plugin_root_url .= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        //print_r($plugin_root_url ."?wc-api=urway_payment");die();
        //  $plugin_root_url = preg_split( "/(\?|!)/", $plugin_root_url );
       // echo $stcFlag;die();
        if($stcFlag=="STC")
      {
        $fields = array(
            'trackid' => $id,
            'terminalId' => $terminalId,
      'instrumentType'=>"Default",
      'customerEmail' => $order -> get_billing_email() ,
      'action' => "13",
      'address'=>"",
      'merchantIp' =>$ip,
      'password'=> $password,
      'currency' => $currencycode,
      'country'=>$order -> get_billing_country(),
      'tranid' => $merchantTxnId,
      'amount' => $orderAmount,
      'udf5'=>"",
      'udf3'=>"",
      'udf4'=>"",
      'udf1'=>"WooCommerce",
      'udf2'=>$plugin_root_url ."?wc-api=urway_payment",
      //'udf2'=>"",
      'requestHash' => $requestHash
            );
    }
    else
    {
    $fields = array(
      'trackid' => $id,
      'terminalId' => $terminalId,
      'customerEmail' => $order -> get_billing_email(),
      'action' => $Transaction_type,
      'merchantIp' =>$ip,
      'password'=> $password,
      'currency' => $currencycode,
      'country'=>$order -> get_billing_country(),
      'tranid' => $merchantTxnId,
      'amount' => $orderAmount,
      'udf5'=>"Test5",
      'udf3'=>"Test3",
      'udf4'=>"Test4",
      'udf1'=>"Test1",
      'udf2'=>$plugin_root_url ."?wc-api=urway_payment",
      'requestHash' => $requestHash
            );
      
    }
    $fields_string = json_encode($fields);
    //echo "<pre>";
  //echo "request Json:- ".$fields_string;die;
    
    
    $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              'Content-Type: application/json',
              'Content-Length: ' . strlen($fields_string))
            );
              curl_setopt($ch, CURLOPT_TIMEOUT, 5);
              curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

              //execute post
              $result = curl_exec($ch);

              //close connection
              curl_close($ch);
                  
                  
                  $urldecode=(json_decode($result,true));
                
                 if( $urldecode['responseCode'] =='' 
                      &&  isset($urldecode['targetUrl'])){

                  $url=$urldecode['targetUrl']."?paymentid=".$urldecode['payid'];
                 
                  //echo $url;die; 
                  
                  if($urldecode['payid'] != NULL)
                  {echo '
                  <html>
                  <form name="myform" method="POST" action="'.$url.'">
                  <h1>Transaction is processing......</h1>
                  </form>
                  <script type="text/javascript">document.myform.submit();
                  </script>
                  </html>';
                } else{
                    echo "<b>Something went wrong!!!!</b>"; 
                  }
                }else if($urldecode['responseCode']!='001'
                          && $urldecode['responseCode']!='000'){
                  if(isset($urldecode['reason'])){
                     //echo "".$urldecode['reason'];die;
                    $this -> msg['class'] = 'woocommerce';
                    $this -> msg['message'] = $urldecode['reason'];
                    $this -> msg['type']= "error";
          
                  $order -> update_status('failed');
                      $order -> add_order_note('failed');
                      $order -> add_order_note($this->msg['message']);
                  $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
    
                  $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );          
                  
                  wc_add_notice($this->msg['message'], 'error');
                  wp_redirect( $redirect_url );
                 
                  exit;
                   } 
                    else{
                      $this -> msg['class'] = 'woocommerce';
                      $this -> msg['message'] = "Thank you for shopping with us. However, the Payment has been Failed.";
                      $this -> msg['type']= "error";
          
                  $order -> update_status('failed');
                      $order -> add_order_note('failed');
                      $order -> add_order_note($this->msg['message']);
                  $redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
    
                  $redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class'],'alert'=>$this -> msg['type']), $redirect_url );          
                  
                  wc_add_notice($this->msg['message'], 'error');
                  wp_redirect( $redirect_url );
                      echo "".$urldecode['result'];
                    }
                  
                }
                  
                 }
   function get_pages($title = false, $indent = true) {
      $wp_pages = get_pages('sort_column=menu_order');
      $page_list = array();
      if ($title) $page_list[] = $title;
      foreach ($wp_pages as $page) {
        $prefix = '';
        // show indented child pages?
        if ($indent) {
          $has_parent = $page->post_parent;
          while($has_parent) {
            $prefix .=  ' - ';
            $next_page = get_page($has_parent);
            $has_parent = $next_page->post_parent;
          }
        }
        // add to page list array array
        $page_list[$page->ID] = $prefix . $page->post_title;
      }
      return $page_list;
    }

  }

  /**
   * Add the Gateway to WooCommerce
   **/
  function enqueue_custom_payment_styles() {
    wp_enqueue_style( 'custom-payment-method-css', plugins_url( 'css/style.css', __FILE__ ) );
}


  add_filter( 'woocommerce_get_checkout_order_url', 'custom_checkout_order_url', 10, 2 );

function custom_checkout_order_url( $order_url, $order ) {
    // Get the order ID
    $order_id = $order->get_id();

    // Modify the URL to use order-pay instead of order
    $order_url = add_query_arg( 'order-pay', $order_id, $order_url );

    return $order_url;
}
  

  function woocommerce_add_urway_payment_gateway($methods) {
    $methods[] = 'Urway_Payment';
    return $methods;
  }

  add_filter('woocommerce_payment_gateways', 'woocommerce_add_urway_payment_gateway' );
  

add_action('wp', 'show_wc_messages_top');
function show_wc_messages_top() {
    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }
}
    function save_transaction_type_meta($order_id) {
    if (isset($_POST['transaction_type'])) {
        update_post_meta($order_id, '_transaction_type', sanitize_text_field($_POST['transaction_type']));
    }
}
 
  

?>