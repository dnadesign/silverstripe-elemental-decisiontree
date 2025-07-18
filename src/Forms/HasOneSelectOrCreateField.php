<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Forms;

use SilverShop\HasOneField\GridFieldHasOneEditButton;
use SilverShop\HasOneField\HasOneButtonField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;

/**
 * This a wrapper for a slightly enhanced user experience when using the
 * HasOneButtonField class.
 * By default, the HasOneButtonField only allow to create or edit the has_one object
 * This class adds a dropdown to allow to select another object or remove the relation
 * without deleting the object itself.
 * Both fields are wrapped in a CompositeField.
 *
 * @param Name|string of the has_one relationship
 * @param string|Title of the drodpwon field
 * @param FieldList or Array | Options listed in the dropdown
 * @param Current|object has_one object, if exists
 * @param object|The object calling this field, required by HasOneButtonField
 */
class HasOneSelectOrCreateField extends CompositeField
{
    protected DropdownField $dropdown;

    protected HasOneButtonField $gridfield;

    public function __construct($record, $name, $title, $options, $current, $parent)
    {
        $this->name = $name;
        $this->title = $title;

        $gridfield = HasOneButtonField::create($record, $name, $name);
        $this->gridfield = $gridfield;

        $dropdown = DropdownField::create($this->getRelationName(), $title, $options, $current);
        $this->dropdown = $dropdown;

        // Modify button name
        $singleton = singleton($gridfield->getModelClass());
        $label = FormField::name_to_label($singleton->i18n_singular_name());
        $gridfieldConfig = $gridfield->getConfig();
        $button = $gridfieldConfig->getComponentByType(GridFieldHasOneEditButton::class);

        if ($current && $current->exists()) {
            $name = ($current->Title) ? $current->Title : $current->Name;
            if ($name) {
                $button->setButtonName('Edit ' . FormField::name_to_label($name));
            }
        } else {
            $button->setButtonName('Create ' . $label);
        }

        // Set Empty String on dropdown
        $dropdown->setEmptyString('Select ' . $label);

        parent::__construct([$this->dropdown, $this->gridfield]);
    }

    public function getRelationName(): string
    {
        return $this->name . 'ID';
    }
}
