<?php

/*
// example
main(function() {
  echo "Start of script\n";
    // Example: Trigger an exception
    if (!file_exists("somefile.txt")) {
        throw new Exception("File not found!");
    }

    // Example: Trigger a warning
    $result = 1 / 0;

    echo "End of script\n";
});
//*/

// Handles program + errors gracefully
function main($fn) {
// Turn on error reporting for all types of errors
ini_set('display_errors', '0'); // Do not display errors to the user
ini_set('log_errors', '1'); // Log errors instead
ini_set('error_log', __DIR__ . '/error.log'); // Specify a log file
error_reporting(E_ALL); // Report all PHP errors

// Set a custom error handler
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set a custom exception handler
set_exception_handler(function ($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    echo "An error occurred. Please try again later.";
    var_dump($exception);
});

// Register a shutdown function to handle fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        error_log("Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}");
        echo "A critical error occurred. Please try again later.";
	    var_dump($error);
    }
});

try {
    // Code that might throw an exception or trigger an error
    $fn();
} catch (Throwable $e) { // Catch any error or exception
    error_log("Caught exception: " . $e->getMessage());
    echo "An error was handled gracefully.\n";
     var_dump($e);
}
}

