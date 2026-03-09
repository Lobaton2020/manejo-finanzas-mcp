# AGENTS.md - Manejo_Finanzas

## Project Overview

Personal finance management application built with vanilla PHP using a custom MVC framework. The app handles income/expense tracking, budgets, investments, and financial reports.

## Tech Stack

- **Language**: PHP 8.x
- **Database**: MySQL (via PDO)
- **Frontend**: HTML, JavaScript, jQuery, Bootstrap 5, SCSS
- **Architecture**: Custom MVC with ORM

## Directory Structure

```
Manejo_Finanzas/
├── app/
│   ├── config/         # Configuration files
│   ├── core/           # Framework classes (Base, Controller, Orm, Router, Authentication)
│   ├── controllers/    # Controller classes (PascalCase naming)
│   ├── helpers/        # Helper functions
│   ├── models/         # Model classes extending Orm
│   ├── views/         # PHP view files
│   └── initializer.php # App bootstrap
├── public/            # Static assets (CSS, JS, images)
├── docs/              # Documentation (ER diagrams, etc.)
└── index.php          # Entry point
```

## Build/Lint/Test Commands

This is a vanilla PHP project without Composer or npm. There are no formal build/test scripts configured.

### Running the Application

```bash
# Using PHP built-in server (from project root)
php -S localhost:8000

# Or using XAMPP/WAMP
```

### Manual Code Review

- Check PHP syntax: `php -l <filepath>`
- Check all PHP files: `find . -name "*.php" -exec php -l {} \;`
- No automated linting (phpstan, psalm) currently configured

### Database

- Database configuration via `.env` file
- See `.env.example` for required variables

## Code Style Guidelines

### General Conventions

- **PHP Opening Tag**: Always use `<?php` (no short tags `<?`)
- **File Encoding**: UTF-8
- **Line Endings**: Unix-style (LF)
- **Indentation**: 4 spaces (no tabs)
- **Closing Tag**: Omit `?>` at end of pure PHP files

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `UserController`, `Orm` |
| Methods | camelCase | `getById()`, `selectAll()` |
| Properties | camelCase | `$this->id`, `$this->model` |
| Constants | UPPER_CASE | `DBHOST`, `URL_PROJECT` |
| Files | PascalCase (classes) | `UserController.php` |
| Views | snake_case | `inflows/list.php`, `users/profileEdit.php` |

### Class Structure

```php
<?php

class ClassName extends ParentClass
{
    // Constants first
    // Private properties
    // Protected properties
    // Public properties (avoid)
    // Constructor
    // Public methods
    // Protected methods
    // Private methods
}
```

### Method Design

- Keep methods focused and under 50 lines when possible
- Use type hints for parameters and return types
- Document complex methods with `@param` and `@return` annotations

```php
// Good
public function get(string $id): ?User
{
    // implementation
}

// Avoid
public function get($id)
{
    // implementation
}
```

### Property Definitions

- Use typed properties (PHP 8+)
- Declare visibility explicitly

```php
// Good
private string $name;
protected ?int $id;

// Avoid
var $name;
public $id;
```

### Database Operations (Orm Usage)

- Always use parameterized queries via `$this->bind()`
- Use the built-in ORM methods: `select()`, `get()`, `insert()`, `update()`, `delete()`
- Return results wrapped in `JSON` class

```php
// Select with conditions
$users = $this->model->select("*", ["status[=]" => 1, "id_user[=]" => $this->id, "AND"])->array();

// Insert
$this->model->insert($data);

// Update
$this->model->update($data, ["id[=]" => $id]);
```

### Controllers

- One controller per feature/domain
- Controller name ends with `Controller` suffix
- Use `$this->model("modelName")` to load models
- Use `$this->view("viewName", $data)` to render views
- Use `$this->redirect("path")` for navigation
- Call `$this->authentication()` in constructor for protected routes

