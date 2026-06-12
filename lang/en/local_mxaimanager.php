<?php

$string['pluginname'] = 'Moxis AI Manager';

// Manage Providers
$string['manage:info'] = "<p>These settings will make it possible for you to define which AI providers (OpenAI, Mistral, eg.) are available on your site.</p><p>You'll also be able to configure:</p><ul><li>Which provider instance should be used by default.</li><li>Which model a provider instance should use by default.</li><li>Which model and/or just which provider instance should be used for a specific AI feature in your AI plugin.</li></ul>";
$string['manage_providers:title'] = 'AI Provider instances';
$string['manage_providers:table:name'] = "Instance Name";
$string['manage_providers:table:classname'] = "Provider Type";
$string['manage_providers:table:supported_actions'] = "Supported Actions";
$string['manage_providers:table:actions'] = "Actions";
$string['manage_providers:form:name'] = 'Name';
$string['manage_providers:form:type'] = 'Type';
$string['manage_providers:add_provider'] = 'Add AI Provider instance';
$string['manage_providers:edit_provider'] = 'Edit AI Provider instance';
$string['manage_providers:delete_provider'] = 'Delete AI Provider instance: "{$a}"';
$string['manage_providers:delete_confirm'] = 'Are you sure you want to delete this provider instance? This action cannot be undone.';
$string['here_you_define_providers'] = 'Here you define the AI provider instances that will be available on your site.';
$string['set_as_default'] = 'Set as default?';
$string['in_use'] = 'Already in use';

// Provider options help texts
$string['openai_chat_model'] = 'OpenAI Chat Model';
$string['openai_chat_model_help'] = 'Here you can specify the chat model that should be used. For example: <strong>gpt-4</strong>, <strong>gpt-3.5-turbo</strong>, etc. Refer to OpenAI\'s documentation for available models.';
$string['mistral_chat_model'] = 'Mistral Chat Model';
$string['mistral_chat_model_help'] = 'Here you can specify the chat model that should be used. For example: <strong>mistral-large</strong>, <strong>mistral-small</strong>, etc. Refer to Mistral\'s documentation for available models.';
$string['ollama_chat_model'] = 'Ollama Chat Model';
$string['ollama_chat_model_help'] = 'Here you can specify the chat model that should be used. For example: <strong>llama2</strong>, <strong>vicuna</strong>, etc. Refer to your Ollama\'s provider for available models.';
$string['nebius_chat_model'] = 'Nebius Chat Model';
$string['nebius_chat_model_help'] = 'Here you can specify the chat model that should be used. For example: <strong>Qwen/Qwen3-32B-fast</strong>, <strong>Qwen/Qwen3-30B-A3B-Instruct-2507</strong>, etc. Refer to Nebius\'s documentation for available models.';
$string['openai_embedding_model'] = 'OpenAI Embedding Model';
$string['openai_embedding_model_help'] = 'Here you can specify the embedding model that should be used. For example: <strong>text-embedding-3-small</strong>, <strong>text-embedding-3-large</strong>, etc. Refer to OpenAI\'s documentation for available embedding models.';
$string['mistral_embedding_model'] = 'Mistral Embedding Model';
$string['mistral_embedding_model_help'] = 'Here you can specify the embedding model that should be used. For example: <strong>mistral-embed</strong>, etc. Refer to Mistral\'s documentation for available embedding models.';
$string['ollama_embedding_model'] = 'Ollama Embedding Model';
$string['ollama_embedding_model_help'] = 'Here you can specify the embedding model that should be used. For example: <strong>nomic-embed-text</strong>, etc. Refer to your Ollama\'s provider for available embedding models.';
$string['nebius_embedding_model'] = 'Nebius Embedding Model';
$string['nebius_embedding_model_help'] = 'Here you can specify the embedding model that should be used. For example: <strong>Qwen/Qwen3-Embedding-8B</strong>, etc. Refer to Nebius\'s documentation for available embedding models.';
$string['scaleway_chat_model'] = 'Scaleway Chat Model';
$string['scaleway_chat_model_help'] = 'Specify the chat model to use. For example: <strong>llama-3.3-70b-instruct</strong>, <strong>qwen2.5-72b-instruct</strong>, <strong>deepseek-r1</strong>. Refer to Scaleway\'s documentation for available models.';
$string['scaleway_embedding_model'] = 'Scaleway Embedding Model';
$string['scaleway_embedding_model_help'] = 'Specify the embedding model to use. For example: <strong>sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2</strong>, <strong>baai/bge-multilingual-gemma2</strong>. Refer to Scaleway\'s documentation for available embedding models.';
$string['openai_image_model'] = 'OpenAI Image Model';
$string['openai_image_model_help'] = 'Here you can specify the image generation model that should be used. For example: <strong>dall-e-3</strong>, <strong>dall-e-2</strong>, etc. Refer to OpenAI\'s documentation for available image generation models.';
$string['nebius_image_model'] = 'Nebius Image Model';
$string['nebius_image_model_help'] = 'Here you can specify the image generation model that should be used. For example: <strong>black-forest-labs/flux-dev</strong>. Refer to Nebius\'s documentation for available image generation models.';
$string['scaleway_image_model'] = 'Scaleway Image Model';
$string['scaleway_image_model_help'] = 'Specify the image generation model to use. For example: <strong>black-forest-labs/flux-schnell</strong>, <strong>black-forest-labs/flux-dev</strong>. Refer to Scaleway\'s documentation for available image models.';
$string['openai_transcription_model'] = 'OpenAI Transcription Model';
$string['openai_transcription_model_help'] = 'Here you can specify the transcription model that should be used. For example: <strong>whisper-1</strong>. Refer to OpenAI\'s documentation for available transcription models.';
$string['mistral_transcription_model'] = 'Mistral Transcription Model';
$string['mistral_transcription_model_help'] = 'Here you can specify the transcription model that should be used. For example: <strong>mistral-whisper</strong>. Refer to Mistral\'s documentation for available transcription models.';

