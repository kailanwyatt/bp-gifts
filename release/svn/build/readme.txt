=== Gifts for BuddyPress ===
Contributors: suiteplugins
Tags: buddypress, gifts, messages, social, community, accessibility, i18n, rtl
Donate link: https://suiteplugins.com/donate
Requires at least: 5.0
Tested up to: 6.8.3
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enhanced virtual gifts for BuddyPress with service architecture, thread-level gifts, accessibility features, and comprehensive user dashboard.

== Description ==

Gifts for BuddyPress is a completely modernized social enhancement plugin that revolutionizes how users send virtual gifts through BuddyPress messaging. With a service-based architecture, comprehensive accessibility features, and powerful new capabilities, it's perfect for any community looking to boost engagement.

= 🎁 What's New in v2.1 =

**Thread-Level Gifts** - Attach gifts to entire conversation threads, not just individual messages
**Service Architecture** - Modern dependency injection container with clean, maintainable code
**User Gift Dashboard** - Complete interface for users to view received and sent gifts with statistics
**Enhanced Accessibility** - Full WCAG compliance with screen reader support and keyboard navigation
**RTL Language Support** - Complete right-to-left language compatibility
**Advanced Search & Filtering** - Real-time gift search with category filtering
**Comprehensive Statistics** - Track gift history, popular gifts, and user engagement

= 🚀 Key Features =

**Modern Architecture**
* Service-based architecture with dependency injection
* Clean separation of concerns with interfaces
* Comprehensive error handling and logging
* WordPress coding standards compliance
* PHP 7.4+ with modern language features

**Enhanced User Experience**
* Thread-level and message-level gift attachment
* Real-time search with instant filtering
* Keyboard navigation throughout interface
* Responsive design for all devices
* Beautiful modal interface with pagination

**Accessibility Excellence**
* Full WCAG 2.1 AA compliance
* Screen reader support with proper ARIA labels
* Keyboard navigation for all interactions
* High contrast mode compatibility
* Focus management and announcements

**Internationalization & RTL**
* Complete translation readiness
* RTL (right-to-left) language support
* Logical CSS properties for international compatibility
* Cultural sensitivity in design patterns

**User Gift Management**
* Comprehensive gift dashboard with statistics
* Received and sent gift tracking
* Gift type filtering (thread vs message)
* Direct links to conversations
* Personal gift analytics

**Developer Features**
* Modern service container architecture
* Extensive hook system for customization
* Comprehensive API for gift management
* Clean template system
* Detailed code documentation

= 📊 User Dashboard Features =

**Gift History**
* View all received and sent gifts
* Filter by gift type (thread/message)
* Beautiful card-based layout
* Direct conversation links

**Statistics & Analytics**
* Total gifts received and sent
* Most received gift identification
* Most active gift senders
* Engagement metrics

**Smart Navigation**
* Tab-based interface (Received/Sent)
* Real-time filtering
* Responsive grid layout
* Touch-friendly mobile interface

= 🔧 Technical Specifications =

**Requirements**
* WordPress 5.0+
* BuddyPress 8.0+
* PHP 7.4+
* MySQL 5.7+ or MariaDB 10.2+

**Performance**
* Efficient database queries with proper indexing
* WordPress transients for intelligent caching
* Optimized JavaScript with minimal footprint
* Lazy loading for improved page speed

**Security**
* Proper data sanitization and validation
* Nonce verification for all actions
* Capability checks for administrative functions
* SQL injection prevention

= 🎯 Perfect For =

* Dating and relationship platforms
* Community forums and social networks
* Educational institutions with social features
* Gaming and entertainment communities
* Professional networking sites
* Family and friend networks
* Any BuddyPress-powered social platform

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Gifts for BuddyPress"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Extract the files to your `/wp-content/plugins/bp-gifts/` directory
3. Activate the plugin through the WordPress admin Plugins menu

= After Installation =

1. **Ensure BuddyPress is installed and activated**
2. **Navigate to "Gifts" in your WordPress admin menu**
3. **Add your first gift with a title and featured image**
4. **Configure gift categories (optional)**
5. **Users can now send gifts through BuddyPress messages!**

= Quick Setup Guide =

**Step 1: Add Gifts**
* Go to WordPress Admin > Gifts > Add New
* Upload an image and add a title
* Assign to a category (optional)
* Publish the gift

**Step 2: Configure Categories (Optional)**
* Go to Gifts > Gift Categories
* Create meaningful categories like "Birthday", "Love", "Friendship"
* Assign gifts to appropriate categories

**Step 3: Display User Dashboard**
* Add shortcode `[bp_user_gifts]` to any page
* Or integrate into BuddyPress member profiles
* Users can now view their gift history

