<?php
/**
 * Job Manager Plugin Class
 *
 * This class handles the registration of the custom post type, taxonomy,
 * shortcode for displaying job listings, and plugin assets.
 */
class Job_Manager {

    /**
     * Constructor to initialize the class and set up necessary hooks.
     */
    public function __construct() {
        // Register custom post type and taxonomy hooks
        add_action( 'init', array( $this, 'register_post_type' ) );

        // Add meta box to the post editor
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

        // Save meta box data when the post is saved
        add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

        // Register shortcode to display job listings
        add_shortcode( 'job_listings', array( $this, 'display_listings' ) );

        // Add 'is featured' checkbox to the publish section
        add_action( 'post_submitbox_misc_actions', array( $this, 'add_is_featured_checkbox_to_publish_section' ) );

        // Hide 'Quick Edit' and 'View' actions from the post list table
        add_filter( 'post_row_actions', array( $this, 'hide_quick_edit_and_view_actions' ), 10, 2 );
    }

    /**
     * Register custom post type for job listings.
     *
     * Registers a custom post type 'job_listing' for managing job listings.
     *
     * @return void
     */
    public function register_post_type() {
        // Define the labels for the custom post type
        $labels = array(
            'name'                  => _x( 'Jobs', 'Post type general name', 'job-manager' ),
            'singular_name'         => _x( 'Job', 'Post type singular name', 'job-manager' ),
            'menu_name'             => _x( 'Job Manager', 'Admin Menu text', 'job-manager' ),
            'name_admin_bar'        => _x( 'Job Manager', 'Add New on Toolbar', 'job-manager' ),
            'add_new'               => __( 'Add New', 'job-manager' ),
            'add_new_item'          => __( 'Add New Job', 'job-manager' ),
            'new_item'              => __( 'New Job Listing', 'job-manager' ),
            'edit_item'             => __( 'Edit Job Listing', 'job-manager' ),
            'view_item'             => __( 'View Job Listing', 'job-manager' ),
            'all_items'             => __( 'Jobs', 'job-manager' ),
            'search_items'         => __( 'Search Job Listings', 'job-manager' ),
            'not_found'             => __( 'No job listings found.', 'job-manager' ),
            'not_found_in_trash'    => __( 'No job listings found in Trash.', 'job-manager' ),
        );

        // Define the arguments for the custom post type
        $args = array(
            'labels'              => $labels,                               // Post type labels
            'public'              => true,                                   // Make the post type public
            'has_archive'         => true,                                   // Enable archive page
            'rewrite'             => array( 'slug' => 'job-listings' ),       // Custom rewrite slug
            'show_in_rest'        => true,                                   // Enable REST API support (for block editor and external APIs)
            'supports'            => array( 'thumbnail' ),                   // Post features supported (title, content, featured image)
            'show_in_menu'        => true,                                   // Display in the admin menu
            'menu_position'       => 5,                                      // Position in the menu
            'menu_icon'           => 'dashicons-businessperson',             // Dashicon for the post type icon
        );

        // Register the custom post type 'job_listing'
        register_post_type( 'job_listing', $args );
    }

    /**
     * Add meta boxes for custom fields on job listings.
     *
     * Registers custom meta boxes for job information, company information, and location.
     *
     * @return void
     */
    public function add_meta_boxes() {
        // Add meta box for Job Information
        add_meta_box(
            'job_information_meta_box',                      // Meta box ID
            __( 'Job Information', 'job-manager' ),           // Meta box title
            array( $this, 'render_meta_box' ),                // Callback to render meta box content
            'job_listing',                                   // Post type (job_listing)
            'normal',                                        // Context (normal)
            'high'                                           // Priority (high)
        );

        // Add meta box for Company Information
        add_meta_box(
            'company_information_meta_box',                  
            __( 'Company Information', 'job-manager' ),      
            array( $this, 'render_company_information_meta_box' ),
            'job_listing',                                   
            'normal',                                       
            'high'                                           
        );

        // Add meta box for Job Location
        add_meta_box(
            'location_meta_box',                             
            __( 'Location', 'job-manager' ),                 
            array( $this, 'render_location_meta_box' ),      
            'job_listing',                                   
            'normal',                                        
            'high'                                           
        );

        // Remove the 'Featured Image' meta box from the side panel for job listings
        remove_meta_box( 'postimagediv', 'job_listing', 'side' );
    }

