<?php

namespace local_mxaimanager\app\ai\feature;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use Exception;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\no_provider_instance_configured;
use local_mxaimanager\app\factory as base_factory;

class provider_resolver
{
    private base_factory $base_factory;
    private entity $feature;

    public function __construct(base_factory $base_factory, entity $feature)
    {
        $this->base_factory = $base_factory;
        $this->feature = $feature;
    }

    /**
     * @param string $action_interface
     * @return array{0: ?int, 1: ?array}|array{}
     */
    private function get_feature_action_provider_id_and_settings_json(string $action_interface): array
    {
        try {
            // Get the feature action.
            $feature_action = $this->base_factory->ai()->feature()->action()->repository(
            )->get_by_feature_id_and_action_interface($this->feature->get_id(), $action_interface);

            // Attempt to get the provider & settings from the feature action.
            if ($feature_action->get_provider_id() !== null) {
                $json = empty($feature_action->get_settings_json()) ? null : json_decode(
                    $feature_action->get_settings_json(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                return [
                    $feature_action->get_provider_id(),
                    $json
                ];
            }
        } catch (\dml_missing_record_exception|\JsonException) {
            // No feature action or invalid JSON, return nulls.
        }

        return [null, null];
    }

    /**
     * @param string $action_interface
     * @return int
     * @throws no_provider_instance_configured
     */
    private function get_default_action_provider_id(string $action_interface): int
    {
        try {
            // Get the default action provider.
            $default_action_provider = $this->base_factory->ai()->default_provider()->repository(
            )->get_by_action_interface($action_interface);
            return $default_action_provider->get_provider_id();
        } catch (\dml_missing_record_exception $e) {
            // Check for preconfigured default providers.
            $preconfigured_providers = $this->base_factory->ai()->provider()->repository()->get_all()->filter(static function (\local_mxaimanager\app\ai\provider\entity $provider) use ($action_interface) {
                if (!$provider->get_is_preconfigured()) {
                    return false;
                }
                $config = json_decode($provider->get_config_json(), true, 512, JSON_THROW_ON_ERROR);
                $supports = in_array($action_interface, class_implements($provider->get_classname()), true);
                $is_default = isset($config['default_unless_explicitly_set']) && $config['default_unless_explicitly_set'];
                return $supports && $is_default;
            });
            if (!$preconfigured_providers->empty()) {
                return $preconfigured_providers->first()->get_id();
            }
            throw new no_provider_instance_configured(
                "No default provider configured for action interface {$action_interface}", previous: $e
            );
        }
    }

    /**
     * @param string $action_interface
     * @return array{0: int, 1: array}
     * @throws no_provider_instance_configured
     * @throws invalid_provider_instance_configuration
     */
    public function get_provider_and_config(string $action_interface): array
    {
        // Try to get the provider & settings configured for the feature action.
        [$provider_id, $settings_json] = $this->get_feature_action_provider_id_and_settings_json($action_interface);

        // If not set, fall back to the configured default provider for the action.
        if (empty($provider_id)) {
            $provider_id = $this->get_default_action_provider_id($action_interface);
            $settings_json = [];
        }

        // Get the provider config JSON.
        try {
            $provider_config_json = json_decode(
                $this->base_factory->ai()->provider()->repository()->get_by_id($provider_id)->get_config_json(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $config_json = array_merge($provider_config_json, $settings_json ?? []);

            return [$provider_id, $config_json];
        } catch (\Throwable $t) {
            throw new invalid_provider_instance_configuration(
                'Invalid provider instance configuration for provider ID: ' . $provider_id, previous: $t
            );
        }
    }
}
