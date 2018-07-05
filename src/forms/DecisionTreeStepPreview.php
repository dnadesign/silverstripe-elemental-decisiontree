<?php

/**
* This form field is used to display the DecisionTree in the edit form
* of any ElementDecisionTree or DecisionTreeStep
* It provides a visual way of navigating the tree
* as well as links to edit/add steps
*/

class DecisionTreeStepPreview extends FormField {

	protected $step = null;

	public function __construct($name, $step = null)
	{
		$this->step = $step;
		parent::__construct($name);
	}

	public function getStep()
	{
		return $this->step;
	}

	public function setStep($step)
	{
		$this->step = $step;
		return $this;
	}
}