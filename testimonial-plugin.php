<?php
/*
Plugin Name: Enhanced Testimonial Plugin
Description: A plugin to add and display testimonials with full name, title, content, star rating, and video file support.
Version: 1.5
Author: Your Name
*/

// Add custom CSS for testimonials
function testimonial_plugin_custom_styles() {
    echo '
    <style>
    span.page-numbers.current {
    background: #1595CE;
    color: #ffffff;
    border-color: #1595CE;
    padding: 11px;
}

a.page-numbers {
    color: #5e5e5e;
    background: #ffffff;
    border: 2px solid #bbbbbb;
    padding: 11px;
}
.tablenav.bottom {
    position: relative;
    top: 15px;
}
    .moses1 .form-field {
        display: inline-block;
        width: 49%; 
        vertical-align: top;
        margin-right: 1%;
        float: left;
    }
    
    .moses1 .form-field input {
        width: 100%;
    }
    
        i.fa.fa-star {
        font-size: 20px;
        letter-spacing: 2px;
    }
    span.page-numbers {
        background: #1595CE;
        color: #ffffff;
        border-color: #1595CE;
        padding: 11px;
    }
    a.prev.page-numbers {
        background: #1595CE;
        color: #ffffff;
        border-color: #1595CE;
        padding: 11px;
    }
    a.page-numbers {
        color: #5e5e5e;
        background: #ffffff;
        border: 2px solid #bbbbbb;
        padding: 11px;
    }

   .testimonial {
        background: #ffffff;
        border: 1px solid #e3e3e3;
        border-radius: 5px;
    }
    .testimonial p {
        padding-bottom: 1em;
        font-size: 16px;
        line-height: 26px;
        text-transform: none;
        text-align: center;
        letter-spacing: normal;
        font-weight: 400;
        font-style: normal;
        margin: 0 0 20px 0;
    }
    .testimonial h4 {
        font-size: 16px;
        text-align: center;
    }
    .testimonial-video {
        text-align: center;
        width: 140px;
        background-color: #813C88;
        color: #fff !important;
        margin: 0px 0 0 80px;
        padding: 5px;
    }
    .testimonial-video a {
    color: #fff;
}
    input#full_name {
        
            height: 38px;
            padding: 10px;
    }
    input#email {
        
            height: 38px;
            padding: 10px;
    }
    textarea#text_testimonial {
        width: 100%;
        height: 60px;
        padding: 10px;
    }
    input[type="submit"] {
        font-size: 16px;
        background-color: #813c88;
        color: #fff;
        padding: 8px;
    }
 
    </style>
    ';
}
add_action('wp_head', 'testimonial_plugin_custom_styles');


session_start();

