$(document).ready(function(){
    // Variables
    var thisImage       = $(this),
        appPreview      = $('.app-preview'),
        appSlider       = $('.app-slider'),
        appSliderWidth  = appPreview.offsetParent().width(),
        appSliderWidthPercentage = (appSliderWidth*0.9)
        sliderContainer = $('.app-slider-container'),
        slides          = $('.swiper-slide'),
        windowHeight    = $(window).height(),
        scrollPosition  = $('.preview-slider').position().top,
        transitionSpeed = 300;

    // Building appSlider div
    appSlider.height(windowHeight);
    appSlider.css('line-height',windowHeight+'px');

    // Building slider
    sliderContainer.height(windowHeight);
    sliderContainer.width(appSliderWidthPercentage+'px');
    slides.width(appSliderWidthPercentage+'px');


    // Functions
    function openSlider(){
        appPreview.stop().animate({
            'opacity': '0',
            'bottom' : '-20px'
        },transitionSpeed,function(){
            appPreview.css('display','none');
            appSlider.css('display','block');
            appSlider.stop().animate({
                'opacity' : '1',
                'bottom'  : '0px'
            },transitionSpeed);
            $('body,html').stop().animate({
                scrollTop: scrollPosition
            }, 500);
        });
    }

    function closeSlider(){
        appSlider.stop().animate({
            'opacity' : '0',
            'bottom'  : '-20px'
        },transitionSpeed,function(){
            appSlider.css('display','none');
            appPreview.css('display','block');
            appPreview.stop().animate({
                'opacity' : '1',
                'bottom'  : '0px'
            },transitionSpeed);
        });
        appPreviewSlider.reInit();
    }

    function startSlider(){
        appPreviewSlider = new Swiper('.app-slider-container',{
            resizeEvent: 'resize'
        });
    }

    // Slider buttons
    $('.prev-arrow').on('click',function(event){
        appPreviewSlider.swipePrev();
        event.preventDefault();
    })

    $('.next-arrow').on('click',function(event){
        appPreviewSlider.swipeNext();
        event.preventDefault();
    })

    // On image click
    $('.slider-image, .first-arrow').on('click',function(event){
        // Start slider
        startSlider();

        // Check if user clicked on image, this way we start swiper at that image
        if($(this).hasClass('slider-image')){
            appPreviewSlider.swipeTo($(this).data("slide"));
        }

        // Open slider
        openSlider();

        event.preventDefault();
    });

    // Close slider click
    $('.close-slider').on('click',function(event){
        closeSlider();
        event.preventDefault();
    });
});