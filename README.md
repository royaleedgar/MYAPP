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
