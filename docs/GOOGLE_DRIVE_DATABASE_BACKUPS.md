# Google Drive database backups (personal My Drive)

The admin Backup Management page retains **Download Database Backup** and adds a Google user OAuth connection for private backups in a personal My Drive. The same runner serves the **Backup Now to Google Drive** button and the nightly `backup:database-to-drive` command. It creates a MySQL dump, compresses it, optionally encrypts it, uploads and verifies it, removes the temporary file, and prunes old app-created backups.

## Google Cloud setup

1. Create or select a Google Cloud project and enable the **Google Drive API**.
2. Configure the OAuth consent screen for an **External** user. Add your Google account as a test user while setting up. Before relying on unattended backups, set the OAuth app publishing status to **In production**: Google says refresh tokens for external apps in **Testing** normally expire after seven days. A personal-use app with fewer than 100 users may be exempt from verification but can show an unverified-app warning. See [Google OAuth token guidance](https://developers.google.com/identity/protocols/oauth2) and [personal-use verification guidance](https://support.google.com/cloud/answer/13464323).
3. Create an **OAuth 2.0 Client ID** with application type **Web application**. Add this exact authorized redirect URI: `https://bilaighor.bd/admin/backups/google/callback`.
4. Copy the Client ID and Client Secret into the production `.env`. Do not commit them or paste them into support messages.

The app requests only `https://www.googleapis.com/auth/drive.file` and offline access. This scope can manage files and folders created by the app, but **a folder you created manually in My Drive is not accessible to the app from its ID alone**. Leave `GOOGLE_DRIVE_BACKUP_FOLDER_ID` empty on first connection. The app creates its own private **Bilai Ghor Backups** folder in your My Drive and stores its ID in the connection record. Only OAuth tokens are encrypted in that record. If you previously configured a manually created folder ID, remove that value before connecting. The existing manual folder is not deleted. Google's [Drive scope guide](https://developers.google.com/workspace/drive/api/guides/api-specific-auth) explains this limit. No Google Workspace or service-account JSON is required.

## Environment settings

Set these on the production server and run `php artisan optimize:clear` after changing them:

```dotenv
GOOGLE_DRIVE_BACKUP_ENABLED=true
GOOGLE_DRIVE_CLIENT_ID=YOUR_OAUTH_WEB_CLIENT_ID
GOOGLE_DRIVE_CLIENT_SECRET=YOUR_OAUTH_WEB_CLIENT_SECRET
GOOGLE_DRIVE_REDIRECT_URI=https://bilaighor.bd/admin/backups/google/callback
GOOGLE_DRIVE_BACKUP_FOLDER_ID=
BACKUP_RETENTION_DAYS=14
BACKUP_DAILY_TIME=02:00
BACKUP_TIMEZONE=Asia/Dhaka
BACKUP_ENCRYPTION_ENABLED=false
BACKUP_ENCRYPTION_KEY=
```

`GOOGLE_DRIVE_BACKUP_CREDENTIALS` and the old service-account JSON are no longer used. OAuth tokens are **not** stored in `.env`. The `google_drive_backup_connections` table stores access and refresh tokens with Laravel encrypted casts, using the server's `APP_KEY`. Keep `APP_KEY` stable and protect database backups; losing it prevents decrypting the stored tokens.

`MYSQLDUMP_PATH` remains optional if the dump binary is on PATH. If backup encryption is enabled, use `BACKUP_ENCRYPTION_KEY=base64:` followed by the base64 encoding of 32 random bytes. Keep a separate offline copy of the encryption key. Losing it makes encrypted backups unrecoverable. The encryption format is libsodium authenticated XChaCha20-Poly1305 secretstream; the `.sql.gz.enc` file begins with `BILAI-GHOR-DB-BACKUP-1\n`, the secretstream header, and length-prefixed ciphertext chunks. No dashboard restore is provided.

## Deploy and connect

1. Deploy the code, then run `php artisan migrate --force` and `php artisan optimize:clear`.
2. Open `/admin/backups` while signed in as a high-level backup-authorized admin and click **Connect Google Drive**.
3. Choose your personal Google account and approve access. The callback checks an admin-bound session state before exchanging the authorization code. It stores encrypted tokens and creates/verifies the private My Drive folder.
4. Click **Test Google Drive Connection**. The test refreshes the token if necessary, verifies the folder, creates a disposable test file, verifies its parent, and removes it.
5. Click **Backup Now to Google Drive**. Verify the file exists in the app-created My Drive folder, check its size and success record in `/admin/backups`, and confirm the new local temporary file was removed.
6. Run `php artisan backup:database-to-drive` from the server once to verify scheduled-style operation without an interactive browser session.

The cPanel cron should run Laravel's scheduler once per minute. In cPanel's **Command** field, enter only the command after the five schedule fields:

```cron
* * * * * cd /home/bilaigho/public_html && php artisan schedule:run >> /dev/null 2>&1
```

If cPanel has separate Minute/Hour/Day/Month/Weekday inputs, set all five to `*` and enter `cd /home/bilaigho/public_html && php artisan schedule:run >> /dev/null 2>&1` in **Command**. If cron cannot find `php`, substitute the full executable path from `which php`. `php artisan schedule:list` should show the 02:00 Asia/Dhaka backup. Keep cPanel/provider backups as an additional layer.

## Retention, failure, and disconnection

Retention operates only within the connected app folder and deletes only files matching `bilaighor-db-YYYY-MM-DD-HH-mm-ss.sql.gz` (or `.enc`) with the `bilaighor_backup=database` app property and an age beyond `BACKUP_RETENTION_DAYS`. Metadata is rechecked before deletion. Unrelated files and folders are ignored.

An upload failure leaves the local private compressed or encrypted file for a short retry window and records a safe error in `backup_logs`. The hourly `backup:prune-local` task removes generated backup files older than 48 hours; temporary SQL and dump credential files are removed sooner. If Google reports `invalid_grant` when refreshing, the connection is marked **Reconnect required** and new backups stop until an admin reconnects. A retention failure is a warning on an otherwise verified successful upload.

**Disconnect Google Drive** removes the stored OAuth tokens and connection record. It keeps backup history and existing files in My Drive. It does not revoke the Google consent grant; you may also revoke it in your Google Account's security settings. No backup download or restore from Drive is included in this phase.
