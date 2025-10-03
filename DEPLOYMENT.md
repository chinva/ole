# Online Examination System - Deployment Guide

## 🚀 Quick Start

### 1. Upload Files
Upload the entire `online-exam-system/` folder to your web server root.

### 2. Set Permissions
```bash
chmod 755 config/
chmod 755 uploads/
chmod 755 uploads/exams/
chmod 755 uploads/profiles/
chmod 755 uploads/payments/
```

### 3. Run Installation
Navigate to: `http://yourdomain.com/install/`

### 4. Complete Setup
Follow the 3-step wizard:
1. Check system requirements
2. Configure database
3. Create admin account

### 5. Post-Installation
1. Delete `/install/` folder for security
2. Update API keys in `config/.env`
3. Configure payment gateways
4. Test the system

## 📋 System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx
- **Extensions**: PDO, PDO_MySQL, mbstring, openssl, curl, gd, fileinfo, zip

## 🔧 Configuration Files

### Database Configuration
File: `config/config.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_exam_system');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Environment Variables
File: `config/.env`
```bash
# Required API Keys
OPENAI_API_KEY=your-openai-key
RAZORPAY_KEY_ID=your-razorpay-key
RAZORPAY_KEY_SECRET=your-razorpay-secret
PAYU_MERCHANT_KEY=your-payu-key
PAYU_MERCHANT_SALT=your-payu-salt
```

## 🏗️ Database Setup

### Manual Installation
If you skip the installer, run:
```sql
CREATE DATABASE online_exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then import:
```bash
mysql -u username -p online_exam_system < database/schema.sql
```

## 🔐 Security Checklist

- [ ] Delete `/install/` folder after setup
- [ ] Change default admin password
- [ ] Set secure file permissions
- [ ] Configure HTTPS (recommended)
- [ ] Update `.htaccess` for production
- [ ] Enable error logging
- [ ] Set up regular backups

## 💳 Payment Gateway Setup

### Razorpay
1. Sign up at razorpay.com
2. Get API keys from dashboard
3. Add to `config/.env`:
   ```
   RAZORPAY_KEY_ID=your_key_id
   RAZORPAY_KEY_SECRET=your_key_secret
   ```

### PayU
1. Create merchant account
2. Get merchant key and salt
3. Add to `config/.env`:
   ```
   PAYU_MERCHANT_KEY=your_merchant_key
   PAYU_MERCHANT_SALT=your_merchant_salt
   ```

## 📧 Email Configuration

### Gmail SMTP
```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
```

## 🎯 Testing Checklist

### Admin Features
- [ ] Login with admin@viniverse.com / Admin@123
- [ ] Create exam categories
- [ ] Generate AI exams
- [ ] Manage students
- [ ] Create coupons
- [ ] View payments

### Student Features
- [ ] Register new account
- [ ] Browse available exams
- [ ] Purchase exam with coupon
- [ ] Take exam with timer
- [ ] View results
- [ ] Update profile

### Payment Testing
- [ ] Razorpay test payments
- [ ] PayU test payments
- [ ] Coupon application
- [ ] Refund process

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials
   - Verify MySQL is running
   - Check user permissions

2. **Permission Denied**
   - Set correct file permissions
   - Check Apache/Nginx configuration

3. **API Errors**
   - Verify API keys are correct
   - Check API limits
   - Review error logs

4. **Blank Pages**
   - Enable PHP error display
   - Check error logs
   - Verify PHP extensions

### Error Logs
Check these locations:
- PHP: `/var/log/php_errors.log`
- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`

## 📞 Support

For technical support:
- Email: support@viniverse.com
- Documentation: See README.md
- Community: GitHub Issues

## 🔄 Updates

To update the system:
1. Backup database and files
2. Download latest version
3. Replace files (except config and uploads)
4. Run any database migrations
5. Test functionality

---

**Happy Learning! 🎓**