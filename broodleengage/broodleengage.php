<?php
/**
 * Broodle Engage - WHMCS Provisioning Module
 *
 * Uses the Chatwoot Platform App API token (NOT the SuperAdmin user token).
 * Create a Platform App at: https://engage.broodle.one/super_admin/platform_apps
 * Paste the generated access_token into the WHMCS Server "Access Hash" field.
 *
 * Confirmed working endpoints (tested live against engage.broodle.one):
 *
 *  CREATE ACCOUNT  POST   /platform/api/v1/accounts
 *  GET ACCOUNT     GET    /platform/api/v1/accounts/{id}
 *  UPDATE ACCOUNT  PATCH  /platform/api/v1/accounts/{id}          (name, status, custom_attributes)
 *  SUSPEND         PATCH  /platform/api/v1/accounts/{id}          {status: suspended}
 *  UNSUSPEND       PATCH  /platform/api/v1/accounts/{id}          {status: active}
 *  DELETE ACCOUNT  DELETE /platform/api/v1/accounts/{id}
 *  CREATE USER     POST   /platform/api/v1/users                  returns access_token
 *  UPDATE USER     PATCH  /platform/api/v1/users/{id}             (password reset)
 *  DELETE USER     DELETE /platform/api/v1/users/{id}
 *  ASSIGN USER     POST   /platform/api/v1/accounts/{id}/account_users
 *  REMOVE USER     DELETE /platform/api/v1/accounts/{id}/account_users  {user_id}
 *  CREATE INBOX    POST   /api/v1/accounts/{id}/inboxes            (uses user access_token)
 *
 * WHMCS Server config:
 *   Hostname    : engage.broodle.one
 *   Access Hash : <Platform App access_token>
 *   Username    : (leave blank)
 *   Password    : (leave blank)
 *   SSL         : checked
 *
 * @author  Broodle <https://broodle.host>
 * @link    https://engage.broodle.one
 * @version 2.1.0
 *
 * Auto-update: tags releases on https://github.com/maitpatni/broodle-engage-whmcs
 * WHMCS admin can check for and apply updates from the server module page.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

// ─────────────────────────────────────────────────────────────────────────────
// UPDATE CHECKER CONSTANTS
// ─────────────────────────────────────────────────────────────────────────────

define('BROODLEENGAGE_VERSION',      '2.1.0');
define('BROODLEENGAGE_GITHUB_REPO',  'maitpatni/broodle-engage-whmcs');
define('BROODLEENGAGE_MODULE_DIR',   __DIR__);
define('BROODLEENGAGE_UPDATE_CACHE', __DIR__ . '/.update_cache.json');

// ─────────────────────────────────────────────────────────────────────────────
// META DATA
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_MetaData()
{
    return [
        'DisplayName'                            => 'Broodle Engage',
        'APIVersion'                             => '1.1',
        'RequiresServer'                         => true,
        'DefaultNonSSLPort'                      => '80',
        'DefaultSSLPort'                         => '443',
        'ServiceSingleSignOnLabel'               => 'Login to Broodle Engage',
        'AdminSingleSignOnLabel'                 => 'Login to Broodle Engage Admin',
        'ListAccountsUniqueIdentifierDisplayName'=> 'Email',
        'ListAccountsUniqueIdentifierField'      => 'username',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// CONFIG OPTIONS  (per-product, Module Settings tab in WHMCS)
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_ConfigOptions()
{
    return [
        'Plan Name' => [
            'Type'        => 'text',
            'Size'        => '40',
            'Default'     => 'Starter',
            'Description' => 'Plan label shown to the customer (e.g. Starter, Pro, Business)',
        ],
        'Max Agents' => [
            'Type'        => 'text',
            'Size'        => '10',
            'Default'     => '5',
            'Description' => 'Maximum agent seats for this plan',
        ],
        'Max Inboxes' => [
            'Type'        => 'text',
            'Size'        => '10',
            'Default'     => '3',
            'Description' => 'Maximum inboxes for this plan',
        ],
        'Auto Create Email Inbox' => [
            'Type'        => 'yesno',
            'Default'     => 'yes',
            'Description' => 'Auto-create an email inbox on provisioning',
        ],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// INTERNAL HELPERS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Build the Chatwoot base URL from WHMCS server params.
 */
function broodleengage_getApiBase(array $params): string
{
    $hostname = rtrim($params['serverhostname'] ?? '', '/');
    if (strpos($hostname, 'http') === 0) {
        return $hostname;
    }
    $ssl         = !empty($params['serversecure']) ? 'https' : 'http';
    $defaultPort = !empty($params['serversecure']) ? '443' : '80';
    $port        = !empty($params['serverport']) ? (string) $params['serverport'] : $defaultPort;
    $portSuffix  = ($port !== $defaultPort) ? ':' . $port : '';
    return "{$ssl}://{$hostname}{$portSuffix}";
}

/**
 * Make a Chatwoot API request.
 *
 * @param string $method     HTTP method
 * @param string $endpoint   Path starting with /
 * @param array  $data       Request body (for POST/PATCH/PUT)
 * @param string $token      api_access_token header value
 * @param string $baseUrl    e.g. https://engage.broodle.one
 * @return array             Decoded JSON response
 * @throws Exception         On cURL error or HTTP 4xx/5xx
 */
function broodleengage_apiRequest(string $method, string $endpoint, array $data, string $token, string $baseUrl): array
{
    $method = strtoupper($method);
    $ch     = curl_init();

    $opts = [
        CURLOPT_URL            => $baseUrl . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'api_access_token: ' . $token,
        ],
        CURLOPT_CUSTOMREQUEST  => $method,
    ];

    if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    // DELETE with body (used for removing account_users)
    if ($method === 'DELETE' && !empty($data)) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($data);
    }

    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception('cURL Error: ' . $curlErr);
    }

    // 200/204 with empty body (e.g. DELETE account) is success
    if (in_array($httpCode, [200, 204], true) && empty(trim($response))) {
        return [];
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
        $msg = $decoded['error'] ?? $decoded['message'] ?? $response;
        throw new Exception("Chatwoot API [{$httpCode}]: {$msg}");
    }

    return is_array($decoded) ? $decoded : [];
}

/**
 * Ensure the local tracking table exists, with auto-migration for older versions.
 */
