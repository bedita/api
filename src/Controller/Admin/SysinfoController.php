<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace BEdita\API\Controller\Admin;

use BEdita\API\Controller\AppController;
use BEdita\API\Policy\EndpointPolicy;
use BEdita\Core\Utility\System;
use Cake\Http\Response;

/**
 * Controller for `/sysinfo` endpoint.
 *
 * @since 5.13.9
 */
class SysinfoController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->request = $this->request->withAttribute(EndpointPolicy::ADMINISTRATOR_ONLY, true);
    }

    /**
     * Show system info
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $this->request->allowMethod(['get']);
        $info = System::info();
        $this->set('_meta', compact('info'));
        $this->setSerialize([]);

        return null;
    }
}
