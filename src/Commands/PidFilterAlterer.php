<?php

namespace Drupal\foxml\Commands;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\CommandInfoAltererInterface;
use Psr\Log\LoggerInterface;

/**
 * Dynamically facilitate filtering.
 *
 * Annotation injection adapter from our "--user" work.
 *
 * @see https://github.com/discoverygarden/dgi_i8_helper/blob/2.x/src/Commands/UserWrappingAlterer.php
 */
class PidFilterAlterer implements CommandInfoAltererInterface {

  /**
   * The annotation we use to handle filtering.
   */
  const ANNO = 'foxml-pid-filterer';

  /**
   * The set of commands we wish to alter.
   */
  const COMMANDS = [
    'dgi-migrate:import',
    'dgi-migrate:rollback',
  ];

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
   * {@inheritdoc}
   */
  public function alterCommandInfo(CommandInfo $commandInfo, $commandFileInstance) {
    if (!$commandInfo->hasAnnotation(static::ANNO) && in_array($commandInfo->getName(), static::COMMANDS)) {
      if ($this->debug) {
        $this->logger->debug('Adding annotation "@annotation" to @command.', [
          '@annotation' => static::ANNO,
          '@command' => $commandInfo->getName(),
        ]);
      }
      $commandInfo->addAnnotation(static::ANNO, 'PID filtering.');
    }
  }

}
