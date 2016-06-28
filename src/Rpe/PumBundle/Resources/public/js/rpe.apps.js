// JS RPE Apps//
(function () {


    jQuery(document).ready(function ($) {


        var slide_news = new Swiper('.news .swiper-container', {

            slidesPerView: 'auto'

        });
        $('.slide-news .arrow-left').on('click', function (e) {
            e.preventDefault();
            slide_news.swipePrev();
        });
        $('.slide-news .arrow-right').on('click', function (e) {
            e.preventDefault();
            slide_news.swipeNext();
        });
        var slide_latest_news = new Swiper('.swiper-container.new', {
            slidesPerView: 5,
            //Enable Scrollbar
            scrollbar: {
                container: '.swiper-scrollbar-new',
                hide: false,
                draggable: true,
                snapOnRelease: true
            }
        });
        var slide_rated = new Swiper('.swiper-container.best-note', {
            slidesPerView: 'auto',
            //Enable Scrollbar
            scrollbar: {
                container: '.swiper-scrollbar-best-note',
                hide: false,
                draggable: true,
                snapOnRelease: true
            }
        });


        // Js Top slider apps "page_apps.php"
		// Js swipper plugin 
		
        var slidesLength, activeIndex, arrowRight = $('.top-slider-apps .arrow-right'),
            arrowLeft = $('.top-slider-apps .arrow-left'),
            swiperContainer = $('.top-slider-apps .swiper-container');

        function initSwiper(direction) {
            $('.top-slider-apps .swiper-container').addClass("big");
            mySwiper.params.slidesPerView = 1;
            mySwiper.reInit();
            if (direction == "next") {
                mySwiper.swipeNext();

            } else if (direction == "prev") {
                mySwiper.swipePrev();
                $('.top-slider-apps .arrow-right').show();
            } else if (direction == " ") {
                $('.top-slider-apps .arrow-right').show();
            }
        }
        var mySwiper = new Swiper('.top-slider-apps .swiper-container', {

            mode: 'horizontal',
            slidesPerView: 3,
            loop: false,
            grabCursor: true,
            onTouchMove: function () { // onTouchMove swiper
                // initialise swiper
                initSwiper(" ");
                slidesLength = mySwiper.slides.length,
                activeIndex = mySwiper.activeIndex;
                if (activeIndex == '0') {
                    arrowLeft.hide();
                } else {
                    arrowLeft.show();
                }
                if (slidesLength - activeIndex == 1) {
                    arrowRight.hide();
                }
            }
        });

        swiperContainer.removeClass("big");
        // Click left arrow
        arrowLeft.on('click', function (e) {
            e.preventDefault();
            initSwiper("prev");
            slidesLength = mySwiper.slides.length,
            activeIndex = mySwiper.activeIndex;
            if (activeIndex == '0') {
                arrowLeft.hide();
            } else {
                arrowLeft.show();
            }
        });

        // Click right arrow
        arrowRight.on('click', function (e) {
            e.preventDefault();
            initSwiper("next");
            slidesLength = mySwiper.slides.length,
            activeIndex = mySwiper.activeIndex;
            if (activeIndex == '0') {
                arrowLeft.hide();
            } else {
                arrowLeft.show();
            }
            // arrived to last image when swiping to right
            if (slidesLength - activeIndex == 1) {
                arrowRight.hide();
            }
        });
        //end js top slider

    });
})();