// Multiple upload script
// Here is the DOM :
// -----------------
// <div class="file-upload">
//     <ul class="uploaded-files-wrapper">
//     </ul>
//     <span class="action-btn btn-file orange">
//         <span class="fileupload icon-export-1"></span>
//         <input type="file" class="rpe-upload" id="pum_object_file_file" name="blouh" />
//     </span>
// </div>
'use strict';

(function($) {
    // ------------ //
    // UPLOAD FILES //
    // ------------ //
    $.fn.addUpload = function() {

        $.each(this, function(i, item) {

            var el = $(item),
                newFileInput = el.parent().clone();

            el.on('change', function() {
                // Vars
                var fileInput         = el.parent(),
                    fileName          = el.val().split('\\').pop(),
                    fileDivWrapper    = fileInput.parent().find('.uploaded-files-wrapper'),
                    generatedDiv      = '<li class="uploaded-element"><span class="new-file">' + fileName + '</span><a href="#" class="remove-upload">x</a></li>',
                    imageGeneratedDiv = '<li class="uploaded-element"><img src="#" class="preview-uploaded-image"><span class="new-file">' + fileName + '</span><a href="#" class="remove-upload">x</a></li>';

                $('#modal-js-library').addClass(fileName);

                if (el.hasClass('show-it')) {
                    fileDivWrapper.css({
                        'display': 'inline-block'
                    });
                }

                console.log(el);

                if (el.hasClass('single-upload')) {
                    if(el.hasClass('preview-image')){
                        fileDivWrapper.html(imageGeneratedDiv);
                    } else {
                        fileDivWrapper.html(generatedDiv);
                    }
                    el.attr('name', el.data('name'));
                    // fileDivWrapper.find('li').last().append(el);

                    // Hiding the upload btn
                    el.addClass('removed-btn');
                    el.parent().css('display', 'none');

                    // Adding remove upload script
                    fileDivWrapper.find('li').last().find('.remove-upload').data('srcInput', el).removeUpload();
                } else if (el.hasClass('btn-ressource-illustration')){
                    console.log('good btn');
                    if(el.hasClass('added')){
                        console.log('already added mate');
                        fileDivWrapper.empty();

                        // Add image & change text on btn
                        el.hide();
                        if(el.hasClass('preview-image')){
                            fileDivWrapper.append(imageGeneratedDiv);
                        } else {
                            fileDivWrapper.append(generatedDiv);
                        }
                        el.attr('name', el.data('name')).removeAttr('data-name');
                        newFileInput.insertAfter(fileInput);

                        fileDivWrapper.find('li').last().append(el);
                        fileInput.remove();

                        // Adding new upload input & activating script again
                        newFileInput.find('input[type=file]').addUpload();

                        // Adding remove upload script
                        fileDivWrapper.find('li').last().find('.remove-upload').removeUpload();

                        newFileInput.find('.fileupload').text('modifier');

                        newFileInput.find('.btn-ressource-illustration').addClass('added');
                    } else {
                        // Add image & change text on btn
                        el.hide();
                        if(el.hasClass('preview-image')){
                            fileDivWrapper.append(imageGeneratedDiv);
                        } else {
                            fileDivWrapper.append(generatedDiv);
                        }
                        el.attr('name', el.data('name')).removeAttr('data-name');
                        newFileInput.insertAfter(fileInput);

                        fileDivWrapper.find('li').last().append(el);
                        fileInput.remove();

                        // Adding new upload input & activating script again
                        newFileInput.find('input[type=file]').addUpload();

                        // Adding remove upload script
                        fileDivWrapper.find('li').last().find('.remove-upload').removeUpload();

                        newFileInput.find('.fileupload').text('modifier');

                        newFileInput.find('.btn-ressource-illustration').addClass('added');
                    }
                } else {
                    el.hide();
                    if(el.hasClass('preview-image')){
                        fileDivWrapper.append(imageGeneratedDiv);
                    } else {
                        fileDivWrapper.append(generatedDiv);
                    }
                    el.attr('name', el.data('name')).removeAttr('data-name');
                    newFileInput.insertAfter(fileInput);

                    fileDivWrapper.find('li').last().append(el);
                    fileInput.remove();

                    // Adding new upload input & activating script again
                    newFileInput.find('input[type=file]').addUpload();

                    // Adding remove upload script
                    fileDivWrapper.find('li').last().find('.remove-upload').removeUpload();
                }

                if(el.hasClass('preview-image')){
                    // imagePreview($('.preview-uploaded-image'), $('.rpe-upload').get(0).files, true);
                    $('.preview-uploaded-image').previewUpload(el.get(0).files[0]);
                }
            });
        });

    };

    // ------------ //
    // REMOVE FILES //
    // ------------ //
    $.fn.removeUpload = function() {
        // Simple enough, this allows to delete the added li
        $(this).on('click', function(event) {
            var el = $(this),
                thisDiv = el.parent(),
                parentDiv = thisDiv.parent(),
                srcInput  = el.data('srcInput') || null;

            thisDiv.stop().animate({
                opacity: 0
            }, 450, function() {
                if($(document.body).find('.removed-btn')){
                    $('.removed-btn').parent().css('display','block');
                }
                thisDiv.remove();
                if (parentDiv.is(':empty')) {
                    parentDiv.css({
                        'display': 'none'
                    });
                }
                if (null !== srcInput) {
                    srcInput.val(null).data('name', srcInput.attr('name')).removeAttr('name');
                }
            });
            event.preventDefault();
        });
    };

    $.fn.previewUpload = function(file)
    {
        var imageDiv  = $(this);
        var reader    = new FileReader();
        var image     = new Image();
		var $submit_button = $('#media_submit');

		reader.onloadstart = function (_file) {
			$submit_button.html('<span class="loader"></span>');
		}

		reader.readAsDataURL(file);
        reader.onload = function(_file) {
            image.src    = _file.target.result; // url.createObjectURL(file);
            imageDiv.attr('src', image.src);
        };

		reader.onloadend = function(_file) {
			$submit_button.removeAttr('disabled');
			$submit_button.html('Envoyer');
        };

    }
}(jQuery));
