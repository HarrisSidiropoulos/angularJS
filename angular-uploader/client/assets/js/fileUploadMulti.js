(function(){

    var fileUploader = new FileUploader("../server/upload.php"),
        files = new FileListManager(),
        filesCompleted = new FileListManager(),
        selectedFiles = new FileListManager(),
        infoLog = $("#infoLog"),
        manageFilesGroup = $("#manageFilesGroup"),
        addFilesBtn = $("#addFilesBtn"),
        removeFilesBtn = $("#removeFilesBtn"),
        filesInput = $("#filesInput"),
        uploadFilesButton = $("#uploadFilesButton"),
        myAlert = $("#myAlert"),
        dropZone = $("#dropZone"),
        dragDialog = $("#dragDialog"),
        dragMessage = $("#dragMessage"),
        dropMessage = $("#dropMessage"),
        thumbNailsPanel = $("#thumbNailsPanel"),
        thumbs = $('.thumbnails'),
        haveFileAPI = FileUploader.haveFileAPI(),
        thumbTemplate = $(".thumbnails li").clone(),
        rememberToOverwriteAll = false,
        overwriteAll = false,
        bytesPerSecond = 0,
        bytesPerSecondMetr = 0;

    $(document).bind('ready', function() {
        init();
    });
    function init() {

        infoLog = $("#infoLog");
        manageFilesGroup = $("#manageFilesGroup");
        addFilesBtn = $("#addFilesBtn");
        removeFilesBtn = $("#removeFilesBtn");
        filesInput = $("#filesInput");
        uploadFilesButton = $("#uploadFilesButton");
        myAlert = $("#myAlert");
        dropZone = $("#dropZone");
        dragDialog = $("#dragDialog");
        dragMessage = $("#dragMessage");
        dropMessage = $("#dropMessage");
        thumbNailsPanel = $("#thumbNailsPanel");
        thumbs = $('.thumbnails');
        thumbTemplate = $(".thumbnails li").clone();

        addFilesBtn.bind('click', onAddBtnClick);
        removeFilesBtn.bind('click', onRemoveFilesClick);
        filesInput.bind('change', onFilesInputChange);
        uploadFilesButton.bind("click", onUploadButtonClick);
        thumbs.bind("click", onThumbsClick);
        thumbs.parent().bind('click', onThumbsPanelClick);

        selectedFiles.addEventListener('add', onFilesAddedToSelection);
        selectedFiles.addEventListener('remove', onFilesRemovedFromSelection);

        document.getElementById("dropZone").addEventListener("drop", onFilesDropped, false);
        document.getElementsByTagName("body")[0].addEventListener("dragover", onDocumentDragOver, false);
        document.getElementById("dropZone").addEventListener("dragover", onDragOver, false);
        document.getElementById("dropZone").addEventListener("dragleave", onDragOut, false);

        //fileUploader.forceAsync = true;
        fileUploader.onStateChange = onUploadStateChange;

        $('.thumbnails').children().remove();
        dropZone.removeClass("hide");
        //filesInput.hide();
        dropMessage.hide();
        resize();
    }

    $(window).bind("resize", function(e) {
        resize();
    });
    function resize() {
        if (thumbNailsPanel.length==0) return;
        thumbNailsPanel.height($(window).height()-thumbNailsPanel.offset().top - 60);

        dropZone.height(thumbNailsPanel.height());
        dropZone.width(thumbNailsPanel.width()-14);

        dropZone.css("top", thumbNailsPanel.offset().top+"px");
        dropZone.css("left", thumbNailsPanel.offset().left+"px");

        dragDialog.css("margin-left", ((dropZone.width()-dragDialog.width())/2)+"px");
        dragDialog.css("margin-top", ((dropZone.height()-dragDialog.height())/2)+"px");
    }
    function onThumbsPanelClick(event) {
        selectedFiles.removeAll();
    }
    function onThumbsClick(event) {
        var liItem = $(event.target).closest('li'),
            index = liItem.index(),
            thumbnail = liItem.find('.thumbnail'),
            file = files.list[index];

        if (thumbnail.length===0 || thumbnail.hasClass('uploading') || thumbnail.hasClass('disabled')) return;
        if (thumbnail.hasClass('selected')) {
            var selectedIndex = selectedFiles.getItemIndex(file.name, file.size);
            selectedFiles.removeItem(selectedFiles.list[selectedIndex]);
        } else {
            selectedFiles.addItem({name:file.name, size:file.size, file: file});
        }
        event.stopPropagation();
        event.preventDefault();
    }

    function onRemoveFilesClick(event) {
        while (selectedFiles.length>0) {
            var selectedItem = selectedFiles.list[0],
                file = selectedItem.file,
                item = getThumbByFileObject(selectedItem);

            item.remove();
            files.removeItem(file);
            selectedFiles.removeItem(selectedItem);
        }
        if (files.length==0) {
            resize();
        }
        event.stopPropagation();
        event.preventDefault();
    }

    function refreshRemoveButton() {
        if (selectedFiles.length==0) {
            removeFilesBtn.val("Remove Selected Files");
            removeFilesBtn.addClass('disabled');
        } else {
            removeFilesBtn.val("Remove (" + selectedFiles.length + ") Selected File" + (selectedFiles.length>1?"s":""));
            removeFilesBtn.removeClass('disabled');
        }
    }

    function refreshUploadButton() {
        if (files.length>0 && files.length>filesCompleted.length) {
            dropZone.hide();
            uploadFilesButton.removeClass("disabled");
        } else {
            if (files.length==0) dropZone.show();
            uploadFilesButton.addClass("disabled");
        }
    }

    function onFilesAddedToSelection(event) {
        var items = event.itemsChanged,
            l = items.length;

        for (var i=0;i<l;i++) {
            var item = getThumbByFileObject(items[i]).find('.thumbnail');
            $(item).addClass('selected');
        }
        refreshRemoveButton();
    }
    function onFilesRemovedFromSelection(event) {
        var items = event.itemsChanged,
            l = items.length;

        for (var i=0;i<l;i++) {
            var item = getThumbByFileObject(items[i]).find('.thumbnail');
            $(item).removeClass('selected');
        }
        refreshRemoveButton();
        refreshUploadButton();
    }
    function getThumbByFileObject(file) {
        return thumbs.find('li').eq(files.getItemIndex(file.name, file.size));
    }
    function getFileSelectionByFileObject(file) {
        var index = selectedFiles.getItemIndex(file.name, file.size);
        if (index<0) return null;
        return selectedFiles.list[index];
    }

    function onDocumentDragOver(event) {
        dropZone.show();
    }
    function onDragOver(event) {
        dropZone.show();
        dragMessage.hide();
        dropMessage.show();
        event.stopPropagation();
        event.preventDefault();
    }
    function onDragOut(event) {
        if (typeof files !== "undefined" && files.length>0) {
            dropZone.hide();
        }
        dragMessage.show();
        dropMessage.hide();
        event.stopPropagation();
        event.preventDefault();
    }
    function onFilesDropped(event) {
        dragMessage.show();
        dropMessage.hide();
        addFiles(event.dataTransfer.files);
        event.stopPropagation();
        event.preventDefault();
    }

    function onAddBtnClick(event) {
        filesInput.trigger("click");
        event.stopPropagation();
        event.preventDefault();
    }

    function onFilesInputChange(event) {
        addFiles(filesInput[0].files);
    }
    function addFiles(fileItems) {
        var fileItemsLength = files.addItems(fileItems),
            l = files.length,
            i = l-fileItemsLength,
            thumbs = $('.thumbnails').clone().empty(),
            thumb = thumbTemplate.clone();

        refreshUploadButton();

        for (i=l-fileItemsLength;i<l;i++) {
            var caption = thumb.find(".caption h5");
            caption.html(files.list[i].name);
            if ($.textMetrics(caption).width>134) {
                caption.attr('title', files.list[i].name);
            } else {
                caption.removeAttr('title');
            }
            thumb.removeClass("hidden");
            thumbs.append(thumb.clone());
        }
        $('.thumbnails').append(thumbs.children());
        //$(".thumbnails .caption h5[title]").tooltip();
        for (i=l-fileItemsLength;i<l;i++) {
            createThumb(files.list[i], i);
        }
    }
    function createThumb(theFile, index) {
        if (haveFileAPI && theFile.type.match("image.*")) {
            var reader = new FileReader(),
                img = $('.thumbnails li').eq(index).find("img"),
                max = 20,
                count = 0;

            reader.onload = function(event) {
                //img.attr("src", event.target.result);
                img.parent().css('background-image', 'url('+event.target.result+')');
                img.css('display', 'none');
                img.remove();
                //imageFit();
            };
            /*
            function imageFit() {
                if (img.css("marginTop")==="0px" && count<max) {
                    count++;
                    setTimeout(imageFit, 100);
                }
                img.css("marginTop", ((img.parent().height()-img.height())/2)+"px");
            }
            */
            reader.readAsDataURL(theFile);
        }
    }
    function onUploadButtonClick(event) {
        if (uploadFilesButton.val().toLowerCase()=="stop") {
            stopUpload();
        } else if (!uploadFilesButton.hasClass("disabled")) {
            doFileUpload();
        }
        event.stopPropagation();
        event.preventDefault();
    }
    function scrollToThumb(element) {
        var elementTop = element.position().top - element.parent().position().top,
            elementHeight = element.height(),
            thumbsTop = thumbs.parent().scrollTop(),
            thumbsHeight = thumbs.parent().height(),
            scrollPosition = 0;

        if ((elementTop+elementHeight)>(thumbsTop+thumbsHeight)) {
            scrollPosition = elementTop-(thumbsHeight-elementHeight);
            if (!fileUploader.isAsync()) {
                thumbs.parent().scrollTop(scrollPosition);
            } else {
                thumbs.parent().animate({scrollTop:scrollPosition},{duration:1, queue:false});
            }
        }
    }

    function doFileUpload(overwrite) {
        var fileOverwrite = typeof overwrite === "undefined" ? overwriteAll : overwrite,
            preloader = getThumbByFileObject(files.currentItem()).find(".loading"),
            thumbItem = getThumbByFileObject(files.currentItem()),
            thumbnail = thumbItem.find('.thumbnail'),
            selectedFile = getFileSelectionByFileObject(files.currentItem());

        scrollToThumb(thumbItem);
        uploadFilesButton.val('Stop');
        fileUploader.filePath = "test";
        fileUploader.fileOverwrite = fileOverwrite;
        manageFilesGroup.addClass('hidden');
        infoLog.removeClass('hidden');
        preloader.removeClass('hidden');
        selectedFiles.removeItem(selectedFile);
        thumbnail.addClass('uploading');
        fileUploader.startFileUpload(files.currentItem());
    }
    function onFileUploadComplete(event) {
        var preloader = getThumbByFileObject(files.currentItem()).find(".loading"),
            thumbnail = getThumbByFileObject(files.currentItem()).find('.thumbnail');

        thumbnail.removeClass('uploading');
        thumbnail.addClass('disabled');
        thumbnail.find(".progress").addClass('done');
        preloader.addClass('done');
        filesCompleted.addItem(files.currentItem());
        files.nextIndex();
        if (!files.hasNext()) {
            refreshUploadButton();
            onFileUploadStop(event);
            return;
        }
        doFileUpload();
        bytesPerSecondMetr = 0;
    }
    function onFileUploadStop(event) {
        uploadFilesButton.val("Upload");
        manageFilesGroup.removeClass('hidden');
        infoLog.addClass('hidden');
        bytesPerSecondMetr = 0;
    }

    function updateProgress(event) {
        bytesPerSecond = Math.max(bytesPerSecond, fileUploader.getBytesPerSecond());
        if (bytesPerSecond!=fileUploader.getBytesPerSecond()) bytesPerSecondMetr++;
        if (bytesPerSecondMetr==12) bytesPerSecond = fileUploader.getBytesPerSecond();

        var t = FileUploader.getUploadTimeRemaining(getTotalBytes(),
            getTotalBytesUploaded(files.currentIndex(), event.bytesUploaded),
            bytesPerSecond);
        if (t=="") t = "calculating...";
        infoLog.find('#timeRemaining').text(t);
        var progressBar = $('.thumbnails li').eq(files.currentIndex()).find(".progress .bar");
        if (!fileUploader.isAsync()) {
            progressBar.addClass('noTransition');
        }
        var progress = Math.ceil((event.bytesUploaded/event.bytesTotal) * 100);
        progressBar.css("width", progress+"%");
    }
    function getTotalBytesUploaded(index, bytesUploaded) {
        var f = files.list,
            totalSize = 0;

        for (var i= 0;i<index;i++) {
            totalSize += f[i].size;
        }
        return (totalSize+bytesUploaded);
    }
    function getTotalBytes() {
        var f = files.list,
            totalSize = 0;

        for (var i= 0, l= f.length;i<l;i++) {
            totalSize += f[i].size;
        }
        return totalSize;
    }

    function onError(event) {
        var modalHeader = myAlert.find(".modal-header"),
            modalBody = myAlert.find(".modal-body"),
            modalFooter = myAlert.find(".modal-footer"),
            modalCancelBtn = myAlert.find("#cancelBtn"),
            modalOkBtn = myAlert.find("#oklBtn"),
            overwriteAllCheckBox = myAlert.find("#overwriteAllCheckBox");

        //File already exists
        if (event.errorId==410 && rememberToOverwriteAll) {
            if (overwriteAll) {
                doFileUpload();
            } else {
                setTimeout(function() {
                    onFileUploadComplete(event);
                }, 100);
            }
            return;
        } else if (event.errorId==410) {
            overwriteAllCheckBox.show();
            modalHeader.html('Upload File Warning');
            modalBody.html('<p>File <strong>'+event.originalName+'</strong> already exist.</p><p>Do you want to replace it?</p>');
            modalCancelBtn.text("No");
            modalOkBtn.text('Replace File');
            modalOkBtn.bind('click', function() {
                overwriteAll = true;
                doFileUpload();
                myAlert.modal('hide');
            });
            modalCancelBtn.bind('click', function() {
                overwriteAll = false;
                if (rememberToOverwriteAll) {
                    onFileUploadComplete(event);
                }
                myAlert.modal('hide');
            });
            overwriteAllCheckBox.bind('click', function(){
                rememberToOverwriteAll = this.checked;
            });
        } else {
            modalHeader.html('Upload File Error');
            modalBody.html(event.msg);
            modalCancelBtn.text("Close");
            overwriteAllCheckBox.hide();
            modalOkBtn.hide();
            getThumbByFileObject(files.currentItem()).find('.thumbnail').removeClass('uploading');
        }
        myAlert.modal('show');

    }
    function onCheckComplete(event) {
        files.currentItem().friendlyName = event.fname;
    }

    function onUploadStateChange(event) {
        switch (event.type) {
            case "checkStart" :
                break;
            case "checkComplete" :
                onCheckComplete(event);
                break;
            case "continue" :
                break;
            case "stop" :
                onFileUploadStop(event);
                break;
            case "progress" :
                updateProgress(event);
                break;
            case "complete" :
                updateProgress(event);
                onFileUploadComplete(event);
                break;
            case "warning" :
                break;
            case "error" :
            case "timeout" :
                onError(event);
                break;
        }

    }

    function stopUpload() {
        fileUploader.stopFileUpload();
    }

})();

(function($) {

    $.textMetrics = function(el) {

        var h = 0, w = 0;

        var div = document.createElement('div');
        document.body.appendChild(div);
        $(div).css({
            position: 'absolute',
            left: -1000,
            top: -1000,
            display: 'none'
        });

        $(div).html($(el).html());
        var styles = ['font-size','font-style', 'font-weight', 'font-family','line-height', 'text-transform', 'letter-spacing'];
        $(styles).each(function() {
            var s = this.toString();
            $(div).css(s, $(el).css(s));
        });

        h = $(div).outerHeight();
        w = $(div).outerWidth();

        $(div).remove();

        var ret = {
            height: h,
            width: w
        };

        return ret;
    }

})(jQuery);