<?php

namespace local_mxaimanager\unit\app\ai\provider\providers;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->libdir . '/formslib.php';

use local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_audio;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding;
use local_mxaimanager\app\ai\provider\providers\interfaces\create_image;
use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use PHPUnit\Framework\MockObject\MockObject;

class openai_test extends \base_testcase
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
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3',
            'transcription_model' => 'whisper-1'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->assertInstanceOf(\local_mxaimanager\app\ai\provider\providers\openai::class, $provider);
    }

    public function test_constructor_missing_base_url(): void
    {
        $json_config = [
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_missing_api_key(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_missing_both_base_url_and_api_key(): void
    {
        $json_config = [
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_empty_config(): void
    {
        $json_config = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OpenAI is missing base url and/or api key');

        new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_chat_completion_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $expected_response = '{"choices":[{"message":{"content":"Hello, world!"},"finish_reason":"stop"}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages']) && $decoded['model'] === 'gpt-3.5-turbo';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
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
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\\"partial\\":"},"finish_reason":"length"}], "usage":{"prompt_tokens": 5, "completion_tokens": 10}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
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
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        // Response without finish_reason field
        $expected_response = '{"choices":[{"message":{"content":"Hello"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
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
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Chat model is not configured');

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_json_decode_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_curl_post_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->will($this->throwException(new \Exception('Connection failed')));

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection failed');

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_with_json_mode(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\\"key\\": \\"value\\"}"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages'], $decoded['response_format']) &&
                        $decoded['model'] === 'gpt-3.5-turbo' &&
                        $decoded['response_format']['type'] === 'json_object';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
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
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
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
                'https://api.openai.com/v1/chat/completions',
                $this->callback(function ($data) use ($json_schema) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages'], $decoded['response_format']) &&
                        $decoded['model'] === 'gpt-3.5-turbo' &&
                        $decoded['response_format']['type'] === 'json_schema' &&
                        $decoded['response_format']['json_schema']['schema'] === $json_schema;
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
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
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
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
                'https://api.openai.com/v1/chat/completions',
                $this->callback(function ($data) use ($json_schema) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages'], $decoded['response_format']) &&
                        $decoded['model'] === 'gpt-3.5-turbo' &&
                        $decoded['response_format']['type'] === 'json_schema' &&
                        $decoded['response_format']['json_schema']['schema'] === $json_schema;
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages, true, $json_schema); // json_schema should take precedence

        $this->assertEquals('{"name": "John"}', $result->get_response());
    }

    public function test_create_image_url_not_supported(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3'
        ];

        // The new OpenAI image API only returns base64, so requesting a URL (return_b64 = false)
        // is no longer supported and must fail before any request is made.
        $this->mock_curl->expects($this->never())
            ->method('post');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Openai new API only accepts b64_json');

        $provider->create_image('A test image', false);
    }

    public function test_create_image_success_b64(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'gpt-image-1',
        ];

        $expected_response = '{"data":[{"b64_json":"base64encodedimage"}]}';

        // The OpenAI /v1/images/generations endpoint no longer accepts response_format, so the
        // provider must send only model and prompt and always read b64_json from the response.
        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/images/generations',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['prompt']) &&
                        !isset($decoded['response_format']) &&
                        $decoded['model'] === 'gpt-image-1' &&
                        $decoded['prompt'] === 'A test image';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->create_image('A test image', true);

        $this->assertEquals('base64encodedimage', $result->get_response());
    }

    public function test_create_image_missing_image_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Image model is not configured');

        $provider->create_image('A test image', false);
    }

    public function test_create_image_curl_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->will($this->throwException(new \Exception('Connection failed')));

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection failed');

        $provider->create_image('A test image', true);
    }

    public function test_create_image_invalid_response(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);

        $provider->create_image('A test image', true);
    }

    public function test_get_embedding_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $expected_response = '{"data":[{"embedding":[0.1, 0.2, 0.3]}],"usage":{"prompt_tokens":1,"total_tokens":1}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/embeddings',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model']) &&
                        $decoded['model'] === 'text-embedding-ada-002' &&
                        isset($decoded['input']);
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->get_embedding('test input', null);

        $this->assertEquals([0.1, 0.2, 0.3], $result->get_response());
    }

    public function test_get_embedding_with_dimension(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $expected_response = '{"data":[{"embedding":[0.1, 0.2, 0.3]}],"usage":{"prompt_tokens":1,"total_tokens":1}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/embeddings',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model']) &&
                        $decoded['model'] === 'text-embedding-ada-002' &&
                        isset($decoded['input']) &&
                        isset($decoded['dimensions']) &&
                        $decoded['dimensions'] === 512;
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->get_embedding('test input', 512);

        $this->assertEquals([0.1, 0.2, 0.3], $result->get_response());
    }

    public function test_get_embedding_missing_embedding_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Embedding model is not configured');

        $provider->get_embedding('test input', null);
    }

    public function test_get_embedding_json_decode_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);

        $provider->get_embedding('test input', null);
    }

    public function test_create_transcription_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3',
            'transcription_model' => 'whisper-1'
        ];

        $expected_response = '{"text":"Hello, world!","segments":[{"start":0,"end":1,"text":"Hello"}],"usage":{"prompt_tokens":10,"completion_tokens":5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/audio/transcriptions',
                $this->callback(function ($data) {
                    return is_array($data) &&
                        isset($data['model']) && $data['model'] === 'whisper-1' &&
                        isset($data['file']) && $data['file'] instanceof \CURLFile &&
                        isset($data['response_format']) && $data['response_format'] === 'verbose_json';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        // Create a temporary audio file
        $temp_file = tempnam(sys_get_temp_dir(), 'test_audio');
        file_put_contents($temp_file, 'fake audio content');

        $result = $provider->create_transcription($temp_file);

        $this->assertEquals('Hello, world!', $result->get_response()->get_text());
        $this->assertEquals([['start' => 0, 'end' => 1, 'text' => 'Hello']], $result->get_response()->get_segments());

        unlink($temp_file);
    }

    public function test_create_transcription_missing_transcription_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transcription model is not configured');

        $temp_file = tempnam(sys_get_temp_dir(), 'test_audio');
        $provider->create_transcription($temp_file);
        unlink($temp_file);
    }

    public function test_create_transcription_curl_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3',
            'transcription_model' => 'whisper-1'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->will($this->throwException(new \Exception('Connection failed')));

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Connection failed');

        $temp_file = tempnam(sys_get_temp_dir(), 'test_audio');
        $provider->create_transcription($temp_file);
        unlink($temp_file);
    }

    public function test_create_transcription_invalid_response(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3',
            'transcription_model' => 'whisper-1'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);

        $temp_file = tempnam(sys_get_temp_dir(), 'test_audio');
        $provider->create_transcription($temp_file);
        unlink($temp_file);
    }

    public function test_moodleform_definition(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        // 6 existing fields (base_url, api_key, chat_model, embedding_model, image_model,
        // transcription_model) + 3 TTS fields + vision_model.
        $mform->expects($this->exactly(10))->method('addElement');
        // setType is called only on text fields, not on selects - TTS adds 1 text + 2 selects.
        $mform->expects($this->exactly(8))->method('setType');
        // setDefault was called for base_url + api_key; TTS adds defaults for voice and format.
        $mform->expects($this->exactly(4))->method('setDefault');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\openai::moodleform_definition(
            $mform,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_moodleform_validation_with_valid_data(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'gpt-3.5-turbo',
            'prefix_embedding_model' => 'text-embedding-ada-002',
            'prefix_image_model' => 'some-image-model',
            'prefix_transcription_model' => 'whisper-1',
            'prefix_tts_model' => 'tts-1'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertEmpty($errors);
    }

    public function test_moodleform_validation_missing_base_url(): void
    {
        $data = [
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'gpt-3.5-turbo',
            'prefix_embedding_model' => 'text-embedding-ada-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
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
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_chat_model' => 'gpt-3.5-turbo',
            'prefix_embedding_model' => 'text-embedding-ada-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
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
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_api_key' => 'test_key',
            'prefix_embedding_model' => 'text-embedding-ada-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
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
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'gpt-3.5-turbo'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_embedding_model', $errors);
        $this->assertIsString($errors['prefix_embedding_model']);
        $this->assertNotEmpty($errors['prefix_embedding_model']);
    }

    public function test_moodleform_validation_missing_image_model(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'gpt-3.5-turbo',
            'prefix_embedding_model' => 'text-embedding-ada-002'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_image_model', $errors);
        $this->assertIsString($errors['prefix_image_model']);
        $this->assertNotEmpty($errors['prefix_image_model']);
    }

    public function test_moodleform_validation_missing_transcription_model(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'gpt-3.5-turbo',
            'prefix_embedding_model' => 'text-embedding-ada-002',
            'prefix_image_model' => 'some-image-model'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_transcription_model', $errors);
        $this->assertIsString($errors['prefix_transcription_model']);
        $this->assertNotEmpty($errors['prefix_transcription_model']);
    }

    public function test_moodleform_validation_missing_tts_model(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.openai.com',
            'prefix_api_key' => 'test_key',
            'prefix_chat_model' => 'gpt-3.5-turbo',
            'prefix_embedding_model' => 'text-embedding-ada-002',
            'prefix_image_model' => 'some-image-model',
            'prefix_transcription_model' => 'whisper-1'
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\openai::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_tts_model', $errors);
        $this->assertIsString($errors['prefix_tts_model']);
        $this->assertNotEmpty($errors['prefix_tts_model']);
    }

    public function test_action_moodleform_definition_chat_completion(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        $mform->expects($this->once())->method('addElement');
        $mform->expects($this->once())->method('setType');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\openai::action_moodleform_definition(
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

        \local_mxaimanager\app\ai\provider\providers\openai::action_moodleform_definition(
            $mform,
            create_embedding::class,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_action_moodleform_definition_create_image(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        $mform->expects($this->once())->method('addElement');
        $mform->expects($this->once())->method('setType');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\openai::action_moodleform_definition(
            $mform,
            create_image::class,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_action_moodleform_definition_create_transcription(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        $mform->expects($this->once())->method('addElement');
        $mform->expects($this->once())->method('setType');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\openai::action_moodleform_definition(
            $mform,
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_transcription::class,
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

        \local_mxaimanager\app\ai\provider\providers\openai::action_moodleform_definition(
            $mform,
            'Some\\Unknown\\Interface',
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_action_moodleform_definition_create_audio(): void
    {
        $mform = $this->createMock(\MoodleQuickForm::class);

        // TTS adds 3 elements: tts_model (text + setType) and tts_voice/tts_format (selects + setDefault).
        $mform->expects($this->exactly(3))->method('addElement');
        $mform->expects($this->once())->method('setType');
        $mform->expects($this->exactly(2))->method('setDefault');

        $element_name_prefix = 'test_';

        \local_mxaimanager\app\ai\provider\providers\openai::action_moodleform_definition(
            $mform,
            create_audio::class,
            $element_name_prefix
        );

        // The static method was called successfully if no exception was thrown
        $this->assertTrue(true);
    }

    public function test_create_audio_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3',
            'transcription_model' => 'whisper-1',
            'tts_model' => 'tts-1',
            'tts_voice' => 'nova',
            'tts_format' => 'mp3'
        ];

        $raw_audio = 'binary-audio-bytes';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/audio/speech',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['input'], $decoded['voice'], $decoded['response_format']) &&
                        $decoded['model'] === 'tts-1' &&
                        $decoded['input'] === 'Hola mundo' &&
                        $decoded['voice'] === 'nova' &&
                        $decoded['response_format'] === 'mp3';
                })
            )
            ->willReturn($raw_audio);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->create_audio('Hola mundo');

        $this->assertEquals(base64_encode($raw_audio), $result->get_response());
        $this->assertEquals(0, $result->get_input_tokens());
        $this->assertEquals(0, $result->get_output_tokens());
    }

    public function test_create_audio_uses_default_voice_and_format_when_empty(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'dall-e-3',
            'transcription_model' => 'whisper-1',
            'tts_model' => 'tts-1'
            // tts_voice and tts_format intentionally omitted.
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/audio/speech',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return $decoded['voice'] === 'alloy' && $decoded['response_format'] === 'mp3';
                })
            )
            ->willReturn('binary-audio-bytes');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->create_audio('Test');

        $this->assertNotEmpty($result->get_response());
    }

    public function test_create_audio_missing_tts_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('TTS model is not configured');

        $provider->create_audio('Hello');
    }

    public function test_create_audio_invalid_voice(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'tts_model' => 'tts-1',
            'tts_voice' => 'not-a-real-voice',
            'tts_format' => 'mp3'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('TTS voice "not-a-real-voice" is not supported by OpenAI');

        $provider->create_audio('Hello');
    }

    public function test_create_audio_invalid_format(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'tts_model' => 'tts-1',
            'tts_voice' => 'alloy',
            'tts_format' => 'ogg-vorbis'
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('TTS format "ogg-vorbis" is not supported by OpenAI');

        $provider->create_audio('Hello');
    }

    public function test_create_audio_curl_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'tts_model' => 'tts-1',
            'tts_voice' => 'alloy',
            'tts_format' => 'mp3'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->will($this->throwException(new \Exception('Connection failed')));

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);
        $this->expectExceptionMessage('Connection failed');

        $provider->create_audio('Hello');
    }

    public function test_create_audio_api_error_response(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'tts_model' => 'tts-1',
            'tts_voice' => 'alloy',
            'tts_format' => 'mp3'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('{"error":{"message":"Invalid API key","type":"invalid_request_error"}}');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);
        $this->expectExceptionMessage('OpenAI TTS error');

        $provider->create_audio('Hello');
    }

    public function test_create_audio_empty_response(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'tts_model' => 'tts-1',
            'tts_voice' => 'alloy',
            'tts_format' => 'mp3'
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('');

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);
        $this->expectExceptionMessage('Empty audio response from OpenAI TTS');

        $provider->create_audio('Hello');
    }

    public function test_vision_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'gpt-image-1',
            'transcription_model' => 'whisper-1',
            'tts_model' => 'tts-1',
            'vision_model' => 'gpt-4o',
        ];

        $image = tempnam(sys_get_temp_dir(), 'vision') . '.jpg';
        file_put_contents($image, 'fakejpeg');

        $expected_response = '{"choices":[{"message":{"content":"Hello from the page"},"finish_reason":"stop"}],"usage":{"prompt_tokens":12,"completion_tokens":4}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.openai.com/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return ($decoded['model'] ?? '') === 'gpt-4o'
                        && ($decoded['messages'][0]['content'][0]['type'] ?? '') === 'text'
                        && ($decoded['messages'][0]['content'][1]['type'] ?? '') === 'image_url';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->vision('Read this page', [$image]);
        $this->assertEquals('Hello from the page', $result->get_response());
        $this->assertEquals(12, $result->get_input_tokens());

        unlink($image);
    }

    public function test_vision_missing_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.openai.com',
            'api_key' => 'test_key',
            'chat_model' => 'gpt-3.5-turbo',
            'embedding_model' => 'text-embedding-ada-002',
            'image_model' => 'gpt-image-1',
            'transcription_model' => 'whisper-1',
            'tts_model' => 'tts-1',
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\openai(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $provider->vision('Read this page', ['/tmp/missing.jpg']);
    }
}
