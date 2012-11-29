angular.module('components', [])
    .directive('helloWorld', function() {
        return {
            restrict: 'E',
            scope: {
                name: '@'
            },
            templateUrl: 'assets/partials/hello.html'
        }
    })

angular.module('HelloApp', ['components'])
