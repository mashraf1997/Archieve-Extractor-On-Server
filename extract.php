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
    // Directory containing extractable files
    $directory = './'; // Change to the directory where your files are located

    // Supported file extensions for extraction
    $extractable_files = array('zip', 'tar', 'tar.gz', 'gz');

    // Function to list all extractable files
    function listExtractableFiles($dir, $supported_extensions) {
        $files = scandir($dir);
        $extractable_files = array();

        foreach ($files as $file) {
            // Get the file extension
            if (is_file($dir . $file)) {
                $file_ext = pathinfo($file, PATHINFO_EXTENSION);
                
                // Special case for tar.gz and other multi-part extensions
                if (preg_match('/\.tar\.gz$/', $file)) {
                    $extractable_files[] = $file;
                } elseif (in_array($file_ext, $supported_extensions)) {
                    $extractable_files[] = $file;
                }
            }
        }
        return $extractable_files;
    }

    // List files in the directory
    $files = listExtractableFiles($directory, $extractable_files);

    // Display the files
    if (count($files) > 0) {
        foreach ($files as $file) {
            echo "<div class='file-item'>$file 
                    <form method='POST' style='display:inline;' action=''>
                        <input type='hidden' name='file_to_extract' value='$file'>
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
// Check if a file extraction request has been made
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_to_extract'])) {
    $file_to_extract = $_POST['file_to_extract'];
    $file_path = $directory . $file_to_extract;
    
    // Extract ZIP Files
    if (pathinfo($file_to_extract, PATHINFO_EXTENSION) === 'zip') {
        $zip = new ZipArchive();
        if ($zip->open($file_path) === TRUE) {
            $zip->extractTo($directory);  // Extract to the same directory
            $zip->close();
            echo "<p class='message'>ZIP file <strong>$file_to_extract</strong> extracted successfully!</p>";
        } else {
            echo "<p class='message'>Failed to extract ZIP file <strong>$file_to_extract</strong>.</p>";
        }
    }
    // Extract TAR or TAR.GZ Files
    elseif (preg_match('/\.tar\.gz$|\.tar$/', $file_to_extract)) {
        try {
            $phar = new PharData($file_path);
            $phar->extractTo($directory);  // Extract to the same directory
            echo "<p class='message'>TAR.GZ file <strong>$file_to_extract</strong> extracted successfully!</p>";
        } catch (Exception $e) {
            echo "<p class='message'>Failed to extract TAR.GZ file <strong>$file_to_extract</strong>. Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

</body>
</html>
