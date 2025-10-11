# BP Gifts Plugin - Technical Documentation

## 🏗️ Architecture Overview

### Service-Based Architecture

The BP Gifts plugin uses a modern service-based architecture with dependency injection for clean, maintainable, and testable code.

```
BP_Gifts_Loader_V2 (Main Class)
├── BP_Gifts_Container (DI Container)
├── Services/
│   ├── BP_Gifts_Gift_Service (Gift Management)
│   ├── BP_Gifts_Message_Service (Message/Thread Integration)
│   ├── BP_Gifts_Modal_Service (UI Rendering)
│   └── BP_Gifts_User_Service (User Gift Management)
├── Interfaces/
│   ├── Gift_Service_Interface
│   ├── Message_Service_Interface
│   └── Modal_Service_Interface
└── BP_Gifts_Taxonomy (Gift Categories)
```

### Dependency Injection Container

**Container Registration:**

```php
// Register services with dependencies
$this->container->register( 'gift_service', function( $container ) {
    return new BP_Gifts_Gift_Service();
});

$this->container->register( 'message_service', function( $container ) {
    return new BP_Gifts_Message_Service( $container->get( 'gift_service' ) );
});

$this->container->register( 'user_service', function( $container ) {
    return new BP_Gifts_User_Service( 
        $container->get( 'gift_service' ),
        $container->get( 'message_service' )
    );
});
```

**Service Access:**

```php
// Get service instance
$gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
$user_service = BP_Gifts_Loader_V2::instance()->get_service( 'user_service' );
```

## 🔧 Core Services Documentation

### Gift Service (`BP_Gifts_Gift_Service`)

**Purpose**: Manages gift data, validation, and operations.

**Key Methods:**

```php
interface Gift_Service_Interface {
    // Get gift data with caching
    public function get_gift( int $gift_id ): ?array;
    
    // Get all available gifts with filtering
    public function get_available_gifts( array $args = array() ): array;
    
    // Validate gift exists and is available
    public function is_valid_gift( int $gift_id ): bool;
    
    // Clear gift cache
    public function clear_gift_cache( int $gift_id = 0 ): void;
}
```

**Usage Examples:**

```php
$gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );

// Get single gift
$gift = $gift_service->get_gift( 123 );
if ( $gift ) {
    echo $gift['name']; // Gift title
    echo $gift['image']; // Featured image URL
    echo $gift['description']; // Gift description
}

// Get all gifts with pagination
$gifts = $gift_service->get_available_gifts( array(
    'posts_per_page' => 12,
    'paged' => 1,
    'category' => 'birthday'
));

// Validate gift
if ( $gift_service->is_valid_gift( 123 ) ) {
    // Gift exists and is published
}
```

### Message Service (`BP_Gifts_Message_Service`)

**Purpose**: Handles gift attachment to messages and threads.

**Key Methods:**

```php
interface Message_Service_Interface {
    // Attach gift to individual message
    public function attach_gift_to_message( int $message_id, int $gift_id ): bool;
    
    // Attach gift to entire thread
    public function attach_gift_to_thread( int $thread_id, int $gift_id ): bool;
    
    // Get gift attached to message
    public function get_message_gift( int $message_id ): ?array;
    
    // Get gift attached to thread
    public function get_thread_gift( int $thread_id ): ?array;
    
    // Check user permissions
    public function can_user_attach_gift( int $user_id, int $gift_id ): bool;
    
    // Process form submission
    public function process_gift_from_submission( $message ): bool;
}
```

**Usage Examples:**

```php
$message_service = BP_Gifts_Loader_V2::instance()->get_service( 'message_service' );

// Attach gift to message
$success = $message_service->attach_gift_to_message( 456, 123 );

// Attach gift to thread
$success = $message_service->attach_gift_to_thread( 789, 123 );

// Get message gift
$gift = $message_service->get_message_gift( 456 );

// Get thread gift
$thread_gift = $message_service->get_thread_gift( 789 );

// Check permissions
if ( $message_service->can_user_attach_gift( get_current_user_id(), 123 ) ) {
    // User can attach this gift
}
```

