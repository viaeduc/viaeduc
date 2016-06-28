(function($) {
    $(document).ready(function () {
        /* Submit comment form */
        $('.content').on('submit', 'form.comment-form', function(ev){
            ev.preventDefault();
            var self = $(this);
            self.find('textarea, button').attr('readonly', true);

            var formObj  = $(this);
            var formURL  = formObj.attr("action");

            if( self.hasClass('active') ){
                return false;
            } else {
                self.addClass('active');

                // check if value text empty
                var content = $.trim(self.find('textarea').val());
                if(content != ""){
                    $.ajax({
                        url: formURL,
                        type: 'POST',
                        data:  self.serialize(),
                    }).done(function(html) {
                        // self.find('input[type=text]').val('');
                        // console.log('sent the form');
                        if(self.hasClass('question-form') && !$(html).hasClass('sub-comment')){
                            // console.log('haz question form & no-subcomment');
                            // console.log(html);

                            var $answerDiv = $('.all-answers-wrapper');

                            $answerDiv.prepend(html);
                            self.find('textarea').val('').html('').css('height', '').blur().change();
                            self.removeClass('active');
                            self.find('textarea, button').attr('readonly', false);

                            if($('.question-no-answer').length){
                                $('.question-no-answer').remove();
                            }
                            if($('.filter_answer').css('display') == 'none'){
                                $('.filter_answer').css('display', 'block');
                            }
                            return false;
                        }

                        var main_comment = self.parents('.timeline-comment').not('.sub-comment').not('.timeline-post-comment');

                        if (main_comment.length) {
                            // console.log(self);
                            // console.log('Main comment = '+main_comment);
                            var subcomment = main_comment.nextUntil('.timeline-comment:not(.sub-comment)').not('.timeline-post-comment').last();
                            if (subcomment.length) {
                                // console.log('subcomment now');
                                main_comment = subcomment;
                            }
                            main_comment.after($(html).hide().fadeIn(450));
                            self.hide();
                        } else {
                            // console.log('else');
                            var parent = self.parents('.timeline-post-comment');

                            if(!parent.length) {
                                parent = self.parents('.question-post-comment').parent().children('.timeline-post-comment');
                            }

                            parent.before($(html).hide().fadeIn(450));
                        }

                        self.find('textarea').val('').html('').css('height', '').blur().change();
                        self.removeClass('active');
                        self.find('textarea, button').attr('readonly', false);
                    }).fail(function() {

                    });
                }else {
                    self.removeClass('active');
                    self.find('textarea, button').attr('readonly', false);
                }
            }
        });
        $('.content').on("keydown", "form.comment-form", function(ev){
            if (ev.keyCode == '13' && ev.ctrlKey) {
                ev.preventDefault();
                $(this).trigger('submit');
                return false;
            }
        });

        var modalJSLibrary = $('#modal-js-library');
        if (modalJSLibrary) {

            $(document).on('click.bs.modal.data-api', '.form-right [data-toggle="modal"]', function (e) {
                var btn     = $(this),
                    btnSource = '#'+btn.attr('id');

                modalJSLibrary.attr('data-btn', btnSource);
            });

            function getLibraryMediaIds()
            {
                var idsDocs     = [],
                    idsIllus    = [],
                    removeLinks = $('.remove-upload[data-media_id]'),
                    typeUpload;

                $.each(removeLinks, function(key, item){
                    typeUpload = $(this).closest('.uploaded-files-wrapper');
                    if (typeUpload.hasClass('linkedIllustrations')) {
                        idsIllus.push(item.dataset.media_id);
                    } else if (typeUpload.hasClass('linkedFiles')) {
                        idsDocs.push(item.dataset.media_id);
                    }
                });

                return [idsDocs, idsIllus];
            }

            function updateSelectedBtns(e)
            {
                var modal      = $('#modal-js-library'),
                    ids        = getLibraryMediaIds(),
                    idsDocs    = ids[0],
                    idsIllus   = ids[1],
                    selectBtns = modal.find('.js-select-media');

                $.each(selectBtns, function(key, btn){
                    var btn       = $(btn),
                        mediaID   = btn.data('media_id'),
                        btnSource = modal.attr('data-btn');

                    if (btnSource == '#'+$('.linkedIllustrations').parent().find('.icon-archive').attr('id')) {
                        if (idsIllus.indexOf(mediaID.toString()) != -1) {
                            btn.addClass('disabled');
                        } else {
                            btn.removeClass('disabled');
                        }
                    } else if (btnSource == '#'+$('.linkedFiles').parent().find('.icon-archive').attr('id')) {
                        if (idsDocs.indexOf(mediaID.toString()) != -1) {
                            btn.addClass('disabled');
                        } else {
                            btn.removeClass('disabled');
                        }
                    }
                });
            }

            modalJSLibrary.on('js_loadmore_xhr_success', '.js-loadmore', updateSelectedBtns);
            modalJSLibrary.on('js_autoload_xhr_success', '.js-autoload', updateSelectedBtns);

            modalJSLibrary.on('show.bs.modal', function(e){
                var modal      = $('#modal-js-library'),
                    ids        = getLibraryMediaIds(),
                    idsDocs    = ids[0],
                    idsIllus   = ids[1],
                    selectBtns = modal.find('.js-select-media');

                $.each(selectBtns, function(key, btn){
                    var btn       = $(btn),
                        mediaID   = btn.data('media_id'),
                        btnSource = modal.attr('data-btn');

                    if (btnSource == '#'+$('.linkedIllustrations').parent().find('.icon-archive').attr('id')) {
                        if (idsIllus.indexOf(mediaID.toString()) != -1) {
                            btn.addClass('disabled');
                        } else {
                            btn.removeClass('disabled');
                        }
                    } else if (btnSource == '#'+$('.linkedFiles').parent().find('.icon-archive').attr('id')) {
                        if (idsDocs.indexOf(mediaID.toString()) != -1) {
                            btn.addClass('disabled');
                        } else {
                            btn.removeClass('disabled');
                        }
                    }
                });
            });

            modalJSLibrary.on('loaded.bs.modal', function(e){
                var libraryModal = $(this);
                libraryModal.find('.js-autoload').autoLoad();
            });

            $(document.body).on('click', '#modal-js-library .js-select-media:not(.disabled)', function(e){
                e.preventDefault();

                var libraryModal  = $('#modal-js-library'),
                    btnSource     = libraryModal.attr('data-btn'),
                    selectLink    = $(this),
                    disabledClass = 'disabled',
                    proto         = $('a[data-target=#modal-js-library]').data('prototype').replace('__name__', ''),
                    mediaID       = selectLink.data('media_id'),
                    mediaName     = selectLink.data('media_name'),
                    libraryFrom   = selectLink.data('media_library_text'),
                    targetWrap    = $(btnSource).parent().find('.uploaded-files-wrapper'),
                    targetLi      = $('<li class="uploaded-element">' + proto.replace('/>', 'value="' + mediaID + '" style="display:none;" />') + '<span class="new-file"><span class="label label-default">' + libraryFrom + '</span> ' + mediaName + '</span><a href="#" class="remove-upload" data-media_id="' + mediaID +'">x</a></li>');

                selectLink.addClass(disabledClass);
                targetLi.find('.remove-upload').removeUpload();
                targetWrap.append(targetLi).css('display','block');

                libraryModal.modal('hide').removeAttr('data-btn');
            });
        }
    });

    // UPDATE SHARE COUNT
    $(document).ajaxComplete(function() {
        var isSent = $('.modal-content.sent-success');
        if (isSent.length > 0) {
            var countLabel = $('.icon-share span'),
                countShare = parseInt(countLabel.first().text());

            countLabel.text(countShare+1);
            isSent.removeClass('sent-success');
        }
    });

}(jQuery));
