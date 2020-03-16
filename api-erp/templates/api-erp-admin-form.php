
<h1><?php _e('ERP API','api_erp');?></h1>
<div  id="loader" class="loader"></div>
<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ) ;?>" class="erp_api_form" method="post" class="erp-api-form">
	<fieldset>
		<?php 
		//if cookie expired
		if(!$get_api_key) {?>
			<legend><h2><?php _e('Connection','api_erp'); ?></h2></legend>
			<div>
				<label for="api_user"><?php _e('Username','api_erp'); ?>:</label>
				<input type="text" name="api_user" value="" class="api-user" />
			</div>
			<div>
				<label for="api_password"><?php _e('Password','api_erp'); ?>:</label>
				<input type="password" name="api_password" value="" class="api-password" />
			</div>
			<div>
				<label for="api_url"><?php _e('Api base url','api_erp'); ?>:</label>
				<input type="text" name="api_url" value="<?php echo get_option('api_url');?>" class="api-url" />
			</div>
			 <input type="submit" name="connect" value="<?php _e('Connect','api_erp'); ?>" class="button button-primary"/>
			<?php 
		}
		else{ ?>
			<legend><h2><?php _e('Data update','api_erp'); ?></h2></legend>
			<?php $last_product_id   = $get_last_erp_id==NULL?0:$get_last_erp_id;?>
			<?php $last_customer_id  = $get_last_erp_customer_id==NULL?0:$get_last_erp_customer_id;?>
			
			<div id="progressbar"></div>
			<div class="erp_message"></div>
			<p>
				<input type="submit" name="api-disconnect" id="api-disconnect" value="<?php _e('Disconnect','api_erp'); ?>" class="button button"/>
			</p>
			
			<div class="api-erp-box">
				<h2><?php _e('Products','api_erp'); ?></h2>
				
				<div>
					<label for="last_product_id"><?php _e('Last imported ERP product id','api_erp'); ?>:</label>
					<input type="text" name="last_product_id" value="<?php echo $last_product_id;?>" class="last_product_id" />
					<button id="get_new_products" class="button"><?php _e('Spetial product add/update','api_erp'); ?></button>
				</div>
				<div>
					<p><?php _e('Update all products and add new products','api_erp'); ?>:</p>
					<button id="get_all_products" class="button button-primary"><?php _e('Update all products','api_erp'); ?></button>
				</div>
			</div>
			
			<div class="api-erp-box">
				<h2><?php _e('Cutomers','api_erp'); ?></h2>
				<div>
					<label for="last_customer_id"><?php _e('Last imported ERP customer id','api_erp'); ?>:</label>	
					<input type="text" name="last_customer_id" value="<?php echo $last_customer_id;?>" class="last_customer_id" />
					<button id="get_new_customers" class="button "><?php _e('Get new customer','api_erp'); ?></button>
				</div>
				
				<p><?php _e('Download all new customers','api_erp'); ?>:</p>
				<button id="get_all_customers" class="button button-primary"><?php _e('Get all customers','api_erp'); ?></button>
			</div>
			
			<div class="api-erp-box">
				<h2><?php _e('Price','api_erp'); ?></h2>
				<p>
					<input type="button" id="update_price" value="<?php _e('Update product prices','api_erp'); ?>" class="button button-primary"/>
				</p>  
			</div>
			
			<div class="api-erp-box">
				<h2><?php _e('Account managers','api_erp'); ?></h2>
				<p>
					<input type="button" id="update_account_man" value="<?php _e('Update all account managers','api_erp'); ?>" class="button button-primary"/>
				</p>
			</div>
			
			<div class="api-erp-box">
				<h2><?php _e('Stock','api_erp'); ?></h2>
				<p>
					<input type="button" id="update_stock" value="<?php _e('Update products stock','api_erp'); ?>" class="button button-primary"/>
				</p>
			</div>
			
			<h2><?php _e('Log','api_erp'); ?></h2>
			<p>
				<button id="show_log" class="button"><?php _e('Show log file content','api_erp'); ?></button>
				<button id="delet_log" class="button"><?php _e('Delete log file content','api_erp'); ?></button>
			</p>
			<div id="log_file" class="postbox"></div>
			<?php
		} ?>
		<p id="progressbar_message"></p>
	</fieldset>
</form>
