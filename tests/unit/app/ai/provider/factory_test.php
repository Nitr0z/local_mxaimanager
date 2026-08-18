<?php

namespace local_mxaimanager\unit\app\ai\provider;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

class factory_test extends \base_testcase
{
    public function test_get_providers(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $providers = $provider_factory->get_providers();

        // Verify return type
        $this->assertIsArray($providers);

        // Verify expected providers are present
        $expected_providers = [
            \local_mxaimanager\app\ai\provider\providers\openai::class,
            \local_mxaimanager\app\ai\provider\providers\mistral::class,
            \local_mxaimanager\app\ai\provider\providers\ollama::class,
            \local_mxaimanager\app\ai\provider\providers\nebius::class,
            \local_mxaimanager\app\ai\provider\providers\scaleway::class,
        ];

        foreach ($expected_providers as $provider_class) {
            $this->assertArrayHasKey($provider_class, $providers);
            $this->assertIsString($providers[$provider_class]);
        }

        // Verify display names are non-empty strings
        foreach ($providers as $classname => $display_name) {
            $this->assertIsString($classname);
            $this->assertIsString($display_name);
            $this->assertNotEmpty($display_name);
        }
    }

    public function test_get_actions(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $actions = $provider_factory->get_actions();

        // Verify return type
        $this->assertIsArray($actions);

        // Verify expected actions are present
        $expected_actions = [
            \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class,
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class,
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_audio::class,
            \local_mxaimanager\app\ai\provider\providers\interfaces\vision::class,
        ];

        foreach ($expected_actions as $action_interface) {
            $this->assertArrayHasKey($action_interface, $actions);
            $this->assertIsString($actions[$action_interface]);
        }

        // Verify display names are non-empty strings
        foreach ($actions as $interface => $display_name) {
            $this->assertIsString($interface);
            $this->assertIsString($display_name);
            $this->assertNotEmpty($display_name);
        }
    }

    public function test_get_providers_supporting_action_create_audio(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $audio_providers = $provider_factory->get_providers_supporting_action(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_audio::class
        );

        // OpenAI is the only provider implementing the TTS interface in this phase.
        $this->assertIsArray($audio_providers);
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\openai::class,
            $audio_providers
        );

        // Other providers must NOT implement TTS yet.
        $this->assertNotContains(
            \local_mxaimanager\app\ai\provider\providers\mistral::class,
            $audio_providers
        );
        $this->assertNotContains(
            \local_mxaimanager\app\ai\provider\providers\nebius::class,
            $audio_providers
        );
        $this->assertNotContains(
            \local_mxaimanager\app\ai\provider\providers\ollama::class,
            $audio_providers
        );
    }

    public function test_get_providers_supporting_action_vision(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $vision_providers = $provider_factory->get_providers_supporting_action(
            \local_mxaimanager\app\ai\provider\providers\interfaces\vision::class
        );

        $this->assertIsArray($vision_providers);
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\openai::class,
            $vision_providers
        );
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\scaleway::class,
            $vision_providers
        );
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\mistral::class,
            $vision_providers
        );
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\ollama::class,
            $vision_providers
        );
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\nebius::class,
            $vision_providers
        );
    }

    public function test_get_providers_supporting_action_chat_completion(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $chat_providers = $provider_factory->get_providers_supporting_action(
            \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class
        );

        // Verify return type
        $this->assertIsArray($chat_providers);

        // All returned items should be provider class strings
        foreach ($chat_providers as $provider_class) {
            $this->assertIsString($provider_class);
            // Verify it's in the known providers list
            $providers = $provider_factory->get_providers();
            $this->assertArrayHasKey($provider_class, $providers);
        }

        // At least one provider should support chat completion
        $this->assertNotEmpty($chat_providers);
    }

    public function test_get_providers_supporting_action_create_embedding(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $embedding_providers = $provider_factory->get_providers_supporting_action(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class
        );

        // Verify return type
        $this->assertIsArray($embedding_providers);

        // All returned items should be provider class strings
        foreach ($embedding_providers as $provider_class) {
            $this->assertIsString($provider_class);
            // Verify it's in the known providers list
            $providers = $provider_factory->get_providers();
            $this->assertArrayHasKey($provider_class, $providers);
        }

        // At least one provider should support embeddings
        $this->assertNotEmpty($embedding_providers);
    }

    public function test_get_providers_supporting_action_unknown_interface(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        // Test with an interface that no provider implements
        $unknown_providers = $provider_factory->get_providers_supporting_action(
            'Some\\Unknown\\Interface'
        );

        // Should return empty array
        $this->assertIsArray($unknown_providers);
        $this->assertEmpty($unknown_providers);
    }

    public function test_get_providers_supporting_action_all_providers_include_chat_completion(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $chat_providers = $provider_factory->get_providers_supporting_action(
            \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class
        );

        // All returned providers should actually implement the interface
        foreach ($chat_providers as $provider_class) {
            $implemented_interfaces = class_implements($provider_class);
            $this->assertContains(
                \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class,
                $implemented_interfaces ?: []
            );
        }
    }

    public function test_get_providers_supporting_action_all_providers_include_create_embedding(): void
    {
        $factory = \local_mxaimanager\app\factory::make();

        $ai_factory = $factory->ai();
        $provider_factory = $ai_factory->provider();

        $embedding_providers = $provider_factory->get_providers_supporting_action(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class
        );

        // All returned providers should actually implement the interface
        foreach ($embedding_providers as $provider_class) {
            $implemented_interfaces = class_implements($provider_class);
            $this->assertContains(
                \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class,
                $implemented_interfaces ?: []
            );
        }
    }
}