// Manage Features
$string['manage_features:title'] = 'AI Features';
$string['manage_features:table:component'] = 'Component';
$string['manage_features:table:name'] = 'Name';
$string['manage_features:table:description'] = 'Description';
$string['manage_features:table:ai_actions'] = 'Required AI Actions';
$string['manage_features:table:actions'] = 'Actions';
$string['manage_features:edit_feature_settings'] = 'Edit AI Feature settings: "{$a}"';
$string['manage_features:form:provider_id'] = 'Provider Instance';
$string['here_you_can_see_all_components_ai_features'] = 'Here you can see all components\' AI features that are available on your site. You can override the default provider instance and/or settings for each feature.';
$string['uses_chat'] = 'Chat';
$string['uses_embedding'] = 'Embeddings';
$string['uses_image'] = 'Image';
$string['uses_audio_transcriptions'] = 'Audio Transcriptions';

$string['base_url'] = 'Base URL';
$string['api_key'] = 'API Key';
$string['default_chat_model'] = 'Default Chat Model';
$string['default_embedding_model'] = 'Default Embedding Model';
$string['default_image_model'] = 'Default Image Model';
$string['default_transcription_model'] = 'Default Transcription Model';
$string['provider_settings'] = 'Provider Settings';
$string['supports_chat'] = 'Supports Chat';
$string['supports_embedding'] = 'Supports Embedding';
$string['supports_image'] = 'Supports Image';
$string['supports_audio_transcriptions'] = 'Supports Audio Transcriptions';
$string['provider_supports'] = 'Provider Capabilities';
$string['default_action_providers'] = 'Default Action Provider instances';
$string['here_you_define_default_action_providers'] = 'Here you define which provider instances should be used by default for each action.';
$string['you_have_configured_a_provider_and_set_the_default'] = 'You have configured a provider instance and set the default provider instance for all actions. You are now ready to use AI features in your AI plugins! :)';
$string['you_have_not_yet_configured_any_providers'] = 'You\'ve not yet configured any AI provider instances. Please add at least one provider <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">here</a>.';
$string['you_have_not_yet_configured_default_providers'] = 'You\'ve not yet configured default provider instances for all actions. Please configure default provider instances <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">here</a>.';
$string['no_available_providers'] = 'No available provider instances';
$string['this_provider_is_preconfigured_no_modify'] = 'This provider instance is preconfigured and cannot be modified.';

// Settings
$string['settings:manage_page'] = 'Manage AI Settings';

// Capabilities
$string['mxaimanager:manage_configuration'] = 'Manage Moxis AI Manager configuration';

// Privacy
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs'] = 'This table stores logs of feature action usage for the Moxis AI Manager plugin.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:id'] = 'ID';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:feature_id'] = 'The ID of the AI feature that was used.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:request_json'] = 'The JSON request sent to the AI provider.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:response_json'] = 'The JSON response received from the AI provider.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:input_tokens'] = 'The number of input tokens used in the request.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:output_tokens'] = 'The number of output tokens received in the response.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:session_id'] = 'The session ID associated with the request.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:user_id'] = 'The ID of the user who made the request.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:timecreated'] = 'The timestamp when the log entry was created.';
