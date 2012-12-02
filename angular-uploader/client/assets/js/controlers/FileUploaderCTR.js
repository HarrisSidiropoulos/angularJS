'use strict';

function FileUploaderCTR($scope) {
    $scope.fileList = [];
    var isObjectDragOver = false;

    $scope.isFileListEmpty = function() {
        return $scope.fileList.length==0;
    }
    $scope.isDragDialogVisible = function() {
        return $scope.isFileListEmpty() || isObjectDragOver;
    }
    $scope.isDragMessageVisible = function() {
        return isObjectDragOver;
    }
    $scope.isDropMessageVisible = function() {
        return !isObjectDragOver;
    }

    $scope.onDrop = function() {
        event.preventDefault();
        $scope.addFiles(event.dataTransfer.files);
    }
    $scope.onDragOver = function() {
        isObjectDragOver = true;
    }
    $scope.onDragLeave = function() {
        isObjectDragOver = false;
    }

    $scope.addFiles = function(fileList) {
        angular.forEach(fileList, function(file) {
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
}
