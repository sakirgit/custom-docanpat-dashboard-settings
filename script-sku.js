jQuery(document).ready(function($) {
	 
    var activityStatus = document.getElementById('task-activity-status');

    $('#task_start_action').on("click",function() {
			
    //    console.log("465465::", 465465);
        $(this).attr('disabled', 'disabled');

		task_start_action();

		function task_start_action(pIdFrom = $("#prod_id_from").val()) {

            var pIdTo = $("#prod_id_to").val();
            var act_for = $("#select_activity").val();

            $.ajax({
                url: custom_task_action_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'custom_task_action_activity',
                    act_for: act_for,
                    pIdFrom: pIdFrom,
                    pIdTo: pIdTo
                },
                dataType: 'json',
                success: function(response) {

                    console.log("response:: ", response);

                    if (response.status === 'success') {

                        activityStatus.insertAdjacentHTML('afterbegin', '<span class="res_succ_msg" ><a href="'+ response.prod_url +'"><img src="'+ response.prod_img_url +'" style="width: 50px;"></a> <span>'+ response.prod_id +'</span><br>Left: <b>' + response.total_products_l + '</b></span>'  );
                        task_start_action(response.next_start_id);

                    } else if (response.status === 'finished') {

                        activityStatus.insertAdjacentHTML('afterbegin', '<p class="res_succ_msg" style="color: red;" >'+ response.message +'  Left: <b>' + response.total_products_l + '</b></p>' );
                        $('#task_start_action').removeAttr('disabled');

                    }else if (response.status === 'notfound') {

                        activityStatus.insertAdjacentHTML('afterbegin', '<span class="res_succ_msg" style="color: red;" ><a href="'+ response.message +'">'+ response.prod_id +'</a>  Left: <b>' + response.total_products_l + '</b></span>' );
                    //    $('#task-activity-status').append('<span style="color: red;">' + response.message + ' | </span> ' );
                        $('#task_start_action').removeAttr('disabled');
                    }else if (response.status === 'server-error') {

                        activityStatus.insertAdjacentHTML('afterbegin', '<span class="res_succ_msg" style="color: red;" >'+ response.prod_id +'  Left: <b>' + response.total_products_l + '</b></span>' );
                        $('#task_start_action').removeAttr('disabled');
                    }
                },
                error: function(xhr, status, error) {
                    console.log(error);
                }
            });
		}
    });
	 
	 
});
