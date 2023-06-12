jQuery(document).ready(function($) {
    $('#remove-all-products').click(function() {
			
			$(this).attr('disabled', 'disabled');

			removeProduct();

			function removeProduct() {
            $.ajax({
                url: custom_product_remover_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'custom_product_remover_remove_products',
                },
                dataType: 'json',
                success: function(response) {
                    $('#removal-status').append('<p>' + response.message + '</p>');

                    if (response.status === 'success') {
                        removeProduct();
                    } else if (response.status === 'finished') {
                        $('#remove-all-products').removeAttr('disabled');
                    }
                },
                error: function(xhr, status, error) {
                    console.log(error);
                }
            });
			}
    });
	 
	 
	 
});
