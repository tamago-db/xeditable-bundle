<?php

namespace Ibrows\XeditableBundle\Twig;

use Ibrows\XeditableBundle\Model\XeditableMapperInterface;

class TwigExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('xedit_inline_render', array($this, 'xeditInlineRender'), array('is_safe' => array('html')))
        );
    }

    /**
     * @param XeditableMapperInterface $mapper
     * @param string $path
     * @param array $attributes
     * @param array $options
     * @return string
     */
    public function xeditInlineRender(XeditableMapperInterface $mapper, $path = null, array $attributes = array(), array $options = array())
    {
        return $mapper->render($path, $attributes, $options);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ibrows_xeditable';
    }
}