<?php

/**
 * A very small PHP logging library.
 *
 * Released under the GNU LGPL v2.1.
 *
 * Copyright Osprey Design Company, LLC.
 */

class Minilog {
  protected $log_destinations = array();

  /**
   * The various error levels that we can log with.
   */
  const DEBUG     = 100; // Detailed debug information.
  const INFO      = 200; // Interesting events. Examples: User logs in, SQL logs.
  const NOTICE    = 250; // Normal but significant events.
  const WARNING   = 300; // Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
  const ERROR     = 400; // Runtime errors that do not require immediate action but should typically be logged and monitored.
  const CRITICAL  = 500; // Critical conditions. Example: Application component unavailable, unexpected exception.
  const ALERT     = 550; // Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
  const EMERGENCY = 600; // Emergency: system is unusable.

  /** 
   * Map error level numbers to
   * human readable strings.
   */
  public $ERROR_LEVELS = array(
    100 => 'Debug',
    200 => 'Info',
    250 => 'Notice',
    300 => 'Warning',
    400 => 'Error',
    500 => 'Critical',
    550 => 'Alert',
    600 => 'Emergency',
  );

  /**
   * @param LogDestinaiton $log_destination [description]
   */
  function __construct(LogDestinaiton $log_destination, $min_level = Minilog::DEBUG) {
    $this->log_destinations = array();
    $this->addDestination($log_destination, $min_level);
  }

  /**
   * [addDestination description]
   * @param LogDestinaiton $log_destination [description]
   * @param [type]         $min_level       [description]
   */
  public function addDestination(LogDestinaiton $log_destination, $min_level = Minilog::DEBUG) {
    // Inject the logger into the desintation
    $log_destination->setLogger($this);

    // Add to our list of destinations
    $this->log_destinations[] = array(
      'destination' => $log_destination,
      'min_level' => $min_level,
    );
  }

  /**
   * [log description]
   * @param  [type] $message [description]
   * @param  [type] $level   [description]
   * @return [type]          [description]
   */
  public function log($message, $level = Minilog::WARNING, $time = NULL) {
    // If the time wasn't provided then use the current time
    if (!$time) {
      $time = time();
    }
    
    foreach ($this->log_destinations as $destination_info) {
      $destination_info['destination']->log($message, $level, $time);
    }
  }

  /**
   * [debug description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function debug($message) {
    $this->log($message, Minilog::DEBUG);
  }

  /**
   * [info description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function info($message) {
    $this->log($message, Minilog::INFO);
  }

  /**
   * [notice description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function notice($message) {
    $this->log($message, Minilog::NOTICE);
  }

  /**
   * [warning description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function warning($message) {
    $this->log($message, Minilog::WARNING);
  }

  /**
   * [error description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function error($message) {
    $this->log($message, Minilog::ERROR);
  }

  /**
   * [critical description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function critical($message) {
    $this->log($message, Minilog::CRITICAL);
  }

  /**
   * [alert description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function alert($message) {
    $this->log($message, Minilog::ALERT);
  }

  /**
   * [emergency description]
   * @param  [type] $message [description]
   * @return [type]          [description]
   */
  public function emergency($message) {
    $this->log($message, Minilog::EMERGENCY);
  }
}

interface LogDestinaiton {
  public function log($message, $level, $time);
  public function setDateFormat(string $new_format);
  public function setMessageFormat(string $new_format);
}

abstract class BaseDestination {
  protected $date_format = 'd-m-Y h:iA';
  protected $log_path = '';
  protected $message_format = "@date - [@level] - @message";

  public function __construct($date_format = NULL, $message_format = NULL) {   
    // Set the date format
    if ($date_format) {
      $this->date_format = $date_format;
    }

    // Set the message format
    if ($message_format) {
      $this->message_format = $message_format;
    }
  }

  // Allow the parent logger to include itself
  // so that we can access the level map
  public function setLogger($logger) {
    $this->logger = $logger;
  }

  public function log($message, $level, $time = NULL) {
    // Log the message
    echo $this->formatMessage($message, $level, $time);
  }

  /**
   * Set a new date format.
   * @param [type] $new_format [description]
   */
  public function setDateFormat(string $new_format) {
    $this->date_format = $new_format;
  }

