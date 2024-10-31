jQuery(document).ready(function($) {
    $(".post-title > p").toggleClass('hidden'); 
	
    $('#date').bind(
		'dpClosed',
		function(e, selectedDates) {
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
			}
		}
	);
	// Hide all post details when directed
	$("#toggle_details").click(function() {
		$(".post-title > p").toggleClass('hidden'); 
	});
	
	// Make print link open up print dialog
	$("#print_link").click(function() {
		window.print();
		return false;
	});
});
