<?php
/**
 * Plugin Name:     Network Plugins
 * Plugin URI:      https://gist.github.com/lightningspirit/dc8efcf2cb2fef0f0a817b773ac474ec
 * Description:     Allow activation of plugins in a multisite network per website
 * Author:          Move Your Digital, Inc.
 * Author URI:      https://moveyourdigital.com
 * Version:         0.4.0
 * Network:         true
 * Requires PHP:    7.4
 *
 * @category WordPress_Plugin
 * @package  Network_Plugins
 * @author   lightningspirit <lightningspirit@gmail.com>
 * @license  GPLv2 https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * @link     https://github.com/lightningspirit/wp-network-plugins/
 */

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Filters the links that appear on site-editing network pages.
 */
add_filter(
	'network_edit_site_nav_links',
	function ( array $nav ) {
		return array(
			'site-info'     => $nav['site-info'],
			'site-users'    => $nav['site-users'],
			'site-themes'   => $nav['site-themes'],
			'site-plugins'  => array(
				'label' => __( 'Plugins' ),
				'url'   => 'sites.php?page=site-plugins',
				'cap'   => 'manage_sites',
			),
			'site-settings' => $nav['site-settings'],
		);
	},
	10,
	1
);

/**
 * Adds the submenu page for site plugins
 */
add_action(
	'network_admin_menu',
	function () {
		add_submenu_page(
			'sites.php',
			__( 'Site Plugins' ),
			__( 'Site Plugins' ),
			'manage_sites',
			'site-plugins',
			function () {

				$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

				global $s, $page;
				$s    = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';
				$page = isset( $_REQUEST['paged'] ) ? wp_unslash( $_REQUEST['paged'] ) : null;

				// Clean up request URI from temporary args for screen options/paging uri's to work as expected.
				$temp_args              = array( 'enabled', 'disabled', 'error' );
				$_SERVER['REQUEST_URI'] = remove_query_arg( $temp_args, isset( $_SERVER['REQUEST_URI'] ) ? esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : null );
				$referer                = remove_query_arg( $temp_args, wp_get_referer() );

				if ( ! empty( $_REQUEST['paged'] ) ) {
					$referer = add_query_arg( 'paged', (int) $_REQUEST['paged'], $referer );
				}

				$id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;

				if ( ! $id ) {
					wp_die( esc_html__( 'Invalid site ID.' ) );
				}

				$wp_list_table->prepare_items();

				$details = get_site( $id );
				if ( ! $details ) {
					wp_die( esc_html__( 'The requested site does not exist.' ) );
				}

				if ( ! can_edit_network( $details->site_id ) ) {
					wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ), 403 );
				}

				$details = get_site( $id );
				/* translators: %s: Site title. */
				$title = sprintf( __( 'Edit Site: %s' ), esc_html( $details->blogname ) );
				?>
	<style>
		#adminmenu .wp-submenu a[href="sites.php"] {
		font-weight: 600;
		color: #fff;
		}

		.plugins .plugin-title strong {
		font-weight: 600 !important;
		font-size: 13px;
		}
	</style>
	<div class="wrap">
			<h1 id="edit-site"><?php esc_html_e( $title ); ?></h1>
			<p class="edit-site-actions"><a href="<?php echo esc_url( get_home_url( $id, '/' ) ); ?>"><?php _e( 'Visit' ); ?></a> | <a href="<?php echo esc_url( get_admin_url( $id ) ); ?>"><?php _e( 'Dashboard' ); ?></a></p>
				<?php

				network_edit_site_nav(
					array(
						'blog_id'  => $id,
						'selected' => 'site-plugins',
					)
				);

				if ( isset( $_GET['enabled'] ) ) {
					$enabled = absint( $_GET['enabled'] );
					if ( 1 === $enabled ) {
						$message = __( 'Plugin enabled.' );
					} else {
						/* translators: %s: Number of themes. */
						$message = _n( '%s theme enabled.', '%s themes enabled.', $enabled );
					}
					echo '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( $message, number_format_i18n( $enabled ) ) . '</p></div>';
				} elseif ( isset( $_GET['disabled'] ) ) {
					$disabled = absint( $_GET['disabled'] );
					if ( 1 === $disabled ) {
						$message = __( 'Plugin disabled.' );
					} else {
						/* translators: %s: Number of themes. */
						$message = _n( '%s theme disabled.', '%s themes disabled.', $disabled );
					}
					echo '<div id="message" class="updated notice is-dismissible"><p>' . sprintf( $message, number_format_i18n( $disabled ) ) . '</p></div>';
				} elseif ( isset( $_GET['error'] ) && 'none' === $_GET['error'] ) {
					echo '<div id="message" class="error notice is-dismissible"><p>' . esc_html__( 'No theme selected.' ) . '</p></div>';
				}
				?>

			<p><?php esc_html_e( 'Network enabled plugins are not shown on this screen.' ); ?></p>

		<form method="get">
				<?php $wp_list_table->search_box( __( 'Search Installed Plugins' ), 'theme' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
		<input type="hidden" name="page" value="site-plugins" />
		</form>

				<?php $wp_list_table->views(); ?>

			<form method="post" action="<?php esc_html_e( $referer ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>" />
		<input type="hidden" name="page" value="site-plugins" />
				<?php $wp_list_table->display(); ?>
		</form>

	</div>
				<?php
			},
			10
		);
	}
);

