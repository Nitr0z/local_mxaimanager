<?php

$string['pluginname'] = 'Moxis AI Manager';

// Manage Providers
$string['manage:info'] = "<p>Ces paramètres vous permettent de définir quels fournisseurs d'IA (OpenAI, Mistral, etc.) sont disponibles sur votre site.</p><p>Vous pourrez également configurer :</p><ul><li>Quelle instance de fournisseur doit être utilisée par défaut.</li><li>Quel modèle une instance de fournisseur doit utiliser par défaut.</li><li>Quel modèle et/ou quelle instance de fournisseur doit être utilisé pour une fonctionnalité IA spécifique.</li></ul>";
$string['manage_providers:title'] = 'Instances de fournisseurs IA';
$string['manage_providers:table:name'] = 'Nom de l\'instance';
$string['manage_providers:table:classname'] = 'Type de fournisseur';
$string['manage_providers:table:supported_actions'] = 'Actions supportées';
$string['manage_providers:table:actions'] = 'Actions';
$string['manage_providers:form:name'] = 'Nom';
$string['manage_providers:form:type'] = 'Type';
$string['manage_providers:add_provider'] = 'Ajouter une instance de fournisseur IA';
$string['manage_providers:edit_provider'] = 'Modifier une instance de fournisseur IA';
$string['manage_providers:delete_provider'] = 'Supprimer l\'instance de fournisseur IA : "{$a}"';
$string['manage_providers:delete_confirm'] = 'Êtes-vous sûr de vouloir supprimer cette instance de fournisseur ? Cette action est irréversible.';
$string['here_you_define_providers'] = 'Définissez ici les instances de fournisseurs IA disponibles sur votre site.';
$string['set_as_default'] = 'Définir par défaut ?';
$string['in_use'] = 'Déjà utilisé';

// Provider options help texts
$string['openai_chat_model'] = 'Modèle Chat OpenAI';
$string['openai_chat_model_help'] = 'Spécifiez le modèle de chat à utiliser. Par exemple : <strong>gpt-5</strong>, <strong>gpt-4.1</strong>, etc. Consultez la documentation OpenAI pour les modèles disponibles.';
$string['mistral_chat_model'] = 'Modèle Chat Mistral';
$string['mistral_chat_model_help'] = 'Spécifiez le modèle de chat à utiliser. Par exemple : <strong>mistral-large-3-25-12</strong>, <strong>mistral-small-3-2-25-06</strong>, etc. Consultez la documentation Mistral pour les modèles disponibles.';
$string['ollama_chat_model'] = 'Modèle Chat Ollama';
$string['ollama_chat_model_help'] = 'Spécifiez le modèle de chat à utiliser. Par exemple : <strong>llama3.1</strong>, <strong>mistral</strong>, etc. Consultez votre fournisseur Ollama pour les modèles disponibles.';
$string['nebius_chat_model'] = 'Modèle Chat Nebius';
$string['nebius_chat_model_help'] = 'Spécifiez le modèle de chat à utiliser. Par exemple : <strong>Qwen/Qwen3-32B-fast</strong>, etc. Consultez la documentation Nebius pour les modèles disponibles.';
$string['scaleway_chat_model'] = 'Modèle Chat Scaleway';
$string['scaleway_chat_model_help'] = 'Spécifiez le modèle de chat à utiliser. Par exemple : <strong>llama-3.3-70b-instruct</strong>, <strong>qwen2.5-72b-instruct</strong>, <strong>deepseek-r1</strong>. Consultez la documentation Scaleway pour les modèles disponibles.';
$string['openai_embedding_model'] = 'Modèle Embedding OpenAI';
$string['openai_embedding_model_help'] = 'Spécifiez le modèle d\'embedding à utiliser. Par exemple : <strong>text-embedding-3-small</strong>, <strong>text-embedding-3-large</strong>, etc.';
$string['mistral_embedding_model'] = 'Modèle Embedding Mistral';
$string['mistral_embedding_model_help'] = 'Spécifiez le modèle d\'embedding à utiliser. Par exemple : <strong>mistral-embed-23-12</strong>, etc.';
$string['ollama_embedding_model'] = 'Modèle Embedding Ollama';
$string['ollama_embedding_model_help'] = 'Spécifiez le modèle d\'embedding à utiliser. Par exemple : <strong>nomic-embed-text</strong>, etc.';
$string['nebius_embedding_model'] = 'Modèle Embedding Nebius';
$string['nebius_embedding_model_help'] = 'Spécifiez le modèle d\'embedding à utiliser. Par exemple : <strong>BAAI/bge-en-icl</strong>, etc.';
$string['scaleway_embedding_model'] = 'Modèle Embedding Scaleway';
$string['scaleway_embedding_model_help'] = 'Spécifiez le modèle d\'embedding à utiliser. Par exemple : <strong>sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2</strong>, <strong>baai/bge-multilingual-gemma2</strong>. Consultez la documentation Scaleway pour les modèles disponibles.';
$string['openai_image_model'] = 'Modèle Image OpenAI';
$string['openai_image_model_help'] = 'Spécifiez le modèle de génération d\'images à utiliser. Par exemple : <strong>gpt-image-1.5</strong>, <strong>dall-e-3</strong>, etc.';
$string['scaleway_image_model'] = 'Modèle Image Scaleway';
$string['scaleway_image_model_help'] = 'Spécifiez le modèle de génération d\'images à utiliser. Par exemple : <strong>black-forest-labs/flux-schnell</strong>, <strong>black-forest-labs/flux-dev</strong>. Consultez la documentation Scaleway pour les modèles disponibles.';
$string['openai_transcription_model'] = 'Modèle Transcription OpenAI';
$string['openai_transcription_model_help'] = 'Spécifiez le modèle de transcription à utiliser. Par exemple : <strong>gpt-4o-transcribe</strong>, <strong>whisper-1</strong>.';
$string['mistral_transcription_model'] = 'Modèle Transcription Mistral';
$string['mistral_transcription_model_help'] = 'Spécifiez le modèle de transcription à utiliser. Par exemple : <strong>voxtral-mini-transcribe-26-02</strong>.';
$string['openai_tts_model'] = 'Modèle TTS OpenAI';
$string['openai_tts_model_help'] = 'Spécifiez le modèle de synthèse vocale à utiliser. Par exemple : <strong>tts-1</strong>, <strong>tts-1-hd</strong>, etc.';
$string['default_tts_model'] = 'Modèle TTS par défaut';
$string['uses_speech_synthesis'] = 'Synthèse vocale';
$string['supports_speech_synthesis'] = 'Supporte la synthèse vocale';

