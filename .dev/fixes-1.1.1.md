# Fixes for 1.1.1 — WordPress.org review checklist

Tick items off as they're resolved. Each links to the reviewer's full explanation below.

- [x] [Ajax endpoint URL](#ajax-url) — `admin_url()` via `wp_localize_script` (`ajaxUrl`)
- [x] [Document source for compressed JS/CSS](#source-code) — sources + build config now ship in the zip; readme maps each built file to its source; Tailwind sources pinned so the build is reproducible (push + tag the repo before submitting)
- [x] [Use wp_enqueue commands](#enqueue) — HTML API / `wp_get_script_tag()` for rewritten tags; drop-in probe now a single `<script src>` to the enqueued probe file
- [x] [Document external services in the readme](#external-services)
- [x] [Determine file and directory locations correctly](#file-locations) — `plugin_basename()` in Menu; URL-prefix mapping in `LocalAssetResolver`; drop-in path commented. Cache dir moves under disallowed-writes
- [x] [Setting a default timezone (vendored Carbon)](#timezone) — Carbon removed via composer `replace`
- [x] [Writing data to disallowed locations](#disallowed-writes) — minified/combined assets moved to uploads via `AssetStorage`; page cache/drop-ins/Blade stay in wp-content (caching exception, documented)
- [~] [Unclosed ob_start() (vendored Illuminate)](#ob-start) — 5 of 7 removed via `bin/patch-vendor-view-trim.php`; 2 component/slot buffers in `ManagesComponents` remain, explained
- [x] [Nonces and user permissions](#nonces) — `FlashNotice`, nonce-gated `TableSort`, screens routed as real admin pages (no `section`/`tab` reads)
- [x] [Sanitize, validate and escape input (`$_SERVER`, `$_COOKIE`)](#sanitize) — all reads via `RequestInput`; drop-in uses a documented inline filter; parity test runs the drop-in
- [x] [Escape variables and options when echoed](#escape) — inline JS/CSS to files, Composer guard removed, export via `wp_send_json_success`, `BladeEngine::display()`; 2 documented `echo` sites remain
- [x] [Generic / too-short names and prefixes](#prefixes) — `ptk` renamed; polyfills dropped (`replace` + `ext-mbstring` guard); vendor autoloader renamed via `bin/rename-vendor-autoloader.php`. `WP_Object_Cache` can't be renamed (WP drop-in contract) — explain to reviewer
- [x] [Unsafe SQL calls](#sql) — constant query + PHP sort; `OPTIMIZE TABLE %i`

---

<a id="ajax-url"></a>

🔴️ The link to the ajax endpoint may not work in some configurations.


When you link to the Ajax endpoint, you cannot assume that it's always located at wp-admin/admin-ajax.php. There are different configurations in which that won't work.

This means you can't link it statically, you have to use a function to determine its location, for example: admin_url( 'admin-ajax.php' );

Obviously you would need to execute that call on PHP and then pass the information to your JS file, you can do that using the wp_localize_script() function which is also useful for other uses like passing the nonce. Let me share an example:

function pivopeto_scripts() {
wp_enqueue_script( 'pivopeto-script', PIVOPETO_PLUGIN_URL . 'js/script.js', array(), PIVOPETO_VERSION );

wp_localize_script( 'pivopeto-script', 'pivopeto-ajax', array(
'ajax_url' => admin_url('admin-ajax.php'),
'nonce'  => wp_create_nonce( 'pivopeto-ajax-nonce' ),
));
}
add_action( 'wp_enqueue_scripts', 'pivopeto_scripts' );

Once you have this, you can later refer to your Ajax endpoint in the JS file by using the pivopeto-ajax.ajax_url variable. Please refer to the Ajax documentation: https://developer.wordpress.org/plugins/javascript/ajax/

Example(s) from your plugin:
dist/admin-js.js:1  ...l==`string`&&window.ajaxurl!==``?window.ajaxurl:`/wp-admin/admin-ajax.php`}function t(e,t,n){if(!(!e||!n)){var r=e.querySelector(`.pivot-performance-toolkit-quick-action-notice`);r&&(r._ptkDismissTime...



<a id="source-code"></a>

🔴️ No publicly documented resource for your generated/compressed content


In reviewing your plugin, we cannot find a non-compiled version of your javascript and/or css related source code.

In order to comply with our guidelines of human-readable code, we require you to include the source code and / or a link to the source code, this is true for your own code and for developer libraries you’ve included in your plugin. If you include a link, this may be in your source code, however we require you to also have it in your readme.

https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#4-code-must-be-mostly-human-readable

We strongly feel that one of the strengths of open source is the ability to review, observe, and adapt code. By maintaining a public directory of freely available code, we encourage and welcome future developers to engage with WordPress and push it forward.

That said, with the advent of larger and larger plugins using more complex libraries, people are making good use of build tools (such as composer or npm) to generate their distributed production code. In order to balance the need to keep plugin sizes smaller while still encouraging open source development, we require plugins to make the source code to any compressed files available to the public in an easy to find location, by documenting it in the readme.

For example, if you’ve made a Gutenberg plugin and used npm and webpack to compress and minify it, you must either include the source code within the published plugin or provide access to a public maintained source that can be reviewed, studied, and yes, forked.

🔗 If you choose to add a link to a repository, please make sure that the repository exists and is publicly accessible. We will check those links in the next review.

We strongly recommend you include directions on the use of any build tools to encourage future developers.

From your plugin:
dist/admin-js.js:3  ...`),t.focus()})}function g(){var e=document.getElementById(`pivot-performance-toolkit-run-cache-test`);if(!e)return;var t=typeof window.ptkBrowserCacheTest==`object`&&window.ptkBrowserCacheTest?window.... 
dist/admin-js.js:1  ...var e=((e,t)=>()=>(t||(e((t={exports:{}}).exports,t),e=null),t.exports))((()=>{(function(){function e(){return typeof window.ajaxurl==`string`&&window.ajaxurl!==``?window.ajaxurl:`/wp-admin/admin-ajax...



<a id="enqueue"></a>

🔴️ Use wp_enqueue commands


Your plugin is not correctly including JS and/or CSS. You should be using the built in functions for this:

When including JavaScript code you can use:
wp_register_script() and wp_enqueue_script() to add JavaScript code from a file.
wp_add_inline_script() to add inline JavaScript code to previous declared scripts.

When including CSS you can use:
wp_register_style() and wp_enqueue_style() to add CSS from a file.
wp_add_inline_style() to add inline CSS to previously declared CSS.

Note that as of WordPress 6.3, you can easily pass attributes like defer or async: https://make.wordpress.org/core/2023/07/14/registering-scripts-with-async-and-defer-attributes-in-wordpress-6-3/

Also, as of WordPress 5.7, you can pass other attributes by using this functions and filters: https://make.wordpress.org/core/2021/02/23/introducing-script-attributes-related-functions-in-wordpress-5-7/

If you're trying to enqueue on the admin pages you'll want to use the admin enqueues.

https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_styles/

Example(s) from your plugin:
includes/Optimization/AsyncCss.php:101 $noscript = '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"' . $id_attr . $media_attr . '></noscript>';
includes/Optimization/Combine.php:413 return '<link rel="stylesheet" href="' . esc_url( $target_web ) . '">';
includes/Optimization/Combine.php:465 return '<script src="' . esc_url( $target_web ) . '"></script>';
includes/Cache/advanced-cache.php:150 $ptk_script  = "\n<script>\n(function(){\nvar token=" . $ptk_safe_token . ';var collectUrl=' . $ptk_safe_url . ';';



<a id="external-services"></a>

🔴️ Undocumented use of a 3rd Party / external service


Plugins are permitted to require the use of third party/external services as long as they are clearly documented.

When your plugin reach out to external services, you must disclose it. This is true even if you are the one providing that service.

You are required to document it in a clear and plain language, so users are aware of: what data is sent, why, where and under which conditions.

To do this, you must update your readme file to clearly explain that your plugin relies on third party/external services, and include at least the following information for each third party/external service that this plugin uses:
What the service is and what it is used for.
What data is sent and when.
Provide links to the service's terms of service and privacy policy.
Remember, this is for your own legal protection. Use of services must be upfront and well documented. This allows users to ensure that any legal issues with data transmissions are covered.

Example:
== External services ==

This plugin connects to an API to obtain weather information, it's needed to show the weather information and forecasts in the included widget.

It sends the user's location every time the widget is loaded (If the location isn't available and/or the user hasn't given their consent, it displays a configurable default location).
This service is provided by "PRT Weather INC": terms of use, privacy policy.

🔗 Please verify that the terms and privacy links exist and they have the proper content. We will check those links in the next review.

Example(s) from your plugin:
# Domain(s) not mentioned in the readme file.
includes/Vendor/symfony/polyfill-php83/Php83.php:26 private const JSON_MAX_DEPTH = 0x7FFFFFFF; // see https://www.php.net/manual/en/function.json-decode.php
includes/Admin/CachePage.php:428 wp_remote_get($fallback_url, $args);
# ↳ Found: 'http://' . substr($url, 8)



<a id="file-locations"></a>

🔴️ Determine files and directories locations correctly


WordPress provides several functions for easily determining where a given file or directory lives.

We detected that the way your plugin references some files, directories and/or URLs may not work with all WordPress setups. This happens because there are hardcoded references or you are using the WordPress internal constants.

Let's improve it, please check out the following documentation:

https://developer.wordpress.org/plugins/plugin-basics/determining-plugin-and-content-directories/

It contains all the functions available to determine locations correctly.

Most common cases in plugins can be solved using the following functions:
For where your plugin is located: plugin_dir_path(), plugin_dir_url(), plugins_url()
For the uploads directory: wp_upload_dir() (Note: If you need to write files, please do so in a folder in the uploads directory, not in your plugin directories).

Example(s) from your plugin:
includes/Cache/ObjectCacheManager.php:130 if ( ! is_writable( WP_CONTENT_DIR ) ) {
includes/Vendor/illuminate/support/Stringable.php:261 return new static(dirname($this->value, $levels));
includes/Cache/ObjectCacheManager.php:73 && is_writable( WP_CONTENT_DIR )
includes/Utils/LocalAssetResolver.php:37 $absolute = ABSPATH . ltrim( $path, '/' );
includes/Utils/LocalAssetResolver.php:39 $root     = realpath( ABSPATH );
includes/Admin/Menu.php:287 if ( defined( 'WP_PLUGIN_DIR' ) && str_starts_with( $callback_file, WP_PLUGIN_DIR ) ) {
-----> WP_PLUGIN_DIR



ℹ️ In order to determine your plugin location, you would need to use the __FILE__ variable for this to work properly.
Note that this variable depends on the location of the file making the call. As this can create confusion, a common practice is to save its value in a define() in the main file of your plugin so that you don't have to worry about this.

Example: Your main plugin file.
define( 'PIVOPETO_PLUGIN_FILE', __FILE__ );
define( 'PIVOPETO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PIVOPETO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

Example: Any file of your plugin.

require_once PIVOPETO_PLUGIN_DIR . 'admin/class-init.php';


function pivopeto_scripts() {
 wp_enqueue_script( 'pivopeto-script', PIVOPETO_PLUGIN_URL . 'js/script.js', array(), PIVOPETO_VERSION );
 // Or alternatively
 wp_enqueue_script( 'pivopeto-script', plugins_url( 'js/script.js', PIVOPETO_PLUGIN_FILE ), array(), PIVOPETO_VERSION );
}
add_action( 'wp_enqueue_scripts', 'pivopeto_scripts' );


Example(s) from your plugin:
includes/Admin/Menu.php:287 if ( defined( 'WP_PLUGIN_DIR' ) && str_starts_with( $callback_file, WP_PLUGIN_DIR ) ) {
-----> WP_PLUGIN_DIR



<a id="timezone"></a>

🔴️ Setting a default timezone


This is rarely a good idea. People should be able to define their own timezones in WordPress.

Also WordPress explicitly sets and expects the default timezone to be UTC (in settings.php) and the date/time functions sometimes rely on the fact that the default timezone is UTC. For instance if you do date_default_timezone_set(get_option('timezone_string')) and then later try to get a GMT timestamp from get_post_time() or get_post_modified_time(), it will fail to give you the right date.

Example(s) from your plugin:
includes/Vendor/nesbot/carbon/src/Carbon/Factory.php:696 date_default_timezone_set($timezone);



<a id="disallowed-writes"></a>

🔴️ Writing data to disallowed or incorrect locations, and/or asking users to edit plugin files


We cannot accept a plugin that forces (or tells) users to edit plugin files in order to function, or that writes data to locations where plugins are not supposed to write.

Plugins must never write to WordPress core directories (such as wp-admin, wp-includes), to other plugins' or themes' folders, to their own plugin folder and only under certain exceptions to the site root, to wp-content or to paths outside the WordPress installation. These locations are not guaranteed to exist, to be writable, or to persist, and writing to them can break sites, be silently wiped, or expose data.

Some specific reasons this is a problem:

Plugin and core folders are deleted or replaced when upgraded, so anything stored there is lost.
Many hosts run WordPress with read-only or restricted filesystems, or with paths that differ from the defaults. Hard-coded paths will fail there.
Writing outside the expected locations breaks multisite and other one-off configurations.

It is preferable that you save your information to the database, via the Settings API, especially if it's privileged data.

If that's not possible because you're uploading media files, you should use the media uploader.

If you can't do either of those, you must save the data in the uploads directory, resolved at runtime with wp_upload_dir() (never a hard-coded path), creating a folder there with the slug of your plugin as name. If the data is not meant to be public, it must be protected from direct access.

Writing directly to the wp-content folder or in a folder there is acceptable only when the nature of your plugin genuinely requires it. Typical examples are caching plugins, which may need drop-ins or cache files outside the uploads directory, and backup or migration plugins, which may need storage that is independent of the per-site uploads directory in multisite.

Please refer to the following links:

https://developer.wordpress.org/plugins/settings/
https://developer.wordpress.org/reference/functions/media_handle_upload/
https://developer.wordpress.org/reference/functions/wp_handle_upload/
https://developer.wordpress.org/reference/functions/wp_upload_dir/

Example(s) from your plugin:
includes/Optimization/Combine.php:504 file_put_contents($target_path, $content, LOCK_EX);
# ↳ Detected: WP_CONTENT_DIR
includes/Optimization/Assets.php:313 file_put_contents($target_path, $minified, LOCK_EX);
# ↳ Detected: WP_CONTENT_DIR
includes/Optimization/Assets.php:355 file_put_contents($target_path, $minified, LOCK_EX);
# ↳ Detected: WP_CONTENT_DIR



<a id="ob-start"></a>

🔴️ Unclosed ob_start()


Using ob_start() without explicitly closing the buffer within the same logical flow (e.g., using ob_get_clean(), ob_end_flush(), or similar) can lead to unpredictable behaviour.

While output buffering is a valid technique, you should not leave a buffer 'open'.

WordPress is a shared environment in which the core, plugins and themes run in a coordinated sequence that isn't always predictable, particularly due to the way hooks work. If another component opens or closes a buffer that doesn't align with yours, the buffer stack can become misaligned, resulting in unexpected behaviour.

If you need to modify the entire response output, you can do so in a standardised way since WordPress 6.9 using the template enhancement output buffer.

Please ensure that every instance of ob_start() you create is paired with a corresponding closing function (like ob_get_clean()) within the same function scope, and that nothing (including hooks and your code logic) can intercept or bypass that closing logic.

Example(s) from your plugin:
includes/Vendor/illuminate/view/Concerns/ManagesStacks.php:100 ob_start();
includes/Vendor/illuminate/view/Concerns/ManagesComponents.php:177 ob_start();
includes/Vendor/illuminate/view/Concerns/ManagesStacks.php:44 ob_start();
includes/Vendor/illuminate/view/Concerns/ManagesFragments.php:35 ob_start();
includes/Vendor/illuminate/view/Concerns/ManagesComponents.php:60 ob_start();
includes/Vendor/illuminate/view/Concerns/ManagesLayouts.php:53 ob_start();
includes/Vendor/illuminate/view/Concerns/ManagesTranslations.php:26 ob_start();



<a id="nonces"></a>

🔴️ Nonces and User Permissions Needed for Security


Please add a nonce check to your input calls ($_POST, $_GET, $REQUEST) to prevent unauthorized access.

If you use wp_ajax_ to trigger submission checks, remember they also need a nonce check.

👮 Checking permissions: Keep in mind, a nonce check alone is not bulletproof security. Do not rely on nonces for authorization purposes. When needed, use it together with current_user_can() in order to prevent users without the right permissions from accessing things they shouldn't.

Also make sure that the nonce logic is correct by making sure it cannot be bypassed. Checking the nonce with current_user_can() is great, but mixing it with other checks can make the condition more complex and, without realising it, bypassable, remember that anything can be sent through an input, don't trust any input.

Keep performance in mind. Don't check for post submission outside of functions. Doing so means that the check will run on every single load of the plugin, which means that every single person who views any page on a site using your plugin will be checking for a submission. This will make your code slow and unwieldy for users on any high traffic site, leading to instability and eventually crashes.

The following links may assist you in development:

https://developer.wordpress.org/plugins/security/nonces/
https://developer.wordpress.org/plugins/javascript/ajax/#nonce
https://developer.wordpress.org/plugins/settings/settings-api/

From your plugin:
includes/Admin/CachePage.php:71 CachePage::buildViewData() [classMethod] No nonce check found validating input origin on lines 71-74
# ↳ Line 74: $settings_updated = isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true';
includes/Admin/DatabaseTablePage.php:49 DatabaseTablePage::buildViewData() [classMethod] No nonce check found validating input origin on lines 49-51
# ↳ Line 51: $sort_by  = isset( $_GET['sort'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort'] ) ) : 'size';
# ↳ Line 52: $sort_dir = isset( $_GET['sort_dir'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort_dir'] ) ) : 'desc';
includes/Admin/BrowserCacheHeadersPage.php:58 BrowserCacheHeadersPage::buildViewData() [classMethod] No nonce check found validating input origin on lines 58-72
# ↳ Line 72: 'notice'           => isset( $_GET['pivot_performance_toolkit_htaccess_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['pivot_performance_toolkit_htaccess_notice'] ) ) : '',
# ↳ Line 73: 'message'          => isset( $_GET['pivot_performance_toolkit_htaccess_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pivot_performance_toolkit_htaccess_message'] ) ) : '',
includes/Admin/ToolsPage.php:107 ToolsPage::buildViewData() [classMethod] No nonce check found validating input origin on lines 107-112
# ↳ Line 111: $removed_files = isset( $_GET['pivot_performance_toolkit_minified_removed'] ) ? absint( wp_unslash( $_GET['pivot_performance_toolkit_minified_removed'] ) ) : 0;
# ↳ Line 112: $tools_notice  = isset( $_GET['pivot_performance_toolkit_tools_notice'] ) ? sanitize_key( (string) wp_unslash( $_GET['pivot_performance_toolkit_tools_notice'] ) ) : '';
# ↳ Line 113: $tools_message = isset( $_GET['pivot_performance_toolkit_tools_message'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['pivot_performance_toolkit_tools_message'] ) ) : '';
includes/Admin/CdnIntegrationsPage.php:65 CdnIntegrationsPage::buildViewData() [classMethod] No nonce check found validating input origin on lines 65-70
# ↳ Line 70: 'notice'                  => isset( $_GET['pivot_performance_toolkit_cf_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['pivot_performance_toolkit_cf_notice'] ) ) : '',
# ↳ Line 71: 'message'                 => isset( $_GET['pivot_performance_toolkit_cf_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pivot_performance_toolkit_cf_message'] ) ) : '',
# ↳ Line 72: 'settings_updated'        => isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true',
... out of a total of 10 incidences.


<a id="sanitize"></a>

🔴️ Data Must be Sanitized, Escaped, and Validated


When you include POST/GET/REQUEST/FILE calls in your plugin, it's important to sanitize, validate, and escape them. The goal here is to prevent a user from accidentally sending trash data through the system, as well as protecting them from potential security issues.

SANITIZE: Data that is input (either by a user or automatically) must be sanitized as soon as possible. This lessens the possibility of XSS vulnerabilities and MITM attacks where posted data is subverted.

VALIDATE: All data should be validated, no matter what. Even when you sanitize, remember that you don’t want someone putting in ‘dog’ when the only valid values are numbers.

ESCAPE: Data that is output must be escaped properly when it is echo'd, so it can't hijack admin screens. There are many esc_*() functions you can use to make sure you don't show people the wrong data.

To help you with this, WordPress comes with a number of sanitization and escaping functions. You can read about those here:

https://developer.wordpress.org/apis/security/sanitizing/
https://developer.wordpress.org/apis/security/escaping/

Remember: You must use the most appropriate functions for the context. If you’re sanitizing email, use sanitize_email(), if you’re outputting HTML, use wp_kses_post(), and so on.

An easy mantra here is this:

Sanitize early
Escape Late
Always Validate

Clean everything, check everything, escape everything, and never trust the users to always have input sane data. After all, users come from all walks of life.

Example(s) from your plugin:
includes/Cache/advanced-cache.php:61 if ( 0 === strncmp( $ptk_cookie, 'wordpress_logged_in_', 20 ) ) {
# ↳ This was found inside a foreach loop in the line 60 iterating over array_keys($_COOKIE)
includes/Cache/PageCache.php:210 $cookie_names = array_keys( $_COOKIE );
# ↳ Line 225: if ( fnmatch( $rule, $cookie_name ) ) {
# ↳ Line 232: if ( 0 === strcasecmp( $rule, $cookie_name ) ) {
includes/Utils/HttpProtocolDetector.php:100 $raw = isset( $_SERVER[ $key ] ) ? (string) wp_unslash( $_SERVER[ $key ] ) : '';
includes/Cache/PageCache.php:183 $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
-----> wp_unslash($_SERVER['REQUEST_URI'])
includes/Admin/AdminBarMenu.php:250 $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
-----> wp_unslash($_SERVER['REQUEST_URI'])
includes/Cache/advanced-cache.php:105 if ( 0 === strcasecmp( $ptk_rule, $ptk_cookie_name ) ) {
# ↳ This was found inside a foreach loop in the line 85 iterating over array_keys($_COOKIE)
includes/Admin/SystemStatusPage.php:162 $server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? (string) wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '';
-----> wp_unslash($_SERVER['SERVER_SOFTWARE'])
includes/Cache/advanced-cache.php:42 $ptk_request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
-----> (string) $_SERVER['REQUEST_URI']
... out of a total of 18 incidences.


Note: $_SERVER, $_COOKIE and $_SESSION inputs must be sanitized as well.

Although this might be counterintuitive, some or all of its included data can be manipulated by the sender of the request. So it needs to be sanitized just like any other input.

Example(s) from your plugin:
includes/Cache/PageCache.php:268 $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
includes/Admin/AdminBarMenu.php:249 $host        = isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : 'localhost';
includes/Cache/advanced-cache.php:117 $ptk_uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
includes/Cache/PageCache.php:247 $token = isset( $_COOKIE['pivot_performance_toolkit_perf_probe'] ) ? trim( (string) wp_unslash( $_COOKIE['pivot_performance_toolkit_perf_probe'] ) ) : '';
... out of a total of 13 incidences.

✔️ You can check this using Plugin Check.


<a id="escape"></a>

🔴️ Variables and options must be escaped when echo'd


Much related to sanitizing everything, all variables that are echoed need to be escaped when they're echoed, so it can't hijack users or (worse) admin screens. There are many esc_*() functions you can use to make sure you don't show people the wrong data, as well as some that will allow you to echo HTML safely.

At this time, we ask you escape all $-variables, options, and any sort of generated data when it is being echoed. That means you should not be escaping when you build a variable, but when you output it at the end. We call this 'escaping late.'

Besides protecting yourself from a possible XSS vulnerability, escaping late makes sure that you're keeping the future you safe. While today your code may be only outputted hardcoded content, that may not be true in the future. By taking the time to properly escape when you echo, you prevent a mistake in the future from becoming a critical security issue.

This remains true of options you've saved to the database. Even if you've properly sanitized when you saved, the tools for sanitizing and escaping aren't interchangeable. Sanitizing makes sure it's safe for processing and storing in the database. Escaping makes it safe to output.

Also keep in mind that sometimes a function is echoing when it should really be returning content instead. This is a common mistake when it comes to returning JSON encoded content. Very rarely is that actually something you should be echoing at all. Echoing is because it needs to be on the screen, read by a human. Returning (which is what you would do with an API) can be json encoded, though remember to sanitize when you save to that json object!

There are a number of options to secure all types of content (html, email, etc). Yes, even HTML needs to be properly escaped.

https://developer.wordpress.org/apis/security/escaping/

Remember: You must use the most appropriate functions for the context. There is pretty much an option for everything you could echo. Even echoing HTML safely.

Example(s) from your plugin:
includes/Admin/Menu.php:89 wp_add_inline_style( 'pivot-performance-toolkit-menu-icon', $this->menuIconCss() );
-----> $this->menuIconCss()
includes/Admin/PerformanceTest.php:69 echo BladeEngine::view(
'cards.optimization.performance.performance-test',
array(
'options'    => self::getTestableContentOptions(),
'last_score' => $last_score,
'has_result' => self::hasStoredResult( $last_result ),
)
);
includes/Optimization/DelayedJs.php:83 wp_add_inline_script( self::RUNTIME_SCRIPT_ID, $this->runtimeScript( self::FALLBACK_TIMEOUT_MS ) );
-----> $this->runtimeScript(self::FALLBACK_TIMEOUT_MS)
includes/Admin/AdminShell.php:116 echo BladeEngine::view( 'admin.shell', $shell_data );
includes/Admin/FilesystemNotices.php:50 echo BladeEngine::view(
'admin.dropin-install-notice',
array(
'reason' => $reason,
'source' => $source,
'dest'   => $dest,
)
);
includes/Vendor/autoload.php:18 echo $err;
# ↳ Detected origin: ...you are running ' . PHP_VERSION . ', please upgrade...
# ↳ Remember to ALWAYS escape as LATE as possible as with a PROPER function for the context.
includes/Admin/PerformanceTest.php:304 wp_add_inline_script( self::PROBE_SCRIPT_ID, $this->frontendProbeScript() );
includes/Cache/advanced-cache.php:171 echo $ptk_html;
# ↳ Detected origin: null !== $ptk_replaced ? $ptk_replaced : $ptk_html . $ptk_script
# ↳ Remember to ALWAYS escape as LATE as possible as with a PROPER function for the context.
... out of a total of 13 incidences.


Note: When escaping, there are cases where your plugin will need to output HTML. This can be done using the functions wp_kses_post or wp_kses. The function wp_kses_post will allow any common HTML that can go inside a post content, wp_kses will allow any HTML that you set up using its second and third parameters, please refer to its documentation.

A common mistake is to use esc_html to escape HTML. This function is not intended for that, it's intended to escape the output that will go inside an HTML tag, therefore it will strip any HTML tags.

Examples:
echo wp_kses_post($html_content);

echo wp_kses($html_content, array( 'a', 'div', 'span' ));

We have heuristically detected these cases of your plugin that might need HTML escaping (might be false positives, please check them out):
includes/Cache/advanced-cache.php:171 echo $ptk_html;


✔️ You can check this using Plugin Check.


<a id="prefixes"></a>

🔴️ Generic function/class/define/namespace/option names


All plugins must have unique function names, namespaces, defines, class and option names. This prevents your plugin from conflicting with other plugins or themes. We need you to update your plugin to use more unique and distinct names.

A good way to do this is with a prefix. For example, if your plugin is called "Pivot Performance Toolkit" then you could use names like these:
function pivopeto_save_post(){ ... }
class PIVOPETO_Admin { ... }
update_option( 'pivopeto_options', $options );
add_shortcode( 'pivopeto_shortcode', $callback );
register_setting( 'pivopeto_settings', 'pivopeto_user_id', ... );
define( 'PIVOPETO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
global $pivopeto_options;
add_action('wp_ajax_pivopeto_save_data', ... );
namespace pivotlabs\pivotperformancetoolkit;

Disclaimer: These are just examples that may have been self-generated from your plugin name, we trust you can find better options. If you have a good alternative, please use it instead, this is just an example.

The prefix should be at least four (4) characters long (don't try to use two- or three-letter prefixes anymore). We host almost 100,000 plugins on WordPress.org alone. There are tens of thousands more outside our servers. Believe us, you're likely to encounter conflicts.

You also need to avoid the use of __ (double underscores), wp_ , or _ (single underscore) as a prefix. Those are reserved for WordPress itself. You can use them inside your classes, but not as stand-alone function.

Please remember, if you're using _n() or __() for translation, that's fine. We're only talking about functions you've created for your plugin, not the core functions from WordPress. In fact, those core features are why you need to not use those prefixes in your own plugin! You don't want to break WordPress for your users.

Related to this, using if (!function_exists('NAME')) { around all your functions and classes sounds like a great idea until you realize the fatal flaw. If something else has a function with the same name and their code loads first, your plugin will break. Using if-exists should be reserved for shared libraries only.

Remember: Good prefix names are unique and distinct to your plugin. This will help you and the next person in debugging, as well as prevent conflicts.

Analysis result:
# This plugin is using the prefix "ptk" for 7 element(s).
# This plugin is using the prefix "date" for 9 element(s).
# This plugin is using the prefix "mb_case" for 3 element(s).
# This plugin is using the prefix "pivot_performance_toolkit" for 142 element(s).

# Using the common word "wp" as a prefix.
includes/Cache/object-cache.php:25 class WP_Object_Cache

# The prefix "ptk" is too short, we require prefixes to be over 4 characters.
includes/Admin/PerformanceTest.php:297 wp_localize_script(self::PROBE_SCRIPT_ID, 'ptkPerfProbe', array('collectUrl' => esc_url_raw(rest_url('ptk/v1/performance-tests/collect'))));
# ↳ Detected name: ptkPerfProbe
includes/Admin/Menu.php:149 wp_localize_script('pivot-performance-toolkit-admin-js', 'ptkBrowserCacheTest', array('homeUrl' => home_url(), 'i18n' => array('noAssets' => __('No testable assets found. Make sure your WordPress site is publicly accessible.', 'pivot-performance-toolkit'), 'results' => __('Results:', 'pivot-performance-toolkit'), 'assetsHaveHeaders' => __('assets have cache headers', 'pivot-performance-toolkit'), 'assetsCompressed' => __('assets are compressed', 'pivot-performance-toolkit'), 'configGood' => __('Browser cache configuration looks good!', 'pivot-performance-toolkit'), 'applyConfig' => __('Not all assets have cache headers. Apply the configuration below.', 'pivot-performance-toolkit'), 'notSet' => __('Not set', 'pivot-performance-toolkit'), 'none' => __('None', 'pivot-performance-toolkit'), 'unknown' => __('Unknown', 'pivot-performance-toolkit'), 'yes' => __('Yes', 'pivot-performance-toolkit'), 'errorPrefix' => __('Error:', 'pivot-performance-toolkit'), 'na' => __('N/A', 'pivot-performance-toolkit'), 'good' => __('GOOD', 'pivot-performance-toolkit'), 'check' => __('CHECK', 'pivot-performance-toolkit'), 'compressionOk' => __('OK', 'pivot-performance-toolkit'), 'notUsed' => __('NOT USED', 'pivot-performance-toolkit'), 'css' => __('CSS', 'pivot-performance-toolkit'), 'js' => __('JavaScript', 'pivot-performance-toolkit'), 'image' => __('Image', 'pivot-performance-toolkit'), 'error' => __('Error', 'pivot-performance-toolkit'))));
# ↳ Detected name: ptkBrowserCacheTest
includes/Admin/PerformanceTest.php:95 wp_localize_script('pivot-performance-toolkit-performance-test', 'ptkPerfTest', array('restRoot' => esc_url_raw(rest_url('ptk/v1/performance-tests/')), 'nonce' => wp_create_nonce('wp_rest'), 'defaultUrl' => esc_url_raw(home_url('/')), 'websiteProfile' => $this->settings->getString('website_profile') ?: 'standard', 'profileBands' => array('resourceCount' => PerformanceProfileBands::resourceCount(), 'cssSize' => PerformanceProfileBands::cssSize(), 'jsSize' => PerformanceProfileBands::jsSize(), 'imageSize' => PerformanceProfileBands::imageSize()), 'scoreWeights' => self::SCORE_WEIGHTS, 'scoreMultipliers' => self::SCORE_MULTIPLIERS, 'pollInterval' => 1000, 'timeoutMs' => 45000, 'lastResult' => is_array($last_result) ? $last_result : array(), 'i18n' => array('starting' => __('Starting test...', 'pivot-performance-toolkit'), 'running' => __('Loading target URL and collecting metrics...', 'pivot-performance-toolkit'), 'done' => __('Test complete.', 'pivot-performance-toolkit'), 'timeout' => __('Timed out waiting for test result.', 'pivot-performance-toolkit'), 'failed' => __('Could not run test.', 'pivot-performance-toolkit'), 'requestFailed' => __('Request failed.', 'pivot-performance-toolkit'), 'cacheServed' => __('Your page is being served from cache.', 'pivot-performance-toolkit'), 'cacheNotServed' => __('Your page is not being served from cache.', 'pivot-performance-toolkit'), 'bandExcellent' => __('Excellent', 'pivot-performance-toolkit'), 'bandGood' => __('Good', 'pivot-performance-toolkit'), 'bandNeedsImprovement' => __('Needs Improvement', 'pivot-performance-toolkit'), 'bandPoor' => __('Poor', 'pivot-performance-toolkit'), 'scoreAriaPrefix' => __('Score', 'pivot-performance-toolkit'), 'statusExcellent' => __('Excellent', 'pivot-performance-toolkit'), 'statusHit' => __('HIT', 'pivot-performance-toolkit'), 'statusGood' => __('Good', 'pivot-performance-toolkit'), 'statusOkay' => __('Okay', 'pivot-performance-toolkit'), 'statusNeedsImprovement' => __('Needs improvement', 'pivot-performance-toolkit'), 'statusSlow' => __('Slow', 'pivot-performance-toolkit'), 'statusModerate' => __('Moderate', 'pivot-performance-toolkit'), 'statusPoor' => __('Poor', 'pivot-performance-toolkit'), 'statusMiss' => __('MISS', 'pivot-performance-toolkit'), 'statusHeavy' => __('Heavy', 'pivot-performance-toolkit'), 'statusHigh' => __('High', 'pivot-performance-toolkit'), 'cacheHit' => __('HIT', 'pivot-performance-toolkit'), 'cacheMiss' => __('MISS', 'pivot-performance-toolkit'))));
# ↳ Detected name: ptkPerfTest
includes/Admin/Menu.php:121 wp_localize_script('pivot-performance-toolkit-admin-js', 'ptkAdmin', array('requestFailed' => __('Request failed.', 'pivot-performance-toolkit'), 'saved' => __('Saved', 'pivot-performance-toolkit'), 'error' => __('Error', 'pivot-performance-toolkit')));
# ↳ Detected name: ptkAdmin
includes/Admin/Menu.php:135 wp_localize_script('pivot-performance-toolkit-admin-js', 'ptkAdvancedRules', array('wooDefaults' => array('/cart', '/checkout', '/my-account', '/wc-api/*', '/?wc-ajax=*')));
# ↳ Detected name: ptkAdvancedRules
includes/Admin/Menu.php:110 wp_localize_script('pivot-performance-toolkit-admin-js', 'ptkSnippet', array('copied' => __('Copied!', 'pivot-performance-toolkit'), 'copyFailed' => __('Failed to copy. Please try again.', 'pivot-performance-toolkit'), 'expand' => __('Expand Full Configuration', 'pivot-performance-toolkit'), 'collapse' => __('Hide Full Configuration', 'pivot-performance-toolkit')));
# ↳ Detected name: ptkSnippet
includes/Admin/Menu.php:179 wp_localize_script('pivot-performance-toolkit-admin-js', 'ptkAssetsDetector', array('i18n' => array('selectUrl' => __('Please select a URL first.', 'pivot-performance-toolkit'), 'detecting' => __('Detecting assets...', 'pivot-performance-toolkit'), 'detectionFailed' => __('Detection failed.', 'pivot-performance-toolkit'), 'noAssetsFound' => __('Detection complete. No assets found.', 'pivot-performance-toolkit'), 'detectionComplete' => __('Detection complete.', 'pivot-performance-toolkit'), 'css' => __('CSS', 'pivot-performance-toolkit'), 'javascript' => __('JavaScript', 'pivot-performance-toolkit'), 'fonts' => __('Fonts', 'pivot-performance-toolkit'), 'images' => __('Images', 'pivot-performance-toolkit'), 'other' => __('Other', 'pivot-performance-toolkit'), 'detected' => __('Detected:', 'pivot-performance-toolkit'))));
# ↳ Detected name: ptkAssetsDetector

# Looks like there are elements not using common prefixes.
includes/Vendor/composer/autoload_real.php:9 class ComposerAutoloaderInitc7d0b287ea627fc0b97c4ae0576fae7b
includes/Vendor/symfony/polyfill-php83/Resources/stubs/SQLite3Exception.php:17 class SQLite3Exception
includes/Vendor/symfony/polyfill-php83/Resources/stubs/Override.php:17 class Override


<a id="sql"></a>

🔴️ Unsafe SQL calls


When making database calls, it's highly important to protect your code from SQL injection vulnerabilities. You need to update your code to use wpdb calls and prepare() with your queries to protect them.

Please review the following:
https://developer.wordpress.org/reference/classes/wpdb/#protect-queries-against-sql-injection-attacks
https://codex.wordpress.org/Data_Validation#Database
https://make.wordpress.org/core/2012/12/12/php-warning-missing-argument-2-for-wpdb-prepare/
https://ottopress.com/2013/better-know-a-vulnerability-sql-injection/
Example(s) from your plugin:


includes/Database/DatabaseOptimizer.php:265 $order_by  = $sortable_columns[ $sort_by ] ?? 'tbl_size';
includes/Database/DatabaseOptimizer.php:273 $rows = $wpdb->get_results(
"SELECT
table_name                 AS tbl_name,
engine                     AS tbl_engine,
table_rows                 AS tbl_rows,
data_length + index_length AS tbl_size,
CASE WHEN engine = 'MyISAM' THEN data_free ELSE 0 END AS tbl_overhead
FROM information_schema.TABLES
WHERE table_schema = DATABASE()
ORDER BY {$order_by} {$direction}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
) ?: array();
# The SQL query needs to be included in a wpdb::prepare($query, $args) function.

