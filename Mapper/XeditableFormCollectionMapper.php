<?php

namespace Ibrows\XeditableBundle\Mapper;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class XeditableFormCollectionMapper extends AbstractFormXeditableMapper
{
    const ROUTE_KEY_EDIT = 'edit';
    const ROUTE_KEY_DELETE = 'delete';
    const ROUTE_KEY_CREATE = 'create';
    const ROUTE_KEY_DEFAULT = self::ROUTE_KEY_EDIT;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var string
     */
    protected $formProperty;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var array
     */
    protected $routeParams;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param FormInterface $form
     * @param mixed $formData
     * @param array $routeNames
     * @param array $routeParams
     * @param EngineInterface $engine
     * @param RouterInterface $router
     */
    public function __construct(FormInterface $form, $formData, array $routeNames, array $routeParams, EngineInterface $engine, RouterInterface $router)
    {
        $this->form = $form;
        $this->formData = $formData;
        $this->routeNames = $routeNames;
        $this->routeParams = $routeParams;
        $this->engine = $engine;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return Response|void
     */
    public function handleRequest(Request $request)
    {
        $form = $this->getFormByPath($request->get('path'), null, true, true);
        $form->submit($request, true);

        if ($form->isValid()) {
            return $form->getData();
        }

        return $this->renderError();
    }

    /**
     * @param string $path
     * @param null $form
     * @param bool $removeOther
     * @param bool $forceData
     * @return FormInterface
     */
    protected function getFormByPath($path, $form = null, $removeOther = true, $forceData = false)
    {
        $form = parent::getFormByPath($path, $form, $removeOther);
        if (!$form->getData() || $forceData) {
            $form->setData(array($this->formData));
            $form = $form->get("0");
            $form->setParent(null);
        }

        return $form;
    }

    /**
     * @param string $path
     * @param array $attributes
     * @param array $options
     * @throws \Exception
     * @return string
     */
    public function render($path = null, array $attributes = array(), array $options = array())
    {
        if (!$form = $this->getFormByPath($path)) {
            throw new \Exception("Path $path invalid");
        }

        $attributes = $this->getAttributes($attributes);
        $options = $this->getOptions($options);
        $values = $this->getValue($form, $options);

        if (!$values instanceof Collection) {
            throw new \Exception("Path $path not a Collection");
        }

        $viewParameters = array();
        $suboptions = $options;

        foreach ($values as $key => $value) {
            $suboptions['template'] = $this->getRenderCollectionSubFormTemplate($key, $options);
            $viewParameters[$key] = $this->getCollectionViewParameters($key, $value, $path, $attributes, $suboptions);
            $suboptions['template'] = $this->getRenderCollectionSubFormTemplate(self::ROUTE_KEY_DELETE, $options);
            $viewParameters[$key . self::ROUTE_KEY_DELETE] = $this->getCollectionViewParameters(self::ROUTE_KEY_DELETE, $value, $path, $attributes, $suboptions);
        }

        $suboptions['template'] = $this->getRenderCollectionSubFormTemplate(self::ROUTE_KEY_CREATE, $options);
        $viewParameters[self::ROUTE_KEY_CREATE] = $this->getCollectionViewParameters('create', null, $path, $attributes, $suboptions);

        return $this->engine->render($this->getRenderTemplate($options), array('path' => $path, 'attributes' => $attributes, 'options' => $options, 'viewParameters' => $viewParameters));
    }

    /**
     * @param string $path
     * @param array $attributes
     * @param array $options
     * @throws \Exception
     * @return string
     */
    public function renderXeditable($path = null, array $attributes = array(), array $options = array())
    {
        $attributes = $this->getAttributes($attributes);
        $options = $this->getOptions($options);

        if (!$form = $this->getFormByPath($path, null, true, true)) {
            throw new \Exception("Path $path invalid");
        }

        return $this->engine->render($this->getFormTemplate($options), $this->getEditParameters($form, $attributes, $options));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ibrows_xeditable_form';
    }

    /**
     * @param array $options
     * @return string
     */
    protected function getRenderTemplate(array $options = array())
    {
        return isset($options['template']) ? $options['template'] : 'IbrowsXeditableBundle::xeditablecollection.html.twig';
    }

    /**
     * @return EngineInterface
     */
    protected function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param string $key
     * @param array $options
     * @return string
     */
    protected function getRenderCollectionSubFormTemplate($key, array $options = array())
    {
        $default = 'IbrowsXeditableBundle::xeditableform.html.twig';

        if ($key === self::ROUTE_KEY_DELETE) {
            $default = 'IbrowsXeditableBundle::xeditableform_delete.html.twig';
        }

        return isset($options['template_' . $key]) ? $options['template_' . $key] : $default;
    }

    /**
     * @param string $key
     * @param object|mixed $value
     * @param string $path
     * @param array $attributes
     * @param array $options
     * @return array
     * @throws \Exception
     */
    protected function getCollectionViewParameters($key, $value, $path, array $attributes, array $options)
    {
        $attributes['id'] = 'xeditable_' . $this->form->getName() . '_' . $path . '_' . $key;

        if (array_key_exists($key, $this->routeNames)) {
            $routeName = $this->routeNames[$key];
        } else {
            $routeName = $this->routeNames[self::ROUTE_KEY_DEFAULT];
        }

        if (array_key_exists($key, $this->routeParams)) {
            $params = $this->routeParams[$key];
        } else {
            $params = $this->routeParams[self::ROUTE_KEY_DEFAULT];
        }

        if ($this->form->getData() && $this->form->getData()->getId()) {
            $params['parent'] = $this->form->getData()->getId();
        }

        if ($value && method_exists($value, 'getId')) {
            $params['id'] = $value->getId();
        }

        $params['key'] = $key;
        $params['path'] = $path;

        $this->url = $this->router->generate($routeName, $params);

        $this->formData = $value;
        if ($this->form->getData() && $this->form->getData()->getId()) {
            $routeParams['parent'] = $this->form->getData()->getId();
        }

        if ($this->getRenderFormPrototype($options)) {
            $attributes['data-form'] = $this->renderXeditable($path, $attributes, $options);
        }

        return $this->getViewParameters(
            $this->form,
            $path,
            $value,
            $attributes,
            $options
        );
    }
}