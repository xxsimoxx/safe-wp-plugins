# Safe WP Plugins

**Allow a list of plugins that needs WP > 6.2 to be installed on Classicpress**
ClassicPress is currently having `$wp_version = '6.2.8'`.

The "safe" plugin list is build merging:
- a static list
- a list prom WP API of plugins tagged "classicpress"

### `cp_local_safe_wp_plugins` hook
This hook is intended to add or remove plugins to the list of those considered working.
Use the slug as reported by WP API.

Example usage *(notice that those slugs are not supposed to work)*:

```php
add_filter( 'cp_local_safe_wp_plugins', function( $safe_plugins ) {
	$to_add = array(
		'wp-mail-smtp',
		'wp-super-cache',
	);
	return array_merge( $safe_plugins, $to_add );
});
```

