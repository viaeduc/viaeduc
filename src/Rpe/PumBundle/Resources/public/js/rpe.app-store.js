$(function(){
    $(document).ready(function(){


        appSwiper = new Swiper('.app-container',{
            centeredSlides: true,
            slidesPerView: 'auto',
            initialSlide: 1
        });

        // Next slide
        $('.slider-nav.next').on('click',function(event){
            appSwiper.swipeNext()
            event.preventDefault();
        })

        // Prev slide
        $('.slider-nav.prev').on('click',function(event){
            appSwiper.swipePrev()
            event.preventDefault();
        })

        smallAppSwiper = new Swiper('.first-small-app-container',{
            slidesPerView: 'auto',
            scrollbar: {
              container :'.first-scroll',
              hide: false,
              draggable: true,
              watchActiveIndex: true,
              onScrollbarDrag: function(){
                console.log('Dragging')
              }
            }
        });

        smallAppSwiper = new Swiper('.second-small-app-container',{
            slidesPerView: 'auto',
            scrollbar: {
              container :'.second-scroll',
              hide: false,
              draggable: true,
              watchActiveIndex: true,
              onScrollbarDrag: function(){
                console.log('Dragging')
              }
            }
        });

    })
});