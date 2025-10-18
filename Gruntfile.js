module.exports = function( grunt ) {

	'use strict';

	const pkg = grunt.file.readJSON( 'package.json' );

	grunt.initConfig({

		pkg: pkg,

		addtextdomain: {
			options: {
				textdomain: 'bp-gifts',
			},
			update_all_domains: {
				options: {
					updateDomains: true
				},
				src: [ '*.php', '**/*.php', '!.git/**/*', '!bin/**/*', '!node_modules/**/*', '!tests/**/*' ]
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: [ '.git/*', 'bin/*', 'node_modules/*', 'tests/*' ],
					mainFile: 'bp-gifts.php',
					potFilename: 'bp-gifts.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

		copy: {
			release: {
				src: [
					'**',
					'!src/**',
					'!assets/js/src/**',
					'!assets/css/src/**',
					'!assets/css/sass/**',
					'!vendor/**',
					'!bin/**',
					'!release/**',
					'!no-releases/**',
					'!docs/**',
					'!tests/**',
					'!node_modules/**',
					'!**/*.md',
					'!.travis.yml',
					'!.bowerrc',
					'!.gitignore',
					'!.distignore',
					'!.editorconfig',
					'!bower.json',
					'!Dockunit.json',
					'!Gruntfile.js',
					'!package.json',
					'!package-lock.json',
					'!yarn.lock',
					'!composer.json',
					'!composer.lock',
					'!phpcs.xml',
					'!phpunit.xml',
					'!phpunit.xml.dist',
					'!.phpcs.xml.dist',
				],
				dest: 'release/' + pkg.version + '/'
			},
			svn: {
				cwd: 'release/<%= pkg.version %>/',
				expand: true,
				src: '**',
				dest: 'release/svn/'
			},
			no_releases_readme: {
				src: 'readme.txt',
				dest: 'no-releases/bp-gifts.txt'
			}
		},

		clean: {
			release: [
				'release/<%= pkg.version %>/',
				'release/svn/'
			]
		},

		compress: {
			dist: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>',
				src: ['**/*'],
				dest: '<%= pkg.name %>'
			}
		}

	});

	// Load tasks
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );

	// Register tasks
	grunt.registerTask( 'default', [ 'i18n', 'readme' ] );
	grunt.registerTask( 'i18n', [ 'addtextdomain', 'makepot' ] );
	grunt.registerTask( 'readme', [ 'wp_readme_to_markdown' ] );
	grunt.registerTask( 'release', [ 'clean:release', 'copy:release', 'copy:svn', 'copy:no_releases_readme', 'compress' ] );

	// Normalize line endings
	grunt.util.linefeed = '\n';
};