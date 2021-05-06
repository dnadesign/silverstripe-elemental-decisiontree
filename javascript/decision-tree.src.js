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
                    radio.prop('checked', false);

                    if ($(tree).length > 0) {
                        $('html, body').animate({
                            scrollTop: $(tree).offset().top - 150
                        }, 500);
                    }

                    const url = location.protocol + '//' + location.host + location.pathname;
                    window.history.pushState(null, null, url);
                });
            }
        });
    });
})(jQuery);