/**
 * Manage custom columns
 */
add_filter(
	'manage_sites_page_site-plugins-network_columns',
	function ( array $columns ) {
		$columns['cb'];
		return $columns;
	}
);

/**
 * Filters bulk actions on network site plugins area
 *
 * @param array $bulk_actions
 * @return array
 */
add_filter(
	'bulk_actions-sites_page_site-plugins-network',
	function () {
		return array(
			'enable-selected'  => __( 'Enable' ),
			'disable-selected' => __( 'Disable' ),
		);
	}
);

/**
 * Fitlers status views on network site plugins area
 *
 * @param array $views
 * @return array
 */
add_filter(
	'views_sites_page_site-plugins-network',
	function () {
		global $totals, $status;

		$status_links = array();

		foreach ( $totals as $type => $count ) {
			if ( ! $count ) {
				continue;
			}

			switch ( $type ) {
				case 'all':
					/* translators: %s: Number of plugins. */
					$text = _nx(
						'All <span class="count">(%s)</span>',
						'All <span class="count">(%s)</span>',
						$count,
						'plugins'
					);
					break;
				case 'active':
					/* translators: %s: Number of plugins. */
					$text = _n(
						'Active <span class="count">(%s)</span>',
						'Active <span class="count">(%s)</span>',
						$count
					);
					break;
				case 'inactive':
					/* translators: %s: Number of plugins. */
					$text = _n(
						'Inactive <span class="count">(%s)</span>',
						'Inactive <span class="count">(%s)</span>',
						$count
					);
					break;
				case 'paused':
					/* translators: %s: Number of plugins. */
					$text = _n(
						'Paused <span class="count">(%s)</span>',
						'Paused <span class="count">(%s)</span>',
						$count
					);
					break;
			}

			if ( 'search' !== $type ) {
				$status_links[ $type ] = sprintf(
					"<a href='%s'%s>%s</a>",
					add_query_arg(
						array(
							'page'          => 'site-plugins',
							'id'            => isset( $_GET['id'] ) ? wp_unslash( $_GET['id'] ) : null,
							'plugin_status' => $type,
						),
						'sites.php'
					),
					( $type === $status ) ? ' class="current" aria-current="page"' : '',
					sprintf( $text, number_format_i18n( $count ) )
				);
			}
		}

		return $status_links;
	}
);

/**
 * Because some actions are fire before admin_head
 * we need to use admin_init
 */
