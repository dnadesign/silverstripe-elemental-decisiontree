<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Forms;

use DNADesign\SilverStripeElementalDecisionTree\Model\DecisionTreeStep;
use SilverStripe\Forms\FormField;

/**
 * This form field is used to display the DecisionTree in the edit form of any
 * ElementDecisionTree or DecisionTreeStes. It provides a visual way of
 * navigating the tree as well as links to edit/add steps.
 */
class DecisionTreeStepPreview extends FormField
{
    protected ?DecisionTreeStep $step = null;

    public function __construct($name, ?DecisionTreeStep $step = null)
    {
        $this->step = $step;
        parent::__construct($name);
    }

    public function getStep(): ?DecisionTreeStep
    {
        return $this->step;
    }

    public function setStep(?DecisionTreeStep $step): static
    {
        $this->step = $step;

        return $this;
    }
}
