module.exports = function(grunt) {
    'use strict';

    // Time how long tasks take. Can help when optimizing build times
    require('time-grunt')(grunt);

    // Automatically load required Grunt tasks
    require('load-grunt-tasks')(grunt);

    // Project configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Clean up build directories
        clean: {
            build: ['build/'],
            dist: ['dist/']
        },

        // Copy files to build directory
        copy: {
            build: {
                files: [
                    {
                        expand: true,
                        src: [
                            '**',
                            '!node_modules/**',
                            '!build/**',
                            '!dist/**',
                            '!.git/**',
                            '!docs/**',
                            '!.gitignore',
                            '!Gruntfile.js',
                            '!package.json',
                            '!package-lock.json',
                            '!phpcs.xml',
                            '!README.md',
                            '!assets/src/**'
                        ],
                        dest: 'build/'
                    }
                ]
            }
        },

        // Minify CSS files
        cssmin: {
            options: {
                mergeIntoShorthands: false,
                roundingPrecision: -1
            },
            target: {
                files: [{
                    expand: true,
                    cwd: 'assets/css/',
                    src: ['*.css', '!*.min.css'],
                    dest: 'assets/css/',
                    ext: '.min.css'
                }]
            }
        },

        // Minify JavaScript files
        uglify: {
            options: {
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            build: {
                files: [{
                    expand: true,
                    cwd: 'assets/js/',
                    src: ['*.js', '!*.min.js'],
                    dest: 'assets/js/',
                    ext: '.min.js'
                }]
            }
        },

        // Concatenate files
        concat: {
            options: {
                separator: ';'
            },
            dist: {
                // Configure as needed for your specific files
                src: [],
                dest: ''
            }
        },

        // Lint JavaScript files
        jshint: {
            files: ['Gruntfile.js', 'assets/js/src/**/*.js'],
            options: {
                globals: {
                    jQuery: true,
                    console: true,
                    module: true,
                    document: true,
                    wp: true
                }
            }
        },

        // Lint PHP files
        phplint: {
            options: {
                limit: 10,
                stdout: true,
                stderr: true
            },
            files: ['**/*.php', '!node_modules/**', '!build/**', '!dist/**']
        },

        // PHP Code Sniffer
        phpcs: {
            application: {
                src: ['**/*.php', '!node_modules/**', '!build/**', '!dist/**']
            },
            options: {
                bin: 'vendor/bin/phpcs',
                standard: 'WordPress',
                extensions: 'php',
                ignore: 'node_modules/**,build/**,dist/**'
            }
        },

        // WordPress i18n
        makepot: {
            target: {
                options: {
                    domainPath: '/languages',
                    exclude: ['node_modules/.*', 'build/.*', 'dist/.*'],
                    mainFile: 'bp-gifts.php',
                    potFilename: 'bp-gifts.pot',
                    potHeaders: {
                        poedit: true,
                        'x-poedit-keywordslist': true
                    },
                    type: 'wp-plugin',
                    updateTimestamp: true,
                    updatePoFiles: true
                }
            }
        },

        // Create distribution archive
        compress: {
            main: {
                options: {
                    archive: 'dist/<%= pkg.name %>-<%= pkg.version %>.zip'
                },
                files: [
                    {
                        expand: true,
                        cwd: 'build/',
                        src: ['**'],
                        dest: '<%= pkg.name %>/'
                    }
                ]
            }
        },

        // Watch for changes and run tasks automatically
        watch: {
            css: {
                files: ['assets/css/src/**/*.css'],
                tasks: ['cssmin']
            },
            js: {
                files: ['assets/js/src/**/*.js'],
                tasks: ['jshint', 'uglify']
            },
            php: {
                files: ['**/*.php', '!node_modules/**', '!build/**', '!dist/**'],
                tasks: ['phplint']
            }
        }
    });

    // Register tasks
    grunt.registerTask('default', ['jshint', 'phplint']);
    grunt.registerTask('lint', ['jshint', 'phplint', 'phpcs']);
    grunt.registerTask('minify', ['cssmin', 'uglify']);
    grunt.registerTask('build', ['clean:build', 'minify', 'copy:build']);
    grunt.registerTask('dist', ['build', 'clean:dist', 'compress']);
    grunt.registerTask('i18n', ['makepot']);

    // Development task
    grunt.registerTask('dev', ['watch']);
};