// Manage Features
$string['manage_features:title'] = 'Fonctionnalités IA';
$string['manage_features:table:component'] = 'Composant';
$string['manage_features:table:name'] = 'Nom';
$string['manage_features:table:description'] = 'Description';
$string['manage_features:table:ai_actions'] = 'Actions IA requises';
$string['manage_features:table:actions'] = 'Actions';
$string['manage_features:edit_feature_settings'] = 'Modifier les paramètres de la fonctionnalité IA : "{$a}"';
$string['manage_features:form:provider_id'] = 'Instance de fournisseur';
$string['here_you_can_see_all_components_ai_features'] = 'Voici toutes les fonctionnalités IA des composants disponibles sur votre site. Vous pouvez remplacer l\'instance de fournisseur par défaut et/ou les paramètres pour chaque fonctionnalité.';
$string['uses_chat'] = 'Chat';
$string['uses_embedding'] = 'Embeddings';
$string['uses_image'] = 'Image';
$string['uses_audio_transcriptions'] = 'Transcription audio';

$string['base_url'] = 'URL de base';
$string['api_key'] = 'Clé API';
$string['model_type_or_select'] = 'Sélectionnez un modèle ou saisissez-en un...';
$string['default_chat_model'] = 'Modèle Chat par défaut';
$string['default_embedding_model'] = 'Modèle Embedding par défaut';
$string['default_image_model'] = 'Modèle Image par défaut';
$string['default_transcription_model'] = 'Modèle Transcription par défaut';
$string['provider_settings'] = 'Paramètres du fournisseur';
$string['supports_chat'] = 'Supporte le Chat';
$string['supports_embedding'] = 'Supporte l\'Embedding';
$string['supports_image'] = 'Supporte l\'Image';
$string['supports_audio_transcriptions'] = 'Supporte la Transcription audio';
$string['provider_supports'] = 'Capacités du fournisseur';
$string['default_action_providers'] = 'Instances de fournisseurs par défaut';
$string['here_you_define_default_action_providers'] = 'Définissez ici quelles instances de fournisseurs doivent être utilisées par défaut pour chaque action.';
$string['you_have_configured_a_provider_and_set_the_default'] = 'Vous avez configuré une instance de fournisseur et défini le fournisseur par défaut pour toutes les actions. Vous êtes prêt à utiliser les fonctionnalités IA ! :)';
$string['you_have_not_yet_configured_any_providers'] = 'Vous n\'avez pas encore configuré d\'instance de fournisseur IA. Veuillez en ajouter au moins une <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">ici</a>.';
$string['you_have_not_yet_configured_default_providers'] = 'Vous n\'avez pas encore configuré les fournisseurs par défaut pour toutes les actions. Veuillez les configurer <a href="/local/mxaimanager/view.php?view=manage_providers&action=browse">ici</a>.';
$string['no_available_providers'] = 'Aucune instance de fournisseur disponible';
$string['this_provider_is_managed_no_modify'] = 'Cette instance de fournisseur est gérée et ne peut pas être modifiée.';
$string['this_provider_is_preconfigured_no_modify'] = 'Cette instance de fournisseur est préconfigurée et ne peut pas être modifiée.';

