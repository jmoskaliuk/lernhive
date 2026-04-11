# Test Data Generator Plugin for Moodle

A developer tool for Moodle that generates and manages test data via JSON configuration. Ideal for testing, development, and demonstrations.

## Features

- **CLI-based generation**: Create test data from JSON config files
- **Flexible configuration**: Define courses, users, questions, and activities
- **Automatic tracking**: Remembers all created entities for easy cleanup
- **Admin interface**: Web UI to view and manage generated datasets
- **Full cleanup**: Remove all or specific datasets with a single command
- **Multichoice questions**: Built-in support for creating multichoice questions
- **Activity modules**: Create any activity module (quiz, assign, leitnerflow, etc.)
- **User enrollment**: Automatically enrol users in courses with role assignment

## Installation

1. Extract the plugin to `/local/testdata/`
2. Run `php admin/cli/upgrade.php` to create database tables
3. Navigate to **Site Administration > Development > Test Data Generator**

## Configuration

Test data is defined using JSON configuration files. See `example_config.json` for a complete example.

### Configuration Structure

```json
{
  "dataset": "demo_2024",
  "description": "Optional description of the dataset",
  "users": [
    {
      "username": "user1",
      "firstname": "John",
      "lastname": "Doe",
      "email": "john@example.com",
      "password": "Password123!",
      "auth": "manual"
    }
  ],
  "courses": [
    {
      "fullname": "Course Name",
      "shortname": "course-code",
      "category": 1,
      "summary": "Optional course description",
      "questions": [
        {
          "category": "Question Category Name",
          "items": [
            {
              "name": "Question Name",
              "questiontext": "<p>Question text here</p>",
              "answer": "Correct answer",
              "wrong": ["Wrong answer 1", "Wrong answer 2", "Wrong answer 3"]
            }
          ]
        }
      ],
      "activities": [
        {
          "module": "quiz",
          "name": "Quiz Name",
          "section": 1,
          "settings": {
            "intro": "Quiz description",
            "grade": 100,
            "grademethod": 1
          }
        }
      ],
      "enrol": "$all_users"
    }
  ]
}
```

### Special Values

- **`"$all_users"`**: Enrolls all users created in the configuration
- **`"$auto"`**: For `questioncategoryid` in activities, uses the first category created in that course

## Usage

### CLI Generate

Generate test data from a configuration file:

```bash
php local/testdata/cli/generate.php --config=path/to/config.json
```

Options:
- `--config=FILE` (required): Path to JSON configuration file
- `--clean`: Remove existing dataset of same name before generating

Example with cleanup:
```bash
php local/testdata/cli/generate.php --config=demo.json --clean
```

### CLI Clean

Clean up test data:

```bash
# List all datasets
php local/testdata/cli/clean.php --list

# Clean specific dataset
php local/testdata/cli/clean.php --dataset=demo_2024

# Clean all datasets
php local/testdata/cli/clean.php --all
```

### Admin Interface

Visit **Site Administration > Development > Test Data Generator** to:
- View all generated datasets with item counts
- View detailed items in each dataset
- Delete individual datasets
- Delete all datasets at once

## Database Tables

### `local_testdata_sets`
Tracks all generated datasets:
- `id`: Primary key
- `name`: Dataset identifier
- `description`: Human-readable description
- `configjson`: JSON configuration used
- `itemcount`: Number of items created
- `timecreated`: Timestamp

### `local_testdata_items`
Tracks individual entities created:
- `id`: Primary key
- `dataset`: Which dataset owns this item
- `entitytype`: Type (course, user, question, course_module, etc.)
- `entityid`: Moodle ID of the entity
- `entityname`: Human-readable name
- `timecreated`: Timestamp

## API Usage

You can also use the generator class in your own code:

```php
use local_testdata\generator;

$gen = new generator('my_dataset');

// Create users
$userids = $gen->create_users([
    ['username' => 'user1', 'firstname' => 'John', 'lastname' => 'Doe', 'password' => 'Pass123!'],
]);

// Create courses
$courseids = $gen->create_courses([
    ['fullname' => 'My Course', 'shortname' => 'mycourse', 'category' => 1],
]);

// Enrol users
$gen->enrol_users($courseids[0], $userids, 'student');

// Create question category
$catid = $gen->create_question_category($courseids[0], 'My Questions');

// Create questions
$questionids = $gen->create_multichoice_questions($catid, [
    [
        'name' => 'Q1',
        'questiontext' => '<p>Question?</p>',
        'answer' => 'Correct',
        'wrong' => ['Wrong1', 'Wrong2', 'Wrong3'],
    ],
]);

// Create activity
$modid = $gen->create_activity($courseids[0], 'quiz', [
    'name' => 'My Quiz',
    'section' => 1,
    'settings' => ['intro' => 'Quiz intro', 'grade' => 100],
]);

// Run full config
$gen->run_config(json_decode(file_get_contents('config.json'), true));

// Delete dataset
$gen->delete_dataset('my_dataset');
```

## Supported Activity Modules

The plugin supports creating any installed activity module by name:
- `quiz` - Quiz
- `assign` - Assignment
- `forum` - Forum
- `choice` - Choice
- `leitnerflow` - LeitnerFlow (when installed)
- And any other custom modules

Module-specific settings should be passed in the `settings` object.

## Requirements

- Moodle 5.x (2024042200+)
- PHP 7.4+
- Database with support for JSON columns (or text fallback)

## Permissions

- **Capability**: `local/testdata:manage`
- **Assigned to**: Manager, Administrator roles by default
- Grants access to CLI commands and admin interface

## Troubleshooting

### "Configuration file not found"
Ensure the path to your JSON config file is absolute or relative to the Moodle root directory.

### "Invalid JSON"
Validate your JSON configuration at [jsonlint.com](https://www.jsonlint.com/) or similar.

### "Module not found"
Ensure the activity module is installed and enabled. Use `php admin/cli/upgrade.php` to verify.

### "Role not found"
Common role shortnames: `student`, `teacher`, `editingteacher`, `manager`, `admin`. Check your Moodle instance for available roles.

## Security Notes

- This plugin is intended for **development and testing only**
- Running on production sites should be done with caution
- The plugin creates real entities that must be cleaned up manually if not using the cleanup tools
- Only users with `local/testdata:manage` capability can access the admin interface

## Development

The plugin structure follows Moodle plugin standards:
- `version.php` - Plugin metadata
- `db/` - Database schema
- `classes/` - PHP classes (namespace: `local_testdata`)
- `cli/` - Command-line scripts
- `lang/` - Language strings
- `settings.php` - Admin settings

## License

GNU GPL v3 or later. See LICENSE file for details.

## Copyright

2024 eLeDia GmbH

## Support

For issues or feature requests, contact the development team.