### User Service (`BP_Gifts_User_Service`)

**Purpose**: Manages user gift history, statistics, and dashboard data.

**Key Methods:**

```php
class BP_Gifts_User_Service {
    // Get all gifts received by user
    public function get_received_gifts( int $user_id, array $args = array() ): array;
    
    // Get all gifts sent by user
    public function get_sent_gifts( int $user_id, array $args = array() ): array;
    
    // Get user gift statistics
    public function get_gift_stats( int $user_id ): array;
}
```

**Usage Examples:**

```php
$user_service = BP_Gifts_Loader_V2::instance()->get_service( 'user_service' );

// Get received gifts with filtering
$received = $user_service->get_received_gifts( 123, array(
    'limit' => 20,
    'type' => 'thread', // 'message', 'thread', 'all'
    'order' => 'DESC'
));

// Get sent gifts
$sent = $user_service->get_sent_gifts( 123, array(
    'limit' => 10
));

// Get statistics
$stats = $user_service->get_gift_stats( 123 );
echo $stats['total_received']; // Total received count
echo $stats['total_sent']; // Total sent count
echo $stats['favorite_gift']['gift_data']['name']; // Most received gift
```

### Modal Service (`BP_Gifts_Modal_Service`)

**Purpose**: Renders UI components and templates.

**Key Methods:**

```php
interface Modal_Service_Interface {
    // Render gift selection modal
    public function render_gift_modal(): string;
    
    // Render message gift display
    public function render_message_gift( array $gift ): string;
    
    // Render thread gift display
    public function render_thread_gift( array $gift ): string;
    
    // Render search interface
    public function render_search_interface(): string;
}
```

## 🗄️ Database Schema

### Gift Storage

**Posts Table** (`wp_posts`):
```sql
-- Gifts are stored as custom post type 'bp_gifts'
post_type = 'bp_gifts'
post_status = 'publish'
post_title = 'Gift Name'
post_content = 'Gift Description'
```

**Post Meta** (`wp_postmeta`):
```sql
-- Featured images stored as attachment ID
meta_key = '_thumbnail_id'
meta_value = '123' -- Attachment ID
```

### Gift Attachments

**Message Gifts** (`wp_bp_messages_meta`):
```sql
message_id = 456
meta_key = '_bp_gift_id'
meta_value = '123' -- Gift post ID
```

**Thread Gifts** (`wp_bp_messages_threadmeta`):
```sql
thread_id = 789
meta_key = '_bp_thread_gift'
meta_value = '123' -- Gift post ID
```

### Custom Queries

**Get Popular Gifts:**

```php
function get_popular_gifts( $limit = 10 ) {
    global $wpdb;
    
    $popular = $wpdb->get_results( $wpdb->prepare( "
        SELECT p.ID, p.post_title, COUNT(mm.meta_value) as usage_count
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->prefix}bp_messages_meta mm ON p.ID = mm.meta_value
        WHERE p.post_type = 'bp_gifts'
        AND p.post_status = 'publish'
        AND mm.meta_key = '_bp_gift_id'
        GROUP BY p.ID
        ORDER BY usage_count DESC
        LIMIT %d
    ", $limit ) );
    
    return $popular;
}
```

**Get User Gift Count:**

```php
function get_user_gift_count( $user_id, $type = 'received' ) {
    global $wpdb;
    
    if ( $type === 'received' ) {
        $count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(DISTINCT mm.message_id)
            FROM {$wpdb->prefix}bp_messages_messages m
            JOIN {$wpdb->prefix}bp_messages_recipients r ON m.thread_id = r.thread_id
            JOIN {$wpdb->prefix}bp_messages_meta mm ON m.id = mm.message_id
            WHERE r.user_id = %d
            AND mm.meta_key = '_bp_gift_id'
        ", $user_id ) );
    } else {
        $count = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}bp_messages_messages m
            JOIN {$wpdb->prefix}bp_messages_meta mm ON m.id = mm.message_id
            WHERE m.sender_id = %d
            AND mm.meta_key = '_bp_gift_id'
        ", $user_id ) );
    }
    
    return absint( $count );
}
```

