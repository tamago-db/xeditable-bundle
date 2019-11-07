<?php

namespace Ibrows\XeditableBundle\Mapper;

use Ibrows\XeditableBundle\Model\XeditableMapperInterface;
use Symfony\Component\Form\FormInterface;

abstract class AbstractXeditableMapper implements XeditableMapperInterface
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * @param string $url
     */
    public function __construct($url = null)
    {
        $this->url = $url;
    }

    /**
     * @param $key
     * @param $attribute
     */
    public function setAttribute($key, $attribute)
    {
        $this->attributes[$key] = $attribute;
    }

    /**
     * @param $key
     * @param $option
     */
    public function setOption($key, $option)
    {
        $this->options[$key] = $option;
    }

    /**
     * @param array $attributesToMerge
     * @return array
     */
    public function getAttributes(array $attributesToMerge = array())
    {
        return array_merge($this->attributes, $attributesToMerge);
    }

    /**
     * @param array $attributes
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param array $optionsToMerge
     * @return array
     */
    public function getOptions(array $optionsToMerge = array())
    {
        return array_merge($this->options, $optionsToMerge);
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return $this->url;
    }

    /**
     * @param FormInterface $form
     * @param string $path
     * @param string $value
     * @param array $attributes
     * @param array $options
     * @return array
     */
    protected function getViewParameters(FormInterface $form, $path, $value, array $attributes = array(), array $options = array())
    {
        $attributes = array_merge(
            array(
                'data-path' => $path,
                'data-url'  => $this->getUrl(),
                'data-type' => $this->getName(),
            ),
            $attributes
        );
        if($form->isDisabled()){
            $attributes['data-disabled'] = $form->isDisabled();
        }

        return array(
            'form'       => $form->createView(),
            'options'    => $options,
            'attributes' => $attributes,
            'value'      => $value
        );
    }
}