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
$string['scaleway_chat_model'] = 'Scaleway Chat Model';
$string['scaleway_chat_model_help'] = 'Her kan du angive den chat-model, der skal bruges. For eksempel: <strong>llama-3.1-8b-instruct</strong>. Se Scaleway\'s dokumentation for tilgængelige modeller.';
$string['scaleway_embedding_model'] = 'Scaleway Embedding Model';
$string['scaleway_embedding_model_help'] = 'Her kan du angive den embedding-model, der skal bruges. Se Scaleway\'s dokumentation for tilgængelige embedding-modeller.';
$string['scaleway_image_model'] = 'Scaleway Image Model';
$string['scaleway_image_model_help'] = 'Her kan du angive den billedgenereringsmodel, der skal bruges. Se Scaleway\'s dokumentation for tilgængelige billedgenereringsmodeller.';
$string['openai_transcription_model'] = 'OpenAI Transcription Model';
$string['openai_transcription_model_help'] = 'Her kan du angive den transskriptionsmodel, der skal bruges. For eksempel: <strong>whisper-1</strong>. Se OpenAI\'s dokumentation for tilgængelige transskriptionsmodeller.';
$string['mistral_transcription_model'] = 'Mistral Transcription Model';
$string['mistral_transcription_model_help'] = 'Her kan du angive den transskriptionsmodel, der skal bruges. For eksempel: <strong>mistral-whisper</strong>. Se Mistral\'s dokumentation for tilgængelige transskriptionsmodeller.';
$string['openai_tts_model'] = 'OpenAI Text-to-Speech Model';
$string['openai_tts_model_help'] = 'Her kan du angive den text-to-speech-model, der skal bruges. For eksempel: <strong>tts-1</strong>, <strong>tts-1-hd</strong>. Se OpenAI\'s dokumentation for tilgængelige TTS-modeller.';

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
$string['uses_speech_synthesis'] = 'Talesyntese';
$string['base_url'] = 'Base URL';
$string['api_key'] = 'API Nøgle';
$string['model_type_or_select'] = 'Vælg en model eller skriv din egen...';
$string['default_chat_model'] = 'Standard Chat Model';
$string['default_embedding_model'] = 'Standard Embedding Model';
$string['default_image_model'] = 'Standard Billedmodel';
$string['default_transcription_model'] = 'Standard Transkriptionsmodel';
$string['default_tts_model'] = 'Standard Text-to-Speech Model';
$string['provider_settings'] = 'Provider Indstillinger';
$string['supports_chat'] = 'Understøtter Chat';
$string['supports_embedding'] = 'Understøtter Embedding';
$string['supports_image'] = 'Understøtter Billede';
$string['supports_audio_transcriptions'] = 'Understøtter Audio Transkriptioner';
$string['supports_speech_synthesis'] = 'Understøtter Talesyntese';
$string['provider_supports'] = 'Provider Kapaciteter';
$string['default_action_providers'] = 'Standard Handling Provider-instanser';
$string['here_you_define_default_action_providers'] = 'Her definerer du, hvilke provider-instanser der skal bruges som standard for hver handling.';
$string['you_have_configured_a_provider_and_set_the_default'] = 'Du har konfigureret en provider-instans og sat standard provider-instansen for alle handlinger. Du er nu klar til at bruge AI-funktioner i dine AI-plugins! :)';
$string['you_have_not_yet_configured_any_providers'] = 'Du har endnu ikke konfigureret nogen AI provider-instanser. Tilføj venligst mindst én provider <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">her</a>.';
$string['you_have_not_yet_configured_default_providers'] = 'Du har endnu ikke konfigureret standard provider-instanser for alle handlinger. Konfigurer venligst standard provider-instanser <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">her</a>.';
$string['no_available_providers'] = 'Ingen tilgængelige provider-instanser';
$string['this_provider_is_managed_no_modify'] = 'Denne provider instans er administreret og kan ikke ændres.';
$string['this_provider_is_preconfigured_no_modify'] = 'Denne provider instans er forudkonfigureret og kan ikke ændres.';

// Settings
$string['settings:manage_page'] = 'Administrer AI Indstillinger';
$string['settings:quota_page'] = 'Token Kvote';
$string['settings:billing_page'] = 'Fakturering & Kvoter';

// Global Token Quota
$string['quota_heading'] = 'Token Kvoter';
$string['quota_heading_desc'] = 'Disse kvoter gælder for alle administrerede providere. Sæt til 0 for ubegrænset.';

$string['daily_input_quota'] = 'Daglig input token-kvote';
$string['daily_input_quota_desc'] = 'Maksimalt antal input tokens tilladt pr. dag. Sæt til 0 for ubegrænset.';
$string['daily_output_quota'] = 'Daglig output token-kvote';
$string['daily_output_quota_desc'] = 'Maksimalt antal output tokens tilladt pr. dag. Sæt til 0 for ubegrænset.';

$string['weekly_input_quota'] = 'Ugentlig input token-kvote';
$string['weekly_input_quota_desc'] = 'Maksimalt antal input tokens tilladt pr. uge (nulstilles mandag). Sæt til 0 for ubegrænset.';
$string['weekly_output_quota'] = 'Ugentlig output token-kvote';
$string['weekly_output_quota_desc'] = 'Maksimalt antal output tokens tilladt pr. uge (nulstilles mandag). Sæt til 0 for ubegrænset.';

