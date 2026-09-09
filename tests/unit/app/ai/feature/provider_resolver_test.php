<?php

namespace local_mxaimanager\unit\app\ai\feature;


// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use base_testcase;
use Exception;
use local_mxaimanager\app\ai\default_provider\entity as default_provider_entity;
use local_mxaimanager\app\ai\default_provider\factory as default_provider_factory;
use local_mxaimanager\app\ai\factory as ai_factory;
use local_mxaimanager\app\ai\feature\action\entity as feature_action_entity;
use local_mxaimanager\app\ai\feature\action\factory as feature_action_factory;
use local_mxaimanager\app\ai\feature\entity;
use local_mxaimanager\app\ai\feature\factory as feature_factory;
use local_mxaimanager\app\ai\feature\provider_resolver;
use local_mxaimanager\app\ai\provider\entity as provider_entity;
use local_mxaimanager\app\ai\provider\factory as provider_factory;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\collection;
use local_mxaimanager\app\factory as base_factory;

class provider_resolver_test extends base_testcase
{
    public function test_get_provider_and_config_feature_action_with_provider_and_settings(): void
    {
        $action_interface = 'chat_completion';

        // Create mocks
        $feature_action_entity_mock = $this->createMock(feature_action_entity::class);
        $provider_entity_mock = $this->createMock(provider_entity::class);
        $feature_entity_mock = $this->createMock(entity::class);

        // Mock feature action entity
        $feature_action_entity_mock
            ->method('get_provider_id')
            ->willReturn(1);
        $feature_action_entity_mock
            ->method('get_settings_json')
            ->willReturn('{"custom_setting": "value"}');

        // Mock provider entity
        $provider_entity_mock
            ->method('get_config_json')
            ->willReturn('{"api_key": "test_key", "model": "gpt-4"}');

        // Mock factory chain
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);

        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        // Mock feature action lookup
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willReturn($feature_action_entity_mock);

