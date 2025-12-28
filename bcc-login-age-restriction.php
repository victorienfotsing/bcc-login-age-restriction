<?php
/*
Plugin Name: BCC Login Age Restriction
Plugin URI:  https://github.com/victorienfotsing/bcc-login-age-restriction
Description: Restrict access to a specific post by age using BCC Login token claims.
Version:     0.1.0
Author:      Victorien Fotsing
Text Domain: bcc-login-age-restriction
Extension plugin for https://github.com/bcc-code/bcc-wp
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Defaults for age interval when a post doesn't declare values
const blar_default_min_age = 0;
const blar_default_max_age = 36;

/**
 * Prevent activation if the required BCC Login plugin is not present.
 */
function blar_on_activation() {
    if ( ! class_exists( 'BCC_Login_Token_Utility' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( __( 'BCC Login Age Restriction requires the BCC Login plugin to be installed and active.', 'bcc-login-age-restriction' ) );
    }
}
register_activation_hook( __FILE__, 'blar_on_activation' );

/**
 * Show an admin notice if the dependency is missing while the plugin is active.
 */
function blar_dependency_admin_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    if ( class_exists( 'BCC_Login_Token_Utility' ) ) {
        return;
    }

    echo '<div class="notice notice-error"><p>' . esc_html__( 'BCC Login Age Restriction plugin requires the BCC Login plugin to be installed and active. Please install/activate it.', 'bcc-login-age-restriction' ) . '</p></div>';
}
add_action( 'admin_notices', 'blar_dependency_admin_notice' );

function blar_on_template_redirect() {
    $post_id = get_the_ID();
    if ( empty( $post_id ) ) {
        return;
    }

    // Determine whether this post has age restriction enabled (post meta takes precedence)
    $meta_restricted = get_post_meta( $post_id, 'blar_age_restriction', true );
    $is_restricted = ( '1' === $meta_restricted || 1 === $meta_restricted || true === $meta_restricted );

    if ( $is_restricted ) {
        $meta_min_age = get_post_meta( $post_id, 'blar_min_age', true );
        $meta_max_age = get_post_meta( $post_id, 'blar_max_age', true );
        $min_age = ( '' !== $meta_min_age && null !== $meta_min_age ) ? intval( $meta_min_age ) : blar_default_min_age;
        $max_age = ( '' !== $meta_max_age && null !== $meta_max_age ) ? intval( $meta_max_age ) : blar_default_max_age;
        if ( ! blar_is_in_age_range( $min_age, $max_age ) ) {
            return blar_not_allowed_to_view_page();
        }
    }
}

add_action( 'template_redirect', 'blar_on_template_redirect', 1 );

function blar_filter_menu_items( $items ) {
    foreach ( $items as $key => $item ) {
        $item_id = isset( $item->object_id ) ? intval( $item->object_id ) : 0;
        if ( empty( $item_id ) ) {
            continue;
        }

        $meta_restricted = get_post_meta( $item_id, 'blar_age_restriction', true );
        $is_restricted = ( '1' === $meta_restricted || 1 === $meta_restricted || true === $meta_restricted );
        if ( $is_restricted ) {
            $meta_min_age = get_post_meta( $item_id, 'blar_min_age', true );
            $meta_max_age = get_post_meta( $item_id, 'blar_max_age', true );
            $min_age = ( '' !== $meta_min_age && null !== $meta_min_age ) ? intval( $meta_min_age ) : blar_default_min_age;
            $max_age = ( '' !== $meta_max_age && null !== $meta_max_age ) ? intval( $meta_max_age ) : blar_default_max_age;
            if ( ! blar_is_in_age_range( $min_age, $max_age ) ) {
                unset( $items[ $key ] );
            }
        }
    }

    return $items;
}

add_filter( 'wp_get_nav_menu_items', 'blar_filter_menu_items', 21 );

/**
 * Admin: add a column to the Pages list for Age Restriction
 */
function blar_add_pages_column( $columns ) {
    $columns['blar_age_restriction'] = __( 'Age Restr.', 'bcc-login' );
    return $columns;
}

add_filter( 'manage_pages_columns', 'blar_add_pages_column' );

/**
 * Render the Age Restriction column and include hidden meta for Quick Edit JS
 */
function blar_render_pages_column( $column, $post_id ) {
    if ( 'blar_age_restriction' !== $column ) {
        return;
    }

    $restricted = get_post_meta( $post_id, 'blar_age_restriction', true );
    $min_age = get_post_meta( $post_id, 'blar_min_age', true );
    $max_age = get_post_meta( $post_id, 'blar_max_age', true );
    $min_age = ( '' !== $min_age && null !== $min_age ) ? intval( $min_age ) : blar_default_min_age;
    $max_age = ( '' !== $max_age && null !== $max_age ) ? intval( $max_age ) : blar_default_max_age;

    if ( $restricted ) {
        echo esc_html( sprintf( '%d–%d', $min_age, $max_age ) );
    } else {
        echo '&mdash;';
    }

    printf(
        '<span class="blar-meta" style="display:none" data-restricted="%s" data-min-age="%d" data-max-age="%d"></span>',
        $restricted ? '1' : '0',
        esc_attr( $min_age ),
        esc_attr( $max_age )
    );
}

add_action( 'manage_pages_custom_column', 'blar_render_pages_column', 10, 2 );

/**
 * Output Quick Edit fields for the Age Restriction column
 */
