'use strict';

function FileUploaderCTR($scope) {
    $scope.fileList = [];
    $scope.uploading = false;
    //$scope.modalShown = false;

    var queueList = [],
        selectedFileList = [],
        isObjectDragOver = false,
        currentFile = null,
        fileUploader = new FileUploader("../server/upload.php");

    fileUploader.onStateChange = function(event) {
        var progress = Math.ceil((event.bytesUploaded/event.bytesTotal) * 100);
        $scope.uploading = true;
        $scope.modalShown = false;
        switch (event.type) {
            case "checkStart" :
                break;
            case "checkComplete" :
                currentFile.friendlyName = event.fname;
                break;
            case "continue" :
                break;
            case "stop" :
                $scope.uploading = currentFile.uploading = false;
                break;
            case "progress" :
                currentFile.uploading = true;
                currentFile.progressStyle = "width: " + progress + "%;"
                $scope.$apply();
                break;
            case "complete" :
                currentFile.uploading = false;
                currentFile.uploaded = true;
                $scope.startFileUpload();
                $scope.uploading = queueList.length > 0;
                $scope.$apply();
                break;
            case "warning" :
                break;
            case "error" :
            case "timeout" :
                $scope.modalShown = true;
                break;
        }
    }

    $scope.haveFileAPI = function() {
        return FileUploader.haveFileAPI();
    }

    $scope.getValue = function(variable,upload,stop) {
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
        console.log($scope.modalShown);
        $scope.modalShown = true;
        return;
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
        currentFile.uploading = false;
        fileUploader.stopFileUpload();
    }
    $scope.startFileUpload = function() {
        queueList = _.filter($scope.fileList, function(file){ return !file.uploaded; });
        if (queueList.length==0) return;
        currentFile = _.first(queueList);
        fileUploader.startFileUpload(currentFile);
    }
}
