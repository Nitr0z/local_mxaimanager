<?php

$string['pluginname'] = 'Moxis AI Manager';

// Manage Providers
$string['manage:info'] = "<p>Disse indstillinger gør det muligt for dig at definere, hvilke AI-providers (OpenAI, Mistral, osv.) der er tilgængelige på dit site.</p><p>Du vil også kunne konfigurere:</p><ul><li>Hvilken provider-instans der skal bruges som standard.</li><li>Hvilken model en provider-instans skal bruge som standard.</li><li>Hvilken model og/eller hvilken provider-instans der skal bruges til en specifik AI-funktion i dit AI-plugin.</li></ul>";
$string['manage_providers:title'] = 'AI Provider-instanser';
$string['manage_providers:table:name'] = "Instansnavn";
$string['manage_providers:table:classname'] = "Provider Type";
$string['manage_providers:table:supported_actions'] = "Understøttede Handling";
$string['manage_providers:table:actions'] = "Handlinger";
$string['manage_providers:form:name'] = 'Navn';
$string['manage_providers:form:type'] = 'Type';
$string['manage_providers:add_provider'] = 'Tilføj AI Provider-instans';
$string['manage_providers:edit_provider'] = 'Rediger AI Provider-instans';
$string['manage_providers:delete_provider'] = 'Slet AI Provider-instans: "{$a}"';
$string['manage_providers:delete_confirm'] = 'Er du sikker på, at du vil slette denne provider-instans? Denne handling kan ikke fortrydes.';
$string['here_you_define_providers'] = 'Her definerer du de AI provider-instanser, der vil være tilgængelige på dit site.';
$string['set_as_default'] = 'Sæt som standard?';
$string['in_use'] = 'Allerede i brug';

// Provider options help texts
$string['openai_chat_model'] = 'OpenAI Chat Model';
$string['openai_chat_model_help'] = 'Her kan du angive den chat-model, der skal bruges. For eksempel: <strong>gpt-4</strong>, <strong>gpt-3.5-turbo</strong>, osv. Se OpenAI\'s dokumentation for tilgængelige modeller.';
$string['mistral_chat_model'] = 'Mistral Chat Model';
$string['mistral_chat_model_help'] = 'Her kan du angive den chat-model, der skal bruges. For eksempel: <strong>mistral-large</strong>, <strong>mistral-small</strong>, osv. Se Mistral\'s dokumentation for tilgængelige modeller.';
$string['ollama_chat_model'] = 'Ollama Chat Model';
$string['ollama_chat_model_help'] = 'Her kan du angive den chat-model, der skal bruges. For eksempel: <strong>llama2</strong>, <strong>vicuna</strong>, osv. Se din Ollama\'s provider for tilgængelige modeller.';
$string['nebius_chat_model'] = 'Nebius Chat Model';
$string['nebius_chat_model_help'] = 'Her kan du angive den chat-model, der skal bruges. For eksempel: <strong>Qwen/Qwen3-32B-fast</strong>, <strong>Qwen/Qwen3-30B-A3B-Instruct-2507</strong>, osv. Se Nebius\'s dokumentation for tilgængelige modeller.';
$string['openai_embedding_model'] = 'OpenAI Embedding Model';
$string['openai_embedding_model_help'] = 'Her kan du angive den embedding-model, der skal bruges. For eksempel: <strong>text-embedding-3-small</strong>, <strong>text-embedding-3-large</strong>, osv. Se OpenAI\'s dokumentation for tilgængelige embedding-modeller.';
$string['mistral_embedding_model'] = 'Mistral Embedding Model';
$string['mistral_embedding_model_help'] = 'Her kan du angive den embedding-model, der skal bruges. For eksempel: <strong>mistral-embed</strong>, osv. Se Mistral\'s dokumentation for tilgængelige embedding-modeller.';
$string['ollama_embedding_model'] = 'Ollama Embedding Model';
$string['ollama_embedding_model_help'] = 'Her kan du angive den embedding-model, der skal bruges. For eksempel: <strong>nomic-embed-text</strong>, osv. Se din Ollama\'s provider for tilgængelige embedding-modeller.';
$string['nebius_embedding_model'] = 'Nebius Embedding Model';
$string['nebius_embedding_model_help'] = 'Her kan du angive den embedding-model, der skal bruges. For eksempel: <strong>Qwen/Qwen3-Embedding-8B</strong>, osv. Se Nebius\'s dokumentation for tilgængelige embedding-modeller.';
$string['openai_image_model'] = 'OpenAI Image Model';
$string['openai_image_model_help'] = 'Her kan du angive den billedgenereringsmodel, der skal bruges. For eksempel: <strong>dall-e-3</strong>, <strong>dall-e-2</strong>, osv. Se OpenAI\'s dokumentation for tilgængelige billedgenereringsmodeller.';
$string['nebius_image_model'] = 'Nebius Image Model';
$string['nebius_image_model_help'] = 'Her kan du angive den billedgenereringsmodel, der skal bruges. For eksempel: <strong>black-forest-labs/flux-dev</strong>. Se Nebius\'s dokumentation for tilgængelige billedgenereringsmodeller.';
$string['openai_transcription_model'] = 'OpenAI Transcription Model';
$string['openai_transcription_model_help'] = 'Here you can specify the transcription model that should be used. For example: <strong>whisper-1</strong>. Refer to OpenAI\'s documentation for available transcription models.';
$string['mistral_transcription_model'] = 'Mistral Transcription Model';
$string['mistral_transcription_model_help'] = 'Here you can specify the transcription model that should be used. For example: <strong>mistral-whisper</strong>. Refer to Mistral\'s documentation for available transcription models.';
$string['openai_tts_model'] = 'OpenAI Text-to-Speech Model';
$string['openai_tts_model_help'] = 'Her kan du angive den TTS-model (text-to-speech), der skal bruges. For eksempel: <strong>tts-1</strong>, <strong>tts-1-hd</strong>. Se OpenAI\'s dokumentation for tilgængelige TTS-modeller.';
$string['openai_tts_voice'] = 'OpenAI Text-to-Speech Stemme';
$string['openai_tts_voice_help'] = 'Stemme der bruges til at syntetisere lyden. OpenAI understøtter aktuelt: <strong>alloy</strong>, <strong>echo</strong>, <strong>fable</strong>, <strong>onyx</strong>, <strong>nova</strong>, <strong>shimmer</strong>.';
$string['openai_tts_format'] = 'OpenAI Text-to-Speech Format';
$string['openai_tts_format_help'] = 'Lydcontainerformat returneret af OpenAI. <strong>mp3</strong> er det sikreste valg for HTML5 audio-tagget; <strong>opus</strong>, <strong>aac</strong>, <strong>flac</strong>, <strong>wav</strong> og <strong>pcm</strong> understøttes også.';

