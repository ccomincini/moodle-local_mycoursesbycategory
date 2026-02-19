# local_mycoursesbycategory

A Moodle local plugin that replaces the standard "My courses" page with a view that groups the user's enrolled courses by **category**, featuring collapsible sections, course cards with images, and completion progress bars.

**Client**: Polis Lombardia

## Requirements

- Moodle 4.3, 4.4, 4.5, or 5.0
- PHP 8.1, 8.2, or 8.3

## Installation

### Via git

```bash
cd /path/to/moodle/local
git clone https://github.com/invisiblefarm/moodle-local_mycoursesbycategory.git mycoursesbycategory
```

### Manual

1. Download the plugin archive.
2. Extract the contents into `/path/to/moodle/local/mycoursesbycategory`.
3. Visit **Site administration > Notifications** to complete the installation.

## Configuration

After installation, go to **Site administration > Plugins > Local plugins > My courses by category**.

| Setting | Description | Default |
|---------|-------------|---------|
| Enable redirect | Redirect `/my/courses.php` to this plugin's page | Off |
| Show progress | Show completion progress bars on course cards | On |
| Layout | Choose between card and list layout | Card |

## Features

- Courses grouped by category with collapsible sections
- Course cards with overview images (or placeholder)
- Completion progress bar per course
- "Expand all" / "Collapse all" buttons
- Optional redirect from the standard My courses page
- Responsive grid layout
- Italian and English language support

## License

This plugin is licensed under the [GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).

## Credits

Developed by [Invisiblefarm](https://www.invisiblefarm.it/).
