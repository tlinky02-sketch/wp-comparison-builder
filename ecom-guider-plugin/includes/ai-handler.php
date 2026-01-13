<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPC_AI_Handler {

    /**
     * Get all AI profiles
     */
    public static function get_profiles() {
        $profiles = get_option( 'wpc_ai_profiles', [] );
        
        // MIGRATION: If no profiles exist but legacy keys do, create default profiles
        // Only run migration ONCE - check for migration flag
        $migrated = get_option( 'wpc_ai_profiles_migrated', false );
        
        if ( empty( $profiles ) && ! $migrated ) {
            $legacy_openai = get_option( 'wpc_ai_openai_key' );
            $legacy_gemini = get_option( 'wpc_ai_gemini_key' );
            $legacy_claude = get_option( 'wpc_ai_claude_key' );
            
            if ( $legacy_openai || $legacy_gemini || $legacy_claude ) {
                $profiles = [];
                
                if ( $legacy_openai ) {
                    $profiles[] = [
                        'id' => uniqid( 'prof_' ),
                        'name' => 'Default OpenAI',
                        'provider' => 'openai',
                        'api_key' => $legacy_openai,
                        'model' => get_option( 'wpc_ai_openai_model', 'gpt-3.5-turbo' ),
                        'is_default' => ( get_option( 'wpc_ai_active_provider' ) === 'openai' )
                    ];
                }
                
                if ( $legacy_gemini ) {
                    $profiles[] = [
                        'id' => uniqid( 'prof_' ),
                        'name' => 'Default Gemini',
                        'provider' => 'gemini',
                        'api_key' => $legacy_gemini,
                        'model' => get_option( 'wpc_ai_gemini_model', 'gemini-1.5-flash' ),
                        'is_default' => ( get_option( 'wpc_ai_active_provider' ) === 'gemini' )
                    ];
                }
                
                if ( $legacy_claude ) {
                    $profiles[] = [
                        'id' => uniqid( 'prof_' ),
                        'name' => 'Default Claude',
                        'provider' => 'claude',
                        'api_key' => $legacy_claude,
                        'model' => get_option( 'wpc_ai_claude_model', 'claude-3-sonnet-20240229' ),
                        'is_default' => ( get_option( 'wpc_ai_active_provider' ) === 'claude' )
                    ];
                }
                
                update_option( 'wpc_ai_profiles', $profiles );
            }
            
            // Mark migration as done so it doesn't run again
            update_option( 'wpc_ai_profiles_migrated', true );
        }
        
        return $profiles;
    }

    /**
     * Get a specific profile by ID
     */
    public static function get_profile( $profile_id ) {
        $profiles = self::get_profiles();
        foreach ( $profiles as $profile ) {
            if ( $profile['id'] === $profile_id ) {
                return $profile;
            }
        }
        return null;
    }

    /**
     * Get the default profile
     */
    public static function get_default_profile() {
        $profiles = self::get_profiles();
        foreach ( $profiles as $profile ) {
            if ( ! empty( $profile['is_default'] ) ) {
                return $profile;
            }
        }
        // Fallback to first profile if no default
        return ! empty( $profiles ) ? $profiles[0] : null;
    }

    /**
     * Get Active Provider Config (Backwards Usage)
     * Used by existing legacy calls that expect a single "Active" configuration.
     */
    public static function get_active_provider() {
        $profile = self::get_default_profile();
        
        if ( ! $profile ) {
            return false;
        }

        // Decrypt key if needed (assuming keys stored encrypted in profiles)
        // Note: In migration above, we copied encrypted keys directly.
        // If coming from UI save, we might need to handle encryption there.
        // For now, let's assume get_api_key handles decryption.
        
        return [
            'provider' => $profile['provider'],
            'api_key'  => self::decrypt_key( $profile['api_key'] ),
            'model'    => $profile['model']
        ];
    }

    /**
     * Get API Key (Helper)
     */
    public static function get_api_key( $provider_or_profile_id ) {
        // Try as profile ID first
        $profile = self::get_profile( $provider_or_profile_id );
        if ( $profile ) {
            return self::decrypt_key( $profile['api_key'] );
        }

        // Fallback: Try as provider name (legacy support)
        // Find first profile with this provider
        $profiles = self::get_profiles();
        foreach ( $profiles as $p ) {
            if ( $p['provider'] === $provider_or_profile_id ) {
                return self::decrypt_key( $p['api_key'] );
            }
        }
        
        return '';
    }

    /**
     * Encrypt/Decrypt Helpers
     */
    private static function encrypt_key( $key ) {
        if ( empty( $key ) ) return '';
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
        $encrypted = openssl_encrypt( $key, 'aes-256-cbc', wp_salt(), 0, $iv );
        return base64_encode( $encrypted . '::' . $iv );
    }

    public static function decrypt_key( $key ) {
        if ( empty( $key ) ) return '';
        
        // If it looks like a plain key (common prefixes), just return it
        if ( strpos( $key, 'sk-' ) === 0 || 
             strpos( $key, 'AIza' ) === 0 || 
             strpos( $key, 'sk-ant' ) === 0 ) {
            return $key;
        }
        
        // Try to decrypt (old encrypted format)
        $decoded = base64_decode( $key, true );
        if ( $decoded === false ) {
            return $key; // Not base64, return as-is
        }
        
        $parts = explode( '::', $decoded, 2 );
        if ( count( $parts ) === 2 ) {
            $decrypted = openssl_decrypt( $parts[0], 'aes-256-cbc', wp_salt(), 0, $parts[1] );
            if ( $decrypted !== false ) {
                return $decrypted;
            }
        }
        
        return $key; // Fallback: return original
    }
    
    /**
     * Clean Clean Response (Helper)
     */
    private static function clean_response( $content ) {
        // Remove markdown code blocks if present
        $content = preg_replace( '/^```json\s*/i', '', $content );
        $content = preg_replace( '/^```\s*/', '', $content );
        $content = preg_replace( '/\s*```$/', '', $content );
        
        // If content still has text before or after, try to extract just the JSON object
        if ( strpos( $content, '{' ) !== 0 || substr( trim( $content ), -1 ) !== '}' ) {
            if ( preg_match( '/\{(?:[^{}]|(?R))*\}/s', $content, $matches ) ) {
                return $matches[0];
            }
        }
        
        return trim( $content );
    }

    /**
     * Fetch Models from Provider
     */
    public static function fetch_models( $provider, $api_key ) {
        if ( empty( $api_key ) ) {
            return new WP_Error( 'missing_key', 'API Key is required' );
        }

        // Check cache first (cache key now needs to vary by key hash to allow different keys)
        $cache_key = 'wpc_ai_models_' . $provider . '_' . md5( $api_key );
        $cached = get_transient( $cache_key );
        if ( $cached ) {
            return $cached;
        }

        $models = [];
        
        switch ( $provider ) {
            case 'openai':
                $response = wp_remote_get( 'https://api.openai.com/v1/models', [
                    'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
                    'timeout' => 15
                ] );
                
                if ( is_wp_error( $response ) ) return $response;
                
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $body['data'] ) ) {
                    foreach ( $body['data'] as $model ) {
                        // Filter for GPT models
                        if ( strpos( $model['id'], 'gpt' ) === 0 ) {
                            $models[] = $model['id'];
                        }
                    }
                    sort( $models );
                }
                break;
                
            case 'gemini':
                $url = 'https://generativelanguage.googleapis.com/v1/models?key=' . $api_key;
                $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
                
                if ( is_wp_error( $response ) ) return $response;
                
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( ! empty( $body['models'] ) ) {
                    foreach ( $body['models'] as $model ) {
                        if ( in_array( 'generateContent', $model['supportedGenerationMethods'] ?? [] ) ) {
                            // Name is "models/gemini-pro", strip prefix
                            $models[] = str_replace( 'models/', '', $model['name'] );
                        }
                    }
                }
                break;
                
            case 'claude':
                // Anthropic doesn't have a public models endpoint yet, returns static list
                $models = [
                    'claude-3-5-sonnet-20241022',
                    'claude-3-opus-20240229',
                    'claude-3-sonnet-20240229',
                    'claude-3-haiku-20240307'
                ];
                break;
                
            case 'custom':
                 return []; // Custom models handled by user input
        }
        
        if ( ! empty( $models ) ) {
            set_transient( $cache_key, $models, DAY_IN_SECONDS );
        }
        
        return $models;
    }

    /**
     * Generate Content
     */
    public static function generate( $prompt, $options = [] ) {
        // Options can now contain 'profile_id' OR 'provider' (legacy/direct)
        
        $config = null;
        
        // Case 1: Specific Profile Requested
        if ( ! empty( $options['profile_id'] ) ) {
            $profile = self::get_profile( $options['profile_id'] );
            if ( $profile ) {
                $config = [
                    'provider' => $profile['provider'],
                    'api_key'  => self::decrypt_key( $profile['api_key'] ),
                    'model'    => $profile['model']
                ];
            }
        }
        // Case 2: Specific Provider Requested (Legacy or Quick Switch support)
        elseif ( ! empty( $options['provider'] ) ) {
             // Find default profile for this provider
             $profiles = self::get_profiles();
             foreach($profiles as $p) {
                 if ($p['provider'] === $options['provider']) {
                     $config = [
                        'provider' => $p['provider'],
                        'api_key'  => self::decrypt_key( $p['api_key'] ),
                        'model'    => $p['model']
                    ];
                    break;
                 }
             }
        }
        // Case 3: Default
        else {
            $config = self::get_active_provider();
        }
        
        if ( ! $config || empty( $config['api_key'] ) ) {
            return new WP_Error( 'no_provider', 'No valid AI provider configured.' );
        }
        
        // Call Provider
        $provider_method = 'call_' . $config['provider'];
        if ( method_exists( __CLASS__, $provider_method ) ) {
            return self::$provider_method( $prompt, $config );
        }
        
        return new WP_Error( 'invalid_provider', 'Invalid AI provider selected.' );
    }

    /**
     * Call OpenAI
     */
    private static function call_openai( $prompt, $config ) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = [
            'model' => $config['model'],
            'messages' => [
                [ 'role' => 'system', 'content' => 'You are a helpful assistant that generates JSON data.' ],
                [ 'role' => 'user', 'content' => $prompt ]
            ],
            'temperature' => 0.7
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode( $body ),
            'timeout' => 30
        ] );
        
        if ( is_wp_error( $response ) ) return $response;
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] ?? 'OpenAI API Error' );
        }
        
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        return self::clean_response( $content );
    }

    /**
     * Call Gemini
     */
    private static function call_gemini( $prompt, $config ) {
        $model = $config['model'] ?: 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $config['api_key'];
        
        $body = [
            'contents' => [
                [ 'parts' => [ [ 'text' => $prompt ] ] ]
            ]
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type'  => 'application/json' ],
            'body'    => json_encode( $body ),
            'timeout' => 30
        ] );
        
        if ( is_wp_error( $response ) ) return $response;
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] ?? 'Gemini API Error' );
        }
        
        $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        return self::clean_response( $content );
    }

    /**
     * Call Claude
     */
    private static function call_claude( $prompt, $config ) {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $body = [
            'model' => $config['model'] ?: 'claude-3-sonnet-20240229',
            'max_tokens' => 4000,
            'messages' => [
                [ 'role' => 'user', 'content' => $prompt ]
            ]
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'x-api-key' => $config['api_key'],
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ],
            'body'    => json_encode( $body ),
            'timeout' => 30
        ] );
        
        if ( is_wp_error( $response ) ) return $response;
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] ?? 'Claude API Error' );
        }
        
        $content = $body['content'][0]['text'] ?? '';
        
        return self::clean_response( $content );
    }
    
    /**
     * Call Custom Provider (OpenAI-compatible)
     */
    private static function call_custom( $prompt, $config ) {
        $endpoint = get_option( 'wpc_ai_custom_endpoint', '' );
        if ( empty( $endpoint ) ) {
            return new WP_Error( 'no_endpoint', 'Custom provider endpoint not configured.' );
        }
        
        $body = [
            'model' => $config['model'] ?: 'gpt-3.5-turbo',
            'messages' => [
                [ 'role' => 'system', 'content' => 'You are a helpful assistant that generates JSON data.' ],
                [ 'role' => 'user', 'content' => $prompt ]
            ],
            'temperature' => 0.7
        ];
        
        $response = wp_remote_post( $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type'  => 'application/json'
            ],
            'body'    => json_encode( $body ),
            'timeout' => 30
        ] );
        
        if ( is_wp_error( $response ) ) return $response;
        
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'api_error', $body['error']['message'] ?? 'Custom API Error' );
        }
        
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        return self::clean_response( $content );
    }
    
    /**
     * Get Usage Statistics
     */
    public static function get_usage_stats() {
        $usage = get_option( 'wpc_ai_usage', [] );
        $today = date( 'Y-m-d' );
        $this_month = date( 'Y-m' );
        
        return [
            'today'      => $usage['daily'][ $today ] ?? 0,
            'this_month' => array_sum( array_filter( $usage['daily'] ?? [], function( $v, $k ) use ( $this_month ) {
                return strpos( $k, $this_month ) === 0;
            }, ARRAY_FILTER_USE_BOTH ) ),
            'total'      => $usage['total'] ?? 0,
            'last_used'  => $usage['last_used'] ?? ''
        ];
    }
    
    /**
     * Log AI Usage
     */
    public static function log_usage() {
        $usage = get_option( 'wpc_ai_usage', [] );
        $today = date( 'Y-m-d' );
        
        if ( ! isset( $usage['daily'] ) ) $usage['daily'] = [];
        if ( ! isset( $usage['daily'][ $today ] ) ) $usage['daily'][ $today ] = 0;
        
        $usage['daily'][ $today ]++;
        $usage['total'] = ( $usage['total'] ?? 0 ) + 1;
        $usage['last_used'] = current_time( 'mysql' );
        
        // Prune old daily entries (keep last 90 days)
        $cutoff = date( 'Y-m-d', strtotime( '-90 days' ) );
        $usage['daily'] = array_filter( $usage['daily'], function( $k ) use ( $cutoff ) {
            return $k >= $cutoff;
        }, ARRAY_FILTER_USE_KEY );
        
        update_option( 'wpc_ai_usage', $usage );
    }
    
    /**
     * Save Profile
     */
    public static function save_profile( $profile_data ) {
        $profiles = self::get_profiles();
        $is_new = true;
        
        // Check if updating existing
        foreach ( $profiles as $i => $p ) {
            if ( $p['id'] === $profile_data['id'] ) {
                $profiles[ $i ] = $profile_data;
                $is_new = false;
                break;
            }
        }
        
        if ( $is_new ) {
            $profiles[] = $profile_data;
        }
        
        // If this is set as default, unset others
        if ( ! empty( $profile_data['is_default'] ) ) {
            foreach ( $profiles as $i => $p ) {
                if ( $p['id'] !== $profile_data['id'] ) {
                    $profiles[ $i ]['is_default'] = false;
                }
            }
        }
        
        update_option( 'wpc_ai_profiles', $profiles );
        return $profile_data;
    }
    
    /**
     * Delete Profile
     */
    public static function delete_profile( $profile_id ) {
        $profiles = self::get_profiles();
        
        // Find the profile being deleted to get its provider
        $deleted_profile = null;
        foreach ( $profiles as $p ) {
            if ( $p['id'] === $profile_id ) {
                $deleted_profile = $p;
                break;
            }
        }
        
        // Filter out the deleted profile
        $profiles = array_filter( $profiles, function( $p ) use ( $profile_id ) {
            return $p['id'] !== $profile_id;
        } );
        $profiles = array_values( $profiles ); // Re-index
        update_option( 'wpc_ai_profiles', $profiles );
        
        // Ensure migration flag is set so old profiles don't come back
        update_option( 'wpc_ai_profiles_migrated', true );
        
        // Clean up legacy options for this provider if no more profiles use it
        if ( $deleted_profile ) {
            $provider = $deleted_profile['provider'];
            $has_other = false;
            foreach ( $profiles as $p ) {
                if ( $p['provider'] === $provider ) {
                    $has_other = true;
                    break;
                }
            }
            if ( ! $has_other ) {
                delete_option( 'wpc_ai_' . $provider . '_key' );
                delete_option( 'wpc_ai_' . $provider . '_model' );
            }
        }
    }
    
    /**
     * Set Default Profile
     */
    public static function set_default_profile( $profile_id ) {
        $profiles = self::get_profiles();
        foreach ( $profiles as $i => $p ) {
            $profiles[ $i ]['is_default'] = ( $p['id'] === $profile_id );
        }
        update_option( 'wpc_ai_profiles', $profiles );
    }
    
    /**
     * Model (for backwards compatibility with legacy calls)
     */
    public static function get_model( $provider ) {
        $profiles = self::get_profiles();
        foreach ( $profiles as $p ) {
            if ( $p['provider'] === $provider ) {
                return $p['model'] ?? '';
            }
        }
        return '';
    }
    
    /**
     * Generate Tool Data (for Recommended Tools)
     */
    public function generate_tool_data( $tool_name ) {
        $prompt = "Generate data for a recommended tool/app named '{$tool_name}'. 

Return ONLY valid JSON (no explanations) with this structure:
{
  \"title\": \"Tool Name\",
  \"description\": \"One sentence description (max 120 characters)\",
  \"badge\": \"Category badge (e.g. 'Best Marketing', 'Top Design Tool')\",
  \"link\": \"https://example.com (official website)\"
}

Be concise and accurate.";

        $result = self::generate( $prompt );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        // Parse JSON
        $parsed = json_decode( $result, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'parse_error', 'Failed to parse AI response: ' . json_last_error_msg() );
        }
        
        return $parsed;
    }
}

