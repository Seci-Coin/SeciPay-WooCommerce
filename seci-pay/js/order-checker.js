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
		switch (response) {
    case 'processing':
        $('.pulse').removeClass( "waiting" ).addClass( "confirmed" );
        $('.pulse').empty();
        $('.pulse').text('Transaction Confirmed!');
        $('.entry-title, .fl-post-title').empty();
        $('.entry-title, .fl-post-title').text('Transaction Confirmed!');
        break;
    case 'on-hold':
        $('.pulse').removeClass( "waiting" ).addClass( "on-hold" );
        $('.pulse').empty();
        $('.pulse').text('Transaction placed on hold!');
        $('.sp-order-status-update').empty();
        $('.sp-order-status-update').text('Transaction On Hold');
        $('.sp-order-text').empty();
        $('.sp-order-text').text('We will be contacting you shortly regarding your order');
        $('.entry-title, .fl-post-title').empty();
        $('.entry-title, .fl-post-title').text('Transaction On Hold');
        break;
}
	
	});
		}, 5000);
});