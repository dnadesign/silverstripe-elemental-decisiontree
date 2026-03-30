<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Extensions;

use DNADesign\SilverStripeElementalDecisionTree\Model\DecisionTreeAnswer;
use DNADesign\SilverStripeElementalDecisionTree\Model\DecisionTreeStep;
use DNADesign\SilverStripeElementalDecisionTree\Model\ElementDecisionTree;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class ElementDecisionTreeController extends Extension
{

    private static $allowed_actions = [
        'getNextStepForAnswer'
    ];

    /**
     * Path to the JS file to load. Set to empty string to disable.
     * Supports any path compatible with Requirements::javascript().
     *
     * @config
     * @var string
     */
    private static $javascript = 'dnadesign/silverstripe-elemental-decisiontree:javascript/decision-tree.src.js';

    /**
     * Path to a CSS file to load. Set to empty string to disable.
     *
     * @config
     * @var string
     */
    private static $css = 'dnadesign/silverstripe-elemental-decisiontree:css/decisiontree.css';

    /**
     * Whether to automatically detect if the current page has a decision
     * tree element before loading assets. Set to false to load assets
     * on all pages regardless.
     *
     * @config
     * @var bool
     */
    private static $auto_detect = true;

    /**
     * Load decision tree assets based on configuration.
     * By default, auto-detects whether the current page contains
     * an ElementDecisionTree element before loading assets.
     */
    public function onAfterInit()
    {
        $config = $this->owner->config();
        $javascript = $config->get('javascript');
        $css = $config->get('css');

        if (!$javascript && !$css) {
            return;
        }

        if ($config->get('auto_detect')) {
            if (!$this->owner->hasMethod('data')) {
                return;
            }

            $record = $this->owner->data();

            if (!$record || !$record->hasMethod('ElementalArea')) {
                return;
            }

            $area = $record->ElementalArea();

            if (!$area || !$area->exists()) {
                return;
            }

            $hasDecisionTree = $area->Elements()
                ->filter('ClassName', ClassInfo::subclassesFor(ElementDecisionTree::class))
                ->exists();

            if (!$hasDecisionTree) {
                return;
            }
        }

        if ($javascript) {
            Requirements::javascript($javascript);
        }

        if ($css) {
            Requirements::css($css);
        }
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
        $answerID = $this->owner->getRequest()->postVar('stepanswerid');

        if (!$answerID) {
            return $this->owner->httpError(404, 'No answer ID found.');
        }

        $answer = DecisionTreeAnswer::get()->byID($answerID);

        if (!$answer || !$answer->exists()) {
            return $this->owner->httpError(
                404,
                $this->renderError('An error has occurred, please reload the page and try again!')
            );
        }

        $nextStep = $answer->ResultingStep();

        if (!$nextStep || !$nextStep->exists()) {
            return $this->owner->httpError(
                404,
                $this->renderError('An error has occurred, please reload the page and try again!')
            );
        }

        $html = $this->owner->customise(new ArrayData([
            'Step' => $nextStep,
            'Controller' => $this->owner
        ]))->renderWith('DNADesign\SilverStripeElementalDecisionTree\Model\DecisionTreeStep');

        $pathway = $nextStep->getAnswerPathway();

        $nextURL = Controller::join_links(
            $this->owner->AbsoluteLink(), '?decisionpathway='.implode(',', $pathway)
        );

        if ($this->owner->getRequest()->isAjax()) {
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
        $ids = $this->owner->getRequest()->getVar('decisionpathway');

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
    * Template returned via ajax in case of an error occuring.
    *
    * @param string
    *
    * @return string
    */
    protected function renderError($message = '')
    {
        return sprintf(
            '<div class="step step--error">
                <hr class="partial_green_border">
                <div class="step-form">
                    <span class="step-title">Sorry!</span>
                    <span class="step-content"><p>%s</p></span>
                </div>
            </div>',
            $message
        );
    }

}
