/*
    HEAVILY MODIFIED !!!! DO NOT UPDATE SCRIPT !!!!
    OR DO IT AT YOUR OWN RISK !!!!!
*/

/* ===================================================
 * tagmanager.js v3.0.1
 * http://welldonethings.com/tags/manager
 * ===================================================
 * Copyright 2012 Max Favilli
 *
 * Licensed under the Mozilla Public License, Version 2.0 You may not use this work except in compliance with the License.
 *
 * http://www.mozilla.org/MPL/2.0/
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */

// *************************** //
// MODIFIED FOR RPE VALIDATION //
//       Lines : 40 - 230      //
// *************************** //

// *********************** //
// MODIFIED FOR RPE COMMAS //
//        Line : 60        //
// *********************** //

(function($) {

    // CHECK TAGS FOR VALIDATION //
    function checkTags(tagList, self) {
        var remainingChildren = tagList.children().length;
        // console.log('checking it !')
        // console.log(remainingChildren)

        if (remainingChildren < 1) {
            // console.log('no children')
            self.attr('data-validation', 'required');
        } else {
            // console.log('other children')
            self.attr('data-validation', '');
        }
    }

    "use strict";

    var defaults = {
        prefilled: null,
        CapitalizeFirstLetter: false,
        preventSubmitOnEnter: true,     // deprecated
        isClearInputOnEsc: true,        // deprecated
        AjaxPush: null,
        AjaxPushAllTags: null,
        AjaxPushParameters: null,
        delimiters: [9, 13],        // tab, enter
        backspace: [8],
        maxTags: 0,
        hiddenTagListName: null,        // deprecated
        hiddenTagListId: null,          // deprecated
        replace: true,
        output: null,
        deleteTagsOnBackspace: true,    // deprecated
        tagsContainer: null,
        hiddenContainerId: null,
        tagCloseIcon: 'x',
        tagClass: '',
        validator: null,
        onlyTagList: false,
        tagList: null,
        storeName: null
    },

    publicMethods = {
        pushTag : function (tag, ignoreEvents) {
            var $self = $(this), opts = $self.data('opts'), alreadyInList, tlisLowerCase, max,
            tagId = tag.id,
            nTagId = tag.id,
            tlis = $self.data("tlis"), tlid = $self.data("tlid"), idx, newTagId, newTagRemoveId, escaped,
            html, $el, lastTagId, lastTagObj,

            tag = privateMethods.trimTag(tag.value, opts.delimiterChars);

            tag = $('<div/>').html(tag).text();

            // console.log($self, tlis);

            if (!tag || tag.length <= 0) { return; }

            // console.log('opts', opts);
            // check if restricted only to the tagList suggestions
            if (opts.onlyTagList){

                //if the list has been updated by look pushed tag in the tagList. if not found return
                // if the list is empty
                if (!opts.tagList){
                    // console.log('no tag list');
                    // console.log('seld.data', $self.data());
                    if ($self.data('bloodhoundData')){
                        $self.data('opts').tagList = Array();

                        $.each($self.data('bloodhoundData').index.datums, function(i, item) {
                            var item_value = $('<div/>').html(item.value.toLowerCase()).text();
                            $self.data('opts').tagList.push({ 'item_id' : item.id ,'item_value' : item_value } );
                        });
                        opts = $self.data('opts');
                    }
                    // console.log('taglist ', opts.tagList);
                }


                if (opts.tagList){
                    var $tagList = opts.tagList;
                    // console.log('# ', $tagList);

                    var suggestion = -1;
                    $.each($tagList, function(index, item) {

                        // console.log('@ ', item );
                        // console.log( $tagList[index] );
                        // console.log( "#"+item.item_value+"#" );
                        // console.log( "#"+item.item_value.toLowerCase()+"#" );
                        // console.log( "#"+$.trim( item.item_value.toLowerCase() )+"#" );

                        // console.log("#"+tag+"#");
                        // console.log("#"+tag.toLowerCase()+"#");
                        // console.log("#"+$.trim( tag.toLowerCase() )+"#");


                        if ( $.trim( tag.toLowerCase() ) == $.trim( item.item_value.toLowerCase() ) ){
                            // console.log('tag.toLowerCase() == item.item_value');
                            suggestion = 1;
                            nTagId = item.item_id;
                            return false;
                        }else{
                            // console.log('!tag.toLowerCase() == item.item_value');
                            suggestion = -1;
                        }
                    });

                    if ( -1 === suggestion ) {
                        return;
                    }
                }
            }

            if (opts.CapitalizeFirstLetter && tag.length > 1) {
            }

            // call the validator (if any) and do not let the tag pass if invalid
            if (opts.validator && !opts.validator(tag)) {
                // console.log('invalid tag');
                return;
            }

            // dont accept new tags beyond the defined maximum
            if (opts.maxTags > 0 && tlis.length >= opts.maxTags) {
                // console.log('max number of tags');
                return;
            }


            alreadyInList = false;
            //use jQuery.map to make this work in IE8 (pure JS map is JS 1.6 but IE8 only supports JS 1.5)
            tlisLowerCase = jQuery.map(tlis, function(elem) {
                return elem.toLowerCase();
            });

            idx = $.inArray(tag.toLowerCase(), tlisLowerCase);

            if (-1 !== idx) {
                alreadyInList = true;
            }
            // console.log('already in list', alreadyInList);

            if (alreadyInList) {
                // console.log('already in list');
                $self.trigger('tm:duplicated', tag);
                $("#" + $self.data("tm_rndid") + "_" + tlid[idx]).stop()
                    .animate({backgroundColor: opts.blinkBGColor_1}, 100)
                    .animate({backgroundColor: opts.blinkBGColor_2}, 100)
                    .animate({backgroundColor: opts.blinkBGColor_1}, 100)
                    .animate({backgroundColor: opts.blinkBGColor_2}, 100)
                    .animate({backgroundColor: opts.blinkBGColor_1}, 100)
                    .animate({backgroundColor: opts.blinkBGColor_2}, 100);
            } else {
                // console.log('not in list');
                if (!ignoreEvents) { $self.trigger('tm:pushing', tag); }

                max = Math.max.apply(null, tlid);
                max = max === -Infinity ? 0 : max;

                tagId = ++max;
                tlis.push(tag);
                tlid.push(nTagId);

                if (!ignoreEvents)
                    // console.log('!ignoreEvents');
                    if (opts.AjaxPush !== null && opts.AjaxPushAllTags == null) {
                        if ($.inArray(tag, opts.prefilled) === -1) {
                            $.post(opts.AjaxPush, $.extend({tag: tag}, opts.AjaxPushParameters));
                        }
                    }

                newTagId = $self.data("tm_rndid") + '_' + nTagId;
                newTagRemoveId = $self.data("tm_rndid") + '_Remover_' + nTagId;
                escaped = $("<span/>").html(tag).text(); // REVERSE text/html for correct escape

                html = '<span class="' + privateMethods.tagClasses.call($self) + '" id="' + newTagId + '">';
                html+= '<span>' + escaped + '</span>';
                html+= '<a href="#" class="tm-tag-remove" id="' + newTagRemoveId + '" TagIdToRemove="' + nTagId + '">';
                html+= opts.tagCloseIcon + '</a></span> ';
                $el = $(html);

                // console.log('opts.tagsContainer: ',opts.tagsContainer);
                if (opts.tagsContainer !== null) {
                    $(opts.tagsContainer).append($el);
                } else {
                    if (nTagId > 1) {
                        lastTagId = nTagId - 1;
                        lastTagObj = $("#" + $self.data("tm_rndid") + "_" + lastTagId);
                        lastTagObj.after($el);
                    } else {
                        $self.before($el);
                    }
                }

                $el.find("#" + newTagRemoveId).on("click", $self, function(e) {
                    // CUSTOM ACTION ADDED FOR VALIDATION //
                    e.preventDefault();

                    // console.log('unpushed')

                    var TagIdToRemove = parseInt($(this).attr("TagIdToRemove")),
                        $this    = $(this),
                        $tagList = $this.parent().parent();

                    privateMethods.spliceTag.call($self, TagIdToRemove, e.data);
                    // checkTags($tagList, $self);

                });

                privateMethods.refreshHiddenTagList.call($self);

                if (!ignoreEvents) { $self.trigger('tm:pushed', tag); }

                privateMethods.showOrHide.call($self);

                publicMethods.addItem.call($self, $self.data().opts.hiddenContainerId, nTagId);
            }
            $self.val("");


        },

        // Add hidden inputs items
        addItem : function(item_id, item_bdd_id){
            // console.log('add Item');
            var $self = $(this), opts = $self.data('opts');
            var store_name = opts.storeName,
                $this    = $(this),
                $tagList = $this.parent().parent();

            if (0 == $(item_id+' input[item-id='+item_bdd_id+']').length) {
                $(item_id).append('<input type="hidden" class="item" name="'+store_name+'" value="'+item_bdd_id+'" />')
            }

            // checkTags($tagList, $self);
        },

        // Remove hidden inputs items
        removeItem : function(item_id, item_bdd_id) {
            // console.log('remove Item');
            if (1 == $(item_id+' input[item-id='+item_bdd_id+']').length) {
                $(item_id+' input[item-id='+item_bdd_id+']').remove();
            }
        },

        popTag : function () {
            // console.log('pop Tag');
            var $self = $(this), nTagId, tagBeingRemoved,
            tlis = $self.data("tlis"),
            tlid = $self.data("tlid");

            if (tlid.length > 0) {
              nTagId = tlid.pop();

              tagBeingRemoved = tlis[tlis.length - 1];
              $self.trigger('tm:popping', tagBeingRemoved);
              tlis.pop();

              $("#" + $self.data("tm_rndid") + "_" + nTagId).remove();
              privateMethods.refreshHiddenTagList.call($self);
              $self.trigger('tm:popped', tagBeingRemoved);
            }
        },

        empty : function() {
            // console.log('empty');

            var $self = $(this), tlis = $self.data("tlis"), tlid = $self.data("tlid"), nTagId;

            while (tlid.length > 0) {
                nTagId = tlid.pop();
                tlis.pop();
                $("#" + $self.data("tm_rndid") + "_" + tagId).remove();
                privateMethods.refreshHiddenTagList.call($self);
            }
            $self.trigger('tm:emptied', null);

            privateMethods.showOrHide.call($self);
        },

        tags : function() {
            var $self = this, tlis = $self.data("tlis");
            return tlis;
        }
    },

    privateMethods = {
        showOrHide : function () {
            var $self = this, opts = $self.data('opts'), tlis = $self.data("tlis");

            if (opts.maxTags > 0 && tlis.length < opts.maxTags) {
                $self.show();
                $self.trigger('tm:show');
            }

            if (opts.maxTags > 0 && tlis.length >= opts.maxTags) {
                $self.hide();
                $self.trigger('tm:hide');
            }
        },

        tagClasses : function () {
            var $self = $(this), opts = $self.data('opts'), tagBaseClass = opts.tagBaseClass,
            inputBaseClass = opts.inputBaseClass, cl;
            // 1) default class (tm-tag)
            cl = tagBaseClass;
            // 2) interpolate from input class: tm-input-xxx --> tm-tag-xxx
            if ($self.attr('class')) {
                $.each($self.attr('class').split(' '), function (index, value) {
                    if (value.indexOf(inputBaseClass + '-') !== -1) {
                        cl += ' ' + tagBaseClass + value.substring(inputBaseClass.length);
                    }
                });
            }
            // 3) tags from tagClass option
            cl += (opts.tagClass ? ' ' + opts.tagClass : '');
            return cl;
        },

        trimTag : function (tag, delimiterChars) {
            var i;
            tag = $.trim(tag);
            // truncate at the first delimiter char
            i = 0;
            for (i; i < tag.length; i++) {
                if ($.inArray(tag.charCodeAt(i), delimiterChars) !== -1) { break; }
            }
            return tag.substring(0, i);
        },

        refreshHiddenTagList : function () {
            var $self = $(this), tlis = $self.data("tlis"), lhiddenTagList = $self.data("lhiddenTagList");

            if (lhiddenTagList) {
                $(lhiddenTagList).val(tlis.join($self.data('opts').baseDelimiter)).change();
            }

            $self.trigger('tm:refresh', tlis.join($self.data('opts').baseDelimiter));
        },

        killEvent : function (e) {
            e.cancelBubble = true;
            e.returnValue = false;
            e.stopPropagation();
            e.preventDefault();
        },

        keyInArray : function (e, ary) {
            return $.inArray(e.which, ary) !== -1;
        },

        applyDelimiter : function (e) {
            var $self = $(this);
            publicMethods.pushTag.call($self,$(this).val());
            e.preventDefault();
        },

        prefill : function (pta) {
            var $self = $(this);
            $.each(pta, function (key, val) {
                publicMethods.pushTag.call($self, val, true);
            });
        },

        pushAllTags : function (e, tag) {
            var $self = $(this), opts = $self.data('opts'), tlis = $self.data("tlis");
            if (opts.AjaxPushAllTags) {
                if (e.type !== 'tm:pushed' || $.inArray(tag, opts.prefilled) === -1) {
                    $.post(opts.AjaxPush, $.extend({ tags: tlis.join(opts.baseDelimiter) }, opts.AjaxPushParameters));
                }
            }
        },

        spliceTag : function (nTagId) {
            var $self = this, tlis = $self.data("tlis"), tlid = $self.data("tlid"), idx = $.inArray(nTagId, tlid),
            tagBeingRemoved;


            if (-1 !== idx) {
                tagBeingRemoved = tlis[idx];
                $self.trigger('tm:splicing', tagBeingRemoved);
                var id_to_remove = "#" + $self.data("tm_rndid") + "_" + nTagId;
                $(id_to_remove).remove();
                tlis.splice(idx, 1);
                tlid.splice(idx, 1);
                privateMethods.refreshHiddenTagList.call($self);
                $self.trigger('tm:spliced', tagBeingRemoved);
                if (1 ==  $( $self.data().opts.hiddenContainerId ).find($("input[item-id='"+nTagId+"']")).length) {
                     $( $self.data().opts.hiddenContainerId ).find($("input[item-id='"+nTagId+"']")).remove();
                }else if(1 == $( $self.data().opts.hiddenContainerId ).find($("input[value='"+nTagId+"']")).length) {
                    $( $self.data().opts.hiddenContainerId ).find($("input[value='"+nTagId+"']")).remove();
                }
            }

            privateMethods.showOrHide.call($self);
        },

        init : function (options) {
            // console.log('init');

            var opts = $.extend({}, defaults, options), delimiters, keyNums;

            delimiters = opts.delimeters || opts.delimiters; // 'delimeter' is deprecated
            keyNums = [9, 13, 17, 18, 19, 37, 38, 39, 40]; // delimiter values to be handled as key codes
            opts.delimiterChars = [];
            opts.delimiterKeys = [];


            $.each(delimiters, function (i, v) {
                if ($.inArray(v, keyNums) !== -1) {
                    opts.delimiterKeys.push(v);
                } else {
                    opts.delimiterChars.push(v);
                }
            });

            opts.baseDelimiter = String.fromCharCode(opts.delimiterChars[0] || 44);
            opts.tagBaseClass = 'tm-tag';
            opts.inputBaseClass = 'tm-input';

            if (!$.isFunction(opts.validator)) { opts.validator = null; }

            this.each(function() {
                var $self = $(this), hiddenObj ='', rndid ='', albet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

                // prevent double-initialization of TagManager
                if ($self.data('tagManager')) { return false; }
                $self.data('tagManager', true);

                for (var i = 0; i < 5; i++) {
                  rndid += albet.charAt(Math.floor(Math.random() * albet.length));
                }

                $self.data("tm_rndid", rndid);

                // store instance-specific data in the DOM object
                $self.data('opts',opts)
                    .data('tlis', []) //list of string tags
                    .data('tlid', []); //list of ID of the string tags


                if (opts.output === null) {
                    hiddenObj = $('<input/>', {
                        type: 'hidden',
                        name: opts.hiddenTagListName
                    });
                    $self.after(hiddenObj);
                    $self.data("lhiddenTagList", hiddenObj);
                } else {
                    $self.data("lhiddenTagList", $(opts.output));
                }

                if (opts.AjaxPushAllTags) {
                    $self.on('tm:spliced', privateMethods.pushAllTags);
                    $self.on('tm:popped', privateMethods.pushAllTags);
                    $self.on('tm:pushed', privateMethods.pushAllTags);
                }

                // hide popovers on focus and keypress events
                $self.on('focus keypress', function(e) {
                    // console.log('focus');
                    if ($(this).popover) { $(this).popover('hide'); }
                });

                // handle ESC (keyup used for browser compatibility)
                if (opts.isClearInputOnEsc) {
                    $self.on('keyup', function(e) {
                        // console.log('keyup');
                        if (e.which === 27) {
                            $(this).val('');
                            privateMethods.killEvent(e);
                        }
                    });
                }

                $self.on('keypress', function(e) {
                    // console.log('keypress');
                    // push ASCII-based delimiters
                    if (privateMethods.keyInArray(e, opts.delimiterChars)) {
                        privateMethods.applyDelimiter.call($self, e);
                    }
                });

                $self.on('keydown', function(e) {
                    // console.log('keydown');

                    // disable ENTER
                    if (e.which === 13) {
                        if (opts.preventSubmitOnEnter) {
                            privateMethods.killEvent(e);
                        }
                    }

                    // push key-based delimiters (includes <enter> by default)
                    if (privateMethods.keyInArray(e, opts.delimiterKeys)) {
                        privateMethods.applyDelimiter.call($self, e);
                    }
                });

                // BACKSPACE (keydown used for browser compatibility)
                if (opts.deleteTagsOnBackspace) {
                    $self.on('keydown', function(e) {
                        if (privateMethods.keyInArray(e, opts.backspace)) {
                            if ($(this).val().length <= 0) {
                                publicMethods.popTag.call($self);
                                privateMethods.killEvent(e);
                            }
                        }
                    });
                }

                $self.change(function(e) {
                    if (!/webkit/.test(navigator.userAgent.toLowerCase())) {
                        $self.focus();
                    } // why?

                    /* unimplemented mode to push tag on blur
                     else if (tagManagerOptions.pushTagOnBlur) {
                     pushTag($(this).val());
                     } */
                    privateMethods.killEvent(e);
                });

                if (opts.prefilled !== null) {
                    if (typeof (opts.prefilled) === "object") {
                        privateMethods.prefill.call($self, opts.prefilled);
                    } else if (typeof (opts.prefilled) === "string") {
                        privateMethods.prefill.call($self, opts.prefilled.split(opts.baseDelimiter));
                    } else if (typeof (opts.prefilled) === "function") {
                        privateMethods.prefill.call($self, opts.prefilled());
                    }
                } else if (opts.output !== null) {
                    if ($(opts.output) && $(opts.output).val()) { var existing_tags = $(opts.output); }
                    privateMethods.prefill.call($self,$(opts.output).val().split(opts.baseDelimiter));
                }

            });

            return this;
        }
    };

    $.fn.tagsManager = function(method) {
        var $self = $(this);

        if (!(0 in this)) { return this; }

        if ( publicMethods[method] ) {
            return publicMethods[method].apply( $self, Array.prototype.slice.call(arguments, 1) );
        } else if ( typeof method === 'object' || ! method ) {
            return privateMethods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist.' );
            return false;
        }
    };

}(jQuery));