function blar_quick_edit_custom_box( $column_name, $post_type ) {
    if ( 'blar_age_restriction' !== $column_name || 'page' !== $post_type ) {
        return;
    }

    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <input type="checkbox" name="blar_age_restriction" value="1" /> <?php esc_html_e( 'Age restricted', 'bcc-login' ); ?>
            </label>
            <label class="inline-edit-group">
                <span class="title"><?php esc_html_e( 'Min age', 'bcc-login' ); ?></span>
                <input type="number" name="blar_min_age" value="<?php echo esc_attr( blar_default_min_age ); ?>" min="0" style="width:60px;" />
            </label>
            <label class="inline-edit-group">
                <span class="title"><?php esc_html_e( 'Max age', 'bcc-login' ); ?></span>
                <input type="number" name="blar_max_age" value="<?php echo esc_attr( blar_default_max_age ); ?>" min="0" style="width:60px;" />
            </label>
            <?php wp_nonce_field( 'blar_quick_edit_nonce', 'blar_quick_edit_nonce', false ); ?>
        </div>
    </fieldset>
    <?php
}

add_action( 'quick_edit_custom_box', 'blar_quick_edit_custom_box', 10, 2 );

/**
 * Print JS to populate Quick Edit fields when inline editing
 */
function blar_quick_edit_js() {
    $screen = get_current_screen();
    if ( ! $screen || 'edit-page' !== $screen->id ) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        var wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function( id ){
            wp_inline_edit.apply( this, arguments );
            var post_id = 0;
            if ( typeof(id) == 'object' ) {
                post_id = parseInt( this.getId( id ) );
            }
            if ( post_id > 0 ) {
                var row = $( '#post-' + post_id );
                var meta = row.find( '.blar-meta' );
                var restricted = meta.attr( 'data-restricted' ) == '1';
                var min_age = meta.attr( 'data-min-age' ) || <?php echo intval( blar_default_min_age ); ?>;
                var max_age = meta.attr( 'data-max-age' ) || <?php echo intval( blar_default_max_age ); ?>;
                var $inline = $( '#inline-edit' );
                $inline.find( 'input[name="blar_age_restriction"]' ).prop( 'checked', restricted );
                $inline.find( 'input[name="blar_min_age"]' ).val( min_age );
                $inline.find( 'input[name="blar_max_age"]' ).val( max_age );
            }
        };
    });
    </script>
    <?php
}

add_action( 'admin_print_footer_scripts-edit.php', 'blar_quick_edit_js' );

/**
 * Save Quick Edit fields
 */
function blar_save_quick_edit( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( 'page' !== $post->post_type ) {
        return;
    }
    if ( isset( $_POST['blar_quick_edit_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['blar_quick_edit_nonce'] ) ), 'blar_quick_edit_nonce' ) ) {
        return;
    }

    $restricted = isset( $_POST['blar_age_restriction'] ) ? '1' : '0';
    update_post_meta( $post_id, 'blar_age_restriction', $restricted );

    if ( isset( $_POST['blar_min_age'] ) ) {
        $min_age = intval( $_POST['blar_min_age'] );
        update_post_meta( $post_id, 'blar_min_age', $min_age );
    }
    if ( isset( $_POST['blar_max_age'] ) ) {
        $max_age = intval( $_POST['blar_max_age'] );
        update_post_meta( $post_id, 'blar_max_age', $max_age );
    }
}

add_action( 'save_post', 'blar_save_quick_edit', 10, 2 );

/*
Returns `true` if the current user's age is <= `blar_max_age`,
otherwise the function returns `false`.
*/
function blar_is_in_age_range( $min_age = null, $max_age = null ) {
    // Check if user is logged in to Wordpress
    if ( ! is_user_logged_in() ) {
        return false;
    }

    // Check if oidc token is available
    if ( ! isset( $_COOKIE['oidc_token_id'] ) ) {
        return false;
    }

    // Check if oidc token is valid (still stored in transient)
    $token_id = $_COOKIE['oidc_token_id'];
    if ( ! empty( $token_id ) ) {
        $token = get_transient( 'oidc_id_token_' . $token_id );
    }
    if ( empty( $token ) ) {
        return false;
    }

    // Guard: ensure token utility exists to avoid fatal errors
    if ( ! class_exists( 'BCC_Login_Token_Utility' ) || ! method_exists( 'BCC_Login_Token_Utility', 'get_token_claims' ) ) {
        return false;
    }

    // Get token claims
    $claims = BCC_Login_Token_Utility::get_token_claims( $token );

    if ( empty( $claims ) || empty( $claims['birthdate'] ) ) {
        return false;
    }

    $birthdate = date_create( $claims['birthdate'] );
    $current_date = date_create();
    $diff = date_diff( $birthdate, $current_date );
    $age = $diff->y;
    $min = ( null !== $min_age && '' !== $min_age ) ? intval( $min_age ) : blar_default_min_age;
    $max = ( null !== $max_age && '' !== $max_age ) ? intval( $max_age ) : blar_default_max_age;
    return ( $age >= $min && $age <= $max );
}

function blar_not_allowed_to_view_page() {
    wp_die(
        sprintf(
            '%s<br><a href="%s">%s</a>',
            __( 'Sorry, you are not allowed to view this page.', 'bcc-login' ),
            site_url(),
            __( 'Go to the front page', 'bcc-login' )
        ),
        __( 'Unauthorized' ),
        array(
            'response' => 401,
        )
    );
}
