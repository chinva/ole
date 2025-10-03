# Online Examination System

A comprehensive, feature-rich online examination platform built with PHP and MySQL, featuring AI-powered exam generation, secure payment processing, and responsive design.

## 🚀 Features

### Super Admin Features
- **Secure Login**: Email/password authentication with bcrypt encryption
- **Dashboard**: Real-time statistics with charts and graphs
- **Exam Management**: Create, edit, delete, and organize exams
- **AI Exam Generation**: Generate exams automatically using ChatGPT API
- **User Management**: Import students via CSV or add manually
- **Coupon System**: Create discount codes with usage limits
- **Payment Tracking**: Monitor all transactions and revenue
- **Settings Management**: Configure SMTP, payment gateways, and system settings

### Student Features
- **Registration/Login**: Secure student account creation
- **Exam Purchase**: Buy exams using Razorpay or PayU
- **Online Testing**: Timer-based exams with navigation
- **Results**: Instant results with detailed performance analysis
- **Profile Management**: Update personal information and password
- **Coupon Support**: Apply discount codes during checkout

### Technical Features
- **Responsive Design**: Works on all devices
- **Security**: Prepared statements, input validation, CSRF protection
- **Performance**: Optimized database queries with caching
- **Scalability**: Modular architecture for easy expansion
- **Documentation**: Comprehensive setup and usage guide

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, Chart.js, Font Awesome
- **Payments**: Razorpay, PayU
- **AI**: OpenAI ChatGPT API
- **Email**: SMTP integration
- **Security**: bcrypt, CSRF tokens, input validation

## 📦 Installation

### Method 1: Using the Installation Wizard (Recommended)

1. **Upload Files**
   - Upload all files to your web server
   - Ensure the web server has write permissions to:
     - `/config/` directory
     - `/uploads/` directory and subdirectories
     - `/install/` directory (temporary)

2. **Run Installation**
   - Navigate to: `http://yourdomain.com/install/`
   - Follow the step-by-step wizard:
     - Check system requirements
     - Configure database connection
     - Create admin account (default: admin@viniverse.com / Admin@123)

3. **Post-Installation**
   - The installer will automatically secure the installation
   - Delete the `/install/` directory after completion

### Method 2: Manual Installation

1. **Database Setup**
   - Create a MySQL database
   - Import the schema from `database/schema.sql`
   - Update database credentials in `config/config.php`

2. **Configuration**
   - Copy `config/.env.example` to `config/.env`
   - Add your API keys:
     - OpenAI API key for AI exam generation
     - Razorpay keys for payments
     - PayU keys for payments
     - SMTP credentials for email

3. **Admin Account**
   - Manually create admin account using the provided SQL:
   ```sql
   INSERT INTO users (email, password, full_name, role, is_active, email_verified) 
   VALUES ('admin@viniverse.com', '$2y$12$[hashed_password]', 'Super Administrator', 'admin', 1, 1);
   ```

## 🔧 Configuration

### Environment Variables (.env)
```bash
# Database
DB_HOST=localhost
DB_NAME=online_exam_system
DB_USER=your_username
DB_PASS=your_password

# Application
APP_URL=http://yourdomain.com
APP_ENV=production

# API Keys
OPENAI_API_KEY=your_openai_api_key
RAZORPAY_KEY_ID=your_razorpay_key
RAZORPAY_KEY_SECRET=your_razorpay_secret
PAYU_MERCHANT_KEY=your_payu_key
PAYU_MERCHANT_SALT=your_payu_salt

# SMTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
```

### Payment Gateway Setup

#### Razorpay
1. Create account at [razorpay.com](https://razorpay.com)
2. Get API keys from dashboard
3. Add to configuration

#### PayU
1. Create merchant account
2. Get merchant key and salt
3. Configure webhook URLs

## 📁 Directory Structure

```
online-exam-system/
├── admin/                    # Admin panel
│   ├── index.php            # Admin dashboard
│   ├── login.php            # Admin login
│   ├── generate-exam.php    # AI exam generator
│   ├── categories.php       # Category management
│   ├── exams.php           # Exam management
│   ├── students.php        # Student management
│   ├── coupons.php         # Coupon management
│   ├── payments.php        # Payment tracking
│   └── settings.php        # System settings
├── student/                 # Student panel
│   ├── index.php           # Student dashboard
│   ├── available-exams.php # Browse exams
│   ├── my-exams.php        # Purchased exams
│   ├── start-exam.php      # Exam interface
│   ├── results.php         # View results
│   └── profile.php         # Profile management
├── install/                # Installation wizard
├── api/                    # API endpoints
│   ├── OpenAIHelper.php    # AI integration
│   └── PaymentGateway.php  # Payment processing
├── config/                 # Configuration files
├── includes/               # Core classes
├── uploads/                # File uploads
│   ├── exams/
│   ├── profiles/
│   └── payments/
├── database/              # Database files
└── assets/               # Static assets
    ├── css/
    ├── js/
    └── images/
```

## 🎯 Usage

### For Admins
1. **Login**: Navigate to `/admin/` and use admin credentials
2. **Create Exam**: Use the AI generator or create manually
3. **Manage Students**: Import via CSV or add individually
4. **Monitor Payments**: Track all transactions and revenue
5. **Configure Settings**: Update payment gateways and SMTP

### For Students
1. **Register**: Create account at `/login.php`
2. **Browse Exams**: View available exams with prices
3. **Purchase**: Use Razorpay/PayU with coupon support
4. **Take Exam**: Timer-based testing with navigation
5. **View Results**: Instant feedback and detailed analysis

## 🔐 Security Features

- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Output escaping and sanitization
- **CSRF Protection**: Token-based form validation
- **Password Security**: bcrypt hashing with configurable cost
- **File Upload Security**: Type and size validation
- **Session Security**: Secure session handling
- **Input Validation**: Server-side validation for all inputs

## 📊 Performance Optimization

- **Database Indexing**: Optimized queries with proper indexes
- **Caching**: Result caching for frequently accessed data
- **Image Optimization**: Automatic image resizing
- **CDN Integration**: Bootstrap and Font Awesome via CDN
- **Minified Assets**: Compressed CSS and JavaScript

## 📞 Support

For support and customization:
- **Documentation**: Check `/docs/` folder
- **Issues**: Report on GitHub
- **Customization**: Contact development team

## 📝 License

This project is licensed under the MIT License. See LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 🔄 Updates

To update the system:
1. Backup database and files
2. Replace files with new version
3. Run any database migrations
4. Clear cache and test functionality

## 📞 Contact

For business inquiries and support:
- Email: support@viniverse.com
- Website: https://viniverse.com
- Phone: +91-XXXXXXXXXX

---

**Built with ❤️ by the Online Exam System Team**