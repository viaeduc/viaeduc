;(function(){
    $(document).ready(function(){
        //bind change order event to select answer order
        $('#question-filter-select').bind('change', function(){
            var selected = $(this).find("option:selected");
            if(selected.data('loadorder')){
                window.location = selected.data('loadorder');
            }
            return false;
        });
    });
    $( document ).ajaxComplete(function() {
        $.timeago.settings.strings = {
           prefixAgo: "il y a",
           prefixFromNow: "d'ici",
           seconds: "moins d'une minute",
           minute: "une minute",
           minutes: "%d minutes",
           hour: "une heure",
           hours: "%d heures",
           day: "un jour",
           days: "%d jours",
           month: "un mois",
           months: "%d mois",
           year: "un an",
           years: "%d ans"
        };
        $(".timeago").timeago();
    });
})(jQuery);