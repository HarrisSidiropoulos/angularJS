'use strict';

function FileUploaderCTR($scope) {
    $scope.selectedFileList = [];
    $scope.fileList = [];
    var isObjectDragOver = false;

    $scope.isSelectedFileListEmpty = function() {
        return $scope.selectedFileList.length==0;
    }
    $scope.isFileListEmpty = function() {
        return $scope.fileList.length==0;
    }
    $scope.isDragDialogVisible = function() {
        return $scope.isFileListEmpty() || isObjectDragOver;
    }
    $scope.isDragMessageVisible = function() {
        return !isObjectDragOver;
    }
    $scope.isDropMessageVisible = function() {
        return isObjectDragOver;
    }

    $scope.onDrop = function() {
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
        angular.forEach(fileList, function(file) {
            file.selected = false;
            $scope.fileList.push(file);
            loadImage(file);
        })
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
            $scope.selectedFileList.push(file);
        } else {
            $scope.selectedFileList = _.reject($scope.selectedFileList, function(f) { return f==file; });
        }
    }
    $scope.isFileSelected = function(file) {
        return file.selected;
    }
    $scope.removeSelectedFiles = function() {
        $scope.fileList = _.difference($scope.fileList, $scope.selectedFileList);
        $scope.selectedFileList = [];
    }
}
