<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Model;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\SilverStripeElementalDecisionTree\Forms\DecisionTreeStepPreview;
use DNADesign\SilverStripeElementalDecisionTree\Forms\HasOneSelectOrCreateField;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;

class ElementDecisionTree extends BaseElement
{
    private static string $title = 'Decision Tree';

    private static string $class_description = 'Display a decision tree with questions and results';

    private static bool $enable_title_in_template = true;

    private static string $icon = 'font-icon-flow-tree';

    private static array $db = [
        'Introduction' => 'HTMLText',
    ];

    private static array $has_one = [
        'FirstStep' => DecisionTreeStep::class,
    ];

    private static string $table_name = 'ElementDecisionTree';

    private static bool $inline_editable = false;

    public function getType()
    {
        return 'Decision Tree';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('FirstStepID');

        $introduction = $fields->dataFieldByName('Introduction');
        $introduction->setRows(4);

        if ($this->IsInDB()) {
            $stepSelector = HasOneSelectOrCreateField::create(
                $this,
                'FirstStep',
                'First Step',
                DecisionTreeStep::get_initial_steps()->map(),
                $this->FirstStep(),
                $this
            );

            $fields->addFieldToTab('Root.Main', $stepSelector);

            $fields->addFieldToTab('Root.Tree', DecisionTreeStepPreview::create('Tree', $this->FirstStep()));
        } else {
            $info = LiteralField::create('info', sprintf(
                '<p class="message info notice">%s</p>',
                'Save this decision tree in order to add the first step.'
            ));

            $fields->addFieldToTab('Root.Main', $info);
        }

        return $fields;
    }

    /**
     * Builds the Edit Link to the FirstStep of this element.
     */
    public function CMSEditFirstStepLink(): ?string
    {
        $page = $this->getPage();
        $firstStep = $this->FirstStep();

        if (!$page || !$page->exists() || !$firstStep->exists()) {
            return null;
        }

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