add_action(
	'admin_init',
	function () {
		if ( isset( $_SERVER['SCRIPT_NAME'] ) && '/wp-admin/plugins.php' === $_SERVER['SCRIPT_NAME'] ) {
			if ( ! current_user_can( 'manage_network_plugins' ) ) {
				/**
				 * Remove not enabled plugins in site plugin
				 */
				add_filter(
					'all_plugins',
					function ( $plugins ) {
						$allowed_plugins = get_option( 'allowedplugins', array() );

						foreach ( $plugins as $plugin_name => $_ ) {
							if ( ! array_key_exists( $plugin_name, $allowed_plugins ) ) {
								unset( $plugins[ $plugin_name ] );
							}
						}

						return $plugins;
					}
				);
			}
		} elseif ( isset( $_GET['page'] ) && 'site-plugins' === $_GET['page'] ) {

			$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

			$action = $wp_list_table->current_action();
			$id     = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;

			if ( ! $id ) {
				wp_die( esc_html__( 'Invalid site ID.' ) );
			}

			$details = get_site( $id );
			if ( ! $details ) {
				wp_die( esc_html__( 'The requested site does not exist.' ) );
			}

			if ( ! can_edit_network( $details->site_id ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ), 403 );
			}

			if ( $action ) {
				switch_to_blog( $id );
				$allowed_plugins = get_option( 'allowedplugins' );

				// Clean up request URI from temporary args for screen options/paging uri's to work as expected.
				$temp_args              = array( 'enabled', 'disabled', 'error' );
				$_SERVER['REQUEST_URI'] = remove_query_arg( $temp_args, $_SERVER['REQUEST_URI'] );
				$referer                = remove_query_arg( $temp_args, wp_get_referer() );

				if ( ! empty( $_REQUEST['paged'] ) ) {
					$referer = add_query_arg( 'paged', (int) $_REQUEST['paged'], $referer );
				}

				switch ( $action ) {
					case 'enable':
						check_admin_referer( 'enable-plugin_' . $_GET['plugin'] );
						$plugin = $_GET['plugin'];
						$action = 'enabled';
						$n      = 1;
						if ( ! $allowed_plugins ) {
							$allowed_plugins = array( $plugin => true );
						} else {
							$allowed_plugins[ $plugin ] = true;
						}
						break;

					case 'disable':
						check_admin_referer( 'disable-plugin_' . $_GET['plugin'] );
						$plugin = $_GET['plugin'];
						$action = 'disabled';
						$n      = 1;
						if ( ! $allowed_plugins ) {
							$allowed_plugins = array();
						} else {
							unset( $allowed_plugins[ $plugin ] );
						}
						break;

					case 'enable-selected':
						check_admin_referer( 'bulk-plugins' );
						if ( isset( $_POST['checked'] ) ) {
							$plugins = (array) $_POST['checked'];
							$action  = 'enabled';
							$n       = count( $plugins );
							foreach ( (array) $plugins as $plugin ) {
								$allowed_plugins[ $plugin ] = true;
							}
						} else {
							$action = 'error';
							$n      = 'none';
						}
						break;

					case 'disable-selected':
						check_admin_referer( 'bulk-plugins' );
						if ( isset( $_POST['checked'] ) ) {
							$plugins = (array) $_POST['checked'];
							$action  = 'disabled';
							$n       = count( $plugins );
							foreach ( (array) $plugins as $plugin ) {
								unset( $allowed_plugins[ $plugin ] );
							}
						} else {
							$action = 'error';
							$n      = 'none';
						}
						break;

					default:
						if ( isset( $_POST['checked'] ) ) {
							check_admin_referer( 'bulk-plugins' );
							$plugins = (array) $_POST['checked'];
							$n       = count( $plugins );
							$screen  = get_current_screen()->id;

							/**
							 * Fires when a custom bulk action should be handled.
							 *
							 * The redirect link should be modified with success or failure feedback
							 * from the action to be used to display feedback to the user.
							 *
							 * The dynamic portion of the hook name, `$screen`, refers to the current screen ID.
							 *
							 * @since 4.7.0
							 *
							 * @param string $redirect_url The redirect URL.
							 * @param string $action       The action being taken.
							 * @param array  $items        The items to take the action on.
							 * @param int    $site_id      The site ID.
							 */
							$referer = apply_filters( "handle_network_bulk_actions-{$screen}", $referer, $action, $plugins, $id ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
						} else {
							$action = 'error';
							$n      = 'none';
						}
				}

				update_option( 'allowedplugins', $allowed_plugins );
				restore_current_blog();

				wp_safe_redirect(
					add_query_arg(
						array(
							'id'    => $id,
							$action => $n,
						),
						$referer
					)
				);
				exit;
			}
		}
	}
);

/**
 * Register screen options
 */
