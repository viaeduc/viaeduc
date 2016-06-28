(function($, window, document){
    'use strict';

    $(document).ready(function(){
        $('.stat-wrapper .stat-inner select.filter').on('change', function(e){
            $(location).attr('href', $(this).val());
            e.preventDefault();
        })
    })
})(window.jQuery, window, document);