IbrowsXeditableBundle
=============================

x-editable ( http://vitalets.github.io/x-editable/ ) symfony2 forms integration


Install & setup the bundle
--------------------------

1. Add IbrowsXeditableBundle in your composer.json:

	```js
	{
	    "require": {
	        "ibrows/xeditable-bundle": "~1.0",
	    }
	}
	```

2. Now tell composer to download the bundle by running the command:

    ``` bash
    $ php composer.phar update ibrows/xeditable-bundle
    ```

    Composer will install the bundle to your project's `ibrows/xeditable-bundle` directory. ( PSR-4 )

3. Add the bundles to your `AppKernel` class

    ``` php
    // app/AppKernerl.php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Ibrows\XeditableBundle\IbrowsXeditableBundle(),
            // ...
        );
        // ...
    }
    ```

4. Include JS-Lib and CSS Files

    ```
            {% javascripts
                '@IbrowsXeditableBundle/Resources/public/javascript/bootstrap.editable-1.5.1.js'
                '@IbrowsXeditableBundle/Resources/public/javascript/xeditable.js'
            %}
                <script type="text/javascript" src="{{ asset_url }}"></script>
            {% endjavascripts %}
    ```



    ```
            {% stylesheets
                'bundles/ibrowsxeditable/css/bootstrap-editable.css'
            %}
                <link rel="stylesheet" type="text/css" media="screen" href="{{ asset_url }}" />
            {% endstylesheets %}
    ```


Basic Usage
-----------
Get the factory and wrap your form with a xeditableFormMapper

``` php
$factory = $this->get('ibrows_xeditable.mapper.factory');
$xeditableFormMapper = $factory->createFormFromRequest(
   'user_xedit', //target route where data would be sent after submit
    array('user' => $user->getId()), // parameters  for the target route
    $request, // request to get information about the current view, to find forward paramters
    new UserEditType(),  // a form type with a name and a firstName field
    $user, // form data for the form type
    array('validation_groups' => arrya('edit_user')) // form options for the form type
);
```



Then user the  xedit_inline_render function in twig to render the  xeditableFormMapper

XeditableMapperInterface $mapper, $formPath = null, array $attributes = array(), array $options = array()

```
  {{ xedit_inline_render(xeditableFormMapper, 'name', {'data-emptytext': 'userName'|trans}) }}
  {{ xedit_inline_render(xeditableFormMapper, 'firstName', {'data-emptytext': 'firstName'|trans}) }}

```

Save the xedit all request from one form in a action

``` php
        /**
         * @Route("/xedit/{user}", name="user_xedit")
         * @param User $user
         * @param Request $request
         * @return Response|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void
         */
        public function xeditAction(User $user, Request $request)
        {
            $factory = $this->get('ibrows_xeditable.mapper.factory');
            $xeditableFormMapper = $factory->createFormFromRequest(
               'user_xedit', //target route where data would be sent after submit
                array('user' => $user->getId()), // parameters  for the target route
                $request, // request to get information about the current view, to find forward paramters
                new UserEditType(),  // a form type with some fields
                $user, // form data for the form type
                array('validation_groups' => array('edit_user')) // form options for the form type
            );

            if ($request->isMethod('POST')) {
                if (($response = $xeditableFormMapper->handleRequest($request)) instanceof Response) {
                    return $response;
                }

                $em = $this->getManagerForClass($user);
                $em->persist($user);
                $em->flush();

                // after success redirect to view, so frontend can be refreshed
                return $this->redirectToForwardRoute($request, 'GET');
            }
            // get back view of the handled form, to display error messages
            return new Response($xeditableFormMapper->renderXeditable($request->get('path')));
        }
```