add_action(
	'admin_head',
	function () {
		register_column_headers(
			'sites_page_site-plugins-network',
			array(
				'cb'          => '<label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">',
				'name'        => __( 'Name' ),
				'description' => __( 'Description' ),
			)
		);

		$current_screen_id = get_current_screen()->id;

		$screens = array(
			'site-info-network',
			'site-users-network',
			'site-themes-network',
			'sites_page_site-plugins-network',
			'site-settings-network',
		);

		if ( in_array( $current_screen_id, $screens ) ) {
			get_current_screen()->remove_help_tabs();
			get_current_screen()->add_help_tab( get_site_screen_help_with_plugins_tab_args() );
			get_current_screen()->set_help_sidebar( get_site_screen_help_sidebar_content() );
		}

		if ( $current_screen_id === 'sites_page_site-plugins-network' ) {

			get_current_screen()->set_screen_reader_content(
				array(
					'heading_views'      => __( 'Filter site plugins list' ),
					'heading_pagination' => __( 'Site plugins list navigation' ),
					'heading_list'       => __( 'Site plugins list' ),
				)
			);

			add_thickbox();
			add_screen_option( 'per_page' );

			$id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;

			/**
			 * Remove network plugins
			 */
			add_filter(
				'all_plugins',
				function ( $plugins ) use ( $id ) {
					foreach ( $plugins as $plugin_name => $_ ) {
						if ( is_plugin_active_for_network( $plugin_name ) ) {
							unset( $plugins[ $plugin_name ] );
						}
					}

					add_filter(
						'pre_site_option_active_sitewide_plugins',
						function () use ( $id ) {
							// TODO: retrieve site's enabled plugins
							// For enabled plugins return true, false otherwise!
							switch_to_blog( $id );
							$allowed_plugins = get_option( 'allowedplugins', array() );
							restore_current_blog();

							return $allowed_plugins;
						}
					);

					return $plugins;
				}
			);

			/**
			 * Disable mu-plugins
			 */
			add_filter( 'show_advanced_plugins', '__return_false' );
			add_filter( 'show_network_active_plugins', '__return_false' );
			add_filter( 'plugins_auto_update_enabled', '__return_false' );

			/**
			 * Remove view details meta
			 */
			add_filter(
				'plugin_row_meta',
				function ( $meta ) {
					// remove view details!
					unset( $meta[2] );
					return $meta;
				}
			);

			/**
			 * Correct links
			 */
			add_filter(
				'network_admin_plugin_action_links',
				function ( $actions, $plugin_file, $plugin_data, $context ) use ( $id ) {
					static $plugin_id_attrs = array();

					$plugin_slug    = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] );
					$plugin_id_attr = $plugin_slug;

					// Ensure the ID attribute is unique.
					$suffix = 2;
					while ( in_array( $plugin_id_attr, $plugin_id_attrs, true ) ) {
						$plugin_id_attr = "$plugin_slug-$suffix";
						$suffix++;
					}

					$plugin_id_attrs[] = $plugin_id_attr;

					$s    = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
					$page = isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : '';

					if ( isset( $actions['activate'] ) ) {
						$actions['enable'] = sprintf(
							'<a href="%s" id="enable-%s" aria-label="%s">%s</a>',
							wp_nonce_url(
								add_query_arg(
									array(
										'id'            => $id,
										'action'        => 'enable',
										'plugin'        => urlencode( $plugin_file ),
										'plugin_status' => $context,
										'paged'         => $page,
										's'             => $s,
									),
									'sites.php?page=site-plugins'
								),
								'enable-plugin_' . $plugin_file
							),
							esc_attr( $plugin_id_attr ),
							/* translators: %s: Plugin name. */
							esc_attr( sprintf( _x( 'Enable %s', 'plugin' ), $plugin_data['Name'] ) ),
							__( 'Enable' )
						);
						unset( $actions['activate'] );
					}

					if ( isset( $actions['deactivate'] ) ) {
						$actions['disable'] = sprintf(
							'<a href="%s" id="disable-%s" aria-label="%s">%s</a>',
							wp_nonce_url(
								add_query_arg(
									array(
										'id'            => $id,
										'action'        => 'disable',
										'plugin'        => urlencode( $plugin_file ),
										'plugin_status' => $context,
										'paged'         => $page,
										's'             => $s,
									),
									'sites.php?page=site-plugins'
								),
								'disable-plugin_' . $plugin_file
							),
							esc_attr( $plugin_id_attr ),
							/* translators: %s: Plugin name. */
							esc_attr( sprintf( _x( 'Enable %s', 'plugin' ), $plugin_data['Name'] ) ),
							__( 'Disable' )
						);
						unset( $actions['deactivate'] );
					}

					unset( $actions['delete'] );

					return $actions;
				},
				10,
				4
			);
		}
		?>
	<style>
	#adminmenu a[href="sites.php?page=site-plugins"] {
		display: none;
	}
	</style>
		<?php
	}
);

/**
 * Returns the arguments for the help tab on the Edit Site screens.
 *
 * @since 4.9.0
 *
 * @return array Help tab arguments.
 */
function get_site_screen_help_with_plugins_tab_args() {
	return array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' =>
		'<p>' . __( 'The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.' ) . '</p>' .
			'<p>' . __( '<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.' ) . '</p>' .
			'<p>' . __( '<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.' ) . '</p>' .
			'<p>' . sprintf(
			/* translators: %s: URL to Network Themes screen. */
				__( '<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ),
				network_admin_url( 'themes.php' )
			) . '</p>' .
			'<p>' . sprintf(
			/* translators: %s: URL to Network Plugins screen. */
				__( '<strong>Plugins</strong> &mdash; This area shows plugins that are not already enabled across the network. Enabling a plugin in this menu makes it accessible to this site. It does not activate the plugin, but allows it to show in the plugin list menu. To enable a plugin for the entire network, see the <a href="%s">Network Plugins</a> screen.' ),
				network_admin_url( 'plugins.php' )
			) . '</p>' .
			'<p>' . __( '<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by WordPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.' ) . '</p>',
	);
}

/**
 * Registers activation hook
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( __FILE__ );

		if ( version_compare( $plugins_data['Version'], get_network_option( get_current_network_id(), 'network_plugins_version' ) ) > 0 ) {
			$role = get_role( 'administrator' );
			$role->add_cap( 'activate_plugins' );

			update_network_option( get_current_network_id(), 'network_plugins_version', $plugins_data['Version'] );
		}
	}
);

/**
 * Register deactivation hook
 */
register_deactivation_hook(
	__FILE__,
	function () {
		delete_network_option( get_current_network_id(), 'network_plugins_version' );
	}
);
