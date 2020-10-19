<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Model;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\SilverStripeElementalDecisionTree\Forms\HasOneSelectOrCreateField;
use DNADesign\SilverStripeElementalDecisionTree\Forms\DecisionTreeStepPreview;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\View\Parsers\ShortcodeParser;

class ElementDecisionTree extends BaseElement
{
    private static $title = "Decision Tree";

    private static $description = "Display a decision tree with questions and results";

    private static $enable_title_in_template = true;

    private static $icon = 'font-icon-flow-tree';

    private static $db = [
        'Introduction' => 'HTMLText'
    ];

    private static $has_one = [
        'FirstStep' => DecisionTreeStep::class
    ];

    private static $table_name = 'ElementDecisionTree';

    private static $inline_editable = false;

    public function getType()
    {
        return 'Decision Tree';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $introduction = $fields->dataFieldByName('Introduction');
        $introduction->setRows(4);

        $fields->removeByName('FirstStepID');
        $stepSelector = HasOneSelectOrCreateField::create(
            $this, 'FirstStep', 'First Step', DecisionTreeStep::get_initial_steps()->map(), $this->FirstStep(), $this
        );

        $fields->addFieldToTab('Root.Main', $stepSelector);

        $fields->addFieldToTab('Root.Tree', DecisionTreeStepPreview::create('Tree', $this->FirstStep()));

        return $fields;
    }

    /**
    * Builds the Edit Link to the FirstStep of this element
    *
    * @return string
    */
    public function CMSEditFirstStepLink()
    {
        $page = $this->getPage();
        $firstStep = $this->FirstStep();

        if (!$page || !$page->exists() || !$firstStep->exists()) return null;

        return Controller::join_links(
            singleton(CMSPageEditController::class)->Link('EditForm'),
            $page->ID,
            'field/ElementalArea/item/',
            $this->ID,
            'ItemEditForm/field/FirstStep/item',
            $this->FirstStep()->ID
        );
    }

    /**
     * To provide all the content block data as JSON to make javascript
     * based front ends easier to build.
     *
     * @return string
     */
    public function getBlockJSON()
    {
        return json_encode(array_merge(
            [
                'blockTitle' => $this->Title,
                'blockIntro' => ShortcodeParser::get('default')->parse($this->Introduction),
            ],
            $this->getTreeData()
        ));
    }

    /**
     * Helper to extract the step and answer data from the block.
     *
     * @return array
     */
    private function getTreeData()
    {
        $data = [
            'steps' => [],
            'answers' => [],
        ];

        // start from the top
        $first = $this->FirstStep();

        if (!$first->exists()) {
            return $data;
        }

        // now start our descent through the flow
        $this->collectStepData($first, $data);

        // provide way to inject extra data
        $this->extend('updateTreeData', $data);

        return $data;
    }

    /**
     * Helper to collect the data for a given step.
     *
     * @param DecisionTreeStep $step
     * @param array &$data
     * @return void
     */
    private function collectStepData($step, &$data)
    {
        // add step data into array
        $data['steps'][$step->ID] = $step->toJSONData();

        // collect answer data into the array
        $this->collectAnswersData($step->Answers(), $data);
    }

    /**
     * Helper to collect the data for the given answers.
     *
     * @param DataList $answers
     * @param array &$data
     * @return void
     */
    private function collectAnswersData($answers, &$data)
    {
        // loop through answers
        foreach ($answers as $answer) {
            // push answer data into array
            $data['answers'][$answer->ID] = $answer->toJSONData();

            // check for next step
            $step = $answer->ResultingStep();

            // recursively collect the next step data
            if ($step->exists()) {
                $this->collectStepData($step, $data);
            }
        }
    }
}
