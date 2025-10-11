# BP Gifts Plugin - Complete Installation & Usage Guide

## 🚀 Quick Start Guide

### Prerequisites
- WordPress 5.0+
- BuddyPress 8.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+

### Installation Steps

1. **Install the Plugin**
   ```bash
   # Via WordPress Admin
   Plugins > Add New > Search "Gifts for BuddyPress" > Install > Activate
   
   # Or Upload manually
   Upload zip file via Plugins > Add New > Upload Plugin
   ```

2. **Verify BuddyPress Integration**
   - Ensure BuddyPress is active
   - Check that Messages component is enabled in BuddyPress settings

3. **Add Your First Gifts**
   - Go to WordPress Admin > Gifts > Add New
   - Upload images (recommended: 200x200px)
   - Add descriptive titles
   - Publish when ready

## 📋 Detailed Setup Instructions

### Step 1: Basic Configuration

**Add Gifts:**
```
WordPress Admin > Gifts > Add New
├── Title: "Birthday Cake" 
├── Featured Image: Upload 200x200px image
├── Description: Optional description
├── Categories: Assign to category (optional)
└── Publish
```

**Create Categories (Optional):**
```
WordPress Admin > Gifts > Gift Categories
├── Add categories like "Birthday", "Love", "Friendship"
├── Assign colors or descriptions
└── Organize gifts for better user experience
```

### Step 2: User Interface Integration

**Display User Gift Dashboard:**

**Option 1: Shortcode**
```php
// Basic usage
[bp_user_gifts]

// For specific user
[bp_user_gifts user_id="123"]
```

**Option 2: Template Integration**
```php
// In your theme files
<?php
if ( function_exists( 'BP_Gifts_Loader_V2' ) ) {
    echo BP_Gifts_Loader_V2::instance()->render_user_gifts_dashboard();
}
?>
```

**Option 3: BuddyPress Profile Tab**
```php
// Add to member profile (functions.php)
function add_gifts_tab() {
    bp_core_new_nav_item( array(
        'name'            => __( 'My Gifts', 'bp-gifts' ),
        'slug'            => 'gifts',
        'screen_function' => 'display_gifts_page',
        'position'        => 30
    ));
}
add_action( 'bp_setup_nav', 'add_gifts_tab' );

function display_gifts_page() {
    add_action( 'bp_template_content', function() {
        echo do_shortcode( '[bp_user_gifts]' );
    });
    bp_core_load_template( 'members/single/plugins' );
}
```

### Step 3: Test the System

1. **Send a Test Gift:**
   - Go to BuddyPress Messages
   - Compose a new message
   - Click "Send Gift" button
   - Select a gift and send

2. **Verify Display:**
   - Check gift appears in message
   - Test user dashboard functionality
   - Verify accessibility features work

## 🎯 Usage Instructions

### For End Users

**Sending Gifts:**

1. **Navigate to Messages**
   - Go to BuddyPress Messages section
   - Start new conversation or reply to existing

2. **Select Gift**
   - Click "Send Gift" button below message area
   - Use search box to find specific gifts
   - Filter by category if available
   - Navigate with arrow keys (accessibility)

3. **Choose Attachment Type**
   - **Message Gift**: Attached to this specific message
   - **Thread Gift**: Attached to entire conversation

4. **Send Message**
   - Complete your message text
   - Click Send to deliver message with gift

**Viewing Gift History:**

1. **Access Dashboard**
   - Visit page with `[bp_user_gifts]` shortcode
   - Or access through profile navigation

2. **Navigate Interface**
   - **Received Tab**: All gifts you've received
   - **Sent Tab**: All gifts you've sent
   - **Filter Options**: All/Message/Thread gifts

3. **View Details**
   - See sender/recipient information
   - Check dates and gift types
   - Click "View Conversation" to return to thread

### For Administrators

**Managing Gifts:**

1. **Add New Gifts**
   ```
   WordPress Admin > Gifts > Add New
   ├── Title: Descriptive name
   ├── Featured Image: High-quality image
   ├── Content: Optional description
   ├── Categories: Logical grouping
   └── Publish Status: Published/Draft
   ```

2. **Organize Categories**
   ```
   WordPress Admin > Gifts > Gift Categories
   ├── Create meaningful categories
   ├── Add descriptions
   └── Assign gifts appropriately
   ```

3. **Monitor Usage**
   - Check individual user dashboards
   - Monitor popular gifts
   - Analyze engagement patterns