function broodleengage_ensureTable(): void
{
    if (!Capsule::schema()->hasTable('mod_broodleengage')) {
        Capsule::schema()->create('mod_broodleengage', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('service_id')->unique();
            $table->unsignedInteger('chatwoot_account_id')->nullable();
            $table->unsignedInteger('chatwoot_user_id')->nullable();
            $table->string('chatwoot_user_token', 512)->nullable(); // user's own access_token for SSO + inbox creation
            $table->string('chatwoot_email', 255)->nullable();
            $table->string('chatwoot_status', 20)->default('active'); // active|suspended|terminated
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();
        });
        return;
    }

    // Migrations for users upgrading from v1.x
    if (!Capsule::schema()->hasColumn('mod_broodleengage', 'chatwoot_user_token')) {
        Capsule::schema()->table('mod_broodleengage', function ($table) {
            $table->string('chatwoot_user_token', 512)->nullable()->after('chatwoot_user_id');
        });
    }
    if (!Capsule::schema()->hasColumn('mod_broodleengage', 'chatwoot_status')) {
        Capsule::schema()->table('mod_broodleengage', function ($table) {
            $table->string('chatwoot_status', 20)->default('active');
        });
    }
    if (!Capsule::schema()->hasColumn('mod_broodleengage', 'provisioned_at')) {
        Capsule::schema()->table('mod_broodleengage', function ($table) {
            $table->timestamp('provisioned_at')->nullable();
        });
    }
}

/**
 * Send a WHMCS email template using localAPI.
 *
 * For 'product' type templates, $relatedId must be the SERVICE ID (tblhosting.id).
 * For 'general' type templates, $relatedId must be the CLIENT ID (tblclients.id).
 *
 * customvars when using localAPI must be a plain array — NOT base64/serialized.
 * Template variables are accessed in the email body as {$variable_name} (Smarty).
 */
function broodleengage_sendEmail(string $templateName, int $relatedId, array $mergeFields): void
{
    $result = localAPI('SendEmail', [
        'messagename' => $templateName,
        'id'          => $relatedId,   // service ID for product-type templates
        'customvars'  => $mergeFields, // plain array for localAPI — NOT base64_encode(serialize())
    ]);

    if (isset($result['result']) && $result['result'] !== 'success') {
        throw new Exception('SendEmail failed: ' . ($result['message'] ?? json_encode($result)));
    }
}

/**
 * Generate a cryptographically secure random password.
 */
function broodleengage_generatePassword(int $length = 16): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $max   = strlen($chars) - 1;
    $pass  = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, $max)];
    }
    return $pass;
}

// ─────────────────────────────────────────────────────────────────────────────
// CREATE ACCOUNT
// Called when a new service is activated in WHMCS.
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_CreateAccount(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl      = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash']; // Platform App token
        $client       = $params['clientsdetails'];
        $serviceId    = (int) $params['serviceid'];
        $clientId     = (int) $params['userid'];
        $fullName     = trim($client['firstname'] . ' ' . $client['lastname']);
        $email        = $client['email'];
        $planName     = $params['configoption1'] ?: 'Starter';
        $maxAgents    = (int) ($params['configoption2'] ?: 5);
        $maxInboxes   = (int) ($params['configoption3'] ?: 3);

        // ── 1. Create Chatwoot Account ────────────────────────────────────────
        $accountResp = broodleengage_apiRequest('POST', '/platform/api/v1/accounts', [
            'name'          => $fullName . "'s Workspace",
            'locale'        => 'en',
            'support_email' => $email,
            'status'        => 'active',
            'custom_attributes' => [
                'whmcs_service_id' => $serviceId,
                'whmcs_plan'       => $planName,
                'max_agents'       => $maxAgents,
                'max_inboxes'      => $maxInboxes,
            ],
        ], $platformToken, $baseUrl);

        $chatwootAccountId = (int) $accountResp['id'];

        // ── 2. Create Chatwoot User ───────────────────────────────────────────
        $password = broodleengage_generatePassword();

        $userResp = broodleengage_apiRequest('POST', '/platform/api/v1/users', [
            'name'                  => $fullName,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
        ], $platformToken, $baseUrl);

        $chatwootUserId    = (int) $userResp['id'];
        $chatwootUserToken = $userResp['access_token']; // permanent token for this user

        // ── 3. Assign user as administrator of the account ────────────────────
        broodleengage_apiRequest('POST', "/platform/api/v1/accounts/{$chatwootAccountId}/account_users", [
            'user_id' => $chatwootUserId,
            'role'    => 'administrator',
        ], $platformToken, $baseUrl);

        // ── 4. Auto-create email inbox (uses user token, not platform token) ──
        if ($params['configoption4'] === 'on') {
            try {
                // Use a unique inbox email to avoid conflicts
                $inboxEmail = 'support-' . $chatwootAccountId . '@' . parse_url($baseUrl, PHP_URL_HOST);
                broodleengage_apiRequest('POST', "/api/v1/accounts/{$chatwootAccountId}/inboxes", [
                    'name'    => 'Email Support',
                    'channel' => [
                        'type'  => 'email',
                        'email' => $inboxEmail,
                    ],
                ], $chatwootUserToken, $baseUrl);
            } catch (Exception $e) {
                // Non-fatal — inbox failure must not block provisioning
                logModuleCall('broodleengage', 'CreateAccount_inbox', [], [], $e->getMessage(), []);
            }
        }

        // ── 5. Persist to local DB ────────────────────────────────────────────
        $now = date('Y-m-d H:i:s');
        $existing = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();

        if ($existing) {
            Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->update([
                'chatwoot_account_id' => $chatwootAccountId,
                'chatwoot_user_id'    => $chatwootUserId,
                'chatwoot_user_token' => $chatwootUserToken,
                'chatwoot_email'      => $email,
                'chatwoot_status'     => 'active',
                'provisioned_at'      => $now,
                'updated_at'          => $now,
            ]);
        } else {
            Capsule::table('mod_broodleengage')->insert([
                'service_id'          => $serviceId,
                'chatwoot_account_id' => $chatwootAccountId,
                'chatwoot_user_id'    => $chatwootUserId,
                'chatwoot_user_token' => $chatwootUserToken,
                'chatwoot_email'      => $email,
                'chatwoot_status'     => 'active',
                'provisioned_at'      => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        // ── 6. Store credentials in WHMCS service record ──────────────────────
        Capsule::table('tblhosting')->where('id', $serviceId)->update([
            'username' => $email,
            'password' => encrypt($password),
        ]);

        // ── 7. Send welcome email ─────────────────────────────────────────────
        // id = service ID for product-type email templates
        try {
            broodleengage_sendEmail('Broodle Engage Welcome Email', $serviceId, [
                'service_name'   => 'Broodle Engage',
                'account_id'     => $chatwootAccountId,
                'login_email'    => $email,
                'login_password' => $password,
                'login_url'      => $baseUrl . '/auth/sign_in',
                'dashboard_url'  => $baseUrl . '/app/accounts/' . $chatwootAccountId . '/dashboard',
                'plan_name'      => $planName,
            ]);
        } catch (Exception $e) {
            logModuleCall('broodleengage', 'CreateAccount_email', [], [], $e->getMessage(), []);
        }

        logModuleCall('broodleengage', __FUNCTION__,
            ['serviceid' => $serviceId, 'email' => $email, 'account_id' => $chatwootAccountId],
            $accountResp, 'Account created successfully', ['serveraccesshash']
        );

        return 'success';

    } catch (Exception $e) {
        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $params['serviceid'] ?? ''], [], $e->getMessage(), ['serveraccesshash']);
        return $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// SUSPEND ACCOUNT
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_SuspendAccount(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];
        $serviceId     = (int) $params['serviceid'];

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();
        if (!$record) {
            return 'No Broodle Engage account found for this service.';
        }

        broodleengage_apiRequest('PATCH', "/platform/api/v1/accounts/{$record->chatwoot_account_id}", [
            'status' => 'suspended',
        ], $platformToken, $baseUrl);

        Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->update([
            'chatwoot_status' => 'suspended',
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $serviceId], [], 'Suspended', ['serveraccesshash']);
        return 'success';

    } catch (Exception $e) {
        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $params['serviceid'] ?? ''], [], $e->getMessage(), ['serveraccesshash']);
        return $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// UNSUSPEND ACCOUNT
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_UnsuspendAccount(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];
        $serviceId     = (int) $params['serviceid'];

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();
        if (!$record) {
            return 'No Broodle Engage account found for this service.';
        }

        broodleengage_apiRequest('PATCH', "/platform/api/v1/accounts/{$record->chatwoot_account_id}", [
            'status' => 'active',
        ], $platformToken, $baseUrl);

        Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->update([
            'chatwoot_status' => 'active',
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $serviceId], [], 'Unsuspended', ['serveraccesshash']);
        return 'success';

    } catch (Exception $e) {
        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $params['serviceid'] ?? ''], [], $e->getMessage(), ['serveraccesshash']);
        return $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// TERMINATE ACCOUNT