// Settings
$string['settings:manage_page'] = 'Gérer les paramètres IA';
$string['settings:quota_page'] = 'Quota de tokens';
$string['settings:billing_page'] = 'Facturation & Quotas';

$string['quota_heading'] = 'Quotas de tokens';
$string['quota_heading_desc'] = 'Les quotas de tokens limitent la consommation sur tous les fournisseurs gérés. Les quotas se cumulent : vous pouvez définir des limites journalières pour éviter les pics ET des limites mensuelles pour le contrôle budgétaire. Mettez 0 pour désactiver un quota spécifique.';

$string['daily_input_quota'] = 'Quota journalier — tokens d\'entrée';
$string['daily_input_quota_desc'] = 'Max de tokens d\'entrée par jour (réinitialisation à minuit). Mettre 0 pour désactiver.';
$string['daily_output_quota'] = 'Quota journalier — tokens de sortie';
$string['daily_output_quota_desc'] = 'Max de tokens de sortie par jour (réinitialisation à minuit). Mettre 0 pour désactiver.';

$string['weekly_input_quota'] = 'Quota hebdomadaire — tokens d\'entrée';
$string['weekly_input_quota_desc'] = 'Max de tokens d\'entrée par semaine (réinitialisation le lundi). Mettre 0 pour désactiver.';
$string['weekly_output_quota'] = 'Quota hebdomadaire — tokens de sortie';
$string['weekly_output_quota_desc'] = 'Max de tokens de sortie par semaine (réinitialisation le lundi). Mettre 0 pour désactiver.';

$string['monthly_input_quota'] = 'Quota mensuel — tokens d\'entrée';
$string['monthly_input_quota_desc'] = 'Max de tokens d\'entrée par mois calendaire. Mettre 0 pour désactiver.';
$string['monthly_output_quota'] = 'Quota mensuel — tokens de sortie';
$string['monthly_output_quota_desc'] = 'Max de tokens de sortie par mois calendaire. Mettre 0 pour désactiver.';

$string['quota_exceeded_daily_input'] = 'Le quota journalier de tokens d\'entrée a été atteint. Veuillez réessayer demain.';
$string['quota_exceeded_daily_output'] = 'Le quota journalier de tokens de sortie a été atteint. Veuillez réessayer demain.';
$string['quota_exceeded_weekly_input'] = 'Le quota hebdomadaire de tokens d\'entrée a été atteint. Veuillez réessayer la semaine prochaine.';
$string['quota_exceeded_weekly_output'] = 'Le quota hebdomadaire de tokens de sortie a été atteint. Veuillez réessayer la semaine prochaine.';
$string['quota_exceeded_monthly_input'] = 'Le quota mensuel de tokens d\'entrée a été atteint. Veuillez réessayer le mois prochain.';
$string['quota_exceeded_monthly_output'] = 'Le quota mensuel de tokens de sortie a été atteint. Veuillez réessayer le mois prochain.';
$string['quota_usage_title'] = 'Utilisation des quotas de tokens';
$string['quota_unlimited'] = 'Illimité (en fonction de votre compte fournisseur)';