## 🎣 Hooks & Filters

### Action Hooks

**Gift Attachment Hooks:**

```php
// Fired after gift attached to message
do_action( 'bp_gifts_gift_attached', $message_id, $gift_id, $result );

// Fired after gift attached to thread
do_action( 'bp_gifts_gift_attached_to_thread', $thread_id, $gift_id, $result );

// Fired after plugin fully loaded
do_action( 'bp_gifts_after_plugin_loaded' );
```

**Usage Examples:**

```php
// Send notification when gift attached
add_action( 'bp_gifts_gift_attached', 'send_gift_notification', 10, 3 );
function send_gift_notification( $message_id, $gift_id, $result ) {
    if ( $result ) {
        // Get message details
        $message = new BP_Messages_Message( $message_id );
        $gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
        $gift = $gift_service->get_gift( $gift_id );
        
        // Send custom notification
        bp_notifications_add_notification( array(
            'user_id'          => $message->recipients[0]->user_id,
            'item_id'          => $gift_id,
            'secondary_item_id' => $message_id,
            'component_name'   => 'bp_gifts',
            'component_action' => 'gift_received'
        ));
    }
}

// Analytics tracking
add_action( 'bp_gifts_gift_attached_to_thread', 'track_thread_gift', 10, 3 );
function track_thread_gift( $thread_id, $gift_id, $result ) {
    if ( $result ) {
        // Custom analytics tracking
        update_option( 'bp_gifts_thread_count', get_option( 'bp_gifts_thread_count', 0 ) + 1 );
    }
}
```

### Filter Hooks

**Permission Filters:**

```php
// Filter user permissions for gift attachment
apply_filters( 'bp_gifts_can_user_attach_gift', $can_attach, $user_id, $gift_id );

// Filter gift availability
apply_filters( 'bp_gifts_is_gift_available', $is_available, $gift_id );

// Filter dashboard arguments
apply_filters( 'bp_gifts_user_dashboard_args', $args );
```

**Data Filters:**

```php
// Filter gift data before display
apply_filters( 'bp_gifts_gift_data', $gift_data, $gift_id );

// Filter modal content
apply_filters( 'bp_gifts_modal_data', $modal_data );

// Filter search results
apply_filters( 'bp_gifts_search_results', $gifts, $search_term );
```

**Usage Examples:**

```php
// Custom permission logic
add_filter( 'bp_gifts_can_user_attach_gift', 'custom_gift_permissions', 10, 3 );
function custom_gift_permissions( $can_attach, $user_id, $gift_id ) {
    // Only premium members can send expensive gifts
    $gift_price = get_post_meta( $gift_id, '_gift_price', true );
    if ( $gift_price > 100 && ! user_has_premium_membership( $user_id ) ) {
        return false;
    }
    return $can_attach;
}

// Add custom gift data
add_filter( 'bp_gifts_gift_data', 'add_custom_gift_data', 10, 2 );
function add_custom_gift_data( $gift_data, $gift_id ) {
    $gift_data['price'] = get_post_meta( $gift_id, '_gift_price', true );
    $gift_data['rarity'] = get_post_meta( $gift_id, '_gift_rarity', true );
    return $gift_data;
}

// Modify dashboard display
add_filter( 'bp_gifts_user_dashboard_args', 'custom_dashboard_args' );
function custom_dashboard_args( $args ) {
    $args['limit'] = 50; // Show more gifts per page
    $args['order'] = 'ASC'; // Change sort order
    return $args;
}
```

## 🎨 Frontend Integration

### JavaScript API

**Core JavaScript Object:**