**Step 4: Test the System**
* Send a test message with a gift
* Verify gifts appear in conversations
* Check the user dashboard functionality

== Usage Instructions ==

= Sending Gifts =

**As a User:**
1. Go to BuddyPress Messages and compose or reply to a message
2. Click the "Send Gift" button below the message area
3. Browse gifts using search or category filtering
4. Use arrow keys for keyboard navigation
5. Click or press Enter/Space to select a gift
6. Choose between attaching to the message or entire thread
7. Send your message with the attached gift

**Gift Types:**
* **Message Gifts**: Attached to individual messages
* **Thread Gifts**: Attached to entire conversation threads

= Viewing Gifts =

**In Conversations:**
* Message gifts appear within individual messages
* Thread gifts display at the top of conversation threads
* Both types show with appropriate visual indicators

**User Dashboard:**
* Use shortcode `[bp_user_gifts]` on any page
* View received and sent gifts in organized tabs
* Filter by gift type or search through history
* Access detailed statistics and analytics

= Administrative Management =

**Adding Gifts:**
1. Navigate to Gifts > Add New in WordPress admin
2. Add a descriptive title
3. Upload a high-quality featured image (recommended: 200x200px)
4. Add optional description
5. Assign to categories for better organization
6. Publish when ready

**Managing Categories:**
1. Go to Gifts > Gift Categories
2. Create logical groupings (e.g., "Celebrations", "Emotions")
3. Assign colors or icons for visual distinction
4. Organize gifts for easier user selection

**Monitoring Usage:**
* Check user gift statistics through the dashboard
* Monitor popular gifts and engagement patterns
* Use insights to add more relevant gift options

= Customization Options =

**Styling:**
* Override CSS classes in your theme
* Customize colors and layouts
* Add custom animations or effects
* Responsive breakpoints for mobile optimization

**Functionality:**
* Use action hooks to extend functionality
* Filter hooks for customizing behavior
* Template override system for advanced customization
* Service container for adding custom services

== Frequently Asked Questions ==

= How do thread-level gifts differ from message gifts? =

**Message Gifts** are attached to individual messages within a conversation, while **Thread Gifts** are attached to the entire conversation thread. Thread gifts appear at the top of the conversation and represent the overall relationship or thread topic.

= Can users view all their gifts in one place? =

Yes! The new User Gift Dashboard provides a comprehensive view of all received and sent gifts, complete with filtering, statistics, and direct links to conversations. Use the `[bp_user_gifts]` shortcode to display it.

= Is the plugin accessible for users with disabilities? =

Absolutely! Version 2.1 includes full WCAG 2.1 AA compliance with screen reader support, keyboard navigation, proper ARIA labels, focus management, and high contrast mode compatibility.

= Does it support right-to-left (RTL) languages? =

Yes, the plugin includes complete RTL language support with logical CSS properties and culturally appropriate design patterns for Arabic, Hebrew, and other RTL languages.

= Can I customize the gift selection interface? =

Yes, the plugin provides extensive customization options through CSS classes, JavaScript hooks, template overrides, and the service architecture. Developers can modify appearance and behavior using standard WordPress methods.

= How many gifts can I add? =

There's no limit to the number of gifts you can add. The plugin uses intelligent pagination and search functionality to ensure optimal performance even with thousands of gifts.

= Can I import gifts in bulk? =

Yes, since gifts are standard WordPress posts, you can use any WordPress post import tool (like WP All Import) to bulk import gifts with images and categories.

= Does this work with multisite installations? =

Yes, the plugin is fully compatible with WordPress multisite installations and can be network-activated or activated per-site as needed.

= Can I track gift usage analytics? =

Yes, the User Dashboard includes comprehensive statistics showing popular gifts, active users, and engagement patterns. Each user can see their personal gift history and statistics.

= Is the search functionality real-time? =

Yes, the gift search feature provides instant results as users type, with no page refreshes required. It searches through gift names and can be extended to include descriptions.

== Developer Information ==

= Service Architecture =

The plugin uses a modern dependency injection container with the following services:

* **Gift_Service**: Manages gift data and operations
* **Message_Service**: Handles message and thread attachments
* **Modal_Service**: Renders interface components
* **User_Service**: Manages user gift history and statistics

= Available Hooks =

**Actions:**
* `bp_gifts_gift_attached` - After gift attached to message
* `bp_gifts_gift_attached_to_thread` - After gift attached to thread
* `bp_gifts_after_plugin_loaded` - After plugin initialization

**Filters:**
* `bp_gifts_can_user_attach_gift` - Control user permissions
* `bp_gifts_modal_data` - Customize modal content
* `bp_gifts_user_dashboard_args` - Modify dashboard parameters

= Template Override System =

