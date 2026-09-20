# Galorem AI

An AI-powered web-based study assistant for undergraduate students, built around adaptive assessment-based learning. Students upload lecture notes, slides, or handwritten materials, and the system uses OCR and a large language model to generate quizzes, theory questions, and flashcards from that content.

Developed as a final year project for the Bachelor of Science in Information Technology program at the University of Energy and Natural Resources (UENR), Sunyani, Ghana.

## Overview

Most students juggle multiple disconnected tools to study: messaging apps to find shared notes, one AI tool to summarize them, another to generate practice questions, and no easy way to track progress across any of it. Galorem AI brings all of that into a single platform.

Students can upload their own course materials and get:
- Automatically generated quizzes and flashcards based on that content
- A chatbot that answers questions using the uploaded materials
- Performance tracking and analytics on past quiz attempts
- Rule-based difficulty adjustment based on quiz scores

Lecturers get a dashboard to upload course materials, approve requests, and manage students.

## Features

- **User Management**: Student and lecturer/admin roles with session-based authentication and hashed passwords
- **Document Processing**: Upload and convert documents and handwritten images via OCR
- **Assessments**: AI-generated quizzes and flashcards, with difficulty adjusted by rule-based logic tied to quiz scores
- **AI Chatbot**: Chat interface backed by the Mistral AI API
- **Performance Analytics**: Score tracking and performance breakdowns per student
- **Notifications**: In-app notification system
- **Lecturer Dashboard**: Course/resource management, lecturer requests and approvals, student progress monitoring
- **Document Export**: Generate Word documents (via PHPWord) and PDFs (via mPDF)

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap |
| Backend | PHP |
| Database | MySQL |
| AI/LLM | Mistral AI API |
| PHP Dependencies | `mpdf/mpdf`, `phpoffice/phpword`, `google/apiclient` |
| Dev Tools | XAMPP, Git |

## Project Structure
```Test3/
├── AI/                          # Quiz, analytics, flashcards, chatbot, lecturer features
│   └── Additional/              # Document export, image converter, fonts
├── uploads/                     # User-uploaded files
├── vendor/                      # Composer dependencies
├── logos/
├── config.php                   # Database and API key configuration
├── login.php                    # Student login
├── signup.php                   # Student registration
├── admin_login.php              # Admin login
├── admin_approve.php            # Admin approval actions
├── index.html                   # Landing page
├── dashboard.html                # Student dashboard
└── composer.json                # PHP dependencies


## Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, MySQL, Apache)
- A Mistral AI API key ([console.mistral.ai](https://console.mistral.ai))
- Composer (optional; `vendor/` is already included, but recommended for a clean setup)

### Installation

1. Clone the repository into your XAMPP `htdocs` folder:
```bash
   cd C:/xampp/htdocs
   git clone https://github.com/callyjoe/Test3.git
```

2. Start **Apache** and **MySQL** from the XAMPP control panel.

3. Create a database named `galorem_auth_new` via phpMyAdmin (`http://localhost/phpmyadmin`), then import the schema.
   > A `.sql` export of the schema should be added to this repo for others to set up the database. If one isn't present yet, export it with:
   > ```bash
   > mysqldump -u root -p galorem_auth_new > galorem_auth_new.sql
   > ```

4. Open `config.php` and set your own values:
```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "galorem_auth_new";
   define('MISTRAL_API_KEY', 'your-api-key-here');
```

5. (Optional) Install PHP dependencies fresh:
```bash
   composer install
```

6. Visit the app in your browser:


## Usage

- **Students**: Register, upload course materials, generate quizzes or flashcards, chat with the AI assistant, and view performance analytics from the dashboard.
- **Lecturers**: Log in via the lecturer portal to upload resources, manage courses, and monitor student progress. New lecturer accounts require admin approval.
- **Admins**: Approve lecturer requests and manage the platform via the admin panel.

## Security Notes

- Passwords are hashed using PHP's `password_hash()`
- Role-based access control restricts admin/lecturer pages to authorized users
- `config.php` contains sensitive credentials (DB and API key) and should not be committed with real values in a public repository; use environment variables or a gitignored config file for production use

## Team

**Group 23D, UENR**
- Chaluatera Calvin Akiweley
- Bawuah Andy Michael
- Joyce Nsiah Atogmbune
- Asiak Angela Anaamlie

**Supervisor**: Mr. Philip Sarfo-Manu

## License

Academic project, not currently licensed for reuse.
