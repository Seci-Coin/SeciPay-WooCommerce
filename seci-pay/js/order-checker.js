jQuery(document).ready(function( $ ) {
		var data = {
		'action': 'order_checking',
		'order_id': sp_data.order_id      // We pass php values differently!
	};
	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
	setInterval(function(){
		console.log('running')
	jQuery.post(sp_data.ajax_url, data, function(response) {
		console.log(response);
		if (response == 'confirmed'){
			$('.pulse').removeClass( "waiting" ).addClass( "confirmed" );
            $('.pulse').empty();
            $('.pulse').text('Transaction Confirmed!');
            $('.entry-title, .fl-post-title').empty();
            $('.entry-title, .fl-post-title').text('Transaction Confirmed!');
			
		} 
	});
		}, 3000);
});