```javascript
// Main BP Gifts JavaScript object
window.BPGifts = {
    // Gift selection handling
    selectGift: function(giftId, giftData) {
        // Custom gift selection logic
    },
    
    // Search functionality
    searchGifts: function(searchTerm) {
        // Custom search implementation
    },
    
    // Modal management
    openModal: function() {
        // Custom modal opening
    },
    
    closeModal: function() {
        // Custom modal closing
    }
};
```

**Custom Events:**

```javascript
// Listen for gift selection
$(document).on('bp-gift-selected', function(event, giftData) {
    console.log('Gift selected:', giftData);
    // Custom handling
});

// Listen for modal events
$(document).on('bp-modal-opened', function() {
    // Modal opened
});

$(document).on('bp-modal-closed', function() {
    // Modal closed
});

// Search events
$(document).on('bp-gifts-search', function(event, searchTerm, results) {
    console.log('Search performed:', searchTerm, results);
});
```

**Extending Functionality:**

```javascript
// Add custom gift filtering
function addPriceFilter() {
    var $priceFilter = $('<select id="bp-gifts-price-filter">')
        .append('<option value="">All Prices</option>')
        .append('<option value="low">Under $10</option>')
        .append('<option value="high">$10+</option>');
    
    $('.bp-gifts-filter-controls').append($priceFilter);
    
    $priceFilter.on('change', function() {
        var priceRange = $(this).val();
        filterGiftsByPrice(priceRange);
    });
}

// Custom gift validation
function validateGiftSelection(giftId) {
    // Custom validation logic
    var userCredits = getUserCredits();
    var giftPrice = getGiftPrice(giftId);
    
    if (userCredits < giftPrice) {
        alert('Insufficient credits for this gift');
        return false;
    }
    
    return true;
}
```

### CSS Architecture

**CSS Class Structure:**

```css
/* Main container classes */
.bp-gifts-user-dashboard {}
.bp-gifts-modal {}
.bp-gifts-grid {}

/* Component classes */
.bp-gift-card {}
.bp-gift-thread-display {}
.bp-gift-type-badge {}

/* State classes */
.bp-gift-selected {}
.bp-gift-disabled {}
.bp-gift-loading {}

/* Accessibility classes */
.screen-reader-text {}
.bp-gifts-focus-trap {}

/* RTL classes */
[dir="rtl"] .bp-gifts-modal {}
[dir="rtl"] .bp-gifts-grid {}
```

**Custom Styling Examples:**

```css
/* Theme integration */
.my-theme .bp-gifts-modal {
    border-radius: var(--theme-border-radius);
    box-shadow: var(--theme-shadow);
    background: var(--theme-background);
}

/* Custom gift cards */
.bp-gift-card.premium {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    border: 2px solid #d4af37;
}

.bp-gift-card.rare {
    background: linear-gradient(135deg, #9f7aea, #ed64a6);
    animation: glow 2s ease-in-out infinite alternate;
}

@keyframes glow {
    from { box-shadow: 0 0 20px rgba(159, 122, 234, 0.5); }
    to { box-shadow: 0 0 30px rgba(159, 122, 234, 0.8); }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .bp-gifts-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .bp-gift-card-title {
        font-size: 14px;
    }
}
```

## 🔌 REST API Integration

### Custom Endpoints

**Gift Data Endpoint:**

```php
// Register custom REST endpoint
add_action( 'rest_api_init', 'register_bp_gifts_endpoints' );
function register_bp_gifts_endpoints() {
    register_rest_route( 'bp-gifts/v1', '/gifts', array(
        'methods' => 'GET',
        'callback' => 'get_gifts_api',
        'permission_callback' => '__return_true'
    ));
    
    register_rest_route( 'bp-gifts/v1', '/user/(?P<id>\d+)/gifts', array(
        'methods' => 'GET',
        'callback' => 'get_user_gifts_api',
        'permission_callback' => 'bp_rest_members_permissions_check'
    ));
}

function get_gifts_api( $request ) {
    $gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
    
    $gifts = $gift_service->get_available_gifts( array(
        'posts_per_page' => $request->get_param( 'per_page' ) ?: 12,
        'paged' => $request->get_param( 'page' ) ?: 1
    ));
    
    return rest_ensure_response( $gifts );
}

function get_user_gifts_api( $request ) {
    $user_id = $request->get_param( 'id' );
    $user_service = BP_Gifts_Loader_V2::instance()->get_service( 'user_service' );
    
    $gifts = $user_service->get_received_gifts( $user_id, array(
        'limit' => $request->get_param( 'per_page' ) ?: 20
    ));
    
    return rest_ensure_response( $gifts );
}
```

