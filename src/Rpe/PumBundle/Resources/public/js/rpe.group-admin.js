$(document).ready(function(){


    $('.start-swiper').each( function(index, item){

        var groupSwiper = [];

        $(item).on('click',function(){
            var location = $(this).attr('href');
            if ( groupSwiper[index] == undefined ) {
                groupSwiper[index] = new Swiper(location+' .swiper-container',{
                    mode: 'vertical',
                    scrollContainer: true,
                    mousewheelControl: true,
                    scrollbar: {
                      container: location+' .swiper-scrollbar',
                      hide: false,
                      draggable: true
                    }
                });
            }
        });
    });
});