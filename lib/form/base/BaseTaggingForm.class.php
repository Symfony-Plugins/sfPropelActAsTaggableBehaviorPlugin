<?php

/**
 * Tagging form base class.
 *
 * @package    form
 * @subpackage tagging
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 8807 2008-05-06 14:12:28Z fabien $
 */
class BaseTaggingForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'             => new sfWidgetFormInputHidden(),
      'tag_id'         => new sfWidgetFormPropelSelect(array('model' => 'Tag', 'add_empty' => false)),
      'taggable_model' => new sfWidgetFormInput(),
      'taggable_id'    => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'             => new sfValidatorPropelChoice(array('model' => 'Tagging', 'column' => 'id', 'required' => false)),
      'tag_id'         => new sfValidatorPropelChoice(array('model' => 'Tag', 'column' => 'id')),
      'taggable_model' => new sfValidatorString(array('max_length' => 30, 'required' => false)),
      'taggable_id'    => new sfValidatorInteger(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('tagging[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'Tagging';
  }


}
