<?php
/**
 * Broodle Engage - WHMCS Hooks
 *
 * Auto-installs email templates and keeps local DB in sync
 * with WHMCS service lifecycle events.
 *
 * WHMCS 8+ auto-loads hooks.php from inside a server module folder.
 *
 * @author Broodle <https://broodle.host>
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

// Install email templates the first time a Broodle Engage service is created
add_hook('AfterModuleCreate', 1, function (array $vars) {
    if (($vars['moduletype'] ?? '') !== 'broodleengage') return;
    broodleengage_hooks_ensureEmailTemplates();
});

// Keep local DB status in sync after WHMCS lifecycle actions succeed
add_hook('AfterModuleSuspend', 1, function (array $vars) {
    if (($vars['moduletype'] ?? '') !== 'broodleengage') return;
    $sid = (int) ($vars['serviceid'] ?? 0);
    if ($sid) {
        Capsule::table('mod_broodleengage')->where('service_id', $sid)
            ->update(['chatwoot_status' => 'suspended', 'updated_at' => date('Y-m-d H:i:s')]);
    }
});

add_hook('AfterModuleUnsuspend', 1, function (array $vars) {
    if (($vars['moduletype'] ?? '') !== 'broodleengage') return;
    $sid = (int) ($vars['serviceid'] ?? 0);
    if ($sid) {
        Capsule::table('mod_broodleengage')->where('service_id', $sid)
            ->update(['chatwoot_status' => 'active', 'updated_at' => date('Y-m-d H:i:s')]);
        broodleengage_hooks_ensureEmailTemplates();
    }
});

add_hook('AfterModuleTerminate', 1, function (array $vars) {
    if (($vars['moduletype'] ?? '') !== 'broodleengage') return;
    $sid = (int) ($vars['serviceid'] ?? 0);
    if ($sid) {
        Capsule::table('mod_broodleengage')->where('service_id', $sid)->delete();
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// Email template installer
// ─────────────────────────────────────────────────────────────────────────────

function broodleengage_hooks_ensureEmailTemplates(): void
{
    $templates = [
        [
            'name'    => 'Broodle Engage Welcome Email',
            'subject' => 'Welcome to Broodle Engage — Your Account is Ready',
            'message' => broodleengage_hooks_welcomeBody(),
        ],
        [
            'name'    => 'Broodle Engage Password Reset',
            'subject' => 'Broodle Engage — Your Password Has Been Reset',
            'message' => broodleengage_hooks_passwordResetBody(),
        ],
    ];

    foreach ($templates as $tpl) {
        if (!Capsule::table('tblemailtemplates')->where('name', $tpl['name'])->exists()) {
            Capsule::table('tblemailtemplates')->insert([
                'type'      => 'product',
                'name'      => $tpl['name'],
                'subject'   => $tpl['subject'],
                'message'   => $tpl['message'],
                'fromname'  => '',
                'fromemail' => '',
                'disabled'  => 0,
                'custom'    => 1,
                'language'  => '',
            ]);
        }
    }
}

function broodleengage_hooks_welcomeBody(): string
{
    return <<<'HTML'
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
  <div style="background:linear-gradient(135deg,#1e2433,#111827);padding:40px 36px;text-align:center;">
    <div style="width:64px;height:64px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:16px;">💬</div>
    <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Welcome to Broodle Engage</h1>
    <p style="color:#9ca3af;margin:0;font-size:14px;">Your omni-channel support workspace is ready</p>
  </div>
  <div style="padding:36px;">
    <p style="color:#374151;font-size:15px;margin:0 0 24px;">Your <strong>{$service_name}</strong> account has been provisioned. Use the details below to sign in:</p>
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:20px 24px;margin-bottom:28px;">
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr><td style="color:#6b7280;padding:7px 0;border-bottom:1px solid #f3f4f6;">Platform URL</td><td style="text-align:right;font-weight:600;padding:7px 0;border-bottom:1px solid #f3f4f6;"><a href="{$login_url}" style="color:#6366f1;">{$login_url}</a></td></tr>
        <tr><td style="color:#6b7280;padding:7px 0;border-bottom:1px solid #f3f4f6;">Email</td><td style="text-align:right;font-weight:600;padding:7px 0;border-bottom:1px solid #f3f4f6;">{$login_email}</td></tr>
        <tr><td style="color:#6b7280;padding:7px 0;border-bottom:1px solid #f3f4f6;">Password</td><td style="text-align:right;font-weight:600;padding:7px 0;border-bottom:1px solid #f3f4f6;">{$login_password}</td></tr>
        <tr><td style="color:#6b7280;padding:7px 0;border-bottom:1px solid #f3f4f6;">Account ID</td><td style="text-align:right;font-weight:600;padding:7px 0;border-bottom:1px solid #f3f4f6;">#{$account_id}</td></tr>
        <tr><td style="color:#6b7280;padding:7px 0;">Plan</td><td style="text-align:right;font-weight:600;padding:7px 0;">{$plan_name}</td></tr>
      </table>
    </div>
    <div style="text-align:center;margin-bottom:28px;">
      <a href="{$dashboard_url}" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;padding:13px 30px;border-radius:8px;font-weight:600;font-size:14px;">Open Your Dashboard →</a>
    </div>
    <p style="color:#6b7280;font-size:13px;line-height:1.6;margin:0 0 8px;">You can also log in instantly from your client portal using the <strong>Auto Login</strong> button — no password needed.</p>
    <p style="color:#6b7280;font-size:13px;margin:0;">We recommend changing your password after your first login.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:28px 0 20px;">
    <p style="color:#9ca3af;font-size:12px;text-align:center;margin:0;">Broodle Engage &middot; <a href="https://broodle.host" style="color:#6366f1;">broodle.host</a></p>
  </div>
</div>
HTML;
}

function broodleengage_hooks_passwordResetBody(): string
{
    return <<<'HTML'
<div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
  <div style="background:linear-gradient(135deg,#1e2433,#111827);padding:40px 36px;text-align:center;">
    <div style="width:64px;height:64px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:16px;">🔑</div>
    <h1 style="color:#fff;font-size:22px;font-weight:700;margin:0 0 6px;">Password Reset</h1>
    <p style="color:#9ca3af;margin:0;font-size:14px;">Your Broodle Engage password has been updated</p>
  </div>
  <div style="padding:36px;">
    <p style="color:#374151;font-size:15px;margin:0 0 24px;">Your <strong>{$service_name}</strong> password has been reset. Use the credentials below to sign in:</p>
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:20px 24px;margin-bottom:28px;">
      <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr><td style="color:#6b7280;padding:7px 0;border-bottom:1px solid #f3f4f6;">Login URL</td><td style="text-align:right;font-weight:600;padding:7px 0;border-bottom:1px solid #f3f4f6;"><a href="{$login_url}" style="color:#6366f1;">{$login_url}</a></td></tr>
        <tr><td style="color:#6b7280;padding:7px 0;">New Password</td><td style="text-align:right;font-weight:600;padding:7px 0;">{$new_password}</td></tr>
      </table>
    </div>
    <div style="text-align:center;margin-bottom:28px;">
      <a href="{$login_url}" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;padding:13px 30px;border-radius:8px;font-weight:600;font-size:14px;">Sign In Now →</a>
    </div>
    <p style="color:#6b7280;font-size:13px;margin:0;">If you did not request this change, please contact support immediately.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:28px 0 20px;">
    <p style="color:#9ca3af;font-size:12px;text-align:center;margin:0;">Broodle Engage &middot; <a href="https://broodle.host" style="color:#6366f1;">broodle.host</a></p>
  </div>
</div>
HTML;
}
