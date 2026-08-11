# Name: Moxis AI Manager

local_mxaimanager

## Description

This plugin is for using AI features in Moodle. You will be able to manage specific settings for AI usage in Moodle.

## Admin settings

None

## Setup

Normal installation

## Capabilities

None

## Usage in other plugins

This plugin provides a way to register AI features and their actions, as well as a handler to use these features.

### Registering AI Features & their actions

To register AI features & their actions in your plugin (usually placed in `db/install.php` and/or `db/upgrade.php`, you
can use the following code snippet:

```php
global $DB;

$transaction = $DB->start_delegated_transaction();
try {
    $base_factory = \local_mxaimanager\app\factory::make();

    /** Register an AI Feature **/
    $component = 'core'; // The component where the feature belongs, e.g., 'core', 'mod_forum', etc.
    $feature_name_identifier = 'chat'; // Language string identifier for name. Make sure to add it in your component's lang file.
    $description_identifier = 'feature_chat_description'; // Language string identifier for description. Make sure to add it in your component's lang file.
    $feature = $base_factory->ai()->feature()->entity();
    $feature->set_component($component);
    $feature->set_name_identifier($feature_name_identifier);
    $feature->set_description_identifier($description_identifier);
    $feature->set_id($base_factory->ai()->feature()->repository()->insert($feature));

    /** Register an AI Feature's actions **/
    $feature_actions = [
        $base_factory->ai()->feature()->action()->entity()
            ->set_feature_id($feature->get_id())
            ->set_action_interface(\local_mxaimanager\app\ai\provider\providers\interfaces\chat_completion::class);
        $base_factory->ai()->feature()->action()->entity()
            ->set_feature_id($feature->get_id())
            ->set_action_interface(\local_mxaimanager\app\ai\provider\providers\interfaces\create_embedding::class);
    ];
    foreach ($feature_actions as $feature_action) {
        $base_factory->ai()->feature()->action()->repository()->insert($feature_action);
    }
    $transaction->allow_commit();
} catch (\Throwable $e) {
    $transaction->rollback($e);
}
```

### AI Usage

To use the AI features you've registered, you can use the following code snippet:

```php
$base_factory = \local_mxaimanager\app\factory::make();

/** Feature usage **/
$component = 'core';
$feature_name_identifier = 'chat';
$feature = $base_factory->ai()->feature()->repository()->get_by_component_and_name_identifier(
    $component,
    $feature_name_identifier
);
$feature_handler = $base_factory->ai()->feature()->handler($feature);

/** Chat completion **/
$messages = [
    new \local_mxaimanager\app\ai\provider\message('system', 'You are only able to respond in JSON format.'),
    new \local_mxaimanager\app\ai\provider\message('user', 'Generate a list of three random colors.')
];
$response = $feature_handler->chat_completion($messages);

/** Create embedding **/
$input = 'Moodle is a learning platform designed to provide educators, administrators and learners with a single robust, secure and integrated system to create personalized learning environments.';
$dimension = 1536;
$embedding = $feature_handler->create_embedding(
    $input,
    $dimension
);

var_dump([
    'chat_completion' => [
        'messages' => $messages,
        'response' => $response
    ],
    'embedding' => [
        'input' => $input,
        'dimension' => $dimension,
        'response' => $embedding
    ]
]);
die();

```

## GDPR

None

## Change log

* **1.0.6 (2026081100)**
    - Fixed bug with providers using chat completion and sending multiple system role messages in the messages array
* **1.0.5 (2026040700)**
    - Removed support for text-to-image generation in nebius AI provider.
* **1.0.4 (2026011200)**
    - Added support for AI usage logging.
    - Added support for AI image generation.
    - Added support for AI audio transcription.
* **1.0.3 (2026010800)**
    - Added support for JSON schema validation for AI provider responses.
* **1.0.2 (2025120400)**
    - Added support for predefined AI providers.
    - Added a nebius AI provider.
* **1.0.1 (2025111900)**
    - Implemented vector API
* **1.0.0 (2025100100)**
    - Initial commit.
