<?php

class DecisionTreeAnswer extends DataObject
{
	private static $db = [
		'Title' => 'Varchar(255)',
		'Sort' => 'Int'
	];

	private static $has_one = [
		'Question' => 'DecisionTreeStep',
		'ResultingStep' => 'DecisionTreeStep'
	];

	private static $summary_fields = [
		'ID' => 'ID',
		'Title' => 'Title',
		'ResultingStep.Title' => 'Resulting Step'
	];

	private static $default_sort = 'Sort ASC';

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();

		// Remove un-necessary fields
		$fields->removeByName('ResultingStepID');
		$fields->removeByName('Sort');

		// Update Parent Question
		$question = $fields->dataFieldByName('QuestionID');
		$question->setTitle('Answer for');
		$fields->insertBefore($question, 'Title');

		if ($this->IsInDB()) {
			// Set up Step Selector
			$availableStepsID = DecisionTreeStep::get_orphans()->column('ID');
			if ($this->ResultingStep()->exists()) {
				array_push($availableStepsID, $this->ResultingStepID);
			}

			$stepSelector = HasOneSelectOrCreateField::create('ResultingStep', 'If selected, go to', DecisionTreeStep::get()->filter('ID', $availableStepsID)->map(), $this->ResultingStep(), $this);

			$fields->addFieldsToTab('Root.Main', $stepSelector);
		} else {
			$info = LiteralField::create('info', sprintf('<p class="message info notice">%s</p>', 'Save this answer in order to add a following step.'));
			$fields->addFieldToTab('Root.Main', $info);
		}

		return $fields;
	}

	/**
	* Permissions
	*/
	public function canCreate($member = null) 
	{
		return singleton('ElementDecisionTree')->canCreate($member);
	}

	public function canView($member = null)
	{
		return singleton('ElementDecisionTree')->canCreate($member);
	}

	public function canEdit($member = null) 
	{
		return singleton('ElementDecisionTree')->canCreate($member);
	}

	/**
	* Can only delete an answer that doesn't have a dependant question
	*/
	public function canDelete($member = null)
	{
		$canDelete = singleton('ElementDecisionTree')->canDelete($member);
		return ($canDelete && !$this->ResultingStep()->exists());
	}

	/**
	* Used as breadcrumbs on the parent Step
	*
	* @return String
	*/
	public function TitleWithQuestion()
	{
		$title = $this->Title;
		if ($this->Question()->exists()) {
			$title = sprintf('%s > %s', $this->Question()->Title, $title);
		}
		return $title;
	}

	/**
	* Create a link that allowd to edit this object in the CMS
	* To do this, it first finds its parent question
	* then rewind the tree up to the element
	* then append its edit url to the edit url of its parent question
	*
	* @return String
	*/
	public function CMSEditLink() {
		if ($this->Question()->exists()) {
			$origin = $this->Question()->getTreeOrigin();
			if ($origin) {
				$root = $origin->ParentElement();
				if ($root) {
					$url = Controller::join_links($root->CMSEditFirstStepLink(), $this->Question()->getRecursiveEditPath(), $this->getRecursiveEditPathForSelf());
					return $url;
				}
			}
		}
	}

	/**
	* Construct the link tp create a new ResultingStep for this answer
	*
	* @return String
	*/
	public function CMSAddStepLink()
	{
		$link = Controller::join_links($this->CMSEditLink(), '/itemEditForm/field/ResultingStep/item/new');
		return $link;
	}

	/**
	* Recursively construct the link to edit this object
	*
	* @return String
	*/
	public function getRecursiveEditPath()
	{
		$path = sprintf('/ItemEditForm/field/Answers/item/%s/', $this->ID);

		if ($this->Question()->exists()) {
			$path .= $this->Question()->getRecursiveEditPath();
		}

		return $path;
	}

	/**
	* Return only the url segment to edit this object
	*
	* @return String
	*/
	public function getRecursiveEditPathForSelf()
	{
		return sprintf('/ItemEditForm/field/Answers/item/%s/', $this->ID);
	}
}