$string['monthly_input_quota'] = 'Månedlig input token-kvote';
$string['monthly_input_quota_desc'] = 'Maksimalt antal input tokens tilladt pr. kalendermåned. Sæt til 0 for ubegrænset.';
$string['monthly_output_quota'] = 'Månedlig output token-kvote';
$string['monthly_output_quota_desc'] = 'Maksimalt antal output tokens tilladt pr. kalendermåned. Sæt til 0 for ubegrænset.';

$string['quota_exceeded_daily_input'] = 'Daglig input token-kvote overskredet. Prøv igen i morgen.';
$string['quota_exceeded_daily_output'] = 'Daglig output token-kvote overskredet. Prøv igen i morgen.';
$string['quota_exceeded_weekly_input'] = 'Ugentlig input token-kvote overskredet. Prøv igen næste uge.';
$string['quota_exceeded_weekly_output'] = 'Ugentlig output token-kvote overskredet. Prøv igen næste uge.';
$string['quota_exceeded_monthly_input'] = 'Månedlig input token-kvote overskredet. Prøv igen næste måned.';
$string['quota_exceeded_monthly_output'] = 'Månedlig output token-kvote overskredet. Prøv igen næste måned.';
$string['quota_usage_title'] = 'Token Kvote Forbrug';
$string['quota_unlimited'] = 'Ubegrænset (baseret på din provider-konto)';


// Credit System
$string['credit_heading'] = 'AI Kredit Indstillinger';
$string['credit_heading_desc'] = 'Kreditter giver en enkel måde at spore AI-forbrug. Hver AI-handling forbruger kreditter baseret på tokens brugt og providerens omkostningsmultiplikator. Kreditter købes som forudbetalt saldo.';
$string['quota_display_mode'] = 'Kvotevisningstilstand';
$string['quota_display_mode_desc'] = 'Vælg hvordan forbrugsgrænser vises: som brugervenlige kreditter eller som rå token-antal (avanceret).';
$string['mode_none'] = 'Ingen (fakturering deaktiveret)';
$string['mode_credits'] = 'Kreditter';
$string['mode_tokens'] = 'Tokens (avanceret)';
$string['tokens_per_credit'] = 'Tokens pr. kredit';
$string['tokens_per_credit_desc'] = 'Antal tokens (input + output kombineret) der svarer til 1 kredit ved basistakst (multiplikator 1.0). Lavere værdier gør kreditter mere granulære.';
$string['managed_provider_ids'] = 'Administrerede provider-ID\'er';
$string['managed_provider_ids_desc'] = 'Kommasepareret liste over provider-ID\'er administreret af dig (f.eks. "1,3,5"). Disse providere vil være underlagt kredit-/tokengrænser og kan ikke redigeres eller slettes af klienten.';
$string['credit_multiplier'] = 'Kredit omkostningsmultiplikator';
$string['credit_multiplier_help'] = 'Multiplikator anvendt på tokenforbrug til kreditberegning. Dyrere providere bør have en højere multiplikator. F.eks.: 1.0 for Scaleway (basistakst), 3.0 for GPT-4 (3x dyrere), 0.0 for selvhostet Ollama (gratis).';
$string['credit_usage_title'] = 'AI Kreditter';
$string['credits_remaining'] = 'kreditter tilbage';
$string['credit_expired'] = 'Kreditterne er udløbet. Kontakt venligst din administrator for fornyelse.';
$string['credit_expires_on'] = 'Udløber den:';
$string['credit_recharge'] = 'Genoplad';
$string['credit_recharge_amount'] = 'Kreditter';
$string['credit_recharge_note'] = 'Note (f.eks. Faktura #123)';
$string['credit_recharged'] = 'Kreditter genopladt med succes.';
$string['credit_recharge_heading'] = 'Kreditsaldo & Genopladning';
$string['credit_recharge_expiry'] = 'Udløbsdato (valgfri)';
$string['credit_history_expiry'] = 'Udløber';
$string['credit_history_date'] = 'Dato';
$string['credit_history_amount'] = 'Kreditter';
$string['credit_history_type'] = 'Type';
$string['credit_history_note'] = 'Note';
$string['credit_type_initial'] = 'Indledende tildeling';
$string['credit_type_recharge'] = 'Genopladning';
$string['credit_type_adjustment'] = 'Justering';
$string['quota_exceeded_title'] = '{$a}';
$string['quota_exceeded_depleted_credits'] = 'Din AI-kreditsaldo er opbrugt. Kontakt venligst din administrator for at genoplade kreditter.';
$string['quota_exceeded_expired_credits'] = 'Dine AI-kreditter er udløbet. Kontakt venligst din administrator for at forny din kredittildeling.';
$string['credit_empty_info'] = 'Der er endnu ikke tildelt nogen kreditter. Brug formularen nedenfor til at tilføje din indledende kreditsaldo.';
$string['credit_disable'] = 'Deaktiver denne kredittildeling';
$string['credit_enable'] = 'Genaktiver denne kredittildeling';
$string['credit_recharge_amount_label'] = 'Antal kreditter der skal tilføjes til saldoen.';
$string['credit_recharge_expiry_label'] = 'Valgfri udløbsdato for disse kreditter. Lad stå tom for ingen udløbsdato.';
$string['credit_recharge_note_label'] = 'Valgfri referencenote (f.eks. fakturanummer) til sporingsformål.';

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