// Manage Features
$string['manage_features:title'] = 'AI Funktioner';
$string['manage_features:table:component'] = 'Komponent';
$string['manage_features:table:name'] = 'Navn';
$string['manage_features:table:description'] = 'Beskrivelse';
$string['manage_features:table:ai_actions'] = 'Krævede AI Handling';
$string['manage_features:table:actions'] = 'Handlinger';
$string['manage_features:edit_feature_settings'] = 'Rediger AI Funktion indstillinger: "{$a}"';
$string['manage_features:form:provider_id'] = 'Provider Instans';
$string['here_you_can_see_all_components_ai_features'] = 'Her kan du se alle komponenters AI-funktioner, der er tilgængelige på dit site. Du kan overskrive standard provider-instansen og/eller indstillinger for hver funktion.';
$string['uses_chat'] = 'Chat';
$string['uses_embedding'] = 'Embeddings';
$string['uses_image'] = 'Image';
$string['uses_audio_transcriptions'] = 'Audio Transcriptions';
$string['uses_tts'] = 'Text-to-Speech';

$string['base_url'] = 'Base URL';
$string['api_key'] = 'API Nøgle';
$string['default_chat_model'] = 'Standard Chat Model';
$string['default_embedding_model'] = 'Standard Embedding Model';
$string['default_image_model'] = 'Standard Billedmodel';
$string['default_transcription_model'] = 'Standard Transkriptionsmodel';
$string['default_tts_model'] = 'Standard Text-to-Speech Model';
$string['default_tts_voice'] = 'Standard Text-to-Speech Stemme';
$string['default_tts_format'] = 'Standard Text-to-Speech Format';
$string['provider_settings'] = 'Provider Indstillinger';
$string['supports_chat'] = 'Understøtter Chat';
$string['supports_embedding'] = 'Understøtter Embedding';
$string['supports_image'] = 'Understøtter Billede';
$string['supports_audio_transcriptions'] = 'Understøtter Audio Transkriptioner';
$string['supports_tts'] = 'Understøtter Text-to-Speech';
$string['provider_supports'] = 'Provider Kapaciteter';
$string['default_action_providers'] = 'Standard Handling Provider-instanser';
$string['here_you_define_default_action_providers'] = 'Her definerer du, hvilke provider-instanser der skal bruges som standard for hver handling.';
$string['you_have_configured_a_provider_and_set_the_default'] = 'Du har konfigureret en provider-instans og sat standard provider-instansen for alle handlinger. Du er nu klar til at bruge AI-funktioner i dine AI-plugins! :)';
$string['you_have_not_yet_configured_any_providers'] = 'Du har endnu ikke konfigureret nogen AI provider-instanser. Tilføj venligst mindst én provider <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">her</a>.';
$string['you_have_not_yet_configured_default_providers'] = 'Du har endnu ikke konfigureret standard provider-instanser for alle handlinger. Konfigurer venligst standard provider-instanser <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">her</a>.';
$string['no_available_providers'] = 'Ingen tilgængelige provider-instanser';
$string['this_provider_is_preconfigured_no_modify'] = 'Denne provider instans er forudkonfigureret og kan ikke ændres.';

// Settings
$string['settings:manage_page'] = 'Administrer AI Indstillinger';

// Capabilities
$string['mxaimanager:manage_configuration'] = 'Manage Moxis AI Manager configuration';

// Privacy
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs'] = 'Denne tabel gemmer logfiler over brugen af funktionshandlinger for Moxis AI Manager-pluginet.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:id'] = 'ID';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:feature_id'] = 'AI Funktions ID';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:request_json'] = 'Forespørgsels JSON sendt til AI provideren.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:response_json'] = 'Respons JSON modtaget fra AI provideren.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:input_tokens'] = 'Antallet af input tokens brugt i forespørgslen.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:output_tokens'] = 'Antallet af output tokens modtaget i responsen.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:session_id'] = 'Brugersessions ID forbundet med forespørgslen.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:user_id'] = 'Brugers ID, der foretog forespørgslen.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:timecreated'] = 'Tidsstempel for, hvornår logposten blev oprettet.';
