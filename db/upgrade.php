<?php

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
// @codeCoverageIgnoreEnd

function xmldb_local_mxaimanager_upgrade($oldversion): bool
{
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026011200) {
        $table = new \xmldb_table('local_mxaimanager_feature_action_usage_logs');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('feature_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('request_json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('response_json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('input_tokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('output_tokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('session_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('feature_id', XMLDB_INDEX_NOTUNIQUE, ['feature_id']);
            $table->add_index(
                'feature_id_timecreated',
                XMLDB_INDEX_NOTUNIQUE,
                ['feature_id', 'timecreated']
            );
            $table->add_index('session_id', XMLDB_INDEX_NOTUNIQUE, ['session_id']);
            $table->add_index('user_id', XMLDB_INDEX_NOTUNIQUE, ['user_id']);

            $dbman->create_table($table);
        }
    }


    if ($oldversion < 2026032700) {
        // Set default freemium quota values for existing installations.
        $defaults = [
            'daily_input_quota'    => 50000,
            'daily_output_quota'   => 32000,
            'weekly_input_quota'   => 350000,
            'weekly_output_quota'  => 226000,
            'monthly_input_quota'  => 1550000,
            'monthly_output_quota' => 1000000,
        ];
        foreach ($defaults as $key => $value) {
            $current = get_config('local_mxaimanager', $key);
            if ($current === false || (int)$current === 0) {
                set_config($key, $value, 'local_mxaimanager');
            }
        }

        // Freemium credentials must be configured manually via admin settings.
        // Set defaults for non-sensitive settings only.
        if (get_config('local_mxaimanager', 'freemium_base_url') === false) {
            set_config('freemium_base_url', 'https://api.scaleway.ai/v1', 'local_mxaimanager');
        }
        if (get_config('local_mxaimanager', 'freemium_model') === false) {
            set_config('freemium_model', 'devstral-2-123b-instruct-2512', 'local_mxaimanager');
        }

        upgrade_plugin_savepoint(true, 2026032700, 'local', 'mxaimanager');
    }

    if ($oldversion < 2026042200) {
        // --- Credit ledger table (purchases & recharges) ---
        $table = new \xmldb_table('local_mxaimanager_credit_ledger');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('amount', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL);
            $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
            $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('created_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // --- Add provider_id + credits_used to usage logs ---
        $logs = new \xmldb_table('local_mxaimanager_feature_action_usage_logs');

        $field = new \xmldb_field('provider_id', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0', 'feature_id');
        if (!$dbman->field_exists($logs, $field)) {
            $dbman->add_field($logs, $field);
        }

        $field = new \xmldb_field('credits_used', XMLDB_TYPE_NUMBER, '10, 2', null,
            XMLDB_NOTNULL, null, '0', 'output_tokens');
        if (!$dbman->field_exists($logs, $field)) {
            $dbman->add_field($logs, $field);
        }

        // --- Default credit settings ---
        if (get_config('local_mxaimanager', 'quota_display_mode') === false) {
            set_config('quota_display_mode', 'credits', 'local_mxaimanager');
        }
        if (get_config('local_mxaimanager', 'tokens_per_credit') === false) {
            set_config('tokens_per_credit', 1000, 'local_mxaimanager');
        }

        upgrade_plugin_savepoint(true, 2026042200, 'local', 'mxaimanager');
    }

    if ($oldversion < 2026042300) {
        // Add expires_at column to credit_ledger (per-recharge expiry).
        $table = new \xmldb_table('local_mxaimanager_credit_ledger');
        $field = new \xmldb_field('expires_at', XMLDB_TYPE_INTEGER, '10', null,
            null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Remove obsolete global expiry config.
        unset_config('credit_expiry_date', 'local_mxaimanager');

        upgrade_plugin_savepoint(true, 2026042300, 'local', 'mxaimanager');
    }

    if ($oldversion < 2026042301) {
        // Add active column to credit_ledger (soft-disable entries).
        $table = new \xmldb_table('local_mxaimanager_credit_ledger');
        $field = new \xmldb_field('active', XMLDB_TYPE_INTEGER, '1', null,
            XMLDB_NOTNULL, null, '1', 'expires_at');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026042301, 'local', 'mxaimanager');
    }

    return true;
}
