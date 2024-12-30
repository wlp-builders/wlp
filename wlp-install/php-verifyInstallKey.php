<?php

// Function to create a secure install key, only if it doesn't already exist
function createInstallKey() {
    $filePath = __DIR__ . '/install-key.php';

    // Check if the install-key.php file already exists
    if (file_exists($filePath)) {
        //echo "Install key already exists.\n";
        return; // Exit if the file already exists
    }

    // Generate 32 bytes of random data (you can adjust the number of bytes for longer/shorter keys)
    $randomBytes = random_bytes(32);

    // Encode the random data into a base64 string, url friendly
    $trimmedBase64String = rtrim(strtr(base64_encode($randomBytes), '+/', '-_'), '=');


    // Prepare the content to be written into the install-key.php file
    $content = "<?php\n";
    $content .= "define('install_key', '$trimmedBase64String');\n";

    // Write the content to a file in the current directory
    file_put_contents($filePath, $content);

    //echo "Install key has been generated and saved in install-key.php\n";
}

// Function to verify the install key
function verifyInstallKey($keyToVerify) {
    $filePath = __DIR__ . '/install-key.php';

    // Check if the file exists
    if (!file_exists($filePath)) {
        //echo "Install key file does not exist.\n";
        return false;
    }

    // Include the file to access the install_key constant
    include($filePath);

    // Verify if the install_key matches the provided key
    if (defined('install_key') && install_key === $keyToVerify) {
        //echo "Install key is valid.\n";
        return true;
    } else {
        //echo "Install key is invalid.\n";
        return false;
    }
}

//*
// Create and store a new install key, only if it doesn't exist
createInstallKey();

/*
// Demo Verify the install key (Replace with the key you want to test)
$key = 'IAHI+lrXmMoKvXsJNel9Rm7k0vysQX5BKPbK08Snbjw'; // Replace with the key you want to verify
$isValid = verifyInstallKey($key);

if ($isValid) {
    echo "The install key is valid.\n";
} else {
    echo "The install key is invalid.\n";
}
//*/

