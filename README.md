# WordPress Multisite Network Plugin management

***For multisite network ONLY***
Adds a new `Plugins` tab under `wp-admin/network/sites.php` which gives
the ability to enable or disable (without actually activate) plugins per site.

This just "hides" not enabled plugins from the site plugins area.

**Note:** the super-admin can still have access to all plugins, even for each specific website
which is great because the super-admin can activate hidden plugins :-)

### Usage
1. Just download or copy this file
2. Place it under `wp-content/mu-plugins/network-plugins.php`
3. Done.

Enable plugins for each site under network sites management area.