// Enqueue Magnific Popup styles and scripts
function enqueue_magnific_popup_assets() {
    wp_enqueue_style('magnific-popup-css', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css');
    wp_enqueue_script('magnific-popup-js', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_magnific_popup_assets');

defined('ABSPATH') or die('No script kiddies please!');

// Create database table on plugin activation
register_activation_hook(__FILE__, 'testimonial_plugin_create_table');

function testimonial_plugin_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        text_testimonial text NOT NULL,
        video_file varchar(255) DEFAULT '' NOT NULL,
        status varchar(20) DEFAULT 'pending' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'testimonial_plugin_enqueue_scripts');
function testimonial_plugin_enqueue_scripts() {
    wp_enqueue_style('testimonial-plugin-style', plugins_url('style.css', __FILE__));
}

// Hook to add menu item in admin
add_action('admin_menu', 'testimonial_plugin_menu');

function testimonial_plugin_menu() {
    add_menu_page(
        'Testimonials',
        'Testimonials',
            'edit_posts', // Change this to a capability that the user has
        'testimonial-plugin',
        'testimonial_plugin_page',
        'dashicons-testimonial',
        25
    );
}
if (isset($_SESSION['testimonial_error'])) {
    echo '<p class="error">' . $_SESSION['testimonial_error'] . '</p>';
    unset($_SESSION['testimonial_error']);
}

// Display the admin page
function testimonial_plugin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';

    // Handle actions for editing
    if (isset($_GET['action']) && $_GET['action'] == 'edit') {
        $id = intval($_GET['id']);
        if ($id) {
            if (isset($_POST['update_testimonial'])) {
                // Update testimonial
                $full_name = sanitize_text_field($_POST['full_name']);
                $email = sanitize_email($_POST['email']);
                $text_testimonial = sanitize_textarea_field($_POST['text_testimonial']);
                $video_file = '';

                if (!empty($_FILES['video_file']['name'])) {
                    $uploaded_file = $_FILES['video_file'];
                    $allowed_formats = array('mp4', 'mov', 'wmv', 'avi', 'avchd', 'flv', 'f4v', 'swf', 'mkv', 'webm');
                    $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

                    if (!in_array($file_extension, $allowed_formats)) {
                        echo '<p>Invalid video format. Please upload a video file in one of the following formats: MP4, MOV, WMV, AVI, AVCHD, FLV, F4V, SWF, MKV, WEBM.</p>';
                    } else {
                        $upload = wp_handle_upload($uploaded_file, array('test_form' => false));
                        if ($upload && !isset($upload['error'])) {
                            $video_file = esc_url_raw($upload['url']);
                        } else {
                            echo '<p>Video upload failed: ' . esc_html($upload['error']) . '</p>';
                        }
                    }
                }

                $wpdb->update(
                    $table_name,
                    array(
                        'full_name' => $full_name,
                        'email' => $email,
                        'text_testimonial' => $text_testimonial,
                        'video_file' => $video_file
                    ),
                    array('id' => $id),
                    array('%s', '%s', '%s', '%s'),
                    array('%d')
                );

                echo '<p>Testimonial updated successfully.</p>';
                wp_redirect(admin_url('admin.php?page=testimonial-plugin'));
                exit;
            }

            $testimonial = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        }
        ?>
        <div class="wrap">
            <h1>Edit Testimonial</h1>
            <form method="post" enctype="multipart/form-data">
                <p>
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo esc_attr($testimonial->full_name); ?>" required>
                </p>
                <p>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($testimonial->email); ?>" required>
                </p>
                <p>
                    <label for="text_testimonial">Testimonial:</label>
                    <textarea id="text_testimonial" name="text_testimonial" required><?php echo esc_textarea($testimonial->text_testimonial); ?></textarea>
                </p>
                <p>
                    <label for="video_file">Upload Video (optional):</label>
                    <input type="file" id="video_file" name="video_file" accept="video/*">
                    <?php if ($testimonial->video_file): ?>
                        <p>Current Video: <a href="<?php echo esc_url($testimonial->video_file); ?>" target="_blank">Watch Video</a></p>
                    <?php endif; ?>
                </p>
                <p>
                    <input type="submit" name="update_testimonial" value="Update Testimonial">
                </p>
            </form>
        </div>
        <?php
        return;
    }

 // Handle Bulk Actions
    if (isset($_POST['bulk_action'])) {
        $action = $_POST['bulk_action'];
        $ids = isset($_POST['testimonial_ids']) ? $_POST['testimonial_ids'] : array();

        if (!empty($ids)) {
            if ($action === 'delete') {
                // Perform bulk delete
                foreach ($ids as $id) {
                    $wpdb->delete($table_name, array('id' => intval($id)), array('%d'));
                }
                echo '<div class="updated"><p>Selected testimonials have been deleted.</p></div>';
            }
        }
    }

    // Fetch testimonials
    $testimonials = $wpdb->get_results("SELECT * FROM $table_name");

    // Pagination setup
    $testimonials_per_page = 12;
    $total_testimonials = count($testimonials); // Total number of testimonials
    $total_pages = ceil($total_testimonials / $testimonials_per_page);
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $testimonials_per_page;

    // Slice the testimonials array to get only the testimonials for the current page
    $paged_testimonials = array_slice($testimonials, $offset, $testimonials_per_page);

    $sno = $offset + 1; // Start S.NO from the current page offset
    ?>
    <div class="wrap">
        <h1>Testimonials</h1>
        <form method="post">
            <p>
                <select name="bulk_action">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" value="Apply" class="button action">
            </p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all" /></th>
                        <th>S.NO</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Your Feedback</th>
                        <th>Video testimonial</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($paged_testimonials)) : ?>
                        <?php foreach ($paged_testimonials as $testimonial) : ?>
                            <tr>
                                <td><input type="checkbox" name="testimonial_ids[]" value="<?php echo esc_attr($testimonial->id); ?>" /></td>
                                <td><?php echo esc_html($sno++); ?></td>
                                <td><?php echo esc_html($testimonial->full_name); ?></td>
                                <td><?php echo esc_html($testimonial->email); ?></td>
                                <td><?php echo esc_html($testimonial->text_testimonial); ?></td>
                                <td>
                                    <?php if (!empty($testimonial->video_file)) : ?>
                                        <a href="<?php echo esc_url($testimonial->video_file); ?>" target="_blank">Watch Video</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($testimonial->status); ?></td>
                                <td>
                                    <?php if ($testimonial->status == 'pending') : ?>
                                        <a style="background-color: #813C88; padding:5px; color:#fff;" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=approve_testimonial&id=' . $testimonial->id), 'approve_testimonial_' . $testimonial->id); ?>">Approve</a>
                                    <?php endif; ?>
                                    <a style="background-color: red; padding:5px; color:#fff;" href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_testimonial&id=' . $testimonial->id), 'delete_testimonial_' . $testimonial->id); ?>">Delete</a>
                                    <a style="background-color: green; padding:5px; color:#fff;" href="<?php echo esc_url(add_query_arg('action', 'edit', admin_url('admin.php?page=testimonial-edit&id=' . $testimonial->id))); ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="8">No testimonials found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
            ));
            ?>
        </div>
    </div>
    </div>
    <script>
        // Select All checkbox logic
        document.getElementById('select_all').onclick = function () {
            let checkboxes = document.getElementsByName('testimonial_ids[]');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        };
    </script>