  /**
   * Set a new message format.
   * @param [type] $new_format [description]
   */
  public function setMessageFormat(string $new_format) {
    $this->message_format = $new_format;
  }


  /**
   * Format a log message
   */
  protected function formatMessage($message, $level, $time) {
    $replacements = array(
      '@date' => date($this->date_format, $time),
      '@message' => $message,
      '@level' => $this->logger->ERROR_LEVELS[$level],
    );

    // var_dump($replacements);

    // Copy the message format to use as the message
    $message = $this->message_format;

    // Make all of the replacements and return the message
    foreach ($replacements as $pattern => $replacement) {
      $message = str_replace($pattern, $replacement, $message);
    }

    return $message;
  }

}

class FileDestination extends BaseDestination implements LogDestinaiton {
  

  public function __construct($log_path, $date_format = NULL, $message_format = NULL) {
    // Set the path to log to
    if (empty($log_path)) {
      throw new Exception('A log path must be configured.');
    }
    else {
      $this->log_path = $log_path;  
    }
    
    // Set the date format
    if ($date_format) {
      $this->date_format = $date_format;
    }

    // Set the message format
    if ($message_format) {
      $this->message_format = $message_format;
    }
  }

  /**
   * Log a message.
   * @param  string $message
   *  The message to log.
   * @param  int $level
   *  The error level to use, which should be a Minilog log
   *  level constant.
   * @param  int $time
   *  The time to log the message for.  If not provided this
   *  will use the current time.  In general it's not suggested
   *  to modify this as with files this could cause lines to 
   *  be out of order.  
   * @return void
   */
  public function log($message, $level, $time = NULL) {
    // Log the message
    $log_line = $this->formatMessage($message, $level, $time);
    file_put_contents($log_path, $log_line, FILE_APPEND); 
  }

  /**
   * Update the path this logger will log to.
   */
  public function setLogPath($newPath) {
    $this->log_path = $newPath;
  }  
}

class StdIODestination extends BaseDestination implements LogDestinaiton {
  protected $message_format = "@date - [@level] - @message";

  protected $green = "\033[0;32m";
  protected $red   = "\033[0;31m";
  protected $reset = "\033[0m";

  public function __construct($date_format = NULL, $message_format = NULL) {
    // Open STDERR and STDOUT in order to write out messages
    $this->stdout = fopen('php://stdout', 'w+');
    $this->stderr = fopen('php://stderr', 'w+');

    // Set the date format
    if ($date_format) {
      $this->date_format = $date_format;
    }

    // Set the message format
    if ($message_format) {
      $this->message_format = $message_format;
    }
  }

  /**
   * Format a log message
   */
  protected function formatMessage($message, $level, $time) {
    // Set the color of the output level
    if ($level >= Minilog::ERROR) {
      $color = $this->red;
    }
    else {
      $color = $this->green;
    }

    // Configure replacements
    $replacements = array(
      '@date' => date($this->date_format, $time),
      '@message' => $message,
      '@level' => $color . $this->logger->ERROR_LEVELS[$level] . $this->reset,
    );

    // Copy the message format to use as the message
    $message = $this->message_format;

    // Make all of the replacements and return the message
    foreach ($replacements as $pattern => $replacement) {
      $message = str_replace($pattern, $replacement, $message);
    }

    return $message;
  }

  /**
   * Log a message.
   * @param  string $message
   *  The message to log.
   * @param  int $level
   *  The error level to use, which should be a Minilog log
   *  level constant.
   * @param  int $time
   *  The time to log the message for.  If not provided this
   *  will use the current time.  In general it's not suggested
   *  to modify this as with files this could cause lines to 
   *  be out of order.  
   * @return void
   */
  public function log($message, $level, $time = NULL) {
    // Log the message
    $log_line = $this->formatMessage($message, $level, $time) . "\n";

    // If there is an error logged we output to STDERR
    // otherwise if it's a lower level then we log to STDOUT
    if ($level >= Minilog::ERROR) {
      fwrite($this->stderr, $log_line);
    }
    else {
      fwrite($this->stdout, $log_line);
    }
  }
}
