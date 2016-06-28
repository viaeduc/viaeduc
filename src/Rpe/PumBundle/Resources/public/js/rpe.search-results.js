$(document).ready(function(){
	// ---------------------- //
	// TOGGLE ADVANCED SEARCH //
	// ---------------------- //
	var $target = $('.advanced-search-content');

	if ($('.advanced-search-link.advanced_search').length === 0) {
		$target.toggle();
	} else {
		$target.addClass('active');
	}

	$('.advanced-search-link').on('click', function (e){
		var $this   = $(this);

		if( $target.hasClass('active') ) {
			$target.removeClass('active');
			$target.stop().slideToggle();
		} else {
			$target.addClass('active');
			$target.stop().slideToggle();
		}
		e.preventDefault();
	})
	// ----------------- //
	// TOGGLE FILTER BOX //
	// ----------------- //

	$('.search-filter-toggle').on('click',function(event){
		var el = $(this).parent().next();
		el.stop().slideToggle();
		event.preventDefault();
	})

	// ----------------- //
	// ACTIVE LINK STATE //
	// ----------------- //

	// New search menu filter links //
	$('.search-filter-links a').on('click',function(event){
		var $el  = $(this),
			link = $el.attr('href');

		if($el.hasClass('side-search-menu-link')){
			// console.log('has the search menu class, stopping');
			if($el.hasClass('active')){
				// console.log('has active class, not doing anything');
				return false;
			}
			// console.log('Link not active, redirecting to : '+link);
			document.location.href = link;
			return false;
		}

		if (!$el.hasClass('select_all')) {
			if($el.hasClass('active')){
				$el.removeClass('active');
				if ($el.parent().find('a.active').length == 0) {
					$el.parent().find(">:first-child").addClass('active');
				}
			} else {
				$el.addClass('active');
				$el.parent().find(">:first-child").removeClass('active');
			}
		} else {
			if(!$el.hasClass('active')){
				$el.parent().find('a').removeClass('active');
				$el.addClass('active');
			}
		}
	})

	// ************* //
	// SEARCH FILTER //
	// ************* //
	$('.search-select-options').toggle();

	$('.search-select-current').on('click',function(e){
		var el        = $(this),
			filterDiv = el.parent(),
			optionDiv = filterDiv.find('.search-select-options');

		optionDiv.toggle();

		e.preventDefault();
	})

	$('#sort_select').on('change',function(event){
		event.preventDefault();

		window.location.replace(this.value);
	})
	
	// specific for belin
	$(document.body).on('js_autoload_xhr_success', function(e) {
		var belin_list = $('#belin_list');
		if (belin_list.find('div#belin_count_hidden')) {
			var count = belin_list.find('div#belin_count_hidden').html();
			
			$('div#belin_inner_search_title').find('span.count').html(count);
			$('div#belin_inner_search_title').css('display', 'block');
		}
		
		// move the belin search filter to sidebar
		if (belin_list.find('div.search-filter-belin')) {
			belin_list.find('div.search-filter-belin').css('display', 'block');
			$('aside.sidebar').append(belin_list.find('div.search-filter-belin'));
		}
		
		if ($('#belin_count_filter') && $('#belin_count_filter').html()) {
			$('#belin_count_filter').css('display', 'inline');
		}
	});
});