**Bulk Import Gifts:**

1. **Prepare CSV File**
   ```csv
   post_title,post_content,post_status,_thumbnail_id
   "Birthday Cake","Celebrate birthdays",publish,123
   "Red Rose","Express love",publish,124
   ```

2. **Use Import Plugin**
   - Install WP All Import or similar
   - Map CSV fields to post fields
   - Set post type to 'bp_gifts'
   - Import with featured images

## 🛠️ Advanced Customization

### Theme Integration

**Override Templates:**

1. **Create Theme Directory**
   ```
   /your-theme/bp-gifts/
   ├── user-gifts-dashboard.php
   ├── gift-modal.php
   ├── message-gift.php
   └── thread-gift.php
   ```

2. **Copy Plugin Templates**
   ```bash
   # Copy from plugin
   /wp-content/plugins/bp-gifts/templates/
   
   # To your theme
   /your-theme/bp-gifts/
   ```

3. **Customize as Needed**
   - Modify HTML structure
   - Add custom styling
   - Integrate with theme design

**Custom CSS:**

```css
/* Gift Modal Customization */
.easy-modal {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Gift Card Styling */
.bp-gift-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Dashboard Customization */
.bp-gifts-user-dashboard {
    font-family: 'Your Custom Font', sans-serif;
}

/* RTL Support */
[dir="rtl"] .bp-gifts-grid {
    direction: rtl;
}
```

### JavaScript Customization

**Extend Modal Behavior:**

```javascript
jQuery(document).ready(function($) {
    // Custom gift selection handler
    $(document).on('bp-gift-selected', function(event, giftData) {
        console.log('Gift selected:', giftData);
        // Add custom analytics or behavior
    });
    
    // Custom search behavior
    $('#bp-gifts-search').on('custom-search', function() {
        // Add custom search logic
    });
});
```

**Add Custom Filters:**

```javascript
// Filter gifts by custom criteria
function filterGiftsByPrice(maxPrice) {
    $('.bp-gift-item-ele').each(function() {
        var price = $(this).data('price');
        if (price > maxPrice) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
}
```

### PHP Hooks & Filters

**Action Hooks:**

```php
// After gift is attached to message
add_action( 'bp_gifts_gift_attached', 'my_gift_attached_handler', 10, 3 );
function my_gift_attached_handler( $message_id, $gift_id, $result ) {
    // Custom logic after gift attachment
    error_log( "Gift {$gift_id} attached to message {$message_id}" );
}

// After gift attached to thread
add_action( 'bp_gifts_gift_attached_to_thread', 'my_thread_gift_handler', 10, 3 );
function my_thread_gift_handler( $thread_id, $gift_id, $result ) {
    // Custom logic for thread gifts
    do_action( 'my_custom_thread_gift_notification', $thread_id, $gift_id );
}
```

**Filter Hooks:**

```php
// Customize user permissions
add_filter( 'bp_gifts_can_user_attach_gift', 'my_gift_permissions', 10, 3 );
function my_gift_permissions( $can_attach, $user_id, $gift_id ) {
    // Custom permission logic
    if ( user_has_premium_membership( $user_id ) ) {
        return true;
    }
    return $can_attach;
}

// Modify dashboard arguments
add_filter( 'bp_gifts_user_dashboard_args', 'my_dashboard_args', 10, 1 );
function my_dashboard_args( $args ) {
    $args['limit'] = 50; // Show more gifts per page
    return $args;
}
```

### Database Customization

**Custom Gift Meta:**

```php
// Add custom gift metadata
add_action( 'save_post', 'save_custom_gift_meta', 10, 2 );
function save_custom_gift_meta( $post_id, $post ) {
    if ( $post->post_type === 'bp_gifts' ) {
        if ( isset( $_POST['gift_price'] ) ) {
            update_post_meta( $post_id, '_gift_price', sanitize_text_field( $_POST['gift_price'] ) );
        }
    }
}

// Retrieve custom meta in templates
$gift_price = get_post_meta( $gift_id, '_gift_price', true );
```

**Custom Database Queries:**

```php
// Get popular gifts
function get_popular_gifts( $limit = 10 ) {
    global $wpdb;
    
    $popular_gifts = $wpdb->get_results( $wpdb->prepare( "
        SELECT p.ID, p.post_title, COUNT(mm.meta_value) as gift_count
        FROM {$wpdb->posts} p
        JOIN {$wpdb->prefix}bp_messages_meta mm ON p.ID = mm.meta_value
        WHERE p.post_type = 'bp_gifts'
        AND mm.meta_key = '_bp_gift_id'
        GROUP BY p.ID
        ORDER BY gift_count DESC
        LIMIT %d
    ", $limit ) );
    
    return $popular_gifts;
}
```

