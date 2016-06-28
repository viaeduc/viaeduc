$(document).ready(function () {
    // REQUIRED
    // Quiz
    $('.quiz-js').each(function () {
        // console.log('test');
        "use strict";
        var answerNumber = $(this).find('.answer-number'),
            answerBar    = $(this).find('.answer-bar'),
            answerWidth  = answerNumber.html();

        // console.log(answerNumber);
        // console.log(answerBar);
        // console.log(answerWidth);

        answerBar.stop().animate({
            'width': answerWidth
        }, 1000, function(){
            // console.log('should be complete');
        });
    });



    notificationSwiper = new Swiper('.notification-container',{
        slidesPerView: 'auto',
        mode: 'vertical',
        mousewheelControl: 'true',
        scrollbar: {
          container :'.notification-scroll',
          hide: false,
          draggable: true,
          watchActiveIndex: true,
          mode:'vertical'
        }
    });


    // ------------
    // AUTHOR COUNT
    // ------------
    var authorDivCounter = $('.author-container').find('.swiper-slide').length;

    //console.log(authorDivCounter)

    if(authorDivCounter > 3){
        // Author slider
        authorSwiper = new Swiper('.author-container',{
            slidesPerView: 'auto',
            mode: 'vertical',
            mousewheelControl: 'true',
            scrollbar: {
              container :'.author-scroll',
              hide: false,
              draggable: true,
              watchActiveIndex: true,
              mode:'vertical'
            }
        });
    } else {
        $('.author-container').height(230);
    }

    // Not required
    // My webpage
    $('.componant-date').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            opacity: 1,
            left: 0
        }, duration);
    });

    // Vote buttons
    $('.quiz-radio').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            opacity: 1,
            left: 0
        }, duration);
    });

    // Agenda
    $('.agenda-componant').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            'opacity': '1',
            'top': '0'
        }, duration);
    });

    // Question
    $('.question-componant').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            'opacity': '1',
            'top': '0'
        }, duration);
    });

    // RSS
    $('.rss-componant').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            'opacity': '1',
            'top': '0'
        }, duration);
    });

    // Relations
    $('.relation-js').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            'opacity': '1',
            'right': '0px'
        }, duration);
    });

    // Group
    $('.group-js').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            'opacity': '1',
            'right': '0px'
        }, duration);
    });

    // Ressource
    $('.ressource-wrapper').each(function (i) {
        "use strict";
        var duration = 350;
        $(this).delay(i * (duration / 2)).animate({
            'opacity': '1',
            'left': '0px'
        }, duration);
    });
});