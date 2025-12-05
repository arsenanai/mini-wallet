Mini Wallet Application
=======================

This is a high-performance, simplified digital wallet application built with Laravel and Vue.js. It allows users to transfer funds to each other in real-time, with a focus on scalability, data integrity, and code quality, designed to simulate a high-traffic financial system.

Features
--------

*   **User Authentication**: Secure login and registration powered by Laravel Breeze.
*   **Fund Transfers**: Users can send money to each other using their email addresses.
*   **Real-Time Updates**: Balances and transaction histories are updated instantly across all logged-in devices using Laravel Echo and Pusher, without needing a page refresh.
*   **Commission System**: A 1.5% commission is automatically charged to the sender on every successful transfer.
*   **Scalable Balance Management**: User balances are stored directly on the `users` table, ensuring fast lookups even with millions of transactions.
*   **High Concurrency & Data Integrity**: The transfer process uses pessimistic locking and database transactions to prevent race conditions and ensure that all operations are atomic.
*   **Detailed Transaction History**: A paginated list of all incoming and outgoing transactions for the authenticated user.
*   **Robust Validation**: Comprehensive backend validation to prevent invalid operations like self-transfers, insufficient funds, or sending to non-existent users.
*   **Modern Frontend**: A clean user interface built with Vue.js (Composition API), TypeScript, and Tailwind CSS.
*   **Internationalization (i18n)**: Frontend text is managed via `vue-i18n` for easy translation.

Technology Stack
----------------

*   **Backend**: Laravel 12
*   **Frontend**: Vue.js 3 (with Composition API), Inertia.js, TypeScript
*   **Database**: MySQL or PostgreSQL
*   **Real-time Broadcasting**: Pusher
*   **Queue Management**: Laravel Queues (Database Driver)
*   **Styling**: Tailwind CSS
*   **Icons**: Heroicons
*   **Testing**:
    *   Backend: Pest
    *   Frontend: Vitest, Vue Test Utils
*   **Code Quality**:
    *   Backend: Laravel Pint (PSR-12)
    *   Frontend: ESLint, Prettier

Prerequisites
-------------

Before you begin, ensure you have the following installed on your local machine:

*   PHP >= 8.2
*   Composer
*   Node.js & npm
*   A database server (MySQL or PostgreSQL)

Setup and Installation
----------------------

### 1\. Clone the Repository

    git clone <your-repository-url>
    cd wallet
    

### 2\. Install Dependencies

    composer install
    npm install
    

### 3\. Configure Environment

    cp .env.example .env
    php artisan key:generate
    

### 4\. Update `.env` File

Configure database, broadcasting, queue, and session drivers. For Dusk testing:

#### Dusk Testing Environment (Optional)

1.  Create a Dusk environment file:
    
        cp .env.dusk.local.example .env.dusk.local
    
2.  Install a browser driver:
    *   **Google Chrome (Recommended)**:
        
            php artisan dusk:chrome-driver --detect
        
        This downloads the correct ChromeDriver binary into `vendor/laravel/dusk/bin`.
        
        > **macOS Users:** If you see a `Failed to connect to localhost port 9515` error, start ChromeDriver manually in a separate terminal:
        > 
        >     vendor/laravel/dusk/bin/chromedriver-mac --port=9515
        > 
        > Keep this process running while you execute:
        > 
        >     php artisan dusk
        > 
        > If macOS blocks the binary, approve it with:
        > 
        >     xattr -d com.apple.quarantine ./vendor/laravel/dusk/bin/chromedriver-mac
        
    *   **Safari (macOS only)**: Enable remote automation in Safari preferences, then run this in separate terminal to start driver session:
        
            safaridriver -p 4444

        then run this to start tests:

            php artisan dusk
        

### 5\. Run Database Migrations and Seed

    php artisan migrate:fresh --seed
    

### 6\. Build Frontend Assets

    npm run build
    

Running the Application
-----------------------

Use `composer run dev` to start Laravel, queue worker, log viewer, and Vite server concurrently. Or run each manually in separate terminals.

Running Tests
-------------

*   **Backend Tests (Pest)**:
    
        composer test
    
*   **Frontend Unit & Feature Tests (Vitest)**:
    
        npm run test:unit
    
*   **Browser Tests (Laravel Dusk)**:
    
        php artisan dusk
    
    Ensure ChromeDriver is running (see instructions above).
    

Code Quality Tools
------------------

*   **Backend Formatting (Laravel Pint)**:
    
        ./vendor/bin/pint
    
*   **Frontend Formatting (Prettier)**:
    
        npm run format
    
*   **Frontend Linting (ESLint)**:
    
        npm run lint