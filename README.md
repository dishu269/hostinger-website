# Dishant Parihar — Digital HQ

This project is a personal Digital HQ, CRM, and Training Hub designed for the Asclepius Wellness sales team. It helps team members manage leads, track tasks, complete learning modules, and monitor their progress in a gamified environment.

## Key Features

- **User Authentication:** Secure user registration, login, and password reset functionality.
- **Role-Based Access Control:** Separate interfaces and permissions for `admin` and `member` roles.
- **User Dashboard:** A central hub for users to see their daily tasks, key performance indicators (KPIs), due follow-ups, and recent notifications.
- **Lead Management (CRM):** A personal CRM to manage sales leads, with support for offline lead creation that syncs automatically when the user is back online.
- **Task Management:** Users can manage their daily and weekly tasks, with a Kanban board view (`todo`, `doing`, `done`) for visual workflow management.
- **Learning Hub:** A collection of learning modules (videos, PDFs, articles) with progress tracking for each user.
- **Gamification:**
    - **Achievements & Badges:** Users can earn badges for reaching milestones (e.g., adding their first lead).
    - **Streaks:** The system tracks daily task completion streaks to encourage consistency.
    - **Leaderboard:** A leaderboard to foster friendly competition.
- **Resource Library:** A central place for admins to publish important documents, scripts, and other resources.
- **Community Forum:** A simple forum where users can create posts and comment on them to ask questions and share knowledge.
- **Admin Panel:** A comprehensive backend for admins to manage users, tasks, learning modules, resources, and motivational messages.
- **Progressive Web App (PWA):** Includes a service worker for basic offline capabilities and a web app manifest.

## Tech Stack

- **Backend:** PHP 8+
- **Database:** MySQL 8+
- **Frontend:**
    - JavaScript (ES6+)
    - HTML5
    - CSS3 (Mobile-first, responsive design)

## Setup and Installation

To set up the project locally, follow these steps:

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    ```

2.  **Set up the database:**
    -   Create a new database in your MySQL server.
    -   Import the database schema from `public_html/db/schema.sql`. This will create all the necessary tables and seed some initial data.

3.  **Configure Environment Variables:**
    This project uses environment variables for configuration to keep sensitive data out of the codebase. In your server environment (e.g., using your hosting panel, an `.htaccess` file, or a `.env` file with a loader like `vlucas/phpdotenv`), set the following variables:

    -   `DB_HOST`: Your database host (e.g., `localhost`).
    -   `DB_NAME`: The name of the database you created.
    -   `DB_USER`: The username for your database.
    -   `DB_PASS`: The password for your database user.
    -   `CRON_TOKEN`: A long, random, and secret string used to secure your cron job endpoints.

4.  **Configure your web server:**
    -   Point your web server's document root to the `public_html/` directory. This is important for security, as it prevents direct web access to files outside of the public directory.
    -   Ensure `mod_rewrite` (or your server's equivalent) is enabled to handle URL rewriting from the `.htaccess` file.

5.  **Admin User:**
    -   The first user who registers with the email address `dishantparihar00@gmail.com` will be automatically assigned the `admin` role. You can change this default admin email in `public_html/includes/config.php`.

## Future Enhancements

Here are some potential areas for future improvement:

-   **Add Automated Tests:** The project currently lacks an automated test suite. Introducing a testing framework like PHPUnit to write unit and integration tests would greatly improve code reliability and long-term stability.
-   **Improve the Admin Panel:** The admin section could be enhanced with more features, such as:
    -   Visual charts and graphs for analytics (e.g., using Chart.js).
    -   A more comprehensive content management system (CMS) for learning modules and resources.
    -   More detailed user management tools.
-   **Enhance the UI with AJAX:** To make the application feel faster and more like a single-page app (SPA), more forms could be converted to use AJAX for background submission, preventing full page reloads.
-   **Implement a Templating Engine:** For better separation of concerns and cleaner code, a templating engine like Twig could be integrated. This would separate the HTML presentation from the PHP business logic, making the frontend code easier to manage and more secure.
