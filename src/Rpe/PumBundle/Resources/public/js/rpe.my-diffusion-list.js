function swipers(){
    //left
    var l_container = $('.modal-list-left .swiper-container'),
        l_scrollbar_container = $('.modal-list-left .swiper-scrollbar')
        l_elements = $('.modal-list-left .swiper-container ul li'),
        l_nb_elements = l_elements.length;

        if ( l_list_swiper == null ){
            if(l_nb_elements > 3){
                l_scrollbar_container.show();
                l_list_swiper = new Swiper(l_container[0],{
                    mode             : 'vertical',
                    scrollContainer  : true,
                    mousewheelControl: true,
                    scrollbar        : {
                        container: l_scrollbar_container[0],
                        hide     : false,
                        draggable: true
                    }
                });
            }
            else{
                l_scrollbar_container.hide();
            }
        }
    //right
    var r_container = $('.modal-list-right .swiper-container'),
        r_scrollbar_container = $('.modal-list-right .swiper-scrollbar')
        r_elements = $('.modal-list-right .swiper-container ul li'),
        r_nb_elements = r_elements.length;


        if ( r_list_swiper == null ){
            if(r_nb_elements > 3){
                r_scrollbar_container.show();
                r_list_swiper = new Swiper(r_container[0],{
                    mode             : 'vertical',
                    scrollContainer  : true,
                    mousewheelControl: true,
                    scrollbar        : {
                        container: r_scrollbar_container[0],
                        hide     : false,
                        draggable: true
                    }
                });
            }
            else{
                r_scrollbar_container.hide();
            }
        }
}


var l_list_swiper = null,
    r_list_swiper = null;
$(document).ready(function(){

    $('.create-list').on('click',function(){
        $('.modal-diffusion').modal('show');
        swipers();
    });

    $('.close-submenu').on('click',function(){
        $('.modal-diffusion').modal('hide');
    });
});