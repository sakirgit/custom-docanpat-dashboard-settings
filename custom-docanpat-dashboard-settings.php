<?php
/*
Plugin Name: Custom Docanpat Dashboard settings
Description: Removes all products and Facebook posting by API .
*/

// Hook into the admin menu to add a custom menu item
add_action('admin_menu', 'custom_product_remover_menu');

function custom_product_remover_menu() {
    add_menu_page(
        'Product Remover',             // Page title
        'Product Remover',             // Menu title
        'manage_options',              // Capability
        'custom-product-remover',      // Menu slug
        'custom_product_remover_page', // Callback function
        'dashicons-trash',             // Icon
        99                             // Position
    );
}

// Enqueue JavaScript file for AJAX
add_action('admin_enqueue_scripts', 'custom_product_remover_enqueue_scripts');

function custom_product_remover_enqueue_scripts($hook) {
	if ($hook === 'toplevel_page_custom-product-remover') {
		wp_enqueue_script('custom-product-remover-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
		wp_localize_script('custom-product-remover-script', 'custom_product_remover_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_script('custom-task-action', plugin_dir_url(__FILE__) . 'script-sku.js', array('jquery'), '1.0', true);
		wp_localize_script('custom-task-action', 'custom_task_action_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	}
}

// Callback function for the custom menu page
function custom_product_remover_page() {
    ?>
	
    <div class="wrap">
        <h1>Product Remover</h1>
        <p>This will remove all products and their meta data. This action cannot be undone. Are you sure you want to proceed?</p>
        <p class="submit">
            <input type="button" id="remove-all-products" class="button button-primary" value="Remove All Products">
        </p>
        <div id="removal-status"></div>
    </div>
    <hr>
    <div class="wrap">
        <h1>Product Task From-To ID:</h1>
        <style type="text/css">
            
            #total_prog_bar {
                animation: blinker 1s linear infinite;
                width:  0%;
                max-width: 100%;
            }
				.res_succ_msg{
					font-size: 12px;
					margin: 0;
					padding: 0;
					color: green;
					padding: 5px;
					float: left;
					height: 50px;
					border: 1px solid #fff;
				}
				.res_succ_msg a{
					color: green;
				}
				.res_succ_msg img{
					float: left;
				}
				
            #task_start_action:disabled{animation: blinker 500ms linear infinite;}
            @keyframes blinker {
                50% {
                    opacity: 0.0;
                }
            }

        </style>
        <table>
            <tr>
                <td>Product ID (From):<br><input type="text" id="prod_id_from" class="" value="11930" ></td>
                <td>Product ID (To):<br><input type="text" id="prod_id_to" class="" value="11938" ></td>
                <td>Select the acrivity:<br>
                    <select name="select_activity" id="select_activity" class="select_activity">
                        <option value=""> - </option>
                        <option value="facebook-shear"> Facebook Shear </option>
                    </select>
                </td>
                <td><br>
                    <input type="button" id="task_start_action" class="button button-primary" value=" &nbsp; &nbsp; Run &nbsp; &nbsp; ">
                </td>
            </tr>
				<!--
            <tr>
                <td colspan="4" style="border: 1px dashed #03D322;border-radius: 3px;overflow: hidden;" id="prog_wrap">
                    <div style="padding:4px;background: #03D322" id="total_prog_bar"></div>
                </td>
            </tr>
				-->
        </table>
        <div id="task-activity-status"></div>

    </div>
    <?php
}

// AJAX callback for removing products
add_action('wp_ajax_custom_product_remover_remove_products', 'custom_product_remover_remove_products');

function custom_product_remover_remove_products() {
    // Increase the maximum execution time for this AJAX call
    set_time_limit(0);

    $products = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => 'any',
        'numberposts'    => 30, // Adjust the number of products to remove per batch
        'fields'         => 'ids',
    ));

    if (!empty($products)) {
        foreach ($products as $product_id) {
            delete_post_meta($product_id, '_product_attributes');
            wp_delete_post($product_id, true);
        }

        $response = array(
            'status'       => 'success',
            'message'      => count($products) . ' products removed successfully.',
            'remaining'    => count(get_posts(array(
                'post_type'      => 'product',
                'post_status'    => 'any',
                'numberposts'    => -1,
                'fields'         => 'ids',
            ))),
        );
    } else {
        $response = array(
            'status'       => 'finished',
            'message'      => 'All products and their meta data have been removed successfully.',
            'remaining'    => 0,
        );
    }

    wp_send_json($response);
}

/* ================================================== */



function devs_facebook_api_hit( $url, $params_arr ){
    
    //  $url = "https://example.com/api/endpoint";
    //  $params_arr = array('param1' => 'value1', 'param2' => 'value2');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params_arr));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
}



// AJAX callback for removing products
add_action('wp_ajax_custom_task_action_activity', 'custom_task_action_activity');

function custom_task_action_activity() {
    // Increase the maximum execution time for this AJAX call
    set_time_limit(0);

    $pIdFrom = $_POST['pIdFrom'];
    $pIdTo = $_POST['pIdTo'];
    $act_for = $_POST['act_for'];

   $products_q = array(
        'post_type'      => 'product',
        'post__in' => range($pIdFrom, $pIdTo),
        'orderby' => 'ID', 
        'order' => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock', // Set 'outofstock' for filtering out-of-stock products
                'compare' => '='
            )
        )
    );
    $products = get_posts($products_q);
    if( $products ) {

        $total_products_l = count($products);

      //  echo json_encode($products); exit;

    //    $prods["bef"] = $products;
        if( $act_for == 'facebook-shear' ){

            if ($pIdFrom <= $pIdTo) {

                $done_product = "";

                $prod_data = array_shift($products);


                $prod_image_url = "https://docanpat.com/wp-content/uploads/2023/06/docanpat-logo-400x50-1-288x109.png";
                // Check if the post has a featured image
                if (has_post_thumbnail($prod_data->ID)) {
                    // Get the featured image URL
                    $prod_image_url = get_the_post_thumbnail_url($prod_data->ID);
                    // Use the featured image URL
                    // ...
                } else {
                    // Get the product image URLs from the meta field
                    $product_image_urls = get_post_meta($prod_data->ID, 'product_urls', true);

                    // Check if the product image URLs exist
                    if (!empty($product_image_urls)) {
                        // Convert the comma-separated string into an array
                        $image_urls = explode(',', $product_image_urls);

                        // Use the first image URL from the array
                        $prod_image_url = trim($image_urls[0]);

                        // Use the product image URL
                        // ...
                    }
                }




                $post_url = get_permalink($prod_data->ID);

                // Replace <br> tags with newlines
                $prod_data_cont = str_replace("<br>", " \n ", $prod_data->post_content);
                $prod_data_cont = str_replace("&nbsp;", " ", $prod_data_cont);

                // Remove remaining HTML tags
                $prod_data_cont = strip_tags($prod_data_cont);
               
                $cont_msg .= $post_url . " \n ";
                $cont_msg = "============= \n ";
                $cont_msg .= $prod_data->post_title . " \n ";
                $cont_msg .= "============= \n ";
                $cont_msg .= $prod_data_cont . " \n ";
                $cont_msg .= "-------Buy Now ------ \n ";            
                $cont_msg .= $post_url;
                        
                $api_param = [
                                "access_token"=> 'EAATL37JGDqgBAKiZCwMut7sujqyL505a9ZCrl73yuuHF6o4QX9lTn8IsAVU5tZBeubLdE5mcZBWaZC6AD1rvD8Y1nsBspdQHwZAIT8iFugdaH0eWJnd8N7vmf5cr9H43tj5eSvW6ZCZBKQo33Y4syyXOenQlp4ZAucFPNuVvXIxaVOSaC5Oh65LmEpZCAmkvX7NQ3j6uT8ktyLKaEsPnm2Q82ZA',
                                "message"=> $cont_msg,
                                "url"=> $prod_image_url,
                            ];
            //    echo json_encode($prod_data_cont); exit;

                $fb_api_resp = devs_facebook_api_hit(
                                                'https://graph.facebook.com/126295263717613/photos/', 
                                                $api_param
                                            );
           
                $fb_api_resp = json_decode($fb_api_resp);

					if( $fb_api_resp->id ){
						
						 $response = array(
							  'status'       => 'success',
							  'message'      => '',
							  'total_products_l'    => $total_products_l,
							  'remaining'    => 1,
							  'prod_id'    => $prod_data->ID,
							  'prod_url'    => $post_url,
							  'prod_img_url'    => $prod_image_url,
							  'next_start_id'    => $prod_data->ID + 1,
						 );
					}else{
						
						 $response = array(
							  'status'       => 'server-error',
							  'message'      => 'Facebook API Server not respond !!',
							  'total_products_l'    => $total_products_l,
							  'remaining'    => 1,
							  'prod_id'    => $prod_data->ID,
							  'prod_url'    => $post_url,
							  'prod_img_url'    => $prod_image_url,
							  'next_start_id'    => $prod_data->ID + 1,
						 );
					}

            } else {
                $response = array(
                    'status'       => 'finished',
                    'message'      => '0 product now',
                    'total_products_l'    => $total_products_l,
                    'remaining'    => 0,
                    'prod_id'    => '',
                    'prod_url'    => '',
                    'prod_img_url'    => '',
                    'next_start_id'    => $pIdTo,
                );
            }

        }elseif( $act_for == 'another' ){

            if ($pIdFrom < $pIdTo) {

                $done_product = "";

                $product_s = array_shift($products);
                $product_sku = get_post_meta( $product_s->ID, '_sku', true ); 

                // Extract the first three characters
                $prefix_dp = substr($product_sku, 0, 3);

                // Check if the prefix is "DP-"
                if ($prefix_dp === "DP-") {
                    $done_product = "<b>$product_sku</b> already has DP prefiex";
                } else {
                    update_post_meta($product_s->ID, '_sku', 'DP-' . $product_sku);
                    $product_sku = get_post_meta( $product_s->ID, '_sku', true ); 
                }

             echo json_encode($product_sku);    exit;


                $response = array(
                    'status'       => 'success',
                    'message'      => 'Product SKU updated successfully.',
                    'remaining'    => count($products),
                    'next_start_id'    => $product_s->ID + 1,
                );
            } else {
                $response = array(
                    'status'       => 'finished',
                    'message'      => 'All products SKU updated successfully.',
                    'remaining'    => 0,
                    'next_start_id'    => $pIdTo,
                );
            }
        }else{
            $response = [
                    'status'       => 'yrtyrty',
                    'message'      => 'Atretercessfully.',
                    'remaining'    => 0,
                    'next_start_id'    => $pIdTo,
                ];
        }
    }else{

		$response = [
				  'status'       => 'notfound',
				  'message'      => '0 Post',
				  'total_products_l'    => $total_products_l,
				  'prod_id'    => '',
				  'prod_url'    => '',
              'prod_img_url'    => '',
				  'remaining'    => 0,
				  'next_start_id'    => 0,
			 ];
	 }

    wp_send_json($response);
}