## 🔧 Troubleshooting

### Common Issues

**Gifts Not Appearing:**

1. **Check BuddyPress Messages**
   - Verify Messages component is active
   - Check message permissions

2. **Verify Gift Status**
   - Ensure gifts are published
   - Check featured images are set

3. **Clear Cache**
   - Clear object cache if using caching plugins
   - Refresh WordPress transients

**Modal Not Opening:**

1. **JavaScript Conflicts**
   - Check browser console for errors
   - Disable other plugins temporarily
   - Test with default theme

2. **jQuery Issues**
   - Verify jQuery is loaded
   - Check for jQuery conflicts

3. **CSS Conflicts**
   - Inspect element for styling issues
   - Check z-index values

**Dashboard Not Displaying:**

1. **Shortcode Issues**
   - Verify shortcode syntax
   - Check user permissions
   - Test on different pages

2. **Template Problems**
   - Check template override conflicts
   - Verify file permissions
   - Test with default templates

### Debug Mode

**Enable WordPress Debug:**

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

**Check Error Logs:**
```bash
# Common log locations
/wp-content/debug.log
/wp-content/plugins/bp-gifts/logs/
/var/log/apache2/error.log
```

**Plugin-Specific Debugging:**

```php
// Add to functions.php for detailed logging
add_action( 'bp_gifts_gift_attached', function( $message_id, $gift_id, $result ) {
    error_log( "BP Gifts Debug: Gift {$gift_id} attached to message {$message_id} with result: " . var_export( $result, true ) );
}, 10, 3 );
```

### Performance Optimization

**Database Optimization:**

```sql
-- Add indexes for better performance
ALTER TABLE wp_bp_messages_meta ADD INDEX bp_gifts_idx (meta_key, meta_value);
```

**Caching Configuration:**

```php
// Extend cache times for gift data
add_filter( 'bp_gifts_cache_expiration', function( $expiration ) {
    return HOUR_IN_SECONDS * 6; // 6 hours instead of default
});
```

**Image Optimization:**

```php
// Optimize gift images
add_filter( 'wp_get_attachment_image_attributes', function( $attr, $attachment, $size ) {
    if ( $size === 'bp-gift-thumbnail' ) {
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
    }
    return $attr;
}, 10, 3 );
```

## 🌐 Internationalization

### Translation Setup

**Create Translation Files:**

1. **Generate POT File**
   ```bash
   # Using WP-CLI
   wp i18n make-pot . languages/bp-gifts.pot
   ```

2. **Create Language Files**
   ```
   /wp-content/plugins/bp-gifts/languages/
   ├── bp-gifts-es_ES.po
   ├── bp-gifts-es_ES.mo
   ├── bp-gifts-fr_FR.po
   └── bp-gifts-fr_FR.mo
   ```

**RTL Language Support:**

```css
/* Automatic RTL support included */
[dir="rtl"] .bp-gifts-modal {
    direction: rtl;
}

[dir="rtl"] .bp-gift-grid {
    direction: rtl;
}
```

### Translation Strings

**Key Translatable Strings:**

- Gift selection interface
- User dashboard labels
- Error messages
- Success notifications
- Accessibility labels
- Admin interface text

## 📞 Support & Resources

### Getting Help

1. **Documentation**: Read this guide thoroughly
2. **WordPress.org Support**: Post in plugin support forum
3. **GitHub Issues**: Report bugs or feature requests
4. **Contact Developer**: For custom development needs

### Useful Resources

- **BuddyPress Codex**: https://buddypress.org/support/
- **WordPress Accessibility**: https://make.wordpress.org/accessibility/
- **RTL CSS Guidelines**: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Logical_Properties
- **WCAG Guidelines**: https://www.w3.org/WAI/WCAG21/quickref/

### Contributing

**Ways to Contribute:**

1. **Report Bugs**: Use GitHub issues
2. **Suggest Features**: Community feedback welcome
3. **Submit Code**: Pull requests accepted
4. **Translate**: Help with internationalization
5. **Documentation**: Improve guides and tutorials

---

*This guide covers the complete installation, configuration, and customization of the BP Gifts plugin. For additional support or custom development needs, please contact the plugin developers.*