<?php

class ElementDecisionTree extends BaseElement
{
	private static $title = "Decision Tree";

	private static $description = "Display a decision tree with questions and results";

	private static $enable_title_in_template = true;

	private static $db = [
		'Introduction' => 'HTMLText'
	];

	private static $has_one = [
		'FirstStep' => 'DecisionTreeStep'
	];

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();

		$introduction = $fields->dataFieldByName('Introduction');
		$introduction->setRows(4);

		$fields->removeByName('FirstStepID');
		$stepSelector = HasOneSelectOrCreateField::create('FirstStep', 'First Step', DecisionTreeStep::get_initial_steps()->map(), $this->FirstStep(), $this);

		$fields->addFieldToTab('Root.Main', $stepSelector);

		$fields->addFieldToTab('Root.Tree', DecisionTreeStepPreview::create('Tree', $this->FirstStep()));

		return $fields;
	}

	/**
	* Builds the Edit Link to the FirstStep of this element
	*
	* @return String
	*/
	public function CMSEditFirstStepLink()
	{
		$page = $this->getPage();
		$firstStep = $this->FirstStep();

		if (!$page || !$page->exists() || !$firstStep->exists()) return null;

        return Controller::join_links(
            singleton('CMSPageEditController')->Link('EditForm'),
            $page->ID,
            'field/ElementArea/item/',
            $this->ID,
            'ItemEditForm/field/FirstStep/item',
            $this->FirstStep()->ID
        );
	}
}

class ElementDecisionTree_Controller extends BaseElement_Controller {

	private static $allowed_actions = [
		'getNextStepForAnswer'
	];

	public function init()
	{
		parent::init();

		// Requires javascript to be included too
		// But it is usually included in theme js
		Requirements::javascript(DECISION_TREE_PATH.'/js/decision-tree.src.js');
	}

	/**
	* Return the HTMl for the next step to be displayed
	* as well as the updated URL which includes the ids of the answers
	* leading to this next step to be returned
	*
	* @param stepanswerid (POST)
	* @return json
	*/
	public function getNextStepForAnswer()
	{
		$answerID = $this->getRequest()->postVar('stepanswerid');
		if (!$answerID) return $this->httpError(404, 'No answer ID found.');

		$answer = DecisionTreeAnswer::get()->byID($answerID);
		if (!$answer || !$answer->exists()) return $this->httpError(404, $this->renderError('An error has occurred, please reload the page and try again!'));

		$nextStep = $answer->ResultingStep();
		if (!$nextStep || !$nextStep->exists()) return $this->httpError(404, $this->renderError('An error has occurred, please reload the page and try again!'));

		$html = $this->customise(new ArrayData(['Step' => $nextStep]))->renderWith('DecisionTreeStep');
		$pathway = $nextStep->getAnswerPathway();
		$nextURL = Controller::join_links($this->getParentController()->absoluteLink(), '?decisionpathway='.implode(',', $pathway));

		if ($this->getRequest()->isAjax()) {
			$data = [
				'html' => $html->forTemplate(),
				'nexturl' => $nextURL
			];

			return json_encode($data);
		}

		return $html;
	}

	/**
	* Returns an array of DecisionStepID from the URL param
	* in order to display the same question when we reload the page
	*
	* @return Array
	*/
	public function getInitialPathway()
	{
		$ids = $this->getParentController()->getRequest()->getVar('decisionpathway');
		if ($ids && is_string($ids)) {
			return explode(',', $ids);
		}

		return null;
	}

	/**
	* Check if an answer should be selected by default
	* ie. The question depending on it is displayed
	*
	* @return Boolean
	*/
	public function getIsAnswerSelected($answerID)
	{
		if ($pathway = $this->getInitialPathway()) {
			return in_array($answerID, $pathway);
		}

		return false;
	}

	/**
	* Gets the next step to be displayed in regards to the selected answer.
	* Used by template to display all the relevant steps from the URL
	*
	* @return DecisionTreeStep
	*/
	public function getNextStepFromSelectedAnswer($stepID)
	{
		$step = DecisionTreeStep::get()->byID($stepID);
		if ($step->exists()) {
			foreach($step->Answers() as $answer) {
				if ($this->getIsAnswerSelected($answer->ID)) {
					if ($nextStep = $answer->ResultingStep()) {
						return $nextStep;
					}
				}
			}
		}

		return null;
	}

	/**
	* Template returned via ajax in case
	* of an error occuring
	*
	* @return String
	*/
	public function renderError($message = '')
	{
		return sprintf('<div class="step step--error">
			<hr class="partial_green_border">
			<div class="step-form">
				<span class="step-title">Sorry!</span>
				<span class="step-content"><p>%s</p></span>
			</div>
		</div>', $message);
	}

}