// AJAX Handlers
add_action( 'wp_ajax_wpc_ai_fetch_models', 'wpc_ajax_ai_fetch_models' );
add_action( 'wp_ajax_wpc_ai_save_settings', 'wpc_ajax_ai_save_settings' );
add_action( 'wp_ajax_wpc_ai_get_profile', 'wpc_ajax_ai_get_profile' );
add_action( 'wp_ajax_wpc_ai_save_profile', 'wpc_ajax_ai_save_profile' );
add_action( 'wp_ajax_wpc_ai_delete_profile', 'wpc_ajax_ai_delete_profile' );
add_action( 'wp_ajax_wpc_ai_set_default_profile', 'wpc_ajax_ai_set_default_profile' );
add_action( 'wp_ajax_wpc_ai_generate', 'wpc_ajax_ai_generate' );
add_action( 'wp_ajax_wpc_ai_create_item', 'wpc_ajax_ai_create_item' );

function wpc_ajax_ai_fetch_models() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    $provider = sanitize_text_field( $_POST['provider'] );
    $api_key = sanitize_text_field( $_POST['api_key'] );
    
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'API Key required' );
    }
    
    $models = WPC_AI_Handler::fetch_models( $provider, $api_key );
    
    if ( is_wp_error( $models ) ) {
        wp_send_json_error( $models->get_error_message() );
    }
    
    wp_send_json_success( $models );
}

