# LinguaLab

An interactive language training platform designed for fun and progress.

## Requirements

- PHP >= 8.2
- Composer
- MySQL

## Setup

Follow these steps to get the project up and running:

1. **Clone the repository:**
    ```bash
    git clone https://github.com/yourusername/lingualab.git
    cd lingualab
    ```

2. **If you do not see an `artisan` file, install the Laravel application skeleton first.**

    **Recommended Fix (Non-Destructive, Keeps Your Work):**
    1. Move all your custom files and code (such as `.env.example`, `README.md`, custom migrations, models, controllers, and views) to a safe temporary location outside the project directory.
    2. Delete everything inside your project directory so it is empty:
        ```bash
        rm -rf *
        ```
    3. Run the official Laravel installer:
        ```bash
        composer create-project laravel/laravel .
        ```
    4. Copy your custom files from your backup to the appropriate locations in the fresh Laravel project (for example, put migrations in `database/migrations`, controllers in `app/Http/Controllers`, models in `app/Models`, views in `resources/views`, and your `.env.example` in the root).
    5. Run `composer install` if needed, then continue with the steps below.

    > This way you keep all your work and benefit from a clean Laravel foundation.

3. **Copy the example environment file and configure your database credentials:**
    ```bash
    cp .env.example .env
    ```
    Open the newly created `.env` file in your preferred editor and update the following lines with your MySQL settings:
    ```
    DB_DATABASE=your_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

4. **Install PHP dependencies using Composer:**
    ```bash
    composer install
    ```

5. **Generate the application key:**
    ```bash
    php artisan key:generate
    ```

6. **Run database migrations:**
    ```bash
    php artisan migrate
    ```

6. **(Optional) Seed the database with an initial admin user:**
    ```bash
    php artisan db:seed
    ```
    Default admin credentials:
    - Email: `admin@lingualab.test`
    - Password: `adminpass`

7. **Start the local development server:**
    ```bash
    php artisan serve
    ```
    The project will be accessible at `http://localhost:8000`.

---

For troubleshooting and more information, see the [Laravel documentation](https://laravel.com/docs).
