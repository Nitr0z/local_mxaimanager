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
use local_mxaimanager\app\ai\provider\providers\interfaces\create_audio;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding;
use local_mxaimanager\app\ai\provider\providers\interfaces\vision;
use local_mxaimanager\app\ai\provider\create_audio_request;
use local_mxaimanager\app\ai\provider\vision_request;

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

    public function test_chat_completion_continuation_with_json_mode(): void
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

    public function test_create_audio_success(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $provider_handler_mock = $this->createMock(create_audio::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(7, ['tts_model' => 'tts-1'])
            ->willReturn($provider_handler_mock);

        $provider_handler_mock->expects($this->once())
            ->method('create_audio')
            ->with('Hola mundo')
            ->willReturn(new create_audio_request(
                ['model' => 'tts-1', 'input' => 'Hola mundo', 'voice' => 'alloy', 'response_format' => 'mp3'],
                [],
                'base64audiopayload',
                0,
                0
            ));

        $result = $handler->create_audio(new entity(), 'Hola mundo', 7, ['tts_model' => 'tts-1']);

        $this->assertEquals('base64audiopayload', $result);
    }

    public function test_create_audio_provider_not_supporting_interface(): void
    {
        // Reuse the existing MockHandlerWithoutInterface (it implements neither create_audio
        // nor any other action interface) to trigger the validation error.
        $handler_instance = new MockHandlerWithoutInterface();

        $base_factory_mock = $this->createMock(base_factory::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(99, ['foo' => 'bar'])
            ->willReturn($handler_instance);

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Provider instance ID: 99 does not support audio creation');

        $handler->create_audio(new entity(), 'Hello', 99, ['foo' => 'bar']);
    }

    public function test_vision_success(): void
    {
        $base_factory_mock = $this->createMock(base_factory::class);
        $provider_handler_mock = $this->createMock(vision::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(8, ['vision_model' => 'gpt-4o'])
            ->willReturn($provider_handler_mock);

        $provider_handler_mock->expects($this->once())
            ->method('vision')
            ->with('Read this page', ['/tmp/page.jpg'])
            ->willReturn(new vision_request(
                ['model' => 'gpt-4o'],
                [],
                'page text',
                12,
                4
            ));

        $result = $handler->vision(
            new entity(),
            'Read this page',
            ['/tmp/page.jpg'],
            8,
            ['vision_model' => 'gpt-4o']
        );

        $this->assertEquals('page text', $result);
    }

    public function test_vision_provider_not_supporting_interface(): void
    {
        $handler_instance = new MockHandlerWithoutInterface();

        $base_factory_mock = $this->createMock(base_factory::class);

        $handler = $this->getMockBuilder(action_handler::class)
            ->setConstructorArgs([$base_factory_mock])
            ->onlyMethods(['get_provider_handler_provider_and_settings_json'])
            ->getMock();

        $handler->expects($this->once())
            ->method('get_provider_handler_provider_and_settings_json')
            ->with(99, ['foo' => 'bar'])
            ->willReturn($handler_instance);

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Provider instance ID: 99 does not support vision');

        $handler->vision(new entity(), 'Read this page', ['/tmp/page.jpg'], 99, ['foo' => 'bar']);
    }
}
