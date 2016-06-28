$(document).ready(function () {
    // remove li block
    $(document).delegate(".delete", "click", function (event) {
        event.preventDefault();
        $(this).parent('li').remove();

    });
    $(document).delegate(".box .btn-result.icon-up-dir", "click", function (event) {
        event.preventDefault();
        $(this).parent('.box').find('ul').slideUp('slow', function () {});
        $(this).removeClass("icon-up-dir").addClass("icon-down-dir");
    });

    $(document).delegate(".box .btn-result.icon-down-dir", "click", function (event) {
        event.preventDefault();
        $(this).parent('.box').find('ul').slideToggle('slow', function () {});
        $(this).removeClass("icon-down-dir").addClass("icon-up-dir");
    });
});