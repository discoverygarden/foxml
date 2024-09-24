<?php

namespace Drupal\foxml_file_validation\Plugin\Validation\Constraint;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Validation\ConstraintManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Allow extensions for FOXML URIs to be ignored.
 *
 * @Constraint(
 *   id = "FoxmlFileValidationFileExtension",
 *   label = @Translation("FOXML File Validation, File Extension", context = "Validation"),
 *   type = "file"
 * )
 */
class FoxmlFileExtensionBypassConstraint extends Constraint implements ContainerFactoryPluginInterface {

  /**
   * The passed in options, to forward to the wrapped constraint.
   *
   * @var mixed|null
   */
  protected mixed $options;

  /**
   * Lazily instantiated wrapped constraint.
   *
   * @var \Symfony\Component\Validator\Constraint
   */
  protected Constraint $wrapped;

  /**
   * Constructor.
   */
  public function __construct(
    protected ConstraintManager $constraintManager,
    mixed $options = NULL,
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct([], $groups, $payload);
    $this->options = $options;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      constraintManager: $container->get('validation.constraint'),
      options: $configuration,
    );
  }

  /**
   * Get the wrapped constraint.
   *
   * @return \Symfony\Component\Validator\Constraint
   *   The wrapped constraint.
   */
  public function getWrappedConstraint() : Constraint {
    return $this->wrapped ??= $this->constraintManager->create('FoxmlFileValidationOriginalFileExtension', $this->options);
  }

}
