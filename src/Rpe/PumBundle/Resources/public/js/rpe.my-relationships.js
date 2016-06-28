$(document).ready(function(){
    var contentDiv = $('.relation-container');

    // Filter click
    $('.filtre').change(function(){

        var href = $(this).find('option:selected').attr('data-href');

        $.ajax({
            url: href
        }).done(function(html){
            contentDiv.html(html)
        }).fail(function(){
            console.log('failed')
        })
    })

    // ---------------------------- //
    //    Profil "Popin" Script     //
    // ---------------------------- //
    $(document.body).on('click','.relation-box', function(event){
        var el             = $(this),
            allRelationBox = $('.relation-box'),
            allRelationDiv = $('.relation-popin'),
            relationDiv    = el.parent().find('.relation-popin')
            href           = el.parent().data('href'),
            id             = el.data('id'),
            animationSpeed = 500;

        // ---------------------------- //
        // Check if it is the same link //
        // ---------------------------- //

        if(el.hasClass('active')){
            console.log('Same box clicked, removing active and closing ')

            el.removeClass('active');
            relationDiv.removeClass('active');

            allRelationBox.not(el).stop().animate({
                'opacity' : '1'
            },animationSpeed);

            relationDiv.stop().animate({
                'opacity' : '0',
                'height'  : '0px'
            }, animationSpeed,function(){
                allRelationDiv.css('display','none');
            })

            return false;
        }

        // ------------------------------- //
        // Check if a relation div is open //
        // ------------------------------- //

        if(allRelationDiv.hasClass('active')){

            // ----------------------- //
            // Check if same container //
            // ----------------------- //

            if(el.parent().find('.relation-popin').hasClass('active')){
                // Same container, loading new info

                allRelationBox.removeClass('active');
                el.addClass('active');

                $.ajax({
                    type: "GET",
                    url: href,
                    data: { id: id }
                }).done(function(html) {
                    if (html != 'ERROR') {
                        allRelationBox.not(el).stop().animate({
                            'opacity' : '0.4'
                        },animationSpeed);
                        $(el).stop().animate({
                            'opacity' : '1'
                        }, animationSpeed)
                        relationDiv.html(html);
                    } else {
                    }
                }).fail(function() {
                });
            } else{
                // Not the same container
                allRelationBox.removeClass('active');
                allRelationDiv.removeClass('active');

                el.addClass('active');
                relationDiv.addClass('active');

                allRelationDiv.stop().animate({
                    'opacity' : '0',
                    'height'  : '0px'
                },animationSpeed, function(){
                    $.ajax({
                        type: "GET",
                        url: href,
                        data: { id: id }
                    }).done(function(html) {
                        if (html != 'ERROR') {
                            relationDiv.html(html);
                            relationDiv.css('display','block');

                            // Animating
                            allRelationBox.not(el).stop().animate({
                                'opacity' : '0.4'
                            },animationSpeed);

                            $(el).stop().animate({
                                'opacity' : '1'
                            },animationSpeed);

                            relationDiv.stop().animate({
                                'opacity' : '1',
                                'height'  : '300px'
                            },animationSpeed);
                        } else {
                        }
                    }).fail(function() {
                    });
                });
            }
        } else {

            // ---------------------------------------------- //
            // No relation container open, add active classes //
            // ---------------------------------------------- //

            el.addClass('active');
            relationDiv.addClass('active');

            $.ajax({
                type: "GET",
                url: href,
                data: { id: id }
            }).done(function(html) {
                if (html != 'ERROR') {
                    relationDiv.html(html);
                    relationDiv.css('display','block');

                    // Animating
                    allRelationBox.not(el).stop().animate({
                        'opacity' : '0.4'
                    },animationSpeed);

                    relationDiv.stop().animate({
                        'opacity' : '1',
                        'height'  : '300px'
                    },animationSpeed);
                } else {
                }
            }).fail(function() {
            });

        }
        event.preventDefault();
    });

    // ----------- //
    // Close popin //
    // ----------- //
    $(document.body).on('click','.close-popin',function(event){
        console.log('closing')
        var allRelationBox = $('.relation-box'),
            allRelationDiv = $('.relation-popin'),
            animationSpeed = 500;

        allRelationBox.removeClass('active');
        allRelationDiv.removeClass('active');

        allRelationBox.stop().animate({
            'opacity' : '1'
        },animationSpeed);

        allRelationDiv.stop().animate({
            'opacity' : '0',
            'height'  : '0px'
        },animationSpeed,function(){
            allRelationDiv.css('display','none');
        });



        event.preventDefault();
    });

    // Relation box click
    // $(document.body).on('click', '.relation-box',function(){
    //     var relationBox       = $('.relation-box'),
    //         relationPopin     = $('.relation-popin'),
    //         animationSpeed    = 500;

    //     if(relationBox.hasClass('active')){
    //         relationBox.removeClass('active')

    //         relationPopin.stop().animate({
    //             'opacity' : '0'
    //         },animationSpeed,function(){
    //             $(this).css('display','none');
    //         });

    //         relationBox.stop().animate({
    //             'opacity' : '1'
    //         },animationSpeed)

    //         return false;
    //     } else {
    //         $(this).addClass('active');

    //         var href = $(this).parent().attr('data-href'),
    //             id   = $(this).attr('data-id');


    // });

    // ACCEPT & REJECT RELATION
    $('.relation-accept, .relation-reject').on('click', function(event){
        var self = this,
            id   = $(this).attr('data-id'),
            href = $(this).attr('href');

        $.ajax({
            type: "GET",
            url: href,
            data: { id: id }
        }).done(function(msg) {
            $(self).parent().parent().fadeOut("slow",function(){
                $(self).parent().parent().remove();
            });

            $('.pending_count').html(parseInt($('.pending_count').html())-1);
            if ($(self).hasClass('relation-accept')) {
                $('#relations_count').html(parseInt($('#relations_count').html())+1);
            }
        });

        event.preventDefault();
    })
})

// Main page load
/*$.ajax({
    url: 'http://google.com'
}).done(function(html) {
    contentDiv.html(html)
}).fail(function() {
    console.log('failed')
})
*/