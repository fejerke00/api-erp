<?php
/**
 * Plugin Name: Api ERP
 * Plugin URI: 
 * Description: Api synchronize users, porducts and orders
 * Version: 1.0
 * Author: Enetixsoftware - Fejer Istvan
 * Author URI: 
 * Created On: 03-05-2019
 * Updated On: 03-03-2020
 */

class apiERP {
	
	// setup ERP routes
	public  $api_token_login_url = 'token';
	public  $api_products_url = 'products';
	public  $api_single_product_url = 'product';
	public  $api_customers_url = 'customers';
	public  $api_allstock_url = 'allstock';
	public  $get_accman_url = 'accman';
	public  $post_order_url = 'order';
	public  $api_prices_url = 'prices';
	public  $api_one_customer_url = 'customer';
	
	
	
	
	public  $api_cron_job_user = 'user-cron';	
	public  $cookie_default_duration_time = 6000;
	public  $admin_tamplate_name= 'api-erp-admin-form.php';
	public  $new_customer_email_tamplate= 'api-erp-admin-form.php';
	private  $token = NULL;
	
	public static function init() {
        $class = __CLASS__;
        new $class;
		
		//Corn schedules interval
		add_filter( 'cron_schedules',  array(  __CLASS__ ,'is_an_add_cron_recurrence_interval' ) );
		
		//Corn hooks
		if ( !wp_next_scheduled( 'daily_action_hook' ))		
			wp_schedule_event( time()+10, 'daily', 'daily_action_hook' );
		if ( !wp_next_scheduled( 'ten_minute_action_hook' ))
			wp_schedule_event( time()+10, 'every_ten_minutes', 'ten_minute_action_hook' );
					
		//Schedules actions
		add_action('ten_minute_action_hook', array(__CLASS__,'stock_updates_schedule') );
		add_action ('daily_action_hooke',  array(__CLASS__,'api_daily_update' ) );
    }
	public function __construct() {
		
		// TODO: Set the cron job password
		//set_option('api_cron_job_password','*******');	
				
		
		// Add Javascript and CSS for admin screens
		add_action( 'admin_enqueue_scripts', array($this,'load_api_admin_scripts_js'));
		add_action( 'admin_enqueue_scripts', array($this,'load_admin_styles_css'), null, '1.05');
		
		// Create menu item to admin menu
		add_action('admin_menu', array($this, 'api_plugin_setup_menu'));
		
		// Show ERP product id in product page
		add_action( 'woocommerce_product_options_general_product_data', 'woo_add_erp_prod_id_field' );
		
		// Send order
		add_action('woocommerce_new_order', 'custom_process_order', 1, 1);
		
		// Ajax calls
		add_action( 'wp_ajax_get_and_update_products',  array($this,'get_and_update_products'));
		add_action( 'wp_ajax_get_erp_customers',  array($this,'get_erp_customers'));
		add_action( 'wp_ajax_get_erp_accman', array($this, 'get_erp_accman'));
		add_action( 'wp_ajax_update_price', array($this, 'update_price'));
		add_action( 'wp_ajax_update_stock', array($this, 'update_stocks'));
		add_action( 'wp_ajax_delet_log', array($this, 'delet_log'));	
		add_action( 'wp_ajax_show_log', array($this, 'show_log'));	
    }

	/********************************************************************************
	/************************************Admin page related********************************
	*********************************************************************************/


	public function load_api_admin_scripts_js($hook){
		if($hook!='toplevel_page_api-erp'){
			return;
		}
		//jQuery UI
		wp_enqueue_script('jquery-ui-progressbar');
		//plugin script
		wp_register_script( 'api-admin-scripts', plugins_url('/js/api_admin_scripts.js',  __FILE__ ), null, '1.11');
		wp_enqueue_script('api-admin-scripts');
	}
	
	public function load_admin_styles_css($hook)
	{
		if($hook!='toplevel_page_api-erp'){
			return;
		}
		//jquery ui css
		wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
		
		//plugin css
		wp_register_style( 'custom_wp_admin_css', plugins_url('/css/api_admin_styles.css', __FILE__) );
		wp_enqueue_style( 'custom_wp_admin_css' );
	}
	
	// Create menu item to admin menu
	public function api_plugin_setup_menu(){
		add_menu_page( __('API','api_erp'), __('Api ERP','api_erp'), 'manage_options', 'api-erp', array($this,'api_admin_page') );
	}

