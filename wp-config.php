<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'hazel' );

/** Database username */
define( 'DB_USER', 'hazel' );

/** Database password */
define( 'DB_PASSWORD', 'C4Lfzc8Zq9FZtf0' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1:3306' );

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
define('AUTH_KEY',         'sdpSOhu+Mz:8A1q:4&1$6&ya:+Ws>X&=?H wM`QE2M @cJNMsYghy|K*]m!<0SnT');
define('SECURE_AUTH_KEY',  'TN%? (wyK/iEP|Z}$+;9aPulJJ]g|IP2OP&aD|c{!N9*?l-kzjx+x|VwQb1<N^&K');
define('LOGGED_IN_KEY',    'n#}3NIId[!Y~u^ fYB$^Q<wNxrae.FX0&))v6]kbQ4s/U1O&].am?1{QQz$&4Spy');
define('NONCE_KEY',        '&P{GMn/U;U!9W}` dP:([AN8f`5fxWv{yomlAKn)[$2iX,w;>g,#3-u:vH7))I%e');
define('AUTH_SALT',        'ph&gFR02p<vaVhO3/TThA}4ncLfvq.1`SWD:`%TIXr*)N5+Vyd7wWW>V|q|0b-xt');
define('SECURE_AUTH_SALT', '8!+-WL;CBd6LE2chPcY|u7y]*R[yz1]xWZ# $bRjEyXb2;&=uZ*Z,9fS: `Pn1O3');
define('LOGGED_IN_SALT',   'w.Be3y&m;m::K4-Fe{Gip%5hU9(WF?#;Tc@S9oJ=n?77}--tKWffQ&lY(C*h{_!r');
define('NONCE_SALT',       'IFT0)&+k_y2jWo-_-~8l#Z1m_rJNBelmV11M4S?]fy 9_O|y[]<ajvl1U2U$7NM2');
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = '97wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
@ini_set( 'display_errors', 0 );

define( 'FORMINATOR_ENCRYPTION_KEY', 'l|N>:r3-6PRCHP=s1#8uqB ?G)w1MSYdG`H3ho!G^s;>N%Y%p@nAnE?+S<z1?C!b' );


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
