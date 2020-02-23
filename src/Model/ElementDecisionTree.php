<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Model;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\SilverStripeElementalDecisionTree\Forms\HasOneSelectOrCreateField;
use DNADesign\SilverStripeElementalDecisionTree\Forms\DecisionTreeStepPreview;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\CMSPageEditController;

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
}
