angular.module('components', [])
    .directive('sGallery', function($http, $parse, $compile) {
        return {
            restrict: 'E',
            scope: {
                src: "@",
                srcVar: "=",
                imagesPath : "@",
                imagesData: "="
            },
            templateUrl: 'assets/partials/gallery.html',
            compile: function compileFn(templateElement, attrs) {

                templateElement.addClass('compiling');

                return function linkFn(scope, compileElement, attrs) {
                    scope.imagesPath = "images/"
                    scope.currentImage = {name:"img001.jpg", description:"", selected: false};
                    scope.images = [scope.currentImage];

                    scope.$watch('imagesPath', function(value) {
                        if (typeof value==="undefined") return;
                        scope.imagesPath = value;
                    });
                    scope.$watch("src", function(value) {
                        if (typeof value==="undefined") return;
                        scope.loadImageData(value);
                    });

                    scope.$watch("srcVar", function(value) {
                        if (typeof value==="undefined") return;
                        scope.loadImageData(value);
                    });

                    scope.$watch("imagesData", function(value) {
                        if (typeof value==="undefined") return;
                        scope.onImageDataSuccess(value);
                    });

                    scope.loadImageData = function(value) {
                        $http.get(value).success(scope.onImageDataSuccess).error(scope.onImageDataError);
                    }
                    scope.onImageDataSuccess = function(data, status) {
                        scope.images = data;
                        scope.setImage(data[0]);
                    }
                    scope.onImageDataError = function(data, status, headers, config) {
                        console.log("Error : "+status);
                    }

                    scope.isSelected = function(image) {
                        return image.selected ? 'selected' : '';
                    }
                    scope.setImage = function(image) {
                        scope.currentImage.selected = false;
                        scope.currentImage = image;
                        scope.currentImage.selected = true;
                    }

                    compileElement.bind('dblclick', function() {
                        if (!setAttributeValue('src', 'assets/data/images.json')) {
                            if (typeof attrs.srcVar!=="undefined") {
                                $parse(attrs.srcVar).assign(scope.$parent, 'assets/data/images.json');
                                scope.$parent.$apply();
                            }
                        }

                    })

                    function setAttributeValue(attr, value) {
                        if (typeof attrs[attr]==="undefined") return false;
                        if (attrs[attr].indexOf('{{')==0 && attrs[attr].indexOf('}}')==attrs[attr].length-2) {
                            var bindVar = attrs[attr].substr(2,attrs[attr].length-4);
                            if (typeof scope.$parent[bindVar]!=="undefined") {
                                scope.$parent[bindVar] = value;
                                scope.$parent.$apply();
                            }
                        } else {
                            scope[attr] = value;
                            attrs.$set(attr, value);
                            scope.$apply();
                        }
                        return true;
                    }

                }
            }

        }
    })

angular.module('Gallery', ['components']);