**AJAX Endpoints:**

```php
// Gift search AJAX
add_action( 'wp_ajax_bp_gifts_search', 'handle_gift_search_ajax' );
add_action( 'wp_ajax_nopriv_bp_gifts_search', 'handle_gift_search_ajax' );

function handle_gift_search_ajax() {
    check_ajax_referer( 'bp_gifts_nonce', 'nonce' );
    
    $search_term = sanitize_text_field( $_POST['search'] );
    $category = sanitize_text_field( $_POST['category'] );
    
    $gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
    
    $gifts = $gift_service->get_available_gifts( array(
        's' => $search_term,
        'tax_query' => array(
            array(
                'taxonomy' => 'gift_category',
                'field' => 'slug',
                'terms' => $category
            )
        )
    ));
    
    wp_send_json_success( $gifts );
}
```

**Frontend AJAX Usage:**

```javascript
// Search gifts via AJAX
function searchGiftsAjax(searchTerm, category) {
    $.ajax({
        url: bp_gifts_vars.ajax_url,
        type: 'POST',
        data: {
            action: 'bp_gifts_search',
            search: searchTerm,
            category: category,
            nonce: bp_gifts_vars.nonce
        },
        success: function(response) {
            if (response.success) {
                updateGiftGrid(response.data);
            }
        },
        error: function(xhr, status, error) {
            console.error('Gift search failed:', error);
        }
    });
}

// Load user gifts
function loadUserGifts(userId, page) {
    $.get('/wp-json/bp-gifts/v1/user/' + userId + '/gifts', {
        page: page,
        per_page: 20
    })
    .done(function(data) {
        renderUserGifts(data);
    })
    .fail(function(xhr, status, error) {
        console.error('Failed to load user gifts:', error);
    });
}
```

## 🧪 Testing & Debugging

### Unit Testing

**Service Testing:**

```php
// Test gift service
class BP_Gifts_Gift_Service_Test extends WP_UnitTestCase {
    
    private $gift_service;
    
    public function setUp(): void {
        parent::setUp();
        $this->gift_service = new BP_Gifts_Gift_Service();
    }
    
    public function test_get_gift_returns_null_for_invalid_id() {
        $result = $this->gift_service->get_gift( 999999 );
        $this->assertNull( $result );
    }
    
    public function test_get_gift_returns_data_for_valid_gift() {
        // Create test gift
        $gift_id = $this->factory->post->create( array(
            'post_type' => 'bp_gifts',
            'post_status' => 'publish'
        ));
        
        $result = $this->gift_service->get_gift( $gift_id );
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'id', $result );
        $this->assertEquals( $gift_id, $result['id'] );
    }
    
    public function test_is_valid_gift_validation() {
        // Test invalid gift
        $this->assertFalse( $this->gift_service->is_valid_gift( 999999 ) );
        
        // Test valid gift
        $gift_id = $this->factory->post->create( array(
            'post_type' => 'bp_gifts',
            'post_status' => 'publish'
        ));
        
        $this->assertTrue( $this->gift_service->is_valid_gift( $gift_id ) );
    }
}
```

**Integration Testing:**

