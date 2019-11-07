<?php

namespace Ibrows\XeditableBundle\Mapper;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class XeditableMapperFactory
{
    const VALIDATE_FULL = 'full';
    const VALIDATE_EDIT_PART = 'part';
    const VALIDATE_NONE = 'none';

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var array
     */
    protected $defaultOptions = array();

    /**
     * @param FormFactoryInterface $formFactory
     * @param EngineInterface $engine
     * @param ValidatorInterface $validator
     * @param Router $router
     */
    public function __construct(FormFactoryInterface $formFactory, EngineInterface $engine, ValidatorInterface $validator, Router $router)
    {
        $this->formFactory = $formFactory;
        $this->engine = $engine;
        $this->validator = $validator;
        $this->router = $router;
        $this->defaultOptions = array(
            'csrf_protection' => false
        );
    }

    /**
     * @param Request $request
     * @return array
     */
    protected static function getForwardParameters(Request $request = null)
    {
        if ($request == null) {
            return array();
        }

        return array(
            'forwardRoute'       => $request->attributes->get('_route'),
            'forwardRouteParams' => $request->attributes->get('_route_params', array())
        );
    }

    /**
     * @param FormBuilderInterface $formBuilder
     * @return bool
     */
    protected static function removeValidationListener(FormBuilderInterface $formBuilder)
    {
        $eventDispatcher = $formBuilder->getEventDispatcher();

        foreach ($eventDispatcher->getListeners(FormEvents::POST_SUBMIT) as $listeners) {
            $listener = reset($listeners);

            if ($listener instanceof ValidationListener) {
                $eventDispatcher->removeListener(FormEvents::POST_SUBMIT, $listeners);
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $xeditableRoute
     * @param array $parameters
     * @param Request $request
     * @param string $type
     * @param mixed $data
     * @param array $options
     * @param string $validate
     * @return XeditableFormMapper
     */
    public function createFormFromRequest($xeditableRoute, $parameters = array(), Request $request = null, $type = 'form', $data = null, array $options = array(), $validate = self::VALIDATE_EDIT_PART)
    {
        $forwardParameters = static::getForwardParameters($request);
        $url = $this->router->generate($xeditableRoute, array_merge($forwardParameters, $parameters));

        return $this->createForm($url, $type, $data, $options, $validate);
    }

    /**
     * @param string $url
     * @param string $type
     * @param mixed $data
     * @param array $options
     * @param string $validate
     * @return XeditableFormMapper
     */
    public function createForm($url, $type = 'form', $data = null, array $options = array(), $validate = self::VALIDATE_EDIT_PART)
    {

        $options = array_merge($this->defaultOptions, $options);
        $builder = $this->formFactory->createBuilder($type, $data, $options);

        if ($validate == self::VALIDATE_EDIT_PART || $validate == self::VALIDATE_NONE) {
            static::removeValidationListener($builder);
        }

        $form = $builder->getForm();

        return new XeditableFormMapper(
            $form,
            $this->engine,
            $url,
            $this->validator,
            ($validate != self::VALIDATE_NONE)
        );
    }

    /**
     * @param string $defaultRoute
     * @param string|array $defaultParam
     * @param string $deleteRoute
     * @param string $deleteParam
     * @param string $createRoute
     * @param string|array $createParam
     * @param string $type
     * @param mixed $data
     * @param array $options
     * @param object $currentObject
     * @return XeditableFormCollectionMapper
     */
    public function createCollectionFormExplicit($defaultRoute, $defaultParam, $deleteRoute, $deleteParam, $createRoute, $createParam, $type = 'form', $data = null, array $options = array(), $currentObject = null)
    {
        $routeNames = array(
            XeditableFormCollectionMapper::ROUTE_KEY_DEFAULT => $defaultRoute,
            XeditableFormCollectionMapper::ROUTE_KEY_DELETE  => $deleteRoute,
            XeditableFormCollectionMapper::ROUTE_KEY_CREATE  => $createRoute,
        );

        $routeParams = array(
            XeditableFormCollectionMapper::ROUTE_KEY_DEFAULT => $defaultParam,
            XeditableFormCollectionMapper::ROUTE_KEY_DELETE  => $deleteParam,
            XeditableFormCollectionMapper::ROUTE_KEY_CREATE  => $createParam,
        );

        return $this->createCollectionForm($routeNames, $routeParams, $type, $data, $options, $currentObject);
    }

    /**
     * @param string $routeBaseName
     * @param string $type
     * @param mixed $data
     * @param object $currentObject
     * @param Request $request
     * @param array $options
     * @return XeditableFormCollectionMapper
     */
    public function createCollectionFormSimple($routeBaseName, $type = 'form', $data = null, $currentObject = null, Request $request = null, array $options = array())
    {
        $routeNames = array(
            XeditableFormCollectionMapper::ROUTE_KEY_EDIT   => $routeBaseName . '_' . XeditableFormCollectionMapper::ROUTE_KEY_EDIT,
            XeditableFormCollectionMapper::ROUTE_KEY_DELETE => $routeBaseName . '_' . XeditableFormCollectionMapper::ROUTE_KEY_DELETE,
            XeditableFormCollectionMapper::ROUTE_KEY_CREATE => $routeBaseName . '_' . XeditableFormCollectionMapper::ROUTE_KEY_CREATE,
        );
        $routeParams = array(XeditableFormCollectionMapper::ROUTE_KEY_DEFAULT => static::getForwardParameters($request));

        return $this->createCollectionForm($routeNames, $routeParams, $type, $data, $options, $currentObject);
    }

    /**
     * @param array $routeNames
     * @param array $routeParams
     * @param string $type
     * @param mixed $data
     * @param array $options
     * @param object $currentObject
     * @return XeditableFormCollectionMapper
     */
    public function createCollectionForm(array $routeNames, array $routeParams, $type = 'form', $data = null, array $options = array(), $currentObject = null)
    {
        $options = array_merge($this->defaultOptions, $options);

        return new XeditableFormCollectionMapper(
            $this->formFactory->create($type, $data, $options),
            $currentObject,
            $routeNames,
            $routeParams,
            $this->engine,
            $this->router
        );
    }
}