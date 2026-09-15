# WP Baseline

WP Baseline is a Composer package that provides baseline functionality for WordPress. Some of the functionality includes:

- Cleanup of unnecessary WordPress features
- Enhanced security measures
- SVG, JSON, and Lottie upload support, admin-only by default
- Cleanup of the admin dashboard
- Duplicate post/page functionality, respecting per-post edit permissions

## Requirements

- PHP >= 8
- WordPress >= 6

## Installation & Usage

This library is meant to be dropped into a theme or plugin via composer.

1. In your WordPress project directory, run: `composer require builtnorth/wp-baseline`.
2. In your main plugin file or theme's functions.php, add:

```php
if (class_exists('BuiltNorth\WPBaseline\App')) {
    $baseline = BuiltNorth\WPBaseline\App::instance();
    $baseline->boot();
}
```

## Features

### Disable Comments

Comments remain enabled by default. To disable them, set this filter to return true:

```php
add_filter('wpbaseline_disable_comments', '__return_true');
```

When comments are disabled, WP Baseline comprehensively removes all comment functionality:

**Backend Changes:**

- Removes comment support from all post types
- Closes comments on all existing posts
- Removes Comments menu from admin
- Removes Discussion settings page
- Removes comment widgets from dashboard
- Redirects comment admin pages to dashboard
- Removes comment link from admin bar
- Disables comment REST API endpoints
- Disables block editor notes (prevents `/wp/v2/comments?type=note` requests when the REST route is removed)

**Frontend Changes:**

- Disables comment feeds
- Removes Recent Comments widget
- Dequeues comment reply scripts
- Removes discussion panel from block editor

**Block Editor:**

- Removes all comment-related blocks including:
    - Comment templates and content
    - Comment forms and reply links
    - Comment pagination
    - Latest comments
    - Post comment counts and links

### Howdy Text

By default the "Howdy" text is removed from the admin bar. You can customize this and add your own text using the following filter:

```php
add_filter('wpbaseline_howdy_text', function ($text) {
    return 'Hey,';
});
```

### Admin Bar

By default the WP logo, search, and updates nodes are removed from the admin bar. They can be re-enabled using the following filter:

```php
add_filter('wpbaseline_clean_admin_bar', '__return_false');
```

### Dashboard Widgets

Most core dashboard widgets are removed. They can be re-enabled using the following filter:

```php
add_filter('wpbaseline_remove_dashboard_widgets', '__return_false');
```

### Emojis

Emojis are disabled. They can be re-enabled using the following filter:

```php
add_filter('wpbaseline_disable_emojis', '__return_false');
```

### Auto Update Emails

Auto update emails are disabled. Additionally, the from name in the email is customized based on the site name. This functionality can be reverted back to the default by using the filter:

```php
add_filter('wpbaseline_disable_update_emails', '__return_false');
```

### Asset Version Numbering

Wordpress adds a version query argument to all enqueued assets by default. This exposes the version number, which can be a security risk. WP Baseline replaces the version number with `filemtime` of the theme's style.css file by default with a fallback to `date('Ymd')`. However, you can set a custom version by defining a constant in your theme or plugin:

```php
define('YOUR_THEME_VERSION', '1.2.9');
add_filter('wpbaseline_asset_version_constant', function () {
    return 'YOUR_THEME_VERSION';
});
```

### Security Headers

WP Baseline implements security headers by default for enhanced security. These include:

- Content Security Policy (CSP)
- X-Content-Type-Options
- X-Frame-Options
- And more...

To disable all security headers:

```php
add_filter('wpbaseline_enable_security_headers', '__return_false');
```

To modify specific headers or CSP rules, use these filters:

```php
// Modify security headers
add_filter('wpbaseline_security_headers', function($headers) {
    // Customize headers
    $headers['X-Frame-Options'] = 'DENY';
    return $headers;
});
```

### Login Security

When enabled (default), login security includes:

- Require email address for sign-in (username attempts are rejected)
- Update the login field label and placeholder to "Email address"
- Return a generic login error message for credential failures (username-format attempts show a specific message)
- Always clear the login field after a failed sign-in attempt
- Disable browser autocomplete on login fields

WordPress normally repopulates the login field when the submitted email exists but the password is wrong, while clearing it for unknown emails. That behavior leaks account existence even with a generic error message. WP Baseline remaps those core error codes so the field is always cleared after failure.

To disable login security enhancements, use the following filter:

```php
add_filter('wpbaseline_login_security', '__return_false');
```

### REST API User Endpoints

REST API user endpoints are restricted to users with the `list_users` capability by default. To disable this restriction and make the user endpoint publicly accessible again use this filter:

```php
add_filter('wpbaseline_disable_user_rest_endpoints', '__return_false');
```

### Author Enumeration

Requests like `?author=2` are blocked with a 404 by default for anyone without the `list_users` capability, instead of being redirected to `/author/username/` and revealing a valid username. Author archive URLs themselves (`/author/username/`) are unaffected. To disable this:

```php
add_filter('wpbaseline_block_author_enumeration', '__return_false');
```

### XMLRPC

XMLRPC is disabled by default. To re-enable it, use the following filter:

```php
add_filter('wpbaseline_disable_xmlrpc', '__return_false');
```

### SVG Support