<?php }

// Approve testimonial action
add_action('admin_post_approve_testimonial', 'approve_testimonial');

function approve_testimonial() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $id = intval($_GET['id']);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'approve_testimonial_' . $id)) {
        wp_die('Nonce verification failed.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';

    $wpdb->update(
        $table_name,
        array('status' => 'approved'),
        array('id' => $id),
        array('%s'),
        array('%d')
    );

    $testimonial = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

   // Send approval email to visitor
        wp_mail(
            $testimonial->email,
            'Your testimonial has been approved!',
            'Your testimonial has been approved!<br>Here’s your 50% off coupon: <strong>Cost $199</strong>. Enjoy!<br><br>
            <img src="https://massageandmeditation.com/wp-content/uploads/2024/08/massageandmeditation-logo-300x156.jpeg" alt="Massage and Meditation Logo" width="500" height="260" />',
            array('Content-Type: text/html; charset=UTF-8')
        );


    wp_redirect(admin_url('admin.php?page=testimonial-plugin'));
    exit;
}

// Delete testimonial action
add_action('admin_post_delete_testimonial', 'delete_testimonial');

function delete_testimonial() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $id = intval($_GET['id']);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_testimonial_' . $id)) {
        wp_die('Nonce verification failed.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';

    $wpdb->delete(
        $table_name,
        array('id' => $id),
        array('%d')
    );

    wp_redirect(admin_url('admin.php?page=testimonial-plugin'));
    exit;
}

// Shortcode to display testimonial form
add_shortcode('testimonial_form', 'display_testimonial_form_shortcode');

function display_testimonial_form_shortcode() {
    ob_start();

    if (isset($_POST['submit_testimonial'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'testimonials';

        // Nonce verification
        if (!isset($_POST['testimonial_nonce']) || !wp_verify_nonce($_POST['testimonial_nonce'], 'submit_testimonial')) {
            echo '<p>Nonce verification failed.</p>';
            return ob_get_clean();
        }

        $full_name = sanitize_text_field($_POST['full_name']);
        $email = sanitize_email($_POST['email']);
        $text_testimonial = sanitize_textarea_field($_POST['text_testimonial']);
        $video_file = '';

        // Handle file upload
        if (!empty($_FILES['video_file']['name'])) {
            $uploaded_file = $_FILES['video_file'];

            // Set allowed video formats
            $allowed_formats = array('mp4', 'mov', 'wmv', 'avi', 'avchd', 'flv', 'f4v', 'swf', 'mkv', 'webm');
            $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));

            // Validate video file format
            if (!in_array($file_extension, $allowed_formats)) {
                echo '<p>Invalid video format. Please upload a video file in one of the following formats: MP4, MOV, WMV, AVI, AVCHD, FLV, F4V, SWF, MKV, WEBM.</p>';
                return ob_get_clean();
            }

            $upload = wp_handle_upload($uploaded_file, array('test_form' => false));

            if ($upload && !isset($upload['error'])) {
                $video_file = esc_url_raw($upload['url']);
            } else {
                echo '<p>Video upload failed: ' . esc_html($upload['error']) . '</p>';
                return ob_get_clean();
            }
        }



// Insert testimonial into the database
$wpdb->insert( 
    $table_name,
    array(
        'full_name' => $full_name,
        'email' => $email,
        'text_testimonial' => $text_testimonial,
        'video_file' => $video_file,
        'status' => 'pending'
    ),
    array('%s', '%s', '%s', '%s', '%s')
);

// Send confirmation email to visitor
wp_mail(
    $email,
    'Thank you for your testimonial submission',
    'Thank you for sharing your testimonial with us. Here is your coupon:<br><br>
    <img src="https://massageandmeditation.com/wp-content/uploads/2024/08/massageandmeditation_logo.jpeg" alt="Massage and Meditation Logo" width="500" height="280" />',
    array('Content-Type: text/html; charset=UTF-8')
);

// Send notification email to admin
$additional_email = 'smtpprotest@gmail.com'; // Additional email address
wp_mail(
    array($admin_email, $additional_email),
    'New Testimonial Submission',
    'A new testimonial has been submitted by ' . $full_name . '.<br><br>
    Full Name: ' . $full_name . '<br>
    Email: ' . $email . '<br>
    Testimonial: ' . nl2br($text_testimonial) . '<br>
    Video File: ' . $video_file . '<br><br>
    Please review the submission.',
    array('Content-Type: text/html; charset=UTF-8')
);


	}

    ?>
    
	<?php
session_start(); // Start the session

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION["msg"] = "Thank you for your submission!";
}

// Display message and form
if (isset($_SESSION["msg"])) {
    echo $_SESSION["msg"];
    unset($_SESSION["msg"]);
} else {
    ?>
   <form method="post" enctype="multipart/form-data" class="moses1">
        <?php wp_nonce_field('submit_testimonial', 'testimonial_nonce'); ?>
        <p class="form-field">
            <input type="text" id="full_name" name="full_name" placeholder="Full Name" required>
        </p>
        <p class="form-field">
            <input type="email" id="email" name="email" placeholder="Email" required>
            <span id="email-error"></span>
        </p>
        <p>
            <textarea id="text_testimonial" name="text_testimonial" placeholder="Write your testimonial here..." required></textarea>
        </p>
        <p>
            <label for="video_file">Upload Video (optional):</label>
            <input type="file" id="video_file" name="video_file" accept="video/*">
        </p>
        <p>
            <input type="submit" name="submit_testimonial" value="Submit Testimonial">
        </p>
    </form>

    <?php
}
?>
	
    <?php
    return ob_get_clean();
}

