function pumDecorateHtml(element)
{
    if (typeof CKEDITOR != 'undefined') {
        var $element = $(element);

        $element.find('textarea[data-ckeditor]').each(function (i, e) {
            CKEDITOR.replace(e, JSON.parse($(e).attr('data-ckeditor')));
        });
    }
}

$(function () {
    pumDecorateHtml(document);
});
