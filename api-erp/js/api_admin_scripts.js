

jQuery( document ).ready(function() {

	/*****get new product****/
	jQuery('.erp_api_form #get_new_products').click(function(){		
		var data = {
			'action': 'get_and_update_products',
			'last_product_id': jQuery(".erp_api_form input[name='last_product_id']").val()
		};

		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});

		return false;
	});
	
	/***** all product update ****/
	jQuery('.erp_api_form #get_all_products').click(function(){

		var data = {
			'action': 'get_and_update_products',
			'last_product_id': 0
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});

		return false;
	})
	
	/*****get one product****/
	jQuery('.erp_api_form #get_one_product').click(function(){
		
		var data = {
			'action': 'get_and_update_products',
			'one_product_id': jQuery(".erp_api_form input[name='one_product_id']").val()
		};

		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);

		});

		return false;
	})
	
	
	
	/***get one custommers****/
	jQuery('.erp_api_form #get_new_customers').click(function(){	

		var data = {
			'action': 'get_erp_customers',
			'one_customer_id': jQuery(".erp_api_form input[name='last_customer_id']").val()
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});
	
		return false;
	});
	
	/***** get all custommers ****/
	jQuery('.erp_api_form #get_all_customers').click(function(){

		var data = {
			'action': 'get_erp_customers',
			'last_customer_id': 0
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});

		return false;
	});
	
	/***** get all account managere ****/
	jQuery('.erp_api_form #update_account_man').click(function(){

		var data = {
			'action': 'get_erp_accman',
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});

		return false;
	})
	
	/*******update spetial price for customer********/
	
	jQuery('.erp_api_form #update_price').click(function(){

		var data = {
			'action': 'update_price'
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});

		return false;
	})
	
		/*******update stock quantity********/
	
	jQuery('.erp_api_form #update_stock').click(function(){

		var data = {
			'action': 'update_stock'
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			add_respons(response);
		});

		return false;
	})	

	/*******show log********/
	
	jQuery('.erp-api-form #show_log').click(function(){

		var data = {
			'action': 'show_log'
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
			jQuery('.erp_api_form #log_file').html(response.replace(/\n/g, "<br />"));
		});

		return false;
	});
	
	jQuery('.erp_api_form #delet_log').click(function(){

		var data = {
			'action': 'delet_log'
		};
		
		jQuery.post('/wp-admin/admin-ajax.php', data, function(response) {
		jQuery('.erp_api_form #log_file').html("");
		});

		return false;
	});
	
	
})

function add_respons(response){
	response =JSON.parse(response);
	var vclass = response['success']==true?"updated":"error";
	jQuery('.erp_api_form .erp_message').html('<div class="'+vclass+'"><p>'+response['message']+'</p></div>')
	
}

jQuery(document).ajaxStart(function() {
        // show loader on start
        jQuery("#loader").css("display","block");
    }).ajaxSuccess(function() {
        // hide loader on success
        jQuery("#loader").css("display","none");
    });


function updateProgressbar(pct, show)
{
       jQuery("#progressbar")
       .progressbar({ value: pct })
       .children('.ui-progressbar-value')
       .html(pct.toPrecision(2) + '%')
       .css("display", 'block');
       
       jQuery('#progressbar').css('display', show ? 'block' : 'none');
       
       jQuery('#piro-disconnect').attr('disabled', show );
}