add_shortcode('display_testimonials', 'display_testimonials_shortcode');

function display_testimonials_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'testimonials';
    $per_page = 12;

    // Get the current page number from query, default is 1
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

    // Calculate the offset
    $offset = ($paged - 1) * $per_page;

    // Get the total number of approved testimonials
    $total_testimonials = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");

    // Fetch the testimonials for the current page
    $testimonials = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}testimonials WHERE status = 'approved' ORDER BY id ASC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));

    $output = '<div class="testimonial-container" style="display: flex; flex-wrap: wrap; gap: 20px;">';

    foreach ($testimonials as $testimonial) {
        $output .= '<div class="testimonial" style="flex: 0 0 30%; border: 1px solid #ddd; padding: 15px; box-shadow: 0 0 5px rgba(0,0,0,0.1);">';
        $output .= '<p>' . esc_html($testimonial->text_testimonial) . '</p>';
        $output .= '<h4><strong>' . esc_html($testimonial->full_name) . '</strong></h4>';
        $output .= '<p><strong>' . esc_html($testimonial->email) . '</strong></p>';

        // Add 5 yellow stars centered
        $output .= '<div style="text-align: center; margin: 10px 0;">';
        $output .= '<i class="fa fa-star" style="color: #F1B914;"></i>';
        $output .= '<i class="fa fa-star" style="color: #F1B914;"></i>';
        $output .= '<i class="fa fa-star" style="color: #F1B914;"></i>';
        $output .= '<i class="fa fa-star" style="color: #F1B914;"></i>';
        $output .= '<i class="fa fa-star" style="color: #F1B914;"></i>';
        $output .= '</div>'; 

        if (!empty($testimonial->video_file)) {
            $output .= '<div class="testimonial-video">';
            $output .= '<a class="popup-video" href="' . esc_url($testimonial->video_file) . '">Watch Video</a>';
            $output .= '</div>';
        }
        $output .= '</div>'; // End of .testimonial
    }

    $output .= '</div>'; // End of .testimonial-container

    // Add pagination
    $total_pages = ceil($total_testimonials / $per_page);
    if ($total_pages > 1) {
        $output .= '<div class="pagination" style="text-align: center; margin-top: 20px;">';
        $output .= paginate_links(array(
            'base'    => add_query_arg('paged', '%#%'),
            'format'  => '?paged=%#%',
            'current' => $paged,
            'total'   => $total_pages,
            'prev_text' => __('« Prev'),
            'next_text' => __('Next »'),
        ));
        $output .= '</div>';
    }

    // Initialize the Magnific Popup for video links
    $output .= '
    <script>
    jQuery(document).ready(function($) {
        $(".popup-video").magnificPopup({
            type: "iframe",
            mainClass: "mfp-fade",
            removalDelay: 160,
            preloader: false,
            fixedContentPos: false
        });
    });
    </script>';

    return $output;
}