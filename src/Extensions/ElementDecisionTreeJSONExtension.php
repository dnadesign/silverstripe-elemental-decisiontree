<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Extensions;

use DNADesign\SilverStripeElementalDecisionTree\Model\ElementDecisionStep;
use DNADesign\SilverStripeElementalDecisionTree\Model\ElementDecisionAnswer;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Parsers\ShortcodeParser;

class ElementDecisionTreeJSONExtension extends DataExtension
{

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
                'blockTitle' => $this->owner->Title,
                'blockIntro' => ShortcodeParser::get('default')->parse($this->owner->Introduction),
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
        $first = $this->owner->FirstStep();

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
