<?php

namespace local_mxaimanager\unit\app\ai\provider\providers;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();

// @codeCoverageIgnoreEnd

use local_mxaimanager\app\ai\provider\message;
use local_mxaimanager\app\ai\provider\providers\provider;
use local_mxaimanager\app\factory as base_factory;
use ReflectionMethod;

class provider_test extends \base_testcase
{
    private provider $provider;

    protected function setUp(): void
    {
        $this->provider = new class ($this->createMock(base_factory::class), []) extends provider {
            public function __construct(base_factory $base_factory, array $json_config)
            {
                $this->base_factory = $base_factory;
            }

            public static function moodleform_definition(\MoodleQuickForm $mform, string $element_name_prefix): void
            {
            }

            public static function moodleform_validation(array $data, string $element_name_prefix): array
            {
                return [];
            }

            public static function action_moodleform_definition(
                \MoodleQuickForm $mform,
                string $interface,
                string $element_name_prefix
            ): void {
            }
        };
    }

    /**
     * @param message[] $messages
     * @return message[]
     */
    private function call_merge_system_messages(array $messages): array
    {
        $method = new ReflectionMethod(provider::class, 'merge_system_messages');
        $method->setAccessible(true);

        return $method->invoke($this->provider, $messages);
    }

    public function test_merge_system_messages_single_system_already_at_index_zero(): void
    {
        $messages = [
            new message('system', 'You are a helpful assistant.'),
            new message('user', 'Hello'),
            new message('assistant', 'Hi there'),
        ];

        $result = $this->call_merge_system_messages($messages);

        $this->assertCount(3, $result);
        $this->assertSame('system', $result[0]->get_role());
        $this->assertSame('You are a helpful assistant.', $result[0]->get_content());
        $this->assertSame('user', $result[1]->get_role());
        $this->assertSame('Hello', $result[1]->get_content());
        $this->assertSame('assistant', $result[2]->get_role());
        $this->assertSame('Hi there', $result[2]->get_content());
    }

    public function test_merge_system_messages_system_messages_at_bottom(): void
    {
        $messages = [
            new message('user', 'First user message'),
            new message('assistant', 'First reply'),
            new message('user', 'Second user message'),
            new message('system', 'System instruction A'),
            new message('system', 'System instruction B'),
        ];

        $result = $this->call_merge_system_messages($messages);

        $this->assertCount(4, $result);
        $this->assertSame('system', $result[0]->get_role());
        $this->assertSame("System instruction A\nSystem instruction B", $result[0]->get_content());
        $this->assertSame('user', $result[1]->get_role());
        $this->assertSame('First user message', $result[1]->get_content());
        $this->assertSame('assistant', $result[2]->get_role());
        $this->assertSame('First reply', $result[2]->get_content());
        $this->assertSame('user', $result[3]->get_role());
        $this->assertSame('Second user message', $result[3]->get_content());
    }

    public function test_merge_system_messages_system_messages_spread_throughout(): void
    {
        $messages = [
            new message('system', 'Preamble'),
            new message('user', 'Question one'),
            new message('system', 'Middle rule'),
            new message('assistant', 'Answer one'),
            new message('user', 'Question two'),
            new message('system', 'Closing rule'),
        ];

        $result = $this->call_merge_system_messages($messages);

        $this->assertCount(4, $result);
        $this->assertSame('system', $result[0]->get_role());
        $this->assertSame("Preamble\nMiddle rule\nClosing rule", $result[0]->get_content());
        $this->assertSame('user', $result[1]->get_role());
        $this->assertSame('Question one', $result[1]->get_content());
        $this->assertSame('assistant', $result[2]->get_role());
        $this->assertSame('Answer one', $result[2]->get_content());
        $this->assertSame('user', $result[3]->get_role());
        $this->assertSame('Question two', $result[3]->get_content());
    }
}
