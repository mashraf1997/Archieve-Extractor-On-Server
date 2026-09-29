<?php
/*
 * Archive extractor — security notes
 * ----------------------------------
 * Extracting archives from a web interface is risky. Two defenses are
 * essential and must not be removed:
 *
 *  1. The archive to extract is chosen only from the files this script lists
 *     in $directory. The POSTed name is reduced to its basename and checked to
 *     be a real file inside $directory, so it can't point elsewhere on disk
 *     (path traversal).
 *  2. Every archive is extracted into extracted/<archive-name>/ — never over
 *     the working directory or the web root — and each entry is checked so it
 *     cannot escape that folder (zip slip). This keeps an archive that
 *     contains a .php file from becoming executable code in a web-served path.
 *
 * All filenames are HTML-escaped before display to prevent XSS.
 */

// Directory containing extractable files. Keep this OUT of a web-served path
// in production, or protect this script behind authentication.
$directory = './';

// Supported archive extensions.
$supported_extensions = array('zip', 'tar', 'tar.gz', 'gz');

$base = realpath($directory);

/** HTML-escape helper. */
function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** List files in $dir whose extension is supported. */
function listExtractableFiles($dir, $supported_extensions) {
    $found = array();
    $entries = @scandir($dir);
    if ($entries === false) {
        return $found;
    }
    foreach ($entries as $file) {
        if (!is_file($dir . DIRECTORY_SEPARATOR . $file)) {
            continue;
        }
        if (preg_match('/\.tar\.gz$/i', $file)) {
            $found[] = $file;
        } else {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $supported_extensions, true)) {
                $found[] = $file;
            }
        }
    }
    return $found;
}

/**
 * Resolve a user-supplied archive name to a safe absolute path inside $base,
 * or return null if it isn't a real file directly within $base.
 */
function resolve_within_base($name, $base) {
    $name = basename($name); // strip any directory components
    $path = realpath($base . DIRECTORY_SEPARATOR . $name);
    if ($path === false || !is_file($path)) {
        return null;
    }
    // Ensure the resolved path is really inside $base.
    if (strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
        return null;
    }
    return $path;
}

/** True if an archive entry path would escape the destination directory. */
function entry_is_unsafe($entry) {
    $entry = str_replace('\\', '/', (string) $entry);
    if ($entry === '' ) {
        return false;
    }
    if ($entry[0] === '/' ) {
        return true; // absolute path
    }
    foreach (explode('/', $entry) as $part) {
        if ($part === '..') {
            return true; // traversal
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extract Files</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .file-list {
            margin-bottom: 20px;
        }
        .file-item {
            padding: 10px;
            background: #f3f3f3;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .file-item button {
            padding: 5px 10px;
            background: #4caf50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }
        .file-item button:hover {
            background: #45a049;
        }
        .message {
            margin-top: 20px;
            color: #ff0000;
        }
    </style>
</head>
<body>

<h1>Extractable Files</h1>

<div class="file-list">
    <?php
    $files = listExtractableFiles($directory, $supported_extensions);

    if (count($files) > 0) {
        foreach ($files as $file) {
            echo "<div class='file-item'>" . h($file) . "
                    <form method='POST' style='display:inline;' action=''>
                        <input type='hidden' name='file_to_extract' value='" . h($file) . "'>
                        <button type='submit'>Extract</button>
                    </form>
                  </div>";
        }
    } else {
        echo "<p>No extractable files found in the directory.</p>";
    }
    ?>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_to_extract'])) {
    // Log errors, don't display them (avoid leaking server paths).
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);

    $requested = (string) $_POST['file_to_extract'];
    $file_path = $base ? resolve_within_base($requested, $base) : null;

    if ($file_path === null) {
        echo "<p class='message'>Invalid or unknown file.</p>";
    } else {
        $name = basename($file_path);
        // Each archive extracts into its own subfolder, never the web root.
        $dest = $base . DIRECTORY_SEPARATOR . 'extracted' . DIRECTORY_SEPARATOR
              . preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        if (!is_dir($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
            echo "<p class='message'>Could not create the extraction directory.</p>";
        } elseif (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($file_path) === true) {
                $unsafe = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    if (entry_is_unsafe($zip->getNameIndex($i))) {
                        $unsafe = true;
                        break;
                    }
                }
                if ($unsafe) {
                    echo "<p class='message'>Archive rejected: it contains unsafe paths.</p>";
                } else {
                    $zip->extractTo($dest);
                    echo "<p class='message'>ZIP file <strong>" . h($name) . "</strong> extracted successfully.</p>";
                }
                $zip->close();
            } else {
                echo "<p class='message'>Failed to open ZIP file <strong>" . h($name) . "</strong>.</p>";
            }
        } elseif (preg_match('/\.tar\.gz$|\.tar$|\.gz$/i', $name)) {
            try {
                $phar = new PharData($file_path);
                // PharData throws on entries that would escape the destination.
                $phar->extractTo($dest, null, true);
                echo "<p class='message'>Archive <strong>" . h($name) . "</strong> extracted successfully.</p>";
            } catch (Exception $e) {
                error_log('Extractor error: ' . $e->getMessage());
                echo "<p class='message'>Failed to extract <strong>" . h($name) . "</strong>.</p>";
            }
        } else {
            echo "<p class='message'>Unsupported file type.</p>";
        }
    }
}
?>

</body>
</html>
