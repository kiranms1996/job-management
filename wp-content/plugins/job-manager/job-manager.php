<?php
/**
 * Plugin Name: Job Manager
 * Plugin URI: http://job-management.test/
 * Description: A simple plugin for posting and managing job listings.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 5.6
 * Author: Kiran M S
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Activation hook to create the necessary table for storing job listings.
 *
 * @return void
 */
function job_manager_activate() {
    global $wpdb;

    // Define the table name with WordPress table prefix
    $table_name = $wpdb->prefix . 'jobs';

    // Get the character set and collation for the database
    $charset_collate = $wpdb->get_charset_collate();

    // SQL query to create the job listings table
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        position_title VARCHAR(255) NOT NULL,             -- Job position title
        company_name VARCHAR(255) NOT NULL,               -- Company name
        job_type VARCHAR(100) NOT NULL,                   -- Job type (full-time, part-time, etc.)
        job_category VARCHAR(100) NOT NULL,               -- Job category
        company_logo VARCHAR(255) NULL,                   -- Company logo URL
        description TEXT NOT NULL,                        -- Job description
        job_location VARCHAR(255) NULL,                   -- Job location
        expiry_date DATE NULL,                            -- Expiry date for the job listing
        is_featured TINYINT(1) DEFAULT 0,                 -- Whether the job is featured (1 or 0)
        post_id BIGINT(20) UNSIGNED NOT NULL,             -- New field to store associated post ID
        PRIMARY KEY (id),                                 -- Set the primary key as `id`
        UNIQUE KEY post_id (post_id)                      -- Unique key for post_id
    ) $charset_collate;";

    // Include the WordPress upgrade functions (dbDelta).
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    // Run the dbDelta function to create the table (it will handle updates as well).
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'job_manager_activate' );

/**
 * Optionally, add a deactivation function to clean up resources (e.g., drop tables)
 *
 * @return void
 */
function job_manager_deactivate() {
    global $wpdb;

    // Define the table name with WordPress table prefix.
    $table_name = $wpdb->prefix . 'jobs';

    // SQL query to drop the table when the plugin is deactivated.
    $sql = "DROP TABLE IF EXISTS $table_name;";
    
    // Execute the query to drop the table.
    $wpdb->query( $sql );
}
register_deactivation_hook( __FILE__, 'job_manager_deactivate' );

/**
 * Include the Job Manager class file.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-job-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-job-listings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-job-applications.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/tables/class-job-application-list-table.php';

/**
 * Initialize and run the Job Manager plugin.
 *
 * This function creates an instance of the Job_Manager class and calls
 * its 'run' method to initialize the plugin functionality.
 *
 * @return void
 */
function job_manager_init() {
    // Ensure the class exists before instantiating it.
    if ( class_exists( 'Job_Manager' ) ) {
        $plugin = new Job_Manager();
        $plugin->run();
    } else {
        // Log an error message if the class is not found.
        error_log( 'Job_Manager class not found. Please ensure the file includes/class-job-manager.php exists and is loaded properly.' );
    }
}
add_action( 'plugins_loaded', 'job_manager_init' );

/**
 * Create the 'job_applications' table in the WordPress database.
 */
function create_job_applications_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'job_applications';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // SQL for creating the table
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            job_id BIGINT(20) NOT NULL,
            applicant_name VARCHAR(255) NOT NULL,
            applicant_email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            resume_url VARCHAR(255) NOT NULL,
            date_applied DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Include WordPress upgrade file to use dbDelta function
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Use dbDelta to create or update the table
        dbDelta( $sql );
    }
}
register_activation_hook( __FILE__, 'create_job_applications_table' );
