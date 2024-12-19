# WhatsApp Appointment Booking Chatbot

A Laravel-based WhatsApp chatbot that helps users book appointments through a conversational interface.

## Features

- WhatsApp message handling through Meta's WhatsApp Business API
- Appointment booking flow
- User information collection
- Session management for conversation state
- Automated responses

## Requirements

- PHP 8.1+
- MySQL
- Composer
- WhatsApp Business API access
- ngrok (for local development)

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and configure:
   ```bash
   cp .env.example .env
   ```
4. Set up your database and WhatsApp credentials in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   WHATSAPP_TOKEN=your_whatsapp_token
   PHONE_NUMBER_ID=your_phone_number_id
   VERIFY_TOKEN=your_verify_token
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Start the server:
   ```bash
   php artisan serve
   ```

## Usage

1. Start ngrok to expose your local server:
   ```bash
   ngrok http 8000
   ```
2. Configure the webhook URL in your Meta WhatsApp Business account:
   - Webhook URL: `https://your-ngrok-url/webhook`
   - Verify Token: Same as `VERIFY_TOKEN` in your `.env`

## Directory Structure

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── ChatbotController.php
│   └── Models/
│       ├── Appointment.php
│       ├── ChatSession.php
│       └── User.php
├── routes/
│   └── webhook.php
└── .env