function wpc_ajax_ai_save_settings() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $provider = sanitize_text_field( $_POST['provider'] );
    $profile_name = sanitize_text_field( $_POST['profile_name'] ?? '' );
    $api_key = sanitize_text_field( $_POST['api_key'] );
    $model = sanitize_text_field( $_POST['model'] ?? '' );
    $is_default = filter_var( $_POST['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN );
    $profile_id = sanitize_text_field( $_POST['profile_id'] ?? '' ); // For editing
    
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'API key is required' );
    }
    
    // If no model specified, try to fetch models and pick a sensible default
    if ( empty( $model ) ) {
        $fetched_models = WPC_AI_Handler::fetch_models( $provider, $api_key );
        if ( ! is_wp_error( $fetched_models ) && ! empty( $fetched_models ) ) {
            // Pick first model, or try to find a good default
            $default_models = [
                'openai' => ['gpt-4.1-mini', 'gpt-4o-mini', 'gpt-4', 'gpt-3.5-turbo'],
                'gemini' => ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-pro'],
                'claude' => ['claude-3-5-sonnet-20241022', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307']
            ];
            
            $preferred = $default_models[$provider] ?? [];
            $model = '';
            
            // Try to find a preferred model
            foreach ( $preferred as $pref ) {
                foreach ( $fetched_models as $m ) {
                    $model_id = is_array( $m ) ? ( $m['id'] ?? $m['name'] ?? '' ) : $m;
                    if ( stripos( $model_id, $pref ) !== false || $model_id === $pref ) {
                        $model = $model_id;
                        break 2;
                    }
                }
            }
            
            // If no preferred found, use first available
            if ( empty( $model ) ) {
                $first = $fetched_models[0];
                $model = is_array( $first ) ? ( $first['id'] ?? $first['name'] ?? '' ) : $first;
            }
        }
    }
    
    if ( empty( $profile_name ) ) {
        $profile_name = ucfirst( $provider ) . ' Profile';
    }
    
    // Build profile array
    $profile = [
        'id' => ! empty( $profile_id ) ? $profile_id : uniqid( 'prof_' ), // Use existing ID if editing
        'name' => $profile_name,
        'provider' => $provider,
        'api_key' => $api_key,
        'model' => $model,
        'is_default' => $is_default
    ];
    
    WPC_AI_Handler::save_profile( $profile );
    
    // Also save to legacy options for backwards compatibility
    update_option( 'wpc_ai_' . $provider . '_key', $api_key );
    update_option( 'wpc_ai_' . $provider . '_model', $model );
    if ( $is_default ) {
        update_option( 'wpc_ai_active_provider', $provider );
    }
    
    wp_send_json_success( [ 'message' => 'Profile saved!', 'profile' => $profile, 'model_selected' => $model ] );
}

