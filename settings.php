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

    // Billing & Quotas settings page.
    $settings = new admin_settingpage('local_mxaimanager_billing', get_string('settings:billing_page', $component));

    // --- Credit System Settings ---
    $settings->add(new admin_setting_heading(
        'local_mxaimanager/credit_heading',
        get_string('credit_heading', $component),
        get_string('credit_heading_desc', $component)
    ));

    // Quota display mode: credits or tokens.
    $settings->add(new admin_setting_configselect(
        'local_mxaimanager/quota_display_mode',
        get_string('quota_display_mode', $component),
        get_string('quota_display_mode_desc', $component),
        'credits',
        [
            'credits' => get_string('mode_credits', $component),
            'tokens'  => get_string('mode_tokens', $component),
        ]
    ));

    // Tokens per credit conversion ratio.
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/tokens_per_credit',
        get_string('tokens_per_credit', $component),
        get_string('tokens_per_credit_desc', $component),
        1000,
        PARAM_INT
    ));

    // Managed provider IDs (credit-limited, non-editable by client).
    $settings->add(new admin_setting_configtext(
        'local_mxaimanager/managed_provider_ids',
        get_string('managed_provider_ids', $component),
        get_string('managed_provider_ids_desc', $component),
        '',
        PARAM_TEXT
    ));

    // Credit recharge form + ledger (custom UI widget).
    require_once(__DIR__ . '/classes/admin_setting_credit_recharge.php');
    $settings->add(new \local_mxaimanager\admin_setting_credit_recharge());

    // --- Advanced Token Quotas (only visible when quota_display_mode = tokens) ---
    $settings->add(new admin_setting_heading(
        'local_mxaimanager/token_quota_heading',
        get_string('quota_heading', $component),
        get_string('quota_heading_desc', $component)
    ));

    // Daily quotas.
    $setting = new admin_setting_configtext(
        'local_mxaimanager/daily_input_quota',
        get_string('daily_input_quota', $component),
        get_string('daily_input_quota_desc', $component),
        50000,
        PARAM_INT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_mxaimanager/daily_output_quota',
        get_string('daily_output_quota', $component),
        get_string('daily_output_quota_desc', $component),
        32000,
        PARAM_INT
    );
    $settings->add($setting);

    // Weekly quotas.
    $setting = new admin_setting_configtext(
        'local_mxaimanager/weekly_input_quota',
        get_string('weekly_input_quota', $component),
        get_string('weekly_input_quota_desc', $component),
        350000,
        PARAM_INT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_mxaimanager/weekly_output_quota',
        get_string('weekly_output_quota', $component),
        get_string('weekly_output_quota_desc', $component),
        226000,
        PARAM_INT
    );
    $settings->add($setting);

    // Monthly quotas.
    $setting = new admin_setting_configtext(
        'local_mxaimanager/monthly_input_quota',
        get_string('monthly_input_quota', $component),
        get_string('monthly_input_quota_desc', $component),
        1550000,
        PARAM_INT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_mxaimanager/monthly_output_quota',
        get_string('monthly_output_quota', $component),
        get_string('monthly_output_quota_desc', $component),
        1000000,
        PARAM_INT
    );
    $settings->add($setting);

    // Hide all token quota fields when display mode is 'credits'.
    $token_fields = [
        'local_mxaimanager/token_quota_heading',
        'local_mxaimanager/daily_input_quota',
        'local_mxaimanager/daily_output_quota',
        'local_mxaimanager/weekly_input_quota',
        'local_mxaimanager/weekly_output_quota',
        'local_mxaimanager/monthly_input_quota',
        'local_mxaimanager/monthly_output_quota',
    ];
    foreach ($token_fields as $field) {
        $settings->hide_if($field, 'local_mxaimanager/quota_display_mode', 'eq', 'credits');
    }

    $ADMIN->add('local_mxaimanager_pages', $settings);
}
