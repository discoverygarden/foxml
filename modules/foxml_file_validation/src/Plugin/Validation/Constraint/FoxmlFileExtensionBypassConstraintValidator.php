<?php

namespace Drupal\foxml_file_validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Validation\ConstraintValidatorFactory;
use Drupal\file\Plugin\Validation\Constraint\BaseFileConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Bypasses extension validation for files in the foxml:// scheme.
 */
class FoxmlFileExtensionBypassConstraintValidator extends BaseFileConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constraint validator factory.
   *
   * @var \Drupal\Core\Validation\ConstraintValidatorFactory
   */
  protected ConstraintValidatorFactory $constraintValidatorFactory;

  /**
   * Constructor.
   */
  public function __construct(
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected ClassResolverInterface $classResolver,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function validate(mixed $value, Constraint $constraint) : void {
    $file = $this->assertValueIsFile($value);
    if (!$constraint instanceof FoxmlFileExtensionBypassConstraint) {
      throw new UnexpectedTypeException($constraint, FoxmlFileExtensionBypassConstraint::class);
    }

    if ($this->streamWrapperManager::getScheme($file->getFileUri()) === 'foxml') {
      return;
    }

    $wrapped_constraint = $constraint->getWrappedConstraint();
    $wrapped_validator = $this->getConstraintValueFactory()->getInstance($wrapped_constraint);
    $wrapped_validator->initialize($this->context);
    $wrapped_validator->validate($value, $wrapped_constraint);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) : static {
    return new static(
      $container->get('stream_wrapper_manager'),
      $container->get('class_resolver'),
    );
  }

  /**
   * Lazily instantiate constraint validator factory.
   *
   * @return \Drupal\Core\Validation\ConstraintValidatorFactory
   *   Constraint validator factory service.
   */
  protected function getConstraintValueFactory() : ConstraintValidatorFactory {
    return $this->constraintValidatorFactory ??= new ConstraintValidatorFactory($this->classResolver);
  }

}