	// Admin page content
	public function api_admin_page(){
		$customer['email']='fejerke00@gmail.com';
		
		$this->create_api_user($customer);
		
		if ( !current_user_can( 'manage_options' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.','api_erp' ) );
		}
		
		//save submited variable
		$this->save_posted_vars();
		
		//get cookie 
		set_query_var('get_api_key', isset($_COOKIE['api_access_token'])?trim($_COOKIE['api_access_token']):NULL);
		set_query_var('get_last_erp_id', $this->get_last_erp_id());
		set_query_var('get_last_erp_customer_id', $this->get_last_erp_customer_id());

		//include admin form
		 if ( locate_template( $this->admin_tamplate_name ) ) {	
            $template = locate_template( $this->admin_tamplate_name );
        } else {
            // Template not found in theme's folder, use plugin's template as a fallback
            $template = dirname( __FILE__ ) . '/templates/' . $this->admin_tamplate_name;
        }
		load_template( $template );
	}
	
	
	/* Show ERP id in product page*/

	public function woo_add_erp_prod_id_field(){
		global $woocommerce, $post;
		$values=get_post_meta($post->ID,'erp_product_id');
		$i=0;
		foreach($values as $i_value){
		
			$value .= $i == 0?$i_value:", ".$i_value;	
			$i++;
		}

	  echo '<div class="options_group">';
			woocommerce_wp_text_input( 
				array( 
					'id'          => '_text_field', 
					'label'       => __( 'ERP product id', 'api_erp' ), 
					'placeholder' => '',
					'desc_tip'    => 'true',
					'class'		  => 'disabled',
					'value'		  => $value,
					'description' => __( 'Product id in ERP', 'api_erp' ) 
				)
			);  
	  echo '</div>';
		
	}
	
	
	
	
	/*save submited variable */		
	public function save_posted_vars(){
		
		/*login*/
		if(isset($_POST['connect'])){
			$this->api_login();
		}
		/*disconnect*/
		if(isset($_POST['api-disconnect'])){
			unset($_COOKIE['api_access_token']);
		}
		/*api url*/
		if(isset($_POST['api_url'])){
			update_option('api_url',$_POST['api_url']);
		}
		
	}
	
	/********************************************************************************
	/************************************Api Login********************************
	*********************************************************************************/	

	public function api_login(){		
		update_option('api_url',$_POST['api_url']);

		$lUrl = get_option('api_url').$this->api_token_login_url;
		
		$respons = $this->get_token($lUrl,$_POST['api_user'],$_POST['api_password']);
		$respons = json_decode($respons,true);

		if($respons['Type'] == 'ERROR'){
			echo '<div class="error">'.__($respons['Message'],'api_erp').'</div>';
			return;
		}
		
		if(!$respons['token']){
			echo '<div class="error">'.__('Connection is failed!!','api_erp').'</div>';
			return;
		}
		$expire_in = $respons['duration']?$respons['duration']:$this->cookie_default_duration_time;
		
		$accessToken = $respons['token'];
		setcookie('api_access_token', $accessToken, time() + $expire_in, "/");
		$_COOKIE['api_access_token'] = $accessToken;
	}
	
	public function cron_login(){		

		$lUrl = get_option('api_url').$this->api_token_login_url;
		
		$respons = $this->get_token($lUrl, $this->api_cron_job_user , get_option('api_cron_job_password'));
		$respons = json_decode($respons,true);

		if($respons['Type'] == 'ERROR'){
			
			$message = __($respons['Message'],'api_erp');
			$this->writeLog($message);
			die;
		}
		
		if(!$respons['token']){
			$message =__('Connection is failed!!','api_erp');
			$this->writeLog($message);
			die;
		}
		
		$accessToken = $respons['token'];
		$this->token=$accessToken;
		return $accessToken;
	}
	
	/********************************************************************************
	/************************************Get Token ***********************************
	*********************************************************************************/

	public function get_token($url, $username, $password){

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		
		if(!get_option ('api_url'))
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = 'ERP API URL is missing! ';

			return json_encode($response);
		}
		
		if($httpcode == 405)
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = 'Method not allowed (405 ERROR)';

			return json_encode($response);
		}
		
		if($httpcode == 0)
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = 'Could not connect to ERP API! ';

