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
        add_action( 'admin_menu', array( $this, 'job_applications_list_menu' ) );

        add_action( 'admin_menu', array( $this, 'add_job_application_view_page' ) );
    }

    // Hook into the 'admin_menu' action to add custom submenu page
    public function job_applications_list_menu() {
        add_submenu_page(
            'edit.php?post_type=job_listing',        
            'Applications',                           
            'Applications',                           
            'manage_options',                      
            'job-applications',                           
            array( $this, 'job_application_list_page' ),
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

    /**
     * View a specific job application
     */
    public function job_application_view_page() {
        // Check if 'id' is present in the query string and sanitize it
        if ( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {
            $application_id = intval( $_GET['id'] );
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'job_applications';
            
            // Get the job application details by ID
            $application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $application_id ), ARRAY_A );
            
            // If no application is found, display an error message
            if ( ! $application ) {
                echo '<div class="error"><p>' . __( 'Job application not found.', 'textdomain' ) . '</p></div>';
                return;
            }

            // Get job details for the associated job_id (you may want to adjust this query)
            $job = $wpdb->get_row( $wpdb->prepare( "SELECT position_title FROM {$wpdb->prefix}jobs WHERE post_id = %d", $application['job_id'] ) );

            ?>
            <div class="wrap">
                <h1><?php echo esc_html( __( 'View Job Application', 'textdomain' ) ); ?></h1>
                <table class="form-table">
                    <tr>
                        <th><?php echo esc_html( __( 'Applicant Name', 'textdomain' ) ); ?></th>
                        <td><?php echo esc_html( $application['applicant_name'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo esc_html( __( 'Applicant Email', 'textdomain' ) ); ?></th>
                        <td><?php echo esc_html( $application['applicant_email'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo esc_html( __( 'Job Position', 'textdomain' ) ); ?></th>
                        <td><?php echo $job ? esc_html( $job->position_title ) : __( 'Job not found', 'textdomain' ); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo esc_html( __( 'Date Applied', 'textdomain' ) ); ?></th>
                        <td><?php echo esc_html( $application['date_applied'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo esc_html( __( 'Message', 'textdomain' ) ); ?></th>
                        <td><em><?php echo esc_html( $application['message'] ); ?></em></td>
                    </tr>

                    <!-- Resume Section -->
                    <?php if ( ! empty( $application['resume_url'] ) ) : ?>
                        <tr>
                            <th><?php echo esc_html( __( 'Resume', 'textdomain' ) ); ?></th>
                            <td>
                                <!-- View Resume Button -->
                                <a href="<?php echo esc_url( $application['resume_url'] ); ?>" target="_blank" class="button button-primary">
                                    <?php echo esc_html__( 'View Resume', 'textdomain' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php
        } else {
            echo '<div class="error"><p>' . __( 'Invalid application ID.', 'textdomain' ) . '</p></div>';
        }
    }

    /**
     * Add the Job Application View Page to the WordPress admin menu
     */
    public function add_job_application_view_page() {
        add_submenu_page(
            'job_application_list', 
            __( 'View Job Application', 'textdomain' ), 
            __( 'View Job Application', 'textdomain' ), 
            'manage_options', 
            'job_application_view',
            array( $this, 'job_application_view_page' )
        );
    }
}

// Instantiate the class to initialize everything
$job_applications = new Job_Applications();
