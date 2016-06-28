$(document).ready(function(){
	// Functions
	var changePublish = function(el){

		var allLinks    = $('.publish-link'),
			thisLink    = $(el),
			allFormDiv  = $('.publish-form'),
			thisFormDiv = $('.publish-forms').find(el),
			headerDiv   = $('header');

		// Switching active button
		allLinks.removeClass('active');
		thisLink.addClass('active');

		// Showing right div
		allFormDiv.hide();
		thisFormDiv.show();
	}

	// Events
	$('.publish-link').on('click', function(event){
		var thisLink = $(this);

		if (thisLink.hasClass('active')){
			return false;
		}

		if (thisLink.hasClass('message')){
			changePublish('.message');
		}

		if (thisLink.hasClass('ressource')){
			changePublish('.ressource');
		}

		event.preventDefault();

	})
})