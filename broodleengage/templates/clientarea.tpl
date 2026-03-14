<style>
.be-wrap {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 860px;
    margin: 0 auto;
    color: #1e293b;
}
.be-hero {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 14px;
    padding: 28px 30px;
    color: #fff;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}
.be-hero-left h2 {
    margin: 0 0 4px;
    font-size: 22px;
    font-weight: 700;
}
.be-hero-left p {
    margin: 0;
    font-size: 13px;
    opacity: .85;
}
.be-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .02em;
}
.be-badge.active   { background: #dcfce7; color: #166534; }
.be-badge.suspended{ background: #fef9c3; color: #854d0e; }
.be-badge.pending  { background: #f1f5f9; color: #475569; }
.be-badge.cancelled{ background: #fee2e2; color: #991b1b; }
.be-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: opacity .15s;
}
.be-btn:hover { opacity: .88; text-decoration: none; }
.be-btn-primary { background: #fff; color: #6366f1; }
.be-btn-outline { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.4); }
.be-btn-indigo  { background: #6366f1; color: #fff; }
.be-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.be-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
@media (max-width: 600px) { .be-stats { grid-template-columns: repeat(2, 1fr); } }
.be-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
}
.be-stat-num {
    font-size: 28px;
    font-weight: 700;
    color: #6366f1;
    line-height: 1;
}
.be-stat-lbl {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-top: 5px;
}
.be-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 22px;
    margin-bottom: 16px;
}
.be-card h4 {
    margin: 0 0 14px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.be-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f8fafc;
    font-size: 13px;
    gap: 12px;
}
.be-row:last-child { border-bottom: none; }
.be-row-label { color: #64748b; min-width: 120px; }
.be-row-value { color: #1e293b; font-weight: 500; text-align: right; word-break: break-all; }
.be-pw-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
}
.be-pw-field {
    font-family: monospace;
    font-size: 13px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 5px 10px;
    color: #1e293b;
    letter-spacing: .08em;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.be-pw-toggle {
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 5px 9px;
    cursor: pointer;
    color: #64748b;
    font-size: 13px;
    line-height: 1;
    transition: background .15s;
}
.be-pw-toggle:hover { background: #f1f5f9; }
.be-copy-btn {
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 5px 9px;
    cursor: pointer;
    color: #64748b;
    font-size: 11px;
    transition: background .15s;
}
.be-copy-btn:hover { background: #f1f5f9; }
.be-alert {
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 16px;
    line-height: 1.5;
}
.be-alert.info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.be-alert.warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.be-alert.error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
</style>

<div class="be-wrap">

{if $isProvisioned}

    {* ── Hero bar ── *}
    <div class="be-hero">
        <div class="be-hero-left">
            <h2>Broodle Engage</h2>
            <p>Account #{$accountId} &nbsp;·&nbsp; {$email}</p>
        </div>
        <div class="be-actions">
            <a href="{$ssoUrl}" class="be-btn be-btn-primary">
                ⚡ One-Click Login
            </a>
            <a href="{$dashboardUrl}" target="_blank" class="be-btn be-btn-outline">
                🔗 Open Dashboard
            </a>
        </div>
    </div>

    {* ── Stats ── *}
    <div class="be-stats">
        <div class="be-stat">
            <div class="be-stat-num">{if $liveAgentCount !== null}{$liveAgentCount}{else}—{/if}</div>
            <div class="be-stat-lbl">Agents</div>
        </div>
        <div class="be-stat">
            <div class="be-stat-num">{if $liveInboxCount !== null}{$liveInboxCount}{else}—{/if}</div>
            <div class="be-stat-lbl">Inboxes</div>
        </div>
        <div class="be-stat">
            <div class="be-stat-num">{if $liveConversations !== null}{$liveConversations}{else}—{/if}</div>
            <div class="be-stat-lbl">Conversations</div>
        </div>
        <div class="be-stat">
            <div class="be-stat-num">{if $liveContacts !== null}{$liveContacts}{else}—{/if}</div>
            <div class="be-stat-lbl">Contacts</div>
        </div>
    </div>

    {* ── Account details ── *}
    <div class="be-card">
        <h4>Account Details</h4>

        <div class="be-row">
            <span class="be-row-label">Status</span>
            <span class="be-row-value">
                <span class="be-badge {$chatwootStatus}">{$chatwootStatus|capitalize}</span>
            </span>
        </div>

        <div class="be-row">
            <span class="be-row-label">Plan</span>
            <span class="be-row-value">{$planName}</span>
        </div>

        <div class="be-row">
            <span class="be-row-label">Max Agents</span>
            <span class="be-row-value">{$maxAgents}</span>
        </div>

        <div class="be-row">
            <span class="be-row-label">Max Inboxes</span>
            <span class="be-row-value">{$maxInboxes}</span>
        </div>

        {if $provisionedAt}
        <div class="be-row">
            <span class="be-row-label">Active Since</span>
            <span class="be-row-value">{$provisionedAt}</span>
        </div>
        {/if}
    </div>

    {* ── Login credentials ── *}
    <div class="be-card">
        <h4>Login Credentials</h4>

        <div class="be-row">
            <span class="be-row-label">Login URL</span>
            <span class="be-row-value">
                <a href="{$baseUrl}/auth/sign_in" target="_blank" style="color:#6366f1;">{$baseUrl}/auth/sign_in</a>
            </span>
        </div>

        <div class="be-row">
            <span class="be-row-label">Email</span>
            <span class="be-row-value">
                {$email}
                <button class="be-copy-btn" onclick="beCopy('{$email|escape:'javascript'}', this)" title="Copy">Copy</button>
            </span>
        </div>

        {if $servicePassword}
        <div class="be-row">
            <span class="be-row-label">Password</span>
            <span class="be-row-value">
                <div class="be-pw-wrap">
                    <span class="be-pw-field" id="be-pw-display">••••••••••••</span>
                    <button class="be-pw-toggle" id="be-pw-btn" onclick="beTogglePw()" title="Show/hide password">👁</button>
                    <button class="be-copy-btn" onclick="beCopy('{$servicePasswordHtml|escape:'javascript'}', this)" title="Copy password">Copy</button>
                </div>
            </span>
        </div>
        {/if}
    </div>

{else}

    {* ── Not yet provisioned ── *}
    <div class="be-alert info">
        ⏳ Your Broodle Engage account is being set up. This usually takes just a moment. Please refresh the page or check back shortly.
    </div>

    {if $whmcsStatus == 'Pending'}
    <div class="be-alert warning">
        Your service is currently <strong>Pending</strong>. Once activated, your Chatwoot account will be created automatically.
    </div>
    {/if}

    {if $whmcsStatus == 'Suspended'}
    <div class="be-alert error">
        Your service is currently <strong>Suspended</strong>. Please contact support to reactivate.
    </div>
    {/if}

{/if}

</div>

<script>
var beRawPw = '{$servicePasswordHtml|escape:'javascript'}';
var beShowing = false;

function beTogglePw() {
    var display = document.getElementById('be-pw-display');
    var btn     = document.getElementById('be-pw-btn');
    if (!display) return;
    beShowing = !beShowing;
    display.textContent = beShowing ? beRawPw : '••••••••••••';
    btn.textContent = beShowing ? '🙈' : '👁';
}

function beCopy(text, btn) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function() { btn.textContent = orig; }, 1500);
    }).catch(function() {
        // fallback for older browsers
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        var orig = btn.textContent;
        btn.textContent = '✓';
        setTimeout(function() { btn.textContent = orig; }, 1500);
    });
}
</script>
