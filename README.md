# Extractable Files Web Tool

This project is a simple PHP-based web tool that lists extractable files (e.g., `.zip`, `.tar`, `.tar.gz`, `.gz`) in a directory and allows you to extract them directly from a web interface. It supports common archive file formats like `.zip`, `.tar`, and `.tar.gz`.

## Features

- **File Listing**: Automatically lists all files in the specified directory that are supported for extraction.
- **Supported Formats**: The tool currently supports the extraction of `.zip`, `.tar`, `.tar.gz`, and `.gz` files.
- **Simple UI**: Users can click a button next to each file to extract it directly in the web browser.
- **PHP Extraction**: Uses built-in PHP libraries (`ZipArchive` and `PharData`) for extraction.

## Requirements

- **PHP**: Version 7.0 or higher.
- **Web Server**: Apache, Nginx, or any server capable of running PHP.
- **PHP Extensions**:
  - `ZipArchive` for extracting `.zip` files.
  - `PharData` for extracting `.tar` and `.tar.gz` files.

## Installation

### Step 1: Clone the Repository

Clone the repository from GitHub:

```bash
git clone https://github.com/mashraf1997/Archieve-Extractor-On-Server
```

### Step 2: Move the Files to the Web Server Directory

Move the cloned project to your web server’s document root (adjust the path based on your setup):

```bash
sudo mv extractable-files-web-tool /var/www/html/
```

### Step 3: Set Directory Permissions

Ensure that the web server has the proper permissions to read and write files:

```bash
sudo chown -R www-data:www-data /var/www/html/extractable-files-web-tool
sudo chmod -R 755 /var/www/html/extractable-files-web-tool
```

### Step 4: Install PHP and Required Extensions

Ensure PHP is installed along with the required extensions. For Ubuntu/Debian:

```bash
sudo apt-get install php php-zip
```

The `php-zip` extension enables support for ZIP files, while TAR file extraction is handled by the built-in `PharData` class, which is part of PHP's default installation.

### Step 5: Access the Application

Once the project is set up, open your web browser and navigate to:

```
http://localhost/extractable-files-web-tool/
```

You should now see a list of all extractable files in the directory.

## Usage

### 1. File Listing

- The application will scan the specified directory (`./` by default) and list all files that are in supported formats (`.zip`, `.tar`, `.tar.gz`, `.gz`).

### 2. Extracting Files

- Next to each listed file, you will see an **Extract** button. Click the button to extract the file directly within the web interface.
- Files are extracted to the same directory where they are located.

### 3. No Files Found

- If no extractable files are found, you will see a message indicating that no files are available for extraction.

## Customization

### Changing the Directory

By default, the tool scans the current directory (`./`) for files. If you want to scan another directory, update this line in the code:

```php
$directory = './';  // Default directory
```

For example, to scan the `archives/` directory instead:

```php
$directory = './archives/';
```

Make sure the directory is accessible and has the appropriate permissions for the web server.

### Adding More File Formats

To add support for additional file formats, you can modify the `$extractable_files` array:

```php
$extractable_files = array('zip', 'tar', 'tar.gz', 'gz');
```

Simply add the desired file extension to this list.

## Troubleshooting

### Common Issues

1. **Missing PHP Extensions**: If you encounter errors related to missing PHP extensions (e.g., `ZipArchive`), ensure that they are installed by running:

   ```bash
   sudo apt-get install php-zip
   ```

2. **Permission Errors**: Ensure the web server user (e.g., `www-data`) has read/write access to the files and the directories where files will be extracted.

3. **Extraction Failures**: Check if the file is corrupted or if it uses a compression format that is not supported by the tool.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.


### Key Points:

- **Explanation**: This README provides a clear overview of the functionality, installation steps, usage instructions, and troubleshooting tips for the PHP-based file extraction tool.
- **Customization**: I included a section on how to customize the directory where the tool scans for files, and how to add more file formats if needed.