Adds support for SVG uploads. SVGs are automatically sanitized upon upload using the [enshrined/svg-sanitize](https://github.com/darylldoyle/svg-sanitizer) library for security to remove potentially malicious content.

To disable SVG support, use the following filter:

```php
add_filter('wpbaseline_enable_svg_uploads', '__return_false');
```

### Additional MIME Types

Support for JSON and Lottie file uploads is available but disabled by default for security.

To enable JSON uploads:

```php
add_filter('wpbaseline_enable_json_uploads', '__return_true');
```

JSON uploads are only checked for well-formed structure — `application/json`
is never executed by a browser or server, so there is nothing an upload-time
transform can safely do to make it "sanitized" the way SVG sanitization
does. Content is validated but never rewritten, so this filter has no
effect on JSON content, only on whether the well-formed check runs at all:

```php
add_filter('wpbaseline_sanitize_json_uploads', '__return_true');
```

**Note:** unlike Lottie validation below, this defaults to `false` even
when JSON uploads are enabled — a site that enables JSON uploads but never
sets this filter accepts any file content with no structural check at all.

To enable Lottie uploads:

```php
add_filter('wpbaseline_enable_lottie_uploads', '__return_true');
```

Lottie validation runs automatically once Lottie uploads are enabled
(default `true`, unlike JSON's sanitize filter above). A `.lottie` upload
must be either valid Lottie JSON, or a real [dotLottie](https://dotlottie.io/)
ZIP archive containing a `manifest.json` and an `animations/` directory —
an arbitrary file renamed to `.lottie` is rejected. Archive validation
requires the `zip` PHP extension; without it, non-JSON `.lottie` uploads
are rejected (fails closed).

To disable Lottie validation:

```php
add_filter('wpbaseline_validate_lottie_uploads', '__return_false');
```

To customize Lottie file size limit (default: 10MB):

```php
add_filter('wpbaseline_lottie_max_file_size', function() {
    return 5 * 1024 * 1024; // 5MB
});
```

### Upload Capability

SVG, JSON, and Lottie are mime types WordPress core disallows by default;
this package is what unlocks them, so all three require the
`manage_options` capability (administrators only) regardless of which of
the filters above enabled the type. This is checked both when the mime
type is registered and again at sanitize/validate time, so a plugin adding
the mime type some other way can't bypass it.

To allow broader access (e.g. Authors or Editors uploading SVGs):

```php
add_filter('wpbaseline_mime_upload_capability', function ($capability, $mime_key) {
    // $mime_key is one of 'svg', 'json', 'lottie'.
    if ('svg' === $mime_key) {
        return 'upload_files';
    }
    return $capability;
}, 10, 2);
```

### Duplicate Post

Adds a "Duplicate" action to post and page row actions, allowing users to quickly create copies of existing content. Duplicated posts are created as drafts and include all content, meta fields, and taxonomies.

The feature is enabled by default for all post types, gated on the same
per-post `edit_post` capability WordPress core uses to decide whether a
user can open that post to edit it — a user who can edit their own posts
but not others' (e.g. the Author role) can duplicate their own posts but
not someone else's. To customize or disable:

```php
// Disable duplicate post functionality entirely
add_filter('wp_baseline_duplicate_post_config', function($config) {
    $config['enabled'] = false;
    return $config;
});

// Limit to specific post types
add_filter('wp_baseline_duplicate_post_config', function($config) {
    $config['post_types'] = ['post', 'page', 'product'];
    return $config;
});

// Disable for specific post types
add_filter('wp_baseline_duplicate_post_config', function($config) {
    // Get all public post types
    $post_types = get_post_types(['public' => true]);
    // Remove the ones you don't want
    unset($post_types['attachment']);
    $config['post_types'] = array_keys($post_types);
    return $config;
});

// Deny duplication for a specific post (list link + admin action)
add_filter('wp_baseline_can_duplicate_post', function($can, $post) {
    if ($post instanceof WP_Post && get_post_meta($post->ID, 'my_locked_meta', true)) {
        return false;
    }
    return $can;
}, 10, 2);
```

When a post is duplicated:

- The new post is created as a draft with "(Copy)" appended to the title
- All custom fields and meta data are copied
- All taxonomies (categories, tags, etc.) are preserved
- The user stays on the posts list with a success message
- A link to edit the duplicate is provided in the success notice

### Block Uploads PHP Execution

Many hosts already block PHP execution inside the uploads directory at the server level. Baseline adds the same rule in `.htaccess` so the protection is in place even on hosts that don't set it up themselves — closing off a common technique for persisting malicious code after a compromise. Enabled by default. To disable this:

```php
add_filter('wpbaseline_block_uploads_php_execution', '__return_false');
```

This only controls Baseline's own `.htaccess` rule. If your host already blocks PHP execution in uploads at the server level, that protection is independent of this filter and stays in place either way.

**This rule has no effect on nginx.** `.htaccess` is an Apache (and LiteSpeed) mechanism; nginx doesn't read it. On nginx, this filter still fires and generates the rule, but nothing ever consumes it — the site is not protected by this feature. Add the equivalent block to your nginx server config directly, e.g.:

```nginx
location ~* /wp-content/uploads/.*\.(?:php|phtml|php\d?|phar)$ {
    deny all;
}
```

## Disclaimer

This software is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software.

Use of this library is at your own risk. The authors and contributors of this project are not responsible for any damage to your website or any loss of data that may result from the use of this library.

While we strive to keep this library up-to-date and secure, we make no guarantees about its performance, reliability, or suitability for any particular purpose. Users are advised to thoroughly test the library in a safe environment before deploying it to a live site.

By using this library, you acknowledge that you have read this disclaimer and agree to its terms.
