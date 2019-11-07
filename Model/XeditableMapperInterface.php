<?php

namespace Ibrows\XeditableBundle\Model;

interface XeditableMapperInterface
{
    /**
     * @param string $path
     * @param array $attributes
     * @param array $options
     * @return string
     */
    public function render($path = null, array $attributes = array(), array $options = array());

    /**
     * @param string $path
     * @param array $attributes
     * @param array $options
     * @return string
     */
    public function renderXeditable($path = null, array $attributes = array(), array $options = array());

    /**
     * @return string
     */
    public function getName();
}