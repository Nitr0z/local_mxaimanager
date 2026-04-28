<?php

namespace local_mxaimanager\unit\app\ai\provider\providers;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

global $CFG;
require_once $CFG->libdir . '/formslib.php';

use local_mxaimanager\app\exceptions\invalid_provider_instance_configuration;
use local_mxaimanager\app\exceptions\invalid_provider_instance_response;
use PHPUnit\Framework\MockObject\MockObject;

class scaleway_test extends \base_testcase
{
    private MockObject $mock_base_factory;
    private MockObject $mock_curl;

    protected function setUp(): void
    {
        $this->mock_curl = $this->createMock(\curl::class);
        $this->mock_base_factory = $this->createMock(\local_mxaimanager\app\factory::class);
        $this->mock_base_factory->method('curl')->willReturn($this->mock_curl);
    }

    // --- Constructor tests ---

    public function test_constructor_with_valid_config(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
            'embedding_model' => 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2',
            'image_model' => 'black-forest-labs/flux-schnell',
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $this->assertInstanceOf(\local_mxaimanager\app\ai\provider\providers\scaleway::class, $provider);
    }

    public function test_constructor_missing_base_url(): void
    {
        $json_config = [
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Scaleway is missing base URL and/or API key');

        new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_missing_api_key(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Scaleway is missing base URL and/or API key');

        new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );
    }

    public function test_constructor_empty_config(): void
    {
        $json_config = [];

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Scaleway is missing base URL and/or API key');

        new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );
    }

    // --- Chat completion tests ---

    public function test_chat_completion_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $expected_response = '{"choices":[{"message":{"content":"Bonjour!"},"finish_reason":"stop"}], "usage":{"prompt_tokens": 5, "completion_tokens": 3}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.scaleway.ai/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['messages']) && $decoded['model'] === 'llama-3.3-70b-instruct';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages);

        $this->assertEquals('Bonjour!', $result->get_response());
        $this->assertEquals('stop', $result->get_finish_reason());
    }

    public function test_chat_completion_finish_reason_length(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\"partial\":"},"finish_reason":"length"}], "usage":{"prompt_tokens": 5, "completion_tokens": 10}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages);

        $this->assertEquals('{"partial":', $result->get_response());
        $this->assertEquals('length', $result->get_finish_reason());
    }

    public function test_chat_completion_missing_chat_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Scaleway chat model is not configured');

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_json_decode_error(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $provider->chat_completion($messages);
    }

    public function test_chat_completion_with_json_mode(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $expected_response = '{"choices":[{"message":{"content":"{\"key\": \"value\"}"}}], "usage":{"prompt_tokens": 5, "completion_tokens": 5}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.scaleway.ai/v1/chat/completions',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['response_format']) &&
                        $decoded['response_format']['type'] === 'json_object';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $messages = [['role' => 'user', 'content' => 'Hello']];
        $result = $provider->chat_completion($messages, true);

        $this->assertEquals('{"key": "value"}', $result->get_response());
    }

    // --- Embedding tests ---

    public function test_get_embedding_success(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
            'embedding_model' => 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2',
        ];

        $expected_response = '{"data":[{"embedding":[0.1, 0.2, 0.3]}],"usage":{"prompt_tokens":3,"total_tokens":3}}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.scaleway.ai/v1/embeddings',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model']) &&
                        $decoded['model'] === 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2' &&
                        isset($decoded['input']);
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->get_embedding('test input', null);

        $this->assertEquals([0.1, 0.2, 0.3], $result->get_response());
    }

    public function test_get_embedding_missing_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Scaleway embedding model is not configured');

        $provider->get_embedding('test input', null);
    }

    // --- Image generation tests ---

    public function test_create_image_success_url(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
            'image_model' => 'black-forest-labs/flux-schnell',
        ];

        $expected_response = '{"data":[{"url":"https://example.com/image.png"}]}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->with(
                'https://api.scaleway.ai/v1/images/generations',
                $this->callback(function ($data) {
                    $decoded = json_decode($data, true);
                    return isset($decoded['model'], $decoded['prompt']) &&
                        $decoded['model'] === 'black-forest-labs/flux-schnell' &&
                        $decoded['response_format'] === 'url';
                })
            )
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->create_image('A test image', false);

        $this->assertEquals('https://example.com/image.png', $result->get_response());
    }

    public function test_create_image_success_b64(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
            'image_model' => 'black-forest-labs/flux-schnell',
        ];

        $expected_response = '{"data":[{"b64_json":"base64encodedimage"}]}';

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn($expected_response);

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $result = $provider->create_image('A test image', true);

        $this->assertEquals('base64encodedimage', $result->get_response());
    }

    public function test_create_image_missing_model(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
        ];

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_configuration::class);
        $this->expectExceptionMessage('Scaleway image model is not configured');

        $provider->create_image('A test image', false);
    }

    public function test_create_image_invalid_response(): void
    {
        $json_config = [
            'base_url' => 'https://api.scaleway.ai/v1',
            'api_key' => 'scw-test-key',
            'chat_model' => 'llama-3.3-70b-instruct',
            'image_model' => 'black-forest-labs/flux-schnell',
        ];

        $this->mock_curl->expects($this->once())
            ->method('post')
            ->willReturn('invalid json');

        $provider = new \local_mxaimanager\app\ai\provider\providers\scaleway(
            $this->mock_base_factory,
            $json_config
        );

        $this->expectException(invalid_provider_instance_response::class);

        $provider->create_image('A test image', false);
    }

    // --- Form tests ---

    public function test_moodleform_validation_with_valid_data(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.scaleway.ai/v1',
            'prefix_api_key' => 'scw-test-key',
            'prefix_chat_model' => 'llama-3.3-70b-instruct',
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\scaleway::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertEmpty($errors);
    }

    public function test_moodleform_validation_missing_base_url(): void
    {
        $data = [
            'prefix_api_key' => 'scw-test-key',
            'prefix_chat_model' => 'llama-3.3-70b-instruct',
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\scaleway::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_base_url', $errors);
    }

    public function test_moodleform_validation_missing_api_key(): void
    {
        $data = [
            'prefix_base_url' => 'https://api.scaleway.ai/v1',
            'prefix_chat_model' => 'llama-3.3-70b-instruct',
        ];

        $errors = \local_mxaimanager\app\ai\provider\providers\scaleway::moodleform_validation(
            $data,
            'prefix_'
        );

        $this->assertArrayHasKey('prefix_api_key', $errors);
    }

    public function test_implements_correct_interfaces(): void
    {
        $interfaces = class_implements(\local_mxaimanager\app\ai\provider\providers\scaleway::class);

        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class,
            $interfaces
        );
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class,
            $interfaces
        );
        $this->assertContains(
            \local_mxaimanager\app\ai\provider\providers\interfaces\create_image::class,
            $interfaces
        );
    }
}
