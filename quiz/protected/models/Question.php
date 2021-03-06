<?php

/**
 * This is the model class for table "quiz_questions".
 *
 * The followings are the available columns in table 'quiz_questions':
 * @property integer $question_id
 * @property integer $section_id
 * @property string $title
 * @property string $text
 * @property string $theme
 * @property string $type
 *
 * The followings are the available model relations:
 * @property QuizSections $section
 * @property QuizQuestionsAnswers[] $quizQuestionsAnswers
 */
class Question extends CActiveRecord
{
    const TYPE_ONECHOICE   = 0;
    const TYPE_MULTICHOICE = 1;
    const TYPE_FREEFORM    = 2;
    const TYPE_POLL        = 3;

    public $answers_data;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Question the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'quiz_questions';
    }

    /**
     * Has right answer flag
     *
     * @return boolean
     */
    public function hasRightAnswer()
    {
        return in_array($this->type, array(self::TYPE_ONECHOICE, self::TYPE_MULTICHOICE));
    }

    /**
     * Validate question result flag
     *
     * @param mixed $result result
     *
     * @return boolean
     */
    public function isValidResult($result)
    {
        $result = (array) $result;
        return $this->_compareArrays($result, $this->getRightAnswers());
    }

   /**
    * Compare arrays
    *
    * @param Array $array1 array to compare
    * @param Array $array2 array to compare
    *
    * @return bool
    */
    protected function _compareArrays(Array $array1, Array $array2)
    {
        sort($array1);
        sort($array2);
        return ($array1 == $array2);
    }

    /**
     * Get question right answers
     *
     * @return array
     */
    public function getRightAnswers()
    {
        $criteria = new CDbCriteria;
        $criteria->select = 't.answer_id';
        $criteria->condition = 't.is_correct = :is_correct AND t.question_id = :question_id';
        $criteria->params = array(
            ':is_correct'  => QuestionsAnswer::IS_CORRECT, 
            ':question_id' => $this->question_id,
            );
        $answers = array();
        $result =  QuestionsAnswer::model()->findAll($criteria);
        foreach ($result as $item) {
            $answers[] = $item->answer_id;
        }
        return $answers;
    }

    /**
     * Check if answer is selected
     *
     * @param  QuestionsAnswer $answer answer
     * @param  Array           $result result
     *
     * @return boolean
     */
    public function isSelectedAnswer(QuestionsAnswer $answer, Array $result)
    {
        if (!isset($result[$this->question_id])) {
            return false;
        }
        $result = $result[$this->question_id];
        if (!is_array($result)) {
            return ($answer->answer_id == $result);
        }
        return (in_array($answer->answer_id, $result));
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('section_id, title', 'required'),
            array('section_id, position', 'numerical', 'integerOnly'=>true),
            array('theme', 'length', 'max'=>500),
            array('title', 'length', 'max'=>255),
            array('type', 'length', 'max'=>1),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('question_id, section_id, title, theme, type, text', 'safe'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'section' => array(self::BELONGS_TO, 'Section', 'section_id'),
            'answers' => array(self::HAS_MANY, 'QuestionsAnswer', 'question_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'question_id' => 'Question',
            'section_id' => 'Test',
            'title' => 'Title',
            'text' => 'Text',
            'theme' => 'Theme',
            'type' => 'Type',
            'position' => 'Position',
        );
    }

    /**
     * Get free form question answer
     *
     * @param array $result result
     *
     * @return string
     */
    public function getAnswerText($result)
    {
        if (!isset($result[$this->question_id])) {
            return false;
        }
        return $result[$this->question_id];
    }

    /**
     * Get question type option
     *
     * @return array
     */
    public function getTypeOptions()
    {
        return array(
                self::TYPE_ONECHOICE   => 'One choice',
                self::TYPE_MULTICHOICE => 'Multiple choice',
                self::TYPE_FREEFORM    => 'Free form',
                self::TYPE_POLL        => 'Poll'
            );
    }

    /**
     * Get type of question string
     *
     * @return string
     */
    public function getTypeOptionValue()
    {
        $options = $this->getTypeOptions();
        if (array_key_exists($this->type, $options)) {
            return $options[$this->type];
        }
        return null;
    }

    /**
     * Validate post answers data
     * 
     * @return bool
     */
    public function validateAnswersData($data)
    {
        if ($this->type != self::TYPE_ONECHOICE && $this->type != self::TYPE_MULTICHOICE) {
            return true;
        }
        $correctCount = 0;
        foreach ($data as $answer) {
            $isCorrect = (isset($answer['is_correct'])) ? $answer['is_correct'] : 0;
            $isDeleted = (isset($answer['deleted']) && $answer['deleted'] == 1);
            if ($isCorrect && !$isDeleted) {
                $correctCount++;
            }
        }
        if ($correctCount == 0) {
            $this->addError('answers', 'One of answers should be marked as correct!');
        }
        if ($correctCount > 1 && $this->type == self::TYPE_ONECHOICE) {
            $this->addError('answers', 'For this type of question only one answer could be marked as correct!');
        }
        return true;
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('question_id',$this->question_id);
        $criteria->compare('section_id',$this->section_id);
        // $criteria->with = 'section';
        // $criteria->compare('section', $this->section->title);
        $criteria->compare('title',$this->title,true);
        $criteria->compare('theme',$this->theme,true);
        $criteria->compare('type',$this->type,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }


    /**
     * This method is invoked before validation starts.
     * The default implementation calls {@link onBeforeValidate} to raise an event.
     * You may override this method to do preliminary checks before validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     * @return boolean whether validation should be executed. Defaults to true.
     * If false is returned, the validation will stop and the model is considered invalid.
     */
    protected function beforeValidate()
    {
        if ($this->answers_data) {
            $this->validateAnswersData($this->answers_data);
        }
        return parent::beforeValidate();
    }

    protected function afterSave()
    {
        if ($this->theme) {
            Theme::model()->addQuestionTheme($this);
        }
        return parent::afterSave();
    }
}