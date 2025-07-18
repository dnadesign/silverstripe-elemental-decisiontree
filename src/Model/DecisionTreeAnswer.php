<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Model;

use DNADesign\SilverStripeElementalDecisionTree\Forms\HasOneSelectOrCreateField;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;

class DecisionTreeAnswer extends DataObject
{
    private static array $db = [
        'Title' => 'Varchar(255)',
        'Sort' => 'Int',
    ];

    private static array $has_one = [
        'Question' => DecisionTreeStep::class,
        'ResultingStep' => DecisionTreeStep::class,
    ];

    private static array $summary_fields = [
        'ID' => 'ID',
        'Title' => 'Title',
        'ResultingStep.Title' => 'Resulting Step',
    ];

    private static string $table_name = 'DecisionTreeAnswer';

    private static string $default_sort = 'Sort ASC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Remove un-necessary fields
        $fields->removeByName('ResultingStepID');
        $fields->removeByName('Sort');

        // Update Parent Question
        $question = $fields->dataFieldByName('QuestionID');
        $question->setTitle('Answer for');
        $fields->insertBefore('Title', $question);

        if ($this->IsInDB()) {
            // Set up Step Selector
            $availableStepsID = DecisionTreeStep::get_orphans()->column('ID');

            if ($this->ResultingStep()->exists()) {
                array_push($availableStepsID, $this->ResultingStepID);
            }
            $steps = [];
            if ($availableStepsID) {
                $steps = DecisionTreeStep::get()->filter('ID', $availableStepsID)->map();
            }

            $stepSelector = HasOneSelectOrCreateField::create(
                $this,
                'ResultingStep',
                'If selected, go to',
                $steps,
                $this->ResultingStep(),
                $this
            );

            $fields->addFieldToTab('Root.Main', $stepSelector);
        } else {
            $info = LiteralField::create('info', sprintf(
                '<p class="message info notice">%s</p>',
                'Save this answer in order to add a following step.'
            ));

            $fields->addFieldToTab('Root.Main', $info);
        }

        return $fields;
    }

    public function canCreate($member = null, $context = [])
    {
        return singleton(ElementDecisionTree::class)->canCreate($member);
    }

    public function canView($member = null)
    {
        return singleton(ElementDecisionTree::class)->canCreate($member);
    }

    public function canEdit($member = null)
    {
        return singleton(ElementDecisionTree::class)->canCreate($member);
    }

    /**
     * Can only delete an answer that doesn't have a dependant question.
     *
     * @param null|mixed $member
     */
    public function canDelete($member = null)
    {
        $canDelete = singleton(ElementDecisionTree::class)->canDelete($member);

        return $canDelete && !$this->ResultingStep()->exists();
    }

    /**
     * Used as breadcrumbs on the parent Step.
     */
    public function TitleWithQuestion(): ?string
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
     * then append its edit url to the edit url of its parent question.
     */
    public function getCMSEditLink(): ?string
    {
        if ($this->Question()->exists()) {
            $origin = $this->Question()->getTreeOrigin();

            if ($origin) {
                $root = $origin->ParentElement();

                if ($root) {
                    return Controller::join_links(
                        $root->CMSEditFirstStepLink(),
                        $this->Question()->getRecursiveEditPath(),
                        $this->getRecursiveEditPathForSelf()
                    );
                }
            }
        }

        return parent::getCMSEditLink();
    }

    /**
     * Construct the link tp create a new ResultingStep for this answer.
     */
    public function CMSAddStepLink(): string
    {
        return Controller::join_links(
            $this->getCMSEditLink(),
            'itemEditForm/field/ResultingStep/item/new'
        );
    }

    /**
     * Recursively construct the link to edit this object.
     */
    public function getRecursiveEditPath(): string
    {
        $path = sprintf('ItemEditForm/field/Answers/item/%s/', $this->ID);

        if ($this->Question()->exists()) {
            $path = Controller::join_links(
                $path,
                $this->Question()->getRecursiveEditPath()
            );
        }

        return $path;
    }

    /**
     * Return only the url segment to edit this object.
     */
    public function getRecursiveEditPathForSelf(): string
    {
        return sprintf('ItemEditForm/field/Answers/item/%s/', $this->ID);
    }
}
