<style>
.bilai-account-page {
    --account-primary: #e8861a;
    --account-secondary: #2a1505;
    --account-page: #f5f5f0;
    --account-warm: #fff;
    --account-cream: #fff8ec;
    --account-neutral: #f0e6d8;
    --account-border: #e8cda5;
    --account-border-default: #e8cda5;
    --account-text: #2b1a10;
    --account-muted: #77706a;
    min-height: 72vh;
    padding: 20px 0 52px;
    color: var(--account-text);
    background: var(--account-page);
}

.bilai-account-container {
    width: min(var(--bilai-container-max, 1440px), calc(100% - (var(--bilai-container-gutter, 24px) * 2)));
    max-width: var(--bilai-container-max, 1440px);
    margin: 0 auto;
}

.bilai-account-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
    margin: 0 0 18px;
    color: var(--account-muted);
    font-size: 12.5px;
    line-height: 20px;
}

.bilai-account-breadcrumb a { color: inherit; text-decoration: none; }
.bilai-account-breadcrumb a:hover,
.bilai-account-breadcrumb-current { color: var(--account-primary); }
.bilai-account-breadcrumb-separator { color: #c0b0a0; font-size: 11px; }

.bilai-account-layout {
    display: grid;
    grid-template-columns: 248px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
}

.bilai-account-sidebar {
    position: sticky;
    top: 90px;
}

.bilai-account-sidebar-card {
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--account-border);
    border-radius: 12px;
}

.bilai-account-profile { padding: 18px 16px 14px; }
.bilai-account-profile-head {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.bilai-account-avatar,
.bilai-account-avatar-placeholder {
    width: 56px;
    height: 56px;
    flex: 0 0 56px;
    border: 2px solid var(--account-border);
    border-radius: 50%;
}

.bilai-account-avatar { display: block; object-fit: cover; }
.bilai-account-avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: var(--account-primary);
    font-size: 22px;
    font-weight: 700;
    text-transform: uppercase;
}

.bilai-account-profile-copy { min-width: 0; flex: 1; }
.bilai-account-profile-name {
    overflow: hidden;
    margin: 0 0 2px;
    color: var(--account-text);
    font-size: 14px;
    font-weight: 700;
    line-height: 18px;
    text-overflow: ellipsis;
    text-transform: capitalize;
    white-space: nowrap;
}
.bilai-account-profile-username {
    overflow: hidden;
    margin: 0;
    color: var(--account-muted);
    font-size: 12px;
    line-height: 16px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bilai-account-menu { background: #fff; }
.bilai-account-menu-list { margin: 0; padding: 4px 0; }
.bilai-account-menu-link {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 38px;
    padding: 9px 16px;
    color: var(--account-text);
    border-left: 3px solid transparent;
    font-size: 13.5px;
    font-weight: 500;
    line-height: 18px;
    text-decoration: none;
}
.bilai-account-menu-link:hover,
.bilai-account-menu-link.active {
    color: var(--account-primary);
    background: var(--account-cream);
    text-decoration: none;
}
.bilai-account-menu-link.active {
    border-left-color: var(--account-primary);
    font-weight: 600;
}
.bilai-account-menu-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    flex: 0 0 16px;
    font-size: 13px;
    opacity: .7;
}
.bilai-account-menu-badge {
    margin-left: auto;
    padding: 1px 6px;
    color: #fff;
    background: #e53935;
    border-radius: 100px;
    font-size: 10px;
    line-height: 15px;
}
.bilai-account-logout {
    border-top: 1px solid var(--account-border);
}
.bilai-account-logout .bilai-account-menu-link { color: #c0392b; }
.bilai-account-logout .bilai-account-menu-link:hover { color: #a93226; background: #fff5f5; }

.bilai-account-main {
    min-width: 0;
    padding: 22px;
    background: #fff;
    border: 1px solid var(--account-border);
    border-radius: 12px;
}

@media (max-width: 1199px) {
    .bilai-account-layout { grid-template-columns: 228px minmax(0, 1fr); gap: 16px; }
}

@media (max-width: 991px) {
    .bilai-account-layout { grid-template-columns: 1fr; }
    .bilai-account-sidebar { position: static; }
}

@media (max-width: 575px) {
    .bilai-account-container { width: calc(100% - (var(--bilai-container-gutter, 16px) * 2)); }
    .bilai-account-main { padding: 16px 14px 18px; }
}
</style>