Create `/bp-gifts/` folder in your active theme and copy plugin templates for customization:

* `user-gifts-dashboard.php` - User gift dashboard
* `gift-modal.php` - Gift selection modal
* `message-gift.php` - Message gift display
* `thread-gift.php` - Thread gift display

= Shortcodes =

**[bp_user_gifts]**
Display user gift dashboard

Parameters:
* `user_id` - Specific user ID (optional)

Examples:
```
[bp_user_gifts] // Current user's gifts
[bp_user_gifts user_id="123"] // Specific user's gifts
```

== Screenshots ==

1. **Enhanced Gift Selection Modal** - Real-time search, keyboard navigation, and accessibility features
2. **Thread Gift Display** - Beautiful thread-level gift integration with conversation threads
3. **User Gift Dashboard** - Comprehensive gift history with statistics and filtering
4. **Mobile-Responsive Interface** - Touch-friendly design for all devices
5. **Admin Gift Management** - Streamlined WordPress admin interface for gift creation
6. **RTL Language Support** - Complete right-to-left language compatibility
7. **Accessibility Features** - Screen reader support and keyboard navigation
8. **Gift Statistics** - Personal analytics and engagement tracking

== Changelog ==

= 2.1.0 - October 2025 =
**🎉 Major Feature Release - Enhanced Architecture & User Experience**

**New Features:**
* **Thread-Level Gifts**: Attach gifts to entire conversation threads
* **User Gift Dashboard**: Comprehensive interface for viewing gift history and statistics
* **Service Architecture**: Modern dependency injection container with clean interfaces
* **Enhanced Accessibility**: Full WCAG 2.1 AA compliance with screen reader support
* **RTL Language Support**: Complete right-to-left language compatibility
* **Advanced Search**: Real-time gift search with instant filtering
* **Keyboard Navigation**: Full keyboard accessibility throughout interface
* **Gift Statistics**: Personal analytics and engagement tracking

**Technical Improvements:**
* Service-based architecture with dependency injection container
* Clean separation of concerns with proper interfaces
* Enhanced error handling and logging
* Comprehensive code documentation
* Modern PHP 7.4+ features and syntax
* Improved database query optimization

**User Experience Enhancements:**
* Beautiful responsive design with mobile optimization
* Touch-friendly interface for tablets and phones
* Improved modal interface with better pagination
* Real-time search with instant results
* Category filtering for better gift organization
* Enhanced visual feedback and animations

**Accessibility Features:**
* Screen reader support with proper ARIA labels
* Keyboard navigation for all interactive elements
* Focus management and visual indicators
* High contrast mode compatibility
* Screen reader announcements for actions

**Internationalization:**
* Complete translation readiness
* RTL (right-to-left) language support
* Logical CSS properties for international compatibility
* Cultural sensitivity in design patterns

**Developer Features:**
* Extensive hook system for customization
* Template override system
* Service container for custom extensions
* Comprehensive API documentation
* Modern coding standards compliance

= 2.0.0 - Previous Release =
* Complete code modernization and security improvements
* Updated to WordPress coding standards
* Improved performance with better caching
* Enhanced security with proper data sanitization
* Responsive design improvements
* Better error handling and user feedback
* Modern JavaScript with improved UX
* Comprehensive admin interface improvements
* PHP 7.4+ requirement for better performance
* WordPress 5.0+ requirement for modern features

= 1.0.0 - Initial Release =
* Basic gift sending functionality
* Simple admin interface
* BuddyPress message integration

== Upgrade Notice ==

= 2.1.0 =
🎉 **MAJOR UPDATE**: New thread-level gifts, user dashboard, accessibility features, RTL support, and service architecture! Backup recommended. Requires PHP 7.4+ and WordPress 5.0+.

= 2.0.0 =
Major update with significant security and performance improvements. Requires PHP 7.4+ and WordPress 5.0+. Please backup your site before upgrading.

== Support ==

For support, feature requests, or bug reports:

* **Documentation**: [Plugin Documentation](https://suiteplugins.com/docs/bp-gifts)
* **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/bp-gifts)
* **GitHub Issues**: [Report Technical Issues](https://github.com/suiteplugins/bp-gifts)
* **Contact**: [SuitePlugins Support](https://suiteplugins.com/support)

== Contributing ==

We welcome contributions! Please visit our [GitHub repository](https://github.com/suiteplugins/bp-gifts) to:

* Report bugs or request features
* Submit pull requests
* Improve documentation
* Translate the plugin

== Privacy & GDPR ==

This plugin:
* Does not collect personal data beyond WordPress defaults
* Stores gift attachments as WordPress metadata
* Respects user privacy settings
* Compatible with GDPR requirements
* Provides data export/deletion capabilities through WordPress core functions