// Deletes the Chatwoot account AND the user created for this service.
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_TerminateAccount(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];
        $serviceId     = (int) $params['serviceid'];

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();
        if (!$record) {
            return 'success'; // Already gone
        }

        // Delete the Chatwoot account (removes all data inside it)
        try {
            broodleengage_apiRequest('DELETE', "/platform/api/v1/accounts/{$record->chatwoot_account_id}", [], $platformToken, $baseUrl);
        } catch (Exception $e) {
            // Log but continue — account may already be deleted
            logModuleCall('broodleengage', 'TerminateAccount_deleteAccount', [], [], $e->getMessage(), ['serveraccesshash']);
        }

        // Delete the Chatwoot user
        try {
            broodleengage_apiRequest('DELETE', "/platform/api/v1/users/{$record->chatwoot_user_id}", [], $platformToken, $baseUrl);
        } catch (Exception $e) {
            logModuleCall('broodleengage', 'TerminateAccount_deleteUser', [], [], $e->getMessage(), ['serveraccesshash']);
        }

        // Remove local record
        Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->delete();

        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $serviceId], [], 'Terminated', ['serveraccesshash']);
        return 'success';

    } catch (Exception $e) {
        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $params['serviceid'] ?? ''], [], $e->getMessage(), ['serveraccesshash']);
        return $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// CHANGE PACKAGE
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_ChangePackage(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];
        $serviceId     = (int) $params['serviceid'];
        $planName      = $params['configoption1'] ?: 'Starter';

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();
        if (!$record) {
            return 'No Broodle Engage account found for this service.';
        }

        broodleengage_apiRequest('PATCH', "/platform/api/v1/accounts/{$record->chatwoot_account_id}", [
            'custom_attributes' => [
                'whmcs_plan'  => $planName,
                'max_agents'  => (int) ($params['configoption2'] ?: 5),
                'max_inboxes' => (int) ($params['configoption3'] ?: 3),
            ],
        ], $platformToken, $baseUrl);

        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $serviceId, 'plan' => $planName], [], 'Package changed', ['serveraccesshash']);
        return 'success';

    } catch (Exception $e) {
        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $params['serviceid'] ?? ''], [], $e->getMessage(), ['serveraccesshash']);
        return $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// SERVICE SINGLE SIGN-ON  (Auto Login button in client area)
