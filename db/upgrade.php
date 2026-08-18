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

    // Remove text-to-image support for nebius providers
    if ($oldversion < 2026040700) {
        $transaction = $DB->start_delegated_transaction();

        try {
            // Unset image model on nebius providers.
            $nebius_providers = $DB->get_records(
                'local_mxaimanager_providers',
                ['classname' => \local_mxaimanager\app\ai\provider\providers\nebius::class]
            );
            foreach ($nebius_providers as $provider) {
                $config = json_decode($provider->config_json, true, 512, JSON_THROW_ON_ERROR);
                unset($config['image_model']);
                $provider->config_json = json_encode($config, JSON_THROW_ON_ERROR);
                $DB->update_record('local_mxaimanager_providers', $provider);
            }

            // Collect nebius provider IDs from both DB and preconfigured providers.
            $nebius_provider_ids = array_map('intval', array_column($nebius_providers, 'id'));

            $preconfigured = $CFG->local_mxaimanager_preconfigured_providers ?? [];
            foreach ($preconfigured as $index => $config) {
                $record = (array) $config;
                if (($record['classname'] ?? '') === \local_mxaimanager\app\ai\provider\providers\nebius::class) {
                    $nebius_provider_ids[] = -($index + 1);
                }
            }

            // Clear any create_image mappings that point to a nebius provider.
            if (!empty($nebius_provider_ids)) {
                $create_image_interface = \local_mxaimanager\app\ai\provider\providers\interfaces\create_image::class;

                // Null out feature-action overrides pointing to nebius for create_image.
                [$in_sql, $params] = $DB->get_in_or_equal($nebius_provider_ids);
                $params[] = $create_image_interface;
                $DB->execute(
                    "UPDATE {local_mxaimanager_feature_actions}
                        SET provider_id = NULL, settings_json = NULL
                      WHERE provider_id $in_sql
                        AND action_interface = ?",
                    $params
                );

                // Delete default provider rows pointing to nebius for create_image.
                [$in_sql, $params] = $DB->get_in_or_equal($nebius_provider_ids);
                $params[] = $create_image_interface;
                $DB->execute(
                    "DELETE FROM {local_mxaimanager_default_providers}
                      WHERE provider_id $in_sql
                        AND action_interface = ?",
                    $params
                );
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    if ($oldversion < 2026081800) {
        $old_interface = 'local_mxaimanager\\app\\ai\\provider\\providers\\interfaces\\create_speech';
        $new_interface = \local_mxaimanager\app\ai\provider\providers\interfaces\create_audio::class;

        $default_audio = $DB->get_record('local_mxaimanager_default_providers', [
            'action_interface' => $new_interface,
        ]);
        $default_speech = $DB->get_record('local_mxaimanager_default_providers', [
            'action_interface' => $old_interface,
        ]);
        if ($default_speech) {
            if ($default_audio) {
                $DB->delete_records('local_mxaimanager_default_providers', ['id' => $default_speech->id]);
            } else {
                $default_speech->action_interface = $new_interface;
                $DB->update_record('local_mxaimanager_default_providers', $default_speech);
            }
        }

        $speech_actions = $DB->get_records('local_mxaimanager_feature_actions', [
            'action_interface' => $old_interface,
        ]);
        foreach ($speech_actions as $row) {
            $existing_audio = $DB->get_record('local_mxaimanager_feature_actions', [
                'feature_id' => $row->feature_id,
                'action_interface' => $new_interface,
            ]);
            if ($existing_audio) {
                $DB->delete_records('local_mxaimanager_feature_actions', ['id' => $row->id]);
            } else {
                $row->action_interface = $new_interface;
                $DB->update_record('local_mxaimanager_feature_actions', $row);
            }
        }

        upgrade_plugin_savepoint(true, 2026081800, 'local', 'mxaimanager');
    }

    return true;
}
