# Administrator Guide

This guide provides comprehensive instructions for System Administrators responsible for deploying, configuring, and maintaining the FreeScout application.

## 🚀 Deployment

### Option 1: Docker (Recommended)
The easiest way to deploy FreeScout is using our One-Click Docker script.

1.  **Run the deployment script:**
    ```bash
    ./docker_deploy.sh
    ```
2.  **Follow the prompts** to select the repository branch.
3.  **Access the app** at `http://<your-server-ip>`.

### Option 2: Manual Installation
For custom servers (Ubuntu/Debian recommended):

1.  **Clone the repo**: `git clone https://github.com/Scotchmcdonald/freescout.git`
2.  **Install dependencies**:
    - PHP 8.2+ (with extensions: bcmath, curl, gd, intl, mbstring, mysql, xml, zip)
    - Composer
    - Node.js & NPM
    - MariaDB 10.6+
    - Redis
3.  **Configure Environment**:
    - Copy `.env.example` to `.env`
    - Update database credentials (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
    - Set application URL (`APP_URL`)
4.  **Initialize App**:
    ```bash
    composer install
    npm install && npm run build
    php artisan key:generate
    php artisan migrate --seed
    ```
5.  **Configure Web Server**: Point Nginx/Apache to the `public/` directory.

## ⚙️ Initial Setup

Once the application is running, log in with the default admin credentials (printed during seeding or provided by your installer).

### 1. Mail Configuration
Navigate to **Manage > System > Mail Settings**.
- **Sending Mode**: Configure SMTP or Sendmail.
- **Incoming Email**: Ensure the cron job is running (`php artisan schedule:run`) to process incoming mail.

### 2. Branding
Navigate to **Manage > System > General**.
- Upload your organization's logo.
- Set the support portal name.

### 3. Security
- **Two-Factor Auth**: Enable 2FA module if available.
- **Backups**: Configure automated database backups.

## 📦 Module Management

FreeScout's functionality can be extended using Modules.

### Installing a Module
1.  Navigate to **Manage > Modules**.
2.  You will see a list of available modules.
3.  **Official Modules**: Click "Install" (requires license key if paid).
4.  **Custom Modules**:
    - Click "Install from Git".
    - Enter the Git Repository URL (HTTPS or SSH).
    - If needed, provide a Personal Access Token (PAT) or SSH Key.
    - Click "Install". The system will clone the repo and install dependencies.

### Managing Active Modules
- **Enable/Disable**: Toggle the switch next to the module name.
- **Update**: If a new version is available, an "Update" button will appear.
- **Remove**: Click the trash icon to uninstall. *Warning: This removes module data.*

## 👥 User Management

### Roles
- **System Admin**: Full access to all settings.
- **Finance**: Access to Invoices, billing templates, and reports.
- **Agent**: Handling tickets and managing assigned mailboxes.

### Adding Users
1.  Go to **Manage > Users**.
2.  Click **New User**.
3.  Fill in the profile and assign a Role.
4.  (Optional) Assign to specific Mailboxes.

## 🔧 Troubleshooting

### Logs
- Application logs are located at `storage/logs/laravel.log`.
- View them via the UI at **Manage > Logs** (if enabled) or via SSH.

### Queue Status
- Check the status of background jobs (email sending, module installation).
- Run `php artisan queue:work` manually if the worker process is down.
