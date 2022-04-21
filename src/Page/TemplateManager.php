<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\PageBundle\Page;

use Sonata\PageBundle\Model\Template;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Templates management and rendering.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 *
 * @final since sonata-project/page-bundle 3.26
 */
class TemplateManager implements TemplateManagerInterface
{
    /**
     * Templating engine.
     *
     * @var Environment
     */
    protected $engine;

    /**
     * @var array
     */
    protected $defaultParameters;

    /**
     * Collection of available templates.
     *
     * @var Template[]
     */
    protected $templates;

    /**
     * Default template code.
     *
     * @var string
     */
    protected $defaultTemplateCode = 'default';

    /**
     * Default template path.
     *
     * @var string
     */
    protected $defaultTemplatePath = '@SonataPage/layout.html.twig';

    /**
     * @param Environment $engine            Templating engine
     * @param array           $defaultParameters An array of default view parameters
     */
    public function __construct(Environment $engine, array $defaultParameters = [])
    {
        $this->engine = $engine;
        $this->defaultParameters = $defaultParameters;
    }

    public function add($code, Template $template): void
    {
        $this->templates[$code] = $template;
    }

    public function get($code)
    {
        if (!isset($this->templates[$code])) {
            return;
        }

        return $this->templates[$code];
    }

    public function setDefaultTemplateCode($code): void
    {
        $this->defaultTemplateCode = $code;
    }

    public function getDefaultTemplateCode()
    {
        return $this->defaultTemplateCode;
    }

    public function setAll($templates): void
    {
        $this->templates = $templates;
    }

    public function getAll()
    {
        return $this->templates;
    }

    public function renderResponse($code, array $parameters = [], ?Response $response = null): Response
    {
        $response = $response ?? new Response();

        $response->setContent(
            $this->engine->render(
                $this->getTemplatePath($code), array_merge($this->defaultParameters, $parameters)
            )
        );

        return $response;
    }

    /**
     * Returns the template path for given code.
     *
     * @param string|null $code
     *
     * @return string
     */
    protected function getTemplatePath($code)
    {
        $code = $code ?: $this->getDefaultTemplateCode();
        $template = $this->get($code);

        return $template ? $template->getPath() : $this->defaultTemplatePath;
    }
}