function wpc_ajax_ai_get_profile() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $profile_id = sanitize_text_field( $_POST['profile_id'] ?? '' );
    
    if ( empty( $profile_id ) ) {
        wp_send_json_error( 'Profile ID required' );
    }
    
    $profile = WPC_AI_Handler::get_profile( $profile_id );
    
    if ( ! $profile ) {
        wp_send_json_error( 'Profile not found' );
    }
    
    // Decrypt API key for editing
    $profile['api_key'] = WPC_AI_Handler::decrypt_key( $profile['api_key'] );
    
    wp_send_json_success( $profile );
}

function wpc_ajax_ai_save_profile() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $profile = [
        'id' => ! empty( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : uniqid( 'prof_' ),
        'name' => sanitize_text_field( $_POST['name'] ),
        'provider' => sanitize_text_field( $_POST['provider'] ),
        'api_key' => sanitize_text_field( $_POST['api_key'] ),
        'model' => sanitize_text_field( $_POST['model'] ),
        'is_default' => filter_var( $_POST['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN )
    ];
    
    $saved = WPC_AI_Handler::save_profile( $profile );
    wp_send_json_success( $saved );
}

function wpc_ajax_ai_delete_profile() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $profile_id = sanitize_text_field( $_POST['profile_id'] );
    WPC_AI_Handler::delete_profile( $profile_id );
    wp_send_json_success( [ 'deleted' => $profile_id ] );
}

function wpc_ajax_ai_set_default_profile() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $profile_id = sanitize_text_field( $_POST['profile_id'] );
    WPC_AI_Handler::set_default_profile( $profile_id );
    wp_send_json_success( [ 'default' => $profile_id ] );
}

