$(document).ready(function(){
    if (typeof user_tutorial_enabled != 'undefined' && user_tutorial_enabled == true) {
        // Showing first modal
        $('.modal-first').modal('show');

        $('.next-modal').on('click',function(event){
            event.preventDefault();
            if ($(this).hasClass('first-link')){
                $('.modal-first').modal('hide');
                $('.modal-second').modal('show');
                $('header').addClass('show');
                $('.header-wrapper').addClass('show');
                return false;
            } else if ($(this).hasClass('second-link')){
                $('.modal-second').modal('hide');
                $('.modal-third').modal('show');
                $('.section-search').addClass('show-smaller');
                return false;
            } else if ($(this).hasClass('third-link')){
                $('.modal-third').modal('hide');
                $('.modal-fourth').modal('show');
                $('.global-wrapper').addClass('show');
                $('footer, .sub-footer').addClass('show-footer');
                return false;
            } else {
                $('footer, .sub-footer').removeClass('show-footer');
                $('.global-wrapper, header, .header-wrapper').removeClass('show');
                $('.section-search').removeClass('show-smaller');
                $('.modal-fourth').modal('hide');
                return false;
            }
        })
    }

    $('a.admin-card-delete').on('click', function(event){
        event.preventDefault();

        var el    = $(this),
            block = el.parent();

        $.ajax({
            type: "GET",
            url: el.attr('href')
        }).done(function(response) {
            if (response == 'OK') {
                block.fadeOut( "slow", function() {
                    block.remove();
                });
            }
        });
    });
})
