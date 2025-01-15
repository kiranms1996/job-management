<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'job_management' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'password' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '89D:[57{afB%Re|XQUfo[WR|GIpDS<:DK *m`d<DR_oK5,@m 5tjF87cD;uIFP*G' );
define( 'SECURE_AUTH_KEY',  'm,kMJ>3S2osWkTPKP63kz/Y-~kuFzbkPWBJwHT|WS?R L]g7:{f1n$*:%2EfS22N' );
define( 'LOGGED_IN_KEY',    'F.:fI_~Ab)`9cYN&gIf4I^|sz=re]l*jw/u7_NsLKgHK$`6t<]:yA=6_#}c]jq~r' );
define( 'NONCE_KEY',        '=4yid6K[<|vqM%LzMn-zD3{[r9G#lyGAk }]U6gmzX<:[j_/q~{b,>bmTlb,B:8i' );
define( 'AUTH_SALT',        'zIzc3LoX|CH2I}reK{R:b>n$IB[b2-!Vj92c8P2hgL*!up]][Be0%)@k.D*MO9kW' );
define( 'SECURE_AUTH_SALT', '7!Su%MSPlJI[k}P,]$~7@]B(#N;l4NA+hGm491xzew>BD&,=F0UL_wHBd7H| ijv' );
define( 'LOGGED_IN_SALT',   ';V!;OPcEFd`6qT>}KsS[$tm%n6~@4l)%4#zJ2}Jnp#%$y=w_G4OO~}`Ox(]O}#he' );
define( 'NONCE_SALT',       '.4a(Z_uI^MwX:U+cdgh*1j&no$a|z#OI|yd:}eNu-D@7S_j?kuy SN;h*g 0Cg1/' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