function wpc_ajax_ai_generate() {
    // Check nonce (allow either global or item nonce)
    $nonce_valid = false;
    if ( isset( $_POST['nonce'] ) ) {
        $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'wpc_ai_nonce' ) || wp_verify_nonce( $_POST['nonce'], 'wpc_ai_item_nonce' );
    }
    if ( ! $nonce_valid ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
    $profile_id = sanitize_text_field( $_POST['profile_id'] ?? '' );
    $provider = sanitize_text_field( $_POST['provider'] ?? '' );
    
    if ( empty( $prompt ) ) {
        wp_send_json_error( 'Prompt is required' );
    }
    
    $options = [];
    if ( ! empty( $profile_id ) ) {
        $options['profile_id'] = $profile_id;
    } elseif ( ! empty( $provider ) && $provider !== 'default' ) {
        $options['provider'] = $provider;
    }
    
    $result = WPC_AI_Handler::generate( $prompt, $options );
    
    // Log usage
    WPC_AI_Handler::log_usage();
    
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }
    
    // Try to parse as JSON
    $parsed = json_decode( $result, true );
    if ( json_last_error() === JSON_ERROR_NONE ) {
        wp_send_json_success( $parsed );
    }
    
    wp_send_json_success( $result );
}

function wpc_ajax_ai_create_item() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $title = sanitize_text_field( $_POST['title'] ?? '' );
    $public_name = sanitize_text_field( $_POST['public_name'] ?? '' );
    $description = sanitize_textarea_field( $_POST['description'] ?? '' );
    $rating = floatval( $_POST['rating'] ?? 4.5 );
    $price = sanitize_text_field( $_POST['price'] ?? '' );
    $period = sanitize_text_field( $_POST['period'] ?? '/mo' );
    $pros = json_decode( stripslashes( $_POST['pros'] ?? '[]' ), true );
    $cons = json_decode( stripslashes( $_POST['cons'] ?? '[]' ), true );
    $pricing_plans = json_decode( stripslashes( $_POST['pricing_plans'] ?? '[]' ), true );
    $best_use_cases = json_decode( stripslashes( $_POST['best_use_cases'] ?? '[]' ), true );
    
    if ( empty( $title ) ) {
        wp_send_json_error( 'Title is required' );
    }
    
    // Create post
    $post_id = wp_insert_post( [
        'post_title'  => $title,
        'post_type'   => 'comparison_item',
        'post_status' => 'draft',
        'post_content' => ''
    ] );
    
    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( $post_id->get_error_message() );
    }
    
    // Save meta
    if ( ! empty( $public_name ) ) {
        update_post_meta( $post_id, '_wpc_public_name', $public_name );
    }
    update_post_meta( $post_id, '_wpc_short_description', $description );
    update_post_meta( $post_id, '_wpc_rating', $rating );
    update_post_meta( $post_id, '_wpc_price', $price );
    update_post_meta( $post_id, '_wpc_price_period', $period );
    update_post_meta( $post_id, '_wpc_pros', is_array( $pros ) ? implode( "\n", $pros ) : '' );
    update_post_meta( $post_id, '_wpc_cons', is_array( $cons ) ? implode( "\n", $cons ) : '' );
    
    if ( ! empty( $pricing_plans ) ) {
        update_post_meta( $post_id, '_wpc_pricing_plans', $pricing_plans );
    }
    
    // Save Best Use Cases as array of objects for _wpc_use_cases
    // Structure: [{ name, desc, icon, image }]
    if ( ! empty( $best_use_cases ) && is_array( $best_use_cases ) ) {
        $formatted_cases = [];
        foreach ( $best_use_cases as $case ) {
            if ( is_array( $case ) ) {
                // Already object format from AI
                $formatted_cases[] = [
                    'name'  => sanitize_text_field( $case['name'] ?? '' ),
                    'desc'  => sanitize_textarea_field( $case['desc'] ?? '' ),
                    'icon'  => sanitize_text_field( $case['icon'] ?? '' ),
                    'image' => esc_url_raw( $case['image'] ?? '' ),
                ];
            } elseif ( is_string( $case ) ) {
                // Simple string fallback
                $formatted_cases[] = [
                    'name'  => sanitize_text_field( $case ),
                    'desc'  => '',
                    'icon'  => '',
                    'image' => '',
                ];
            }
        }
        update_post_meta( $post_id, '_wpc_use_cases', $formatted_cases );
    }
    
    wp_send_json_success( [
        'post_id' => $post_id,
        'edit_url' => get_edit_post_link( $post_id, 'raw' )
    ] );
}


