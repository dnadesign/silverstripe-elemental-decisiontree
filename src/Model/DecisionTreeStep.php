<?php

namespace DNADesign\SilverStripeElementalDecisionTree\Model;

use DNADesign\SilverStripeElementalDecisionTree\Forms\DecisionTreeStepPreview;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\ReadOnlyField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use UncleCheese\DisplayLogic\Forms\Wrapper as DisplayLogicWrapper;

class DecisionTreeStep extends DataObject
{
    private static array $db = [
        'Title' => 'Varchar(255)',
        'Type' => "Enum('Question, Result')",
        'Content' => 'HTMLText',
        'HideTitle' => 'Boolean',
    ];

    private static array $has_many = [
        'Answers' => DecisionTreeAnswer::class . '.Question',
    ];

    private static array $owns = [
        'Answers',
    ];

    private static array $cascade_deletes = [
        'Answers',
    ];

    private static string $table_name = 'DecisionTreeStep';

    private static array $belongs_to = [
        'ParentAnswer' => DecisionTreeAnswer::class,
        'ParentElement' => ElementDecisionTree::class,
    ];

    private static array $summary_fields = [
        'ID' => 'ID',
        'Title' => 'Title',
    ];

    private static string $default_result_title = 'Our recommendation';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $content = $fields->dataFieldByname('Content');
        $content->setRows(4);

        $fields->removeByName('Answers');

        $fields->replaceField('Type', $type = OptionsetField::create('Type', 'Type', $this->dbObject('Type')->enumValues()));

        // Allow to hide the title only on Result
        $hideTitle = CheckboxField::create('HideTitle', 'Hide title');
        $hideTitle->displayIf('Type')->isEqualTo('Result')->end();
        $fields->insertAfter('Type', $hideTitle);

        if ($this->IsInDB()) {
            // Display Parent Answer
            if ($this->ParentAnswer()->exists()) {
                $parentAnswerTitle = ReadOnlyField::create('ParentAnswerTitle', 'Parent Answer', $this->ParentAnswer()->TitleWithQuestion());
                $fields->addFieldToTab('Root.Main', $parentAnswerTitle, 'Title');
            }

            // List answers
            $answerConfig = GridFieldConfig_RecordEditor::create();
            $answerConfig->addComponent(new GridFieldOrderableRows('Sort'));
            $answerGrid = GridField::create(
                'Answers',
                'Answers',
                $this->Answers(),
                $answerConfig
            );

            $fields->addFieldTotab('Root.Main', DisplayLogicWrapper::create($answerGrid)->displayUnless('Type')->isEqualTo('Result')->end());

            // Add Tree Preview
            // Note: cannot add it if the object is not in DB
            $fields->addFieldToTab('Root.Tree', DecisionTreeStepPreview::create('Tree', $this->getTreeOrigin()));
        }