			return json_encode($response);
		}
		
		$decoded = json_decode($output, true);
		
		if(isset($decoded['ExceptionMessage']))
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = $decoded['ExceptionMessage'];
			
			return json_encode($response);
		}
		
		if($httpcode != 200 && $httpcode != 0)
		{
			$http_codes = parse_ini_file(plugin_dir_path( __FILE__ ).'status_codes.ini');
			
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = $httpcode.' '.$http_codes[$httpcode];
			
			return json_encode($response);
		}
	  
		return $output;
		
	}
	
	
	/********************************************************************************
	/********************************** Schedule daily event  ********************************
	*********************************************************************************/

	public static function is_an_add_cron_recurrence_interval( $schedules ) {
		$schedules['every_days_once'] = array(
				'interval'  => 86400,
				'display'   => __( 'Every day', 'api_erp' )
		);
		
		$schedules['every_ten_minutes'] = array(
				'interval'  => 600, 
				'display'   => __( 'Every 10 Minutes', 'api_erp' )
		);
		return $schedules;
	}

	public function stock_updates_schedule(){
		$message = "-----stock update----";
		$this->writeLog($message);	
		//get token
		$this->cron_login();
		
		try {
			$stock=$this->update_stocks(true);
		}
		catch(Exception $e) {
			writeLog('Exception Error: ' .$e->getMessage());
		}
		if($stock){
			$stock_json=json_decode($stock, true);
			$message_stock = "Stock update success: ".$stock_json['success'];
			$this->writeLog($message_stock);	
			$message_stock_message = "Stock update message: ".$stock_json['message'];
			$this->writeLog($message_stock_message);
		}
		$message_end = "---------------------";
		$this->writeLog($message_end);	
		$this->token=NULL;
	}

	public function api_daily_update() {
		$message = "-----Daily update----";
		$this->writeLog($message);	
		//get token
		$this->cron_login();
		
		/*update accman*/	
		try {
			$accman=$this->get_crm_accman(true);
		}
		catch(Exception $e) {
			$this->writeLog('Error: ' .$e->getMessage());
		}
		if($accman){
			$accman_json=json_decode($accman, true);
			$message_accman = "Account Managers update success:".$accman_json['success'];
			$this->writeLog($message_accman);	
			$message_message_accman = "Account Managers update message:".$accman_json['message'];
			$this->writeLog($message_message_accman);
		}
		
		/*update cusommers*/
		$this->writeLog("Customers update");
		try {
			$cusommers=$this->get_crm_customers(true);
		}
		catch(Exception $e) {
			$this->writeLog('Error: ' .$e->getMessage());
		}
		if($cusommers){
			$cusommer_json=json_decode($cusommers, true);
			$message_cusommer = "Customers update success:".$cusommer_json['success'];
			$this->writeLog($message_cusommer);	
			$message_message_cusommer = "Customers update message:".$cusommer_json['message'];
			$this->writeLog($message_message_cusommer);
		}
		/*update products*/

		try {
			$products=$this->get_and_update_products(true);
		}
		catch(Exception $e) {
			$this->writeLog('Error: ' .$e->getMessage());
		}
		
		$products_json=json_decode($products, true);
		$message_products = "Account Managers update success:".$products_json['success'];
		$this->writeLog($message_products);	
		$message_message_products = "Account Managers update message:".$products_json['message'];
		$this->writeLog($message_message_products);
		
		/*update price*/
		try {
			$price=$this->update_price(true);
		}
		catch(Exception $e) {
			$this->writeLog('Error: ' .$e->getMessage());
		}
	
		$price_json=json_decode($price, true);
		$message_price = "Price update success:".$price_json['success'];
		$this->writeLog($message_price);	
		$message_price_message = "Price update message:".$price_json['message'];
		$this->writeLog($message_price_message);	
		
		$message_end = "---------------------";
		$this->writeLog($message_end);
		
		$this->token=NULL;
	}

	
	/********************************************************************************
	/************************************Update functions ********************************
	*********************************************************************************/	


	/* add new porduct update existing product */
	public function get_and_update_products(){
		if(!$_POST['last_product_id']||$_POST['last_product_id'] == 0){
			$get_new_product_url = $this->api_products_url;
		}else{
			$get_new_product_url = $this->api_product_url."/".$_POST['last_product_id'];
		}
		
		if($_POST['one_product_id']||$_POST['one_product_id'] != 0){
			$get_new_product_url = $this->api_single_product_url."/".$_POST['one_product_id'];
		}

		$respons =  json_decode(curlRequest($get_new_product_url), true);

		if($respons['Type'] == 'ERROR'){
			die(
				json_encode(
					array(
						'success' => false,
						'message' => $respons["Message"]
					)
				)
			);
		}

		if(count($respons)>0){
			foreach($respons as $product){
				$site_product_id =$this->get_product_id_by_erp_id($product['id']);
				if($site_product_id){
					update_stock($site_product_id, $product['stock']);
					$message = __('Update product stock wordpress product id: ','api_erp').$site_product_id." ERP id:".$product['id']." to ".$product['stock'].__(' pieces','api_erp');
					$this->writeLog($message);	
				}else{
					
					$new_product_id=$this->add_product($product);
					if($new_product_id>0){
						$message = __('Add new product in wordpress id:' ,'api_erp').$new_product_id." ERP id:".$product['id'];
						$this->writeLog($message);	
					}else{
						if($product['id'] != "" && $product['id'] != null){
							$message =  __("Error product creation ERP product id: ",'api_erp').$product['id'];
							$this->writeLog($message);	
							$error = $error?1:$error++;
						}
					}
				}			
			}
			if($error){
				die(
					json_encode(
						array(
							'success' => false,
							'message' =>  __('Has some unspecified error occurred. Please check the logs for more information.','api_erp')
						)
					)
				);
			}else{
				die(
					json_encode(
						array(
							'success' => true,
							'message' => __('Successfully added new products and updated existing product stock quantity.','api_erp')
						)
					)
				);
			}
		}else{
			 die(
				json_encode(
					array(
						'success' => true,
						'message' => __('Does not has any new product.','api_erp')
					)
				)
			);		
		}
	}
	
	/* add new customers */
	public function get_erp_customers(){

		if(!$_POST['one_customer_id']||$_POST['one_customer_id'] == 0){
			$get_new_customer_url =$this->api_customers_url;
		}else{
			
			$get_new_customer_url =$this->api_one_customer_url."/".$_POST['one_customer_id'];	
		}
		$respons =  json_decode(curlRequest($get_new_customer_url), true);
		if($respons['Type'] == 'ERROR'){
			die(
				json_encode(
					array(
						'success' => false,
						'message' => $respons["Message"]
					)
				)
			);
		}
		
		if(count($respons)>0){
			foreach($respons as $customer){
				$user = get_user_by( 'email', $customer['email'] );
				if ( ! empty( $user ) ) {
					$message =  __('This Customer Already Exists(site customer ERP id: ', 'api_erp').$customer['id'].", Email: ".$customer['email'].")";
					$this->writeLog($message);	
					$error = $error==NULL?1:$error+1;					
				}else{				
					$new_customer=create_api_user($customer);

					if($new_customer['Success']==false){
						$message = $new_customer["Message"];
						$this->writeLog($message);	
						$error = $error==NULL?1:$error+1;
					}else{
						$message =  __('Create new user with this username: ', 'api_erp').$customer['email'];
						$this->writeLog($message);	
					}
				}
			}
		
			if($error){
				die(
					json_encode(
						array(
							'success' => false,
							'message' => __('Has some unspecified error occurred. Please check the logs for more information.', 'api_erp')
						)
					)
				);
			}else{
				die(
					json_encode(
						array(
							'success' => true,
							'message' => __('Successfully added new users.', 'api_erp')
						)
					)
				);
			}
		}else{
			 die(
				json_encode(
					array(
						'success' => true,
						'message' => __('Does not has any new customers.', 'api_erp')
					)
				)
			);
			
		}
	}

	/*add  accont managers*/
	public function get_erp_accman(){

		$respons =  json_decode(curlRequest($this->get_accman_url), true);		
		if($respons['Type'] == 'ERROR'){
			die(
				json_encode(
					array(
						'success' => false,
						'message' => $respons["Message"]
					)
				)
			);
		}
		if(count($respons)>0){
			foreach($respons as $accontman){
				if($accontman['email']!= "NULL" && $accontman['email']!="" && $accontman['email']!= NULL){
					$new_customer=$this->create_api_accman($accontman);

					if($new_customer['Success']==false){
						$message = $new_customer["Message"];
						$this->writeLog($message);	
						$error = $error==NULL?1:$error+1;
					}else{
						$message = __('Create new account manager with this username: ', 'api_erp').$accontman['email'];
						$this->writeLog($message);	
					}
				}
				else{
					$message = __('Error in adding new account manager.Email address is required.', 'api_erp'). " #username: ".$accontman['name'];
							$this->writeLog($message);	
				}
			}
			die;
			if($error){
				die(
					json_encode(
						array(
							'success' => false,
							'message' => __('Has some unspecified error occurred. Please check the logs for more information.', 'api_erp')
						)
					)
				);
			}else{
				die(
					json_encode(
						array(
							'success' => true,
							'message' => __('Successfully added new account managers.', 'api_erp')
						)
					)
				);
			}
		}else{
			 die(
				json_encode(
					array(
						'success' => true,
						'message' => __('Does not has any account manager.', 'api_erp')
					)
				)
			);
		}
	}

	/* update price*/
	public function update_price($not_exit){
		$this->writeLog( __('Price update', 'api_erp'));	
		$update_price_url = $this->api_prices_url;	
		$all_prod_price = json_decode(curlRequest($update_price_url), true);


		if($all_prod_price['Type'] == 'ERROR'){
			$return = 
				json_encode(
					array(
						'success' => false,
						'message' => $all_prod_price["Message"]
					)
				);
		}
		if(count($all_prod_price)>0){
			foreach($all_prod_price as $item){
				$users_id = $this->get_users_id_by_erp_comp_id($item['CompanyId']);
				$product_id = $this->get_product_id_by_erp_id($item['Product']);
				foreach($users_id as $user_id){
					
					if($product_id!=NULL && $user_id != NULL && $item['Price']!=NULL){
						$update = $this->update_ERP_pricing_in_db($user_id, $product_id, $item['Price']);	
						$update_unitep=update_post_meta( $product_id, '_price', $item['UnitPrice'] );
						$update_unireg = update_post_meta( $product_id, '_regular_price', $item['UnitPrice'] );
					}else{
						$message = "Error: Update product price , wp user id: ".$user_id.", ERP user company id: ".$item['CompanyId'].", wp product id: ".$product_id.", ERP product id: ".$item['Product']." , price: ".$item['Price'] ;
						$this->writeLog($message);	
						$error = $error==NULL?1:$error+1;
					}
				}
			}	
			
			if($error){
				$return = 
					json_encode(
						array(
							'success' => false,
							'message' => __('Has some unspecified error occurred. Please check the logs for more information.', 'api_erp')
						)
					);
			}else{
				$return = 
					json_encode(
						array(
							'success' => true,
							'message' => __('Successfully updated product prices.', 'api_erp')
						)
					);
			}
		}else{
			$return = 
				json_encode(
					array(
						'success' => false,
						'message' => __('Error: Does not get any data.', 'api_erp')
					)
				);
		}
		$this->writeLog($return);
		if($not_exit != true){
			die($return);
		}else{
			return  $return;	
		}
	}

	/*Update stock*/
	public function update_stocks($not_exit){
		$api_allstock_url = $this->api_allstock_url;	
		$all_prod_stock = json_decode(curlRequest($api_allstock_url), true);
		
		if($all_prod_stock['Type'] == 'ERROR'){
			$return =
				json_encode(
					array(
						'success' => false,
						'message' => $all_prod_stock["Message"]
					)
				);
		}
		if(count($all_prod_stock)>0){
			foreach($all_prod_stock as $item){
				$product_id = get_product_id_by_erp_id($item['product_id']);
				if($product_id!=NULL  && $item['stock']!=NULL){
					$update = update_stock($product_id, $item['stock']);
					$message = "Success: Update product stock , wp product id: ".$product_id.", ERP product id: ".$item['product_id']." , stock: ".$item['stock'] ;
					$this->writeLog($message);	
				}else{
					$message = "Error: Update product stock , wp product id: ".$product_id.", ERP product id: ".$item['product_id']." , stock: ".$item['stock'] ;
					$this->writeLog($message);	
					$error = $error==NULL?1:$error+1;
				}	
			}
			
			if($error){
				$return =
					json_encode(
						array(
							'success' => false,
							'message' => __('Has some unspecified error occurred. Please check the logs for more information.', 'api_erp')
						)
					);
			}else{
				$return =
					json_encode(
						array(
							'success' => true,
							'message' => __('Successfully updated stock.', 'api_erp')
						)
					);
			}
			
		}else{
			$return =
				json_encode(
					array(
						'success' => false,
						'message' => __('Error: Does not get any data.', 'api_erp')
					)
				);
		}
		die;
		if($not_exit != true){
			die($return);
		}else{
			return  $return;	
		}
	}
		
	/********************************************************************************
	/************************************Product actions********************************
	*********************************************************************************/


	public function add_product($product){
			
		$user_id = get_current_user();

		//foreach( $products as $item ) {
		$post_id = wp_insert_post( array(
			'post_author' => 1,
			'post_title' => $product['description'],
			'post_content' => $product['description'],
			'post_status' => 'pending',
			'post_type' => "product",
		) );
		
		wp_set_object_terms( $post_id, 'simple', 'product_type' );
		update_post_meta( $post_id, '_visibility', 'visible' );
		update_post_meta( $post_id, '_stock_status', 'instock');
		update_post_meta( $post_id, 'total_sales', '0' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_regular_price', $product['price'] );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', 'no' );
		update_post_meta( $post_id, '_weight', '' );
		update_post_meta( $post_id, '_length', '' );
		update_post_meta( $post_id, '_width', '' );
		update_post_meta( $post_id, '_height', '' );
		update_post_meta( $post_id, '_sku', $product['sku']);
		update_post_meta( $post_id, '_product_attributes', array() );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_price', $product['price'] );
		update_post_meta( $post_id, '_sold_individually', '' );
		update_post_meta( $post_id, '_manage_stock', 'yes' );
		update_post_meta( $post_id, '_backorders', 'yes' );
		update_post_meta( $post_id, '_stock', $product['stock'] );
		update_post_meta( $post_id, 'erp_product_id', $product['id'] );
		//}
		return $post_id;
	}
	
	/*get product id by erp id*/
	public function get_product_id_by_erp_id($id){
		global $wpdb;
		$results = $wpdb->get_results( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'erp_product_id' and meta_value ='".$id."'", ARRAY_A );

		if(count($results)>1){
			foreach($results as $row){
				$wpids .= $wpids? ", ".$row["post_id"]: $row["post_id"];
			}
			die('Error: Duplicated erp id: '.$id.' in wordpress products: '.$wpids);
		}
		elseif(count($results)==0){
			return NULL;
		}
		elseif(count($results)==1){
			return $results[0]["post_id"];
		}
	}

	/*update product stock*/

	public function update_stock($post_id, $stock_qty){
		update_post_meta( $post_id, '_stock', $stock_qty);
	}

	/* get last product id */

	public function get_last_erp_id(){
		global $wpdb;
		$results = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'erp_product_id' ORDER BY CAST(meta_value AS UNSIGNED) DESC LIMIT 1;", ARRAY_A );
		if(count($results)==0){
			return NULL;
		}
		elseif(count($results==1)){
			return $results[0]["meta_value"];
		}
	}

	
	/********************************************************************************
	/*********************Sender function **************************
	*********************************************************************************/




	public function curlRequest($pUrl, $data = null, $method = 'GET')
	{
		$method = empty($method) ? 'GET' : $method;
			
		if(strpos($pUrl, 'http://') === false ){
			$url = get_option ('api_url').$pUrl;

		}else{
			$url = $pUrl;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL , $url); 
		
		if($method != 'POST' && $method != 'GET'){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //PUT/DELETE
		}else{
			curl_setopt($ch, CURLOPT_POST, ($method == 'POST' ? 1 : 0));
		}
		curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE); 
		curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE); 
			
		$content_length = 0;
		$headers = array();
		if($this->token == NULL){
			$access_token = $_COOKIE['api_access_token']?$_COOKIE['api_access_token']:"";
		}else{
			$access_token = $this->token;
		}

		$headers[] = 'AccessToken: Bearer '. $access_token ;
		$headers[] = 'Content-Type: application/json';
		$data_string =$data;

		if($data != null)
		{    
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);   			
			$content_length = strlen($data_string);
			
		}
		
		$headers[] = 'Content-Length: '.$content_length;		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch,CURLOPT_USERAGENT, "ERP API");
		$server_output = curl_exec ($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		
		curl_close ($ch);
		// return $server_output;

		
		/*****/
		
		if(!get_option ('api_url'))
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = 'ERP API URL is missing! ';
			return json_encode($response);
		}
		
		if($httpcode == 405)
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = 'Method not allowed (405 ERROR)';
			return json_encode($response);
		}
		
		if($httpcode == 0)
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = 'Could not connect to ERP API! ';
			return json_encode($response);
		}	
		$decoded = json_decode($server_output, true);
		
		if(isset($decoded['ExceptionMessage']))
		{
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = $decoded['ExceptionMessage'];			
			return json_encode($response);
		}
		
		if($httpcode != 200 && $httpcode != 0)
		{
			$http_codes = parse_ini_file(plugin_dir_path( __FILE__ ).'status_codes.ini');		
			$response = array();
			$response['Type'] = 'ERROR';
			$response['Message'] = $httpcode.' '.$http_codes[$httpcode];			
			return json_encode($response);
		}
		return $server_output;

	}
	
	/********************************************************************************
	/************************************Customer********************************
	*********************************************************************************/

	public function create_api_user($customer){

		include_once(ABSPATH . 'wp-includes/pluggable.php');
		if( null == username_exists($customer['email']) ) {

			$password = wp_generate_password( 12, false );
			$user_id = wp_create_user( $customer['email'], $password, $customer['email'] );
		
			wp_update_user(
				array(
				  'ID'          =>    $user_id,
				  'nickname'    =>    $customer['email']
				)
			);
			$user = new WP_User( $user_id );
			$user->set_role( 'customer' );
			update_user_meta( $user_id, 'erp_company_id', $customer['CompanyId'] ); /*this is company id*/
			update_user_meta( $user_id, 'erp_customer_user_id', $customer['UserID'] ); /*this the user id*/
			update_user_meta( $user_id, 'billing_email', $customer['email']);
			
			if($customer['manid'])
				update_user_meta( $user_id, 'erp_accman_id', $customer['manid'] );				
			
			
			
			//send mail to new customer	
			$email = $this->wp_new_user_notification_email($user,$password);
			$mail=$email->send(
                    $user->user_email,
                    $email->get_subject(),
                    $email->get_content_html(),
                    $email->get_headers(),
					null
                );
			if($mail==true){			
				$response['Success'] = true;
				$response['Message'] = __('Create user successfully', 'api_erp').' ('. $customer['email'].'- ERP id: '.$customer['id'].__(' id in the site: ', 'api_erp').$user_id.' )';
				$this->writeLog( __('Successfully send new customer messages for this email address:  ', 'api_erp').$customer['email']);	
			}else{
				$response['Success'] = false;
				$response['Message'] = __('Notice: Failed delivery new customer messages for this email address ', 'api_erp'). $customer['email'];		
				$this->writeLog( __('New customer messages does not send  for this email address:  ', 'api_erp').$customer['email']);
			}

		}else{			
			$response = array();	
			$response['Success'] = false;
			$response['Message'] = __('Notice: Customer already exist ', 'api_erp'). $customer['email'];

		}
		return $response;
		
	}
	
	/*get erp cutomer id*/
	public function get_user_id_by_erp_id($id){
		global $wpdb;
		$results = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'erp_company_id' and meta_value ='".$id."'", ARRAY_A );

		if(count($results)>1){
			foreach($results as $row){
				$wpids .= $wpids? ", ".$row["user_id"]: $row["user_id"];
			}
			die(__('Error: Duplicated erp id: ', 'api_erp').$id.__(' in wordpress users: ', 'api_erp').$wpids);
		}
		elseif(count($results)==0){
			return NULL;
		}
		elseif(count($results)==1){
			return $results[0]["user_id"];
		}
	}

	/*modify this function because use company id*/
	public function get_users_id_by_erp_comp_id($id){
		global $wpdb;
		$results = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'erp_company_id' and meta_value ='".$id."'", ARRAY_A );
		if(count($results)>=1){
			foreach($results as $row){
				$res[] =  $row["user_id"];
				//$wpids .= $wpids? ", ".$row["user_id"]: $row["user_id"];
			}
			return $res;
		}
		elseif(count($results)==0){
			return NULL;
		}
	}

	/* get last customer id */

	public function get_last_erp_customer_id(){
		global $wpdb;
		$results = $wpdb->get_results( "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = 'erp_company_id' ORDER BY CAST(meta_value AS UNSIGNED) DESC LIMIT 1;", ARRAY_A );
		if(count($results)==0){
			return NULL;
		}
		elseif(count($results==1)){
			return $results[0]["meta_value"];
		}
	}
	
	/********************************************************************************
	/************************************Send Order********************************
	*********************************************************************************/
	
	
	public function custom_process_order($order_id){
		$session_cart = WC()->session->cart;
		global $wpdb;	
		   
		$this->writeLog('new Order');
		$theorder = new WC_Order( $order_id );

		$cmr_products = array();
		$user = $theorder->get_user();

		if(count($cmr_products) == 0){
			foreach($session_cart as $item_data) {
				$item = $item_data;	
				$_product = wc_get_product( $item['product_id'] );
				$price = intval($_product->get_regular_price()*100)/100;

				$cmr_products[] = array(
					'ProductID'=> (int)get_post_meta($item['product_id'],'crm_product_id')[0],
					'ProductName' => $_product->get_name(), 
					'UnitPrice' => (intval($_product->get_regular_price()*100)/100), 			
					'Price' => $price, 
					'Quantity' => $item['quantity'],
					'SiteProductId' =>$item['product_id']
				);
			}
			
		}

		$accmenid= get_user_meta( $user->ID, 'crm_accman_id');
		$crm_order = array();
		$crm_order['SiteUserID'] =$user->ID;
		$crm_order['SiteOrderId'] = (int)$theorder->get_order_number();
		$crm_order['CustomerID'] = (int)get_user_meta( $user->ID, 'crm_customer_id')[0];
		$crm_order['AccountManager'] = (int)get_user_meta( $user->ID, 'crm_accman_id')[0];
		$crm_order["Note"] = $theorder->customer_message;


		$crm_order['Email'] = $user->user_email;
		$crm_order['Products'] = $cmr_products;
			
		$crm_order =json_encode($crm_order);
		if(!$crm_order['CustomerID'] || $crm_order['CustomerID']< 0){
			 die('stop order sent because doesn\'t have valid crm_customer_id');
		}
		$this->writeLog('Order data:'.$crm_order);
		$response = json_decode(curlRequest(get_option('post_order_url'), $crm_order, 'POST'), true);
		$this->writeLog(ucfirst(strtolower($response['Type'])).": Order sender response #".$order_id." :".$response['Message']);	
	}	
	
	

	/********************************************************************************
	/************************************Loging ***********************************
	*********************************************************************************/


	public function writeLog($data)
	{
		$data =  date('Y-m-d H:i').' - '.$data."\n";
		
		if(!empty($data))
		{
			$path = plugin_dir_path( __FILE__ )."erp_wp_log.txt";
			$fh2 = fopen($path, 'a');
			fwrite($fh2, $data);
			fclose($fh2);
		}
	}

	public function show_log(){
		$path = plugin_dir_path( __FILE__ )."erp_wp_log.txt";
		$fh2 = file_get_contents($path, true);
		die($fh2);
	}

	public function delet_log(){	
			$path = plugin_dir_path( __FILE__ )."erp_wp_log.txt";
			$fh2 = fopen($path, 'w');
			fclose($fh2);
	}
	
	
	public function wp_new_user_notification_email($user,$userpass) {
		$wp_new_user_notification_email=array(); 
		if (class_exists('WooCommerce') && $user) {
			$wc_emails = WC_Emails::instance();
			$email = $wc_emails->emails['WC_Email_Customer_New_Account'];
			

			// Set object variables so the email can use it
			$email->object = $user;
			$email->user_pass = $userpass;
			$email->user_login = stripslashes($user->user_login);
			$email->user_email = stripslashes($user->user_email);
			$email->recipient = $email->user_email;
			$email->password_generated = true;
		}
		return $email;
	}	
}

/*activate deactivate plugin*/
register_activation_hook(__FILE__, 'apiERP_activation_logic');
add_action( 'deactivated_plugin', 'detect_plugin_deactivation', 10, 2 );
function mypluginname_activation_logic() {
    //if dependent plugin is not active
    if (!is_plugin_active('woocommerce/woocommerce.php')  )
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}
function detect_plugin_deactivation( $plugin, $network_activation ) {
    if ($plugin=="woocommerce/woocommerce.php")
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action('plugins_loaded' , array( 'apiERP', 'init' ));  
}