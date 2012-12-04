'use strict';

function FileUploaderCTR($scope) {
    var queueList = [],
        selectedFileList = [],
        isObjectDragOver = false,
        fileUploader = new FileUploader("../server/upload.php");

    $scope.fileList = [];
    $scope.uploading = false;
    $scope.currentFile = null;
    $scope.rememberFileOverwriteSetting = false;
    $scope.fileOverwrite = false;

    fileUploader.fileOverwrite = $scope.fileOverwrite;

    fileUploader.onStateChange = function(event) {
        $scope.uploading = true;
        switch (event.type) {
            case "checkStart" :
                break;
            case "checkComplete" :
                onUploadCheckComplete(event);
                break;
            case "continue" :
                break;
            case "stop" :
                onUploadStop(event);
                break;
            case "progress" :
                onUploadProgress(event);
                break;
            case "complete" :
                onUploadComplete(event);
                break;
            case "warning" :
                break;
            case "error" :
            case "timeout" :
                onUploadError(event);
                break;
        }
    }
    function onUploadCheckComplete(event) {
        $scope.currentFile.friendlyName = event.fname;
    }
    function onUploadProgress(event) {
        var progress = Math.ceil((event.bytesUploaded/event.bytesTotal) * 100);
        $scope.currentFile.uploading = true;
        $scope.currentFile.progressStyle = "width: " + progress + "%;"
        $scope.$apply();
    }
    function onUploadStop(event) {
        $scope.uploading = $scope.currentFile.uploading = false;
    }
    function onUploadComplete(event) {
        $scope.currentFile.uploading = false;
        $scope.currentFile.uploaded = true;
        if (!$scope.rememberFileOverwriteSetting) { fileUploader.fileOverwrite = false; }
        $scope.startFileUpload();
        $scope.uploading = queueList.length > 0;
        $scope.$apply();
    }
    function onUploadError(event) {
        if (event.errorId==410) {
            if ($scope.rememberFileOverwriteSetting) {
                if (!$scope.fileOverwrite) {
                    setTimeout(function() {
                        $scope.handleFileExists();
                        $scope.$apply();
                    }, 100);
                } else {
                    $scope.handleFileExists();
                }
            } else {
                $scope.modalShown = true;
                $scope.$apply();
            }
        } else {
            $scope.modalShown = true;
            $scope.$apply();
        }
    }
    $scope.haveFileAPI = function() {
        return FileUploader.haveFileAPI();
    }

    $scope.getValueIf = function(variable,upload,stop) {
        return !variable?upload:stop;
    }

    $scope.isSelectedFileListEmpty = function() {
        return selectedFileList.length==0;
    }
    $scope.isFileListEmpty = function() {
        return $scope.fileList.length==0;
    }
    $scope.isDragDialogVisible = function() {
        return $scope.isFileListEmpty() || isObjectDragOver;
    }
    $scope.isDragMessageVisible = function() {
        return !isObjectDragOver && $scope.haveFileAPI();
    }
    $scope.isDropMessageVisible = function() {
        return isObjectDragOver && $scope.haveFileAPI();
    }
    $scope.isUploadEnabled = function() {
        return !($scope.isFileListEmpty() || queueList.length==0);
    }

    $scope.onDrop = function() {
        if (!$scope.haveFileAPI()) return;
        event.preventDefault();
        isObjectDragOver = false;
        $scope.addFiles(event.dataTransfer.files);
    }
    $scope.onDragOver = function() {
        isObjectDragOver = true;
    }
    $scope.onDragLeave = function() {
        isObjectDragOver = false;
    }

    $scope.onFileInputSelection = function() {
        $scope.addFiles(event.target.files);
    }

    $scope.addFiles = function(fileList) {
        _.each(fileList, function(file) {
            file.selected = false;
            file.uploading = false;
            file.uploaded = false;
            $scope.fileList.push(file);
            loadImage(file);
        })
        queueList = _.filter($scope.fileList, function(file){ return !file.uploaded; });
    }
    function loadImage(file) {
        if (file.type.match("image.*")) {
            var reader = new FileReader();
            reader.onload = function(event) {
                file.imageDataAsStyle = "background-image:url("+event.target.result+")";
                $scope.$apply();
            };
            reader.readAsDataURL(file);
        }
    }

    $scope.toggleSelection = function(file) {
        file.selected = file.selected?false:true;
        if (file.selected) {
            selectedFileList.push(file);
        } else {
            selectedFileList = _.reject(selectedFileList, function(f) { return f==file; });
        }
        event.stopPropagation();
    }
    $scope.getSelectedFileListLength = function() {
        return selectedFileList.length==0?"":selectedFileList.length.toString()+ " ";
    }
    $scope.deselectAll = function() {
        _.each(selectedFileList, function(file) { file.selected = false; })
        selectedFileList = [];
    }
    $scope.isFileSelected = function(file) {
        return file.selected;
    }
    $scope.isFileUploading = function(file) {
        return file.uploading;
    }
    $scope.isFileUploaded = function(file) {
        return file.uploaded;
    }
    $scope.removeSelectedFiles = function() {
        $scope.fileList = _.difference($scope.fileList, selectedFileList);
        selectedFileList = [];
    }
    $scope.toggleUpload = function() {
        if ($scope.uploading) {
            $scope.stopFileUpload();
        } else {
            $scope.startFileUpload();
        }
    }
    $scope.stopFileUpload = function() {
        $scope.currentFile.uploading = false;
        fileUploader.stopFileUpload();
    }
    $scope.startFileUpload = function() {
        queueList = _.filter($scope.fileList, function(file){ return !file.uploaded; });
        if (queueList.length==0) return;
        $scope.currentFile = _.first(queueList);
        fileUploader.startFileUpload($scope.currentFile);
    }
    $scope.handleFileExists = function(overwrite) {
        overwrite = typeof overwrite==="undefined"?$scope.fileOverwrite:overwrite;
        $scope.modalShown = false;
        $scope.fileOverwrite = overwrite;
        fileUploader.fileOverwrite = $scope.fileOverwrite;
        if (!$scope.fileOverwrite) {
            $scope.currentFile.uploaded = true;
        }
        $scope.startFileUpload();
    }
}
