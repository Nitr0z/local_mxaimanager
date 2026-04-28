<?php

namespace local_mxaimanager\unit\app\ai\feature;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use base_testcase;
use Exception;
use local_mxaimanager\app\ai\feature\entity;
use local_mxaimanager\app\ai\provider\chat_completion_request;
use local_mxaimanager\app\ai\provider\create_embedding_request;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\factory as base_factory;
use local_mxaimanager\app\ai\provider\message;
use local_mxaimanager\app\ai\feature\action_handler;
use local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding;

/**
 * Mock handler class that has methods but doesn't implement interfaces
 */
class MockHandlerWithoutInterface
{
    public function chat_completion(array $messages): string
    {
        return 'mocked response';
    }

    public function get_embedding(string $input, int $dimension): array
    {
        return [0.1, 0.2, 0.3];
    }
}

class action_handler_test extends base_testcase
{
    public function test_chat_completion_success(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        // Mock the protected provider instantiation method
        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(1, ['api_key' => 'test'])
            ->willReturn($handler_mock);

        // Mock the chat completion call
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->with([
                new message('user', 'Hello'),
                new message('assistant', 'Hi there')
            ])
            ->willReturn(new chat_completion_request([], [], 'Response from AI', 1, 1));

        // Execute test
        $messages = [
            new message('user', 'Hello'),
            new message('assistant', 'Hi there')
        ];

        $result = $handler->chat_completion(new entity(), $messages, false, null, 1, ['api_key' => 'test']);

        // Assert
        $this->assertEquals('Response from AI', $result);
    }

    public function test_chat_completion_provider_not_supporting_interface(): void
    {
        // Create instance of mock handler class that has methods but doesn't implement interfaces
        $handler_instance = new MockHandlerWithoutInterface();

        // Setup minimal mocks
        $base_factory_mock = $this->createMock(base_factory::class);

        // Mock handler instantiation
        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(2, ['api_key' => 'test'])
            ->willReturn($handler_instance);

        // Execute test and expect exception
        $messages = [new message('user', 'Hello')];

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Provider instance ID: 2 does not support chat completion');

        $handler->chat_completion(new entity(), $messages, false, null, 2, ['api_key' => 'test']);
    }

    public function test_create_embedding_success(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(create_embedding::class);

        // Mock the protected provider instantiation method
        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(3, ['model' => 'embedding-model'])
            ->willReturn($handler_mock);

        // Mock the embedding call
        $handler_mock->expects($this->once())
            ->method('get_embedding')
            ->with('Hello world', 512)
            ->willReturn(
                new create_embedding_request(
                    [
                        'model' => 'embedding-model',
                        'input' => 'Hello world',
                        'dimension' => 512
                    ],
                    [
                        "object" => "embedding",
                        "data" => [
                            0.123,
                            -0.456,
                            0.789
                        ],
                        "model" => "embedding-model",
                        "usage" => [
                            "prompt_tokens" => 5,
                            "total_tokens" => 5
                        ]
                    ],
                    [
                        0.123,
                        -0.456,
                        0.789
                    ],
                    5,
                    0
                )
            );

        // Execute test
        $result = $handler->create_embedding(new entity(), 'Hello world', 512, 3, ['model' => 'embedding-model']);

        // Assert
        $this->assertEquals([0.123, -0.456, 0.789], $result);
    }

    public function test_create_embedding_provider_not_supporting_interface(): void
    {
        // Create instance of mock handler class that has methods but doesn't implement interfaces
        $handler_instance = new MockHandlerWithoutInterface();

        // Setup minimal mocks
        $base_factory_mock = $this->createMock(base_factory::class);

        // Mock handler instantiation
        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(4, ['model' => 'test'])
            ->willReturn($handler_instance);

        // Execute test and expect exception
        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Provider instance ID: 4 does not support embedding creation');

        $handler->create_embedding(new entity(), 'Hello world', 256, 4, ['model' => 'test']);
    }

    // --- Continuation and markdown stripping tests (restored from upstream) ---

