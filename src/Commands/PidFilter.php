<?php

namespace Drupal\foxml\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Psr\Log\LoggerInterface;

class PidFilter {

  const OPTION = 'pids';

  /**
   * The logger to use.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Whether or not we should emit debug messages.
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Constructor.
   */
  public function __construct(LoggerInterface $logger, bool $debug = FALSE) {
    $this->logger = $logger;
    $this->debug = $debug;
  }

  /**
   * @hook option @foxml-pid-filterer
   */
  public function options(Command $command, AnnotationData $annotationData) {
    $command->addOption(
      static::OPTION,
      'p',
      InputOption::VALUE_REQUIRED,
      'A file of PIDs (without the "info:fedora/" prefix) to which to restrict operations. "-" can be used to read from STDIN.'
    );
  }

  /**
   * Check that the file.
   *
   * @hook validate @foxml-pid-filterer
   */
  public function validate(CommandData $commandData) {
    // TODO: First bluff check that if we have a value for the command, it's
    // readable.
    $input = $commandData->input();
    $filename = $input->getOption(static::OPTION)

    if (!isset($filename)) {
      $this->logDebug('"{opt}" option does not appear to be set.', ['pids' => static::OPTION]);
      return;
    }

    if ($filename === '-') {
      $this->logDebug('Using STDIN.');
    }
    else {
      if (!file_exists($filename)) {
        throw new CommandError(\dt('The %opt file (%filename) does not appear to exist.', [
          '%opt' => static::OPTION,
          '%filename' => $filename,
        ]));
      }
      elseif (!is_file(realpath($filename))) {
        throw new CommandError(\dt('The %opt file (%filename) does not appear to be a regular file.', [
          '%opt' => static::OPTION,
          '%filename' => $filename,
        ]));
      }
      elseif (!is_readable($filename)) {
        throw new CommandError(\dt('The %opt file (%filename) does not appear to be readable.', [
          '%opt' => static::OPTION,
          '%filename' => $filename,
        ]));
      }
      $this->logDebug('{opt} file ({filename}) appears to be good to go.', [
        'opt' => static::OPTION,
        'filename' => $filename,
      ]);
    }
  }

  protected function logDebug(...$args) {
    if ($this->debug) {
      $this->logger->debug(...$args);
    }
  }

  /**
   * Check that the file.
   *
   * @hook pre-command @foxml-pid-filterer
   */
  public function preCommand(CommandData $commandData) {
    // TODO: Configure the service that actually does the filtering.
  }

  /**
   * Check that the file.
   *
   * @hook post-command @foxml-pid-filterer
   */
  public function postCommand($result, CommandData $commandData) {
    // TODO: Unconfigure the service, to avoid potentially escaping our scope
    // should commands be run sequentially inside the same kernel.
    // TODO: Check that all the PIDs provided have been encountered during
    // whatever process... logging those that have not been encountered
    // somewhere?
  }

}