        // Mock provider lookup
        $provider_repository_mock->expects($this->once())
            ->method('get_by_id')
            ->with(1)
            ->willReturn($provider_entity_mock);

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);
        $result = $resolver->get_provider_and_config($action_interface);

        // Assert merged config
        $this->assertEquals([
            1,
            [
                'api_key' => 'test_key',
                'model' => 'gpt-4',
                'custom_setting' => 'value'
            ]
        ], $result);
    }

    public function test_get_provider_and_config_feature_action_with_provider_no_settings(): void
    {
        $action_interface = 'chat_completion';

        // Create mocks
        $feature_action_entity_mock = $this->createMock(feature_action_entity::class);
        $provider_entity_mock = $this->createMock(provider_entity::class);
        $feature_entity_mock = $this->createMock(entity::class);

        // Mock feature action - has provider but null settings
        $feature_action_entity_mock
            ->method('get_provider_id')
            ->willReturn(2);
        $feature_action_entity_mock
            ->method('get_settings_json')
            ->willReturn(null);

        // Mock provider entity
        $provider_entity_mock
            ->method('get_config_json')
            ->willReturn('{"api_key": "test_key"}');

        // Mock factory chains
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        // Mock lookups
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willReturn($feature_action_entity_mock);

        $provider_repository_mock->expects($this->once())
            ->method('get_by_id')
            ->with(2)
            ->willReturn($provider_entity_mock);

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);
        $result = $resolver->get_provider_and_config($action_interface);

        // Assert provider config only (no feature settings merged)
        $this->assertEquals([2, ['api_key' => 'test_key']], $result);
    }

    public function test_get_provider_and_config_feature_action_invalid_json(): void
    {
        $action_interface = 'chat_completion';

        // Create mocks
        $feature_action_entity_mock = $this->createMock(feature_action_entity::class);
        $provider_entity_mock = $this->createMock(provider_entity::class);
        $feature_entity_mock = $this->createMock(entity::class);

        // Mock feature action with invalid settings JSON
        $feature_action_entity_mock
            ->method('get_provider_id')
            ->willReturn(1);
        $feature_action_entity_mock
            ->method('get_settings_json')
            ->willReturn('{invalid json}');

        // Mock provider entity
        $provider_entity_mock
            ->method('get_config_json')
            ->willReturn('{"api_key": "test_key"}');

        // Mock factory chains
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);
        $default_provider_factory_mock = $this->createMock(default_provider_factory::class);
        $default_provider_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\default_provider\repository::class
        );
        $default_provider_entity_mock = $this->createMock(default_provider_entity::class);

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $ai_factory_mock
            ->method('default_provider')
            ->willReturn($default_provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        $default_provider_factory_mock
            ->method('repository')
            ->willReturn($default_provider_repository_mock);

        // Mock feature action lookup throws JsonException (handled), falls back
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willReturn($feature_action_entity_mock);

        // Mock fallback to default provider
        $default_provider_repository_mock->expects($this->once())
            ->method('get_by_action_interface')
            ->with($action_interface)
            ->willReturn($default_provider_entity_mock);

        $default_provider_entity_mock
            ->method('get_provider_id')
            ->willReturn(3);

        // Mock default provider lookup
        $provider_repository_mock->expects($this->once())
            ->method('get_by_id')
            ->with(3)
            ->willReturn($provider_entity_mock);

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);
        $result = $resolver->get_provider_and_config($action_interface);

        // Assert fallback to default provider
        $this->assertEquals([3, ['api_key' => 'test_key']], $result);
    }

    public function test_get_provider_and_config_no_feature_action_uses_defaults(): void
    {
        $action_interface = 'image_generation';

        // Create mocks
        $default_provider_entity_mock = $this->createMock(default_provider_entity::class);
        $provider_entity_mock = $this->createMock(provider_entity::class);
        $feature_entity_mock = $this->createMock(entity::class);

        // Mock default provider
        $default_provider_entity_mock
            ->method('get_provider_id')
            ->willReturn(4);

        // Mock provider config
        $provider_entity_mock
            ->method('get_config_json')
            ->willReturn('{"model": "dall-e-3"}');

        // Mock factory chains
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);
        $default_provider_factory_mock = $this->createMock(default_provider_factory::class);
        $default_provider_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\default_provider\repository::class
        );

        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $ai_factory_mock
            ->method('default_provider')
            ->willReturn($default_provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        $default_provider_factory_mock
            ->method('repository')
            ->willReturn($default_provider_repository_mock);

        // Mock feature action not found
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willThrowException(new \dml_missing_record_exception('feature_action'));

        // Mock default provider lookup
        $default_provider_repository_mock->expects($this->once())
            ->method('get_by_action_interface')
            ->with($action_interface)
            ->willReturn($default_provider_entity_mock);

        // Mock provider lookup
        $provider_repository_mock->expects($this->once())
            ->method('get_by_id')
            ->with(4)
            ->willReturn($provider_entity_mock);

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);
        $result = $resolver->get_provider_and_config($action_interface);

        // Assert default provider used
        $this->assertEquals([4, ['model' => 'dall-e-3']], $result);
    }

    public function test_get_provider_and_config_missing_default_provider_throws_exception(): void
    {
        $action_interface = 'unknown_action';

        $feature_entity_mock = $this->createMock(entity::class);

        // Mock factory chains
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $default_provider_factory_mock = $this->createMock(default_provider_factory::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $default_provider_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\default_provider\repository::class
        );
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('default_provider')
            ->willReturn($default_provider_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $default_provider_factory_mock
            ->method('repository')
            ->willReturn($default_provider_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        // Mock preconfigured providers
        $collection_mock = $this->createMock(collection::class);
        $collection_mock->method('empty')->willReturn(true);
        $collection_mock->method('filter')->willReturnSelf();
        $collection_mock->method('first')->willReturn(null);
        $provider_repository_mock->expects($this->once())->method('get_all')->willReturn($collection_mock);

        // Mock feature action not found
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willThrowException(new \dml_missing_record_exception('feature_action'));

        // Mock default provider not found
        $default_provider_repository_mock->expects($this->once())
            ->method('get_by_action_interface')
            ->with($action_interface)
            ->willThrowException(new \dml_missing_record_exception('default_provider'));

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No default provider configured for action interface {$action_interface}");

        $resolver->get_provider_and_config($action_interface);
    }

    public function test_get_provider_and_config_provider_config_json_decode_fails(): void
    {
        $action_interface = 'chat_completion';

        // Create mocks
        $default_provider_entity_mock = $this->createMock(default_provider_entity::class);
        $provider_entity_mock = $this->createMock(provider_entity::class);
        $feature_entity_mock = $this->createMock(entity::class);

        // Mock default provider
        $default_provider_entity_mock
            ->method('get_provider_id')
            ->willReturn(5);

        // Mock provider with invalid config JSON
        $provider_entity_mock
            ->method('get_config_json')
            ->willReturn('{invalid json}');

        // Mock factory chains
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);
        $default_provider_factory_mock = $this->createMock(default_provider_factory::class);
        $default_provider_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\default_provider\repository::class
        );

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $ai_factory_mock
            ->method('default_provider')
            ->willReturn($default_provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        $default_provider_factory_mock
            ->method('repository')
            ->willReturn($default_provider_repository_mock);

        // Mock feature action not found
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willThrowException(new \dml_missing_record_exception('feature_action'));

        // Mock default provider found
        $default_provider_repository_mock->expects($this->once())
            ->method('get_by_action_interface')
            ->with($action_interface)
            ->willReturn($default_provider_entity_mock);

        // Mock provider found but invalid config
        $provider_repository_mock->expects($this->once())
            ->method('get_by_id')
            ->with(5)
            ->willReturn($provider_entity_mock);

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);

        // invalid_provider_instance_configuration should be thrown and not caught
        $this->expectException(invalid_provider_instance_configuration::class);

        $resolver->get_provider_and_config($action_interface);
    }

    public function test_get_provider_and_config_provider_not_found(): void
    {
        $action_interface = 'chat_completion';

        // Create mocks
        $default_provider_entity_mock = $this->createMock(default_provider_entity::class);
        $feature_entity_mock = $this->createMock(entity::class);

        // Mock default provider
        $default_provider_entity_mock
            ->method('get_provider_id')
            ->willReturn(999); // Non-existent provider

        // Mock factory chains
        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(\local_mxaimanager\app\ai\feature\action\repository::class);
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);
        $default_provider_factory_mock = $this->createMock(default_provider_factory::class);
        $default_provider_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\default_provider\repository::class
        );

        // Set up feature entity
        $feature_entity_mock
            ->method('get_id')
            ->willReturn(10);

        // Set up factory chains
        $base_factory_mock
            ->method('ai')
            ->willReturn($ai_factory_mock);

        $ai_factory_mock
            ->method('feature')
            ->willReturn($feature_factory_mock);

        $ai_factory_mock
            ->method('provider')
            ->willReturn($provider_factory_mock);

        $ai_factory_mock
            ->method('default_provider')
            ->willReturn($default_provider_factory_mock);

        $feature_factory_mock
            ->method('action')
            ->willReturn($feature_action_factory_mock);

        $feature_action_factory_mock
            ->method('repository')
            ->willReturn($feature_action_repository_mock);

        $provider_factory_mock
            ->method('repository')
            ->willReturn($provider_repository_mock);

        $default_provider_factory_mock
            ->method('repository')
            ->willReturn($default_provider_repository_mock);

        // Mock feature action not found
        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willThrowException(new \dml_missing_record_exception('feature_action'));

        // Mock default provider found
        $default_provider_repository_mock->expects($this->once())
            ->method('get_by_action_interface')
            ->with($action_interface)
            ->willReturn($default_provider_entity_mock);

        // Mock provider not found
        $provider_repository_mock->expects($this->once())
            ->method('get_by_id')
            ->with(999)
            ->willThrowException(new \dml_missing_record_exception('provider'));

        // Create resolver and execute
        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);

        $this->expectException(invalid_provider_instance_configuration::class);

        $resolver->get_provider_and_config($action_interface);
    }

    /**
     * A preconfigured provider whose class is no longer installed must be skipped,
     * not blow up class_implements()/in_array() with a TypeError.
     */
    public function test_get_provider_and_config_preconfigured_provider_with_missing_class_is_skipped(): void
    {
        $action_interface = 'chat_completion';

        $feature_entity_mock = $this->createMock(entity::class);
        $feature_entity_mock->method('get_id')->willReturn(10);

        $base_factory_mock = $this->createMock(base_factory::class);
        $ai_factory_mock = $this->createMock(ai_factory::class);
        $feature_factory_mock = $this->createMock(feature_factory::class);
        $feature_action_factory_mock = $this->createMock(feature_action_factory::class);
        $feature_action_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\feature\action\repository::class
        );
        $default_provider_factory_mock = $this->createMock(default_provider_factory::class);
        $default_provider_repository_mock = $this->createMock(
            \local_mxaimanager\app\ai\default_provider\repository::class
        );
        $provider_factory_mock = $this->createMock(provider_factory::class);
        $provider_repository_mock = $this->createMock(\local_mxaimanager\app\ai\provider\repository::class);

        $base_factory_mock->method('ai')->willReturn($ai_factory_mock);
        $ai_factory_mock->method('feature')->willReturn($feature_factory_mock);
        $ai_factory_mock->method('default_provider')->willReturn($default_provider_factory_mock);
        $ai_factory_mock->method('provider')->willReturn($provider_factory_mock);
        $feature_factory_mock->method('action')->willReturn($feature_action_factory_mock);
        $feature_action_factory_mock->method('repository')->willReturn($feature_action_repository_mock);
        $default_provider_factory_mock->method('repository')->willReturn($default_provider_repository_mock);
        $provider_factory_mock->method('repository')->willReturn($provider_repository_mock);

        $feature_action_repository_mock->expects($this->once())
            ->method('get_by_feature_id_and_action_interface')
            ->with(10, $action_interface)
            ->willThrowException(new \dml_missing_record_exception('feature_action'));

        $default_provider_repository_mock->expects($this->once())
            ->method('get_by_action_interface')
            ->with($action_interface)
            ->willThrowException(new \dml_missing_record_exception('default_provider'));

        // A real collection, so the filter callback actually runs.
        $orphan = (new provider_entity())
            ->set_id(7)
            ->set_classname('local_mxaimanager\app\ai\provider\providers\removed_provider')
            ->set_config_json('{"default_unless_explicitly_set": true}')
            ->set_is_preconfigured(true);

        $provider_repository_mock->expects($this->once())
            ->method('get_all')
            ->willReturn(new collection([$orphan]));

        $resolver = new provider_resolver($base_factory_mock, $feature_entity_mock);

        // Without the class_exists() guard this is a TypeError, not this exception.
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No default provider configured for action interface {$action_interface}");

        $resolver->get_provider_and_config($action_interface);
    }
}