```php
// Test message service integration
class BP_Gifts_Message_Service_Test extends WP_UnitTestCase {
    
    private $message_service;
    private $gift_service;
    
    public function setUp(): void {
        parent::setUp();
        $this->gift_service = new BP_Gifts_Gift_Service();
        $this->message_service = new BP_Gifts_Message_Service( $this->gift_service );
    }
    
    public function test_attach_gift_to_message() {
        // Create test gift and message
        $gift_id = $this->factory->post->create( array(
            'post_type' => 'bp_gifts',
            'post_status' => 'publish'
        ));
        
        $message_id = $this->factory->post->create();
        
        $result = $this->message_service->attach_gift_to_message( $message_id, $gift_id );
        $this->assertTrue( $result );
        
        // Verify attachment
        $attached_gift = $this->message_service->get_message_gift( $message_id );
        $this->assertIsArray( $attached_gift );
        $this->assertEquals( $gift_id, $attached_gift['id'] );
    }
}
```

### JavaScript Testing

**Jest Test Example:**

```javascript
// test/bp-gifts.test.js
describe('BP Gifts JavaScript', function() {
    
    beforeEach(function() {
        // Setup DOM
        document.body.innerHTML = `
            <div id="bp-gifts-modal"></div>
            <div class="bp-gifts-grid"></div>
        `;
        
        // Initialize
        BPGifts.init();
    });
    
    test('Gift selection updates interface', function() {
        const giftData = {
            id: 123,
            name: 'Test Gift',
            image: 'test.jpg'
        };
        
        BPGifts.selectGift(123, giftData);
        
        const selectedGift = document.querySelector('.bp-gift-selected');
        expect(selectedGift).toBeTruthy();
        expect(selectedGift.dataset.id).toBe('123');
    });
    
    test('Search filters gifts correctly', function() {
        // Mock gifts
        const gifts = [
            { id: 1, name: 'Birthday Cake' },
            { id: 2, name: 'Red Rose' },
            { id: 3, name: 'Chocolate Box' }
        ];
        
        BPGifts.setGifts(gifts);
        BPGifts.searchGifts('cake');
        
        const visibleGifts = document.querySelectorAll('.bp-gift-item:not(.hidden)');
        expect(visibleGifts.length).toBe(1);
        expect(visibleGifts[0].textContent).toContain('Birthday Cake');
    });
});
```

### Debug Tools

**Debug Functions:**

```php
// Debug gift data
function debug_gift_data( $gift_id ) {
    $gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
    $gift = $gift_service->get_gift( $gift_id );
    
    echo '<pre>';
    print_r( $gift );
    echo '</pre>';
}

// Debug user gifts
function debug_user_gifts( $user_id ) {
    $user_service = BP_Gifts_Loader_V2::instance()->get_service( 'user_service' );
    $received = $user_service->get_received_gifts( $user_id );
    $sent = $user_service->get_sent_gifts( $user_id );
    
    echo '<h3>Received Gifts:</h3><pre>';
    print_r( $received );
    echo '</pre>';
    
    echo '<h3>Sent Gifts:</h3><pre>';
    print_r( $sent );
    echo '</pre>';
}

// Debug container services
function debug_container_services() {
    $container = BP_Gifts_Loader_V2::instance()->get_container();
    
    echo '<h3>Registered Services:</h3>';
    echo '<ul>';
    foreach ( array( 'gift_service', 'message_service', 'modal_service', 'user_service' ) as $service ) {
        $status = $container->has( $service ) ? 'Registered' : 'Missing';
        echo "<li>{$service}: {$status}</li>";
    }
    echo '</ul>';
}
```

**Performance Profiling:**

```php
// Profile gift loading
function profile_gift_loading() {
    $start_time = microtime( true );
    
    $gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
    $gifts = $gift_service->get_available_gifts( array( 'posts_per_page' => 50 ) );
    
    $end_time = microtime( true );
    $execution_time = $end_time - $start_time;
    
    echo "Loaded " . count( $gifts ) . " gifts in " . number_format( $execution_time, 4 ) . " seconds";
}

// Profile database queries
function profile_database_queries() {
    global $wpdb;
    
    $wpdb->show_errors();
    $wpdb->queries = array();
    
    // Execute your code here
    $user_service = BP_Gifts_Loader_V2::instance()->get_service( 'user_service' );
    $gifts = $user_service->get_received_gifts( 1 );
    
    echo '<h3>Database Queries (' . count( $wpdb->queries ) . '):</h3>';
    foreach ( $wpdb->queries as $query ) {
        echo '<pre>' . $query[0] . ' (' . $query[1] . 's)</pre>';
    }
}
```