add_action( 'wp_ajax_wpc_ai_create_tool', 'wpc_ajax_ai_create_tool' );
function wpc_ajax_ai_create_tool() {
    check_ajax_referer( 'wpc_ai_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $title = sanitize_text_field( $_POST['title'] ?? '' );
    $description = sanitize_textarea_field( $_POST['description'] ?? '' );
    $badge = sanitize_text_field( $_POST['badge'] ?? '' );
    $rating = floatval( $_POST['rating'] ?? 0 );
    $link = esc_url_raw( $_POST['link'] ?? '' );
    
    // Optional: Price (not standard field but useful to save)
    $price = sanitize_text_field( $_POST['price'] ?? '' );
    
    if ( empty( $title ) ) {
        wp_send_json_error( 'Title is required' );
    }
    
    // Create post
    $post_id = wp_insert_post( [
        'post_title'  => $title,
        'post_type'   => 'comparison_tool',
        'post_status' => 'draft',
        'post_content' => ''
    ] );
    
    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( $post_id->get_error_message() );
    }
    
    // Save meta keys (aligned with tools-cpt.php)
    update_post_meta( $post_id, '_wpc_tool_short_description', $description );
    update_post_meta( $post_id, '_tool_badge', $badge );
    update_post_meta( $post_id, '_wpc_tool_rating', $rating );
    update_post_meta( $post_id, '_tool_link', $link );
    
    if ( ! empty( $price ) ) {
        update_post_meta( $post_id, '_wpc_tool_price', $price );
    }
    
    // Try to update custom table if class exists
    if ( class_exists('WPC_Tools_Database') ) {
        $db = new WPC_Tools_Database();
        $db->create_table(); // Ensure exists
        $db->update_tool( $post_id, [
            'badge_text'        => $badge,
            'link'              => $link,
            'short_description' => $description,
            'rating'            => $rating
        ] );
    }
    
    wp_send_json_success( [
        'post_id' => $post_id,
        'edit_url' => get_edit_post_link( $post_id, 'raw' )
    ] );
}
