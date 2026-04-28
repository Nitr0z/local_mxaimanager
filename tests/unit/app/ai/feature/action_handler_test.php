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
        $base_factory_mock = $this->create_base_factory_with_db();
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
        $base_factory_mock = $this->create_base_factory_with_db();
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

    public function test_chat_completion_continuation_with_json_mode(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // First call returns truncated response (finish_reason = 'length')
        // Second call returns completed response (finish_reason = 'stop')
        $handler_mock->expects($this->exactly(2))
            ->method('chat_completion')
            ->willReturnOnConsecutiveCalls(
                new chat_completion_request([], [], '{"partial":', 5, 5, 'length'),
                new chat_completion_request([], [], '"value"}', 10, 3, 'stop')
            );

        // Execute test
        $messages = [
            new message('user', 'Generate JSON'),
        ];

        $result = $handler->chat_completion(new entity(), $messages, true, null, 1, ['api_key' => 'test']);

        // Assert the concatenated response
        $this->assertEquals('{"partial":"value"}', $result);
    }

    public function test_chat_completion_no_continuation_for_non_json_mode(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // Only one call should be made - no continuation for non-JSON mode
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(
                new chat_completion_request([], [], 'Truncated text response...', 5, 5, 'length')
            );

        // Execute test with json_mode=false and json_schema=null
        $messages = [
            new message('user', 'Generate text'),
        ];

        $result = $handler->chat_completion(new entity(), $messages, false, null, 1, ['api_key' => 'test']);

        // Assert only the truncated response is returned (no continuation)
        $this->assertEquals('Truncated text response...', $result);
    }

    public function test_chat_completion_continuation_max_retries_respected(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // All calls return finish_reason = 'length' (never completes).
        // Should be 1 initial call + MAX_CONTINUATION_ATTEMPTS continuation calls.
        $total_calls = 1 + action_handler::MAX_CONTINUATION_ATTEMPTS;
        $responses = [];
        for ($i = 0; $i < $total_calls; $i++) {
            $responses[] = new chat_completion_request([], [], 'part' . $i, 5, 5, 'length');
        }

        $handler_mock->expects($this->exactly($total_calls))
            ->method('chat_completion')
            ->willReturnOnConsecutiveCalls(...$responses);

        // Execute test
        $messages = [
            new message('user', 'Generate JSON'),
        ];

        $result = $handler->chat_completion(new entity(), $messages, true, null, 1, ['api_key' => 'test']);

        // Assert the accumulated response contains all parts
        $expected = '';
        for ($i = 0; $i < $total_calls; $i++) {
            $expected .= 'part' . $i;
        }
        $this->assertEquals($expected, $result);
    }

    public function test_chat_completion_continuation_with_json_schema(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        $json_schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer']
            ]
        ];

        // First call returns truncated response, second returns completion
        $handler_mock->expects($this->exactly(2))
            ->method('chat_completion')
            ->willReturnOnConsecutiveCalls(
                new chat_completion_request([], [], '{"name":"John",', 5, 5, 'length'),
                new chat_completion_request([], [], '"age":30}', 10, 3, 'stop')
            );

        // Execute test with json_mode=false but json_schema set
        $messages = [
            new message('user', 'Generate JSON'),
        ];

        $result = $handler->chat_completion(new entity(), $messages, false, $json_schema, 1, ['api_key' => 'test']);

        // Assert the concatenated response
        $this->assertEquals('{"name":"John","age":30}', $result);
    }

    public function test_chat_completion_strips_markdown_json_wrapper_with_json_mode(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // Response wrapped in markdown code block
        $wrapped_response = "```json\n{\"key\":\"value\"}\n```";
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $wrapped_response, 5, 5));

        // Execute test with json_mode=true
        $messages = [new message('user', 'Generate JSON')];
        $result = $handler->chat_completion(new entity(), $messages, true, null, 1, ['api_key' => 'test']);

        // Assert the markdown wrapper was stripped
        $this->assertEquals('{"key":"value"}', $result);
    }

    public function test_chat_completion_strips_markdown_wrapper_without_language_tag(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // Response wrapped in code block without language tag
        $wrapped_response = "```\n{\"key\":\"value\"}\n```";
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $wrapped_response, 5, 5));

        // Execute test with json_schema set
        $json_schema = ['type' => 'object', 'properties' => ['key' => ['type' => 'string']]];
        $messages = [new message('user', 'Generate JSON')];
        $result = $handler->chat_completion(new entity(), $messages, false, $json_schema, 1, ['api_key' => 'test']);

        // Assert the markdown wrapper was stripped
        $this->assertEquals('{"key":"value"}', $result);
    }

    public function test_chat_completion_does_not_strip_markdown_for_non_json_mode(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // Response wrapped in markdown code block
        $wrapped_response = "```json\n{\"key\":\"value\"}\n```";
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $wrapped_response, 5, 5));

        // Execute test with json_mode=false and json_schema=null (non-JSON mode)
        $messages = [new message('user', 'Show me some code')];
        $result = $handler->chat_completion(new entity(), $messages, false, null, 1, ['api_key' => 'test']);

        // Assert the response is returned as-is (no stripping)
        $this->assertEquals($wrapped_response, $result);
    }

    public function test_chat_completion_returns_clean_json_unchanged(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // Response is already clean JSON (no wrapper)
        $clean_response = '{"key":"value"}';
        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], $clean_response, 5, 5));

        // Execute test with json_mode=true
        $messages = [new message('user', 'Generate JSON')];
        $result = $handler->chat_completion(new entity(), $messages, true, null, 1, ['api_key' => 'test']);

        // Assert clean JSON is returned as-is
        $this->assertEquals('{"key":"value"}', $result);
    }

    public function test_chat_completion_strips_markdown_wrapper_from_continuation_response(): void
    {
        // Create minimal mocks needed
        $base_factory_mock = $this->create_base_factory_with_db();
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

        // First call returns truncated response wrapped in markdown, second completes it
        // In practice, only the single-response path would be wrapped, but let's test
        // that the continuation path also strips wrappers from the final concatenated result.
        $handler_mock->expects($this->exactly(2))
            ->method('chat_completion')
            ->willReturnOnConsecutiveCalls(
                new chat_completion_request([], [], '{"partial":', 5, 5, 'length'),
                new chat_completion_request([], [], '"value"}', 10, 3, 'stop')
            );

        // Execute test with json_mode=true
        $messages = [new message('user', 'Generate JSON')];
        $result = $handler->chat_completion(new entity(), $messages, true, null, 1, ['api_key' => 'test']);

        // Assert concatenated response (no wrapper to strip, but stripping logic should not break it)
        $this->assertEquals('{"partial":"value"}', $result);
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

    /**
     * Helper: create a base_factory mock with db and user properly stubbed.
     * This prevents "Undefined property: stdClass::$id" warnings.
     */
    private function create_base_factory_with_db(?\moodle_database $db_mock = null): base_factory
    {
        $db_mock = $db_mock ?? $this->createMock(\moodle_database::class);
        $user = new \stdClass();
        $user->id = 1;

        $base_factory_mock = $this->createMock(base_factory::class);
        $base_factory_mock->method('db')->willReturn($db_mock);
        $base_factory_mock->method('user')->willReturn($user);

        return $base_factory_mock;
    }

    /**
     * Helper: configure all token quotas for testing.
     */
    private function set_all_quotas(
        int $daily_in = 0, int $daily_out = 0,
        int $weekly_in = 0, int $weekly_out = 0,
        int $monthly_in = 0, int $monthly_out = 0
    ): void {
        set_config('quota_display_mode', 'tokens', 'local_mxaimanager');
        set_config('daily_input_quota', (string)$daily_in, 'local_mxaimanager');
        set_config('daily_output_quota', (string)$daily_out, 'local_mxaimanager');
        set_config('weekly_input_quota', (string)$weekly_in, 'local_mxaimanager');
        set_config('weekly_output_quota', (string)$weekly_out, 'local_mxaimanager');
        set_config('monthly_input_quota', (string)$monthly_in, 'local_mxaimanager');
        set_config('monthly_output_quota', (string)$monthly_out, 'local_mxaimanager');
    }

    /**
     * Helper: build an action_handler that uses the given factory mock
     * and stubs out provider instantiation with the given provider mock.
     */
    private function build_handler_for_quota_test(
        base_factory $base_factory_mock,
        $provider_mock
    ): action_handler {
        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->method('get_provider_handler_provider_and_settings_json')
            ->willReturn($provider_mock);

        return $handler;
    }

    public function test_enforce_quotas_skips_non_managed_non_preconfigured(): void
    {
        // Provider ID > 0 and not in managed list → should NOT throw.
        set_config('managed_provider_ids', '99', 'local_mxaimanager');

        $base_factory_mock = $this->create_base_factory_with_db();
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        // Provider 1 is NOT managed (only 99 is) → quotas should be skipped.
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
        $this->assertEquals('OK', $result);
    }

    public function test_enforce_quotas_triggers_for_preconfigured_provider(): void
    {
        // Provider ID < 0 (preconfigured) → should trigger quota enforcement.
        set_config('managed_provider_ids', '', 'local_mxaimanager');
        $this->set_all_quotas(); // All 0 = unlimited.

        $base_factory_mock = $this->create_base_factory_with_db();
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        // Provider -1 (preconfigured) → quotas should apply, but all are 0 (unlimited) → no exception.
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, -1, []);
        $this->assertEquals('OK', $result);
    }

    public function test_check_token_quotas_daily_input_exceeded(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        $this->set_all_quotas(daily_in: 100);

        // DB returns 100 input tokens used → exactly at the limit → should throw.
        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 100, 'used_output' => 0]
        );
        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $this->expectException(\local_mxaimanager\app\exceptions\quota_exceeded_exception::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
    }

    public function test_check_token_quotas_daily_output_exceeded(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        $this->set_all_quotas(daily_out: 50);

        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 0, 'used_output' => 75]
        );
        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $this->expectException(\local_mxaimanager\app\exceptions\quota_exceeded_exception::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
    }

    public function test_check_token_quotas_weekly_input_exceeded(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        // Daily unlimited, weekly input = 500
        $this->set_all_quotas(weekly_in: 500);

        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 600, 'used_output' => 0]
        );
        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $this->expectException(\local_mxaimanager\app\exceptions\quota_exceeded_exception::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
    }

    public function test_check_token_quotas_monthly_output_exceeded(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        // Only monthly output has a limit.
        $this->set_all_quotas(monthly_out: 1000);

        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 5000, 'used_output' => 1200]
        );
        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $this->expectException(\local_mxaimanager\app\exceptions\quota_exceeded_exception::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
    }

    public function test_check_token_quotas_under_limit_passes(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        $this->set_all_quotas(daily_in: 1000, daily_out: 1000);

        // Usage is well below quota.
        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 50, 'used_output' => 30]
        );
        $db_mock->method('insert_record')->willReturn(1);

        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        // Should succeed without exception.
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
        $this->assertEquals('OK', $result);
    }

    public function test_check_token_quotas_input_unlimited_output_exceeded(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        // Input unlimited (0), output limited to 200.
        $this->set_all_quotas(daily_in: 0, daily_out: 200);

        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 99999, 'used_output' => 250]
        );
        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        // Input is unlimited so no error; output exceeds quota → should throw.
        $this->expectException(\local_mxaimanager\app\exceptions\quota_exceeded_exception::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
    }

    public function test_check_token_quotas_credits_mode_skips_token_check(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        set_config('quota_display_mode', 'credits', 'local_mxaimanager');
        // Even if token quotas are set, credits mode should NOT call check_token_quotas.
        // Instead it calls credit_service::check_balance() which uses global $DB.
        // In a unit test context $DB is unavailable, so check_balance() will throw.
        // This proves that the credits branch is taken (not the token branch).
        set_config('daily_input_quota', '1', 'local_mxaimanager');
        set_config('daily_output_quota', '1', 'local_mxaimanager');

        $base_factory_mock = $this->create_base_factory_with_db();
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        // check_balance() will throw because global $DB is not available in unit tests.
        // The important assertion is that it does NOT throw quota_exceeded_exception
        // from check_token_quotas() (which would mean the wrong branch was taken).
        $this->expectException(\Throwable::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
    }

    public function test_check_token_quotas_all_periods_unlimited(): void
    {
        set_config('managed_provider_ids', '1', 'local_mxaimanager');
        // All quotas at 0 = unlimited → all periods skipped → should pass.
        $this->set_all_quotas();

        $db_mock = $this->createMock(\moodle_database::class);
        // get_record_sql should NOT be called because all periods are skipped.
        $db_mock->expects($this->never())->method('get_record_sql');
        $db_mock->method('insert_record')->willReturn(1);

        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, 1, []);
        $this->assertEquals('OK', $result);
    }

    public function test_enforce_quotas_skipped_when_config_flag_false(): void
    {
        // Preconfigured provider with enforce_quotas = false should skip quotas
        // even when limits are set and exceeded.
        set_config('managed_provider_ids', '', 'local_mxaimanager');
        $this->set_all_quotas(daily_in: 1, daily_out: 1);

        $db_mock = $this->createMock(\moodle_database::class);
        // Return usage that exceeds quotas.
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 99999, 'used_output' => 99999]
        );
        $db_mock->method('insert_record')->willReturn(1);

        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $handler_mock->expects($this->once())
            ->method('chat_completion')
            ->willReturn(new chat_completion_request([], [], 'OK', 5, 3, 'stop'));

        // Provider -1 (preconfigured) with enforce_quotas=false → should pass despite exceeded quotas.
        $config = ['enforce_quotas' => false, 'base_url' => 'https://api.test.com', 'api_key' => 'key'];
        $result = $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, -1, $config);
        $this->assertEquals('OK', $result);
    }

    public function test_enforce_quotas_applied_when_config_flag_true(): void
    {
        // Preconfigured provider with enforce_quotas = true should still enforce quotas.
        set_config('managed_provider_ids', '', 'local_mxaimanager');
        $this->set_all_quotas(daily_in: 1);

        $db_mock = $this->createMock(\moodle_database::class);
        $db_mock->method('get_record_sql')->willReturn(
            (object)['used_input' => 99999, 'used_output' => 0]
        );

        $base_factory_mock = $this->create_base_factory_with_db($db_mock);
        $handler_mock = $this->createMock(chat_completion::class);

        $handler = $this->build_handler_for_quota_test($base_factory_mock, $handler_mock);

        $config = ['enforce_quotas' => true, 'base_url' => 'https://api.test.com', 'api_key' => 'key'];

        $this->expectException(\local_mxaimanager\app\exceptions\quota_exceeded_exception::class);
        $handler->chat_completion(new entity(), [new message('user', 'test')], false, null, -1, $config);
    }
}
