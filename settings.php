<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

/**
 * @var bool $hassiteconfig
 * @var admin_root $ADMIN
 */

global $CFG, $PAGE, $OUTPUT;

$component = 'local_mxaimanager';

$ADMIN->add('localplugins', new admin_category('local_mxaimanager_pages', get_string('pluginname', $component)));

if ($hassiteconfig) {
    $ADMIN->add(
        'local_mxaimanager_pages',
        new admin_externalpage(
            'local_mxaimanager_manage_page',
            get_string('settings:manage_page', $component),
            new moodle_url('/local/mxaimanager/view.php?view=manage&action=index')
        )
    );

    // Freemium Provider settings page (toggle + connection + quotas).
    $settings = new admin_settingpage('local_mxaimanager_freemium', get_string('settings:freemium_page', $component));

    // Enable/disable toggle.
    $settings->add(new admin_setting_configcheckbox(
        'local_mxaimanager/enable_freemium',
        get_string('enable_freemium', $component),
        get_string('enable_freemium_desc', $component),
        '1'
    ));

    // Freemium connection heading.
    $settings->add(new admin_setting_heading(
        'local_mxaimanager/freemium_connection_heading',
        get_string('freemium_connection_heading', $component),
        get_string('freemium_connection_heading_desc', $component)
    ));

    // API key (password field — masked in UI, stored in DB).
    $settings->add(new admin_setting_configpasswordunmask(
        'local_mxaimanager/freemium_api_key',
        get_string('freemium_api_key', $component),
        get_string('freemium_api_key_desc', $component),
        ''
    ));

    // Base URL.
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/freemium_base_url',
        get_string('freemium_base_url', $component),
        get_string('freemium_base_url_desc', $component),
        'https://api.scaleway.ai/v1',
        PARAM_URL
    ));

    // Model.
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/freemium_model',
        get_string('freemium_model', $component),
        get_string('freemium_model_desc', $component),
        '',
        PARAM_TEXT
    ));

    // Token Quota heading.
    $settings->add(new admin_setting_heading(
        'local_mxaimanager/quota_heading',
        get_string('quota_heading', $component),
        get_string('quota_heading_desc', $component)
    ));

    // Daily quotas.
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/daily_input_quota',
        get_string('daily_input_quota', $component),
        get_string('daily_input_quota_desc', $component),
        50000,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/daily_output_quota',
        get_string('daily_output_quota', $component),
        get_string('daily_output_quota_desc', $component),
        32000,
        PARAM_INT
    ));

    // Weekly quotas.
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/weekly_input_quota',
        get_string('weekly_input_quota', $component),
        get_string('weekly_input_quota_desc', $component),
        350000,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/weekly_output_quota',
        get_string('weekly_output_quota', $component),
        get_string('weekly_output_quota_desc', $component),
        226000,
        PARAM_INT
    ));

    // Monthly quotas.
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/monthly_input_quota',
        get_string('monthly_input_quota', $component),
        get_string('monthly_input_quota_desc', $component),
        1550000,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/monthly_output_quota',
        get_string('monthly_output_quota', $component),
        get_string('monthly_output_quota_desc', $component),
        1000000,
        PARAM_INT
    ));

    $ADMIN->add('local_mxaimanager_pages', $settings);
}
