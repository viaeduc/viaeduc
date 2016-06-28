$(document).ready(function(){
    // prevent modal from closing when form has error
    $('#submitFavDisBtn').on('click', function(e){
        e.preventDefault();
        $('#submitFavDisBtn').attr('clicked', 'true');
        $('#favdis_form').submit();
    });
    $('#cancelFavDisBtn').on('click', function(e){
        $('#submitFavDisBtn').attr('clicked', 'false');
    });
    
    $('#modal-fdiscussion').on('hide.bs.modal', function (e) {
    	$('#submitFavDisBtn').attr('clicked');
        if ($('#submitFavDisBtn').attr('clicked') == 'true') {
            if ($('#favdis_form').find('.fav-discussion-input').hasClass('error')) {
                // modal remains displayed
                e.preventDefault();
            }
        }
    });
    
    $('#favdis_form').submit(function(event) {
    	var formObj  = $(this);
        var formURL  = formObj.attr("action");
        var formData = new FormData(this);
        var replaceEl = formObj.data('replace-el') || null;
        
        // Adding active class to avoid multiple submits
        formObj.addClass('active');
        
        if ($('#favdis_form').find('.fav-discussion-input').val()) {
        	console.log("test");
            // Starting Ajax
            $.ajax({
                url			: formURL,
                type		: 'POST',
                data		: formData,
                contentType	: false,
                cache		: false,
                processData	: false
            }).done(function(response) {
            	console.log($(replaceEl));
            	$(replaceEl).replaceWith(response);
            }).fail(function() {
            	
            });
        }
        
        event.preventDefault();
    });
    
    
});