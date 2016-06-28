function swipers(){
    //swiper modal left
    if(typeof l_list_swiper == 'string'){
        l_list_swiper = new Swiper(l_container[0],{
            mode             : 'vertical',
            scrollContainer  : true,
            mousewheelControl: true,
            simulateTouch: false,
            scrollbar        : {
                container: l_scrollbar_container[0],
                hide     : false,
                draggable: true
            }
        });
    }

    //swiper modal right
    if(typeof r_list_swiper == 'string'){
        r_list_swiper = new Swiper(r_container[0],{
            mode             : 'vertical',
            scrollContainer  : true,
            mousewheelControl: true,
            simulateTouch: false,
            scrollbar        : {
                container: r_scrollbar_container[0],
                hide     : false,
                draggable: true
            }
        });
    }
}

function drag_drop(){
    //drag
    $( '.modal-list-right ul li .move').draggable({
        cursorAt: { left: 10 , top: 10},
        revert: 'invalid',
        containment: 'document',
        cursor: 'move',
        helper: function(){
            var li_drag = $(this).parents('li'),
                img = $(li_drag.html()).find('img').clone().addClass('img_clone').appendTo('.modal-list-right'),
                w = img.width(),
                h = img.height();

            $(this).draggable( 'option', 'cursorAt').left = w/2;
            $(this).draggable( 'option', 'cursorAt').top = h/2;

            return img;
        },
        start: function(event, ui) {
            var li_drag = $(this).parents('li');
            li_drag.addClass('drag_selected');
            $('<div class="dark_layer"></div>').css({'width':li_drag.width()+'px','height':li_drag+'px'}).prependTo(li_drag);
            $('.modal-list-right,.modal-list-left').addClass('drag');
        },
        stop: function(event, ui) {
            var li_drag = $(this).parents('li');
            li_drag.removeClass('drag_selected');
            $('.dark_layer').remove();
            $('.modal-list-right,.modal-list-left').removeClass('drag');
            $('body').css('cursor','auto');
        }
    });

    //drop
    $( '.modal-list-left ul li:not(.selected)' ).droppable({
        accept: '.modal-list-right ul li .move',
        hoverClass: 'drop_hover',
        drop: function( event, ui ) {
            var li_drag = $(ui.draggable.parents('li')),
                li_drop = $(this);
            alert('Media '+li_drag.attr('id')+' dropped in folder '+li_drop.attr('id')+' !!');
        }
    });
}

// Helper function to get parameters from the query string.
function getUrlParam( paramName ) {
    var reParam = new RegExp( '(?:[\?&]|&)' + paramName + '=([^&]+)', 'i' ) ;
    var match = window.location.search.match(reParam) ;

    return ( match && match.length > 1 ) ? match[ 1 ] : null ;
}

var l_list_swiper = '',l_container,l_scrollbar_container,
    r_list_swiper = '',r_container,r_scrollbar_container;

$(document).ready(function(){

    l_container = $('.modal-list-left .swiper-container'),
    l_scrollbar_container = $('.modal-list-left .swiper-scrollbar'),
    r_container = $('.modal-list-right .swiper-container'),
    r_scrollbar_container = $('.modal-list-right .swiper-scrollbar');


    //drag drop controls
    drag_drop();

    //open modal with 'open_modal' class
    $('.open_modal').on('click',function(){
        $('.modal-library').modal('show');
        //swipers
        swipers();
    });

    //close modal
    $('.close-submenu').on('click',function(){
        $('.modal-library').modal('hide');
    });


    //add folder click
    $( '.modal-library .add_folder_button' ).on('click',function(event){
        if( $( '.modal-list-left' ).hasClass('add') ){
            $( '.modal-list-left' ).removeClass('add');
        }
        else{
            $( '.modal-list-left' ).addClass('add');
        }
    });
});