// Système de crédits
$string['credit_heading'] = 'Paramètres des crédits IA';
$string['credit_heading_desc'] = 'Les crédits offrent un moyen simple de suivre l\'utilisation de l\'IA. Chaque action IA consomme des crédits en fonction des tokens utilisés et du multiplicateur de coût du fournisseur. Les crédits sont achetés en tant que solde prépayé.';
$string['quota_display_mode'] = 'Mode d\'affichage des quotas';
$string['quota_display_mode_desc'] = 'Choisissez l\'affichage des limites d\'utilisation : crédits (simplifié) ou tokens (avancé).';
$string['mode_none'] = 'Aucun (facturation désactivée)';
$string['mode_credits'] = 'Crédits';
$string['mode_tokens'] = 'Tokens (avancé)';
$string['tokens_per_credit'] = 'Tokens par crédit';
$string['tokens_per_credit_desc'] = 'Nombre de tokens (entrée + sortie combinés) pour 1 crédit au taux de base (multiplicateur 1.0). Des valeurs plus basses rendent les crédits plus granulaires.';
$string['managed_provider_ids'] = 'IDs des providers gérés';
$string['managed_provider_ids_desc'] = 'Liste d\'IDs de providers séparés par des virgules (ex : "1,3,5"). Ces providers seront soumis aux limites de crédits/tokens et ne pourront pas être modifiés ou supprimés par le client.';
$string['credit_multiplier'] = 'Multiplicateur de coût crédit';
$string['credit_multiplier_help'] = 'Multiplicateur appliqué à la consommation de tokens pour le calcul des crédits. Les fournisseurs plus chers doivent avoir un multiplicateur plus élevé. Par exemple : 1.0 pour Scaleway (taux de base), 3.0 pour GPT-4 (3x plus cher), 0.0 pour Ollama auto-hébergé (gratuit).';
$string['credit_usage_title'] = 'Crédits IA';
$string['credits_remaining'] = 'crédits restants';
$string['credit_expired'] = 'Les crédits ont expiré. Veuillez contacter votre administrateur pour renouveler.';
$string['credit_expires_on'] = 'Expire le :';
$string['credit_recharge'] = 'Recharger';
$string['credit_recharge_amount'] = 'Crédits';
$string['credit_recharge_note'] = 'Note (ex : Facture #123)';
$string['credit_recharged'] = 'Crédits rechargés avec succès.';
$string['credit_recharge_heading'] = 'Solde de crédits & Recharge';
$string['credit_recharge_expiry'] = 'Date d\'expiration (optionnel)';
$string['credit_history_expiry'] = 'Expire le';
$string['credit_history_date'] = 'Date';
$string['credit_history_amount'] = 'Crédits';
$string['credit_history_type'] = 'Type';
$string['credit_history_note'] = 'Note';
$string['credit_type_initial'] = 'Allocation initiale';
$string['credit_type_recharge'] = 'Recharge';
$string['credit_type_adjustment'] = 'Ajustement';
$string['quota_exceeded_title'] = '{$a}';
$string['quota_exceeded_depleted_credits'] = 'Votre solde de crédits IA est épuisé. Veuillez contacter votre administrateur pour recharger vos crédits.';
$string['quota_exceeded_expired_credits'] = 'Vos crédits IA ont expiré. Veuillez contacter votre administrateur pour renouveler votre allocation de crédits.';
$string['credit_empty_info'] = 'Aucun crédit n\'a encore été alloué. Utilisez le formulaire ci-dessous pour ajouter votre solde initial de crédits.';
$string['credit_disable'] = 'Désactiver cette allocation de crédits';
$string['credit_enable'] = 'Réactiver cette allocation de crédits';
$string['credit_recharge_amount_label'] = 'Nombre de crédits à ajouter au solde.';
$string['credit_recharge_expiry_label'] = 'Date d\'expiration optionnelle pour ces crédits. Laisser vide pour aucune expiration.';
$string['credit_recharge_note_label'] = 'Note de référence optionnelle (ex : numéro de facture) pour le suivi.';

// Capabilities
$string['mxaimanager:manage_configuration'] = 'Gérer la configuration de Moxis AI Manager';

// Privacy
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs'] = 'Cette table stocke les logs d\'utilisation des actions IA du plugin Moxis AI Manager.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:id'] = 'ID';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:feature_id'] = 'L\'ID de la fonctionnalité IA utilisée.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:request_json'] = 'La requête JSON envoyée au fournisseur IA.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:response_json'] = 'La réponse JSON reçue du fournisseur IA.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:input_tokens'] = 'Le nombre de tokens d\'entrée utilisés dans la requête.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:output_tokens'] = 'Le nombre de tokens de sortie reçus dans la réponse.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:session_id'] = 'L\'ID de session associé à la requête.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:user_id'] = 'L\'ID de l\'utilisateur ayant effectué la requête.';
$string['privacy:metadata:local_mxaimanager_feature_action_usage_logs:timecreated'] = 'L\'horodatage de création de l\'entrée de log.';