    /**
     * Helper function to fetch job listing data by post ID.
     *
     * @param int $post_id Post ID.
     * @return object|null Job listing data or null if not found.
     */
    private function get_job_listing_by_post_id( $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jobs';
        
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $post_id ) );
    }

    /**
     * Render the fields in the meta box.
     */
    public function render_meta_box( $post ) {
        // Fetch existing job listing data if available
        $job_listing = $this->get_job_listing_by_post_id( $post->ID );

        // Set default values for new jobs
        $position_title = $job_listing ? $job_listing->position_title : '';
        $description = $job_listing ? $job_listing->description : '';
        $job_type = $job_listing ? $job_listing->job_type : '';
        $job_category = $job_listing ? $job_listing->job_category : '';

        // Add nonce for security
        wp_nonce_field( 'job_manager_meta_box_nonce', 'meta_box_nonce' );
        
        // Display fields
        ?>
        <p>
            <table class="form-table">
                <tr>
                    <th><label for="job_position_title"><?php _e( 'Title', 'job-manager' ); ?> <span style="color: red;">*</span></label></th>
                    <td>
                        <input type="text" id="job_position_title" name="job_position_title" value="<?php echo esc_attr( $position_title ); ?>" class="widefat" placeholder="Enter Job Title Here" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="job_description"><?php _e( 'Description', 'job-manager' ); ?> <span style="color: red;">*</span></label></th>
                    <td>
                        <?php
                        wp_editor(
                            $description, // Show existing description if editing
                            'job_description', 
                            array(
                                'media_buttons' => false,
                                'textarea_rows' => 5,
                                'tinymce' => array(
                                    'toolbar1' => 'bold,italic,strikethrough,bullist,numlist,link,unlink',
                                    'toolbar2' => '',
                                    'menubar' => false,
                                    'statusbar' => false,
                                ),
                                'quicktags' => false,
                            )
                        );
                        ?>
                    </td>
                </tr>    
                <tr>
                    <th><label for="job_type"><?php _e( 'Job Type', 'job-manager' ); ?></label></th>
                    <td>
                        <select id="job_type" name="job_type" class="widefat">
                            <option value="freelance" <?php selected( $job_type, 'freelance' ); ?>><?php _e( 'Freelance', 'job-manager' ); ?></option>
                            <option value="parttime" <?php selected( $job_type, 'parttime' ); ?>><?php _e( 'Part-Time', 'job-manager' ); ?></option>
                            <option value="fulltime" <?php selected( $job_type, 'fulltime' ); ?>><?php _e( 'Full-Time', 'job-manager' ); ?></option>
                        </select>
                    </td>
                </tr>    
                <tr>
                    <th><label for="job_category"><?php _e( 'Category', 'job-manager' ); ?></label></th>
                    <td>
                        <select id="job_category" name="job_category" class="widefat">
                            <option value="copywriting" <?php selected( $job_category, 'copywriting' ); ?>><?php _e( 'Copywriting', 'job-manager' ); ?></option>
                            <option value="programming" <?php selected( $job_category, 'programming' ); ?>><?php _e( 'Programming', 'job-manager' ); ?></option>
                            <option value="design" <?php selected( $job_category, 'design' ); ?>><?php _e( 'Design', 'job-manager' ); ?></option>
                            <option value="user_experience" <?php selected( $job_category, 'user_experience' ); ?>><?php _e( 'User Experience', 'job-manager' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </p>
        <?php
    }

    /**
     * Renders the company information meta box on the job listing post edit page.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_company_information_meta_box( $post ) {
        // Fetch existing job listing data if available
        $job_listing = $this->get_job_listing_by_post_id( $post->ID );
    
        // Retrieve existing logo URL if available
        $logo_url = $job_listing ? $job_listing->company_logo : '';
    
        // Extract the image file name from the URL (if logo exists)
        $logo_filename = $logo_url ? basename( $logo_url ) : '';
    
        // Retrieve the logo file size (if logo exists)
        $logo_file_size = '';
        if ( $logo_url ) {
            $attachment_id = attachment_url_to_postid( $logo_url );
            if ( $attachment_id ) {
                $file_path = get_attached_file( $attachment_id );
                $logo_file_size = size_format( filesize( $file_path ) ); // Get the file size in a readable format
            }
        }
    
        // Fetch company name if available
        $company_name = $job_listing ? $job_listing->company_name : '';
    
        ?>
        <p>
            <table class="form-table">
                <tr>
                    <th><label for="company_name"><?php esc_html_e( 'Company Name', 'job-manager' ); ?> <span style="color: red;">*</span></label></th>
                    <td>
                        <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $company_name ); ?>" class="widefat" required>
                    </td>
                </tr>
    
                <!-- Logo Upload Field -->
                <tr>
                    <th><label for="company_logo"><?php esc_html_e( 'Company Logo', 'job-manager' ); ?></label></th>
                    <td>
                        <div id="company_logo_container" class="widefat">
                            <?php if ( $logo_url ) : ?>
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $company_name ? $company_name : 'Company Logo' ); ?>" style="max-width: 100px; max-height: 100px; display: block; margin-bottom: 10px;">
                            <?php endif; ?>
                            <button type="button" class="button" id="upload_logo_button"><?php esc_html_e( 'Upload Logo', 'job-manager' ); ?></button>
                            <input type="hidden" id="company_logo" name="company_logo" value="<?php echo esc_url( $logo_url ); ?>">
                        </div>
    
                        <div id="logo_filename_display" style="margin-top: 10px;">
                            <?php if ( $logo_filename ) : ?>
                                <p><strong><?php esc_html_e( 'Selected Logo File:', 'job-manager' ); ?></strong></p>
                                <p><?php echo esc_html( $logo_filename ); ?></p>
                                <?php if ( $logo_file_size ) : ?>
                                    <p><strong><?php esc_html_e( 'File Size:', 'job-manager' ); ?></strong></p>
                                    <p><?php echo esc_html( $logo_file_size ); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </table>
        </p>
    
        <script>
            jQuery( document ).ready( function( $ ) {
                // Open the WordPress media uploader when the "Upload Logo" button is clicked
                $( '#upload_logo_button' ).on( 'click', function( e ) {
                    e.preventDefault();
    
                    var mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: '<?php esc_js( _e( "Select or Upload Logo", "job-manager" ) ); ?>',
                        button: {
                            text: '<?php esc_js( _e( "Use this logo", "job-manager" ) ); ?>'
                        },
                        multiple: false
                    });
    
                    mediaUploader.on( 'select', function() {
                        var attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
                        $( '#company_logo' ).val( attachment.url ); // Set the URL of the selected image in the hidden input
                        $( '#company_logo_container img' ).attr( 'src', attachment.url ).show(); // Display the image preview
                        
                        // Extract the image file name and display it below the logo
                        var filename = attachment.url.split( '/' ).pop(); // Get the filename from the URL
                        $( '#logo_filename_display' ).html( '<p><strong><?php esc_js( _e( "Selected Logo File:", "job-manager" ) ); ?></strong></p><p>' + filename + '</p>' ); // Display the file name
                        
                        // Display the file size
                        var fileSize = attachment.filesize ? size_format( attachment.filesize ) : ''; // Check for filesize attribute
                        if ( fileSize ) {
                            $( '#logo_filename_display' ).append( '<p><strong><?php esc_js( _e( "File Size:", "job-manager" ) ); ?></strong></p><p>' + fileSize + '</p>' );
                        }
                    });
    
                    mediaUploader.open();
                });
            });
        </script>
        <?php
    }       

    /**
     * Renders the location (country) meta box for the job listing post edit page.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_location_meta_box( $post ) {
        // Fetch existing job listing data if available
        $job_listing = $this->get_job_listing_by_post_id( $post->ID );

        // Predefined full list of countries
        $countries = array(
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo (Democratic Republic)',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Côte d\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CW' => 'Curaçao',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island and McDonald Islands',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KR' => 'Korea (Republic of)',
            'KP' => 'Korea (Democratic People\'s Republic of)',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia (FYROM)',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia (Federated States of)',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn Islands',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Réunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthélemy',
            'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin (French part)',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SX' => 'Sint Maarten (Dutch part)',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard and Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania (United Republic of)',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Minor Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Viet Nam',
            'VG' => 'Virgin Islands (British)',
            'VI' => 'Virgin Islands (U.S.)',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        );

        // Default country if no value is set
        $selected_country = '';

        // Ensure job_listing is an object and has the job_location property
        if ( is_object( $job_listing ) && isset( $job_listing->job_location ) ) {
            $selected_country = $job_listing->job_location;
        }

        ?>
        <p>
            <table class="form-table">
                <tr>
                    <th><label for="job_location"><?php esc_html_e( 'Country', 'job-manager' ); ?></label></th>
                    <td>
                        <select id="job_location" name="job_location" class="widefat">
                            <option value=""><?php esc_html_e( 'Select a Country', 'job-manager' ); ?></option>
                            <?php
                            // Loop through countries and create option elements
                            foreach ( $countries as $code => $country ) {
                                // Check if the country is selected
                                $selected = selected( $selected_country, $code, false );
                                echo "<option value='" . esc_attr( $code ) . "' $selected>" . esc_html( $country ) . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </p>
        <?php
    }

    /**
     * Save the meta box data when the job listing is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_box_data( $post_id ) {
        // Check nonce for security
        if ( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['meta_box_nonce'], 'job_manager_meta_box_nonce' ) ) {
            return;
        }

        // Ensure it's a job listing and not an autosave
        if ( get_post_type( $post_id ) !== 'job_listing' || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Only proceed if the post ID is set and is not autosaving
        if ( ! $post_id ) {
            return;
        }

        // Retrieve and sanitize form data
        $position_title = isset( $_POST['job_position_title'] ) ? sanitize_text_field( $_POST['job_position_title'] ) : '';
        $company_name  = isset( $_POST['company_name'] ) ? sanitize_text_field( $_POST['company_name'] ) : '';
        $job_type      = isset( $_POST['job_type'] ) ? sanitize_text_field( $_POST['job_type'] ) : ''; // Fixed incorrect variable for 'job_type'
        $job_category  = isset( $_POST['job_category'] ) ? sanitize_text_field( $_POST['job_category'] ) : ''; // Fixed incorrect variable for 'job_category'
        $company_logo  = isset( $_POST['company_logo'] ) ? esc_url_raw( $_POST['company_logo'] ) : ''; // Sanitize URL for logo
        $description   = isset( $_POST['job_description'] ) ? wp_kses_post( $_POST['job_description'] ) : ''; // Sanitize HTML description
        $job_location  = isset( $_POST['job_location'] ) ? sanitize_text_field( $_POST['job_location'] ) : ''; // Sanitize location
        $expiry_date   = isset( $_POST['job_expiry_date'] ) ? sanitize_text_field( $_POST['job_expiry_date'] ) : ''; // Sanitize date
        $is_featured   = isset( $_POST['job_is_featured'] ) ? 1 : 0; // Set featured status as 1 or 0

        global $wpdb;
        $table_name = $wpdb->prefix . 'jobs';

        // Check if the job already exists in the custom table
        $existing_job = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id ) );

        // Prepare the data for insertion or update
        $job_data = array(
            'position_title' => $position_title,
            'company_name'   => $company_name,
            'job_type'       => $job_type,
            'job_category'   => $job_category,
            'company_logo'   => $company_logo,
            'description'    => $description,
            'job_location'   => $job_location,
            'expiry_date'    => $expiry_date,
            'is_featured'    => $is_featured,
            'post_id'        => $post_id, // Save the post_id in the custom table
        );

        // Prepare the format for the values to insert/update
        $formats = array(
            '%s', // position_title
            '%s', // company_name
            '%s', // job_type
            '%s', // job_category
            '%s', // company_logo
            '%s', // description
            '%s', // job_location
            '%s', // expiry_date
            '%d', // is_featured
            '%d', // post_id
        );

        // If job exists, update it; otherwise, insert a new job listing
        if ( $existing_job ) {
            // Update existing job listing in the custom table
            $wpdb->update(
                $table_name,
                $job_data,
                array( 'post_id' => $post_id ),
                $formats,
                array( '%d' )
            );
        } else {
            // Insert new job listing in the custom table
            $wpdb->insert(
                $table_name,
                $job_data,
                $formats
            );
        }
    }   

    /**
    * Display job listings on the front-end.
    *
    * @param array $atts Shortcode attributes.
    * @return string HTML output for job listings.
    */
    public function display_listings( $atts ) {
        global $wpdb;
        
        // Define default attributes for the shortcode
        $atts = shortcode_atts( array(
            'category'       => '', // Job category filter
            'posts_per_page' => 10, // Default number of posts
        ), $atts, 'job_listings' );
        
        // Prepare SQL query to fetch job listings from the custom table
        $table_name = $wpdb->prefix . 'jobs';
        
        // Build the base query
        $sql = "SELECT * FROM $table_name";
        
        // Add category filter if a category is provided
        if ( ! empty( $atts['category'] ) ) {
            $sql .= $wpdb->prepare( " WHERE job_category = %s", $atts['category'] );
        }
        
        // Add limit clause to restrict the number of posts per page
        $sql .= $wpdb->prepare( " LIMIT %d", $atts['posts_per_page'] );
        
        // Get the job listings from the database
        $results = $wpdb->get_results( $sql );
        
        // Start output
        $output = '<div class="job-listings">';
        
        if ( $results ) {
            foreach ( $results as $job ) {
                // Start the individual job listing container
                $output .= '<div class="job-listing">';
                
                // Display the job title
                $output .= '<h2>' . esc_html( $job->position_title ) . '</h2>';
                
                // Display company name
                $output .= '<p><strong>' . __( 'Company Name:', 'job-manager' ) . '</strong> ' . esc_html( $job->company_name ) . '</p>';
                
                // Display job type
                $output .= '<p><strong>' . __( 'Job Type:', 'job-manager' ) . '</strong> ' . esc_html( $job->job_type ) . '</p>';
                
                // Display experience level
                if ( ! empty( $job->experience ) ) {
                    $output .= '<p><strong>' . __( 'Experience Level:', 'job-manager' ) . '</strong> ' . esc_html( $job->job_category ) . '</p>';
                }
                
                // Display description (trimmed to 20 words)
                $output .= '<p><strong>' . __( 'Description:', 'job-manager' ) . '</strong> ' . esc_html( wp_trim_words( $job->description, 20 ) ) . '</p>';
                
                // Display 'Is Featured' status
                $output .= '<p><strong>' . __( 'Featured:', 'job-manager' ) . '</strong> ' . ( $job->is_featured ? __( 'Yes', 'job-manager' ) : __( 'No', 'job-manager' ) ) . '</p>';
                
                // Close job listing container
                $output .= '</div>';
            }
        } else {
            // No job listings found
            $output .= '<p>' . __( 'No job listings found.', 'job-manager' ) . '</p>';
        }
        
        // Close the main job listings container
        $output .= '</div>';
        
        return $output;
    }  

    /**
    * Add the 'Is Featured?' checkbox and 'Expiry Date' to the Publish section.
    *
    * @param WP_Post $post The current post object.
    */
    public function add_is_featured_checkbox_to_publish_section( $post ) {
        // Check if the current post is a job listing
        if ( 'job_listing' !== $post->post_type ) {
            return;
        }
        
        // Check user permissions to ensure they have the right capabilities
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'jobs';

        // Retrieve the job listing data from the custom jobs table based on the post ID
        $job_listing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $post->ID ) );

        // Retrieve the 'is_featured' and 'expiry_date' from the database
        $is_featured = isset( $job_listing->is_featured ) ? $job_listing->is_featured : 0;
        $expiry_date = isset( $job_listing->expiry_date ) ? $job_listing->expiry_date : '';

        // Output the fields in the Publish section
        ?>
        <div class="misc-pub-section">
            <label for="job_expiry_date"><?php _e( 'Expiry Date', 'job-manager' ); ?>:</label>
            <input type="date" id="job_expiry_date" name="job_expiry_date" value="<?php echo esc_attr( $expiry_date ); ?>" class="widefat">
        </div>
        <div class="misc-pub-section">
            <label for="job_is_featured">
                <input type="checkbox" id="job_is_featured" name="job_is_featured" value="1" <?php checked( $is_featured, 1 ); ?> />
                <?php _e( 'Is Featured?', 'job-manager' ); ?>
            </label>
        </div>
        <?php
    }
  
    /**
    * Hide Quick Edit and View actions for job listings.
    *
    * @param array   $actions The array of actions.
    * @param WP_Post $post    The current post object.
    * @return array Modified actions array.
    */
    public function hide_quick_edit_and_view_actions( $actions, $post ) {
        // Check if the post type is 'job_listing'
        if ( 'job_listing' === $post->post_type ) {
            // Remove the Quick Edit and View actions
            if ( isset( $actions['inline hide-if-no-js'] ) ) {
                unset( $actions['inline hide-if-no-js'] ); // Quick Edit
            }
            if ( isset( $actions['view'] ) ) {
                unset( $actions['view'] ); // View
            }
        }
        
        return $actions;
    }

    /**
    * Add custom columns to the job listings admin table.
    *
    * @param array $columns Default columns.
    * @return array Modified columns.
    */
    public function add_custom_columns( $columns ) {
        // Remove the default 'title' and 'date' columns
        unset( $columns['title'] );
        unset( $columns['date'] );

        // Add custom columns after the 'title' column
        $columns['job_position_title'] = __( 'Position Title', 'job-manager' );
        $columns['job_company_name']   = __( 'Company Name', 'job-manager' );
        $columns['job_is_featured']    = __( 'Is Featured', 'job-manager' );
        $columns['job_type']           = __( 'Job Type', 'job-manager' );
        $columns['expiry_date']        = __( 'Expires', 'job-manager' );
        $columns['applications']       = __( 'Applications', 'job-manager' );

        return $columns;
    }

    /**
    * Populate custom columns with data for job listings.
    *
    * @param string $column The column name.
    * @param int    $post_id The post ID.
    */
    public function populate_custom_columns( $column, $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jobs';
        $applications_table = $wpdb->prefix . 'job_applications';
    
        // Fetch job listing data from the custom table
        $job_listing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $post_id ) );
    
        // Ensure job_listing exists to avoid errors
        if ( ! $job_listing ) {
            return; // No data, exit early
        }
    
        switch ( $column ) {
            case 'job_position_title':
                // Display the position title and make it a link to edit the post
                echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '"><strong>' . esc_html( $job_listing->position_title ) . '</strong></a>';
                break;
    
            case 'job_company_name':
                // Output the company name
                echo esc_html( $job_listing->company_name );
                break;
    
            case 'job_is_featured':
                // Display check mark for 'Yes' and red cross for 'No'
                if ( $job_listing->is_featured ) {
                    echo '<span style="color: green;">&#10004;</span>'; // Check mark
                } else {
                    echo '<span style="color: red;">&#10008;</span>'; // Cross
                }
                break;
    
            case 'job_type':
                // Output the job type
                echo esc_html( $job_listing->job_type );
                break;
    
            case 'expiry_date':
                // Output the expiry date, and additional info about days left/overdue
                if ( ! empty( $job_listing->expiry_date ) ) {
                    echo esc_html( $job_listing->expiry_date );
    
                    $expiry_date = strtotime( $job_listing->expiry_date );
                    $current_date = time();
                    $days_left = ceil( ( $expiry_date - $current_date ) / ( 60 * 60 * 24 ) );
    
                    // Display the number of days left or expired
                    if ( $days_left > 0 ) {
                        echo '<br><span style="color: blue;">' . $days_left . ' ' . __( 'Days Left', 'job-manager' ) . '</span>';
                    } elseif ( $days_left == 0 ) {
                        echo '<br><span style="color: orange;">' . __( 'Expires Today', 'job-manager' ) . '</span>';
                    } else {
                        echo '<br><span style="color: red;">' . abs( $days_left ) . ' ' . __( 'Days Overdue', 'job-manager' ) . '</span>';
                    }
                }
                break;
    
            case 'applications':
                // Get the number of applications for the current job
                $application_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $applications_table WHERE job_id = %d", $post_id ) );
    
                // Output the count of applications
                echo esc_html( $application_count );
                break;
    
            default:
                // If the column is not recognized, we can leave it empty or handle it as needed.
                break;
        }
    }    

    /**
    * Make the job_position_title column sortable.
    */
    public function make_column_sortable( $columns ) {
        // Make the 'job_position_title' column sortable
        $columns['job_position_title'] = 'position_title';
        return $columns;
    }

    /**
    * Handle sorting by job_position_title.
    */
    public function sort_custom_columns( $query ) {
        // Only apply sorting for the admin dashboard and job_listing post type
        if ( is_admin() && $query->is_main_query() && 'job_listing' === $query->get( 'post_type' ) ) {
            // Check if we are sorting by the custom column 'job_position_title'
            if ( 'position_title' === $query->get( 'orderby' ) ) {
                $query->set( 'orderby', 'position_title' ); // Use WordPress post title sorting by default
            }
        }
    }

    /**
    * Add custom SQL for sorting job listings by position_title.
    */
    public function add_custom_sql_for_sorting( $join, $query ) {
        global $wpdb;

        // Check if we are sorting by 'position_title' for job listings
        if ( 'job_listing' === $query->get( 'post_type' ) && 'position_title' === $query->get( 'orderby' ) ) {
            // Join custom table to get the position_title for sorting
            $join .= " LEFT JOIN {$wpdb->prefix}jobs AS jobs ON jobs.post_id = {$wpdb->posts}.ID";
        }

        return $join;
    }

    /**
    * Add custom ORDER BY for sorting job listings by position_title.
    */
    public function add_custom_orderby_for_sorting( $orderby, $query ) {
        global $wpdb;

        // Check if we are sorting by 'position_title' for job listings
        if ( 'job_listing' === $query->get( 'post_type' ) && 'position_title' === $query->get( 'orderby' ) ) {
            // Add custom orderby logic to sort by the position_title column in the custom table
            $orderby = "jobs.position_title " . strtoupper( $query->get( 'order' ) );
        }

        return $orderby;
    }

    /**
    * Run the plugin (calls the necessary hooks).
    */
    public function run() {
        // Add the custom columns to the job listings table
        add_filter( 'manage_job_listing_posts_columns', array( $this, 'add_custom_columns' ) );

        // Populate the custom columns with data
        add_action( 'manage_job_listing_posts_custom_column', array( $this, 'populate_custom_columns' ), 10, 2 );

        // Make the job_position_title column sortable
        add_filter( 'manage_edit-job_listing_sortable_columns', array( $this, 'make_column_sortable' ) );

        // Handle sorting
        add_action( 'pre_get_posts', array( $this, 'sort_custom_columns' ) );

        // Add custom JOIN for sorting
        add_filter( 'posts_join', array( $this, 'add_custom_sql_for_sorting' ), 10, 2 );

        // Add custom ORDER BY for sorting
        add_filter( 'posts_orderby', array( $this, 'add_custom_orderby_for_sorting' ), 10, 2 );
    }
}
