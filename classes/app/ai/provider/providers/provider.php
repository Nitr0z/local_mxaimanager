<?php

namespace local_mxaimanager\app\ai\provider\providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\factory as base_factory;

abstract class provider
{
    protected base_factory $base_factory;

    abstract public function __construct(base_factory $base_factory, array $json_config);

    /**
     * Define the form elements required for this provider's configuration.
     * This method should add the elements using the provided MoodleQuickForm instance and prefix the element names
     * with the provided prefix to avoid name collisions with other providers.
     * @param \MoodleQuickForm $mform The MoodleQuickForm instance to add elements to.
     * @param string $element_name_prefix The prefix to use for the element names.
     * @return void
     */
    abstract public static function moodleform_definition(\MoodleQuickForm $mform, string $element_name_prefix): void;

    /**
     * Define the form validation rules for this provider's configuration.
     * This method should add the validation rules in a group element to prevent conflicts with other providers.
     * @param array $data The form data to validate.
     * @param string $element_name_prefix The prefix used for the element names.
     * @return array
     */
    abstract public static function moodleform_validation(array $data, string $element_name_prefix): array;

    /**
     * Define the form elements available for configuration of this action on this provider.
     *  This method should add the elements using the provided MoodleQuickForm instance and prefix the element names
     *  with the provided prefix to avoid name collisions with other providers. You should also make sure to add an
     *  attribute 'action' with the action interface name to each element so that the form can hide/show elements
     *  based on the selected provider instance for each action.
     * @param \MoodleQuickForm $mform The MoodleQuickForm instance to add elements to.
     * @param class-string $interface The interface of the action being configured.
     * @param string $element_name_prefix The prefix to use for the element names.
     * @return void
     */
    abstract public static function action_moodleform_definition(
        \MoodleQuickForm $mform,
        string $interface,
        string $element_name_prefix
    ): void;

    /**
     * Define the prefix to use for the provider's moodleform elements.'
     *
     * @return string
     */
    public static function get_provider_moodleform_element_prefix(): string
    {
        return str_replace(' ', '_', strtolower(static::class)) . '_';
    }

    /**
     * Model cost weights: model_name => ['input' => $/1M tokens, 'output' => $/1M tokens].
     * Used to weight token consumption relative to actual API pricing.
     * Override in each provider subclass with provider-specific model costs.
     */
    protected const MODEL_COST_WEIGHTS = [];

    /** Default cost weight when a model is not in MODEL_COST_WEIGHTS. */
    protected const DEFAULT_COST_WEIGHT = ['input' => 1.0, 'output' => 1.0];

    /**
     * Get the cost weights for the model used by a given capability.
     *
     * @param string $capability One of: 'chat', 'embedding', 'image', 'tts', 'transcription'.
     * @return array{input: float, output: float} Cost weights in $/1M tokens.
     */
    public function get_cost_weights(string $capability): array
    {
        $model_property = match ($capability) {
            'chat' => 'chat_model',
            'embedding' => 'embedding_model',
            'image' => 'image_model',
            'tts' => 'tts_model',
            'transcription' => 'transcription_model',
            default => null,
        };

        if ($model_property === null || !isset($this->$model_property)) {
            return static::DEFAULT_COST_WEIGHT;
        }

        $model = $this->$model_property;
        return static::MODEL_COST_WEIGHTS[$model] ?? static::DEFAULT_COST_WEIGHT;
    }

    /**
     * Add a model field as an autocomplete element with known models + free-text input.
     *
     * @param \MoodleQuickForm $mform
     * @param string $element_name_prefix
     * @param string $field_key e.g. 'chat_model'
     * @param string $label_key lang string key for the label
     * @param string $help_key lang string key for the help button
     * @param string $action_interface The action interface class name
     * @param array $known_models List of known model names (values shown in dropdown)
     */
    protected static function add_model_field(
        \MoodleQuickForm $mform,
        string $element_name_prefix,
        string $field_key,
        string $label_key,
        string $help_key,
        string $action_interface,
        array $known_models = []
    ): void {
        $element_name = "{$element_name_prefix}{$field_key}";
        $options = !empty($known_models) ? array_combine($known_models, $known_models) : [];

        $mform->addElement(
            'autocomplete',
            $element_name,
            get_string($label_key, 'local_mxaimanager'),
            $options,
            [
                'tags' => true,
                'multiple' => false,
                'noselectionstring' => get_string('model_type_or_select', 'local_mxaimanager'),
                'action' => $action_interface,
            ]
        );
        $mform->setType($element_name, PARAM_TEXT);
        $mform->addHelpButton($element_name, $help_key, 'local_mxaimanager');
    }

    /**
     * Add a credit multiplier field to the provider form.
     * This allows admins to configure how many credits each token costs for this provider.
     * A higher multiplier means more credits consumed per token (e.g. GPT-4 = 3.0, Scaleway = 1.0).
     *
     * @param \MoodleQuickForm $mform
     * @param string $element_name_prefix
     */
    protected static function add_credit_multiplier_field(
        \MoodleQuickForm $mform,
        string $element_name_prefix
    ): void {
        $element_name = "{$element_name_prefix}credit_multiplier";
        $mform->addElement(
            'text',
            $element_name,
            get_string('credit_multiplier', 'local_mxaimanager')
        );
        $mform->setType($element_name, PARAM_FLOAT);
        $mform->setDefault($element_name, 1.0);
        $mform->addHelpButton($element_name, 'credit_multiplier', 'local_mxaimanager');
    }
}
