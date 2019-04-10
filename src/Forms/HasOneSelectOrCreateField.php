<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Forms;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverShop\HasOneField\HasOneButtonField;
use SilverShop\HasOneField\GridFieldHasOneEditButton;
/**
* This a wrapper for a slightly enhanced user experience when using the
* HasOneButtonField class.
* By default, the HasOneButtonField only allow to create or edit the has_one object
* This class adds a dropdown to allow to select another object or remove the relation
* without deleting the object itself.
* Both fields are wrapped in a CompositeField.
*
* @param String | Name of the has_one relationship
* @param String | Title of the drodpwon field
* @param FieldList or Array | Options listed in the dropdown
* @param Object | Current has_one object, if exists
* @param Object | The object calling this field, required by HasOneButtonField
*/

class HasOneSelectOrCreateField extends CompositeField
{

    protected $dropdown;

    protected $gridfield;

    public function __construct($record, $name, $title, $options, $current = null, $parent)
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
                $button->setButtonName('Edit '.FormField::name_to_label($name));
            }
        } else {
            $button->setButtonName('Create '.$label);
        }

        // Set Empty String on dropdown
        $dropdown->setEmptyString('Select '.$label);

        parent::__construct(array($this->dropdown, $this->gridfield));
    }

    public function getRelationName()
    {
        return $this->name.'ID';
    }

}
