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

2. **Copy the example environment file and configure your database credentials:**
    ```bash
    cp .env.example .env
    ```
   Open the newly created `.env` file in your preferred editor and update the following lines with your MySQL settings:
    ```
    DB_DATABASE=your_database
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

3. **Set up authentication scaffolding:**
    ```bash
    composer require laravel/breeze --dev
    php artisan breeze:install
    npm install
    npm run build
    php artisan migrate
    ```
   This will add login and registration pages. Access the login page at `http://localhost:8000/login`.

3. **Install PHP dependencies using Composer:**
    ```bash
    composer install
    ```

4. **Generate the application key:**
    ```bash
    php artisan key:generate
    ```

5. **Run database migrations:**
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

LinguaLab is built using [Laravel](https://laravel.com/), which is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).

For troubleshooting and more information, see the [Laravel documentation](https://laravel.com/docs).
