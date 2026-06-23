<?php

namespace local_mxaimanager\app\ai\provider;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class factory
{
    protected \local_mxaimanager\app\factory $base_factory;

    public function __construct(\local_mxaimanager\app\factory $base_factory)
    {
        $this->base_factory = $base_factory;
    }

    public function entity(array $record = []): entity
    {
        return new entity($record);
    }

    public function repository(): repository
    {
        return new repository($this->base_factory);
    }

    /**
     * @return array<providers\provider, string>
     */
    public function get_providers(): array
    {
        return [
            \local_mxaimanager\app\ai\provider\providers\openai::class => 'OpenAI',
            \local_mxaimanager\app\ai\provider\providers\mistral::class => 'Mistral',
            \local_mxaimanager\app\ai\provider\providers\ollama::class => 'Ollama',
            \local_mxaimanager\app\ai\provider\providers\nebius::class => 'Nebius',
        ];
    }

    /**
     * @return array<class-string, string>
     */
    public function get_actions(): array
    {
        return [
            \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class => 'Chat',
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class => 'Embedding',
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_image::class => 'Image',
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription::class => 'Audio Transcription',
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_audio::class => 'Text-to-Speech',
        ];
    }

    /**
     * @param class-string $interface
     * @return array<class-string>
     */
    public function get_providers_supporting_action(string $interface): array
    {
        $providers = array_keys($this->get_providers());

        $providers_supporting_action = [];
        foreach ($providers as $provider_classname) {
            $classes_implemented = class_implements($provider_classname);

            if (in_array($interface, $classes_implemented, true)) {
                $providers_supporting_action[] = $provider_classname;
            }
        }

        return $providers_supporting_action;
    }
}
