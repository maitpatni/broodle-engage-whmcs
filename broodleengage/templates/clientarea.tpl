<style>
.be-wrap {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 860px;
    margin: 0 auto;
    color: #1e293b;
}

/* ── Header bar ── */
.be-header {
    background: #0A5ED3;
    border-radius: 12px;
    padding: 22px 26px;
    color: #fff;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}
.be-header-info h2 {
    margin: 0 0 3px;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -.01em;
}
.be-header-info p {
    margin: 0;
    font-size: 12px;
    opacity: .8;
}
.be-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Buttons ── */
.be-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none !important;
    cursor: pointer;
    border: none;
    transition: filter .15s, opacity .15s;
    white-space: nowrap;
}
.be-btn:hover { filter: brightness(1.08); }
.be-btn-white  { background: #fff; color: #0A5ED3; }
.be-btn-ghost  { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.35); }
.be-btn-blue   { background: #0A5ED3; color: #fff; }

/* ── Stats grid ── */
.be-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 18px;
}
@media (max-width: 580px) { .be-stats { grid-template-columns: repeat(2, 1fr); } }
.be-stat {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 12px;
    text-align: center;
}
.be-stat-num {
    font-size: 26px;
    font-weight: 700;
    color: #0A5ED3;
    line-height: 1;
}
.be-stat-lbl {
    font-size: 10px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-top: 5px;
}

/* ── Cards ── */
.be-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 14px;
}
.be-card-title {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin: 0 0 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.be-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid #f8fafc;
    font-size: 13px;
    gap: 16px;
}
.be-row:last-child { border-bottom: none; padding-bottom: 0; }
.be-row-label { color: #64748b; flex-shrink: 0; }
.be-row-value { color: #1e293b; font-weight: 500; text-align: right; word-break: break-all; display: flex; align-items: center; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }

/* ── Badge ── */
.be-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.be-badge.active    { background: #dbeafe; color: #1d4ed8; }
.be-badge.suspended { background: #fef9c3; color: #854d0e; }
.be-badge.pending   { background: #f1f5f9; color: #475569; }
.be-badge.cancelled { background: #fee2e2; color: #991b1b; }

/* ── Password field ── */
.be-pw-group {
    display: flex;
    align-items: center;
    gap: 6px;
}
.be-pw-input {
    font-family: monospace;
    font-size: 13px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    padding: 6px 10px;
    color: #1e293b;
    width: 180px;
    outline: none;
    letter-spacing: .05em;
}
.be-pw-input:focus { border-color: #0A5ED3; box-shadow: 0 0 0 2px rgba(10,94,211,.12); }
.be-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    background: #fff;
    cursor: pointer;
    color: #64748b;
    transition: background .15s, border-color .15s;
    flex-shrink: 0;
}
.be-icon-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.be-icon-btn svg { width: 15px; height: 15px; }

/* ── Copy button ── */
.be-copy {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    background: #fff;
    cursor: pointer;
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
    transition: background .15s;
    white-space: nowrap;
}
.be-copy:hover { background: #f1f5f9; }
.be-copy.copied { color: #16a34a; border-color: #86efac; background: #f0fdf4; }

/* ── Alerts ── */
.be-alert {
    padding: 13px 16px;
    border-radius: 9px;
    font-size: 13px;
    margin-bottom: 14px;
    line-height: 1.55;
}
.be-alert.info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.be-alert.warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.be-alert.error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
</style>

<div class="be-wrap">

{if $isProvisioned}

    {* ── Header ── *}
    <div class="be-header">
        <div class="be-header-info">
            <h2>Broodle Engage</h2>
            <p>Account #{$accountId}&nbsp;&nbsp;·&nbsp;&nbsp;{$email}</p>
        </div>
        <div class="be-header-actions">
            <a href="{$ssoUrl}" target="_blank" rel="noopener" class="be-btn be-btn-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                One-Click Login
            </a>
            <a href="{$dashboardUrl}" target="_blank" rel="noopener" class="be-btn be-btn-ghost">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
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
        <div class="be-card-title">Account Details</div>

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

    {* ── Credentials ── *}
    <div class="be-card">
        <div class="be-card-title">Login Credentials</div>

        <div class="be-row">
            <span class="be-row-label">Login URL</span>
            <span class="be-row-value">
                <a href="{$baseUrl}/auth/sign_in" target="_blank" style="color:#0A5ED3;">{$baseUrl}/auth/sign_in</a>
            </span>
        </div>

        <div class="be-row">
            <span class="be-row-label">Email / Username</span>
            <span class="be-row-value">
                <span>{$email}</span>
                <button class="be-copy" onclick="beCopy('{$email|escape:'javascript'}', this)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    Copy
                </button>
            </span>
        </div>

        {if $servicePassword}
        <div class="be-row">
            <span class="be-row-label">Password</span>
            <span class="be-row-value">
                <div class="be-pw-group">
                    <input type="password" class="be-pw-input" id="be-pw" value="{$servicePasswordHtml}" readonly autocomplete="off">
                    <button class="be-icon-btn" id="be-pw-toggle" onclick="beTogglePw()" title="Show / hide password" type="button">
                        <svg id="be-eye-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg id="be-eye-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                    <button class="be-copy" id="be-pw-copy" onclick="beCopyPw(this)" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copy
                    </button>
                </div>
            </span>
        </div>
        {/if}
    </div>

{else}

    <div class="be-alert info">
        ⏳ Your Broodle Engage account is being set up. Please refresh the page or check back shortly.
    </div>

    {if $whmcsStatus == 'Pending'}
    <div class="be-alert warning">
        Your service is <strong>Pending</strong>. Once activated, your account will be created automatically.
    </div>
    {/if}

    {if $whmcsStatus == 'Suspended'}
    <div class="be-alert error">
        Your service is <strong>Suspended</strong>. Please contact support to reactivate.
    </div>
    {/if}

{/if}

</div>

<script>
// Password stored as JSON-encoded string — safe for all special characters
var beRawPw = {$servicePasswordJson};

// Set the password input value via JS to avoid HTML attribute encoding issues
(function() {
    var inp = document.getElementById('be-pw');
    if (inp && beRawPw) inp.value = beRawPw;
})();

function beTogglePw() {
    var inp  = document.getElementById('be-pw');
    var show = document.getElementById('be-eye-show');
    var hide = document.getElementById('be-eye-hide');
    if (!inp) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        show.style.display = 'none';
        hide.style.display = '';
    } else {
        inp.type = 'password';
        show.style.display = '';
        hide.style.display = 'none';
    }
}

function beCopyPw(btn) {
    beCopy(beRawPw, btn);
}

function beCopy(text, btn) {
    if (!text) return;
    var done = function() {
        var orig = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied';
        setTimeout(function() { btn.classList.remove('copied'); btn.innerHTML = orig; }, 1800);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function() { beCopyFallback(text, done); });
    } else {
        beCopyFallback(text, done);
    }
}

function beCopyFallback(text, cb) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); } catch(e) {}
    document.body.removeChild(ta);
    if (cb) cb();
}
</script>
