<?php

namespace local_mxaimanager\app\ai\provider\providers;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\message;
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
     * Merges the contents of all system messages into one and places it at index zero.
     *
     * @param message[] $messages
     * @return message[]
     */
    protected function merge_system_messages(array $messages): array
    {
        $system_messages_contents = [];
        $non_system_messages = [];

        foreach ($messages as $message) {
            $msg = $message;
            //Guard against message delivered as associative array instead of obj of type message.
            if(is_array($message)) {
                $msg = new message($message['role'], $message['content']);
            }

            if ($msg->get_role() === 'system') {
                $system_messages_contents[] = $msg->get_content();
            } else {
                $non_system_messages[] = $msg;
            }
        }

        if(empty($system_messages_contents)) {
            return $messages;
        }

        return array_merge([new message('system', implode("\n", $system_messages_contents))], $non_system_messages);
    }

    /**
     * @param string $path
     * @return array{mime: string, base64: string}
     */
    protected static function read_image_file(string $path): array
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            throw new \local_mxaimanager\app\exceptions\invalid_provider_instance_configuration(
                'Vision image is empty or unreadable: ' . $path
            );
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';

        return [
            'mime' => $mime,
            'base64' => base64_encode($bytes),
        ];
    }

    /**
     * OpenAI-compatible multimodal user content (text + image_url data URLs).
     *
     * @param string $prompt
     * @param string[] $image_filepaths
     * @return array
     */
    protected static function build_openai_vision_content(string $prompt, array $image_filepaths): array
    {
        if ($prompt === '' || empty($image_filepaths)) {
            throw new \local_mxaimanager\app\exceptions\invalid_provider_instance_configuration(
                'Vision requires a prompt and at least one image'
            );
        }

        $parts = [
            ['type' => 'text', 'text' => $prompt],
        ];
        foreach ($image_filepaths as $path) {
            $image = self::read_image_file($path);
            $parts[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:' . $image['mime'] . ';base64,' . $image['base64'],
                ],
            ];
        }

        return $parts;
    }
}
