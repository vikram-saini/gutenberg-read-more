# DMG Read More WordPress Plugin

A WordPress plugin that provides a Gutenberg block for inserting stylized post links and a WP-CLI command for searching posts containing the block.

## Features

### Gutenberg Block
- Search for published posts using a search string
- Support for searching by post ID
- Paginated search results
- Shows recent posts by default
- Outputs a stylized "Read More:" link with the post title
- Real-time preview in the editor

### WP-CLI Command
- Search for posts containing the DMG Read More block
- Optional date range filtering (defaults to last 30 days)
- Optimized for performance with large databases
- Outputs post IDs to STDOUT

## Installation

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/yourusername/dmg-read-more.git
   cd dmg-read-more
   ```

2. Install dependencies and build the block:
   ```bash
   npm install
   npm run build
   ```

3. Activate the plugin in WordPress admin

## Usage

### Gutenberg Block

1. In the block editor, add a new block and search for "DMG Read More"
2. Use the Inspector Controls (sidebar) to search for posts
3. Click on a post to select it
4. The block will display as "Read More: [Post Title]" with a link to the post

### WP-CLI Command

```bash
# Search posts from the last 30 days (default)
wp dmg-read-more search

# Search posts between specific dates
wp dmg-read-more search --date-after=2024-01-01 --date-before=2024-12-31

# Search posts from a specific date onwards
wp dmg-read-more search --date-after=2024-06-01
```

## Project Structure

```
dmg-read-more/
├── dmg-read-more.php          # Main plugin file
├── includes/
│   └── class-dmg-read-more-cli.php  # WP-CLI command class
├── src/
│   ├── index.js               # Block JavaScript source
│   ├── editor.css             # Editor styles
│   └── style.css              # Frontend styles
├── build/                     # Compiled assets (generated)
├── package.json              # NPM configuration
└── README.md                 # This file
```

## Development

### Building the Block

```bash
# Development build with watch mode
npm run start

# Production build
npm run build
```

### Code Structure

The plugin follows WordPress coding standards and best practices:

- Uses native WordPress React components (no external dependencies)
- Server-side rendering for better performance
- REST API endpoint for post searching
- Direct SQL queries in WP-CLI command for optimal performance with large datasets

## Performance Considerations

The WP-CLI command is optimized for databases with millions of records:
- Uses direct SQL queries with proper indexing
- Implements chunked processing to manage memory usage
- Includes small delays between large batches to prevent database overload
- Uses `LIKE` query initially for speed, then validates with `has_block()` for accuracy

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Node.js 14 or higher (for building)
- WP-CLI (for command functionality)

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.