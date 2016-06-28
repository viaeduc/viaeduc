$(document).ready(function(){
    // ------------------ //
    // INDEXATION TRIGGER //
    // ------------------ //

    $('.open-indexation').on('click',function(event){
        var el   = $(this),
            list = el.next();

        if(el.hasClass('active')){
            el.removeClass('active');
            el.removeClass('arrow-down');
            el.addClass('arrow-right');
        } else {
            el.addClass('active');
            el.removeClass('arrow-right');
            el.addClass('arrow-down');
        }

        list.toggle();

        event.preventDefault();
    })

    // -------------- //
    // CHECKBOX CHECK //
    // -------------- //

    $('.checkbox-input').on('click',function(event){
        var el                 = $(this),
            checkbox           = el.parent().parent().find('.checkbox-input:checked').length,
            errorText          = $('.error-text-version'),
            compareBtn         = $('.compare-btn'),
            doneCompareWrapper = $('.done-compare-wrapper');

        if(checkbox == 3){
            errorText.css({
                'display' : 'inline-block'
            });

            event.preventDefault();
            return false;
        }

        if(checkbox == 2){
            errorText.css({
                'display' : 'none'
            });
            compareBtn.css({
                'display' : 'inline-block'
            });
        }

        if (checkbox == 1) {
            errorText.css({
                'display' : 'none'
            });
            compareBtn.css({
                'display' : 'none'
            });
            doneCompareWrapper.css({
                'display' : 'none'
            })

            // Here goes the load to load the version in the content.
        }

        event.stopPropagation();
    });

    // -------------------- //
    // COMPARE BUTTON CLICK //
    // -------------------- //

    $('.compare-btn').on('click',function(event){
        // Quick check too see if 2 are still checked
        var el                 = $(this),
            checkbox           = $('.checkbox-input').parent().parent().find('.checkbox-input:checked').length,
            compareBtn         = $('.compare-options'),
            doneCompareWrapper = $('.done-compare-wrapper');

        if(checkbox == 2){
            // Here goes the loader
            // Temporary .hide / .show for demo.

            $('.ressource-edit-form-wrapper').hide();
            $('.temp-full-ressource').show();

            // Non temp, activating done button

            compareBtn.hide();
            doneCompareWrapper.show();
        } else {
            return false;
        }

        event.preventDefault();
    });

    // ------------------------- //
    // DONE COMPARE BUTTON CLICK //
    // ------------------------- //

    $('.done-compare-btn').on('click',function(){
        var el                  = $(this),
            doneCompareWrapper  = $('.done-compare-wrapper'),
            compareBtn          = $('.compare-options');

        compareBtn.css({
            'display' : 'inline-block'
        });
        doneCompareWrapper.css({
            'display' : 'none'
        });

        // Here goes the loader to reload latest version of the 2 compared
        // Next line is temp :

        $('.ressource-edit-form-wrapper').show();
        $('.temp-full-ressource').hide();

        event.preventDefault();
    });

    /* GESTION PUBLIE DANS */
    if($('input.post_type:checked').val() != 4){
    	$('#resource_publishedGroup').closest('li').hide();
    }
    $('#radio-wall').click();
    $('input.post_type').on('click',function(){
        var post_type = $(this).val();

        if (post_type == 4) {
            $('#resource_publishedGroup').closest('li').show();
        } else {
            $('#resource_publishedGroup').closest('li').hide();
        }
    });
})