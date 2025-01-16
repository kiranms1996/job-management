<?php
/**
 * Job Manager Plugin Class
 *
 * This class handles the registration of the custom post type, taxonomy,
 * shortcode for displaying job listings, and plugin assets.
 */
class Job_Applications {

    /**
     * Constructor to initialize the class and set up necessary hooks.
     */
    public function __construct() {
        // Register custom post type and taxonomy
        add_action( 'admin_menu', array( $this, 'frontpage_admin_menu_content_checkout' ) );
    }

    /**
     * Register Checkout Content Custom Post Type
     */
    public function frontpage_admin_menu_content_checkout() {
        add_menu_page(
            'Applications', 
            'Job Applications',
            'read',
            'job-applications',
            array( $this, 'job_application_list_page' ), 
            'dashicons-clipboard',
            40
        );
    }

    /**
     * Display the job application list table
     */
    public function job_application_list_page() {
        echo '<h1>Applications</h1>';
    
        // Create an instance of the Job_Application_List_Table class.
        $list_table = new Job_Application_List_Table();
        $list_table->prepare_items();
    
        // Display the list table.
        $list_table->display();
    }
}

// Instantiate the class to initialize everything
$job_applications = new Job_Applications();
