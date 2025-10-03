# 403 Forbidden Error - Troubleshooting Guide

## Common Causes and Solutions

### 1. File Permissions
**Issue**: Files or directories have incorrect permissions
**Solution**:
```bash
# Set correct permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Ensure web server can read
chown -R www-data:www-data /var/www/html/ole
chmod 755 config uploads uploads/*
```

### 2. Missing .htaccess
**Issue**: Apache requires .htaccess for PHP processing
**Solution**: Ensure `.htaccess` exists in root directory with:
```apache
# Basic PHP handling
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### 3. Apache Configuration
**Issue**: Apache not configured to serve PHP files
**Solution**:
```apache
# In Apache config or .htaccess
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
```

### 4. Directory Indexes
**Issue**: Directory listing disabled
**Solution**: Add to .htaccess:
```apache
DirectoryIndex index.php index.html
```

### 5. SELinux (Linux systems)
**Issue**: SELinux blocking access
**Solution**:
```bash
# Check SELinux status
getenforce

# Temporarily disable for testing
setenforce 0

# Permanently allow
setsebool -P httpd_can_network_connect 1
```

## Quick Fix Commands

```bash
# Fix all permissions at once
sudo chown -R www-data:www-data /var/www/html/ole
sudo find /var/www/html/ole -type f -exec chmod 644 {} \;
sudo find /var/www/html/ole -type d -exec chmod 755 {} \;

# Restart web server
sudo service apache2 restart
# OR
sudo service nginx restart
```

## Testing Steps

1. **Check file permissions**:
   ```bash
   ls -la /var/www/html/ole/
   ```

2. **Test PHP processing**:
   Create `test.php` with:
   ```php
   <?php phpinfo(); ?>
   ```

3. **Check Apache logs**:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

4. **Verify .htaccess**:
   Ensure it exists and has correct permissions (644)

## Web Server Specific Fixes

### Apache
```apache
# In sites-available/000-default.conf
<Directory /var/www/html/ole>
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx
```nginx
# In nginx config
location /ole {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Common Solutions by Platform

### Ubuntu/Apache
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### CentOS/Nginx
```bash
sudo setsebool -P httpd_can_network_connect 1
sudo systemctl restart nginx
```

### Windows/XAMPP
1. Check Apache error logs
2. Ensure mod_rewrite is enabled
3. Verify .htaccess syntax

## Debug Mode

To enable debug output, add to config.php:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contact Support
If issues persist:
- Check server error logs
- Verify PHP version compatibility
- Test with minimal PHP file
- Contact hosting provider