# Mini Wallet Application

This is a high-performance, simplified digital wallet application built with Laravel and Vue.js. It allows users to transfer funds to each other in real-time, with a focus on scalability, data integrity, and code quality, designed to simulate a high-traffic financial system.

## Features

-   **User Authentication**: Secure login and registration powered by Laravel Breeze.
-   **Fund Transfers**: Users can send money to each other using their email addresses.
-   **Real-Time Updates**: Balances and transaction histories are updated instantly across all logged-in devices using Laravel Echo and Pusher, without needing a page refresh.
-   **Commission System**: A 1.5% commission is automatically charged to the sender on every successful transfer.
-   **Scalable Balance Management**: User balances are stored directly on the `users` table, ensuring fast lookups even with millions of transactions.
-   **High Concurrency & Data Integrity**: The transfer process uses pessimistic locking and database transactions to prevent race conditions and ensure that all operations are atomic.
-   **Detailed Transaction History**: A paginated list of all incoming and outgoing transactions for the authenticated user.
-   **Robust Validation**: Comprehensive backend validation to prevent invalid operations like self-transfers, insufficient funds, or sending to non-existent users.
-   **Modern Frontend**: A clean user interface built with Vue.js (Composition API), TypeScript, and Tailwind CSS.
-   **Internationalization (i18n)**: Frontend text is managed via `vue-i18n` for easy translation.

## Technology Stack

-   **Backend**: Laravel 12
-   **Frontend**: Vue.js 3 (with Composition API), Inertia.js, TypeScript
-   **Database**: MySQL or PostgreSQL
-   **Real-time Broadcasting**: Pusher
-   **Queue Management**: Laravel Queues (Database Driver)
-   **Styling**: Tailwind CSS
-   **Icons**: Heroicons
-   **Testing**:
    -   Backend: Pest
    -   Frontend: Vitest, Vue Test Utils
-   **Code Quality**:
    -   Backend: Laravel Pint (PSR-12)
    -   Frontend: ESLint, Prettier

## Prerequisites

Before you begin, ensure you have the following installed on your local machine:
-   PHP >= 8.2
-   Composer
-   Node.js & npm
-   A database server (MySQL or PostgreSQL)

## Setup and Installation

Follow these steps to get the project up and running on your local machine.

**1. Clone the Repository**
```bash
git clone <your-repository-url>
cd wallet
```

**2. Install Dependencies**
Install both PHP and Node.js dependencies.
```bash
composer install
npm install
```

**3. Configure Environment**
Copy the example environment file and generate your application key.
```bash
cp .env.example .env
php artisan key:generate
```

**4. Update `.env` File**
Open the `.env` file and configure the following variables:

-   **Database Connection:**
    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=wallet
    DB_USERNAME=root
    DB_PASSWORD=
    ```

-   **Broadcasting with Pusher:**
    Add your Pusher application credentials.
    ```dotenv
    BROADCAST_CONNECTION=pusher
    PUSHER_APP_ID=your_pusher_app_id
    PUSHER_APP_KEY=your_pusher_app_key
    PUSHER_SECRET=your_pusher_secret
    PUSHER_APP_CLUSTER=your_pusher_cluster
    ```

-   **Queue & Session Drivers:**
    Ensure these are set to use the database for reliability.
    ```dotenv
    QUEUE_CONNECTION=database
    SESSION_DRIVER=database
    ```

**5. Run Database Migrations and Seed**
This will create all necessary tables and populate the database with a central commission account and 100 sample users.
```bash
php artisan migrate:fresh --seed
```
> **Note:** The default password for all seeded users is `password`.

**6. Build Frontend Assets**
Compile the frontend assets for production.
```bash
npm run build
```

## Running the Application

To run the application, you need to start the web server, the Vite development server, and the queue worker.

**Recommended Method (Concurrent)**

A convenient script is included in `composer.json` to run all necessary services at once.
```bash
composer run dev
```
This command will start:
-   The Laravel development server (`php artisan serve`)
-   The queue worker (`php artisan queue:listen`)
-   The Laravel Pail log viewer (`php artisan pail`)
-   The Vite development server (`npm run dev`)

**Manual Method**

Alternatively, you can run each service in a separate terminal window:
```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Vite Server
npm run dev

# Terminal 3: Queue Worker (MANDATORY for real-time events)
php artisan queue:work
```

Once the servers are running, you can access the application at `http://127.0.0.1:8000`.

## Running Tests

The application has a comprehensive test suite for both the backend and frontend.

**Backend Tests (Pest)**
```bash
composer test
```

**Frontend Unit & Feature Tests (Vitest)**
```bash
npm run test:unit
```

## Code Quality Tools

To maintain code consistency, you can use the built-in formatting and linting tools.

**Backend Formatting (Laravel Pint)**
```bash
./vendor/bin/pint
```

**Frontend Formatting (Prettier)**
```bash
npm run format
```

**Frontend Linting (ESLint)**
```bash
npm run lint
```