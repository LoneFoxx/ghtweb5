<?php

/**
 * This is the model class for table "{{config}}".
 *
 * The followings are the available columns in table '{{config}}':
 * @property string      $id
 * @property string      $param
 * @property string      $value
 * @property string      $default
 * @property string      $label
 * @property string      $type
 *
 * The followings are the available model relations:
 * @property ConfigGroup $group
 */
class Config extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{config}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('param, value, default, label, type', 'required'),
            array('param, type', 'length', 'max' => 128),
            array('label', 'length', 'max' => 255),

            array('id, param, value, default, label, type', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array('group' => array(self::BELONGS_TO, 'ConfigGroup', 'id'));
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id'      => 'ID',
            'param'   => 'Param',
            'value'   => 'Value',
            'default' => 'Default',
            'label'   => 'Label',
            'type'    => 'Type',
        );
    }

    public function search()
    {
        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id, TRUE);
        $criteria->compare('param', $this->param, TRUE);
        $criteria->compare('value', $this->value, TRUE);
        $criteria->compare('default', $this->default, TRUE);
        $criteria->compare('label', $this->label, TRUE);
        $criteria->compare('type', $this->type, TRUE);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    public function getField()
    {
        switch($this->field_type)
        {
            case 'dropDownList':
                if($this->method && method_exists($this, $this->method))
                {
                    $params = $this->{$this->method}();
                }
                else
                {
                    $params = array(Yii::t('main', 'Нет'), Yii::t('main', 'Да'));
                }

                $htmlOptions = array('class' => 'form-control');

                if($this->param == 'theme')
                {
                    $htmlOptions = array_merge($htmlOptions, array('empty' => Yii::t('backend', 'Дефолтная тема (GHTWEB)')));
                }

                $field = HTML::dropDownList('Config[' . $this->param . ']', ($this->value == '' ? $this->default : $this->value), $params, $htmlOptions);

                break;
            default:
                $field = HTML::textField('Config[' . $this->param . ']', ($this->value == '' ? $this->default : $this->value), array('class' => 'form-control'));
        }

        return $field;
    }

    /**
     * Список GS
     *
     * @return array
     */
    public function getGs()
    {
        return HTML::listData(Gs::model()->findAll(), 'id', 'name');
    }

    /**
     * Возвращает типы главной страницы
     *
     * @return array
     */
    public function getIndexPageTypes()
    {
        return array(
            'page' => Yii::t('backend', 'Страница'),
            'news' => Yii::t('backend', 'Новости'),
            'rss'  => Yii::t('backend', 'RSS новости с форума'),
        );
    }

    /**
     * Список страниц
     *
     * @return array
     */
    public function getPages()
    {
        return HTML::listData(Pages::model()->not_deleted()->findAll(), 'page', 'title');
    }

    /**
     * Список платежных систем
     *
     * @return array
     */
    public function getPaymentTypes()
    {
        Yii::import('modules.deposit.extensions.Deposit.Deposit');
        $data = Deposit::getAggregatorsList();

        unset($data[Deposit::PAYMENT_SYSTEM_UNITPAY_SMS], $data[Deposit::PAYMENT_SYSTEM_WAYTOPAY_SMS]);

        return $data;
    }

    /**
     * Возвращает типы форумов
     *
     * @return array
     */
    public function getForumTypes()
    {
        $types = app()->params['forum_types'];

        return array_combine($types, $types);
    }

    /**
     * Возвращает темы
     *
     * @return array
     */
    public function getThemes()
    {
        return getTemplates();
    }
}