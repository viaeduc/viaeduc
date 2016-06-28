$(document).ready(function(){
	if(window.FormData !== undefined)  // for HTML5 browsers
    {
        $('#group-invite-form').submit(function(event){
            var formObj  = $(this);
            var formURL  = formObj.attr("action");
            var formData = new FormData(this);

//            console.log(formObj);

            // Checking for active class
            if ( formObj.hasClass('active') ){
                return false;
            } else {
                // Adding active class to avoid multiple submits
                formObj.addClass('active');
                // Starting Ajax
                $.ajax({
                    url			: formURL,
                    type		: 'POST',
                    data		: formData,
                    contentType	: false,
                    cache		: false,
                    processData	: false
                }).done(function(html) {
                    // Sending data
                	if(formObj.find('#invite-friend-list')){
                		formObj.find('#invite-friend-list').html(html);
                	}

                    if(formObj.find('input')){
                        formObj.find('input').val('');
                    }

                    if(formObj.find('textarea')){
                        formObj.find('textarea').val('');
                    }

                    // Removing active class
                    formObj.removeClass('active');
                }).fail(function() {
                });
            }
            event.preventDefault();
        });
    }
});