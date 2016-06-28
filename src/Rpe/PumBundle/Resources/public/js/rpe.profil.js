$(document).ready(function () {
    //Drag image profil

    $('#drag_container .fileupload-preview img').jWindowCrop({
        targetWidth: 838,
        targetHeight: 344,
        smartControls: false,
        showControlsOnStart: false

    });

    $(".container-right.fileupload").click(function () {
        $(".container-right.fileupload .edit-photo-cover").addClass("active");
        $(".container-right.fileupload .edit-photo-profil").addClass("hide");

        $(document).on("click", ".container-right .save-image", function () {
            $(".container-right.fileupload .edit-photo-cover").removeClass("active");
            $(".container-right.fileupload .edit-photo-profil").removeClass("hide");
        });

    });


    $("#drag_container .controls-position .slide-up-image").click(function (event) {
        var p = $("#preview img");
        var position = p.position();
        $("#preview img").css("top", position.top + 5);
    });
    $("#drag_container .controls-position .slide-down-image").click(function (event) {
        var p = $("#preview img");
        var position = p.position();
        $("#preview img").css("top", position.top - 5);
    });

    //reset and close

    $(".profil-tabs .small-box .form-reset").click(function (e) {
        //  $(this).parents(".small-box.gray-dark").hide();
        $(this).parents(".small-box.gray-dark").removeClass("active");
        $(this).parents(".small-box.gray-dark").prev().removeClass("hidden");
    });

    //tabs Edit profil
    $(".profil-tabs .small-box.container").click(function (e) {
        e.preventDefault();
        $("#form_" + this.id).addClass("active");
        $("#" + this.id).addClass("hidden");
       
    });

    // ad tags to form elements this is should be deleted after dev
    //delete elements from table experience profil
    $(document).delegate('.block-experience .delete .icon-cancel', 'click', function () {
        $(this).parents(".block-experience").remove();
    });
    $('.small-box .add-experience').on('click', function () {
        var newNode = $(this).parent('.block-button').prev("ul").children(':last').clone();
        newNode.removeClass('hidden');
        newNode.appendTo($(this).parent('.block-button').prev("ul"));
        return false;
    });

});