// Uses the user's own access_token stored at provisioning time.
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_ServiceSingleSignOn(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl   = broodleengage_getApiBase($params);
        $serviceId = (int) $params['serviceid'];

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();

        if (!$record || !$record->chatwoot_user_token) {
            return ['success' => false, 'errorMsg' => 'No Broodle Engage account found for this service.'];
        }

        if ($record->chatwoot_status === 'suspended') {
            return ['success' => false, 'errorMsg' => 'Your Broodle Engage account is currently suspended. Please contact support.'];
        }

        $accountId = $record->chatwoot_account_id;
        $userToken = $record->chatwoot_user_token;

        // Chatwoot accepts auth_token as a query param on /app/login
        // After auth it redirects to the account dashboard
        $redirectUrl = "{$baseUrl}/app/login?auth_token=" . urlencode($userToken)
            . "&account_id={$accountId}";

        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $serviceId], [], 'SSO redirect issued', ['serveraccesshash']);

        return [
            'success'    => true,
            'redirectTo' => $redirectUrl,
        ];

    } catch (Exception $e) {
        logModuleCall('broodleengage', __FUNCTION__, ['serviceid' => $params['serviceid'] ?? ''], [], $e->getMessage(), ['serveraccesshash']);
        return ['success' => false, 'errorMsg' => $e->getMessage()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN SINGLE SIGN-ON  (server-level, goes to super admin panel)
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_AdminSingleSignOn(array $params)
{
    $baseUrl = broodleengage_getApiBase($params);
    return [
        'success'    => true,
        'redirectTo' => $baseUrl . '/super_admin',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// CLIENT AREA OUTPUT
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_ClientArea(array $params)
{
    broodleengage_ensureTable();

    $serviceId     = (int) $params['serviceid'];
    $baseUrl       = broodleengage_getApiBase($params);
    $platformToken = $params['serveraccesshash'];
    $record        = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();

    $isProvisioned     = $record && $record->chatwoot_account_id;
    $chatwootStatus    = $record ? $record->chatwoot_status : 'pending';
    $chatwootAccountId = $isProvisioned ? (int) $record->chatwoot_account_id : null;
    $chatwootEmail     = $record ? $record->chatwoot_email : $params['clientsdetails']['email'];
    $userToken         = $record ? $record->chatwoot_user_token : null;

    $provisionedAt = ($record && !empty($record->provisioned_at))
        ? date('d M Y', strtotime($record->provisioned_at))
        : null;

    // Get WHMCS service record for status + stored password
    $hosting     = Capsule::table('tblhosting')->where('id', $serviceId)->first();
    $whmcsStatus = $hosting ? $hosting->domainstatus : 'Unknown';

    // Decrypt stored password to show on service page
    // htmlspecialchars-escaped version is used in onclick attributes to prevent JS injection
    $servicePassword = '';
    if ($hosting && !empty($hosting->password)) {
        try {
            $servicePassword = decrypt($hosting->password);
        } catch (Exception $e) {
            $servicePassword = '';
        }
    }
    $servicePasswordHtml = htmlspecialchars($servicePassword, ENT_QUOTES, 'UTF-8');

    $planName   = $params['configoption1'] ?: 'Starter';
    $maxAgents  = (int) ($params['configoption2'] ?: 5);
    $maxInboxes = (int) ($params['configoption3'] ?: 3);

    $ssoUrl       = 'clientarea.php?action=productdetails&id=' . $serviceId . '&dosinglesignon=1';
    $dashboardUrl = $isProvisioned
        ? "{$baseUrl}/app/accounts/{$chatwootAccountId}/dashboard"
        : $baseUrl;

    // ── Fetch live stats from Chatwoot API (non-fatal if they fail) ───────────
    $liveAgentCount       = null;
    $liveInboxCount       = null;
    $liveConversations    = null;
    $liveOpenConversations = null;
    $liveContacts         = null;
    $liveAccountStatus    = $chatwootStatus; // fallback to local

    if ($isProvisioned && $userToken && $chatwootStatus === 'active') {
        try {
            // Agent count via Application API (user token)
            $agents = broodleengage_apiRequest('GET', "/api/v1/accounts/{$chatwootAccountId}/agents", [], $userToken, $baseUrl);
            $liveAgentCount = count($agents);
        } catch (Exception $e) { /* non-fatal */ }

        try {
            // Inbox count
            $inboxResp = broodleengage_apiRequest('GET', "/api/v1/accounts/{$chatwootAccountId}/inboxes", [], $userToken, $baseUrl);
            $liveInboxCount = count($inboxResp['payload'] ?? $inboxResp);
        } catch (Exception $e) { /* non-fatal */ }

        try {
            // Conversation counts from meta
            $convResp = broodleengage_apiRequest('GET', "/api/v1/accounts/{$chatwootAccountId}/conversations?page=1", [], $userToken, $baseUrl);
            $meta = $convResp['data']['meta'] ?? [];
            $liveConversations     = $meta['all_count'] ?? 0;
            $liveOpenConversations = ($meta['mine_count'] ?? 0) + ($meta['unassigned_count'] ?? 0);
        } catch (Exception $e) { /* non-fatal */ }

        try {
            // Contact count
            $contactResp = broodleengage_apiRequest('GET', "/api/v1/accounts/{$chatwootAccountId}/contacts?page=1", [], $userToken, $baseUrl);
            $liveContacts = $contactResp['meta']['count'] ?? 0;
        } catch (Exception $e) { /* non-fatal */ }

        try {
            // Live account status from Platform API
            $accountData = broodleengage_apiRequest('GET', "/platform/api/v1/accounts/{$chatwootAccountId}", [], $platformToken, $baseUrl);
            $liveAccountStatus = $accountData['status'] ?? $chatwootStatus;
            // Sync local DB if status drifted
            if ($liveAccountStatus !== $chatwootStatus) {
                Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->update([
                    'chatwoot_status' => $liveAccountStatus,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
                $chatwootStatus = $liveAccountStatus;
            }
        } catch (Exception $e) { /* non-fatal */ }
    }

    return [
        'templatefile' => 'templates/clientarea',
        'vars'         => [
            'baseUrl'              => $baseUrl,
            'accountId'            => $chatwootAccountId,
            'email'                => $chatwootEmail,
            'servicePassword'      => $servicePassword,      // raw (for display)
            'servicePasswordHtml'  => $servicePasswordHtml, // HTML-escaped (for onclick attrs)
            'planName'             => $planName,
            'maxAgents'            => $maxAgents,
            'maxInboxes'           => $maxInboxes,
            'ssoUrl'               => $ssoUrl,
            'dashboardUrl'         => $dashboardUrl,
            'isProvisioned'        => $isProvisioned,
            'serviceId'            => $serviceId,
            'chatwootStatus'       => $chatwootStatus,
            'whmcsStatus'          => $whmcsStatus,
            'provisionedAt'        => $provisionedAt,
            // Live stats (null = could not fetch)
            'liveAgentCount'       => $liveAgentCount,
            'liveInboxCount'       => $liveInboxCount,
            'liveConversations'    => $liveConversations,
            'liveOpenConversations'=> $liveOpenConversations,
            'liveContacts'         => $liveContacts,
        ],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// UPDATE CHECKER + APPLIER
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Fetch the latest release info from GitHub Releases API.
 * Returns ['tag' => 'v2.1.0', 'version' => '2.1.0', 'zip_url' => '...', 'body' => '...']
 * or throws Exception on failure.
 */
function broodleengage_fetchLatestRelease(): array
{
    $url = 'https://api.github.com/repos/' . BROODLEENGAGE_GITHUB_REPO . '/releases/latest';
    $ch  = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: BroodleEngage-WHMCS-Module/' . BROODLEENGAGE_VERSION,
            'Accept: application/vnd.github+json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception('GitHub API cURL error: ' . $curlErr);
    }
    if ($httpCode !== 200) {
        throw new Exception('GitHub API returned HTTP ' . $httpCode . '. Response: ' . substr($response, 0, 200));
    }

    $data = json_decode($response, true);
    if (empty($data['tag_name'])) {
        throw new Exception('Could not parse GitHub release response: ' . substr($response, 0, 200));
    }

    $tag     = $data['tag_name'];       // e.g. v2.1.0
    $version = ltrim($tag, 'v');        // e.g. 2.1.0
    $body    = $data['body'] ?? '';

    // Use the direct zipball URL — GitHub redirects this to S3, we follow with CURLOPT_FOLLOWLOCATION
    $zipUrl = 'https://github.com/' . BROODLEENGAGE_GITHUB_REPO . '/archive/refs/tags/' . $tag . '.zip';

    return compact('tag', 'version', 'zipUrl', 'body');
}

/**
 * Download the release zip, extract it, and overwrite module files.
 * Returns a status message string.
 */
function broodleengage_doApplyUpdate(string $zipUrl, string $newVersion): string
{
    $moduleDir = BROODLEENGAGE_MODULE_DIR;
    $ts        = time();
    $tmpZip    = sys_get_temp_dir() . '/broodleengage_update_' . $ts . '.zip';
    $tmpDir    = sys_get_temp_dir() . '/broodleengage_update_' . $ts;

    // ── 1. Download zip ───────────────────────────────────────────────────────
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $zipUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: BroodleEngage-WHMCS-Module/' . BROODLEENGAGE_VERSION,
        ],
    ]);
    $zipData  = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception('Download failed: ' . $curlErr);
    }
    if ($httpCode !== 200 || empty($zipData)) {
        throw new Exception('Download returned HTTP ' . $httpCode . ' from ' . $zipUrl);
    }

    if (file_put_contents($tmpZip, $zipData) === false) {
        throw new Exception('Could not write zip to temp dir: ' . sys_get_temp_dir());
    }

    // ── 2. Extract zip ────────────────────────────────────────────────────────
    if (!class_exists('ZipArchive')) {
        @unlink($tmpZip);
        throw new Exception('PHP ZipArchive extension is not available on this server.');
    }

    $zip = new ZipArchive();
    $res = $zip->open($tmpZip);
    if ($res !== true) {
        @unlink($tmpZip);
        throw new Exception('Could not open zip archive (ZipArchive error code: ' . $res . ').');
    }
    @mkdir($tmpDir, 0755, true);
    $zip->extractTo($tmpDir);
    $zip->close();
    @unlink($tmpZip);

    // ── 3. Find the broodleengage/ subfolder ──────────────────────────────────
    // GitHub archive structure: broodle-engage-whmcs-2.0.3/broodleengage/
    // We search recursively for the first directory named "broodleengage"
    $srcDir = null;
    $iter   = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $path => $info) {
        if ($info->isDir() && $info->getFilename() === 'broodleengage') {
            $srcDir = $path . DIRECTORY_SEPARATOR;
            break;
        }
    }

    if (!$srcDir) {
        broodleengage_rrmdir($tmpDir);
        throw new Exception('Could not locate broodleengage/ folder inside the downloaded zip. Contents: ' . implode(', ', array_slice(scandir($tmpDir), 2, 10)));
    }

    // ── 4. Copy new files over existing module ────────────────────────────────
    broodleengage_rcopy($srcDir, $moduleDir . DIRECTORY_SEPARATOR);

    // ── 5. Clean up ───────────────────────────────────────────────────────────
    broodleengage_rrmdir($tmpDir);

    // Clear the update cache so the banner refreshes on next page load
    @unlink(BROODLEENGAGE_UPDATE_CACHE);

    return "Module updated to v{$newVersion} successfully. Please reload this page.";
}

/** Recursive copy helper */
function broodleengage_rcopy(string $src, string $dst): void
{
    @mkdir($dst, 0755, true);
    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..') continue;
        $s = rtrim($src, '/') . '/' . $item;
        $d = rtrim($dst, '/') . '/' . $item;
        if (is_dir($s)) {
            broodleengage_rcopy($s, $d);
        } else {
            copy($s, $d);
        }
    }
}

/** Recursive rmdir helper */
function broodleengage_rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? broodleengage_rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN CUSTOM BUTTONS  (shown in admin > service management page)
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_AdminCustomButtonArray()
{
    return [
        'Sync Account Status' => 'SyncStatus',
        'Reset Password'      => 'ResetPassword',
        'Check for Updates'   => 'CheckForUpdates',
        'Apply Update'        => 'ApplyUpdate',
    ];
}

function broodleengage_SyncStatus(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];
        $serviceId     = (int) $params['serviceid'];

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();
        if (!$record) {
            return ['status' => 'error', 'description' => 'No Broodle Engage account record found.'];
        }

        $data         = broodleengage_apiRequest('GET', "/platform/api/v1/accounts/{$record->chatwoot_account_id}", [], $platformToken, $baseUrl);
        $remoteStatus = $data['status'] ?? 'unknown';

        Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->update([
            'chatwoot_status' => $remoteStatus,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);

        return ['status' => 'success', 'description' => "Chatwoot account status: {$remoteStatus}. Local record synced."];

    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function broodleengage_ResetPassword(array $params)
{
    try {
        broodleengage_ensureTable();

        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];
        $serviceId     = (int) $params['serviceid'];
        $clientId      = (int) $params['userid'];

        $record = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();
        if (!$record) {
            return ['status' => 'error', 'description' => 'No Broodle Engage account record found.'];
        }

        $newPassword = broodleengage_generatePassword();

        broodleengage_apiRequest('PATCH', "/platform/api/v1/users/{$record->chatwoot_user_id}", [
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ], $platformToken, $baseUrl);

        // Update WHMCS service record
        Capsule::table('tblhosting')->where('id', $serviceId)->update([
            'password'   => encrypt($newPassword),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Email the client — id = service ID for product-type templates
        try {
            broodleengage_sendEmail('Broodle Engage Password Reset', $serviceId, [
                'service_name' => 'Broodle Engage',
                'new_password' => $newPassword,
                'login_url'    => broodleengage_getApiBase($params) . '/auth/sign_in',
            ]);
        } catch (Exception $e) {
            logModuleCall('broodleengage', 'ResetPassword_email', [], [], $e->getMessage(), []);
        }

        return ['status' => 'success', 'description' => 'Password reset successfully. New credentials emailed to client.'];

    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function broodleengage_CheckForUpdates(array $params): array
{
    try {
        $release   = broodleengage_fetchLatestRelease();
        $latest    = $release['version'];
        $current   = BROODLEENGAGE_VERSION;
        $isNewer   = version_compare($latest, $current, '>');
        $notes     = trim($release['body']);
        $notesHtml = $notes ? '<br><br><strong>Release notes:</strong><br>' . nl2br(htmlspecialchars($notes)) : '';

        // Always persist cache so the banner on the tab also updates
        file_put_contents(BROODLEENGAGE_UPDATE_CACHE,
            json_encode(['version' => $latest, 'zip_url' => $release['zipUrl'], 'ts' => time()]));

        if ($isNewer) {
            return [
                'status'      => 'success',
                'description' => "✅ Update available: v{$latest} (you have v{$current}). "
                    . "Click <strong>Apply Update</strong> to install it now.{$notesHtml}",
            ];
        }

        return [
            'status'      => 'success',
            'description' => "✅ You are running the latest version (v{$current}). Latest on GitHub: v{$latest}.",
        ];

    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Update check failed: ' . $e->getMessage()];
    }
}

function broodleengage_ApplyUpdate(array $params): array
{
    try {
        // Always do a fresh fetch to get the real latest version + zip URL
        $release = broodleengage_fetchLatestRelease();
        $latest  = $release['version'];
        $zipUrl  = $release['zipUrl'];

        if (!version_compare($latest, BROODLEENGAGE_VERSION, '>')) {
            return [
                'status'      => 'success',
                'description' => 'Already on the latest version (v' . BROODLEENGAGE_VERSION . '). Nothing to update.',
            ];
        }

        if (!is_writable(BROODLEENGAGE_MODULE_DIR)) {
            throw new Exception(
                'Module directory is not writable: ' . BROODLEENGAGE_MODULE_DIR .
                '. Fix permissions (chmod 755 or chown to web server user) and try again.'
            );
        }

        $msg = broodleengage_doApplyUpdate($zipUrl, $latest);

        logModuleCall('broodleengage', 'ApplyUpdate',
            ['from' => BROODLEENGAGE_VERSION, 'to' => $latest], [], $msg, []);

        return ['status' => 'success', 'description' => $msg];

    } catch (Exception $e) {
        logModuleCall('broodleengage', 'ApplyUpdate', [], [], $e->getMessage(), []);
        return ['status' => 'error', 'description' => 'Update failed: ' . $e->getMessage()];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// TEST CONNECTION  (shown in WHMCS server config page)
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_TestConnection(array $params)
{
    try {
        $baseUrl       = broodleengage_getApiBase($params);
        $platformToken = $params['serveraccesshash'];

        if (empty($platformToken)) {
            return [
                'success' => false,
                'error'   => 'Access Hash is empty. Add your Platform App token in the server configuration.',
            ];
        }

        // POST /platform/api/v1/users with a dummy payload to verify the token is valid.
        // A valid token returns 422 (validation error) — invalid token returns 401/403.
        // We treat anything other than a network/auth failure as "connected".
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => rtrim($baseUrl, '/') . '/platform/api/v1/users',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode(['name' => '', 'email' => '', 'password' => '']),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'api_access_token: ' . $platformToken,
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception('cURL Error: ' . $curlErr);
        }

        if ($httpCode === 401 || $httpCode === 403) {
            return [
                'success' => false,
                'error'   => "Authentication failed (HTTP {$httpCode}). Check your Platform App token in the Access Hash field.",
            ];
        }

        // 422 = token valid, request rejected due to empty fields — that's fine, connection works
        // 200/201 = token valid and somehow accepted
        return [
            'success' => true,
            'error'   => "Connected successfully to {$baseUrl} (HTTP {$httpCode}).",
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error'   => $e->getMessage(),
        ];
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN SERVICES TAB  — "Broodle Engage" tab on /admin/clientsservices.php
// Admin can enter a Chatwoot Account ID to link/change the account for a service.
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_AdminServicesTabFields(array $params): array
{
    broodleengage_ensureTable();

    $serviceId     = (int) $params['serviceid'];
    $baseUrl       = broodleengage_getApiBase($params);
    $platformToken = $params['serveraccesshash'];
    $record        = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->first();

    // ── Fetch live stats for currently linked account ─────────────────────────
    $accountInfo  = null;
    $agentCount   = null;
    $inboxCount   = null;
    $convMeta     = [];
    $contactCount = null;
    $fetchError   = null;

    if ($record && $record->chatwoot_account_id) {
        $aid = (int) $record->chatwoot_account_id;
        try {
            $accountInfo = broodleengage_apiRequest('GET', "/platform/api/v1/accounts/{$aid}", [], $platformToken, $baseUrl);
        } catch (Exception $e) {
            $fetchError = $e->getMessage();
        }
        if ($record->chatwoot_user_token) {
            $ut = $record->chatwoot_user_token;
            try {
                $agents     = broodleengage_apiRequest('GET', "/api/v1/accounts/{$aid}/agents", [], $ut, $baseUrl);
                $agentCount = count($agents);
            } catch (Exception $e) {}
            try {
                $inboxResp  = broodleengage_apiRequest('GET', "/api/v1/accounts/{$aid}/inboxes", [], $ut, $baseUrl);
                $inboxCount = count($inboxResp['payload'] ?? $inboxResp);
            } catch (Exception $e) {}
            try {
                $convResp = broodleengage_apiRequest('GET', "/api/v1/accounts/{$aid}/conversations?page=1", [], $ut, $baseUrl);
                $convMeta = $convResp['data']['meta'] ?? [];
            } catch (Exception $e) {}
            try {
                $contactResp  = broodleengage_apiRequest('GET', "/api/v1/accounts/{$aid}/contacts?page=1", [], $ut, $baseUrl);
                $contactCount = $contactResp['meta']['count'] ?? 0;
            } catch (Exception $e) {}
        }
    }

    // ── Fetch users for the currently linked account (for the user picker) ────
    $linkedAccountUsers = [];
    if ($record && $record->chatwoot_account_id) {
        $aid = (int) $record->chatwoot_account_id;
        try {
            $auList = broodleengage_apiRequest('GET', "/platform/api/v1/accounts/{$aid}/account_users", [], $platformToken, $baseUrl);
            foreach (($auList['payload'] ?? $auList) as $au) {
                $uid = (int) ($au['user_id'] ?? $au['id'] ?? 0);
                if (!$uid) continue;
                try {
                    $ud = broodleengage_apiRequest('GET', "/platform/api/v1/users/{$uid}", [], $platformToken, $baseUrl);
                    $linkedAccountUsers[] = [
                        'id'           => $uid,
                        'name'         => $ud['name']         ?? '',
                        'email'        => $ud['email']        ?? '',
                        'access_token' => $ud['access_token'] ?? '',
                        'role'         => $au['role']         ?? 'agent',
                    ];
                } catch (Exception $e) {}
            }
        } catch (Exception $e) {}
    }

    // ── Update banner (cached 6 hours) ────────────────────────────────────────
    $updateBanner = '';
    $updateCache  = file_exists(BROODLEENGAGE_UPDATE_CACHE)
        ? json_decode(file_get_contents(BROODLEENGAGE_UPDATE_CACHE), true) : null;
    if (!$updateCache || (time() - ($updateCache['ts'] ?? 0)) > 21600) {
        try {
            $rel         = broodleengage_fetchLatestRelease();
            $updateCache = ['version' => $rel['version'], 'zip_url' => $rel['zipUrl'], 'ts' => time()];
            file_put_contents(BROODLEENGAGE_UPDATE_CACHE, json_encode($updateCache));
        } catch (Exception $e) { $updateCache = null; }
    }
    if ($updateCache && version_compare($updateCache['version'], BROODLEENGAGE_VERSION, '>')) {
        $updateBanner = '<div style="background:#fefce8;border:1px solid #fde047;border-radius:9px;padding:12px 16px;margin-bottom:16px;font-size:13px;">'
            . '🚀 <strong>Update available: v' . htmlspecialchars($updateCache['version']) . '</strong> (installed: v' . BROODLEENGAGE_VERSION . '). '
            . 'Click <strong>Apply Update</strong> in the module commands above.</div>';
    } elseif ($updateCache) {
        $updateBanner = '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:9px;padding:10px 16px;margin-bottom:16px;font-size:12px;color:#166534;">'
            . '✅ Module is up to date (v' . BROODLEENGAGE_VERSION . ')</div>';
    }

    // ── Build HTML ────────────────────────────────────────────────────────────
    $html = '<style>
.be-admin-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:900px;}
.be-admin-section{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;margin-bottom:16px;}
.be-admin-section h4{margin:0 0 14px;font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;padding-bottom:10px;border-bottom:1px solid #f1f5f9;}
.be-admin-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f8fafc;font-size:13px;}
.be-admin-row:last-child{border-bottom:none;}
.be-admin-label{color:#6b7280;min-width:140px;}
.be-admin-value{color:#111827;font-weight:500;text-align:right;}
.be-admin-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:4px;}
.be-admin-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;text-align:center;}
.be-admin-stat-num{font-size:22px;font-weight:700;color:#1e293b;line-height:1;}
.be-admin-stat-lbl{font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:4px;}
.be-admin-badge{display:inline-block;padding:2px 9px;border-radius:12px;font-size:11px;font-weight:600;}
.be-admin-badge.active{background:#dcfce7;color:#166534;}
.be-admin-badge.suspended{background:#fef9c3;color:#854d0e;}
.be-admin-badge.na{background:#f1f5f9;color:#64748b;}
.be-admin-alert{padding:10px 14px;border-radius:7px;font-size:12px;margin-bottom:12px;}
.be-admin-alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;}
.be-admin-alert.warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e;}
.be-picker-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;margin-bottom:16px;}
.be-picker-box h4{margin:0 0 10px;font-size:13px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;}
.be-picker-box p{font-size:12px;color:#6b7280;margin:0 0 12px;line-height:1.5;}
.be-inp{width:100%;border:1px solid #d1d5db;border-radius:7px;padding:9px 12px;font-size:13px;background:#fff;color:#111827;margin-bottom:12px;box-sizing:border-box;}
.be-inp:focus{outline:2px solid #6366f1;border-color:#6366f1;}
.be-sel{width:100%;border:1px solid #d1d5db;border-radius:7px;padding:9px 12px;font-size:13px;background:#fff;color:#111827;margin-bottom:12px;box-sizing:border-box;}
.be-sel:focus{outline:2px solid #6366f1;border-color:#6366f1;}
.be-preview{background:#fff;border:1px solid #c4b5fd;border-radius:8px;padding:14px 16px;margin-bottom:12px;}
.be-preview-row{display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f3f4f6;}
.be-preview-row:last-child{border-bottom:none;}
.be-preview-label{color:#6b7280;}
.be-preview-value{color:#111827;font-weight:500;word-break:break-all;}
</style>
<div class="be-admin-wrap">';

    $html .= $updateBanner;

    // ── Currently linked account ──────────────────────────────────────────────
    if ($record && $record->chatwoot_account_id) {
        $aid         = (int) $record->chatwoot_account_id;
        $uid         = (int) $record->chatwoot_user_id;
        $status      = $accountInfo['status'] ?? $record->chatwoot_status ?? '—';
        $name        = $accountInfo['name']   ?? '—';
        $email       = $record->chatwoot_email ?? '—';
        $prov        = $record->provisioned_at ? date('d M Y H:i', strtotime($record->provisioned_at)) : '—';
        $statusClass = $status === 'active' ? 'active' : ($status === 'suspended' ? 'suspended' : 'na');

        if ($fetchError) {
            $html .= '<div class="be-admin-alert error">⚠️ Could not fetch live account data: ' . htmlspecialchars($fetchError) . '</div>';
        }

        $html .= '<div class="be-admin-section">
            <h4>🔗 Currently Linked Account</h4>
            <div class="be-admin-row"><span class="be-admin-label">Account ID</span><span class="be-admin-value">#' . $aid . '</span></div>
            <div class="be-admin-row"><span class="be-admin-label">Account Name</span><span class="be-admin-value">' . htmlspecialchars($name) . '</span></div>
            <div class="be-admin-row"><span class="be-admin-label">User Email</span><span class="be-admin-value">' . htmlspecialchars($email) . '</span></div>
            <div class="be-admin-row"><span class="be-admin-label">User ID</span><span class="be-admin-value">#' . $uid . '</span></div>
            <div class="be-admin-row"><span class="be-admin-label">Status</span><span class="be-admin-value"><span class="be-admin-badge ' . $statusClass . '">' . htmlspecialchars($status) . '</span></span></div>
            <div class="be-admin-row"><span class="be-admin-label">Provisioned</span><span class="be-admin-value">' . $prov . '</span></div>
            <div class="be-admin-row"><span class="be-admin-label">Dashboard</span><span class="be-admin-value"><a href="' . $baseUrl . '/app/accounts/' . $aid . '/dashboard" target="_blank" style="color:#6366f1;">Open →</a></span></div>
        </div>';

        $html .= '<div class="be-admin-section">
            <h4>📊 Live Stats</h4>
            <div class="be-admin-stats">
                <div class="be-admin-stat"><div class="be-admin-stat-num">' . ($agentCount   !== null ? $agentCount   : '—') . '</div><div class="be-admin-stat-lbl">Agents</div></div>
                <div class="be-admin-stat"><div class="be-admin-stat-num">' . ($inboxCount   !== null ? $inboxCount   : '—') . '</div><div class="be-admin-stat-lbl">Inboxes</div></div>
                <div class="be-admin-stat"><div class="be-admin-stat-num">' . (isset($convMeta['all_count']) ? $convMeta['all_count'] : '—') . '</div><div class="be-admin-stat-lbl">Conversations</div></div>
                <div class="be-admin-stat"><div class="be-admin-stat-num">' . ($contactCount !== null ? $contactCount : '—') . '</div><div class="be-admin-stat-lbl">Contacts</div></div>
            </div>
        </div>';
    } else {
        $html .= '<div class="be-admin-alert warn">⚠️ No Chatwoot account is currently linked to this service.</div>';
    }

    // ── Account assignment form ───────────────────────────────────────────────
    $currentLinkedId = $record ? (int) $record->chatwoot_account_id : 0;
    $savedUserId     = $record ? (int) $record->chatwoot_user_id    : 0;

    $html .= '<div class="be-picker-box">
        <h4>🔧 Assign / Change Chatwoot Account</h4>
        <p>Enter the Chatwoot Account ID to link it to this service. The user list will load automatically. Select a user for SSO &amp; stats, then click <strong>Save Changes</strong>.</p>';

    // Account ID input
    $html .= '<label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">Chatwoot Account ID</label>';
    $html .= '<input type="number" class="be-inp" id="be_account_id_input" placeholder="e.g. 3" min="1" value="' . ($currentLinkedId ?: '') . '" oninput="beOnAccountIdInput(this)">';

    // User dropdown (populated by JS from embedded data or AJAX-like approach)
    $html .= '<label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:5px;">Select User (for SSO &amp; stats)</label>';
    $html .= '<select class="be-sel" id="be_user_picker" onchange="beOnUserChange(this)">';

    if (!empty($linkedAccountUsers)) {
        $html .= '<option value="">— select a user —</option>';
        foreach ($linkedAccountUsers as $u) {
            $sel   = ((int)$u['id'] === $savedUserId) ? ' selected' : '';
            $label = htmlspecialchars('[#' . $u['id'] . '] ' . $u['name'] . ' <' . $u['email'] . '> (' . $u['role'] . ')');
            $html .= '<option value="' . (int)$u['id'] . '" data-email="' . htmlspecialchars($u['email'], ENT_QUOTES) . '" data-token="' . htmlspecialchars($u['access_token'], ENT_QUOTES) . '"' . $sel . '>' . $label . '</option>';
        }
    } else {
        $html .= '<option value="">— enter an Account ID above first —</option>';
    }
    $html .= '</select>';

    // Preview box (always visible when data is set)
    $previewStyle = ($currentLinkedId && $savedUserId) ? '' : 'display:none;';
    $prevEmail    = htmlspecialchars($record->chatwoot_email ?? '', ENT_QUOTES);
    $prevToken    = $record->chatwoot_user_token ?? '';
    $prevTokenShort = $prevToken ? substr($prevToken, 0, 24) . '…' : '—';

    $html .= '<div class="be-preview" id="be_preview" style="' . $previewStyle . '">
        <div style="font-size:11px;font-weight:700;color:#5b21b6;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Will be saved</div>
        <div class="be-preview-row"><span class="be-preview-label">Account ID</span><span class="be-preview-value" id="be_prev_aid">' . ($currentLinkedId ? '#' . $currentLinkedId : '—') . '</span></div>
        <div class="be-preview-row"><span class="be-preview-label">User ID</span><span class="be-preview-value" id="be_prev_uid">' . ($savedUserId ? '#' . $savedUserId : '—') . '</span></div>
        <div class="be-preview-row"><span class="be-preview-label">User Email</span><span class="be-preview-value" id="be_prev_email">' . $prevEmail . '</span></div>
        <div class="be-preview-row"><span class="be-preview-label">User Token</span><span class="be-preview-value" id="be_prev_token">' . htmlspecialchars($prevTokenShort) . '</span></div>
    </div>';

    // Hidden inputs submitted with WHMCS Save Changes
    $html .= '<input type="hidden" name="be_assign_account_id" id="be_assign_account_id" value="' . ($currentLinkedId ?: '') . '">';
    $html .= '<input type="hidden" name="be_assign_user_id"    id="be_assign_user_id"    value="' . ($savedUserId ?: '') . '">';
    $html .= '<input type="hidden" name="be_assign_user_token" id="be_assign_user_token" value="' . htmlspecialchars($record->chatwoot_user_token ?? '', ENT_QUOTES) . '">';
    $html .= '<input type="hidden" name="be_assign_email"      id="be_assign_email"      value="' . htmlspecialchars($record->chatwoot_email ?? '', ENT_QUOTES) . '">';

    $html .= '<p style="margin-top:8px;font-size:11px;color:#9ca3af;">
        💡 To find an Account ID: open Chatwoot → go to the account → the ID is in the URL: <code>/app/accounts/<strong>{ID}</strong>/dashboard</code>.<br>
        After changing the Account ID, click <strong>Save Changes</strong> — the user list will refresh on next page load.
    </p>';

    $html .= "<script>
function beOnAccountIdInput(inp) {
    var aid = parseInt(inp.value) || 0;
    document.getElementById('be_assign_account_id').value = aid || '';
    document.getElementById('be_prev_aid').textContent    = aid ? '#' + aid : '—';
    // Clear user selection when account changes
    document.getElementById('be_assign_user_id').value    = '';
    document.getElementById('be_assign_user_token').value = '';
    document.getElementById('be_assign_email').value      = '';
    document.getElementById('be_prev_uid').textContent    = '—';
    document.getElementById('be_prev_email').textContent  = '—';
    document.getElementById('be_prev_token').textContent  = '—';
    document.getElementById('be_preview').style.display   = aid ? 'block' : 'none';
}

function beOnUserChange(sel) {
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    document.getElementById('be_assign_user_id').value    = opt.value;
    document.getElementById('be_assign_email').value      = opt.dataset.email || '';
    document.getElementById('be_assign_user_token').value = opt.dataset.token || '';
    document.getElementById('be_prev_uid').textContent    = '#' + opt.value;
    document.getElementById('be_prev_email').textContent  = opt.dataset.email || '—';
    var tok = opt.dataset.token || '';
    document.getElementById('be_prev_token').textContent  = tok ? tok.substring(0, 24) + '…' : '—';
    document.getElementById('be_preview').style.display   = 'block';
}

// Auto-trigger user change on load if a user is already selected
(function() {
    var userSel = document.getElementById('be_user_picker');
    if (userSel && userSel.value) { beOnUserChange(userSel); }
})();
</script>";

    $html .= '</div></div>'; // .be-picker-box .be-admin-wrap

    return ['Broodle Engage Account' => $html];
}

function broodleengage_AdminServicesTabFieldsSave(array $params): void
{
    broodleengage_ensureTable();

    $serviceId     = (int) $params['serviceid'];
    $accountId     = (int) ($_POST['be_assign_account_id']  ?? 0);
    $userId        = (int) ($_POST['be_assign_user_id']     ?? 0);
    $userToken     = trim($_POST['be_assign_user_token']    ?? '');
    $email         = trim($_POST['be_assign_email']         ?? '');

    if ($accountId <= 0) return;

    // If no user was selected but we have an account ID, try to auto-fetch the first admin user
    if (!$userId || !$userToken) {
        try {
            $baseUrl       = broodleengage_getApiBase($params);
            $platformToken = $params['serveraccesshash'];
            $auList = broodleengage_apiRequest('GET', "/platform/api/v1/accounts/{$accountId}/account_users", [], $platformToken, $baseUrl);
            foreach (($auList['payload'] ?? $auList) as $au) {
                $uid = (int) ($au['user_id'] ?? $au['id'] ?? 0);
                if (!$uid) continue;
                $ud = broodleengage_apiRequest('GET', "/platform/api/v1/users/{$uid}", [], $platformToken, $baseUrl);
                if (!empty($ud['access_token'])) {
                    $userId    = $uid;
                    $userToken = $ud['access_token'];
                    $email     = $email ?: ($ud['email'] ?? '');
                    break;
                }
            }
        } catch (Exception $e) {
            logModuleCall('broodleengage', 'AdminServicesTabFieldsSave_autoUser', [], [], $e->getMessage(), []);
        }
    }

    $now    = date('Y-m-d H:i:s');
    $exists = Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->exists();

    $data = [
        'chatwoot_account_id' => $accountId,
        'chatwoot_user_id'    => $userId    ?: null,
        'chatwoot_user_token' => $userToken ?: null,
        'chatwoot_email'      => $email     ?: null,
        'chatwoot_status'     => 'active',
        'updated_at'          => $now,
    ];

    if ($exists) {
        Capsule::table('mod_broodleengage')->where('service_id', $serviceId)->update($data);
    } else {
        Capsule::table('mod_broodleengage')->insert(array_merge($data, [
            'service_id'     => $serviceId,
            'provisioned_at' => $now,
            'created_at'     => $now,
        ]));
    }

    if ($email) {
        Capsule::table('tblhosting')->where('id', $serviceId)->update(['username' => $email]);
    }

    logModuleCall('broodleengage', 'AdminServicesTabFieldsSave',
        ['serviceid' => $serviceId, 'account_id' => $accountId, 'user_id' => $userId], [], 'Account assigned', []);
}
