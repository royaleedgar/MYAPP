# Omni News Platform

Omni is a modern news aggregation platform that provides personalized news feeds, article summaries using AI, and bookmark functionality. Built with PHP and styled with Tailwind CSS, it offers a clean, responsive interface for reading and managing news from various sources.

## Features

- 🔐 User Authentication System
- 📰 Personalized News Feed
- 🎨 Dark/Light Theme Support
- 🔖 Article Bookmarking
- 🤖 AI-Powered Article Summaries using Google Gemini
- 📱 Responsive Design
- 🎯 Category-based News Filtering
- 🔍 Advanced Search Functionality
- ⚡ Real-time Updates

## Live Demo
[View Live Demo](https://github.com/royaleedgar/MYAPP.git)

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- API Keys:
  - [News API](https://newsapi.org/) account
  - [Google Gemini API](https://makersuite.google.com/app/apikey) key

## Quick Start

1. Clone the repository:
   ```bash
   git clone https://github.com/royaleedgar/MYAPP.git
   cd MYAPP
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Configure environment variables in `.env`:
   - Add your database credentials
   - Add your News API key
   - Add your Gemini API key

4. Import the database schema:
   - Open phpMyAdmin in your browser (usually at http://localhost/phpmyadmin)
   - Login with your MySQL credentials
   - Click "New" in the left sidebar to create a new database
   - Enter "omni_db" as the database name and click "Create"
   - Select the "omni_db" database from the left sidebar
   - Click the "Import" tab at the top
   - Click "Choose File" and select the database/schema.sql file from the project
   - Make sure "SQL" format is selected
   - Click "Go" at the bottom to import the schema

5. Configure your web server:
   - Point the document root to the project's root directory
   - Ensure PHP has write permissions for cache/logs directories

6. Start the server:
   - Start XAMPP Control Panel
   - Start Apache and MySQL services
   - Place project files in `C:/xampp/htdocs/MYAPP`

7. Visit `http://localhost/MYAPP` in your browser

## API Integration

### News API
- Used for fetching news articles
- Supports multiple categories
- Search functionality
- Rate limiting handled

### Google Gemini API
- AI-powered article summarization
- Natural language processing
- Concise content generation

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Troubleshooting

### Common Issues
1. Database Connection Errors
   - Check your database credentials in `.env`
   - Ensure MySQL service is running

2. API Key Issues
   - Verify API keys in `.env`
   - Check API usage limits

3. Permission Issues
   - Ensure proper file permissions
   - Check web server configuration

## Security

- SQL injection prevention using prepared statements
- XSS protection through output escaping
- CSRF protection
- Secure password hashing
- Environment variable protection

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact & Support

- GitHub: [@royaleedgar](https://github.com/royaleedgar)
- Project Link: [https://github.com/royaleedgar/MYAPP](https://github.com/royaleedgar/MYAPP)

## Acknowledgments

- [NewsAPI](https://newsapi.org/) for news data
- [Google Gemini](https://deepmind.google/technologies/gemini/) for AI summarization
- [Tailwind CSS](https://tailwindcss.com/) for styling
- [Material Icons](https://fonts.google.com/icons) for icons
