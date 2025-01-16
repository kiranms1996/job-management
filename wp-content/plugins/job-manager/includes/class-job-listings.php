<?php
// File: includes/class-job-listings.php

if ( ! class_exists( 'Job_Listings_API' ) ) {
    class Job_Listings_API {

        // Constructor to initialize the class and hooks
        public function __construct() {
            // Register the API routes
            add_action( 'rest_api_init', array( $this, 'register_job_listings_api' ) );
            add_action( 'rest_api_init', array( $this, 'register_job_details_api' ) );
            add_action( 'rest_api_init', array( $this, 'register_apply_to_job_api' ) ); // Register apply-to-job API
        }

        /**
         * Register the REST API endpoint for job listings.
         */
        public function register_job_listings_api() {
            register_rest_route( 'job-manager/v1', '/job-listings/', array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_job_listings' ),
                'args' => array(
                    'category' => array(
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_string( $param );
                        }
                    ),
                    'posts_per_page' => array(
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ),
                ),
            ) );
        }

        /**
         * Register the REST API endpoint for job details.
         */
        public function register_job_details_api() {
            register_rest_route( 'job-manager/v1', '/job-details/(?P<job_id>\d+)', array(
                'methods' => 'GET',
                'callback' => array( $this, 'get_job_details' ),
                'args' => array(
                    'job_id' => array(
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ),
                ),
            ) );
        }

        /**
         * Callback to retrieve job listings.
         */
        public function get_job_listings( $data ) {
            global $wpdb;
        
            $posts_per_page = isset( $data['posts_per_page'] ) ? intval( $data['posts_per_page'] ) : 10;
            
            // Table name
            $table_name = $wpdb->prefix . 'jobs';
        
            // Base SQL query - filter by expiry_date and featured status (is_featured = 1)
            $sql = "SELECT position_title, company_name, job_type, expiry_date, is_featured, post_id as job_id FROM $table_name WHERE expiry_date >= %s AND is_featured = 1";  // Only non-expired and featured jobs
        
            // Limit the number of results (pagination)
            $sql .= $wpdb->prepare( " LIMIT %d", $posts_per_page );
        
            // Get job listings from the database
            $results = $wpdb->get_results( $wpdb->prepare( $sql, current_time( 'Y-m-d' ) ) );
        
            // Return the results as a JSON response
            if ( $results ) {
                return new WP_REST_Response( $results, 200 );
            } else {
                return new WP_REST_Response( 'No job listings found.', 404 );
            }
        }

        /**
         * Callback to retrieve job details.
         */
        public function get_job_details( $data ) {
            global $wpdb;

            // Get the job ID from the request
            $job_id = $data['job_id'];

            // Table name
            $table_name = $wpdb->prefix . 'jobs';

            // SQL query to fetch job details
            $sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $job_id );
        
            // Get the job details from the database
            $job = $wpdb->get_row( $sql );

            // Check if the job exists
            if ( $job ) {
                // Return the job details as a JSON response
                return new WP_REST_Response( $job, 200 );
            } else {
                return new WP_REST_Response( 'Job not found.', 404 );
            }
        }

        /**
         * Register the REST API endpoint for applying to a job.
         */
        public function register_apply_to_job_api() {
            register_rest_route( 'job-manager/v1', '/apply-to-job/', array(
                'methods' => 'POST',
                'callback' => array( $this, 'apply_to_job' ),
                'permission_callback' => '__return_true',  // Public access
                'args' => array(
                    'job_id' => array(
                        'required' => true,
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ),
                    'applicant_name' => array(
                        'required' => true,
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_string( $param );
                        }
                    ),
                    'applicant_email' => array(
                        'required' => true,
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_email( $param );
                        }
                    ),
                    'message' => array(
                        'required' => true,
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_string( $param );
                        }
                    )
                ),
            ) );
        }

        /**
         * Callback to apply to a job.
         */
        public function apply_to_job( $data ) {
            // Get the application data
            $job_id = $data['job_id'];
            $applicant_name = sanitize_text_field( $data['applicant_name'] );
            $applicant_email = sanitize_email( $data['applicant_email'] );
            $message = sanitize_textarea_field( $data['message'] );
            
            // Access the resume file from the $_FILES superglobal
            if ( isset( $_FILES['resume'] ) ) {
                $resume_file = $_FILES['resume'];  // File data from the request
            } else {
                return new WP_REST_Response( 'No resume file provided.', 400 );
            }

            // Validate job ID (make sure the job exists)
            if ( ! $this->job_exists( $job_id ) ) {
                return new WP_REST_Response( 'Job not found.', 404 );
            }

            // Handle file upload (the resume)
            $upload = $this->handle_file_upload( $resume_file );

            if ( is_wp_error( $upload ) ) {
                return new WP_REST_Response( $upload->get_error_message(), 400 );
            }

            // Save the application to the database
            global $wpdb;
            $table_name = $wpdb->prefix . 'job_applications';

            $wpdb->insert(
                $table_name,
                array(
                    'job_id'           => $job_id,
                    'applicant_name'   => $applicant_name,
                    'applicant_email'  => $applicant_email,
                    'message'          => $message,
                    'resume_url'       => $upload['url'],
                )
            );

            return new WP_REST_Response( 'Application submitted successfully.', 200 );
        }

        /**
         * Check if the job exists.
         */
        private function job_exists( $job_id ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'jobs';
            $job = $wpdb->get_row( $wpdb->prepare( "SELECT post_id FROM $table_name WHERE post_id = %d", $job_id ) );
            return $job !== null;
        }

        /**
         * Handle file upload for the resume.
         */
        private function handle_file_upload( $file ) {
            // Include the necessary file for wp_handle_upload
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
            
            // Check if file is a valid PDF
            if ( ! isset( $file['name'] ) || empty( $file['name'] ) ) {
                return new WP_Error( 'no_file', 'No file uploaded.' );
            }
        
            // Check file type (must be a PDF)
            $file_type = wp_check_filetype( $file['name'] );
            $mime_type = $file_type['type']; // Get the MIME type
        
            if ( $mime_type !== 'application/pdf' ) {
                return new WP_Error( 'invalid_file_type', 'Only PDF files are allowed.' );
            }
        
            // Handle file upload
            $upload = wp_handle_upload( $file, array( 'test_form' => false ) );
        
            // Check for errors
            if ( isset( $upload['error'] ) ) {
                // Log the error
                error_log( 'Upload Error: ' . $upload['error'] );
                return new WP_Error( 'upload_error', $upload['error'] );
            }
        
            return $upload;  // Return the upload data (URL, file path, etc.)
        }
        
    }
}

// Initialize the class
new Job_Listings_API();
