$(document).on('ready', function(){
	$('.dashboard-param-btn').on('click', function(e){
		var $this          = $(this),
			$parentWrapper = $this.parent().parent();
			$optionsDiv    = $parentWrapper.find('.dashboard-settings-options');

		if($this.hasClass('active')){
			$this.removeClass('active');
			$optionsDiv.removeClass('active');
		} else {
			$this.addClass('active');
			$optionsDiv.addClass('active');
		}

		e.preventDefault();
	});

	$('.add-dashboard-item').on('click', function(e) {
		var $this          = $(this),
			$parentWrapper = $this.parent();
			$optionsDiv    = $parentWrapper.find('.dashboard-add-item-wrapper');

		if($this.hasClass('active')){
			$this.removeClass('active');
			$optionsDiv.removeClass('active');
		} else {
			$this.addClass('active');
			$optionsDiv.addClass('active');
		}

		e.preventDefault();
	})

	$('.dashboard-position-select').change(function(e) {console.log('change');
		e.preventDefault();

		window.location.href = $(this).find(":selected").data('href');
	})

	// ajax validation of dashboard widget form
	$('form.dashboard-add-item-form button').on('click', function(event) {
		$this     = $(this).parent('form');
		$loader   = $this.find('span.loader');
		$messages = $this.find('ul.error');

		$.ajax({
			url: '/dashboard-validate-widgetform',
			type: 'POST',
			data: $this.serialize(),
			beforeSend: function() {
				if ($messages.length) {
					$messages.remove();
				}
				$loader.removeClass('hide');
			}
		})
		.complete(function(jqXHR) {
			try {
				response = JSON.parse(jqXHR.responseText);
				if (!response.validated) {
					if (response.message.length) {
						$content = '<ul class="error" style="color:red">';
						$.each(response.message, function(i,e){
							$content += '<li>' + e + '</li>';
						});
						$content += '</ul>';
						$loader.addClass('hide');
						$this.prepend($content);
					}
				} else {
					$this.submit();
				}
			} catch (error) {
			}
		});
		event.preventDefault();
	});
});