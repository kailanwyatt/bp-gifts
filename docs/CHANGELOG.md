# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-10-10

### Added
- Modern WordPress coding standards compliance
- Comprehensive input sanitization and output escaping
- Responsive CSS design for mobile devices
- Admin interface with meta boxes and instructions
- Proper uninstall cleanup functionality
- Translation support improvements
- Performance optimizations with better caching
- Security improvements throughout codebase
- Modern JavaScript with better error handling
- Comprehensive documentation and code comments

### Changed
- **BREAKING**: Minimum PHP version requirement increased to 7.4
- **BREAKING**: Minimum WordPress version requirement increased to 5.0
- Complete code refactoring using WordPress coding standards
- Replaced direct database queries with WP_Query
- Improved error handling and user feedback
- Enhanced security with proper data validation
- Modernized JavaScript and CSS architecture
- Better file organization and structure
- Updated plugin headers and metadata
- Improved transient caching system

### Fixed
- Security vulnerabilities in data handling
- File naming inconsistencies (bp-gitst-en_US.po → bp-gifts-en_US.po)
- Improper escaping of output data
- Missing nonce verification in forms
- SQL injection vulnerabilities
- XSS vulnerabilities in admin and frontend
- Performance issues with gift loading
- Mobile responsiveness issues
- JavaScript errors and conflicts

### Security
- Added proper nonce verification for all forms
- Implemented comprehensive data sanitization
- Added output escaping for all dynamic content
- Removed direct database queries in favor of WordPress APIs
- Added capability checks for admin functions
- Implemented proper file access restrictions

### Deprecated
- Legacy function names (maintained for backwards compatibility)
- Old class name `BP_GIFTS_EXT` (now `BP_Gifts_Loader`)

## [1.0.0] - Original Release

### Added
- Initial plugin release
- Basic gift management functionality
- BuddyPress message integration
- Simple admin interface
- Gift selection modal
- Basic caching with transients