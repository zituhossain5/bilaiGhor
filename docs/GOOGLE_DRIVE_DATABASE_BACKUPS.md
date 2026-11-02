# Google Drive database backups

The admin **Backup Management** page keeps the existing database download and adds **Backup Now to Google Drive**. The same `backup:database-to-drive` workflow runs nightly at 02:00 Asia/Dhaka. It dumps the configured MySQL database, compresses it, optionally encrypts it, uploads it, verifies Drive metadata, deletes the local copy, and prunes old Drive backups.

## Required Google setup

1. Enable the Google Drive API in a Google Cloud project and create a service account with a JSON key.
2. Create a private backup folder **inside a Google Workspace Shared Drive**. Grant the service account a role that can upload and remove files in that folder (typically Content manager). Keep access limited to backup operators.
3. Copy the JSON key to a path outside `public_html`, for example `/home/bilaigho/.credentials/google-drive-backup.json`. Protect its directory with mode `0700` and the JSON file with `0600`. The application rejects credential files inside the project directory.
4. Copy the Shared Drive folder ID into the environment configuration. Do not use a public link or add public Drive permissions.

Google states that service accounts have no personal Drive storage quota and cannot own files. A folder merely shared from someone's **My Drive** may fail with a storage quota error; use a Shared Drive. See [Google's Shared Drive documentation](https://developers.google.com/workspace/drive/api/guides/about-shareddrives).

## Environment settings

Set these on the server's `.env`; do not commit real values:

```dotenv
GOOGLE_DRIVE_BACKUP_ENABLED=true
GOOGLE_DRIVE_BACKUP_FOLDER_ID=YOUR_PRIVATE_SHARED_DRIVE_FOLDER_ID
GOOGLE_DRIVE_BACKUP_CREDENTIALS=/home/bilaigho/.credentials/google-drive-backup.json
BACKUP_RETENTION_DAYS=14
BACKUP_DAILY_TIME=02:00
BACKUP_TIMEZONE=Asia/Dhaka
BACKUP_ENCRYPTION_ENABLED=false
BACKUP_ENCRYPTION_KEY=
```

`MYSQLDUMP_PATH` remains optional when the dump binary is on the server's PATH. If encryption is enabled, set `BACKUP_ENCRYPTION_KEY` to `base64:` followed by the base64 encoding of **32 random bytes**. Generate the key on a trusted machine, keep an offline copy, and never upload it with the backup. Losing the key makes encrypted backups unrecoverable. A backup contains database data, including any secrets stored in database settings; `.env`, uploads and application files are not included.

Encryption uses libsodium's authenticated XChaCha20-Poly1305 secretstream. The `.sql.gz.enc` file contains the ASCII magic `BILAI-GHOR-DB-BACKUP-1\n`, followed by the secretstream header, then repeated four-byte big-endian ciphertext lengths and ciphertext chunks. The final chunk carries the secretstream final tag. This describes the format for manual recovery tooling; there is no dashboard restore or import feature.

## cPanel schedule

After deployment, run `php artisan migrate --force` and `php artisan optimize:clear`. Check `which mysqldump` and `mysqldump --version`. Add this cPanel cron entry (the path matches this installation):

```cron
* * * * * cd /home/bilaigho/public_html && php artisan schedule:run >> /dev/null 2>&1
```

If cron does not find `php`, replace it with the absolute executable returned by `which php`. Check `php artisan schedule:list` for the 02:00 backup entry. For an initial live verification, run `php artisan backup:database-to-drive` manually, then confirm the file in the private Shared Drive folder, its size and ID in `/admin/backups`, and that the newly generated local temporary file was removed. Run a second backup to exercise retention. Keep cPanel/provider backups as a separate layer.

## Retention and failures

Drive retention deletes only files in the configured folder whose names match `bilaighor-db-YYYY-MM-DD-HH-mm-ss.sql.gz` (optionally `.enc`), whose app property is `bilaighor_backup=database`, and whose Drive creation time is older than the configured period. It rechecks metadata before deletion. Unrelated files are not touched.

On failed upload, the private temporary file remains available for a short retry window. `backup:prune-local` runs hourly and removes generated backup files older than 48 hours; temporary SQL and credential files are removed sooner. A retention error is recorded as a warning without changing the verified upload's success status. The admin page shows configuration status and audit history without exposing credential paths or tokens.
