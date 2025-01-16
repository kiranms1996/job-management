<?php
// Prevent direct access to this file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include WP_List_Table class if it's not already included
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Define the Job_Application_List_Table class if it doesn't exist
if ( ! class_exists( 'Job_Application_List_Table' ) ) {

    /**
     * Class to handle displaying job applications in a table format
     */
    class Job_Application_List_Table extends WP_List_Table {

        /**
         * @var string $table_name The table name for the job applications
         */
        public $table_name;

        /**
         * Constructor method to set up the table name
         */
        public function __construct() {
            global $wpdb;
            $this->table_name = $wpdb->prefix . 'job_applications';
            parent::__construct(
                array(
                    'singular' => 'job_application', // Singular label for the table
                    'plural'   => 'job_applications', // Plural label for the table
                )
            );
        }

        /**
         * Default column renderer
         *
         * @param array  $item The row data
         * @param string $column_name The column name
         * @return string Column content
         */
        public function column_default( $item, $column_name ) {
            global $wpdb;

            switch ( $column_name ) {
                case 'applicant_name':
                    return $this->column_applicant_name( $item ); // Custom column for applicant name
                case 'applicant_email':
                    return $item['applicant_email'];
                case 'job_id':
                    // Query to get job details from the jobs table
                    $job = $wpdb->get_row( $wpdb->prepare( "SELECT position_title FROM {$wpdb->prefix}jobs WHERE post_id = %d", $item['job_id'] ) );

                    // If job is found, return the job name and type
                    if ( $job ) {
                        return sprintf( '%s', esc_html( $job->position_title ) );
                    } else {
                        return __( 'Job not found', 'textdomain' );
                    }
                case 'date_applied':
                    return $this->format_posted_date( $item['date_applied'] ); // Call to custom function for date formatting
                case 'message':
                    return '<em>' . esc_html( $item[ $column_name ] ) . '</em>'; // Escape and output content
                default:
                    return print_r( $item, true );
            }
        }

        /**
         * Format the posted date in YYYY-MM-DD format and show "X days ago"
         *
         * @param string $date_applied The date when the application was posted
         * @return string Formatted date with "X days ago"
         */
        public function format_posted_date( $date_applied ) {
            // Convert the date to a DateTime object
            $posted_date = new DateTime( $date_applied );
            
            // Format the date as 'YYYY-MM-DD'
            $formatted_date = $posted_date->format( 'Y-m-d' );

            // Get the current date
            $current_date = new DateTime();

            // Calculate the difference between current date and the posted date
            $interval = $current_date->diff( $posted_date );

            // Show the number of days ago
            if ( $interval->y > 0 ) {
                $days_ago = $interval->y . ' year' . ( $interval->y > 1 ? 's' : '' ) . ' ago';
            } elseif ( $interval->m > 0 ) {
                $days_ago = $interval->m . ' month' . ( $interval->m > 1 ? 's' : '' ) . ' ago';
            } elseif ( $interval->d > 0 ) {
                $days_ago = $interval->d . ' day' . ( $interval->d > 1 ? 's' : '' ) . ' ago';
            } else {
                $days_ago = 'Today';
            }

            // Return the formatted date along with the "X days ago" information
            return $formatted_date . '<br>' . $days_ago;
        }

        /**
         * Custom column for applicant name with View and Delete buttons
         *
         * @param array $item The row data
         * @return string HTML with "View" and "Delete" buttons
         */
        public function column_applicant_name( $item ) {
            $view_url = admin_url( 'admin.php?page=job_application_view&id=' . $item['id'] ); // URL for the View button
            $delete_url = wp_nonce_url( admin_url( 'admin.php?page=' . $_REQUEST['page'] . '&action=delete&id=' . $item['id'] ), 'delete_job_application' ); // Delete action URL with nonce for security

            // Return the HTML for the applicant name column with "View" and "Delete" buttons
            $actions = array(
                'view'   => sprintf( '<a href="%s">%s</a>', $view_url, __( 'View', 'textdomain' ) ),
                'delete' => sprintf( '<a href="%s">%s</a>', $delete_url, __( 'Delete', 'textdomain' ) ),
            );
            
            // Display the applicant name with action links
            return sprintf( '%1$s %2$s', '<strong>'.esc_html( $item['applicant_name'] ).'</strong>', $this->row_actions( $actions ) );
        }

        /**
         * Checkbox column for bulk actions
         *
         * @param array $item The row data
         * @return string Checkbox HTML
         */
        public function column_cb( $item ) {
            return sprintf(
                '<input type="checkbox" name="id[]" value="%d" />', // Checkbox for bulk actions
                $item['id']
            );
        }

        /**
         * Column for the ID of the job application with action links
         *
         * @param array $item The row data
         * @return string HTML with ID and action links
         */
        public function column_id( $item ) {
            $actions = array(
                'edit'   => sprintf( 
                    '<a href="?page=smart_report_product_list_add_form&id=%s">%s</a>', 
                    $item['id'], 
                    __( 'Edit', 'textdomain' )
                ),
                'delete' => sprintf( 
                    '<a href="?page=%s&action=delete&id=%s">%s</a>', 
                    $_REQUEST['page'], 
                    $item['id'], 
                    __( 'Delete', 'textdomain' )
                ),
            );
            return sprintf( '%1$s %2$s', $item['id'], $this->row_actions( $actions ) );
        }

        /**
         * Define the columns to display in the table
         *
         * @return array Column headers
         */
        public function get_columns() {
            return array(
                'cb'             => '<input type="checkbox" />', // Bulk select
                'applicant_name' => __( 'Applicant Name', 'textdomain' ),
                'applicant_email' => __( 'Applicant Email', 'textdomain' ),
                'job_id'         => __( 'Job', 'textdomain' ),
                'date_applied'   => __( 'Posted', 'textdomain' ),
                'message'        => __( 'Message', 'textdomain' ),
            );
        }

        /**
         * Define the sortable columns
         *
         * @return array Sortable columns
         */
        public function get_sortable_columns() {
            return array(
                'applicant_name' => array( 'applicant_name', true ),
                'applicant_email' => array( 'applicant_email', true ),
                'job_id'          => array( 'job_id', true ),
                'date_applied'    => array( 'date_applied', true ),
                'message'         => array( 'message', true ),
            );
        }

        /**
         * Define bulk actions for the table
         *
         * @return array Bulk actions
         */
        public function get_bulk_actions() {
            return array(
                'delete' => __( 'Delete', 'textdomain' ), // Bulk delete action
            );
        }

        /**
         * Handle bulk actions (delete in this case)
         */
        public function process_bulk_action() {
            global $wpdb;
            $table_name = $this->table_name;

            if ( 'delete' === $this->current_action() ) {
                // Get IDs to delete
                $ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();

                // Ensure the IDs are a comma-separated string
                if ( is_array( $ids ) ) {
                    $ids = implode( ',', $ids );
                }

                // Delete the selected items
                if ( ! empty( $ids ) ) {
                    $wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );
                }
            }
        }

        /**
         * Prepare table items (data to be displayed)
         */
        public function prepare_items() {
            global $wpdb;
            $table_name   = $this->table_name;
            $per_page     = 20;
            $columns      = $this->get_columns();
            $hidden       = array();
            $sortable     = $this->get_sortable_columns();
            $this->_column_headers = array( $columns, $hidden, $sortable );

            // Process bulk actions
            $this->process_bulk_action();

            // Get the total number of job applications
            $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

            // Calculate pagination
            $paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0;

            // Fetch job applications for the current page
            $this->items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name LIMIT %d OFFSET %d",
                    $per_page,
                    $paged
                ),
                ARRAY_A
            );

            // Set pagination arguments
            $this->set_pagination_args(
                array(
                    'total_items' => $total_items,
                    'per_page'    => $per_page,
                    'total_pages' => ceil( $total_items / $per_page ),
                )
            );
        }
    }
}
