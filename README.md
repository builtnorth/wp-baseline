# WP Baseline

WP Baseline is a Composer package that provides baseline functionality for WordPress. Some of the functionality includes:

-   Cleanup of unnecessary WordPress features
-   Enhanced security measures
-   SVG upload support with sanitization
-   Cleanup of the admin dashboard

## Requirements

-   PHP >= 8
-   WordPress >= 6

## Installation & Usage

This library is meant to be dropped into a theme or plugin via composer.

1. In your WordPress project directory, run: `composer require builtnorth/wp-baseline`.
2. In your main plugin file or theme's functions.php, add:

```php
use WPBaseline;

if (class_exists('WPBaseline\App')) {
    $baseline = new WPBaseline\App;
    $baseline->boot();
}
```

## Features

### Disable Comments

Comments remain enabled by default. To disable them, set this filter to return true:

```php
add_filter('wpbaseline_disable_comments', '__return_true');
```

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

### SVG Support

Adds support for SVG uploads. SVGs are automatically sanitized upon upload using the [enshrined/svg-sanitize](https://github.com/darylldoyle/svg-sanitizer) library for security to remove potentially malicious content.

To disable SVG support, use the following filter:

```php
add_filter('wpbaseline_enable_svg_support', '__return_false');
```

## Disclaimer

This software is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software.

Use of this library is at your own risk. The authors and contributors of this project are not responsible for any damage to your website or any loss of data that may result from the use of this library.

While we strive to keep this library up-to-date and secure, we make no guarantees about its performance, reliability, or suitability for any particular purpose. Users are advised to thoroughly test the library in a safe environment before deploying it to a live site.

By using this library, you acknowledge that you have read this disclaimer and agree to its terms.
