<?php

namespace unit\app\ai\provider\providers;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->libdir . '/formslib.php';

use local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding;
use PHPUnit\Framework\MockObject\MockObject;

class nebius_test extends \base_testcase
{
    private MockObject $mock_base_factory;
    private MockObject $mock_curl;

    protected function setUp(): void
    {
        $this->mock_curl = $this->createMock(\curl::class);
        $this->mock_base_factory = $this->createMock(\local_mxaimanager\app\factory::class);
        $this->mock_base_factory->method('curl')->willReturn($this->mock_curl);
    }

    public function test_constructor_with_valid_config(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $this->assertInstanceOf(\local_mxaimanager\app\ai\provider\providers\nebius::class, $provider);
    }

    public function test_constructor_missing_base_url(): void
    {
        $json_config = [
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Nebius is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_missing_api_key(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Nebius is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_missing_both_base_url_and_api_key(): void
    {
        $json_config = [
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Nebius is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_empty_config(): void
    {
        $json_config = [];

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Nebius is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_chat_completion_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $expected_response = '{"choices":[{"message":{"content":"Hello, world!"},"finish_reason":"stop"}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.tokenfactory.nebius.com/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages']) && $decoded['model'] === 'qwen-turbo';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages);

        $this->assertEquals('Hello, world!', $result->get_response());
        $this->assertEquals('stop', $result->get_finish_reason());
    }

    public function test_chat_completion_finish_reason_length(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\\"partial\\":"},"finish_reason":"length"}], "usage":{"prompt_tokens": 5, "completion_tokens": 10}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages);

        $this->assertEquals('{"partial":', $result->get_response());
        $this->assertEquals('length', $result->get_finish_reason());
    }

    public function test_chat_completion_finish_reason_defaults_to_stop(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        // Response without finish_reason field
        $expected_response = '{"choices":[{"message":{"content":"Hello"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages);

        $this->assertEquals('stop', $result->get_finish_reason());
    }

    public function test_chat_completion_missing_chat_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Chat model is not configured');

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_json_decode_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_response::class);

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_curl_post_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->will($this->throwException(new \Exception('Connection failed')));

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_response::class);
        $this->expectExceptionMessage('Invalid response from Nebius: Connection failed');

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_with_json_mode(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\\"key\\": \\"value\\"}"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.tokenfactory.nebius.com/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages'], $decoded['response_format']) &&
                        $decoded['model'] === 'qwen-turbo' &&
                        $decoded['response_format']['type'] === 'json_object';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages, true);

        $this->assertEquals('{"key": "value"}', $result->get_response());
    }

    public function test_chat_completion_with_json_schema(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $json_schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer']
            ]
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\\"name\\": \\"John\\", \\"age\\": 30}"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.tokenfactory.nebius.com/v1/chat/completions',
                $this->callback(function ($data) use ($json_schema) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages'], $decoded['response_format']) &&
                        $decoded['model'] === 'qwen-turbo' &&
                        $decoded['response_format']['type'] === 'json_schema' &&
                        $decoded['response_format']['json_schema']['schema'] === $json_schema;
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages, false, $json_schema);

        $this->assertEquals('{"name": "John", "age": 30}', $result->get_response());
    }

    public function test_chat_completion_json_mode_and_schema_precedence(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $json_schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string']
            ]
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\\"name\\": \\"John\\"}"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.tokenfactory.nebius.com/v1/chat/completions',
                $this->callback(function ($data) use ($json_schema) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages'], $decoded['response_format']) &&
                        $decoded['model'] === 'qwen-turbo' &&
                        $decoded['response_format']['type'] === 'json_schema' &&
                        $decoded['response_format']['json_schema']['schema'] === $json_schema;
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages, true, $json_schema); // json_schema should take precedence

        $this->assertEquals('{"name": "John"}', $result->get_response());
    }

    public function test_get_embedding_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $expected_response = '{"data":[{"embedding":[0.1, 0.2, 0.3]}],"usage":{"prompt_tokens":1,"total_tokens":1}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.tokenfactory.nebius.com/v1/embeddings',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model']) &&
                        $decoded['model'] === 'text-embedding-qwen-002' &&
                        isset($decoded['input']);
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->get_embedding('test input', null);

        $this->assertEquals([0.1, 0.2, 0.3], $result->get_response());
    }

    public function test_get_embedding_with_dimension(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $expected_response = '{"data":[{"embedding":[0.1, 0.2, 0.3]}],"usage":{"prompt_tokens":1,"total_tokens":1}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.tokenfactory.nebius.com/v1/embeddings',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model']) &&
                        $decoded['model'] === 'text-embedding-qwen-002' &&
                        isset($decoded['input']) &&
                        isset($decoded['dimensions']) &&
                        $decoded['dimensions'] === 512;
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->get_embedding('test input', 512);

        $this->assertEquals([0.1, 0.2, 0.3], $result->get_response());
    }

    public function test_get_embedding_missing_embedding_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Embedding model is not configured');

        $provider->get_embedding('test input', null);
    }

    public function test_get_embedding_json_decode_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.tokenfactory.nebius.com',
            'api_key' => 'test_key',
            'chat_model' => 'qwen-turbo',
            'embedding_model' => 'text-embedding-qwen-002'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\nebius(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\local_mxaimanager\app\exceptions\invalid_provider_instance_response::class);

        $provider->get_embedding('test input', null);
    }

    public function test_moodleform_definition(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        // Expectations for all the element additions
        $mform->expects($this->exactly(5))->method('addElement');
        $mform->expects($this->exactly(5))->method('setType');
        $mform->expects($this->exactly(2))->method('setDefault');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\nebius::moodleform_definition(
            $mform,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_moodleform_validation_with_valid_data(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.tokenfactory.nebius.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'qwen-turbo',
            'prefix_embedding_model' => 'text-embedding-qwen-002',
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\nebius::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertEmpty($errors);
    }

    public function test_moodleform_validation_missing_base_url(): void
    {
        $data = [
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'qwen-turbo',
            'prefix_embedding_model' => 'text-embedding-qwen-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\nebius::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_base_url', $errors);
        $this->assertIsString($errors['prefix_base_url']);
        $this->assertNotEmpty($errors['prefix_base_url']);
    }

    public function test_moodleform_validation_missing_api_key(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.tokenfactory.nebius.com',
            'prefix_chat_model' => 'qwen-turbo',
            'prefix_embedding_model' => 'text-embedding-qwen-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\nebius::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_api_key', $errors);
        $this->assertIsString($errors['prefix_api_key']);
        $this->assertNotEmpty($errors['prefix_api_key']);
    }

    public function test_moodleform_validation_missing_chat_model(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.tokenfactory.nebius.com',
            'prefix_api_key' => 'test_key',
            'prefix_embedding_model' => 'text-embedding-qwen-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\nebius::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_chat_model', $errors);
        $this->assertIsString($errors['prefix_chat_model']);
        $this->assertNotEmpty($errors['prefix_chat_model']);
    }

    public function test_moodleform_validation_missing_embedding_model(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.tokenfactory.nebius.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'qwen-turbo'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\nebius::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_embedding_model', $errors);
        $this->assertIsString($errors['prefix_embedding_model']);
        $this->assertNotEmpty($errors['prefix_embedding_model']);
    }

    public function test_action_moodleform_definition_chat_completion(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        $mform->expects($this->once())->method('addElement');
        $mform->expects($this->once())->method('setType');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\nebius::action_moodleform_definition(
            $mform,
            chat_completion::class,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_action_moodleform_definition_create_embedding(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        $mform->expects($this->once())->method('addElement');
        $mform->expects($this->once())->method('setType');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\nebius::action_moodleform_definition(
            $mform,
            create_embedding::class,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_action_moodleform_definition_unknown_interface(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        $mform->expects($this->never())->method('addElement');
        $mform->expects($this->never())->method('setType');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\nebius::action_moodleform_definition(
            $mform,
            'Some\\Unknown\\Interface',
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }
}
