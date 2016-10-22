module.exports = function(grunt) {
    // Load tasks
    require('load-grunt-tasks')(grunt);
    // Display task timing
    require('time-grunt')(grunt);
    // Project configuration.
    grunt.initConfig({
        // Metadata
        pkg : grunt.file.readJSON('package.json'),
        // Variables
        browserify: {
            dist: {
                src : ['js/src/index.js'],
                dest : 'js/bundle.js'
            }
        },
        paths : {
            // Base dir assets dir
            base : '',

            // PHP assets
            php : {
                files_std : ['*.php', '**/*.php', '!node_modules/**/*.php'], // Standard file match
                files : '<%= paths.php.files_std %>' // Dynamic file match
            },

            // JavaScript assets
            js : {
                base : 'js', //Base dir
                src : '<%= paths.js.base %>/src', // Development code
                dest : '<%= paths.js.base %>/prod', // Production code
                files_std : '**/<%= paths.js.src %>/**/*.js', // Standard file match
                files : '<%= paths.js.files_std %>' // Dynamic file match
            },

            // Sass assets
            sass : {
                src : 'sass', // Source files dir
                dest : 'css', // Compiled files dir
                ext : '.css', // Compiled extension
                target : '*.scss', // Only Sass files in CWD
                exclude : '!_*.scss', // Do not process partials
                base_src : '<%= paths.base %>/<%= paths.sass.src %>', //Base source dir
                base_dest : '<%= paths.base %>/<%= paths.sass.dest %>', //Base compile dir
            }
        }
    });

    // Load task configurations
    grunt.loadTasks('grunt');
    grunt.loadNpmTasks('grunt-browserify');
    // Default Tasks
    grunt.registerTask('build', ['phplint', 'jshint:all', 'uglify', ]);//'sass']);
    grunt.registerTask('watch_all', ['watch:js', 'watch:sass']);

    grunt.config('phplint', {
        options : {
            phpArgs : {
                '-lf': null
            }
        },
        all : {
            src : '<%= paths.php.files %>'
        }
    });

    grunt.config('jshint', {
        options : {
            reporter: require('jshint-stylish'),
            curly : true,
            eqeqeq : true,
            immed : true,
            latedef : true,
            newcap : false,
            noarg : true,
            sub : true,
            undef : true,
            unused : true,
            boss : true,
            eqnull : true,
            browser : true,
            jquery : true,
            node: true,
            esversion: 6,
            globals : {}
        },
        grunt : {
            options : {
                node : true
            },
            src : ['Gruntfile.js', 'grunt/*.js']
        },
        all : {
            options : {
                globals : {
                    'SLB' : true,
                    'console' : true
                }
            },
            src : ['<%= paths.js.files %>']
        },
    });
    grunt.config('uglify', {
        options : {
            mangle: false,
            report: 'min'
        },
        all : {
            files : [{
                expand : true,
                cwd : '',
                dest : '',
                src : ['<%= paths.js.files %>'],
                rename : function(dest, srcPath) {
                    return srcPath.replace('/' + grunt.config.get('paths.js.src') + '/', '/' + grunt.config.get('paths.js.dest') + '/');
                }
            }]
        },
    });
};
