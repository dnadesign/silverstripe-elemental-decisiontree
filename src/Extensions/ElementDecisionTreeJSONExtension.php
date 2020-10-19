<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Extensions;

use DNADesign\SilverStripeElementalDecisionTree\Model\ElementDecisionStep;
use DNADesign\SilverStripeElementalDecisionTree\Model\ElementDecisionAnswer;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Parsers\ShortcodeParser;

class ElementDecisionTreeJSONExtension extends DataExtension
{

    public function getDecisionTreeJSON()
    {
        $data = $this->getTreeData();

        return json_encode(array_merge(
            [
                'blockTitle' => $this->owner->Title,
                'blockIntro' => ShortcodeParser::get('default')->parse($this->owner->Introduction),
            ],
            $data
        ));
    }

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

        return $data;
    }

    private function collectStepData($step, &$data)
    {
        // add step id
        $data['steps'][$step->ID] = $step->toJSONData();

        // collect answer ids
        $this->collectAnswersData($step->Answers(), $data);
    }

    private function collectAnswersData($answers, &$data)
    {
        // loop through answers
        foreach ($answers as $answer) {
            // push answer id into array
            $data['answers'][$answer->ID] = $answer->toJSONData();

            // check for step
            $step = $answer->ResultingStep();

            // go to collect step data
            if ($step->exists()) {
                $this->collectStepData($step, $data);
            }
        }
    }
}