```php
class InflowController extends Controller
{
    private $model;
    
    public function __construct()
    {
        parent::__construct();
        $this->authentication();  // Protect routes
        $this->model = $this->model("inflow");
    }
    
    public function index()
    {
        $data = $this->model->select("*", ["id_user[=]" => $this->id])->array();
        return $this->view("inflows.list", ["inflows" => $data]);
    }
}
```

### Models

- Extend `Orm` class
- Pass table name to parent constructor
- Keep business logic in models

```php
class User extends Orm
{
    public function __construct()
    {
        parent::__construct("users");
    }
    
    public function login($data)
    {
        // business logic
    }
}
```

### Views

- Use `.php` extension
- Follow snake_case naming: `module/action.php`
- Access data via variables passed from controller
- Use helper functions for common operations

```php
<?php foreach ($inflows as $inflow): ?>
    <tr>
        <td><?= $inflow->total ?></td>
    </tr>
<?php endforeach; ?>
```

### Error Handling

- Use `try/catch` blocks for database operations
- Throw `ErrorException` for framework errors
- Display user-friendly messages in views

```php
try {
    $this->model->insert($data);
} catch (Exception $e) {
    throw new ErrorException($e->getMessage());
}
```

### Input Validation

- Use helper functions from `helpers/validator.php`
- Validate all user input before database operations
- Use `arrayEmpty()` to check required fields

### Security

- Never expose database credentials
- Use environment variables for sensitive data
- Always hash passwords (see `helpers/encrypt.php`)
- Escape output in views (`<?= htmlspecialchars($var) ?>`)
- Use CSRF protection for forms (if implemented)

### SQL Conventions

- Use uppercase for SQL keywords: `SELECT`, `FROM`, `WHERE`, `INSERT INTO`, etc.
- Always use prepared statements with bound parameters
- Prefix table names consistently

### Helpers

Available helper functions (see `app/helpers/`):

| Helper | Purpose |
|--------|---------|
| `dates.php` | Date formatting |
| `encrypt.php` | Password hashing/verification |
| `json.php` | JSON encoding/decoding |
| `validator.php` | Input validation |
| `redirect()` | URL redirection |
| `view()` | Render views |
| `getCurrentDatetime()` | Current timestamp |

### Common Patterns

**Loading a model:**
```php
$user = $this->model("user");
```

**Loading with view:**
```php
return view("inflows.list", ["inflows" => $inflows], true);  // true for JSON response
```

**Handling POST requests:**
```php
return execute_post(function ($request) {
    // process $request data
});
```

**Redirecting with message:**
```php
return redirect("inflow/create")->with("error", "Validation message");
```

## Common Tasks

### Adding a New Feature

1. Create model in `app/models/FeatureName.php`
2. Create controller in `app/controllers/FeatureNameController.php`
3. Create views in `app/views/featureName/`
4. Add route in `app/core/Router.php`

### Database Changes

- Update `.env` for connection settings
- Schema stored in `docs/MR_finances.mdj` (Modelio)

### Working with the ORM

```php
// Simple select
$results = $this->model->select("*", ["status[=]" => 1])->array();

// Get single record
$record = $this->model->get("*", ["id[=]" => $id])->array();

// Check existence
$exists = $this->model->has(["email[=]" => $email])->array();

// Count records
$count = $this->model->count(["status[=]" => 1])->int();

// Aggregate functions
$max = $this->model->max("amount", ["user_id[=]" => $id]);
$sum = $this->model->sum("amount", ["status[=]" => 1]);
```

## Notes for AI Agents

- This is a legacy-style PHP project without modern tooling
- No automated tests exist - test manually via browser
- No type checking/linting in CI pipeline
- The ORM has custom syntax (e.g., `["id[=]" => $value]` for WHERE clauses)
- Views mix PHP and HTML - follow existing patterns
- Some inconsistency in code style exists - prioritize consistency with surrounding code
