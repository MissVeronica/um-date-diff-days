<?php
/**
 * Plugin Name:     Ultimate Member - Date Difference in Days
 * Description:     Extension to Ultimate Member to display number of days until/after a date from/to today either by UM Form fields or a Shortcode.
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica?tab=repositories
 * Plugin URI:      https://github.com/MissVeronica/um-date-diff-days
 * Update URI:      https://github.com/MissVeronica/um-date-diff-days
 * Text Domain:     ultimate-member
 * UM version:      2.10.2
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Date_Diff_Days {

    public $prefix           = 'um_date_diff_days_';
    public $shortcode        = array();
    public $um_hook1_removed = false;
    public $um_hook2_removed = false;
    public $default          = array(
                                        'one_day_before' => 'one day before %s',
                                        'days_before'    => '%d days before %s',
                                        'one_day_after'  => 'one day after %s',
                                        'days_after'     => '%d days after %s',
                                        'today'          => 'today %s',
                                        'date_format'    => 'M j',
                                        'limit_before'   => 30,
                                        'limit_after'    => 30,
                                    );

    public function __construct() {

        add_shortcode( 'date_diff_days', array( $this, 'date_diff_days_shortcode' ));

        add_filter( 'um_profile_field_filter_hook__date',            array( $this, 'um_profile_field_filter_hook__diff_date' ), 80, 2 );
        add_filter( 'um_profile_field_filter_hook__user_registered', array( $this, 'um_profile_field_filter_hook__diff_date' ), 90, 2 );

        if ( is_admin()) {

            define( 'Plugin_Basename_DDD', plugin_basename( __FILE__ ));

            add_filter( 'um_settings_structure', array( $this, 'um_settings_structure_date_diff_days' ), 10, 1 );
            add_filter( 'plugin_action_links_' . Plugin_Basename_DDD, array( $this, 'plugin_settings_link' ), 10 );
        }
    }

    public function plugin_settings_link( $links ) {

        $url = get_admin_url() . 'admin.php?page=um_options&tab=appearance';
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings' ) . '</a>';

        return $links;
    }

    public function date_diff_days_shortcode( $atts, $content ) {

        $value = '';
        if ( isset( $atts['meta_key'] ) && ! empty( $atts['meta_key'] )) {

            $meta_key = sanitize_key( $atts['meta_key'] );
            $value    = um_user( $meta_key );

            if ( ! empty( $value )) {

                $this->shortcode = $atts;
                $value = $this->find_diff_days( $value, $meta_key, 1 );
                $this->shortcode = array();
            } 
        }

        return $value;
    }

    public function text_message( $type ) {

        $text = ( isset( $this->shortcode[$type] ) && ! empty( $this->shortcode[$type] )) ?
                                    sanitize_text_field( $this->shortcode[$type] ) :
                                    sanitize_text_field( UM()->options()->get( $this->prefix . $type ));

        return ( ! empty( $text )) ? $text : $this->default[$type];
    }

    public function compare_sprintf( $type, $days, $diff_day ) {

        $text = $this->text_message( $type );

        $s = strpos( $text, '%s' );
        $d = strpos( $text, '%d' );

        if ( $s === false ) {

            return ( $d === false ) ? $text : sprintf( $text, $days );

        } else {

            return ( $d === false ) ? sprintf( $text, $diff_day ) : (( $s < $d ) ? sprintf( $text, $diff_day, $days ) :
                                                                                   sprintf( $text, $days, $diff_day )
                                                                    );
        }
    }

    public function um_profile_field_filter_hook__diff_date( $value, $data ) {

        if ( ! empty( $value )) {

            $setting_dates = array_map( 'trim', array_map( 'sanitize_key', explode( ',', UM()->options()->get( $this->prefix . 'meta_keys' ))));
            if ( ! empty( $setting_dates ) && in_array( $data['metakey'], $setting_dates )) {

                $value = $this->find_diff_days( $value, $data['metakey'], UM()->options()->get( $this->prefix . 'next_bday' ) );

            } else {

                $this->restore_um_hooks( $data['metakey'] );
            }
        }

        return $value;
    }

    public function find_diff_days( $um_value, $meta_key, $next_bday ) {

        $value = date_i18n( 'Y/m/d', strtotime( $um_value ));
        $today = date_i18n( 'Y/m/d', current_time( 'timestamp' ));

        if ( $meta_key == 'birth_date' && $next_bday == 1 ) {

            $value = ( substr( $value, 5 ) < substr( $today, 5 ) ) ? strval( intval( substr( $today, 0, 4 )) + 1 ) . substr( $value, 4 ) :
                                                                     substr( $today, 0, 4 ) . substr( $value, 4 );
        }

        $diff_day = date_i18n( $this->text_message( 'date_format' ), strtotime( $value ) );

        if ( $today != $value ) {

            $diff_date    = new DateTime( $value );
            $current_date = new DateTime();

            $diff = $diff_date->diff( $current_date );
            $days = $diff->days;

            if ( $today < $value ) {

                if ( $days > $this->text_message( 'limit_before' )) {
                    return ( empty( $this->shortcode )) ? $um_value : '';
                }

                $this->remove_um_hooks( $meta_key );
                $days++;
                $value = ( $days == 1 ) ? sprintf( $this->text_message( 'one_day_before' ), $diff_day ) : $this->compare_sprintf( 'days_before', $days, $diff_day );

            } else {

                if ( $days > $this->text_message( 'limit_after' )) {
                    return ( empty( $this->shortcode )) ? $um_value : '';
                }
    
                $this->remove_um_hooks( $meta_key );
                $value = ( $days == 1 ) ? sprintf( $this->text_message( 'one_day_after' ), $diff_day ) : $this->compare_sprintf( 'days_after', $days, $diff_day );
            }

        } else {

            $this->remove_um_hooks( $meta_key );
            $value = sprintf( $this->text_message( 'today' ), $diff_day );

            if ( $meta_key == 'birth_date' ) {

                $age = intval( substr( $today, 0, 4 )) - intval( substr( $um_value, 0, 4 ));
                $value = str_replace( '{age}', $age, $value );
            }
        }

        $placeholders = um_replace_placeholders();
        $value = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $value );

        $value = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );

        return $value;
    }

    public function default_strong( $type ) {

        return esc_html__( 'Default value if empty:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;' . esc_attr( $this->default[$type] ) . '</strong>';
    }

    public function remove_um_hooks( $meta_key ) {

        if ( empty( $this->shortcode )) {

            remove_filter( 'um_profile_field_filter_hook__date', 'um_profile_field_filter_hook__date', 99, 2 );
            $this->um_hook1_removed = true;

            if ( $meta_key == 'user_registered' ) {
                remove_filter( 'um_profile_field_filter_hook__user_registered', 'um_profile_field_filter_hook__user_registered', 99, 2 );
                $this->um_hook2_removed = true;
            }
        }
    }

    public function restore_um_hooks( $meta_key ) {

        if ( $this->um_hook1_removed ) {

            add_filter( 'um_profile_field_filter_hook__date', 'um_profile_field_filter_hook__date', 99, 2 );
            $this->um_hook1_removed = false;

            if ( $meta_key == 'user_registered' && $this->um_hook2_removed ) {
                add_filter( 'um_profile_field_filter_hook__user_registered', 'um_profile_field_filter_hook__user_registered', 99, 2 );
                $this->um_hook2_removed = false;
            }                    
        }
    }

    public function um_settings_structure_date_diff_days( $settings ) {

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'um_options' ) {
            if ( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'appearance' ) {

                if ( ! isset( $_REQUEST['section'] ) || $_REQUEST['section'] == '' ) {

                    if ( ! isset( ['appearance']['sections']['']['form_sections']['date_diff_days']['fields'] ) ) {

                        $plugin_data = get_plugin_data( __FILE__ );

                        $link = sprintf( '<a href="%s" target="_blank" title="%s">%s</a>',
                                        esc_url( $plugin_data['PluginURI'] ),
                                        esc_html__( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                        esc_html__( 'Plugin', 'ultimate-member' ));

                        $settings['appearance']['sections']['']['form_sections']['date_diff_days']['title']       = esc_html__( 'Date Difference in Days', 'ultimate-member' );
                        $settings['appearance']['sections']['']['form_sections']['date_diff_days']['description'] = sprintf( esc_html__( '%s version %s - tested with UM %s', 'ultimate-member' ), $link, $plugin_data['Version'], '2.10.2' );
                        $settings['appearance']['sections']['']['form_sections']['date_diff_days']['fields']      = $this->create_plugin_settings_fields();
                    }
                }
            }
        }

        return $settings;
    }

    public function create_plugin_settings_fields() {

        $settings = array();
        $prefix = '&nbsp; * &nbsp;';

        $settings[] = array(
                'id'             => $this->prefix . 'meta_keys',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'meta_keys', 'ultimate-member' ),
                'description'    => esc_html__( 'Comma separated meta_keys to include in the "Date difference in days" formatting.', 'ultimate-member' ) . '<br>' .
                                    esc_html__( 'This field is not used by the "date_diff_days" shortcode where the meta_key is defined in the meta_key parameter.', 'ultimate-member' )
            );

        $settings[] = array(
                'id'             => $this->prefix . 'next_bday',
                'type'           => 'checkbox',
                'label'          => $prefix . esc_html__( 'Number of days until next birthday', 'ultimate-member' ),
                'checkbox_label' => esc_html__( 'Click to display number of days until the User\'s next birthday if birth_date meta_key selected.', 'ultimate-member' ) . '<br>' .
                                    esc_html__( 'This field is not used by the "date_diff_days" shortcode where it\'s always true.', 'ultimate-member' )
            );

        $settings[] = array(
                'id'             => $this->prefix . 'limit_before',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'Max value of days before for display', 'ultimate-member' ),
                'size'           => 'small',
                'description'    => esc_html__( 'Enter the max value for number of days to show until original value is displayed and Shortcode displays blank.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'limit_before' )
            );

        $settings[] = array(
                'id'             => $this->prefix . 'limit_after',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'Max value of days after for display', 'ultimate-member' ),
                'size'           => 'small',
                'description'    => esc_html__( 'Enter the max value for number of days to show after original value is displayed and Shortcode displays blank.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'limit_after' )
            );

        $settings[] = array(
                'id'             => $this->prefix . 'date_format',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'Date format', 'ultimate-member' ),
                'size'           => 'small',
                'description'    => esc_html__( 'Enter your PHP date format for the "date value from the date meta_key" into the placeholder %s.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'date_format' ) . '&nbsp;&nbsp; - ' .
                                    esc_html__( 'Shortcode "date_diff_days" parameter:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;date_format</strong>' .
                                    '<br><a href="https://www.php.net/manual/en/datetime.format.php" target=_blank" title="PHP Date formatting">PHP Date formatting</a>'
            );

        $settings[] = array(
                'id'             => $this->prefix . 'one_day_before',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'One day before', 'ultimate-member' ),
                'size'           => 'medium',
                'description'    => esc_html__( 'Enter your text where %s if inserted is replaced with the date value from the date meta_key.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'one_day_before' ) . '&nbsp;&nbsp; - ' .
                                    esc_html__( 'Shortcode "date_diff_days" parameter:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;one_day_before</strong>'
            );

        $settings[] = array(
                'id'             => $this->prefix . 'days_before',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'Days before', 'ultimate-member' ),
                'size'           => 'medium',
                'description'    => esc_html__( 'Enter your text where %d is number of days and %s is the date value from the date meta_key.', 'ultimate-member' ) . '<br>' .
                                    esc_html__( '%d and %s may be inserted in any order or one or both omitted.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'days_before' ) . '&nbsp;&nbsp; - ' .
                                    esc_html__( 'Shortcode "date_diff_days" parameter:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;days_before</strong>'
            );

        $settings[] = array(
                'id'             => $this->prefix . 'one_day_after',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'One day after', 'ultimate-member' ),
                'size'           => 'medium',
                'description'    => esc_html__( 'Enter your text where %s if inserted is replaced with the date value from the date meta_key.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'one_day_after' ) . '&nbsp;&nbsp; - ' .
                                    esc_html__( 'Shortcode "date_diff_days" parameter:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;one_day_after</strong>'
            );

        $settings[] = array(
                'id'             => $this->prefix . 'days_after',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'Days after', 'ultimate-member' ),
                'size'           => 'medium',
                'description'    => esc_html__( 'Enter your text where %d is number of days and %s is the date value from the date meta_key.', 'ultimate-member' ) . '<br>' .
                                    esc_html__( '%d and %s may be inserted in any order or one or both omitted.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'days_after' ) . '&nbsp;&nbsp; - ' .
                                    esc_html__( 'Shortcode "date_diff_days" parameter:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;days_after</strong>'
            );

        $settings[] = array(
                'id'             => $this->prefix . 'today',
                'type'           => 'text',
                'label'          => $prefix . esc_html__( 'Today', 'ultimate-member' ),
                'size'           => 'medium',
                'description'    => esc_html__( 'Enter your text where %s if inserted is the date value from the date meta_key.', 'ultimate-member' ) . '<br>' .
                                    $this->default_strong( 'today' ) . '&nbsp;&nbsp; - ' .
                                    esc_html__( 'Shortcode "date_diff_days" parameter:', 'ultimate-member' ) . '<strong>&nbsp; &nbsp;today</strong>'
            );

        return $settings;
    }
}


new UM_Date_Diff_Days();