        return $fields;
    }

    /**
     * Set default title on Result steps.
     */
    public function onBeforeWrite(): void
    {
        if ($this->Type == 'Result' && !$this->Title) {
            $this->Title = $this->config()->default_result_title;
        }

        parent::onBeforeWrite();
    }

    public function canCreate($member = null, $context = [])
    {
        return singleton(ElementDecisionTree::class)->canCreate($member, $context);
    }

    public function canView($member = null)
    {
        return singleton(ElementDecisionTree::class)->canCreate($member);
    }

    public function canEdit($member = null)
    {
        return singleton(ElementDecisionTree::class)->canCreate($member);
    }

    public function canDelete($member = null)
    {
        return singleton(ElementDecisionTree::class)->canDelete($member);
    }

    /**
     * Return a readable list of the answer title and the title of the question
     * which will be displayed if the answer is selected
     * Used for Gridfield.
     */
    public function getAnswerTreeForGrid(): DBField|DBHTMLText
    {
        $output = '';
        if ($this->Answers()->Count()) {
            foreach ($this->Answers() as $answer) {
                $output .= $answer->Title;
                if ($answer->ResultingStep()) {
                    $output .= ' => ' . $answer->ResultingStep()->Title;
                }
                $output .= '<br/>';
            }
        }

        return DBField::create_field('HTMLText', $output);
    }

    /**
     * Outputs an optionset to allow user to select an answer to the question.
     */
    public function getAnswersOptionset(): OptionsetField
    {
        $source = [];
        foreach ($this->Answers() as $answer) {
            $source[$answer->ID] = $answer->Title;
        }

        return OptionsetField::create('stepanswerid', '', $source)->addExtraClass('decisiontree-option');
    }

    /**
     * Return the DecisionAnswer rsponsible for displaying this step.
     */
    public function getParentAnswer(): ?DecisionTreeAnswer
    {
        return DecisionTreeAnswer::get()->filter('ResultingStepID', $this->ID)->first();
    }

    /**
     * Return the list of DecisionTreeAnswer ID
     * leading to this step being displayed.
     *
     * @param mixed $idList
     */
    public function getAnswerPathway(&$idList = []): array
    {
        if ($answer = $this->getParentAnswer()) {
            array_push($idList, $answer->ID);
            if ($question = $answer->Question()) {
                $question->getAnswerPathway($idList);
            }
        }

        return $idList;
    }

    /**
     * Return the list of DecisionTreeStep ID
     * leading to this step being displayed.
     *
     * @param mixed $idList
     */
    public function getQuestionPathway(&$idList = []): array
    {
        array_push($idList, $this->ID);
        if ($answer = $this->getParentAnswer()) {
            if ($question = $answer->Question()) {
                $question->getQuestionPathway($idList);
            }
        }

        return $idList;
    }

    /**
     * Builds an array of question and answers leading to this Step
     * Each entry is an array which key is either 'question' or 'answer'
     * and value is the ID of the object
     * Note: the array is in reverse order.
     *
     * @param mixed $path
     */
    public function getFullPathway(&$path = []): array
    {
        if ($answer = $this->getParentAnswer()) {
            array_push($path, ['question' => $this->ID]);
            array_push($path, ['answer' => $answer->ID]);
            if ($question = $answer->Question()) {
                $question->getFullPathway($path);
            }
        } else {
            array_push($path, ['question' => $this->ID]);
        }

        return $path;
    }

    /**
     * Find the very first DecisionStep in the tree.
     */
    public function getTreeOrigin(): ?DecisionTreeStep
    {
        $pathway = array_reverse($this->getQuestionPathway());

        return DecisionTreeStep::get()->byID($pathway[0]);
    }

    /**
     * Return this step position in the pathway
     * Used to number step on the front end.
     */
    public function getPositionInPathway(): int
    {
        $pathway = array_reverse($this->getFullPathway());
        // Pathway has both questions and answers
        // so need to retain ids of questions only
        $id = array_column($pathway, 'question');

        $pos = array_search($this->ID, $id);

        return ($pos === false) ? 0 : $pos + 1;
    }

    /**
     * Return a DataList of DecisionTreeStep that do not belong to a Tree.
     */
    public static function get_orphans(): SS_List
    {
        $orphans = DecisionTreeStep::get()->filterByCallback(function ($item) {
            return !$item->belongsToTree();
        });

        if (!$orphans->count()) {
            return new ArrayList();
        }

        return DecisionTreeStep::get()->filter('ID', $orphans->column('ID'));
    }

    /**
     * Return a DataList of all DecisionTreeStep that do not belong to an answer
     * ie. are the first child of a element.
     */
    public static function get_initial_steps(): ?SS_List
    {
        $initial = DecisionTreeStep::get()->filterByCallback(function ($item) {
            return !$item->belongsToAnswer();
        });

        if (!$initial->count()) {
            return new ArrayList();
        }

        return DecisionTreeStep::get()->filter([
            'ID' => $initial->column('ID'),
        ])->exclude('Type', 'Result');
    }

    public function belongsToTree(): bool
    {
        return $this->belongsToElement() || $this->belongsToAnswer();
    }

    public function belongsToElement(): bool
    {
        return ElementDecisionTree::get()->filter('FirstStepID', $this->ID)->Count() > 0;
    }

    public function belongsToAnswer(): bool
    {
        return $this->ParentAnswer() && $this->ParentAnswer()->exists();
    }

    /**
     * Checks if this object is currently being edited in the CMS
     * by comparing its ID with the one in the request.
     */
    public function IsCurrentlyEdited(): bool
    {
        $request = Controller::curr()->getRequest();
        $class = $request->param('FieldName');
        $currentID = $request->param('ID');

        $stepRelationships = ['ResultingStep', 'FirstStep'];

        if ($currentID && in_array($class, $stepRelationships)) {
            return $currentID == $this->ID;
        }

        return false;
    }

    /**
     * Create a link that allowd to edit this object in the CMS
     * To do this, it rewinds the tree up to the element
     * then append its edit url to the edit url of its parent question.
     */
    public function getCMSEditLink(): ?string
    {
        $origin = $this->getTreeOrigin();
        if ($origin) {
            $root = $origin->ParentElement();
            if ($root) {
                return Controller::join_links($root->CMSEditFirstStepLink(), $this->getRecursiveEditPath());
            }
        }

        return parent::getCMSEditLink();
    }

    /**
     * Build url to allow to edit this object.
     */
    public function getRecursiveEditPath(): string
    {
        $pathway = array_reverse($this->getFullPathway());
        unset($pathway[0]); // remove first question

        $url = '';
        foreach ($pathway as $step) {
            if (is_array($step) && !empty($step)) {
                $type = array_keys($step)[0];
                $id = $step[$type];

                if ($type == 'question') {
                    $url .= '/ItemEditForm/field/ResultingStep/item/' . $id;
                } elseif ($type == 'answer') {
                    $url .= '/ItemEditForm/field/Answers/item/' . $id;
                }
            }
        }

        return $url;
    }
}
