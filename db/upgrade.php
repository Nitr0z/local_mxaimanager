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

    if ($oldversion < 2026032002) {
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
    }

    if ($oldversion < 2026032700) {
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

    return true;
}
