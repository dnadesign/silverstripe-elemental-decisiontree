(function($) {

	$(document).ready(function() {
		/**
		* Submit Step form when selecting an answer
		* Adds loading animation to empty "nextstep" div
		* Fetch content via ajax
		* Insert HTML and update URL
		*/
		$(document).on( 'change', 'input[name=stepanswerid] ', function() {
			var form = $(this).parents('form'),
				tree = form.parents('.ElementDecisionTree'),
				step = form.parent('.step'),
				nextstep_holder = step.find('> .nextstep');

				nextstep_holder.html('<div class="spinner-holder"><div class="spinner"><span class="sr-only">loading</span></div></div>');
				setTimeout(function() {
					nextstep_holder.addClass('loading');
				}, 100);

				$.ajax({
					url     : form.attr('action'),
					type    : form.attr('method'),
					dataType: 'json',
					data    : form.serialize(),
					success : function( data ) {
						nextstep_holder.addClass('new-content-loaded');
						nextstep_holder.html(data.html);
						window.history.pushState(null, null, data.nexturl);

						// Toggle wrapper class so we know if the form is complete
						if( isFormComplete(nextstep_holder) ) {
							tree.find('.decisiontree').addClass('decisiontree--complete');
						} else {
							tree.find('.decisiontree').removeClass('decisiontree--complete');
						}
					},
					error   : function( xhr, err ) {
						nextstep_holder.html(xhr.responseText);
					}
				}).always(function() {
					setTimeout(function() {
						nextstep_holder.removeClass('loading new-content-loaded');
					}, 100);
				});
		});

		/**
		* Handles the restart button
		* Empties all subsequent steps then
		* Scroll back to first step then
		* Reset url to original page url
		*/
		$(document).on('click', 'button[data-action="restart-tree"]', function() {
			var button = $(this),
				firststep = button.parents('.step--first'),
				radio = firststep.find('input[type="radio"]'),
				tree = firststep.parents('.ElementDecisionTree');

			if (firststep) {
				firststep.find('.nextstep').fadeOut(function() {
					$(this).html('').show();
					radio.removeAttr('checked');
					$('html, body').animate({
				        scrollTop: $(tree).offset().top - 150
				    }, 500);
				    window.history.pushState(null, null, button.data('target'));

					// Reset wrapper class
					tree.find('.decisiontree').removeClass('decisiontree--complete');
				});
			}
		});

		// Add wrapper class on page load if form is complete
		$('.decisiontree').each( function() {
			var tree = $(this);

			if ( isFormComplete (tree) ) {
				tree.addClass('decisiontree--complete');
			}
		});

	});

	function isFormComplete(wrapper) {
		return ( wrapper.find('.step--result').length > 0 ) ? true : false;
	}

})(jQuery);
