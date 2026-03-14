<style>
.be-wrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',sans-serif;max-width:900px;margin:0 auto;padding-bottom:48px;color:#111827;}

/* Header */
.be-header{background:linear-gradient(135deg,#1e2433 0%,#111827 100%);border-radius:16px;padding:26px 30px;display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;box-shadow:0 8px 32px rgba(0,0,0,.22);}
.be-header-left{display:flex;align-items:center;gap:14px;}
.be-logo{width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 4px 14px rgba(99,102,241,.45);flex-shrink:0;}
.be-header-title h2{color:#fff;font-size:19px;font-weight:700;margin:0 0 2px;}
.be-header-title p{color:#9ca3af;font-size:12px;margin:0;}
.be-header-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;}

/* Badges */
.be-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:600;letter-spacing:.02em;}
.be-badge-dot{width:6px;height:6px;border-radius:50%;background:currentColor;}
.be-badge.active{background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3);}
.be-badge.suspended{background:rgba(245,158,11,.13);color:#f59e0b;border:1px solid rgba(245,158,11,.3);}
.be-badge.pending{background:rgba(99,102,241,.12);color:#6366f1;border:1px solid rgba(99,102,241,.3);}
.be-badge.terminated{background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.25);}
.be-badge.grey{background:rgba(107,114,128,.1);color:#6b7280;border:1px solid rgba(107,114,128,.2);}

/* Alert */
.be-alert{border-radius:10px;padding:13px 16px;display:flex;align-items:flex-start;gap:11px;margin-bottom:18px;font-size:13px;}
.be-alert.warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);color:#92400e;}
.be-alert.info{background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.25);color:#3730a3;}
.be-alert-icon{font-size:17px;flex-shrink:0;margin-top:1px;}
.be-alert strong{display:block;margin-bottom:2px;}

/* Two-col layout */
.be-cols{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}

/* Panels */
.be-panel{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px 22px;margin-bottom:16px;}
.be-panel-title{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.07em;margin:0 0 14px;padding-bottom:10px;border-bottom:1px solid #f3f4f6;}

/* Login box */
.be-login-box{background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #c4b5fd;border-radius:12px;padding:20px 22px;margin-bottom:16px;}
.be-login-box .be-panel-title{color:#5b21b6;border-bottom-color:#c4b5fd;}
.be-cred-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(196,181,253,.4);font-size:13px;}
.be-cred-row:last-of-type{border-bottom:none;padding-bottom:0;}
.be-cred-label{color:#6d28d9;font-weight:500;}
.be-cred-value{color:#1e1b4b;font-weight:600;font-family:'SF Mono',Monaco,Consolas,monospace;font-size:12px;display:flex;align-items:center;gap:8px;}
.be-copy-btn{background:rgba(99,102,241,.12);border:none;border-radius:5px;padding:3px 8px;font-size:11px;color:#6366f1;cursor:pointer;font-weight:600;transition:background .15s;}
.be-copy-btn:hover{background:rgba(99,102,241,.22);}

/* Stats grid */
.be-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px;}
.be-stat{background:#fff;border:1px solid #e5e7eb;border-radius:11px;padding:16px 14px;transition:box-shadow .18s;}
.be-stat:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);}
.be-stat-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;margin-bottom:9px;}
.be-stat-icon.v{background:rgba(99,102,241,.1);}
.be-stat-icon.g{background:rgba(16,185,129,.1);}
.be-stat-icon.b{background:rgba(59,130,246,.1);}
.be-stat-icon.o{background:rgba(245,158,11,.1);}
.be-stat-icon.r{background:rgba(239,68,68,.1);}
.be-stat-label{font-size:10px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;}
.be-stat-value{font-size:22px;font-weight:700;color:#111827;line-height:1;}
.be-stat-sub{font-size:10px;color:#9ca3af;margin-top:3px;}
.be-stat-value.na{font-size:14px;color:#9ca3af;}

/* Buttons */
.be-btn-row{display:flex;gap:9px;flex-wrap:wrap;}
.be-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 17px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer;border:none;transition:all .18s;white-space:nowrap;}
.be-btn:focus{outline:2px solid #6366f1;outline-offset:2px;}
.be-btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff!important;box-shadow:0 2px 10px rgba(99,102,241,.35);}
.be-btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(99,102,241,.45);text-decoration:none;}
.be-btn-secondary{background:#f3f4f6;color:#374151!important;border:1px solid #e5e7eb;}
.be-btn-secondary:hover{background:#e5e7eb;color:#111827!important;text-decoration:none;}
.be-btn-outline{background:transparent;color:#6366f1!important;border:1.5px solid #6366f1;}
.be-btn-outline:hover{background:rgba(99,102,241,.06);text-decoration:none;}
.be-btn-disabled{background:#f3f4f6;color:#9ca3af!important;border:1px solid #e5e7eb;cursor:not-allowed;opacity:.7;}

/* Info rows */
.be-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:13px;}
.be-row:last-child{border-bottom:none;padding-bottom:0;}
.be-row-label{color:#6b7280;}
.be-row-value{color:#111827;font-weight:500;text-align:right;}
.be-row-value a{color:#6366f1;text-decoration:none;}
.be-row-value a:hover{text-decoration:underline;}

/* Empty */
.be-empty{text-align:center;padding:52px 24px;background:#fff;border:1.5px dashed #d1d5db;border-radius:14px;}
.be-empty-icon{font-size:42px;margin-bottom:12px;}
.be-empty h3{color:#374151;font-size:16px;font-weight:600;margin:0 0 7px;}
.be-empty p{color:#6b7280;font-size:13px;line-height:1.6;margin:0;}

/* Responsive */
@media(max-width:680px){
  .be-stats{grid-template-columns:repeat(2,1fr);}
  .be-cols{grid-template-columns:1fr;}
  .be-header{flex-direction:column;gap:12px;align-items:flex-start;}
  .be-header-right{align-items:flex-start;}
}
@media(max-width:420px){.be-stats{grid-template-columns:1fr;}}
</style>

<div class="be-wrap">

{* ── Header ── *}
<div class="be-header">
  <div class="be-header-left">
    <div class="be-logo">💬</div>
    <div class="be-header-title">
      <h2>Broodle Engage</h2>
      <p>Omni-channel customer support platform &middot; <a href="{$baseUrl}" target="_blank" rel="noopener" style="color:#6366f1;">{$baseUrl}</a></p>
    </div>
  </div>
  <div class="be-header-right">
    {if $chatwootStatus == 'active'}
      <span class="be-badge active"><span class="be-badge-dot"></span> Account Active</span>
    {elseif $chatwootStatus == 'suspended'}
      <span class="be-badge suspended"><span class="be-badge-dot"></span> Account Suspended</span>
    {elseif $chatwootStatus == 'terminated'}
      <span class="be-badge terminated"><span class="be-badge-dot"></span> Terminated</span>
    {else}
      <span class="be-badge pending"><span class="be-badge-dot"></span> Pending Setup</span>
    {/if}
    {if $whmcsStatus}
      <span class="be-badge grey">Service: {$whmcsStatus}</span>
    {/if}
  </div>
</div>

{* ── Suspended alert ── *}
{if $chatwootStatus == 'suspended'}
<div class="be-alert warn">
  <span class="be-alert-icon">⚠️</span>
  <div><strong>Account Suspended</strong>Your Broodle Engage account is suspended. Login and messaging are disabled. Please contact support or settle any outstanding invoices to reactivate.</div>
</div>
{/if}

{if $isProvisioned}

  {* ── One-click login + credentials ── *}
  <div class="be-login-box">
    <div class="be-panel-title">🔐 Login Credentials &amp; Access</div>

    <div class="be-cred-row">
      <span class="be-cred-label">Email / Username</span>
      <span class="be-cred-value">
        {$email}
        <button class="be-copy-btn" onclick="navigator.clipboard.writeText(this.dataset.v);this.textContent='✓';setTimeout(()=>this.textContent='Copy',1500)" data-v="{$email|escape:'html'}">Copy</button>
      </span>
    </div>

    {if $servicePassword}
    <div class="be-cred-row">
      <span class="be-cred-label">Password</span>
      <span class="be-cred-value">
        <span id="be-pw-text" style="filter:blur(4px);transition:filter .2s;cursor:pointer;" onclick="document.getElementById('be-pw-text').style.filter='none'">{$servicePassword}</span>
        <button class="be-copy-btn" onclick="navigator.clipboard.writeText(this.dataset.v);this.textContent='✓';setTimeout(()=>this.textContent='Copy',1500)" data-v="{$servicePasswordHtml}">Copy</button>
        <button class="be-copy-btn" onclick="var el=document.getElementById('be-pw-text');el.style.filter=el.style.filter?'':'blur(4px)'">Show</button>
      </span>
    </div>
    {/if}

    <div class="be-cred-row">
      <span class="be-cred-label">Login URL</span>
      <span class="be-cred-value">
        <a href="{$baseUrl}/auth/sign_in" target="_blank" rel="noopener" style="color:#6d28d9;">{$baseUrl}/auth/sign_in</a>
      </span>
    </div>

    <div class="be-cred-row">
      <span class="be-cred-label">Account ID</span>
      <span class="be-cred-value">#{$accountId}</span>
    </div>

    <div style="margin-top:16px;">
      <div class="be-btn-row">
        {if $chatwootStatus == 'active'}
          <a href="{$ssoUrl}" class="be-btn be-btn-primary">🚀 One-Click Auto Login</a>
          <a href="{$dashboardUrl}" target="_blank" rel="noopener" class="be-btn be-btn-secondary">🔗 Open Dashboard</a>
          <a href="{$baseUrl}/auth/sign_in" target="_blank" rel="noopener" class="be-btn be-btn-outline">🔑 Manual Sign In</a>
        {else}
          <span class="be-btn be-btn-disabled">🚀 Auto Login (Account Suspended)</span>
          <a href="{$baseUrl}/auth/sign_in" target="_blank" rel="noopener" class="be-btn be-btn-outline">🔑 Sign In Page</a>
        {/if}
      </div>
    </div>
  </div>

  {* ── Live stats from Chatwoot API ── *}
  <div class="be-stats">
    <div class="be-stat">
      <div class="be-stat-icon g">👥</div>
      <div class="be-stat-label">Agents</div>
      {if $liveAgentCount !== null}
        <div class="be-stat-value">{$liveAgentCount}</div>
        <div class="be-stat-sub">of {$maxAgents} max</div>
      {else}
        <div class="be-stat-value na">—</div>
        <div class="be-stat-sub">{$maxAgents} max</div>
      {/if}
    </div>
    <div class="be-stat">
      <div class="be-stat-icon b">📥</div>
      <div class="be-stat-label">Inboxes</div>
      {if $liveInboxCount !== null}
        <div class="be-stat-value">{$liveInboxCount}</div>
        <div class="be-stat-sub">of {$maxInboxes} max</div>
      {else}
        <div class="be-stat-value na">—</div>
        <div class="be-stat-sub">{$maxInboxes} max</div>
      {/if}
    </div>
    <div class="be-stat">
      <div class="be-stat-icon v">💬</div>
      <div class="be-stat-label">Conversations</div>
      {if $liveConversations !== null}
        <div class="be-stat-value">{$liveConversations}</div>
        <div class="be-stat-sub">{$liveOpenConversations} open</div>
      {else}
        <div class="be-stat-value na">—</div>
        <div class="be-stat-sub">total</div>
      {/if}
    </div>
    <div class="be-stat">
      <div class="be-stat-icon o">👤</div>
      <div class="be-stat-label">Contacts</div>
      {if $liveContacts !== null}
        <div class="be-stat-value">{$liveContacts}</div>
        <div class="be-stat-sub">in CRM</div>
      {else}
        <div class="be-stat-value na">—</div>
        <div class="be-stat-sub">in CRM</div>
      {/if}
    </div>
  </div>

  {* ── Two-col: Plan info + Account details ── *}
  <div class="be-cols">
    <div class="be-panel">
      <div class="be-panel-title">📦 Plan Details</div>
      <div class="be-row">
        <span class="be-row-label">Plan</span>
        <span class="be-row-value">{$planName}</span>
      </div>
      <div class="be-row">
        <span class="be-row-label">Max Agents</span>
        <span class="be-row-value">{$maxAgents} seats</span>
      </div>
      <div class="be-row">
        <span class="be-row-label">Max Inboxes</span>
        <span class="be-row-value">{$maxInboxes} channels</span>
      </div>
      <div class="be-row">
        <span class="be-row-label">Provisioned</span>
        <span class="be-row-value">{if $provisionedAt}{$provisionedAt}{else}—{/if}</span>
      </div>
    </div>

    <div class="be-panel">
      <div class="be-panel-title">ℹ️ Account Info</div>
      <div class="be-row">
        <span class="be-row-label">Account ID</span>
        <span class="be-row-value">#{$accountId}</span>
      </div>
      <div class="be-row">
        <span class="be-row-label">Status</span>
        <span class="be-row-value">
          {if $chatwootStatus == 'active'}
            <span class="be-badge active" style="font-size:10px;"><span class="be-badge-dot"></span> Active</span>
          {elseif $chatwootStatus == 'suspended'}
            <span class="be-badge suspended" style="font-size:10px;"><span class="be-badge-dot"></span> Suspended</span>
          {else}
            <span class="be-badge grey" style="font-size:10px;">{$chatwootStatus}</span>
          {/if}
        </span>
      </div>
      <div class="be-row">
        <span class="be-row-label">Dashboard</span>
        <span class="be-row-value"><a href="{$dashboardUrl}" target="_blank" rel="noopener">Open →</a></span>
      </div>
      <div class="be-row">
        <span class="be-row-label">Platform</span>
        <span class="be-row-value"><a href="{$baseUrl}" target="_blank" rel="noopener">engage.broodle.one</a></span>
      </div>
    </div>
  </div>

  {* ── Getting started tips (active only) ── *}
  {if $chatwootStatus == 'active'}
  <div class="be-panel" style="background:#f9fafb;">
    <div class="be-panel-title">🚀 Getting Started</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:9px;padding:14px;text-align:center;">
        <div style="font-size:22px;margin-bottom:6px;">📬</div>
        <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:3px;">Connect Inboxes</div>
        <div style="font-size:11px;color:#6b7280;">Email, live chat, WhatsApp &amp; more</div>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:9px;padding:14px;text-align:center;">
        <div style="font-size:22px;margin-bottom:6px;">👤</div>
        <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:3px;">Invite Agents</div>
        <div style="font-size:11px;color:#6b7280;">Add your support team members</div>
      </div>
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:9px;padding:14px;text-align:center;">
        <div style="font-size:22px;margin-bottom:6px;">⚙️</div>
        <div style="font-size:12px;font-weight:600;color:#374151;margin-bottom:3px;">Configure</div>
        <div style="font-size:11px;color:#6b7280;">Automations &amp; canned responses</div>
      </div>
    </div>
  </div>
  {/if}

{else}

  {* ── Not provisioned ── *}
  <div class="be-alert info">
    <span class="be-alert-icon">ℹ️</span>
    <div><strong>Account Being Set Up</strong>Your Broodle Engage workspace is being provisioned. This usually completes within a few seconds. Refresh this page or contact support if it takes longer than a minute.</div>
  </div>
  <div class="be-empty">
    <div class="be-empty-icon">⏳</div>
    <h3>Workspace Not Yet Provisioned</h3>
    <p>Your Broodle Engage account hasn't been created yet.<br>If you just purchased this service, please wait a moment and refresh.<br>Contact support if this persists.</p>
  </div>

{/if}

</div>
