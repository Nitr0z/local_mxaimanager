<?php

namespace local_mxaimanager\output\manage_providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use local_mxaimanager\app\ai\provider\entity;
use local_mxaimanager\app\factory as base_factory;

class default_provider_form extends \moodleform
{
    private base_factory $base_factory;
    private bool $ai_pack_owned;

    public function __construct(
        base_factory $base_factory,
        \moodle_url $url,
        bool $ai_pack_owned = true
    ) {
        $this->base_factory = $base_factory;
        $this->ai_pack_owned = $ai_pack_owned;

        parent::__construct($url);
    }

    /**
     * @param \local_mxaimanager\app\collection<\local_mxaimanager\app\ai\default_provider\entity> $default_providers
     * @return void
     */
    public function load_data(\local_mxaimanager\app\collection $default_providers): void
    {
        $data = [];
        foreach ($default_providers as $default_provider) {
            $data[$default_provider->get_action_interface()] = (string)$default_provider->get_provider_id();
        }

        $this->set_data($data);
    }

    protected function definition(): void
    {
        $mform = $this->_form;

        $this->add_default_actions($mform);

        if (!$this->ai_pack_owned) {
            // Freeze all fields — read-only when AI pack is not owned.
            $actions = $this->base_factory->ai()->provider()->get_actions();
            foreach ($actions as $interface => $action) {
                $mform->freeze($interface);
            }
        } else {
            $this->add_action_buttons();
        }
    }

    public function validation($data, $files): array
    {
        $errors = [];
        $actions = $this->base_factory->ai()->provider()->get_actions();
        $all_providers = $this->base_factory->ai()->provider()->repository()->get_all();

        /** @var string $interface */
        foreach ($actions as $interface => $action) {
            // Allow "none" (0) — no default provider for this action.
            if (empty($data[$interface]) || ((int)$data[$interface]) === 0) {
                continue;
            }

            // Check all providers that support this action.
            $provider_exists = $all_providers->filter(
                static function (entity $provider) use ($data, $interface) {
                    if (!isset($data[$interface])) {
                        return false;
                    }
                    if ($provider->get_id() !== (int)$data[$interface]) {
                        return false;
                    }
                    $classes_implemented = class_implements($provider->get_classname());
                    return in_array($interface, $classes_implemented, true);
                }
            )->not_empty();

            if (!$provider_exists) {
                $errors[$interface] = get_string('required');
            }
        }

        return $errors;
    }

    private function add_default_actions(\MoodleQuickForm $mform): void
    {
        $actions = $this->base_factory->ai()->provider()->get_actions();
        $all_providers = $this->base_factory->ai()->provider()->repository()->get_all();

        foreach ($actions as $interface => $action) {
            // Filter all providers that support this action.
            $configured_providers_supporting_action = $all_providers->filter(
                static function (entity $provider) use ($interface) {
                    $classes_implemented = class_implements($provider->get_classname());
                    return in_array($interface, $classes_implemented, true);
                }
            );

            $options = $configured_providers_supporting_action->to_list(static function (entity $provider) {
                return $provider->get_id();
            }, static function (entity $provider) {
                return $provider->get_name();
            })->to_array();

            if (empty($options)) {
                $options[0] = get_string('no_available_providers', 'local_mxaimanager');
            } else {
                $options = [0 => get_string('none')] + $options;
            }

            $mform->addElement('select', $interface, $action, $options);
        }
    }
}
