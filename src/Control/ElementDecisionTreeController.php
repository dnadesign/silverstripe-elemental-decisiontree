<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Control;

use SilverStripe\Control\Controller;
use SilverStripe\View\ArrayData;

class ElementDecisionTreeController extends Controller
{

    private static $allowed_actions = [
        'getNextStepForAnswer'
    ];

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

        if (!$answerID) {
            return $this->httpError(404, 'No answer ID found.');
        }

        $answer = DecisionTreeAnswer::get()->byID($answerID);

        if (!$answer || !$answer->exists()) {
            return $this->httpError(404, $this->renderError('An error has occurred, please reload the page and try again!'));
        }

        $nextStep = $answer->ResultingStep();

        if (!$nextStep || !$nextStep->exists()) {
            return $this->httpError(404, $this->renderError('An error has occurred, please reload the page and try again!'));
        }

        $html = $this->customise(new ArrayData([
            'Step' => $nextStep
        ]))->renderWith('Includes\DecisionTreeStep');

        $pathway = $nextStep->getAnswerPathway();

        $nextURL = Controller::join_links(
            $this->getParentController()->absoluteLink(), '?decisionpathway='.implode(',', $pathway)
        );

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