## 🚀 Performance Optimization

### Caching Strategy

**Gift Data Caching:**

```php
// Implement custom caching
class BP_Gifts_Cache {
    
    const CACHE_GROUP = 'bp_gifts';
    const CACHE_EXPIRATION = HOUR_IN_SECONDS * 6;
    
    public static function get_gift( $gift_id ) {
        $cache_key = "gift_data_{$gift_id}";
        $cached = wp_cache_get( $cache_key, self::CACHE_GROUP );
        
        if ( false === $cached ) {
            $gift_service = BP_Gifts_Loader_V2::instance()->get_service( 'gift_service' );
            $cached = $gift_service->get_gift( $gift_id );
            wp_cache_set( $cache_key, $cached, self::CACHE_GROUP, self::CACHE_EXPIRATION );
        }
        
        return $cached;
    }
    
    public static function clear_gift( $gift_id ) {
        $cache_key = "gift_data_{$gift_id}";
        wp_cache_delete( $cache_key, self::CACHE_GROUP );
    }
    
    public static function clear_all() {
        wp_cache_flush_group( self::CACHE_GROUP );
    }
}
```

**Query Optimization:**

```php
// Optimize gift queries
function optimize_gift_queries() {
    // Use meta_query for better performance
    $args = array(
        'post_type' => 'bp_gifts',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            )
        ),
        'fields' => 'ids' // Only get IDs first
    );
    
    $gift_ids = get_posts( $args );
    
    // Then get full data only for needed gifts
    $gifts = array();
    foreach ( $gift_ids as $gift_id ) {
        $gifts[] = BP_Gifts_Cache::get_gift( $gift_id );
    }
    
    return $gifts;
}
```

### Database Optimization

**Index Creation:**

```sql
-- Add indexes for better performance
ALTER TABLE wp_bp_messages_meta 
ADD INDEX bp_gifts_meta_key_value (meta_key, meta_value);

ALTER TABLE wp_bp_messages_threadmeta 
ADD INDEX bp_gifts_thread_meta (meta_key, meta_value);

-- Composite index for user gift queries
ALTER TABLE wp_bp_messages_recipients 
ADD INDEX bp_gifts_user_thread (user_id, thread_id, is_deleted);
```

**Query Optimization:**

```php
// Optimized user gifts query
function get_user_gifts_optimized( $user_id, $limit = 20 ) {
    global $wpdb;
    
    // Use prepared statements and proper indexes
    $sql = $wpdb->prepare( "
        SELECT DISTINCT
            m.id as message_id,
            m.thread_id,
            m.sender_id,
            m.date_sent,
            mm.meta_value as gift_id,
            p.post_title as gift_name
        FROM {$wpdb->prefix}bp_messages_messages m
        FORCE INDEX (PRIMARY)
        INNER JOIN {$wpdb->prefix}bp_messages_recipients r 
            ON m.thread_id = r.thread_id AND r.user_id = %d AND r.is_deleted = 0
        INNER JOIN {$wpdb->prefix}bp_messages_meta mm 
            ON m.id = mm.message_id AND mm.meta_key = '_bp_gift_id'
        INNER JOIN {$wpdb->posts} p 
            ON mm.meta_value = p.ID AND p.post_status = 'publish'
        ORDER BY m.date_sent DESC
        LIMIT %d
    ", $user_id, $limit );
    
    return $wpdb->get_results( $sql, ARRAY_A );
}
```

---

*This technical documentation provides a comprehensive overview of the BP Gifts plugin architecture, APIs, and development patterns. For additional technical support or custom development needs, please contact the plugin developers.*