    public function test_chat_completion_continuation_with_json_mode(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        // First call: truncated response.
        // Second call: continuation completes.
        $handler_mock->expects($this->exactly(2))
            ->method('chat_completion')
            ->willReturnOnConsecutiveCalls(
                new chat_completion_request([], [], '{"partial":', 10, 5, 'length'),
                new chat_completion_request([], [], '"value"}', 15, 3, 'stop')
            );

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], true, null, 1, []);

        $this->assertEquals('{"partial":"value"}', $result);
    }

    public function test_chat_completion_no_continuation_for_non_json_mode(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        // Even though finish_reason is 'length', non-JSON mode should NOT continue.
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'partial text', 10, 5, 'length'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);

        $this->assertEquals('partial text', $result);
    }

    public function test_chat_completion_continuation_max_retries_respected(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        // All calls return truncated — should stop after MAX_CONTINUATION_ATTEMPTS + 1 (initial).
        $handler_mock->expects($this->exactly(action_handler::MAX_CONTINUATION_ATTEMPTS + 1))
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'chunk', 5, 3, 'length'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], true, null, 1, []);

        // Initial + MAX_CONTINUATION_ATTEMPTS chunks.
        $expected = str_repeat('chunk', action_handler::MAX_CONTINUATION_ATTEMPTS + 1);
        $this->assertEquals($expected, $result);
    }

    public function test_chat_completion_continuation_with_json_schema(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $schema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];

        $handler_mock->expects($this->exactly(2))
            ->method('chat_completion')
            ->willReturnOnConsecutiveCalls(
                new chat_completion_request([], [], '{"name":', 10, 5, 'length'),
                new chat_completion_request([], [], '"Alice"}', 15, 3, 'stop')
            );

        // json_mode=false but json_schema is set → implies JSON mode for continuation.
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, $schema, 1, []);

        $this->assertEquals('{"name":"Alice"}', $result);
    }

    public function test_chat_completion_strips_markdown_json_wrapper_with_json_mode(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $wrapped = "```json\n{\"key\": \"value\"}\n```";
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $wrapped, 10, 5, 'stop'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], true, null, 1, []);

        $this->assertEquals('{"key": "value"}', $result);
    }

    public function test_chat_completion_strips_markdown_wrapper_without_language_tag(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $wrapped = "```\n{\"key\": \"value\"}\n```";
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $wrapped, 10, 5, 'stop'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], true, null, 1, []);

        $this->assertEquals('{"key": "value"}', $result);
    }

    public function test_chat_completion_does_not_strip_markdown_for_non_json_mode(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $wrapped = "```json\n{\"key\": \"value\"}\n```";
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $wrapped, 10, 5, 'stop'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);

        // Non-JSON mode: markdown wrapper should NOT be stripped.
        $this->assertEquals($wrapped, $result);
    }

    public function test_chat_completion_returns_clean_json_unchanged(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $clean_json = '{"key": "value"}';
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $clean_json, 10, 5, 'stop'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], true, null, 1, []);

        $this->assertEquals($clean_json, $result);
    }

    // --- Managed provider tests ---

    public function test_is_managed_provider_empty_config(): void
    {
        set_config('managed_provider_ids', '', 'local_mxaimanager');
        $this->assertFalse(action_handler::is_managed_provider(1));
    }

    public function test_is_managed_provider_single_match(): void
    {
        set_config('managed_provider_ids', '5', 'local_mxaimanager');
        $this->assertTrue(action_handler::is_managed_provider(5));
        $this->assertFalse(action_handler::is_managed_provider(3));
    }

    public function test_is_managed_provider_multiple_ids(): void
    {
        set_config('managed_provider_ids', '1, 3, 7', 'local_mxaimanager');
        $this->assertTrue(action_handler::is_managed_provider(1));
        $this->assertTrue(action_handler::is_managed_provider(3));
        $this->assertTrue(action_handler::is_managed_provider(7));
        $this->assertFalse(action_handler::is_managed_provider(2));
        $this->assertFalse(action_handler::is_managed_provider(99));
    }

    public function test_is_managed_provider_with_invalid_entries(): void
    {
        set_config('managed_provider_ids', '1, abc, , 5', 'local_mxaimanager');
        $this->assertTrue(action_handler::is_managed_provider(1));
        $this->assertTrue(action_handler::is_managed_provider(5));
        $this->assertFalse(action_handler::is_managed_provider(0));
    }

    // --- check_token_quotas tests ---

    public function test_enforce_quotas_skips_non_managed_non_preconfigured(): void
    {
        // Provider ID > 0 and not in managed list → should NOT throw.
        set_config('managed_provider_ids', '99', 'local_mxaimanager');

        $base_factory_mock = $this->createMock(base_factory::class);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new \local_mxaimanager\app\ai\provider\chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        // Provider 1 is NOT managed (only 99 is) → quotas should be skipped.
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
        $this->assertEquals('OK', $result);
    }

    public function test_enforce_quotas_triggers_for_preconfigured_provider(): void
    {
        // Provider ID < 0 (preconfigured) → should trigger quota enforcement.
        set_config('managed_provider_ids', '', 'local_mxaimanager');
        set_config('quota_display_mode', 'tokens', 'local_mxaimanager');
        // Set quotas to 0 (unlimited) to avoid exception.
        set_config('daily_input_quota', '0', 'local_mxaimanager');
        set_config('daily_output_quota', '0', 'local_mxaimanager');
        set_config('weekly_input_quota', '0', 'local_mxaimanager');
        set_config('weekly_output_quota', '0', 'local_mxaimanager');
        set_config('monthly_input_quota', '0', 'local_mxaimanager');
        set_config('monthly_output_quota', '0', 'local_mxaimanager');

        $db_mock = $this->createMock(\moodle_database::class);
        $base_factory_mock = $this->createMock(base_factory::class);
        $base_factory_mock->method('db')->willReturn($db_mock);

        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new \local_mxaimanager\app\ai\provider\chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        // Provider -1 (preconfigured) → quotas should apply, but all are 0 (unlimited) → no exception.
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, -1, []);
        $this->assertEquals('OK', $result);
    }
}

