(function($, window, document){
    'use strict';

    // FAQ INIT METHOD
    function faqInit()
    {
        $(document.body).on('click', '.faq-sidebar-menu a', function(ev){
            $('.faq-sidebar-menu a.active').removeClass('active');
            $(this).addClass('active');
        });
    }

    // DOMREADY
    $(document).ready(function(){
        // init FAQ scripts
        faqInit();
    })
})(window.jQuery, window, document);